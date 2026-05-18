import React, { useState, useEffect } from 'react';
import { Modal, Button, Form, Table, InputGroup } from 'react-bootstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPlus, faMinus } from '@fortawesome/free-solid-svg-icons';
import { useSelector } from 'react-redux';
import { currencySymbolHandling, getFormattedMessage } from '../../../shared/sharedMethod';
import moment from 'moment';

const QuickRefundModal = ({ show, onHide, saleData, onConfirmRefund }) => {
    const { allConfigData, frontSetting } = useSelector(state => state);
    const [refundItems, setRefundItems] = useState([]);
    const [refundReason, setRefundReason] = useState('');
    const [totalRefund, setTotalRefund] = useState(0);
    const [isProcessing, setIsProcessing] = useState(false);
    const [printReceipt, setPrintReceipt] = useState(true);

    useEffect(() => {
        if (saleData && saleData.attributes) {
            // Initialize refund items from sale data
            const saleItems = saleData.attributes.sale_items || saleData.attributes.saleItems || [];
            const items = saleItems.map(item => {
                // Extract sale_unit ID - it can be an object with 'id' or a direct number
                let saleUnitId = 1; // Default unit ID
                if (item.sale_unit) {
                    if (typeof item.sale_unit === 'object' && item.sale_unit.id) {
                        saleUnitId = parseInt(item.sale_unit.id);
                    } else if (typeof item.sale_unit === 'number') {
                        saleUnitId = item.sale_unit;
                    } else if (typeof item.sale_unit === 'string') {
                        saleUnitId = parseInt(item.sale_unit);
                    }
                }

                return {
                    id: item.id,
                    product_id: item.product_id,
                    product_name: item.product?.name || item.product_name || item.name || 'Unknown Product',
                    product_code: item.product?.code || item.product_code || item.code || '',
                    sale_unit: saleUnitId,
                    quantity: 0, // Start with 0, user will select
                    max_quantity: parseFloat(item.quantity || 0),
                    product_price: parseFloat(item.product_price || item.net_unit_price || 0),
                    net_unit_price: parseFloat(item.net_unit_price || item.product_price || 0),
                    tax_value: parseFloat(item.tax_value || item.order_tax || 0),
                    tax_type: item.tax_type || 1,
                    discount_value: parseFloat(item.discount_value || item.discount_amount || 0),
                    discount_type: item.discount_type || 1,
                    sub_total: 0
                };
            });
            setRefundItems(items);
        } else {
            // Reset when modal closes
            setRefundItems([]);
            setRefundReason('');
            setTotalRefund(0);
        }
    }, [saleData]);

    useEffect(() => {
        // Calculate total refund amount
        const total = refundItems.reduce((sum, item) => sum + item.sub_total, 0);
        setTotalRefund(total);
    }, [refundItems]);

    const handleQuantityChange = (index, newQuantity) => {
        const updatedItems = [...refundItems];
        const item = updatedItems[index];
        
        // Ensure quantity is within valid range
        const quantity = Math.max(0, Math.min(newQuantity, item.max_quantity));
        item.quantity = quantity;
        
        // Calculate subtotal
        let itemTotal = item.net_unit_price * quantity;
        
        // Apply tax
        if (item.tax_value > 0) {
            if (item.tax_type === 1) {
                // Exclusive tax
                itemTotal += (itemTotal * item.tax_value) / 100;
            }
            // Inclusive tax is already in price
        }
        
        // Apply discount
        if (item.discount_value > 0) {
            if (item.discount_type === 1) {
                // Percentage discount
                itemTotal -= (itemTotal * item.discount_value) / 100;
            } else {
                // Fixed discount
                itemTotal -= item.discount_value * quantity;
            }
        }
        
        item.sub_total = parseFloat(itemTotal.toFixed(2));
        setRefundItems(updatedItems);
    };

    const incrementQuantity = (index) => {
        const item = refundItems[index];
        handleQuantityChange(index, item.quantity + 1);
    };

    const decrementQuantity = (index) => {
        const item = refundItems[index];
        handleQuantityChange(index, item.quantity - 1);
    };

    const handleConfirm = async () => {
        // Filter only items with quantity > 0
        const itemsToRefund = refundItems.filter(item => item.quantity > 0);
        
        if (itemsToRefund.length === 0) {
            alert('Please select at least one item to refund');
            return;
        }
        
        if (!refundReason.trim()) {
            alert('Please provide a reason for the refund');
            return;
        }
        
        setIsProcessing(true);
        
        // Calculate tax amount from items
        const taxAmount = itemsToRefund.reduce((sum, item) => {
            if (item.tax_type === 1 && item.tax_value > 0) {
                // Exclusive tax
                return sum + ((item.net_unit_price * item.quantity * item.tax_value) / 100);
            }
            return sum;
        }, 0);
        
        // Prepare refund data matching backend structure
        const refundData = {
            date: moment().format('YYYY-MM-DD'),
            warehouse_id: saleData.attributes.warehouse_id,
            customer_id: saleData.attributes.customer_id,
            discount: 0,
            tax_rate: 0,
            tax_amount: parseFloat(taxAmount.toFixed(2)),
            shipping: 0,
            grand_total: parseFloat(totalRefund.toFixed(2)),
            received_amount: 0,
            paid_amount: 0,
            payment_type: null,
            status: 1, // Received status
            note: refundReason,
            sale_id: saleData.id,
            sale_reference: saleData.attributes.reference_code,
            posted_status: 1, // Default to Draft - stock only updates when posted
            sale_return_items: itemsToRefund.map(item => ({
                product_id: item.product_id,
                product_name: item.product_name,
                product_code: item.product_code,
                product_price: item.product_price,
                net_unit_price: item.net_unit_price,
                quantity: item.quantity,
                sale_unit: item.sale_unit,
                tax_value: item.tax_value,
                tax_type: item.tax_type,
                discount_value: item.discount_value,
                discount_type: item.discount_type,
                sub_total: item.sub_total
            }))
        };
        
        try {
            await onConfirmRefund(refundData, printReceipt);
        } finally {
            setIsProcessing(false);
        }
    };

    if (!saleData) {
        return (
            <Modal show={show} onHide={onHide} size="xl">
                <Modal.Header closeButton>
                    <Modal.Title>Loading Sale Details...</Modal.Title>
                </Modal.Header>
                <Modal.Body className="text-center py-5">
                    <div className="spinner-border text-primary" role="status">
                        <span className="visually-hidden">Loading...</span>
                    </div>
                    <p className="mt-3">Please wait...</p>
                </Modal.Body>
            </Modal>
        );
    }

    return (
        <Modal show={show} onHide={onHide} size="xl">
            <Modal.Header closeButton>
                <Modal.Title>Quick Refund - {saleData.attributes?.reference_code}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="mb-3">
                    <div className="row">
                        <div className="col-md-6">
                            <strong>Customer:</strong> {saleData.attributes?.customer_name}
                        </div>
                        <div className="col-md-6">
                            <strong>Sale Date:</strong> {moment(saleData.attributes?.created_at).format('DD/MM/YYYY HH:mm')}
                        </div>
                    </div>
                    <div className="row mt-2">
                        <div className="col-md-6">
                            <strong>Original Total:</strong> {currencySymbolHandling(
                                allConfigData,
                                frontSetting.value?.currency_symbol,
                                saleData.attributes?.grand_total
                            )}
                        </div>
                    </div>
                </div>

                <Table responsive bordered className="mt-3">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Code</th>
                            <th>Price</th>
                            <th>Available Qty</th>
                            <th style={{ width: '180px' }}>Refund Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        {refundItems.map((item, index) => (
                            <tr key={index}>
                                <td>{item.product_name}</td>
                                <td>{item.product_code}</td>
                                <td>
                                    {currencySymbolHandling(
                                        allConfigData,
                                        frontSetting.value?.currency_symbol,
                                        item.net_unit_price
                                    )}
                                </td>
                                <td>{item.max_quantity}</td>
                                <td>
                                    <InputGroup size="sm">
                                        <Button 
                                            variant="outline-secondary" 
                                            onClick={() => decrementQuantity(index)}
                                            disabled={item.quantity <= 0}
                                        >
                                            <FontAwesomeIcon icon={faMinus} />
                                        </Button>
                                        <Form.Control
                                            type="number"
                                            min="0"
                                            max={item.max_quantity}
                                            value={item.quantity}
                                            onChange={(e) => handleQuantityChange(index, parseInt(e.target.value) || 0)}
                                            className="text-center"
                                        />
                                        <Button 
                                            variant="outline-secondary" 
                                            onClick={() => incrementQuantity(index)}
                                            disabled={item.quantity >= item.max_quantity}
                                        >
                                            <FontAwesomeIcon icon={faPlus} />
                                        </Button>
                                    </InputGroup>
                                </td>
                                <td>
                                    {currencySymbolHandling(
                                        allConfigData,
                                        frontSetting.value?.currency_symbol,
                                        item.sub_total
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colSpan="5" className="text-end"><strong>Total Refund:</strong></td>
                            <td>
                                <strong>
                                    {currencySymbolHandling(
                                        allConfigData,
                                        frontSetting.value?.currency_symbol,
                                        totalRefund
                                    )}
                                </strong>
                            </td>
                        </tr>
                    </tfoot>
                </Table>

                <Form.Group className="mt-3">
                    <Form.Label>Refund Reason *</Form.Label>
                    <Form.Control
                        as="textarea"
                        rows={3}
                        value={refundReason}
                        onChange={(e) => setRefundReason(e.target.value)}
                        placeholder="Enter reason for refund..."
                        required
                    />
                </Form.Group>

                <Form.Group className="mt-3">
                    <Form.Check
                        type="checkbox"
                        id="printReceiptCheck"
                        label="Print Receipt"
                        checked={printReceipt}
                        onChange={(e) => setPrintReceipt(e.target.checked)}
                    />
                </Form.Group>
            </Modal.Body>
            <Modal.Footer>
                <Button variant="secondary" onClick={onHide} disabled={isProcessing}>
                    Cancel
                </Button>
                <Button 
                    variant="danger" 
                    onClick={handleConfirm}
                    disabled={totalRefund === 0 || !refundReason.trim() || isProcessing}
                >
                    {isProcessing ? 'Processing...' : 'Process Refund'}
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

export default QuickRefundModal;
