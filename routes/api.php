<?php

use App\Http\Controllers\API\AdjustmentAPIController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BaseUnitAPIController;
use App\Http\Controllers\API\BrandAPIController;
use App\Http\Controllers\API\CouponCodeAPIController;
use App\Http\Controllers\API\CurrencyAPIController;
use App\Http\Controllers\API\CustomerAPIController;
use App\Http\Controllers\API\DashboardAPIController;
use App\Http\Controllers\API\ExpenseAPIController;
use App\Http\Controllers\API\ExpenseCategoryAPIController;
use App\Http\Controllers\API\FbrProfileAPIController;
use App\Http\Controllers\API\HoldAPIController;
use App\Http\Controllers\API\LanguageAPIController;
use App\Http\Controllers\API\MainProductAPIController;
use App\Http\Controllers\API\ManageStockAPIController;
use App\Http\Controllers\API\OfflineSyncAPIController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\POSRegisterAPIController;
use App\Http\Controllers\API\ProductAPIController;
use App\Http\Controllers\API\ProductCategoryAPIController;
use App\Http\Controllers\API\PurchaseAPIController;
use App\Http\Controllers\API\PurchaseReturnAPIController;
use App\Http\Controllers\API\QuotationAPIController;
use App\Http\Controllers\API\ReportAPIController;
use App\Http\Controllers\API\RoleAPIController;
use App\Http\Controllers\API\SaleAPIController;
use App\Http\Controllers\API\SaleReturnAPIController;
use App\Http\Controllers\API\SalesPaymentAPIController;
use App\Http\Controllers\API\SettingAPIController;
use App\Http\Controllers\API\SmsSettingAPIController;
use App\Http\Controllers\API\SmsTemplateAPIController;
use App\Http\Controllers\API\ShopAPIController;
use App\Http\Controllers\API\StockMovementAPIController;
use App\Http\Controllers\API\StoreAPIController;
use App\Http\Controllers\API\SupplierAPIController;
use App\Http\Controllers\API\TenantRegistrationController;
use App\Http\Controllers\API\TransferAPIController;
use App\Http\Controllers\API\UnitAPIController;
use App\Http\Controllers\API\UserAPIController;
use App\Http\Controllers\API\WarehouseAPIController;
use App\Http\Controllers\API\VariationAPIController;
use App\Http\Controllers\API\TaxesAPIController;
use App\Http\Controllers\MailTemplateAPIController;
use App\Http\Controllers\API\PaymentMethodAPIController; // Changed
use App\Http\Controllers\API\PlatformTenantController;
use App\Http\Controllers\API\PlatformSaasController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::post('products/generate-barcode', [ProductAPIController::class, 'generateStandaloneBarcode']);

Route::middleware(['auth:sanctum', 'tenant.active'])->group(function () {
    Route::prefix('platform')->middleware('role:platform_super_admin')->group(function () {
        Route::get('dashboard', [PlatformSaasController::class, 'dashboard']);
        Route::get('tenants', [PlatformTenantController::class, 'index']);
        Route::post('tenants', [PlatformTenantController::class, 'store']);
        Route::get('tenants/{tenantId}', [PlatformTenantController::class, 'show']);
        Route::patch('tenants/{tenantId}/status', [PlatformTenantController::class, 'changeStatus']);
        Route::post('tenants/{tenantId}/subscription', [PlatformSaasController::class, 'assignTenantPlan']);
        Route::get('tenants/{tenantId}/usage', [PlatformSaasController::class, 'tenantUsage']);
        Route::post('tenants/{tenantId}/backups', [PlatformSaasController::class, 'requestBackup']);
        Route::get('plans', [PlatformSaasController::class, 'plans']);
        Route::post('plans', [PlatformSaasController::class, 'storePlan']);
        Route::patch('plans/{plan}', [PlatformSaasController::class, 'updatePlan']);
        Route::get('subscriptions', [PlatformSaasController::class, 'subscriptions']);
        Route::get('audit-logs', [PlatformSaasController::class, 'auditLogs']);
        Route::get('backups', [PlatformSaasController::class, 'backups']);
        Route::patch('backups/{backup}/status', [PlatformSaasController::class, 'updateBackupStatus']);
        Route::get('backups/{backup}/download', [PlatformSaasController::class, 'downloadBackup']);
    });

    Route::resource('stores', StoreAPIController::class);
    Route::post('change-store/{store}', [StoreAPIController::class, 'changeStore']);
    Route::get('change-status/{store}', [StoreAPIController::class, 'changeStatus']);
    Route::get('change-default-store/{store}', [StoreAPIController::class, 'changeDefaultStore']);
    Route::get('shops', [ShopAPIController::class, 'index']);
    Route::get('shops/{shop}', [ShopAPIController::class, 'show']);
    Route::middleware('permission:manage_shops')->group(function () {
        Route::post('shops', [ShopAPIController::class, 'store']);
        Route::post('shops/{shop}', [ShopAPIController::class, 'update']);
        Route::delete('shops/{shop}', [ShopAPIController::class, 'destroy']);
        Route::post('shops/{shop}/assign-users', [ShopAPIController::class, 'assignUsers']);
        Route::get('shops/{shop}/status-change', [ShopAPIController::class, 'changeStatus']);
    });

    Route::middleware('permission:manage_brands')->group(function () {
        Route::post('/brands', [BrandAPIController::class, 'store']);
        Route::get('/brands/{id}', [BrandAPIController::class, 'show'])->name('brands.show');
        Route::post('/brands/{id}', [BrandAPIController::class, 'update']);
        Route::delete('/brands/{brand}', [BrandAPIController::class, 'destroy']);
    });
    Route::get('/brands', [BrandAPIController::class, 'index']);

    //Dashboard
    Route::middleware('permission:manage_dashboard')->group(function () {
        Route::get('today-sales-purchases-count', [DashboardAPIController::class, 'getPurchaseSalesCounts']);
        Route::get('all-sales-purchases-count', [DashboardAPIController::class, 'getAllPurchaseSalesCounts']);
        Route::get('recent-sales', [DashboardAPIController::class, 'getRecentSales']);
        Route::get('top-selling-products', [DashboardAPIController::class, 'getTopSellingProducts']);
        Route::get('week-selling-purchases', [DashboardAPIController::class, 'getWeekSalePurchases']);
        Route::get('yearly-top-selling', [DashboardAPIController::class, 'getYearlyTopSelling']);
        Route::get('top-customers', [DashboardAPIController::class, 'getTopCustomer']);
        Route::get('stock-alerts', [DashboardAPIController::class, 'stockAlerts']);
    });
    // get all permission
    Route::get('/permissions', [PermissionController::class, 'getPermissions'])->name('get-permissions');

    // roles route
    Route::middleware('permission:manage_roles')->group(function () {
        Route::resource('roles', RoleAPIController::class);
    });
    Route::get('roles', [RoleAPIController::class, 'index']);

    // product category route
    Route::middleware('permission:manage_product_categories')->group(function () {
        Route::resource('product-categories', ProductCategoryAPIController::class);
        Route::post(
            'product-categories/{product_category}',
            [ProductCategoryAPIController::class, 'update']
        )->name('product-category');
    });
    Route::get('product-categories', [ProductCategoryAPIController::class, 'index']);


    Route::middleware('permission:manage_currency')->group(function () {
        Route::resource('currencies', CurrencyAPIController::class);
    });
    Route::get('currencies', [CurrencyAPIController::class, 'index']);

    // warehouses route
    Route::middleware('permission:manage_warehouses')->group(function () {
        Route::resource('warehouses', WarehouseAPIController::class);
        Route::get('warehouse-details/{id}', [WarehouseAPIController::class, 'warehouseDetails']);
    });
    Route::get('warehouses', [WarehouseAPIController::class, 'index']);

    // units route
    Route::middleware('permission:manage_units')->group(function () {
        Route::resource('units', UnitAPIController::class);
    });
    Route::resource('base-units', BaseUnitAPIController::class);
    Route::get('units', [UnitAPIController::class, 'index']);

    // products route

    Route::resource('products', ProductAPIController::class);
    Route::resource('main-products', MainProductAPIController::class);
    Route::post(
        'products/{product}',
        [ProductAPIController::class, 'update']
    );

    Route::post(
        'main-products/{product}',
        [MainProductAPIController::class, 'update']
    );
    Route::delete(
        'products-image-delete/{mediaId}',
        [ProductAPIController::class, 'productImageDelete']
    )->name('products-image-delete');

    Route::get('products', [ProductAPIController::class, 'index']);
    Route::get('get-all-products', [ProductAPIController::class, 'getAllProducts']);

    Route::resource('variations', VariationAPIController::class);

    Route::middleware('permission:manage_transfers')->group(function () {
        Route::resource('transfers', TransferAPIController::class);
        Route::post('transfers/{id}/post', [TransferAPIController::class, 'post'])->name('transfers.post');
    });

    Route::post('import-products', [ProductAPIController::class, 'importProducts']);
    Route::post('import-customers', [CustomerAPIController::class, 'importCustomers']);

    Route::get(
        'products-export-excel/{id?}',
        [ProductAPIController::class, 'getProductExportExcel']
    )->name('products-export-excel');

    Route::resource('transfers', TransferAPIController::class);

    // customers route
    Route::middleware('permission:manage_customers')->group(function () {
        Route::resource('customers', CustomerAPIController::class);
    });
    Route::get('customers', [CustomerAPIController::class, 'index']);


    //Users route
    Route::middleware('permission:manage_users|manage_expenses|manage_reports')->group(function () {
        Route::resource('users', UserAPIController::class);
        Route::post('users/{user}', [UserAPIController::class, 'update']);
        Route::post('/change-user-password', [UserAPIController::class, 'changeUserPassword']);
    });
    // update user profile
    Route::get('edit-profile', [UserAPIController::class, 'editProfile'])->name('edit-profile');
    Route::post('update-profile', [UserAPIController::class, 'updateProfile'])->name('update-profile');
    Route::patch('/change-password', [UserAPIController::class, 'changePassword'])->name('user.changePassword');

    //suppliers route
    Route::middleware('permission:manage_suppliers')->group(function () {
        Route::resource('suppliers', SupplierAPIController::class);
    });
    Route::get('suppliers', [SupplierAPIController::class, 'index']);
    Route::post('import-suppliers', [SupplierAPIController::class, 'importSuppliers']);

    //sale
    Route::middleware('permission:manage_sale|manage_pos_screen|manage_reports')->group(function () {
        Route::resource('sales', SaleAPIController::class);
        Route::post('sales/{id}/post', [SaleAPIController::class, 'post'])->name('sales.post');
        Route::get('sale-pdf-download/{sale}', [SaleAPIController::class, 'pdfDownload'])->name('sale-pdf-download');
        Route::get('sale-info/{sale}', [SaleAPIController::class, 'saleInfo'])->name('sale-info');

        Route::post('sales/{sale}/capture-payment', [SalesPaymentAPIController::class, 'createSalePayment']);
        Route::get('sales/{sale}/payments', [SalesPaymentAPIController::class, 'getAllPayments']);
        Route::post('sales/{salesPayment}/payment', [SalesPaymentAPIController::class, 'updateSalePayment']);
        Route::delete('sales/{id}/payment', [SalesPaymentAPIController::class, 'deletePayment']);
        
        Route::get('dual-screen-settings', [SettingAPIController::class, 'getDualScreenSettings']);
        Route::post('dual-screen-settings/update', [SettingAPIController::class, 'updateDualScreenSettings']);
    });

    Route::resource('holds', HoldAPIController::class);

    // Quotation
    Route::resource('quotations', QuotationAPIController::class);
    Route::get('quotation-info/{quotation}', [QuotationAPIController::class, 'quotationInfo']);
    Route::get('quotation-pdf-download/{quotation}', [QuotationAPIController::class, 'pdfDownload']);

    Route::resource('mail-templates', MailTemplateAPIController::class);
    Route::post('mail-template-status/{id}', [MailTemplateAPIController::class, 'changeActiveStatus']);

    Route::resource('sms-templates', SmsTemplateAPIController::class);
    Route::post('sms-template-status/{id}', [SmsTemplateAPIController::class, 'changeActiveStatus']);

    //sale return
    Route::middleware('permission:manage_sale_return|manage_reports')->group(function () {
        Route::resource('sales-return', SaleReturnAPIController::class);
        Route::get('sales-return-edit/{id}', [SaleReturnAPIController::class, 'editBySale']);
        Route::post('sales-return/{id}/post', [SaleReturnAPIController::class, 'post'])->name('sales-return.post');
        Route::get(
            'sale-return-info/{sales_return}',
            [SaleReturnAPIController::class, 'saleReturnInfo']
        )->name('sale-return-info');
        Route::get(
            'sale-return-pdf-download/{sale_return}',
            [SaleReturnAPIController::class, 'pdfDownload']
        )->name('sale-return-pdf-download');
    });

    //expense category route
    Route::middleware('permission:manage_expense_categories')->group(function () {
        Route::resource('expense-categories', ExpenseCategoryAPIController::class);
    });
    Route::get('expense-categories', [ExpenseCategoryAPIController::class, 'index']);

    //expense route
    Route::middleware('permission:manage_expenses|manage_reports')->group(function () {
        Route::resource('expenses', ExpenseAPIController::class);
        Route::post('expenses/{id}/post', [ExpenseAPIController::class, 'post'])->name('expenses.post');
    });

    //setting route
    Route::middleware('permission:manage_setting')->group(function () {
        Route::get('pos-settings', [SettingAPIController::class, 'getPosSettings']);
        Route::post('pos-settings/update', [SettingAPIController::class, 'updatePosSettings']);
        Route::resource('settings', SettingAPIController::class);
        Route::post('settings', [SettingAPIController::class, 'update']);
        Route::get('states/{id}', [SettingAPIController::class, 'getStates']);
        Route::get('mail-settings', [SettingAPIController::class, 'getMailSettings']);
        Route::post('mail-settings/update', [SettingAPIController::class, 'updateMailSettings']);
        Route::post('send-test-email', [SettingAPIController::class, 'sendTestEmail']);
        Route::post('receipt-settings/update', [SettingAPIController::class, 'updateReceiptSetting']);
    });

    //Payment Methods
    // Route::middleware('permission:manage_setting')->group(function () { // Assuming manage_setting permission or similar will be used
    Route::resource('payment-methods', PaymentMethodAPIController::class);
    Route::post('payment-methods/status-change/{payment_method}', [PaymentMethodAPIController::class, 'changeStatus']);
    // });

    // Route::middleware('permission:manage_language')->group(function () {
    Route::resource('languages', LanguageAPIController::class);
    Route::get('languages/translation/{language}', [LanguageAPIController::class, 'showTranslation']);
    Route::post('languages/translation/{language}/update', [LanguageAPIController::class, 'updateTranslation']);
    Route::post('languages/{language}/toggle-status', [LanguageAPIController::class, 'toggleStatus']);
    // });

    Route::resource('sms-settings', SmsSettingAPIController::class);
    Route::post('sms-settings', [SmsSettingAPIController::class, 'update']);

    Route::middleware('permission:manage_fbr')->group(function () {
        Route::get('fbr-profiles', [FbrProfileAPIController::class, 'index']);
        Route::post('fbr-profiles', [FbrProfileAPIController::class, 'store']);
        Route::get('fbr-profiles/{fbrProfile}', [FbrProfileAPIController::class, 'show']);
        Route::post('fbr-profiles/{fbrProfile}', [FbrProfileAPIController::class, 'update']);
        Route::delete('fbr-profiles/{fbrProfile}', [FbrProfileAPIController::class, 'destroy']);
    });

    Route::middleware('permission:manage_offline_devices')->group(function () {
        Route::get('offline-devices', [OfflineSyncAPIController::class, 'devices']);
        Route::get('sync-queue', [OfflineSyncAPIController::class, 'queue']);
        Route::patch('sync-queue/{syncQueue}/status', [OfflineSyncAPIController::class, 'updateQueueStatus']);
    });
    Route::post('offline-devices/register', [OfflineSyncAPIController::class, 'registerDevice']);
    Route::post('offline-devices/{offlineDevice}/heartbeat', [OfflineSyncAPIController::class, 'heartbeat']);
    Route::post('offline-sync/batches', [OfflineSyncAPIController::class, 'pushBatch']);

    Route::get('settings', [SettingAPIController::class, 'index']);

    Route::resource('taxes', TaxesAPIController::class);
    Route::get('taxes/status-change/{tax}', [TaxesAPIController::class, 'changeStatus']);

    //clear cache route
    Route::get('cache-clear', [SettingAPIController::class, 'clearCache'])->name('cache-clear');

    //purchase routes
    Route::resource('purchases', PurchaseAPIController::class);
    Route::post('purchases/{id}/post', [PurchaseAPIController::class, 'post'])->name('purchases.post');
    Route::get(
        'purchase-pdf-download/{purchase}',
        [PurchaseAPIController::class, 'pdfDownload']
    )->name('purchase-pdf-download');
    Route::get('purchase-info/{purchase}', [PurchaseAPIController::class, 'purchaseInfo'])->name('purchase-info');
    Route::post('logout', [AuthController::class, 'logout']);

    Route::middleware('permission:manage_adjustments')->group(function () {
        Route::resource('adjustments', AdjustmentAPIController::class);
        Route::post('adjustments/{id}/post', [AdjustmentAPIController::class, 'post'])->name('adjustments.post');
    });

    //purchase return routes
    Route::resource('purchases-return', PurchaseReturnAPIController::class);
    Route::post('purchases-return/{id}/post', [PurchaseReturnAPIController::class, 'post']);
    Route::get('purchase-return-edit/{id}', [PurchaseReturnAPIController::class, 'editByPurchase']);
    Route::get(
        'purchase-return-info/{purchase_return}',
        [PurchaseReturnAPIController::class, 'purchaseReturnInfo']
    )->name('purchase-return-info');
    Route::get(
        'purchase-return-pdf-download/{purchase_return}',
        [PurchaseReturnAPIController::class, 'pdfDownload']
    )->name('purchase-return-pdf-download');

    //Language Change
    Route::post('change-language', [UserAPIController::class, 'updateLanguage']);

    // warehouse report
    Route::get('warehouse-report', [WarehouseAPIController::class, 'warehouseReport'])->name('report-warehouse');
    Route::get(
        'sales-report-excel',
        [ReportAPIController::class, 'getWarehouseSaleReportExcel']
    )->name('report-getSaleReportExcel');
    Route::get(
        'purchases-report-excel',
        [ReportAPIController::class, 'getWarehousePurchaseReportExcel']
    );
    Route::get(
        'sales-return-report-excel',
        [ReportAPIController::class, 'getWarehouseSaleReturnReportExcel']
    )->name('report-getSaleReturnReportExcel');
    Route::get(
        'purchases-return-report-excel',
        [
            ReportAPIController::class,
            'getWarehousePurchaseReturnReportExcel',
        ]
    )->name('report-getPurchaseReturnReportExcel');
    Route::get(
        'expense-report-excel',
        [ReportAPIController::class, 'getWarehouseExpenseReportExcel']
    )->name('report-getExpenseReportExcel');

    //sale report
    Route::get(
        'total-sale-report-excel',
        [ReportAPIController::class, 'getSalesReportExcel']
    )->name('report-getSalesReportExcel');

    // purchase report
    Route::get(
        'total-purchase-report-excel',
        [ReportAPIController::class, 'getPurchaseReportExcel']
    );
    // top-selling product report
    Route::get(
        'top-selling-product-report-excel',
        [ReportAPIController::class, 'getSellingProductReportExcel']
    );
    Route::get(
        'top-selling-product-report',
        [ReportAPIController::class, 'getSellingProductReport']
    );

    Route::get('supplier-report', [ReportAPIController::class, 'getSupplierReport']);
    Route::get('supplier-pdf-download/{supplier}', [SupplierAPIController::class, 'pdfDownload']);

    Route::get('supplier-purchases-report/{supplier_id}', [ReportAPIController::class, 'getSupplierPurchasesReport']);
    Route::get(
        'supplier-purchases-return-report/{supplier_id}',
        [ReportAPIController::class, 'getSupplierPurchasesReturnReport']
    );
    Route::get('supplier-report-info/{supplier_id}', [ReportAPIController::class, 'getSupplierInfo']);

    // profit loss report
    Route::get('profit-loss-report', [ReportAPIController::class, 'getProfitLossReport']);

    // best customers report

    Route::get('best-customers-report', [ReportAPIController::class, 'getBestCustomersReport']);
    Route::get('best-customers-pdf-download', [CustomerAPIController::class, 'bestCustomersPdfDownload']);

    //customer all report
    Route::get('customer-report', [ReportAPIController::class, 'getCustomerReport']);
    Route::get('customer-payments-report/{customer}', [ReportAPIController::class, 'getCustomerPaymentsReport']);
    Route::get('customer-info/{customer}', [ReportAPIController::class, 'getCustomerInfo']);
    Route::get('customer-pdf-download/{customer}', [CustomerAPIController::class, 'pdfDownload']);
    Route::get('customer-sales-pdf-download/{customer}', [CustomerAPIController::class, 'customerSalesPdfDownload']);
    Route::get(
        'customer-quotations-pdf-download/{customer}',
        [CustomerAPIController::class, 'customerQuotationsPdfDownload']
    );
    Route::get(
        'customer-returns-pdf-download/{customer}',
        [CustomerAPIController::class, 'customerReturnsPdfDownload']
    );
    Route::get(
        'customer-payments-pdf-download/{customer}',
        [CustomerAPIController::class, 'customerPaymentsPdfDownload']
    );

    //Warehouse Products alert Quantity Report
    Route::get('product-stock-alerts/{warehouse_id?}', [ReportAPIController::class, 'stockAlerts']);

    //stock report
    Route::get('stock-report', [ManageStockAPIController::class, 'stockReport'])->name('report-stockReport');
    Route::get('stock-movements', [StockMovementAPIController::class, 'index'])->middleware('permission:manage_reports');
    Route::get('stock-movements-summary', [StockMovementAPIController::class, 'summary'])->middleware('permission:manage_reports');
    Route::get('stock-report-excel', [ReportAPIController::class, 'stockReportExcel'])->name('report-stockReportExcel');
    Route::get(
        'get-sale-product-report',
        [SaleAPIController::class, 'getSaleProductReport']
    )->name('report-get-sale-product-report');
    Route::get(
        'get-purchase-product-report',
        [PurchaseAPIController::class, 'getPurchaseProductReport']
    )->name('report-get-purchase-product-report');
    Route::get(
        'get-sale-return-product-report',
        [SaleReturnAPIController::class, 'getSaleReturnProductReport']
    );
    Route::get('get-purchase-return-product-report', [
        PurchaseReturnAPIController::class,
        'getPurchaseReturnProductReport',
    ]);

    // Today sale overall report

    Route::get('today-sales-overall-report', [ReportAPIController::class, 'getTodaySalesOverallReport']);

    // stock report excel
    Route::get('get-product-sale-report-excel', [ReportAPIController::class, 'getProductSaleReportExport']);
    Route::get('get-product-purchase-report-excel', [ReportAPIController::class, 'getPurchaseProductReportExport']);
    Route::get(
        'get-product-sale-return-report-excel',
        [ReportAPIController::class, 'getSaleReturnProductReportExport']
    );
    Route::get(
        'get-product-purchase-return-report-excel',
        [ReportAPIController::class, 'getPurchaseReturnProductReportExport']
    );
    Route::get('get-product-count', [ReportAPIController::class, 'getProductQuantity']);

    Route::get('config', [UserAPIController::class, 'config']);

    // POS Register routes
    Route::get('get-register-details/{pos?}', [POSRegisterAPIController::class, 'getRegisterDetails']);
    Route::post('register-entry', [POSRegisterAPIController::class, 'entry']);
    Route::post('register-close', [POSRegisterAPIController::class, 'closeRegister']);
    Route::get('register-report', [POSRegisterAPIController::class, 'registerReport']);

    // Coupon Code Routes
    Route::resource('coupon-codes', CouponCodeAPIController::class);
    Route::get('front-setting', [SettingAPIController::class, 'getFrontSettingsValue'])->name('front-settings');
});

Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('register', [AuthController::class, 'register']);
Route::post('register-tenant', [TenantRegistrationController::class, 'register'])->name('tenant.register');

Route::post(
    '/forgot-password',
    [AuthController::class, 'sendPasswordResetLinkEmail']
)->middleware('throttle:5,1')->name('password.email');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');

Route::get('front-cms', [SettingAPIController::class, 'getFrontCms']);

Route::post('validate-auth-token', [AuthController::class, 'isValidToken']);

require __DIR__ . '/m1.php';
