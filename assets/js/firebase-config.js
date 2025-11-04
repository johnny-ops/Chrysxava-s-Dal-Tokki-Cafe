// Firebase Configuration for Website
import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
import { getFirestore, collection, getDocs, onSnapshot, doc, addDoc, updateDoc, deleteDoc } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js';
import { getAuth, signInAnonymously } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';

// Firebase config - Same as mobile app
const firebaseConfig = {
  apiKey: "AIzaSyDIu5hhLqjAERyeU1on-3H3KT0DzsNFNuw",
  authDomain: "daltokki-828fa.firebaseapp.com",
  projectId: "daltokki-828fa",
  storageBucket: "daltokki-828fa.firebasestorage.app",
  messagingSenderId: "722840974541",
  appId: "1:722840974541:web:3b408b8f3ebd1c97d9e584",
  measurementId: "G-NZ77WHMTQG"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const db = getFirestore(app);
const auth = getAuth(app);

// Sign in anonymously for website access
signInAnonymously(auth)
  .then(() => {
    console.log('Firebase authenticated successfully for website');
  })
  .catch((error) => {
    console.error('Firebase authentication error:', error);
  });

// Export Firebase functions
export { db, auth, collection, getDocs, onSnapshot, doc, addDoc, updateDoc, deleteDoc };
