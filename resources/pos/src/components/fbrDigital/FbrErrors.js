import React, { useEffect, useState } from "react";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL } from "../../constants";

const FbrErrors = () => {
    const [rows, setRows] = useState([]);
    const [search, setSearch] = useState("");
    const [occurredOnly, setOccurredOnly] = useState(false);

    const load = () => {
        const params = { ...(search ? { search } : {}), ...(occurredOnly ? { occurred_only: 1 } : {}) };
        apiConfig.get(apiBaseURL.FBR_DI_ERRORS, { params }).then((r) => setRows(r.data?.data || [])).catch(() => {});
    };
    useEffect(() => { load(); }, [occurredOnly]); // eslint-disable-line

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="FBR Error Center" />
            <div className="card">
                <div className="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 className="mb-0">FBR Error Codes</h6>
                    <div className="d-flex align-items-center gap-2">
                        <input className="form-control form-control-sm" placeholder="Search code / message…" value={search}
                            onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === "Enter" && load()} />
                        <div className="form-check form-switch mb-0">
                            <input className="form-check-input" type="checkbox" id="occ" checked={occurredOnly} onChange={(e) => setOccurredOnly(e.target.checked)} />
                            <label className="form-check-label text-nowrap" htmlFor="occ">Occurred only</label>
                        </div>
                    </div>
                </div>
                <div className="card-body table-responsive">
                    <table className="table align-middle">
                        <thead><tr>
                            <th>Code</th><th>Message</th><th>Description</th><th>Resolution</th><th>Occurrences</th><th>Last seen</th>
                        </tr></thead>
                        <tbody>
                            {rows.length === 0 ? (
                                <tr><td colSpan={6} className="text-center text-muted py-4">No error codes.</td></tr>
                            ) : rows.map((r) => (
                                <tr key={r.id}>
                                    <td className="fw-semibold">{r.code}</td>
                                    <td>{r.message || "—"}</td>
                                    <td className="small text-muted">{r.description || "—"}</td>
                                    <td className="small">{r.resolution || "—"}</td>
                                    <td>{r.total_occurrences > 0
                                        ? <span className="badge bg-light-danger">{r.total_occurrences}</span>
                                        : <span className="text-muted">0</span>}</td>
                                    <td className="small text-muted">{r.last_occurrence_at ? new Date(r.last_occurrence_at).toLocaleString() : "—"}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </MasterLayout>
    );
};

export default FbrErrors;
