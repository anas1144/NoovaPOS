import { describe, it, expect } from "vitest";
import { prepareCartArray } from "../frontend/shared/PrepareCartArray";

const product = (overrides = {}) => ({
    id: 1,
    attributes: {
        name: "Sample",
        code: "S-1",
        product_price: 100,
        product_cost: 60,
        tax_type: 1,
        order_tax: 0,
        tier_prices: {},
        stock: { quantity: 5 },
        product_unit: 1,
        sale_unit: 1,
        quantity_limit: 0,
        ...overrides,
    },
});

describe("prepareCartArray", () => {
    it("prices a product at the retail (base) tier by default", () => {
        const cart = prepareCartArray([product()]);
        expect(cart).toHaveLength(1);
        expect(cart[0].product_price).toBe(100);
        expect(cart[0].net_unit_cost).toBe(100);
        expect(cart[0].base_price).toBe(100);
    });

    it("applies a per-product tier override when a non-retail tier is chosen", () => {
        const cart = prepareCartArray([product({ tier_prices: { wholesale: 85 } })], "wholesale");
        expect(cart[0].product_price).toBe(85);
        expect(cart[0].base_price).toBe(100); // base preserved for re-pricing
    });

    it("falls back to base price if the tier has no override", () => {
        const cart = prepareCartArray([product({ tier_prices: {} })], "wholesale");
        expect(cart[0].product_price).toBe(100);
    });
});
