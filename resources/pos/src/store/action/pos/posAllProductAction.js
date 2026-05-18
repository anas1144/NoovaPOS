import {
    posProductActionType,
    productActionType,
    toastType,
} from "../../../constants";
import apiConfig from "../../../config/apiConfig";
import { addToast } from "../toastAction";
import { setLoading } from "../loadingAction";
import cacheService from "../../../utils/cacheService";

// Cache keys
const CACHE_KEYS = {
    PRODUCTS_ALL: 'products_all',
    PRODUCTS_WAREHOUSE: (warehouseId) => `products_warehouse_${warehouseId}`,
    PRODUCTS_FILTERED: (brandId, categoryId, warehouseId) => 
        `products_filtered_b${brandId}_c${categoryId}_w${warehouseId}`
};

// Cache TTL: 5 minutes for products
const CACHE_TTL = 5 * 60 * 1000;

// Memoization cache for filtered results
let filterMemoCache = {};

// Helper function to filter products on frontend with memoization for instant switching
const filterProducts = (products, brandId, categoryId) => {
    if (!products || !Array.isArray(products)) {
        return [];
    }
    
    // Create a cache key based on the filter parameters
    const cacheKey = `${products.length}_${brandId}_${categoryId}`;
    
    // Return cached result if available
    if (filterMemoCache[cacheKey]) {
        return filterMemoCache[cacheKey];
    }
    
    // Convert to numbers and treat 0/undefined as "no filter"
    const brandIdNum = brandId && brandId !== 0 ? Number(brandId) : 0;
    const categoryIdNum = categoryId && categoryId !== 0 ? Number(categoryId) : 0;
    
    // If both are 0 (no filters), return all products
    if (!brandIdNum && !categoryIdNum) {
        filterMemoCache[cacheKey] = products;
        return products;
    }
    
    // Use a single pass filter with optimized condition checking
    const filtered = products.filter(product => {
        const productBrandId = product.attributes?.brand_id;
        const productCategoryId = product.attributes?.product_category_id;
        
        // Both conditions must match (if filters are specified)
        return (!brandIdNum || Number(productBrandId) === brandIdNum) &&
               (!categoryIdNum || Number(productCategoryId) === categoryIdNum);
    });
    
    // Cache the result
    filterMemoCache[cacheKey] = filtered;
    
    // Keep cache size under control (max 50 entries)
    const cacheKeys = Object.keys(filterMemoCache);
    if (cacheKeys.length > 50) {
        delete filterMemoCache[cacheKeys[0]];
    }
    
    return filtered;
};

// Clear cache when products are updated
export const clearProductCache = () => {
    cacheService.clearByPattern('products_');
    filterMemoCache = {}; // Also clear filter memoization cache
};


export const posAllProductAction = (forceRefresh = false) => async (dispatch) => {
    // Check cache first
    if (!forceRefresh) {
        const cachedData = cacheService.get(CACHE_KEYS.PRODUCTS_ALL);
        if (cachedData) {
            dispatch({
                type: posProductActionType.POS_ALL_PRODUCT,
                payload: cachedData,
            });
            return;
        }
    }

    apiConfig
        .get(`products?page[size]=0`)
        .then((response) => {
            const products = response.data.data;
            
            // Cache the response
            cacheService.set(CACHE_KEYS.PRODUCTS_ALL, products, CACHE_TTL);
            
            dispatch({
                type: posProductActionType.POS_ALL_PRODUCT,
                payload: products,
            });
        })
        .catch(({ response }) => {
            dispatch(
                addToast({ text: response.data.message, type: toastType.ERROR })
            );
        });
};

export const posAllProduct =
    (warehouse, isLoading = true, forceRefresh = false) =>
    async (dispatch) => {
        const cacheKey = CACHE_KEYS.PRODUCTS_WAREHOUSE(warehouse);
        
        // Check cache first
        if (!forceRefresh) {
            const cachedData = cacheService.get(cacheKey);
            if (cachedData) {
                dispatch({
                    type: posProductActionType.POS_ALL_PRODUCTS,
                    payload: cachedData,
                });
                return;
            }
        }

        if (isLoading) {
            dispatch(setLoading(true));
        }
        apiConfig
            .get(`products?page[size]=0&warehouse_id=${warehouse}`)
            .then((response) => {
                const products = response.data.data;
                
                // Cache the products
                cacheService.set(cacheKey, products, CACHE_TTL);
                
                dispatch({
                    type: posProductActionType.POS_ALL_PRODUCTS,
                    payload: products,
                });
                if (isLoading) {
                    dispatch(setLoading(false));
                }
            })
            .catch(({ response }) => {
                dispatch(
                    addToast({
                        text: response.data.message,
                        type: toastType.ERROR,
                    })
                );
                if (isLoading) {
                    dispatch(setLoading(false));
                }
            });
    };

export const fetchBrandClickable =
    (brandId, categoryId, warehouse, isLoading = true) => async (dispatch, getState) => {
        // IMPORTANT: We must get the UNFILTERED warehouse products, not the potentially-filtered posAllProducts
        // Always use cache with warehouse key only, never the filtered Redux state
        
        // Check cache for warehouse products (unfiltered)
        const warehouseCacheKey = CACHE_KEYS.PRODUCTS_WAREHOUSE(warehouse);
        const cachedWarehouseProducts = cacheService.get(warehouseCacheKey);
        
        // If we have cached warehouse products, use them (these are unfiltered)
        // DO NOT show loading state for cached results - apply filter instantly for smooth UX
        if (cachedWarehouseProducts && cachedWarehouseProducts.length > 0) {
            const filteredProducts = filterProducts(cachedWarehouseProducts, brandId, categoryId);
            
            dispatch({
                type: productActionType.FETCH_BRAND_CLICKABLE,
                payload: filteredProducts,
            });
            
            return;
        }
        
        // If no cache, fetch all warehouse products (unfiltered) from API
        if (isLoading) {
            dispatch(setLoading(true));
        }
        
        await apiConfig
            .get(`products?page[size]=0&warehouse_id=${warehouse ? warehouse : ""}`)
            .then((response) => {
                const products = response.data.data;
                
                // Cache the warehouse products (unfiltered - THIS IS IMPORTANT)
                cacheService.set(warehouseCacheKey, products, CACHE_TTL);
                
                // Apply filters on frontend
                const filteredProducts = filterProducts(products, brandId, categoryId);
                
                dispatch({
                    type: productActionType.FETCH_BRAND_CLICKABLE,
                    payload: filteredProducts,
                });
                if (isLoading) {
                    dispatch(setLoading(false));
                }
            })
            .catch(({ response }) => {
                dispatch(
                    addToast({
                        text: response.data.message,
                        type: toastType.ERROR,
                    })
                );
                if (isLoading) {
                    dispatch(setLoading(false));
                }
            });
    };
