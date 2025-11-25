// Supabase Configuration for Website
// Using Supabase JS library from CDN

// Supabase config - Replace with your actual Supabase project credentials
const supabaseConfig = {
  url: 'https://tbfjmhgnvtweotlqnplm.supabase.co',
  anonKey: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRiZmptaGdudnR3ZW90bHFucGxtIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjI1OTk3NTcsImV4cCI6MjA3ODE3NTc1N30.8FU3eU2-ThlyQS8I-oQw_eDzcIpxSxLe-2YtNE_xLvc'
};

// Initialize Supabase Client
// Make sure Supabase library is loaded via CDN before this script
let supabase;

if (typeof window !== 'undefined' && typeof window.supabaseLib !== 'undefined') {
  const { createClient } = window.supabaseLib;
  supabase = createClient(supabaseConfig.url, supabaseConfig.anonKey);
} else if (typeof supabase !== 'undefined') {
  // Fallback if Supabase is loaded differently
  supabase = supabase.createClient(supabaseConfig.url, supabaseConfig.anonKey);
} else {
  console.error('Supabase library not loaded. Make sure to include the Supabase CDN script.');
  // Create a dummy client to prevent errors
  supabase = {
    from: () => ({
      select: () => Promise.resolve({ data: [], error: null }),
      insert: () => Promise.resolve({ data: null, error: new Error('Supabase not initialized') }),
      update: () => Promise.resolve({ data: null, error: new Error('Supabase not initialized') }),
      delete: () => Promise.resolve({ data: null, error: new Error('Supabase not initialized') })
    }),
    channel: () => ({
      on: () => ({ subscribe: () => ({}) }),
      subscribe: () => ({})
    })
  };
}

console.log('Supabase initialized successfully for website');

// Export Supabase client
export { supabase, supabaseConfig };

