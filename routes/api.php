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
use App\Http\Controllers\API\PlatformRoleTemplateController;
use App\Http\Controllers\API\TenantBillingController;
use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\AttendanceTaskController;
use App\Http\Controllers\API\AttendanceSettingController;
use App\Http\Controllers\API\AttendanceDashboardController;
use App\Http\Controllers\API\EmployeeBiometricController;
use App\Http\Controllers\API\DeviceConnectorController;
use App\Http\Controllers\API\AttendanceRequestController;
use App\Http\Controllers\API\AttendanceReportController;
use App\Http\Controllers\API\WebAuthnController;
use App\Http\Controllers\API\FbrBusinessController;
use App\Http\Controllers\API\FbrDiInvoiceController;
use App\Http\Controllers\API\FbrDiSyncController;
use App\Http\Controllers\API\FbrErrorController;
use App\Http\Controllers\API\FbrSandboxController;
use App\Http\Controllers\API\FbrDiDashboardController;
use App\Http\Controllers\API\FbrDiLimitController;
use App\Http\Controllers\API\FbrDiProductController;
use App\Http\Controllers\API\FbrAgentController;
use App\Http\Controllers\API\FbrReportController;
use App\Http\Controllers\API\ProductBatchController;
use App\Http\Controllers\API\WaterSupplyController;
use App\Http\Controllers\API\BakeryProductionController;
use App\Http\Controllers\API\ElectronicsController;
use App\Http\Controllers\API\DistributionController;
use App\Http\Controllers\API\AppApiController;
use App\Http\Controllers\API\PlatformPaymentController;
use App\Http\Controllers\API\PlatformBankAccountController;
use App\Http\Controllers\API\PlatformSettingController;
use App\Http\Controllers\API\PlatformShopTypeController;
use App\Http\Controllers\API\PlatformFeatureController;
use App\Http\Controllers\API\StoreFeatureController;
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
use App\Http\Controllers\API\RestaurantAPIController;
use App\Http\Controllers\API\RecurringAPIController;
use App\Http\Controllers\API\AccountingAPIController;
use App\Http\Controllers\API\NotificationAPIController;
use App\Http\Controllers\API\InsightsAPIController;
use App\Http\Controllers\API\HRAPIController;
use App\Http\Controllers\API\CRMAPIController;
use App\Http\Controllers\API\PublicController;
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

// Tenant billing — auth required but NOT tenant.active, so a locked/expired
// tenant can still reach billing to renew.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('billing/overview', [TenantBillingController::class, 'overview']);
    Route::post('billing/request', [TenantBillingController::class, 'requestPayment']);
    Route::post('billing/checkout', [TenantBillingController::class, 'checkout']);
    Route::post('billing/addon-request', [TenantBillingController::class, 'requestAddon']);
    Route::post('billing/fbr-request', [TenantBillingController::class, 'requestFbr']);
});

// ── App API (v1) — mobile Flutter + Electron desktop ──────────────────────────
// Auth tokens come from POST /api/login (Sanctum). Offline sales upload uses the
// existing /api/offline-sync/batches endpoint.
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('me', [AppApiController::class, 'me']);
    Route::get('sync/bootstrap', [AppApiController::class, 'bootstrap']);
    Route::post('devices', [AppApiController::class, 'registerDevice']);
    Route::delete('devices', [AppApiController::class, 'unregisterDevice']);
});

Route::middleware(['auth:sanctum', 'tenant.active'])->group(function () {
    Route::prefix('platform')->middleware('role:platform_super_admin')->group(function () {
        // Subscription payments (super admin sees all tenant payments + confirms)
        Route::get('payments', [PlatformPaymentController::class, 'index']);
        Route::post('payments/{payment}/confirm', [PlatformPaymentController::class, 'confirm']);
        Route::post('payments/{payment}/reject', [PlatformPaymentController::class, 'reject']);
        // Payout bank accounts (per country)
        Route::get('bank-accounts', [PlatformBankAccountController::class, 'index']);
        Route::post('bank-accounts', [PlatformBankAccountController::class, 'store']);
        Route::patch('bank-accounts/{bankAccount}', [PlatformBankAccountController::class, 'update']);
        Route::delete('bank-accounts/{bankAccount}', [PlatformBankAccountController::class, 'destroy']);
        // Global billing settings (discount + add-on rates)
        Route::get('settings', [PlatformSettingController::class, 'show']);
        Route::post('settings', [PlatformSettingController::class, 'update']);
        // Subscription payment methods (enable/disable request + checkout) and
        // the per-country online checkout channels.
        Route::get('billing-methods', [PlatformSettingController::class, 'billingMethods']);
        Route::post('billing-methods', [PlatformSettingController::class, 'updateBillingMethods']);
        // FBR Digital Invoice plan limits + agent (company) assignments
        Route::get('fbr-di/limits', [FbrDiLimitController::class, 'index']);
        Route::post('fbr-di/limits', [FbrDiLimitController::class, 'upsert']);
        Route::get('fbr-di/agents', [FbrAgentController::class, 'index']);
        Route::post('fbr-di/agents/attach', [FbrAgentController::class, 'attach']);
        Route::post('fbr-di/agents/detach', [FbrAgentController::class, 'detach']);
        // Shop-type registry (enable/disable business types)
        Route::get('shop-types', [PlatformShopTypeController::class, 'index']);
        Route::post('shop-types', [PlatformShopTypeController::class, 'store']);
        Route::patch('shop-types/{shopType}', [PlatformShopTypeController::class, 'update']);
        // Global feature toggles (apply to all tenants)
        Route::get('features', [PlatformFeatureController::class, 'index']);
        Route::post('features', [PlatformFeatureController::class, 'update']);
        // Marketing CMS
        Route::get('cms/pages', [\App\Http\Controllers\API\PlatformCmsController::class, 'index']);
        Route::get('cms/pages/{cmsPage}', [\App\Http\Controllers\API\PlatformCmsController::class, 'show']);
        Route::post('cms/pages', [\App\Http\Controllers\API\PlatformCmsController::class, 'store']);
        Route::post('cms/pages/{cmsPage}', [\App\Http\Controllers\API\PlatformCmsController::class, 'update']);
        Route::delete('cms/pages/{cmsPage}', [\App\Http\Controllers\API\PlatformCmsController::class, 'destroy']);
        Route::get('blog', [\App\Http\Controllers\API\PlatformBlogController::class, 'index']);
        Route::post('blog', [\App\Http\Controllers\API\PlatformBlogController::class, 'store']);
        Route::post('blog/{blogPost}', [\App\Http\Controllers\API\PlatformBlogController::class, 'update']);
        Route::delete('blog/{blogPost}', [\App\Http\Controllers\API\PlatformBlogController::class, 'destroy']);

        Route::get('dashboard', [PlatformSaasController::class, 'dashboard']);
        Route::get('tenants', [PlatformTenantController::class, 'index']);
        Route::post('tenants', [PlatformTenantController::class, 'store']);
        Route::get('tenants/{tenantId}', [PlatformTenantController::class, 'show']);
        Route::patch('tenants/{tenantId}/status', [PlatformTenantController::class, 'changeStatus']);
        Route::patch('tenants/{tenantId}/separate-db', [PlatformTenantController::class, 'toggleSeparateDb']);
        Route::post('tenants/{tenantId}/addons', [PlatformTenantController::class, 'grantAddons']);
        Route::post('tenants/{tenantId}/subscription', [PlatformSaasController::class, 'assignTenantPlan']);
        Route::get('tenants/{tenantId}/usage', [PlatformSaasController::class, 'tenantUsage']);
        Route::post('tenants/{tenantId}/backups', [PlatformSaasController::class, 'requestBackup']);
        // Role + permission templates (super-admin defines roles per shop type)
        Route::get('role-templates/permission-catalog', [PlatformRoleTemplateController::class, 'permissionCatalog']);
        Route::get('role-templates', [PlatformRoleTemplateController::class, 'index']);
        Route::post('role-templates', [PlatformRoleTemplateController::class, 'store']);
        Route::patch('role-templates/{roleTemplate}', [PlatformRoleTemplateController::class, 'update']);
        Route::delete('role-templates/{roleTemplate}', [PlatformRoleTemplateController::class, 'destroy']);

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
    Route::get('store-features', [StoreFeatureController::class, 'index']);
    Route::post('store-features', [StoreFeatureController::class, 'update']);
    Route::get('my-features', [StoreFeatureController::class, 'mine']);

    Route::get('price-tiers', [\App\Http\Controllers\API\PriceTierController::class, 'index']);
    Route::post('price-tiers', [\App\Http\Controllers\API\PriceTierController::class, 'store']);
    Route::patch('price-tiers/{priceTier}', [\App\Http\Controllers\API\PriceTierController::class, 'update']);
    Route::delete('price-tiers/{priceTier}', [\App\Http\Controllers\API\PriceTierController::class, 'destroy']);
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
        // Tenant creates roles from super-admin shop-type templates (no raw permission editing)
        Route::get('roles/available-templates', [RoleAPIController::class, 'availableTemplates']);
        Route::post('roles/from-template', [RoleAPIController::class, 'storeFromTemplate']);
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

    Route::get('products/{product}/unit-levels', [ProductAPIController::class, 'unitLevels']);
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
        Route::get('fbr-invoices', [FbrProfileAPIController::class, 'invoices']);
        Route::post('fbr-invoices/from-sale/{sale}', [FbrProfileAPIController::class, 'queueFromSale']);
        Route::post('fbr-invoices/{fbrInvoice}/submit', [FbrProfileAPIController::class, 'submitInvoice']);
        Route::post('fbr-invoices/{fbrInvoice}/retry', [FbrProfileAPIController::class, 'retryInvoice']);
        Route::post('fbr-invoices/process-queue', [FbrProfileAPIController::class, 'processFbrQueue']);
    });

    Route::middleware('permission:manage_offline_devices')->group(function () {
        Route::get('offline-devices', [OfflineSyncAPIController::class, 'devices']);
        Route::get('sync-queue', [OfflineSyncAPIController::class, 'queue']);
        Route::patch('sync-queue/{syncQueue}/status', [OfflineSyncAPIController::class, 'updateQueueStatus']);
    });
    Route::post('offline-devices/register', [OfflineSyncAPIController::class, 'registerDevice']);
    Route::post('offline-devices/{offlineDevice}/heartbeat', [OfflineSyncAPIController::class, 'heartbeat']);
    Route::post('offline-sync/batches', [OfflineSyncAPIController::class, 'pushBatch']);

    // Restaurant module
    Route::get('restaurant/halls', [RestaurantAPIController::class, 'halls']);
    Route::post('restaurant/halls', [RestaurantAPIController::class, 'storeHall']);
    Route::patch('restaurant/halls/{hall}', [RestaurantAPIController::class, 'updateHall']);
    Route::delete('restaurant/halls/{hall}', [RestaurantAPIController::class, 'destroyHall']);

    Route::get('restaurant/tables', [RestaurantAPIController::class, 'tables']);
    Route::post('restaurant/tables', [RestaurantAPIController::class, 'storeTable']);
    Route::patch('restaurant/tables/{table}', [RestaurantAPIController::class, 'updateTable']);
    Route::delete('restaurant/tables/{table}', [RestaurantAPIController::class, 'destroyTable']);
    Route::patch('restaurant/tables/{table}/state', [RestaurantAPIController::class, 'changeTableState']);

    Route::get('restaurant/kots', [RestaurantAPIController::class, 'tickets']);
    Route::post('restaurant/kots', [RestaurantAPIController::class, 'storeTicket']);
    Route::patch('restaurant/kots/{ticket}/status', [RestaurantAPIController::class, 'updateTicketStatus']);

    // Customer displays (kiosks) + their order board
    Route::get('customer-displays', [\App\Http\Controllers\API\CustomerDisplayController::class, 'index']);
    Route::post('customer-displays', [\App\Http\Controllers\API\CustomerDisplayController::class, 'store']);
    Route::patch('customer-displays/{customerDisplay}', [\App\Http\Controllers\API\CustomerDisplayController::class, 'update']);
    Route::delete('customer-displays/{customerDisplay}', [\App\Http\Controllers\API\CustomerDisplayController::class, 'destroy']);
    Route::get('customer-orders', [\App\Http\Controllers\API\CustomerDisplayController::class, 'orders']);
    Route::post('customer-orders/{customerOrder}/accept', [\App\Http\Controllers\API\CustomerDisplayController::class, 'acceptOrder']);
    Route::post('customer-orders/{customerOrder}/assign', [\App\Http\Controllers\API\CustomerDisplayController::class, 'assignOrder']);
    Route::post('customer-orders/{customerOrder}/serve', [\App\Http\Controllers\API\CustomerDisplayController::class, 'serveOrder']);

    Route::get('deliveries', [\App\Http\Controllers\API\DeliveryAPIController::class, 'index']);
    Route::get('delivery-boys', [\App\Http\Controllers\API\DeliveryAPIController::class, 'deliveryBoys']);
    Route::post('deliveries/{sale}/assign', [\App\Http\Controllers\API\DeliveryAPIController::class, 'assign']);
    Route::post('deliveries/{sale}/status', [\App\Http\Controllers\API\DeliveryAPIController::class, 'updateStatus']);

    Route::get('deals', [\App\Http\Controllers\API\DealAPIController::class, 'index']);
    Route::post('deals', [\App\Http\Controllers\API\DealAPIController::class, 'store']);
    Route::post('deals/{deal}', [\App\Http\Controllers\API\DealAPIController::class, 'update']);
    Route::delete('deals/{deal}', [\App\Http\Controllers\API\DealAPIController::class, 'destroy']);

    Route::get('restaurant/kitchens', [RestaurantAPIController::class, 'kitchens']);
    Route::post('restaurant/kitchens', [RestaurantAPIController::class, 'storeKitchen']);
    Route::patch('restaurant/kitchens/{kitchen}', [RestaurantAPIController::class, 'updateKitchen']);
    Route::delete('restaurant/kitchens/{kitchen}', [RestaurantAPIController::class, 'destroyKitchen']);
    Route::post('restaurant/assign-waiter-kitchen', [RestaurantAPIController::class, 'assignWaiterKitchen']);

    // Recurring / Water Supply module
    Route::get('recurring/plans', [RecurringAPIController::class, 'plans']);
    Route::post('recurring/plans', [RecurringAPIController::class, 'storePlan']);
    Route::patch('recurring/plans/{plan}', [RecurringAPIController::class, 'updatePlan']);
    Route::delete('recurring/plans/{plan}', [RecurringAPIController::class, 'destroyPlan']);

    Route::get('recurring/customer-subscriptions', [RecurringAPIController::class, 'subscriptions']);
    Route::post('recurring/customer-subscriptions', [RecurringAPIController::class, 'storeSubscription']);
    Route::patch('recurring/customer-subscriptions/{subscription}', [RecurringAPIController::class, 'updateSubscription']);
    Route::post('recurring/customer-subscriptions/{subscription}/pause', [RecurringAPIController::class, 'pauseSubscription']);
    Route::post('recurring/customer-subscriptions/{subscription}/resume', [RecurringAPIController::class, 'resumeSubscription']);
    Route::delete('recurring/customer-subscriptions/{subscription}', [RecurringAPIController::class, 'destroySubscription']);
    Route::post('recurring/customer-subscriptions/{subscription}/generate-invoice', [RecurringAPIController::class, 'generateInvoice']);

    Route::get('recurring/delivery-schedules', [RecurringAPIController::class, 'deliveries']);
    Route::patch('recurring/delivery-schedules/{delivery}', [RecurringAPIController::class, 'updateDelivery']);

    Route::get('recurring/invoices', [RecurringAPIController::class, 'invoices']);
    Route::post('recurring/invoices/{invoice}/pay', [RecurringAPIController::class, 'payInvoice']);
    Route::get('recurring/overdue', [RecurringAPIController::class, 'overdue']);
    Route::post('recurring/invoices/{invoice}/remind', [RecurringAPIController::class, 'sendReminder']);

    // Accounting module
    Route::get('accounting/accounts', [AccountingAPIController::class, 'accounts']);
    Route::post('accounting/accounts', [AccountingAPIController::class, 'storeAccount']);
    Route::patch('accounting/accounts/{account}', [AccountingAPIController::class, 'updateAccount']);
    Route::delete('accounting/accounts/{account}', [AccountingAPIController::class, 'destroyAccount']);

    Route::get('accounting/journal-entries', [AccountingAPIController::class, 'journalEntries']);
    Route::post('accounting/journal-entries', [AccountingAPIController::class, 'storeJournalEntry']);
    Route::patch('accounting/journal-entries/{journal}/post', [AccountingAPIController::class, 'postJournalEntry']);
    Route::get('accounting/trial-balance', [AccountingAPIController::class, 'trialBalance']);
    Route::get('accounting/profit-loss', [AccountingAPIController::class, 'profitAndLoss']);
    Route::get('accounting/balance-sheet', [AccountingAPIController::class, 'balanceSheet']);
    Route::get('accounting/ledger/{account}', [AccountingAPIController::class, 'ledger']);
    Route::post('accounting/seed-chart-of-accounts', [AccountingAPIController::class, 'seedChartOfAccounts']);
    Route::post('accounting/auto-post/sale/{sale}', [AccountingAPIController::class, 'autoPostSale']);
    Route::post('accounting/auto-post/purchase/{purchase}', [AccountingAPIController::class, 'autoPostPurchase']);
    Route::post('accounting/auto-post/expense/{expense}', [AccountingAPIController::class, 'autoPostExpense']);

    // Notifications
    Route::get('notifications', [NotificationAPIController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationAPIController::class, 'unreadCount']);
    Route::post('notifications', [NotificationAPIController::class, 'store']);
    Route::patch('notifications/{notification}/read', [NotificationAPIController::class, 'markRead']);
    Route::post('notifications/mark-all-read', [NotificationAPIController::class, 'markAllRead']);
    Route::delete('notifications/{notification}', [NotificationAPIController::class, 'destroy']);
    Route::post('notifications/dispatch', [NotificationAPIController::class, 'dispatchPending']);

    // AI / Insights
    Route::get('insights/sales-forecast', [InsightsAPIController::class, 'salesForecast']);
    Route::get('insights/reorder-suggestions', [InsightsAPIController::class, 'reorderSuggestions']);
    Route::get('insights/customer-trends', [InsightsAPIController::class, 'customerTrends']);

    // HR
    Route::get('hr/employees', [HRAPIController::class, 'employees']);
    Route::post('hr/employees', [HRAPIController::class, 'storeEmployee']);
    Route::patch('hr/employees/{employee}', [HRAPIController::class, 'updateEmployee']);
    Route::delete('hr/employees/{employee}', [HRAPIController::class, 'destroyEmployee']);
    Route::get('hr/attendance', [HRAPIController::class, 'attendance']);
    Route::post('hr/employees/{employee}/check-in', [HRAPIController::class, 'checkIn']);
    Route::post('hr/employees/{employee}/check-out', [HRAPIController::class, 'checkOut']);
    Route::patch('hr/attendance/{attendance}', [HRAPIController::class, 'updateAttendance']);

    // Attendance (POS-integrated kiosk + tasks + biometrics + dashboard)
    Route::get('attendance/status', [AttendanceController::class, 'status']);
    Route::post('attendance/identify', [AttendanceController::class, 'identify']);
    Route::post('attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('attendance/check-out', [AttendanceController::class, 'checkOut']);
    Route::post('attendance/break/start', [AttendanceController::class, 'startBreak']);
    Route::post('attendance/break/end', [AttendanceController::class, 'endBreak']);
    Route::post('attendance/manual', [AttendanceController::class, 'manual']);

    Route::get('attendance/tasks', [AttendanceTaskController::class, 'index']);
    Route::post('attendance/tasks/start', [AttendanceTaskController::class, 'start']);
    Route::post('attendance/tasks/{task}/pause', [AttendanceTaskController::class, 'pause']);
    Route::post('attendance/tasks/{task}/resume', [AttendanceTaskController::class, 'resume']);
    Route::post('attendance/tasks/{task}/complete', [AttendanceTaskController::class, 'complete']);

    Route::get('attendance/dashboard', [AttendanceDashboardController::class, 'dashboard']);
    Route::get('attendance/live', [AttendanceDashboardController::class, 'live']);
    Route::get('attendance/records', [AttendanceDashboardController::class, 'records']);

    Route::get('attendance/settings', [AttendanceSettingController::class, 'show']);
    Route::post('attendance/settings', [AttendanceSettingController::class, 'update']);

    Route::get('attendance/biometrics', [EmployeeBiometricController::class, 'index']);
    Route::get('attendance/biometrics/face-data', [EmployeeBiometricController::class, 'faceData']);
    Route::post('attendance/biometrics/face', [EmployeeBiometricController::class, 'enrollFace']);
    Route::post('attendance/biometrics/fingerprint', [EmployeeBiometricController::class, 'enrollFingerprint']);
    Route::delete('attendance/biometrics/{biometric}', [EmployeeBiometricController::class, 'destroy']);

    // No-code device connectors (external biometric scanners / face terminals / cloud APIs)
    Route::get('attendance/device-connectors', [DeviceConnectorController::class, 'index']);
    Route::get('attendance/device-connectors/kiosk', [DeviceConnectorController::class, 'forKiosk']);
    Route::post('attendance/device-connectors', [DeviceConnectorController::class, 'store']);
    Route::patch('attendance/device-connectors/{deviceConnector}', [DeviceConnectorController::class, 'update']);
    Route::delete('attendance/device-connectors/{deviceConnector}', [DeviceConnectorController::class, 'destroy']);
    Route::post('attendance/device-connectors/{deviceConnector}/test', [DeviceConnectorController::class, 'test']);

    // Attendance requests (corrections / leave) + manager review
    Route::get('attendance/requests', [AttendanceRequestController::class, 'index']);
    Route::post('attendance/requests', [AttendanceRequestController::class, 'store']);
    Route::post('attendance/requests/{attendanceRequest}/approve', [AttendanceRequestController::class, 'approve']);
    Route::post('attendance/requests/{attendanceRequest}/reject', [AttendanceRequestController::class, 'reject']);

    // WebAuthn (device-sensor biometric attendance — no external hardware)
    Route::post('attendance/webauthn/register-options', [WebAuthnController::class, 'registerOptions']);
    Route::post('attendance/webauthn/register', [WebAuthnController::class, 'register']);
    Route::post('attendance/webauthn/login-options', [WebAuthnController::class, 'loginOptions']);
    Route::post('attendance/webauthn/verify', [WebAuthnController::class, 'verify']);

    // FBR Digital Invoice module (standalone shop type)
    Route::get('fbr-di/businesses', [FbrBusinessController::class, 'index']);
    Route::post('fbr-di/businesses', [FbrBusinessController::class, 'store']);
    Route::get('fbr-di/businesses/{fbrBusiness}', [FbrBusinessController::class, 'show']);
    Route::patch('fbr-di/businesses/{fbrBusiness}', [FbrBusinessController::class, 'update']);
    Route::delete('fbr-di/businesses/{fbrBusiness}', [FbrBusinessController::class, 'destroy']);

    // FBR DI product/item catalog + FBR HS-code lookup
    Route::get('fbr-di/hs-codes', [FbrDiProductController::class, 'hsCodes']);
    Route::get('fbr-di/products', [FbrDiProductController::class, 'index']);
    Route::post('fbr-di/products', [FbrDiProductController::class, 'store']);
    Route::get('fbr-di/products/{fbrDiProduct}', [FbrDiProductController::class, 'show']);
    Route::patch('fbr-di/products/{fbrDiProduct}', [FbrDiProductController::class, 'update']);
    Route::delete('fbr-di/products/{fbrDiProduct}', [FbrDiProductController::class, 'destroy']);

    Route::get('fbr-di/invoices', [FbrDiInvoiceController::class, 'index']);
    Route::post('fbr-di/invoices', [FbrDiInvoiceController::class, 'store']);
    Route::get('fbr-di/invoices/{id}', [FbrDiInvoiceController::class, 'show']);
    Route::patch('fbr-di/invoices/{id}', [FbrDiInvoiceController::class, 'update']);
    Route::delete('fbr-di/invoices/{id}', [FbrDiInvoiceController::class, 'destroy']);
    // Controlled sync (permission-gated)
    Route::post('fbr-di/invoices/{id}/sync', [FbrDiSyncController::class, 'sync']);
    Route::post('fbr-di/invoices/bulk-sync', [FbrDiSyncController::class, 'bulkSync']);
    // Error Center + Sandbox Testing Center
    Route::get('fbr-di/errors', [FbrErrorController::class, 'index']);
    Route::get('fbr-di/sandbox', [FbrSandboxController::class, 'index']);
    Route::post('fbr-di/sandbox/{id}/run', [FbrSandboxController::class, 'run']);
    Route::get('fbr-di/dashboard', [FbrDiDashboardController::class, 'dashboard']);
    // FBR reports
    Route::get('fbr-di/reports/sales', [FbrReportController::class, 'sales']);
    Route::get('fbr-di/reports/tax', [FbrReportController::class, 'tax']);
    Route::get('fbr-di/reports/sync', [FbrReportController::class, 'sync']);
    Route::get('fbr-di/reports/rejected', [FbrReportController::class, 'rejected']);

    // Pharmacy / perishables — product batches + expiry
    Route::get('product-batches', [ProductBatchController::class, 'index']);
    Route::get('product-batches/near-expiry', [ProductBatchController::class, 'nearExpiry']);
    Route::post('product-batches', [ProductBatchController::class, 'store']);
    Route::patch('product-batches/{productBatch}', [ProductBatchController::class, 'update']);
    Route::delete('product-batches/{productBatch}', [ProductBatchController::class, 'destroy']);

    // Water Supply — routes, bottle ledger, deposits
    Route::get('water/routes', [WaterSupplyController::class, 'routes']);
    Route::post('water/routes', [WaterSupplyController::class, 'storeRoute']);
    Route::patch('water/routes/{deliveryRoute}', [WaterSupplyController::class, 'updateRoute']);
    Route::delete('water/routes/{deliveryRoute}', [WaterSupplyController::class, 'destroyRoute']);
    Route::get('water/bottles', [WaterSupplyController::class, 'bottles']);
    Route::post('water/bottles', [WaterSupplyController::class, 'recordBottles']);
    Route::get('water/bottle-balances', [WaterSupplyController::class, 'bottleBalances']);
    Route::get('water/deposits', [WaterSupplyController::class, 'deposits']);
    Route::post('water/deposits', [WaterSupplyController::class, 'storeDeposit']);
    Route::post('water/deposits/{waterDeposit}/refund', [WaterSupplyController::class, 'refundDeposit']);

    // Bakery — recipes + production runs
    Route::get('bakery/recipes', [BakeryProductionController::class, 'recipes']);
    Route::post('bakery/recipes', [BakeryProductionController::class, 'storeRecipe']);
    Route::patch('bakery/recipes/{recipe}', [BakeryProductionController::class, 'updateRecipe']);
    Route::delete('bakery/recipes/{recipe}', [BakeryProductionController::class, 'destroyRecipe']);
    Route::get('bakery/production', [BakeryProductionController::class, 'runs']);
    Route::post('bakery/production', [BakeryProductionController::class, 'storeRun']);

    // Electronics — serials + warranties
    Route::get('electronics/serials', [ElectronicsController::class, 'serials']);
    Route::post('electronics/serials', [ElectronicsController::class, 'storeSerials']);
    Route::patch('electronics/serials/{productSerial}', [ElectronicsController::class, 'updateSerial']);
    Route::delete('electronics/serials/{productSerial}', [ElectronicsController::class, 'destroySerial']);
    Route::get('electronics/warranties', [ElectronicsController::class, 'warranties']);
    Route::post('electronics/warranties', [ElectronicsController::class, 'registerWarranty']);
    Route::get('electronics/warranty-lookup', [ElectronicsController::class, 'lookup']);

    // Distribution — van load-out + reconciliation
    Route::get('distribution/routes', [DistributionController::class, 'routes']);
    Route::get('distribution/loads', [DistributionController::class, 'loads']);
    Route::post('distribution/loads', [DistributionController::class, 'storeLoad']);
    Route::post('distribution/loads/{dispatchLoad}/reconcile', [DistributionController::class, 'reconcile']);
    Route::delete('distribution/loads/{dispatchLoad}', [DistributionController::class, 'destroyLoad']);

    // Attendance reports
    Route::get('attendance/reports/summary', [AttendanceReportController::class, 'summary']);
    Route::get('attendance/reports/productivity', [AttendanceReportController::class, 'productivity']);
    Route::get('attendance/reports/performance', [AttendanceReportController::class, 'performance']);

    // CRM
    Route::get('crm/leads', [CRMAPIController::class, 'leads']);
    Route::post('crm/leads', [CRMAPIController::class, 'storeLead']);
    Route::patch('crm/leads/{lead}', [CRMAPIController::class, 'updateLead']);
    Route::patch('crm/leads/{lead}/stage', [CRMAPIController::class, 'changeStage']);
    Route::delete('crm/leads/{lead}', [CRMAPIController::class, 'destroyLead']);
    Route::get('crm/pipeline-stats', [CRMAPIController::class, 'pipelineStats']);

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

// ── Public marketing endpoints (no auth required) ─────────────────────
Route::prefix('public')->name('public.')->group(function () {
    Route::get('plans',               [PublicController::class, 'plans'])->name('plans');
    Route::get('countries',           [PublicController::class, 'countries'])->name('countries');
    Route::get('currencies',          [PublicController::class, 'currencies'])->name('currencies');
    Route::get('shop-types',          [PublicController::class, 'shopTypes'])->name('shop-types');
    Route::get('cms/menu',            [PublicController::class, 'cmsMenu'])->name('cms.menu');
    Route::get('cms/page/{slug}',     [PublicController::class, 'cmsPage'])->name('cms.page');
    Route::get('blog',                [PublicController::class, 'blogList'])->name('blog.list');
    Route::get('blog/{slug}',         [PublicController::class, 'blogPost'])->name('blog.post');
    Route::post('demo-request',       [PublicController::class, 'demoRequest'])->name('demo-request');
    // Customer self-service kiosk (no login, scoped by display token)
    Route::get('kiosk/{token}/config', [\App\Http\Controllers\API\KioskController::class, 'config'])->name('kiosk.config');
    Route::post('kiosk/{token}/order', [\App\Http\Controllers\API\KioskController::class, 'order'])->name('kiosk.order');
});

// Super-admin only: manage demo requests (requires Sanctum auth)
Route::middleware('auth:sanctum')->prefix('admin')->name('admin.')->group(function () {
    Route::get('demo-requests',        [PublicController::class, 'demoRequestList'])->name('demo-requests.index');
    Route::patch('demo-requests/{id}', [PublicController::class, 'demoRequestUpdate'])->name('demo-requests.update');
});

Route::post(
    '/forgot-password',
    [AuthController::class, 'sendPasswordResetLinkEmail']
)->middleware('throttle:5,1')->name('password.email');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');

Route::get('front-cms', [SettingAPIController::class, 'getFrontCms']);

Route::post('validate-auth-token', [AuthController::class, 'isValidToken']);

require __DIR__ . '/m1.php';
