import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useDispatch } from "react-redux";
import MasterLayout from "../MasterLayout";
import HeaderTitle from "../header/HeaderTitle";
import { getFormattedMessage } from "../../shared/sharedMethod";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addRoleFromTemplate } from "../../store/action/roleAction";
import { addToast } from "../../store/action/toastAction";

/**
 * Tenant role creation is template-based: the tenant owner picks a role template
 * (defined by the super admin per shop type) and gives it a name. Raw permission
 * editing is intentionally not available to tenants.
 */
const CreateRole = () => {
    const dispatch = useDispatch();
    const navigate = useNavigate();

    const [templates, setTemplates] = useState([]);
    const [form, setForm] = useState({ name: "", template_id: "" });
    const [errors, setErrors] = useState({});

    useEffect(() => {
        apiConfig
            .get(apiBaseURL.ROLE_AVAILABLE_TEMPLATES)
            .then((res) => setTemplates(res.data?.data || []))
            .catch(({ response }) =>
                dispatch(
                    addToast({
                        text:
                            response?.data?.message ||
                            "Failed to load role templates",
                        type: toastType.ERROR,
                    })
                )
            );
    }, []);

    const selected = templates.find(
        (t) => String(t.id) === String(form.template_id)
    );

    const validate = () => {
        const e = {};
        if (!form.name.trim()) e.name = "Role name is required";
        if (!form.template_id) e.template_id = "Please choose a role template";
        setErrors(e);
        return Object.keys(e).length === 0;
    };

    const onSubmit = () => {
        if (!validate()) return;
        dispatch(
            addRoleFromTemplate(
                { name: form.name, template_id: form.template_id },
                navigate
            )
        );
    };

    return (
        <MasterLayout>
            <TopProgressBar />
            <HeaderTitle
                title={getFormattedMessage("role.create.title")}
                to="/app/roles"
            />
            <div className="card">
                <div className="card-body">
                    {templates.length === 0 ? (
                        <div className="text-muted">
                            No role templates are available for your shop types
                            yet. Ask the platform administrator to publish role
                            templates.
                        </div>
                    ) : (
                        <div className="row">
                            <div className="col-md-6 mb-3">
                                <label className="form-label">
                                    Role Name
                                    <span className="required" />
                                </label>
                                <input
                                    type="text"
                                    className="form-control"
                                    value={form.name}
                                    placeholder="e.g. Front Desk Cashier"
                                    onChange={(e) =>
                                        setForm((f) => ({
                                            ...f,
                                            name: e.target.value,
                                        }))
                                    }
                                />
                                <span className="text-danger d-block fs-small mt-1">
                                    {errors.name}
                                </span>
                            </div>
                            <div className="col-md-6 mb-3">
                                <label className="form-label">
                                    Role Template
                                    <span className="required" />
                                </label>
                                <select
                                    className="form-control"
                                    value={form.template_id}
                                    onChange={(e) =>
                                        setForm((f) => ({
                                            ...f,
                                            template_id: e.target.value,
                                        }))
                                    }
                                >
                                    <option value="">
                                        -- Select a template --
                                    </option>
                                    {templates.map((t) => (
                                        <option key={t.id} value={t.id}>
                                            {t.display_name} (
                                            {(t.shop_type || "").replace(
                                                /_/g,
                                                " "
                                            )}
                                            )
                                        </option>
                                    ))}
                                </select>
                                <span className="text-danger d-block fs-small mt-1">
                                    {errors.template_id}
                                </span>
                            </div>

                            {selected && (
                                <div className="col-md-12 mb-3">
                                    <div className="alert alert-light-primary">
                                        This role will be created with{" "}
                                        <strong>
                                            {selected.permissions_count}
                                        </strong>{" "}
                                        permission(s) from the{" "}
                                        <strong>{selected.display_name}</strong>{" "}
                                        template. Permissions are managed by the
                                        platform administrator.
                                    </div>
                                </div>
                            )}

                            <div className="col-md-12">
                                <button
                                    className="btn btn-primary me-2"
                                    type="button"
                                    onClick={onSubmit}
                                >
                                    {getFormattedMessage("globally.save-btn")}
                                </button>
                                <button
                                    className="btn btn-secondary"
                                    type="button"
                                    onClick={() => navigate("/app/roles")}
                                >
                                    {getFormattedMessage("globally.cancel-btn")}
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </MasterLayout>
    );
};

export default CreateRole;
