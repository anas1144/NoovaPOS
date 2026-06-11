import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

/**
 * Super-admin: FBR-DI per-Company plan limits (Single Company / Agent, monthly
 * invoice + businesses caps; blank = unlimited) and Agent → Company assignment.
 */
const PlatformFbrDi = () => {
    const dispatch = useDispatch();
    const [limits, setLimits] = useState([]);
    const [agents, setAgents] = useState([]);
    const [companies, setCompanies] = useState([]);
    const [assign, setAssign] = useState({ agent_user_id: "", tenant_id: "" });

    const toast = (text, type) => dispatch(addToast({ text, type }));

    const loadLimits = () => apiConfig.get(apiBaseURL.PLATFORM_FBR_DI_LIMITS).then((r) => setLimits(r.data?.data || [])).catch(() => {});
    const loadAgents = () => apiConfig.get(apiBaseURL.PLATFORM_FBR_DI_AGENTS).then((r) => {
        setAgents(r.data?.data?.agents || []);
        setCompanies(r.data?.data?.companies || []);
    }).catch(() => {});
    useEffect(() => { loadLimits(); loadAgents(); }, []);

    const setRow = (i, k) => (e) => setLimits((rows) => rows.map((r, idx) => idx === i ? { ...r, [k]: e.target.value } : r));

    const saveRow = (row) => {
        apiConfig.post(apiBaseURL.PLATFORM_FBR_DI_LIMITS, {
            tenant_id: row.tenant_id,
            plan_type: row.plan_type || "single",
            monthly_invoice_limit: row.monthly_invoice_limit === "" ? null : Number(row.monthly_invoice_limit),
            businesses_limit: row.businesses_limit === "" ? null : Number(row.businesses_limit),
        }).then(() => toast(`Saved limits for ${row.company}`))
            .catch(({ response }) => toast(response?.data?.message || "Save failed", toastType.ERROR));
    };

    const attach = () => {
        if (!assign.agent_user_id || !assign.tenant_id) { toast("Pick an agent and a company", toastType.ERROR); return; }
        apiConfig.post(`${apiBaseURL.PLATFORM_FBR_DI_AGENTS}/attach`, assign)
            .then(() => { toast("Company assigned."); loadAgents(); })
            .catch(({ response }) => toast(response?.data?.message || "Failed", toastType.ERROR));
    };
    const detach = (agentId, tenantId) => {
        apiConfig.post(`${apiBaseURL.PLATFORM_FBR_DI_AGENTS}/detach`, { agent_user_id: agentId, tenant_id: tenantId })
            .then(() => { toast("Removed."); loadAgents(); })
            .catch(() => toast("Failed", toastType.ERROR));
    };

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="FBR-DI Plans & Agents" />

            <div className="card mb-4">
                <div className="card-header"><h6 className="mb-0">Company plan limits (blank = unlimited)</h6></div>
                <div className="card-body table-responsive">
                    <table className="table align-middle">
                        <thead><tr><th>Company</th><th>Plan type</th><th>Monthly invoices</th><th>Businesses</th><th></th></tr></thead>
                        <tbody>
                            {limits.length === 0 ? (
                                <tr><td colSpan={5} className="text-center text-muted py-4">No companies.</td></tr>
                            ) : limits.map((r, i) => (
                                <tr key={r.tenant_id}>
                                    <td>{r.company}</td>
                                    <td>
                                        <select className="form-control form-control-sm w-auto" value={r.plan_type || "single"} onChange={setRow(i, "plan_type")}>
                                            <option value="single">Single Company</option>
                                            <option value="agent">Agent</option>
                                        </select>
                                    </td>
                                    <td><input type="number" className="form-control form-control-sm" style={{ width: 130 }}
                                        value={r.monthly_invoice_limit ?? ""} onChange={setRow(i, "monthly_invoice_limit")} placeholder="∞" /></td>
                                    <td><input type="number" className="form-control form-control-sm" style={{ width: 110 }}
                                        value={r.businesses_limit ?? ""} onChange={setRow(i, "businesses_limit")} placeholder="∞" /></td>
                                    <td><Button size="sm" variant="outline-primary" onClick={() => saveRow(r)}>Save</Button></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="card">
                <div className="card-header"><h6 className="mb-0">Agents & their companies</h6></div>
                <div className="card-body">
                    <div className="d-flex flex-wrap gap-2 align-items-end mb-3">
                        <div>
                            <label className="form-label">Agent</label>
                            <select className="form-control" value={assign.agent_user_id} onChange={(e) => setAssign((a) => ({ ...a, agent_user_id: e.target.value }))}>
                                <option value="">— Agent —</option>
                                {agents.map((a) => <option key={a.id} value={a.id}>{a.name || a.email}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="form-label">Company</label>
                            <select className="form-control" value={assign.tenant_id} onChange={(e) => setAssign((a) => ({ ...a, tenant_id: e.target.value }))}>
                                <option value="">— Company —</option>
                                {companies.map((c) => <option key={c.tenant_id} value={c.tenant_id}>{c.company}</option>)}
                            </select>
                        </div>
                        <Button variant="primary" onClick={attach}>Assign</Button>
                    </div>

                    {agents.length === 0 ? (
                        <div className="text-muted">No agent users. Assign the <code>fbr_agent</code> role to a user first.</div>
                    ) : agents.map((a) => (
                        <div key={a.id} className="border rounded p-3 mb-2">
                            <div className="fw-semibold">{a.name || a.email} <span className="text-muted small">{a.email}</span></div>
                            {a.companies.length === 0 ? <div className="text-muted small">No companies assigned.</div> : (
                                <div className="d-flex flex-wrap gap-2 mt-2">
                                    {a.companies.map((co) => (
                                        <span key={co.tenant_id} className="badge bg-light-primary d-flex align-items-center">
                                            {co.company}
                                            <button className="btn btn-sm btn-link text-danger p-0 ms-2" onClick={() => detach(a.id, co.tenant_id)}>✕</button>
                                        </span>
                                    ))}
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </MasterLayout>
    );
};

export default PlatformFbrDi;
