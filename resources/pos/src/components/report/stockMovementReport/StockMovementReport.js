import React, { useEffect } from "react";
import { connect } from "react-redux";
import MasterLayout from "../../MasterLayout";
import TabTitle from "../../../shared/tab-title/TabTitle";
import TopProgressBar from "../../../shared/components/loaders/TopProgressBar";
import ReactDataTable from "../../../shared/table/ReactDataTable";
import Widget from "../../../shared/Widget/Widget";
import { Row } from "react-bootstrap";
import {
    currencySymbolHandling,
    getFormattedDate,
    getFormattedMessage,
    placeholderText,
} from "../../../shared/sharedMethod";
import {
    faArrowDown,
    faArrowUp,
    faBalanceScale,
} from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { fetchAllWarehouses } from "../../../store/action/warehouseAction";
import {
    fetchStockMovements,
    fetchStockMovementSummary,
} from "../../../store/action/stockMovementAction";

const StockMovementReport = (props) => {
    const {
        isLoading,
        totalRecord,
        allConfigData,
        frontSetting,
        warehouses,
        stockMovement,
        fetchAllWarehouses,
        fetchStockMovements,
        fetchStockMovementSummary,
    } = props;

    const currencySymbol =
        frontSetting &&
        frontSetting.value &&
        frontSetting.value.currency_symbol;

    useEffect(() => {
        fetchAllWarehouses();
    }, []);

    useEffect(() => {
        fetchStockMovements({}, true);
        fetchStockMovementSummary({});
    }, []);

    const onChange = (filter) => {
        fetchStockMovements(filter, true);
        fetchStockMovementSummary(filter);
    };

    const itemsValue =
        stockMovement?.movements?.length >= 0 &&
        stockMovement.movements.map((item) => ({
            id: item.id,
            created_at: getFormattedDate(item.created_at, allConfigData),
            movement_type: item.movement_type,
            direction: item.direction,
            product_name: item.product_name,
            product_code: item.product_code,
            warehouse_name: item.warehouse_name,
            quantity: Number(item.quantity || 0),
            reference: `${item.reference_type || "-"} #${item.reference_id || "-"}`,
            created_by_name: item.created_by_name || "-",
            currency: currencySymbol,
        }));

    const columns = [
        {
            name: getFormattedMessage("globally.react-table.column.created-date.label"),
            selector: (row) => row.created_at,
            sortField: "created_at",
            sortable: true,
        },
        {
            name: getFormattedMessage("stock.movement.type.title"),
            selector: (row) => row.movement_type,
            sortField: "movement_type",
            sortable: true,
            cell: (row) => <span className="badge bg-light-primary">{row.movement_type}</span>,
        },
        {
            name: getFormattedMessage("stock.movement.direction.title"),
            selector: (row) => row.direction,
            sortField: "direction",
            sortable: true,
            cell: (row) => (
                <span className={`badge ${row.direction === "in" ? "bg-light-success" : "bg-light-danger"}`}>
                    {row.direction}
                </span>
            ),
        },
        {
            name: getFormattedMessage("dashboard.stockAlert.product.label"),
            selector: (row) => row.product_name,
            sortField: "product_name",
            sortable: true,
            cell: (row) => (
                <div>
                    <div>{row.product_name}</div>
                    <small className="text-muted">{row.product_code}</small>
                </div>
            ),
        },
        {
            name: getFormattedMessage("warehouse.title"),
            selector: (row) => row.warehouse_name,
            sortField: "warehouse_name",
            sortable: true,
        },
        {
            name: getFormattedMessage("current.stock.label"),
            selector: (row) => row.quantity,
            sortField: "quantity",
            sortable: true,
            cell: (row) => (
                <span className={`badge ${row.direction === "in" ? "bg-light-success" : "bg-light-danger"}`}>
                    {row.direction === "out" ? "-" : "+"}
                    {row.quantity}
                </span>
            ),
        },
        {
            name: getFormattedMessage("dashboard.recentSales.reference.label"),
            selector: (row) => row.reference,
            sortField: "reference_id",
            sortable: true,
        },
        {
            name: getFormattedMessage("users.title"),
            selector: (row) => row.created_by_name,
            sortField: "created_by_name",
            sortable: false,
        },
    ];

    const summary = stockMovement?.summary || { totals: {}, by_type: [] };
    const totalIn = Number(summary?.totals?.total_in || 0);
    const totalOut = Number(summary?.totals?.total_out || 0);
    const netQuantity = Number(summary?.totals?.net_quantity || 0);

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText("stock.movement.report.title")} />
            <Row className="g-4 mb-4">
                <Widget
                    title={getFormattedMessage("stock.movement.total.in.title")}
                    icon={<FontAwesomeIcon icon={faArrowDown} className="fs-1-xl text-white" />}
                    currency={currencySymbol}
                    className="bg-success"
                    iconClass="bg-green-300"
                    value={currencySymbolHandling(allConfigData, currencySymbol, totalIn)}
                />
                <Widget
                    title={getFormattedMessage("stock.movement.total.out.title")}
                    icon={<FontAwesomeIcon icon={faArrowUp} className="fs-1-xl text-white" />}
                    currency={currencySymbol}
                    className="bg-danger"
                    iconClass="bg-red-300"
                    value={currencySymbolHandling(allConfigData, currencySymbol, totalOut)}
                />
                <Widget
                    title={getFormattedMessage("stock.movement.net.title")}
                    icon={<FontAwesomeIcon icon={faBalanceScale} className="fs-1-xl text-white" />}
                    currency={currencySymbol}
                    className="bg-primary"
                    iconClass="bg-cyan-300"
                    value={currencySymbolHandling(allConfigData, currencySymbol, netQuantity)}
                />
            </Row>

            <ReactDataTable
                columns={columns}
                items={itemsValue}
                onChange={onChange}
                isLoading={isLoading}
                totalRows={totalRecord}
                isShowDateRangeField
                isShowFilterField
                isWarehouseType
                warehouseOptions={warehouses}
            />
        </MasterLayout>
    );
};

const mapStateToProps = (state) => {
    const { isLoading, totalRecord, allConfigData, frontSetting, warehouses, stockMovement } = state;
    return { isLoading, totalRecord, allConfigData, frontSetting, warehouses, stockMovement };
};

export default connect(mapStateToProps, {
    fetchAllWarehouses,
    fetchStockMovements,
    fetchStockMovementSummary,
})(StockMovementReport);
