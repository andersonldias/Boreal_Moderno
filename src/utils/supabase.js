import { createClient } from '@supabase/supabase-js';

// Leitura das variáveis de ambiente
const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;

// Criação e exportação do cliente Supabase
export const supabase = createClient(supabaseUrl, supabaseAnonKey);
