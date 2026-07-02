<?php

class CompanyModel extends BaseModel
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
            'SELECT c.*, p.Name AS PartyName
             FROM CompanyInfo c
             LEFT JOIN DirPartyTable p ON p.RecId = c.PartyId
             WHERE c.IsActive = "1"
             ORDER BY c.IsDefault DESC, c.RecId DESC'
        );
    }

    public function findById($id)
    {
        return $this->db->fetchOne('SELECT * FROM CompanyInfo WHERE RecId = ? LIMIT 1', [(int) $id]);
    }

    public function create($data, $createdBy)
    {
        $normalized = $this->normalizeCompanyData($data);
        $partyPayload = $this->buildPartyPayload($normalized);
        $partyId = $this->partyModel->save($partyPayload, $createdBy);
        $now = $this->now();
        $isDefault = $this->normalizeFlag(isset($normalized['IsDefault']) ? $normalized['IsDefault'] : '0', '0');

        $recId = $this->db->insert(
            'INSERT INTO CompanyInfo (PartyId, Alias, LegalName, TradeName, Cnpj, FiscalZipCode, FiscalStreet, FiscalStreetNumber, FiscalComplement, FiscalDistrict, FiscalCity, FiscalState, FiscalCountry, BillingZipCode, BillingStreet, BillingStreetNumber, BillingComplement, BillingDistrict, BillingCity, BillingState, BillingCountry, BillingSameAsFiscal, MainLogoUrl, MainLogoFileName, MainLogoBase64, InitialBalance, InitialBalanceDate, IsActive, IsDefault, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                (int) $partyId,
                $normalized['Alias'],
                $normalized['LegalName'],
                $normalized['TradeName'],
                $normalized['Cnpj'],
                $normalized['FiscalZipCode'],
                $normalized['FiscalStreet'],
                $normalized['FiscalStreetNumber'],
                $normalized['FiscalComplement'],
                $normalized['FiscalDistrict'],
                $normalized['FiscalCity'],
                $normalized['FiscalState'],
                $normalized['FiscalCountry'],
                $normalized['BillingZipCode'],
                $normalized['BillingStreet'],
                $normalized['BillingStreetNumber'],
                $normalized['BillingComplement'],
                $normalized['BillingDistrict'],
                $normalized['BillingCity'],
                $normalized['BillingState'],
                $normalized['BillingCountry'],
                $this->normalizeFlag($normalized['BillingSameAsFiscal'], '0'),
                $normalized['MainLogoUrl'],
                $normalized['MainLogoFileName'],
                $normalized['MainLogoBase64'],
                $normalized['InitialBalance'],
                $normalized['InitialBalanceDate'],
                $this->normalizeFlag($normalized['IsActive'], '1'),
                $isDefault,
                $now,
                $now,
                $createdBy
            ]
        );

        $this->ensureSingleDefault((int) $recId, $isDefault);
        return $recId;
    }

    public function update($id, $data, $createdBy)
    {
        $company = $this->findById($id);

        if (!$company) {
            throw new Exception('Company not found.');
        }

        $normalized = $this->normalizeCompanyData($data);
        $partyPayload = $this->buildPartyPayload($normalized);
        $this->partyModel->save($partyPayload, $createdBy, (int) $company['PartyId']);

        $isDefault = $this->normalizeFlag(isset($normalized['IsDefault']) ? $normalized['IsDefault'] : '0', '0');

        $result = $this->db->execute(
            'UPDATE CompanyInfo SET Alias = ?, LegalName = ?, TradeName = ?, Cnpj = ?, FiscalZipCode = ?, FiscalStreet = ?, FiscalStreetNumber = ?, FiscalComplement = ?, FiscalDistrict = ?, FiscalCity = ?, FiscalState = ?, FiscalCountry = ?, BillingZipCode = ?, BillingStreet = ?, BillingStreetNumber = ?, BillingComplement = ?, BillingDistrict = ?, BillingCity = ?, BillingState = ?, BillingCountry = ?, BillingSameAsFiscal = ?, MainLogoUrl = ?, MainLogoFileName = ?, MainLogoBase64 = ?, InitialBalance = ?, InitialBalanceDate = ?, IsActive = ?, IsDefault = ?, ModifiedDateTime = ? WHERE RecId = ?',
            [
                $normalized['Alias'],
                $normalized['LegalName'],
                $normalized['TradeName'],
                $normalized['Cnpj'],
                $normalized['FiscalZipCode'],
                $normalized['FiscalStreet'],
                $normalized['FiscalStreetNumber'],
                $normalized['FiscalComplement'],
                $normalized['FiscalDistrict'],
                $normalized['FiscalCity'],
                $normalized['FiscalState'],
                $normalized['FiscalCountry'],
                $normalized['BillingZipCode'],
                $normalized['BillingStreet'],
                $normalized['BillingStreetNumber'],
                $normalized['BillingComplement'],
                $normalized['BillingDistrict'],
                $normalized['BillingCity'],
                $normalized['BillingState'],
                $normalized['BillingCountry'],
                $this->normalizeFlag($normalized['BillingSameAsFiscal'], '0'),
                $normalized['MainLogoUrl'],
                $normalized['MainLogoFileName'],
                $normalized['MainLogoBase64'],
                $normalized['InitialBalance'],
                $normalized['InitialBalanceDate'],
                $this->normalizeFlag($normalized['IsActive'], '1'),
                $isDefault,
                $this->now(),
                (int) $id
            ]
        );

        $this->ensureSingleDefault((int) $id, $isDefault);
        return $result;
    }

    public function delete($id)
    {
        $company = $this->findById($id);

        if (!$company) {
            throw new Exception('Company not found.');
        }

        $this->db->execute(
            'UPDATE CompanyInfo SET IsActive = "0", IsDefault = "0", ModifiedDateTime = ? WHERE RecId = ?',
            [$this->now(), (int) $id]
        );

        if ($this->normalizeFlag($company['IsDefault'], '0') === '1') {
            $fallback = $this->db->fetchOne('SELECT RecId FROM CompanyInfo WHERE IsActive = "1" ORDER BY RecId ASC LIMIT 1');
            if ($fallback) {
                $this->db->execute('UPDATE CompanyInfo SET IsDefault = "1", ModifiedDateTime = ? WHERE RecId = ?', [$this->now(), (int) $fallback['RecId']]);
            }
        }

        return true;
    }

    public function findDefault()
    {
        $company = $this->db->fetchOne('SELECT * FROM CompanyInfo WHERE IsActive = "1" AND IsDefault = "1" ORDER BY RecId DESC LIMIT 1');

        if ($company) {
            return $company;
        }

        return $this->db->fetchOne('SELECT * FROM CompanyInfo WHERE IsActive = "1" ORDER BY RecId ASC LIMIT 1');
    }

    private function ensureSingleDefault($companyRecId, $isDefault)
    {
        if ($isDefault === '1') {
            $this->db->execute('UPDATE CompanyInfo SET IsDefault = "0", ModifiedDateTime = ? WHERE RecId <> ?', [$this->now(), (int) $companyRecId]);
            return;
        }

        $existing = $this->db->fetchOne('SELECT RecId FROM CompanyInfo WHERE IsActive = "1" AND IsDefault = "1" LIMIT 1');
        if (!$existing) {
            $this->db->execute('UPDATE CompanyInfo SET IsDefault = "1", ModifiedDateTime = ? WHERE RecId = ?', [$this->now(), (int) $companyRecId]);
        }
    }

    private function normalizeCompanyData($data)
    {
        $normalized = [
            'Alias' => trim(isset($data['Alias']) ? $data['Alias'] : ''),
            'LegalName' => trim(isset($data['LegalName']) ? $data['LegalName'] : ''),
            'TradeName' => trim(isset($data['TradeName']) ? $data['TradeName'] : ''),
            'Cnpj' => trim(isset($data['Cnpj']) ? $data['Cnpj'] : ''),
            'FiscalZipCode' => trim(isset($data['FiscalZipCode']) ? $data['FiscalZipCode'] : ''),
            'FiscalStreet' => trim(isset($data['FiscalStreet']) ? $data['FiscalStreet'] : ''),
            'FiscalStreetNumber' => trim(isset($data['FiscalStreetNumber']) ? $data['FiscalStreetNumber'] : ''),
            'FiscalComplement' => trim(isset($data['FiscalComplement']) ? $data['FiscalComplement'] : ''),
            'FiscalDistrict' => trim(isset($data['FiscalDistrict']) ? $data['FiscalDistrict'] : ''),
            'FiscalCity' => trim(isset($data['FiscalCity']) ? $data['FiscalCity'] : ''),
            'FiscalState' => trim(isset($data['FiscalState']) ? $data['FiscalState'] : ''),
            'FiscalCountry' => trim(isset($data['FiscalCountry']) ? $data['FiscalCountry'] : 'BRASIL'),
            'BillingZipCode' => trim(isset($data['BillingZipCode']) ? $data['BillingZipCode'] : ''),
            'BillingStreet' => trim(isset($data['BillingStreet']) ? $data['BillingStreet'] : ''),
            'BillingStreetNumber' => trim(isset($data['BillingStreetNumber']) ? $data['BillingStreetNumber'] : ''),
            'BillingComplement' => trim(isset($data['BillingComplement']) ? $data['BillingComplement'] : ''),
            'BillingDistrict' => trim(isset($data['BillingDistrict']) ? $data['BillingDistrict'] : ''),
            'BillingCity' => trim(isset($data['BillingCity']) ? $data['BillingCity'] : ''),
            'BillingState' => trim(isset($data['BillingState']) ? $data['BillingState'] : ''),
            'BillingCountry' => trim(isset($data['BillingCountry']) ? $data['BillingCountry'] : 'BRASIL'),
            'BillingSameAsFiscal' => $this->normalizeFlag(isset($data['BillingSameAsFiscal']) ? $data['BillingSameAsFiscal'] : '0', '0'),
            'MainLogoUrl' => trim(isset($data['MainLogoUrl']) ? $data['MainLogoUrl'] : ''),
            'MainLogoFileName' => trim(isset($data['MainLogoFileName']) ? $data['MainLogoFileName'] : ''),
            'MainLogoBase64' => isset($data['MainLogoBase64']) ? $data['MainLogoBase64'] : null,
            'InitialBalance' => $this->normalizeDecimal(isset($data['InitialBalance']) ? $data['InitialBalance'] : 0),
            'InitialBalanceDate' => $this->normalizeDate(isset($data['InitialBalanceDate']) ? $data['InitialBalanceDate'] : ''),
            'IsActive' => $this->normalizeFlag(isset($data['IsActive']) ? $data['IsActive'] : '1', '1'),
            'IsDefault' => $this->normalizeFlag(isset($data['IsDefault']) ? $data['IsDefault'] : '0', '0')
        ];

        if ($normalized['BillingSameAsFiscal'] === '1') {
            $normalized['BillingZipCode'] = $normalized['FiscalZipCode'];
            $normalized['BillingStreet'] = $normalized['FiscalStreet'];
            $normalized['BillingStreetNumber'] = $normalized['FiscalStreetNumber'];
            $normalized['BillingComplement'] = $normalized['FiscalComplement'];
            $normalized['BillingDistrict'] = $normalized['FiscalDistrict'];
            $normalized['BillingCity'] = $normalized['FiscalCity'];
            $normalized['BillingState'] = $normalized['FiscalState'];
            $normalized['BillingCountry'] = $normalized['FiscalCountry'];
        }

        return $normalized;
    }

    private function buildPartyPayload($company)
    {
        return [
            'PartyType' => 'O',
            'Name' => $company['LegalName'] !== '' ? $company['LegalName'] : $company['TradeName'],
            'Alias' => $company['Alias'],
            'TaxId' => $company['Cnpj'],
            'IsActive' => $company['IsActive'],
            'IsBlocked' => '0',
            'Addresses' => [
                [
                    'AddressType' => 'F',
                    'ZipCode' => $company['FiscalZipCode'],
                    'Street' => $company['FiscalStreet'],
                    'StreetNumber' => $company['FiscalStreetNumber'],
                    'Complement' => $company['FiscalComplement'],
                    'District' => $company['FiscalDistrict'],
                    'City' => $company['FiscalCity'],
                    'State' => $company['FiscalState'],
                    'Country' => $company['FiscalCountry'],
                    'IsPrimary' => '1'
                ],
                [
                    'AddressType' => 'B',
                    'ZipCode' => $company['BillingZipCode'],
                    'Street' => $company['BillingStreet'],
                    'StreetNumber' => $company['BillingStreetNumber'],
                    'Complement' => $company['BillingComplement'],
                    'District' => $company['BillingDistrict'],
                    'City' => $company['BillingCity'],
                    'State' => $company['BillingState'],
                    'Country' => $company['BillingCountry'],
                    'IsPrimary' => '0'
                ]
            ],
            'Contacts' => []
        ];
    }

    private function normalizeDecimal($value)
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 0;
        }

        // Accept pt-BR and en-US decimal formats.
        $raw = str_replace(' ', '', $raw);
        if (strpos($raw, ',') !== false) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        }

        return is_numeric($raw) ? round((float) $raw, 2) : 0;
    }

    private function normalizeDate($value)
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        // Accept DD/MM/YYYY and YYYY-MM-DD payloads.
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $raw, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }

        return null;
    }
}
