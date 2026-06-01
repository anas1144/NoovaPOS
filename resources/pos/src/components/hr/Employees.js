import React, { useEffect, useState } from "react";
import { Modal, Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import ActionButton from "../../shared/action-buttons/ActionButton";
import DeleteModel from "../../shared/action-buttons/DeleteModel";
import apiConfig from "../../config/apiConfig";
import moment from "moment";

const EmployeeForm = ({ show, data, onHide, onSaved }) => {
    const [f, setF] = useState({});

    useEffect(() => {
        setF({
            employee_code: data?.employee_code || "",
            first_name: data?.first_name || "",
            last_name: data?.last_name || "",
            email: data?.email || "",
            phone: data?.phone || "",
            cnic: data?.cnic || "",
            designation: data?.designation || "",
            department: data?.department || "",
            employment_type: data?.employment_type || "full_time",
            hired_at: data?.hired_at?.split("T")?.[0] || moment().format("YYYY-MM-DD"),
            salary: data?.salary || 0,
            salary_cycle: data?.salary_cycle || "monthly",
            address: data?.address || "",
            status: data?.status !== false ? 1 : 0,
        });
    }, [data, show]);

    const set = (k) => (e) => setF((s) => ({ ...s, [k]: e.target.value }));

    const submit = async () => {
        try {
            if (data?.id) {
                await apiConfig.patch("hr/employees/" + data.id, f);
            } else {
                await apiConfig.post("hr/employees", f);
            }
            onSaved && onSaved();
            onHide();
        } catch (err) {
            console.error(err);
        }
    };

    return (
        <Modal show={show} onHide={onHide} size="lg">
            <Modal.Header closeButton>
                <Modal.Title>
                    {data?.id ? "Edit Employee" : "Create Employee"}
                </Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="row">
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Employee Code</label>
                        <input
                            className="form-control"
                            value={f.employee_code || ""}
                            onChange={set("employee_code")}
                        />
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">First Name</label>
                        <input
                            className="form-control"
                            value={f.first_name || ""}
                            onChange={set("first_name")}
                        />
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Last Name</label>
                        <input
                            className="form-control"
                            value={f.last_name || ""}
                            onChange={set("last_name")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Email</label>
                        <input
                            type="email"
                            className="form-control"
                            value={f.email || ""}
                            onChange={set("email")}
                        />
                    </div>
                    <div className="col-md-3 mb-3">
                        <label className="form-label">Phone</label>
                        <input
                            className="form-control"
                            value={f.phone || ""}
                            onChange={set("phone")}
                        />
                    </div>
                    <div className="col-md-3 mb-3">
                        <label className="form-label">CNIC</label>
                        <input
                            className="form-control"
                            value={f.cnic || ""}
                            onChange={set("cnic")}
                        />
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Designation</label>
                        <input
                            className="form-control"
                            value={f.designation || ""}
                            onChange={set("designation")}
                        />
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Department</label>
                        <input
                            className="form-control"
                            value={f.department || ""}
                            onChange={set("department")}
                        />
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Employment Type</label>
                        <select
                            className="form-control"
                            value={f.employment_type || "full_time"}
                            onChange={set("employment_type")}
                        >
                            <option value="full_time">Full Time</option>
                            <option value="part_time">Part Time</option>
                            <option value="contract">Contract</option>
                        </select>
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Hired On</label>
                        <input
                            type="date"
                            className="form-control"
                            value={f.hired_at || ""}
                            onChange={set("hired_at")}
                        />
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Salary</label>
                        <input
                            type="number"
                            step="0.01"
                            className="form-control"
                            value={f.salary || 0}
                            onChange={set("salary")}
                        />
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Salary Cycle</label>
                        <select
                            className="form-control"
                            value={f.salary_cycle || "monthly"}
                            onChange={set("salary_cycle")}
                        >
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div className="col-md-12 mb-3">
                        <label className="form-label">Address</label>
                        <input
                            className="form-control"
                            value={f.address || ""}
                            onChange={set("address")}
                        />
                    </div>
                </div>
            </Modal.Body>
            <Modal.Footer>
                <Button onClick={submit}>Save</Button>
                <Button variant="secondary" onClick={onHide}>
                    Cancel
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

const Employees = () => {
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(false);
    const [show, setShow] = useState(false);
    const [editData, setEditData] = useState(null);
    const [del, setDel] = useState(null);

    const load = async () => {
        setLoading(true);
        try {
            const res = await apiConfig.get("hr/employees");
            setItems(res.data?.data?.data || res.data?.data || []);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, []);

    const checkIn = async (id) => {
        await apiConfig.post("hr/employees/" + id + "/check-in");
        load();
    };
    const checkOut = async (id) => {
        await apiConfig.post("hr/employees/" + id + "/check-out");
        load();
    };

    const columns = [
        { name: "Code", selector: (r) => r.employee_code || "—" },
        {
            name: "Name",
            cell: (r) => (
                <span className="fw-semibold">
                    {r.first_name} {r.last_name || ""}
                </span>
            ),
        },
        { name: "Designation", selector: (r) => r.designation || "—" },
        { name: "Department", selector: (r) => r.department || "—" },
        {
            name: "Type",
            cell: (r) => (
                <span className="badge bg-light-primary text-capitalize">
                    {(r.employment_type || "").replace("_", " ")}
                </span>
            ),
        },
        {
            name: "Salary",
            selector: (r) => r.salary,
            cell: (r) =>
                Number(r.salary || 0).toLocaleString() +
                " / " +
                (r.salary_cycle || "monthly"),
        },
        {
            name: "Status",
            cell: (r) =>
                r.status ? (
                    <span className="badge bg-light-success">Active</span>
                ) : (
                    <span className="badge bg-light-secondary">Inactive</span>
                ),
        },
        {
            name: "Action",
            right: true,
            cell: (r) => (
                <div className="d-flex gap-1">
                    <button
                        className="btn btn-sm btn-outline-success"
                        onClick={() => checkIn(r.id)}
                    >
                        In
                    </button>
                    <button
                        className="btn btn-sm btn-outline-warning"
                        onClick={() => checkOut(r.id)}
                    >
                        Out
                    </button>
                    <ActionButton
                        item={r}
                        goToEditProduct={() => {
                            setEditData(r);
                            setShow(true);
                        }}
                        isEditMode={true}
                        onClickDeleteModel={() => setDel(r)}
                    />
                </div>
            ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Employees" />
            <ReactDataTable
                columns={columns}
                items={items}
                isLoading={loading}
                pagination={false}
                isShowSearch
                onChange={load}
                AddButton={
                    <div className="text-end">
                        <Button
                            onClick={() => {
                                setEditData(null);
                                setShow(true);
                            }}
                        >
                            + New Employee
                        </Button>
                    </div>
                }
            />
            <EmployeeForm
                show={show}
                data={editData}
                onHide={() => setShow(false)}
                onSaved={load}
            />
            {del && (
                <DeleteModel
                    onClickDeleteModel={() => setDel(null)}
                    deleteModel={true}
                    deleteUserClick={async () => {
                        await apiConfig.delete("hr/employees/" + del.id);
                        setDel(null);
                        load();
                    }}
                    title="Delete Employee"
                    name="Employee"
                />
            )}
        </MasterLayout>
    );
};

export default Employees;
