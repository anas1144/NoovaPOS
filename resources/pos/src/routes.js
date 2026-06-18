import Dashboard from "./components/dashboard/Dashboard";
import Brands from "./components/brands/Brands";
import Currencies from "./components/currency/Currencies";
import Warehouses from "./components/warehouse/Warehouses";
import CreateWarehouse from "./components/warehouse/CreateWarehouse";
import EditWarehouse from "./components/warehouse/EditWarehouse";
import ProductCategory from "./components/productCategory/ProductCategory";
import Units from "./components/units/Units";
import Suppliers from "./components/supplier/Suppliers";
import CreateSupplier from "./components/supplier/CreateSupplier";
import EditSupplier from "./components/supplier/EditSupplier";
import Customers from "./components/customer/Customers";
import CreateCustomer from "./components/customer/CreateCustomer";
import EditCustomer from "./components/customer/EditCustomer";
import User from "./components/users/User";
import CreateUser from "./components/users/CreateUser";
import EditUser from "./components/users/EditUser";
import UserDetail from "./components/users/UserDetail";
import UpdateProfile from "./components/user-profile/UpdateProfile";
import Product from "./components/product/Product";
import CreateProduct from "./components/product/CreateProduct";
import EditProduct from "./components/product/EditProduct";
import ProductDetail from "./components/product/ProductDetail";
import Settings from "./components/settings/Settings";
import ExpenseCategory from "./components/expense-category/ExpenseCategory";
import Expenses from "./components/expense/Expenses";
import CreateExpense from "./components/expense/CreateExpense";
import EditExpense from "./components/expense/EditExpense";
import Purchases from "./components/purchase/Purchases";
import CreatePurchase from "./components/purchase/CreatePurchase";
import EditPurchase from "./components/purchase/EditPurchase";
import PurchaseDetails from "./components/purchase/PurchaseDetails";
import PosMainPage from "./frontend/components/PosMainPage";
import PrintData from "./frontend/components/printModal/PrintData";
import Sales from "./components/sales/Sales";
import CreateSale from "./components/sales/CreateSale";
import EditSale from "./components/sales/EditSale";
import SaleReturn from "./components/saleReturn/SaleReturn";
import CreateSaleReturn from "./components/saleReturn/CreateSaleReturn";
import EditSaleReturn from "./components/saleReturn/EditSaleReturn";
import SaleReturnDetails from "./components/saleReturn/SaleReturnDetails";
import SaleDetails from "./components/sales/SaleDetails";
import PurchaseReturn from "./components/purchaseReturn/PurchaseReturn";
import CreatePurchaseReturn from "./components/purchaseReturn/CreatePurchaseReturn";
import EditPurchaseReturn from "./components/purchaseReturn/EditPurchaseReturn";
import PurchaseReturnDetails from "./components/purchaseReturn/PurchaseReturnDetails";
import WarehouseReport from "./components/report/warehouseReport/WarehouseReport";
import SaleReport from "./components/report/saleReport/SaleReport";
import StockReport from "./components/report/stockReport/StockReport";
import StockMovementReport from "./components/report/stockMovementReport/StockMovementReport";
import StockDetails from "./components/report/stockReport/StockDetails";
import TopSellingProductsReport from "./components/report/topSellingReport/TopSellingProductsReport";
import PurchaseReport from "./components/report/purchaseReport/PurchaseReport";
import PrintBarcode from "./components/printBarcode/PrintBarcode";
import { Permissions } from "./constants";
import Role from "./components/roles/Role";
import CreateRole from "./components/roles/CreateRole";
import EditRole from "./components/roles/EditRole";
import Adjustments from "./components/adjustments/Adjustments";
import CreateAdjustment from "./components/adjustments/CreateAdjustment";
import EditAdjustMent from "./components/adjustments/EditAdjustMent";
import WarehouseDetail from "./components/warehouse/WarehouseDetail";
import ProductQuantityReport from "./components/report/productQuantityReport/ProductQuantityReport";
import Transfers from "./components/transfers/Transfers";
import EditTransfer from "./components/transfers/EditTransfer";
import CreateTransfer from "./components/transfers/CreateTransfer";
import Prefixes from "./components/settings/Prefixes";
import SuppliersReport from "./components/report/supplier-report/SuppliersReport";
import SupplierReportDetails from "./components/report/supplier-report/SupplierReportDetails";
import EmailTemplates from "./components/Email-templates/EmailTemplates";
import EditEmailTemplate from "./components/Email-templates/EditEmailTemplate";
import Quotations from "./components/quotations/Quotations";
import CreateQuotation from "./components/quotations/CreateQuotation";
import EditQuotation from "./components/quotations/EditQuotation";
import CreateQuotationSale from "./components/quotations/CreateQuotationSale";
import QuotationDetails from "./components/quotations/QuotationDetails";
import MailSettings from "./components/settings/MailSettings";
import SmsTemplates from "./components/sms-templates/SmsTemplates";
import EditSmsTemplate from "./components/sms-templates/EditSmsTemplate";
import BestCustomerReport from "./components/report/best-customerReport/BestCustomerReport";
import ProfitLossReport from "./components/report/ProfitLossReport/ProfitLossReport";
import CustomerReportDetails from "./components/report/customer-report/CustomerReportDetails";
import CustomersReport from "./components/report/customer-report/CustomersReport";
import SmsApi from "./components/sms-api/SmsApi";
import EditSaleReturnFromSale from "./components/saleReturn/EditSaleReturnFromSale";
import Language from "./components/languages/Language";
import EditLanguageData from "./components/languages/EditLanguageData";
import BaseUnits from "./components/base-unit/BaseUnits";
import RegisterReport from "./components/report/registerReport/RegisterReport";
import Variation from "./components/variation/Variation";
import ReceiptSettings from "./components/settings/ReceiptSettings";
import Store from "./components/store/store";
import Taxes from "./components/settings/Taxes/Taxes";
import PosSettings from "./components/settings/PosSettings";
import EditPurchaseReturnForm from "./components/purchaseReturn/EditPurchaseReturnForm";
import PaymentMethod from "./components/paymentMethod/PaymentMethod";
import CustomerDisplay from "./components/customerDisplay/CustomerDisplay";
import DualScreenSetting from "./components/settings/DualScreenSetting";
import Shop from "./components/shop/Shop";
import FbrProfiles from "./components/fbrProfile/FbrProfiles";
import OfflineDevices from "./components/offlineSync/OfflineDevices";
import SyncQueue from "./components/offlineSync/SyncQueue";
import PlatformDashboard from "./components/platform/PlatformDashboard";
import PlatformTenants from "./components/platform/PlatformTenants";
import PlatformPlans from "./components/platform/PlatformPlans";
import PlatformRoleTemplates from "./components/platform/PlatformRoleTemplates";
import Billing from "./components/billing/Billing";
import PlatformPayments from "./components/platform/PlatformPayments";
import PlatformBankAccounts from "./components/platform/PlatformBankAccounts";
import PlatformBillingSettings from "./components/platform/PlatformBillingSettings";
import PlatformShopTypes from "./components/platform/PlatformShopTypes";
import PlatformFeatures from "./components/platform/PlatformFeatures";
import PlatformCmsPages from "./components/platform/PlatformCmsPages";
import PlatformBlog from "./components/platform/PlatformBlog";
import StoreFeatures from "./components/store/StoreFeatures";
import PriceTiers from "./components/price-tier/PriceTiers";
import Deals from "./components/deals/Deals";
import Deliveries from "./components/delivery/Deliveries";
import CustomerDisplays from "./components/kiosk/CustomerDisplays";
import CustomerOrders from "./components/kiosk/CustomerOrders";
import PlatformSubscriptions from "./components/platform/PlatformSubscriptions";
import PlatformAuditLogs from "./components/platform/PlatformAuditLogs";
import PlatformBackups from "./components/platform/PlatformBackups";
import Halls from "./components/restaurant/Halls";
import Tables from "./components/restaurant/Tables";
import KotBoard from "./components/restaurant/KotBoard";
import Kitchens from "./components/restaurant/Kitchens";
import RecurringPlans from "./components/recurring/RecurringPlans";
import CustomerSubscriptions from "./components/recurring/CustomerSubscriptions";
import DeliverySchedules from "./components/recurring/DeliverySchedules";
import ChartOfAccounts from "./components/accounting/ChartOfAccounts";
import JournalEntries from "./components/accounting/JournalEntries";
import TrialBalance from "./components/accounting/TrialBalance";
import ProfitLoss from "./components/accounting/ProfitLoss";
import BalanceSheet from "./components/accounting/BalanceSheet";
import Notifications from "./components/notifications/Notifications";
import Insights from "./components/insights/Insights";
import FbrInvoices from "./components/fbrProfile/FbrInvoices";
import Employees from "./components/hr/Employees";
import Attendance from "./components/hr/Attendance";
import AttendanceCheckin from "./components/attendance/AttendanceCheckin";
import AttendanceDashboard from "./components/attendance/AttendanceDashboard";
import LiveAttendance from "./components/attendance/LiveAttendance";
import AttendanceTasks from "./components/attendance/AttendanceTasks";
import FaceEnrollment from "./components/attendance/FaceEnrollment";
import FingerprintEnrollment from "./components/attendance/FingerprintEnrollment";
import AttendanceSettings from "./components/attendance/AttendanceSettings";
import AttendanceReports from "./components/attendance/AttendanceReports";
import AttendanceRequests from "./components/attendance/AttendanceRequests";
import DeviceConnectors from "./components/attendance/DeviceConnectors";
import FbrBusinesses from "./components/fbrDigital/FbrBusinesses";
import FbrInvoices from "./components/fbrDigital/FbrInvoices";
import FbrInvoiceForm from "./components/fbrDigital/FbrInvoiceForm";
import FbrErrors from "./components/fbrDigital/FbrErrors";
import FbrSandbox from "./components/fbrDigital/FbrSandbox";
import FbrDashboard from "./components/fbrDigital/FbrDashboard";
import FbrReports from "./components/fbrDigital/FbrReports";
import PlatformFbrDi from "./components/platform/PlatformFbrDi";
import PharmacyBatches from "./components/pharmacy/PharmacyBatches";
import WaterSupply from "./components/water/WaterSupply";
import BakeryProduction from "./components/bakery/BakeryProduction";
import Electronics from "./components/electronics/Electronics";
import Distribution from "./components/distribution/Distribution";
import OverdueInvoices from "./components/recurring/OverdueInvoices";
import Pipeline from "./components/crm/Pipeline";

export const route = [
    {
        path: "dashboard",
        ele: <Dashboard />,
        permission: Permissions.MANAGE_DASHBOARD,
    },
    {
        path: "brands",
        ele: <Brands />,
        permission: Permissions.MANAGE_BRANDS,
    },
    {
        path: "currencies",
        ele: <Currencies />,
        permission: Permissions.MANAGE_CURRENCY,
    },
    {
        path: "warehouses",
        ele: <Warehouses />,
        permission: Permissions.MANAGE_WAREHOUSES,
    },
    {
        path: "warehouse/create",
        ele: <CreateWarehouse />,
        permission: Permissions.CREATE_WAREHOUSES,
    },
    {
        path: "warehouse/edit/:id",
        ele: <EditWarehouse />,
        permission: Permissions.EDIT_WAREHOUSES,
    },
    {
        path: "warehouse/detail/:id",
        ele: <WarehouseDetail />,
        permission: Permissions.VIEW_WAREHOUSES,
    },
    {
        path: "product-categories",
        ele: <ProductCategory />,
        permission: Permissions.MANAGE_PRODUCT_CATEGORIES,
    },
    {
        path: "variations",
        ele: <Variation />,
        permission: Permissions.MANAGE_VARIATIONS,
    },

    {
        path: "units",
        ele: <Units />,
        permission: Permissions.MANAGE_UNITS,
    },
    {
        path: "base-units",
        ele: <BaseUnits />,
        permission: Permissions.MANAGE_UNITS,
    },
    {
        path: "suppliers",
        ele: <Suppliers />,
        permission: Permissions.MANAGE_SUPPLIERS,
    },
    {
        path: "suppliers/create",
        ele: <CreateSupplier />,
        permission: Permissions.CREATE_SUPPLIERS,
    },
    {
        path: "suppliers/edit/:id",
        ele: <EditSupplier />,
        permission: Permissions.EDIT_SUPPLIERS,
    },
    {
        path: "customers",
        ele: <Customers />,
        permission: Permissions.MANAGE_CUSTOMERS,
    },
    {
        path: "customers/create",
        ele: <CreateCustomer />,
        permission: Permissions.CREATE_CUSTOMERS,
    },
    {
        path: "customers/edit/:id",
        ele: <EditCustomer />,
        permission: Permissions.EDIT_CUSTOMERS,
    },
    {
        path: "users",
        ele: <User />,
        permission: Permissions.MANAGE_USER,
    },
    {
        path: "users/create",
        ele: <CreateUser />,
        permission: Permissions.CREATE_USERS,
    },
    {
        path: "users/edit/:id",
        ele: <EditUser />,
        permission: Permissions.EDIT_USERS,
    },
    {
        path: "users/detail/:id",
        ele: <UserDetail />,
        permission: Permissions.VIEW_USERS,
    },
    {
        path: "profile/edit",
        ele: <UpdateProfile />,
        permission: "",
    },
    {
        path: "billing",
        ele: <Billing />,
        permission: "",
    },
    {
        path: "products",
        ele: <Product />,
        permission: Permissions.MANAGE_PRODUCTS,
    },
    {
        path: "products/create",
        ele: <CreateProduct />,
        permission: Permissions.CREATE_PRODUCTS,
    },
    {
        path: "products/edit/:id",
        ele: <EditProduct />,
        permission: Permissions.EDIT_PRODUCTS,
    },
    {
        path: "products/detail/:id",
        ele: <ProductDetail />,
        permission: Permissions.VIEW_PRODUCTS,
    },
    {
        path: "adjustments",
        ele: <Adjustments />,
        permission: Permissions.MANAGE_ADJUSTMENTS,
    },
    {
        path: "adjustments/create",
        ele: <CreateAdjustment />,
        permission: Permissions.CREATE_ADJUSTMENTS,
    },
    {
        path: "adjustments/:id",
        ele: <EditAdjustMent />,
        permission: Permissions.EDIT_ADJUSTMENTS,
    },
    {
        path: "settings",
        ele: <Settings />,
        permission: Permissions.MANAGE_SETTING,
    },
    {
        path: "prefixes",
        ele: <Prefixes />,
        permission: Permissions.MANAGE_SETTING,
    },
    {
        path: "mail-settings",
        ele: <MailSettings />,
        permission: Permissions.MANAGE_SETTING,
    },
    {
        path: "receipt-settings",
        ele: <ReceiptSettings />,
        permission: Permissions.MANAGE_SETTING,
    },
    {
        ele: <Taxes />,
        permission: Permissions.MANAGE_SETTING,
        path: "taxes",
    },
    {
        ele: <PosSettings />,
        permission: Permissions.MANAGE_SETTING,
        path: "pos-settings",
    },
    {
        path: "dual-screen-settings",
        ele: <DualScreenSetting />,
        permission: Permissions.MANAGE_SETTING,
    },
    {
        path: "expense-categories",
        ele: <ExpenseCategory />,
        permission: Permissions.MANAGE_EXPENSES_CATEGORIES,
    },
    {
        path: "expenses",
        ele: <Expenses />,
        permission: Permissions.MANAGE_EXPENSES,
    },
    {
        path: "expenses/create",
        ele: <CreateExpense />,
        permission: Permissions.CREATE_EXPENSES,
    },
    {
        path: "expenses/edit/:id",
        ele: <EditExpense />,
        permission: Permissions.EDIT_EXPENSES,
    },
    {
        path: "purchases",
        ele: <Purchases />,
        permission: Permissions.MANAGE_PURCHASE,
    },
    {
        path: "purchases/create",
        ele: <CreatePurchase />,
        permission: Permissions.CREATE_PURCHASE,
    },
    {
        path: "purchases/edit/:id",
        ele: <EditPurchase />,
        permission: Permissions.EDIT_PURCHASE,
    },
    {
        path: "purchases/detail/:id",
        ele: <PurchaseDetails />,
        permission: Permissions.VIEW_PURCHASE,
    },
    {
        path: "purchases/return/:id",
        ele: <CreatePurchaseReturn />,
        permission: Permissions.CREATE_PURCHASE_RETURN,
    },
    {
        path: "purchases/return/edit/:id",
        ele: <EditPurchaseReturn />,
        permission: Permissions.EDIT_PURCHASE_RETURN,
    },
    {
        path: "pos",
        ele: <PosMainPage />,
        permission: Permissions.MANAGE_POS_SCREEN,
    },
    {
        path: "/payment",
        ele: <PrintData />,
        permission: "",
    },
    {
        path: "user-detail",
        ele: <UserDetail />,
        permission: Permissions.MANAGE_USER,
    },
    {
        path: "sales",
        ele: <Sales />,
        permission: Permissions.MANAGE_SALE,
    },
    {
        path: "sales/create",
        ele: <CreateSale />,
        permission: Permissions.CREATE_SALE,
    },
    {
        path: "sales/edit/:id",
        ele: <EditSale />,
        permission: Permissions.EDIT_SALE,
    },
    {
        path: "sales/return/:id",
        ele: <CreateSaleReturn />,
        permission: Permissions.CREATE_SALE_RETURN,
    },
    {
        path: "sales/return/edit/:id",
        ele: <EditSaleReturnFromSale />,
        permission: Permissions.EDIT_SALE_RETURN,
    },
    {
        path: "quotations",
        ele: <Quotations />,
        permission: Permissions.MANAGE_QUOTATION,
    },
    {
        path: "quotations/create",
        ele: <CreateQuotation />,
        permission: Permissions.CREATE_QUOTATIONS,
    },
    {
        path: "quotations/edit/:id",
        ele: <EditQuotation />,
        permission: Permissions.EDIT_QUOTATIONS,
    },
    {
        path: "quotations/Create_sale/:id",
        ele: <CreateQuotationSale />,
        permission: Permissions.CREATE_SALE,
    },
    {
        path: "quotations/detail/:id",
        ele: <QuotationDetails />,
        permission: Permissions.VIEW_QUOTATIONS,
    },
    {
        path: "sale-return",
        ele: <SaleReturn />,
        permission: Permissions.MANAGE_SALE_RETURN,
    },
    {
        path: "sale-return/edit/:id",
        ele: <EditSaleReturn />,
        permission: Permissions.EDIT_SALE_RETURN,
    },
    {
        path: "sale-return/detail/:id",
        ele: <SaleReturnDetails />,
        permission: Permissions.VIEW_SALE_RETURN,
    },
    {
        path: "sales/detail/:id",
        ele: <SaleDetails />,
        permission: Permissions.VIEW_SALE,
    },
    {
        path: "purchase-return",
        ele: <PurchaseReturn />,
        permission: Permissions.MANAGE_PURCHASE_RETURN,
    },
    {
        path: "purchase-return/create",
        ele: <CreatePurchaseReturn />,
        permission: Permissions.CREATE_PURCHASE_RETURN,
    },
    {
        path: "purchase-return/edit/:id",
        ele: <EditPurchaseReturnForm />,
        permission: Permissions.EDIT_PURCHASE_RETURN,
    },
    {
        path: "purchase-return/detail/:id",
        ele: <PurchaseReturnDetails />,
        permission: Permissions.VIEW_PURCHASE_RETURN,
    },
    {
        path: "report/report-warehouse",
        ele: <WarehouseReport />,
        permission: Permissions.MANAGE_REPORTS,
    },
    {
        path: "report/report-sale",
        ele: <SaleReport />,
        permission: Permissions.MANAGE_REPORTS,
    },
    {
        path: "report/report-stock",
        ele: <StockReport />,
        permission: Permissions.MANAGE_REPORTS,
    },
    {
        path: "report/report-stock-movements",
        ele: <StockMovementReport />,
        permission: Permissions.MANAGE_REPORTS,
    },
    {
        path: "report/report-detail-stock/:id",
        ele: <StockDetails />,
        permission: Permissions.MANAGE_REPORTS,
    },
    {
        path: "report/report-top-selling-products",
        ele: <TopSellingProductsReport />,
        permission: Permissions.MANAGE_REPORTS,
    },
    {
        path: "report/report-product-quantity",
        ele: <ProductQuantityReport />,
        permission: Permissions.MANAGE_REPORTS,
    },
    {
        path: "report/report-purchase",
        ele: <PurchaseReport />,
        permission: Permissions.MANAGE_REPORTS,
    },
    {
        path: "report/suppliers",
        ele: <SuppliersReport />,
        permission: Permissions.MANAGE_REPORTS,
    },
    {
        path: "report/profit-loss",
        ele: <ProfitLossReport />,
        permission: Permissions.MANAGE_REPORTS,
    },
    {
        path: "report/suppliers/details/:id",
        ele: <SupplierReportDetails />,
        permission: Permissions.MANAGE_REPORTS,
    },
    {
        path: "print/barcode",
        ele: <PrintBarcode />,
        permission: Permissions.MANAGE_PRODUCTS,
    },
    {
        path: "roles",
        ele: <Role />,
        permission: Permissions.MANAGE_ROLES,
    },
    {
        path: "roles/create",
        ele: <CreateRole />,
        permission: Permissions.CREATE_ROLES,
    },
    {
        path: "roles/edit/:id",
        ele: <EditRole />,
        permission: Permissions.EDIT_ROLES,
    },
    {
        path: "transfers",
        ele: <Transfers />,
        permission: Permissions.MANAGE_TRANSFERS,
    },
    {
        path: "transfers/create",
        ele: <CreateTransfer />,
        permission: Permissions.CREATE_TRANSFERS,
    },
    {
        path: "transfers/:id",
        ele: <EditTransfer />,
        permission: Permissions.EDIT_TRANSFERS,
    },
    {
        path: "email-templates",
        ele: <EmailTemplates />,
        permission: Permissions.MANAGE_EMAIL_TEMPLATES,
    },
    {
        path: "email-templates/:id",
        ele: <EditEmailTemplate />,
        permission: Permissions.MANAGE_EMAIL_TEMPLATES,
    },
    {
        path: "sms-templates",
        ele: <SmsTemplates />,
        permission: Permissions.MANAGE_SMS_TEMPLATES,
    },
    {
        path: "sms-templates/:id",
        ele: <EditSmsTemplate />,
        permission: Permissions.MANAGE_SMS_TEMPLATES,
    },
    {
        path: "report/best-customers",
        ele: <BestCustomerReport />,
        permission: "",
    },
    {
        path: "report/customers",
        ele: <CustomersReport />,
        permission: "",
    },
    {
        path: "report/customers/details/:id",
        ele: <CustomerReportDetails />,
        permission: "",
    },
    {
        path: "report/register",
        ele: <RegisterReport />,
        permission: "",
    },
    {
        path: "sms-api",
        ele: <SmsApi />,
        permission: Permissions.MANAGE_SMS_API,
    },
    {
        path: "languages",
        ele: <Language />,
        permission: Permissions.MANAGE_LANGUAGES,
    },
    {
        path: "languages/:id",
        ele: <EditLanguageData />,
        permission: Permissions.EDIT_LANGUAGE,
    },
    {
        path: "payment-methods",
        ele: <PaymentMethod />,
        permission: "",
    },
    {
        path: "store",
        ele: <Store />,
        permission: "",
    },
    {
        path: "customer-display",
        ele: <CustomerDisplay />,
        permission: "",
    },
    {
        path: "shops",
        ele: <Shop />,
        permission: "",
    },
    {
        path: "fbr-profiles",
        ele: <FbrProfiles />,
        permission: "",
    },
    {
        path: "offline-devices",
        ele: <OfflineDevices />,
        permission: "",
    },
    {
        path: "sync-queue",
        ele: <SyncQueue />,
        permission: "",
    },
    {
        path: "platform/dashboard",
        ele: <PlatformDashboard />,
        permission: "",
    },
    {
        path: "platform/tenants",
        ele: <PlatformTenants />,
        permission: "",
    },
    {
        path: "platform/plans",
        ele: <PlatformPlans />,
        permission: "",
    },
    {
        path: "platform/role-templates",
        ele: <PlatformRoleTemplates />,
        permission: "",
    },
    {
        path: "platform/subscriptions",
        ele: <PlatformSubscriptions />,
        permission: "",
    },
    {
        path: "platform/payments",
        ele: <PlatformPayments />,
        permission: "",
    },
    {
        path: "platform/bank-accounts",
        ele: <PlatformBankAccounts />,
        permission: "",
    },
    {
        path: "platform/billing-settings",
        ele: <PlatformBillingSettings />,
        permission: "",
    },
    {
        path: "platform/shop-types",
        ele: <PlatformShopTypes />,
        permission: "",
    },
    {
        path: "platform/features",
        ele: <PlatformFeatures />,
        permission: "",
    },
    {
        path: "platform/cms-pages",
        ele: <PlatformCmsPages />,
        permission: "",
    },
    {
        path: "platform/blog",
        ele: <PlatformBlog />,
        permission: "",
    },
    {
        path: "store-features",
        ele: <StoreFeatures />,
        permission: "",
    },
    {
        path: "price-tiers",
        ele: <PriceTiers />,
        permission: "",
    },
    {
        path: "deals",
        ele: <Deals />,
        permission: "",
    },
    {
        path: "deliveries",
        ele: <Deliveries />,
        permission: "",
    },
    {
        path: "customer-displays",
        ele: <CustomerDisplays />,
        permission: "",
    },
    {
        path: "customer-orders",
        ele: <CustomerOrders />,
        permission: "",
    },
    {
        path: "platform/audit-logs",
        ele: <PlatformAuditLogs />,
        permission: "",
    },
    {
        path: "platform/backups",
        ele: <PlatformBackups />,
        permission: "",
    },
    {
        path: "restaurant/halls",
        ele: <Halls />,
        permission: "",
    },
    {
        path: "restaurant/tables",
        ele: <Tables />,
        permission: "",
    },
    {
        path: "restaurant/kots",
        ele: <KotBoard />,
        permission: "",
    },
    {
        path: "restaurant/kitchens",
        ele: <Kitchens />,
        permission: "",
    },
    {
        path: "recurring/plans",
        ele: <RecurringPlans />,
        permission: "",
    },
    {
        path: "recurring/subscriptions",
        ele: <CustomerSubscriptions />,
        permission: "",
    },
    {
        path: "recurring/deliveries",
        ele: <DeliverySchedules />,
        permission: "",
    },
    {
        path: "accounting/chart-of-accounts",
        ele: <ChartOfAccounts />,
        permission: "",
    },
    {
        path: "accounting/journal-entries",
        ele: <JournalEntries />,
        permission: "",
    },
    {
        path: "accounting/trial-balance",
        ele: <TrialBalance />,
        permission: "",
    },
    {
        path: "accounting/profit-loss",
        ele: <ProfitLoss />,
        permission: "",
    },
    {
        path: "accounting/balance-sheet",
        ele: <BalanceSheet />,
        permission: "",
    },
    {
        path: "notifications",
        ele: <Notifications />,
        permission: "",
    },
    {
        path: "insights",
        ele: <Insights />,
        permission: "",
    },
    {
        path: "fbr-invoices",
        ele: <FbrInvoices />,
        permission: "",
    },
    {
        path: "hr/employees",
        ele: <Employees />,
        permission: "",
    },
    {
        path: "hr/attendance",
        ele: <Attendance />,
        permission: "",
    },
    {
        path: "attendance/checkin",
        ele: <AttendanceCheckin />,
        permission: "",
    },
    {
        path: "attendance/dashboard",
        ele: <AttendanceDashboard />,
        permission: "",
    },
    {
        path: "attendance/live",
        ele: <LiveAttendance />,
        permission: "",
    },
    {
        path: "attendance/tasks",
        ele: <AttendanceTasks />,
        permission: "",
    },
    {
        path: "attendance/biometrics/face",
        ele: <FaceEnrollment />,
        permission: "",
    },
    {
        path: "attendance/biometrics/fingerprint",
        ele: <FingerprintEnrollment />,
        permission: "",
    },
    {
        path: "attendance/settings",
        ele: <AttendanceSettings />,
        permission: "",
    },
    {
        path: "attendance/reports",
        ele: <AttendanceReports />,
        permission: "",
    },
    {
        path: "attendance/requests",
        ele: <AttendanceRequests />,
        permission: "",
    },
    {
        path: "attendance/devices",
        ele: <DeviceConnectors />,
        permission: "",
    },
    {
        path: "fbr-di/businesses",
        ele: <FbrBusinesses />,
        permission: "",
    },
    {
        path: "fbr-di/invoices",
        ele: <FbrInvoices />,
        permission: "",
    },
    {
        path: "fbr-di/invoices/create",
        ele: <FbrInvoiceForm />,
        permission: "",
    },
    {
        path: "fbr-di/invoices/:id",
        ele: <FbrInvoiceForm />,
        permission: "",
    },
    {
        path: "fbr-di/invoices/:id/edit",
        ele: <FbrInvoiceForm />,
        permission: "",
    },
    {
        path: "fbr-di/errors",
        ele: <FbrErrors />,
        permission: "",
    },
    {
        path: "fbr-di/sandbox",
        ele: <FbrSandbox />,
        permission: "",
    },
    {
        path: "fbr-di/dashboard",
        ele: <FbrDashboard />,
        permission: "",
    },
    {
        path: "fbr-di/reports",
        ele: <FbrReports />,
        permission: "",
    },
    {
        path: "platform/fbr-di",
        ele: <PlatformFbrDi />,
        permission: "",
    },
    {
        path: "pharmacy/batches",
        ele: <PharmacyBatches />,
        permission: "",
    },
    {
        path: "water/supply",
        ele: <WaterSupply />,
        permission: "",
    },
    {
        path: "bakery/production",
        ele: <BakeryProduction />,
        permission: "",
    },
    {
        path: "electronics/manage",
        ele: <Electronics />,
        permission: "",
    },
    {
        path: "distribution/loads",
        ele: <Distribution />,
        permission: "",
    },
    {
        path: "recurring/overdue",
        ele: <OverdueInvoices />,
        permission: "",
    },
    {
        path: "crm/pipeline",
        ele: <Pipeline />,
        permission: "",
    },
];
