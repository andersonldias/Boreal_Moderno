'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { supabase } from '@/utils/supabase';
import DashboardLayout from '@/components/DashboardLayout';

export default function FotosPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [fotos, setFotos] = useState([]);
  const [obras, setObras] = useState([]);
  const [showUploadModal, setShowUploadModal] = useState(false);
  const [selectedObra, setSelectedObra] = useState('');
  const [filtros, setFiltros] = useState({
    obraId: '',
    tipo: '',
    search: ''
  });
  const [uploadForm, setUploadForm] = useState({
    obra_id: '',
    comodo_id: '',
    titulo: '',
    descricao: '',
    tipo: 'instalacao',
    file: null
  });

  useEffect(() => {
    checkAuth();
    loadObras();
    loadFotos();
  }, []);

  useEffect(() => {
    loadFotos();
  }, [filtros]);

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

  const loadFotos = async () => {
    try {
      setLoading(true);
      
      let query = supabase
        .from('fotos')
        .select(`
          *,
          obras!inner(nome, cliente),
          comodos(nome)
        `)
        .order('created_at', { ascending: false });

      if (filtros.obraId) {
        query = query.eq('obra_id', filtros.obraId);
      }

      if (filtros.tipo) {
        query = query.eq('tipo', filtros.tipo);
      }

      if (filtros.search) {
        query = query.or(`titulo.ilike.%${filtros.search}%,descricao.ilike.%${filtros.search}%`);
      }

      const { data, error } = await query;

      if (error) throw error;
      setFotos(data || []);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  };

  const handleFileUpload = async (e) => {
    e.preventDefault();
    
    if (!uploadForm.file || !uploadForm.obra_id) {
      alert('Por favor, selecione um arquivo e uma obra.');
      return;
    }

    try {
      // Upload do arquivo para o Supabase Storage
      const fileExt = uploadForm.file.name.split('.').pop();
      const fileName = `${Date.now()}.${fileExt}`;
      const filePath = `fotos/${uploadForm.obra_id}/${fileName}`;

      const { error: uploadError } = await supabase.storage
        .from('fotos')
        .upload(filePath, uploadForm.file);

      if (uploadError) throw uploadError;

      // Salvar metadados no banco
      const { error: dbError } = await supabase
        .from('fotos')
        .insert([{
          obra_id: uploadForm.obra_id,
          comodo_id: uploadForm.comodo_id || null,
          titulo: uploadForm.titulo,
          descricao: uploadForm.descricao,
          tipo: uploadForm.tipo,
          filename: uploadForm.file.name,
          storage_path: filePath,
          file_size: uploadForm.file.size,
          mime_type: uploadForm.file.type
        }]);

      if (dbError) throw dbError;

      setShowUploadModal(false);
      setUploadForm({
        obra_id: '',
        comodo_id: '',
        titulo: '',
        descricao: '',
        tipo: 'instalacao',
        file: null
      });
      loadFotos();
    } catch (error) {
      setError(error.message);
    }
  };

  const deleteFoto = async (id) => {
    if (!confirm('Tem certeza que deseja excluir esta foto?')) return;

    try {
      const { error } = await supabase
        .from('fotos')
        .delete()
        .eq('id', id);

      if (error) throw error;
      loadFotos();
    } catch (error) {
      setError(error.message);
    }
  };

  const getTipoLabel = (tipo) => {
    const labels = {
      'instalacao': 'Instalação',
      'antes': 'Antes',
      'depois': 'Depois',
      'problema': 'Problema',
      'outro': 'Outro'
    };
    return labels[tipo] || tipo;
  };

  const getTipoColor = (tipo) => {
    const colors = {
      'instalacao': 'primary',
      'antes': 'info',
      'depois': 'success',
      'problema': 'danger',
      'outro': 'secondary'
    };
    return colors[tipo] || 'secondary';
  };

  return (
    <DashboardLayout>
      <div className="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 className="h2">Gerenciar Fotos</h1>
        <button 
          className="btn btn-primary"
          onClick={() => setShowUploadModal(true)}
        >
          <i className="fas fa-plus"></i> Nova Foto
        </button>
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
          <h5 className="card-title">Filtros</h5>
          <form className="row g-3">
            <div className="col-md-4">
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
              <label htmlFor="tipo" className="form-label">Tipo</label>
              <select
                className="form-select"
                id="tipo"
                value={filtros.tipo}
                onChange={(e) => setFiltros({...filtros, tipo: e.target.value})}
              >
                <option value="">Todos os tipos</option>
                <option value="instalacao">Instalação</option>
                <option value="antes">Antes</option>
                <option value="depois">Depois</option>
                <option value="problema">Problema</option>
                <option value="outro">Outro</option>
              </select>
            </div>
            <div className="col-md-3">
              <label htmlFor="search" className="form-label">Buscar</label>
              <input
                type="text"
                className="form-control"
                id="search"
                placeholder="Título ou descrição"
                value={filtros.search}
                onChange={(e) => setFiltros({...filtros, search: e.target.value})}
              />
            </div>
            <div className="col-md-2">
              <label className="form-label">&nbsp;</label>
              <button 
                type="button" 
                className="btn btn-secondary w-100"
                onClick={() => setFiltros({ obraId: '', tipo: '', search: '' })}
              >
                <i className="fas fa-times"></i> Limpar
              </button>
            </div>
          </form>
        </div>
      </div>

      {/* Grid de Fotos */}
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
              {fotos.length === 0 ? (
                <div className="col-12 text-center text-muted py-5">
                  <i className="fas fa-camera fa-3x mb-3"></i>
                  <h5>Nenhuma foto encontrada</h5>
                  <p>Faça upload de fotos para começar.</p>
                </div>
              ) : (
                fotos.map((foto) => (
                  <div key={foto.id} className="col-md-6 col-lg-4 mb-4">
                    <div className="card foto-card h-100">
                      <div className="card-img-top position-relative" style={{ height: '200px', backgroundColor: '#f8f9fa' }}>
                        <div className="d-flex align-items-center justify-content-center h-100">
                          <i className="fas fa-image fa-3x text-muted"></i>
                        </div>
                        <div className="position-absolute top-0 end-0 m-2">
                          <span className={`badge bg-${getTipoColor(foto.tipo)}`}>
                            {getTipoLabel(foto.tipo)}
                          </span>
                        </div>
                      </div>
                      <div className="card-body">
                        <h6 className="card-title">{foto.titulo}</h6>
                        <p className="card-text">
                          <small className="text-muted">
                            <strong>Obra:</strong> {foto.obras?.nome}<br />
                            {foto.comodos?.nome && (
                              <>
                                <strong>Cômodo:</strong> {foto.comodos.nome}<br />
                              </>
                            )}
                            <strong>Data:</strong> {new Date(foto.created_at).toLocaleDateString('pt-BR')}
                          </small>
                        </p>
                        {foto.descricao && (
                          <p className="card-text">
                            <small>{foto.descricao}</small>
                          </p>
                        )}
                      </div>
                      <div className="card-footer">
                        <div className="btn-group w-100" role="group">
                          <button 
                            className="btn btn-sm btn-outline-primary"
                            onClick={() => {
                              // Implementar visualização da foto
                              alert('Visualizar foto');
                            }}
                          >
                            <i className="fas fa-eye"></i> Ver
                          </button>
                          <button 
                            className="btn btn-sm btn-outline-danger"
                            onClick={() => deleteFoto(foto.id)}
                          >
                            <i className="fas fa-trash"></i> Excluir
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                ))
              )}
            </div>
          )}
        </div>
      </div>

      {/* Modal de Upload */}
      {showUploadModal && (
        <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
          <div className="modal-dialog">
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="modal-title">Upload de Foto</h5>
                <button 
                  type="button" 
                  className="btn-close" 
                  onClick={() => setShowUploadModal(false)}
                ></button>
              </div>
              <form onSubmit={handleFileUpload}>
                <div className="modal-body">
                  <div className="mb-3">
                    <label htmlFor="obra_id" className="form-label">Obra *</label>
                    <select
                      className="form-select"
                      id="obra_id"
                      value={uploadForm.obra_id}
                      onChange={(e) => setUploadForm({...uploadForm, obra_id: e.target.value})}
                      required
                    >
                      <option value="">Selecione uma obra...</option>
                      {obras.map(obra => (
                        <option key={obra.id} value={obra.id}>
                          {obra.nome} - {obra.cliente}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="mb-3">
                    <label htmlFor="titulo" className="form-label">Título *</label>
                    <input
                      type="text"
                      className="form-control"
                      id="titulo"
                      value={uploadForm.titulo}
                      onChange={(e) => setUploadForm({...uploadForm, titulo: e.target.value})}
                      required
                    />
                  </div>

                  <div className="mb-3">
                    <label htmlFor="tipo" className="form-label">Tipo *</label>
                    <select
                      className="form-select"
                      id="tipo"
                      value={uploadForm.tipo}
                      onChange={(e) => setUploadForm({...uploadForm, tipo: e.target.value})}
                      required
                    >
                      <option value="instalacao">Instalação</option>
                      <option value="antes">Antes</option>
                      <option value="depois">Depois</option>
                      <option value="problema">Problema</option>
                      <option value="outro">Outro</option>
                    </select>
                  </div>

                  <div className="mb-3">
                    <label htmlFor="descricao" className="form-label">Descrição</label>
                    <textarea
                      className="form-control"
                      id="descricao"
                      rows="3"
                      value={uploadForm.descricao}
                      onChange={(e) => setUploadForm({...uploadForm, descricao: e.target.value})}
                    />
                  </div>

                  <div className="mb-3">
                    <label htmlFor="file" className="form-label">Arquivo *</label>
                    <input
                      type="file"
                      className="form-control"
                      id="file"
                      accept="image/*"
                      onChange={(e) => setUploadForm({...uploadForm, file: e.target.files[0]})}
                      required
                    />
                  </div>
                </div>
                <div className="modal-footer">
                  <button 
                    type="button" 
                    className="btn btn-secondary" 
                    onClick={() => setShowUploadModal(false)}
                  >
                    Cancelar
                  </button>
                  <button type="submit" className="btn btn-primary">
                    <i className="fas fa-upload me-1"></i> Upload
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
