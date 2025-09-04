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

// Processar ações
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload_foto':
                $obraId = $_POST['obra_id'];
                $comodoId = $_POST['comodo_id'] ?? null;
                $titulo = sanitizeInput($_POST['titulo']);
                $descricao = sanitizeInput($_POST['descricao']);
                $tipo = $_POST['tipo'];
                
                // Verificar se foi enviado um arquivo
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                    $arquivo = $_FILES['foto'];
                    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
                    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($extensao, $extensoesPermitidas)) {
                        // Criar diretório se não existir
                        $diretorio = 'uploads/fotos/';
                        if (!is_dir($diretorio)) {
                            mkdir($diretorio, 0755, true);
                        }
                        
                        // Gerar nome único para o arquivo
                        $nomeArquivo = uniqid() . '_' . time() . '.' . $extensao;
                        $caminhoCompleto = $diretorio . $nomeArquivo;
                        
                        if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
                            try {
                                $stmt = $pdo->prepare("INSERT INTO fotos (obra_id, comodo_id, titulo, descricao, tipo, filename, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                $stmt->execute([$obraId, $comodoId, $titulo, $descricao, $tipo, $nomeArquivo, $userId]);
                                
                                logActivity($userId, 'Upload de foto', "Obra ID: $obraId, Foto: $titulo");
                                $message = 'Foto enviada com sucesso!';
                                $messageType = 'success';
                            } catch (Exception $e) {
                                unlink($caminhoCompleto); // Remover arquivo se erro no banco
                                $message = 'Erro ao salvar foto no banco: ' . $e->getMessage();
                                $messageType = 'danger';
                            }
                        } else {
                            $message = 'Erro ao mover arquivo enviado!';
                            $messageType = 'danger';
                        }
                    } else {
                        $message = 'Tipo de arquivo não permitido! Use apenas JPG, PNG ou GIF.';
                        $messageType = 'danger';
                    }
                } else {
                    $message = 'Nenhum arquivo foi enviado ou ocorreu um erro no upload!';
                    $messageType = 'danger';
                }
                break;
                
            case 'delete_foto':
                if (isGestor()) {
                    $fotoId = $_POST['foto_id'];
                    $foto = fetchOne("SELECT filename FROM fotos WHERE id = ?", [$fotoId]);
                    
                    if ($foto) {
                        try {
                            $stmt = $pdo->prepare("DELETE FROM fotos WHERE id = ?");
                            $stmt->execute([$fotoId]);
                            
                            // Remover arquivo físico
                            $caminhoArquivo = 'uploads/fotos/' . $foto['filename'];
                            if (file_exists($caminhoArquivo)) {
                                unlink($caminhoArquivo);
                            }
                            
                            logActivity($userId, 'Excluiu foto', "Foto ID: $fotoId");
                            $message = 'Foto excluída com sucesso!';
                            $messageType = 'success';
                        } catch (Exception $e) {
                            $message = 'Erro ao excluir foto: ' . $e->getMessage();
                            $messageType = 'danger';
                        }
                    }
                }
                break;
        }
    }
}

// Filtros
$obraFilter = $_GET['obra_id'] ?? '';
$comodoFilter = $_GET['comodo_id'] ?? '';
$tipoFilter = $_GET['tipo'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// Obter obras disponíveis para o usuário
if (isGestor()) {
    $obras = fetchAll("SELECT * FROM obras ORDER BY nome ASC");
} else {
    $obras = fetchAll("SELECT o.* FROM obras o JOIN user_obra_permissions p ON o.id = p.obra_id WHERE p.user_id = ? ORDER BY o.nome ASC", [$userId]);
}

// Obter cômodos da obra selecionada
$comodos = [];
if ($obraFilter) {
    $comodos = fetchAll("SELECT * FROM comodos WHERE obra_id = ? ORDER BY nome ASC", [$obraFilter]);
}

// Construir query para fotos
$whereConditions = [];
$params = [];

if ($obraFilter) {
    $whereConditions[] = "f.obra_id = ?";
    $params[] = $obraFilter;
}

if ($comodoFilter) {
    $whereConditions[] = "f.comodo_id = ?";
    $params[] = $comodoFilter;
}

if ($tipoFilter) {
    $whereConditions[] = "f.tipo = ?";
    $params[] = $tipoFilter;
}

if ($searchTerm) {
    $whereConditions[] = "(f.titulo LIKE ? OR f.descricao LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$query = "SELECT f.*, o.nome as obra_nome, c.nome as comodo_nome, u.nome as usuario_nome
          FROM fotos f
          JOIN obras o ON f.obra_id = o.id
          LEFT JOIN comodos c ON f.comodo_id = c.id
          JOIN users u ON f.uploaded_by = u.id
          $whereClause
          ORDER BY f.created_at DESC";

$fotos = fetchAll($query, $params);

// Estatísticas
$totalFotos = count($fotos);
$fotosPorTipo = [];
foreach ($fotos as $foto) {
    $tipo = $foto['tipo'];
    if (!isset($fotosPorTipo[$tipo])) {
        $fotosPorTipo[$tipo] = 0;
    }
    $fotosPorTipo[$tipo]++;
}

// Obter tipos únicos para filtro
$tipos = fetchAll("SELECT DISTINCT tipo FROM fotos WHERE tipo IS NOT NULL ORDER BY tipo");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Fotos - Instalação de Esquadrias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
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
        .foto-card {
            border-radius: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            overflow: hidden;
        }
        .foto-card:hover {
            transform: translateY(-5px);
        }
        .foto-thumbnail {
            width: 100%;
            height: 200px;
            object-fit: cover;
            cursor: pointer;
        }
        .foto-info {
            padding: 15px;
        }
        .tipo-badge {
            font-size: 0.8rem;
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s;
        }
        .upload-area:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        .upload-area.dragover {
            border-color: #007bff;
            background-color: #e3f2fd;
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
                            <a class="nav-link" href="relatorios.php">
                                <i class="fas fa-chart-bar me-2"></i>
                                Relatórios
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link active" href="fotos.php">
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
                    <h1 class="h2">Gerenciar Fotos</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadFotoModal">
                            <i class="fas fa-upload"></i> Enviar Foto
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
                                            Total de Fotos</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalFotos; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-camera fa-2x text-gray-300"></i>
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
                                            Obras Documentadas</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count(array_unique(array_column($fotos, 'obra_id'))); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-building fa-2x text-gray-300"></i>
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
                                            Tipos de Foto</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($fotosPorTipo); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tags fa-2x text-gray-300"></i>
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
                                            Última Foto</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $totalFotos > 0 ? formatDate($fotos[0]['created_at']) : 'N/A'; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                <label for="obra_id" class="form-label">Obra</label>
                                <select class="form-select" id="obra_id" name="obra_id" onchange="this.form.submit()">
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
                                <label for="comodo_id" class="form-label">Cômodo</label>
                                <select class="form-select" id="comodo_id" name="comodo_id">
                                    <option value="">Todos os Cômodos</option>
                                    <?php foreach ($comodos as $comodo): ?>
                                        <option value="<?php echo $comodo['id']; ?>" 
                                                <?php echo $comodoFilter == $comodo['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($comodo['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="tipo" class="form-label">Tipo</label>
                                <select class="form-select" id="tipo" name="tipo">
                                    <option value="">Todos</option>
                                    <?php foreach ($tipos as $tipo): ?>
                                        <option value="<?php echo htmlspecialchars($tipo['tipo']); ?>" 
                                                <?php echo $tipoFilter == $tipo['tipo'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tipo['tipo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="search" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>" 
                                       placeholder="Título ou descrição">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                    <a href="fotos.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Limpar
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Galeria de Fotos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Galeria de Fotos</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($fotos)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-camera fa-3x mb-3"></i>
                                <h5>Nenhuma foto encontrada</h5>
                                <p>Não há fotos que atendam aos filtros aplicados ou ainda não foram enviadas fotos.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadFotoModal">
                                    <i class="fas fa-upload"></i> Enviar Primeira Foto
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($fotos as $foto): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card foto-card h-100">
                                            <a href="uploads/fotos/<?php echo htmlspecialchars($foto['filename']); ?>" 
                                               data-lightbox="galeria" 
                                               data-title="<?php echo htmlspecialchars($foto['titulo']); ?>">
                                                <img src="uploads/fotos/<?php echo htmlspecialchars($foto['filename']); ?>" 
                                                     class="foto-thumbnail" 
                                                     alt="<?php echo htmlspecialchars($foto['titulo']); ?>">
                                            </a>
                                            <div class="foto-info">
                                                <h6 class="card-title"><?php echo htmlspecialchars($foto['titulo']); ?></h6>
                                                <?php if ($foto['descricao']): ?>
                                                    <p class="card-text small text-muted"><?php echo htmlspecialchars($foto['descricao']); ?></p>
                                                <?php endif; ?>
                                                
                                                <div class="mb-2">
                                                    <span class="badge bg-primary tipo-badge"><?php echo htmlspecialchars($foto['tipo']); ?></span>
                                                    <span class="badge bg-info tipo-badge"><?php echo htmlspecialchars($foto['obra_nome']); ?></span>
                                                    <?php if ($foto['comodo_nome']): ?>
                                                        <span class="badge bg-secondary tipo-badge"><?php echo htmlspecialchars($foto['comodo_nome']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user me-1"></i>
                                                        <?php echo htmlspecialchars($foto['usuario_nome']); ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <?php echo formatDate($foto['created_at']); ?>
                                                    </small>
                                                </div>
                                                
                                                <?php if (isGestor()): ?>
                                                <div class="mt-2">
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteFoto(<?php echo $foto['id']; ?>, '<?php echo htmlspecialchars($foto['titulo']); ?>')" 
                                                            title="Excluir">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Upload de Foto -->
    <div class="modal fade" id="uploadFotoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enviar Nova Foto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="upload_foto">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="modal_obra_id" class="form-label">Obra *</label>
                                    <select class="form-select" id="modal_obra_id" name="obra_id" required onchange="carregarComodos()">
                                        <option value="">Selecione uma obra...</option>
                                        <?php foreach ($obras as $obra): ?>
                                            <option value="<?php echo $obra['id']; ?>">
                                                <?php echo htmlspecialchars($obra['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="modal_comodo_id" class="form-label">Cômodo (opcional)</label>
                                    <select class="form-select" id="modal_comodo_id" name="comodo_id">
                                        <option value="">Selecione um cômodo...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="titulo" class="form-label">Título *</label>
                                    <input type="text" class="form-control" id="titulo" name="titulo" required 
                                           placeholder="Ex: Instalação da janela da sala">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tipo" class="form-label">Tipo *</label>
                                    <select class="form-select" id="tipo" name="tipo" required>
                                        <option value="">Selecione...</option>
                                        <option value="Antes">Antes da Instalação</option>
                                        <option value="Durante">Durante a Instalação</option>
                                        <option value="Depois">Depois da Instalação</option>
                                        <option value="Problema">Problema/Defeito</option>
                                        <option value="Detalhe">Detalhe da Instalação</option>
                                        <option value="Outro">Outro</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3" 
                                      placeholder="Descreva a foto, observações importantes, etc."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="foto" class="form-label">Foto *</label>
                            <div class="upload-area" id="uploadArea">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h5>Arraste e solte a foto aqui</h5>
                                <p class="text-muted">ou clique para selecionar</p>
                                <input type="file" class="form-control" id="foto" name="foto" required 
                                       accept="image/*" style="display: none;">
                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('foto').click()">
                                    Selecionar Arquivo
                                </button>
                            </div>
                            <div id="previewContainer" class="mt-3" style="display: none;">
                                <img id="imagePreview" class="img-fluid rounded" style="max-height: 200px;">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Enviar Foto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="deleteFotoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir a foto <strong id="deleteFotoTitulo"></strong>?</p>
                    <p class="text-danger"><small>Esta ação não pode ser desfeita e removerá o arquivo permanentemente.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_foto">
                        <input type="hidden" name="foto_id" id="deleteFotoId">
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script>
        function carregarComodos() {
            const obraId = document.getElementById('modal_obra_id').value;
            const comodoSelect = document.getElementById('modal_comodo_id');
            
            if (obraId) {
                fetch(`get_comodos.php?obra_id=${obraId}`)
                    .then(response => response.json())
                    .then(comodos => {
                        comodoSelect.innerHTML = '<option value="">Selecione um cômodo...</option>';
                        comodos.forEach(comodo => {
                            const option = document.createElement('option');
                            option.value = comodo.id;
                            option.textContent = comodo.nome;
                            comodoSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Erro ao carregar cômodos:', error);
                    });
            } else {
                comodoSelect.innerHTML = '<option value="">Selecione um cômodo...</option>';
            }
        }

        function deleteFoto(fotoId, titulo) {
            document.getElementById('deleteFotoId').value = fotoId;
            document.getElementById('deleteFotoTitulo').textContent = titulo;
            new bootstrap.Modal(document.getElementById('deleteFotoModal')).show();
        }

        // Preview da imagem
        document.getElementById('foto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('previewContainer').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // Drag and drop
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('foto');

        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });

        // Configuração do Lightbox
        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true,
            'albumLabel': 'Foto %1 de %2'
        });
    </script>
</body>
</html>
