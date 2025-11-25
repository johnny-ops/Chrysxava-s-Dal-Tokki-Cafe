// Supabase Menu Service for Website
import { supabase, supabaseConfig } from './supabase-config.js';

const STORAGE_BASE_URL = supabaseConfig.url.replace(/\/$/, '') + '/storage/v1/object/public/menu-images/';

const resolveImageUrl = (item = {}) => {
    const directUrl = item.image_url || item.imageUrl || item.image;
    if (directUrl && /^https?:\/\//i.test(directUrl)) {
        return directUrl;
    }
    const path = item.image_path || (typeof directUrl === 'string' ? directUrl.replace(/^.*menu-images\//, '') : null);
    if (path) {
        return `${STORAGE_BASE_URL}${path.replace(/^\/+/, '')}`;
    }
    return null;
};

const normalizePriceMap = (prices) => {
    if (!prices || typeof prices !== 'object') return null;
    const normalized = {};
    Object.keys(prices).forEach(key => {
        const entry = prices[key];
        if (entry == null) return;
        const value = Number(typeof entry === 'object' ? entry.price ?? entry.value : entry);
        if (!Number.isNaN(value) && value > 0) {
            normalized[key] = { price: value, label: entry?.label || null };
        }
    });
    return Object.keys(normalized).length ? normalized : null;
};

const mergePriceMaps = (target, incoming) => {
    if (!incoming) return target || null;
    if (!target) return { ...incoming };
    return { ...target, ...incoming };
};

const deriveBasePrice = (item) => {
    const collect = (map) => Object.values(map || {})
        .map(entry => Number(entry?.price ?? entry))
        .filter(value => !Number.isNaN(value) && value > 0);
    const values = [
        ...collect(item.hot_prices),
        ...collect(item.iced_prices),
        ...collect(item.sizes)
    ];
    if (values.length === 0) {
        return Number(item.price || 0);
    }
    return Math.min(...values);
};

class SupabaseMenuService {
    constructor() {
        this.menuItems = [];
        this.categories = [];
        this.listeners = [];
        this.subscriptions = [];
    }

    // Initialize menu data from Supabase
    async initializeMenu() {
        try {
            // Load categories (try with is_active filter, fallback to all)
            let { data: categories, error: categoriesError } = await supabase
                .from('categories')
                .select('*')
                .order('name', { ascending: true });
            
            // Filter by is_active if column exists (client-side filter)
            if (categories && categories.length > 0) {
                categories = categories.filter(cat => {
                    return cat.is_active !== false && cat.isActive !== false;
                });
            }

            if (categoriesError) {
                console.warn('Error loading categories, using empty array:', categoriesError);
                categories = [];
            }

            this.categories = categories || [];

            // Load menu items (check both is_active and isActive for compatibility)
            // First try with is_active, if that fails, try without filter
            let { data: menuItems, error: menuError } = await supabase
                .from('menu')
                .select('*')
                .gt('stock', 0)
                .order('name', { ascending: true });
            
            // If error, try without stock filter (in case stock column doesn't exist)
            if (menuError) {
                console.warn('Error with stock filter, trying without:', menuError);
                const retry = await supabase
                    .from('menu')
                    .select('*')
                    .order('name', { ascending: true });
                menuItems = retry.data;
                menuError = retry.error;
            }
            
            // Filter by is_active if column exists (client-side filter as fallback)
            if (menuItems && menuItems.length > 0) {
                menuItems = menuItems.filter(item => {
                    // Include if is_active is true or undefined (for backward compatibility)
                    const isActive = item.is_active !== false && item.isActive !== false;
                    const hasStock = (item.stock || 0) > 0;
                    return isActive && hasStock;
                });
            }

            if (menuError) {
                console.error('Error loading menu items:', menuError);
                // Don't throw - use empty array instead
                menuItems = [];
            }

            const normalizedItems = (menuItems || []).map(item => {
                const hotPrices = normalizePriceMap(item.hot_prices || item.hotPrices);
                const icedPrices = normalizePriceMap(item.iced_prices || item.icedPrices);
                const resolvedImageUrl = resolveImageUrl(item);
                const normalized = {
                    ...item,
                    hot_prices: hotPrices,
                    iced_prices: icedPrices,
                    image_url: resolvedImageUrl,
                    imageUrl: resolvedImageUrl,
                    image_path: item.image_path || null
                };
                normalized.price = deriveBasePrice(normalized);
                return normalized;
            });

            const mergedMap = new Map();
            normalizedItems.forEach(item => {
                const isDrink = /^(coffee|non-coffee)$/i.test(String(item.category || ''));
                const key = isDrink ? `${item.category}:${item.name}` : item.id;
                if (mergedMap.has(key)) {
                    const existing = mergedMap.get(key);
                    existing.hot_prices = mergePriceMaps(existing.hot_prices, item.hot_prices);
                    existing.iced_prices = mergePriceMaps(existing.iced_prices, item.iced_prices);
                    if (!existing.image_url && item.image_url) {
                        existing.image_url = item.image_url;
                        existing.imageUrl = item.image_url;
                    }
                    existing.price = deriveBasePrice(existing);
                } else {
                    mergedMap.set(key, item);
                }
            });

            this.menuItems = Array.from(mergedMap.values());

            console.log('Menu data loaded from Supabase:', {
                categories: this.categories.length,
                items: this.menuItems.length
            });

            return {
                categories: this.categories,
                items: this.menuItems
            };
        } catch (error) {
            console.error('Error loading menu from Supabase:', error);
            // Return empty arrays instead of throwing to prevent page crash
            return {
                categories: [],
                items: []
            };
        }
    }

    // Set up real-time listeners for menu updates
    setupRealtimeListeners() {
        try {
            // Listen for category changes
            const categoriesSubscription = supabase
                .channel('categories-changes')
                .on('postgres_changes', 
                    { event: '*', schema: 'public', table: 'categories' },
                    (payload) => {
                        console.log('Category change:', payload);
                        this.initializeMenu().then(() => {
                            this.notifyListeners('categories', this.categories);
                        }).catch(err => {
                            console.error('Error reloading menu after category change:', err);
                        });
                    }
                )
                .subscribe();

            // Listen for menu item changes
            const menuSubscription = supabase
                .channel('menu-changes')
                .on('postgres_changes',
                    { event: '*', schema: 'public', table: 'menu' },
                    (payload) => {
                        console.log('Menu change:', payload);
                        this.initializeMenu().then(() => {
                            this.notifyListeners('items', this.menuItems);
                        }).catch(err => {
                            console.error('Error reloading menu after menu change:', err);
                        });
                    }
                )
                .subscribe();

            // Store subscriptions for cleanup
            this.subscriptions.push(categoriesSubscription, menuSubscription);
        } catch (error) {
            console.warn('Error setting up realtime listeners:', error);
            // Continue without realtime - not critical
        }
    }

    // Add listener for menu updates
    addListener(callback) {
        this.listeners.push(callback);
    }

    // Notify all listeners of changes
    notifyListeners(type, data) {
        this.listeners.forEach(listener => {
            if (typeof listener === 'function') {
                listener(type, data);
            }
        });
    }

    // Get menu items by category
    getMenuItemsByCategory(categoryId) {
        if (!categoryId) return this.menuItems;
        return this.menuItems.filter(item => 
            item.category_id === categoryId || item.category === categoryId
        );
    }

    // Get menu items by search term
    getMenuItemsBySearch(searchTerm) {
        const term = searchTerm.toLowerCase();
        return this.menuItems.filter(item => 
            item.name.toLowerCase().includes(term) ||
            (item.description && item.description.toLowerCase().includes(term))
        );
    }

    // Get all categories
    getCategories() {
        return this.categories;
    }

    // Get all menu items
    getMenuItems() {
        return this.menuItems;
    }

    // Clean up listeners
    cleanup() {
        this.subscriptions.forEach(subscription => {
            if (subscription && typeof subscription.unsubscribe === 'function') {
                subscription.unsubscribe();
            }
        });
        this.subscriptions = [];
        this.listeners = [];
    }
}

// Create singleton instance
const supabaseMenuService = new SupabaseMenuService();

// Export for use in other files
export default supabaseMenuService;

