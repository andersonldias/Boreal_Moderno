'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { supabase } from '@/utils/supabase';
import DashboardLayout from '@/components/DashboardLayout';

export default function FuncionariosPage() {
  const router = useRouter();
  const [funcionarios, setFuncionarios] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [editingFuncionario, setEditingFuncionario] = useState(null);
  const [filters, setFilters] = useState({
    search: '',
    funcao: '',
    status: ''
  });
  const [formData, setFormData] = useState({
    nome: '',
    funcao: '',
    telefone: '',
    email: '',
    active: true
  });

  useEffect(() => {
    checkAuth();
    loadFuncionarios();
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

  const loadFuncionarios = async () => {
    try {
      setLoading(true);
      let query = supabase
        .from('funcionarios')
        .select('*')
        .order('nome', { ascending: true });

      // Aplicar filtros
      if (filters.search) {
        query = query.or(`nome.ilike.%${filters.search}%,email.ilike.%${filters.search}%`);
      }
      if (filters.funcao) {
        query = query.eq('funcao', filters.funcao);
      }
      if (filters.status !== '') {
        query = query.eq('active', filters.status === '1');
      }

      const { data, error } = await query;

      if (error) throw error;
      setFuncionarios(data || []);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadFuncionarios();
  }, [filters]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    try {
      if (editingFuncionario) {
        // Atualizar funcionário existente
        const { error } = await supabase
          .from('funcionarios')
          .update(formData)
          .eq('id', editingFuncionario.id);

        if (error) throw error;
      } else {
        // Criar novo funcionário
        const { error } = await supabase
          .from('funcionarios')
          .insert([formData]);

        if (error) throw error;
      }

      setShowModal(false);
      setEditingFuncionario(null);
      resetForm();
      loadFuncionarios();
    } catch (error) {
      setError(error.message);
    }
  };

  const handleEdit = (funcionario) => {
    setEditingFuncionario(funcionario);
    setFormData({
      nome: funcionario.nome,
      funcao: funcionario.funcao,
      telefone: funcionario.telefone || '',
      email: funcionario.email || '',
      active: funcionario.active
    });
    setShowModal(true);
  };

  const handleDelete = async (id) => {
    if (!confirm('Tem certeza que deseja excluir este funcionário?')) return;

    try {
      const { error } = await supabase
        .from('funcionarios')
        .delete()
        .eq('id', id);

      if (error) throw error;
      loadFuncionarios();
    } catch (error) {
      setError(error.message);
    }
  };

  const resetForm = () => {
    setFormData({
      nome: '',
      funcao: '',
      telefone: '',
      email: '',
      active: true
    });
    setEditingFuncionario(null);
  };

  // Estatísticas
  const totalFuncionarios = funcionarios.length;
  const funcionariosAtivos = funcionarios.filter(f => f.active).length;
  const funcionariosInativos = funcionarios.filter(f => !f.active).length;
  const funcoesUnicas = [...new Set(funcionarios.filter(f => f.active).map(f => f.funcao))];

  return (
    <DashboardLayout>
      <div className="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 className="h2">Gerenciar Funcionários</h1>
        <button 
          className="btn btn-primary"
          onClick={() => {
            resetForm();
            setShowModal(true);
          }}
        >
          <i className="fas fa-plus"></i> Novo Funcionário
        </button>
      </div>

      {error && (
        <div className="alert alert-danger alert-dismissible fade show" role="alert">
          {error}
          <button type="button" className="btn-close" data-bs-dismiss="alert"></button>
        </div>
      )}

      {/* Estatísticas */}
      <div className="row mb-4">
        <div className="col-xl-3 col-md-6 mb-4">
          <div className="card card-stats border-left-primary shadow h-100 py-2">
            <div className="card-body">
              <div className="row no-gutters align-items-center">
                <div className="col mr-2">
                  <div className="text-xs font-weight-bold text-primary text-uppercase mb-1">
                    Total de Funcionários
                  </div>
                  <div className="h5 mb-0 font-weight-bold text-gray-800">{totalFuncionarios}</div>
                </div>
                <div className="col-auto">
                  <i className="fas fa-users fa-2x text-gray-300"></i>
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
                    Ativos
                  </div>
                  <div className="h5 mb-0 font-weight-bold text-gray-800">{funcionariosAtivos}</div>
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
                <div className="text-xs font-weight-bold text-warning text-uppercase mb-1">
                  Inativos
                </div>
                <div className="h5 mb-0 font-weight-bold text-gray-800">{funcionariosInativos}</div>
              </div>
            </div>
            <div className="col-auto">
              <i className="fas fa-pause-circle fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>

        <div className="col-xl-3 col-md-6 mb-4">
          <div className="card card-stats border-left-info shadow h-100 py-2">
            <div className="card-body">
              <div className="row no-gutters align-items-center">
                <div className="col mr-2">
                  <div className="text-xs font-weight-bold text-info text-uppercase mb-1">
                    Funções
                  </div>
                  <div className="h5 mb-0 font-weight-bold text-gray-800">{funcoesUnicas.length}</div>
                </div>
                <div className="col-auto">
                  <i className="fas fa-briefcase fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Filtros */}
      <div className="card mb-4">
        <div className="card-body">
          <form className="row g-3">
            <div className="col-md-3">
              <label htmlFor="search" className="form-label">Buscar</label>
              <input
                type="text"
                className="form-control"
                id="search"
                placeholder="Nome ou email"
                value={filters.search}
                onChange={(e) => setFilters({...filters, search: e.target.value})}
              />
            </div>
            <div className="col-md-3">
              <label htmlFor="funcao" className="form-label">Função</label>
              <select
                className="form-select"
                id="funcao"
                value={filters.funcao}
                onChange={(e) => setFilters({...filters, funcao: e.target.value})}
              >
                <option value="">Todas</option>
                {funcoesUnicas.map(funcao => (
                  <option key={funcao} value={funcao}>{funcao}</option>
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
                <option value="1">Ativos</option>
                <option value="0">Inativos</option>
              </select>
            </div>
            <div className="col-md-3">
              <label className="form-label">&nbsp;</label>
              <div>
                <button 
                  type="button" 
                  className="btn btn-primary"
                  onClick={() => setFilters({ search: '', funcao: '', status: '' })}
                >
                  <i className="fas fa-times"></i> Limpar
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      {/* Lista de Funcionários */}
      <div className="card">
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
                    <th>Nome</th>
                    <th>Função</th>
                    <th>Contato</th>
                    <th>Status</th>
                    <th>Data de Cadastro</th>
                    <th>Ações</th>
                  </tr>
                </thead>
                <tbody>
                  {funcionarios.length === 0 ? (
                    <tr>
                      <td colSpan="6" className="text-center text-muted py-4">
                        <i className="fas fa-inbox fa-2x mb-2"></i><br />
                        Nenhum funcionário encontrado
                      </td>
                    </tr>
                  ) : (
                    funcionarios.map((funcionario) => (
                      <tr key={funcionario.id}>
                        <td>
                          <strong>{funcionario.nome}</strong>
                        </td>
                        <td>
                          <span className="badge bg-info">{funcionario.funcao}</span>
                        </td>
                        <td>
                          {funcionario.telefone && (
                            <div><i className="fas fa-phone me-1"></i> {funcionario.telefone}</div>
                          )}
                          {funcionario.email && (
                            <div><i className="fas fa-envelope me-1"></i> {funcionario.email}</div>
                          )}
                        </td>
                        <td>
                          {funcionario.active ? (
                            <span className="badge bg-success">Ativo</span>
                          ) : (
                            <span className="badge bg-secondary">Inativo</span>
                          )}
                        </td>
                        <td>
                          {new Date(funcionario.created_at).toLocaleDateString('pt-BR')}
                        </td>
                        <td>
                          <div className="btn-group" role="group">
                            <button 
                              className="btn btn-sm btn-outline-warning"
                              onClick={() => handleEdit(funcionario)}
                              title="Editar"
                            >
                              <i className="fas fa-edit"></i>
                            </button>
                            <button 
                              className="btn btn-sm btn-outline-danger"
                              onClick={() => handleDelete(funcionario.id)}
                              title="Excluir"
                            >
                              <i className="fas fa-trash"></i>
                            </button>
                          </div>
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

      {/* Modal de Criação/Edição */}
      {showModal && (
        <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
          <div className="modal-dialog">
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="modal-title">
                  {editingFuncionario ? 'Editar Funcionário' : 'Novo Funcionário'}
                </h5>
                <button 
                  type="button" 
                  className="btn-close" 
                  onClick={() => {
                    setShowModal(false);
                    resetForm();
                  }}
                ></button>
              </div>
              <form onSubmit={handleSubmit}>
                <div className="modal-body">
                  <div className="mb-3">
                    <label htmlFor="nome" className="form-label">Nome Completo *</label>
                    <input
                      type="text"
                      className="form-control"
                      id="nome"
                      value={formData.nome}
                      onChange={(e) => setFormData({...formData, nome: e.target.value})}
                      required
                    />
                  </div>
                  
                  <div className="mb-3">
                    <label htmlFor="funcao" className="form-label">Função *</label>
                    <input
                      type="text"
                      className="form-control"
                      id="funcao"
                      value={formData.funcao}
                      onChange={(e) => setFormData({...formData, funcao: e.target.value})}
                      placeholder="Ex: Instalador, Auxiliar, Supervisor"
                      required
                    />
                  </div>
                  
                  <div className="row">
                    <div className="col-md-6">
                      <div className="mb-3">
                        <label htmlFor="telefone" className="form-label">Telefone</label>
                        <input
                          type="tel"
                          className="form-control"
                          id="telefone"
                          value={formData.telefone}
                          onChange={(e) => setFormData({...formData, telefone: e.target.value})}
                          placeholder="(11) 99999-9999"
                        />
                      </div>
                    </div>
                    <div className="col-md-6">
                      <div className="mb-3">
                        <label htmlFor="email" className="form-label">Email</label>
                        <input
                          type="email"
                          className="form-control"
                          id="email"
                          value={formData.email}
                          onChange={(e) => setFormData({...formData, email: e.target.value})}
                          placeholder="funcionario@empresa.com"
                        />
                      </div>
                    </div>
                  </div>

                  {editingFuncionario && (
                    <div className="mb-3">
                      <div className="form-check">
                        <input
                          className="form-check-input"
                          type="checkbox"
                          id="active"
                          checked={formData.active}
                          onChange={(e) => setFormData({...formData, active: e.target.checked})}
                        />
                        <label className="form-check-label" htmlFor="active">
                          Funcionário ativo
                        </label>
                      </div>
                    </div>
                  )}
                </div>
                <div className="modal-footer">
                  <button 
                    type="button" 
                    className="btn btn-secondary" 
                    onClick={() => {
                      setShowModal(false);
                      resetForm();
                    }}
                  >
                    Cancelar
                  </button>
                  <button type="submit" className="btn btn-primary">
                    {editingFuncionario ? 'Atualizar' : 'Criar'} Funcionário
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
