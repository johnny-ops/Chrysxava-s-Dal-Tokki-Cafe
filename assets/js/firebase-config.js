// ==========================================
// FIREBASE CONFIGURATION - COMMENTED OUT (MIGRATED TO SUPABASE)
// ==========================================
// This file has been replaced by supabase-config.js
// Firebase code is commented out but preserved for reference

/*
// Firebase Configuration for Website
import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
import { getFirestore, collection, getDocs, onSnapshot, doc, addDoc, updateDoc, deleteDoc } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js';
import { getAuth, signInAnonymously } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';

// Firebase config - Unified with admin and mobile (new project)
const firebaseConfig = {
  apiKey: "AIzaSyDDUmrgvsO0e-6V8we3mpFqG-R68BMMefM",
  authDomain: "daltokki-main.firebaseapp.com",
  projectId: "daltokki-main",
  storageBucket: "daltokki-main.firebasestorage.app",
  messagingSenderId: "43238704811",
  appId: "1:43238704811:web:9e09eceac52990335659ee",
  measurementId: "G-VM0FHE5S32"
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
*/

// Re-export Supabase for backward compatibility
export { supabase as db, supabase as auth } from './supabase-config.js';
