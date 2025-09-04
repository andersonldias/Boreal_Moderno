import { createClient } from '@supabase/supabase-js';

// Leitura das variáveis de ambiente
const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;

// Verificação das variáveis de ambiente
console.log('=== Configuração do Supabase ===');
console.log('Supabase URL:', supabaseUrl ? 'Configurada' : 'Não configurada');
console.log('Supabase Anon Key:', supabaseAnonKey ? 'Configurada' : 'Não configurada');

// Verifica se as variáveis estão definidas
if (!supabaseUrl) {
  console.error('ERRO CRÍTICO: NEXT_PUBLIC_SUPABASE_URL não está definida nas variáveis de ambiente');
  console.error('Configure esta variável no Vercel: Settings > Environment Variables');
}

if (!supabaseAnonKey) {
  console.error('ERRO CRÍTICO: NEXT_PUBLIC_SUPABASE_ANON_KEY não está definida nas variáveis de ambiente');
  console.error('Configure esta variável no Vercel: Settings > Environment Variables');
}

// Verifica o formato da URL do Supabase
if (supabaseUrl && !supabaseUrl.startsWith('https://')) {
  console.error('ERRO: NEXT_PUBLIC_SUPABASE_URL não parece ser uma URL válida:', supabaseUrl);
}

// URLs de fallback para desenvolvimento (substitua pelos valores corretos)
const fallbackUrl = 'https://seu-projeto.supabase.co';
const fallbackKey = 'sua-chave-anonima-aqui';

// Usa valores de fallback se as variáveis não estiverem definidas
const finalUrl = supabaseUrl || fallbackUrl;
const finalKey = supabaseAnonKey || fallbackKey;

// Criação e exportação do cliente Supabase
export const supabase = createClient(finalUrl, finalKey);

// Verificação adicional para garantir que o cliente foi criado corretamente
if (supabase) {
  console.log('Cliente Supabase inicializado com sucesso');
  
  // Adiciona um listener para eventos de autenticação
  const { data: authListener } = supabase.auth.onAuthStateChange((event, session) => {
    console.log('Evento de autenticação:', event);
    console.log('Sessão:', session ? 'Presente' : 'Ausente');
  });
  
  // Limpa o listener quando o módulo for descarregado
  if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', () => {
      authListener?.subscription.unsubscribe();
    });
  }
} else {
  console.error('Falha ao inicializar o cliente Supabase');
}
