DROP TABLE IF EXISTS LedgerJournalTrans;
DROP TABLE IF EXISTS LedgerJournalTable;
DROP TABLE IF EXISTS DocuAttachment;
DROP TABLE IF EXISTS TaxTypeTable;
DROP TABLE IF EXISTS BankAccountTable;
DROP TABLE IF EXISTS PurchLine;
DROP TABLE IF EXISTS PurchTable;
DROP TABLE IF EXISTS CustInvoiceTrans;
DROP TABLE IF EXISTS CustInvoiceJour;
DROP TABLE IF EXISTS ServiceCodeTable;
DROP TABLE IF EXISTS InventTable;
DROP TABLE IF EXISTS VendTable;
DROP TABLE IF EXISTS VendContactPerson;
DROP TABLE IF EXISTS CustContactPerson;
DROP TABLE IF EXISTS CustTable;
DROP TABLE IF EXISTS LogisticsElectronicAddress;
DROP TABLE IF EXISTS LogisticsPostalAddress;
DROP TABLE IF EXISTS DirPartyTable;
DROP TABLE IF EXISTS SysMenuItem;
DROP TABLE IF EXISTS SysMenuGroup;
DROP TABLE IF EXISTS SysLabelText;
DROP TABLE IF EXISTS SysLanguage;
DROP TABLE IF EXISTS SysNumberSequenceTable;
DROP TABLE IF EXISTS CompanyInfo;
DROP TABLE IF EXISTS SysUserInfo;
DROP TABLE IF EXISTS SecurityRole;

CREATE TABLE SecurityRole (
    RecId INT NOT NULL AUTO_INCREMENT,
    RoleCode VARCHAR(30) NOT NULL,
    Name VARCHAR(100) NOT NULL,
    Description VARCHAR(255) NOT NULL,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_SecurityRole_RoleCode (RoleCode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE SysLanguage (
    RecId INT NOT NULL AUTO_INCREMENT,
    LanguageId VARCHAR(10) NOT NULL,
    Name VARCHAR(80) NOT NULL,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_SysLanguage_LanguageId (LanguageId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE SysLabelText (
    RecId INT NOT NULL AUTO_INCREMENT,
    LabelKey VARCHAR(120) NOT NULL,
    LanguageId VARCHAR(10) NOT NULL,
    TextValue VARCHAR(255) NOT NULL,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_SysLabelText_LabelLanguage (LabelKey, LanguageId),
    KEY IX_SysLabelText_LanguageId (LanguageId),
    CONSTRAINT FK_SysLabelText_SysLanguage FOREIGN KEY (LanguageId) REFERENCES SysLanguage (LanguageId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE SysMenuGroup (
    RecId INT NOT NULL AUTO_INCREMENT,
    GroupCode VARCHAR(40) NOT NULL,
    LabelKey VARCHAR(120) NOT NULL,
    SequenceNo INT NOT NULL DEFAULT 0,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_SysMenuGroup_GroupCode (GroupCode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE SysMenuItem (
    RecId INT NOT NULL AUTO_INCREMENT,
    GroupId INT NOT NULL,
    ParentMenuId INT NOT NULL DEFAULT 0,
    MenuCode VARCHAR(50) NOT NULL,
    LabelKey VARCHAR(120) NOT NULL,
    ViewKey VARCHAR(60) NOT NULL,
    SequenceNo INT NOT NULL DEFAULT 0,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_SysMenuItem_MenuCode (MenuCode),
    KEY IX_SysMenuItem_GroupId (GroupId),
    KEY IX_SysMenuItem_ParentMenuId (ParentMenuId),
    CONSTRAINT FK_SysMenuItem_SysMenuGroup FOREIGN KEY (GroupId) REFERENCES SysMenuGroup (RecId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE SysUserInfo (
    RecId INT NOT NULL AUTO_INCREMENT,
    UserId VARCHAR(30) NOT NULL,
    UserName VARCHAR(120) NOT NULL,
    Email VARCHAR(160) NOT NULL,
    PasswordHash VARCHAR(64) NOT NULL,
    RoleId INT NOT NULL,
    LanguageId VARCHAR(10) NOT NULL DEFAULT 'PT-BR',
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    IsBlocked CHAR(1) NOT NULL DEFAULT '0',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_SysUserInfo_UserId (UserId),
    UNIQUE KEY UX_SysUserInfo_Email (Email),
    KEY IX_SysUserInfo_RoleId (RoleId),
    KEY IX_SysUserInfo_LanguageId (LanguageId),
    CONSTRAINT FK_SysUserInfo_SecurityRole FOREIGN KEY (RoleId) REFERENCES SecurityRole (RecId),
    CONSTRAINT FK_SysUserInfo_SysLanguage FOREIGN KEY (LanguageId) REFERENCES SysLanguage (LanguageId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE CompanyInfo (
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
    BillingSameAsFiscal CHAR(1) NOT NULL DEFAULT '0',
    MainLogoUrl VARCHAR(255) NOT NULL,
    MainLogoFileName VARCHAR(255) NOT NULL,
    MainLogoBase64 LONGTEXT NULL,
    InitialBalance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    InitialBalanceDate DATE NULL,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    IsDefault CHAR(1) NOT NULL DEFAULT '0',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_CompanyInfo_Cnpj (Cnpj),
    KEY IX_CompanyInfo_PartyId (PartyId),
    KEY IX_CompanyInfo_IsDefault (IsDefault)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE SysNumberSequenceTable (
    RecId INT NOT NULL AUTO_INCREMENT,
    ObjectCode VARCHAR(40) NOT NULL,
    ObjectName VARCHAR(120) NOT NULL,
    CurrentNumber INT NOT NULL DEFAULT 0,
    NextNumber INT NOT NULL DEFAULT 1,
    FormatMask VARCHAR(40) NOT NULL,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_SysNumberSequenceTable_ObjectCode (ObjectCode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE DirPartyTable (
    RecId INT NOT NULL AUTO_INCREMENT,
    PartyNumber VARCHAR(30) NOT NULL,
    PartyType CHAR(1) NOT NULL,
    Name VARCHAR(160) NOT NULL,
    Alias VARCHAR(120) NOT NULL,
    TaxId VARCHAR(30) NOT NULL,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    IsBlocked CHAR(1) NOT NULL DEFAULT '0',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_DirPartyTable_PartyNumber (PartyNumber),
    KEY IX_DirPartyTable_Name (Name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE LogisticsPostalAddress (
    RecId INT NOT NULL AUTO_INCREMENT,
    PartyId INT NOT NULL,
    AddressType CHAR(1) NOT NULL DEFAULT 'O',
    ZipCode VARCHAR(20) NOT NULL,
    Street VARCHAR(160) NOT NULL,
    StreetNumber VARCHAR(30) NOT NULL,
    Complement VARCHAR(120) NOT NULL,
    District VARCHAR(120) NOT NULL,
    City VARCHAR(120) NOT NULL,
    State VARCHAR(60) NOT NULL,
    Country VARCHAR(80) NOT NULL,
    IsPrimary CHAR(1) NOT NULL DEFAULT '0',
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    KEY IX_LogisticsPostalAddress_PartyId (PartyId),
    CONSTRAINT FK_LogisticsPostalAddress_DirPartyTable FOREIGN KEY (PartyId) REFERENCES DirPartyTable (RecId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE LogisticsElectronicAddress (
    RecId INT NOT NULL AUTO_INCREMENT,
    PartyId INT NOT NULL,
    Type CHAR(1) NOT NULL,
    Locator VARCHAR(160) NOT NULL,
    ContactRole VARCHAR(60) NOT NULL,
    IsPrimary CHAR(1) NOT NULL DEFAULT '0',
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    KEY IX_LogisticsElectronicAddress_PartyId (PartyId),
    CONSTRAINT FK_LogisticsElectronicAddress_DirPartyTable FOREIGN KEY (PartyId) REFERENCES DirPartyTable (RecId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE CustTable (
    RecId INT NOT NULL AUTO_INCREMENT,
    CustAccount VARCHAR(30) NOT NULL,
    PartyId INT NOT NULL,
    CompanyType VARCHAR(40) NOT NULL,
    CurrencyCode VARCHAR(10) NOT NULL,
    CreditLimit DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PaymentTermDays INT NOT NULL DEFAULT 0,
    CustomerGroup VARCHAR(60) NOT NULL,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    IsBlocked CHAR(1) NOT NULL DEFAULT '0',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_CustTable_CustAccount (CustAccount),
    KEY IX_CustTable_PartyId (PartyId),
    CONSTRAINT FK_CustTable_DirPartyTable FOREIGN KEY (PartyId) REFERENCES DirPartyTable (RecId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE CustContactPerson (
    RecId INT NOT NULL AUTO_INCREMENT,
    CustRecId INT NOT NULL,
    FirstName VARCHAR(120) NOT NULL,
    LastName VARCHAR(120) NOT NULL,
    ContactType VARCHAR(20) NOT NULL,
    Email VARCHAR(160) NOT NULL,
    Phone VARCHAR(60) NOT NULL,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    KEY IX_CustContactPerson_CustRecId (CustRecId),
    CONSTRAINT FK_CustContactPerson_CustTable FOREIGN KEY (CustRecId) REFERENCES CustTable (RecId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE VendTable (
    RecId INT NOT NULL AUTO_INCREMENT,
    VendAccount VARCHAR(30) NOT NULL,
    PartyId INT NOT NULL,
    CompanyType VARCHAR(40) NOT NULL,
    CurrencyCode VARCHAR(10) NOT NULL,
    PaymentTermDays INT NOT NULL DEFAULT 0,
    VendorGroup VARCHAR(60) NOT NULL,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    IsBlocked CHAR(1) NOT NULL DEFAULT '0',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_VendTable_VendAccount (VendAccount),
    KEY IX_VendTable_PartyId (PartyId),
    CONSTRAINT FK_VendTable_DirPartyTable FOREIGN KEY (PartyId) REFERENCES DirPartyTable (RecId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE VendContactPerson (
    RecId INT NOT NULL AUTO_INCREMENT,
    VendRecId INT NOT NULL,
    FirstName VARCHAR(120) NOT NULL,
    LastName VARCHAR(120) NOT NULL,
    ContactType VARCHAR(20) NOT NULL,
    Email VARCHAR(160) NOT NULL,
    Phone VARCHAR(60) NOT NULL,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    KEY IX_VendContactPerson_VendRecId (VendRecId),
    CONSTRAINT FK_VendContactPerson_VendTable FOREIGN KEY (VendRecId) REFERENCES VendTable (RecId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE InventTable (
    RecId INT NOT NULL AUTO_INCREMENT,
    ItemId VARCHAR(30) NOT NULL,
    Name VARCHAR(160) NOT NULL,
    Description VARCHAR(1000) NOT NULL,
    UnitOfMeasure VARCHAR(20) NOT NULL,
    ItemType CHAR(1) NOT NULL,
    SalesPrice DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CostPrice DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    IsBlocked CHAR(1) NOT NULL DEFAULT '0',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_InventTable_ItemId (ItemId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE ServiceCodeTable (
    RecId INT NOT NULL AUTO_INCREMENT,
    ServiceCode VARCHAR(30) NOT NULL,
    Name VARCHAR(160) NOT NULL,
    Description VARCHAR(1000) NOT NULL,
    DefaultPrice DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    IsBlocked CHAR(1) NOT NULL DEFAULT '0',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_ServiceCodeTable_ServiceCode (ServiceCode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE TaxTypeTable (
    RecId INT NOT NULL AUTO_INCREMENT,
    TaxTypeCode VARCHAR(30) NOT NULL,
    Name VARCHAR(160) NOT NULL,
    Description VARCHAR(500) NOT NULL,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    IsBlocked CHAR(1) NOT NULL DEFAULT '0',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_TaxTypeTable_TaxTypeCode (TaxTypeCode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE BankAccountTable (
    RecId INT NOT NULL AUTO_INCREMENT,
    BankName VARCHAR(120) NOT NULL,
    AccountNumber VARCHAR(40) NOT NULL,
    AccountDigit VARCHAR(8) NOT NULL,
    Description VARCHAR(255) NOT NULL,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    IsBlocked CHAR(1) NOT NULL DEFAULT '0',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE DocuAttachment (
    RecId INT NOT NULL AUTO_INCREMENT,
    EntityName VARCHAR(60) NOT NULL,
    RecordRecId INT NOT NULL,
    LineEntityName VARCHAR(60) NOT NULL DEFAULT '',
    LineRecId INT NOT NULL DEFAULT 0,
    FileName VARCHAR(255) NOT NULL,
    MimeType VARCHAR(120) NOT NULL,
    FileContentBase64 LONGTEXT NOT NULL,
    Notes VARCHAR(255) NOT NULL,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    KEY IX_DocuAttachment_EntityRecord (EntityName, RecordRecId),
    KEY IX_DocuAttachment_Line (LineEntityName, LineRecId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE CustInvoiceJour (
    RecId INT NOT NULL AUTO_INCREMENT,
    InvoiceId VARCHAR(30) NOT NULL,
    CompanyRecId INT NOT NULL,
    CompanyAlias VARCHAR(120) NOT NULL,
    CustAccount VARCHAR(30) NOT NULL,
    PartyId INT NOT NULL,
    InvoiceNumber VARCHAR(60) NOT NULL,
    InvoiceDate DATETIME NOT NULL,
    DueDate DATETIME NOT NULL,
    TotalAmount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    TaxAmount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    DeductionAmount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    IsInternationalReplacement CHAR(1) NOT NULL DEFAULT '0',
    InternationalInvoiceNumber VARCHAR(80) NOT NULL,
    InternationalInvoiceSeries VARCHAR(40) NOT NULL,
    InternationalInvoiceDate DATETIME NULL,
    Status CHAR(1) NOT NULL DEFAULT 'O',
    Notes VARCHAR(1000) NOT NULL,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_CustInvoiceJour_InvoiceId (InvoiceId),
    KEY IX_CustInvoiceJour_CompanyRecId (CompanyRecId),
    KEY IX_CustInvoiceJour_CustAccount (CustAccount),
    KEY IX_CustInvoiceJour_PartyId (PartyId),
    CONSTRAINT FK_CustInvoiceJour_CompanyInfo FOREIGN KEY (CompanyRecId) REFERENCES CompanyInfo (RecId),
    CONSTRAINT FK_CustInvoiceJour_DirPartyTable FOREIGN KEY (PartyId) REFERENCES DirPartyTable (RecId),
    CONSTRAINT FK_CustInvoiceJour_CustTable FOREIGN KEY (CustAccount) REFERENCES CustTable (CustAccount)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE CustInvoiceTrans (
    RecId INT NOT NULL AUTO_INCREMENT,
    InvoiceRecId INT NOT NULL,
    LineNum INT NOT NULL,
    ServiceCodeId INT NOT NULL,
    Description VARCHAR(255) NOT NULL,
    LineAmount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    KEY IX_CustInvoiceTrans_InvoiceRecId (InvoiceRecId),
    KEY IX_CustInvoiceTrans_ServiceCodeId (ServiceCodeId),
    CONSTRAINT FK_CustInvoiceTrans_CustInvoiceJour FOREIGN KEY (InvoiceRecId) REFERENCES CustInvoiceJour (RecId),
    CONSTRAINT FK_CustInvoiceTrans_ServiceCodeTable FOREIGN KEY (ServiceCodeId) REFERENCES ServiceCodeTable (RecId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE PurchTable (
    RecId INT NOT NULL AUTO_INCREMENT,
    PurchId VARCHAR(30) NOT NULL,
    PurchType CHAR(1) NOT NULL DEFAULT 'I',
    CompanyRecId INT NOT NULL,
    CompanyAlias VARCHAR(120) NOT NULL,
    VendAccount VARCHAR(30) NOT NULL,
    PartyId INT NOT NULL,
    PurchNumber VARCHAR(60) NOT NULL,
    PurchDate DATETIME NOT NULL,
    DueDate DATETIME NOT NULL,
    TotalAmount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    DeductionAmount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    Status CHAR(1) NOT NULL DEFAULT 'O',
    Notes VARCHAR(1000) NOT NULL,
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_PurchTable_PurchId (PurchId),
    KEY IX_PurchTable_CompanyRecId (CompanyRecId),
    KEY IX_PurchTable_VendAccount (VendAccount),
    KEY IX_PurchTable_PartyId (PartyId),
    CONSTRAINT FK_PurchTable_CompanyInfo FOREIGN KEY (CompanyRecId) REFERENCES CompanyInfo (RecId),
    CONSTRAINT FK_PurchTable_DirPartyTable FOREIGN KEY (PartyId) REFERENCES DirPartyTable (RecId),
    CONSTRAINT FK_PurchTable_VendTable FOREIGN KEY (VendAccount) REFERENCES VendTable (VendAccount)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE PurchLine (
    RecId INT NOT NULL AUTO_INCREMENT,
    PurchRecId INT NOT NULL,
    LineNum INT NOT NULL,
    ItemId VARCHAR(30) NULL,
    ServiceCodeId INT NULL,
    Description VARCHAR(255) NOT NULL,
    Quantity DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    UnitPrice DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    LineAmount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    KEY IX_PurchLine_PurchRecId (PurchRecId),
    KEY IX_PurchLine_ItemId (ItemId),
    KEY IX_PurchLine_ServiceCodeId (ServiceCodeId),
    CONSTRAINT FK_PurchLine_PurchTable FOREIGN KEY (PurchRecId) REFERENCES PurchTable (RecId),
    CONSTRAINT FK_PurchLine_InventTable FOREIGN KEY (ItemId) REFERENCES InventTable (ItemId),
    CONSTRAINT FK_PurchLine_ServiceCodeTable FOREIGN KEY (ServiceCodeId) REFERENCES ServiceCodeTable (RecId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE LedgerJournalTable (
    RecId INT NOT NULL AUTO_INCREMENT,
    JournalBatchNumber VARCHAR(30) NOT NULL,
    JournalType CHAR(3) NOT NULL DEFAULT 'GEN',
    CompanyRecId INT NOT NULL,
    CompanyAlias VARCHAR(120) NOT NULL,
    Description VARCHAR(255) NOT NULL,
    JournalDate DATETIME NOT NULL,
    Posted CHAR(1) NOT NULL DEFAULT '0',
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    UNIQUE KEY UX_LedgerJournalTable_JournalBatchNumber (JournalBatchNumber),
    KEY IX_LedgerJournalTable_CompanyRecId (CompanyRecId),
    CONSTRAINT FK_LedgerJournalTable_CompanyInfo FOREIGN KEY (CompanyRecId) REFERENCES CompanyInfo (RecId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE LedgerJournalTrans (
    RecId INT NOT NULL AUTO_INCREMENT,
    JournalRecId INT NOT NULL,
    LineNum INT NOT NULL,
    TransDate DATETIME NOT NULL,
    DueDate DATETIME NOT NULL,
    Voucher VARCHAR(60) NOT NULL,
    TaxTypeId INT NULL,
    BankAccountId INT NULL,
    VendAccount VARCHAR(30) NULL,
    CustAccount VARCHAR(30) NULL,
    ServiceInvoiceRecId INT NULL,
    PurchRecId INT NULL,
    PaymentMethod VARCHAR(40) NOT NULL,
    PaymentDate DATETIME NULL,
    PaidFlag CHAR(1) NOT NULL DEFAULT '0',
    ReceivedFlag CHAR(1) NOT NULL DEFAULT '0',
    Description VARCHAR(255) NOT NULL,
    LedgerCategory VARCHAR(60) NOT NULL,
    AmountCurDebit DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    AmountCurCredit DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PeriodMonth CHAR(7) NOT NULL,
    Status CHAR(1) NOT NULL DEFAULT 'O',
    IsActive CHAR(1) NOT NULL DEFAULT '1',
    CreatedDateTime DATETIME NOT NULL,
    ModifiedDateTime DATETIME NOT NULL,
    CreatedBy VARCHAR(60) NOT NULL,
    PRIMARY KEY (RecId),
    KEY IX_LedgerJournalTrans_JournalRecId (JournalRecId),
    KEY IX_LedgerJournalTrans_TaxTypeId (TaxTypeId),
    KEY IX_LedgerJournalTrans_BankAccountId (BankAccountId),
    KEY IX_LedgerJournalTrans_VendAccount (VendAccount),
    KEY IX_LedgerJournalTrans_CustAccount (CustAccount),
    KEY IX_LedgerJournalTrans_ServiceInvoiceRecId (ServiceInvoiceRecId),
    KEY IX_LedgerJournalTrans_PurchRecId (PurchRecId),
    KEY IX_LedgerJournalTrans_PeriodMonth (PeriodMonth),
    CONSTRAINT FK_LedgerJournalTrans_LedgerJournalTable FOREIGN KEY (JournalRecId) REFERENCES LedgerJournalTable (RecId),
    CONSTRAINT FK_LedgerJournalTrans_TaxTypeTable FOREIGN KEY (TaxTypeId) REFERENCES TaxTypeTable (RecId),
    CONSTRAINT FK_LedgerJournalTrans_BankAccountTable FOREIGN KEY (BankAccountId) REFERENCES BankAccountTable (RecId),
    CONSTRAINT FK_LedgerJournalTrans_VendTable FOREIGN KEY (VendAccount) REFERENCES VendTable (VendAccount),
    CONSTRAINT FK_LedgerJournalTrans_CustTable FOREIGN KEY (CustAccount) REFERENCES CustTable (CustAccount),
    CONSTRAINT FK_LedgerJournalTrans_CustInvoiceJour FOREIGN KEY (ServiceInvoiceRecId) REFERENCES CustInvoiceJour (RecId),
    CONSTRAINT FK_LedgerJournalTrans_PurchTable FOREIGN KEY (PurchRecId) REFERENCES PurchTable (RecId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE VIEW vw_report_accounts_receivable AS
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
LEFT JOIN DirPartyTable dp ON dp.RecId = ci.PartyId;

CREATE VIEW vw_report_journal_trans AS
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
INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId;

CREATE VIEW vw_report_accounts_payable_documents AS
SELECT 'PURCHASE' AS SourceType,
       p.RecId AS SourceRecId,
       p.PurchId AS DocumentId,
       p.PurchNumber AS DocumentNumber,
       d.Name AS PartyName,
       p.PurchDate AS TransDate,
       p.DueDate,
       p.Status,
       p.TotalAmount AS Amount,
       p.IsActive,
       '1' AS Posted
FROM PurchTable p
INNER JOIN DirPartyTable d ON d.RecId = p.PartyId
UNION ALL
SELECT 'JOURNAL' AS SourceType,
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
WHERE jt.AmountCurDebit > 0;

CREATE VIEW vw_report_financial_movements AS
SELECT DATE_FORMAT(ar.InvoiceDate, '%Y-%m') AS SummaryMonth,
       ar.InvoiceDate AS RefDate,
       ar.TotalAmount AS BillingAmount,
       0.00 AS ExpenseAmount,
       ar.IsActive,
       '1' AS Posted,
       'AR' AS SourceType
FROM vw_report_accounts_receivable ar
UNION ALL
SELECT DATE_FORMAT(jt.TransDate, '%Y-%m') AS SummaryMonth,
       jt.TransDate AS RefDate,
       jt.AmountCurCredit AS BillingAmount,
       0.00 AS ExpenseAmount,
       jt.IsActive,
       jt.Posted,
       'JRN_CR' AS SourceType
FROM vw_report_journal_trans jt
WHERE jt.AmountCurCredit > 0
UNION ALL
SELECT DATE_FORMAT(p.PurchDate, '%Y-%m') AS SummaryMonth,
       p.PurchDate AS RefDate,
       0.00 AS BillingAmount,
       p.TotalAmount AS ExpenseAmount,
       p.IsActive,
       '1' AS Posted,
       'PUR' AS SourceType
FROM PurchTable p
UNION ALL
SELECT DATE_FORMAT(jt.TransDate, '%Y-%m') AS SummaryMonth,
       jt.TransDate AS RefDate,
       0.00 AS BillingAmount,
       jt.AmountCurDebit AS ExpenseAmount,
       jt.IsActive,
       jt.Posted,
       'JRN_DR' AS SourceType
FROM vw_report_journal_trans jt
WHERE jt.AmountCurDebit > 0;

INSERT INTO SecurityRole (RoleCode, Name, Description, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
VALUES
    ('ADMIN', 'Administrator', 'Full access to all modules', '1', NOW(), NOW(), 'SYSTEM'),
    ('USER', 'Standard User', 'Operational access without user administration', '1', NOW(), NOW(), 'SYSTEM');

INSERT INTO SysLanguage (LanguageId, Name, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
VALUES
    ('PT-BR', 'Portuguese (Brazil)', '1', NOW(), NOW(), 'SYSTEM'),
    ('EN-US', 'English (United States)', '1', NOW(), NOW(), 'SYSTEM');

INSERT INTO SysUserInfo (UserId, UserName, Email, PasswordHash, RoleId, LanguageId, IsActive, IsBlocked, CreatedDateTime, ModifiedDateTime, CreatedBy)
VALUES
    ('ADMIN', 'System Administrator', 'admin@caspti.local', MD5(CONCAT('CASPTI_FIXED_SALT_2026', 'Admin@123')), 1, 'PT-BR', '1', '0', NOW(), NOW(), 'SYSTEM');

INSERT INTO ServiceCodeTable (ServiceCode, Name, Description, DefaultPrice, IsActive, IsBlocked, CreatedDateTime, ModifiedDateTime, CreatedBy)
VALUES
    ('CONSULTING', 'IT Consulting', 'ERP and technology consulting services', 0.00, '1', '0', NOW(), NOW(), 'SYSTEM');

INSERT INTO SysMenuGroup (GroupCode, LabelKey, SequenceNo, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
VALUES
    ('SYSADMIN', 'menu.group.sysadmin', 1, '1', NOW(), NOW(), 'SYSTEM'),
    ('AR', 'menu.group.ar', 2, '1', NOW(), NOW(), 'SYSTEM'),
    ('AP', 'menu.group.ap', 3, '1', NOW(), NOW(), 'SYSTEM'),
    ('GENERAL', 'menu.group.general', 4, '1', NOW(), NOW(), 'SYSTEM'),
    ('REPORTS', 'menu.group.reports', 5, '1', NOW(), NOW(), 'SYSTEM');

INSERT INTO SysMenuItem (GroupId, ParentMenuId, MenuCode, LabelKey, ViewKey, SequenceNo, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
VALUES
    (1, 0, 'SYS_SECURITY', 'menu.sys.security', '', 1, '1', NOW(), NOW(), 'SYSTEM'),
    (1, 1, 'SYS_ROLES', 'menu.sys.roles', 'roles', 1, '1', NOW(), NOW(), 'SYSTEM'),
    (1, 1, 'SYS_USERS', 'menu.sys.users', 'users', 2, '1', NOW(), NOW(), 'SYSTEM'),
    (1, 0, 'SYS_SETUP', 'menu.sys.setup', '', 2, '1', NOW(), NOW(), 'SYSTEM'),
    (1, 4, 'SYS_COMPANY', 'menu.sys.company', 'company', 1, '1', NOW(), NOW(), 'SYSTEM'),
    (1, 4, 'SYS_LABELS', 'menu.sys.labels', 'labels', 2, '1', NOW(), NOW(), 'SYSTEM'),
    (1, 4, 'SYS_NUMBERSEQUENCES', 'menu.sys.numbersequences', 'number-sequences', 3, '1', NOW(), NOW(), 'SYSTEM'),
    (1, 0, 'SYS_GAB', 'menu.sys.gab', '', 3, '1', NOW(), NOW(), 'SYSTEM'),
    (1, 8, 'SYS_PARTIES', 'menu.sys.parties', 'parties', 1, '1', NOW(), NOW(), 'SYSTEM'),
    (2, 0, 'AR_CUSTOMERS', 'menu.ar.customers', 'customers', 1, '1', NOW(), NOW(), 'SYSTEM'),
    (2, 0, 'AR_INVOICES', 'menu.ar.invoices', 'service-invoices', 2, '1', NOW(), NOW(), 'SYSTEM'),
    (3, 0, 'AP_VENDORS', 'menu.ap.vendors', 'vendors', 1, '1', NOW(), NOW(), 'SYSTEM'),
    (3, 0, 'AP_PURCHASES', 'menu.ap.purchases', 'purchase-orders', 2, '1', NOW(), NOW(), 'SYSTEM'),
    (3, 0, 'AP_SERVICE_PURCHASES', 'menu.ap.servicepurchases', 'service-purchase-orders', 3, '1', NOW(), NOW(), 'SYSTEM'),
    (4, 0, 'GEN_DASHBOARD', 'menu.general.dashboard', 'dashboard', 1, '1', NOW(), NOW(), 'SYSTEM'),
    (4, 0, 'GEN_PRODUCTS', 'menu.general.products', 'products', 2, '1', NOW(), NOW(), 'SYSTEM'),
    (4, 0, 'GEN_SERVICES', 'menu.general.servicecodes', 'service-codes', 3, '1', NOW(), NOW(), 'SYSTEM'),
    (4, 0, 'GEN_JOURNALS', 'menu.general.journals', 'journals', 4, '1', NOW(), NOW(), 'SYSTEM'),
    (5, 0, 'REP_HUB', 'menu.reports.hub', 'reports', 1, '1', NOW(), NOW(), 'SYSTEM');

INSERT INTO SysLabelText (LabelKey, LanguageId, TextValue, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
VALUES
    ('app.title', 'EN-US', 'CASPTI Mini ERP', '1', NOW(), NOW(), 'SYSTEM'),
    ('app.title', 'PT-BR', 'Mini ERP CASPTI', '1', NOW(), NOW(), 'SYSTEM'),
    ('login.email', 'EN-US', 'Email', '1', NOW(), NOW(), 'SYSTEM'),
    ('login.email', 'PT-BR', 'E-mail', '1', NOW(), NOW(), 'SYSTEM'),
    ('login.password', 'EN-US', 'Password', '1', NOW(), NOW(), 'SYSTEM'),
    ('login.password', 'PT-BR', 'Senha', '1', NOW(), NOW(), 'SYSTEM'),
    ('login.signin', 'EN-US', 'Sign in', '1', NOW(), NOW(), 'SYSTEM'),
    ('login.signin', 'PT-BR', 'Entrar', '1', NOW(), NOW(), 'SYSTEM'),
    ('button.save', 'EN-US', 'Save Record', '1', NOW(), NOW(), 'SYSTEM'),
    ('button.save', 'PT-BR', 'Salvar Registro', '1', NOW(), NOW(), 'SYSTEM'),
    ('button.new', 'EN-US', 'New Record', '1', NOW(), NOW(), 'SYSTEM'),
    ('button.new', 'PT-BR', 'Novo Registro', '1', NOW(), NOW(), 'SYSTEM'),
    ('button.logout', 'EN-US', 'Logout', '1', NOW(), NOW(), 'SYSTEM'),
    ('button.logout', 'PT-BR', 'Sair', '1', NOW(), NOW(), 'SYSTEM'),
    ('button.runreport', 'EN-US', 'Run Report', '1', NOW(), NOW(), 'SYSTEM'),
    ('button.runreport', 'PT-BR', 'Executar Relatorio', '1', NOW(), NOW(), 'SYSTEM'),
    ('field.UserId', 'EN-US', 'User ID', '1', NOW(), NOW(), 'SYSTEM'),
    ('field.UserId', 'PT-BR', 'Usuario', '1', NOW(), NOW(), 'SYSTEM'),
    ('field.UserName', 'EN-US', 'User Name', '1', NOW(), NOW(), 'SYSTEM'),
    ('field.UserName', 'PT-BR', 'Nome do Usuario', '1', NOW(), NOW(), 'SYSTEM'),
    ('field.Email', 'EN-US', 'Email', '1', NOW(), NOW(), 'SYSTEM'),
    ('field.Email', 'PT-BR', 'E-mail', '1', NOW(), NOW(), 'SYSTEM'),
    ('field.Password', 'EN-US', 'Password', '1', NOW(), NOW(), 'SYSTEM'),
    ('field.Password', 'PT-BR', 'Senha', '1', NOW(), NOW(), 'SYSTEM'),
    ('field.RoleId', 'EN-US', 'Role', '1', NOW(), NOW(), 'SYSTEM'),
    ('field.RoleId', 'PT-BR', 'Perfil', '1', NOW(), NOW(), 'SYSTEM'),
    ('field.LanguageId', 'EN-US', 'Language', '1', NOW(), NOW(), 'SYSTEM'),
    ('field.LanguageId', 'PT-BR', 'Idioma', '1', NOW(), NOW(), 'SYSTEM'),
    ('field.IsActive', 'EN-US', 'Is Active', '1', NOW(), NOW(), 'SYSTEM'),
    ('field.IsActive', 'PT-BR', 'Ativo', '1', NOW(), NOW(), 'SYSTEM'),
    ('field.IsBlocked', 'EN-US', 'Is Blocked', '1', NOW(), NOW(), 'SYSTEM'),
    ('field.IsBlocked', 'PT-BR', 'Bloqueado', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.dashboard.title', 'EN-US', 'Dashboard', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.dashboard.title', 'PT-BR', 'Dashboard', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.roles.title', 'EN-US', 'Security Roles', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.roles.title', 'PT-BR', 'Perfis de Seguranca', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.users.title', 'EN-US', 'Users', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.users.title', 'PT-BR', 'Usuarios', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.parties.title', 'EN-US', 'Parties', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.parties.title', 'PT-BR', 'Partes', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.customers.title', 'EN-US', 'Customers', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.customers.title', 'PT-BR', 'Clientes', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.vendors.title', 'EN-US', 'Vendors', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.vendors.title', 'PT-BR', 'Fornecedores', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.products.title', 'EN-US', 'Products and Services', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.products.title', 'PT-BR', 'Produtos e Servicos', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.servicecodes.title', 'EN-US', 'Service Codes', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.servicecodes.title', 'PT-BR', 'Codigos de Servico', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.serviceinvoices.title', 'EN-US', 'Service Invoices', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.serviceinvoices.title', 'PT-BR', 'Faturas de Servico', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.purchaseorders.title', 'EN-US', 'Material Purchase Orders', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.purchaseorders.title', 'PT-BR', 'Ordens de Compra de Materiais', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.servicepurchaseorders.title', 'EN-US', 'Service Purchase Orders', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.servicepurchaseorders.title', 'PT-BR', 'Tomada de Servicos', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.journals.title', 'EN-US', 'General Journals', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.journals.title', 'PT-BR', 'Diarios Gerais', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.reports.title', 'EN-US', 'Reports', '1', NOW(), NOW(), 'SYSTEM'),
    ('module.reports.title', 'PT-BR', 'Relatorios', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.group.sysadmin', 'EN-US', 'System Administration', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.group.sysadmin', 'PT-BR', 'Administracao de Sistemas', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.group.ar', 'EN-US', 'Accounts Receivable', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.group.ar', 'PT-BR', 'Contas a Receber', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.group.ap', 'EN-US', 'Accounts Payable', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.group.ap', 'PT-BR', 'Contas a Pagar', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.group.general', 'EN-US', 'General', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.group.general', 'PT-BR', 'Geral', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.group.reports', 'EN-US', 'Reports', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.group.reports', 'PT-BR', 'Relatorios', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.sys.security', 'EN-US', 'Security', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.sys.security', 'PT-BR', 'Seguranca', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.sys.setup', 'EN-US', 'Setup', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.sys.setup', 'PT-BR', 'Configuracoes', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.sys.roles', 'EN-US', 'Security Roles', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.sys.roles', 'PT-BR', 'Perfis de Seguranca', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.sys.users', 'EN-US', 'Users', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.sys.users', 'PT-BR', 'Usuarios', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.sys.gab', 'EN-US', 'Global Address Book', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.sys.gab', 'PT-BR', 'Global Address Book', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.sys.parties', 'EN-US', 'Parties', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.sys.parties', 'PT-BR', 'Partes', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.ar.customers', 'EN-US', 'Customers', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.ar.customers', 'PT-BR', 'Clientes', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.ar.invoices', 'EN-US', 'Service Invoices', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.ar.invoices', 'PT-BR', 'Faturas de Servico', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.ap.vendors', 'EN-US', 'Vendors', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.ap.vendors', 'PT-BR', 'Fornecedores', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.ap.purchases', 'EN-US', 'Material Purchase Orders', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.ap.purchases', 'PT-BR', 'Ordens de Compra de Materiais', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.ap.servicepurchases', 'EN-US', 'Service Procurement', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.ap.servicepurchases', 'PT-BR', 'Tomada de Servicos', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.general.dashboard', 'EN-US', 'Dashboard', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.general.dashboard', 'PT-BR', 'Dashboard', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.general.products', 'EN-US', 'Products', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.general.products', 'PT-BR', 'Produtos', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.general.servicecodes', 'EN-US', 'Service Codes', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.general.servicecodes', 'PT-BR', 'Codigos de Servico', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.general.journals', 'EN-US', 'General Journals', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.general.journals', 'PT-BR', 'Diarios Gerais', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.reports.hub', 'EN-US', 'Reports', '1', NOW(), NOW(), 'SYSTEM'),
    ('menu.reports.hub', 'PT-BR', 'Relatorios', '1', NOW(), NOW(), 'SYSTEM'),
    ('login.subtitle', 'EN-US', 'Technology consulting, e-commerce operations and financial control in one web workspace.', '1', NOW(), NOW(), 'SYSTEM'),
    ('login.subtitle', 'PT-BR', 'Consultoria, operacao de e-commerce e controle financeiro em um unico ERP web.', '1', NOW(), NOW(), 'SYSTEM'),
    ('login.defaultaccess', 'EN-US', 'Default access', '1', NOW(), NOW(), 'SYSTEM'),
    ('login.defaultaccess', 'PT-BR', 'Acesso padrao', '1', NOW(), NOW(), 'SYSTEM'),
    ('sidebar.subtitle', 'EN-US', 'AX-style operational workspace', '1', NOW(), NOW(), 'SYSTEM'),
    ('sidebar.subtitle', 'PT-BR', 'Workspace operacional no estilo AX', '1', NOW(), NOW(), 'SYSTEM'),
    ('header.operations', 'EN-US', 'Operations', '1', NOW(), NOW(), 'SYSTEM'),
    ('header.operations', 'PT-BR', 'Operacoes', '1', NOW(), NOW(), 'SYSTEM');

INSERT IGNORE INTO SysLabelText (LabelKey, LanguageId, TextValue, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
VALUES
    ('tab.overview', 'EN-US', 'Overview', '1', NOW(), NOW(), 'SYSTEM'),
    ('tab.overview', 'PT-BR', 'Visao Geral', '1', NOW(), NOW(), 'SYSTEM'),
    ('tab.general', 'EN-US', 'General', '1', NOW(), NOW(), 'SYSTEM'),
    ('tab.general', 'PT-BR', 'Geral', '1', NOW(), NOW(), 'SYSTEM'),
    ('form.section.identification', 'EN-US', 'Identification', '1', NOW(), NOW(), 'SYSTEM'),
    ('form.section.identification', 'PT-BR', 'Identificacao', '1', NOW(), NOW(), 'SYSTEM'),
    ('form.section.dates', 'EN-US', 'Dates', '1', NOW(), NOW(), 'SYSTEM'),
    ('form.section.dates', 'PT-BR', 'Datas', '1', NOW(), NOW(), 'SYSTEM'),
    ('form.section.financial', 'EN-US', 'Financial', '1', NOW(), NOW(), 'SYSTEM'),
    ('form.section.financial', 'PT-BR', 'Financeiro', '1', NOW(), NOW(), 'SYSTEM'),
    ('form.section.general', 'EN-US', 'General', '1', NOW(), NOW(), 'SYSTEM'),
    ('form.section.general', 'PT-BR', 'Geral', '1', NOW(), NOW(), 'SYSTEM'),
    ('form.section.control', 'EN-US', 'Control', '1', NOW(), NOW(), 'SYSTEM'),
    ('form.section.control', 'PT-BR', 'Controle', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.JournalDate', 'EN-US', 'Journal Date', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.JournalDate', 'PT-BR', 'Data do Diario', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.Posted', 'EN-US', 'Posted', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.Posted', 'PT-BR', 'Lancado', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.Description', 'EN-US', 'Description', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.Description', 'PT-BR', 'Descricao', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.Amount', 'EN-US', 'Amount', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.Amount', 'PT-BR', 'Valor', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.ReferenceName', 'EN-US', 'Reference', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.ReferenceName', 'PT-BR', 'Referencia', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.Month', 'EN-US', 'Month', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.Month', 'PT-BR', 'Mes', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.TotalBilling', 'EN-US', 'Total Billing', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.TotalBilling', 'PT-BR', 'Faturamento Total', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.TotalExpenses', 'EN-US', 'Total Expenses', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.TotalExpenses', 'PT-BR', 'Despesas Totais', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.NetBalance', 'EN-US', 'Net Balance', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.NetBalance', 'PT-BR', 'Saldo Liquido', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.Taxes', 'EN-US', 'Taxes', '1', NOW(), NOW(), 'SYSTEM'),
    ('column.Taxes', 'PT-BR', 'Impostos', '1', NOW(), NOW(), 'SYSTEM');