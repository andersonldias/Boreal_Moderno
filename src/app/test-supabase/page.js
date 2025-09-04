'use client';

import { useState, useEffect } from 'react';
import { supabase } from '@/utils/supabase';

export default function TestSupabasePage() {
  const [testResult, setTestResult] = useState(null);
  const [loading, setLoading] = useState(false);

  const testSupabaseConnection = async () => {
    setLoading(true);
    setTestResult(null);
    
    try {
      console.log('Iniciando teste de conexão com o Supabase...');
      
      // Teste 0: Verificar variáveis de ambiente
      const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
      const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;
      
      if (!supabaseUrl) {
        throw new Error('NEXT_PUBLIC_SUPABASE_URL não está definida. Configure no Vercel: Settings > Environment Variables');
      }
      
      if (!supabaseAnonKey) {
        throw new Error('NEXT_PUBLIC_SUPABASE_ANON_KEY não está definida. Configure no Vercel: Settings > Environment Variables');
      }
      
      setTestResult(prev => [...(prev || []), '✓ Variáveis de ambiente configuradas']);
      
      // Verificar formato da URL
      if (!supabaseUrl.startsWith('https://')) {
        throw new Error(`URL do Supabase inválida: ${supabaseUrl}. Deve começar com https://`);
      }
      
      setTestResult(prev => [...(prev || []), '✓ Formato da URL do Supabase válido']);
      
      // Teste 1: Verificar configuração do cliente
      if (!supabase) {
        throw new Error('Cliente Supabase não foi inicializado');
      }
      
      setTestResult(prev => [...(prev || []), '✓ Cliente Supabase inicializado com sucesso']);
      
      // Teste 2: Verificar autenticação
      const { data: sessionData, error: sessionError } = await supabase.auth.getSession();
      
      if (sessionError) {
        throw new Error(`Erro na autenticação: ${sessionError.message}`);
      }
      
      setTestResult(prev => [...(prev || []), '✓ Sistema de autenticação acessível']);
      
      // Teste 3: Tentar fazer uma requisição simples
      try {
        const { data, error } = await supabase.auth.getUser();
        
        if (error && error.message !== 'Auth session missing!') {
          throw error;
        }
        
        setTestResult(prev => [...(prev || []), '✓ Conexão com o servidor Supabase estabelecida']);
      } catch (connectionError) {
        if (connectionError.message.includes('Failed to fetch')) {
          throw new Error('Erro de conexão: Verifique se a URL do Supabase está correta e se o projeto está ativo');
        }
        throw connectionError;
      }
      
      setTestResult(prev => [...(prev || []), '✅ Todos os testes concluídos com sucesso!']);
      
    } catch (error) {
      console.error('Erro no teste:', error);
      setTestResult(prev => [...(prev || []), `❌ Erro: ${error.message}`]);
      
      // Adicionar instruções específicas baseadas no erro
      if (error.message.includes('NEXT_PUBLIC_SUPABASE_URL')) {
        setTestResult(prev => [...(prev || []), '💡 Solução: Configure NEXT_PUBLIC_SUPABASE_URL no Vercel']);
      } else if (error.message.includes('NEXT_PUBLIC_SUPABASE_ANON_KEY')) {
        setTestResult(prev => [...(prev || []), '💡 Solução: Configure NEXT_PUBLIC_SUPABASE_ANON_KEY no Vercel']);
      } else if (error.message.includes('Failed to fetch')) {
        setTestResult(prev => [...(prev || []), '💡 Solução: Verifique se a URL do Supabase está correta']);
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="container py-5">
      <div className="row justify-content-center">
        <div className="col-md-8">
          <div className="card">
            <div className="card-header">
              <h3>Teste de Conexão com Supabase</h3>
            </div>
            <div className="card-body">
              <p>Esta página testa a conexão com o Supabase para diagnosticar problemas de autenticação.</p>
              
              <button 
                className="btn btn-primary mb-3" 
                onClick={testSupabaseConnection}
                disabled={loading}
              >
                {loading ? 'Testando...' : 'Executar Teste'}
              </button>
              
              {testResult && (
                <div className="mt-4">
                  <h5>Resultados do Teste:</h5>
                  <ul className="list-group">
                    {testResult.map((result, index) => (
                      <li 
                        key={index} 
                        className={`list-group-item ${
                          result.startsWith('✓') ? 'list-group-item-success' : 
                          result.startsWith('✅') ? 'list-group-item-primary' : 
                          'list-group-item-danger'
                        }`}
                      >
                        {result}
                      </li>
                    ))}
                  </ul>
                </div>
              )}
              
              <div className="mt-4">
                <h5>Instruções:</h5>
                <ol>
                  <li>Clique no botão "Executar Teste" acima</li>
                  <li>Verifique os resultados no painel</li>
                  <li>Abra o console do navegador (F12) para ver detalhes adicionais</li>
                  <li>Se houver erros, verifique as variáveis de ambiente no Vercel</li>
                </ol>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}