<!-- Modal Atualizar Status -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Atualizar Status da Instalação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="comodo_id" id="update_comodo_id">
                    
                    <p>Atualizando status para: <strong id="updateComodoNome"></strong></p>
                    
                    <div class="mb-3">
                        <label for="update_status" class="form-label">Novo Status *</label>
                        <select class="form-select" id="update_status" name="status" required>
                            <option value="nao_instalado">Não Instalado</option>
                            <option value="em_instalacao">Em Instalação</option>
                            <option value="instalado">Instalado</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="funcionarioField" style="display: none;">
                            <label class="form-label">Funcionário(s) Responsável(eis)</label>
                            <div class="p-2 rounded" style="max-height: 150px; overflow-y: auto; border: 1px solid #dee2e6;">
                                <?php if (empty($funcionarios)): ?>
                                    <p class="text-muted small mb-0">Nenhum funcionário ativo encontrado.</p>
                                <?php else: ?>
                                    <?php foreach ($funcionarios as $funcionario): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="funcionario_ids[]" value="<?php echo $funcionario['id']; ?>" id="func_<?php echo $funcionario['id']; ?>">
                                            <label class="form-check-label" for="func_<?php echo $funcionario['id']; ?>">
                                                <?php echo htmlspecialchars($funcionario['nome']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    
                    <div class="mb-3">
                        <label for="observacao" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacao" name="observacao" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="submit-status-update-btn">Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Novo Cômodo (Apenas para a visualização legada) -->
<?php if (isGestor()): ?>
<div class="modal fade" id="novoComodoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Cômodo Legado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_comodo">
                        <input type="hidden" name="obra_id" value="<?php echo $obraFilter; ?>">
                        <div class="mb-3"><label class="form-label">Nome do Cômodo</label><input type="text" class="form-control" name="nome" required></div>
                        <div class="mb-3"><label class="form-label">Tipo de Esquadria</label><input type="text" class="form-control" name="tipo_esquadria" required></div>
                        <div class="mb-3"><label class="form-label">Modelo</label><input type="text" class="form-control" name="modelo"></div>
                        <div class="mb-3"><label class="form-label">Dimensões</label><input type="text" class="form-control" name="dimensoes"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
