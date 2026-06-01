/**
 * Cache Service Utility
 * Provides localStorage/sessionStorage caching with TTL support
 * for frontend data caching
 */

const DEFAULT_TTL = 5 * 60 * 1000; // 5 minutes in milliseconds

class CacheService {
    constructor() {
        this.storage = localStorage;
        this.prefix = 'noovapos_cache_';
    }

    /**
     * Set cache with TTL
     * @param {string} key - Cache key
     * @param {any} value - Value to cache
     * @param {number} ttl - Time to live in milliseconds (default: 5 minutes)
     */
    set(key, value, ttl = DEFAULT_TTL) {
        try {
            const item = {
                value: value,
                timestamp: Date.now(),
                ttl: ttl
            };
            this.storage.setItem(this.prefix + key, JSON.stringify(item));
        } catch (error) {
            console.error('Cache set error:', error);
            // Handle storage quota exceeded
            if (error.name === 'QuotaExceededError') {
                this.clear();
                // Try again after clearing
                try {
                    const item = {
                        value: value,
                        timestamp: Date.now(),
                        ttl: ttl
                    };
                    this.storage.setItem(this.prefix + key, JSON.stringify(item));
                } catch (retryError) {
                    console.error('Cache retry failed:', retryError);
                }
            }
        }
    }

    /**
     * Get cached value
     * @param {string} key - Cache key
     * @returns {any|null} Cached value or null if expired/not found
     */
    get(key) {
        try {
            const item = this.storage.getItem(this.prefix + key);
            
            if (!item) {
                return null;
            }

            const cached = JSON.parse(item);
            const now = Date.now();

            // Check if cache has expired
            if (now - cached.timestamp > cached.ttl) {
                this.remove(key);
                return null;
            }

            return cached.value;
        } catch (error) {
            console.error('Cache get error:', error);
            return null;
        }
    }

    /**
     * Remove cache entry
     * @param {string} key - Cache key
     */
    remove(key) {
        try {
            this.storage.removeItem(this.prefix + key);
        } catch (error) {
            console.error('Cache remove error:', error);
        }
    }

    /**
     * Clear all cache with our prefix
     */
    clear() {
        try {
            const keys = Object.keys(this.storage);
            keys.forEach(key => {
                if (key.startsWith(this.prefix)) {
                    this.storage.removeItem(key);
                }
            });
        } catch (error) {
            console.error('Cache clear error:', error);
        }
    }

    /**
     * Clear cache by pattern
     * @param {string} pattern - Pattern to match (e.g., 'product_')
     */
    clearByPattern(pattern) {
        try {
            const keys = Object.keys(this.storage);
            const fullPattern = this.prefix + pattern;
            keys.forEach(key => {
                if (key.startsWith(fullPattern)) {
                    this.storage.removeItem(key);
                }
            });
        } catch (error) {
            console.error('Cache clear by pattern error:', error);
        }
    }

    /**
     * Check if cache exists and is valid
     * @param {string} key - Cache key
     * @returns {boolean}
     */
    has(key) {
        return this.get(key) !== null;
    }

    /**
     * Get cache info (timestamp, ttl)
     * @param {string} key - Cache key
     * @returns {object|null}
     */
    getInfo(key) {
        try {
            const item = this.storage.getItem(this.prefix + key);
            if (!item) {
                return null;
            }

            const cached = JSON.parse(item);
            const now = Date.now();
            const age = now - cached.timestamp;
            const remaining = cached.ttl - age;

            return {
                age: age,
                remaining: remaining > 0 ? remaining : 0,
                expired: remaining <= 0,
                timestamp: cached.timestamp,
                ttl: cached.ttl
            };
        } catch (error) {
            console.error('Cache info error:', error);
            return null;
        }
    }

    /**
     * Update cache TTL without changing the value
     * @param {string} key - Cache key
     * @param {number} newTtl - New TTL in milliseconds
     */
    updateTTL(key, newTtl) {
        const value = this.get(key);
        if (value !== null) {
            this.set(key, value, newTtl);
        }
    }

    /**
     * Get all cache keys
     * @returns {string[]}
     */
    getAllKeys() {
        try {
            const keys = Object.keys(this.storage);
            return keys
                .filter(key => key.startsWith(this.prefix))
                .map(key => key.replace(this.prefix, ''));
        } catch (error) {
            console.error('Get all keys error:', error);
            return [];
        }
    }

    /**
     * Get cache size in bytes (approximate)
     * @returns {number}
     */
    getSize() {
        try {
            let size = 0;
            const keys = Object.keys(this.storage);
            keys.forEach(key => {
                if (key.startsWith(this.prefix)) {
                    const item = this.storage.getItem(key);
                    if (item) {
                        size += item.length + key.length;
                    }
                }
            });
            return size;
        } catch (error) {
            console.error('Get size error:', error);
            return 0;
        }
    }
}

// Export singleton instance
const cacheService = new CacheService();
export default cacheService;

// Named exports for convenience
export const {
    set: setCache,
    get: getCache,
    remove: removeCache,
    clear: clearCache,
    clearByPattern: clearCacheByPattern,
    has: hasCache,
    getInfo: getCacheInfo,
    updateTTL: updateCacheTTL,
    getAllKeys: getAllCacheKeys,
    getSize: getCacheSize
} = cacheService;
