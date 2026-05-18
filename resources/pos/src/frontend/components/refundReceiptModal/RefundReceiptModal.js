import React, { useRef } from 'react';
import { Modal, Table, Image } from 'react-bootstrap';
import { useSelector } from 'react-redux';
import { useReactToPrint } from 'react-to-print';
import { calculateProductCost } from '../../shared/SharedMethod';
import { 
    currencySymbolHandling, 
    getFormattedMessage,
    getFormattedDate 
} from '../../../shared/sharedMethod';
import moment from 'moment';
import { Tokens } from '../../../constants';
import PrintRefundData from '../printModal/PrintRefundData';

const RefundReceiptModal = ({ show, onHide, refundData, saleData, responseData }) => {
    const { allConfigData, frontSetting, settings, taxes } = useSelector(state => state);
    const printRef = useRef();

    if (!refundData || !saleData) return null;

    const currency = frontSetting?.value?.currency_symbol;
    const updatedLanguage = localStorage.getItem(Tokens.UPDATED_LANGUAGE);

    const handlePrint = useReactToPrint({
        content: () => printRef.current,
    });

    return (
        <>
            <div className="d-none">
                <PrintRefundData
                    ref={printRef}
                    refundData={refundData}
                    saleData={saleData}
                    settings={settings}
                    frontSetting={frontSetting}
                    allConfigData={allConfigData}
                    taxes={taxes}
                    responseData={responseData}
                />
            </div>
            <Modal
                show={show}
                onHide={onHide}
                size="sm"
                aria-labelledby="contained-modal-title-vcenter"
                centered
                className="pos-modal"
            >
                <Modal.Header closeButton className="pb-3">
                    <Modal.Title id="contained-modal-title-vcenter">
                        {getFormattedMessage("pos-sale.detail.invoice.info")} - REFUND
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body className="pt-0 pb-3">
                    <div className="mt-4 mb-4 text-black text-center fs-1">
                        {settings.attributes &&
                        parseInt(settings.attributes.show_logo_in_receipt) == 1 ? (
                            <img
                                src={settings.attributes.logo}
                                alt=""
                                width="100px"
                            />
                        ) : (
                            ""
                        )}
                    </div>
                    <div className="mt-4 mb-4 text-black text-center fs-1">
                        {settings?.attributes?.store_name}
                    </div>
                    <div className="mb-2">
                        {taxes?.length > 0 && taxes
                            ?.filter((tax) => tax.attributes.status == 1)
                            ?.map((tax, index) => (
                                <div key={index} className="text-center fw-semibold ">
                                    <p className="fs-6 text-body-tertiary mb-0">{tax.attributes.name}: <span className="fs-6">{tax.attributes.number}</span></p>
                                </div>
                            ))}
                    </div>
                    
                    <div className="text-center mb-3">
                        <h5 className="text-danger fw-bold mb-2">REFUND RECEIPT</h5>
                    </div>

                    <Table>
                        <tbody>
                            <tr>
                                <td scope="row" className="p-0">
                                    <span>
                                        {getFormattedMessage(
                                            "react-data-table.date.column.label"
                                        )}
                                        :
                                    </span>
                                    <span className="ms-2 font-label">
                                        {getFormattedDate(
                                            refundData.date,
                                            allConfigData && allConfigData
                                        )}{" "}{moment().format("hh:mm A")}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td scope="row" className="p-0">
                                    <span>Original Sale:</span>
                                    <span className="ms-2 font-label">
                                        {saleData.attributes?.reference_code}
                                    </span>
                                </td>
                            </tr>
                            {parseInt(settings.attributes?.show_address) == 1 && (
                                <tr>
                                    <td scope="row" className="p-0">
                                        <span className="address__label d-inline-block">
                                            {getFormattedMessage(
                                                "supplier.table.address.column.title"
                                            )}
                                            :
                                        </span>
                                        <span className="ms-2 address__value d-inline-block font-label">
                                            {frontSetting.value &&
                                                frontSetting.value.address}
                                        </span>
                                    </td>
                                </tr>
                            )}
                            {parseInt(settings.attributes?.show_email) == 1 && (
                                <tr>
                                    <td scope="row" className="p-0">
                                        <span>
                                            {getFormattedMessage(
                                                "globally.input.email.label"
                                            )}
                                            :
                                        </span>
                                        <span className="ms-2 font-label">
                                            {frontSetting.value &&
                                                frontSetting.value.email}
                                        </span>
                                    </td>
                                </tr>
                            )}
                            {parseInt(settings.attributes?.show_phone) == 1 && (
                                <tr>
                                    <td scope="row" className="p-0">
                                        <span>
                                            {getFormattedMessage(
                                                "pos-sale.detail.Phone.info"
                                            )}
                                            :
                                        </span>
                                        <span className="ms-2 font-label">
                                            {frontSetting.value &&
                                                frontSetting.value.phone}
                                        </span>
                                    </td>
                                </tr>
                            )}
                            {parseInt(settings.attributes?.show_customer) == 1 && (
                                <tr>
                                    <td scope="row" className="p-0">
                                        <span>
                                            {" "}
                                            {getFormattedMessage(
                                                "dashboard.recentSales.customer.label"
                                            )}
                                            :{" "}
                                        </span>
                                        <span className="ms-2 font-label">
                                            {saleData.attributes?.customer_name}
                                        </span>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </Table>

                    {refundData.sale_return_items &&
                        refundData.sale_return_items.map((item, index) => {
                            return (
                                <div key={index + 1}>
                                    <div className="p-0">
                                        <div>
                                            {item.product_name}{" "}
                                            {parseInt(settings?.attributes?.show_product_code) == 1 && item.product_code ? <span>({item.product_code})</span> : ''}
                                        </div>
                                        {settings?.attributes?.show_tax == "1" && <div className="d-flex justify-content-between">
                                            <p className="m-0 ws-6">{getFormattedMessage("product.table.price.column.label")}: {currencySymbolHandling(allConfigData, currency, item.product_price)}</p>
                                            <p className="m-0 ws-6">{getFormattedMessage("globally.detail.tax")}: {currencySymbolHandling(
                                                allConfigData,
                                                currency,
                                                item.tax_value || 0
                                            )} ({item.tax_type || 0} %) </p>
                                        </div>}
                                    </div>
                                    <div className="product-border">
                                        <div className="border-0 d-flex justify-content-between">
                                            <span dir="ltr" className="text-black">
                                                {updatedLanguage === "ar"
                                                    ? `${parseFloat(item.net_unit_price).toFixed(2)} X ${parseFloat(item.quantity).toFixed(2)}`
                                                    : `${parseFloat(item.quantity).toFixed(2)} X ${parseFloat(item.net_unit_price).toFixed(2)}`
                                                }
                                            </span>
                                            <span className="text-end">
                                                {currencySymbolHandling(
                                                    allConfigData,
                                                    currency,
                                                    item.sub_total
                                                )}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}

                    <div className="d-flex product-border">
                        <div>{getFormattedMessage("pos-total-amount.title")}:</div>
                        <div className="text-end ms-auto">
                            {" "}
                            {currencySymbolHandling(
                                allConfigData,
                                currency,
                                refundData.grand_total - (refundData.tax_amount || 0)
                            )}
                        </div>
                    </div>

                    {parseInt(settings.attributes?.show_tax) == 1 && refundData.tax_amount > 0 && (
                        <div className="d-flex product-border">
                            <div>
                                {getFormattedMessage("globally.detail.order.tax")}:
                            </div>
                            <div className="text-end ms-auto">
                                {" "}
                                {currencySymbolHandling(
                                    allConfigData,
                                    currency,
                                    refundData.tax_amount || 0
                                )}{" "}
                                (
                                {refundData.tax_rate
                                    ? parseFloat(refundData.tax_rate).toFixed(2)
                                    : "0.00"}{" "}
                                %)
                            </div>
                        </div>
                    )}

                    {parseInt(settings.attributes?.show_tax_discount_shipping) == 1 && refundData.discount > 0 && (
                        <div className="d-flex product-border">
                            <div>
                                {getFormattedMessage(
                                    "purchase.order-item.table.discount.column.label"
                                )}
                                :
                            </div>
                            <div className="text-end ms-auto">
                                {" "}
                                {currencySymbolHandling(
                                    allConfigData,
                                    currency,
                                    refundData.discount || 0
                                )}
                            </div>
                        </div>
                    )}

                    {parseInt(settings.attributes?.show_tax_discount_shipping) == 1 && refundData.shipping > 0 && (
                        <div className="d-flex product-border">
                            <div>Shipping:</div>
                            <div className="text-end ms-auto">
                                {" "}
                                {currencySymbolHandling(
                                    allConfigData,
                                    currency,
                                    refundData.shipping || 0
                                )}
                            </div>
                        </div>
                    )}

                    <div className="d-flex product-border">
                        <div>
                            {getFormattedMessage("purchase.grant-total.label")}:
                        </div>
                        <div className="text-end ms-auto">
                            {" "}
                            {currencySymbolHandling(
                                allConfigData,
                                currency,
                                refundData.grand_total
                            )}
                        </div>
                    </div>

                    {refundData.note ? (
                        <div className="d-flex product-border mb-5">
                            <div className="fw-bolder">Refund Reason:</div>
                            <div className="ms-2 mb-2 product-border__product-width">
                                {refundData.note}
                            </div>
                        </div>
                    ) : (
                        ""
                    )}

                    {parseInt(settings.attributes?.show_note) == 1 && (
                        <h5 className="text-center font-label">
                            {settings.attributes?.notes
                                ? settings.attributes?.notes
                                : getFormattedMessage("pos-thank.you-slip.invoice")}
                        </h5>
                    )}

                    {responseData && (
                        <div className="text-center d-block">
                            {parseInt(settings.attributes?.show_barcode_in_receipt) == 1 && responseData.barcode_url && (
                                <Image
                                    src={responseData.barcode_url}
                                    className=""
                                    height={25}
                                    width={100}
                                />
                            )}
                            <span className="d-block">
                                {responseData.reference_code}
                            </span>
                        </div>
                    )}
                </Modal.Body>
                <Modal.Footer className="justify-content-center pt-2">
                    <button
                        className="btn btn-primary text-white"
                        onClick={handlePrint}
                    >
                        {getFormattedMessage("print.title")}
                    </button>
                    <button
                        className="btn btn-secondary"
                        onClick={onHide}
                    >
                        {getFormattedMessage("pos-close-btn.title")}
                    </button>
                </Modal.Footer>
            </Modal>
        </>
    );
};

export default RefundReceiptModal;
