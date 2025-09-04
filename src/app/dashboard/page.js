'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { supabase } from '@/utils/supabase';
import DashboardLayout from '@/components/DashboardLayout';

export default function DashboardPage() {
  const router = useRouter();
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState({
    totalObras: 0,
    obrasEmAndamento: 0,
    obrasConcluidas: 0,
    totalFuncionarios: 0,
    instalacoesHoje: 0
  });
  const [obrasRecentes, setObrasRecentes] = useState([]);
  const [atividadesRecentes, setAtividadesRecentes] = useState([]);

  useEffect(() => {
    const checkUser = async () => {
      try {
        const { data: { session }, error } = await supabase.auth.getSession();
        
        if (error) {
          console.error('Erro ao verificar sessão:', error);
          router.push('/login');
          return;
        }
        
        if (!session) {
          router.push('/login');
          return;
        }
        
        setUser(session.user);
        await loadDashboardData();
      } catch (error) {
        console.error('Erro:', error);
        router.push('/login');
      } finally {
        setLoading(false);
      }
    };

    checkUser();
  }, [router]);

  const loadDashboardData = async () => {
    try {
      // Carregar estatísticas das obras
      const { data: obras, error: obrasError } = await supabase
        .from('obras')
        .select('*');

      if (obrasError) throw obrasError;

      // Carregar funcionários
      const { data: funcionarios, error: funcionariosError } = await supabase
        .from('funcionarios')
        .select('*')
        .eq('active', true);

      if (funcionariosError) throw funcionariosError;

      // Calcular estatísticas
      const totalObras = obras?.length || 0;
      const obrasEmAndamento = obras?.filter(o => ['em_andamento', 'em_finalizacao'].includes(o.status)).length || 0;
      const obrasConcluidas = obras?.filter(o => o.status === 'concluida').length || 0;
      const totalFuncionarios = funcionarios?.length || 0;

      setStats({
        totalObras,
        obrasEmAndamento,
        obrasConcluidas,
        totalFuncionarios,
        instalacoesHoje: 0 // TODO: Implementar contagem de instalações de hoje
      });

      // Obras recentes (últimas 5)
      setObrasRecentes(obras?.slice(0, 5) || []);

      // Atividades recentes (placeholder por enquanto)
      setAtividadesRecentes([
        { id: 1, action: 'Criou nova obra', user: 'Sistema', created_at: new Date().toISOString() },
        { id: 2, action: 'Atualizou status de instalação', user: 'João Silva', created_at: new Date().toISOString() }
      ]);

    } catch (error) {
      console.error('Erro ao carregar dados do dashboard:', error);
    }
  };

  if (loading) {
    return (
      <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '100vh' }}>
        <div className="spinner-border text-primary" role="status">
          <span className="visually-hidden">Carregando...</span>
        </div>
      </div>
    );
  }

  return (
    <DashboardLayout>
      <div className="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 className="h2">Dashboard</h1>
        <div className="btn-toolbar mb-2 mb-md-0">
          <div className="btn-group me-2">
            <button className="btn btn-sm btn-outline-primary">
              <i className="fas fa-plus"></i> Nova Instalação
            </button>
          </div>
        </div>
      </div>

      {/* Estatísticas */}
      <div className="row mb-4">
        <div className="col-xl-3 col-md-6 mb-4">
          <div className="card card-stats border-left-primary shadow h-100 py-2">
            <div className="card-body">
              <div className="row no-gutters align-items-center">
                <div className="col mr-2">
                  <div className="text-xs font-weight-bold text-primary text-uppercase mb-1">
                    Total de Obras
                  </div>
                  <div className="h5 mb-0 font-weight-bold text-gray-800">{stats.totalObras}</div>
                </div>
                <div className="col-auto">
                  <i className="fas fa-building fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="col-xl-3 col-md-6 mb-4">
          <div className="card card-stats border-left-success shadow h-100 py-2">
            <div className="card-body">
              <div className="row no-gutters align-items-center">
                <div className="col mr-2">
                  <div className="text-xs font-weight-bold text-success text-uppercase mb-1">
                    Em Andamento
                  </div>
                  <div className="h5 mb-0 font-weight-bold text-gray-800">{stats.obrasEmAndamento}</div>
                </div>
                <div className="col-auto">
                  <i className="fas fa-play-circle fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="col-xl-3 col-md-6 mb-4">
          <div className="card card-stats border-left-info shadow h-100 py-2">
            <div className="card-body">
              <div className="row no-gutters align-items-center">
                <div className="col mr-2">
                  <div className="text-xs font-weight-bold text-info text-uppercase mb-1">
                    Concluídas
                  </div>
                  <div className="h5 mb-0 font-weight-bold text-gray-800">{stats.obrasConcluidas}</div>
                </div>
                <div className="col-auto">
                  <i className="fas fa-check-circle fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="col-xl-3 col-md-6 mb-4">
          <div className="card card-stats border-left-warning shadow h-100 py-2">
            <div className="card-body">
              <div className="row no-gutters align-items-center">
                <div className="col mr-2">
                  <div className="text-xs font-weight-bold text-warning text-uppercase mb-1">
                    Funcionários
                  </div>
                  <div className="h5 mb-0 font-weight-bold text-gray-800">{stats.totalFuncionarios}</div>
                </div>
                <div className="col-auto">
                  <i className="fas fa-users fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Obras Recentes e Atividades */}
      <div className="row">
        <div className="col-lg-8">
          <div className="card shadow mb-4">
            <div className="card-header py-3 d-flex flex-row align-items-center justify-content-between">
              <h6 className="m-0 font-weight-bold text-primary">Obras Recentes</h6>
              <a href="/obras" className="btn btn-sm btn-primary">Ver Todas</a>
            </div>
            <div className="card-body">
              {obrasRecentes.length === 0 ? (
                <p className="text-muted text-center">Nenhuma obra encontrada.</p>
              ) : (
                obrasRecentes.map((obra) => (
                  <div key={obra.id} className="d-flex justify-content-between align-items-center mb-3 p-3 border rounded">
                    <div>
                      <h6 className="mb-1">{obra.nome}</h6>
                      <small className="text-muted">
                        {obra.cliente} • {new Date(obra.created_at).toLocaleDateString('pt-BR')}
                      </small>
                      <div className="mt-2">
                        <span className={`badge bg-${getStatusColor(obra.status)}`}>
                          {getStatusLabel(obra.status)}
                        </span>
                      </div>
                    </div>
                    <div className="text-end">
                      <div className="h6 mb-1">0%</div>
                      <small className="text-muted">0 de 0 cômodos</small>
                      <div className="progress mt-1" style={{ width: '100px' }}>
                        <div className="progress-bar bg-secondary" style={{ width: '0%' }}></div>
                      </div>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>

        <div className="col-lg-4">
          <div className="card shadow mb-4">
            <div className="card-header py-3">
              <h6 className="m-0 font-weight-bold text-primary">Atividades Recentes</h6>
            </div>
            <div className="card-body">
              {atividadesRecentes.length === 0 ? (
                <p className="text-muted text-center">Nenhuma atividade registrada.</p>
              ) : (
                atividadesRecentes.map((atividade) => (
                  <div key={atividade.id} className="mb-3">
                    <div className="d-flex align-items-start">
                      <div className="flex-shrink-0">
                        <i className="fas fa-circle text-primary" style={{ fontSize: '0.5rem' }}></i>
                      </div>
                      <div className="flex-grow-1 ms-2">
                        <small className="text-muted">
                          {new Date(atividade.created_at).toLocaleString('pt-BR')}
                        </small>
                        <div className="small">
                          <strong>{atividade.user}</strong> {atividade.action}
                        </div>
                      </div>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      </div>
    </DashboardLayout>
  );
}

// Funções auxiliares
function getStatusColor(status) {
  const colors = {
    'planejada': 'secondary',
    'em_andamento': 'warning',
    'em_finalizacao': 'info',
    'concluida': 'success',
    'pausada': 'danger'
  };
  return colors[status] || 'secondary';
}

function getStatusLabel(status) {
  const labels = {
    'planejada': 'Planejada',
    'em_andamento': 'Em Andamento',
    'em_finalizacao': 'Em Finalização',
    'concluida': 'Concluída',
    'pausada': 'Pausada'
  };
  return labels[status] || status;
}