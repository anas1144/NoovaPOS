import React from "react";
import { Table, Image } from "react-bootstrap-v5";
import { calculateProductCost } from "../../shared/SharedMethod";
import "../../../assets/scss/frontend/pdf.scss";
import {
    currencySymbolHandling,
    getFormattedDate,
    getFormattedMessage,
} from "../../../shared/sharedMethod";
import moment from "moment";
import { paymentStatusOptionsConstant, Tokens } from "../../../constants";
class PrintData extends React.PureComponent {
    render() {
        const paymentPrint = this.props.updateProducts;
        const allConfigData = this.props.allConfigData;
        const paymentType = this.props.paymentType;
        const taxes = this.props.taxes;
        const currency =
            paymentPrint.settings &&
            paymentPrint.settings.attributes &&
            paymentPrint.settings.attributes.currency_symbol;
        
        const updatedLanguage = localStorage.getItem(Tokens.UPDATED_LANGUAGE);

        const hasPaymentDetails =
                Array.isArray(paymentPrint?.payment_details) &&
                paymentPrint.payment_details.length > 0;


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
                    {paymentPrint.settings &&
                    parseInt(
                        paymentPrint.settings.attributes.show_logo_in_receipt
                    ) == 1 ? (
                        <img
                            src={
                                paymentPrint.settings &&
                                paymentPrint.settings.attributes.logo
                            }
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
                    {paymentPrint.settings &&
                        paymentPrint.settings?.attributes?.store_name}
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
                                new Date(),
                                allConfigData && allConfigData
                            )}{" "}{moment().format("hh:mm A")}
                        </span>
                    </div>
                    {paymentPrint.settings &&
                        parseInt(
                            paymentPrint.settings.attributes.show_address
                        ) == 1 && (
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
                                    {paymentPrint.frontSetting &&
                                        paymentPrint.frontSetting.value.address}
                                </span>
                            </div>
                        )}
                    {paymentPrint.settings &&
                        parseInt(
                            paymentPrint.settings.attributes.show_email
                        ) == 1 && (
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
                                    {paymentPrint.frontSetting &&
                                        paymentPrint.frontSetting.value.email}
                                </span>
                            </div>
                        )}
                    {paymentPrint.settings &&
                        parseInt(
                            paymentPrint.settings.attributes.show_phone
                        ) == 1 && (
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
                                    {paymentPrint.frontSetting &&
                                        paymentPrint.frontSetting.value.phone}
                                </span>
                            </div>
                        )}
                    {paymentPrint.settings &&
                        parseInt(
                            paymentPrint.settings.attributes.show_customer
                        ) == 1 && (
                            <div style={{}}>
                                <span className="fw-bold me-2">
                                    {getFormattedMessage(
                                        "dashboard.recentSales.customer.label"
                                    )}
                                    :
                                </span>
                                <span>
                                    {paymentPrint.customer_name &&
                                    paymentPrint.customer_name[0]
                                        ? paymentPrint.customer_name[0].label
                                        : paymentPrint.customer_name &&
                                          paymentPrint.customer_name.label}
                                </span>
                            </div>
                        )}
                </section>

                <section className="mt-3">
                    {paymentPrint.products &&
                        paymentPrint.products.map((productName, index) => {
                            return (
                                <div key={index + 1}>
                                    <div className="p-0">
                                        {productName.name}{" "}
                                        {paymentPrint.settings &&
                                        parseInt(
                                            paymentPrint.settings.attributes
                                                .show_product_code
                                        ) == 1 ? (
                                            <span>({productName.code})</span>
                                        ) : (
                                            ""
                                        )}
                                    </div>
                                    {paymentPrint?.settings?.attributes?.show_tax == "1" && <div className="d-flex justify-content-between">
                                        <p className="m-0 ws-6">{getFormattedMessage("product.table.price.column.label")}: {currencySymbolHandling(allConfigData, currency, productName.product_price)}</p>
                                        <p className="m-0 ws-6">{getFormattedMessage("globally.detail.tax")}: {currencySymbolHandling(
                                            allConfigData,
                                            currency,
                                            productName.tax_amount
                                        )} ({productName.tax_value} %) </p>
                                    </div>}
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
                                paymentPrint.subTotal
                                    ? paymentPrint.subTotal
                                    : "0.00"
                            )}
                        </div>
                    </div>
                    {paymentPrint.settings &&
                        parseInt(
                            paymentPrint.settings.attributes
                                .show_tax
                        ) == 1 && (
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
                                    {Number(paymentPrint.tax) > 0
                                        ? paymentPrint
                                            ? `(${Number(
                                                  paymentPrint.tax
                                              ).toFixed(2)}%)`
                                            : "(0.00%)"
                                        : null}
                                </div>
                                <div className="text-end ms-auto">
                                    {currencySymbolHandling(
                                        allConfigData,
                                        currency,
                                        paymentPrint.taxTotal
                                            ? paymentPrint.taxTotal
                                            : "0.00"
                                    )}
                                </div>
                            </div>
                        )}
                    {paymentPrint.settings &&
                        parseInt(
                            paymentPrint.settings.attributes
                                .show_tax_discount_shipping
                        ) == 1 && (
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
                                        paymentPrint
                                            ? paymentPrint.discount
                                            : "0.00"
                                    )}
                                </div>
                            </div>
                        )}
                    {paymentPrint.settings &&
                        parseInt(
                            paymentPrint.settings.attributes
                                .show_tax_discount_shipping
                        ) == 1 &&
                        parseFloat(paymentPrint.shipping) !== 0.0 && (
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
                                        paymentPrint
                                            ? paymentPrint.shipping
                                            : "0.00"
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
                            {getFormattedMessage("globally.detail.grand.total")}
                            :
                        </div>
                        <div className="text-end ms-auto">
                            {currencySymbolHandling(
                                allConfigData,
                                currency,
                                paymentPrint.grandTotal
                            )}
                        </div>
                    </div>
                </section>

                {paymentPrint?.payment_status?.value == paymentStatusOptionsConstant.PAID
                    && paymentPrint.paid_amount >= parseFloat(paymentPrint.grandTotal)
                    ? <Table
                    style={{
                        padding: "none !important",
                        marginTop: "20px !important",
                    }}
                >
                    <thead>
                        <tr
                            style={{
                                padding: "none !important",
                            }}
                        >
                            <th
                                className="fw-bold"
                                style={{
                                    textAlign: "start",
                                    padding: "8px 15px",

                                    color: "#000000",
                                }}
                            >
                                {getFormattedMessage(
                                    "pos-sale.detail.paid-by.title"
                                )}
                            </th>
                            <th
                                className="fw-bold"
                                style={{
                                    textAlign: "center",
                                    padding: "8px 15px",

                                    color: "#000000",
                                }}
                            >
                                {getFormattedMessage(
                                    "expense.input.amount.label"
                                )}
                            </th>
                            <th
                                className="fw-bold"
                                style={{
                                    textAlign: "end",
                                    padding: "8px 15px",

                                    color: "#000000",
                                }}
                            >
                                {getFormattedMessage("pos.change-return.label")}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {/* Display all payment methods from API */}
                        {hasPaymentDetails ?
                            (
                                paymentPrint.payment_details.map((payment, index) => (
                                <tr key={index} style={{ padding: "none !important" }}>
                                    <td className="text-end py-2 px-0">
                                        {payment.payment_method?.name || paymentType}
                                    </td>
                                    <td className="text-end py-2 px-0">
                                        {currencySymbolHandling(allConfigData, currency, payment.amount)}
                                    </td>
                                    <td className="text-end py-2 px-0">
                                        {index === paymentPrint.payment_details.length - 1
                                            ? currencySymbolHandling(allConfigData, currency, paymentPrint.changeReturn || 0)
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
                                        paymentPrint.grandTotal
                                    )}
                                </td>
                                <td className="text-end py-2 px-0">
                                    {currencySymbolHandling(
                                        allConfigData,
                                        currency,
                                        paymentPrint.changeReturn
                                    )}
                                </td>
                            </tr> 
                            )
                        }
                    </tbody>
                </Table> : ""}

                {(paymentPrint?.payment_status?.value == paymentStatusOptionsConstant.PARTIAL || paymentPrint?.payment_status?.value == paymentStatusOptionsConstant.PAID)
                    && paymentPrint.paid_amount > 0
                    && paymentPrint.paid_amount < parseFloat(paymentPrint.grandTotal)
                    ? <Table
                    style={{
                        padding: "none !important",
                        marginTop: "20px !important",
                    }}
                >
                    <thead>
                        <tr
                            style={{
                                padding: "none !important",
                            }}
                        >
                            <th
                                className="fw-bold"
                                style={{
                                    textAlign: "start",
                                    padding: "8px 15px",

                                    color: "#000000",
                                }}
                            >
                                {getFormattedMessage(
                                    "pos-sale.detail.paid-by.title"
                                )}
                            </th>
                            <th
                                className="fw-bold"
                                style={{
                                    textAlign: "center",
                                    padding: "8px 15px",

                                    color: "#000000",
                                }}
                            >
                                {getFormattedMessage(
                                    "globally.detail.paid"
                                )}
                            </th>
                            <th
                                className="fw-bold"
                                style={{
                                    textAlign: "end",
                                    padding: "8px 15px",

                                    color: "#000000",
                                }}
                            >
                                {getFormattedMessage("globally.detail.due")}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                       {/* Display all payment methods from API */}
                        {hasPaymentDetails ?
                            (
                                paymentPrint.payment_details.map((payment, index) => (
                                <tr key={index} style={{ padding: "none !important" }}>
                                    <td className="text-end py-2 px-0">
                                        {payment.payment_method?.name || paymentType}
                                    </td>
                                    <td className="text-end py-2 px-0">
                                        {currencySymbolHandling(allConfigData, currency, payment.amount)}
                                    </td>
                                    <td className="text-end py-2 px-0">
                                        {index === paymentPrint.payment_details.length - 1
                                            ? currencySymbolHandling(allConfigData, currency, paymentPrint.changeReturn || 0)
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
                                        paymentPrint.grandTotal
                                    )}
                                </td>
                                <td className="text-end py-2 px-0">
                                    {currencySymbolHandling(
                                        allConfigData,
                                        currency,
                                        paymentPrint.changeReturn
                                    )}
                                </td>
                            </tr> 
                            )
                        }
                    </tbody>
                </Table> : ''}
                
                {paymentPrint?.payment_status?.value == paymentStatusOptionsConstant.UNPAID ?
                    <div style={{
                        textAlign: "center",
                        padding: "20px 0",
                        marginBottom: "15px"
                    }}>
                        <h3 style={{
                            color: "#dc3545",
                            fontWeight: "bold",
                            marginBottom: "10px"
                        }}>
                            {getFormattedMessage("payment-status.filter.unpaid.label")}
                        </h3>
                        <div style={{
                            color: "#6c757d"
                        }}>
                            {getFormattedMessage("sale-Due.total.amount.title")}: {currencySymbolHandling(
                                allConfigData,
                                currency,
                                paymentPrint.grandTotal
                            )}
                        </div>
                    </div> : ''}    

                {/*note section*/}
                {paymentPrint && paymentPrint.note ? (
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
                                        {getFormattedMessage(
                                            "globally.input.notes.label"
                                        )}{" "}
                                        :
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
                                        {paymentPrint && paymentPrint.note}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </Table>
                ) : (
                    ""
                )}
                {paymentPrint.settings &&
                    parseInt(paymentPrint.settings.attributes.show_note) ==
                        1 && (
                        <h3
                            style={{
                                textAlign: "center",
                                color: "#000000",
                                padding: "none !important",
                            }}
                        >
                            {paymentPrint.settings &&
                            paymentPrint.settings.attributes.notes
                                ? paymentPrint.settings &&
                                  paymentPrint.settings.attributes.notes
                                : ""}
                        </h3>
                    )}
                <div className="text-center d-block">
                    {paymentPrint.settings &&
                        parseInt(
                            paymentPrint.settings.attributes
                                ?.show_barcode_in_receipt
                        ) == 1 && (
                            <Image
                                src={paymentPrint && paymentPrint.barcode_url}
                                alt={
                                    paymentPrint && paymentPrint.reference_code
                                }
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
                        {paymentPrint && paymentPrint.reference_code}
                    </span>
                </div>
            </div>
        );
    }
}

export default PrintData;
