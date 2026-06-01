import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import ActionButton from "../../shared/action-buttons/ActionButton";
import FbrProfileForm from "./FbrProfileForm";
import DeleteModel from "../../shared/action-buttons/DeleteModel";
import {
    fetchFbrProfiles,
    deleteFbrProfile,
} from "../../store/action/fbrProfileAction";

const FbrProfiles = () => {
    const dispatch = useDispatch();
    const profiles = useSelector((state) => state.fbrProfiles || []);
    const isLoading = useSelector((state) => state.isLoading);
    const [showForm, setShowForm] = useState(false);
    const [editData, setEditData] = useState(null);
    const [isEdit, setIsEdit] = useState(false);
    const [deleteModel, setDeleteModel] = useState(false);
    const [toDelete, setToDelete] = useState(null);

    useEffect(() => {
        dispatch(fetchFbrProfiles());
    }, []);

    const openCreate = () => {
        setEditData(null);
        setIsEdit(false);
        setShowForm(true);
    };

    const openEdit = (row) => {
        setEditData(row);
        setIsEdit(true);
        setShowForm(true);
    };

    const askDelete = (row = null) => {
        setDeleteModel(!deleteModel);
        setToDelete(row);
    };

    const onDelete = () => {
        if (toDelete) {
            dispatch(deleteFbrProfile(toDelete.id));
        }
        setDeleteModel(false);
    };

    const items = (profiles || []).map((p) => ({
        id: p.id,
        business_name: p.business_name,
        ntn: p.ntn,
        strn: p.strn,
        province: p.province,
        store_id: p.store_id,
        store_name: p.store?.name || "Tenant level",
        mode: p.mode,
        enabled: p.enabled,
        sandbox_token: p.sandbox_token,
        production_token: p.production_token,
        pos_id: p.pos_id,
        business_activity: p.business_activity,
    }));

    const columns = [
        {
            name: "Business",
            selector: (row) => row.business_name,
            sortable: false,
        },
        { name: "NTN", selector: (row) => row.ntn },
        { name: "STRN", selector: (row) => row.strn || "-" },
        {
            name: "Store",
            selector: (row) => row.store_name,
        },
        {
            name: "Mode",
            selector: (row) => row.mode,
            cell: (row) => (
                <span
                    className={`badge ${
                        row.mode === "production"
                            ? "bg-light-danger"
                            : "bg-light-warning"
                    } text-capitalize`}
                >
                    {row.mode}
                </span>
            ),
        },
        {
            name: "Enabled",
            cell: (row) => (
                <span
                    className={`badge ${
                        row.enabled ? "bg-light-success" : "bg-light-secondary"
                    }`}
                >
                    {row.enabled ? "Yes" : "No"}
                </span>
            ),
        },
        {
            name: "Action",
            right: true,
            ignoreRowClick: true,
            allowOverflow: true,
            button: true,
            width: "120px",
            cell: (row) => (
                <ActionButton
                    item={row}
                    goToEditProduct={() => openEdit(row)}
                    isEditMode={true}
                    onClickDeleteModel={askDelete}
                />
            ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="FBR Profiles" />
            <ReactDataTable
                columns={columns}
                items={items}
                isLoading={isLoading}
                onChange={() => dispatch(fetchFbrProfiles())}
                pagination={false}
                isShowSearch
                AddButton={
                    <div className="text-end w-sm-auto">
                        <Button
                            variant="primary mb-lg-0 mb-4"
                            onClick={openCreate}
                        >
                            Create FBR Profile
                        </Button>
                    </div>
                }
            />
            <FbrProfileForm
                show={showForm}
                isEdit={isEdit}
                data={editData}
                handleClose={() => setShowForm(false)}
            />
            {deleteModel && (
                <DeleteModel
                    onClickDeleteModel={askDelete}
                    deleteModel={deleteModel}
                    deleteUserClick={onDelete}
                    title="Delete FBR Profile"
                    name="FBR Profile"
                />
            )}
        </MasterLayout>
    );
};

export default FbrProfiles;
