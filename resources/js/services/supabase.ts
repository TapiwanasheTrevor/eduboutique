import { createClient, type SupabaseClient } from '@supabase/supabase-js';

declare global {
  interface Window {
    __SUPABASE_URL?: string;
    __SUPABASE_ANON_KEY?: string;
  }
}

const envSupabaseUrl = (import.meta.env.VITE_SUPABASE_URL as string | undefined) || undefined;
const envSupabaseAnonKey = (import.meta.env.VITE_SUPABASE_ANON_KEY as string | undefined) || undefined;

const runtimeSupabaseUrl = typeof window !== 'undefined' ? window.__SUPABASE_URL : undefined;
const runtimeSupabaseAnonKey = typeof window !== 'undefined' ? window.__SUPABASE_ANON_KEY : undefined;

const supabaseUrl = envSupabaseUrl || runtimeSupabaseUrl;
const supabaseAnonKey = envSupabaseAnonKey || runtimeSupabaseAnonKey;

let client: SupabaseClient | null = null;

if (!supabaseUrl || !supabaseAnonKey) {
  console.warn('Supabase environment variables are not configured.');
} else {
  client = createClient(supabaseUrl, supabaseAnonKey);
}

export const supabase = client;
export const isSupabaseConfigured = client !== null;
