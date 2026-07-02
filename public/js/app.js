const appState = {
    authUser: null,
    currentView: 'dashboard',
    currentEntity: null,
    currentModule: null,
    labels: {},
    lookups: {
        roles: [],
        companies: [],
        customers: [],
        vendors: [],
        products: [],
        serviceCodes: [],
        taxTypes: [],
        languages: [],
        ledgerCategories: []
    }
};

const genericModules = {
    roles: {
        endpoint: '/api/roles',
        keyField: 'RecId',
        columns: ['RoleCode', 'Name', 'Description', 'IsActive'],
        fields: [
            { name: 'RoleCode', label: 'Role Code', type: 'text', required: true },
            { name: 'Name', label: 'Role Name', type: 'text', required: true },
            { name: 'Description', label: 'Description', type: 'text', required: true },
            { name: 'IsActive', label: 'Is Active', type: 'select', options: statusOptions(['1', '0']) }
        ]
    },
    users: {
        endpoint: '/api/users',
        keyField: 'RecId',
        columns: ['UserId', 'UserName', 'Email', 'RoleName', 'LanguageName', 'IsActive', 'IsBlocked'],
        fields: [
            { name: 'UserId', label: 'User ID', type: 'text', required: false, readOnly: true },
            { name: 'UserName', label: 'User Name', type: 'text', required: true },
            { name: 'Email', label: 'Email', type: 'email', required: true },
            { name: 'Password', label: 'Password', type: 'password' },
            { name: 'RoleId', label: 'Role', type: 'select', options: () => appState.lookups.roles.map(role => ({ value: role.RecId, label: role.Name })) },
            { name: 'LanguageId', label: 'Language', type: 'select', options: () => appState.lookups.languages.map(item => ({ value: item.LanguageId, label: item.Name })) },
            { name: 'IsActive', label: 'Is Active', type: 'select', options: statusOptions(['1', '0']) },
            { name: 'IsBlocked', label: 'Is Blocked', type: 'select', options: statusOptions(['0', '1']) }
        ]
    },
    'cors-origins': {
        endpoint: '/api/cors-origins',
        keyField: 'RecId',
        allowDelete: true,
        columns: ['Origin', 'Description', 'IsActive'],
        fields: [
            { name: 'Origin', label: 'Origem (URL / IP)', type: 'text', required: true },
            { name: 'Description', label: 'Descrição', type: 'text' },
            { name: 'IsActive', label: 'Ativo', type: 'select', options: statusOptions(['1', '0']) }
        ]
    },
    labels: {
        endpoint: '/api/localization/label-texts',
        keyField: 'RecId',
        allowDelete: true,
        columns: ['LabelKey', 'LanguageId', 'LanguageName', 'TextValue', 'IsActive'],
        fields: [
            { name: 'LabelKey', label: 'Label Key', type: 'text', required: true },
            { name: 'LanguageId', label: 'Language', type: 'select', options: () => appState.lookups.languages.map(item => ({ value: item.LanguageId, label: item.Name })) },
            { name: 'TextValue', label: 'Text Value', type: 'textarea', required: true },
            { name: 'IsActive', label: 'Is Active', type: 'select', options: statusOptions(['1', '0']) }
        ]
    },
    'number-sequences': {
        endpoint: '/api/number-sequences',
        keyField: 'RecId',
        allowDelete: true,
        columns: ['ObjectCode', 'ObjectName', 'CurrentNumber', 'NextNumber', 'FormatMask', 'IsActive'],
        fields: [
            { name: 'ObjectCode', label: 'Object Code', type: 'text', required: true },
            { name: 'ObjectName', label: 'Object Name', type: 'text', required: true },
            { name: 'CurrentNumber', label: 'Current Number', type: 'number', required: true },
            { name: 'NextNumber', label: 'Next Number', type: 'number', required: true },
            { name: 'FormatMask', label: 'Format', type: 'text', required: true },
            { name: 'IsActive', label: 'Is Active', type: 'select', options: statusOptions(['1', '0']) }
        ]
    },
    company: {
        endpoint: '/api/companies',
        keyField: 'RecId',
        allowDelete: true,
        columns: ['Alias', 'LegalName', 'TradeName', 'Cnpj', 'InitialBalance', 'InitialBalanceDate', 'IsDefault', 'IsActive'],
        fields: [
            { name: 'Alias', label: 'Alias', type: 'text', required: true },
            { name: 'LegalName', label: 'Legal Name', type: 'text', required: true },
            { name: 'TradeName', label: 'Trade Name', type: 'text' },
            { name: 'Cnpj', label: 'CNPJ', type: 'text', required: true },
            { name: 'FiscalZipCode', label: 'Fiscal Zip Code', type: 'text', required: true },
            { name: 'FiscalStreet', label: 'Fiscal Street', type: 'text', required: true },
            { name: 'FiscalStreetNumber', label: 'Fiscal Street Number', type: 'text', required: true },
            { name: 'FiscalComplement', label: 'Fiscal Complement', type: 'text' },
            { name: 'FiscalDistrict', label: 'Fiscal District', type: 'text', required: true },
            { name: 'FiscalCity', label: 'Fiscal City', type: 'text', required: true },
            { name: 'FiscalState', label: 'Fiscal State', type: 'text', required: true },
            { name: 'BillingZipCode', label: 'Billing Zip Code', type: 'text', required: true },
            { name: 'BillingStreet', label: 'Billing Street', type: 'text', required: true },
            { name: 'BillingStreetNumber', label: 'Billing Street Number', type: 'text', required: true },
            { name: 'BillingComplement', label: 'Billing Complement', type: 'text' },
            { name: 'BillingDistrict', label: 'Billing District', type: 'text', required: true },
            { name: 'BillingCity', label: 'Billing City', type: 'text', required: true },
            { name: 'BillingState', label: 'Billing State', type: 'text', required: true },
            { name: 'MainLogoUrl', label: 'Main Logo URL', type: 'text' },
            { name: 'InitialBalance', label: 'Initial Balance', type: 'number' },
            { name: 'InitialBalanceDate', label: 'Initial Balance Date', type: 'date' },
            { name: 'IsDefault', label: 'Default Company', type: 'select', options: statusOptions(['1', '0']) },
            { name: 'IsActive', label: 'Is Active', type: 'select', options: statusOptions(['1', '0']) }
        ]
    },
    products: {
        endpoint: '/api/products',
        keyField: 'RecId',
        columns: ['ItemId', 'Name', 'UnitOfMeasure', 'ItemType', 'SalesPrice', 'CostPrice', 'IsActive'],
        fields: [
            { name: 'ItemId', label: 'Item ID', type: 'text', required: false, readOnly: true },
            { name: 'Name', label: 'Name', type: 'text', required: true },
            { name: 'Description', label: 'Description', type: 'textarea' },
            { name: 'UnitOfMeasure', label: 'Unit Of Measure', type: 'text', required: true },
            { name: 'ItemType', label: 'Item Type', type: 'select', options: () => [{ value: 'I', label: t('option.item', 'Item') }, { value: 'S', label: t('option.service', 'Service') }] },
            { name: 'SalesPrice', label: 'Sales Price', type: 'number' },
            { name: 'CostPrice', label: 'Cost Price', type: 'number' },
            { name: 'IsActive', label: 'Is Active', type: 'select', options: statusOptions(['1', '0']) },
            { name: 'IsBlocked', label: 'Is Blocked', type: 'select', options: statusOptions(['0', '1']) }
        ]
    },
    'service-codes': {
        endpoint: '/api/service-codes',
        keyField: 'RecId',
        columns: ['ServiceCode', 'Name', 'DefaultPrice', 'IsActive'],
        fields: [
            { name: 'ServiceCode', label: 'Service Code', type: 'text', required: false, readOnly: true },
            { name: 'Name', label: 'Name', type: 'text', required: true },
            { name: 'Description', label: 'Description', type: 'textarea' },
            { name: 'DefaultPrice', label: 'Default Price', type: 'number' },
            { name: 'IsActive', label: 'Is Active', type: 'select', options: statusOptions(['1', '0']) },
            { name: 'IsBlocked', label: 'Is Blocked', type: 'select', options: statusOptions(['0', '1']) }
        ]
    },
    'tax-types': {
        endpoint: '/api/tax-types',
        keyField: 'RecId',
        columns: ['TaxTypeCode', 'Name', 'Description', 'IsActive'],
        fields: [
            { name: 'TaxTypeCode', label: 'Tax Type Code', type: 'text', required: false, readOnly: true },
            { name: 'Name', label: 'Name', type: 'text', required: true },
            { name: 'Description', label: 'Description', type: 'textarea' },
            { name: 'IsActive', label: 'Is Active', type: 'select', options: statusOptions(['1', '0']) },
            { name: 'IsBlocked', label: 'Is Blocked', type: 'select', options: statusOptions(['0', '1']) }
        ]
    },
    'ledger-categories': {
        endpoint: '/api/ledger-categories',
        keyField: 'RecId',
        columns: ['CategoryCode', 'Name', 'CategoryType', 'Description', 'IsActive'],
        fields: [
            { name: 'CategoryCode', label: 'Código', labelKey: 'ledgerCategory.categoryCode', type: 'text', required: true },
            { name: 'Name', label: 'Nome', labelKey: 'ledgerCategory.categoryName', type: 'text', required: true },
            { name: 'Description', label: 'Descrição', labelKey: 'ledgerCategory.description', type: 'textarea' },
            { name: 'CategoryType', label: 'Tipo', labelKey: 'ledgerCategory.categoryType', type: 'select', options: () => [
                { value: 'E', label: t('ledgerCategory.type.expense', 'Despesa') },
                { value: 'R', label: t('ledgerCategory.type.receipt', 'Receita') },
                { value: 'N', label: t('ledgerCategory.type.neutral', 'Neutro') }
            ] },
            { name: 'IsActive', label: 'Ativo', type: 'select', options: statusOptions(['1', '0']) }
        ]
    },
    'bank-accounts': {
        endpoint: '/api/bank-accounts',
        keyField: 'RecId',
        columns: ['BankName', 'AccountNumber', 'AccountDigit', 'Description', 'IsActive'],
        fields: [
            { name: 'BankName', label: 'Bank Name', type: 'text', required: true },
            { name: 'AccountNumber', label: 'Account Number', type: 'text', required: true },
            { name: 'AccountDigit', label: 'Digit', type: 'text' },
            { name: 'Description', label: 'Description', type: 'text' },
            { name: 'IsActive', label: 'Is Active', type: 'select', options: statusOptions(['1', '0']) },
            { name: 'IsBlocked', label: 'Is Blocked', type: 'select', options: statusOptions(['0', '1']) }
        ]
    }
};

const documentModules = {
    'service-invoices': {
        endpoint: '/api/service-invoices',
        detailEndpoint: '/api/service-invoices/',
        listColumns: ['InvoiceId', 'CompanyAlias', 'InvoiceNumber', 'CustomerName', 'InvoiceDate', 'DueDate', 'Status', 'TotalAmount'],
        lineMode: 'grid',
        sections: [
            { key: 'identification', titleKey: 'form.section.identification', fallback: 'Identification' },
            { key: 'billing', titleKey: 'form.section.billingdata', fallback: 'Billing Data' },
            { key: 'other', titleKey: 'form.section.other', fallback: 'Other' }
        ],
        headerFields: [
            { name: 'CompanyRecId', label: 'Company', labelKey: 'field.InvoiceCompany', section: 'identification', type: 'select', options: () => companyOptions() },
            { name: 'CustAccount', label: 'Customer', labelKey: 'field.InvoiceCustomer', section: 'identification', type: 'select', options: () => appState.lookups.customers.map(item => ({ value: item.CustAccount, label: `${item.CustAccount} - ${item.Name}` })) },
            { name: 'InvoiceId', label: 'Invoice Id', section: 'identification', type: 'text', readOnly: true },
            { name: 'InvoiceNumber', label: 'NFSe Number', labelKey: 'field.NfseNumber', section: 'billing', type: 'text' },
            { name: 'InvoiceDate', label: 'Invoice Date', section: 'billing', type: 'date' },
            { name: 'DueDate', label: 'Due Date', section: 'billing', type: 'date' },
            { name: 'BillingAmount', label: 'Amount', labelKey: 'field.BillingAmount', section: 'billing', type: 'number', readOnly: true },
            { name: 'DeductionAmount', label: 'Discount', section: 'billing', type: 'number' },
            { name: 'TotalAmount', label: 'Total', section: 'billing', type: 'number', readOnly: true },
            { name: 'Status', label: 'Status', section: 'billing', type: 'select', options: serviceInvoiceStatusOptions() },
            { name: 'Notes', label: 'Notes', section: 'other', type: 'textarea' }
        ],
        lineFields: [
            { name: 'ServiceCodeId', label: 'Service Code', type: 'select', required: true, emptyOption: { labelKey: 'option.select', label: 'Select' }, options: () => appState.lookups.serviceCodes.map(item => ({ value: item.RecId, label: `${item.ServiceCode} - ${item.Name}` })) },
            { name: 'Description', label: 'Description', type: 'text' },
            { name: 'LineAmount', label: 'Line Amount', type: 'number', required: true }
        ]
    },
    'purchase-orders': {
        endpoint: '/api/purchase-orders/materials',
        detailEndpoint: '/api/purchase-orders/materials/',
        listColumns: ['PurchId', 'CompanyAlias', 'PurchNumber', 'VendorName', 'PurchDate', 'DueDate', 'Status', 'TotalAmount'],
        fixedPayload: { PurchType: 'I' },
        lineMode: 'grid',
        sections: [
            { key: 'identification', titleKey: 'form.section.identification', fallback: 'Identification' },
            { key: 'billing', titleKey: 'form.section.billingdata', fallback: 'Billing Data' },
            { key: 'other', titleKey: 'form.section.other', fallback: 'Other' }
        ],
        headerFields: [
            { name: 'CompanyRecId', label: 'Company', labelKey: 'field.InvoiceCompany', section: 'identification', type: 'select', options: () => companyOptions() },
            { name: 'VendAccount', label: 'Vendor', labelKey: 'field.InvoiceVendor', section: 'identification', type: 'select', options: () => appState.lookups.vendors.map(item => ({ value: item.VendAccount, label: `${item.VendAccount} - ${item.Name}` })) },
            { name: 'PurchId', label: 'Purchase Id', labelKey: 'field.PurchId', section: 'identification', type: 'text', readOnly: true },
            { name: 'PurchNumber', label: 'Order Or Invoice Number', labelKey: 'field.PurchNumber', section: 'billing', type: 'text' },
            { name: 'PurchDate', label: 'Purchase Date', labelKey: 'field.PurchDate', section: 'billing', type: 'date' },
            { name: 'DueDate', label: 'Due Date', section: 'billing', type: 'date' },
            { name: 'BillingAmount', label: 'Amount', labelKey: 'field.BillingAmount', section: 'billing', type: 'number', readOnly: true },
            { name: 'DeductionAmount', label: 'Discount', section: 'billing', type: 'number' },
            { name: 'TotalAmount', label: 'Total', section: 'billing', type: 'number', readOnly: true },
            { name: 'Status', label: 'Status', section: 'billing', type: 'select', options: financialStatusOptions() },
            { name: 'Notes', label: 'Notes', section: 'other', type: 'textarea' }
        ],
        lineFields: [
            { name: 'ItemId', label: 'Item', required: true, emptyOption: { labelKey: 'option.select', label: 'Select' }, type: 'select', options: () => appState.lookups.products.filter(item => item.ItemType !== 'S').map(item => ({ value: item.ItemId, label: `${item.ItemId} - ${item.Name}` })) },
            { name: 'Description', label: 'Description', type: 'text' },
            { name: 'Quantity', label: 'Quantity', type: 'number' },
            { name: 'UnitPrice', label: 'Unit Price', type: 'number' },
            { name: 'LineAmount', label: 'Line Amount', type: 'number' }
        ]
    },
    'service-purchase-orders': {
        endpoint: '/api/purchase-orders/services',
        detailEndpoint: '/api/purchase-orders/services/',
        listColumns: ['PurchId', 'CompanyAlias', 'PurchNumber', 'VendorName', 'PurchDate', 'DueDate', 'Status', 'TotalAmount'],
        fixedPayload: { PurchType: 'S' },
        lineMode: 'grid',
        sections: [
            { key: 'identification', titleKey: 'form.section.identification', fallback: 'Identification' },
            { key: 'billing', titleKey: 'form.section.billingdata', fallback: 'Billing Data' },
            { key: 'other', titleKey: 'form.section.other', fallback: 'Other' }
        ],
        headerFields: [
            { name: 'CompanyRecId', label: 'Company', labelKey: 'field.InvoiceCompany', section: 'identification', type: 'select', options: () => companyOptions() },
            { name: 'VendAccount', label: 'Vendor', labelKey: 'field.InvoiceVendor', section: 'identification', type: 'select', options: () => appState.lookups.vendors.map(item => ({ value: item.VendAccount, label: `${item.VendAccount} - ${item.Name}` })) },
            { name: 'PurchId', label: 'Purchase Id', labelKey: 'field.PurchId', section: 'identification', type: 'text', readOnly: true },
            { name: 'PurchNumber', label: 'Service Request Or Invoice Number', labelKey: 'field.PurchNumber', section: 'billing', type: 'text' },
            { name: 'PurchDate', label: 'Purchase Date', labelKey: 'field.PurchDate', section: 'billing', type: 'date' },
            { name: 'DueDate', label: 'Due Date', section: 'billing', type: 'date' },
            { name: 'BillingAmount', label: 'Amount', labelKey: 'field.BillingAmount', section: 'billing', type: 'number', readOnly: true },
            { name: 'DeductionAmount', label: 'Discount', section: 'billing', type: 'number' },
            { name: 'TotalAmount', label: 'Total', section: 'billing', type: 'number', readOnly: true },
            { name: 'Status', label: 'Status', section: 'billing', type: 'select', options: financialStatusOptions() },
            { name: 'Notes', label: 'Notes', section: 'other', type: 'textarea' }
        ],
        lineFields: [
            { name: 'ServiceCodeId', label: 'Service Code', required: true, emptyOption: { labelKey: 'option.select', label: 'Select' }, type: 'select', options: () => appState.lookups.serviceCodes.map(item => ({ value: item.RecId, label: `${item.ServiceCode} - ${item.Name}` })) },
            { name: 'Description', label: 'Description', type: 'text' },
            { name: 'Quantity', label: 'Quantity', type: 'number' },
            { name: 'UnitPrice', label: 'Unit Price', type: 'number' },
            { name: 'LineAmount', label: 'Line Amount', type: 'number' }
        ]
    },
    'receipt-journals': {
        endpoint: '/api/journals/receipt',
        detailEndpoint: '/api/journals/receipt/',
        listColumns: ['JournalBatchNumber', 'CompanyAlias', 'Description', 'JournalDate', 'Posted'],
        headerFields: [
            { name: 'JournalBatchNumber', label: 'Journal Batch Number', type: 'text', readOnly: true },
            { name: 'CompanyRecId', label: 'Company', type: 'select', options: () => companyOptions() },
            { name: 'Description', label: 'Description', type: 'text' },
            { name: 'JournalDate', label: 'Journal Date', type: 'date' },
            { name: 'Posted', label: 'Lançado', type: 'checkbox' }
        ],
        lineFields: [
            { name: 'TransDate', label: 'Trans Date', type: 'date' },
            { name: 'DueDate', label: 'Due Date', type: 'date' },
            { name: 'Voucher', label: 'Voucher', type: 'text' },
            { name: 'Description', label: 'Description', type: 'text' },
            { name: 'LedgerCategoryId', label: 'Categoria de Lançamento', type: 'select', emptyOption: true, options: () => (appState.lookups.ledgerCategories || []).filter(item => String(item.type || '').toUpperCase() === 'R').map(item => ({ value: item.id, label: `${item.code} - ${item.name}` })) },
            { name: 'CustAccount', label: 'Customer', type: 'select', options: () => appState.lookups.customers.map(item => ({ value: item.CustAccount, label: `${item.CustAccount} - ${item.Name}` })) },
            { name: 'ReceivedFlag', label: 'Received', type: 'select', options: [{ value: '1', label: t('option.yes', 'Yes') }, { value: '0', label: t('option.no', 'No') }] },
            { name: 'AmountCurDebit', label: 'Debit', type: 'number' },
            { name: 'AmountCurCredit', label: 'Credit', type: 'number' },
            { name: 'Status', label: 'Status', type: 'select', options: financialStatusOptions() },
            { name: 'ServiceInvoiceRecId', label: 'Service Invoice Ref', type: 'hidden' }
        ]
    },
    'payment-journals': {
        endpoint: '/api/journals/payment',
        detailEndpoint: '/api/journals/payment/',
        listColumns: ['JournalBatchNumber', 'CompanyAlias', 'Description', 'JournalDate', 'Posted'],
        headerFields: [
            { name: 'JournalBatchNumber', label: 'Journal Batch Number', type: 'text', readOnly: true },
            { name: 'CompanyRecId', label: 'Company', type: 'select', options: () => companyOptions() },
            { name: 'Description', label: 'Description', type: 'text' },
            { name: 'JournalDate', label: 'Journal Date', type: 'date' },
            { name: 'Posted', label: 'Lançado', type: 'checkbox' }
        ],
        lineFields: [
            { name: 'TransDate', label: 'Trans Date', type: 'date' },
            { name: 'DueDate', label: 'Due Date', type: 'date' },
            { name: 'Voucher', label: 'Voucher', type: 'text' },
            { name: 'Description', label: 'Description', type: 'text' },
            { name: 'LedgerCategoryId', label: 'Categoria de Lançamento', type: 'select', emptyOption: true, options: () => appState.lookups.ledgerCategories.map(item => ({ value: item.id, label: `${item.code} - ${item.name}` })) },
            { name: 'VendAccount', label: 'Vendor', type: 'select', options: () => appState.lookups.vendors.map(item => ({ value: item.VendAccount, label: `${item.VendAccount} - ${item.Name}` })) },
            { name: 'PaidFlag', label: 'Paid', type: 'select', options: [{ value: '1', label: t('option.yes', 'Yes') }, { value: '0', label: t('option.no', 'No') }] },
            { name: 'AmountCurDebit', label: 'Debit', type: 'number' },
            { name: 'AmountCurCredit', label: 'Credit', type: 'number' },
            { name: 'Status', label: 'Status', type: 'select', options: financialStatusOptions() }
        ]
    },
    'tax-journals': {
        endpoint: '/api/journals/tax',
        detailEndpoint: '/api/journals/tax/',
        listColumns: ['JournalBatchNumber', 'CompanyAlias', 'Description', 'JournalDate', 'Posted'],
        headerFields: [
            { name: 'JournalBatchNumber', label: 'Journal Batch Number', type: 'text', readOnly: true },
            { name: 'CompanyRecId', label: 'Company', type: 'select', options: () => companyOptions() },
            { name: 'Description', label: 'Description', type: 'text' },
            { name: 'JournalDate', label: 'Journal Date', type: 'date' },
            { name: 'Posted', label: 'Lançado', type: 'checkbox' }
        ],
        lineFields: [
            { name: 'TransDate', label: 'Trans Date', type: 'date' },
            { name: 'DueDate', label: 'Due Date', type: 'date' },
            { name: 'Voucher', label: 'Voucher', type: 'text' },
            { name: 'Description', label: 'Description', type: 'text' },
            { name: 'TaxTypeId', label: 'Tax Type', type: 'select', options: () => appState.lookups.taxTypes.map(item => ({ value: item.RecId, label: `${item.TaxTypeCode} - ${item.Name}` })) },
            { name: 'LedgerCategoryId', label: 'Categoria de Lançamento', type: 'select', emptyOption: true, options: () => appState.lookups.ledgerCategories.map(item => ({ value: item.id, label: `${item.code} - ${item.name}` })) },
            { name: 'AmountCurDebit', label: 'Debit', type: 'number' },
            { name: 'AmountCurCredit', label: 'Credit', type: 'number' },
            { name: 'Status', label: 'Status', type: 'select', options: financialStatusOptions() }
        ]
    },
    journals: {
        endpoint: '/api/journals',
        detailEndpoint: '/api/journals/',
        listColumns: ['JournalBatchNumber', 'CompanyAlias', 'Description', 'JournalDate', 'Posted'],
        headerFields: [
            { name: 'JournalBatchNumber', label: 'Journal Batch Number', type: 'text', readOnly: true },
            { name: 'CompanyRecId', label: 'Company', type: 'select', options: () => companyOptions() },
            { name: 'Description', label: 'Description', type: 'text' },
            { name: 'JournalDate', label: 'Journal Date', type: 'date' },
            { name: 'Posted', label: 'Lançado', type: 'checkbox' }
        ],
        lineFields: [
            { name: 'TransDate', label: 'Trans Date', type: 'date' },
            { name: 'DueDate', label: 'Due Date', type: 'date' },
            { name: 'Voucher', label: 'Voucher', type: 'text' },
            { name: 'Description', label: 'Description', type: 'text' },
            { name: 'LedgerCategoryId', label: 'Categoria de Lançamento', type: 'select', emptyOption: true, options: () => appState.lookups.ledgerCategories.map(item => ({ value: item.id, label: `${item.code} - ${item.name}` })) },
            { name: 'AmountCurDebit', label: 'Debit', type: 'number' },
            { name: 'AmountCurCredit', label: 'Credit', type: 'number' },
            { name: 'Status', label: 'Status', type: 'select', options: financialStatusOptions() }
        ]
    }
};

const reportModules = {
    accountsReceivable: {
        titleKey: 'report.accountsReceivable.title',
        fallback: 'Contas a Receber',
        endpoint: '/api/reports/accounts-receivable',
        filters: [
            { name: 'CustAccount', label: 'Cliente', labelKey: 'filter.customer', type: 'select', emptyOption: true, options: () => appState.lookups.customers.map(item => ({ value: item.CustAccount, label: `${item.CustAccount} - ${item.Name}` })) },
            { name: 'DueDateFrom', label: 'Vencimento De', labelKey: 'filter.dueDateFrom', type: 'date' },
            { name: 'DueDateTo', label: 'Vencimento Até', labelKey: 'filter.dueDateTo', type: 'date' },
            { name: 'Status', label: 'Status', labelKey: 'filter.status', type: 'select', options: financialStatusOptions(true) }
        ]
    },
    accountsPayable: {
        titleKey: 'report.accountsPayable.title',
        fallback: 'Contas a Pagar',
        endpoint: '/api/reports/accounts-payable',
        filters: [
            { name: 'VendAccount', label: 'Fornecedor', labelKey: 'filter.vendor', type: 'select', emptyOption: true, options: () => appState.lookups.vendors.map(item => ({ value: item.VendAccount, label: `${item.VendAccount} - ${item.Name}` })) },
            { name: 'DueDateFrom', label: 'Vencimento De', labelKey: 'filter.dueDateFrom', type: 'date' },
            { name: 'DueDateTo', label: 'Vencimento Até', labelKey: 'filter.dueDateTo', type: 'date' },
            { name: 'Status', label: 'Status', labelKey: 'filter.status', type: 'select', options: financialStatusOptions(true) }
        ]
    },
    billingByPeriod: {
        titleKey: 'report.billingByPeriod.title',
        fallback: 'Faturamento por Período',
        endpoint: '/api/reports/billing-by-period',
        filters: [
            { name: 'FromDate', label: 'Data Inicial', labelKey: 'filter.fromDate', type: 'date' },
            { name: 'ToDate', label: 'Data Final', labelKey: 'filter.toDate', type: 'date' },
            { name: 'Status', label: 'Status', labelKey: 'filter.status', type: 'select', options: financialStatusOptions(true) }
        ]
    },
    billingByCustomer: {
        titleKey: 'report.billingByCustomer.title',
        fallback: 'Faturamento por Cliente',
        endpoint: '/api/reports/billing-by-customer',
        filters: [
            { name: 'CustAccount', label: 'Cliente', labelKey: 'filter.customer', type: 'select', emptyOption: true, options: () => appState.lookups.customers.map(item => ({ value: item.CustAccount, label: `${item.CustAccount} - ${item.Name}` })) },
            { name: 'FromDate', label: 'Data Inicial', labelKey: 'filter.fromDate', type: 'date' },
            { name: 'ToDate', label: 'Data Final', labelKey: 'filter.toDate', type: 'date' }
        ]
    },
    expensesByPeriod: {
        titleKey: 'report.expensesByPeriod.title',
        fallback: 'Despesas por Período',
        endpoint: '/api/reports/expenses-by-period',
        filters: [
            { name: 'VendAccount', label: 'Fornecedor', labelKey: 'filter.vendor', type: 'select', emptyOption: true, options: () => appState.lookups.vendors.map(item => ({ value: item.VendAccount, label: `${item.VendAccount} - ${item.Name}` })) },
            { name: 'LedgerCategoryId', label: 'Categoria', labelKey: 'filter.ledgerCategory', type: 'select', emptyOption: true, options: () => appState.lookups.ledgerCategories.map(item => ({ value: item.id, label: item.name })) },
            { name: 'FromDate', label: 'Data Inicial', labelKey: 'filter.fromDate', type: 'date' },
            { name: 'ToDate', label: 'Data Final', labelKey: 'filter.toDate', type: 'date' },
            { name: 'PurchType', label: 'Tipo', labelKey: 'filter.purchType', type: 'select', options: [{ value: '', label: 'Todos' }, { value: 'S', label: 'Serviço' }, { value: 'I', label: 'Material' }] }
        ]
    },
    financialSummary: {
        titleKey: 'report.financialSummary.title',
        fallback: 'Resumo Financeiro',
        endpoint: '/api/reports/financial-summary',
        filters: [
            { name: 'FromDate', label: 'Data Inicial', labelKey: 'filter.fromDate', type: 'date' },
            { name: 'ToDate', label: 'Data Final', labelKey: 'filter.toDate', type: 'date' }
        ]
    },
    profitAndLoss: {
        titleKey: 'report.profitAndLoss.title',
        fallback: 'DRE - Demonstração de Resultado',
        endpoint: '/api/reports/profit-and-loss',
        isDRE: true,
        filters: [
            { name: 'Year', label: 'Ano', labelKey: 'filter.year', type: 'select', options: () => generateYearOptions() },
            { name: 'PeriodMonth', label: 'Mês', labelKey: 'filter.periodMonth', type: 'month' },
            { name: 'FromDate', label: 'Data Inicial', labelKey: 'filter.fromDate', type: 'date' },
            { name: 'ToDate', label: 'Data Final', labelKey: 'filter.toDate', type: 'date' }
        ]
    },
    taxesByPeriod: {
        titleKey: 'report.taxesByPeriod.title',
        fallback: 'Impostos por Período',
        endpoint: '/api/reports/taxes-by-period',
        filters: [
            { name: 'FromDate', label: 'Data Inicial', labelKey: 'filter.fromDate', type: 'date' },
            { name: 'ToDate', label: 'Data Final', labelKey: 'filter.toDate', type: 'date' },
            { name: 'TaxTypeId', label: 'Tipo de Imposto', labelKey: 'filter.taxType', type: 'select', emptyOption: true, options: () => appState.lookups.taxTypes.map(item => ({ value: item.RecId, label: `${item.TaxTypeCode} - ${item.Name}` })) }
        ]
    },
    cashFlow: {
        titleKey: 'report.cashFlow.title',
        fallback: 'Fluxo de Caixa',
        endpoint: '/api/reports/cash-flow',
        filters: [
            { name: 'FromDate', label: 'Data Inicial', labelKey: 'filter.fromDate', type: 'date' },
            { name: 'ToDate', label: 'Data Final', labelKey: 'filter.toDate', type: 'date' }
        ]
    }
};

function generateYearOptions() {
    const currentYear = new Date().getFullYear();
    const years = [];
    for (let y = currentYear; y >= currentYear - 5; y--) {
        years.push({ value: y.toString(), label: y.toString() });
    }
    return [{ value: '', label: 'Todos' }, ...years];
}

const viewMeta = {
    dashboard: { titleKey: 'module.dashboard.title', fallback: 'Painel', subtitle: 'Visao geral financeira e operacional.' },
    roles: { titleKey: 'module.roles.title', fallback: 'Security Roles', subtitle: 'Control role definitions and administrative access profiles.' },
    users: { titleKey: 'module.users.title', fallback: 'Users', subtitle: 'Administrators manage login users and role assignments.' },
    'cors-origins': { titleKey: 'module.corsorigins.title', fallback: 'Origens CORS', subtitle: 'Gerencie as origens permitidas para chamadas de API via CORS.' },
    labels: { titleKey: 'module.labels.title', fallback: 'Labels', subtitle: 'Manage dynamic UI labels for all screens and languages.' },
    'number-sequences': { titleKey: 'module.numbersequences.title', fallback: 'Number Sequences', subtitle: 'Define ID format and next number by object type.' },
    company: { titleKey: 'module.company.title', fallback: 'Company', subtitle: 'Maintain legal, fiscal, billing and branding information for your own company.' },
    parties: { titleKey: 'module.parties.title', fallback: 'Parties', subtitle: 'Global address book with normalized postal and electronic addresses.' },
    customers: { titleKey: 'module.customers.title', fallback: 'Customers', subtitle: 'Cadastro de clientes com grupos gerais, enderecos fiscal/cobranca e contatos.' },
    vendors: { titleKey: 'module.vendors.title', fallback: 'Vendors', subtitle: 'Cadastro de fornecedores com grupos gerais, enderecos fiscal/cobranca e contatos.' },
    products: { titleKey: 'module.products.title', fallback: 'Products and Services', subtitle: 'Manage InventTable items for product and service purchasing.' },
    'service-codes': { titleKey: 'module.servicecodes.title', fallback: 'Service Codes', subtitle: 'Configure service references used by NFSe invoice lines.' },
    'ledger-categories': { titleKey: 'module.ledgercategories.title', fallback: 'Categorias de Lançamento', subtitle: 'Gerencie as categorias contábeis usadas na classificação dos lançamentos.' },
    'tax-types': { titleKey: 'module.taxtypes.title', fallback: 'Tax Types', subtitle: 'Maintain tax type master data used in tax journals.' },
    'bank-accounts': { titleKey: 'module.bankaccounts.title', fallback: 'Bank Accounts', subtitle: 'Maintain company bank accounts used in financial journals.' },
    'service-invoices': { titleKey: 'module.serviceinvoices.title', fallback: 'Service Invoices', subtitle: 'Register service invoices and invoice lines for accounts receivable.' },
    'purchase-orders': { titleKey: 'module.purchaseorders.title', fallback: 'Material Purchase Orders', subtitle: 'Manage purchase orders for products, fixed assets and other material acquisitions.' },
    'service-purchase-orders': { titleKey: 'module.servicepurchaseorders.title', fallback: 'Service Purchase Orders', subtitle: 'Manage service procurement and vendor service engagements in accounts payable.' },
    'receipt-journals': { titleKey: 'module.receiptjournals.title', fallback: 'Accounts Receivable Journals', subtitle: 'Control receivable journals with receipt lines, posting and attachments.' },
    'payment-journals': { titleKey: 'module.paymentjournals.title', fallback: 'Accounts Payable Journals', subtitle: 'Control payable journals with payment lines, posting and attachments.' },
    'tax-journals': { titleKey: 'module.taxjournals.title', fallback: 'Tax Journals', subtitle: 'Control tax journals with tax type allocation and posting workflow.' },
    journals: { titleKey: 'module.journals.title', fallback: 'General Journals', subtitle: 'Post manual revenues and expenses with month-based classification.' },
    reports: { titleKey: 'module.reports.title', fallback: 'Reports', subtitle: 'Financial analytics and control views with totals.' }
};

document.addEventListener('DOMContentLoaded', () => {
    bindGlobalEvents();
    initializeApp();
});

async function initializeApp() {
    await loadLocalization('PT-BR');
    applyStaticLabels();

    if (!window.apiClient.hasToken()) {
        showLogin();
        return;
    }

    try {
        const user = await window.apiClient.get('/api/auth/me');
        await handleAuthenticated(user);
    } catch (error) {
        window.apiClient.clearTokens();
        showLogin();
    }
}

function isJournalModule(moduleKey) {
    return !!moduleKey && moduleKey.indexOf('journals') !== -1;
}

function bindGlobalEvents() {
    document.getElementById('login-form').addEventListener('submit', handleLogin);
    document.getElementById('logout-button').addEventListener('click', handleLogout);
    document.getElementById('logout-top-button').addEventListener('click', handleLogout);

    document.getElementById('sidebar-nav').addEventListener('click', async event => {
        const groupToggle = event.target.closest('[data-group-toggle]');
        if (groupToggle) {
            event.preventDefault();
            const group = groupToggle.closest('.menu-group');
            const willExpand = !group.classList.contains('expanded');

            document.querySelectorAll('.menu-group.expanded').forEach(openGroup => {
                openGroup.classList.remove('expanded');
                const toggle = openGroup.querySelector('[data-group-toggle]');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });

            const isExpanded = willExpand;
            if (willExpand) {
                group.classList.add('expanded');
            }
            groupToggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
            return;
        }

        const submenuToggle = event.target.closest('[data-submenu-toggle]');
        if (submenuToggle) {
            event.preventDefault();
            const submenu = submenuToggle.closest('.submenu-wrap');
            const willExpand = !submenu.classList.contains('expanded');
            const parent = submenu.parentElement;

            if (parent) {
                parent.querySelectorAll('.submenu-wrap.expanded').forEach(openSubmenu => {
                    openSubmenu.classList.remove('expanded');
                    const toggle = openSubmenu.querySelector('[data-submenu-toggle]');
                    if (toggle) {
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            const isExpanded = willExpand;
            if (willExpand) {
                submenu.classList.add('expanded');
            }
            submenuToggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
            return;
        }

        const button = event.target.closest('[data-view]');

        if (!button) {
            return;
        }

        event.preventDefault();
        document.querySelectorAll('.nav-button').forEach(item => item.classList.remove('active'));
        button.classList.add('active');

        const parentGroup = button.closest('.menu-group');
        if (parentGroup) {
            parentGroup.classList.add('expanded');
            const parentToggle = parentGroup.querySelector('[data-group-toggle]');
            if (parentToggle) {
                parentToggle.setAttribute('aria-expanded', 'true');
            }
        }

        const parentSubmenu = button.closest('.submenu-wrap');
        if (parentSubmenu) {
            parentSubmenu.classList.add('expanded');
            const submenuToggleButton = parentSubmenu.querySelector('[data-submenu-toggle]');
            if (submenuToggleButton) {
                submenuToggleButton.setAttribute('aria-expanded', 'true');
            }
        }

        await navigateTo(button.dataset.view);
    });
}

function base64FromBytes(bytes) {
    let binary = '';
    for (let index = 0; index < bytes.length; index += 1) {
        binary += String.fromCharCode(bytes[index]);
    }
    return btoa(binary);
}

async function sha256Bytes(value) {
    const encoded = new TextEncoder().encode(String(value));
    const hashBuffer = await crypto.subtle.digest('SHA-256', encoded);
    return new Uint8Array(hashBuffer);
}

async function encryptCredentialField(value) {
    if (!window.crypto || !window.crypto.subtle) {
        throw new Error('Secure login requires browser crypto support.');
    }

    const sharedKey = String(window.APP_OAUTH_CREDENTIAL_KEY || '');

    if (!sharedKey) {
        throw new Error('Secure credential key is not configured.');
    }

    const keyBytes = await sha256Bytes(sharedKey);
    const cryptoKey = await crypto.subtle.importKey('raw', keyBytes, { name: 'AES-CBC' }, false, ['encrypt']);
    const iv = crypto.getRandomValues(new Uint8Array(16));
    const plainBytes = new TextEncoder().encode(String(value || ''));
    const cipherBuffer = await crypto.subtle.encrypt({ name: 'AES-CBC', iv }, cryptoKey, plainBytes);

    return {
        cipher: base64FromBytes(new Uint8Array(cipherBuffer)),
        iv: base64FromBytes(iv)
    };
}

async function handleLogin(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const payload = Object.fromEntries(formData.entries());

    try {
        const encryptedEmail = await encryptCredentialField(payload.Email || '');
        const encryptedPassword = await encryptCredentialField(payload.Password || '');

        const tokenData = await window.apiClient.post('/api/oauth/token', {
            grant_type: 'password_secure',
            username_cipher: encryptedEmail.cipher,
            username_iv: encryptedEmail.iv,
            password_cipher: encryptedPassword.cipher,
            password_iv: encryptedPassword.iv
        });

        window.apiClient.setTokens(tokenData);
        const user = await window.apiClient.get('/api/auth/me');
        await handleAuthenticated(user);
        notify(t('toast.login.success', 'Login successful.'), 'success');
    } catch (error) {
        window.apiClient.clearTokens();
        notify(error.message, 'error');
    }
}

async function handleLogout() {
    try {
        await window.apiClient.post('/api/auth/logout', {});
    } catch (error) {
        notify(error.message, 'error');
    } finally {
        window.apiClient.clearTokens();
        appState.authUser = null;
        showLogin();
    }
}

async function handleAuthenticated(user) {
    appState.authUser = user;
    await loadLocalization(user.LanguageId || 'PT-BR');
    applyStaticLabels();
    updateUserIdentity();
    document.getElementById('login-screen').classList.add('hidden');
    document.getElementById('erp-shell').classList.remove('hidden');
    await loadLookups();
    await renderSidebarMenu();
    await renderDashboard();
}

function showLogin() {
    document.getElementById('erp-shell').classList.add('hidden');
    document.getElementById('login-screen').classList.remove('hidden');
    document.getElementById('sidebar-user-name').textContent = t('user.guest', 'Guest');
    document.getElementById('header-user-name').textContent = t('user.guest', 'Guest');
    document.getElementById('header-user-role').textContent = t('user.norole', 'No role');
}

function updateUserIdentity() {
    if (!appState.authUser) {
        return;
    }

    document.getElementById('sidebar-user-name').textContent = appState.authUser.UserName;
    document.getElementById('header-user-name').textContent = appState.authUser.UserName;
    document.getElementById('header-user-role').textContent = `${appState.authUser.RoleName} | ${appState.authUser.LanguageId || 'PT-BR'}`;
}

async function loadLookups() {
    const [roles, companies, customers, vendors, products, serviceCodes, taxTypes, languages, ledgerCategories] = await Promise.all([
        safeLookup('/api/roles'),
        safeLookup('/api/companies'),
        safeLookup('/api/customers'),
        safeLookup('/api/vendors'),
        safeLookup('/api/products'),
        safeLookup('/api/service-codes'),
        safeLookup('/api/tax-types'),
        safeLookup('/api/localization/languages'),
        safeLookup('/api/ledger-categories/lookup')
    ]);

    appState.lookups.roles = Array.isArray(roles) ? roles : [];
    appState.lookups.companies = Array.isArray(companies) ? companies : [];
    appState.lookups.customers = Array.isArray(customers) ? customers : [];
    appState.lookups.vendors = Array.isArray(vendors) ? vendors : [];
    appState.lookups.products = Array.isArray(products) ? products : [];
    appState.lookups.serviceCodes = Array.isArray(serviceCodes) ? serviceCodes : [];
    appState.lookups.taxTypes = Array.isArray(taxTypes) ? taxTypes : [];
    appState.lookups.languages = Array.isArray(languages) ? languages : [];
    appState.lookups.ledgerCategories = Array.isArray(ledgerCategories) ? ledgerCategories : [];
}

async function loadLocalization(languageId) {
    try {
        appState.labels = await window.apiClient.get(`/api/localization/labels?languageId=${encodeURIComponent(languageId)}`);
    } catch (error) {
        appState.labels = {};
    }
}

function applyStaticLabels() {
    document.querySelectorAll('[data-label-key]').forEach(element => {
        const key = element.getAttribute('data-label-key');
        element.textContent = t(key, element.textContent);
    });
}

async function renderSidebarMenu() {
    const nav = document.getElementById('sidebar-nav');
    nav.innerHTML = `<div class="loader">${t('loader.menu', 'Loading menu...')}</div>`;

    try {
        const groups = await window.apiClient.get('/api/navigation/menu');
        nav.innerHTML = groups.map(group => renderMenuGroup(group)).join('');
    } catch (error) {
        nav.innerHTML = `<div class="empty-state">${error.message}</div>`;
    }
}

function renderMenuGroup(group) {
    return `
        <div class="menu-group">
            <button type="button" class="menu-group-toggle" data-group-toggle aria-expanded="false">
                <span class="menu-group-title">${group.LabelText}</span>
                <span class="tree-chevron">></span>
            </button>
            <div class="menu-group-items">
                ${group.Items.map(item => renderMenuItem(item)).join('')}
            </div>
        </div>
    `;
}

function renderMenuItem(item) {
    if (item.Children && item.Children.length) {
        return `
            <div class="submenu-wrap">
                <button type="button" class="submenu-toggle" data-submenu-toggle aria-expanded="false">
                    <span class="submenu-title">${item.LabelText}</span>
                    <span class="tree-chevron">></span>
                </button>
                <div class="submenu-items">
                    ${item.Children.map(child => renderMenuItem(child)).join('')}
                </div>
            </div>
        `;
    }

    if (!item.ViewKey) {
        return '';
    }

    return `<button data-view="${item.ViewKey}" class="nav-button">${item.LabelText}</button>`;
}

async function safeLookup(endpoint) {
    try {
        return await window.apiClient.get(endpoint);
    } catch (error) {
        return [];
    }
}

async function navigateTo(viewKey) {
    appState.currentView = viewKey;
    document.getElementById('dashboard-view').classList.toggle('hidden', viewKey !== 'dashboard');
    document.getElementById('workspace-view').classList.toggle('hidden', viewKey === 'dashboard');

    if (viewKey === 'dashboard') {
        await renderDashboard();
        return;
    }

    if (genericModules[viewKey]) {
        await renderGenericModule(viewKey);
        return;
    }

    if (viewKey === 'parties' || viewKey === 'customers' || viewKey === 'vendors') {
        await renderPartyStyleModule(viewKey);
        return;
    }

    if (documentModules[viewKey]) {
        await renderDocumentModule(viewKey);
        return;
    }

    if (reportModules[viewKey]) {
        await renderReportModule(viewKey);
    }
}

async function renderDashboard() {
    const meta = viewMeta.dashboard;
    setPageHeader(t(meta.titleKey, meta.fallback), t('module.dashboard.subtitle', meta.subtitle));
    const target = document.getElementById('dashboard-view');
    target.innerHTML = `<div class="loader">${t('loader.dashboard', 'Carregando painel...')}</div>`;

    try {
        const summary = await window.apiClient.get('/api/dashboard/summary');
        target.innerHTML = `
            <div class="summary-grid">
                ${renderSummaryCard(t('kpi.billing', 'Faturamento Total'), summary.TotalBilling)}
                ${renderSummaryCard(t('kpi.expenses', 'Despesas Totais'), summary.TotalExpenses)}
                ${renderSummaryCard(t('kpi.receivable', 'Contas a Receber em Aberto'), summary.OpenReceivable)}
                ${renderSummaryCard(t('kpi.payable', 'Contas a Pagar em Aberto'), summary.OpenPayable)}
            </div>
            <div class="workspace-card">
                <span class="eyebrow">${t('kpi.netbalance', 'Saldo Liquido')}</span>
                <strong style="font-size:40px;display:block;margin-top:12px;">${formatCurrency(summary.NetBalance)}</strong>
            </div>
        `;
    } catch (error) {
        target.innerHTML = `<div class="workspace-card empty-state">${error.message}</div>`;
    }
}

function renderSummaryCard(title, amount) {
    return `
        <article class="summary-card">
            <span class="eyebrow">${t('kpi.tag', 'Indicador')}</span>
            <div class="section-title">${title}</div>
            <strong>${formatCurrency(amount)}</strong>
        </article>
    `;
}

async function renderGenericModule(moduleKey) {
    const module = genericModules[moduleKey];
    const meta = viewMeta[moduleKey] || {
        titleKey: `module.${moduleKey}.title`,
        fallback: moduleKey,
        subtitle: ''
    };
    appState.currentModule = moduleKey;
    appState.currentEntity = null;
    setPageHeader(t(meta.titleKey, meta.fallback), t(`module.${moduleKey}.subtitle`, meta.subtitle));
    setWorkspaceShell(t(meta.titleKey, meta.fallback), t(`module.${moduleKey}.subtitle`, meta.subtitle));

    const toolbar = document.getElementById('workspace-toolbar');
    toolbar.innerHTML += `
        <div class="toolbar-row top-command-row">
            <button type="button" id="cmd-new" class="secondary-button">${t('button.new', 'New Record')}</button>
            <button type="button" id="cmd-save" class="primary-button">${t('button.save', 'Save Record')}</button>
            <button type="button" id="cmd-delete" class="ghost-button">${t('button.delete', 'Delete')}</button>
            <button type="button" id="cmd-attach" class="secondary-button">${t('button.attachfile', 'Anexar arquivo')}</button>
        </div>
    `;

    const content = document.getElementById('workspace-content');
    content.innerHTML = `<div class="loader">${t('loader.data', 'Loading data...')}</div>`;

    try {
        const rows = await window.apiClient.get(module.endpoint);

        if (moduleKey === 'company') {
            await renderCompanyModule(rows);
            return;
        }

        content.innerHTML = `
            <div class="workspace-tabs">
                <button type="button" class="secondary-button tab-button active" data-tab="overview">${t('tab.overview', 'Overview')}</button>
                <button type="button" class="secondary-button tab-button" data-tab="general">${t('tab.general', 'General')}</button>
            </div>
            <div class="workspace-card" id="tab-overview-panel">
                ${renderTable(module.columns, rows)}
            </div>
            <div class="workspace-card hidden" id="tab-general-panel">
                <div class="table-title">${t(meta.titleKey, meta.fallback)} ${t('form.title', 'Form')}</div>
                <div id="generic-form-host">${renderGenericForm(module, {}, false)}</div>
            </div>
        `;

        let selectedRow = null;

        const setActiveTab = tab => {
            document.querySelectorAll('.tab-button').forEach(button => button.classList.toggle('active', button.dataset.tab === tab));
            document.getElementById('tab-overview-panel').classList.toggle('hidden', tab !== 'overview');
            document.getElementById('tab-general-panel').classList.toggle('hidden', tab !== 'general');
        };

        const renderForm = record => {
            document.getElementById('generic-form-host').innerHTML = renderGenericForm(module, record || {}, false);
            bindGenericForm(moduleKey);
        };

        const selectRowElement = rowElement => {
            document.querySelectorAll('#tab-overview-panel tbody tr').forEach(row => row.classList.remove('selected-row'));
            if (rowElement) {
                rowElement.classList.add('selected-row');
            }
        };

        document.querySelectorAll('#tab-overview-panel tbody tr[data-record]').forEach(rowElement => {
            rowElement.onclick = () => {
                const row = JSON.parse(decodeURIComponent(rowElement.dataset.record));
                selectedRow = row;
                selectRowElement(rowElement);
            };

            rowElement.ondblclick = () => {
                const row = JSON.parse(decodeURIComponent(rowElement.dataset.record));
                selectedRow = row;
                selectRowElement(rowElement);
                renderForm(row);
                setActiveTab('general');
            };
        });

        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                const tab = button.dataset.tab;
                if (tab === 'general' && selectedRow) {
                    renderForm(selectedRow);
                }
                setActiveTab(tab);
            });
        });

        document.getElementById('cmd-new').onclick = () => {
            selectedRow = null;
            selectRowElement(null);
            renderForm({});
            setActiveTab('general');
        };

        document.getElementById('cmd-save').onclick = () => {
            const form = document.getElementById('entity-form');
            if (form) {
                form.requestSubmit();
            }
        };

        document.getElementById('cmd-delete').onclick = async () => {
            const form = document.getElementById('entity-form');
            const recId = form ? form.querySelector('[name="RecId"]').value : '';

            if (!recId || !module.allowDelete) {
                return;
            }

            try {
                await window.apiClient.delete(`${module.endpoint}/${recId}`);
                notify(t('toast.delete.success', 'Record removed successfully.'), 'success');
                await loadLookups();
                await renderGenericModule(moduleKey);
            } catch (error) {
                notify(error.message, 'error');
            }
        };

        document.getElementById('cmd-attach').onclick = async () => {
            const form = document.getElementById('entity-form');
            const recId = form && form.querySelector('[name="RecId"]')
                ? form.querySelector('[name="RecId"]').value
                : (selectedRow ? selectedRow.RecId : '');

            if (!recId) {
                notify(t('toast.attachment.selectrecord', 'Selecione ou salve um registro antes de anexar arquivos.'), 'error');
                return;
            }

            await openAttachmentDialog(moduleKey, recId);
        };

        bindGenericForm(moduleKey);
    } catch (error) {
        content.innerHTML = `<div class="workspace-card empty-state">${error.message}</div>`;
    }
}

async function renderCompanyModule(rows) {
    const content = document.getElementById('workspace-content');
    const meta = viewMeta.company;

    content.innerHTML = `
        <div class="workspace-tabs">
            <button type="button" class="secondary-button tab-button active" data-tab="overview">${t('tab.overview', 'Overview')}</button>
            <button type="button" class="secondary-button tab-button" data-tab="general">${t('tab.general', 'General')}</button>
        </div>
        <div class="workspace-card" id="tab-overview-panel">
            ${renderTable(genericModules.company.columns, rows)}
        </div>
        <div class="workspace-card hidden" id="tab-general-panel">
            <div class="table-title">${t(meta.titleKey, meta.fallback)} ${t('form.title', 'Form')}</div>
            <div id="company-form-host">${renderCompanyForm()}</div>
        </div>
    `;

    let selectedRow = null;

    const setActiveTab = tab => {
        document.querySelectorAll('.tab-button').forEach(button => button.classList.toggle('active', button.dataset.tab === tab));
        document.getElementById('tab-overview-panel').classList.toggle('hidden', tab !== 'overview');
        document.getElementById('tab-general-panel').classList.toggle('hidden', tab !== 'general');
    };

    const renderForm = record => {
        document.getElementById('company-form-host').innerHTML = renderCompanyForm(record || {});
        bindCompanyForm();
    };

    const selectRowElement = rowElement => {
        document.querySelectorAll('#tab-overview-panel tbody tr').forEach(row => row.classList.remove('selected-row'));
        if (rowElement) {
            rowElement.classList.add('selected-row');
        }
    };

    document.querySelectorAll('#tab-overview-panel tbody tr[data-record]').forEach(rowElement => {
        rowElement.onclick = () => {
            const row = JSON.parse(decodeURIComponent(rowElement.dataset.record));
            selectedRow = row;
            selectRowElement(rowElement);
        };

        rowElement.ondblclick = () => {
            const row = JSON.parse(decodeURIComponent(rowElement.dataset.record));
            selectedRow = row;
            selectRowElement(rowElement);
            renderForm(row);
            setActiveTab('general');
        };
    });

    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', () => {
            const tab = button.dataset.tab;
            if (tab === 'general' && selectedRow) {
                renderForm(selectedRow);
            }
            setActiveTab(tab);
        });
    });

    document.getElementById('cmd-new').onclick = () => {
        selectedRow = null;
        selectRowElement(null);
        renderForm({});
        setActiveTab('general');
    };

    document.getElementById('cmd-save').onclick = () => {
        const form = document.getElementById('entity-form');
        if (form) {
            form.requestSubmit();
        }
    };

    document.getElementById('cmd-delete').onclick = async () => {
        const form = document.getElementById('entity-form');
        const recId = form ? form.querySelector('[name="RecId"]').value : '';

        if (!recId) {
            return;
        }

        try {
            await window.apiClient.delete(`/api/companies/${recId}`);
            notify(t('toast.delete.success', 'Record removed successfully.'), 'success');
            await loadLookups();
            await renderGenericModule('company');
        } catch (error) {
            notify(error.message, 'error');
        }
    };

    document.getElementById('cmd-attach').onclick = async () => {
        const form = document.getElementById('entity-form');
        const recId = form && form.querySelector('[name="RecId"]')
            ? form.querySelector('[name="RecId"]').value
            : (selectedRow ? selectedRow.RecId : '');

        if (!recId) {
            notify(t('toast.attachment.selectrecord', 'Selecione ou salve um registro antes de anexar arquivos.'), 'error');
            return;
        }

        await openAttachmentDialog('company', recId);
    };

    bindCompanyForm();
}

function renderCompanyForm(record = {}) {
    const isSame = String(record.BillingSameAsFiscal || '0') === '1';
    const logoPreview = record.MainLogoBase64 || record.MainLogoUrl || '';

    return `
        <form id="entity-form" class="entity-form">
            <input type="hidden" name="RecId" value="${record.RecId || ''}">
            <input type="hidden" name="MainLogoBase64" value="${escapeAttribute(record.MainLogoBase64 || '')}">
            <input type="hidden" name="MainLogoFileName" value="${escapeAttribute(record.MainLogoFileName || '')}">
            <div class="form-block form-section section-general">
                <div class="form-title">${t('form.section.general', 'General')}</div>
                <div class="fields-grid three-columns">
                    ${renderField({ name: 'Alias', label: 'Alias', type: 'text', required: true }, record.Alias || '')}
                    ${renderField({ name: 'LegalName', label: 'Legal Name', type: 'text', required: true }, record.LegalName || '')}
                    ${renderField({ name: 'TradeName', label: 'Trade Name', type: 'text' }, record.TradeName || '')}
                    ${renderField({ name: 'Cnpj', label: 'CNPJ', type: 'text', required: true }, record.Cnpj || '')}
                    ${renderField({ name: 'IsDefault', label: 'Default Company', type: 'select', options: statusOptions(['1', '0']) }, record.IsDefault || '0')}
                    ${renderField({ name: 'IsActive', label: 'Is Active', type: 'select', options: statusOptions(['1', '0']) }, record.IsActive || '1')}
                    ${renderField({ name: 'InitialBalance', label: 'Initial Balance', type: 'number' }, record.InitialBalance || '0')}
                    ${renderField({ name: 'InitialBalanceDate', label: 'Initial Balance Date', type: 'date' }, (record.InitialBalanceDate || '').toString().slice(0, 10))}
                </div>
                <div class="fields-grid three-columns" style="margin-top:12px;">
                    <label>
                        <span>${t('field.MainLogoUpload', 'Logo principal')}</span>
                        <input type="file" id="company-logo-file" accept="image/*">
                    </label>
                    ${renderField({ name: 'MainLogoUrl', label: 'Main Logo URL', type: 'text' }, record.MainLogoUrl || '')}
                    <div class="logo-preview-wrap">${logoPreview ? `<img src="${logoPreview}" alt="logo" style="max-height:56px;max-width:200px;">` : t('value.empty', '-')}</div>
                </div>
            </div>

            <div class="form-block">
                <div class="form-title">${t('form.address.fiscal', 'Endereco Fiscal')}</div>
                <div class="fields-grid three-columns">
                    ${renderField({ name: 'FiscalStreet', label: 'Fiscal Street', type: 'text' }, record.FiscalStreet || '')}
                    ${renderField({ name: 'FiscalStreetNumber', label: 'Fiscal Street Number', type: 'text' }, record.FiscalStreetNumber || '')}
                    ${renderField({ name: 'FiscalDistrict', label: 'Fiscal District', type: 'text' }, record.FiscalDistrict || '')}
                    ${renderField({ name: 'FiscalComplement', label: 'Fiscal Complement', type: 'text' }, record.FiscalComplement || '')}
                    ${renderField({ name: 'FiscalCity', label: 'Fiscal City', type: 'text' }, record.FiscalCity || '')}
                    ${renderField({ name: 'FiscalState', label: 'Fiscal State', type: 'text' }, record.FiscalState || '')}
                    ${renderField({ name: 'FiscalZipCode', label: 'Fiscal Zip Code', type: 'text' }, record.FiscalZipCode || '')}
                    ${renderField({ name: 'FiscalCountry', label: 'Fiscal Country', type: 'text' }, record.FiscalCountry || 'BRASIL')}
                </div>
            </div>

            <div class="form-block">
                <div class="header-actions">
                    <div class="form-title">${t('form.address.billing', 'Endereco de Cobranca')}</div>
                    <label class="inline-checkbox"><input type="checkbox" name="BillingSameAsFiscal" value="1" ${isSame ? 'checked' : ''}> ${t('field.BillingSameAsFiscal', 'Endereco fiscal igual ao de cobranca')}</label>
                </div>
                <div class="fields-grid three-columns">
                    ${renderField({ name: 'BillingStreet', label: 'Billing Street', type: 'text' }, record.BillingStreet || '')}
                    ${renderField({ name: 'BillingStreetNumber', label: 'Billing Street Number', type: 'text' }, record.BillingStreetNumber || '')}
                    ${renderField({ name: 'BillingDistrict', label: 'Billing District', type: 'text' }, record.BillingDistrict || '')}
                    ${renderField({ name: 'BillingComplement', label: 'Billing Complement', type: 'text' }, record.BillingComplement || '')}
                    ${renderField({ name: 'BillingCity', label: 'Billing City', type: 'text' }, record.BillingCity || '')}
                    ${renderField({ name: 'BillingState', label: 'Billing State', type: 'text' }, record.BillingState || '')}
                    ${renderField({ name: 'BillingZipCode', label: 'Billing Zip Code', type: 'text' }, record.BillingZipCode || '')}
                    ${renderField({ name: 'BillingCountry', label: 'Billing Country', type: 'text' }, record.BillingCountry || 'BRASIL')}
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-button">${t('button.save', 'Save Record')}</button>
                <button type="button" id="entity-reset" class="ghost-button">${t('button.new', 'New Record')}</button>
            </div>
        </form>
    `;
}

function bindCompanyForm() {
    const form = document.getElementById('entity-form');
    const resetButton = document.getElementById('entity-reset');
    const logoInput = document.getElementById('company-logo-file');

    if (!form) {
        return;
    }

    if (logoInput) {
        logoInput.addEventListener('change', async event => {
            const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            if (!file) {
                return;
            }

            const base64 = await fileToBase64(file);
            form.querySelector('[name="MainLogoBase64"]').value = base64;
            form.querySelector('[name="MainLogoFileName"]').value = file.name;
        });
    }

    const copyAddress = () => {
        const same = form.querySelector('[name="BillingSameAsFiscal"]').checked;
        if (!same) {
            return;
        }

        [
            ['FiscalStreet', 'BillingStreet'],
            ['FiscalStreetNumber', 'BillingStreetNumber'],
            ['FiscalDistrict', 'BillingDistrict'],
            ['FiscalComplement', 'BillingComplement'],
            ['FiscalCity', 'BillingCity'],
            ['FiscalState', 'BillingState'],
            ['FiscalZipCode', 'BillingZipCode'],
            ['FiscalCountry', 'BillingCountry']
        ].forEach(([from, to]) => {
            const source = form.querySelector(`[name="${from}"]`);
            const target = form.querySelector(`[name="${to}"]`);
            if (source && target) {
                target.value = source.value;
            }
        });
    };

    form.querySelector('[name="BillingSameAsFiscal"]').addEventListener('change', copyAddress);
    ['FiscalStreet', 'FiscalStreetNumber', 'FiscalDistrict', 'FiscalComplement', 'FiscalCity', 'FiscalState', 'FiscalZipCode', 'FiscalCountry'].forEach(name => {
        const input = form.querySelector(`[name="${name}"]`);
        if (input) {
            input.addEventListener('input', copyAddress);
        }
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        copyAddress();

        const payload = Object.fromEntries(new FormData(form).entries());
        payload.BillingSameAsFiscal = form.querySelector('[name="BillingSameAsFiscal"]').checked ? '1' : '0';

        try {
            if (payload.RecId) {
                await window.apiClient.put(`/api/companies/${payload.RecId}`, payload);
            } else {
                await window.apiClient.post('/api/companies', payload);
            }

            notify(t('toast.save.success', 'Record saved successfully.'), 'success');
            await loadLookups();
            await renderGenericModule('company');
        } catch (error) {
            notify(error.message, 'error');
        }
    });

    if (resetButton) {
        resetButton.addEventListener('click', () => renderGenericModule('company'));
    }
}

function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result || '');
        reader.onerror = () => reject(new Error('Unable to read selected file.'));
        reader.readAsDataURL(file);
    });
}

function renderGenericForm(module, record = {}, showActions = true) {
    const showDelete = module.allowDelete && record.RecId;
    const fieldSections = buildFunctionalFieldSections(module.fields, record);

    return `
        <form id="entity-form" class="entity-form">
            <input type="hidden" name="RecId" value="${record.RecId || ''}">
            ${fieldSections}
            ${showActions ? `<div class="form-actions">
                <button type="submit" class="primary-button">${t('button.save', 'Save Record')}</button>
                <button type="button" id="entity-reset" class="ghost-button">${t('button.new', 'New Record')}</button>
                ${showDelete ? `<button type="button" id="entity-delete" class="secondary-button">${t('button.delete', 'Delete')}</button>` : ''}
            </div>` : ''}
        </form>
    `;
}

function bindGenericForm(moduleKey) {
    const module = genericModules[moduleKey];
    const form = document.getElementById('entity-form');
    const resetButton = document.getElementById('entity-reset');
    const deleteButton = document.getElementById('entity-delete');

    if (!form) {
        return;
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const payload = Object.fromEntries(new FormData(form).entries());

        try {
            if (payload.RecId) {
                await window.apiClient.put(`${module.endpoint}/${payload.RecId}`, payload);
            } else {
                await window.apiClient.post(module.endpoint, payload);
            }

            notify(t('toast.save.success', 'Record saved successfully.'), 'success');
            await loadLookups();
            await renderGenericModule(moduleKey);
        } catch (error) {
            notify(error.message, 'error');
        }
    });

    if (resetButton) {
        resetButton.addEventListener('click', () => renderGenericModule(moduleKey));
    }

    if (deleteButton) {
        deleteButton.addEventListener('click', async () => {
            const recId = form.querySelector('[name="RecId"]').value;

            if (!recId) {
                return;
            }

            try {
                await window.apiClient.delete(`${module.endpoint}/${recId}`);
                notify(t('toast.delete.success', 'Record removed successfully.'), 'success');
                await loadLookups();
                await renderGenericModule(moduleKey);
            } catch (error) {
                notify(error.message, 'error');
            }
        });
    }
}

function loadGenericRecord(moduleKey, row) {
    const module = genericModules[moduleKey];
    const meta = viewMeta[moduleKey];
    const formHost = document.querySelector('#workspace-content .workspace-card:last-child');
    formHost.innerHTML = `
        <div class="table-title">${t(meta.titleKey, meta.fallback)} ${t('form.title', 'Form')}</div>
        ${renderGenericForm(module, row)}
    `;
    bindGenericForm(moduleKey);
}

async function renderPartyStyleModule(moduleKey) {
    const content = document.getElementById('workspace-content');
    appState.currentModule = moduleKey;
    appState.currentEntity = null;

    const settings = {
        parties: { endpoint: '/api/parties', detailEndpoint: '/api/parties/' },
        customers: { endpoint: '/api/customers', detailEndpoint: '/api/customers/' },
        vendors: { endpoint: '/api/vendors', detailEndpoint: '/api/vendors/' }
    }[moduleKey];

    const meta = viewMeta[moduleKey];
    setPageHeader(t(meta.titleKey, meta.fallback), t(`module.${moduleKey}.subtitle`, meta.subtitle));
    setWorkspaceShell(t(meta.titleKey, meta.fallback), t(`module.${moduleKey}.subtitle`, meta.subtitle));

    const toolbar = document.getElementById('workspace-toolbar');
    toolbar.innerHTML += `
        <div class="toolbar-row top-command-row">
            <button type="button" id="cmd-new" class="secondary-button">${t('button.new', 'New Record')}</button>
            <button type="button" id="cmd-save" class="primary-button">${t('button.save', 'Save Record')}</button>
            <button type="button" id="cmd-delete" class="ghost-button">${t('button.delete', 'Delete')}</button>
            <button type="button" id="cmd-attach" class="secondary-button">${t('button.attachfile', 'Anexar arquivo')}</button>
        </div>
    `;

    content.innerHTML = `<div class="loader">${t('loader.data', 'Loading data...')}</div>`;

    try {
        const rows = await window.apiClient.get(settings.endpoint);
        content.innerHTML = `
            <div class="workspace-tabs">
                <button type="button" class="secondary-button tab-button active" data-tab="overview">${t('tab.overview', 'Overview')}</button>
                <button type="button" class="secondary-button tab-button" data-tab="general">${t('tab.general', 'General')}</button>
            </div>
            <section class="workspace-card" id="tab-overview-panel">
                ${renderTable(resolvePartyColumns(moduleKey), rows)}
            </section>
            <section class="workspace-card hidden" id="tab-general-panel">
                <div id="party-form-host">${renderPartyStyleForm(moduleKey)}</div>
            </section>
            
        `;

        let selectedRecId = null;

        const setActiveTab = tab => {
            document.querySelectorAll('.tab-button').forEach(button => button.classList.toggle('active', button.dataset.tab === tab));
            document.getElementById('tab-overview-panel').classList.toggle('hidden', tab !== 'overview');
            document.getElementById('tab-general-panel').classList.toggle('hidden', tab !== 'general');
        };

        const selectRowElement = rowElement => {
            document.querySelectorAll('#tab-overview-panel tbody tr').forEach(row => row.classList.remove('selected-row'));
            if (rowElement) {
                rowElement.classList.add('selected-row');
            }
        };

        document.querySelectorAll('#tab-overview-panel tbody tr[data-record]').forEach(rowElement => {
            rowElement.onclick = () => {
                const row = JSON.parse(decodeURIComponent(rowElement.dataset.record));
                selectedRecId = row.RecId;
                selectRowElement(rowElement);
            };

            rowElement.ondblclick = async () => {
                const row = JSON.parse(decodeURIComponent(rowElement.dataset.record));
                selectedRecId = row.RecId;
                selectRowElement(rowElement);
                await loadPartyStyleRecord(moduleKey, row.RecId);
                setActiveTab('general');
            };
        });

        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', async () => {
                const tab = button.dataset.tab;
                if (tab === 'general' && selectedRecId) {
                    await loadPartyStyleRecord(moduleKey, selectedRecId);
                }
                setActiveTab(tab);
            });
        });

        document.getElementById('cmd-new').onclick = () => {
            selectedRecId = null;
            selectRowElement(null);
            document.getElementById('party-form-host').innerHTML = renderPartyStyleForm(moduleKey);
            bindPartyStyleForm(moduleKey);
            setActiveTab('general');
        };

        document.getElementById('cmd-save').onclick = () => {
            const form = document.getElementById('entity-form');
            if (form) {
                form.requestSubmit();
            }
        };

        document.getElementById('cmd-delete').onclick = async () => {
            const form = document.getElementById('entity-form');
            const recId = form ? form.querySelector('[name="RecId"]').value : selectedRecId;

            if (!recId) {
                return;
            }

            try {
                await window.apiClient.delete(`${settings.endpoint}/${recId}`);
                notify(t('toast.delete.success', 'Record removed successfully.'), 'success');
                await loadLookups();
                await renderPartyStyleModule(moduleKey);
            } catch (error) {
                notify(error.message, 'error');
            }
        };

        document.getElementById('cmd-attach').onclick = async () => {
            const form = document.getElementById('entity-form');
            const recId = form && form.querySelector('[name="RecId"]')
                ? form.querySelector('[name="RecId"]').value
                : selectedRecId;

            if (!recId) {
                notify(t('toast.attachment.selectrecord', 'Selecione ou salve um registro antes de anexar arquivos.'), 'error');
                return;
            }

            await openAttachmentDialog(moduleKey, recId);
        };

        bindPartyStyleForm(moduleKey);
    } catch (error) {
        content.innerHTML = `<div class="workspace-card empty-state">${error.message}</div>`;
    }
}

function resolvePartyColumns(moduleKey) {
    if (moduleKey === 'parties') return ['PartyNumber', 'Name', 'Alias', 'TaxId', 'PartyType', 'IsActive'];
    if (moduleKey === 'customers') return ['CustAccount', 'Name', 'CustomerGroup', 'CreditLimit', 'PaymentTermDays', 'IsActive'];
    return ['VendAccount', 'Name', 'VendorGroup', 'PaymentTermDays', 'IsActive'];
}

function renderPartyStyleForm(moduleKey, record = null) {
    if (moduleKey === 'customers') {
        return renderCustomerForm(record);
    }

    if (moduleKey === 'vendors') {
        return renderVendorForm(record);
    }

    const party = record && record.Party ? record.Party : record || {};
    const address = party.Addresses && party.Addresses[0] ? party.Addresses[0] : {};
    const email = (party.Contacts || []).find(item => item.Type === 'E') || {};
    const phone = (party.Contacts || []).find(item => item.Type === 'P') || {};
    const isEntity = moduleKey !== 'parties';

    return `
        <div class="table-title">${t(viewMeta[moduleKey].titleKey, viewMeta[moduleKey].fallback)} ${t('form.title', 'Form')}</div>
        <form id="entity-form" class="entity-form">
            <input type="hidden" name="RecId" value="${record && record.RecId ? record.RecId : ''}">
            <div class="fields-grid three-columns">
                ${isEntity ? renderEntityAccountFields(moduleKey, record || {}) : ''}
                ${renderField({ name: 'PartyType', label: 'Party Type', type: 'select', options: [{ value: 'O', label: 'Organization' }, { value: 'P', label: 'Person' }, { value: 'F', label: 'Foreign' }] }, party.PartyType || 'O')}
                ${renderField({ name: 'Name', label: 'Name', type: 'text', required: true }, party.Name || '')}
                ${renderField({ name: 'Alias', label: 'Alias', type: 'text' }, party.Alias || '')}
                ${renderField({ name: 'TaxId', label: 'Tax Id', type: 'text' }, party.TaxId || '')}
                ${renderField({ name: 'IsActive', label: 'Is Active', type: 'select', options: statusOptions(['1', '0']) }, record && record.IsActive ? record.IsActive : (party.IsActive || '1'))}
                ${moduleKey !== 'parties' ? renderField({ name: 'IsBlocked', label: 'Is Blocked', type: 'select', options: statusOptions(['0', '1']) }, record && record.IsBlocked ? record.IsBlocked : '0') : ''}
            </div>
            <div class="form-block">
                <div class="form-title">${t('form.address.primary', 'Primary Address')}</div>
                <div class="fields-grid three-columns">
                    ${renderField({ name: 'ZipCode', label: 'Zip Code', type: 'text' }, address.ZipCode || '')}
                    ${renderField({ name: 'Street', label: 'Street', type: 'text' }, address.Street || '')}
                    ${renderField({ name: 'StreetNumber', label: 'Street Number', type: 'text' }, address.StreetNumber || '')}
                    ${renderField({ name: 'Complement', label: 'Complement', type: 'text' }, address.Complement || '')}
                    ${renderField({ name: 'District', label: 'District', type: 'text' }, address.District || '')}
                    ${renderField({ name: 'City', label: 'City', type: 'text' }, address.City || '')}
                    ${renderField({ name: 'State', label: 'State', type: 'text' }, address.State || '')}
                </div>
            </div>
            <div class="form-block">
                <div class="form-title">${t('form.contacts.primary', 'Primary Contacts')}</div>
                <div class="fields-grid">
                    ${renderField({ name: 'PrimaryEmail', label: 'Primary Email', type: 'email' }, email.Locator || '')}
                    ${renderField({ name: 'PrimaryPhone', label: 'Primary Phone', type: 'text' }, phone.Locator || '')}
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="primary-button">${t('button.save', 'Save Record')}</button>
                <button type="button" id="entity-reset" class="ghost-button">${t('button.new', 'New Record')}</button>
            </div>
        </form>
    `;
}

function renderEntityAccountFields(moduleKey, record) {
    if (moduleKey === 'customers') {
        return [
            renderField({ name: 'CustAccount', label: 'Customer Account', type: 'text', required: false, readOnly: true }, record.CustAccount || ''),
            renderField({ name: 'CurrencyCode', label: 'Currency Code', type: 'text' }, record.CurrencyCode || 'BRL'),
            renderField({ name: 'CreditLimit', label: 'Credit Limit', type: 'number' }, record.CreditLimit || '0.00'),
            renderField({ name: 'PaymentTermDays', label: 'Payment Term Days', type: 'number' }, record.PaymentTermDays || 0),
            renderField({ name: 'CustomerGroup', label: 'Customer Group', type: 'text' }, record.CustomerGroup || 'DEFAULT')
        ].join('');
    }

    return [
        renderField({ name: 'VendAccount', label: 'Vendor Account', type: 'text', required: false, readOnly: true }, record.VendAccount || ''),
        renderField({ name: 'CurrencyCode', label: 'Currency Code', type: 'text' }, record.CurrencyCode || 'BRL'),
        renderField({ name: 'PaymentTermDays', label: 'Payment Term Days', type: 'number' }, record.PaymentTermDays || 0),
        renderField({ name: 'VendorGroup', label: 'Vendor Group', type: 'text' }, record.VendorGroup || 'DEFAULT')
    ].join('');
}

function renderCustomerForm(record = null) {
    const party = record && record.Party ? record.Party : {};
    const fiscalAddress = ((party.Addresses || []).find(item => item.AddressType === 'F')) || (party.Addresses && party.Addresses[0] ? party.Addresses[0] : {});
    const billingAddress = ((party.Addresses || []).find(item => item.AddressType === 'B')) || {};
    const contacts = record && record.ContactPersons && record.ContactPersons.length ? record.ContactPersons : [{}];
    const sameFlag = String(record && record.BillingSameAsFiscal ? record.BillingSameAsFiscal : '0') === '1';

    return `
        <div class="table-title">${t(viewMeta.customers.titleKey, viewMeta.customers.fallback)} ${t('form.title', 'Form')}</div>
        <form id="entity-form" class="entity-form">
            <input type="hidden" name="RecId" value="${record && record.RecId ? record.RecId : ''}">
            <div class="form-block form-section section-general">
                <div class="form-title">${t('form.section.general', 'General')}</div>
                <div class="fields-grid three-columns">
                    ${renderField({ name: 'CustAccount', label: 'Customer Account', type: 'text', readOnly: true }, record && record.CustAccount ? record.CustAccount : '')}
                    ${renderField({ name: 'CompanyType', label: 'Company Type', type: 'select', options: [{ value: 'JURIDICA', label: t('option.companytype.legal', 'Juridica') }, { value: 'FISICA', label: t('option.companytype.person', 'Fisica') }, { value: 'ESTRANGEIRA', label: t('option.companytype.foreign', 'Estrangeira') }] }, record && record.CompanyType ? record.CompanyType : 'JURIDICA')}
                    ${renderField({ name: 'Alias', label: 'Trade Name', type: 'text' }, party.Alias || '')}
                    ${renderField({ name: 'Name', label: 'Legal Name', type: 'text', required: true }, party.Name || '')}
                    ${renderField({ name: 'TaxId', label: 'Tax Id', type: 'text' }, party.TaxId || '')}
                    ${renderField({ name: 'CustomerGroup', label: 'Customer Group', type: 'text' }, record && record.CustomerGroup ? record.CustomerGroup : 'DEFAULT')}
                    ${renderField({ name: 'PaymentTermDays', label: 'Payment Term Days', type: 'number' }, record && record.PaymentTermDays ? record.PaymentTermDays : 0)}
                    ${renderField({ name: 'CurrencyCode', label: 'Currency Code', type: 'text' }, record && record.CurrencyCode ? record.CurrencyCode : 'BRL')}
                    ${renderField({ name: 'CreditLimit', label: 'Credit Limit', type: 'number' }, record && record.CreditLimit ? record.CreditLimit : '0.00')}
                    ${renderField({ name: 'IsActive', label: 'Is Active', type: 'select', options: statusOptions(['1', '0']) }, record && record.IsActive ? record.IsActive : '1')}
                    ${renderField({ name: 'IsBlocked', label: 'Is Blocked', type: 'select', options: statusOptions(['0', '1']) }, record && record.IsBlocked ? record.IsBlocked : '0')}
                    <input type="hidden" name="PartyType" value="O">
                </div>
            </div>

            <div class="form-block">
                <div class="form-title">${t('form.address.fiscal', 'Endereco Fiscal')}</div>
                <div class="fields-grid three-columns">
                    ${renderField({ name: 'FiscalStreet', label: 'Fiscal Street', type: 'text' }, fiscalAddress.Street || '')}
                    ${renderField({ name: 'FiscalStreetNumber', label: 'Fiscal Street Number', type: 'text' }, fiscalAddress.StreetNumber || '')}
                    ${renderField({ name: 'FiscalDistrict', label: 'Fiscal District', type: 'text' }, fiscalAddress.District || '')}
                    ${renderField({ name: 'FiscalComplement', label: 'Fiscal Complement', type: 'text' }, fiscalAddress.Complement || '')}
                    ${renderField({ name: 'FiscalCity', label: 'Fiscal City', type: 'text' }, fiscalAddress.City || '')}
                    ${renderField({ name: 'FiscalState', label: 'Fiscal State', type: 'text' }, fiscalAddress.State || '')}
                    ${renderField({ name: 'FiscalZipCode', label: 'Fiscal Zip Code', type: 'text' }, fiscalAddress.ZipCode || '')}
                    ${renderField({ name: 'FiscalCountry', label: 'Fiscal Country', type: 'text' }, fiscalAddress.Country || 'BRASIL')}
                </div>
            </div>

            <div class="form-block">
                <div class="header-actions">
                    <div class="form-title">${t('form.address.billing', 'Endereco de Cobranca')}</div>
                    <label class="inline-checkbox"><input type="checkbox" name="BillingSameAsFiscal" value="1" ${sameFlag ? 'checked' : ''}> ${t('field.BillingSameAsFiscal', 'Endereco fiscal igual ao de cobranca')}</label>
                </div>
                <div class="fields-grid three-columns">
                    ${renderField({ name: 'BillingStreet', label: 'Billing Street', type: 'text' }, billingAddress.Street || '')}
                    ${renderField({ name: 'BillingStreetNumber', label: 'Billing Street Number', type: 'text' }, billingAddress.StreetNumber || '')}
                    ${renderField({ name: 'BillingDistrict', label: 'Billing District', type: 'text' }, billingAddress.District || '')}
                    ${renderField({ name: 'BillingComplement', label: 'Billing Complement', type: 'text' }, billingAddress.Complement || '')}
                    ${renderField({ name: 'BillingCity', label: 'Billing City', type: 'text' }, billingAddress.City || '')}
                    ${renderField({ name: 'BillingState', label: 'Billing State', type: 'text' }, billingAddress.State || '')}
                    ${renderField({ name: 'BillingZipCode', label: 'Billing Zip Code', type: 'text' }, billingAddress.ZipCode || '')}
                    ${renderField({ name: 'BillingCountry', label: 'Billing Country', type: 'text' }, billingAddress.Country || 'BRASIL')}
                </div>
            </div>

            <div class="form-block">
                <div class="header-actions">
                    <div class="form-title">${t('form.contacts.grid', 'Contato')}</div>
                    <button type="button" class="mini-button" id="add-customer-contact-row">${t('button.addline', 'Add Line')}</button>
                </div>
                <div class="editable-grid-wrap">
                    <table class="editable-grid" id="customer-contacts-grid">
                        <thead>
                            <tr>
                                <th>${t('field.FirstName', 'Nome')}</th>
                                <th>${t('field.LastName', 'Sobrenome')}</th>
                                <th>${t('field.ContactType', 'Tipo de Contato')}</th>
                                <th>${t('field.Email', 'Email')}</th>
                                <th>${t('field.Phone', 'Telefone')}</th>
                                <th>${t('column.Actions', 'Actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${contacts.map(contact => renderCustomerContactRow(contact)).join('')}
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-button">${t('button.save', 'Save Record')}</button>
                <button type="button" id="entity-reset" class="ghost-button">${t('button.new', 'New Record')}</button>
            </div>
        </form>
    `;
}

function renderCustomerContactRow(contact = {}) {
    return `
        <tr class="customer-contact-row">
            <td><input class="grid-input" type="text" name="ContactFirstName" value="${escapeAttribute(contact.FirstName || '')}"></td>
            <td><input class="grid-input" type="text" name="ContactLastName" value="${escapeAttribute(contact.LastName || '')}"></td>
            <td>
                <select class="grid-input" name="ContactType">
                    ${['FISCAL', 'COBRANCA', 'OUTROS'].map(type => `<option value="${type}" ${String(contact.ContactType || 'OUTROS').toUpperCase() === type ? 'selected' : ''}>${t(`option.contacttype.${type.toLowerCase()}`, type)}</option>`).join('')}
                </select>
            </td>
            <td><input class="grid-input" type="email" name="ContactEmail" value="${escapeAttribute(contact.Email || '')}"></td>
            <td><input class="grid-input" type="text" name="ContactPhone" value="${escapeAttribute(contact.Phone || '')}"></td>
            <td class="grid-actions-cell"><button type="button" class="mini-button remove-customer-contact-row">${t('button.remove', 'Remove')}</button></td>
        </tr>
    `;
}

function renderVendorForm(record = null) {
    const party = record && record.Party ? record.Party : {};
    const fiscalAddress = ((party.Addresses || []).find(item => item.AddressType === 'F')) || (party.Addresses && party.Addresses[0] ? party.Addresses[0] : {});
    const billingAddress = ((party.Addresses || []).find(item => item.AddressType === 'B')) || {};
    const contacts = record && record.ContactPersons && record.ContactPersons.length ? record.ContactPersons : [{}];
    const sameFlag = String(record && record.BillingSameAsFiscal ? record.BillingSameAsFiscal : '0') === '1';

    return `
        <div class="table-title">${t(viewMeta.vendors.titleKey, viewMeta.vendors.fallback)} ${t('form.title', 'Form')}</div>
        <form id="entity-form" class="entity-form">
            <input type="hidden" name="RecId" value="${record && record.RecId ? record.RecId : ''}">
            <div class="form-block form-section section-general">
                <div class="form-title">${t('form.section.general', 'General')}</div>
                <div class="fields-grid three-columns">
                    ${renderField({ name: 'VendAccount', label: 'Vendor Account', type: 'text', readOnly: true }, record && record.VendAccount ? record.VendAccount : '')}
                    ${renderField({ name: 'CompanyType', label: 'Company Type', type: 'select', options: [{ value: 'JURIDICA', label: t('option.companytype.legal', 'Juridica') }, { value: 'FISICA', label: t('option.companytype.person', 'Fisica') }, { value: 'ESTRANGEIRA', label: t('option.companytype.foreign', 'Estrangeira') }] }, record && record.CompanyType ? record.CompanyType : 'JURIDICA')}
                    ${renderField({ name: 'Alias', label: 'Trade Name', type: 'text' }, party.Alias || '')}
                    ${renderField({ name: 'Name', label: 'Legal Name', type: 'text', required: true }, party.Name || '')}
                    ${renderField({ name: 'TaxId', label: 'Tax Id', type: 'text' }, party.TaxId || '')}
                    ${renderField({ name: 'VendorGroup', label: 'Vendor Group', type: 'text' }, record && record.VendorGroup ? record.VendorGroup : 'DEFAULT')}
                    ${renderField({ name: 'PaymentTermDays', label: 'Payment Term Days', type: 'number' }, record && record.PaymentTermDays ? record.PaymentTermDays : 0)}
                    ${renderField({ name: 'CurrencyCode', label: 'Currency Code', type: 'text' }, record && record.CurrencyCode ? record.CurrencyCode : 'BRL')}
                    ${renderField({ name: 'IsActive', label: 'Is Active', type: 'select', options: statusOptions(['1', '0']) }, record && record.IsActive ? record.IsActive : '1')}
                    ${renderField({ name: 'IsBlocked', label: 'Is Blocked', type: 'select', options: statusOptions(['0', '1']) }, record && record.IsBlocked ? record.IsBlocked : '0')}
                    <input type="hidden" name="PartyType" value="O">
                </div>
            </div>

            <div class="form-block">
                <div class="form-title">${t('form.address.fiscal', 'Endereco Fiscal')}</div>
                <div class="fields-grid three-columns">
                    ${renderField({ name: 'FiscalStreet', label: 'Fiscal Street', type: 'text' }, fiscalAddress.Street || '')}
                    ${renderField({ name: 'FiscalStreetNumber', label: 'Fiscal Street Number', type: 'text' }, fiscalAddress.StreetNumber || '')}
                    ${renderField({ name: 'FiscalDistrict', label: 'Fiscal District', type: 'text' }, fiscalAddress.District || '')}
                    ${renderField({ name: 'FiscalComplement', label: 'Fiscal Complement', type: 'text' }, fiscalAddress.Complement || '')}
                    ${renderField({ name: 'FiscalCity', label: 'Fiscal City', type: 'text' }, fiscalAddress.City || '')}
                    ${renderField({ name: 'FiscalState', label: 'Fiscal State', type: 'text' }, fiscalAddress.State || '')}
                    ${renderField({ name: 'FiscalZipCode', label: 'Fiscal Zip Code', type: 'text' }, fiscalAddress.ZipCode || '')}
                    ${renderField({ name: 'FiscalCountry', label: 'Fiscal Country', type: 'text' }, fiscalAddress.Country || 'BRASIL')}
                </div>
            </div>

            <div class="form-block">
                <div class="header-actions">
                    <div class="form-title">${t('form.address.billing', 'Endereco de Cobranca')}</div>
                    <label class="inline-checkbox"><input type="checkbox" name="BillingSameAsFiscal" value="1" ${sameFlag ? 'checked' : ''}> ${t('field.BillingSameAsFiscal', 'Endereco fiscal igual ao de cobranca')}</label>
                </div>
                <div class="fields-grid three-columns">
                    ${renderField({ name: 'BillingStreet', label: 'Billing Street', type: 'text' }, billingAddress.Street || '')}
                    ${renderField({ name: 'BillingStreetNumber', label: 'Billing Street Number', type: 'text' }, billingAddress.StreetNumber || '')}
                    ${renderField({ name: 'BillingDistrict', label: 'Billing District', type: 'text' }, billingAddress.District || '')}
                    ${renderField({ name: 'BillingComplement', label: 'Billing Complement', type: 'text' }, billingAddress.Complement || '')}
                    ${renderField({ name: 'BillingCity', label: 'Billing City', type: 'text' }, billingAddress.City || '')}
                    ${renderField({ name: 'BillingState', label: 'Billing State', type: 'text' }, billingAddress.State || '')}
                    ${renderField({ name: 'BillingZipCode', label: 'Billing Zip Code', type: 'text' }, billingAddress.ZipCode || '')}
                    ${renderField({ name: 'BillingCountry', label: 'Billing Country', type: 'text' }, billingAddress.Country || 'BRASIL')}
                </div>
            </div>

            <div class="form-block">
                <div class="header-actions">
                    <div class="form-title">${t('form.contacts.grid', 'Contato')}</div>
                    <button type="button" class="mini-button" id="add-vendor-contact-row">${t('button.addline', 'Add Line')}</button>
                </div>
                <div class="editable-grid-wrap">
                    <table class="editable-grid" id="vendor-contacts-grid">
                        <thead>
                            <tr>
                                <th>${t('field.FirstName', 'Nome')}</th>
                                <th>${t('field.LastName', 'Sobrenome')}</th>
                                <th>${t('field.ContactType', 'Tipo de Contato')}</th>
                                <th>${t('field.Email', 'Email')}</th>
                                <th>${t('field.Phone', 'Telefone')}</th>
                                <th>${t('column.Actions', 'Actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${contacts.map(contact => renderVendorContactRow(contact)).join('')}
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-button">${t('button.save', 'Save Record')}</button>
                <button type="button" id="entity-reset" class="ghost-button">${t('button.new', 'New Record')}</button>
            </div>
        </form>
    `;
}

function renderVendorContactRow(contact = {}) {
    return `
        <tr class="vendor-contact-row">
            <td><input class="grid-input" type="text" name="ContactFirstName" value="${escapeAttribute(contact.FirstName || '')}"></td>
            <td><input class="grid-input" type="text" name="ContactLastName" value="${escapeAttribute(contact.LastName || '')}"></td>
            <td>
                <select class="grid-input" name="ContactType">
                    ${['FISCAL', 'COBRANCA', 'OUTROS'].map(type => `<option value="${type}" ${String(contact.ContactType || 'OUTROS').toUpperCase() === type ? 'selected' : ''}>${t(`option.contacttype.${type.toLowerCase()}`, type)}</option>`).join('')}
                </select>
            </td>
            <td><input class="grid-input" type="email" name="ContactEmail" value="${escapeAttribute(contact.Email || '')}"></td>
            <td><input class="grid-input" type="text" name="ContactPhone" value="${escapeAttribute(contact.Phone || '')}"></td>
            <td class="grid-actions-cell"><button type="button" class="mini-button remove-vendor-contact-row">${t('button.remove', 'Remove')}</button></td>
        </tr>
    `;
}

function bindVendorFormDetails() {
    const form = document.getElementById('entity-form');
    if (!form) {
        return;
    }

    const syncBilling = () => {
        const checked = form.querySelector('[name="BillingSameAsFiscal"]').checked;
        if (!checked) {
            return;
        }

        [
            ['FiscalStreet', 'BillingStreet'],
            ['FiscalStreetNumber', 'BillingStreetNumber'],
            ['FiscalDistrict', 'BillingDistrict'],
            ['FiscalComplement', 'BillingComplement'],
            ['FiscalCity', 'BillingCity'],
            ['FiscalState', 'BillingState'],
            ['FiscalZipCode', 'BillingZipCode'],
            ['FiscalCountry', 'BillingCountry']
        ].forEach(([from, to]) => {
            const source = form.querySelector(`[name="${from}"]`);
            const target = form.querySelector(`[name="${to}"]`);
            if (source && target) {
                target.value = source.value;
            }
        });
    };

    const sameCheckbox = form.querySelector('[name="BillingSameAsFiscal"]');
    if (sameCheckbox) {
        sameCheckbox.addEventListener('change', syncBilling);
    }

    ['FiscalStreet', 'FiscalStreetNumber', 'FiscalDistrict', 'FiscalComplement', 'FiscalCity', 'FiscalState', 'FiscalZipCode', 'FiscalCountry'].forEach(name => {
        const input = form.querySelector(`[name="${name}"]`);
        if (input) {
            input.addEventListener('input', syncBilling);
        }
    });

    const addButton = document.getElementById('add-vendor-contact-row');
    if (addButton) {
        addButton.addEventListener('click', () => {
            const body = document.querySelector('#vendor-contacts-grid tbody');
            if (body) {
                body.insertAdjacentHTML('beforeend', renderVendorContactRow({}));
                bindVendorContactRemove();
            }
        });
    }

    bindVendorContactRemove();
}

function bindVendorContactRemove() {
    document.querySelectorAll('.remove-vendor-contact-row').forEach(button => {
        button.onclick = () => {
            const rows = document.querySelectorAll('#vendor-contacts-grid .vendor-contact-row');
            if (rows.length > 1) {
                button.closest('tr').remove();
            }
        };
    });
}

function bindCustomerFormDetails() {
    const form = document.getElementById('entity-form');
    if (!form) {
        return;
    }

    const syncBilling = () => {
        const checked = form.querySelector('[name="BillingSameAsFiscal"]').checked;
        if (!checked) {
            return;
        }

        [
            ['FiscalStreet', 'BillingStreet'],
            ['FiscalStreetNumber', 'BillingStreetNumber'],
            ['FiscalDistrict', 'BillingDistrict'],
            ['FiscalComplement', 'BillingComplement'],
            ['FiscalCity', 'BillingCity'],
            ['FiscalState', 'BillingState'],
            ['FiscalZipCode', 'BillingZipCode'],
            ['FiscalCountry', 'BillingCountry']
        ].forEach(([from, to]) => {
            const source = form.querySelector(`[name="${from}"]`);
            const target = form.querySelector(`[name="${to}"]`);
            if (source && target) {
                target.value = source.value;
            }
        });
    };

    const sameCheckbox = form.querySelector('[name="BillingSameAsFiscal"]');
    if (sameCheckbox) {
        sameCheckbox.addEventListener('change', syncBilling);
    }

    ['FiscalStreet', 'FiscalStreetNumber', 'FiscalDistrict', 'FiscalComplement', 'FiscalCity', 'FiscalState', 'FiscalZipCode', 'FiscalCountry'].forEach(name => {
        const input = form.querySelector(`[name="${name}"]`);
        if (input) {
            input.addEventListener('input', syncBilling);
        }
    });

    const addButton = document.getElementById('add-customer-contact-row');
    if (addButton) {
        addButton.addEventListener('click', () => {
            const body = document.querySelector('#customer-contacts-grid tbody');
            if (body) {
                body.insertAdjacentHTML('beforeend', renderCustomerContactRow({}));
                bindCustomerContactRemove();
            }
        });
    }

    bindCustomerContactRemove();
}

function bindCustomerContactRemove() {
    document.querySelectorAll('.remove-customer-contact-row').forEach(button => {
        button.onclick = () => {
            const rows = document.querySelectorAll('#customer-contacts-grid .customer-contact-row');
            if (rows.length > 1) {
                button.closest('tr').remove();
            }
        };
    });
}

function bindPartyStyleForm(moduleKey) {
    const form = document.getElementById('entity-form');
    const resetButton = document.getElementById('entity-reset');
    const endpointMap = { parties: '/api/parties', customers: '/api/customers', vendors: '/api/vendors' };

    if (!form) {
        return;
    }

    if (moduleKey === 'customers') {
        bindCustomerFormDetails();
    }

    if (moduleKey === 'vendors') {
        bindVendorFormDetails();
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const payload = buildPartyPayload(moduleKey, new FormData(form));

        try {
            if (payload.RecId) {
                await window.apiClient.put(`${endpointMap[moduleKey]}/${payload.RecId}`, payload);
            } else {
                await window.apiClient.post(endpointMap[moduleKey], payload);
            }

            notify(t('toast.save.success', 'Record saved successfully.'), 'success');
            await loadLookups();
            await renderPartyStyleModule(moduleKey);
        } catch (error) {
            notify(error.message, 'error');
        }
    });

    resetButton.addEventListener('click', () => renderPartyStyleModule(moduleKey));
}

function buildPartyPayload(moduleKey, formData) {
    const data = Object.fromEntries(formData.entries());
    const billingSameAsFiscal = data.BillingSameAsFiscal === '1' ? '1' : '0';
    const customerContacts = Array.from(document.querySelectorAll('#customer-contacts-grid .customer-contact-row')).map(row => ({
        FirstName: row.querySelector('[name="ContactFirstName"]') ? row.querySelector('[name="ContactFirstName"]').value : '',
        LastName: row.querySelector('[name="ContactLastName"]') ? row.querySelector('[name="ContactLastName"]').value : '',
        ContactType: row.querySelector('[name="ContactType"]') ? row.querySelector('[name="ContactType"]').value : 'OUTROS',
        Email: row.querySelector('[name="ContactEmail"]') ? row.querySelector('[name="ContactEmail"]').value : '',
        Phone: row.querySelector('[name="ContactPhone"]') ? row.querySelector('[name="ContactPhone"]').value : ''
    }));
    const vendorContacts = Array.from(document.querySelectorAll('#vendor-contacts-grid .vendor-contact-row')).map(row => ({
        FirstName: row.querySelector('[name="ContactFirstName"]') ? row.querySelector('[name="ContactFirstName"]').value : '',
        LastName: row.querySelector('[name="ContactLastName"]') ? row.querySelector('[name="ContactLastName"]').value : '',
        ContactType: row.querySelector('[name="ContactType"]') ? row.querySelector('[name="ContactType"]').value : 'OUTROS',
        Email: row.querySelector('[name="ContactEmail"]') ? row.querySelector('[name="ContactEmail"]').value : '',
        Phone: row.querySelector('[name="ContactPhone"]') ? row.querySelector('[name="ContactPhone"]').value : ''
    }));

    const party = {
        PartyType: data.PartyType,
        Name: data.Name,
        Alias: data.Alias,
        TaxId: data.TaxId,
        IsActive: data.IsActive,
        IsBlocked: data.IsBlocked || '0',
        Addresses: moduleKey === 'customers'
            ? [
                { AddressType: 'F', ZipCode: data.FiscalZipCode, Street: data.FiscalStreet, StreetNumber: data.FiscalStreetNumber, Complement: data.FiscalComplement, District: data.FiscalDistrict, City: data.FiscalCity, State: data.FiscalState, Country: data.FiscalCountry, IsPrimary: '1' },
                { AddressType: 'B', ZipCode: billingSameAsFiscal === '1' ? data.FiscalZipCode : data.BillingZipCode, Street: billingSameAsFiscal === '1' ? data.FiscalStreet : data.BillingStreet, StreetNumber: billingSameAsFiscal === '1' ? data.FiscalStreetNumber : data.BillingStreetNumber, Complement: billingSameAsFiscal === '1' ? data.FiscalComplement : data.BillingComplement, District: billingSameAsFiscal === '1' ? data.FiscalDistrict : data.BillingDistrict, City: billingSameAsFiscal === '1' ? data.FiscalCity : data.BillingCity, State: billingSameAsFiscal === '1' ? data.FiscalState : data.BillingState, Country: billingSameAsFiscal === '1' ? data.FiscalCountry : data.BillingCountry, IsPrimary: '0' }
            ]
            : moduleKey === 'vendors'
                ? [
                    { AddressType: 'F', ZipCode: data.FiscalZipCode, Street: data.FiscalStreet, StreetNumber: data.FiscalStreetNumber, Complement: data.FiscalComplement, District: data.FiscalDistrict, City: data.FiscalCity, State: data.FiscalState, Country: data.FiscalCountry, IsPrimary: '1' },
                    { AddressType: 'B', ZipCode: billingSameAsFiscal === '1' ? data.FiscalZipCode : data.BillingZipCode, Street: billingSameAsFiscal === '1' ? data.FiscalStreet : data.BillingStreet, StreetNumber: billingSameAsFiscal === '1' ? data.FiscalStreetNumber : data.BillingStreetNumber, Complement: billingSameAsFiscal === '1' ? data.FiscalComplement : data.BillingComplement, District: billingSameAsFiscal === '1' ? data.FiscalDistrict : data.BillingDistrict, City: billingSameAsFiscal === '1' ? data.FiscalCity : data.BillingCity, State: billingSameAsFiscal === '1' ? data.FiscalState : data.BillingState, Country: billingSameAsFiscal === '1' ? data.FiscalCountry : data.BillingCountry, IsPrimary: '0' }
                ]
            : [{ ZipCode: data.ZipCode, Street: data.Street, StreetNumber: data.StreetNumber, Complement: data.Complement, District: data.District, City: data.City, State: data.State, Country: data.Country || 'BRASIL', IsPrimary: '1' }],
        Contacts: [
            { Type: 'E', Locator: data.PrimaryEmail, ContactRole: 'PRIMARY', IsPrimary: '1' },
            { Type: 'P', Locator: data.PrimaryPhone, ContactRole: 'PRIMARY', IsPrimary: '1' }
        ].filter(contact => contact.Locator)
    };

    if (moduleKey === 'parties') return { ...party, RecId: data.RecId };

    if (moduleKey === 'customers') {
        return {
            RecId: data.RecId,
            CustAccount: data.CustAccount,
            CompanyType: data.CompanyType,
            CurrencyCode: data.CurrencyCode,
            CreditLimit: data.CreditLimit,
            PaymentTermDays: data.PaymentTermDays,
            CustomerGroup: data.CustomerGroup,
            IsActive: data.IsActive,
            IsBlocked: data.IsBlocked,
            BillingSameAsFiscal: billingSameAsFiscal,
            ContactPersons: customerContacts,
            Party: party
        };
    }

    return {
        RecId: data.RecId,
        VendAccount: data.VendAccount,
        CompanyType: data.CompanyType,
        CurrencyCode: data.CurrencyCode,
        PaymentTermDays: data.PaymentTermDays,
        VendorGroup: data.VendorGroup,
        IsActive: data.IsActive,
        IsBlocked: data.IsBlocked,
        BillingSameAsFiscal: billingSameAsFiscal,
        ContactPersons: vendorContacts,
        Party: party
    };
}

async function loadPartyStyleRecord(moduleKey, recordId) {
    const endpointMap = { parties: '/api/parties/', customers: '/api/customers/', vendors: '/api/vendors/' };

    try {
        const record = await window.apiClient.get(`${endpointMap[moduleKey]}${recordId}`);
        const host = document.getElementById('party-form-host');
        host.innerHTML = renderPartyStyleForm(moduleKey, record);
        bindPartyStyleForm(moduleKey);
    } catch (error) {
        notify(error.message, 'error');
    }
}

async function renderDocumentModule(moduleKey) {
    const module = documentModules[moduleKey];
    const meta = viewMeta[moduleKey];
    appState.currentModule = moduleKey;
    appState.currentEntity = null;
    setPageHeader(t(meta.titleKey, meta.fallback), t(`module.${moduleKey}.subtitle`, meta.subtitle));
    setWorkspaceShell(t(meta.titleKey, meta.fallback), t(`module.${moduleKey}.subtitle`, meta.subtitle));

    const toolbar = document.getElementById('workspace-toolbar');
    toolbar.innerHTML += `
        <div class="toolbar-row top-command-row">
            <button type="button" id="cmd-new" class="secondary-button">${t('button.new', 'New Record')}</button>
            <button type="button" id="cmd-save" class="primary-button">${t('button.save', 'Save Record')}</button>
            <button type="button" id="cmd-delete" class="ghost-button">${t('button.delete', 'Delete')}</button>
            <button type="button" id="cmd-attach" class="secondary-button">${t('button.attachfile', 'Anexar arquivo')}</button>
        </div>
    `;

    const content = document.getElementById('workspace-content');
    content.innerHTML = `<div class="loader">${t('loader.documents', 'Loading documents...')}</div>`;

    try {
        const rows = await window.apiClient.get(module.endpoint);
        const hasDetailsTab = module.endpoint.indexOf('/api/journals') === 0;
        content.innerHTML = `
            <div class="workspace-tabs">
                <button type="button" class="secondary-button tab-button active" data-tab="overview">${t('tab.overview', 'Overview')}</button>
                <button type="button" class="secondary-button tab-button" data-tab="general">${t('tab.general', 'General')}</button>
                ${hasDetailsTab ? `<button type="button" class="secondary-button tab-button" data-tab="details">${t('tab.details', 'Details')}</button>` : ''}
            </div>
            <section class="workspace-card" id="tab-overview-panel">
                ${renderTable(module.listColumns, rows)}
            </section>
            <section class="workspace-card hidden" id="tab-general-panel">
                <div id="document-form-host">${renderDocumentForm(moduleKey)}</div>
            </section>
            ${hasDetailsTab ? `<section class="workspace-card hidden" id="tab-details-panel"><div id="document-details-host">${renderDocumentDetailsPanel(moduleKey)}</div></section>` : ''}
        `;

        let selectedRecId = null;
        let selectedRowData = null;

        const updateDeleteButtonState = () => {
            const deleteButton = document.getElementById('cmd-delete');
            if (!deleteButton) {
                return;
            }

            if (!isJournalModule(moduleKey)) {
                deleteButton.disabled = false;
                return;
            }

            const isPosted = selectedRowData && String(selectedRowData.Posted) === '1';
            deleteButton.disabled = !!isPosted;
        };

        const setActiveTab = tab => {
            document.querySelectorAll('.tab-button').forEach(button => button.classList.toggle('active', button.dataset.tab === tab));
            document.getElementById('tab-overview-panel').classList.toggle('hidden', tab !== 'overview');
            document.getElementById('tab-general-panel').classList.toggle('hidden', tab !== 'general');
            const detailsPanel = document.getElementById('tab-details-panel');
            if (detailsPanel) {
                detailsPanel.classList.toggle('hidden', tab !== 'details');
            }
        };

        const selectRowElement = rowElement => {
            document.querySelectorAll('#tab-overview-panel tbody tr').forEach(row => row.classList.remove('selected-row'));
            if (rowElement) {
                rowElement.classList.add('selected-row');
            }
        };

        document.querySelectorAll('#tab-overview-panel tbody tr[data-record]').forEach(rowElement => {
            rowElement.onclick = () => {
                const row = JSON.parse(decodeURIComponent(rowElement.dataset.record));
                selectedRecId = row.RecId;
                selectedRowData = row;
                selectRowElement(rowElement);
                updateDeleteButtonState();
            };

            rowElement.ondblclick = async () => {
                const row = JSON.parse(decodeURIComponent(rowElement.dataset.record));
                selectedRecId = row.RecId;
                selectedRowData = row;
                selectRowElement(rowElement);
                await loadDocumentRecord(moduleKey, row.RecId);
                setActiveTab('general');
                updateDeleteButtonState();
            };
        });

        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', async () => {
                const tab = button.dataset.tab;
                if (tab === 'general' && selectedRecId) {
                    await loadDocumentRecord(moduleKey, selectedRecId);
                }
                if (tab === 'details' && selectedRecId) {
                    await loadDocumentRecord(moduleKey, selectedRecId);
                }
                setActiveTab(tab);
            });
        });

        document.getElementById('cmd-new').onclick = () => {
            selectedRecId = null;
            selectedRowData = null;
            selectRowElement(null);
            document.getElementById('document-form-host').innerHTML = renderDocumentForm(moduleKey);
            if (hasDetailsTab) {
                document.getElementById('document-details-host').innerHTML = renderDocumentDetailsPanel(moduleKey);
            }
            bindDocumentForm(moduleKey);
            setActiveTab('general');
            updateDeleteButtonState();
        };

        document.getElementById('cmd-save').onclick = () => {
            const form = document.getElementById('document-form');
            if (form) {
                form.requestSubmit();
            }
        };

        document.getElementById('cmd-delete').onclick = async () => {
            if (isJournalModule(moduleKey) && selectedRowData && String(selectedRowData.Posted) === '1') {
                notify(t('toast.journal.deleteblocked', 'Posted journals cannot be deleted.'), 'error');
                return;
            }

            const form = document.getElementById('document-form');
            const recId = form ? form.querySelector('[name="RecId"]').value : selectedRecId;

            if (!recId) {
                return;
            }

            try {
                await window.apiClient.delete(`${module.endpoint}/${recId}`);
                notify(t('toast.delete.success', 'Record removed successfully.'), 'success');
                await renderDocumentModule(moduleKey);
            } catch (error) {
                notify(error.message, 'error');
            }
        };

        document.getElementById('cmd-attach').onclick = async () => {
            const form = document.getElementById('document-form');
            const recId = form && form.querySelector('[name="RecId"]')
                ? form.querySelector('[name="RecId"]').value
                : selectedRecId;

            if (!recId) {
                notify(t('toast.attachment.selectrecord', 'Selecione ou salve um registro antes de anexar arquivos.'), 'error');
                return;
            }

            await openAttachmentDialog(moduleKey, recId);
        };

        bindDocumentForm(moduleKey);
        updateDeleteButtonState();
    } catch (error) {
        content.innerHTML = `<div class="workspace-card empty-state">${error.message}</div>`;
    }
}

function renderDocumentForm(moduleKey, record = {}) {
    const module = documentModules[moduleKey];
    const recordWithDefaults = computeDocumentRecord(moduleKey, applyDefaultCompanyToRecord(record));
    const headerSections = buildDocumentFieldSections(module, recordWithDefaults);
    const isJournal = module.endpoint.indexOf('/api/journals') === 0;
    const lineRows = recordWithDefaults.Lines && recordWithDefaults.Lines.length ? recordWithDefaults.Lines : [{}];

    return `
        <div class="table-title">${t(viewMeta[moduleKey].titleKey, viewMeta[moduleKey].fallback)} ${t('form.title', 'Form')}</div>
        <form id="document-form" class="document-header-form">
            <input type="hidden" name="RecId" value="${record.RecId || ''}">
            ${headerSections}
            ${isJournal ? `
                <div class="form-block">
                    <div class="header-actions">
                        <div class="form-title">${t('form.details', 'Details')}</div>
                        <button type="button" class="mini-button" id="open-details-button">${t('button.details', 'Details')}</button>
                    </div>
                </div>
            ` : `
                <div class="form-block">
                    <div class="header-actions">
                        <div class="form-title">${t('form.lines', 'Lines')}</div>
                        <button type="button" class="mini-button" id="add-line-button">${t('button.addline', 'Add Line')}</button>
                    </div>
                    ${module.lineMode === 'grid' ? `
                        <div class="editable-grid-wrap">
                            <table class="editable-grid" id="document-lines-grid">
                                <thead>
                                    <tr>
                                        ${module.lineFields.map(field => `<th>${resolveFieldLabel(field)}</th>`).join('')}
                                        <th>${t('column.Actions', 'Actions')}</th>
                                    </tr>
                                </thead>
                                <tbody id="document-lines-grid-body">
                                    ${lineRows.map(line => renderEditableLineGridRow(module.lineFields, line)).join('')}
                                </tbody>
                            </table>
                        </div>
                    ` : `
                        <div id="lines-container" class="lines-grid">
                            ${lineRows.map(line => renderLineRow(module.lineFields, 'line-row', line)).join('')}
                        </div>
                    `}
                </div>
            `}
            <div class="form-actions">
                <button type="submit" class="primary-button">${t('button.save', 'Save Record')}</button>
                <button type="button" id="document-reset" class="ghost-button">${t('button.new', 'New Record')}</button>
            </div>
        </form>
    `;
}

function computeDocumentRecord(moduleKey, record = {}) {
    if (moduleKey === 'service-invoices') {
        return deriveServiceInvoiceRecord(record);
    }

    if (moduleKey === 'purchase-orders' || moduleKey === 'service-purchase-orders') {
        return derivePurchaseRecord(record);
    }

    if (isJournalModule(moduleKey)) {
        return {
            ...record,
            Posted: String(record.Posted || '0')
        };
    }

    return record;
}

function deriveServiceInvoiceRecord(record = {}) {
    const lines = Array.isArray(record.Lines) ? record.Lines : [];
    const billingAmount = calculateServiceInvoiceBillingAmount(lines);
    const deductionAmount = Number(record.DeductionAmount || 0);
    const totalAmount = Math.max(0, billingAmount - deductionAmount);

    return {
        ...record,
        BillingAmount: billingAmount.toFixed(2),
        TotalAmount: totalAmount.toFixed(2),
        Status: record.Status || 'O',
        Lines: lines
    };
}

function calculateServiceInvoiceBillingAmount(lines = []) {
    return lines.reduce((total, line) => total + Number(line && line.LineAmount ? line.LineAmount : 0), 0);
}

function derivePurchaseRecord(record = {}) {
    const lines = Array.isArray(record.Lines) ? record.Lines : [];
    const billingAmount = calculatePurchaseBillingAmount(lines);
    const deductionAmount = Number(record.DeductionAmount || 0);
    const totalAmount = Math.max(0, billingAmount - deductionAmount);

    return {
        ...record,
        BillingAmount: billingAmount.toFixed(2),
        TotalAmount: totalAmount.toFixed(2),
        Status: record.Status || 'O',
        Lines: lines
    };
}

function calculatePurchaseBillingAmount(lines = []) {
    return lines.reduce((total, line) => {
        const quantity = Number(line && line.Quantity ? line.Quantity : 0);
        const unitPrice = Number(line && line.UnitPrice ? line.UnitPrice : 0);
        const lineAmount = Number(line && line.LineAmount ? line.LineAmount : (quantity * unitPrice));
        return total + lineAmount;
    }, 0);
}

function applyDefaultCompanyToRecord(record = {}) {
    if (record.CompanyRecId) {
        return record;
    }

    const defaultCompany = (appState.lookups.companies || []).find(item => item.IsDefault === '1') || (appState.lookups.companies || [])[0];
    if (!defaultCompany) {
        return record;
    }

    return {
        ...record,
        CompanyRecId: String(defaultCompany.RecId)
    };
}

function renderDocumentDetailsPanel(moduleKey, record = {}) {
    const module = documentModules[moduleKey];
    if (module.endpoint.indexOf('/api/journals') !== 0) {
        return '';
    }

    const lines = record.Lines && record.Lines.length ? record.Lines : [{}];

    return `
        <div class="table-title">${t('form.lines', 'Lines')}</div>
        <div class="form-block">
            <div class="header-actions">
                <div class="form-title">${t('form.details', 'Details')}</div>
                <button type="button" class="mini-button" id="add-line-grid-button">${t('button.addline', 'Add Line')}</button>
                ${moduleKey === 'receipt-journals' ? `<button type="button" class="mini-button" id="capture-invoices-button" style="margin-left:6px;">${t('button.captureservices', 'Capturar Servi\u00e7os em Aberto')}</button>` : ''}
            </div>
            <div class="editable-grid-wrap">
                <table class="editable-grid" id="journal-lines-grid">
                    <thead>
                        <tr>
                            ${module.lineFields.filter(f => f.type !== 'hidden').map(field => `<th>${resolveFieldLabel(field)}</th>`).join('')}
                            <th>${t('column.Actions', 'Actions')}</th>
                        </tr>
                    </thead>
                    <tbody id="journal-lines-grid-body">
                        ${lines.map(line => renderEditableLineGridRow(module.lineFields, line)).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function renderEditableLineGridRow(fields, record = {}) {
    const visibleFields = fields.filter(f => f.type !== 'hidden');
    const hiddenFields  = fields.filter(f => f.type === 'hidden');
    return `
        <tr class="editable-line-row">
            ${visibleFields.map(field => `<td>${renderGridFieldControl(field, record[field.name] || '')}</td>`).join('')}
            <td class="grid-actions-cell"><button type="button" class="mini-button remove-grid-line-button">${t('button.remove', 'Remove')}</button></td>
            ${hiddenFields.map(field => `<td style="display:none;padding:0;border:0;">${renderGridFieldControl(field, record[field.name] || '')}</td>`).join('')}
        </tr>
    `;
}

function renderGridFieldControl(field, value) {
    const safeValue = escapeAttribute(value || '');
    const required = field.required ? 'required' : '';
    const readOnly = field.readOnly ? 'readonly' : '';

    if (field.type === 'hidden') {
        return `<input type="hidden" name="${field.name}" value="${safeValue}">`;
    }

    if (field.type === 'select') {
        const options = typeof field.options === 'function' ? field.options() : (field.options || []);
        const emptyOption = field.emptyOption
            ? `<option value="">${t(field.emptyOption.labelKey || 'option.select', field.emptyOption.label || 'Select')}</option>`
            : '';
        return `<select class="grid-input" name="${field.name}" ${required}>${emptyOption}${options.map(option => `<option value="${option.value}" ${String(value) === String(option.value) ? 'selected' : ''}>${option.label}</option>`).join('')}</select>`;
    }

    if (field.type === 'month') {
        return `<input class="grid-input" type="month" name="${field.name}" value="${safeValue}" ${required}>`;
    }

    if (field.type === 'date') {
        return `<input class="grid-input" type="date" name="${field.name}" value="${safeValue ? String(safeValue).slice(0, 10) : ''}" ${required}>`;
    }

    if (field.type === 'number') {
        return `<input class="grid-input" type="number" step="0.01" name="${field.name}" value="${safeValue}" ${required} ${readOnly}>`;
    }

    return `<input class="grid-input" type="text" name="${field.name}" value="${safeValue}" ${required} ${readOnly}>`;
}

function renderLineRow(fields, lineClass, record = {}) {
    return `
        <div class="${lineClass}">
            ${fields.map(field => renderField({ ...field, compact: true }, record[field.name] || '')).join('')}
            <button type="button" class="mini-button remove-line-button">${t('button.remove', 'Remove')}</button>
        </div>
    `;
}

function buildFunctionalFieldSections(fields, record = {}) {
    const sections = {
        identification: [],
        dates: [],
        financial: [],
        control: [],
        general: []
    };

    fields.forEach(field => {
        sections[resolveFieldSection(field)].push(field);
    });

    const ordered = [
        ['identification', 'form.section.identification', 'Identification'],
        ['dates', 'form.section.dates', 'Dates'],
        ['financial', 'form.section.financial', 'Financial'],
        ['general', 'form.section.general', 'General'],
        ['control', 'form.section.control', 'Control']
    ];

    return ordered
        .filter(([key]) => sections[key].length > 0)
        .map(([key, titleKey, titleFallback]) => `
            <div class="form-block form-section section-${key}">
                <div class="form-title">${t(titleKey, titleFallback)}</div>
                <div class="fields-grid">
                    ${sections[key].map(field => renderField(field, record[field.name] || '')).join('')}
                </div>
            </div>
        `)
        .join('');
}

function buildDocumentFieldSections(module, record = {}) {
    if (!Array.isArray(module.sections) || !module.sections.length) {
        return buildFunctionalFieldSections(module.headerFields, record);
    }

    const sections = {};
    module.sections.forEach(section => {
        sections[section.key] = [];
    });

    module.headerFields.forEach(field => {
        const sectionKey = field.section && sections[field.section] ? field.section : module.sections[0].key;
        sections[sectionKey].push(field);
    });

    return module.sections
        .filter(section => sections[section.key].length > 0)
        .map(section => `
            <div class="form-block form-section section-${section.key}">
                <div class="form-title">${t(section.titleKey, section.fallback)}</div>
                <div class="fields-grid">
                    ${sections[section.key].map(field => renderField(field, record[field.name] || '')).join('')}
                </div>
            </div>
        `)
        .join('');
}

function resolveFieldLabel(field) {
    return t(field.labelKey || `field.${field.name}`, field.label || humanize(field.name));
}

function resolveFieldSection(field) {
    const fieldName = String(field.name || '').toLowerCase();

    if (/isactive|isblocked|status|posted/.test(fieldName)) {
        return 'control';
    }

    if (/date|duedate|period|month/.test(fieldName)) {
        return 'dates';
    }

    if (/amount|price|credit|debit|limit|currency|paymentterm|quantity|lineamount|unitprice|tax|deduction/.test(fieldName)) {
        return 'financial';
    }

    if (/id$|code$|number$|account$|batch|group|type$/.test(fieldName)) {
        return 'identification';
    }

    return 'general';
}

function bindDocumentForm(moduleKey) {
    const module = documentModules[moduleKey];
    const form = document.getElementById('document-form');
    const addLineButton = document.getElementById('add-line-button');
    const addLineGridButton = document.getElementById('add-line-grid-button');
    const openDetailsButton = document.getElementById('open-details-button');
    const resetButton = document.getElementById('document-reset');

    if (addLineButton) {
        addLineButton.addEventListener('click', () => {
            const gridBody = document.getElementById('document-lines-grid-body');
            if (gridBody) {
                gridBody.insertAdjacentHTML('beforeend', renderEditableLineGridRow(module.lineFields));
                bindRemoveGridLineButtons();
                refreshDocumentComputedState(moduleKey, form);
                return;
            }

            const container = document.getElementById('lines-container');
            container.insertAdjacentHTML('beforeend', renderLineRow(module.lineFields, 'line-row'));
            bindRemoveLineButtons();
            refreshDocumentComputedState(moduleKey, form);
        });
    }

    if (addLineGridButton) {
        addLineGridButton.addEventListener('click', () => {
            const body = document.getElementById('journal-lines-grid-body');
            if (!body) {
                return;
            }
            body.insertAdjacentHTML('beforeend', renderEditableLineGridRow(module.lineFields));
            bindRemoveGridLineButtons();
        });
    }

    const captureInvoicesButton = document.getElementById('capture-invoices-button');
    if (captureInvoicesButton) {
        captureInvoicesButton.addEventListener('click', () => openCaptureInvoicesModal(moduleKey));
    }

    if (openDetailsButton) {
        openDetailsButton.addEventListener('click', () => {
            const detailsTab = document.querySelector('.tab-button[data-tab="details"]');
            if (detailsTab) {
                detailsTab.click();
            }
        });
    }

    bindRemoveLineButtons();
    bindRemoveGridLineButtons();
    bindDocumentComputedState(moduleKey, form);
    refreshDocumentComputedState(moduleKey, form);

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const payload = buildDocumentPayload(moduleKey, form);

        try {
            if (payload.RecId) {
                await window.apiClient.put(`${module.endpoint}/${payload.RecId}`, payload);
            } else {
                await window.apiClient.post(module.endpoint, payload);
            }

            notify(t('toast.save.success', 'Record saved successfully.'), 'success');
            await renderDocumentModule(moduleKey);
        } catch (error) {
            notify(error.message, 'error');
        }
    });

    resetButton.addEventListener('click', () => renderDocumentModule(moduleKey));
}

function bindRemoveLineButtons() {
    document.querySelectorAll('.remove-line-button').forEach(button => {
        button.onclick = () => {
            const rows = document.querySelectorAll('#lines-container .line-row');
            if (rows.length > 1) {
                button.parentElement.remove();
                refreshDocumentComputedState(appState.currentModule, document.getElementById('document-form'));
            }
        };
    });
}

function bindRemoveGridLineButtons() {
    document.querySelectorAll('.remove-grid-line-button').forEach(button => {
        button.onclick = () => {
            const body = button.closest('tbody');
            const rows = body ? body.querySelectorAll('.editable-line-row') : [];
            if (rows.length > 1) {
                button.closest('tr').remove();
                refreshDocumentComputedState(appState.currentModule, document.getElementById('document-form'));
            }
        };
    });
}

function bindDocumentComputedState(moduleKey, form) {
    if (!form || !['service-invoices', 'purchase-orders', 'service-purchase-orders'].includes(moduleKey)) {
        return;
    }

    const refresh = event => {
        if (!event || !event.target || !event.target.name) {
            return;
        }

        if (['DeductionAmount', 'LineAmount', 'Quantity', 'UnitPrice'].includes(event.target.name)) {
            refreshDocumentComputedState(moduleKey, form);
        }
    };

    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);
}

function refreshDocumentComputedState(moduleKey, form) {
    if (!form || !['service-invoices', 'purchase-orders', 'service-purchase-orders'].includes(moduleKey)) {
        return;
    }

    const lineRows = Array.from(document.querySelectorAll('#document-lines-grid-body .editable-line-row'));
    const billingAmount = lineRows.reduce((total, row) => {
        const quantity = Number((row.querySelector('[name="Quantity"]') || {}).value || 0);
        const unitPrice = Number((row.querySelector('[name="UnitPrice"]') || {}).value || 0);
        const lineAmountInput = row.querySelector('[name="LineAmount"]');

        if (lineAmountInput && moduleKey !== 'service-invoices' && (!lineAmountInput.value || lineAmountInput.value === '0')) {
            lineAmountInput.value = (quantity * unitPrice).toFixed(2);
        }

        const lineAmount = Number((lineAmountInput || {}).value || 0);
        return total + lineAmount;
    }, 0);
    const deductionInput = form.querySelector('[name="DeductionAmount"]');
    const deductionAmount = Number(deductionInput ? deductionInput.value || 0 : 0);
    const totalAmount = Math.max(0, billingAmount - deductionAmount);
    const billingInput = form.querySelector('[name="BillingAmount"]');
    const totalInput = form.querySelector('[name="TotalAmount"]');

    if (billingInput) {
        billingInput.value = billingAmount.toFixed(2);
    }

    if (totalInput) {
        totalInput.value = totalAmount.toFixed(2);
    }
}

function buildDocumentPayload(moduleKey, form) {
    const module = documentModules[moduleKey];
    const payload = { ...(module.fixedPayload || {}), RecId: form.querySelector('[name="RecId"]').value || '' };

    module.headerFields.forEach(field => {
        const input = form.querySelector(`[name="${field.name}"]`);
        if (field.type === 'checkbox') {
            payload[field.name] = input && input.checked ? '1' : '0';
            return;
        }
        payload[field.name] = input ? input.value : '';
    });

    const lineRows = document.querySelectorAll('#document-lines-grid-body .editable-line-row').length
        ? Array.from(document.querySelectorAll('#document-lines-grid-body .editable-line-row'))
        : document.querySelectorAll('#journal-lines-grid-body .editable-line-row').length
        ? Array.from(document.querySelectorAll('#journal-lines-grid-body .editable-line-row'))
        : Array.from(document.querySelectorAll('#lines-container .line-row'));

    payload.Lines = lineRows.map(row => {
        const line = {};
        module.lineFields.forEach(field => {
            const input = row.querySelector(`[name="${field.name}"]`);
            line[field.name] = input ? input.value : '';
        });
        return line;
    });

    return payload;
}

async function loadDocumentRecord(moduleKey, recordId) {
    const module = documentModules[moduleKey];

    try {
        const record = await window.apiClient.get(`${module.detailEndpoint}${recordId}`);
        const host = document.getElementById('document-form-host');
        host.innerHTML = renderDocumentForm(moduleKey, record);
        const detailsHost = document.getElementById('document-details-host');
        if (detailsHost) {
            detailsHost.innerHTML = renderDocumentDetailsPanel(moduleKey, record);
        }
        bindDocumentForm(moduleKey);
    } catch (error) {
        notify(error.message, 'error');
    }
}

function renderReportHub() {
    const meta = viewMeta.reports;
    setPageHeader(t(meta.titleKey, meta.fallback), t('module.reports.subtitle', meta.subtitle));
    setWorkspaceShell(t(meta.titleKey, meta.fallback), t('module.reports.subtitle', meta.subtitle));

    const toolbar = document.getElementById('workspace-toolbar');
    toolbar.innerHTML = `
        <div class="report-selector-grid">
            ${Object.entries(reportModules).map(([key, report]) => `<button class="secondary-button report-selector" data-report="${key}">${t(report.titleKey, report.fallback)}</button>`).join('')}
        </div>
    `;

    document.getElementById('workspace-content').innerHTML = `<div class="workspace-card empty-state">${t('reports.select', 'Select a report to start.')}</div>`;

    document.querySelectorAll('.report-selector').forEach(button => {
        button.addEventListener('click', () => renderReportModule(button.dataset.report));
    });
}

function setWorkspaceShell(title, subtitle) {
    document.getElementById('workspace-toolbar').innerHTML = `
        <div>
            <div class="table-title">${title}</div>
            <p class="table-subtitle">${subtitle}</p>
        </div>
    `;
}

async function renderReportModule(reportKey) {
    const report = reportModules[reportKey];
    const content = document.getElementById('workspace-content');
    const title = t(report.titleKey, report.fallback);
    const subtitle = t('module.reports.subtitle', viewMeta.reports.subtitle);

    setPageHeader(title, subtitle);
    setWorkspaceShell(title, subtitle);

    content.innerHTML = `
        <div class="workspace-card">
            <div class="table-title">${t(report.titleKey, report.fallback)}</div>
            <form id="report-form" class="filters-grid">
                ${report.filters.map(field => renderField(field, '')).join('')}
                <div class="report-actions">
                    <button type="submit" class="primary-button">${t('button.runreport', 'Run Report')}</button>
                </div>
            </form>
            <div id="report-results" class="workspace-card" style="margin-top:18px;"></div>
        </div>
    `;

    document.getElementById('report-form').addEventListener('submit', async event => {
        event.preventDefault();
        await executeReport(reportKey, new FormData(event.target));
    });
}

async function executeReport(reportKey, formData) {
    const report = reportModules[reportKey];
    const query = new URLSearchParams();

    Array.from(formData.entries()).forEach(([key, value]) => {
        if (value) query.append(key, value);
    });

    const target = document.getElementById('report-results');
    target.innerHTML = `<div class="loader">${t('loader.report', 'Executando relatório...')}</div>`;

    try {
        const data = await window.apiClient.get(`${report.endpoint}?${query.toString()}`);
        const rows = data.rows || [];
        const totals = data.totals || {};
        const period = data.period || null;

        if (!rows.length) {
            target.innerHTML = `<div class="empty-state">${t('report.nodata', 'Nenhum dado encontrado para os filtros selecionados.')}</div>`;
            return;
        }

        // Store report data globally for export/print
        window.currentReportData = {
            reportKey: reportKey,
            reportTitle: t(report.titleKey, report.fallback),
            columns: Object.keys(rows[0]).filter(c => c !== 'LineKey' && c !== 'LineType'),
            rows: rows,
            totals: totals,
            isDRE: report.isDRE || false,
            period: period
        };

        // Renderização especial para DRE
        if (report.isDRE) {
            target.innerHTML = renderDREReport(rows, totals, period) + renderReportActions();
            bindReportActions();
            return;
        }

        target.innerHTML = renderTable(window.currentReportData.columns, rows, null, totals) + renderReportActions();
        bindReportActions();
    } catch (error) {
        target.innerHTML = `<div class="empty-state">${error.message}</div>`;
    }
}

function renderDREReport(rows, totals, period) {
    const periodText = period ? `Período: ${period.from} a ${period.to}` : '';
    
    const lineRows = rows.map(row => {
        const lineType = row.LineType || 'normal';
        const cssClass = lineType === 'header' ? 'dre-header' : 
                         lineType === 'subtotal' ? 'dre-subtotal' : 
                         lineType === 'total' ? 'dre-total' :
                         lineType === 'withdrawal' ? 'dre-withdrawal' :
                         lineType === 'deduction' || lineType === 'cost' || lineType === 'expense' ? 'dre-deduction' : '';
        const amount = formatCurrency(row.Amount);
        return `<tr class="${cssClass}"><td>${row.LineName}</td><td class="dre-amount">${amount}</td></tr>`;
    }).join('');

    return `
        <div class="dre-report">
            ${periodText ? `<div class="dre-period">${periodText}</div>` : ''}
            <table class="dre-table">
                <thead>
                    <tr>
                        <th>${t('dre.description', 'Descrição')}</th>
                        <th class="dre-amount-header">${t('dre.amount', 'Valor (R$)')}</th>
                    </tr>
                </thead>
                <tbody>${lineRows}</tbody>
            </table>
            <div class="dre-summary">
                <div class="dre-summary-item">
                    <span>${t('dre.grossRevenue', 'Receita Bruta')}</span>
                    <strong>${formatCurrency(totals.ReceitaBruta || 0)}</strong>
                </div>
                <div class="dre-summary-item">
                    <span>${t('dre.netIncome', 'Lucro Líquido')}</span>
                    <strong class="${(totals.LucroLiquido || 0) >= 0 ? 'positive' : 'negative'}">${formatCurrency(totals.LucroLiquido || 0)}</strong>
                </div>
                ${totals.RetiradaLucro !== undefined ? `
                <div class="dre-summary-item">
                    <span>${t('dre.profitWithdrawal', 'Retirada de Lucro')}</span>
                    <strong class="negative">${formatCurrency(totals.RetiradaLucro || 0)}</strong>
                </div>
                <div class="dre-summary-item">
                    <span>${t('dre.retainedEarnings', 'Lucro Retido')}</span>
                    <strong class="${(totals.LucroRetido || 0) >= 0 ? 'positive' : 'negative'}">${formatCurrency(totals.LucroRetido || 0)}</strong>
                </div>
                ` : ''}
                ${totals.OpeningBalance !== undefined ? `
                <div class="dre-summary-item">
                    <span>${t('dre.openingBalance', 'Saldo Inicial')}</span>
                    <strong>${formatCurrency(totals.OpeningBalance || 0)}</strong>
                </div>
                <div class="dre-summary-item">
                    <span>${t('dre.closingBalance', 'Saldo Final')}</span>
                    <strong class="${(totals.ClosingBalance || 0) >= 0 ? 'positive' : 'negative'}">${formatCurrency(totals.ClosingBalance || 0)}</strong>
                </div>
                ` : ''}
            </div>
        </div>
    `;
}

function renderTable(columns, rows, onClick = null, totals = null) {
    const body = rows.length
        ? rows.map(row => `<tr data-record="${encodeURIComponent(JSON.stringify(row))}">${columns.map(column => `<td>${formatCell(row[column])}</td>`).join('')}</tr>`).join('')
        : `<tr><td colspan="${columns.length || 1}">${t('table.norecords', 'Nenhum registro encontrado.')}</td></tr>`;

    const footer = totals ? `<tfoot><tr>${columns.map((column, index) => `<td>${index === 0 ? t('table.totals', 'Totais') : formatFooterValue(column, totals[column])}</td>`).join('')}</tr></tfoot>` : '';

    setTimeout(() => {
        if (!onClick) return;
        document.querySelectorAll('tbody tr[data-record]').forEach(row => {
            row.onclick = () => onClick(JSON.parse(decodeURIComponent(row.dataset.record)));
        });
    }, 0);

    return `
        <div class="table-wrap">
            <table>
                <thead><tr>${columns.map(column => `<th>${columnLabel(column)}</th>`).join('')}</tr></thead>
                <tbody>${body}</tbody>
                ${footer}
            </table>
        </div>
    `;
}

function renderField(field, value) {
    const options = typeof field.options === 'function' ? field.options() : (field.options || []);
    const required = field.required ? 'required' : '';
    const step = field.type === 'number' ? 'step="0.01"' : '';
    const compactClass = field.compact ? 'compact-field' : '';
    const spanClass = `field-span-${resolveFieldSpan(field)}`;
    const labelText = field.labelKey ? t(field.labelKey, field.label) : t(`field.${field.name}`, field.label || humanize(field.name));
    const readOnly = field.readOnly ? 'readonly' : '';

    if (field.type === 'select') {
        const emptyOpt = field.emptyOption ? `<option value="">${t('option.select', '-- Selecione --')}</option>` : '';
        return `
            <label class="${compactClass} ${spanClass}">
                <span>${labelText}</span>
                <select name="${field.name}" ${required}>
                    ${emptyOpt}
                    ${options.map(option => `<option value="${option.value}" ${String(value) === String(option.value) ? 'selected' : ''}>${option.label}</option>`).join('')}
                </select>
            </label>
        `;
    }

    if (field.type === 'textarea') {
        return `
            <label class="${compactClass} ${spanClass}">
                <span>${labelText}</span>
                <textarea name="${field.name}" ${required} ${readOnly}>${value || ''}</textarea>
            </label>
        `;
    }

    if (field.type === 'checkbox') {
        const checked = String(value || '0') === '1' ? 'checked' : '';
        return `
            <label class="inline-checkbox ${compactClass} ${spanClass}">
                <input type="checkbox" name="${field.name}" value="1" ${checked}>
                <span>${labelText}</span>
            </label>
        `;
    }

    return `
        <label class="${compactClass} ${spanClass}">
            <span>${labelText}</span>
            <input type="${field.type || 'text'}" name="${field.name}" value="${escapeAttribute(value || '')}" ${required} ${step} ${readOnly}>
        </label>
    `;
}

function resolveFieldSpan(field) {
    if (field.compact) {
        return 1;
    }

    if (field.type === 'textarea') {
        return 3;
    }

    const fieldName = String(field.name || '').toLowerCase();

    if (/description|notes|textvalue|street|name|mainlogourl/.test(fieldName)) {
        return 2;
    }

    return 1;
}

function setPageHeader(title, subtitle) {
    document.getElementById('page-title').textContent = title;
    document.getElementById('page-subtitle').textContent = subtitle;
}

function statusOptions(order) {
    return order.map(value => ({ value, label: value === '1' ? t('option.yes', 'Yes') : t('option.no', 'No') }));
}

function companyOptions() {
    const sorted = [...(appState.lookups.companies || [])].sort((a, b) => {
        if (a.IsDefault === b.IsDefault) {
            return String(a.Alias || '').localeCompare(String(b.Alias || ''));
        }

        return a.IsDefault === '1' ? -1 : 1;
    });

    return sorted.map(item => ({
        value: item.RecId,
        label: item.IsDefault === '1'
            ? `${item.Alias} (${t('field.IsDefault', 'Empresa padrao')})`
            : item.Alias
    }));
}

function financialStatusOptions(includeAll = false) {
    const options = [
        { value: 'O', label: t('status.open', 'Open') },
        { value: 'P', label: t('status.paid', 'Paid') },
        { value: 'C', label: t('status.cancelled', 'Cancelled') }
    ];

    return includeAll ? [{ value: '', label: t('option.all', 'All') }, ...options] : options;
}

function serviceInvoiceStatusOptions() {
    return [
        { value: 'O', label: t('status.open', 'Open') },
        { value: 'B', label: t('status.billed', 'Billed') },
        { value: 'C', label: t('status.cancelled', 'Cancelled') }
    ];
}

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0));
}

function formatCell(value) {
    if (value === null || value === undefined || value === '') return t('value.empty', '-');
    if (['O', 'P', 'B', 'C'].includes(String(value).toUpperCase())) return translateStatusValue(value);
    if (!Number.isNaN(Number(value)) && (String(value).includes('.') || String(value).includes(','))) return formatCurrency(value);
    if (String(value).match(/^\d{4}-\d{2}-\d{2}/)) return String(value).slice(0, 10);
    return value;
}

function translateStatusValue(value) {
    const normalized = String(value || '').trim().toUpperCase();
    const translations = {
        O: t('status.open', 'Open'),
        P: t('status.paid', 'Paid'),
        B: t('status.billed', 'Billed'),
        C: t('status.cancelled', 'Cancelled')
    };

    return translations[normalized] || value;
}

function formatFooterValue(column, value) {
    return value === undefined ? '' : formatCell(value);
}

function humanize(value) {
    return value.replace(/([A-Z])/g, ' $1').replace(/[-_]/g, ' ').replace(/^./, char => char.toUpperCase()).trim();
}

function columnLabel(columnName) {
    return t(`column.${columnName}`, humanize(columnName));
}

function resolveAttachmentEntity(moduleKey) {
    const map = {
        roles: 'SecurityRole',
        users: 'SysUserInfo',
        labels: 'SysLabelText',
        'number-sequences': 'SysNumberSequenceTable',
        company: 'CompanyInfo',
        parties: 'DirPartyTable',
        customers: 'CustTable',
        vendors: 'VendTable',
        products: 'InventTable',
        'service-codes': 'ServiceCodeTable',
        'service-invoices': 'CustInvoiceJour',
        'purchase-orders': 'PurchTable',
        'service-purchase-orders': 'PurchTable',
        journals: 'LedgerJournalTable',
        'receipt-journals': 'LedgerJournalTable',
        'payment-journals': 'LedgerJournalTable',
        'tax-journals': 'LedgerJournalTable'
    };

    return map[moduleKey] || moduleKey;
}

function ensureAttachmentModal() {
    let modal = document.getElementById('attachment-modal');

    if (modal) {
        return modal;
    }

    modal = document.createElement('div');
    modal.id = 'attachment-modal';
    modal.className = 'attachment-modal hidden';
    modal.innerHTML = `
        <div class="attachment-modal-backdrop" data-close-attachment></div>
        <div class="attachment-modal-panel glass-card">
            <div class="header-actions">
                <div class="table-title" id="attachment-modal-title">${t('attachment.title', 'Anexos')}</div>
                <button type="button" class="ghost-button" id="attachment-close">${t('button.close', 'Fechar')}</button>
            </div>
            <div class="form-block">
                <div class="fields-grid three-columns">
                    <label class="field-span-2">
                        <span>${t('field.AttachmentFile', 'Arquivo')}</span>
                        <input type="file" id="attachment-file-input">
                    </label>
                    <label>
                        <span>${t('field.AttachmentNotes', 'Observacao')}</span>
                        <input type="text" id="attachment-notes-input" maxlength="255">
                    </label>
                </div>
                <div class="form-actions" style="justify-content:flex-start;">
                    <button type="button" class="primary-button" id="attachment-upload-button">${t('button.upload', 'Enviar')}</button>
                </div>
            </div>
            <div id="attachment-list-host" class="workspace-card"></div>
        </div>
    `;

    document.body.appendChild(modal);

    modal.querySelector('#attachment-close').addEventListener('click', closeAttachmentDialog);
    modal.querySelector('[data-close-attachment]').addEventListener('click', closeAttachmentDialog);

    return modal;
}

function closeAttachmentDialog() {
    const modal = document.getElementById('attachment-modal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

// ── Capture Open Service Invoices Modal ────────────────────────────────────

function ensureCaptureInvoicesModal() {
    let modal = document.getElementById('capture-invoices-modal');
    if (modal) {
        return modal;
    }

    modal = document.createElement('div');
    modal.id = 'capture-invoices-modal';
    modal.className = 'attachment-modal hidden';
    modal.innerHTML = `
        <div class="attachment-modal-backdrop" id="capture-invoices-backdrop"></div>
        <div class="attachment-modal-panel glass-card">
            <div class="header-actions">
                <div class="table-title">${t('button.captureservices', 'Capturar Servi\u00e7os em Aberto')}</div>
                <button type="button" class="ghost-button" id="capture-invoices-close">${t('button.close', 'Fechar')}</button>
            </div>
            <div id="capture-invoices-body-host"></div>
            <div class="form-actions" style="margin-top:12px;">
                <button type="button" class="primary-button" id="capture-invoices-transfer">${t('button.transfer', 'Transferir')}</button>
                <button type="button" class="ghost-button" id="capture-invoices-cancel">${t('button.cancel', 'Cancelar')}</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    modal.querySelector('#capture-invoices-close').addEventListener('click', closeCaptureInvoicesModal);
    modal.querySelector('#capture-invoices-cancel').addEventListener('click', closeCaptureInvoicesModal);
    modal.querySelector('#capture-invoices-backdrop').addEventListener('click', closeCaptureInvoicesModal);

    return modal;
}

function closeCaptureInvoicesModal() {
    const modal = document.getElementById('capture-invoices-modal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

async function openCaptureInvoicesModal(moduleKey) {
    const modal = ensureCaptureInvoicesModal();
    const host = modal.querySelector('#capture-invoices-body-host');
    host.innerHTML = `<div class="loader">${t('loader.data', 'Loading data...')}</div>`;
    modal.classList.remove('hidden');

    let invoices = [];

    try {
        invoices = await window.apiClient.get('/api/service-invoices/open-for-journal');
    } catch (error) {
        host.innerHTML = `<div class="workspace-card empty-state">${error.message}</div>`;
        return;
    }

    if (!invoices.length) {
        host.innerHTML = `<div class="workspace-card empty-state">${t('capture.invoices.empty', 'Nenhuma fatura de servi\u00e7o em aberto sem di\u00e1rio.')}</div>`;
        return;
    }

    host.innerHTML = `
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="capture-select-all" title="${t('button.selectall', 'Selecionar todos')}"></th>
                        <th>${t('column.InvoiceId', 'Fatura')}</th>
                        <th>${t('column.CustomerName', 'Cliente')}</th>
                        <th>${t('column.InvoiceNumber', 'N\u00ba NFSe')}</th>
                        <th>${t('column.InvoiceDate', 'Data')}</th>
                        <th>${t('column.DueDate', 'Vencimento')}</th>
                        <th>${t('column.TotalAmount', 'Total')}</th>
                    </tr>
                </thead>
                <tbody id="capture-invoices-table-body">
                    ${invoices.map(inv => `
                        <tr class="capture-invoice-row" data-invoice="${encodeURIComponent(JSON.stringify(inv))}" style="cursor:pointer;">
                            <td><input type="checkbox" class="capture-invoice-check"></td>
                            <td>${escapeAttribute(inv.InvoiceId || '')}</td>
                            <td>${escapeAttribute(inv.CustomerName || '')}</td>
                            <td>${escapeAttribute(inv.InvoiceNumber || '')}</td>
                            <td>${formatCell(inv.InvoiceDate)}</td>
                            <td>${formatCell(inv.DueDate)}</td>
                            <td>${formatCurrency(inv.TotalAmount)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;

    modal.querySelector('#capture-select-all').addEventListener('change', function () {
        modal.querySelectorAll('.capture-invoice-check').forEach(cb => { cb.checked = this.checked; });
    });

    modal.querySelectorAll('.capture-invoice-row').forEach(row => {
        row.addEventListener('click', e => {
            if (e.target.tagName === 'INPUT') {
                return;
            }
            const cb = row.querySelector('.capture-invoice-check');
            cb.checked = !cb.checked;
        });
    });

    modal.querySelector('#capture-invoices-transfer').onclick = () => {
        const module = documentModules[moduleKey];
        const gridBody = document.getElementById('journal-lines-grid-body');
        if (!gridBody) {
            closeCaptureInvoicesModal();
            return;
        }

        const selectedRows = Array.from(modal.querySelectorAll('.capture-invoice-row'))
            .filter(row => row.querySelector('.capture-invoice-check').checked);

        if (!selectedRows.length) {
            notify(t('toast.capture.empty', 'Selecione ao menos uma fatura para transferir.'), 'error');
            return;
        }

        selectedRows.forEach(row => {
            const inv = JSON.parse(decodeURIComponent(row.dataset.invoice));
            const lineRecord = {
                TransDate: inv.InvoiceDate ? String(inv.InvoiceDate).slice(0, 10) : '',
                DueDate: inv.DueDate ? String(inv.DueDate).slice(0, 10) : '',
                Voucher: inv.InvoiceNumber || '',
                Description: ('NFSe ' + (inv.InvoiceNumber || inv.InvoiceId) + ' - ' + (inv.CustomerName || '')).trim(),
                CustAccount: inv.CustAccount || '',
                ReceivedFlag: '0',
                AmountCurDebit: '0',
                AmountCurCredit: String(inv.TotalAmount || '0'),
                Status: 'O',
                ServiceInvoiceRecId: String(inv.RecId || '')
            };
            gridBody.insertAdjacentHTML('beforeend', renderEditableLineGridRow(module.lineFields, lineRecord));
        });

        bindRemoveGridLineButtons();
        closeCaptureInvoicesModal();
        notify(t('toast.capture.success', selectedRows.length + ' fatura(s) transferida(s) para o di\u00e1rio.'), 'success');
    };
}

// ── End Capture Modal ──────────────────────────────────────────────────────

// ── Report Export and Print Functions ──────────────────────────────────────

function renderReportActions() {
    return `
        <div style="margin-top: 16px; display: flex; gap: 12px; justify-content: flex-start;">
            <button type="button" class="primary-button" id="export-pdf-button" title="Exportar para PDF">${t('button.exportpdf', 'Exportar PDF')}</button>
            <button type="button" class="primary-button" id="export-excel-button" title="Exportar para Excel">${t('button.exportexcel', 'Exportar Excel')}</button>
            <button type="button" class="secondary-button" id="print-report-button" title="Imprimir">${t('button.print', 'Imprimir')}</button>
        </div>
    `;
}

function bindReportActions() {
    const pdfBtn = document.getElementById('export-pdf-button');
    const excelBtn = document.getElementById('export-excel-button');
    const printBtn = document.getElementById('print-report-button');

    if (pdfBtn) pdfBtn.addEventListener('click', exportReportToPDF);
    if (excelBtn) excelBtn.addEventListener('click', exportReportToExcel);
    if (printBtn) printBtn.addEventListener('click', printReport);
}

function exportReportToPDF() {
    if (!window.currentReportData) {
        notify(t('toast.report.nodata', 'Nenhum relatório disponível para exportação.'), 'error');
        return;
    }

    const data = window.currentReportData;
    const element = document.createElement('div');
    const timestamp = new Date().toLocaleDateString('pt-BR');

    element.innerHTML = `
        <div style="padding: 20px; font-family: Arial, sans-serif;">
            <h1 style="margin-bottom: 5px;">${data.reportTitle}</h1>
            <p style="color: #666; margin-bottom: 20px;">Gerado em: ${timestamp}</p>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <thead>
                    <tr style="background-color: #f5f5f5; border-bottom: 2px solid #000;">
                        ${data.columns.map(col => `<th style="text-align: left; padding: 8px; border: 1px solid #ddd;">${col}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
                    ${data.rows.map(row => `
                        <tr style="border-bottom: 1px solid #ddd;">
                            ${data.columns.map(col => `<td style="padding: 8px; border: 1px solid #ddd;">${formatCell(row[col])}</td>`).join('')}
                        </tr>
                    `).join('')}
                    ${data.totals && Object.keys(data.totals).length > 0 ? `
                        <tr style="background-color: #f9f9f9; font-weight: bold; border-top: 2px solid #000; border-bottom: 2px solid #000;">
                            <td style="padding: 8px; border: 1px solid #ddd;">${t('table.totals', 'Totais')}</td>
                            ${data.columns.slice(1).map(col => `<td style="padding: 8px; border: 1px solid #ddd;">${formatFooterValue(col, data.totals[col])}</td>`).join('')}
                        </tr>
                    ` : ''}
                </tbody>
            </table>
        </div>
    `;

    const opt = {
        margin: 10,
        filename: `${data.reportTitle.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { orientation: 'landscape', unit: 'mm', format: 'a4' }
    };

    html2pdf().set(opt).from(element).save();
    notify(t('toast.pdf.exported', 'PDF exportado com sucesso!'), 'success');
}

function exportReportToExcel() {
    if (!window.currentReportData) {
        notify(t('toast.report.nodata', 'Nenhum relatório disponível para exportação.'), 'error');
        return;
    }

    const data = window.currentReportData;
    const wb = XLSX.utils.book_new();
    
    // Preparar dados para o Excel
    const wsData = [data.columns];
    data.rows.forEach(row => {
        wsData.push(data.columns.map(col => formatCell(row[col]).replace(/<[^>]*>/g, '')));
    });

    // Adicionar totais se existirem
    if (data.totals && Object.keys(data.totals).length > 0) {
        const totalsRow = [t('table.totals', 'Totais')];
        data.columns.slice(1).forEach(col => {
            totalsRow.push(formatFooterValue(col, data.totals[col]).replace(/<[^>]*>/g, ''));
        });
        wsData.push(totalsRow);
    }

    const ws = XLSX.utils.aoa_to_sheet(wsData);
    
    // Ajustar largura das colunas
    const colWidths = data.columns.map(col => Math.min(Math.max(col.length, 15), 50));
    ws['!cols'] = colWidths.map(w => ({ wch: w }));

    XLSX.utils.book_append_sheet(wb, ws, data.reportTitle.substring(0, 31));
    XLSX.writeFile(wb, `${data.reportTitle.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.xlsx`);
    
    notify(t('toast.excel.exported', 'Excel exportado com sucesso!'), 'success');
}

function printReport() {
    if (!window.currentReportData) {
        notify(t('toast.report.nodata', 'Nenhum relatório disponível para impressão.'), 'error');
        return;
    }

    const data = window.currentReportData;
    const printWindow = window.open('', '', 'width=1200,height=800');
    const timestamp = new Date().toLocaleDateString('pt-BR');

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
            <head>
                <title>${data.reportTitle}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    h1 { margin-bottom: 5px; }
                    .timestamp { color: #666; margin-bottom: 20px; font-size: 12px; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    th { background-color: #f5f5f5; text-align: left; padding: 8px; border: 1px solid #ddd; font-weight: bold; }
                    td { padding: 8px; border: 1px solid #ddd; }
                    tbody tr:nth-child(even) { background-color: #f9f9f9; }
                    tfoot tr { background-color: #f5f5f5; font-weight: bold; border-top: 2px solid #000; }
                    @media print { body { margin: 0; } }
                </style>
            </head>
            <body>
                <h1>${data.reportTitle}</h1>
                <div class="timestamp">Gerado em: ${timestamp}</div>
                <table>
                    <thead>
                        <tr>
                            ${data.columns.map(col => `<th>${col}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${data.rows.map(row => `
                            <tr>
                                ${data.columns.map(col => `<td>${formatCell(row[col])}</td>`).join('')}
                            </tr>
                        `).join('')}
                    </tbody>
                    ${data.totals && Object.keys(data.totals).length > 0 ? `
                        <tfoot>
                            <tr>
                                <td>${t('table.totals', 'Totais')}</td>
                                ${data.columns.slice(1).map(col => `<td>${formatFooterValue(col, data.totals[col])}</td>`).join('')}
                            </tr>
                        </tfoot>
                    ` : ''}
                </table>
            </body>
        </html>
    `);

    printWindow.document.close();
    setTimeout(() => {
        printWindow.print();
    }, 250);
}

// ── End Report Functions ───────────────────────────────────────────────────

async function openAttachmentDialog(moduleKey, recordId) {
    const modal = ensureAttachmentModal();
    const entityName = resolveAttachmentEntity(moduleKey);
    const normalizedRecordId = parseInt(recordId, 10);

    modal.dataset.entityName = entityName;
    modal.dataset.recordId = String(normalizedRecordId);
    modal.querySelector('#attachment-modal-title').textContent = `${t('button.attachfile', 'Anexar arquivo')} - ${entityName} #${normalizedRecordId}`;
    modal.classList.remove('hidden');

    const uploadButton = modal.querySelector('#attachment-upload-button');
    uploadButton.onclick = async () => {
        const fileInput = modal.querySelector('#attachment-file-input');
        const notesInput = modal.querySelector('#attachment-notes-input');
        const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;

        if (!file) {
            notify(t('toast.attachment.selectfile', 'Selecione um arquivo para anexar.'), 'error');
            return;
        }

        try {
            const dataUrl = await fileToBase64(file);
            const payload = {
                EntityName: entityName,
                RecordRecId: normalizedRecordId,
                FileName: file.name,
                MimeType: file.type || 'application/octet-stream',
                FileContentBase64: String(dataUrl).includes(',') ? String(dataUrl).split(',')[1] : String(dataUrl),
                Notes: notesInput.value || ''
            };

            await window.apiClient.post('/api/attachments', payload);
            fileInput.value = '';
            notesInput.value = '';
            notify(t('toast.attachment.uploaded', 'Arquivo anexado com sucesso.'), 'success');
            await loadAttachmentList(entityName, normalizedRecordId);
        } catch (error) {
            notify(error.message, 'error');
        }
    };

    await loadAttachmentList(entityName, normalizedRecordId);
}

async function loadAttachmentList(entityName, recordId) {
    const modal = document.getElementById('attachment-modal');
    const host = modal.querySelector('#attachment-list-host');
    host.innerHTML = `<div class="loader">${t('loader.data', 'Loading data...')}</div>`;

    try {
        const rows = await window.apiClient.get(`/api/attachments?entity=${encodeURIComponent(entityName)}&recordId=${encodeURIComponent(recordId)}`);

        if (!rows.length) {
            host.innerHTML = `<div class="empty-state">${t('attachment.empty', 'Nenhum arquivo anexado para este registro.')}</div>`;
            return;
        }

        host.innerHTML = `
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>${t('column.FileName', 'Arquivo')}</th>
                            <th>${t('column.MimeType', 'Tipo')}</th>
                            <th>${t('column.Notes', 'Observacao')}</th>
                            <th>${t('column.CreatedDateTime', 'Criado em')}</th>
                            <th>${t('column.Actions', 'Actions')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map(row => `
                            <tr>
                                <td>${escapeAttribute(row.FileName || '')}</td>
                                <td>${escapeAttribute(row.MimeType || '')}</td>
                                <td>${escapeAttribute(row.Notes || '')}</td>
                                <td>${formatCell(row.CreatedDateTime)}</td>
                                <td>
                                    <button type="button" class="mini-button attachment-download" data-id="${row.RecId}">${t('button.download', 'Download')}</button>
                                    <button type="button" class="mini-button attachment-delete" data-id="${row.RecId}">${t('button.delete', 'Delete')}</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;

        host.querySelectorAll('.attachment-download').forEach(button => {
            button.addEventListener('click', async () => {
                const attachmentId = button.dataset.id;
                try {
                    const file = await window.apiClient.get(`/api/attachments/${attachmentId}`);
                    const base64Content = String(file.FileContentBase64 || '');
                    const href = base64Content.startsWith('data:')
                        ? base64Content
                        : `data:${file.MimeType || 'application/octet-stream'};base64,${base64Content}`;

                    const anchor = document.createElement('a');
                    anchor.href = href;
                    anchor.download = file.FileName || `attachment-${attachmentId}`;
                    document.body.appendChild(anchor);
                    anchor.click();
                    anchor.remove();
                } catch (error) {
                    notify(error.message, 'error');
                }
            });
        });

        host.querySelectorAll('.attachment-delete').forEach(button => {
            button.addEventListener('click', async () => {
                const attachmentId = button.dataset.id;
                try {
                    await window.apiClient.delete(`/api/attachments/${attachmentId}`);
                    notify(t('toast.attachment.deleted', 'Anexo removido com sucesso.'), 'success');
                    await loadAttachmentList(entityName, recordId);
                } catch (error) {
                    notify(error.message, 'error');
                }
            });
        });
    } catch (error) {
        host.innerHTML = `<div class="empty-state">${error.message}</div>`;
    }
}

function notify(message, type = 'success') {
    const host = document.getElementById('toast-host');
    const element = document.createElement('div');
    element.className = `toast ${type === 'error' ? 'error' : ''}`;
    element.textContent = message;
    host.appendChild(element);
    setTimeout(() => element.remove(), 3600);
}

function t(key, fallback) {
    return appState.labels && appState.labels[key] ? appState.labels[key] : fallback;
}

function escapeAttribute(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}
