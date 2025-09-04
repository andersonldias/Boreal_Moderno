'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { supabase } from '@/utils/supabase';

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(false);

  // Verifica se o usuário já está logado
  useEffect(() => {
    const checkSession = async () => {
      try {
        console.log('Verificando sessão existente...');
        const { data: { session }, error } = await supabase.auth.getSession();
        
        if (error) {
          console.error('Erro ao verificar sessão:', error);
          return;
        }
        
        if (session) {
          console.log('Usuário já está logado, redirecionando...');
          router.push('/dashboard');
        } else {
          console.log('Nenhum usuário logado atualmente');
        }
      } catch (error) {
        console.error('Erro ao verificar sessão:', error);
      }
    };

    checkSession();
  }, [router]);

  const handleLogin = async (e) => {
    e.preventDefault();
    console.log('Formulário de login enviado');
    
    setLoading(true);
    setError(null);

    // Validação dos campos
    if (!email || !password) {
      setError('Por favor, preencha todos os campos.');
      setLoading(false);
      return;
    }

    try {
      console.log('Tentando fazer login com email:', email);
      
      // Verifica se o cliente Supabase está configurado corretamente
      if (!supabase) {
        throw new Error('Cliente Supabase não está configurado corretamente');
      }
      
      // Verifica se as variáveis de ambiente estão definidas
      const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
      const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;
      
      console.log('Variáveis de ambiente:', {
        url: supabaseUrl ? 'Definida' : 'Não definida',
        key: supabaseAnonKey ? 'Definida' : 'Não definida'
      });
      
      // Verifica se os valores estão corretos (sem mostrar a chave completa por segurança)
      if (supabaseUrl) {
        console.log('URL do Supabase:', supabaseUrl.substring(0, 30) + '...');
      } else {
        throw new Error('Configuração do Supabase não encontrada. Verifique as variáveis de ambiente no Vercel.');
      }
      
      if (!supabaseAnonKey) {
        throw new Error('Chave de autenticação do Supabase não encontrada. Verifique as variáveis de ambiente no Vercel.');
      }
      
      const { data, error } = await supabase.auth.signInWithPassword({
        email,
        password,
      });

      console.log('Resposta do Supabase:', { data, error });

      if (error) {
        console.error('Erro retornado pelo Supabase:', error);
        throw error;
      }

      if (data?.user) {
        // Login bem-sucedido, redireciona para o dashboard
        console.log('Login bem-sucedido, redirecionando para o dashboard');
        console.log('Dados do usuário:', data.user);
        router.push('/dashboard');
        router.refresh(); // Atualiza a página para garantir que os dados estejam atualizados
      } else {
        throw new Error('Falha na autenticação. Resposta inesperada do servidor.');
      }

    } catch (error) {
      console.error('Erro durante o login:', error);
      let errorMessage = 'Ocorreu um erro ao tentar fazer login. ';
      
      if (error.message) {
        errorMessage += error.message;
      } else if (error.status === 400) {
        errorMessage += 'Verifique suas credenciais e tente novamente.';
      } else {
        errorMessage += 'Por favor, tente novamente mais tarde.';
      }
      
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', minHeight: '100vh' }} className="d-flex align-items-center justify-content-center">
      <div className="card p-4" style={{ width: '100%', maxWidth: '400px', borderRadius: '15px', boxShadow: '0 15px 35px rgba(0,0,0,0.1)' }}>
        <div className="card-body">
          <div className="text-center mb-4">
            <i className="fas fa-building" style={{ fontSize: '3rem', color: '#667eea' }}></i>
            <h3 className="mt-3">Instalação de Esquadrias</h3>
            <p className="text-muted">Controle de Obras - Versão Moderna</p>
          </div>

          {error && <div className="alert alert-danger">{error}</div>}

          <form onSubmit={handleLogin}>
            <div className="mb-3">
              <label htmlFor="email" className="form-label">Email</label>
              <input
                type="email"
                className="form-control"
                id="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
            </div>
            <div className="mb-4">
              <label htmlFor="password" className="form-label">Senha</label>
              <input
                type="password"
                className="form-control"
                id="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
              />
            </div>
            <button type="submit" className="btn btn-primary w-100" disabled={loading}>
              {loading ? (
                <>
                  <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  Entrando...
                </>
              ) : 'Entrar'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
