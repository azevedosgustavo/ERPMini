<?php

require_once __DIR__ . '/../app/bootstrap.php';

function tableExists($connection, $tableName)
{
    $database = $GLOBALS['app_config']['db']['database'];
    $stmt = mysqli_prepare($connection, 'SELECT COUNT(*) AS Qty FROM information_schema.tables WHERE table_schema = ? AND table_name = ?');
    mysqli_stmt_bind_param($stmt, 'ss', $database, $tableName);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $qty);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return ((int) $qty) > 0;
}

function columnExists($connection, $tableName, $columnName)
{
    $database = $GLOBALS['app_config']['db']['database'];
    $stmt = mysqli_prepare($connection, 'SELECT COUNT(*) AS Qty FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?');
    mysqli_stmt_bind_param($stmt, 'sss', $database, $tableName, $columnName);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $qty);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return ((int) $qty) > 0;
}

function indexExists($connection, $tableName, $indexName)
{
    $database = $GLOBALS['app_config']['db']['database'];
    $stmt = mysqli_prepare($connection, 'SELECT COUNT(*) AS Qty FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?');
    mysqli_stmt_bind_param($stmt, 'sss', $database, $tableName, $indexName);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $qty);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return ((int) $qty) > 0;
}

function fkExists($connection, $constraintName)
{
    $database = $GLOBALS['app_config']['db']['database'];
    $stmt = mysqli_prepare($connection, 'SELECT COUNT(*) AS Qty FROM information_schema.table_constraints WHERE table_schema = ? AND constraint_name = ? AND constraint_type = "FOREIGN KEY"');
    mysqli_stmt_bind_param($stmt, 'ss', $database, $constraintName);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $qty);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return ((int) $qty) > 0;
}

function runSql($connection, $sql)
{
    if (!mysqli_query($connection, $sql)) {
        throw new Exception(mysqli_error($connection));
    }
}

try {
    $connection = Connection::getInstance();

    if (!tableExists($connection, 'SysLanguage')) {
        runSql($connection, 'CREATE TABLE SysLanguage (
            RecId INT NOT NULL AUTO_INCREMENT,
            LanguageId VARCHAR(10) NOT NULL,
            Name VARCHAR(80) NOT NULL,
            IsActive CHAR(1) NOT NULL DEFAULT "1",
            CreatedDateTime DATETIME NOT NULL,
            ModifiedDateTime DATETIME NOT NULL,
            CreatedBy VARCHAR(60) NOT NULL,
            PRIMARY KEY (RecId),
            UNIQUE KEY UX_SysLanguage_LanguageId (LanguageId)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
    }

    if (!tableExists($connection, 'SysLabelText')) {
        runSql($connection, 'CREATE TABLE SysLabelText (
            RecId INT NOT NULL AUTO_INCREMENT,
            LabelKey VARCHAR(120) NOT NULL,
            LanguageId VARCHAR(10) NOT NULL,
            TextValue VARCHAR(255) NOT NULL,
            IsActive CHAR(1) NOT NULL DEFAULT "1",
            CreatedDateTime DATETIME NOT NULL,
            ModifiedDateTime DATETIME NOT NULL,
            CreatedBy VARCHAR(60) NOT NULL,
            PRIMARY KEY (RecId),
            UNIQUE KEY UX_SysLabelText_LabelLanguage (LabelKey, LanguageId),
            KEY IX_SysLabelText_LanguageId (LanguageId),
            CONSTRAINT FK_SysLabelText_SysLanguage FOREIGN KEY (LanguageId) REFERENCES SysLanguage (LanguageId)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
    }

    if (!tableExists($connection, 'SysMenuGroup')) {
        runSql($connection, 'CREATE TABLE SysMenuGroup (
            RecId INT NOT NULL AUTO_INCREMENT,
            GroupCode VARCHAR(40) NOT NULL,
            LabelKey VARCHAR(120) NOT NULL,
            SequenceNo INT NOT NULL DEFAULT 0,
            IsActive CHAR(1) NOT NULL DEFAULT "1",
            CreatedDateTime DATETIME NOT NULL,
            ModifiedDateTime DATETIME NOT NULL,
            CreatedBy VARCHAR(60) NOT NULL,
            PRIMARY KEY (RecId),
            UNIQUE KEY UX_SysMenuGroup_GroupCode (GroupCode)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
    }

    if (!tableExists($connection, 'SysMenuItem')) {
        runSql($connection, 'CREATE TABLE SysMenuItem (
            RecId INT NOT NULL AUTO_INCREMENT,
            GroupId INT NOT NULL,
            ParentMenuId INT NOT NULL DEFAULT 0,
            MenuCode VARCHAR(50) NOT NULL,
            LabelKey VARCHAR(120) NOT NULL,
            ViewKey VARCHAR(60) NOT NULL,
            SequenceNo INT NOT NULL DEFAULT 0,
            IsActive CHAR(1) NOT NULL DEFAULT "1",
            CreatedDateTime DATETIME NOT NULL,
            ModifiedDateTime DATETIME NOT NULL,
            CreatedBy VARCHAR(60) NOT NULL,
            PRIMARY KEY (RecId),
            UNIQUE KEY UX_SysMenuItem_MenuCode (MenuCode),
            KEY IX_SysMenuItem_GroupId (GroupId),
            KEY IX_SysMenuItem_ParentMenuId (ParentMenuId),
            CONSTRAINT FK_SysMenuItem_SysMenuGroup FOREIGN KEY (GroupId) REFERENCES SysMenuGroup (RecId)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
    }

    if (!tableExists($connection, 'CompanyInfo')) {
        runSql($connection, 'CREATE TABLE CompanyInfo (
            RecId INT NOT NULL AUTO_INCREMENT,
            PartyId INT NOT NULL,
            Alias VARCHAR(120) NOT NULL,
            LegalName VARCHAR(200) NOT NULL,
            TradeName VARCHAR(200) NOT NULL,
            Cnpj VARCHAR(30) NOT NULL,
            FiscalZipCode VARCHAR(20) NOT NULL,
            FiscalStreet VARCHAR(160) NOT NULL,
            FiscalStreetNumber VARCHAR(30) NOT NULL,
            FiscalComplement VARCHAR(120) NOT NULL,
            FiscalDistrict VARCHAR(120) NOT NULL,
            FiscalCity VARCHAR(120) NOT NULL,
            FiscalState VARCHAR(60) NOT NULL,
            BillingZipCode VARCHAR(20) NOT NULL,
            BillingStreet VARCHAR(160) NOT NULL,
            BillingStreetNumber VARCHAR(30) NOT NULL,
            BillingComplement VARCHAR(120) NOT NULL,
            BillingDistrict VARCHAR(120) NOT NULL,
            BillingCity VARCHAR(120) NOT NULL,
            BillingState VARCHAR(60) NOT NULL,
            FiscalCountry VARCHAR(80) NOT NULL,
            BillingCountry VARCHAR(80) NOT NULL,
            BillingSameAsFiscal CHAR(1) NOT NULL DEFAULT "0",
            MainLogoUrl VARCHAR(255) NOT NULL,
            MainLogoFileName VARCHAR(255) NOT NULL,
            MainLogoBase64 LONGTEXT NULL,
            IsActive CHAR(1) NOT NULL DEFAULT "1",
            IsDefault CHAR(1) NOT NULL DEFAULT "0",
            CreatedDateTime DATETIME NOT NULL,
            ModifiedDateTime DATETIME NOT NULL,
            CreatedBy VARCHAR(60) NOT NULL,
            PRIMARY KEY (RecId),
            UNIQUE KEY UX_CompanyInfo_Cnpj (Cnpj),
            KEY IX_CompanyInfo_PartyId (PartyId),
            KEY IX_CompanyInfo_IsDefault (IsDefault),
            CONSTRAINT FK_CompanyInfo_DirPartyTable FOREIGN KEY (PartyId) REFERENCES DirPartyTable (RecId)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
    }

    $companyColumns = [
        'PartyId' => 'ALTER TABLE CompanyInfo ADD COLUMN PartyId INT NOT NULL DEFAULT 0 AFTER RecId',
        'FiscalZipCode' => 'ALTER TABLE CompanyInfo ADD COLUMN FiscalZipCode VARCHAR(20) NOT NULL DEFAULT "" AFTER Cnpj',
        'FiscalStreet' => 'ALTER TABLE CompanyInfo ADD COLUMN FiscalStreet VARCHAR(160) NOT NULL DEFAULT "" AFTER FiscalZipCode',
        'FiscalStreetNumber' => 'ALTER TABLE CompanyInfo ADD COLUMN FiscalStreetNumber VARCHAR(30) NOT NULL DEFAULT "" AFTER FiscalStreet',
        'FiscalComplement' => 'ALTER TABLE CompanyInfo ADD COLUMN FiscalComplement VARCHAR(120) NOT NULL DEFAULT "" AFTER FiscalStreetNumber',
        'FiscalDistrict' => 'ALTER TABLE CompanyInfo ADD COLUMN FiscalDistrict VARCHAR(120) NOT NULL DEFAULT "" AFTER FiscalComplement',
        'FiscalCity' => 'ALTER TABLE CompanyInfo ADD COLUMN FiscalCity VARCHAR(120) NOT NULL DEFAULT "" AFTER FiscalDistrict',
        'FiscalState' => 'ALTER TABLE CompanyInfo ADD COLUMN FiscalState VARCHAR(60) NOT NULL DEFAULT "" AFTER FiscalCity',
        'BillingZipCode' => 'ALTER TABLE CompanyInfo ADD COLUMN BillingZipCode VARCHAR(20) NOT NULL DEFAULT "" AFTER FiscalState',
        'BillingStreet' => 'ALTER TABLE CompanyInfo ADD COLUMN BillingStreet VARCHAR(160) NOT NULL DEFAULT "" AFTER BillingZipCode',
        'BillingStreetNumber' => 'ALTER TABLE CompanyInfo ADD COLUMN BillingStreetNumber VARCHAR(30) NOT NULL DEFAULT "" AFTER BillingStreet',
        'BillingComplement' => 'ALTER TABLE CompanyInfo ADD COLUMN BillingComplement VARCHAR(120) NOT NULL DEFAULT "" AFTER BillingStreetNumber',
        'BillingDistrict' => 'ALTER TABLE CompanyInfo ADD COLUMN BillingDistrict VARCHAR(120) NOT NULL DEFAULT "" AFTER BillingComplement',
        'BillingCity' => 'ALTER TABLE CompanyInfo ADD COLUMN BillingCity VARCHAR(120) NOT NULL DEFAULT "" AFTER BillingDistrict',
        'BillingState' => 'ALTER TABLE CompanyInfo ADD COLUMN BillingState VARCHAR(60) NOT NULL DEFAULT "" AFTER BillingCity',
        'FiscalCountry' => 'ALTER TABLE CompanyInfo ADD COLUMN FiscalCountry VARCHAR(80) NOT NULL DEFAULT "BRASIL" AFTER FiscalState',
        'BillingCountry' => 'ALTER TABLE CompanyInfo ADD COLUMN BillingCountry VARCHAR(80) NOT NULL DEFAULT "BRASIL" AFTER BillingState',
        'BillingSameAsFiscal' => 'ALTER TABLE CompanyInfo ADD COLUMN BillingSameAsFiscal CHAR(1) NOT NULL DEFAULT "0" AFTER BillingCountry',
        'MainLogoFileName' => 'ALTER TABLE CompanyInfo ADD COLUMN MainLogoFileName VARCHAR(255) NOT NULL DEFAULT "" AFTER MainLogoUrl',
        'MainLogoBase64' => 'ALTER TABLE CompanyInfo ADD COLUMN MainLogoBase64 LONGTEXT NULL AFTER MainLogoFileName',
        'IsDefault' => 'ALTER TABLE CompanyInfo ADD COLUMN IsDefault CHAR(1) NOT NULL DEFAULT "0" AFTER IsActive'
    ];

    foreach ($companyColumns as $columnName => $sql) {
        if (!columnExists($connection, 'CompanyInfo', $columnName)) {
            runSql($connection, $sql);
        }
    }

    if (columnExists($connection, 'CompanyInfo', 'IsBlocked')) {
        runSql($connection, 'UPDATE CompanyInfo SET IsBlocked = "0"');
    }

    if (!indexExists($connection, 'CompanyInfo', 'IX_CompanyInfo_PartyId')) {
        runSql($connection, 'ALTER TABLE CompanyInfo ADD KEY IX_CompanyInfo_PartyId (PartyId)');
    }

    if (!indexExists($connection, 'CompanyInfo', 'IX_CompanyInfo_IsDefault')) {
        runSql($connection, 'ALTER TABLE CompanyInfo ADD KEY IX_CompanyInfo_IsDefault (IsDefault)');
    }

    // Ensure postal address extended columns exist before company->party backfill inserts.
    if (!columnExists($connection, 'LogisticsPostalAddress', 'AddressType')) {
        runSql($connection, 'ALTER TABLE LogisticsPostalAddress ADD COLUMN AddressType CHAR(1) NOT NULL DEFAULT "O" AFTER PartyId');
    }

    if (!columnExists($connection, 'LogisticsPostalAddress', 'Country')) {
        runSql($connection, 'ALTER TABLE LogisticsPostalAddress ADD COLUMN Country VARCHAR(80) NOT NULL DEFAULT "BRASIL" AFTER State');
    }

    if (tableExists($connection, 'CompanyInfo') && tableExists($connection, 'DirPartyTable')) {
        $companiesResult = mysqli_query($connection, 'SELECT * FROM CompanyInfo WHERE PartyId IS NULL OR PartyId = 0');

        if ($companiesResult) {
            while ($company = mysqli_fetch_assoc($companiesResult)) {
                $now = date('Y-m-d H:i:s');
                $partyNumber = 'CMP' . str_pad((string) $company['RecId'], 5, '0', STR_PAD_LEFT);
                $partyName = trim($company['LegalName']) !== '' ? trim($company['LegalName']) : trim($company['TradeName']);
                $partyAlias = trim($company['Alias']);
                $partyTax = trim($company['Cnpj']);
                $partyActive = isset($company['IsActive']) && $company['IsActive'] === '0' ? '0' : '1';

                $stmt = mysqli_prepare($connection, 'INSERT INTO DirPartyTable (PartyNumber, PartyType, Name, Alias, TaxId, IsActive, IsBlocked, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, "O", ?, ?, ?, ?, "0", ?, ?, "SYSTEM")');
                mysqli_stmt_bind_param($stmt, 'sssssss', $partyNumber, $partyName, $partyAlias, $partyTax, $partyActive, $now, $now);
                mysqli_stmt_execute($stmt);
                $partyId = mysqli_insert_id($connection);
                mysqli_stmt_close($stmt);

                if ($partyId > 0) {
                    $stmtUpdate = mysqli_prepare($connection, 'UPDATE CompanyInfo SET PartyId = ? WHERE RecId = ?');
                    $companyRecId = (int) $company['RecId'];
                    mysqli_stmt_bind_param($stmtUpdate, 'ii', $partyId, $companyRecId);
                    mysqli_stmt_execute($stmtUpdate);
                    mysqli_stmt_close($stmtUpdate);

                    $fiscalCountry = columnExists($connection, 'CompanyInfo', 'FiscalCountry') ? (isset($company['FiscalCountry']) ? $company['FiscalCountry'] : 'BRASIL') : 'BRASIL';
                    $billingCountry = columnExists($connection, 'CompanyInfo', 'BillingCountry') ? (isset($company['BillingCountry']) ? $company['BillingCountry'] : 'BRASIL') : 'BRASIL';

                    runSql($connection, 'INSERT INTO LogisticsPostalAddress (PartyId, AddressType, ZipCode, Street, StreetNumber, Complement, District, City, State, Country, IsPrimary, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
                        VALUES (' . (int) $partyId . ', "F", ' . "'" . mysqli_real_escape_string($connection, (string) $company['FiscalZipCode']) . "'" . ', ' . "'" . mysqli_real_escape_string($connection, (string) $company['FiscalStreet']) . "'" . ', ' . "'" . mysqli_real_escape_string($connection, (string) $company['FiscalStreetNumber']) . "'" . ', ' . "'" . mysqli_real_escape_string($connection, (string) $company['FiscalComplement']) . "'" . ', ' . "'" . mysqli_real_escape_string($connection, (string) $company['FiscalDistrict']) . "'" . ', ' . "'" . mysqli_real_escape_string($connection, (string) $company['FiscalCity']) . "'" . ', ' . "'" . mysqli_real_escape_string($connection, (string) $company['FiscalState']) . "'" . ', ' . "'" . mysqli_real_escape_string($connection, (string) $fiscalCountry) . "'" . ', "1", "1", NOW(), NOW(), "SYSTEM")');

                    runSql($connection, 'INSERT INTO LogisticsPostalAddress (PartyId, AddressType, ZipCode, Street, StreetNumber, Complement, District, City, State, Country, IsPrimary, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
                        VALUES (' . (int) $partyId . ', "B", ' . "'" . mysqli_real_escape_string($connection, (string) $company['BillingZipCode']) . "'" . ', ' . "'" . mysqli_real_escape_string($connection, (string) $company['BillingStreet']) . "'" . ', ' . "'" . mysqli_real_escape_string($connection, (string) $company['BillingStreetNumber']) . "'" . ', ' . "'" . mysqli_real_escape_string($connection, (string) $company['BillingComplement']) . "'" . ', ' . "'" . mysqli_real_escape_string($connection, (string) $company['BillingDistrict']) . "'" . ', ' . "'" . mysqli_real_escape_string($connection, (string) $company['BillingCity']) . "'" . ', ' . "'" . mysqli_real_escape_string($connection, (string) $company['BillingState']) . "'" . ', ' . "'" . mysqli_real_escape_string($connection, (string) $billingCountry) . "'" . ', "0", "1", NOW(), NOW(), "SYSTEM")');
                }
            }

            mysqli_free_result($companiesResult);
        }
    }

    if (tableExists($connection, 'CompanyInfo') && !fkExists($connection, 'FK_CompanyInfo_DirPartyTable')) {
        runSql($connection, 'ALTER TABLE CompanyInfo ADD CONSTRAINT FK_CompanyInfo_DirPartyTable FOREIGN KEY (PartyId) REFERENCES DirPartyTable (RecId)');
    }

    if (!columnExists($connection, 'CustTable', 'CompanyType')) {
        runSql($connection, 'ALTER TABLE CustTable ADD COLUMN CompanyType VARCHAR(40) NOT NULL DEFAULT "JURIDICA" AFTER PartyId');
    }

    if (!columnExists($connection, 'VendTable', 'CompanyType')) {
        runSql($connection, 'ALTER TABLE VendTable ADD COLUMN CompanyType VARCHAR(40) NOT NULL DEFAULT "JURIDICA" AFTER PartyId');
    }

    if (!tableExists($connection, 'CustContactPerson')) {
        runSql($connection, 'CREATE TABLE CustContactPerson (
            RecId INT NOT NULL AUTO_INCREMENT,
            CustRecId INT NOT NULL,
            FirstName VARCHAR(120) NOT NULL,
            LastName VARCHAR(120) NOT NULL,
            ContactType VARCHAR(20) NOT NULL,
            Email VARCHAR(160) NOT NULL,
            Phone VARCHAR(60) NOT NULL,
            IsActive CHAR(1) NOT NULL DEFAULT "1",
            CreatedDateTime DATETIME NOT NULL,
            ModifiedDateTime DATETIME NOT NULL,
            CreatedBy VARCHAR(60) NOT NULL,
            PRIMARY KEY (RecId),
            KEY IX_CustContactPerson_CustRecId (CustRecId),
            CONSTRAINT FK_CustContactPerson_CustTable FOREIGN KEY (CustRecId) REFERENCES CustTable (RecId)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
    }

    if (!tableExists($connection, 'VendContactPerson')) {
        runSql($connection, 'CREATE TABLE VendContactPerson (
            RecId INT NOT NULL AUTO_INCREMENT,
            VendRecId INT NOT NULL,
            FirstName VARCHAR(120) NOT NULL,
            LastName VARCHAR(120) NOT NULL,
            ContactType VARCHAR(20) NOT NULL,
            Email VARCHAR(160) NOT NULL,
            Phone VARCHAR(60) NOT NULL,
            IsActive CHAR(1) NOT NULL DEFAULT "1",
            CreatedDateTime DATETIME NOT NULL,
            ModifiedDateTime DATETIME NOT NULL,
            CreatedBy VARCHAR(60) NOT NULL,
            PRIMARY KEY (RecId),
            KEY IX_VendContactPerson_VendRecId (VendRecId),
            CONSTRAINT FK_VendContactPerson_VendTable FOREIGN KEY (VendRecId) REFERENCES VendTable (RecId)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
    }

    if (!tableExists($connection, 'SysNumberSequenceTable')) {
        runSql($connection, 'CREATE TABLE SysNumberSequenceTable (
            RecId INT NOT NULL AUTO_INCREMENT,
            ObjectCode VARCHAR(40) NOT NULL,
            ObjectName VARCHAR(120) NOT NULL,
            CurrentNumber INT NOT NULL DEFAULT 0,
            NextNumber INT NOT NULL DEFAULT 1,
            FormatMask VARCHAR(40) NOT NULL,
            IsActive CHAR(1) NOT NULL DEFAULT "1",
            CreatedDateTime DATETIME NOT NULL,
            ModifiedDateTime DATETIME NOT NULL,
            CreatedBy VARCHAR(60) NOT NULL,
            PRIMARY KEY (RecId),
            UNIQUE KEY UX_SysNumberSequenceTable_ObjectCode (ObjectCode)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
    }

    if (tableExists($connection, 'PurchTable') && !columnExists($connection, 'PurchTable', 'PurchType')) {
        runSql($connection, 'ALTER TABLE PurchTable ADD COLUMN PurchType CHAR(1) NOT NULL DEFAULT "I" AFTER PurchId');
    }

    if (tableExists($connection, 'PurchLine') && !columnExists($connection, 'PurchLine', 'ServiceCodeId')) {
        runSql($connection, 'ALTER TABLE PurchLine ADD COLUMN ServiceCodeId INT NULL AFTER ItemId');
    }

    if (tableExists($connection, 'PurchLine')) {
        runSql($connection, 'ALTER TABLE PurchLine MODIFY COLUMN ItemId VARCHAR(30) NULL');
    }

    if (tableExists($connection, 'PurchLine') && !indexExists($connection, 'PurchLine', 'IX_PurchLine_ServiceCodeId')) {
        runSql($connection, 'ALTER TABLE PurchLine ADD KEY IX_PurchLine_ServiceCodeId (ServiceCodeId)');
    }

    if (tableExists($connection, 'PurchLine') && !fkExists($connection, 'FK_PurchLine_ServiceCodeTable')) {
        runSql($connection, 'ALTER TABLE PurchLine ADD CONSTRAINT FK_PurchLine_ServiceCodeTable FOREIGN KEY (ServiceCodeId) REFERENCES ServiceCodeTable (RecId)');
    }

    if (!tableExists($connection, 'TaxTypeTable')) {
        runSql($connection, 'CREATE TABLE TaxTypeTable (
            RecId INT NOT NULL AUTO_INCREMENT,
            TaxTypeCode VARCHAR(30) NOT NULL,
            Name VARCHAR(160) NOT NULL,
            Description VARCHAR(500) NOT NULL,
            IsActive CHAR(1) NOT NULL DEFAULT "1",
            IsBlocked CHAR(1) NOT NULL DEFAULT "0",
            CreatedDateTime DATETIME NOT NULL,
            ModifiedDateTime DATETIME NOT NULL,
            CreatedBy VARCHAR(60) NOT NULL,
            PRIMARY KEY (RecId),
            UNIQUE KEY UX_TaxTypeTable_TaxTypeCode (TaxTypeCode)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
    }

    if (!tableExists($connection, 'BankAccountTable')) {
        runSql($connection, 'CREATE TABLE BankAccountTable (
            RecId INT NOT NULL AUTO_INCREMENT,
            BankName VARCHAR(120) NOT NULL,
            AccountNumber VARCHAR(40) NOT NULL,
            AccountDigit VARCHAR(8) NOT NULL,
            Description VARCHAR(255) NOT NULL,
            IsActive CHAR(1) NOT NULL DEFAULT "1",
            IsBlocked CHAR(1) NOT NULL DEFAULT "0",
            CreatedDateTime DATETIME NOT NULL,
            ModifiedDateTime DATETIME NOT NULL,
            CreatedBy VARCHAR(60) NOT NULL,
            PRIMARY KEY (RecId)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
    }

    if (!tableExists($connection, 'DocuAttachment')) {
        runSql($connection, 'CREATE TABLE DocuAttachment (
            RecId INT NOT NULL AUTO_INCREMENT,
            EntityName VARCHAR(60) NOT NULL,
            RecordRecId INT NOT NULL,
            LineEntityName VARCHAR(60) NOT NULL DEFAULT "",
            LineRecId INT NOT NULL DEFAULT 0,
            FileName VARCHAR(255) NOT NULL,
            MimeType VARCHAR(120) NOT NULL,
            FileContentBase64 LONGTEXT NOT NULL,
            Notes VARCHAR(255) NOT NULL,
            IsActive CHAR(1) NOT NULL DEFAULT "1",
            CreatedDateTime DATETIME NOT NULL,
            ModifiedDateTime DATETIME NOT NULL,
            CreatedBy VARCHAR(60) NOT NULL,
            PRIMARY KEY (RecId),
            KEY IX_DocuAttachment_EntityRecord (EntityName, RecordRecId),
            KEY IX_DocuAttachment_Line (LineEntityName, LineRecId)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
    }

    if (tableExists($connection, 'CustInvoiceJour') && !columnExists($connection, 'CustInvoiceJour', 'IsInternationalReplacement')) {
        runSql($connection, 'ALTER TABLE CustInvoiceJour ADD COLUMN IsInternationalReplacement CHAR(1) NOT NULL DEFAULT "0" AFTER DeductionAmount');
    }

    if (tableExists($connection, 'CustInvoiceJour') && !columnExists($connection, 'CustInvoiceJour', 'CompanyRecId')) {
        runSql($connection, 'ALTER TABLE CustInvoiceJour ADD COLUMN CompanyRecId INT NOT NULL DEFAULT 0 AFTER InvoiceId');
    }

    if (tableExists($connection, 'CustInvoiceJour') && !columnExists($connection, 'CustInvoiceJour', 'CompanyAlias')) {
        runSql($connection, 'ALTER TABLE CustInvoiceJour ADD COLUMN CompanyAlias VARCHAR(120) NOT NULL DEFAULT "" AFTER CompanyRecId');
    }
    if (tableExists($connection, 'CustInvoiceJour') && !columnExists($connection, 'CustInvoiceJour', 'InternationalInvoiceNumber')) {
        runSql($connection, 'ALTER TABLE CustInvoiceJour ADD COLUMN InternationalInvoiceNumber VARCHAR(80) NOT NULL DEFAULT "" AFTER IsInternationalReplacement');
    }
    if (tableExists($connection, 'CustInvoiceJour') && !columnExists($connection, 'CustInvoiceJour', 'InternationalInvoiceSeries')) {
        runSql($connection, 'ALTER TABLE CustInvoiceJour ADD COLUMN InternationalInvoiceSeries VARCHAR(40) NOT NULL DEFAULT "" AFTER InternationalInvoiceNumber');
    }
    if (tableExists($connection, 'CustInvoiceJour') && !columnExists($connection, 'CustInvoiceJour', 'InternationalInvoiceDate')) {
        runSql($connection, 'ALTER TABLE CustInvoiceJour ADD COLUMN InternationalInvoiceDate DATETIME NULL AFTER InternationalInvoiceSeries');
    }

    if (tableExists($connection, 'LedgerJournalTable') && !columnExists($connection, 'LedgerJournalTable', 'JournalType')) {
        runSql($connection, 'ALTER TABLE LedgerJournalTable ADD COLUMN JournalType CHAR(3) NOT NULL DEFAULT "GEN" AFTER JournalBatchNumber');
    }

    if (tableExists($connection, 'LedgerJournalTable') && !columnExists($connection, 'LedgerJournalTable', 'CompanyRecId')) {
        runSql($connection, 'ALTER TABLE LedgerJournalTable ADD COLUMN CompanyRecId INT NOT NULL DEFAULT 0 AFTER JournalType');
    }

    if (tableExists($connection, 'LedgerJournalTable') && !columnExists($connection, 'LedgerJournalTable', 'CompanyAlias')) {
        runSql($connection, 'ALTER TABLE LedgerJournalTable ADD COLUMN CompanyAlias VARCHAR(120) NOT NULL DEFAULT "" AFTER CompanyRecId');
    }

    if (tableExists($connection, 'PurchTable') && !columnExists($connection, 'PurchTable', 'CompanyRecId')) {
        runSql($connection, 'ALTER TABLE PurchTable ADD COLUMN CompanyRecId INT NOT NULL DEFAULT 0 AFTER PurchType');
    }

    if (tableExists($connection, 'PurchTable') && !columnExists($connection, 'PurchTable', 'CompanyAlias')) {
        runSql($connection, 'ALTER TABLE PurchTable ADD COLUMN CompanyAlias VARCHAR(120) NOT NULL DEFAULT "" AFTER CompanyRecId');
    }

    if (tableExists($connection, 'PurchTable') && !columnExists($connection, 'PurchTable', 'DeductionAmount')) {
        runSql($connection, 'ALTER TABLE PurchTable ADD COLUMN DeductionAmount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER TotalAmount');
    }

    $anyCompany = $connection->query('SELECT RecId, Alias, PartyId FROM CompanyInfo WHERE IsActive = "1" ORDER BY RecId ASC LIMIT 1');
    $anyCompanyRow = $anyCompany ? $anyCompany->fetch_assoc() : null;

    if (!$anyCompanyRow && tableExists($connection, 'CompanyInfo') && tableExists($connection, 'DirPartyTable')) {
        $now = date('Y-m-d H:i:s');
        $partyNumberBase = 'CMP00001';
        $partyNumber = $partyNumberBase;
        $suffix = 2;

        while (true) {
            $existingParty = mysqli_query($connection, 'SELECT RecId FROM DirPartyTable WHERE PartyNumber = ' . "'" . mysqli_real_escape_string($connection, $partyNumber) . "'" . ' LIMIT 1');
            $existingPartyRow = $existingParty ? mysqli_fetch_assoc($existingParty) : null;
            if ($existingParty) {
                mysqli_free_result($existingParty);
            }

            if (!$existingPartyRow) {
                break;
            }

            $partyNumber = 'CMP' . str_pad((string) $suffix, 5, '0', STR_PAD_LEFT);
            $suffix++;
        }

        $partyName = 'Empresa Padrao';
        $partyAlias = 'EMPRESA';
        $partyTax = substr('00000000000000' . (string) time(), -14);

        $stmt = mysqli_prepare($connection, 'INSERT INTO DirPartyTable (PartyNumber, PartyType, Name, Alias, TaxId, IsActive, IsBlocked, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, "O", ?, ?, ?, "1", "0", ?, ?, "SYSTEM")');
        mysqli_stmt_bind_param($stmt, 'ssssss', $partyNumber, $partyName, $partyAlias, $partyTax, $now, $now);
        mysqli_stmt_execute($stmt);
        $partyId = mysqli_insert_id($connection);
        mysqli_stmt_close($stmt);

        if ($partyId > 0) {
            $stmtCompany = mysqli_prepare($connection, 'INSERT INTO CompanyInfo (PartyId, Alias, LegalName, TradeName, Cnpj, FiscalZipCode, FiscalStreet, FiscalStreetNumber, FiscalComplement, FiscalDistrict, FiscalCity, FiscalState, FiscalCountry, BillingZipCode, BillingStreet, BillingStreetNumber, BillingComplement, BillingDistrict, BillingCity, BillingState, BillingCountry, BillingSameAsFiscal, MainLogoUrl, MainLogoFileName, MainLogoBase64, IsActive, IsDefault, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, "", "", "", "", "", "", "", "BRASIL", "", "", "", "", "", "", "", "BRASIL", "1", "", "", NULL, "1", "1", ?, ?, "SYSTEM")');
            $alias = 'EMPRESA';
            $legalName = 'Empresa Padrao';
            $tradeName = 'Empresa Padrao';
            $cnpj = '00000000000000';
            mysqli_stmt_bind_param($stmtCompany, 'issssss', $partyId, $alias, $legalName, $tradeName, $cnpj, $now, $now);
            mysqli_stmt_execute($stmtCompany);
            mysqli_stmt_close($stmtCompany);

            runSql($connection, 'INSERT INTO LogisticsPostalAddress (PartyId, AddressType, ZipCode, Street, StreetNumber, Complement, District, City, State, Country, IsPrimary, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
                VALUES (' . (int) $partyId . ', "F", "", "", "", "", "", "", "", "BRASIL", "1", "1", NOW(), NOW(), "SYSTEM")');
            runSql($connection, 'INSERT INTO LogisticsPostalAddress (PartyId, AddressType, ZipCode, Street, StreetNumber, Complement, District, City, State, Country, IsPrimary, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
                VALUES (' . (int) $partyId . ', "B", "", "", "", "", "", "", "", "BRASIL", "0", "1", NOW(), NOW(), "SYSTEM")');
        }
    }

    $defaultCompany = $connection->query('SELECT RecId, Alias FROM CompanyInfo WHERE IsActive = "1" AND IsDefault = "1" ORDER BY RecId DESC LIMIT 1');
    $defaultCompanyRow = $defaultCompany ? $defaultCompany->fetch_assoc() : null;

    if (!$defaultCompanyRow) {
        $fallbackCompany = $connection->query('SELECT RecId, Alias FROM CompanyInfo WHERE IsActive = "1" ORDER BY RecId ASC LIMIT 1');
        $defaultCompanyRow = $fallbackCompany ? $fallbackCompany->fetch_assoc() : null;
    }

    if ($defaultCompanyRow) {
        runSql($connection, 'UPDATE CompanyInfo SET IsDefault = "0" WHERE IsDefault <> "0"');
        runSql($connection, 'UPDATE CompanyInfo SET IsDefault = "1" WHERE RecId = ' . (int) $defaultCompanyRow['RecId']);

        if (tableExists($connection, 'CustInvoiceJour')) {
            runSql($connection, 'UPDATE CustInvoiceJour SET CompanyRecId = ' . (int) $defaultCompanyRow['RecId'] . ' WHERE CompanyRecId IS NULL OR CompanyRecId = 0');
            runSql($connection, 'UPDATE CustInvoiceJour SET CompanyAlias = ' . "'" . mysqli_real_escape_string($connection, $defaultCompanyRow['Alias']) . "'" . ' WHERE CompanyAlias IS NULL OR CompanyAlias = ""');
        }

        if (tableExists($connection, 'PurchTable')) {
            runSql($connection, 'UPDATE PurchTable SET CompanyRecId = ' . (int) $defaultCompanyRow['RecId'] . ' WHERE CompanyRecId IS NULL OR CompanyRecId = 0');
            runSql($connection, 'UPDATE PurchTable SET CompanyAlias = ' . "'" . mysqli_real_escape_string($connection, $defaultCompanyRow['Alias']) . "'" . ' WHERE CompanyAlias IS NULL OR CompanyAlias = ""');
        }

        if (tableExists($connection, 'LedgerJournalTable')) {
            runSql($connection, 'UPDATE LedgerJournalTable SET CompanyRecId = ' . (int) $defaultCompanyRow['RecId'] . ' WHERE CompanyRecId IS NULL OR CompanyRecId = 0');
            runSql($connection, 'UPDATE LedgerJournalTable SET CompanyAlias = ' . "'" . mysqli_real_escape_string($connection, $defaultCompanyRow['Alias']) . "'" . ' WHERE CompanyAlias IS NULL OR CompanyAlias = ""');
        }
    }

    if (tableExists($connection, 'CustInvoiceJour') && !indexExists($connection, 'CustInvoiceJour', 'IX_CustInvoiceJour_CompanyRecId')) {
        runSql($connection, 'ALTER TABLE CustInvoiceJour ADD KEY IX_CustInvoiceJour_CompanyRecId (CompanyRecId)');
    }

    if (tableExists($connection, 'PurchTable') && !indexExists($connection, 'PurchTable', 'IX_PurchTable_CompanyRecId')) {
        runSql($connection, 'ALTER TABLE PurchTable ADD KEY IX_PurchTable_CompanyRecId (CompanyRecId)');
    }

    if (tableExists($connection, 'LedgerJournalTable') && !indexExists($connection, 'LedgerJournalTable', 'IX_LedgerJournalTable_CompanyRecId')) {
        runSql($connection, 'ALTER TABLE LedgerJournalTable ADD KEY IX_LedgerJournalTable_CompanyRecId (CompanyRecId)');
    }

    if (tableExists($connection, 'CustInvoiceJour') && !fkExists($connection, 'FK_CustInvoiceJour_CompanyInfo')) {
        runSql($connection, 'ALTER TABLE CustInvoiceJour ADD CONSTRAINT FK_CustInvoiceJour_CompanyInfo FOREIGN KEY (CompanyRecId) REFERENCES CompanyInfo (RecId)');
    }

    if (tableExists($connection, 'PurchTable') && !fkExists($connection, 'FK_PurchTable_CompanyInfo')) {
        runSql($connection, 'ALTER TABLE PurchTable ADD CONSTRAINT FK_PurchTable_CompanyInfo FOREIGN KEY (CompanyRecId) REFERENCES CompanyInfo (RecId)');
    }

    if (tableExists($connection, 'LedgerJournalTable') && !fkExists($connection, 'FK_LedgerJournalTable_CompanyInfo')) {
        runSql($connection, 'ALTER TABLE LedgerJournalTable ADD CONSTRAINT FK_LedgerJournalTable_CompanyInfo FOREIGN KEY (CompanyRecId) REFERENCES CompanyInfo (RecId)');
    }

    $ledgerColumns = [
        'TaxTypeId' => 'ALTER TABLE LedgerJournalTrans ADD COLUMN TaxTypeId INT NULL AFTER Voucher',
        'BankAccountId' => 'ALTER TABLE LedgerJournalTrans ADD COLUMN BankAccountId INT NULL AFTER TaxTypeId',
        'VendAccount' => 'ALTER TABLE LedgerJournalTrans ADD COLUMN VendAccount VARCHAR(30) NULL AFTER BankAccountId',
        'CustAccount' => 'ALTER TABLE LedgerJournalTrans ADD COLUMN CustAccount VARCHAR(30) NULL AFTER VendAccount',
        'ServiceInvoiceRecId' => 'ALTER TABLE LedgerJournalTrans ADD COLUMN ServiceInvoiceRecId INT NULL AFTER CustAccount',
        'PurchRecId' => 'ALTER TABLE LedgerJournalTrans ADD COLUMN PurchRecId INT NULL AFTER ServiceInvoiceRecId',
        'PaymentMethod' => 'ALTER TABLE LedgerJournalTrans ADD COLUMN PaymentMethod VARCHAR(40) NOT NULL DEFAULT "" AFTER PurchRecId',
        'PaymentDate' => 'ALTER TABLE LedgerJournalTrans ADD COLUMN PaymentDate DATETIME NULL AFTER PaymentMethod',
        'PaidFlag' => 'ALTER TABLE LedgerJournalTrans ADD COLUMN PaidFlag CHAR(1) NOT NULL DEFAULT "0" AFTER PaymentDate',
        'ReceivedFlag' => 'ALTER TABLE LedgerJournalTrans ADD COLUMN ReceivedFlag CHAR(1) NOT NULL DEFAULT "0" AFTER PaidFlag'
    ];

    foreach ($ledgerColumns as $columnName => $sql) {
        if (tableExists($connection, 'LedgerJournalTrans') && !columnExists($connection, 'LedgerJournalTrans', $columnName)) {
            runSql($connection, $sql);
        }
    }

    if (tableExists($connection, 'LedgerJournalTrans') && !indexExists($connection, 'LedgerJournalTrans', 'IX_LedgerJournalTrans_TaxTypeId')) {
        runSql($connection, 'ALTER TABLE LedgerJournalTrans ADD KEY IX_LedgerJournalTrans_TaxTypeId (TaxTypeId)');
    }
    if (tableExists($connection, 'LedgerJournalTrans') && !indexExists($connection, 'LedgerJournalTrans', 'IX_LedgerJournalTrans_BankAccountId')) {
        runSql($connection, 'ALTER TABLE LedgerJournalTrans ADD KEY IX_LedgerJournalTrans_BankAccountId (BankAccountId)');
    }
    if (tableExists($connection, 'LedgerJournalTrans') && !indexExists($connection, 'LedgerJournalTrans', 'IX_LedgerJournalTrans_VendAccount')) {
        runSql($connection, 'ALTER TABLE LedgerJournalTrans ADD KEY IX_LedgerJournalTrans_VendAccount (VendAccount)');
    }
    if (tableExists($connection, 'LedgerJournalTrans') && !indexExists($connection, 'LedgerJournalTrans', 'IX_LedgerJournalTrans_CustAccount')) {
        runSql($connection, 'ALTER TABLE LedgerJournalTrans ADD KEY IX_LedgerJournalTrans_CustAccount (CustAccount)');
    }
    if (tableExists($connection, 'LedgerJournalTrans') && !indexExists($connection, 'LedgerJournalTrans', 'IX_LedgerJournalTrans_ServiceInvoiceRecId')) {
        runSql($connection, 'ALTER TABLE LedgerJournalTrans ADD KEY IX_LedgerJournalTrans_ServiceInvoiceRecId (ServiceInvoiceRecId)');
    }
    if (tableExists($connection, 'LedgerJournalTrans') && !indexExists($connection, 'LedgerJournalTrans', 'IX_LedgerJournalTrans_PurchRecId')) {
        runSql($connection, 'ALTER TABLE LedgerJournalTrans ADD KEY IX_LedgerJournalTrans_PurchRecId (PurchRecId)');
    }

    if (tableExists($connection, 'LedgerJournalTrans') && !fkExists($connection, 'FK_LedgerJournalTrans_TaxTypeTable')) {
        runSql($connection, 'ALTER TABLE LedgerJournalTrans ADD CONSTRAINT FK_LedgerJournalTrans_TaxTypeTable FOREIGN KEY (TaxTypeId) REFERENCES TaxTypeTable (RecId)');
    }
    if (tableExists($connection, 'LedgerJournalTrans') && !fkExists($connection, 'FK_LedgerJournalTrans_BankAccountTable')) {
        runSql($connection, 'ALTER TABLE LedgerJournalTrans ADD CONSTRAINT FK_LedgerJournalTrans_BankAccountTable FOREIGN KEY (BankAccountId) REFERENCES BankAccountTable (RecId)');
    }
    if (tableExists($connection, 'LedgerJournalTrans') && !fkExists($connection, 'FK_LedgerJournalTrans_VendTable')) {
        runSql($connection, 'ALTER TABLE LedgerJournalTrans ADD CONSTRAINT FK_LedgerJournalTrans_VendTable FOREIGN KEY (VendAccount) REFERENCES VendTable (VendAccount)');
    }
    if (tableExists($connection, 'LedgerJournalTrans') && !fkExists($connection, 'FK_LedgerJournalTrans_CustTable')) {
        runSql($connection, 'ALTER TABLE LedgerJournalTrans ADD CONSTRAINT FK_LedgerJournalTrans_CustTable FOREIGN KEY (CustAccount) REFERENCES CustTable (CustAccount)');
    }
    if (tableExists($connection, 'LedgerJournalTrans') && !fkExists($connection, 'FK_LedgerJournalTrans_CustInvoiceJour')) {
        runSql($connection, 'ALTER TABLE LedgerJournalTrans ADD CONSTRAINT FK_LedgerJournalTrans_CustInvoiceJour FOREIGN KEY (ServiceInvoiceRecId) REFERENCES CustInvoiceJour (RecId)');
    }
    if (tableExists($connection, 'LedgerJournalTrans') && !fkExists($connection, 'FK_LedgerJournalTrans_PurchTable')) {
        runSql($connection, 'ALTER TABLE LedgerJournalTrans ADD CONSTRAINT FK_LedgerJournalTrans_PurchTable FOREIGN KEY (PurchRecId) REFERENCES PurchTable (RecId)');
    }

    if (!columnExists($connection, 'SysUserInfo', 'LanguageId')) {
        runSql($connection, 'ALTER TABLE SysUserInfo ADD COLUMN LanguageId VARCHAR(10) NOT NULL DEFAULT "PT-BR" AFTER RoleId');
    }

    if (!indexExists($connection, 'SysUserInfo', 'IX_SysUserInfo_LanguageId')) {
        runSql($connection, 'ALTER TABLE SysUserInfo ADD KEY IX_SysUserInfo_LanguageId (LanguageId)');
    }

    runSql($connection, "INSERT IGNORE INTO SysLanguage (LanguageId, Name, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES ('PT-BR','Portuguese (Brazil)','1',NOW(),NOW(),'SYSTEM')");
    runSql($connection, "INSERT IGNORE INTO SysLanguage (LanguageId, Name, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES ('EN-US','English (United States)','1',NOW(),NOW(),'SYSTEM')");
    runSql($connection, "UPDATE SysUserInfo SET LanguageId = 'PT-BR' WHERE LanguageId IS NULL OR LanguageId = ''");

    if (!fkExists($connection, 'FK_SysUserInfo_SysLanguage')) {
        runSql($connection, 'ALTER TABLE SysUserInfo ADD CONSTRAINT FK_SysUserInfo_SysLanguage FOREIGN KEY (LanguageId) REFERENCES SysLanguage (LanguageId)');
    }

        // Reporting views centralize read models for ERP reports and BI integrations.
        runSql($connection, 'DROP VIEW IF EXISTS vw_report_financial_movements');
        runSql($connection, 'DROP VIEW IF EXISTS vw_report_accounts_payable_documents');
        runSql($connection, 'DROP VIEW IF EXISTS vw_report_journal_trans');
        runSql($connection, 'DROP VIEW IF EXISTS vw_report_accounts_receivable');

        runSql($connection, 'CREATE VIEW vw_report_accounts_receivable AS
         SELECT ci.RecId,
             ci.InvoiceId,
             ci.InvoiceNumber,
             ci.CustAccount,
             dp.Name AS CustomerName,
             ci.InvoiceDate,
             ci.DueDate,
             ci.Status,
             ci.TotalAmount,
             ci.TaxAmount,
             ci.DeductionAmount,
             ci.IsActive
         FROM CustInvoiceJour ci
         LEFT JOIN DirPartyTable dp ON dp.RecId = ci.PartyId');

        runSql($connection, 'CREATE VIEW vw_report_journal_trans AS
         SELECT t.RecId,
             t.JournalRecId,
             t.TransDate,
             t.DueDate,
             t.Voucher,
             t.Description,
             t.LedgerCategory,
             t.AmountCurDebit,
             t.AmountCurCredit,
             t.PeriodMonth,
             t.Status,
             t.IsActive,
             j.Posted
         FROM LedgerJournalTrans t
         INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId');

        runSql($connection, 'CREATE VIEW vw_report_accounts_payable_documents AS
         SELECT "PURCHASE" AS SourceType,
             p.RecId AS SourceRecId,
             p.PurchId AS DocumentId,
             p.PurchNumber AS DocumentNumber,
             d.Name AS PartyName,
             p.PurchDate AS TransDate,
             p.DueDate,
             p.Status,
             p.TotalAmount AS Amount,
             p.IsActive,
             "1" AS Posted
         FROM PurchTable p
         INNER JOIN DirPartyTable d ON d.RecId = p.PartyId
         UNION ALL
         SELECT "JOURNAL" AS SourceType,
             jt.RecId AS SourceRecId,
             jt.Voucher AS DocumentId,
             jt.Voucher AS DocumentNumber,
             jt.Description AS PartyName,
             jt.TransDate,
             jt.DueDate,
             jt.Status,
             jt.AmountCurDebit AS Amount,
             jt.IsActive,
             jt.Posted
         FROM vw_report_journal_trans jt
         WHERE jt.AmountCurDebit > 0');

        runSql($connection, 'CREATE VIEW vw_report_financial_movements AS
         SELECT DATE_FORMAT(ar.InvoiceDate, "%Y-%m") AS SummaryMonth,
             ar.InvoiceDate AS RefDate,
             ar.TotalAmount AS BillingAmount,
             0.00 AS ExpenseAmount,
             ar.IsActive,
             "1" AS Posted,
             "AR" AS SourceType
         FROM vw_report_accounts_receivable ar
         UNION ALL
         SELECT DATE_FORMAT(jt.TransDate, "%Y-%m") AS SummaryMonth,
             jt.TransDate AS RefDate,
             jt.AmountCurCredit AS BillingAmount,
             0.00 AS ExpenseAmount,
             jt.IsActive,
             jt.Posted,
             "JRN_CR" AS SourceType
         FROM vw_report_journal_trans jt
         WHERE jt.AmountCurCredit > 0
         UNION ALL
         SELECT DATE_FORMAT(p.PurchDate, "%Y-%m") AS SummaryMonth,
             p.PurchDate AS RefDate,
             0.00 AS BillingAmount,
             p.TotalAmount AS ExpenseAmount,
             p.IsActive,
             "1" AS Posted,
             "PUR" AS SourceType
         FROM PurchTable p
         UNION ALL
         SELECT DATE_FORMAT(jt.TransDate, "%Y-%m") AS SummaryMonth,
             jt.TransDate AS RefDate,
             0.00 AS BillingAmount,
             jt.AmountCurDebit AS ExpenseAmount,
             jt.IsActive,
             jt.Posted,
             "JRN_DR" AS SourceType
         FROM vw_report_journal_trans jt
         WHERE jt.AmountCurDebit > 0');

    $seedSqlPath = __DIR__ . '/seed_i18n_navigation.sql';
    if (file_exists($seedSqlPath)) {
        $sql = file_get_contents($seedSqlPath);
        $parts = explode(';', $sql);
        foreach ($parts as $part) {
            $stmt = trim($part);
            if ($stmt === '') {
                continue;
            }
            runSql($connection, $stmt);
        }
    }

    runSql($connection, 'INSERT IGNORE INTO SysMenuItem (GroupId, ParentMenuId, MenuCode, LabelKey, ViewKey, SequenceNo, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
        SELECT g.RecId, 0, "SYS_SETUP", "menu.sys.setup", "", 2, "1", NOW(), NOW(), "SYSTEM"
        FROM SysMenuGroup g
        WHERE g.GroupCode = "SYSADMIN"');

    runSql($connection, 'UPDATE SysMenuItem setup
        INNER JOIN SysMenuItem company ON company.MenuCode = "SYS_COMPANY"
        SET company.ParentMenuId = setup.RecId,
            company.SequenceNo = 1,
            company.ModifiedDateTime = NOW()
        WHERE setup.MenuCode = "SYS_SETUP"');

    runSql($connection, 'UPDATE SysMenuItem setup
        INNER JOIN SysMenuItem labels ON labels.MenuCode = "SYS_LABELS"
        SET labels.ParentMenuId = setup.RecId,
            labels.SequenceNo = 2,
            labels.ModifiedDateTime = NOW()
        WHERE setup.MenuCode = "SYS_SETUP"');

    runSql($connection, 'UPDATE SysMenuItem setup
        INNER JOIN SysMenuItem seqs ON seqs.MenuCode = "SYS_NUMBERSEQUENCES"
        SET seqs.ParentMenuId = setup.RecId,
            seqs.SequenceNo = 3,
            seqs.ModifiedDateTime = NOW()
        WHERE setup.MenuCode = "SYS_SETUP"');

    runSql($connection, 'INSERT INTO SysLabelText (LabelKey, LanguageId, TextValue, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
        VALUES ("menu.sys.setup", "EN-US", "Setup", "1", NOW(), NOW(), "SYSTEM"),
               ("menu.sys.setup", "PT-BR", "Configuracoes", "1", NOW(), NOW(), "SYSTEM")
        ON DUPLICATE KEY UPDATE TextValue = VALUES(TextValue), ModifiedDateTime = NOW()');

    echo "I18N_MENU_MIGRATION=SUCCESS" . PHP_EOL;
    exit(0);
} catch (Exception $exception) {
    echo "I18N_MENU_MIGRATION=FAILED" . PHP_EOL;
    echo "ERROR=" . $exception->getMessage() . PHP_EOL;
    exit(1);
}
