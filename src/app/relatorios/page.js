'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { supabase } from '@/utils/supabase';
import DashboardLayout from '@/components/DashboardLayout';

export default function RelatoriosPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [relatorios, setRelatorios] = useState([]);
  const [filtros, setFiltros] = useState({
    dataInicio: '',
    dataFim: '',
    obraId: '',
    status: ''
  });
  const [obras, setObras] = useState([]);

  useEffect(() => {
    checkAuth();
    loadObras();
    loadRelatorios();
  }, []);

  const checkAuth = async () => {
    try {
      const { data: { session }, error } = await supabase.auth.getSession();
      
      if (error || !session) {
        router.push('/login');
        return;
      }
    } catch (error) {
      console.error('Erro de autenticação:', error);
      router.push('/login');
    }
  };

  const loadObras = async () => {
    try {
      const { data, error } = await supabase
        .from('obras')
        .select('id, nome, cliente')
        .order('nome');

      if (error) throw error;
      setObras(data || []);
    } catch (error) {
      console.error('Erro ao carregar obras:', error);
    }
  };

  const loadRelatorios = async () => {
    try {
      setLoading(true);
      
      // Carregar dados para relatórios
      const { data: obrasData, error: obrasError } = await supabase
        .from('obras')
        .select(`
          *,
          comodos(*)
        `);

      if (obrasError) throw obrasError;

      // Processar dados para relatórios
      const relatoriosData = obrasData?.map(obra => {
        const totalComodos = obra.comodos?.length || 0;
        const comodosInstalados = obra.comodos?.filter(c => c.status === 'instalado').length || 0;
        const comodosEmInstalacao = obra.comodos?.filter(c => c.status === 'em_instalacao').length || 0;
        const comodosNaoInstalados = obra.comodos?.filter(c => c.status === 'nao_instalado').length || 0;
        
        const percentualConclusao = totalComodos > 0 ? Math.round((comodosInstalados / totalComodos) * 100) : 0;

        return {
          id: obra.id,
          nome: obra.nome,
          cliente: obra.cliente,
          status: obra.status,
          dataInicio: obra.data_inicio,
          totalComodos,
          comodosInstalados,
          comodosEmInstalacao,
          comodosNaoInstalados,
          percentualConclusao
        };
      }) || [];

      setRelatorios(relatoriosData);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  };

  const exportarRelatorio = (tipo) => {
    // Implementar exportação de relatórios
    alert(`Exportando relatório ${tipo}...`);
  };

  const getStatusColor = (status) => {
    const colors = {
      'planejada': 'secondary',
      'em_andamento': 'warning',
      'em_finalizacao': 'info',
      'concluida': 'success',
      'pausada': 'danger'
    };
    return colors[status] || 'secondary';
  };

  const getStatusLabel = (status) => {
    const labels = {
      'planejada': 'Planejada',
      'em_andamento': 'Em Andamento',
      'em_finalizacao': 'Em Finalização',
      'concluida': 'Concluída',
      'pausada': 'Pausada'
    };
    return labels[status] || status;
  };

  return (
    <DashboardLayout>
      <div className="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 className="h2">Relatórios</h1>
        <div className="btn-toolbar mb-2 mb-md-0">
          <div className="btn-group me-2">
            <button 
              className="btn btn-success"
              onClick={() => exportarRelatorio('completo')}
            >
              <i className="fas fa-file-excel"></i> Exportar Excel
            </button>
            <button 
              className="btn btn-primary"
              onClick={() => exportarRelatorio('pdf')}
            >
              <i className="fas fa-file-pdf"></i> Exportar PDF
            </button>
          </div>
        </div>
      </div>

      {error && (
        <div className="alert alert-danger alert-dismissible fade show" role="alert">
          {error}
          <button type="button" className="btn-close" data-bs-dismiss="alert"></button>
        </div>
      )}

      {/* Filtros */}
      <div className="card mb-4">
        <div className="card-body">
          <h5 className="card-title">Filtros de Relatório</h5>
          <form className="row g-3">
            <div className="col-md-3">
              <label htmlFor="dataInicio" className="form-label">Data Início</label>
              <input
                type="date"
                className="form-control"
                id="dataInicio"
                value={filtros.dataInicio}
                onChange={(e) => setFiltros({...filtros, dataInicio: e.target.value})}
              />
            </div>
            <div className="col-md-3">
              <label htmlFor="dataFim" className="form-label">Data Fim</label>
              <input
                type="date"
                className="form-control"
                id="dataFim"
                value={filtros.dataFim}
                onChange={(e) => setFiltros({...filtros, dataFim: e.target.value})}
              />
            </div>
            <div className="col-md-3">
              <label htmlFor="obraId" className="form-label">Obra</label>
              <select
                className="form-select"
                id="obraId"
                value={filtros.obraId}
                onChange={(e) => setFiltros({...filtros, obraId: e.target.value})}
              >
                <option value="">Todas as obras</option>
                {obras.map(obra => (
                  <option key={obra.id} value={obra.id}>
                    {obra.nome} - {obra.cliente}
                  </option>
                ))}
              </select>
            </div>
            <div className="col-md-3">
              <label htmlFor="status" className="form-label">Status</label>
              <select
                className="form-select"
                id="status"
                value={filtros.status}
                onChange={(e) => setFiltros({...filtros, status: e.target.value})}
              >
                <option value="">Todos os status</option>
                <option value="planejada">Planejada</option>
                <option value="em_andamento">Em Andamento</option>
                <option value="em_finalizacao">Em Finalização</option>
                <option value="concluida">Concluída</option>
                <option value="pausada">Pausada</option>
              </select>
            </div>
          </form>
        </div>
      </div>

      {/* Resumo Geral */}
      <div className="row mb-4">
        <div className="col-xl-3 col-md-6 mb-4">
          <div className="card card-stats border-left-primary shadow h-100 py-2">
            <div className="card-body">
              <div className="row no-gutters align-items-center">
                <div className="col mr-2">
                  <div className="text-xs font-weight-bold text-primary text-uppercase mb-1">
                    Total de Obras
                  </div>
                  <div className="h5 mb-0 font-weight-bold text-gray-800">{relatorios.length}</div>
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
                    Obras Concluídas
                  </div>
                  <div className="h5 mb-0 font-weight-bold text-gray-800">
                    {relatorios.filter(r => r.status === 'concluida').length}
                  </div>
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
                    Em Andamento
                  </div>
                  <div className="h5 mb-0 font-weight-bold text-gray-800">
                    {relatorios.filter(r => ['em_andamento', 'em_finalizacao'].includes(r.status)).length}
                  </div>
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
                    Média de Conclusão
                  </div>
                  <div className="h5 mb-0 font-weight-bold text-gray-800">
                    {relatorios.length > 0 
                      ? Math.round(relatorios.reduce((acc, r) => acc + r.percentualConclusao, 0) / relatorios.length)
                      : 0}%
                  </div>
                </div>
                <div className="col-auto">
                  <i className="fas fa-chart-line fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Tabela de Relatórios */}
      <div className="card">
        <div className="card-header">
          <h5 className="mb-0">Relatório Detalhado por Obra</h5>
        </div>
        <div className="card-body">
          {loading ? (
            <div className="text-center py-4">
              <div className="spinner-border text-primary" role="status">
                <span className="visually-hidden">Carregando...</span>
              </div>
            </div>
          ) : (
            <div className="table-responsive">
              <table className="table table-hover">
                <thead>
                  <tr>
                    <th>Obra</th>
                    <th>Cliente</th>
                    <th>Status</th>
                    <th>Progresso</th>
                    <th>Cômodos</th>
                    <th>Instalados</th>
                    <th>Em Instalação</th>
                    <th>Não Instalados</th>
                  </tr>
                </thead>
                <tbody>
                  {relatorios.length === 0 ? (
                    <tr>
                      <td colSpan="8" className="text-center text-muted py-4">
                        <i className="fas fa-chart-bar fa-2x mb-2"></i><br />
                        Nenhum dado encontrado
                      </td>
                    </tr>
                  ) : (
                    relatorios.map((relatorio) => (
                      <tr key={relatorio.id}>
                        <td>
                          <strong>{relatorio.nome}</strong>
                          {relatorio.dataInicio && (
                            <br />
                            <small className="text-muted">
                              Início: {new Date(relatorio.dataInicio).toLocaleDateString('pt-BR')}
                            </small>
                          )}
                        </td>
                        <td>{relatorio.cliente}</td>
                        <td>
                          <span className={`badge bg-${getStatusColor(relatorio.status)}`}>
                            {getStatusLabel(relatorio.status)}
                          </span>
                        </td>
                        <td>
                          <div className="d-flex align-items-center">
                            <div className="progress me-2" style={{ width: '100px', height: '8px' }}>
                              <div 
                                className={`progress-bar bg-${relatorio.percentualConclusao === 100 ? 'success' : 'primary'}`}
                                style={{ width: `${relatorio.percentualConclusao}%` }}
                              ></div>
                            </div>
                            <span className="text-muted">{relatorio.percentualConclusao}%</span>
                          </div>
                        </td>
                        <td>
                          <span className="badge bg-info">{relatorio.totalComodos}</span>
                        </td>
                        <td>
                          <span className="badge bg-success">{relatorio.comodosInstalados}</span>
                        </td>
                        <td>
                          <span className="badge bg-warning">{relatorio.comodosEmInstalacao}</span>
                        </td>
                        <td>
                          <span className="badge bg-secondary">{relatorio.comodosNaoInstalados}</span>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </DashboardLayout>
  );
}
