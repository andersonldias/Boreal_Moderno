'use client';

import { useState, useEffect } from 'react';
import { supabase } from '@/utils/supabase';
import DashboardLayout from '@/components/DashboardLayout';

export default function DashboardPage() {
  const [obras, setObras] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchObras = async () => {
      try {
        setLoading(true);
        const { data, error } = await supabase
          .from('obras')
          .select('id, nome, cliente, status, created_at');

        if (error) {
          throw error;
        }

        setObras(data);
      } catch (error) {
        setError(error.message);
      } finally {
        setLoading(false);
      }
    };

    fetchObras();
  }, []);

  return (
    <DashboardLayout>
      <div className="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 className="h2">Dashboard</h1>
        {/* Botões de ação podem ser adicionados aqui */}
      </div>

      {/* Cards de estatísticas virão aqui */}
      
      <div className="card mt-4">
        <div className="card-header">
          <h5 className="mb-0">Obras Recentes</h5>
        </div>
        <div className="card-body">
          {loading && <p>Carregando obras...</p>}
          {error && <div className="alert alert-danger">Erro ao carregar obras: {error}</div>}
          {!loading && !error && (
            <div className="table-responsive">
              <table className="table">
                <thead>
                  <tr>
                    <th>Nome da Obra</th>
                    <th>Cliente</th>
                    <th>Status</th>
                    <th>Data de Criação</th>
                  </tr>
                </thead>
                <tbody>
                  {obras.length > 0 ? (
                    obras.map(obra => (
                      <tr key={obra.id}>
                        <td>{obra.nome}</td>
                        <td>{obra.cliente}</td>
                        <td><span className="badge bg-primary">{obra.status}</span></td>
                        <td>{new Date(obra.created_at).toLocaleDateString()}</td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan="4" className="text-center">Nenhuma obra encontrada.</td>
                    </tr>
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
