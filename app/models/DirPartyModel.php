<?php

class DirPartyModel extends BaseModel
{
    public function all()
    {
        return $this->db->fetchAll(
            'SELECT p.*, pa.ZipCode, pa.City, pa.State,
                    MAX(CASE WHEN ea.Type = "E" THEN ea.Locator ELSE "" END) AS PrimaryEmail,
                    MAX(CASE WHEN ea.Type = "P" THEN ea.Locator ELSE "" END) AS PrimaryPhone
             FROM DirPartyTable p
             LEFT JOIN LogisticsPostalAddress pa ON pa.PartyId = p.RecId AND pa.IsPrimary = "1" AND pa.IsActive = "1"
             LEFT JOIN LogisticsElectronicAddress ea ON ea.PartyId = p.RecId AND ea.IsPrimary = "1" AND ea.IsActive = "1"
             GROUP BY p.RecId
             ORDER BY p.Name ASC'
        );
    }

    public function findById($id)
    {
        $party = $this->db->fetchOne('SELECT * FROM DirPartyTable WHERE RecId = ? LIMIT 1', [(int) $id]);

        if (!$party) {
            return null;
        }

        $party['Addresses'] = $this->db->fetchAll(
            'SELECT * FROM LogisticsPostalAddress WHERE PartyId = ? AND IsActive = "1" ORDER BY IsPrimary DESC, RecId ASC',
            [(int) $id]
        );
        $party['Contacts'] = $this->db->fetchAll(
            'SELECT * FROM LogisticsElectronicAddress WHERE PartyId = ? AND IsActive = "1" ORDER BY IsPrimary DESC, RecId ASC',
            [(int) $id]
        );

        return $party;
    }

    public function create($data, $createdBy)
    {
        return $this->save($data, $createdBy, null);
    }

    public function update($id, $data, $createdBy)
    {
        return $this->save($data, $createdBy, $id);
    }

    public function delete($id)
    {
        $this->db->beginTransaction();

        try {
            $this->db->execute('UPDATE DirPartyTable SET IsActive = "0", IsBlocked = "1", ModifiedDateTime = ? WHERE RecId = ?', [$this->now(), (int) $id]);
            $this->db->execute('UPDATE LogisticsPostalAddress SET IsActive = "0", ModifiedDateTime = ? WHERE PartyId = ?', [$this->now(), (int) $id]);
            $this->db->execute('UPDATE LogisticsElectronicAddress SET IsActive = "0", ModifiedDateTime = ? WHERE PartyId = ?', [$this->now(), (int) $id]);
            $this->db->commit();
        } catch (Exception $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function save($data, $createdBy, $id = null)
    {
        $now = $this->now();
        $partyType = $this->normalizePartyType($data['PartyType']);

        if ($id === null) {
            $id = $this->db->insert(
                'INSERT INTO DirPartyTable (PartyNumber, PartyType, Name, Alias, TaxId, IsActive, IsBlocked, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $this->generateNumber('DirPartyTable', 'PartyNumber', 'PTY'),
                    $partyType,
                    trim($data['Name']),
                    isset($data['Alias']) ? trim($data['Alias']) : '',
                    isset($data['TaxId']) ? trim($data['TaxId']) : '',
                    $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                    $this->normalizeFlag(isset($data['IsBlocked']) ? $data['IsBlocked'] : '0', '0'),
                    $now,
                    $now,
                    $createdBy
                ]
            );
        } else {
            $this->db->execute(
                'UPDATE DirPartyTable SET PartyType = ?, Name = ?, Alias = ?, TaxId = ?, IsActive = ?, IsBlocked = ?, ModifiedDateTime = ? WHERE RecId = ?',
                [
                    $partyType,
                    trim($data['Name']),
                    isset($data['Alias']) ? trim($data['Alias']) : '',
                    isset($data['TaxId']) ? trim($data['TaxId']) : '',
                    $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
                    $this->normalizeFlag(isset($data['IsBlocked']) ? $data['IsBlocked'] : '0', '0'),
                    $now,
                    (int) $id
                ]
            );
        }

        $this->syncAddresses($id, isset($data['Addresses']) ? $data['Addresses'] : [], $createdBy);
        $this->syncContacts($id, isset($data['Contacts']) ? $data['Contacts'] : [], $createdBy);

        return (int) $id;
    }

    private function syncAddresses($partyId, $addresses, $createdBy)
    {
        $this->db->execute('UPDATE LogisticsPostalAddress SET IsActive = "0", ModifiedDateTime = ? WHERE PartyId = ?', [$this->now(), (int) $partyId]);

        foreach ($addresses as $address) {
            $this->db->insert(
                'INSERT INTO LogisticsPostalAddress (PartyId, AddressType, ZipCode, Street, StreetNumber, Complement, District, City, State, Country, IsPrimary, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "1", ?, ?, ?)',
                [
                    (int) $partyId,
                    $this->normalizeAddressType(isset($address['AddressType']) ? $address['AddressType'] : 'O'),
                    isset($address['ZipCode']) ? trim($address['ZipCode']) : '',
                    isset($address['Street']) ? trim($address['Street']) : '',
                    isset($address['StreetNumber']) ? trim($address['StreetNumber']) : '',
                    isset($address['Complement']) ? trim($address['Complement']) : '',
                    isset($address['District']) ? trim($address['District']) : '',
                    isset($address['City']) ? trim($address['City']) : '',
                    isset($address['State']) ? trim($address['State']) : '',
                    isset($address['Country']) ? trim($address['Country']) : 'BRASIL',
                    $this->normalizeFlag(isset($address['IsPrimary']) ? $address['IsPrimary'] : '0', '0'),
                    $this->now(),
                    $this->now(),
                    $createdBy
                ]
            );
        }
    }

    private function syncContacts($partyId, $contacts, $createdBy)
    {
        $this->db->execute('UPDATE LogisticsElectronicAddress SET IsActive = "0", ModifiedDateTime = ? WHERE PartyId = ?', [$this->now(), (int) $partyId]);

        foreach ($contacts as $contact) {
            $this->db->insert(
                'INSERT INTO LogisticsElectronicAddress (PartyId, Type, Locator, ContactRole, IsPrimary, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, "1", ?, ?, ?)',
                [
                    (int) $partyId,
                    $this->normalizeContactType($contact['Type']),
                    isset($contact['Locator']) ? trim($contact['Locator']) : '',
                    isset($contact['ContactRole']) ? trim($contact['ContactRole']) : '',
                    $this->normalizeFlag(isset($contact['IsPrimary']) ? $contact['IsPrimary'] : '0', '0'),
                    $this->now(),
                    $this->now(),
                    $createdBy
                ]
            );
        }
    }

    private function normalizePartyType($value)
    {
        $normalized = strtoupper(substr(trim($value), 0, 1));
        return in_array($normalized, ['P', 'O', 'F']) ? $normalized : 'O';
    }

    private function normalizeContactType($value)
    {
        $normalized = strtoupper(substr(trim($value), 0, 1));
        return $normalized === 'P' ? 'P' : 'E';
    }

    private function normalizeAddressType($value)
    {
        $normalized = strtoupper(substr(trim((string) $value), 0, 1));
        return in_array($normalized, ['F', 'B', 'O']) ? $normalized : 'O';
    }
}