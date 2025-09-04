'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { supabase } from '@/utils/supabase';
import DashboardLayout from '@/components/DashboardLayout';

export default function InstalacoesPage() {
  const router = useRouter();
  const [obras, setObras] = useState([]);
  const [comodos, setComodos] = useState([]);
  const [funcionarios, setFuncionarios] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [selectedObra, setSelectedObra] = useState('');
  const [filters, setFilters] = useState({
    status: '',
    search: ''
  });
  const [showStatusModal, setShowStatusModal] = useState(false);
  const [selectedComodo, setSelectedComodo] = useState(null);
  const [statusForm, setStatusForm] = useState({
    status: '',
    observacao: '',
    funcionario_ids: []
  });

  useEffect(() => {
    checkAuth();
    loadData();
  }, []);

  useEffect(() => {
    if (selectedObra) {
      loadComodos();
    }
  }, [selectedObra, filters]);

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

  const loadData = async () => {
    try {
      setLoading(true);
      
      // Carregar obras
      const { data: obrasData, error: obrasError } = await supabase
        .from('obras')
        .select('*')
        .order('nome');

      if (obrasError) throw obrasError;

      // Carregar funcionários ativos
      const { data: funcionariosData, error: funcionariosError } = await supabase
        .from('funcionarios')
        .select('*')
        .eq('active', true)
        .order('nome');

      if (funcionariosError) throw funcionariosError;

      setObras(obrasData || []);
      setFuncionarios(funcionariosData || []);
      
      if (obrasData && obrasData.length > 0) {
        setSelectedObra(obrasData[0].id);
      }
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  };

  const loadComodos = async () => {
    try {
      let query = supabase
        .from('comodos')
        .select(`
          *,
          obras!inner(nome, cliente)
        `)
        .eq('obra_id', selectedObra);

      if (filters.status) {
        query = query.eq('status', filters.status);
      }

      if (filters.search) {
        query = query.or(`nome.ilike.%${filters.search}%,tipo_esquadria.ilike.%${filters.search}%`);
      }

      const { data, error } = await query.order('nome');

      if (error) throw error;
      setComodos(data || []);
    } catch (error) {
      setError(error.message);
    }
  };

  const handleStatusUpdate = async (e) => {
    e.preventDefault();
    
    try {
      const updateData = {
        status: statusForm.status,
        observacao: statusForm.observacao
      };

      if (statusForm.status === 'instalado') {
        updateData.data_instalacao = new Date().toISOString();
      }

      // Atualizar cômodo
      const { error: comodoError } = await supabase
        .from('comodos')
        .update(updateData)
        .eq('id', selectedComodo.id);

      if (comodoError) throw comodoError;

      // Criar registros de instalação se status for 'instalado'
      if (statusForm.status === 'instalado' && statusForm.funcionario_ids.length > 0) {
        const instalacoes = statusForm.funcionario_ids.map(funcionario_id => ({
          comodo_id: selectedComodo.id,
          funcionario_id,
          data_instalacao: new Date().toISOString(),
          observacoes: statusForm.observacao
        }));

        const { error: instalacoesError } = await supabase
          .from('instalacoes')
          .insert(instalacoes);

        if (instalacoesError) throw instalacoesError;
      }

      setShowStatusModal(false);
      setSelectedComodo(null);
      setStatusForm({ status: '', observacao: '', funcionario_ids: [] });
      loadComodos();
    } catch (error) {
      setError(error.message);
    }
  };

  const openStatusModal = (comodo) => {
    setSelectedComodo(comodo);
    setStatusForm({
      status: comodo.status,
      observacao: comodo.observacao || '',
      funcionario_ids: []
    });
    setShowStatusModal(true);
  };

  const getStatusColor = (status) => {
    const colors = {
      'nao_instalado': 'secondary',
      'em_instalacao': 'warning',
      'instalado': 'success'
    };
    return colors[status] || 'secondary';
  };

  const getStatusLabel = (status) => {
    const labels = {
      'nao_instalado': 'Não Instalado',
      'em_instalacao': 'Em Instalação',
      'instalado': 'Instalado'
    };
    return labels[status] || status;
  };

  // Estatísticas
  const totalComodos = comodos.length;
  const comodosInstalados = comodos.filter(c => c.status === 'instalado').length;
  const comodosEmInstalacao = comodos.filter(c => c.status === 'em_instalacao').length;
  const comodosNaoInstalados = comodos.filter(c => c.status === 'nao_instalado').length;

  return (
    <DashboardLayout>
      <div className="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 className="h2">Gerenciar Instalações</h1>
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
          <form className="row g-3">
            <div className="col-md-4">
              <label htmlFor="obra_id" className="form-label">Selecione a Obra</label>
              <select
                className="form-select"
                id="obra_id"
                value={selectedObra}
                onChange={(e) => setSelectedObra(e.target.value)}
              >
                <option value="">Selecione uma obra...</option>
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
                value={filters.status}
                onChange={(e) => setFilters({...filters, status: e.target.value})}
              >
                <option value="">Todos</option>
                <option value="nao_instalado">Não Instalado</option>
                <option value="em_instalacao">Em Instalação</option>
                <option value="instalado">Instalado</option>
              </select>
            </div>
            <div className="col-md-3">
              <label htmlFor="search" className="form-label">Buscar</label>
              <input
                type="text"
                className="form-control"
                id="search"
                placeholder="Nome ou tipo de esquadria"
                value={filters.search}
                onChange={(e) => setFilters({...filters, search: e.target.value})}
              />
            </div>
            <div className="col-md-2">
              <label className="form-label">&nbsp;</label>
              <button 
                type="button" 
                className="btn btn-primary w-100"
                onClick={() => setFilters({ status: '', search: '' })}
              >
                <i className="fas fa-times"></i> Limpar
              </button>
            </div>
          </form>
        </div>
      </div>

      {selectedObra && (
        <>
          {/* Estatísticas */}
          <div className="row mb-4">
            <div className="col-xl-3 col-md-6 mb-4">
              <div className="card card-stats border-left-primary shadow h-100 py-2">
                <div className="card-body">
                  <div className="row no-gutters align-items-center">
                    <div className="col mr-2">
                      <div className="text-xs font-weight-bold text-primary text-uppercase mb-1">
                        Total de Cômodos
                      </div>
                      <div className="h5 mb-0 font-weight-bold text-gray-800">{totalComodos}</div>
                    </div>
                    <div className="col-auto">
                      <i className="fas fa-door-open fa-2x text-gray-300"></i>
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
                        Instalados
                      </div>
                      <div className="h5 mb-0 font-weight-bold text-gray-800">{comodosInstalados}</div>
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
                        Em Instalação
                      </div>
                      <div className="h5 mb-0 font-weight-bold text-gray-800">{comodosEmInstalacao}</div>
                    </div>
                    <div className="col-auto">
                      <i className="fas fa-tools fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div className="col-xl-3 col-md-6 mb-4">
              <div className="card card-stats border-left-secondary shadow h-100 py-2">
                <div className="card-body">
                  <div className="row no-gutters align-items-center">
                    <div className="col mr-2">
                      <div className="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                        Não Instalados
                      </div>
                      <div className="h5 mb-0 font-weight-bold text-gray-800">{comodosNaoInstalados}</div>
                    </div>
                    <div className="col-auto">
                      <i className="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Lista de Cômodos */}
          <div className="card">
            <div className="card-body">
              {loading ? (
                <div className="text-center py-4">
                  <div className="spinner-border text-primary" role="status">
                    <span className="visually-hidden">Carregando...</span>
                  </div>
                </div>
              ) : (
                <div className="row">
                  {comodos.length === 0 ? (
                    <div className="col-12 text-center text-muted py-5">
                      <i className="fas fa-inbox fa-3x mb-3"></i>
                      <h5>Nenhum cômodo encontrado</h5>
                      <p>Selecione uma obra para visualizar os cômodos.</p>
                    </div>
                  ) : (
                    comodos.map((comodo) => (
                      <div key={comodo.id} className="col-md-6 col-lg-4 mb-3">
                        <div className="card comodo-card h-100">
                          <div className="card-header d-flex justify-content-between align-items-center">
                            <h6 className="mb-0">{comodo.nome}</h6>
                            <span className={`badge bg-${getStatusColor(comodo.status)}`}>
                              {getStatusLabel(comodo.status)}
                            </span>
                          </div>
                          <div className="card-body">
                            <p className="mb-1"><strong>Tipo:</strong> {comodo.tipo_esquadria}</p>
                            {comodo.modelo && (
                              <p className="mb-1"><strong>Modelo:</strong> {comodo.modelo}</p>
                            )}
                            {comodo.dimensoes && (
                              <p className="mb-1"><strong>Dimensões:</strong> {comodo.dimensoes}</p>
                            )}
                            {comodo.observacao && (
                              <p className="mb-1"><small className="text-muted">{comodo.observacao}</small></p>
                            )}
                            {comodo.data_instalacao && (
                              <p className="mb-0"><small className="text-success">
                                <i className="fas fa-calendar me-1"></i>
                                Instalado em: {new Date(comodo.data_instalacao).toLocaleDateString('pt-BR')}
                              </small></p>
                            )}
                          </div>
                          <div className="card-footer">
                            <button 
                              className="btn btn-sm btn-outline-primary w-100"
                              onClick={() => openStatusModal(comodo)}
                            >
                              <i className="fas fa-edit me-1"></i>
                              Atualizar Status
                            </button>
                          </div>
                        </div>
                      </div>
                    ))
                  )}
                </div>
              )}
            </div>
          </div>
        </>
      )}

      {/* Modal de Atualização de Status */}
      {showStatusModal && selectedComodo && (
        <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
          <div className="modal-dialog">
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="modal-title">Atualizar Status - {selectedComodo.nome}</h5>
                <button 
                  type="button" 
                  className="btn-close" 
                  onClick={() => setShowStatusModal(false)}
                ></button>
              </div>
              <form onSubmit={handleStatusUpdate}>
                <div className="modal-body">
                  <div className="mb-3">
                    <label htmlFor="status" className="form-label">Status *</label>
                    <select
                      className="form-select"
                      id="status"
                      value={statusForm.status}
                      onChange={(e) => setStatusForm({...statusForm, status: e.target.value})}
                      required
                    >
                      <option value="nao_instalado">Não Instalado</option>
                      <option value="em_instalacao">Em Instalação</option>
                      <option value="instalado">Instalado</option>
                    </select>
                  </div>

                  {statusForm.status === 'instalado' && (
                    <div className="mb-3">
                      <label className="form-label">Funcionários Responsáveis</label>
                      {funcionarios.map(funcionario => (
                        <div key={funcionario.id} className="form-check">
                          <input
                            className="form-check-input"
                            type="checkbox"
                            id={`funcionario_${funcionario.id}`}
                            value={funcionario.id}
                            checked={statusForm.funcionario_ids.includes(funcionario.id)}
                            onChange={(e) => {
                              if (e.target.checked) {
                                setStatusForm({
                                  ...statusForm,
                                  funcionario_ids: [...statusForm.funcionario_ids, funcionario.id]
                                });
                              } else {
                                setStatusForm({
                                  ...statusForm,
                                  funcionario_ids: statusForm.funcionario_ids.filter(id => id !== funcionario.id)
                                });
                              }
                            }}
                          />
                          <label className="form-check-label" htmlFor={`funcionario_${funcionario.id}`}>
                            {funcionario.nome} - {funcionario.funcao}
                          </label>
                        </div>
                      ))}
                    </div>
                  )}

                  <div className="mb-3">
                    <label htmlFor="observacao" className="form-label">Observações</label>
                    <textarea
                      className="form-control"
                      id="observacao"
                      rows="3"
                      value={statusForm.observacao}
                      onChange={(e) => setStatusForm({...statusForm, observacao: e.target.value})}
                    />
                  </div>
                </div>
                <div className="modal-footer">
                  <button 
                    type="button" 
                    className="btn btn-secondary" 
                    onClick={() => setShowStatusModal(false)}
                  >
                    Cancelar
                  </button>
                  <button type="submit" className="btn btn-primary">
                    Atualizar Status
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </DashboardLayout>
  );
}
