import React from "react";
import { Permissions } from "../constants";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
    faPieChart, faUser, faTruck, faUserGroup, faHome, faBoxes, faPrint,
    faBookmark, faBoxOpen, faMoneyCheckDollar, faMoneyBills, faQuoteRight,
    faDollarSign, faReceipt, faArrowRight, faArrowLeft, faEnvelope,
    faCartShopping, faChartColumn, faGear, faMapLocation, faBasketShopping,
    faSms, faCube, faFile, faRulerHorizontal, faLanguage, faShieldHalved,
    faLayerGroup, faMoneyCheck, faStore, faDisplay, faShop, faBuilding,
    faCloud, faServer, faRotate, faFileLines, faFileShield, faDatabase,
    faClipboardList, faCrown, faUtensils, faChair, faKitchenSet,
    faTruckMoving, faCalendarDays, faMoneyBillTransfer, faBookOpen,
    faScaleBalanced, faChartLine, faBuildingColumns, faBell, faBrain,
    faIdBadge, faCalendarCheck, faHandshake, faPaperPlane, faWarehouse,
    faCashRegister, faList,
} from "@fortawesome/free-solid-svg-icons";
import { getFormattedMessage } from "../shared/sharedMethod";

// ─────────────────────────────────────────────────────────────────────────────
// buildAsideConfig
// Builds the sidebar filtered by shopType, roles, and permissions.
//
// Usage:
//   import { buildAsideConfig } from "./asideConfig";
//   const menu = buildAsideConfig(activeShopType, userRoles, userPermissions);
//
// Section dividers have shape: { type: 'section', label: '...' }
// The sidebar renderer must check for type === 'section' and render a label.
// ─────────────────────────────────────────────────────────────────────────────
export const buildAsideConfig = (shopType = "retail", userRoles = [], permissions = []) => {
    const isRole  = (r) => userRoles.includes(r);
    const hasPerm = (p) => !p || permissions.includes(p);

    const isSuperAdmin    = isRole("platform_super_admin");
    const isTenantOwner   = isRole("tenant_owner") || isRole("admin");
    const isBranchManager = isRole("branch_manager");
    const isShopManager   = isRole("shop_manager");
    const isAccountant    = isRole("accountant");
    const isInventory     = isRole("inventory_manager");
    const isCashier       = isRole("cashier");
    const isWaiter        = isRole("waiter");
    const isDelivery      = isRole("delivery_staff");

    const canManage = isSuperAdmin || isTenantOwner || isBranchManager || isShopManager;

    const isRestaurant     = shopType === "restaurant";
    const isWaterSupply    = shopType === "water_supply";
    const isMonthlyService = shopType === "monthly_service";
    const isDistribution   = shopType === "distribution";

    const config = [];

    // ─────────────────────────────────────────────────────────────────
    // PLATFORM SUPER ADMIN — system-only menu.
    // The super admin operates the SaaS platform, not an individual shop,
    // so all tenant-level operational menus (products, sales, purchases,
    // stores, shops, reports, etc.) are intentionally hidden. They only
    // get tenant management + platform/system administration.
    // ─────────────────────────────────────────────────────────────────
    if (isSuperAdmin) {
        config.push({ type: "section", label: "Platform (SaaS)" });

        config.push({
            title: "Platform (SaaS)",
            name: "platform",
            fontIcon: <FontAwesomeIcon icon={faCrown} />,
            to: "/app/platform/dashboard",
            class: "d-flex",
            is_submenu: "true",
            permission: "",
            subPath: {
                platformDashboardSubPath:     "/app/platform/dashboard",
                platformTenantsSubPath:       "/app/platform/tenants",
                platformPlansSubPath:         "/app/platform/plans",
                platformRoleTemplatesSubPath: "/app/platform/role-templates",
                platformSubscriptionsSubPath: "/app/platform/subscriptions",
                platformPaymentsSubPath:      "/app/platform/payments",
                platformBankAccountsSubPath:  "/app/platform/bank-accounts",
                platformBillingSettingsSubPath: "/app/platform/billing-settings",
                platformShopTypesSubPath:     "/app/platform/shop-types",
                platformAuditLogsSubPath:     "/app/platform/audit-logs",
                platformBackupsSubPath:       "/app/platform/backups",
            },
            newRoute: [
                { title: "Platform Dashboard", to: "/app/platform/dashboard",     fontIcon: <FontAwesomeIcon icon={faPieChart} />,      class: "d-flex", permission: "" },
                { title: "Tenants",            to: "/app/platform/tenants",       fontIcon: <FontAwesomeIcon icon={faBuilding} />,      class: "d-flex", permission: "" },
                { title: "Plans",              to: "/app/platform/plans",         fontIcon: <FontAwesomeIcon icon={faClipboardList} />, class: "d-flex", permission: "" },
                { title: "Roles & Permissions",to: "/app/platform/role-templates",fontIcon: <FontAwesomeIcon icon={faShieldHalved} />,  class: "d-flex", permission: "" },
                { title: "Subscriptions",      to: "/app/platform/subscriptions", fontIcon: <FontAwesomeIcon icon={faMoneyCheck} />,    class: "d-flex", permission: "" },
                { title: "Payments",           to: "/app/platform/payments",      fontIcon: <FontAwesomeIcon icon={faMoneyBillTransfer} />, class: "d-flex", permission: "" },
                { title: "Bank Accounts",      to: "/app/platform/bank-accounts", fontIcon: <FontAwesomeIcon icon={faBuildingColumns} />, class: "d-flex", permission: "" },
                { title: "Billing Settings",   to: "/app/platform/billing-settings", fontIcon: <FontAwesomeIcon icon={faGear} />,        class: "d-flex", permission: "" },
                { title: "Shop Types",         to: "/app/platform/shop-types",    fontIcon: <FontAwesomeIcon icon={faShop} />,          class: "d-flex", permission: "" },
                { title: "Features",           to: "/app/platform/features",      fontIcon: <FontAwesomeIcon icon={faLayerGroup} />,    class: "d-flex", permission: "" },
                { title: "CMS Pages",          to: "/app/platform/cms-pages",     fontIcon: <FontAwesomeIcon icon={faFile} />,          class: "d-flex", permission: "" },
                { title: "Blog",               to: "/app/platform/blog",          fontIcon: <FontAwesomeIcon icon={faFileLines} />,     class: "d-flex", permission: "" },
                { title: "Audit Logs",         to: "/app/platform/audit-logs",    fontIcon: <FontAwesomeIcon icon={faFileLines} />,     class: "d-flex", permission: "" },
                { title: "Tenant Backups",     to: "/app/platform/backups",       fontIcon: <FontAwesomeIcon icon={faDatabase} />,      class: "d-flex", permission: "" },
            ],
        });

        config.push({ type: "section", label: "Offline & Sync" });

        config.push({
            title: "Offline Sync",
            name: "offline-sync",
            fontIcon: <FontAwesomeIcon icon={faCloud} />,
            to: "/app/offline-devices",
            class: "d-flex",
            is_submenu: "true",
            permission: "",
            subPath: { offlineDevicesSubPath: "/app/offline-devices", syncQueueSubPath: "/app/sync-queue" },
            newRoute: [
                { title: "Offline Devices", to: "/app/offline-devices", fontIcon: <FontAwesomeIcon icon={faServer} />, class: "d-flex", permission: "" },
                { title: "Sync Queue",      to: "/app/sync-queue",      fontIcon: <FontAwesomeIcon icon={faRotate} />, class: "d-flex", permission: "" },
            ],
        });

        config.push({ type: "section", label: "FBR Pakistan" });

        config.push({
            title: "FBR (Pakistan)",
            name: "fbr",
            fontIcon: <FontAwesomeIcon icon={faFileShield} />,
            to: "/app/fbr-profiles",
            class: "d-flex",
            is_submenu: "true",
            permission: "",
            subPath: { fbrProfilesSubPath: "/app/fbr-profiles", fbrInvoicesSubPath: "/app/fbr-invoices" },
            newRoute: [
                { title: "FBR Profiles",  to: "/app/fbr-profiles", fontIcon: <FontAwesomeIcon icon={faFileShield} />, class: "d-flex", permission: "" },
                { title: "Invoice Queue", to: "/app/fbr-invoices",  fontIcon: <FontAwesomeIcon icon={faPaperPlane} />, class: "d-flex", permission: "" },
            ],
        });

        config.push({ type: "section", label: "System" });

        // Centralized configuration that used to live under each tenant. The
        // super admin manages these system-wide.
        config.push({
            title: "System Settings",
            name: "system-settings",
            fontIcon: <FontAwesomeIcon icon={faGear} />,
            to: "/app/currencies",
            class: "d-flex",
            is_submenu: "true",
            permission: "",
            subPath: {
                currenciesSubPath:   "/app/currencies",
                languagesSubPath:    "/app/languages",
                paymentMethodsSubPath: "/app/payment-methods",
                emailTemplateSubPath: "/app/email-templates",
                smsTemplateSubPath:  "/app/sms-templates",
                rolesSubPath:        "/app/roles",
                settingsSubPath:     "/app/settings",
                dualScreenSubPath:   "/app/dual-screen-settings",
            },
            newRoute: [
                { title: "currencies.title",        to: "/app/currencies",            fontIcon: <FontAwesomeIcon icon={faDollarSign} />,    class: "d-flex", permission: "" },
                { title: "languages.title",         to: "/app/languages",             fontIcon: <FontAwesomeIcon icon={faLanguage} />,      class: "d-flex", permission: "" },
                { title: "payment.methods.title",   to: "/app/payment-methods",       fontIcon: <FontAwesomeIcon icon={faMoneyCheck} />,    class: "d-flex", permission: "" },
                { title: "email-template.title",    to: "/app/email-templates",       fontIcon: <FontAwesomeIcon icon={faEnvelope} />,      class: "d-flex", permission: "" },
                { title: "sms-template.title",      to: "/app/sms-templates",         fontIcon: <FontAwesomeIcon icon={faSms} />,           class: "d-flex", permission: "" },
                { title: "roles.permissions.title", to: "/app/roles",                 fontIcon: <FontAwesomeIcon icon={faShieldHalved} />,  class: "d-flex", permission: "" },
                { title: "settings.title",          to: "/app/settings",              fontIcon: <FontAwesomeIcon icon={faGear} />,          class: "d-flex", permission: "" },
                { title: "dual.screen.settings.title", to: "/app/dual-screen-settings", fontIcon: <FontAwesomeIcon icon={faDisplay} />,    class: "d-flex", permission: "" },
            ],
        });

        config.push({
            title: "Notifications",
            name: "notifications",
            fontIcon: <FontAwesomeIcon icon={faBell} />,
            to: "/app/notifications",
            class: "d-flex",
            permission: "",
        });

        return config;
    }

    // ── MAIN ─────────────────────────────────────────────────────────
    config.push({ type: "section", label: "Main" });

    config.push({
        title: "dashboard.title",
        name: "dashboard",
        fontIcon: <FontAwesomeIcon icon={faPieChart} />,
        to: "/app/dashboard",
        class: "d-flex",
        permission: Permissions.MANAGE_DASHBOARD,
    });

    // POS button — hidden in sidebar, triggered from header
    config.push({
        to: "/app/pos",
        class: "d-none",
        name: "pos",
        title: "header.pos.title",
        permission: Permissions.MANAGE_POS_SCREEN,
    });

    // Billing & subscription — tenant owner only (manages the tenant's plan).
    if (isTenantOwner) {
        config.push({
            title: "Billing & Plan",
            name: "billing",
            fontIcon: <FontAwesomeIcon icon={faMoneyCheck} />,
            to: "/app/billing",
            class: "d-flex",
            permission: "",
        });
    }

    // ── INVENTORY ────────────────────────────────────────────────────
    if (canManage || isInventory) {
        config.push({ type: "section", label: "Inventory" });

        config.push({
            title: "products.title",
            name: "products",
            fontIcon: <FontAwesomeIcon icon={faBoxes} />,
            to: "/app/products",
            class: "d-flex",
            is_submenu: "true",
            permission: Permissions.MANAGE_PRODUCTS,
            subPath: {
                productsSubPath:    "/app/products",
                categoriesSubPath:  "/app/product-categories",
                variationsSubPath:  "/app/variations",
                brandsSubPath:      "/app/brands",
                unitsSubPath:       "/app/units",
                baseUnitsSubPath:   "/app/base-units",
                barcodeSubPath:     "/app/print/barcode",
            },
            newRoute: [
                { title: "products.title",         to: "/app/products",           fontIcon: <FontAwesomeIcon icon={faBoxes} />,          class: "d-flex", permission: Permissions.MANAGE_PRODUCTS },
                { title: "product.categories.title",to: "/app/product-categories",fontIcon: <FontAwesomeIcon icon={faBoxOpen} />,         class: "d-flex", permission: Permissions.MANAGE_PRODUCT_CATEGORIES },
                { title: "variations.title",        to: "/app/variations",         fontIcon: <FontAwesomeIcon icon={faLayerGroup} />,      class: "d-flex", permission: Permissions.MANAGE_VARIATIONS },
                { title: "brands.title",            to: "/app/brands",             fontIcon: <FontAwesomeIcon icon={faBookmark} />,        class: "d-flex", permission: Permissions.MANAGE_BRANDS },
                { title: "units.title",             to: "/app/units",              fontIcon: <FontAwesomeIcon icon={faQuoteRight} />,      class: "d-flex", permission: Permissions.MANAGE_UNITS },
                { title: "base-units.title",        to: "/app/base-units",         fontIcon: <FontAwesomeIcon icon={faRulerHorizontal} />, class: "d-flex", permission: Permissions.MANAGE_UNITS },
                { title: "print.barcode.title",     to: "/app/print/barcode",      fontIcon: <FontAwesomeIcon icon={faPrint} />,          class: "d-flex", permission: Permissions.MANAGE_PRODUCTS },
            ],
        });

        config.push({
            title: "warehouse.title",
            name: "warehouse",
            fontIcon: <FontAwesomeIcon icon={faWarehouse} />,
            to: "/app/warehouses",
            class: "d-flex",
            permission: Permissions.MANAGE_WAREHOUSES,
        });

        config.push({
            title: "adjustments.title",
            name: "adjustments",
            fontIcon: <FontAwesomeIcon icon={faMapLocation} />,
            to: "/app/adjustments",
            class: "d-flex",
            permission: Permissions.MANAGE_ADJUSTMENTS,
        });

        config.push({
            title: "transfers.title",
            name: "transfers",
            fontIcon: <FontAwesomeIcon icon={faMapLocation} />,
            to: "/app/transfers",
            class: "d-flex",
            permission: Permissions.MANAGE_TRANSFERS,
        });

        config.push({
            title: "Deals / Combos",
            name: "deals",
            fontIcon: <FontAwesomeIcon icon={faBasketShopping} />,
            to: "/app/deals",
            class: "d-flex",
            permission: "",
        });
    }

    // ── SALES ────────────────────────────────────────────────────────
    config.push({ type: "section", label: "Sales" });

    config.push({
        title: "sales.title",
        name: "sales",
        fontIcon: <FontAwesomeIcon icon={faCartShopping} />,
        to: "/app/sales",
        class: "d-flex",
        is_submenu: "true",
        permission: Permissions.MANAGE_SALE,
        subPath: { salesSubPath: "/app/sales", salesReturnSubPath: "/app/sale-return" },
        newRoute: [
            { title: "sales.title",       to: "/app/sales",       fontIcon: <FontAwesomeIcon icon={faCartShopping} />, class: "d-flex", permission: Permissions.MANAGE_SALE },
            { title: "sales-return.title",to: "/app/sale-return", fontIcon: <FontAwesomeIcon icon={faArrowRight} />,   class: "d-flex", permission: Permissions.MANAGE_SALE_RETURN },
        ],
    });

    config.push({
        title: "quotations.title",
        name: "quotations",
        fontIcon: <FontAwesomeIcon icon={faBasketShopping} />,
        to: "/app/quotations",
        class: "d-flex",
        permission: Permissions.MANAGE_QUOTATION,
    });

    config.push({
        title: "pos.register.title",
        name: "pos-register",
        fontIcon: <FontAwesomeIcon icon={faCashRegister} />,
        to: "/app/report/register",
        class: "d-flex",
        permission: Permissions.MANAGE_POS_SCREEN,
    });

    config.push({
        title: "Deliveries",
        name: "deliveries",
        fontIcon: <FontAwesomeIcon icon={faTruckMoving} />,
        to: "/app/deliveries",
        class: "d-flex",
        permission: "",
    });

    config.push({
        title: "Customer Orders",
        name: "customer-orders",
        fontIcon: <FontAwesomeIcon icon={faDisplay} />,
        to: "/app/customer-orders",
        class: "d-flex",
        permission: "",
    });

    // ── PURCHASES ────────────────────────────────────────────────────
    if (canManage || isInventory || isAccountant) {
        config.push({ type: "section", label: "Purchases" });

        config.push({
            title: "purchases.title",
            name: "purchases",
            fontIcon: <FontAwesomeIcon icon={faReceipt} />,
            to: "/app/purchases",
            class: "d-flex",
            is_submenu: "true",
            permission: Permissions.MANAGE_PURCHASE,
            subPath: { purchasesSubPath: "/app/purchases", purchaseReturnSubPath: "/app/purchase-return" },
            newRoute: [
                { title: "purchases.title",       to: "/app/purchases",        fontIcon: <FontAwesomeIcon icon={faReceipt} />,   class: "d-flex", permission: Permissions.MANAGE_PURCHASE },
                { title: "purchases.return.title",to: "/app/purchase-return",  fontIcon: <FontAwesomeIcon icon={faArrowLeft} />, class: "d-flex", permission: Permissions.MANAGE_PURCHASE_RETURN },
            ],
        });
    }

    // ── PEOPLE ───────────────────────────────────────────────────────
    if (canManage || isAccountant) {
        config.push({ type: "section", label: "People" });

        config.push({
            title: "pepole.title",
            name: "Pepoles",
            fontIcon: <FontAwesomeIcon icon={faUser} />,
            to: "/app/suppliers",
            class: "d-flex",
            is_submenu: "true",
            permission: Permissions.MANAGE_SUPPLIERS,
            subPath: { customerSubPath: "/app/customers", userSubPath: "/app/users", suppliareSubPath: "/app/suppliers" },
            newRoute: [
                { title: "suppliers.title", to: "/app/suppliers", fontIcon: <FontAwesomeIcon icon={faTruck} />,      class: "d-flex", permission: Permissions.MANAGE_SUPPLIERS },
                { title: "customers.title", to: "/app/customers", fontIcon: <FontAwesomeIcon icon={faUserGroup} />,  class: "d-flex", permission: Permissions.MANAGE_CUSTOMERS },
                { title: "users.title",     to: "/app/users",     fontIcon: <FontAwesomeIcon icon={faUser} />,       class: "d-flex", permission: Permissions.MANAGE_USER },
            ],
        });
    }

    // ── FINANCE ──────────────────────────────────────────────────────
    if (canManage || isAccountant) {
        config.push({ type: "section", label: "Finance" });

        config.push({
            title: "expenses.title",
            name: "expenses",
            fontIcon: <FontAwesomeIcon icon={faMoneyBills} />,
            to: "/app/expenses",
            class: "d-flex",
            is_submenu: "true",
            permission: Permissions.MANAGE_EXPENSES,
            subPath: { expensesSubPath: "/app/expenses", expenseCategoriesSubPath: "/app/expense-categories" },
            newRoute: [
                { title: "expenses.title",          to: "/app/expenses",           fontIcon: <FontAwesomeIcon icon={faMoneyBills} />,        class: "d-flex", permission: Permissions.MANAGE_EXPENSES },
                { title: "expense.categories.title",to: "/app/expense-categories", fontIcon: <FontAwesomeIcon icon={faMoneyCheckDollar} />,  class: "d-flex", permission: Permissions.MANAGE_EXPENSES_CATEGORIES },
            ],
        });

        config.push({
            title: "Accounting",
            name: "accounting",
            fontIcon: <FontAwesomeIcon icon={faBookOpen} />,
            to: "/app/accounting/chart-of-accounts",
            class: "d-flex",
            is_submenu: "true",
            permission: "",
            subPath: {
                accountingChartSubPath:    "/app/accounting/chart-of-accounts",
                accountingJournalSubPath:  "/app/accounting/journal-entries",
                accountingTrialBalSubPath: "/app/accounting/trial-balance",
            },
            newRoute: [
                { title: "Chart of Accounts", to: "/app/accounting/chart-of-accounts", fontIcon: <FontAwesomeIcon icon={faList} />,              class: "d-flex", permission: "" },
                { title: "Journal Entries",   to: "/app/accounting/journal-entries",   fontIcon: <FontAwesomeIcon icon={faMoneyBillTransfer} />,  class: "d-flex", permission: "" },
                { title: "Trial Balance",     to: "/app/accounting/trial-balance",     fontIcon: <FontAwesomeIcon icon={faScaleBalanced} />,      class: "d-flex", permission: "" },
                { title: "Profit & Loss",     to: "/app/accounting/profit-loss",       fontIcon: <FontAwesomeIcon icon={faChartLine} />,          class: "d-flex", permission: "" },
                { title: "Balance Sheet",     to: "/app/accounting/balance-sheet",     fontIcon: <FontAwesomeIcon icon={faBuildingColumns} />,    class: "d-flex", permission: "" },
            ],
        });
    }

    // ── REPORTS ──────────────────────────────────────────────────────
    if (canManage || isAccountant) {
        config.push({ type: "section", label: "Reports" });

        config.push({
            title: "reports.title",
            name: "reports",
            fontIcon: <FontAwesomeIcon icon={faChartColumn} />,
            to: "/app/report/report-warehouse",
            path: "/app/report/report-sale",
            stockPath: "/app/report/report-stock",
            stockMovementPath: "/app/report/report-stock-movements",
            purchasePath: "/app/report/report-purchase",
            topSellingPath: "/app/report/report-top-selling-products",
            stockDetailPath: "/app/report/report-detail-stock",
            productQuantityAlertPath: "/app/report/report-product-quantity",
            supplierReportPath: "/app/report/suppliers",
            profitLossReportPath: "/app/report/profit-loss",
            supplierReportDetailsPath: "/app/report/suppliers/details",
            bestCustomerReportPath: "/app/report/best-customers",
            customerReportPath: "/app/report/customers",
            customerReportDetailsPath: "/app/report/customers/details",
            registerReportPath: "/app/report/register",
            class: "d-flex",
            isSamePrefix: "true",
            permission: Permissions.MANAGE_REPORTS,
            subTitles: [
                { title: "warehouse.reports.title" },
                { title: "sale.reports.title" },
                { title: "stock.reports.title" },
                { title: "stock.movement.report.title" },
                { title: "purchase.reports.title" },
                { title: "top-selling-product.reports.title" },
                { title: "product.quantity.alert.reports.title" },
                { title: "supplier.report.title" },
                { title: "profit-loss.reports.title" },
                { title: "best-customer.report.title" },
                { title: "customer.report.title" },
                { title: "register.report.title" },
            ],
            items: [
                { title: getFormattedMessage("warehouse.reports.title"),              to: "/app/report/report-warehouse" },
                { title: getFormattedMessage("sale.reports.title"),                   to: "/app/report/report-sale" },
                { title: getFormattedMessage("stock.reports.title"),                  to: "/app/report/report-stock" },
                { title: getFormattedMessage("stock.movement.report.title"),          to: "/app/report/report-stock-movements" },
                { title: getFormattedMessage("purchase.reports.title"),               to: "/app/report/report-purchase" },
                { title: getFormattedMessage("top-selling-product.reports.title"),    to: "/app/report/report-top-selling-products" },
                { title: getFormattedMessage("product.quantity.alert.reports.title"), to: "/app/report/report-product-quantity" },
                { title: getFormattedMessage("supplier.report.title"),                to: "/app/report/suppliers" },
                { title: getFormattedMessage("profit-loss.reports.title"),            to: "/app/report/profit-loss" },
                { title: getFormattedMessage("best-customer.report.title"),           to: "/app/report/best-customers" },
                { title: getFormattedMessage("customer.report.title"),                to: "/app/report/customers" },
                { title: getFormattedMessage("register.report.title"),                to: "/app/report/register" },
            ],
        });

        config.push({
            title: "AI Insights",
            name: "insights",
            fontIcon: <FontAwesomeIcon icon={faBrain} />,
            to: "/app/insights",
            class: "d-flex",
            permission: "",
        });
    }

    // ── RESTAURANT (only for restaurant shop type) ────────────────────
    if (isRestaurant && (canManage || isWaiter)) {
        config.push({ type: "section", label: "Restaurant" });

        config.push({
            title: "Restaurant",
            name: "restaurant",
            fontIcon: <FontAwesomeIcon icon={faUtensils} />,
            to: "/app/restaurant/halls",
            class: "d-flex",
            is_submenu: "true",
            permission: "",
            subPath: {
                restaurantHallsSubPath:    "/app/restaurant/halls",
                restaurantTablesSubPath:   "/app/restaurant/tables",
                restaurantKitchensSubPath: "/app/restaurant/kitchens",
                restaurantKotsSubPath:     "/app/restaurant/kots",
            },
            newRoute: [
                { title: "Halls",         to: "/app/restaurant/halls",    fontIcon: <FontAwesomeIcon icon={faBuilding} />,   class: "d-flex", permission: "" },
                { title: "Tables",        to: "/app/restaurant/tables",   fontIcon: <FontAwesomeIcon icon={faChair} />,      class: "d-flex", permission: "" },
                { title: "Kitchens",      to: "/app/restaurant/kitchens", fontIcon: <FontAwesomeIcon icon={faKitchenSet} />, class: "d-flex", permission: "" },
                { title: "Kitchen Display", to: "/app/restaurant/kots",   fontIcon: <FontAwesomeIcon icon={faKitchenSet} />, class: "d-flex", permission: "" },
            ],
        });
    }

    // ── RECURRING & DELIVERY (water_supply, distribution, monthly_service) ─
    if ((isWaterSupply || isDistribution || isMonthlyService) && (canManage || isDelivery || isAccountant)) {
        config.push({ type: "section", label: "Recurring & Delivery" });

        config.push({
            title: "Recurring / Delivery",
            name: "recurring",
            fontIcon: <FontAwesomeIcon icon={faTruckMoving} />,
            to: "/app/recurring/subscriptions",
            class: "d-flex",
            is_submenu: "true",
            permission: "",
            subPath: {
                recurringPlansSubPath:      "/app/recurring/plans",
                recurringSubsSubPath:       "/app/recurring/subscriptions",
                recurringDeliveriesSubPath: "/app/recurring/deliveries",
            },
            newRoute: [
                { title: "Recurring Plans",        to: "/app/recurring/plans",         fontIcon: <FontAwesomeIcon icon={faClipboardList} />, class: "d-flex", permission: "" },
                { title: "Customer Subscriptions", to: "/app/recurring/subscriptions", fontIcon: <FontAwesomeIcon icon={faUser} />,          class: "d-flex", permission: "" },
                { title: "Delivery Schedule",      to: "/app/recurring/deliveries",    fontIcon: <FontAwesomeIcon icon={faCalendarDays} />,  class: "d-flex", permission: "" },
            ],
        });
    }

    // ── HR & CRM ─────────────────────────────────────────────────────
    if (isSuperAdmin || isTenantOwner || isBranchManager) {
        config.push({ type: "section", label: "HR & CRM" });

        config.push({
            title: "HR",
            name: "hr",
            fontIcon: <FontAwesomeIcon icon={faIdBadge} />,
            to: "/app/hr/employees",
            class: "d-flex",
            is_submenu: "true",
            permission: "",
            subPath: { hrEmployeesSubPath: "/app/hr/employees", hrAttendanceSubPath: "/app/hr/attendance" },
            newRoute: [
                { title: "Employees", to: "/app/hr/employees",  fontIcon: <FontAwesomeIcon icon={faIdBadge} />,       class: "d-flex", permission: "" },
                { title: "Attendance",to: "/app/hr/attendance", fontIcon: <FontAwesomeIcon icon={faCalendarCheck} />, class: "d-flex", permission: "" },
            ],
        });

        config.push({
            title: "CRM",
            name: "crm",
            fontIcon: <FontAwesomeIcon icon={faHandshake} />,
            to: "/app/crm/pipeline",
            class: "d-flex",
            permission: "",
        });
    }

    // ── ATTENDANCE (POS-integrated; any tenant user who clocks in) ────
    if (!isSuperAdmin && hasPerm("attendance.view")) {
        config.push({ type: "section", label: "Attendance" });

        config.push({
            title: "Attendance",
            name: "attendance",
            fontIcon: <FontAwesomeIcon icon={faCalendarCheck} />,
            to: "/app/attendance/checkin",
            class: "d-flex",
            is_submenu: "true",
            permission: "attendance.view",
            subPath: {
                attendanceDashboardSubPath:   "/app/attendance/dashboard",
                attendanceCheckinSubPath:     "/app/attendance/checkin",
                attendanceLiveSubPath:        "/app/attendance/live",
                attendanceTasksSubPath:       "/app/attendance/tasks",
                attendanceHistorySubPath:     "/app/attendance/history",
                attendanceFaceSubPath:        "/app/attendance/biometrics/face",
                attendanceFingerprintSubPath: "/app/attendance/biometrics/fingerprint",
                attendanceDevicesSubPath:     "/app/attendance/devices",
                attendanceReportsSubPath:     "/app/attendance/reports",
                attendanceRequestsSubPath:    "/app/attendance/requests",
                attendanceSettingsSubPath:    "/app/attendance/settings",
            },
            newRoute: [
                { title: "Dashboard",            to: "/app/attendance/dashboard",              fontIcon: <FontAwesomeIcon icon={faPieChart} />,      class: "d-flex", permission: "attendance.dashboard" },
                { title: "Check In / Check Out", to: "/app/attendance/checkin",                fontIcon: <FontAwesomeIcon icon={faCalendarCheck} />, class: "d-flex", permission: "attendance.checkin" },
                { title: "Live Attendance",      to: "/app/attendance/live",                   fontIcon: <FontAwesomeIcon icon={faDisplay} />,       class: "d-flex", permission: "attendance.dashboard" },
                { title: "Tasks",                to: "/app/attendance/tasks",                  fontIcon: <FontAwesomeIcon icon={faClipboardList} />, class: "d-flex", permission: "attendance.task.view" },
                { title: "Attendance History",   to: "/app/attendance/history",                fontIcon: <FontAwesomeIcon icon={faList} />,          class: "d-flex", permission: "attendance.view" },
                { title: "Face Enrollment",      to: "/app/attendance/biometrics/face",        fontIcon: <FontAwesomeIcon icon={faUser} />,          class: "d-flex", permission: "attendance.face.view" },
                { title: "Fingerprint Enrollment", to: "/app/attendance/biometrics/fingerprint", fontIcon: <FontAwesomeIcon icon={faIdBadge} />,    class: "d-flex", permission: "attendance.fingerprint.view" },
                { title: "Devices",              to: "/app/attendance/devices",                fontIcon: <FontAwesomeIcon icon={faServer} />,        class: "d-flex", permission: "attendance.device.view" },
                { title: "Reports",              to: "/app/attendance/reports",                fontIcon: <FontAwesomeIcon icon={faChartColumn} />,   class: "d-flex", permission: "attendance.report.view" },
                { title: "Requests",             to: "/app/attendance/requests",               fontIcon: <FontAwesomeIcon icon={faPaperPlane} />,    class: "d-flex", permission: "attendance.view" },
                { title: "Settings",             to: "/app/attendance/settings",               fontIcon: <FontAwesomeIcon icon={faGear} />,          class: "d-flex", permission: "attendance.settings.view" },
            ],
        });
    }

    // ── OFFLINE & SYNC (super admin only) ─────────────────────────────
    if (isSuperAdmin) {
        config.push({ type: "section", label: "Offline & Sync" });

        config.push({
            title: "Offline Sync",
            name: "offline-sync",
            fontIcon: <FontAwesomeIcon icon={faCloud} />,
            to: "/app/offline-devices",
            class: "d-flex",
            is_submenu: "true",
            permission: "",
            subPath: { offlineDevicesSubPath: "/app/offline-devices", syncQueueSubPath: "/app/sync-queue" },
            newRoute: [
                { title: "Offline Devices", to: "/app/offline-devices", fontIcon: <FontAwesomeIcon icon={faServer} />, class: "d-flex", permission: "" },
                { title: "Sync Queue",      to: "/app/sync-queue",      fontIcon: <FontAwesomeIcon icon={faRotate} />, class: "d-flex", permission: "" },
            ],
        });
    }

    // ── FBR PAKISTAN (super admin only) ───────────────────────────────
    if (isSuperAdmin) {
        config.push({ type: "section", label: "FBR Pakistan" });

        config.push({
            title: "FBR (Pakistan)",
            name: "fbr",
            fontIcon: <FontAwesomeIcon icon={faFileShield} />,
            to: "/app/fbr-profiles",
            class: "d-flex",
            is_submenu: "true",
            permission: "",
            subPath: { fbrProfilesSubPath: "/app/fbr-profiles", fbrInvoicesSubPath: "/app/fbr-invoices" },
            newRoute: [
                { title: "FBR Profiles",  to: "/app/fbr-profiles", fontIcon: <FontAwesomeIcon icon={faFileShield} />, class: "d-flex", permission: "" },
                { title: "Invoice Queue", to: "/app/fbr-invoices",  fontIcon: <FontAwesomeIcon icon={faPaperPlane} />, class: "d-flex", permission: "" },
            ],
        });
    }

    // ── PLATFORM (super admin only) ───────────────────────────────────
    if (isSuperAdmin) {
        config.push({ type: "section", label: "Platform (SaaS)" });

        config.push({
            title: "Platform (SaaS)",
            name: "platform",
            fontIcon: <FontAwesomeIcon icon={faCrown} />,
            to: "/app/platform/dashboard",
            class: "d-flex",
            is_submenu: "true",
            permission: "",
            subPath: {
                platformDashboardSubPath:     "/app/platform/dashboard",
                platformTenantsSubPath:       "/app/platform/tenants",
                platformPlansSubPath:         "/app/platform/plans",
                platformRoleTemplatesSubPath: "/app/platform/role-templates",
                platformSubscriptionsSubPath: "/app/platform/subscriptions",
                platformPaymentsSubPath:      "/app/platform/payments",
                platformBankAccountsSubPath:  "/app/platform/bank-accounts",
                platformBillingSettingsSubPath: "/app/platform/billing-settings",
                platformShopTypesSubPath:     "/app/platform/shop-types",
                platformAuditLogsSubPath:     "/app/platform/audit-logs",
                platformBackupsSubPath:       "/app/platform/backups",
            },
            newRoute: [
                { title: "Platform Dashboard", to: "/app/platform/dashboard",     fontIcon: <FontAwesomeIcon icon={faPieChart} />,      class: "d-flex", permission: "" },
                { title: "Tenants",            to: "/app/platform/tenants",       fontIcon: <FontAwesomeIcon icon={faBuilding} />,      class: "d-flex", permission: "" },
                { title: "Plans",              to: "/app/platform/plans",         fontIcon: <FontAwesomeIcon icon={faClipboardList} />, class: "d-flex", permission: "" },
                { title: "Roles & Permissions",to: "/app/platform/role-templates",fontIcon: <FontAwesomeIcon icon={faShieldHalved} />,  class: "d-flex", permission: "" },
                { title: "Subscriptions",      to: "/app/platform/subscriptions", fontIcon: <FontAwesomeIcon icon={faMoneyCheck} />,    class: "d-flex", permission: "" },
                { title: "Payments",           to: "/app/platform/payments",      fontIcon: <FontAwesomeIcon icon={faMoneyBillTransfer} />, class: "d-flex", permission: "" },
                { title: "Bank Accounts",      to: "/app/platform/bank-accounts", fontIcon: <FontAwesomeIcon icon={faBuildingColumns} />, class: "d-flex", permission: "" },
                { title: "Billing Settings",   to: "/app/platform/billing-settings", fontIcon: <FontAwesomeIcon icon={faGear} />,        class: "d-flex", permission: "" },
                { title: "Shop Types",         to: "/app/platform/shop-types",    fontIcon: <FontAwesomeIcon icon={faShop} />,          class: "d-flex", permission: "" },
                { title: "Features",           to: "/app/platform/features",      fontIcon: <FontAwesomeIcon icon={faLayerGroup} />,    class: "d-flex", permission: "" },
                { title: "CMS Pages",          to: "/app/platform/cms-pages",     fontIcon: <FontAwesomeIcon icon={faFile} />,          class: "d-flex", permission: "" },
                { title: "Blog",               to: "/app/platform/blog",          fontIcon: <FontAwesomeIcon icon={faFileLines} />,     class: "d-flex", permission: "" },
                { title: "Audit Logs",         to: "/app/platform/audit-logs",    fontIcon: <FontAwesomeIcon icon={faFileLines} />,     class: "d-flex", permission: "" },
                { title: "Tenant Backups",     to: "/app/platform/backups",       fontIcon: <FontAwesomeIcon icon={faDatabase} />,      class: "d-flex", permission: "" },
            ],
        });

        config.push({
            title: "Notifications",
            name: "notifications",
            fontIcon: <FontAwesomeIcon icon={faBell} />,
            to: "/app/notifications",
            class: "d-flex",
            permission: "",
        });
    }

    // ── SETTINGS (always last) ────────────────────────────────────────
    if (canManage || isSuperAdmin) {
        config.push({ type: "section", label: "Settings" });

        config.push({
            title: "store.title",
            name: "store",
            fontIcon: <FontAwesomeIcon icon={faStore} />,
            to: "/app/store",
            class: "d-flex",
            permission: "",
        });

        config.push({
            title: "Shops / Counters",
            name: "shops",
            fontIcon: <FontAwesomeIcon icon={faShop} />,
            to: "/app/shops",
            class: "d-flex",
            permission: "",
        });

        config.push({
            title: "Store Features",
            name: "store-features",
            fontIcon: <FontAwesomeIcon icon={faLayerGroup} />,
            to: "/app/store-features",
            class: "d-flex",
            permission: "",
        });

        config.push({
            title: "Price Tiers",
            name: "price-tiers",
            fontIcon: <FontAwesomeIcon icon={faDollarSign} />,
            to: "/app/price-tiers",
            class: "d-flex",
            permission: "",
        });

        config.push({
            title: "Customer Displays",
            name: "customer-displays",
            fontIcon: <FontAwesomeIcon icon={faDisplay} />,
            to: "/app/customer-displays",
            class: "d-flex",
            permission: "",
        });

        // Roles, Currencies, Languages, Templates, Payment Methods, Dual Screen
        // and Settings are managed centrally by the super admin (System Settings),
        // so they are intentionally not shown to tenants here.
    }

    return config;
};

// ─────────────────────────────────────────────────────────────────────────────
// Default export — backward compatible (no filtering).
// Existing imports of asideConfig still work.
// For shop-type / permission filtering call buildAsideConfig() directly.
// ─────────────────────────────────────────────────────────────────────────────
export default buildAsideConfig("retail", ["admin"], []);
