<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar se está logado
if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Verificar se é gestor
if (!isGestor()) {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$error = '';

// Processar formulário de criação/edição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $username = trim($_POST['username']);
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $password = $_POST['password'] ?? '';
        
        // Validações básicas
        if (empty($username) || empty($nome) || empty($email) || empty($role)) {
            $error = 'Todos os campos obrigatórios devem ser preenchidos.';
        } else {
            try {
                if ($action === 'create') {
                    // Verificar se username já existe
                                            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    
                    if ($stmt->fetch()) {
                        $error = 'Nome de usuário já existe.';
                    } else {
                        // Criar novo usuário
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, nome, email, role, active) VALUES (?, ?, ?, ?, ?, 1)");
                        $stmt->execute([$username, $hashedPassword, $nome, $email, $role]);
                        
                        $message = 'Usuário criado com sucesso!';
                        logActivity('criou usuário', 'usuarios', $pdo->lastInsertId());
                    }
                } else {
                    // Atualizar usuário existente
                    $userId = $_POST['user_id'];
                    $updateFields = [];
                    $params = [];
                    
                    if (!empty($password)) {
                        $updateFields[] = "password = ?";
                        $params[] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    
                    $updateFields[] = "nome = ?";
                    $updateFields[] = "email = ?";
                    $updateFields[] = "role = ?";
                    $params[] = $nome;
                    $params[] = $email;
                    $params[] = $role;
                    $params[] = $userId;
                    
                    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $message = 'Usuário atualizado com sucesso!';
                    logActivity('atualizou usuário', 'usuarios', $userId);
                }
            } catch (PDOException $e) {
                $error = 'Erro ao processar usuário: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $userId = $_POST['user_id'];
        
        try {
            // Verificar se o usuário tem instalações
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM instalacoes WHERE funcionario_id = ?");
            $stmt->execute([$userId]);
            
            if ($stmt->fetchColumn() > 0) {
                $error = 'Não é possível excluir usuário com instalações registradas.';
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                
                $message = 'Usuário excluído com sucesso!';
                logActivity('excluiu usuário', 'usuarios', $userId);
            }
        } catch (PDOException $e) {
            $error = 'Erro ao excluir usuário: ' . $e->getMessage();
        }
    }
}

// Buscar usuários
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';

$whereClause = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (nome LIKE ? OR username LIKE ? OR email LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

if (!empty($roleFilter)) {
    $whereClause .= " AND role = ?";
    $params[] = $roleFilter;
}

$query = "SELECT * FROM users $whereClause ORDER BY nome ASC";
$usuarios = fetchAll($query, $params);

// Buscar funções disponíveis para filtro
$roles = fetchAll("SELECT DISTINCT role FROM users WHERE role IS NOT NULL ORDER BY role");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários - Sistema de Instalação de Esquadrias</title>
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
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
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
                        
                        <?php if (isGestor()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="obras.php">
                                <i class="fas fa-building me-2"></i>
                                Gerenciar Obras
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="funcionarios.php">
                                <i class="fas fa-users me-2"></i>
                                Funcionários
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="usuarios.php">
                                <i class="fas fa-user-cog me-2"></i>
                                Usuários
                            </a>
                        </li>
                        <?php endif; ?>
                        
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
                <?php require_once 'includes/header.php'; ?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestão de Usuários</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                                <i class="fas fa-plus"></i> Novo Usuário
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Filtros -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" placeholder="Buscar por nome, usuário ou email" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="role">
                                    <option value="">Todas as funções</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo htmlspecialchars($role['role']); ?>" <?php echo $roleFilter === $role['role'] ? 'selected' : ''; ?>>
                                            <?php echo ucfirst(htmlspecialchars($role['role'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                            </div>
                            <div class="col-md-2">
                                <a href="usuarios.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-times"></i> Limpar
                                </a>
                            </div>
                        </form>
                        
                        <!-- Tabela de usuários -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Usuário</th>
                                        <th>Email</th>
                                        <th>Função</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($usuarios)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                <i class="fas fa-info-circle"></i> Nenhum usuário encontrado
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                                <td><code><?php echo htmlspecialchars($usuario['username']); ?></code></td>
                                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $usuario['role'] === 'gestor' ? 'primary' : 'secondary'; ?>">
                                                        <?php echo ucfirst(htmlspecialchars($usuario['role'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $usuario['active'] ? 'success' : 'danger'; ?>">
                                                        <?php echo $usuario['active'] ? 'Ativo' : 'Inativo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($usuario)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nome']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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
    
    <!-- Modal Criar Usuário -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Nome de Usuário *</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Função *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Selecione...</option>
                                <option value="gestor">Gestor</option>
                                <option value="funcionario">Funcionário</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Senha *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Criar Usuário</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar Usuário -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Nome de Usuário</label>
                            <input type="text" class="form-control" id="edit_username" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_nome" class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" id="edit_nome" name="nome" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Função *</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="gestor">Gestor</option>
                                <option value="funcionario">Funcionário</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Nova Senha (deixe em branco para manter)</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Atualizar Usuário</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Confirmar Exclusão -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o usuário <strong id="delete_user_name"></strong>?</p>
                    <p class="text-danger"><small>Esta ação não pode ser desfeita.</small></p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Excluir Usuário</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_nome').value = user.nome;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }
        
        function deleteUser(userId, userName) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_user_name').textContent = userName;
            
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        }
    </script>
</body>
</html>
