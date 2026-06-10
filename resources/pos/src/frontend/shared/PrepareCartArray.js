export const prepareCartArray = (products, priceTier = 'retail') => {

    let cartProductRowArray = [];
    // Effective unit price for the chosen tier: a per-product tier override
    // (tier_prices[tier]) when set, otherwise the base (retail) product_price.
    const tierPrice = (product) => {
        const tp = product.attributes.tier_prices || {};
        const v = priceTier && priceTier !== 'retail' ? tp[priceTier] : undefined;
        return (v !== undefined && v !== null && v !== '') ? Number(v) : product.attributes.product_price;
    };
    products.forEach(product => {
        const unitPrice = tierPrice(product);
        const taxAmount = (unitPrice) => {
            const total = Number(unitPrice);
            let tax = 0;

            if (product.attributes.order_tax > 0) {
                if (product.attributes.tax_type === 1 || product.attributes.tax_type === '1') {
                    tax = (total * Number(product.attributes.order_tax)) / 100;
                } else if (product.attributes.tax_type === 2 || product.attributes.tax_type === '2') {
                    tax = total - (total / (1 + Number(product.attributes.order_tax) / 100));
                }
            }

            return parseFloat(tax.toFixed(2));
        };
        
        cartProductRowArray.push({
            name: product.attributes.name,
            code: product.attributes.code,
            stock_alert: product.attributes.stock_alert,
            product_id: product.id,
            product_cost: product.attributes.product_cost,
            net_unit_cost: unitPrice,
            tax_type: product.attributes.tax_type.value ? Number(product.attributes.tax_type.value) : product.attributes.tax_type,
            product_price: unitPrice,
            tax_amount: taxAmount(unitPrice),
            base_price: product.attributes.product_price,
            tier_prices: product.attributes.tier_prices || {},
            discount_type: 1,
            discount_value: 0,
            discount_amount: 0,
            product_unit: product.attributes.product_unit,
            sale_unit: product.attributes.sale_unit,
            quantity: product.attributes.stock.quantity > 1 ? 1 : product.attributes.stock.quantity,
            sub_total: 0,
            id: product.id,
            sale_id: 1,
            tax_value: product.attributes.order_tax ?? 0,
            hold_item_id: '',
            quantity_limit: product.attributes.quantity_limit,
            warehouse_id: 0
        })
    });
    return cartProductRowArray;
};
