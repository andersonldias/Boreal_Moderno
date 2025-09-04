<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar se está logado
if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Filtros de período
$periodoInicio = $_GET['periodo_inicio'] ?? date('Y-m-01'); // Primeiro dia do mês atual
$periodoFim = $_GET['periodo_fim'] ?? date('Y-m-d'); // Hoje

// Obter obras disponíveis para o usuário
if (isGestor()) {
    $obras = fetchAll("SELECT * FROM obras ORDER BY nome ASC");
} else {
    $obras = fetchAll("SELECT o.* FROM obras o JOIN user_obra_permissions p ON o.id = p.obra_id WHERE p.user_id = ? ORDER BY o.nome ASC", [$userId]);
}

$obraFilter = $_GET['obra_id'] ?? '';

// Estatísticas gerais
$totalObras = count($obras);
        $totalFuncionarios = fetchOne("SELECT COUNT(*) FROM funcionarios WHERE active = 1");
        $totalUsuarios = fetchOne("SELECT COUNT(*) FROM users WHERE active = 1");

// Estatísticas por período
$obrasIniciadas = fetchOne("SELECT COUNT(*) FROM obras WHERE data_inicio BETWEEN ? AND ?", [$periodoInicio, $periodoFim]);
$obrasConcluidas = fetchOne("SELECT COUNT(*) FROM obras WHERE status = 'concluida' AND data_prevista_conclusao BETWEEN ? AND ?", [$periodoInicio, $periodoFim]);

// Estatísticas de instalações por período
$instalacoesPeriodo = fetchOne("SELECT COUNT(*) FROM instalacoes WHERE data_instalacao BETWEEN ? AND ?", [$periodoInicio, $periodoFim]);

// Estatísticas por obra (se filtro aplicado)
$statsObra = null;
if ($obraFilter) {
    $statsObra = getObraStats($obraFilter);
}

// Dados para gráficos
$obrasPorStatus = fetchAll("SELECT status, COUNT(*) as total FROM obras GROUP BY status");
$instalacoesPorMes = fetchAll("SELECT DATE_FORMAT(data_instalacao, '%Y-%m') as mes, COUNT(*) as total 
                              FROM instalacoes 
                              WHERE data_instalacao BETWEEN ? AND ? 
                              GROUP BY DATE_FORMAT(data_instalacao, '%Y-%m') 
                              ORDER BY mes", [$periodoInicio, $periodoFim]);

        $funcionariosPorFuncao = fetchAll("SELECT funcao, COUNT(*) as total FROM funcionarios WHERE active = 1 GROUP BY funcao");

// Top funcionários por instalações
$topFuncionarios = fetchAll("SELECT f.nome, f.funcao, COUNT(i.id) as total_instalacoes
                            FROM funcionarios f
                            LEFT JOIN instalacoes i ON f.id = i.funcionario_id
                            WHERE f.active = 1
                            GROUP BY f.id, f.nome, f.funcao
                            ORDER BY total_instalacoes DESC
                            LIMIT 10");

// Obras com maior progresso
$obrasProgresso = fetchAll("SELECT o.nome, o.cliente, 
                                  (SELECT COUNT(*) FROM comodos WHERE obra_id = o.id) as total_comodos,
                                  (SELECT COUNT(*) FROM comodos WHERE obra_id = o.id) as total_comodos
                           FROM obras o
                           HAVING total_comodos > 0
                           ORDER BY (comodos_instalados / total_comodos) DESC
                           LIMIT 5");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Instalação de Esquadrias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        .progress {
            height: 8px;
            border-radius: 10px;
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
                            <a class="nav-link active" href="relatorios.php">
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
                    <h1 class="h2">Relatórios e Estatísticas</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-success" onclick="exportarRelatorio()">
                            <i class="fas fa-download"></i> Exportar Relatório
                        </button>
                    </div>
                </div>

                <!-- Filtros de Período -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="periodo_inicio" class="form-label">Período Início</label>
                                <input type="date" class="form-control" id="periodo_inicio" name="periodo_inicio" 
                                       value="<?php echo $periodoInicio; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="periodo_fim" class="form-label">Período Fim</label>
                                <input type="date" class="form-control" id="periodo_fim" name="periodo_fim" 
                                       value="<?php echo $periodoFim; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="obra_id" class="form-label">Obra Específica</label>
                                <select class="form-select" id="obra_id" name="obra_id">
                                    <option value="">Todas as Obras</option>
                                    <?php foreach ($obras as $obra): ?>
                                        <option value="<?php echo $obra['id']; ?>" 
                                                <?php echo $obraFilter == $obra['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($obra['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                    <a href="relatorios.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Limpar
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Estatísticas Gerais -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total de Obras</div>
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
                                            Funcionários Ativos</div>
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
                        <div class="card card-stats border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Instalações no Período</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $instalacoesPeriodo; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tools fa-2x text-gray-300"></i>
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
                                            Obras Iniciadas</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $obrasIniciadas; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-play fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="row mb-4">
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Status das Obras</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="statusObrasChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Instalações por Mês</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="instalacoesMesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Funcionários por Função</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="funcionariosFuncaoChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Top Funcionários</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="topFuncionariosChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabelas de Dados -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Top Funcionários por Instalações</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Funcionário</th>
                                                <th>Função</th>
                                                <th>Instalações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($topFuncionarios as $funcionario): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($funcionario['nome']); ?></td>
                                                    <td><span class="badge bg-info"><?php echo htmlspecialchars($funcionario['funcao']); ?></span></td>
                                                    <td><strong><?php echo $funcionario['total_instalacoes']; ?></strong></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Obras com Maior Progresso</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Obra</th>
                                                <th>Cliente</th>
                                                <th>Progresso</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($obrasProgresso as $obra): ?>
                                                <?php 
                                                $percentual = $obra['total_comodos'] > 0 ? 
                                                    calculatePercentage($obra['comodos_instalados'], $obra['total_comodos']) : 0;
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($obra['nome']); ?></td>
                                                    <td><?php echo htmlspecialchars($obra['cliente']); ?></td>
                                                    <td>
                                                        <div class="progress mb-1" style="height: 20px;">
                                                            <div class="progress-bar" role="progressbar" 
                                                                 style="width: <?php echo $percentual; ?>%">
                                                                <?php echo $percentual; ?>%
                                                            </div>
                                                        </div>
                                                        <small><?php echo $obra['comodos_instalados']; ?>/<?php echo $obra['total_comodos']; ?></small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($obraFilter && $statsObra): ?>
                <!-- Estatísticas da Obra Específica -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Estatísticas da Obra Selecionada</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-primary"><?php echo $statsObra['total']; ?></h4>
                                    <p class="text-muted">Total de Cômodos</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-success"><?php echo $statsObra['instalados']; ?></h4>
                                    <p class="text-muted">Instalados</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-warning"><?php echo $statsObra['nao_instalados']; ?></h4>
                                    <p class="text-muted">Não Instalados</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-info"><?php echo $statsObra['percentual']; ?>%</h4>
                                    <p class="text-muted">Progresso</p>
                                </div>
                            </div>
                        </div>
                        <div class="progress mt-3" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $statsObra['percentual']; ?>%" 
                                 aria-valuenow="<?php echo $statsObra['percentual']; ?>" 
                                 aria-valuemin="0" aria-valuemax="100">
                                <?php echo $statsObra['percentual']; ?>%
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dados para os gráficos
        const statusObrasData = <?php echo json_encode($obrasPorStatus); ?>;
        const instalacoesMesData = <?php echo json_encode($instalacoesPorMes); ?>;
        const funcionariosFuncaoData = <?php echo json_encode($funcionariosPorFuncao); ?>;
        const topFuncionariosData = <?php echo json_encode($topFuncionarios); ?>;

        // Gráfico de Status das Obras
        const statusObrasCtx = document.getElementById('statusObrasChart').getContext('2d');
        new Chart(statusObrasCtx, {
            type: 'doughnut',
            data: {
                labels: statusObrasData.map(item => {
                    const labels = {
                        'planejada': 'Planejada',
                        'em_andamento': 'Em Andamento',
                        'em_finalizacao': 'Em Finalização',
                        'concluida': 'Concluída',
                        'pausada': 'Pausada'
                    };
                    return labels[item.status] || item.status;
                }),
                datasets: [{
                    data: statusObrasData.map(item => item.total),
                    backgroundColor: [
                        '#6c757d', // Planejada
                        '#ffc107', // Em Andamento
                        '#17a2b8', // Em Finalização
                        '#28a745', // Concluída
                        '#dc3545'  // Pausada
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de Instalações por Mês
        const instalacoesMesCtx = document.getElementById('instalacoesMesChart').getContext('2d');
        new Chart(instalacoesMesCtx, {
            type: 'line',
            data: {
                labels: instalacoesMesData.map(item => {
                    const [ano, mes] = item.mes.split('-');
                    const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
                    return `${meses[parseInt(mes) - 1]}/${ano}`;
                }),
                datasets: [{
                    label: 'Instalações',
                    data: instalacoesMesData.map(item => item.total),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de Funcionários por Função
        const funcionariosFuncaoCtx = document.getElementById('funcionariosFuncaoChart').getContext('2d');
        new Chart(funcionariosFuncaoCtx, {
            type: 'bar',
            data: {
                labels: funcionariosFuncaoData.map(item => item.funcao),
                datasets: [{
                    label: 'Funcionários',
                    data: funcionariosFuncaoData.map(item => item.total),
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: '#28a745',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de Top Funcionários
        const topFuncionariosCtx = document.getElementById('topFuncionariosChart').getContext('2d');
        new Chart(topFuncionariosCtx, {
            type: 'horizontalBar',
            data: {
                labels: topFuncionariosData.map(item => item.nome),
                datasets: [{
                    label: 'Instalações',
                    data: topFuncionariosData.map(item => item.total_instalacoes),
                    backgroundColor: 'rgba(255, 193, 7, 0.8)',
                    borderColor: '#ffc107',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });

        function exportarRelatorio() {
            // Implementar exportação para PDF ou Excel
            alert('Funcionalidade de exportação será implementada em breve!');
        }
    </script>
</body>
</html>
