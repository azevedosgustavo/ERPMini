<?php

class VendTableModel extends BaseModel
{
    private $partyModel;

    public function __construct()
    {
        parent::__construct();
        $this->partyModel = new DirPartyModel();
    }

    public function all()
    {
        return $this->db->fetchAll(
            'SELECT v.*, p.PartyNumber, p.Name, p.Alias, p.TaxId
             FROM VendTable v
             INNER JOIN DirPartyTable p ON p.RecId = v.PartyId
             ORDER BY p.Name ASC'
        );
    }

    public function findById($id)
    {
        $vendor = $this->db->fetchOne('SELECT * FROM VendTable WHERE RecId = ? LIMIT 1', [(int) $id]);

        if (!$vendor) {
            return null;
        }

        $vendor['Party'] = $this->partyModel->findById($vendor['PartyId']);
        $vendor['ContactPersons'] = $this->db->fetchAll(
            'SELECT * FROM VendContactPerson WHERE VendRecId = ? AND IsActive = "1" ORDER BY RecId ASC',
            [(int) $id]
        );

        $fiscalAddress = null;
        $billingAddress = null;
        foreach ($vendor['Party']['Addresses'] as $address) {
            if ($address['AddressType'] === 'F') {
                $fiscalAddress = $address;
            }

            if ($address['AddressType'] === 'B') {
                $billingAddress = $address;
            }
        }

        $vendor['BillingSameAsFiscal'] = ($fiscalAddress && $billingAddress
            && $fiscalAddress['ZipCode'] === $billingAddress['ZipCode']
            && $fiscalAddress['Street'] === $billingAddress['Street']
            && $fiscalAddress['StreetNumber'] === $billingAddress['StreetNumber']
            && $fiscalAddress['Complement'] === $billingAddress['Complement']
            && $fiscalAddress['District'] === $billingAddress['District']
            && $fiscalAddress['City'] === $billingAddress['City']
            && $fiscalAddress['State'] === $billingAddress['State']
            && $fiscalAddress['Country'] === $billingAddress['Country'])
            ? '1'
            : '0';

        return $vendor;
    }

    public function create($data, $createdBy)
    {
        $this->db->beginTransaction();

        try {
            $partyId = $this->partyModel->save($data['Party'], $createdBy);
            $now = $this->now();
            $vendorId = $this->db->insert(
                'INSERT INTO VendTable (VendAccount, PartyId, CompanyType, CurrencyCode, PaymentTermDays, VendorGroup, IsActive, IsBlocked, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $this->generateNumber('VendTable', 'VendAccount', 'VEND'),
                    (int) $partyId,
                    isset($data['CompanyType']) ? trim($data['CompanyType']) : 'JURIDICA',
                    isset($data['CurrencyCode']) ? trim($data['CurrencyCode']) : 'BRL',
                    isset($data['PaymentTermDays']) ? (int) $data['PaymentTermDays'] : 0,
                    isset($data['VendorGroup']) ? trim($data['VendorGroup']) : 'DEFAULT',
                    $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                    $this->normalizeFlag(isset($data['IsBlocked']) ? $data['IsBlocked'] : '0', '0'),
                    $now,
                    $now,
                    $createdBy
                ]
            );

            $this->syncContactPersons($vendorId, isset($data['ContactPersons']) ? $data['ContactPersons'] : [], $createdBy);

            $this->db->commit();
            return $vendorId;
        } catch (Exception $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function update($id, $data, $createdBy)
    {
        $vendor = $this->findById($id);

        if (!$vendor) {
            throw new Exception('Vendor not found.');
        }

        $this->db->beginTransaction();

        try {
            $this->partyModel->save($data['Party'], $createdBy, $vendor['PartyId']);
            $this->db->execute(
                'UPDATE VendTable SET CompanyType = ?, CurrencyCode = ?, PaymentTermDays = ?, VendorGroup = ?, IsActive = ?, IsBlocked = ?, ModifiedDateTime = ? WHERE RecId = ?',
                [
                    isset($data['CompanyType']) ? trim($data['CompanyType']) : 'JURIDICA',
                    isset($data['CurrencyCode']) ? trim($data['CurrencyCode']) : 'BRL',
                    isset($data['PaymentTermDays']) ? (int) $data['PaymentTermDays'] : 0,
                    isset($data['VendorGroup']) ? trim($data['VendorGroup']) : 'DEFAULT',
                    $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                    $this->normalizeFlag(isset($data['IsBlocked']) ? $data['IsBlocked'] : '0', '0'),
                    $this->now(),
                    (int) $id
                ]
            );

            $this->syncContactPersons((int) $id, isset($data['ContactPersons']) ? $data['ContactPersons'] : [], $createdBy);

            $this->db->commit();
        } catch (Exception $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function delete($id)
    {
        return $this->db->execute(
            'UPDATE VendTable SET IsActive = "0", IsBlocked = "1", ModifiedDateTime = ? WHERE RecId = ?',
            [$this->now(), (int) $id]
        );
    }

    private function syncContactPersons($vendorRecId, $contacts, $createdBy)
    {
        $this->db->execute('UPDATE VendContactPerson SET IsActive = "0", ModifiedDateTime = ? WHERE VendRecId = ?', [$this->now(), (int) $vendorRecId]);

        foreach ($contacts as $contact) {
            $firstName = isset($contact['FirstName']) ? trim($contact['FirstName']) : '';
            $lastName = isset($contact['LastName']) ? trim($contact['LastName']) : '';
            $contactType = isset($contact['ContactType']) ? trim($contact['ContactType']) : 'OUTROS';
            $email = isset($contact['Email']) ? trim($contact['Email']) : '';
            $phone = isset($contact['Phone']) ? trim($contact['Phone']) : '';

            if ($firstName === '' && $lastName === '' && $email === '' && $phone === '') {
                continue;
            }

            $this->db->insert(
                'INSERT INTO VendContactPerson (VendRecId, FirstName, LastName, ContactType, Email, Phone, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, "1", ?, ?, ?)',
                [
                    (int) $vendorRecId,
                    $firstName,
                    $lastName,
                    strtoupper($contactType),
                    $email,
                    $phone,
                    $this->now(),
                    $this->now(),
                    $createdBy
                ]
            );
        }
    }
}