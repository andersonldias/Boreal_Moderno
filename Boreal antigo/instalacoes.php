<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$message = '';
$messageType = '';

$message = '';
$messageType = '';

// A lógica de processamento de POST foi movida para a API (api_update_status.php)

// --- Lógica de Carregamento da Página ---

// Filtros
$obraFilter = $_GET['obra_id'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// Obter obras disponíveis para o usuário
if (isGestor()) {
    $obras = fetchAll("SELECT id, nome, cliente, usa_hierarquia_locais FROM obras ORDER BY nome ASC");
} else {
    $obras = fetchAll("SELECT o.id, o.nome, o.cliente, o.usa_hierarquia_locais FROM obras o JOIN user_obra_permissions p ON o.id = p.obra_id WHERE p.user_id = ? ORDER BY o.nome ASC", [$userId]);
}

// Se não há filtro de obra, usar a primeira disponível
if (!$obraFilter && !empty($obras)) {
    $obraFilter = $obras[0]['id'];
}

// Determinar se a obra selecionada usa a nova hierarquia
$selectedObraUsesHierarchy = false;
if ($obraFilter) {
    foreach ($obras as $obra) {
        if ($obra['id'] == $obraFilter) {
            $selectedObraUsesHierarchy = !empty($obra['usa_hierarquia_locais']);
            break;
        }
    }
}

// Obter dados apenas se a obra selecionada NÃO usar o sistema de hierarquia
$comodos = [];
if ($obraFilter && !$selectedObraUsesHierarchy) {
    $whereConditions = ["c.obra_id = ?"];
    $params = [$obraFilter];
    if ($statusFilter) {
        $whereConditions[] = "c.status = ?";
        $params[] = $statusFilter;
    }
    if ($searchTerm) {
        $whereConditions[] = "(c.nome LIKE ? OR c.tipo_esquadria LIKE ? OR c.modelo LIKE ?)";
        $searchParam = "%$searchTerm%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    }
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    $query = "SELECT c.* FROM comodos c $whereClause ORDER BY c.nome ASC";
    $comodos = fetchAll($query, $params);
}

$funcionarios = fetchAll("SELECT * FROM funcionarios WHERE active = 1 ORDER BY nome ASC");
$statsObra = $obraFilter ? getObraStats($obraFilter) : null;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Instalações</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>.sidebar{min-height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)}.sidebar .nav-link{color:rgba(255,255,255,.8);border-radius:10px;margin:2px 0;transition:all .3s}.sidebar .nav-link:hover,.sidebar .nav-link.active{color:#fff;background:rgba(255,255,255,.1);transform:translateX(5px)}.comodo-card{border-radius:15px;box-shadow:0 3px 10px rgba(0,0,0,.1);transition:transform .3s}.comodo-card:hover{transform:translateY(-2px)}</style>
</head>
<body>
<div class="container-fluid"><div class="row">
    <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
        <!-- Sidebar Content -->
        <div class="position-sticky pt-3">
            <div class="text-center mb-4"><i class="fas fa-building text-white" style="font-size: 2rem;"></i><h5 class="text-white mt-2">Instalação de Esquadrias</h5><small class="text-white-50">Controle de Obras</small></div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                <?php if (isGestor()): ?>
                <li class="nav-item"><a class="nav-link" href="obras.php"><i class="fas fa-building me-2"></i>Gerenciar Obras</a></li>
                <li class="nav-item"><a class="nav-link" href="funcionarios.php"><i class="fas fa-users me-2"></i>Funcionários</a></li>
                <li class="nav-item"><a class="nav-link" href="usuarios.php"><i class="fas fa-user-cog me-2"></i>Usuários</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link active" href="instalacoes.php"><i class="fas fa-tools me-2"></i>Instalações</a></li>
                <li class="nav-item"><a class="nav-link" href="relatorios.php"><i class="fas fa-chart-bar me-2"></i>Relatórios</a></li>
                <li class="nav-item"><a class="nav-link" href="fotos.php"><i class="fas fa-camera me-2"></i>Fotos</a></li>
                <li class="nav-item mt-4"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
            </ul>
        </div>
    </nav>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <?php require_once 'includes/header.php'; ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Gerenciar Instalações</h1>
        </div>

        <!-- Filtro de Obra -->
        <div class="card mb-4" id="filtros-container">
            <div class="card-body">
                <form method="GET" class="row align-items-end gy-2">
                    <div class="col-12 col-md-6 col-lg-5">
                        <label for="obra_id" class="form-label">Selecione a Obra</label>
                        <select class="form-select" id="obra_id" name="obra_id" onchange="this.form.submit()">
                            <option value="">Selecione...</option>
                            <?php foreach ($obras as $obra): ?>
                                <option value="<?php echo $obra['id']; ?>" <?php echo $obraFilter == $obra['id'] ? 'selected' : ''; ?> data-usa-hierarquia="<?php echo $obra['usa_hierarquia_locais'] ? '1' : '0'; ?>">
                                    <?php echo htmlspecialchars($obra['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Todos</option>
                            <option value="nao_instalado" <?php echo $statusFilter == 'nao_instalado' ? 'selected' : ''; ?>>Não Instalado</option>
                            <option value="em_instalacao" <?php echo $statusFilter == 'em_instalacao' ? 'selected' : ''; ?>>Em Instalação</option>
                            <option value="instalado" <?php echo $statusFilter == 'instalado' ? 'selected' : ''; ?>>Instalado</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="search" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($searchTerm); ?>" 
                               placeholder="Nome, tipo ou modelo">
                    </div>
                    <div class="col-12 col-md-6 col-lg-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
            </div>
        </div>

        <?php if ($obraFilter): ?>
            <!-- Container da Nova Visualização Hierárquica -->
            <div id="hierarquia-container" style="display: none;">
                <div class="card">
                    <div class="card-header"><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0" id="breadcrumb-container"></ol></nav></div>
                    <div class="card-body">
                        <div id="locais-loading" class="text-center" style="display: none;"><div class="spinner-border my-5"></div></div>
                        <div id="locais-list" class="row"></div>
                    </div>
                </div>
            </div>

            <!-- Container da Visualização Legada -->
            <div id="legacy-container" style="display: none;">
                <?php require 'instalacoes_legacy_view.php'; ?>
            </div>
        <?php else: ?>
            <div class="card"><div class="card-body text-center py-5"><i class="fas fa-building fa-3x text-muted mb-3"></i><h5>Selecione uma Obra</h5><p>Escolha uma obra para visualizar suas instalações.</p></div></div>
        <?php endif; ?>

    </main>
</div></div>

<!-- Modals (Atualizar Status, etc) -->
<?php require_once 'modals/instalacoes_modals.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const updateStatusModal = new bootstrap.Modal(document.getElementById('updateStatusModal'));

function updateStatus(comodoId, statusAtual, comodoNome) {
    document.getElementById('update_comodo_id').value = comodoId;
    document.getElementById('updateComodoNome').textContent = comodoNome;
    document.getElementById('update_status').value = statusAtual;
    
    const funcionarioField = document.getElementById('funcionarioField');
    funcionarioField.style.display = statusAtual === 'instalado' ? 'block' : 'none';

    // Limpa checkboxes antigos
    const checkboxes = document.querySelectorAll('input[name="funcionario_ids[]"]');
    checkboxes.forEach(cb => cb.checked = false);

    updateStatusModal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const updateStatusSelect = document.getElementById('update_status');
    if(updateStatusSelect) {
        updateStatusSelect.addEventListener('change', function() {
            document.getElementById('funcionarioField').style.display = this.value === 'instalado' ? 'block' : 'none';
        });
    }

    const obraSelect = document.getElementById('obra_id');
    const selectedOption = obraSelect.options[obraSelect.selectedIndex];
    const hierarquiaContainer = document.getElementById('hierarquia-container');
    const legacyContainer = document.getElementById('legacy-container');
    const filtrosContainer = document.getElementById('filtros-container');
    let currentParentId = null; // Variável para rastrear o nível atual

    if (!selectedOption || !selectedOption.value) return;

    const usaHierarquia = selectedOption.getAttribute('data-usa-hierarquia') === '1';
    
    if (usaHierarquia) {
        const legacyFilters = filtrosContainer.querySelector('.row');
        if(legacyFilters) legacyFilters.style.display = 'none';
        hierarquiaContainer.style.display = 'block';
        legacyContainer.style.display = 'none';

        const breadcrumbContainer = document.getElementById('breadcrumb-container');
        const locaisList = document.getElementById('locais-list');
        const locaisLoading = document.getElementById('locais-loading');
        let currentObraId = obraSelect.value;
        let breadcrumb = [];

        const escapeHTML = str => str ? str.toString().replace(/[&<>'"]/g, tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag])) : '';

        const renderComodoCard = (local) => {
            const statusColors = {'nao_instalado':'secondary','em_instalacao':'warning','instalado':'success'};
            const statusLabels = {'nao_instalado':'Não Instalado','em_instalacao':'Em Instalação','instalado':'Instalado'};
            const statusColor = statusColors[local.status] || 'secondary';
            const statusLabel = statusLabels[local.status] || local.status;
            
            const card = document.createElement('div');
            card.className = 'col-md-6 col-lg-4 mb-3';
            card.innerHTML = `
                <div class="card comodo-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">${escapeHTML(local.nome)}</h6>
                        <span class="badge bg-${statusColor} status-badge">${escapeHTML(statusLabel)}</span>
                    </div>
                    <div class="card-body">
                        ${local.observacao ? `<p class="mb-1"><small>${escapeHTML(local.observacao)}</small></p>` : ''}
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i> Atualizar Status</button>
                    </div>
                </div>`;
            card.querySelector('button').addEventListener('click', () => updateStatus(local.id, local.status, local.nome));
            return card;
        };

        const renderNavNode = (local) => {
            const div = document.createElement('div');
            div.className = 'col-md-4 col-lg-3 mb-3';
            div.innerHTML = `<a href="#" class="card comodo-card text-decoration-none h-100"><div class="card-body text-center d-flex flex-column justify-content-center"><i class="fas fa-folder-open fa-2x text-secondary mb-2"></i><h6 class="mb-0">${escapeHTML(local.nome)}</h6><small class="text-muted">(${escapeHTML(local.tipo)})</small></div></a>`;
            div.querySelector('a').addEventListener('click', e => { e.preventDefault(); breadcrumb.push({ id: local.id, nome: local.nome }); loadLocais(currentObraId, local.id); });
            return div;
        };

        const renderBreadcrumb = () => {
            breadcrumbContainer.innerHTML = '';
            const rootItem = document.createElement('li');
            rootItem.className = 'breadcrumb-item';
            rootItem.innerHTML = `<a href="#">Raiz da Obra</a>`;
            rootItem.querySelector('a').addEventListener('click', e => { e.preventDefault(); breadcrumb = []; loadLocais(currentObraId, null); });
            breadcrumbContainer.appendChild(rootItem);
            breadcrumb.forEach((item, index) => {
                const li = document.createElement('li');
                li.className = 'breadcrumb-item';
                if (index === breadcrumb.length - 1) { li.classList.add('active'); li.textContent = escapeHTML(item.nome); } else { 
                    const a = document.createElement('a'); a.href='#'; a.textContent=escapeHTML(item.nome); 
                    a.addEventListener('click', e => { e.preventDefault(); breadcrumb = breadcrumb.slice(0, index + 1); loadLocais(currentObraId, item.id); });
                    li.appendChild(a);
                }
                breadcrumbContainer.appendChild(li);
            });
        };

        const loadLocais = async (obraId, parentId) => {
            currentParentId = parentId; // Atualiza o nível atual
            locaisLoading.style.display = 'block';
            locaisList.innerHTML = '';
            const url = parentId ? `get_locais_instalacao.php?id_obra=${obraId}&parent_id=${parentId}` : `get_locais_instalacao.php?id_obra=${obraId}`;
            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error('Falha na rede');
                const data = await response.json();
                if (data.length === 0) {
                    locaisList.innerHTML = '<div class="col-12 text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-3"></i><h5>Nenhum item encontrado.</h5></div>';
                } else {
                    data.forEach(local => {
                        locaisList.appendChild(local.tipo === 'comodo' ? renderComodoCard(local) : renderNavNode(local));
                    });
                }
            } catch (error) {
                locaisList.innerHTML = `<div class="col-12 text-danger text-center py-5"><i class="fas fa-exclamation-triangle fa-3x mb-3"></i><h5>Erro ao carregar.</h5><p>${error.message}</p></div>`;
            } finally {
                locaisLoading.style.display = 'none';
                renderBreadcrumb();
            }
        };

        // Lógica do botão de submissão do modal
        document.getElementById('submit-status-update-btn').addEventListener('click', async function() {
            const form = this.closest('form');
            const comodoId = document.getElementById('update_comodo_id').value;
            const status = document.getElementById('update_status').value;
            const observacao = document.getElementById('observacao').value;
            const funcionarioCheckboxes = document.querySelectorAll('input[name="funcionario_ids[]"]:checked');
            const funcionario_ids = Array.from(funcionarioCheckboxes).map(cb => cb.value);

            const data = { comodo_id: comodoId, status, observacao, funcionario_ids };

            this.disabled = true; // Desabilita botão para evitar cliques duplos

            try {
                const response = await fetch('api_update_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.message);
                }
                updateStatusModal.hide();
                loadLocais(currentObraId, currentParentId); // Recarrega a lista para mostrar a mudança
            } catch (error) {
                alert(`Erro ao atualizar: ${error.message}`);
            } finally {
                this.disabled = false; // Reabilita o botão
            }
        });

        loadLocais(currentObraId, null);

    } else {
        legacyContainer.style.display = 'block';
        hierarquiaContainer.style.display = 'none';
    }
});
</script>
</body>
</html>