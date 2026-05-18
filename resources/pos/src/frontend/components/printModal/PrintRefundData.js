import React from "react";
import { Table, Image } from "react-bootstrap-v5";
import "../../../assets/scss/frontend/pdf.scss";
import {
    currencySymbolHandling,
    getFormattedDate,
    getFormattedMessage,
} from "../../../shared/sharedMethod";
import moment from "moment";
import { Tokens } from "../../../constants";

class PrintRefundData extends React.PureComponent {
    render() {
        const { refundData, saleData, settings, frontSetting, allConfigData, taxes, responseData } = this.props;
        const currency = frontSetting?.value?.currency_symbol;
        const updatedLanguage = localStorage.getItem(Tokens.UPDATED_LANGUAGE);

        return (
            <div
                className="print-data"
                style={{
                    padding: "none !important",
                }}
            >
                <style>
                    {`
                        @media print {
                            body, html {
                                background: white !important;
                                margin: 0 !important;
                                padding: 0 !important;
                            }
                            * {
                                -webkit-print-color-adjust: exact !important;
                                color-adjust: exact !important;
                            }
                        }
                    `}
                </style>
                <div className="mt-4 mb-4 text-black text-center">
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
                <div
                    className="mt-4 mb-4 text-black text-center"
                    style={{
                        fontSize: "24px",
                        fontWeight: "600",
                        marginBottom: "15px !important",
                    }}
                >
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
                
                <div 
                    className="text-center mb-3"
                    style={{
                        fontSize: "18px",
                        fontWeight: "600",
                        color: "#dc3545",
                        marginTop: "10px"
                    }}
                >
                    REFUND RECEIPT
                </div>

                <section className="product-border">
                    <div
                        style={{
                            marginBottom: "4px",
                        }}
                    >
                        <span className="fw-bold me-2">
                            {getFormattedMessage(
                                "react-data-table.date.column.label"
                            )}
                            :
                        </span>
                        <span>
                            {getFormattedDate(
                                refundData.date,
                                allConfigData && allConfigData
                            )}{" "}{moment().format("hh:mm A")}
                        </span>
                    </div>
                    <div
                        style={{
                            marginBottom: "4px",
                        }}
                    >
                        <span className="fw-bold me-2">
                            Original Sale:
                        </span>
                        <span>
                            {saleData.attributes?.reference_code}
                        </span>
                    </div>
                    {settings &&
                        parseInt(settings.attributes.show_address) == 1 && (
                            <div
                                style={{
                                    marginBottom: "4px",
                                }}
                            >
                                <span className="fw-bold me-2">
                                    {getFormattedMessage(
                                        "supplier.table.address.column.title"
                                    )}
                                    :
                                </span>
                                <span>
                                    {frontSetting.value &&
                                        frontSetting.value.address}
                                </span>
                            </div>
                        )}
                    {settings &&
                        parseInt(settings.attributes.show_email) == 1 && (
                            <div
                                style={{
                                    marginBottom: "4px",
                                }}
                            >
                                <span className="fw-bold me-2">
                                    {getFormattedMessage(
                                        "globally.input.email.label"
                                    )}
                                    :
                                </span>
                                <span>
                                    {frontSetting.value &&
                                        frontSetting.value.email}
                                </span>
                            </div>
                        )}
                    {settings &&
                        parseInt(settings.attributes.show_phone) == 1 && (
                            <div
                                style={{
                                    marginBottom: "4px",
                                }}
                            >
                                <span className="fw-bold me-2">
                                    {getFormattedMessage(
                                        "pos-sale.detail.Phone.info"
                                    )}
                                    :
                                </span>
                                <span>
                                    {frontSetting.value &&
                                        frontSetting.value.phone}
                                </span>
                            </div>
                        )}
                    {settings &&
                        parseInt(settings.attributes.show_customer) == 1 && (
                            <div style={{}}>
                                <span className="fw-bold me-2">
                                    {getFormattedMessage(
                                        "dashboard.recentSales.customer.label"
                                    )}
                                    :
                                </span>
                                <span>
                                    {saleData.attributes?.customer_name}
                                </span>
                            </div>
                        )}
                </section>

                <section className="mt-3">
                    {refundData.sale_return_items &&
                        refundData.sale_return_items.map((item, index) => {
                            return (
                                <div key={index + 1}>
                                    <div className="p-0">
                                        {item.product_name}{" "}
                                        {settings &&
                                        parseInt(settings.attributes.show_product_code) == 1 && item.product_code ? (
                                            <span>({item.product_code})</span>
                                        ) : (
                                            ""
                                        )}
                                    </div>
                                    {settings?.attributes?.show_tax == "1" && <div className="d-flex justify-content-between">
                                        <p className="m-0 ws-6">{getFormattedMessage("product.table.price.column.label")}: {currencySymbolHandling(allConfigData, currency, item.product_price)}</p>
                                        <p className="m-0 ws-6">{getFormattedMessage("globally.detail.tax")}: {currencySymbolHandling(
                                            allConfigData,
                                            currency,
                                            item.tax_value || 0
                                        )} ({item.tax_type || 0} %) </p>
                                    </div>}
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
                </section>

                <section className="mt-3 product-border">
                    <div className="d-flex">
                        <div
                            style={{
                                fontWeight: "500",
                                color: "#000000",
                            }}
                        >
                            {getFormattedMessage("pos-total-amount.title")}:
                        </div>
                        <div className="text-end ms-auto">
                            {currencySymbolHandling(
                                allConfigData,
                                currency,
                                refundData.grand_total - (refundData.tax_amount || 0)
                            )}
                        </div>
                    </div>
                    {settings &&
                        parseInt(settings.attributes.show_tax) == 1 && refundData.tax_amount > 0 && (
                            <div className="d-flex">
                                <div
                                    style={{
                                        fontWeight: "500",
                                        color: "#000000",
                                    }}
                                >
                                    {getFormattedMessage(
                                        "globally.detail.order.tax"
                                    )}
                                    :{" "}
                                    {Number(refundData.tax_rate) > 0
                                        ? `(${Number(refundData.tax_rate).toFixed(2)}%)`
                                        : null}
                                </div>
                                <div className="text-end ms-auto">
                                    {currencySymbolHandling(
                                        allConfigData,
                                        currency,
                                        refundData.tax_amount || 0
                                    )}
                                </div>
                            </div>
                        )}
                    {settings &&
                        parseInt(settings.attributes.show_tax_discount_shipping) == 1 && refundData.discount > 0 && (
                            <div className="d-flex">
                                <div
                                    style={{
                                        fontWeight: "500",
                                        color: "#000000",
                                    }}
                                >
                                    {getFormattedMessage(
                                        "globally.detail.discount"
                                    )}
                                    :
                                </div>
                                <div className="text-end ms-auto">
                                    {currencySymbolHandling(
                                        allConfigData,
                                        currency,
                                        refundData.discount || 0
                                    )}
                                </div>
                            </div>
                        )}
                    {settings &&
                        parseInt(settings.attributes.show_tax_discount_shipping) == 1 &&
                        parseFloat(refundData.shipping) !== 0.0 && (
                            <div className="d-flex">
                                <div
                                    style={{
                                        fontWeight: "500",
                                        color: "#000000",
                                    }}
                                >
                                    {getFormattedMessage(
                                        "globally.detail.shipping"
                                    )}
                                    :
                                </div>
                                <div className="text-end ms-auto">
                                    {currencySymbolHandling(
                                        allConfigData,
                                        currency,
                                        refundData.shipping || 0
                                    )}
                                </div>
                            </div>
                        )}
                    <div className="d-flex">
                        <div
                            style={{
                                fontWeight: "500",
                                color: "#000000",
                            }}
                        >
                            {getFormattedMessage("globally.detail.grand.total")}:
                        </div>
                        <div className="text-end ms-auto">
                            {currencySymbolHandling(
                                allConfigData,
                                currency,
                                refundData.grand_total
                            )}
                        </div>
                    </div>
                </section>

                {/*note section*/}
                {refundData && refundData.note ? (
                    <Table>
                        <tbody>
                            <tr
                                style={{
                                    border: "0",
                                }}
                            >
                                <td
                                    scope="row"
                                    style={{
                                        padding: "none !important",
                                        fontSize: "15px",
                                    }}
                                >
                                    <span
                                        style={{
                                            padding: "none !important",
                                            fontSize: "15px",
                                            verticalAlign: "top",
                                            display: "inline-block",
                                            color: "#000000",
                                        }}
                                    >
                                        Refund Reason :
                                    </span>
                                    <p
                                        style={{
                                            fontSize: "15px",
                                            verticalAlign: "top",
                                            display: "inline-block",
                                            padding: "none !important",
                                            color: "#000000",
                                        }}
                                    >
                                        {refundData.note}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </Table>
                ) : (
                    ""
                )}
                {settings &&
                    parseInt(settings.attributes.show_note) == 1 && (
                        <h3
                            style={{
                                textAlign: "center",
                                color: "#000000",
                                padding: "none !important",
                            }}
                        >
                            {settings.attributes.notes
                                ? settings.attributes.notes
                                : ""}
                        </h3>
                    )}
                <div className="text-center d-block">
                    {settings &&
                        parseInt(settings.attributes?.show_barcode_in_receipt) == 1 && 
                        responseData?.barcode_url && (
                            <Image
                                src={responseData.barcode_url}
                                alt={responseData.reference_code}
                                height={25}
                                width={100}
                            />
                        )}
                    <span
                        className="d-block"
                        style={{
                            color: "#000000",
                            padding: "none !important",
                        }}
                    >
                        {responseData?.reference_code}
                    </span>
                </div>
            </div>
        );
    }
}

export default PrintRefundData;
