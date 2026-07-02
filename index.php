<?php

require_once __DIR__ . '/app/bootstrap.php';

$router = new Router();

$authController = new AuthController();
$oauthController = new OAuthController();
$dashboardController = new DashboardController();
$roleController = new SecurityRoleController();
$userController = new SysUserController();
$partyController = new PartyController();
$customerController = new CustomerController();
$vendorController = new VendorController();
$productController = new ProductController();
$serviceCodeController = new ServiceCodeController();
$invoiceController = new ServiceInvoiceController();
$purchaseController = new PurchaseOrderController();
$journalController = new JournalController();
$reportController = new ReportController();
$localizationController = new LocalizationController();
$navigationController = new NavigationController();
$companyController = new CompanyController();
$numberSequenceController = new NumberSequenceController();
$taxTypeController = new TaxTypeController();
$bankAccountController = new BankAccountController();
$attachmentController = new DocuAttachmentController();
$ledgerCategoryController = new LedgerCategoryController();
$corsOriginController = new CorsOriginController();

$router->add('POST', '/api/oauth/token', [$oauthController, 'token']);
$router->add('POST', '/api/auth/login', [$authController, 'login']);
$router->add('POST', '/api/auth/logout', [$authController, 'logout']);
$router->add('GET', '/api/auth/me', [$authController, 'me']);

$router->add('GET', '/api/localization/languages', [$localizationController, 'languages']);
$router->add('GET', '/api/localization/labels', [$localizationController, 'labels']);
$router->add('GET', '/api/localization/label-texts', [$localizationController, 'indexLabelTexts']);
$router->add('POST', '/api/localization/label-texts', [$localizationController, 'storeLabelText']);
$router->add('PUT', '/api/localization/label-texts/{id}', [$localizationController, 'updateLabelText']);
$router->add('DELETE', '/api/localization/label-texts/{id}', [$localizationController, 'destroyLabelText']);
$router->add('GET', '/api/navigation/menu', [$navigationController, 'menu']);

$router->add('GET', '/api/dashboard/summary', [$dashboardController, 'summary']);

$router->add('GET', '/api/roles', [$roleController, 'index']);
$router->add('POST', '/api/roles', [$roleController, 'store']);
$router->add('PUT', '/api/roles/{id}', [$roleController, 'update']);
$router->add('DELETE', '/api/roles/{id}', [$roleController, 'destroy']);

$router->add('GET', '/api/users', [$userController, 'index']);
$router->add('POST', '/api/users', [$userController, 'store']);
$router->add('PUT', '/api/users/{id}', [$userController, 'update']);
$router->add('DELETE', '/api/users/{id}', [$userController, 'destroy']);

$router->add('GET', '/api/number-sequences', [$numberSequenceController, 'index']);
$router->add('POST', '/api/number-sequences', [$numberSequenceController, 'store']);
$router->add('PUT', '/api/number-sequences/{id}', [$numberSequenceController, 'update']);
$router->add('DELETE', '/api/number-sequences/{id}', [$numberSequenceController, 'destroy']);

$router->add('GET', '/api/companies', [$companyController, 'index']);
$router->add('POST', '/api/companies', [$companyController, 'store']);
$router->add('PUT', '/api/companies/{id}', [$companyController, 'update']);
$router->add('DELETE', '/api/companies/{id}', [$companyController, 'destroy']);

$router->add('GET', '/api/parties', [$partyController, 'index']);
$router->add('GET', '/api/parties/{id}', [$partyController, 'show']);
$router->add('POST', '/api/parties', [$partyController, 'store']);
$router->add('PUT', '/api/parties/{id}', [$partyController, 'update']);
$router->add('DELETE', '/api/parties/{id}', [$partyController, 'destroy']);

$router->add('GET', '/api/customers', [$customerController, 'index']);
$router->add('GET', '/api/customers/{id}', [$customerController, 'show']);
$router->add('POST', '/api/customers', [$customerController, 'store']);
$router->add('PUT', '/api/customers/{id}', [$customerController, 'update']);
$router->add('DELETE', '/api/customers/{id}', [$customerController, 'destroy']);

$router->add('GET', '/api/vendors', [$vendorController, 'index']);
$router->add('GET', '/api/vendors/{id}', [$vendorController, 'show']);
$router->add('POST', '/api/vendors', [$vendorController, 'store']);
$router->add('PUT', '/api/vendors/{id}', [$vendorController, 'update']);
$router->add('DELETE', '/api/vendors/{id}', [$vendorController, 'destroy']);

$router->add('GET', '/api/products', [$productController, 'index']);
$router->add('POST', '/api/products', [$productController, 'store']);
$router->add('PUT', '/api/products/{id}', [$productController, 'update']);
$router->add('DELETE', '/api/products/{id}', [$productController, 'destroy']);

$router->add('GET', '/api/service-codes', [$serviceCodeController, 'index']);
$router->add('POST', '/api/service-codes', [$serviceCodeController, 'store']);
$router->add('PUT', '/api/service-codes/{id}', [$serviceCodeController, 'update']);
$router->add('DELETE', '/api/service-codes/{id}', [$serviceCodeController, 'destroy']);

$router->add('GET', '/api/service-invoices/open-for-journal', [$invoiceController, 'openForJournal']);
$router->add('GET', '/api/service-invoices', [$invoiceController, 'index']);
$router->add('GET', '/api/service-invoices/{id}', [$invoiceController, 'show']);
$router->add('POST', '/api/service-invoices', [$invoiceController, 'store']);
$router->add('PUT', '/api/service-invoices/{id}', [$invoiceController, 'update']);
$router->add('DELETE', '/api/service-invoices/{id}', [$invoiceController, 'destroy']);

$router->add('GET', '/api/purchase-orders/materials', [$purchaseController, 'indexMaterials']);
$router->add('GET', '/api/purchase-orders/materials/{id}', [$purchaseController, 'showMaterial']);
$router->add('POST', '/api/purchase-orders/materials', [$purchaseController, 'storeMaterial']);
$router->add('PUT', '/api/purchase-orders/materials/{id}', [$purchaseController, 'updateMaterial']);
$router->add('DELETE', '/api/purchase-orders/materials/{id}', [$purchaseController, 'destroyMaterial']);
$router->add('GET', '/api/purchase-orders/services', [$purchaseController, 'indexServices']);
$router->add('GET', '/api/purchase-orders/services/{id}', [$purchaseController, 'showService']);
$router->add('POST', '/api/purchase-orders/services', [$purchaseController, 'storeService']);
$router->add('PUT', '/api/purchase-orders/services/{id}', [$purchaseController, 'updateService']);
$router->add('DELETE', '/api/purchase-orders/services/{id}', [$purchaseController, 'destroyService']);
$router->add('GET', '/api/purchase-orders', [$purchaseController, 'index']);
$router->add('GET', '/api/purchase-orders/{id}', [$purchaseController, 'show']);
$router->add('POST', '/api/purchase-orders', [$purchaseController, 'store']);
$router->add('PUT', '/api/purchase-orders/{id}', [$purchaseController, 'update']);
$router->add('DELETE', '/api/purchase-orders/{id}', [$purchaseController, 'destroy']);

$router->add('GET', '/api/journals/tax', [$journalController, 'indexTax']);
$router->add('GET', '/api/journals/tax/{id}', [$journalController, 'showTax']);
$router->add('POST', '/api/journals/tax', [$journalController, 'storeTax']);
$router->add('PUT', '/api/journals/tax/{id}', [$journalController, 'updateTax']);
$router->add('DELETE', '/api/journals/tax/{id}', [$journalController, 'destroyTax']);

$router->add('GET', '/api/journals/payment', [$journalController, 'indexPayment']);
$router->add('GET', '/api/journals/payment/{id}', [$journalController, 'showPayment']);
$router->add('POST', '/api/journals/payment', [$journalController, 'storePayment']);
$router->add('PUT', '/api/journals/payment/{id}', [$journalController, 'updatePayment']);
$router->add('DELETE', '/api/journals/payment/{id}', [$journalController, 'destroyPayment']);

$router->add('GET', '/api/journals/receipt', [$journalController, 'indexReceipt']);
$router->add('GET', '/api/journals/receipt/{id}', [$journalController, 'showReceipt']);
$router->add('POST', '/api/journals/receipt', [$journalController, 'storeReceipt']);
$router->add('PUT', '/api/journals/receipt/{id}', [$journalController, 'updateReceipt']);
$router->add('DELETE', '/api/journals/receipt/{id}', [$journalController, 'destroyReceipt']);

$router->add('GET', '/api/journals', [$journalController, 'index']);
$router->add('GET', '/api/journals/{id}', [$journalController, 'show']);
$router->add('POST', '/api/journals', [$journalController, 'store']);
$router->add('PUT', '/api/journals/{id}', [$journalController, 'update']);
$router->add('DELETE', '/api/journals/{id}', [$journalController, 'destroy']);

$router->add('GET', '/api/tax-types', [$taxTypeController, 'index']);
$router->add('POST', '/api/tax-types', [$taxTypeController, 'store']);
$router->add('PUT', '/api/tax-types/{id}', [$taxTypeController, 'update']);
$router->add('DELETE', '/api/tax-types/{id}', [$taxTypeController, 'destroy']);

$router->add('GET', '/api/bank-accounts', [$bankAccountController, 'index']);
$router->add('POST', '/api/bank-accounts', [$bankAccountController, 'store']);
$router->add('PUT', '/api/bank-accounts/{id}', [$bankAccountController, 'update']);
$router->add('DELETE', '/api/bank-accounts/{id}', [$bankAccountController, 'destroy']);

$router->add('GET', '/api/attachments', [$attachmentController, 'index']);
$router->add('GET', '/api/attachments/{id}', [$attachmentController, 'show']);
$router->add('POST', '/api/attachments', [$attachmentController, 'store']);
$router->add('DELETE', '/api/attachments/{id}', [$attachmentController, 'destroy']);

$router->add('GET', '/api/ledger-categories', [$ledgerCategoryController, 'index']);
$router->add('GET', '/api/ledger-categories/lookup', [$ledgerCategoryController, 'lookup']);
$router->add('GET', '/api/ledger-categories/{id}', [$ledgerCategoryController, 'show']);
$router->add('POST', '/api/ledger-categories', [$ledgerCategoryController, 'store']);
$router->add('PUT', '/api/ledger-categories/{id}', [$ledgerCategoryController, 'update']);
$router->add('DELETE', '/api/ledger-categories/{id}', [$ledgerCategoryController, 'destroy']);

$router->add('GET', '/api/reports/accounts-receivable', [$reportController, 'accountsReceivable']);
$router->add('GET', '/api/reports/accounts-payable', [$reportController, 'accountsPayable']);
$router->add('GET', '/api/reports/billing-by-period', [$reportController, 'billingByPeriod']);
$router->add('GET', '/api/reports/billing-by-customer', [$reportController, 'billingByCustomer']);
$router->add('GET', '/api/reports/expenses-by-period', [$reportController, 'expensesByPeriod']);
$router->add('GET', '/api/reports/financial-summary', [$reportController, 'financialSummary']);
$router->add('GET', '/api/reports/profit-and-loss', [$reportController, 'profitAndLoss']);
$router->add('GET', '/api/reports/taxes-by-period', [$reportController, 'taxesByPeriod']);
$router->add('GET', '/api/reports/cash-flow', [$reportController, 'cashFlow']);

$router->add('GET',    '/api/cors-origins',      [$corsOriginController, 'index']);
$router->add('POST',   '/api/cors-origins',      [$corsOriginController, 'store']);
$router->add('PUT',    '/api/cors-origins/{id}', [$corsOriginController, 'update']);
$router->add('DELETE', '/api/cors-origins/{id}', [$corsOriginController, 'destroy']);

$requestPath = isset($_GET['route']) ? $_GET['route'] : parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptDirectory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

if ($scriptDirectory !== '' && $scriptDirectory !== '/' && strpos($requestPath, $scriptDirectory) === 0) {
    $requestPath = substr($requestPath, strlen($scriptDirectory));
}

if ($requestPath === '' || $requestPath === false) {
    $requestPath = '/';
}

if (strpos($requestPath, '/api/') === 0 || $requestPath === '/api') {
    // CORS enforcement: validate Origin header against the SysCorsOrigin whitelist.
    $requestOrigin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : null;

    if ($requestOrigin !== null) {
        $originAllowed = false;

        try {
            $corsDb = new Database();
            $allowedRows = $corsDb->fetchAll('SELECT Origin FROM SysCorsOrigin WHERE IsActive = "1"');

            foreach ($allowedRows as $row) {
                $stored = trim($row['Origin']);

                // Normalise stored value to host only (strip scheme/port if present).
                if (strpos($stored, '://') !== false) {
                    $parsed = parse_url($stored);
                    $stored = isset($parsed['host']) ? $parsed['host'] : $stored;
                }
                $stored = rtrim($stored, '/');

                // Normalise incoming Origin to host only.
                $parsedIncoming = parse_url($requestOrigin);
                $incomingHost   = isset($parsedIncoming['host']) ? $parsedIncoming['host'] : $requestOrigin;
                $incomingHost   = rtrim($incomingHost, '/');

                if (strcasecmp($stored, $incomingHost) === 0) {
                    $originAllowed = true;
                    break;
                }
            }
        } catch (Exception $corsEx) {
            // If the CORS table is unavailable (e.g. first-run before migration) deny by default.
            $originAllowed = false;
        }

        if (!$originAllowed) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Origin not permitted by CORS policy.']);
            exit;
        }

        header('Access-Control-Allow-Origin: ' . $requestOrigin);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
        header('Vary: Origin');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    $router->dispatch($_SERVER['REQUEST_METHOD'], $requestPath);
    exit;
}

require_once __DIR__ . '/app/views/layout.php';