import React, { useEffect, useState } from "react";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import { placeholderText } from "../../shared/sharedMethod";
import ReactDataTable from "../../shared/table/ReactDataTable";
import ShopForm from "./ShopForm";
import AddShopButton from "./AddShopButton";
import ActionButton from "../../shared/action-buttons/ActionButton";
import DeleteShop from "./DeleteShop";
import {
    changeShopStatus,
    fetchShops,
} from "../../store/action/shopAction";
import { useDispatch, useSelector } from "react-redux";

const Shop = () => {
    const dispatch = useDispatch();
    const shopsState = useSelector((state) => state.shops);
    const isCallFetchDataApi = useSelector((state) => state.isCallFetchDataApi);
    const isLoading = useSelector((state) => state.isLoading);
    const [deleteModel, setDeleteModel] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [isDelete, setIsDelete] = useState(null);
    const [editData, setEditData] = useState();
    const [localItemsValue, setLocalItemsValue] = useState([]);

    useEffect(() => {
        dispatch(fetchShops());
    }, []);

    useEffect(() => {
        if (shopsState && shopsState.length >= 0) {
            const mapped = (shopsState || []).map((item) => ({
                name: item?.attributes?.name,
                code: item?.attributes?.code,
                store_id: item?.attributes?.store_id,
                store_name: item?.attributes?.store_name,
                shop_type: item?.attributes?.shop_type || "retail",
                status: item?.attributes?.status ? 1 : 0,
                users: item?.attributes?.users,
                id: item?.id,
            }));
            setLocalItemsValue(mapped);
        }
    }, [shopsState]);

    const onChange = () => {
        dispatch(fetchShops());
    };

    const goToEditShop = (data) => {
        setEditData(data);
        setShowEditModal(true);
    };

    const onClickDeleteModel = (isDelete = null) => {
        setDeleteModel(!deleteModel);
        setIsDelete(isDelete);
    };

    const handleStatusChange = async (id, status) => {
        if (!id) return;
        dispatch(changeShopStatus(id));
        setLocalItemsValue((prev) =>
            prev.map((item) =>
                item.id === id ? { ...item, status: status ? 1 : 0 } : item
            )
        );
    };

    const columns = [
        {
            name: "Shop / Counter",
            selector: (row) => row?.name,
            sortable: false,
        },
        {
            name: "Code",
            selector: (row) => row?.code || "-",
            sortable: false,
        },
        {
            name: "Store / Branch",
            selector: (row) => row?.store_name || "-",
            sortable: false,
        },
        {
            name: "Type",
            selector: (row) => row?.shop_type,
            sortable: false,
            cell: (row) => (
                <span className="badge bg-light-primary text-capitalize">
                    {(row.shop_type || "retail").replace("_", " ")}
                </span>
            ),
        },
        {
            name: "Status",
            selector: (row) => row.status,
            sortable: false,
            cell: (row) => (
                <div className="d-flex align-items-center">
                    <label className="form-check form-switch form-switch-sm">
                        <input
                            type="checkbox"
                            checked={!!row.status}
                            onChange={(e) =>
                                handleStatusChange(row.id, e.target.checked)
                            }
                            className="me-3 form-check-input cursor-pointer"
                        />
                        <div className="control__indicator" />
                    </label>
                </div>
            ),
        },
        {
            name: "Users",
            selector: (row) => row?.users,
            sortable: false,
            cell: (row) => (
                <span className="badge bg-light-primary">
                    <span>{row?.users || 0}</span>
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
                    goToEditProduct={() => goToEditShop(row)}
                    isEditMode={true}
                    onClickDeleteModel={onClickDeleteModel}
                />
            ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText("shop.title") || "Shops"} />
            <ReactDataTable
                columns={columns}
                items={localItemsValue}
                onChange={onChange}
                isLoading={isLoading}
                pagination={false}
                isShowSearch
                AddButton={<AddShopButton />}
                isCallFetchDataApi={isCallFetchDataApi}
            />
            <ShopForm
                show={showEditModal}
                isEdit={true}
                data={editData}
                handleClose={() => setShowEditModal(false)}
                title="Edit Shop"
            />
            <DeleteShop
                onClickDeleteModel={onClickDeleteModel}
                deleteModel={deleteModel}
                onDelete={isDelete}
            />
        </MasterLayout>
    );
};

export default Shop;
