// Firebase Menu Service for Website
import { db, collection, getDocs, onSnapshot, doc, addDoc, updateDoc, deleteDoc } from './firebase-config.js';

class FirebaseMenuService {
    constructor() {
        this.menuItems = [];
        this.categories = [];
        this.listeners = [];
    }

    // Initialize menu data from Firebase
    async initializeMenu() {
        try {
            // Load categories
            const categoriesSnapshot = await getDocs(collection(db, 'categories'));
            this.categories = categoriesSnapshot.docs.map(doc => ({
                id: doc.id,
                ...doc.data()
            }));

            // Load menu items
            const menuSnapshot = await getDocs(collection(db, 'menu'));
            this.menuItems = menuSnapshot.docs.map(doc => ({
                id: doc.id,
                ...doc.data()
            }));

            console.log('Menu data loaded from Firebase:', {
                categories: this.categories.length,
                items: this.menuItems.length
            });

            return {
                categories: this.categories,
                items: this.menuItems
            };
        } catch (error) {
            console.error('Error loading menu from Firebase:', error);
            throw error;
        }
    }

    // Set up real-time listeners for menu updates
    setupRealtimeListeners() {
        // Listen for category changes
        const categoriesUnsubscribe = onSnapshot(collection(db, 'categories'), (snapshot) => {
            this.categories = snapshot.docs.map(doc => ({
                id: doc.id,
                ...doc.data()
            }));
            this.notifyListeners('categories', this.categories);
        });

        // Listen for menu item changes
        const menuItemsUnsubscribe = onSnapshot(collection(db, 'menu'), (snapshot) => {
            this.menuItems = snapshot.docs.map(doc => ({
                id: doc.id,
                ...doc.data()
            }));
            this.notifyListeners('items', this.menuItems);
        });

        // Store unsubscribe functions
        this.listeners.push(categoriesUnsubscribe, menuItemsUnsubscribe);
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
        return this.menuItems.filter(item => item.category_id === categoryId);
    }

    // Get menu items by search term
    getMenuItemsBySearch(searchTerm) {
        const term = searchTerm.toLowerCase();
        return this.menuItems.filter(item => 
            item.name.toLowerCase().includes(term) ||
            item.description.toLowerCase().includes(term)
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
        this.listeners.forEach(unsubscribe => {
            if (typeof unsubscribe === 'function') {
                unsubscribe();
            }
        });
        this.listeners = [];
    }
}

// Create singleton instance
const firebaseMenuService = new FirebaseMenuService();

// Export for use in other files
export default firebaseMenuService;
