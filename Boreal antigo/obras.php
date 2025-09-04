<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        if (!isGestor()) {
            throw new Exception('Acesso negado.');
        }
        $pdo->beginTransaction();

        switch ($_POST['action']) {
            case 'create':
                $tipo_construcao = sanitizeInput($_POST['tipo_construcao']);
                $usa_hierarquia = in_array($tipo_construcao, ['Prédio', 'Sobrado']);

                // 1. Inserir a obra principal
                $stmt = $pdo->prepare(
                    "INSERT INTO obras (nome, cliente, endereco, data_inicio, tipo_construcao, observacoes, created_by, usa_hierarquia_locais) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    sanitizeInput($_POST['nome']),
                    sanitizeInput($_POST['cliente']),
                    sanitizeInput($_POST['endereco']),
                    (isset($_POST['data_inicio']) && $_POST['data_inicio'] !== '') ? sanitizeInput($_POST['data_inicio']) : null,
                    $tipo_construcao,
                    sanitizeInput($_POST['observacoes']),
                    $userId,
                    $usa_hierarquia
                ]);
                $obraId = $pdo->lastInsertId();

                // 2. Inserir a estrutura hierárquica ou legada
                if ($usa_hierarquia) {
                    // Lógica para inserir na tabela 'locais'
                    $stmtLocal = $pdo->prepare("INSERT INTO locais (id_obra, parent_id, nome, tipo) VALUES (?, ?, ?, ?)");
                    
                    $comodos_padrao = array_filter(array_map('trim', explode(',', sanitizeInput($_POST['comodos_padrao']))));

                    if ($tipo_construcao == 'Prédio') {
                        $tem_blocos = isset($_POST['tem_blocos']);
                        $num_blocos = $tem_blocos ? (int)$_POST['num_blocos'] : 1;
                        $num_pavimentos = (int)$_POST['num_pavimentos'];
                        $unidades_por_andar = (int)$_POST['unidades_por_andar'];

                        for ($b = 1; $b <= $num_blocos; $b++) {
                            $blocoParentId = null;
                            if ($tem_blocos) {
                                $stmtLocal->execute([$obraId, null, "Bloco " . chr(64 + $b), 'bloco']);
                                $blocoParentId = $pdo->lastInsertId();
                            }

                            for ($p = 1; $p <= $num_pavimentos; $p++) {
                                $stmtLocal->execute([$obraId, $blocoParentId, "Pavimento {$p}", 'andar']);
                                $pavimentoParentId = $pdo->lastInsertId();

                                for ($u = 1; $u <= $unidades_por_andar; $u++) {
                                    $aptoNome = "Apto " . ($p * 100 + $u);
                                    $stmtLocal->execute([$obraId, $pavimentoParentId, $aptoNome, 'apartamento']);
                                    $aptoParentId = $pdo->lastInsertId();

                                    foreach ($comodos_padrao as $comodoNome) {
                                        $stmtLocal->execute([$obraId, $aptoParentId, $comodoNome, 'comodo']);
                                    }
                                }
                            }
                        }
                    } elseif ($tipo_construcao == 'Sobrado') {
                        // Lógica para Sobrado
                        $num_pavimentos = (int)$_POST['num_pavimentos_sobrado'];
                         for ($p = 1; $p <= $num_pavimentos; $p++) {
                            $stmtLocal->execute([$obraId, null, "Pavimento {$p}", 'andar']);
                            $pavimentoParentId = $pdo->lastInsertId();
                            foreach ($comodos_padrao as $comodoNome) {
                                $stmtLocal->execute([$obraId, $pavimentoParentId, $comodoNome, 'comodo']);
                            }
                        }
                    }

                } else {
                    // Lógica legada para inserir na tabela 'comodos'
                    if (isset($_POST['comodos']) && is_array($_POST['comodos'])) {
                        $stmtComodo = $pdo->prepare("INSERT INTO comodos (obra_id, nome, tipo_esquadria) VALUES (?, ?, ?)");
                        foreach ($_POST['comodos'] as $comodo) {
                            if (!empty($comodo['nome'])) {
                                $stmtComodo->execute([$obraId, sanitizeInput($comodo['nome']), 'A definir']);
                            }
                        }
                    }
                }

                $message = 'Obra criada com sucesso!';
                $messageType = 'success';
                break;

            case 'update':
                $obraId = sanitizeInput($_POST['obra_id']);
                $nome = sanitizeInput($_POST['nome']);
                $cliente = sanitizeInput($_POST['cliente']);
                $endereco = sanitizeInput($_POST['endereco']);
                $status = sanitizeInput($_POST['status']);
                $observacoes = sanitizeInput($_POST['observacoes']);

                $stmt = $pdo->prepare("UPDATE obras SET nome = ?, cliente = ?, endereco = ?, status = ?, observacoes = ? WHERE id = ?");
                $stmt->execute([$nome, $cliente, $endereco, $status, $observacoes, $obraId]);

                $stmtDeleteComodos = $pdo->prepare("DELETE FROM comodos WHERE obra_id = ?");
                $stmtDeleteComodos->execute([$obraId]);

                if (isset($_POST['edit_comodos']) && is_array($_POST['edit_comodos'])) {
                    $stmtComodo = $pdo->prepare("INSERT INTO comodos (obra_id, nome, tipo_esquadria) VALUES (?, ?, ?)");
                    foreach ($_POST['edit_comodos'] as $comodo) {
                        if (!empty($comodo['nome'])) {
                            $comodoNome = sanitizeInput($comodo['nome']);
                            $comodoTipoEsquadria = sanitizeInput($comodo['tipo_esquadria']);
                            $stmtComodo->execute([$obraId, $comodoNome, $comodoTipoEsquadria]);
                        }
                    }
                }
                $message = 'Obra atualizada com sucesso!';
                $messageType = 'success';
                break;

            case 'delete':
                $obraId = sanitizeInput($_POST['obra_id']);
                $stmt = $pdo->prepare("DELETE FROM obras WHERE id = ?");
                $stmt->execute([$obraId]);
                $message = 'Obra excluída com sucesso!';
                $messageType = 'success';
                break;
        }
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = 'Erro: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

$obrasData = fetchAll("SELECT *, usa_hierarquia_locais FROM obras ORDER BY created_at DESC");

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Obras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>.sidebar{min-height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)}.sidebar .nav-link{color:rgba(255,255,255,.8);border-radius:10px;margin:2px 0;transition:all .3s}.sidebar .nav-link:hover,.sidebar .nav-link.active{color:#fff;background:rgba(255,255,255,.1);transform:translateX(5px)}.wizard-step{display:none}.wizard-step.active{display:block}</style>
</head>
<body>
<div class="container-fluid"><div class="row">
    <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
        <div class="position-sticky pt-3">
            <div class="text-center mb-4"><i class="fas fa-building text-white" style="font-size: 2rem;"></i><h5 class="text-white mt-2">Instalação de Esquadrias</h5><small class="text-white-50">Controle de Obras</small></div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                <?php if (isGestor()): ?>
                <li class="nav-item"><a class="nav-link active" href="obras.php"><i class="fas fa-building me-2"></i>Gerenciar Obras</a></li>
                <li class="nav-item"><a class="nav-link" href="funcionarios.php"><i class="fas fa-users me-2"></i>Funcionários</a></li>
                <li class="nav-item"><a class="nav-link" href="usuarios.php"><i class="fas fa-user-cog me-2"></i>Usuários</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="instalacoes.php"><i class="fas fa-tools me-2"></i>Instalações</a></li>
                <li class="nav-item"><a class="nav-link" href="relatorios.php"><i class="fas fa-chart-bar me-2"></i>Relatórios</a></li>
                <li class="nav-item"><a class="nav-link" href="fotos.php"><i class="fas fa-camera me-2"></i>Fotos</a></li>
                <li class="nav-item mt-4"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
            </ul>
        </div>
    </nav>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <?php require_once 'includes/header.php'; ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Gerenciar Obras</h1>
            <?php if (isGestor()): ?><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novaObraModal"><i class="fas fa-plus"></i> Nova Obra</button><?php endif; ?>
        </div>
        <?php if (!empty($message)): ?><div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <div class="table-responsive"><table class="table table-striped">
            <thead><tr><th>Nome</th><th>Cliente</th><th>Endereço</th><th>Status</th><th>Ações</th></tr></thead>
            <tbody>
                <?php foreach ($obrasData as $obra): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($obra['nome']); ?></td>
                        <td><?php echo htmlspecialchars($obra['cliente']); ?></td>
                        <td><?php echo htmlspecialchars($obra['endereco']); ?></td>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($obra['status']); ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick='editObra(<?php echo json_encode($obra); ?>)'><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger" onclick='deleteObra(<?php echo $obra['id']; ?>, "<?php echo htmlspecialchars(addslashes($obra['nome'])); ?>")'><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    </main>
</div></div>

<!-- Create Modal -->
<div class="modal fade" id="novaObraModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Novo Assistente de Obra</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="wizard-step active" id="step-1">
                        <h5>Passo 1: Informações Básicas</h5>
                        <div class="mb-3"><label>Nome da Obra</label><input type="text" class="form-control" name="nome" required></div>
                        <div class="mb-3"><label>Cliente</label><input type="text" class="form-control" name="cliente"></div>
                        <div class="mb-3"><label>Endereço</label><textarea class="form-control" name="endereco" required></textarea></div>
                        <div class="mb-3"><label>Data de Início</label><input type="date" class="form-control" name="data_inicio"></div>
                    </div>
                    <div class="wizard-step" id="step-2">
                        <h5>Passo 2: Estrutura</h5>
                        <div class="mb-3"><label>Tipo de Construção</label><select class="form-select" name="tipo_construcao" id="tipo_construcao"><option value="">Selecione...</option><option value="Prédio">Prédio</option><option value="Casa">Casa</option><option value="Sobrado">Sobrado</option><option value="Outro">Outro</option></select></div>
                        <div id="predio-fields" style="display: none;">
                            <div class="form-check"><input class="form-check-input" type="checkbox" id="tem_blocos" name="tem_blocos"><label class="form-check-label">Possui mais de um bloco?</label></div>
                            <div id="blocos-container" style="display: none;" class="mb-3"><label>Nº de Blocos</label><input type="number" class="form-control" id="num_blocos" name="num_blocos" value="1"></div>
                            <div class="mb-3"><label>Nº de Pavimentos</label><input type="number" class="form-control" id="num_pavimentos" name="num_pavimentos" value="1"></div>
                            <div class="mb-3"><label>Unidades por Pavimento</label><input type="number" class="form-control" id="unidades_por_andar" name="unidades_por_andar" value="1"></div>
                        </div>
                        <div id="sobrado-fields" style="display: none;"><div class="mb-3"><label>Nº de Pavimentos</label><input type="number" class="form-control" id="num_pavimentos_sobrado" name="num_pavimentos_sobrado" value="1"></div></div>
                    </div>
                    <div class="wizard-step" id="step-3">
                        <h5>Passo 3: Cômodos</h5>
                        <div class="mb-3"><label>Cômodos Padrão por Unidade</label><input type="text" class="form-control" id="comodos_padrao" name="comodos_padrao" placeholder="Ex: Sala, Cozinha, Quarto 1"></div>
                    </div>
                    <div class="wizard-step" id="step-4">
                        <h5>Passo 4: Revisão</h5>
                        <div id="comodos-preview-container" style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; border-radius: 5px;"></div>
                        <div class="mt-3"><label>Observações</label><textarea class="form-control" name="observacoes"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="prev-btn" style="display: none;">Anterior</button>
                    <button type="button" class="btn btn-primary" id="next-btn">Próximo</button>
                    <button type="submit" class="btn btn-success" id="submit-btn" style="display: none;">Criar Obra</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editarObraModal" tabindex="-1">
    <div class="modal-dialog modal-xl"> <!-- Aumentado para XL -->
        <div class="modal-content">
            <form method="POST" id="editObraForm">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Obra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="obra_id" id="edit_obra_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Dados da Obra</h6>
                            <div class="mb-3"><label>Nome</label><input type="text" class="form-control" name="nome" id="edit_nome" required></div>
                            <div class="mb-3"><label>Cliente</label><input type="text" class="form-control" name="cliente" id="edit_cliente"></div>
                            <div class="mb-3"><label>Endereço</label><textarea class="form-control" name="endereco" id="edit_endereco"></textarea></div>
                            <div class="mb-3"><label>Status</label><select class="form-select" name="status" id="edit_status"><option value="planejada">Planejada</option><option value="em_andamento">Em Andamento</option><option value="em_finalizacao">Em Finalização</option><option value="concluida">Concluída</option><option value="pausada">Pausada</option></select></div>
                            <div class="mb-3"><label>Observações</label><textarea class="form-control" name="observacoes" id="edit_observacoes"></textarea></div>
                        </div>
                        <div class="col-md-6">
                            <h6>Estrutura Hierárquica</h6>
                            <div id="hierarquia-view">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb" id="breadcrumb-container"></ol>
                                </nav>
                                <div id="locais-list" class="list-group" style="max-height: 300px; overflow-y: auto;">
                                    <!-- Locais serão carregados aqui -->
                                </div>
                                <div id="locais-loading" class="text-center" style="display: none;">
                                    <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
                                </div>
                                <div class="mt-3">
                                    <div class="input-group">
                                        <input type="text" id="novo-local-nome" class="form-control" placeholder="Nome do novo local">
                                        <select id="novo-local-tipo" class="form-select" style="max-width: 120px;">
                                            <option value="bloco">Bloco</option>
                                            <option value="andar">Andar</option>
                                            <option value="apartamento">Apartamento</option>
                                            <option value="comodo" selected>Cômodo</option>
                                        </select>
                                        <button class="btn btn-success" type="button" id="add-local-btn">Adicionar</button>
                                    </div>
                                </div>
                            </div>
                            <div id="legacy-comodos-view" class="mt-3">
                                <!-- Visão antiga de cômodos (se necessário) -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteObraModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form method="POST">
            <div class="modal-header"><h5 class="modal-title">Confirmar Exclusão</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a obra <strong id="deleteObraNome"></strong>?</p>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="obra_id" id="deleteObraId">
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-danger">Excluir</button></div>
        </form>
    </div></div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Lógica do Assistente de Nova Obra ---
            const novaObraModal = document.getElementById('novaObraModal');
            if (novaObraModal) {
                const steps = Array.from(novaObraModal.querySelectorAll('.wizard-step'));
                const prevBtn = novaObraModal.querySelector('#prev-btn');
                const nextBtn = novaObraModal.querySelector('#next-btn');
                const submitBtn = novaObraModal.querySelector('#submit-btn');
                let currentStep = 0;

                const tipoConstrucaoSelect = novaObraModal.querySelector('#tipo_construcao');
                const predioFields = novaObraModal.querySelector('#predio-fields');
                const sobradoFields = novaObraModal.querySelector('#sobrado-fields');
                const temBlocosCheckbox = novaObraModal.querySelector('#tem_blocos');
                const blocosContainer = novaObraModal.querySelector('#blocos-container');
                const comodosPadraoInput = novaObraModal.querySelector('#comodos_padrao');
                const comodosPreviewContainer = novaObraModal.querySelector('#comodos-preview-container');

                const showStep = (stepIndex) => {
                    steps.forEach((step, index) => {
                        step.style.display = index === stepIndex ? 'block' : 'none';
                    });
                    prevBtn.style.display = stepIndex > 0 ? 'inline-block' : 'none';
                    nextBtn.style.display = stepIndex < steps.length - 1 ? 'inline-block' : 'none';
                    submitBtn.style.display = stepIndex === steps.length - 1 ? 'inline-block' : 'none';
                };

                const validateStep = (stepIndex) => {
                    if (stepIndex === 0) { // Passo 1: Informações Básicas
                        const nome = novaObraModal.querySelector('[name="nome"]').value;
                        const endereco = novaObraModal.querySelector('[name="endereco"]').value;
                        if (!nome || !endereco) {
                            alert('Por favor, preencha o nome e o endereço da obra.');
                            return false;
                        }
                    } else if (stepIndex === 1) { // Passo 2: Estrutura
                        const tipo = tipoConstrucaoSelect.value;
                        if (!tipo) {
                            alert('Por favor, selecione o tipo de construção.');
                            return false;
                        }
                        if (tipo === 'Prédio') {
                            const numBlocos = parseInt(novaObraModal.querySelector('#num_blocos').value);
                            const numPavimentos = parseInt(novaObraModal.querySelector('#num_pavimentos').value);
                            const unidadesPorAndar = parseInt(novaObraModal.querySelector('#unidades_por_andar').value);
                            if (isNaN(numBlocos) || numBlocos < 1 || isNaN(numPavimentos) || numPavimentos < 1 || isNaN(unidadesPorAndar) || unidadesPorAndar < 1) {
                                alert('Por favor, insira valores válidos para blocos, pavimentos e unidades.');
                                return false;
                            }
                        } else if (tipo === 'Sobrado') {
                            const numPavimentos = parseInt(novaObraModal.querySelector('#num_pavimentos_sobrado').value);
                            if (isNaN(numPavimentos) || numPavimentos < 1) {
                                alert('Por favor, insira um número válido de pavimentos.');
                                return false;
                            }
                        }
                    } else if (stepIndex === 2) { // Passo 3: Cômodos
                        const comodosPadrao = comodosPadraoInput.value.split(',').map(s => s.trim()).filter(Boolean);
                        if (comodosPadrao.length === 0) {
                            alert('Por favor, defina os cômodos padrão.');
                            return false;
                        }
                    }
                    return true;
                };

                nextBtn.addEventListener('click', () => {
                    if (validateStep(currentStep)) {
                        if (currentStep === 2) { generateComodosPreview(); }
                        currentStep++;
                        showStep(currentStep);
                    }
                });

                prevBtn.addEventListener('click', () => {
                    if (currentStep > 0) {
                        currentStep--;
                        showStep(currentStep);
                    }
                });

                tipoConstrucaoSelect.addEventListener('change', function() {
                    predioFields.style.display = this.value === 'Prédio' ? 'block' : 'none';
                    sobradoFields.style.display = this.value === 'Sobrado' ? 'block' : 'none';
                });

                temBlocosCheckbox.addEventListener('change', function() {
                    blocosContainer.style.display = this.checked ? 'block' : 'none';
                });

                const generateComodosPreview = () => {
                    comodosPreviewContainer.innerHTML = '';
                    const tipo = tipoConstrucaoSelect.value;
                    const comodosPadrao = comodosPadraoInput.value.split(',').map(s => s.trim()).filter(Boolean);
                    let comodosList = [];

                    if (tipo === 'Prédio') {
                        const temBlocos = temBlocosCheckbox.checked;
                        const numBlocos = temBlocos ? parseInt(novaObraModal.querySelector('#num_blocos').value) : 1;
                        const numPavimentos = parseInt(novaObraModal.querySelector('#num_pavimentos').value);
                        const unidadesPorAndar = parseInt(novaObraModal.querySelector('#unidades_por_andar').value);
                        for (let b = 1; b <= numBlocos; b++) {
                            for (let p = 1; p <= numPavimentos; p++) {
                                for (let u = 1; u <= unidadesPorAndar; u++) {
                                    const apto = `Apto ${p}${String(u).padStart(2, '0')}`;
                                    comodosPadrao.forEach(c => comodosList.push(`${temBlocos ? `Bloco ${b} - ` : ''}Pavimento ${p} - ${apto} - ${c}`));
                                }
                            }
                        }
                    } else if (tipo === 'Sobrado') {
                        const numPavimentos = parseInt(novaObraModal.querySelector('#num_pavimentos_sobrado').value);
                        for (let p = 1; p <= numPavimentos; p++) {
                            comodosPadrao.forEach(c => comodosList.push(`Pavimento ${p} - ${c}`));
                        }
                    } else { // Casa ou Outro
                        comodosList = comodosPadrao;
                    }

                    if (comodosList.length === 0) {
                        comodosPreviewContainer.innerHTML = '<p class="text-muted">Nenhum cômodo gerado.</p>';
                        return;
                    }

                    comodosList.forEach((nome, index) => {
                        const div = document.createElement('div');
                        div.className = 'input-group input-group-sm mb-1';
                        div.innerHTML = `
                            <input type="text" class="form-control" name="comodos[${index}][nome]" value="${nome}" required>
                            <input type="hidden" name="comodos[${index}][tipo_esquadria]" value="A definir">
                            <button class="btn btn-outline-danger remove-comodo-preview" type="button"><i class="fas fa-times"></i></button>
                        `;
                        comodosPreviewContainer.appendChild(div);
                        div.querySelector('.remove-comodo-preview').addEventListener('click', () => div.remove());
                    });
                };

                novaObraModal.addEventListener('hidden.bs.modal', () => {
                    currentStep = 0;
                    showStep(currentStep);
                    novaObraModal.querySelector('form').reset();
                    predioFields.style.display = 'none';
                    sobradoFields.style.display = 'none';
                    blocosContainer.style.display = 'none';
                    comodosPreviewContainer.innerHTML = '';
                });

                showStep(0);
            }
        });

        // --- NOVA LÓGICA PARA EDIÇÃO HIERÁRQUICA ---
        
        const editModal = new bootstrap.Modal(document.getElementById('editarObraModal'));
        const breadcrumbContainer = document.getElementById('breadcrumb-container');
        const locaisList = document.getElementById('locais-list');
        const locaisLoading = document.getElementById('locais-loading');
        const addLocalBtn = document.getElementById('add-local-btn');
        
        let currentObraId = null;
        let currentParentId = null;
        let breadcrumb = [];

        // Função para fazer chamadas à nova API
        const manageLocal = async (action, params) => {
            try {
                const response = await fetch('manage_locais.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action, ...params })
                });
                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.message);
                }
                return result;
            } catch (error) {
                alert(`Erro: ${error.message}`);
                return null;
            }
        };

        // Função para renderizar o breadcrumb
        const renderBreadcrumb = () => {
            breadcrumbContainer.innerHTML = '';
            const rootItem = document.createElement('li');
            rootItem.className = 'breadcrumb-item';
            rootItem.innerHTML = `<a href="#" data-id="null">Raiz</a>`;
            rootItem.querySelector('a').addEventListener('click', (e) => {
                e.preventDefault();
                breadcrumb = [];
                loadLocais(currentObraId, null);
            });
            breadcrumbContainer.appendChild(rootItem);

            breadcrumb.forEach((item, index) => {
                const li = document.createElement('li');
                li.className = 'breadcrumb-item';
                if (index === breadcrumb.length - 1) {
                    li.classList.add('active');
                    li.textContent = item.nome;
                } else {
                    li.innerHTML = `<a href="#" data-id="${item.id}">${item.nome}</a>`;
                    li.querySelector('a').addEventListener('click', (e) => {
                        e.preventDefault();
                        breadcrumb = breadcrumb.slice(0, index + 1);
                        loadLocais(currentObraId, item.id);
                    });
                }
                breadcrumbContainer.appendChild(li);
            });
        };

        // Função para carregar os locais via AJAX
        const loadLocais = async (obraId, parentId) => {
            currentParentId = parentId;
            locaisLoading.style.display = 'block';
            locaisList.innerHTML = '';
            
            const url = parentId ?
                `get_locais.php?id_obra=${obraId}&parent_id=${parentId}` :
                `get_locais.php?id_obra=${obraId}`;

            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error('Falha na rede');
                const data = await response.json();

                locaisList.innerHTML = '';
                if (data.length === 0) {
                    locaisList.innerHTML = '<div class="list-group-item text-muted">Nenhum local encontrado.</div>';
                } else {
                    data.forEach(local => {
                        const item = document.createElement('div');
                        item.className = 'list-group-item d-flex justify-content-between align-items-center';
                        
                        const nameSpan = document.createElement('span');
                        nameSpan.className = 'local-name';
                        nameSpan.textContent = `${local.nome} (${local.tipo})`;
                        nameSpan.style.cursor = 'pointer';
                        nameSpan.addEventListener('click', () => {
                            breadcrumb.push({ id: local.id, nome: local.nome });
                            loadLocais(obraId, local.id);
                        });

                        const buttonsDiv = document.createElement('div');

                        const editBtn = document.createElement('button');
                        editBtn.className = 'btn btn-sm btn-outline-primary me-2';
                        editBtn.innerHTML = '<i class="fas fa-edit"></i>';
                        editBtn.onclick = () => {
                            const newName = prompt('Digite o novo nome para:', local.nome);
                            if (newName && newName.trim() !== '') {
                                manageLocal('update', { id: local.id, nome: newName.trim() }).then(result => {
                                    if(result) loadLocais(obraId, parentId); // Recarrega a lista
                                });
                            }
                        };

                        const deleteBtn = document.createElement('button');
                        deleteBtn.className = 'btn btn-sm btn-outline-danger';
                        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                        deleteBtn.onclick = () => {
                            if (confirm(`Tem certeza que deseja excluir "${local.nome}"?\nTODOS os sub-itens serão excluídos permanentemente.`)) {
                                manageLocal('delete', { id: local.id }).then(result => {
                                    if(result) loadLocais(obraId, parentId); // Recarrega a lista
                                });
                            }
                        };

                        buttonsDiv.appendChild(editBtn);
                        buttonsDiv.appendChild(deleteBtn);
                        
                        item.appendChild(nameSpan);
                        item.appendChild(buttonsDiv);
                        locaisList.appendChild(item);
                    });
                }
            } catch (error) {
                locaisList.innerHTML = `<div class="list-group-item text-danger">Erro ao carregar locais: ${error.message}</div>`;
            } finally {
                locaisLoading.style.display = 'none';
                renderBreadcrumb();
            }
        };

        // Lógica do botão Adicionar
        addLocalBtn.addEventListener('click', () => {
            const nomeInput = document.getElementById('novo-local-nome');
            const tipoSelect = document.getElementById('novo-local-tipo');
            const nome = nomeInput.value.trim();
            const tipo = tipoSelect.value;

            if (!nome) {
                alert('Por favor, insira um nome para o novo local.');
                return;
            }

            manageLocal('create', { id_obra: currentObraId, parent_id: currentParentId, nome, tipo }).then(result => {
                if (result) {
                    nomeInput.value = ''; // Limpa o campo
                    loadLocais(currentObraId, currentParentId); // Recarrega a lista
                }
            });
        });

        // Função para abrir o modal de edição
        window.editObra = (obra) => {
            currentObraId = obra.id;
            document.getElementById('edit_obra_id').value = obra.id;
            document.getElementById('edit_nome').value = obra.nome;
            document.getElementById('edit_cliente').value = obra.cliente;
            document.getElementById('edit_endereco').value = obra.endereco;
            document.getElementById('edit_status').value = obra.status;
            document.getElementById('edit_observacoes').value = obra.observacoes || '';

            const hierarquiaView = document.getElementById('hierarquia-view');
            const legacyView = document.getElementById('legacy-comodos-view');

            if (obra.usa_hierarquia_locais && obra.usa_hierarquia_locais !== '0') {
                hierarquiaView.style.display = 'block';
                legacyView.style.display = 'none';
                legacyView.innerHTML = '';
                breadcrumb = [];
                loadLocais(currentObraId, null);
            } else {
                hierarquiaView.style.display = 'none';
                legacyView.style.display = 'block';
                legacyView.innerHTML = '<h6>Cômodos (Legado)</h6><p class="text-muted">Este sistema de edição não está disponível para obras que não usam a estrutura hierárquica.</p>';
            }
            
            editModal.show();
        };

    </script>
</body>
</html>
