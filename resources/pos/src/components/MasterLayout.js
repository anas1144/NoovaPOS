import React, { useEffect, useState } from "react";
import { connect } from "react-redux";
import { useNavigate } from "react-router-dom";
import AsideDefault from "./sidebar/asideDefault";
import Header from "./header/Header";
import Footer from "./footer/Footer";
import AsideTopSubMenuItem from "./sidebar/asideTopSubMenuItem";
import { Tokens } from "../constants";
import { buildAsideConfig } from "../config/asideConfig";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faBars } from "@fortawesome/free-solid-svg-icons";
import { fetchConfig } from "../store/action/configAction";

const MasterLayout = (props) => {
    const {
        children,
        newPermissions,
        frontSetting,
        fetchConfig,
        config,
        allConfigData,
        userRoles,
        shopType,
    } = props;
    const [isResponsiveMenu, setIsResponsiveMenu] = useState(false);
    const [isMenuCollapse, setIsMenuCollapse] = useState(false);
    // Pass actual user roles so platform_super_admin sees Tenant management etc.
    const newRoutes = config && prepareRoutes(config, userRoles, shopType);
    const token = localStorage.getItem(Tokens.ADMIN);
    const navigate = useNavigate();

    useEffect(() => {
        if (token) {
            fetchConfig();
        } else {
            // BrowserRouter-compatible redirect — no more hash refs
            navigate('/login', { replace: true });
        }
    }, []);

    const menuClick = () => {
        setIsResponsiveMenu(!isResponsiveMenu);
    };

    const menuIconClick = () => {
        setIsMenuCollapse(!isMenuCollapse);
    };

    return (
        <div className="d-flex flex-row flex-column-fluid">
            <AsideDefault
                asideConfig={newRoutes}
                frontSetting={frontSetting}
                isResponsiveMenu={isResponsiveMenu}
                menuClick={menuClick}
                menuIconClick={menuIconClick}
                isMenuCollapse={isMenuCollapse}
            />
            <div
                className={`${
                    isMenuCollapse === true ? "wrapper-res" : "wrapper"
                } d-flex flex-column flex-row-fluid`}
            >
                <div className="d-flex align-items-stretch justify-content-between header">
                    <div className="container-fluid d-flex align-items-stretch justify-content-xxl-between flex-grow-1">
                        <button
                            type="button"
                            className="btn d-flex align-items-center d-xl-none px-0"
                            title="Show aside menu"
                            onClick={menuClick}
                        >
                            <FontAwesomeIcon icon={faBars} className="fs-1" />
                        </button>
                        <AsideTopSubMenuItem
                            asideConfig={newRoutes || []}
                            isMenuCollapse={isMenuCollapse}
                        />
                        <Header newRoutes={newRoutes} />
                    </div>
                </div>
                <div className="content d-flex flex-column flex-column-fluid pt-7">
                    <div className="d-flex flex-column-fluid">
                        <div className="container-fluid">{children}</div>
                    </div>
                </div>
                <div className="container-fluid">
                    <Footer
                        allConfigData={allConfigData}
                        frontSetting={frontSetting}
                    />
                </div>
            </div>
        </div>
    );
};

const getRouteWithSubMenu = (route, permissions) => {
    const subRoutes = route.subMenu
        ? route.subMenu.filter(
              (item) =>
                  permissions.indexOf(item.permission) !== -1 ||
                  item.permission === ""
          )
        : null;
    const newSubRoutes = subRoutes ? { ...route, newRoute: subRoutes } : route;
    return newSubRoutes;
};

const prepareRoutes = (config, userRoles = [], shopType = "retail") => {
    const permissions = config;

    // Build the sidebar with the actual logged-in user's roles so
    // platform_super_admin sees the Platform/Tenant section,
    // cashier sees only POS items, etc.
    const fullMenu = buildAsideConfig(shopType, userRoles, permissions);

    let filterRoutes = [];
    fullMenu.forEach((route) => {
        // Skip section dividers — they have no `to` or `permission` and the
        // sidebar renders them as <Link to={undefined}> which throws.
        // Section labels are decorative only; the sidebar does not render them.
        if (route.type === "section") return;

        const permissionsRoute = getRouteWithSubMenu(route, permissions);
        if (
            (permissions && permissions.indexOf(route.permission) !== -1) ||
            route.permission === "" ||
            permissionsRoute.newRoute?.length
        ) {
            filterRoutes.push(permissionsRoute);
        }
    });
    return filterRoutes;
};

const mapStateToProps = (state) => {
    const newPermissions = [];
    const { permissions, settings, frontSetting, config, allConfigData, loginUser } = state;

    if (permissions) {
        permissions.forEach((permission) =>
            newPermissions.push(permission.attributes.name)
        );
    }

    // Normalise roles to always be an array
    const userRoles = loginUser?.roles
        ? (Array.isArray(loginUser.roles) ? loginUser.roles : [loginUser.roles])
        : [];

    const shopType = loginUser?.shop_type ?? "retail";

    return { newPermissions, settings, frontSetting, config, allConfigData, loginUser, userRoles, shopType };
};

export default connect(mapStateToProps, { fetchConfig })(MasterLayout);
