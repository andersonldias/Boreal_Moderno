<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar se está logado
if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Obter estatísticas gerais
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Estatísticas baseadas no papel do usuário
if (isGestor()) {
    // Gestor vê todas as obras
    $totalObras = fetchOne("SELECT COUNT(*) as total FROM obras")['total'];
    $obrasEmAndamento = fetchOne("SELECT COUNT(*) as total FROM obras WHERE status IN ('em_andamento', 'em_finalizacao')")['total'];
    $obrasConcluidas = fetchOne("SELECT COUNT(*) as total FROM obras WHERE status = 'concluida'")['total'];
    $obrasComProblemas = 0; // Temporariamente desativado devido a schema inconsistente
    
    $obras = fetchAll("SELECT o.*, 
                              (SELECT COUNT(*) FROM comodos WHERE obra_id = o.id) as total_comodos
                       FROM obras o 
                       ORDER BY o.created_at DESC 
                       LIMIT 10");
} else {
    // Funcionário vê apenas obras atribuídas
    $totalObras = fetchOne("SELECT COUNT(DISTINCT o.id) as total FROM obras o JOIN user_obra_permissions p ON o.id = p.obra_id WHERE p.user_id = ? AND p.can_view = 1", [$userId])['total'];
    $obrasEmAndamento = fetchOne("SELECT COUNT(DISTINCT o.id) as total FROM obras o JOIN user_obra_permissions p ON o.id = p.obra_id WHERE p.user_id = ? AND p.can_view = 1 AND o.status IN ('em_andamento', 'em_finalizacao')", [$userId])['total'];
    $obrasConcluidas = fetchOne("SELECT COUNT(DISTINCT o.id) as total FROM obras o JOIN user_obra_permissions p ON o.id = p.obra_id WHERE p.user_id = ? AND p.can_view = 1 AND o.status = 'concluida'", [$userId])['total'];
    $obrasComProblemas = 0; // Temporariamente desativado devido a schema inconsistente
    
    $obras = fetchAll("SELECT o.*, 
                              (SELECT COUNT(*) FROM comodos WHERE obra_id = o.id) as total_comodos
                       FROM obras o 
                       JOIN user_obra_permissions p ON o.id = p.obra_id 
                       WHERE p.user_id = ? AND p.can_view = 1
                       ORDER BY o.created_at DESC 
                       LIMIT 10", [$userId]);
}

// Obter notificações não lidas
$notificacoes = getUnreadNotifications($userId);
$totalNotificacoes = count($notificacoes);

// Obter atividades recentes
$atividades = fetchAll("SELECT al.*, u.nome as user_nome 
                       FROM activity_logs al 
                       LEFT JOIN users u ON al.user_id = u.id 
                       ORDER BY al.created_at DESC 
                       LIMIT 5");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Instalação de Esquadrias</title>
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
        .progress {
            height: 8px;
            border-radius: 10px;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.7rem;
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
                            <a class="nav-link active" href="dashboard.php">
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
                            <a class="nav-link" href="usuarios.php">
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
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="instalacoes.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus"></i> Nova Instalação
                            </a>
                        </div>
                        <div class="dropdown position-relative">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <?php if ($totalNotificacoes > 0): ?>
                                    <span class="badge bg-danger notification-badge"><?php echo $totalNotificacoes; ?></span>
                                <?php endif; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if (empty($notificacoes)): ?>
                                    <li><span class="dropdown-item-text">Nenhuma notificação</span></li>
                                <?php else: ?>
                                    <?php foreach ($notificacoes as $notif): ?>
                                        <li><a class="dropdown-item" href="#" onclick="markNotificationAsRead(<?php echo $notif['id']; ?>)">
                                            <strong><?php echo htmlspecialchars($notif['title']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($notif['message']); ?></small>
                                        </a></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Estatísticas -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total de Obras
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalObras; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-building fa-2x text-gray-300"></i>
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
                                            Em Andamento
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $obrasEmAndamento; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-play-circle fa-2x text-gray-300"></i>
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
                                            Concluídas
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $obrasConcluidas; ?></div>
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
                                            Com Problemas
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $obrasComProblemas; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Obras Recentes -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Obras Recentes</h6>
                                <a href="obras.php" class="btn btn-sm btn-primary">Ver Todas</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($obras)): ?>
                                    <p class="text-muted text-center">Nenhuma obra encontrada.</p>
                                <?php else: ?>
                                    <?php foreach ($obras as $obra): ?>
                                        <?php 
                                        $percentual = $obra['total_comodos'] > 0 ? 
                                            calculatePercentage($obra['comodos_instalados'], $obra['total_comodos']) : 0;
                                        $statusColor = getStatusColor(getObraStatus($percentual));
                                        ?>
                                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 border rounded">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($obra['nome']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($obra['cliente']); ?> • 
                                                    <?php echo formatDate($obra['data_inicio']); ?>
                                                </small>
                                                <div class="mt-2">
                                                    <span class="badge bg-<?php echo $statusColor; ?>">
                                                        <?php echo getObraStatus($percentual); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="h6 mb-1"><?php echo $percentual; ?>%</div>
                                                <small class="text-muted">
                                                    <?php echo $obra['comodos_instalados']; ?> de <?php echo $obra['total_comodos']; ?>
                                                </small>
                                                <div class="progress mt-1" style="width: 100px;">
                                                    <div class="progress-bar bg-<?php echo $statusColor; ?>" 
                                                         style="width: <?php echo $percentual; ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Atividades Recentes</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($atividades)): ?>
                                    <p class="text-muted text-center">Nenhuma atividade registrada.</p>
                                <?php else: ?>
                                    <?php foreach ($atividades as $atividade): ?>
                                        <div class="mb-3">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <i class="fas fa-circle text-primary" style="font-size: 0.5rem;"></i>
                                                </div>
                                                <div class="flex-grow-1 ms-2">
                                                    <small class="text-muted">
                                                        <?php echo formatDateTime($atividade['created_at']); ?>
                                                    </small>
                                                    <div class="small">
                                                        <strong><?php echo htmlspecialchars($atividade['user_nome'] ?? 'Sistema'); ?></strong>
                                                        <?php echo htmlspecialchars($atividade['action']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function markNotificationAsRead(notificationId) {
            fetch('ajax/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'notification_id=' + notificationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>
