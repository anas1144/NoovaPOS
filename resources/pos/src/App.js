import React, { useEffect, useState } from "react";
import { Route, useLocation, Navigate, Routes, useNavigate } from "react-router-dom";
import "../../pos/src/assets/sass/style.react.scss";
import { useDispatch, useSelector } from "react-redux";
import { IntlProvider } from "react-intl";
import { settingsKey, Tokens } from "./constants";
import Toasts from "./shared/toast/Toasts";
import { fetchFrontSetting } from "./store/action/frontSettingAction";
import { fetchConfig } from "./store/action/configAction";
import { addRTLSupport } from "./shared/sharedMethod";
import Login from "./components/auth/Login";
import ResetPassword from "./components/auth/ResetPassword";
import ForgotPassword from "./components/auth/ForgotPassword";
import RegisterTenant from "./components/auth/RegisterTenant";
import AdminApp from "./AdminApp";
import { getFiles } from "./locales/index";
import Cookies from "js-cookie";

const cssFiles = import.meta.glob("./assets/css/*.css");

function App() {
    //do not remove updateLanguag
    const dispatch = useDispatch();
    const { updateLanguage } = useSelector((state) => state);
    const location = useLocation();
    const token = Cookies.get("authToken");
    const navigate = useNavigate()
    const updatedLanguage = localStorage.getItem(Tokens.UPDATED_LANGUAGE);
    const { selectedLanguage, config, language } = useSelector(
        (state) => state
    );
    const [allLocales, setAllLocales] = useState({});
    const [messages, setMessages] = useState(() => getFiles()['en'] || {});
    const [userEditedMessage, setUserEditedMessage] = useState({});
    const updateLanguag =
        allLocales[updatedLanguage ? updatedLanguage : selectedLanguage];
    const [languageData, setLanguageData] = useState([]);

    const permissionMappings = {
        manage_dashboard: "/app/dashboard",
        manage_roles: "/app/roles",
        manage_brands: "/app/brands",
        manage_warehouses: "/app/warehouses",
        manage_units: "/app/units",
        manage_product_categories: "/app/product-categories",
        manage_products: "/app/products",
        manage_suppliers: "/app/suppliers",
        manage_customers: "/app/customers",
        manage_users: "/app/users",
        manage_purchase: "/app/purchases",
        manage_pos_screen: "/app/pos",
        manage_sale: "/app/sales",
        manage_print_barcode: "/app/print/barcode",
        manage_adjustments: "/app/adjustments",
        manage_quotations: "/app/quotations",
        manage_transfers: "/app/transfers",
        manage_expenses: "/app/expenses",
        manage_currency: "/app/currencies",
        manage_variations: "/app/variations",
        manage_expense_categories: "/app/expense-categories",
        manage_setting: "/app/settings",
        manage_purchase_return: "/app/purchase-return",
        manage_sale_return: "/app/sale-return",
        manage_report: "/app/report/report-warehouse",
        edit_reports: "/app/report/report-warehouse",
        manage_reports: "/app/report/report-warehouse",
        manage_language: "/app/languages",
        manage_sms_apis: "/app/sms-api",
        edit_sms_apis: "/app/sms-api",
        view_sms_apis: "/app/sms-api",
        manage_sms_templates: "/app/sms-templates",
        manage_email_templates: "/app/email-templates",
        manage_store: "/app/store",
    };

    const mapPermissionToRoute = (permission) => {
        const permissionKey = permission.toLowerCase();
        if (permissionMappings.hasOwnProperty(permissionKey)) {
            return permissionMappings[permissionKey];
        } else {
            const entity = permissionKey.split("_").slice(1).join("-");
            return `/app/${entity}`;
        }
    };

    const [mappedRoutes, setMappedRoutes] = useState([]);
    const [redirectTo, setRedirectTo] = useState("/app/dashboard");
    useEffect(() => {
        setMappedRoutes(config.map(mapPermissionToRoute));
    }, [config]);
    useEffect(() => {
        if (mappedRoutes && mappedRoutes.length > 0) {
            if (config.includes("manage_dashboard")) {
                setRedirectTo("/app/dashboard");
            } else if (config.includes("manage_sale")) {
                setRedirectTo("/app/sales");
            }
             else if(mappedRoutes.length === 1 && config.includes("manage_pos_screen")){
                setRedirectTo("/app/pos");
            }
             else {
                const currentPath = window.location.hash;
                const targetPath = mappedRoutes[0];
                if (currentPath === `#${targetPath}`) {
                    setRedirectTo(mappedRoutes[1]);
                } else {
                    setRedirectTo(mappedRoutes[0]);
                }
            }
        } else {
            setRedirectTo("/app/dashboard");
        }
    }, [mappedRoutes]);

    useEffect(() => {
        const getData = getFiles();
        setAllLocales(getData);
    }, [language, updateLanguage?.lang_json_array]);

    useEffect(() => {
        if (updateLanguage?.iso_code === updatedLanguage && languageData) {
            setUserEditedMessage(updateLanguage?.lang_json_array);
        }
    }, [language, languageData, updateLanguage?.lang_json_array]);

    // updated language handling
    // Strategy: static en.json is always the BASE (contains all keys including
    // new sidebar items). API / DB lang_json_array is merged ON TOP so
    // user-customised translations still win, but missing keys fall back to
    // the static file instead of triggering MISSING_TRANSLATION errors.
    useEffect(() => {
        const staticBase = allLocales["en"] || {};

        const mergeWithBase = (overrides) => {
            // staticBase provides all keys; overrides replace only what the DB has
            return { ...staticBase, ...(overrides || {}) };
        };

        if (Object.values(userEditedMessage).length !== 0) {
            setMessages(mergeWithBase(userEditedMessage));
        } else {
            if (updateLanguage?.iso_code === updatedLanguage) {
                setMessages(mergeWithBase(updateLanguage?.lang_json_array));
            } else {
                if (!updateLanguag) {
                    setMessages(staticBase);
                } else {
                    setMessages(mergeWithBase(updateLanguag));
                }
            }
        }
    }, [userEditedMessage, allLocales, updateLanguage?.lang_json_array]);

    useEffect(() => {
        selectCSS();
    }, [location.pathname]);

    // Public paths that must never force-redirect to /login
    const publicPaths = ["/", "/register-tenant", "/login"];

    useEffect(() => {
        const currentPath = location.pathname;
        if (token) {
            dispatch(fetchConfig());
            dispatch(fetchFrontSetting());
        } else if (
            !publicPaths.some((p) => currentPath === p || currentPath.startsWith(p + "/")) &&
            !currentPath.includes("/forgot-password") &&
            !currentPath.includes("/reset-password")
        ) {
            navigate("/login");
        }
    }, []);

    const selectCSS = () => {
        const files =
            updatedLanguage === "ar"
                ? [
                    "./assets/css/custom.rtl.css",
                    "./assets/css/style.rtl.css",
                    "./assets/css/frontend.rtl.css",
                ]
                : [
                    "./assets/css/custom.css",
                    "./assets/css/style.css",
                    "./assets/css/frontend.css",
                ];

        files.forEach((file) => cssFiles[file]?.());
    };

    useEffect(() => {
        addRTLSupport(updatedLanguage ? updatedLanguage : selectedLanguage);
    }, [updatedLanguage, selectedLanguage]);

    return (
        <div className="d-flex flex-column flex-root">
            <IntlProvider
                locale={settingsKey.DEFAULT_LOCALE}
                messages={messages}
                onError={(err) => {
                    // Silently use the message id as fallback for missing keys.
                    // This prevents the console flood and React error overlay.
                    if (err.code === 'MISSING_TRANSLATION') return;
                    console.error(err);
                }}
            >
                <Routes>
                    <Route path="/login" element={<Login />} />
                    <Route path="/register-tenant" element={<RegisterTenant />} />
                    <Route
                        path="reset-password/:token/:email"
                        element={<ResetPassword />}
                    />
                    <Route
                        path="forgot-password"
                        element={<ForgotPassword />}
                    />
                    <Route
                        path="app/*"
                        element={<AdminApp config={config} />}
                    />
                    <Route
                        path="/"
                        element={
                            <Navigate
                                replace
                                to={token ? (redirectTo || "/app/dashboard") : "/login"}
                            />
                        }
                    />
                    {/* Unknown routes: go to login (not back to /, which is the Blade landing) */}
                    <Route path="*" element={<Navigate replace to={"/login"} />} />
                </Routes>
                <Toasts
                    language={
                        updatedLanguage ? updatedLanguage : selectedLanguage
                    }
                />
            </IntlProvider>
        </div>
    );
}

export default App;
