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
    fetchRecurringPlans,
    saveRecurringPlan,
    deleteRecurringPlan,
} from "../../store/action/recurringAction";

const PlanForm = ({ show, data, onHide }) => {
    const dispatch = useDispatch();
    const [f, setF] = useState({
        name: "",
        billing_cycle: "monthly",
        price: 0,
        deposit_amount: 0,
        is_bottle: false,
        status: 1,
    });

    useEffect(() => {
        setF({
            name: data?.name || "",
            billing_cycle: data?.billing_cycle || "monthly",
            price: data?.price || 0,
            deposit_amount: data?.deposit_amount || 0,
            is_bottle: !!data?.is_bottle,
            status: data?.status !== false ? 1 : 0,
        });
    }, [data, show]);

    const change = (k) => (e) =>
        setF((s) => ({
            ...s,
            [k]: e.target.type === "checkbox" ? e.target.checked : e.target.value,
        }));

    const submit = () =>
        dispatch(saveRecurringPlan(f, data?.id, onHide));

    return (
        <Modal show={show} onHide={onHide}>
            <Modal.Header closeButton>
                <Modal.Title>
                    {data?.id ? "Edit Recurring Plan" : "Create Recurring Plan"}
                </Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="row">
                    <div className="col-md-12 mb-3">
                        <label className="form-label">Name</label>
                        <input
                            className="form-control"
                            value={f.name}
                            onChange={change("name")}
                            placeholder="e.g. 19L Water — Monthly"
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Billing Cycle</label>
                        <select
                            className="form-control"
                            value={f.billing_cycle}
                            onChange={change("billing_cycle")}
                        >
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Price per unit</label>
                        <input
                            type="number"
                            step="0.01"
                            className="form-control"
                            value={f.price}
                            onChange={change("price")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Bottle Deposit</label>
                        <input
                            type="number"
                            step="0.01"
                            className="form-control"
                            value={f.deposit_amount}
                            onChange={change("deposit_amount")}
                        />
                    </div>
                    <div className="col-md-6 mb-3 d-flex align-items-end">
                        <label className="form-check form-switch form-switch-sm">
                            <input
                                type="checkbox"
                                className="form-check-input me-3"
                                checked={!!f.is_bottle}
                                onChange={change("is_bottle")}
                            />
                            <span>Bottle / refundable container</span>
                        </label>
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

const RecurringPlans = () => {
    const dispatch = useDispatch();
    const plans = useSelector((s) => s.recurring?.plans || []);
    const isLoading = useSelector((s) => s.isLoading);
    const [show, setShow] = useState(false);
    const [editData, setEditData] = useState(null);
    const [del, setDel] = useState(null);

    useEffect(() => {
        dispatch(fetchRecurringPlans());
    }, []);

    const columns = [
        { name: "Name", selector: (r) => r.name },
        {
            name: "Cycle",
            selector: (r) => r.billing_cycle,
            cell: (r) => (
                <span className="badge bg-light-primary text-capitalize">
                    {r.billing_cycle}
                </span>
            ),
        },
        { name: "Price", selector: (r) => r.price },
        { name: "Deposit", selector: (r) => r.deposit_amount },
        {
            name: "Bottle?",
            cell: (r) =>
                r.is_bottle ? (
                    <span className="badge bg-light-success">Yes</span>
                ) : (
                    <span className="badge bg-light-secondary">No</span>
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
                    onClickDeleteModel={() => setDel(r)}
                />
            ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Recurring Plans" />
            <ReactDataTable
                columns={columns}
                items={plans}
                isLoading={isLoading}
                pagination={false}
                isShowSearch
                onChange={() => dispatch(fetchRecurringPlans())}
                AddButton={
                    <div className="text-end">
                        <Button
                            onClick={() => {
                                setEditData(null);
                                setShow(true);
                            }}
                        >
                            + New Plan
                        </Button>
                    </div>
                }
            />
            <PlanForm
                show={show}
                data={editData}
                onHide={() => setShow(false)}
            />
            {del && (
                <DeleteModel
                    onClickDeleteModel={() => setDel(null)}
                    deleteModel={true}
                    deleteUserClick={() => {
                        dispatch(deleteRecurringPlan(del.id));
                        setDel(null);
                    }}
                    title="Delete Plan"
                    name="Plan"
                />
            )}
        </MasterLayout>
    );
};

export default RecurringPlans;
