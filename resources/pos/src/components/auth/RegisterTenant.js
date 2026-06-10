import React, { useEffect, useMemo, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useDispatch } from "react-redux";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faEye, faEyeSlash, faStore, faUserTie, faGlobe, faCheckCircle, faLink } from "@fortawesome/free-solid-svg-icons";
import apiConfig from "../../config/apiConfig";
import { addToast } from "../../store/action/toastAction";
import { toastType, Tokens, apiBaseURL } from "../../constants";
import TabTitle from "../../shared/tab-title/TabTitle";

// Derive base domain from current hostname so it works on localhost, .local, .com, etc.
// e.g. noovapos.local → noovapos.local
//      app.noovapos.com → noovapos.com  (strip any existing subdomain)
//      localhost → localhost
function getBaseDomain() {
    const hostname = window.location.hostname;
    const parts = hostname.split('.');
    // If 3+ parts, strip the first (existing subdomain)
    if (parts.length >= 3) {
        return parts.slice(1).join('.');
    }
    return hostname; // noovapos.local or localhost
}

const RegisterTenant = () => {
    const navigate = useNavigate();
    const dispatch = useDispatch();
    const [showPassword, setShowPassword] = useState(false);
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({
        business_name: "",
        owner_first_name: "",
        owner_last_name: "",
        owner_email: "",
        owner_phone: "",
        owner_password: "",
        subdomain: "",
        country: "",
    });
    const [errors, setErrors] = useState({});
    const [countries, setCountries] = useState([]);

    useEffect(() => {
        apiConfig
            .get(apiBaseURL.PUBLIC_COUNTRIES)
            .then((res) => setCountries(res.data?.data || []))
            .catch(() => {});
    }, []);
    const [registered, setRegistered] = useState(false);
    const [tenantUrl, setTenantUrl] = useState("");

    const baseDomain = useMemo(() => getBaseDomain(), []);
    const port = window.location.port ? `:${window.location.port}` : '';
    const protocol = window.location.protocol;

    // Build live preview URL from subdomain input
    const previewUrl = form.subdomain
        ? `${protocol}//${form.subdomain}.${baseDomain}${port}/login`
        : null;

    const onChange = (e) =>
        setForm((s) => ({ ...s, [e.target.name]: e.target.value }));

    const onSubdomainChange = (e) => {
        setForm((s) => ({
            ...s,
            subdomain: e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ""),
        }));
    };

    const validate = () => {
        const errs = {};
        if (!form.business_name) errs.business_name = "Business name is required";
        if (!form.owner_first_name) errs.owner_first_name = "First name is required";
        if (!form.owner_email) errs.owner_email = "Email is required";
        if (!form.owner_password || form.owner_password.length < 6)
            errs.owner_password = "Password must be at least 6 characters";
        if (!form.subdomain) errs.subdomain = "Subdomain is required";
        else if (!/^[a-z0-9-]+$/.test(form.subdomain))
            errs.subdomain = "Subdomain may only contain lowercase letters, numbers, and hyphens";
        setErrors(errs);
        return Object.keys(errs).length === 0;
    };

    const submit = async (e) => {
        e.preventDefault();
        if (!validate()) return;
        setLoading(true);
        try {
            const fd = new FormData();
            Object.entries(form).forEach(([k, v]) => fd.append(k, v));
            const res = await apiConfig.post("register-tenant", fd);
            const data = res.data?.data;
            if (data?.token) {
                localStorage.setItem(Tokens.ADMIN, data.token);
            }

            // Build tenant login URL and redirect
            const loginUrl = `${protocol}//${form.subdomain}.${baseDomain}${port}/login`;
            setTenantUrl(loginUrl);
            setRegistered(true);

            dispatch(
                addToast({ text: "Tenant registered! Redirecting to your workspace…" })
            );

            // Redirect after a short delay so user sees the success state
            setTimeout(() => {
                window.location.href = loginUrl;
            }, 2500);
        } catch (err) {
            const msg = err?.response?.data?.message || "Registration failed";
            dispatch(addToast({ text: msg, type: toastType.ERROR }));
        } finally {
            setLoading(false);
        }
    };

    // ── Success screen ──────────────────────────────────────────────────────────
    if (registered) {
        return (
            <div className="d-flex flex-column flex-root min-vh-100 align-items-center justify-content-center" style={{ background: 'var(--np-bg, #0b1326)' }}>
                <TabTitle title="Workspace Created" />
                <div className="text-center px-4" style={{ maxWidth: 480 }}>
                    <div className="mb-4" style={{ fontSize: '4rem', color: 'var(--np-accent, #4edea3)' }}>
                        <FontAwesomeIcon icon={faCheckCircle} />
                    </div>
                    <h2 className="fw-bold mb-2" style={{ color: 'var(--np-text, #e2e8f0)' }}>
                        Workspace ready!
                    </h2>
                    <p style={{ color: 'var(--np-text-muted, #94a3b8)' }} className="mb-4">
                        Your free trial is active. Redirecting you to your login page…
                    </p>
                    <div className="p-3 rounded-3 mb-4" style={{ background: 'rgba(78,222,163,0.10)', border: '1px solid rgba(78,222,163,0.25)' }}>
                        <FontAwesomeIcon icon={faLink} className="me-2" style={{ color: 'var(--np-accent, #4edea3)' }} />
                        <a href={tenantUrl} style={{ color: 'var(--np-accent, #4edea3)', wordBreak: 'break-all' }}>
                            {tenantUrl}
                        </a>
                    </div>
                    <a href={tenantUrl} className="btn btn-primary w-100">
                        Go to my workspace now →
                    </a>
                </div>
            </div>
        );
    }

    // ── Registration form ───────────────────────────────────────────────────────
    return (
        <div className="d-flex flex-column flex-root">
            <TabTitle title="Create your account" />
            <div className="login d-flex flex-column flex-lg-row flex-column-fluid">
                {/* Left aside */}
                <div className="login-aside d-flex flex-row-auto bgi-size-cover bgi-no-repeat p-10 p-lg-10 bg-primary text-white">
                    <div className="d-flex flex-row-fluid flex-column justify-content-between">
                        <Link to="/login" className="text-white text-decoration-none">
                            <FontAwesomeIcon icon={faStore} className="me-2" />
                            <span className="fw-bold fs-3">NoovaPOS SaaS</span>
                        </Link>
                        <div className="my-auto">
                            <h2 className="display-5 fw-bold mb-4">
                                Launch your POS in minutes
                            </h2>
                            <p className="fs-5 opacity-75">
                                Multi-tenant SaaS POS for retail, restaurant, pharmacy,
                                water-supply and more. Start your free trial — no card
                                required.
                            </p>
                            <ul className="list-unstyled mt-4 fs-6">
                                <li className="py-1">
                                    <FontAwesomeIcon icon={faGlobe} className="me-2" />
                                    Your own subdomain (e.g.&nbsp;
                                    <strong>yourname.{baseDomain}</strong>)
                                </li>
                                <li className="py-1">
                                    <FontAwesomeIcon icon={faStore} className="me-2" />
                                    Stores, shops &amp; POS counters
                                </li>
                                <li className="py-1">
                                    <FontAwesomeIcon icon={faUserTie} className="me-2" />
                                    Role-based access (owner, manager, cashier)
                                </li>
                            </ul>
                        </div>
                        <div>
                            <small className="opacity-75">
                                &copy; {new Date().getFullYear()} NoovaPOS
                            </small>
                        </div>
                    </div>
                </div>

                {/* Right form */}
                <div className="login-content flex-row-fluid d-flex flex-column p-10">
                    <div className="d-flex justify-content-end">
                        <Link to="/login" className="text-decoration-none">
                            Already have an account?{" "}
                            <span className="text-primary fw-semibold">Sign in</span>
                        </Link>
                    </div>
                    <div className="d-flex flex-column-fluid flex-center mt-5">
                        <form className="login-form w-100" onSubmit={submit} style={{ maxWidth: 640 }}>
                            <div className="text-center mb-8">
                                <h3 className="fw-bold">Create your tenant</h3>
                                <p className="text-muted">Set up your company workspace.</p>
                            </div>

                            <div className="row">
                                {/* Business Name */}
                                <div className="col-md-6 mb-4">
                                    <label className="form-label">Business Name</label>
                                    <input
                                        name="business_name"
                                        className="form-control form-control-lg"
                                        value={form.business_name}
                                        onChange={onChange}
                                        placeholder="Acme Retailers"
                                    />
                                    <span className="text-danger fs-small">{errors.business_name}</span>
                                </div>

                                {/* Country */}
                                <div className="col-md-6 mb-4">
                                    <label className="form-label">Country</label>
                                    <select
                                        name="country"
                                        className="form-control form-control-lg"
                                        value={form.country}
                                        onChange={onChange}
                                    >
                                        <option value="">-- Select country --</option>
                                        {countries.map((c) => (
                                            <option key={c.id} value={c.short_code}>
                                                {c.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Subdomain with live preview */}
                                <div className="col-md-6 mb-4">
                                    <label className="form-label">Subdomain</label>
                                    <div className="input-group">
                                        <input
                                            name="subdomain"
                                            className="form-control form-control-lg"
                                            value={form.subdomain}
                                            onChange={onSubdomainChange}
                                            placeholder="acme"
                                            autoComplete="off"
                                        />
                                        <span className="input-group-text text-muted" style={{ fontSize: '0.85rem' }}>
                                            .{baseDomain}
                                        </span>
                                    </div>
                                    <span className="text-danger fs-small">{errors.subdomain}</span>
                                    {/* Live URL preview */}
                                    {previewUrl && (
                                        <div className="mt-2 d-flex align-items-center gap-1" style={{ fontSize: '0.78rem', color: 'var(--np-accent, #4edea3)' }}>
                                            <FontAwesomeIcon icon={faLink} style={{ fontSize: '0.7rem' }} />
                                            <span style={{ wordBreak: 'break-all' }}>{previewUrl}</span>
                                        </div>
                                    )}
                                </div>

                                {/* Owner First Name */}
                                <div className="col-md-6 mb-4">
                                    <label className="form-label">Owner First Name</label>
                                    <input
                                        name="owner_first_name"
                                        className="form-control form-control-lg"
                                        value={form.owner_first_name}
                                        onChange={onChange}
                                    />
                                    <span className="text-danger fs-small">{errors.owner_first_name}</span>
                                </div>

                                {/* Owner Last Name */}
                                <div className="col-md-6 mb-4">
                                    <label className="form-label">Owner Last Name</label>
                                    <input
                                        name="owner_last_name"
                                        className="form-control form-control-lg"
                                        value={form.owner_last_name}
                                        onChange={onChange}
                                    />
                                </div>

                                {/* Email */}
                                <div className="col-md-6 mb-4">
                                    <label className="form-label">Email</label>
                                    <input
                                        type="email"
                                        name="owner_email"
                                        className="form-control form-control-lg"
                                        value={form.owner_email}
                                        onChange={onChange}
                                    />
                                    <span className="text-danger fs-small">{errors.owner_email}</span>
                                </div>

                                {/* Phone */}
                                <div className="col-md-6 mb-4">
                                    <label className="form-label">Phone</label>
                                    <input
                                        name="owner_phone"
                                        className="form-control form-control-lg"
                                        value={form.owner_phone}
                                        onChange={onChange}
                                    />
                                </div>

                                {/* Password */}
                                <div className="col-md-12 mb-4">
                                    <label className="form-label">Password</label>
                                    <div className="position-relative">
                                        <input
                                            type={showPassword ? "text" : "password"}
                                            name="owner_password"
                                            className="form-control form-control-lg"
                                            value={form.owner_password}
                                            onChange={onChange}
                                        />
                                        <span
                                            className="position-absolute end-0 top-50 translate-middle-y me-3 cursor-pointer"
                                            onClick={() => setShowPassword((s) => !s)}
                                        >
                                            <FontAwesomeIcon icon={showPassword ? faEyeSlash : faEye} />
                                        </span>
                                    </div>
                                    <span className="text-danger fs-small">{errors.owner_password}</span>
                                </div>
                            </div>

                            <div className="d-grid mt-3">
                                <button
                                    type="submit"
                                    disabled={loading}
                                    className="btn btn-primary btn-lg fw-semibold"
                                >
                                    {loading ? "Creating workspace…" : "Create my tenant"}
                                </button>
                            </div>
                            <p className="text-muted small mt-4 text-center">
                                By signing up you agree to our{" "}
                                <a href="#terms" className="text-primary text-decoration-none">terms</a>
                                {" "}and{" "}
                                <a href="#privacy" className="text-primary text-decoration-none">privacy policy</a>.
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default RegisterTenant;
