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

const emptyPost = {
    title: "", cover_image: "", excerpt: "", content: "", author: "",
    seo_title: "", seo_description: "", seo_keywords: "", status: true, published_at: "",
};

const PostModal = ({ show, onHide, editData, onSaved }) => {
    const dispatch = useDispatch();
    const [form, setForm] = useState(emptyPost);

    useEffect(() => {
        setForm(editData ? { ...emptyPost, ...editData, published_at: editData.published_at ? editData.published_at.substring(0, 10) : "" } : emptyPost);
    }, [editData, show]);

    const setF = (k) => (e) =>
        setForm((f) => ({ ...f, [k]: e.target.type === "checkbox" ? e.target.checked : e.target.value }));

    const submit = () => {
        if (!form.title) {
            dispatch(addToast({ text: "Title is required", type: toastType.ERROR }));
            return;
        }
        const url = editData ? apiBaseURL.PLATFORM_BLOG + "/" + editData.id : apiBaseURL.PLATFORM_BLOG;
        apiConfig.post(url, form)
            .then(() => { dispatch(addToast({ text: "Post saved." })); onSaved(); onHide(); })
            .catch(({ response }) => dispatch(addToast({ text: response?.data?.message || "Failed to save", type: toastType.ERROR })));
    };

    return (
        <Modal show={show} onHide={onHide} size="lg" scrollable>
            <Modal.Header closeButton><Modal.Title>{editData ? "Edit Post" : "New Post"}</Modal.Title></Modal.Header>
            <Modal.Body>
                <div className="row">
                    <div className="col-md-8 mb-3">
                        <label className="form-label">Title</label>
                        <input className="form-control" value={form.title} onChange={setF("title")} />
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Author</label>
                        <input className="form-control" value={form.author || ""} onChange={setF("author")} />
                    </div>
                    <div className="col-md-8 mb-3">
                        <label className="form-label">Cover image URL</label>
                        <input className="form-control" value={form.cover_image || ""} onChange={setF("cover_image")} />
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Publish date</label>
                        <input type="date" className="form-control" value={form.published_at || ""} onChange={setF("published_at")} />
                    </div>
                    <div className="col-md-12 mb-3">
                        <label className="form-label">Excerpt</label>
                        <textarea className="form-control" rows={2} value={form.excerpt || ""} onChange={setF("excerpt")} />
                    </div>
                    <div className="col-md-12 mb-3">
                        <label className="form-label">Content (HTML)</label>
                        <textarea className="form-control" rows={6} value={form.content || ""} onChange={setF("content")} />
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
                    <div className="col-md-12">
                        <label className="form-check form-switch">
                            <input type="checkbox" className="form-check-input" checked={!!form.status} onChange={setF("status")} />
                            <span className="ms-2">Published</span>
                        </label>
                    </div>
                </div>
            </Modal.Body>
            <Modal.Footer>
                <Button variant="primary" onClick={submit}>{editData ? "Update" : "Create"}</Button>
                <Button variant="secondary" onClick={onHide}>Cancel</Button>
            </Modal.Footer>
        </Modal>
    );
};

const PlatformBlog = () => {
    const dispatch = useDispatch();
    const [posts, setPosts] = useState([]);
    const [loading, setLoading] = useState(false);
    const [show, setShow] = useState(false);
    const [editData, setEditData] = useState(null);

    const load = () => {
        setLoading(true);
        apiConfig.get(apiBaseURL.PLATFORM_BLOG)
            .then((res) => setPosts(res.data?.data || []))
            .catch(() => {})
            .finally(() => setLoading(false));
    };
    useEffect(() => { load(); }, []);

    const remove = (row) => {
        apiConfig.delete(apiBaseURL.PLATFORM_BLOG + "/" + row.id)
            .then(() => { dispatch(addToast({ text: "Post deleted." })); load(); })
            .catch(({ response }) => dispatch(addToast({ text: response?.data?.message || "Failed", type: toastType.ERROR })));
    };

    const columns = [
        { name: "Title", selector: (r) => r.title, wrap: true },
        { name: "Slug", selector: (r) => r.slug, wrap: true },
        { name: "Author", selector: (r) => r.author || "—" },
        { name: "Published", selector: (r) => (r.published_at ? r.published_at.substring(0, 10) : "—") },
        { name: "Status", cell: (r) => (r.status ? <span className="badge bg-light-success">Live</span> : <span className="badge bg-light-secondary">Draft</span>) },
        {
            name: "Action", right: true,
            cell: (r) => (
                <div className="d-flex gap-2">
                    <button className="btn btn-sm btn-outline-primary" onClick={() => { setEditData(r); setShow(true); }}>Edit</button>
                    <button className="btn btn-sm btn-outline-danger" onClick={() => remove(r)}>Delete</button>
                </div>
            ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Blog" />
            <ReactDataTable
                columns={columns}
                items={posts}
                isLoading={loading}
                onChange={load}
                pagination={false}
                isShowSearch
                AddButton={<div className="text-end"><Button onClick={() => { setEditData(null); setShow(true); }}>+ New Post</Button></div>}
            />
            <PostModal show={show} editData={editData} onHide={() => setShow(false)} onSaved={load} />
        </MasterLayout>
    );
};

export default PlatformBlog;
