import { useEffect, useState } from 'react';
import { Table, Form, InputGroup, Button } from 'react-bootstrap';
import Modal from 'react-bootstrap/Modal';
import { currencySymbolHandling, getFormattedDate, getFormattedMessage, paymentMethodName } from '../../../shared/sharedMethod';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPenToSquare, faArrowRotateLeft, faMagnifyingGlass, faXmark } from '@fortawesome/free-solid-svg-icons';
import { useSelector, useDispatch } from 'react-redux';
import warning from "../../../assets/images/warning.png"
import EditHoldConfirmationModal from '../cart-product/EditHoldConfirmationModal';
import moment from "moment";
import { fetchSales } from '../../../store/action/salesAction';
import { Permissions } from '../../../constants';

const RecentSaleModal = ({ recentSaleModal, setRecentSaleModal, sales, handleLoadMore, setSalePage, onQuickRefund }) => {
    const dispatch = useDispatch();
    const { totalRecord, isLoading, loadingMore, allConfigData, frontSetting, paymentMethods, config } = useSelector(state => state);
    const [isEdit, setIsEdit] = useState(false);
    const [isEditSale, setIsEditSale] = useState(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [currentPage, setCurrentPage] = useState(1);

    const editSaleModel = (item) => {
        setIsEdit(true);
        setIsEditSale(item);
    }

    const onConfirm = () => {
        setIsEdit(false);
        setRecentSaleModal(false);
        setSalePage(1);
        window.open(`#/app/sales/edit/${isEditSale.id}`, '_blank');
    }

    const handleRefund = (sale) => {
        onQuickRefund(sale);
        setRecentSaleModal(false);
    }

    const handleSearch = () => {
        const filters = {
            order_By: "created_at",
            direction: "desc",
            page: 1,
            pageSize: 10,
            search: searchTerm
        };
        dispatch(fetchSales(filters, true, false));
        setCurrentPage(1);
        setSalePage(1);
    };

    const handleClearSearch = () => {
        setSearchTerm('');
        const filters = {
            order_By: "created_at",
            direction: "desc",
            page: 1,
            pageSize: 10
        };
        dispatch(fetchSales(filters, true, false));
        setCurrentPage(1);
        setSalePage(1);
    };

    const handleKeyPress = (e) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    };

    return (
        <>
            <Modal show={recentSaleModal} onHide={() => setRecentSaleModal(false)} size='xl'>
                <Modal.Header closeButton>
                    <Modal.Title>{getFormattedMessage("dashboard.recentSales.title")}</Modal.Title>
                </Modal.Header>
                <Modal.Body className='recent-sale-modal'>
                    {/* Search Bar */}
                    <div className="mb-3">
                        <InputGroup>
                            <Form.Control
                                placeholder="Search by reference code, customer name..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                onKeyPress={handleKeyPress}
                            />
                            {searchTerm && (
                                <Button 
                                    variant="outline-secondary"
                                    onClick={handleClearSearch}
                                >
                                    <FontAwesomeIcon icon={faXmark} />
                                </Button>
                            )}
                            <Button 
                                variant="primary"
                                onClick={handleSearch}
                            >
                                <FontAwesomeIcon icon={faMagnifyingGlass} className="me-2" />
                                Search
                            </Button>
                        </InputGroup>
                    </div>

                    <Table responsive bordered hover className='m-0 registerModel holdListModel'>
                        <tbody>
                            <tr>
                                <th >{getFormattedMessage('react-data-table.date.column.label')}</th>
                                <th >{getFormattedMessage('globally.detail.reference')}</th>
                                <th >{getFormattedMessage("customer.title")}</th>
                                <th >{getFormattedMessage("globally.detail.grand.total")}</th>
                                <th >{getFormattedMessage("globally.detail.payment.status")}</th>
                                <th >{getFormattedMessage("select.payment-type.label")}</th>
                                <th className="text-center">{getFormattedMessage("react-data-table.action.column.label")}</th>
                            </tr>
                            {sales && sales.length <= 0 ?
                                <tr>
                                    <td colSpan={6} className={"custom-text-center"}>{getFormattedMessage("sale.product.table.no-data.label")}</td>
                                </tr>
                                : null}
                            {sales && sales.length > 0 && sales?.map((items, index) => {
                                return (
                                    <tr key={index}>
                                        <td>{ getFormattedDate(
                                                        items.attributes.created_at,
                                                        allConfigData && allConfigData
                                                    )} {moment(items.attributes.created_at).format("LT")}</td>
                                        <td>{items.attributes.reference_code}</td>
                                        <td>{items.attributes.customer_name}</td>
                                        <td>
                                            {currencySymbolHandling(
                                                allConfigData,
                                                frontSetting.value &&
                                                frontSetting.value.currency_symbol,
                                                items.attributes.grand_total
                                            )}
                                        </td>
                                        <td>
                                            <span className={`badge ${items.attributes.payment_status == 1 ? "bg-light-success"
                                                : items.attributes.payment_status == 2 ? "bg-light-danger"
                                                    : items.attributes.payment_status == 3 ? "bg-light-warning" : ""}`}>
                                                <span>
                                                    {items.attributes.payment_status == 1 ? getFormattedMessage(
                                                        "globally.detail.paid"
                                                    ) : items.attributes.payment_status == 2 ? getFormattedMessage(
                                                        "payment-status.filter.unpaid.label"
                                                    ) : items.attributes.payment_status == 3 ? getFormattedMessage(
                                                        "payment-status.filter.partial.label"
                                                    ) : ""}
                                                </span>
                                            </span>
                                        </td>
                                        <td>
                                            <span className={`badge ${items.attributes.payment_type == null || items.attributes.payment_type == 0 ? "text-dark" : 'bg-light-primary'}`}>
                                                <span>
                                                    {paymentMethodName(paymentMethods, items.attributes) ?? '-'}
                                                </span>
                                            </span>
                                        </td>
                                        <td className="text-center">
                                            <FontAwesomeIcon 
                                                onClick={() => editSaleModel(items)} 
                                                cursor={"pointer"} 
                                                className={"me-3 edit"} 
                                                icon={faPenToSquare}
                                            />
                                            {config && config.includes(Permissions.MANAGE_SALE_RETURN) && (
                                                <FontAwesomeIcon 
                                                    onClick={() => handleRefund(items)} 
                                                    cursor={"pointer"} 
                                                    className={"text-danger"} 
                                                    icon={faArrowRotateLeft}
                                                />
                                            )}
                                        </td>
                                    </tr>
                                )
                            })}
                        </tbody>
                    </Table>
                </Modal.Body>
                <Modal.Footer className='pt-0'>
                    {sales.length < totalRecord && !isLoading && (
                        <div className="d-flex justify-content-center w-100 my-3">
                            <button
                                className="btn btn-outline-primary"
                                onClick={handleLoadMore}
                                disabled={loadingMore}
                            >
                                {loadingMore ? getFormattedMessage("loading.title") : getFormattedMessage("load.more.title")}
                            </button>
                        </div>
                    )}
                    {isLoading && (
                        <div className="d-flex justify-content-center w-100 text-primary">
                            <div className="spinner-border" role="status">
                                <span className="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    )}
                </Modal.Footer>
            </Modal>
            {isEdit &&
                <EditHoldConfirmationModal onConfirm={onConfirm} onCancel={() => setIsEdit(false)} icon={warning} title={getFormattedMessage("pos.edit.sale.title")} itemName={getFormattedMessage("product.title")} />}
        </>

    );
}

export default RecentSaleModal;