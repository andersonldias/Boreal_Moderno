<?php if ($obraFilter && $statsObra): ?>
<!-- Estatísticas e Progresso -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4"><div class="card card-stats"><div class="card-body"><div class="row"><div class="col"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total de Cômodos</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $statsObra['total']; ?></div></div><div class="col-auto"><i class="fas fa-door-open fa-2x text-gray-300"></i></div></div></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card card-stats"><div class="card-body"><div class="row"><div class="col"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Instalados</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $statsObra['instalados']; ?></div></div><div class="col-auto"><i class="fas fa-check-circle fa-2x text-gray-300"></i></div></div></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card card-stats"><div class="card-body"><div class="row"><div class="col"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Não Instalados</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $statsObra['nao_instalados']; ?></div></div><div class="col-auto"><i class="fas fa-clock fa-2x text-gray-300"></i></div></div></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card card-stats"><div class="card-body"><div class="row"><div class="col"><div class="text-xs font-weight-bold text-info text-uppercase mb-1">Progresso</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $statsObra['percentual']; ?>%</div></div><div class="col-auto"><i class="fas fa-percentage fa-2x text-gray-300"></i></div></div></div></div></div>
</div>
<div class="card mb-4"><div class="card-body"><h6 class="card-title">Progresso da Instalação</h6><div class="progress mb-2" style="height: 25px;"><div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $statsObra['percentual']; ?>%" aria-valuenow="<?php echo $statsObra['percentual']; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $statsObra['percentual']; ?>%</div></div><small class="text-muted"><?php echo $statsObra['instalados']; ?> de <?php echo $statsObra['total']; ?> cômodos instalados</small></div></div>
<?php endif; ?>

<?php if ($obraFilter): ?>
<!-- Lista de Cômodos Legado -->
<div class="card">
    <div class="card-header"><h5 class="mb-0">Cômodos da Obra</h5></div>
    <div class="card-body">
        <?php if (empty($comodos)) : ?>
            <div class="text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-3"></i><h5>Nenhum cômodo encontrado</h5><p>Não há cômodos cadastrados para esta obra ou que atendam aos filtros aplicados.</p></div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($comodos as $comodo): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card comodo-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><?php echo htmlspecialchars($comodo['nome']); ?></h6>
                                <?php 
                                $statusColors = ['nao_instalado' => 'secondary', 'em_instalacao' => 'warning', 'instalado' => 'success'];
                                $statusLabels = ['nao_instalado' => 'Não Instalado', 'em_instalacao' => 'Em Instalação', 'instalado' => 'Instalado'];
                                ?>
                                <span class="badge bg-<?php echo $statusColors[$comodo['status']] ?? 'secondary'; ?> status-badge"><?php echo $statusLabels[$comodo['status']] ?? $comodo['status']; ?></span>
                            </div>
                            <div class="card-body">
                                <div class="mb-2"><strong>Tipo:</strong> <?php echo htmlspecialchars($comodo['tipo_esquadria']); ?></div>
                                <?php if ($comodo['modelo']): ?><div class="mb-2"><strong>Modelo:</strong> <?php echo htmlspecialchars($comodo['modelo']); ?></div><?php endif; ?>
                                <?php if ($comodo['dimensoes']): ?><div class="mb-2"><strong>Dimensões:</strong> <?php echo htmlspecialchars($comodo['dimensoes']); ?></div><?php endif; ?>
                                <?php if ($comodo['observacao']): ?><div class="mb-2"><strong>Observação:</strong> <small class="text-muted"><?php echo htmlspecialchars($comodo['observacao']); ?></small></div><?php endif; ?>
                                <?php if ($comodo['status'] == 'instalado' && $comodo['data_instalacao']): ?><div class="mb-2"><strong>Instalado em:</strong> <small class="text-muted"><?php echo formatDateTime($comodo['data_instalacao']); ?></small></div><?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-sm btn-outline-primary" onclick="updateStatus(<?php echo $comodo['id']; ?>, '<?php echo $comodo['status']; ?>', '<?php echo htmlspecialchars(addslashes($comodo['nome'])); ?>')"><i class="fas fa-edit"></i> Atualizar Status</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<!-- Mensagem para selecionar obra -->
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-building fa-3x text-muted mb-3"></i>
        <h5>Selecione uma Obra</h5>
        <p class="text-muted">Escolha uma obra para visualizar e gerenciar suas instalações.</p>
    </div>
</div>
<?php endif; ?>
