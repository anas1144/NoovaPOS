import React, { useState } from "react";
import { connect } from "react-redux";
import MasterLayout from "../MasterLayout";
import ReactDataTable from "../../shared/table/ReactDataTable";
import ModalAction from "../../shared/action-buttons/ActionButton";
import { fetchRoles } from "../../store/action/roleAction";
import DeleteRole from "./DeleteRole";
import TabTitle from "../../shared/tab-title/TabTitle";
import {
    getFormattedDate,
    getFormattedMessage,
    getPermission,
    placeholderText,
} from "../../shared/sharedMethod";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import { Permissions } from "../../constants";

const Role = (props) => {
    const { roles, fetchRoles, totalRecord, isLoading, allConfigData, isCallFetchDataApi } = props;
    const [deleteModel, setDeleteModel] = useState(false);
    const [isDelete, setIsDelete] = useState(null);

    const onClickDeleteModel = (isDelete = null) => {
        setDeleteModel(!deleteModel);
        setIsDelete(isDelete);
    };

    const itemsValue =
        roles.length >= 0 &&
        roles.map((role) => ({
            date: getFormattedDate(
                role.attributes.created_at,
                allConfigData && allConfigData
            ),
            name: role.attributes.name,
            id: role.id,
        }));

    const onChange = (filter) => {
        fetchRoles(filter, true);
    };

    const goToEdit = (item) => {
        const id = item.id;
        window.location.href = "#/app/roles/edit/" + id;
    };

    const columns = [
        {
            name: getFormattedMessage("globally.input.name.label"),
            selector: (row) => row.name,
            sortable: true,
            sortField: "name",
        },
        {
            name: getFormattedMessage("react-data-table.date.column.label"),
            selector: (row) => row.date,
            sortField: "date",
            sortable: false,
        },
        ...((
            getPermission(allConfigData?.permissions, Permissions.EDIT_ROLES) ||
            getPermission(allConfigData?.permissions, Permissions.DELETE_ROLES)
        ) ? [
            {
                name: getFormattedMessage("react-data-table.action.column.label"),
                right: true,
                ignoreRowClick: true,
                allowOverflow: true,
                button: true,
                cell: (row) => (
                    <ModalAction
                        item={row}
                        goToEditProduct={goToEdit}
                        isEditMode={getPermission(allConfigData?.permissions, Permissions.EDIT_ROLES)}
                        onClickDeleteModel={onClickDeleteModel}
                        isDeleteMode={getPermission(allConfigData?.permissions, Permissions.DELETE_ROLES)}
                    />
                ),
            }] : []),
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText("roles.title")} />
            <ReactDataTable
                columns={columns}
                items={itemsValue}
                onChange={onChange}
                {...(getPermission(allConfigData?.permissions, Permissions.CREATE_ROLES) &&
                {
                    to: "#/app/roles/create",
                    ButtonValue: getFormattedMessage("role.create.title")
                }
                )}
                totalRows={totalRecord}
                isLoading={isLoading}
                isCallFetchDataApi={isCallFetchDataApi}
            />
            <DeleteRole
                onClickDeleteModel={onClickDeleteModel}
                deleteModel={deleteModel}
                onDelete={isDelete}
            />
        </MasterLayout>
    );
};

const mapStateToProps = (state) => {
    const { roles, totalRecord, isLoading, allConfigData, isCallFetchDataApi } = state;
    return { roles, totalRecord, isLoading, allConfigData, isCallFetchDataApi };
};
export default connect(mapStateToProps, { fetchRoles })(Role);
