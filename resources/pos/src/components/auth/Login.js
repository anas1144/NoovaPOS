import React, { useEffect, useMemo, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useDispatch, useSelector } from "react-redux";
import { Image } from "react-bootstrap-v5";
import * as EmailValidator from "email-validator";
import { loginAction } from "../../store/action/authAction";
import TabTitle from "../../shared/tab-title/TabTitle";
import { Tokens } from "../../constants";
import { createBrowserHistory } from "history";
import {
    getFormattedMessage,
    placeholderText,
} from "../../shared/sharedMethod";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faEye, faEyeSlash } from "@fortawesome/free-solid-svg-icons";
import { fetchFrontCms } from "../../store/action/frontCmsAction";
import Cookies from "js-cookie";

// Detect subdomain context — works for noovapos.local, noovapos.com, localhost, etc.
function detectSubdomain() {
    const hostname = window.location.hostname;
    const parts = hostname.split('.');
    // superadmin.noovapos.local  → parts = ['superadmin', 'noovapos', 'local']
    // acme.noovapos.com          → parts = ['acme', 'noovapos', 'com']
    // noovapos.local / localhost → parts.length <= 2
    if (parts.length >= 3) {
        const sub = parts[0].toLowerCase();
        if (sub === 'superadmin') return { type: 'superadmin', subdomain: 'superadmin' };
        return { type: 'tenant', subdomain: sub };
    }
    // plain domain or localhost — treat as super-admin entry point
    return { type: 'main', subdomain: null };
}

const Login = () => {
    const navigate = useNavigate();
    const dispatch = useDispatch();
    const history = createBrowserHistory();
    const { frontCms } = useSelector((state) => state);
    const [showPassword, setShowPassword] = useState(false)
    const [loading, setLoading] = useState(false);
    const token = Cookies.get("authToken");
    const subdomainCtx = useMemo(() => detectSubdomain(), []);

    const [loginInputs, setLoginInputs] = useState({
        email: "",
        password: "",
    });

    useEffect(() => {
        dispatch(fetchFrontCms());
        if (token) {
            history.push(window.location.pathname);
        }
    }, []);

    const [errors, setErrors] = useState({
        email: "",
        password: "",
    });

    const handleValidation = () => {
        let errorss = {};
        let isValid = false;
        if (!EmailValidator.validate(loginInputs["email"])) {
            if (!loginInputs["email"]) {
                errorss["email"] = getFormattedMessage(
                    "globally.input.email.validate.label"
                );
            } else {
                errorss["email"] = getFormattedMessage(
                    "globally.input.email.valid.validate.label"
                );
            }
        } else if (!loginInputs["password"]) {
            errorss["password"] = getFormattedMessage(
                "user.input.password.validate.label"
            );
        } else {
            isValid = true;
        }
        setErrors(errorss);
        setLoading(false);
        return isValid;
    };

    const prepareFormData = () => {
        const formData = new FormData();
        formData.append("email", loginInputs.email);
        formData.append("password", loginInputs.password);
        formData.append(
            "language_code",
            localStorage.getItem("updated_language")
        );
        return formData;
    };

    const onLogin = async (e) => {
        e.preventDefault();
        const valid = handleValidation();
        if (valid) {
            setLoading(true);
            dispatch(
                loginAction(prepareFormData(loginInputs), navigate, setLoading)
            );
            const dataBlank = {
                email: "",
                password: "",
            };
            setLoginInputs(dataBlank);
        }
    };

    const handleChange = (e) => {
        e.persist();
        setLoginInputs((inputs) => ({
            ...inputs,
            [e.target.name]: e.target.value,
        }));
        setErrors("");
    };

    const handleHideShowPassword = (e) => {
        setShowPassword(!showPassword);
    }

    return (
        <div className="content d-flex flex-column flex-column-fluid">
            <div className="d-flex flex-wrap flex-column-fluid">
                <div className="d-flex flex-column flex-column-fluid align-items-center justify-content-center p-4">
                    <TabTitle
                        title={placeholderText("login-form.login-btn.label")}
                    />
                    <div className="col-12 text-center align-items-center justify-content-center">
                        <a href="#" className="image">
                            <Image
                                className="logo-height image login-company-logo mb-7 mb-sm-10"
                                src={
                                    frontCms &&
                                    frontCms.value &&
                                    frontCms.value.logo
                                }
                            />
                        </a>
                    </div>
                    <div className="bg-theme-white rounded-15 shadow-md width-540 px-5 px-sm-7 py-10 mx-auto">
                        {/* Subdomain context badge */}
                        {subdomainCtx.type === 'tenant' && (
                            <div className="alert alert-info py-2 px-3 mb-4 d-flex align-items-center gap-2 rounded-3" style={{ fontSize: '0.85rem' }}>
                                <i className="bi bi-building me-1" />
                                <span>Signing in to <strong>{subdomainCtx.subdomain}</strong> workspace</span>
                            </div>
                        )}
                        {subdomainCtx.type === 'superadmin' && (
                            <div className="alert alert-warning py-2 px-3 mb-4 d-flex align-items-center gap-2 rounded-3" style={{ fontSize: '0.85rem' }}>
                                <i className="bi bi-shield-lock me-1" />
                                <span><strong>Super Admin</strong> access</span>
                            </div>
                        )}
                        <h1 className="text-dark text-center mb-7">
                            {getFormattedMessage("login-form.title")}
                        </h1>
                        <form>
                            <div className="mb-sm-7 mb-4">
                                <label className="form-label">
                                    {getFormattedMessage(
                                        "globally.input.email.label"
                                    )}{" "}
                                    :
                                </label>
                                <span className="required" />
                                <input
                                    placeholder={placeholderText(
                                        "globally.input.email.placeholder.label"
                                    )}
                                    required
                                    value={loginInputs.email}
                                    className="form-control"
                                    type="text"
                                    name="email"
                                    autoComplete="off"
                                    onChange={(e) => handleChange(e)}
                                />
                                <span className="text-danger d-block fw-400 fs-small mt-2">
                                    {errors["email"] ? errors["email"] : null}
                                </span>
                            </div>

                            <div className="mb-sm-7 mb-4">
                                <div className="d-flex justify-content-between mt-n5">
                                    <div className="d-flex justify-content-between w-100">
                                        <label className="form-label">
                                            {getFormattedMessage(
                                                "user.input.password.label"
                                            )}
                                            :
                                            <span className="required" />
                                        </label>
                                        <Link
                                            to="/forgot-password"
                                            className="link-info fs-6 text-decoration-none"
                                        >
                                            {getFormattedMessage(
                                                "login-form.forgot-password.label"
                                            )}
                                        </Link>
                                    </div>
                                </div>
                                <div className="input-group">
                                    <input
                                        className="form-control"
                                        type={showPassword ? "text" : "password"}
                                        name="password"
                                        placeholder={placeholderText(
                                            "user.input.password.placeholder.label"
                                        )}
                                        autoComplete="off"
                                        required
                                        value={loginInputs.password}
                                        onChange={(e) => handleChange(e)}
                                    />
                                    <span className="showpassword" onClick={handleHideShowPassword}>
                                        <FontAwesomeIcon
                                            icon={showPassword ? faEye : faEyeSlash}
                                            className="top-0 m-0 fa"
                                        />
                                    </span>
                                </div>
                                <span className="text-danger d-block fw-400 fs-small mt-2">
                                    {errors["password"]
                                        ? errors["password"]
                                        : null}
                                </span>
                            </div>
                            <div className="text-center">
                                <button
                                    type="submit"
                                    className="btn btn-primary w-100"
                                    onClick={(e) => onLogin(e)}
                                >
                                    {loading ? (
                                        <span className="d-block">
                                            {getFormattedMessage(
                                                "globally.loading.label"
                                            )}
                                        </span>
                                    ) : (
                                        <span>
                                            {getFormattedMessage(
                                                "login-form.login-btn.label"
                                            )}
                                        </span>
                                    )}
                                </button>
                            </div>
                            {/* Only show registration link on main domain / superadmin entry point */}
                            {(subdomainCtx.type === 'main' || subdomainCtx.type === 'superadmin') && (
                                <div className="text-center mt-4">
                                    <span className="text-muted">
                                        New to Noova POS?{" "}
                                    </span>
                                    <Link
                                        to="/register-tenant"
                                        className="text-primary fw-semibold text-decoration-none"
                                    >
                                        Create a tenant account
                                    </Link>
                                </div>
                            )}
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Login;
