import React, { useState, useEffect } from 'react';
import { connect, useDispatch } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import moment from 'moment';
import { InputGroup, Table } from 'react-bootstrap-v5';
import { editTransfer, postTransfer } from '../../store/action/transfersAction';
import TransfersTable from '../../shared/components/transfers/TransfersTable';
import { prepareTransferArray } from '../../shared/prepareArray/prepareTransferArray';
import { decimalValidate, getFormattedMessage, placeholderText, onFocusInput, getFormattedOptions } from '../../shared/sharedMethod';
import { calculateCartTotalAmount, calculateCartTotalTaxAmount } from '../../shared/calculation/calculation';
import ModelFooter from '../../shared/components/modelFooter';
import ProductSearch from '../../shared/components/product-cart/search/ProductSearch';
import { addToast } from '../../store/action/toastAction';
import { toastType, transferCreatStatusOptions } from '../../constants';
import ReactDatePicker from '../../shared/datepicker/ReactDatePicker';
import ProductMainCalculation from '../sales/ProductMainCalculation';
import ReactSelect from '../../shared/select/reactSelect';
import { fetchProductsByWarehouse } from "../../store/action/productAction";
import PostConfirmationModal from "../../shared/components/modals/PostConfirmationModal";

const TransferForm = ( props ) => {
    const {
        addTtansferData,
        id,
        editTransfer,
        postTransfer,
        customProducts,
        singleTransfer,
        warehouses,
        fetchProductsByWarehouse,
        products, frontSetting, allConfigData
    } = props;
    const navigate = useNavigate();
    const dispatch = useDispatch();
    const [ newCost, setNewCost ] = useState( '' );
    const [ newDiscount, setNewDiscount ] = useState( '' );
    const [ newTax, setNewTax ] = useState( '' );
    const [ newPurchaseUnit, setNewPurchaseUnit ] = useState( '' );
    const [ subTotal, setSubTotal ] = useState( '' );
    const [ updateProducts, setUpdateProducts ] = useState( [] );
    const [ quantity, setQuantity ] = useState( 0 );
    const [transferValueUpdated, setTransferValueUpdated] = useState(false);
    const [isPosting, setIsPosting] = useState(false);
    const [showPostConfirm, setShowPostConfirm] = useState(false);

    const [transferValue, setTransferValue] = useState({
        date: new Date(),
        from_warehouse_id: '',
        to_warehouse_id: '',
        warehouse_id: undefined,
        supplier_id: '',
        tax_rate: '0.00',
        tax_amount: '0.00',
        discount: '0.00',
        shipping: '0.00',
        grand_total: '0.00',
        notes: '',
        posted_status: 0,
        status_id: {
            label: getFormattedMessage("status.filter.complated.label"),
            value: 1
        },
    });

    const [ errors, setErrors ] = useState( {
        date: '',
        from_warehouse_id: '',
        to_warehouse_id: '',
        supplier_id: '',
        details: '',
        tax_rate: '',
        discount: '',
        shipping: '',
        status_id: ''
    } );

    useEffect(() => {
        if (singleTransfer && !transferValueUpdated) {
            setTransferValue({
                date: moment(singleTransfer.date).toDate(),
                from_warehouse_id: singleTransfer.from_warehouse_id,
                to_warehouse_id: singleTransfer.to_warehouse_id,
                warehouse_id: undefined,
                supplier_id: singleTransfer.supplier_id,
                tax_rate: singleTransfer.tax_rate.toFixed(2),
                tax_amount: singleTransfer.tax_amount.toFixed(2),
                discount: singleTransfer.discount.toFixed(2),
                shipping: singleTransfer.shipping.toFixed(2),
                grand_total: singleTransfer.grand_total,
                notes: singleTransfer.notes,
                posted_status: singleTransfer.posted_status || 0,
                status_id: singleTransfer.status_id || {
                    label: getFormattedMessage("status.filter.complated.label"),
                    value: 1
                }
            });
            setTransferValueUpdated(true);
        }
    }, [singleTransfer]);

    useEffect( () => {
        setUpdateProducts( updateProducts );
    }, [ updateProducts, quantity, newCost, newDiscount, newTax, subTotal, newPurchaseUnit ] );

    useEffect( () => {
        if ( singleTransfer && !transferValueUpdated ) {
            setUpdateProducts( singleTransfer.transfer_items );
            setTransferValueUpdated(true);
        }
    }, [singleTransfer] );

    useEffect( () => {
        updateProducts.length >= 1 ? dispatch( { type: 'DISABLE_OPTION', payload: true } ) : dispatch( { type: 'DISABLE_OPTION', payload: false } )
    }, [ singleTransfer ] )

    useEffect( () => {
        transferValue.from_warehouse_id.value ? fetchProductsByWarehouse( transferValue.from_warehouse_id.value ) : null
        transferValue.from_warehouse_id.value ? setTransferValue( inputs => ( { ...inputs, warehouse_id: transferValue.from_warehouse_id } ) ) : null
    }, [ transferValue.from_warehouse_id ] )

    const handleValidation = () => {
        let errorss = {};
        let isValid = false;
        const qtyCart = updateProducts.filter( ( a ) => a.quantity === 0 );
        if ( !transferValue.date ) {
            error[ 'date' ] = getFormattedMessage( 'globally.date.validate.label' );
        } else if ( !transferValue.from_warehouse_id ) {
            errorss[ 'from_warehouse_id' ] = getFormattedMessage( 'purchase.select.warehouse.validate.label' )
        } else if ( !transferValue.to_warehouse_id ) {
            errorss[ 'to_warehouse_id' ] = getFormattedMessage( 'purchase.select.warehouse.validate.label' )
        } else if ( transferValue.from_warehouse_id.value === transferValue.to_warehouse_id.value ) {
            errorss[ 'to_warehouse_id' ] = getFormattedMessage( "transfer.select.warehouse.validate.message" )
        } else if ( qtyCart.length > 0 ) {
            dispatch( addToast( {
                text: getFormattedMessage( 'globally.product-quantity.validate.message' ),
                type: toastType.ERROR
            } ) )
        } else if ( updateProducts.length < 1 ) {
            dispatch( addToast( {
                text: getFormattedMessage( 'purchase.product-list.validate.message' ),
                type: toastType.ERROR
            } ) )
        } else if ( !transferValue.status_id ) {
            errorss[ 'status_id' ] = getFormattedMessage( 'globally.status.validate.label' )
        } else {
            isValid = true;
        }
        setErrors( errorss );
        return isValid;
    };

    const onWarehouseChangeOne = ( obj ) => {
        setTransferValue( inputs => ( { ...inputs, from_warehouse_id: obj } ) )
        setErrors( '' )
    };

    const onWarehouseChangeTow = ( obj ) => {
        setTransferValue( inputs => ( { ...inputs, to_warehouse_id: obj } ) )
        setErrors( '' )
    };

    const onStatusChange = ( obj ) => {
        setTransferValue( inputs => ( { ...inputs, status_id: obj } ) )
    };

    const updateCost = ( item ) => {
        setNewCost( item );
    };

    const updateDiscount = ( item ) => {
        setNewDiscount( item );
    };

    const updateTax = ( item ) => {
        setNewTax( item );
    };

    const onChangeInput = ( e ) => {
        e.preventDefault();
        const { value } = e.target;
        // check if value includes a decimal point
        if ( value.match( /\./g ) ) {
            const [ , decimal ] = value.split( '.' );
            // restrict value to only 2 decimal places
            if ( decimal?.length > 2 ) {
                // do nothing
                return;
            }
        }
        setTransferValue( inputs => ( { ...inputs, [ e.target.name ]: value && value } ) )
    };

    const onNotesChangeInput = ( e ) => {
        e.preventDefault();
        setTransferValue( inputs => ( { ...inputs, notes: e.target.value } ) )
    }

    const handleCallback = ( date ) => {
        setTransferValue( previousState => {
            return { ...previousState, date: date }
        } );
        setErrors( '' )
    };

    const updatedQty = ( qty ) => {
        setQuantity( qty );
    };

    const updateSubTotal = ( item ) => {
        setSubTotal( item );
    };

    const updatePurchaseUnit = ( item ) => {
        setNewPurchaseUnit( item );
    };

    const transferStatusFilterOptions = getFormattedOptions( transferCreatStatusOptions )

    const prepareData = ( prepareData ) => {
        const formValue = {
            from_warehouse_id: prepareData.from_warehouse_id.value ? prepareData.from_warehouse_id.value : prepareData.from_warehouse_id,
            to_warehouse_id: prepareData.to_warehouse_id.value ? prepareData.to_warehouse_id.value : prepareData.to_warehouse_id,
            date: moment( prepareData.date ).locale('en').toDate(),
            transfer_items: updateProducts,
            note: prepareData.notes,
            discount: prepareData.discount,
            tax_rate: prepareData.tax_rate,
            tax_amount: calculateCartTotalTaxAmount( updateProducts, transferValue ),
            shipping: prepareData.shipping,
            grand_total: calculateCartTotalAmount( updateProducts, transferValue ),
            received_amount: 0,
            paid_amount: 0,
            status: prepareData.status_id.value ? prepareData.status_id.value : prepareData.status_id,
        }
        return formValue
    };

    const onSubmit = ( event ) => {
        event.preventDefault();
        const valid = handleValidation();
        if ( valid ) {
            if ( singleTransfer ) {
                editTransfer( id, prepareData( transferValue ), navigate );
            } else {
                addTtansferData( prepareData( transferValue ) );
                setTransferValue( transferValue );
            }
        }
    };

    const handlePostTransfer = () => {
        if (transferValue.posted_status === 1) {
            dispatch(
                addToast({
                    text: getFormattedMessage("globally.post.already_posted") || "This transfer has already been posted.",
                    type: toastType.WARNING,
                })
            );
            return;
        }
        setShowPostConfirm(true);
    };

    const handleConfirmPost = () => {
        setIsPosting(true);
        postTransfer(id, navigate, () => {
            setIsPosting(false);
            setShowPostConfirm(false);
        });
    };

    const onBlurInput = ( el ) => {
        if ( el.target.value === '' ) {
            if ( el.target.name === 'shipping' ) {
                setTransferValue( { ...transferValue, shipping: '0.00' } )
            }
            if ( el.target.name === 'discount' ) {
                setTransferValue( { ...transferValue, discount: '0.00' } )
            }
            if ( el.target.name === 'tax_rate' ) {
                setTransferValue( { ...transferValue, tax_rate: '0.00' } )
            }
        }
    }

    return (
        <div className='card'>
            <div className='card-body'>
                {/*<Form>*/}
                <div className='row'>
                    <div className='col-md-4'>
                        <label className='form-label'>
                            {getFormattedMessage( 'react-data-table.date.column.label' )}:
                        </label>
                        <span className='required' />
                        <div className='position-relative'>
                            <ReactDatePicker onChangeDate={handleCallback} newStartDate={transferValue.date} />
                        </div>
                        <span className='text-danger d-block fw-400 fs-small mt-2'>{errors[ 'date' ] ? errors[ 'date' ] : null}</span>
                    </div>
                    <div className='col-md-4 mb-3'>
                        <ReactSelect data={warehouses} onChange={onWarehouseChangeOne}
                            value={transferValue.from_warehouse_id}
                            defaultValue={transferValue.from_warehouse_id} addSearchItems={singleTransfer}
                            isWarehouseDisable={true}
                            title={getFormattedMessage( 'transfer.from-warehouse.title' )} errors={errors[ 'from_warehouse_id' ]}
                            placeholder={placeholderText( 'purchase.select.warehouse.placeholder.label' )} />
                    </div>
                    <div className='col-md-4 mb-3'>
                        <ReactSelect data={warehouses} onChange={onWarehouseChangeTow}
                            value={transferValue.to_warehouse_id}
                            defaultValue={transferValue.to_warehouse_id} addSearchItems={singleTransfer}
                            isWarehouseDisable={true}
                            title={getFormattedMessage( 'transfer.to-warehouse.title' )} errors={errors[ 'to_warehouse_id' ]}
                            placeholder={placeholderText( 'purchase.select.warehouse.placeholder.label' )} />
                    </div>
                    <div className='col-md-12 mb-3'>
                        <label className='form-label'>
                            {getFormattedMessage( 'dashboard.stockAlert.product.label' )}:
                        </label>
                        <ProductSearch values={transferValue} products={products} isAllProducts={false}
                            handleValidation={handleValidation} updateProducts={updateProducts}
                            setUpdateProducts={setUpdateProducts} customProducts={customProducts} />
                    </div>
                    <div className='col-12 md-12'>
                        <label
                            className='form-label'>
                            {getFormattedMessage( 'purchase.order-item.table.label' )}:
                        </label>
                        <span className='required ' />
                        <Table responsive>
                            <thead>
                                <tr>
                                    <th>{getFormattedMessage( 'dashboard.stockAlert.product.label' )}</th>
                                    <th>{getFormattedMessage( 'purchase.order-item.table.net-unit-cost.column.label' )}</th>
                                    <th>{getFormattedMessage( 'purchase.order-item.table.stock.column.label' )}</th>
                                    <th className='text-lg-start text-center'>{getFormattedMessage( 'purchase.order-item.table.qty.column.label' )}</th>
                                    <th>{getFormattedMessage( 'purchase.order-item.table.discount.column.label' )}</th>
                                    <th>{getFormattedMessage( 'purchase.order-item.table.tax.column.label' )}</th>
                                    <th>{getFormattedMessage( 'purchase.order-item.table.sub-total.column.label' )}</th>
                                    <th>{getFormattedMessage( 'react-data-table.action.column.label' )}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {updateProducts && updateProducts.map( ( singleProduct, index ) => {
                                    return <TransfersTable singleProduct={singleProduct} index={index}
                                        updateQty={updatedQty}
                                        updateCost={updateCost} updateDiscount={updateDiscount}
                                        updateProducts={updateProducts}
                                        updateSubTotal={updateSubTotal} frontSetting={frontSetting}
                                        setUpdateProducts={setUpdateProducts} updateTax={updateTax}
                                        updatePurchaseUnit={updatePurchaseUnit}
                                        transferItem={singleTransfer && singleTransfer.purchase_items}
                                    />
                                } )}
                                {!updateProducts.length && <tr>
                                    <td colSpan={8} className='fs-5 px-3 py-6 custom-text-center'>
                                        {getFormattedMessage( 'sale.product.table.no-data.label' )}
                                    </td>
                                </tr>
                                }
                            </tbody>
                        </Table>
                    </div>
                    <div className='col-12'>
                        <ProductMainCalculation inputValues={transferValue} updateProducts={updateProducts}
                            frontSetting={frontSetting} allConfigData={allConfigData} />
                    </div>
                    <div className='col-md-4 mb-5'>
                        <label className='form-label'>
                            {getFormattedMessage( 'purchase.input.order-tax.label' )}:
                        </label>
                        <InputGroup>
                            <input aria-label='Dollar amount (with dot and two decimal places)'
                                className='form-control'
                                onBlur={( event ) => onBlurInput( event )}
                                onFocus={( event ) => onFocusInput( event )}
                                value={transferValue.tax_rate} type='text' name='tax_rate'
                                onKeyPress={( event ) => decimalValidate( event )}
                                onChange={( e ) => {
                                    onChangeInput( e )
                                }} />
                            <InputGroup.Text>%</InputGroup.Text>
                        </InputGroup>
                        <span className='text-danger d-block fw-400 fs-small mt-2'>{errors[ 'orderTax' ] ? errors[ 'orderTax' ] : null}</span>
                    </div>
                    <div className='col-md-4 mb-5'>
                        <label className='form-label'>
                            {getFormattedMessage( 'purchase.order-item.table.discount.column.label' )}:
                        </label>
                        <InputGroup>
                            <input aria-label='Dollar amount (with dot and two decimal places)'
                                className='form-control'
                                onBlur={( event ) => onBlurInput( event )}
                                onFocus={( event ) => onFocusInput( event )}
                                value={transferValue.discount} type='text' name='discount'
                                onKeyPress={( event ) => decimalValidate( event )}
                                onChange={( e ) => onChangeInput( e )}
                            />
                            <InputGroup.Text>{frontSetting.value && frontSetting.value.currency_symbol}</InputGroup.Text>
                        </InputGroup>
                        <span className='text-danger d-block fw-400 fs-small mt-2'>{errors[ 'discount' ] ? errors[ 'discount' ] : null}</span>
                    </div>
                    <div className='col-md-4 mb-5'>
                        <label
                            className='form-label'>
                            {getFormattedMessage( 'purchase.input.shipping.label' )}:
                        </label>
                        <InputGroup>
                            <input aria-label='Dollar amount (with dot and two decimal places)'
                                className='form-control' value={transferValue.shipping}
                                type='text' name='shipping'
                                onBlur={( event ) => onBlurInput( event )}
                                onFocus={( event ) => onFocusInput( event )}
                                onKeyPress={( event ) => decimalValidate( event )}
                                onChange={( e ) => onChangeInput( e )}
                            />
                            <InputGroup.Text>{frontSetting.value && frontSetting.value.currency_symbol}</InputGroup.Text>
                        </InputGroup>
                        <span className='text-danger d-block fw-400 fs-small mt-2'>{errors[ 'shipping' ] ? errors[ 'shipping' ] : null}</span>
                    </div>
                    <div className='col-md-4 mb-3'>
                        <ReactSelect multiLanguageOption={transferStatusFilterOptions}
                            name='status' onChange={onStatusChange}
                            title={getFormattedMessage( 'purchase.select.status.label' )}
                            defaultValue={transferValue.status_id} errors={errors[ 'status_id' ]}
                            placeholder={placeholderText( 'purchase.select.status.placeholder.label' )} />
                    </div>
                    <div className='col-md-12 mb-5'>
                        <label className='form-label'>
                            {getFormattedMessage( 'globally.input.notes.label' )}:
                        </label>
                        <textarea name='notes' className='form-control'
                            placeholder={placeholderText( 'purchase.placeholder.notes.input' )}
                            onChange={( e ) => onNotesChangeInput( e )}
                            value={transferValue.notes}
                        />
                        <span className='text-danger d-block fw-400 fs-small mt-2'>{errors[ 'notes' ] ? errors[ 'notes' ] : null}</span>
                    </div>
                    {singleTransfer && (
                        <div className="alert alert-info mb-3">
                            <strong>Status:</strong>{" "}
                            <span className={transferValue.posted_status === 1 ? "text-success fw-bold" : "text-warning fw-bold"}>
                                {transferValue.posted_status === 1 ? getFormattedMessage("globally.status.posted") : getFormattedMessage("globally.status.draft")}
                            </span>
                        </div>
                    )}
                    <div className="d-flex mt-5 justify-content-end gap-2">
                        <button
                            onClick={onSubmit}
                            className="btn btn-primary"
                            type="submit"
                        >
                            {placeholderText("globally.save-btn")}
                        </button>
                        {singleTransfer && transferValue.posted_status === 0 && (
                            <button
                                onClick={handlePostTransfer}
                                className="btn btn-success"
                                type="button"
                                disabled={isPosting}
                            >
                                {isPosting ? getFormattedMessage("globally.post.posting") || "Posting..." : getFormattedMessage("globally.post.button") || "Post Transfer"}
                            </button>
                        )}
                        <a href="#/app/transfers" className="btn btn-secondary">
                            {getFormattedMessage("globally.cancel-btn")}
                        </a>
                    </div>
                </div>
                {/*</Form>*/}
            </div>
            {showPostConfirm && (
                <PostConfirmationModal
                    onConfirm={handleConfirmPost}
                    onCancel={() => setShowPostConfirm(false)}
                    title={getFormattedMessage("globally.post.label") || "Post Transfer"}
                />
            )}
        </div>
    )
};

const mapStateToProps = ( state ) => {
    const { products, frontSetting, allConfigData } = state;
    return { customProducts: prepareTransferArray( products ), products, frontSetting, allConfigData }
};

export default connect( mapStateToProps, { editTransfer, postTransfer, fetchProductsByWarehouse } )( TransferForm );
