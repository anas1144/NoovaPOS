import React, { useEffect, useMemo, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { Modal, Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import {
    fetchRoleTemplates,
    fetchPermissionCatalog,
    addRoleTemplate,
    editRoleTemplate,
    deleteRoleTemplate,
} from "../../store/action/platformAction";

const emptyForm = {
    display_name: "",
    shop_type: "retail",
    permissions: [],
};

const RoleTemplateModal = ({ show, onHide, editData }) => {
    const dispatch = useDispatch();
    const catalog = useSelector(
        (s) => s.platform?.permissionCatalog || { modules: [], shop_types: [] }
    );
    const [form, setForm] = useState(emptyForm);
    const [errors, setErrors] = useState({});

    useEffect(() => {
        if (show) dispatch(fetchPermissionCatalog());
    }, [show]);

    useEffect(() => {
        if (editData) {
            setForm({
                display_name: editData.display_name || "",
                shop_type: editData.shop_type || "retail",
                permissions: Array.isArray(editData.permissions)
                    ? editData.permissions
                    : [],
            });
        } else {
            setForm(emptyForm);
        }
        setErrors({});
    }, [editData, show]);

    const togglePermission = (name) => {
        setForm((f) => {
            const has = f.permissions.includes(name);
            return {
                ...f,
                permissions: has
                    ? f.permissions.filter((p) => p !== name)
                    : [...f.permissions, name],
            };
        });
    };

    const toggleModule = (mod, checked) => {
        const names = [mod.name, ...mod.children.map((c) => c.name)];
        setForm((f) => {
            const set = new Set(f.permissions);
            names.forEach((n) => (checked ? set.add(n) : set.delete(n)));
            return { ...f, permissions: Array.from(set) };
        });
    };

    const validate = () => {
        const e = {};
        if (!form.display_name.trim()) e.display_name = "Name is required";
        if (!form.shop_type) e.shop_type = "Shop type is required";
        if (!form.permissions.length)
            e.permissions = "Select at least one permission";
        setErrors(e);
        return Object.keys(e).length === 0;
    };

    const submit = () => {
        if (!validate()) return;
        const payload = {
            name: form.display_name,
            display_name: form.display_name,
            shop_type: form.shop_type,
            permissions: form.permissions,
        };
        if (editData) {
            dispatch(editRoleTemplate(editData.id, payload, onHide));
        } else {
            dispatch(addRoleTemplate(payload, onHide));
        }
    };

    return (
        <Modal show={show} onHide={onHide} size="lg" scrollable>
            <Modal.Header closeButton>
                <Modal.Title>
                    {editData ? "Edit Role Template" : "New Role Template"}
                </Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="row">
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Role Name</label>
                        <input
                            type="text"
                            className="form-control"
                            value={form.display_name}
                            placeholder="e.g. Cashier, Branch Manager"
                            onChange={(e) =>
                                setForm((f) => ({
                                    ...f,
                                    display_name: e.target.value,
                                }))
                            }
                        />
                        <span className="text-danger fs-small">
                            {errors.display_name}
                        </span>
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Shop Type</label>
                        <select
                            className="form-control"
                            value={form.shop_type}
                            onChange={(e) =>
                                setForm((f) => ({
                                    ...f,
                                    shop_type: e.target.value,
                                }))
                            }
                        >
                            {(catalog.shop_types || []).map((t) => (
                                <option key={t.value} value={t.value}>
                                    {t.label}
                                </option>
                            ))}
                        </select>
                        <span className="text-danger fs-small">
                            {errors.shop_type}
                        </span>
                    </div>
                </div>

                <label className="form-label fw-bold">Permissions</label>
                <span className="text-danger fs-small d-block mb-2">
                    {errors.permissions}
                </span>

                <div className="row">
                    {(catalog.modules || []).map((mod) => {
                        const allChildNames = [
                            mod.name,
                            ...mod.children.map((c) => c.name),
                        ];
                        const allChecked = allChildNames.every((n) =>
                            form.permissions.includes(n)
                        );
                        return (
                            <div className="col-md-6 mb-3" key={mod.id}>
                                <div className="border rounded p-2 h-100">
                                    <label className="form-check d-flex align-items-center gap-2 mb-2 fw-semibold text-capitalize">
                                        <input
                                            type="checkbox"
                                            className="form-check-input m-0"
                                            checked={allChecked}
                                            onChange={(e) =>
                                                toggleModule(
                                                    mod,
                                                    e.target.checked
                                                )
                                            }
                                        />
                                        {mod.display_name ||
                                            mod.module.replace(/_/g, " ")}
                                    </label>
                                    <div className="d-flex flex-wrap gap-3 ps-2">
                                        {mod.children.map((c) => (
                                            <label
                                                key={c.id}
                                                className="form-check d-flex align-items-center gap-1 mb-0 text-capitalize"
                                            >
                                                <input
                                                    type="checkbox"
                                                    className="form-check-input m-0"
                                                    checked={form.permissions.includes(
                                                        c.name
                                                    )}
                                                    onChange={() =>
                                                        togglePermission(c.name)
                                                    }
                                                />
                                                {c.name.split("_")[0]}
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </Modal.Body>
            <Modal.Footer>
                <Button variant="primary" onClick={submit}>
                    {editData ? "Update" : "Create"}
                </Button>
                <Button variant="secondary" onClick={onHide}>
                    Cancel
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

const PlatformRoleTemplates = () => {
    const dispatch = useDispatch();
    const templates = useSelector((s) => s.platform?.roleTemplates || []);
    const isLoading = useSelector((s) => s.isLoading);
    const [show, setShow] = useState(false);
    const [editData, setEditData] = useState(null);

    useEffect(() => {
        dispatch(fetchRoleTemplates());
    }, []);

    const openCreate = () => {
        setEditData(null);
        setShow(true);
    };
    const openEdit = (row) => {
        setEditData(row);
        setShow(true);
    };

    const columns = useMemo(
        () => [
            { name: "Role", selector: (r) => r.display_name },
            {
                name: "Shop Type",
                selector: (r) => r.shop_type,
                cell: (r) => (
                    <span className="badge bg-light-primary text-capitalize">
                        {(r.shop_type || "").replace(/_/g, " ")}
                    </span>
                ),
            },
            {
                name: "Permissions",
                selector: (r) => (r.permissions || []).length,
                cell: (r) => (
                    <span className="badge bg-light-info">
                        {(r.permissions || []).length}
                    </span>
                ),
            },
            {
                name: "Type",
                cell: (r) =>
                    r.is_system ? (
                        <span className="badge bg-light-secondary">System</span>
                    ) : (
                        <span className="badge bg-light-success">Custom</span>
                    ),
            },
            {
                name: "Action",
                right: true,
                cell: (r) => (
                    <div className="d-flex gap-2">
                        <button
                            className="btn btn-sm btn-outline-primary"
                            onClick={() => openEdit(r)}
                        >
                            Edit
                        </button>
                        {!r.is_system && (
                            <button
                                className="btn btn-sm btn-outline-danger"
                                onClick={() =>
                                    dispatch(deleteRoleTemplate(r.id))
                                }
                            >
                                Delete
                            </button>
                        )}
                    </div>
                ),
            },
        ],
        []
    );

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Role Templates" />
            <ReactDataTable
                columns={columns}
                items={templates}
                isLoading={isLoading}
                onChange={() => dispatch(fetchRoleTemplates())}
                pagination={false}
                isShowSearch
                AddButton={
                    <div className="text-end">
                        <Button onClick={openCreate}>+ New Role Template</Button>
                    </div>
                }
            />
            <RoleTemplateModal
                show={show}
                editData={editData}
                onHide={() => setShow(false)}
            />
        </MasterLayout>
    );
};

export default PlatformRoleTemplates;
