import React from "react";
import { Modal, Table, Image } from "react-bootstrap";
import { calculateProductCost } from "../../shared/SharedMethod";
import {
    currencySymbolHandling,
    getFormattedDate,
    getFormattedMessage,
} from "../../../shared/sharedMethod";
import moment from "moment";
import { paymentStatusOptionsConstant, Tokens } from "../../../constants";

const PaymentSlipModal = (props) => {
    const {
        settings,
        modalShowPaymentSlip,
        setModalShowPaymentSlip,
        updateProducts,
        printPaymentReceiptPdf,
        paymentType,
        frontSetting,
        paymentDetails,
        allConfigData,
        setPaymentValue,
        paymentTypeDefaultValue,
        taxes,
        paymentStatus,
    } = props;

    const currency =
        updateProducts.settings &&
        updateProducts.settings.attributes &&
        updateProducts.settings.attributes.currency_symbol;
    
    const updatedLanguage = localStorage.getItem(Tokens.UPDATED_LANGUAGE);

     const hasPaymentDetails =
                Array.isArray(paymentDetails?.attributes?.payments) &&
                paymentDetails?.attributes?.payments?.length > 0;

    return (
        <Modal
            show={modalShowPaymentSlip}
            onHide={() => {
                setModalShowPaymentSlip(false);
                setPaymentValue({
                    payment_type: paymentTypeDefaultValue[0],
                });
            }}
            size="sm"
            aria-labelledby="contained-modal-title-vcenter"
            centered
            className="pos-modal"
        >
            <Modal.Header closeButton className="pb-3">
                <Modal.Title id="contained-modal-title-vcenter">
                    {getFormattedMessage("pos-sale.detail.invoice.info")} POS
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
                                        new Date(),
                                        allConfigData && allConfigData
                                    )}{" "}{moment().format("hh:mm A")}
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
                                        {updateProducts.customer_name &&
                                        updateProducts.customer_name[0]
                                            ? updateProducts.customer_name[0]
                                                  .label
                                            : updateProducts.customer_name &&
                                              updateProducts.customer_name
                                                  .label}
                                    </span>
                                </td>
                            </tr>
                        )}
                    </tbody>
                </Table>
                {updateProducts.products &&
                    updateProducts.products.map((productName, index) => {
                        return (
                            <div key={index + 1}>
                                <div className="p-0">
                                    <div>
                                        {productName.name}{" "}
                                        {parseInt(settings?.attributes?.show_product_code) == 1 ? <span>({productName.code})</span> : ''}
                                    </div>
                                    {settings?.attributes?.show_tax == "1" && <div className="d-flex justify-content-between">
                                        <p className="m-0 ws-6">{getFormattedMessage("product.table.price.column.label")}: {currencySymbolHandling(allConfigData, currency, productName.product_price)}</p>
                                        <p className="m-0 ws-6">{getFormattedMessage("globally.detail.tax")}: {currencySymbolHandling(
                                            allConfigData,
                                            currency,
                                            productName.tax_amount
                                        )} ({productName.tax_value} %) </p>
                                    </div>}
                                </div>
                                <div className="product-border">
                                    <div className="border-0 d-flex justify-content-between">

                                        <span dir="ltr" className="text-black">
                                            {updatedLanguage === "ar"
                                                ? `${calculateProductCost(productName).toFixed(2)} X ${productName.quantity.toFixed(2)}`
                                                : `${productName.quantity.toFixed(2)} X ${calculateProductCost(productName).toFixed(2)}`
                                            }
                                        </span>

                                        <span className="text-end">
                                            {currencySymbolHandling(
                                                allConfigData,
                                                currency,
                                                productName.quantity *
                                                    calculateProductCost(
                                                        productName
                                                    )
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
                            updateProducts.subTotal
                                ? updateProducts.subTotal
                                : "0.00"
                        )}
                    </div>
                </div>

                {parseInt(settings.attributes?.show_tax) ==
                    1 && (
                    <div className="d-flex product-border">
                        <div>
                            {getFormattedMessage("globally.detail.order.tax")}:
                        </div>
                        <div className="text-end ms-auto">
                            {" "}
                            {currencySymbolHandling(
                                allConfigData,
                                currency,
                                updateProducts.taxTotal
                                    ? updateProducts.taxTotal
                                    : "0.00"
                            )}{" "}
                            (
                            {updateProducts
                                ? parseFloat(updateProducts.tax).toFixed(2)
                                : "0.00"}{" "}
                            %)
                        </div>
                    </div>
                )}
                {parseInt(settings.attributes?.show_tax_discount_shipping) ==
                    1 && (
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
                                updateProducts
                                    ? updateProducts.discount
                                    : "0.00"
                            )}
                        </div>
                    </div>
                )}
                {parseInt(settings.attributes?.show_tax_discount_shipping) ==
                    1 && updateProducts.shipping ? (
                    <div className="d-flex product-border">
                        <div>Shipping:</div>
                        <div className="text-end ms-auto">
                            {" "}
                            {currencySymbolHandling(
                                allConfigData,
                                currency,
                                updateProducts
                                    ? updateProducts.shipping
                                    : "0.00"
                            )}
                        </div>
                    </div>
                ) : (
                    ""
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
                            updateProducts.grandTotal
                        )}
                    </div>
                </div>
                {paymentStatus == paymentStatusOptionsConstant.PAID
                    && updateProducts.paid_amount >= parseFloat(updateProducts.grandTotal)
                    ? <Table striped className="mb-0">
                    <thead>
                        <tr>
                            <th className="py-2 px-0">
                                {getFormattedMessage(
                                    "pos-sale.detail.paid-by.title"
                                )}
                            </th>
                            <th className="text-end py-2 px-0">
                                {getFormattedMessage(
                                    "expense.input.amount.label"
                                )}
                            </th>
                            <th className="text-end py-2 px-0">
                                {getFormattedMessage("pos.change-return.label")}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                           {/* Display all payment methods from API */}
                                               {hasPaymentDetails ?
                                                   (
                                                       paymentDetails?.attributes?.payments?.map((payment, index) => (
                                                       <tr key={index} style={{ padding: "none !important" }}>
                                                           <td className="text-end py-2 px-0">
                                                               {payment.payment_method?.name || paymentType}
                                                           </td>
                                                           <td className="text-end py-2 px-0">
                                                               {currencySymbolHandling(allConfigData, currency, payment.amount)}
                                                           </td>
                                                           <td className="text-end py-2 px-0">
                                                               {index === paymentDetails?.attributes?.payments?.length - 1
                                                                   ? currencySymbolHandling(allConfigData, currency, paymentDetails?.attributes?.changeReturn || 0)
                                                                   : "-"
                                                               }
                                                           </td>
                                                       </tr>
                                                   ))
                                               )
                                                   : (
                                                        <tr style={{ padding: "none !important" }}>
                                                       <td className="text-end py-2 px-0">
                                                           {paymentType}
                                                       </td>
                                                       <td className="text-end py-2 px-0">
                                                           {currencySymbolHandling(
                                                               allConfigData,
                                                               currency,
                                                               updateProducts.grandTotal
                                                           )}
                                                       </td>
                                                       <td className="text-end py-2 px-0">
                                                           {currencySymbolHandling(
                                                               allConfigData,
                                                               currency,
                                                               updateProducts.changeReturn
                                                           )}
                                                       </td>
                                                   </tr>
                                                   )
                                               }
                    
                    </tbody>
                </Table> : ""}
                {(paymentStatus == paymentStatusOptionsConstant.PARTIAL || paymentStatus == paymentStatusOptionsConstant.PAID)
                    && updateProducts.paid_amount > 0 &&
                    updateProducts.paid_amount < parseFloat(updateProducts.grandTotal)
                    ? <Table striped className="mb-0">
                    <thead>
                        <tr>
                            <th className="py-2 px-0">
                                {getFormattedMessage(
                                    "pos-sale.detail.paid-by.title"
                                )}
                            </th>
                            <th className="text-end py-2 px-0">
                                {getFormattedMessage(
                                    "globally.detail.paid"
                                )}
                            </th>
                            <th className="text-end py-2 px-0">
                                {getFormattedMessage("globally.detail.due")}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {/* Display all payment methods from API */}
                                               {hasPaymentDetails ?
                                                   (
                                                       paymentDetails?.attributes?.payments?.map((payment, index) => (
                                                       <tr key={index} style={{ padding: "none !important" }}>
                                                           <td className="text-end py-2 px-0">
                                                               {payment.payment_method?.name || paymentType}
                                                           </td>
                                                           <td className="text-end py-2 px-0">
                                                               {currencySymbolHandling(allConfigData, currency, payment.amount)}
                                                           </td>
                                                           <td className="text-end py-2 px-0">
                                                               {index === paymentDetails?.attributes?.payments?.length - 1
                                                                   ? currencySymbolHandling(allConfigData, currency, paymentDetails?.attributes?.changeReturn || 0)
                                                                   : "-"
                                                               }
                                                           </td>
                                                       </tr>
                                                   ))
                                               )
                                                   : (
                                                        <tr style={{ padding: "none !important" }}>
                                                       <td className="text-end py-2 px-0">
                                                           {paymentType}
                                                       </td>
                                                       <td className="text-end py-2 px-0">
                                                           {currencySymbolHandling(
                                                               allConfigData,
                                                               currency,
                                                               updateProducts.grandTotal
                                                           )}
                                                       </td>
                                                       <td className="text-end py-2 px-0">
                                                           {currencySymbolHandling(
                                                               allConfigData,
                                                               currency,
                                                               updateProducts.changeReturn
                                                           )}
                                                       </td>
                                                   </tr>
                                                   )
                                               }
                    </tbody>
                </Table> : ''}
                {paymentStatus == paymentStatusOptionsConstant.UNPAID ?
                    <div className="text-center py-3 mb-3">
                        <h5 className="text-danger fw-bold">
                            {getFormattedMessage("payment-status.filter.unpaid.label")}
                        </h5>
                        <div className="text-muted">
                            {getFormattedMessage("sale-Due.total.amount.title")}: {currencySymbolHandling(
                                allConfigData,
                                currency,
                                updateProducts.grandTotal
                            )}
                        </div>
                    </div>
                : ''}
                {updateProducts && updateProducts.note ? (
                    <div className="d-flex product-border mb-5">
                        <div className="fw-bolder">Notes:</div>
                        <div className="ms-2 mb-2 product-border__product-width">
                            {updateProducts && updateProducts.note}
                        </div>
                    </div>
                ) : (
                    ""
                )}
               {parseInt(settings.attributes?.show_note) == 1 &&  <h5 className="text-center font-label">
                    {settings.attributes?.notes
                        ? settings.attributes?.notes
                        : getFormattedMessage("pos-thank.you-slip.invoice")}
                </h5>}
                <div className="text-center d-block">
                    {parseInt(settings.attributes?.show_barcode_in_receipt) ==
                        1 && (
                        <Image
                            src={
                                paymentDetails &&
                                paymentDetails.attributes.barcode_url
                            }
                            className=""
                            height={25}
                            width={100}
                        />
                    )}
                    <span className="d-block">
                        {paymentDetails &&
                            paymentDetails.attributes.reference_code}
                    </span>
                </div>
            </Modal.Body>
            <Modal.Footer className="justify-content-center pt-2">
                <button
                    className="btn btn-primary text-white"
                    onClick={printPaymentReceiptPdf}
                >
                    {getFormattedMessage("print.title")}
                </button>
                <button
                    className="btn btn-secondary"
                    onClick={() => {
                        setModalShowPaymentSlip(false);
                        setPaymentValue({
                            payment_type: paymentTypeDefaultValue[0],
                        });
                    }}
                >
                    {getFormattedMessage("pos-close-btn.title")}
                </button>
            </Modal.Footer>
        </Modal>
    );
};
export default PaymentSlipModal;
