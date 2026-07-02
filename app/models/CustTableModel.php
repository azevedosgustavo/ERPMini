<?php

class CustTableModel extends BaseModel
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
            'SELECT c.*, p.PartyNumber, p.Name, p.Alias, p.TaxId
             FROM CustTable c
             INNER JOIN DirPartyTable p ON p.RecId = c.PartyId
             ORDER BY p.Name ASC'
        );
    }

    public function findById($id)
    {
        $customer = $this->db->fetchOne('SELECT * FROM CustTable WHERE RecId = ? LIMIT 1', [(int) $id]);

        if (!$customer) {
            return null;
        }

        $customer['Party'] = $this->partyModel->findById($customer['PartyId']);
        $customer['ContactPersons'] = $this->db->fetchAll(
            'SELECT * FROM CustContactPerson WHERE CustRecId = ? AND IsActive = "1" ORDER BY RecId ASC',
            [(int) $id]
        );
        return $customer;
    }

    public function create($data, $createdBy)
    {
        $this->db->beginTransaction();

        try {
            $partyId = $this->partyModel->save($data['Party'], $createdBy);
            $now = $this->now();
            $customerId = $this->db->insert(
                'INSERT INTO CustTable (CustAccount, PartyId, CompanyType, CurrencyCode, CreditLimit, PaymentTermDays, CustomerGroup, IsActive, IsBlocked, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $this->generateNumber('CustTable', 'CustAccount', 'CUST'),
                    (int) $partyId,
                    isset($data['CompanyType']) ? trim($data['CompanyType']) : 'JURIDICA',
                    isset($data['CurrencyCode']) ? trim($data['CurrencyCode']) : 'BRL',
                    $this->normalizeAmount(isset($data['CreditLimit']) ? $data['CreditLimit'] : 0),
                    isset($data['PaymentTermDays']) ? (int) $data['PaymentTermDays'] : 0,
                    isset($data['CustomerGroup']) ? trim($data['CustomerGroup']) : 'DEFAULT',
                    $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                    $this->normalizeFlag(isset($data['IsBlocked']) ? $data['IsBlocked'] : '0', '0'),
                    $now,
                    $now,
                    $createdBy
                ]
            );

            $this->syncContactPersons($customerId, isset($data['ContactPersons']) ? $data['ContactPersons'] : [], $createdBy);

            $this->db->commit();
            return $customerId;
        } catch (Exception $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function update($id, $data, $createdBy)
    {
        $customer = $this->findById($id);

        if (!$customer) {
            throw new Exception('Customer not found.');
        }

        $this->db->beginTransaction();

        try {
            $this->partyModel->save($data['Party'], $createdBy, $customer['PartyId']);
            $this->db->execute(
                'UPDATE CustTable SET CompanyType = ?, CurrencyCode = ?, CreditLimit = ?, PaymentTermDays = ?, CustomerGroup = ?, IsActive = ?, IsBlocked = ?, ModifiedDateTime = ? WHERE RecId = ?',
                [
                    isset($data['CompanyType']) ? trim($data['CompanyType']) : 'JURIDICA',
                    isset($data['CurrencyCode']) ? trim($data['CurrencyCode']) : 'BRL',
                    $this->normalizeAmount(isset($data['CreditLimit']) ? $data['CreditLimit'] : 0),
                    isset($data['PaymentTermDays']) ? (int) $data['PaymentTermDays'] : 0,
                    isset($data['CustomerGroup']) ? trim($data['CustomerGroup']) : 'DEFAULT',
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
            'UPDATE CustTable SET IsActive = "0", IsBlocked = "1", ModifiedDateTime = ? WHERE RecId = ?',
            [$this->now(), (int) $id]
        );
    }

    private function syncContactPersons($customerRecId, $contacts, $createdBy)
    {
        $this->db->execute('UPDATE CustContactPerson SET IsActive = "0", ModifiedDateTime = ? WHERE CustRecId = ?', [$this->now(), (int) $customerRecId]);

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
                'INSERT INTO CustContactPerson (CustRecId, FirstName, LastName, ContactType, Email, Phone, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, "1", ?, ?, ?)',
                [
                    (int) $customerRecId,
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