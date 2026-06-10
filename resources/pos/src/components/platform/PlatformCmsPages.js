import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Modal, Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const emptyPage = {
    title: "", type: "custom", shop_type: "", show_in_menu: false, menu_order: 0,
    seo_title: "", seo_description: "", seo_keywords: "", status: true, sections: [],
};

const PageModal = ({ show, onHide, editId, onSaved }) => {
    const dispatch = useDispatch();
    const [form, setForm] = useState(emptyPage);

    useEffect(() => {
        if (!show) return;
        if (editId) {
            apiConfig.get(apiBaseURL.PLATFORM_CMS_PAGES + "/" + editId)
                .then((res) => {
                    const d = res.data?.data || {};
                    setForm({
                        ...emptyPage, ...d,
                        sections: (d.sections || []).map((s) => ({
                            type: s.type || "html",
                            content: s.content || "",
                            visible_global: s.visible_global !== false,
                            visible_countries: (s.visible_countries || []).join(", "),
                            status: s.status !== false,
                        })),
                    });
                })
                .catch(() => {});
        } else {
            setForm(emptyPage);
        }
    }, [editId, show]);

    const setF = (k) => (e) =>
        setForm((f) => ({ ...f, [k]: e.target.type === "checkbox" ? e.target.checked : e.target.value }));

    const addSection = () =>
        setForm((f) => ({ ...f, sections: [...f.sections, { type: "html", content: "", visible_global: true, visible_countries: "", status: true }] }));
    const setSection = (i, k, v) =>
        setForm((f) => ({ ...f, sections: f.sections.map((s, idx) => (idx === i ? { ...s, [k]: v } : s)) }));
    const removeSection = (i) =>
        setForm((f) => ({ ...f, sections: f.sections.filter((_, idx) => idx !== i) }));

    const submit = () => {
        if (!form.title) {
            dispatch(addToast({ text: "Title is required", type: toastType.ERROR }));
            return;
        }
        const payload = {
            ...form,
            sections: form.sections.map((s) => ({
                type: s.type,
                content: s.content,
                visible_global: !!s.visible_global,
                visible_countries: (s.visible_countries || "")
                    .split(",").map((x) => x.trim().toUpperCase()).filter(Boolean),
                status: !!s.status,
            })),
        };
        const url = editId ? apiBaseURL.PLATFORM_CMS_PAGES + "/" + editId : apiBaseURL.PLATFORM_CMS_PAGES;
        apiConfig.post(url, payload)
            .then(() => { dispatch(addToast({ text: "Page saved." })); onSaved(); onHide(); })
            .catch(({ response }) => dispatch(addToast({ text: response?.data?.message || "Failed to save", type: toastType.ERROR })));
    };

    return (
        <Modal show={show} onHide={onHide} size="lg" scrollable>
            <Modal.Header closeButton>
                <Modal.Title>{editId ? "Edit Page" : "New Page"}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="row">
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Title</label>
                        <input className="form-control" value={form.title} onChange={setF("title")} />
                    </div>
                    <div className="col-md-3 mb-3">
                        <label className="form-label">Type</label>
                        <select className="form-control" value={form.type} onChange={setF("type")}>
                            <option value="custom">Custom</option>
                            <option value="landing">Landing</option>
                            <option value="shop_type">Shop type</option>
                            <option value="fbr">FBR</option>
                        </select>
                    </div>
                    <div className="col-md-3 mb-3">
                        <label className="form-label">Shop type (if any)</label>
                        <input className="form-control" value={form.shop_type || ""} onChange={setF("shop_type")} placeholder="retail…" />
                    </div>
                    <div className="col-md-3 mb-3 d-flex align-items-end">
                        <label className="form-check form-switch">
                            <input type="checkbox" className="form-check-input" checked={!!form.show_in_menu} onChange={setF("show_in_menu")} />
                            <span className="ms-2">Show in menu</span>
                        </label>
                    </div>
                    <div className="col-md-3 mb-3">
                        <label className="form-label">Menu order</label>
                        <input type="number" className="form-control" value={form.menu_order} onChange={setF("menu_order")} />
                    </div>
                    <div className="col-md-12 mb-2"><h6 className="text-muted">SEO</h6></div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">SEO title</label>
                        <input className="form-control" value={form.seo_title || ""} onChange={setF("seo_title")} />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">SEO keywords</label>
                        <input className="form-control" value={form.seo_keywords || ""} onChange={setF("seo_keywords")} />
                    </div>
                    <div className="col-md-12 mb-3">
                        <label className="form-label">SEO description</label>
                        <textarea className="form-control" rows={2} value={form.seo_description || ""} onChange={setF("seo_description")} />
                    </div>
                </div>

                <div className="d-flex justify-content-between align-items-center">
                    <h6 className="mb-0">Sections</h6>
                    <Button size="sm" variant="outline-primary" onClick={addSection}>+ Add section</Button>
                </div>
                {form.sections.map((s, i) => (
                    <div className="border rounded p-2 mt-2" key={i}>
                        <div className="row">
                            <div className="col-md-3 mb-2">
                                <input className="form-control form-control-sm" placeholder="type (hero, html…)"
                                    value={s.type} onChange={(e) => setSection(i, "type", e.target.value)} />
                            </div>
                            <div className="col-md-4 mb-2">
                                <input className="form-control form-control-sm" placeholder="Countries (PK, US) — blank = all"
                                    value={s.visible_countries} onChange={(e) => setSection(i, "visible_countries", e.target.value)} />
                            </div>
                            <div className="col-md-3 mb-2">
                                <label className="form-check form-switch mt-1">
                                    <input type="checkbox" className="form-check-input" checked={!!s.visible_global}
                                        onChange={(e) => setSection(i, "visible_global", e.target.checked)} />
                                    <span className="ms-2">Visible</span>
                                </label>
                            </div>
                            <div className="col-md-2 mb-2 text-end">
                                <button className="btn btn-sm btn-outline-danger" onClick={() => removeSection(i)}>✕</button>
                            </div>
                            <div className="col-md-12">
                                <textarea className="form-control form-control-sm" rows={3} placeholder="Content (HTML or JSON)"
                                    value={s.content} onChange={(e) => setSection(i, "content", e.target.value)} />
                            </div>
                        </div>
                    </div>
                ))}
            </Modal.Body>
            <Modal.Footer>
                <Button variant="primary" onClick={submit}>{editId ? "Update" : "Create"}</Button>
                <Button variant="secondary" onClick={onHide}>Cancel</Button>
            </Modal.Footer>
        </Modal>
    );
};

const PlatformCmsPages = () => {
    const dispatch = useDispatch();
    const [pages, setPages] = useState([]);
    const [loading, setLoading] = useState(false);
    const [show, setShow] = useState(false);
    const [editId, setEditId] = useState(null);

    const load = () => {
        setLoading(true);
        apiConfig.get(apiBaseURL.PLATFORM_CMS_PAGES)
            .then((res) => setPages(res.data?.data || []))
            .catch(() => {})
            .finally(() => setLoading(false));
    };
    useEffect(() => { load(); }, []);

    const remove = (row) => {
        apiConfig.delete(apiBaseURL.PLATFORM_CMS_PAGES + "/" + row.id)
            .then(() => { dispatch(addToast({ text: "Page deleted." })); load(); })
            .catch(({ response }) => dispatch(addToast({ text: response?.data?.message || "Failed", type: toastType.ERROR })));
    };

    const columns = [
        { name: "Title", selector: (r) => r.title },
        { name: "Slug", selector: (r) => r.slug },
        { name: "Type", selector: (r) => r.type },
        { name: "In menu", cell: (r) => (r.show_in_menu ? "Yes" : "—") },
        { name: "Status", cell: (r) => (r.status ? <span className="badge bg-light-success">Active</span> : <span className="badge bg-light-secondary">Off</span>) },
        {
            name: "Action", right: true,
            cell: (r) => (
                <div className="d-flex gap-2">
                    <button className="btn btn-sm btn-outline-primary" onClick={() => { setEditId(r.id); setShow(true); }}>Edit</button>
                    {!r.is_system && <button className="btn btn-sm btn-outline-danger" onClick={() => remove(r)}>Delete</button>}
                </div>
            ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="CMS Pages" />
            <ReactDataTable
                columns={columns}
                items={pages}
                isLoading={loading}
                onChange={load}
                pagination={false}
                isShowSearch
                AddButton={<div className="text-end"><Button onClick={() => { setEditId(null); setShow(true); }}>+ New Page</Button></div>}
            />
            <PageModal show={show} editId={editId} onHide={() => setShow(false)} onSaved={load} />
        </MasterLayout>
    );
};

export default PlatformCmsPages;
