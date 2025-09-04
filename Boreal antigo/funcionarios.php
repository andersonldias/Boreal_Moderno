<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar se está logado e é gestor
if (!isLoggedIn() || !isGestor()) {
    header('Location: dashboard.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Processar ações
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $nome = sanitizeInput($_POST['nome']);
                $funcao = sanitizeInput($_POST['funcao']);
                $telefone = sanitizeInput($_POST['telefone']);
                $email = sanitizeInput($_POST['email']);
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO funcionarios (nome, funcao, telefone, email) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$nome, $funcao, $telefone, $email]);
                    
                    logActivity($userId, 'Criou funcionário', "Funcionário: $nome");
                    $message = 'Funcionário criado com sucesso!';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Erro ao criar funcionário: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'update':
                $funcionarioId = $_POST['funcionario_id'];
                $nome = sanitizeInput($_POST['nome']);
                $funcao = sanitizeInput($_POST['funcao']);
                $telefone = sanitizeInput($_POST['telefone']);
                $email = sanitizeInput($_POST['email']);
                $active = isset($_POST['active']) ? 1 : 0;
                
                try {
                    $stmt = $pdo->prepare("UPDATE funcionarios SET nome = ?, funcao = ?, telefone = ?, email = ?, active = ? WHERE id = ?");
                    $stmt->execute([$nome, $funcao, $telefone, $email, $active, $funcionarioId]);
                    
                    logActivity($userId, 'Atualizou funcionário', "Funcionário ID: $funcionarioId");
                    $message = 'Funcionário atualizado com sucesso!';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Erro ao atualizar funcionário: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'delete':
                $funcionarioId = $_POST['funcionario_id'];
                try {
                    // Verificar se o funcionário está sendo usado em instalações
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM instalacoes WHERE funcionario_id = ?");
                    $stmt->execute([$funcionarioId]);
                    $usoCount = $stmt->fetchColumn();
                    
                    if ($usoCount > 0) {
                        $message = 'Não é possível excluir este funcionário pois está sendo usado em instalações.';
                        $messageType = 'warning';
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM funcionarios WHERE id = ?");
                        $stmt->execute([$funcionarioId]);
                        
                        logActivity($userId, 'Excluiu funcionário', "Funcionário ID: $funcionarioId");
                        $message = 'Funcionário excluído com sucesso!';
                        $messageType = 'success';
                    }
                } catch (Exception $e) {
                    $message = 'Erro ao excluir funcionário: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Filtros
$searchTerm = $_GET['search'] ?? '';
$funcaoFilter = $_GET['funcao'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Construir query
$whereConditions = [];
$params = [];

if ($searchTerm) {
    $whereConditions[] = "(nome LIKE ? OR email LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($funcaoFilter) {
    $whereConditions[] = "funcao = ?";
    $params[] = $funcaoFilter;
}

if ($statusFilter !== '') {
    $whereConditions[] = "active = ?";
    $params[] = $statusFilter;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$query = "SELECT * FROM funcionarios $whereClause ORDER BY nome ASC";
$funcionarios = fetchAll($query, $params);

// Obter funções únicas para o filtro
$funcoes = fetchAll("SELECT DISTINCT funcao FROM funcionarios WHERE funcao IS NOT NULL ORDER BY funcao");

// Estatísticas
$totalFuncionarios = count($funcionarios);
$funcionariosAtivos = count(array_filter($funcionarios, function($f) { return $f['active'] == 1; }));
$funcionariosInativos = count(array_filter($funcionarios, function($f) { return $f['active'] == 0; }));

// Agrupar por função
$funcionariosPorFuncao = [];
foreach ($funcionarios as $funcionario) {
    if ($funcionario['active'] == 1) {
        $funcao = $funcionario['funcao'];
        if (!isset($funcionariosPorFuncao[$funcao])) {
            $funcionariosPorFuncao[$funcao] = 0;
        }
        $funcionariosPorFuncao[$funcao]++;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Funcionários - Instalação de Esquadrias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 10px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        .card-stats {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .card-stats:hover {
            transform: translateY(-5px);
        }
        .table-responsive {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <i class="fas fa-building text-white" style="font-size: 2rem;"></i>
                        <h5 class="text-white mt-2">Instalação de Esquadrias</h5>
                        <small class="text-white-50">Controle de Obras</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="obras.php">
                                <i class="fas fa-building me-2"></i>
                                Gerenciar Obras
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="funcionarios.php">
                                <i class="fas fa-users me-2"></i>
                                Funcionários
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="usuarios.php">
                                <i class="fas fa-user-cog me-2"></i>
                                Usuários
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="instalacoes.php">
                                <i class="fas fa-tools me-2"></i>
                                Instalações
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="relatorios.php">
                                <i class="fas fa-chart-bar me-2"></i>
                                Relatórios
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="fotos.php">
                                <i class="fas fa-camera me-2"></i>
                                Fotos
                            </a>
                        </li>
                        
                        <li class="nav-item mt-4">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Sair
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Conteúdo principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gerenciar Funcionários</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novoFuncionarioModal">
                            <i class="fas fa-plus"></i> Novo Funcionário
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Estatísticas -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total de Funcionários</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalFuncionarios; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Ativos</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $funcionariosAtivos; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Inativos</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $funcionariosInativos; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-pause-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Funções</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($funcionariosPorFuncao); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-briefcase fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>" 
                                       placeholder="Nome ou email">
                            </div>
                            <div class="col-md-3">
                                <label for="funcao" class="form-label">Função</label>
                                <select class="form-select" id="funcao" name="funcao">
                                    <option value="">Todas</option>
                                    <?php foreach ($funcoes as $funcao): ?>
                                        <option value="<?php echo htmlspecialchars($funcao['funcao']); ?>" 
                                                <?php echo $funcaoFilter == $funcao['funcao'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($funcao['funcao']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Todos</option>
                                    <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Ativos</option>
                                    <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inativos</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                    <a href="funcionarios.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Limpar
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Funcionários -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Lista de Funcionários</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                                    <?php if (empty($funcionarios)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                Nenhum funcionário encontrado
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($funcionarios as $funcionario): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($funcionario['nome']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($funcionario['funcao']); ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($funcionario['telefone']): ?>
                                                        <div><i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($funcionario['telefone']); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($funcionario['email']): ?>
                                                        <div><i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($funcionario['email']); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($funcionario['active']): ?>
                                                        <span class="badge bg-success status-badge">Ativo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary status-badge">Inativo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo formatDate($funcionario['created_at']); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-sm btn-outline-warning" 
                                                                onclick="editFuncionario(<?php echo htmlspecialchars(json_encode($funcionario)); ?>)" 
                                                                title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" 
                                                                onclick="deleteFuncionario(<?php echo $funcionario['id']; ?>, '<?php echo htmlspecialchars($funcionario['nome']); ?>')" 
                                                                title="Excluir">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Novo Funcionário -->
    <div class="modal fade" id="novoFuncionarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Funcionário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="funcao" class="form-label">Função *</label>
                            <input type="text" class="form-control" id="funcao" name="funcao" required 
                                   placeholder="Ex: Instalador, Auxiliar, Supervisor">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="telefone" class="form-label">Telefone</label>
                                    <input type="tel" class="form-control" id="telefone" name="telefone" 
                                           placeholder="(11) 99999-9999">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="funcionario@empresa.com">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Criar Funcionário</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Funcionário -->
    <div class="modal fade" id="editarFuncionarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Funcionário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="funcionario_id" id="edit_funcionario_id">
                        
                        <div class="mb-3">
                            <label for="edit_nome" class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" id="edit_nome" name="nome" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_funcao" class="form-label">Função *</label>
                            <input type="text" class="form-control" id="edit_funcao" name="funcao" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_telefone" class="form-label">Telefone</label>
                                    <input type="tel" class="form-control" id="edit_telefone" name="telefone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="edit_email" name="email">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_active" name="active">
                                <label class="form-check-label" for="edit_active">
                                    Funcionário ativo
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Atualizar Funcionário</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="deleteFuncionarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o funcionário <strong id="deleteFuncionarioNome"></strong>?</p>
                    <p class="text-danger"><small>Esta ação não pode ser desfeita.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="funcionario_id" id="deleteFuncionarioId">
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editFuncionario(funcionario) {
            document.getElementById('edit_funcionario_id').value = funcionario.id;
            document.getElementById('edit_nome').value = funcionario.nome;
            document.getElementById('edit_funcao').value = funcionario.funcao;
            document.getElementById('edit_telefone').value = funcionario.telefone || '';
            document.getElementById('edit_email').value = funcionario.email || '';
            document.getElementById('edit_active').checked = funcionario.active == 1;
            
            new bootstrap.Modal(document.getElementById('editarFuncionarioModal')).show();
        }
        
        function deleteFuncionario(funcionarioId, funcionarioNome) {
            document.getElementById('deleteFuncionarioId').value = funcionarioId;
            document.getElementById('deleteFuncionarioNome').textContent = funcionarioNome;
            new bootstrap.Modal(document.getElementById('deleteFuncionarioModal')).show();
        }
    </script>
</body>
</html>
