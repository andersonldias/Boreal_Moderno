'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { supabase } from '@/utils/supabase';
import DashboardLayout from '@/components/DashboardLayout';

export default function ObrasPage() {
  const router = useRouter();
  const [obras, setObras] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [editingObra, setEditingObra] = useState(null);
  const [formData, setFormData] = useState({
    nome: '',
    cliente: '',
    endereco: '',
    data_inicio: '',
    status: 'planejada',
    observacoes: ''
  });

  useEffect(() => {
    checkAuth();
    loadObras();
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
      setLoading(true);
      const { data, error } = await supabase
        .from('obras')
        .select('*')
        .order('created_at', { ascending: false });

      if (error) throw error;
      setObras(data || []);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    try {
      if (editingObra) {
        // Atualizar obra existente
        const { error } = await supabase
          .from('obras')
          .update(formData)
          .eq('id', editingObra.id);

        if (error) throw error;
      } else {
        // Criar nova obra
        const { error } = await supabase
          .from('obras')
          .insert([formData]);

        if (error) throw error;
      }

      setShowModal(false);
      setEditingObra(null);
      setFormData({
        nome: '',
        cliente: '',
        endereco: '',
        data_inicio: '',
        status: 'planejada',
        observacoes: ''
      });
      loadObras();
    } catch (error) {
      setError(error.message);
    }
  };

  const handleEdit = (obra) => {
    setEditingObra(obra);
    setFormData({
      nome: obra.nome,
      cliente: obra.cliente,
      endereco: obra.endereco,
      data_inicio: obra.data_inicio || '',
      status: obra.status,
      observacoes: obra.observacoes || ''
    });
    setShowModal(true);
  };

  const handleDelete = async (id) => {
    if (!confirm('Tem certeza que deseja excluir esta obra?')) return;

    try {
      const { error } = await supabase
        .from('obras')
        .delete()
        .eq('id', id);

      if (error) throw error;
      loadObras();
    } catch (error) {
      setError(error.message);
    }
  };

  const resetForm = () => {
    setFormData({
      nome: '',
      cliente: '',
      endereco: '',
      data_inicio: '',
      status: 'planejada',
      observacoes: ''
    });
    setEditingObra(null);
  };

  return (
    <DashboardLayout>
      <div className="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 className="h2">Gerenciar Obras</h1>
        <button 
          className="btn btn-primary"
          onClick={() => {
            resetForm();
            setShowModal(true);
          }}
        >
          <i className="fas fa-plus"></i> Nova Obra
        </button>
      </div>

      {error && (
        <div className="alert alert-danger alert-dismissible fade show" role="alert">
          {error}
          <button type="button" className="btn-close" data-bs-dismiss="alert"></button>
        </div>
      )}

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
                    <th>Cliente</th>
                    <th>Endereço</th>
                    <th>Status</th>
                    <th>Data de Início</th>
                    <th>Ações</th>
                  </tr>
                </thead>
                <tbody>
                  {obras.length === 0 ? (
                    <tr>
                      <td colSpan="6" className="text-center text-muted py-4">
                        <i className="fas fa-building fa-2x mb-2"></i><br />
                        Nenhuma obra encontrada
                      </td>
                    </tr>
                  ) : (
                    obras.map((obra) => (
                      <tr key={obra.id}>
                        <td>
                          <strong>{obra.nome}</strong>
                        </td>
                        <td>{obra.cliente}</td>
                        <td>
                          <small className="text-muted">{obra.endereco}</small>
                        </td>
                        <td>
                          <span className={`badge bg-${getStatusColor(obra.status)}`}>
                            {getStatusLabel(obra.status)}
                          </span>
                        </td>
                        <td>
                          {obra.data_inicio ? new Date(obra.data_inicio).toLocaleDateString('pt-BR') : '-'}
                        </td>
                        <td>
                          <div className="btn-group" role="group">
                            <button 
                              className="btn btn-sm btn-outline-warning"
                              onClick={() => handleEdit(obra)}
                              title="Editar"
                            >
                              <i className="fas fa-edit"></i>
                            </button>
                            <button 
                              className="btn btn-sm btn-outline-danger"
                              onClick={() => handleDelete(obra.id)}
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
          <div className="modal-dialog modal-lg">
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="modal-title">
                  {editingObra ? 'Editar Obra' : 'Nova Obra'}
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
                  <div className="row">
                    <div className="col-md-6">
                      <div className="mb-3">
                        <label htmlFor="nome" className="form-label">Nome da Obra *</label>
                        <input
                          type="text"
                          className="form-control"
                          id="nome"
                          value={formData.nome}
                          onChange={(e) => setFormData({...formData, nome: e.target.value})}
                          required
                        />
                      </div>
                    </div>
                    <div className="col-md-6">
                      <div className="mb-3">
                        <label htmlFor="cliente" className="form-label">Cliente *</label>
                        <input
                          type="text"
                          className="form-control"
                          id="cliente"
                          value={formData.cliente}
                          onChange={(e) => setFormData({...formData, cliente: e.target.value})}
                          required
                        />
                      </div>
                    </div>
                  </div>
                  
                  <div className="mb-3">
                    <label htmlFor="endereco" className="form-label">Endereço *</label>
                    <textarea
                      className="form-control"
                      id="endereco"
                      rows="2"
                      value={formData.endereco}
                      onChange={(e) => setFormData({...formData, endereco: e.target.value})}
                      required
                    />
                  </div>

                  <div className="row">
                    <div className="col-md-6">
                      <div className="mb-3">
                        <label htmlFor="data_inicio" className="form-label">Data de Início</label>
                        <input
                          type="date"
                          className="form-control"
                          id="data_inicio"
                          value={formData.data_inicio}
                          onChange={(e) => setFormData({...formData, data_inicio: e.target.value})}
                        />
                      </div>
                    </div>
                    <div className="col-md-6">
                      <div className="mb-3">
                        <label htmlFor="status" className="form-label">Status</label>
                        <select
                          className="form-select"
                          id="status"
                          value={formData.status}
                          onChange={(e) => setFormData({...formData, status: e.target.value})}
                        >
                          <option value="planejada">Planejada</option>
                          <option value="em_andamento">Em Andamento</option>
                          <option value="em_finalizacao">Em Finalização</option>
                          <option value="concluida">Concluída</option>
                          <option value="pausada">Pausada</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <div className="mb-3">
                    <label htmlFor="observacoes" className="form-label">Observações</label>
                    <textarea
                      className="form-control"
                      id="observacoes"
                      rows="3"
                      value={formData.observacoes}
                      onChange={(e) => setFormData({...formData, observacoes: e.target.value})}
                    />
                  </div>
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
                    {editingObra ? 'Atualizar' : 'Criar'} Obra
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
