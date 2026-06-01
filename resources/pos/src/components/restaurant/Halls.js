import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { Modal, Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import ActionButton from "../../shared/action-buttons/ActionButton";
import DeleteModel from "../../shared/action-buttons/DeleteModel";
import {
    fetchHalls,
    saveHall,
    deleteHall,
} from "../../store/action/restaurantAction";

const HallForm = ({ show, data, onHide }) => {
    const dispatch = useDispatch();
    const [form, setForm] = useState({
        name: "",
        code: "",
        floor: 1,
        capacity: 0,
        status: 1,
    });

    useEffect(() => {
        setForm({
            name: data?.name || "",
            code: data?.code || "",
            floor: data?.floor || 1,
            capacity: data?.capacity || 0,
            status: data?.status !== false ? 1 : 0,
        });
    }, [data, show]);

    const setF = (k) => (e) => setForm((s) => ({ ...s, [k]: e.target.value }));

    const submit = () => {
        if (!form.name) return;
        dispatch(saveHall(form, data?.id, onHide));
    };

    return (
        <Modal show={show} onHide={onHide}>
            <Modal.Header closeButton>
                <Modal.Title>{data?.id ? "Edit Hall" : "Create Hall"}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="row">
                    <div className="col-md-12 mb-3">
                        <label className="form-label">Name</label>
                        <input
                            className="form-control"
                            value={form.name}
                            onChange={setF("name")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Code</label>
                        <input
                            className="form-control"
                            value={form.code}
                            onChange={setF("code")}
                        />
                    </div>
                    <div className="col-md-3 mb-3">
                        <label className="form-label">Floor</label>
                        <input
                            type="number"
                            className="form-control"
                            value={form.floor}
                            onChange={setF("floor")}
                        />
                    </div>
                    <div className="col-md-3 mb-3">
                        <label className="form-label">Capacity</label>
                        <input
                            type="number"
                            className="form-control"
                            value={form.capacity}
                            onChange={setF("capacity")}
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

const Halls = () => {
    const dispatch = useDispatch();
    const halls = useSelector((s) => s.restaurant?.halls || []);
    const isLoading = useSelector((s) => s.isLoading);
    const [show, setShow] = useState(false);
    const [editData, setEditData] = useState(null);
    const [deleteRow, setDeleteRow] = useState(null);

    useEffect(() => {
        dispatch(fetchHalls());
    }, []);

    const columns = [
        { name: "Name", selector: (r) => r.name },
        { name: "Code", selector: (r) => r.code || "-" },
        { name: "Floor", selector: (r) => r.floor },
        { name: "Capacity", selector: (r) => r.capacity || "-" },
        {
            name: "Tables",
            cell: (r) => (
                <span className="badge bg-light-primary">{r.tables_count || 0}</span>
            ),
        },
        {
            name: "Status",
            cell: (r) => (
                <span
                    className={`badge ${r.status ? "bg-light-success" : "bg-light-secondary"}`}
                >
                    {r.status ? "Active" : "Disabled"}
                </span>
            ),
        },
        {
            name: "Action",
            right: true,
            cell: (r) => (
                <ActionButton
                    item={r}
                    goToEditProduct={() => {
                        setEditData(r);
                        setShow(true);
                    }}
                    isEditMode={true}
                    onClickDeleteModel={() => setDeleteRow(r)}
                />
            ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Halls" />
            <ReactDataTable
                columns={columns}
                items={halls}
                isLoading={isLoading}
                pagination={false}
                isShowSearch
                onChange={() => dispatch(fetchHalls())}
                AddButton={
                    <div className="text-end">
                        <Button
                            onClick={() => {
                                setEditData(null);
                                setShow(true);
                            }}
                        >
                            + New Hall
                        </Button>
                    </div>
                }
            />
            <HallForm
                show={show}
                data={editData}
                onHide={() => setShow(false)}
            />
            {deleteRow && (
                <DeleteModel
                    onClickDeleteModel={() => setDeleteRow(null)}
                    deleteModel={true}
                    deleteUserClick={() => {
                        dispatch(deleteHall(deleteRow.id));
                        setDeleteRow(null);
                    }}
                    title="Delete Hall"
                    name="Hall"
                />
            )}
        </MasterLayout>
    );
};

export default Halls;
