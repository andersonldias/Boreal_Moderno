<?php
/**
 * Script de Instalação Automática
 * Sistema de Instalação de Esquadrias
 * 
 * Este script verifica os requisitos do sistema, cria o banco de dados
 * e configura o sistema automaticamente.
 */

// Definir ambiente de instalação
define('INSTALLING', true);

// Verificar se já está instalado
if (file_exists('config/database.php') && !isset($_GET['force'])) {
    die('O sistema já está instalado. Use ?force=1 para forçar reinstalação.');
}

// Funções de verificação
function checkRequirements() {
    $errors = [];
    $warnings = [];
    
    // Verificar versão do PHP
    if (version_compare(PHP_VERSION, '7.4.0', '<')) {
        $errors[] = "PHP 7.4 ou superior é necessário. Versão atual: " . PHP_VERSION;
    }
    
    // Verificar extensões necessárias
    $required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
    foreach ($required_extensions as $ext) {
        if (!extension_loaded($ext)) {
            $errors[] = "Extensão PHP '$ext' não está carregada";
        }
    }
    
    // Verificar extensões recomendadas
    $recommended_extensions = ['gd', 'zip', 'curl'];
    foreach ($recommended_extensions as $ext) {
        if (!extension_loaded($ext)) {
            $warnings[] = "Extensão PHP '$ext' não está carregada (recomendada)";
        }
    }
    
    // Verificar permissões de diretório
    $directories = ['uploads', 'uploads/fotos', 'logs'];
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                $errors[] = "Não foi possível criar o diretório '$dir'";
            }
        } elseif (!is_writable($dir)) {
            $errors[] = "Diretório '$dir' não tem permissão de escrita";
        }
    }
    
    return ['errors' => $errors, 'warnings' => $warnings];
}

function testDatabaseConnection($host, $username, $password, $dbname = null) {
    try {
        $dsn = "mysql:host=$host;charset=utf8mb4";
        if ($dbname) {
            $dsn .= ";dbname=$dbname";
        }
        
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return ['success' => true, 'pdo' => $pdo];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function createDatabase($pdo, $dbname) {
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function importSchema($pdo, $schemaFile) {
    try {
        $sql = file_get_contents($schemaFile);
        $pdo->exec($sql);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function createConfigFile($host, $username, $password, $dbname) {
    $config = "<?php
// Configuração do banco de dados
\$host = '$host';
\$dbname = '$dbname';
\$username = '$username';
\$password = '$password';

try {
    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\", \$username, \$password);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException \$e) {
    die(\"Erro na conexão: \" . \$e->getMessage());
}
?>";
    
    return file_put_contents('config/database.php', $config);
}

// Processar formulário de instalação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'] ?? 'localhost';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $dbname = $_POST['dbname'] ?? 'esquadrias_db';
    $admin_user = $_POST['admin_user'] ?? 'admin';
    $admin_password = $_POST['admin_password'] ?? '';
    
    $errors = [];
    
    // Validar campos obrigatórios
    if (empty($username) || empty($password) || empty($dbname) || empty($admin_password)) {
        $errors[] = "Todos os campos são obrigatórios";
    }
    
    if (empty($errors)) {
        // Testar conexão
        $connection = testDatabaseConnection($host, $username, $password);
        
        if ($connection['success']) {
            $pdo = $connection['pdo'];
            
            // Criar banco se não existir
            if (!createDatabase($pdo, $dbname)) {
                $errors[] = "Não foi possível criar o banco de dados";
            } else {
                // Conectar ao banco criado
                $connection = testDatabaseConnection($host, $username, $password, $dbname);
                
                if ($connection['success']) {
                    $pdo = $connection['pdo'];
                    
                    // Importar schema
                    if (importSchema($pdo, 'database/schema.sql')) {
                        // Criar arquivo de configuração
                        if (createConfigFile($host, $username, $password, $dbname)) {
                            // Atualizar senha do admin
                            try {
                                $hashedPassword = password_hash($admin_password, PASSWORD_DEFAULT);
                                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
                                $stmt->execute([$hashedPassword, $admin_user]);
                                
                                $success = true;
                                $successMessage = "Sistema instalado com sucesso! Use as credenciais configuradas para fazer login.";
                            } catch (Exception $e) {
                                $errors[] = "Erro ao configurar usuário admin: " . $e->getMessage();
                            }
                        } else {
                            $errors[] = "Não foi possível criar o arquivo de configuração";
                        }
                    } else {
                        $errors[] = "Não foi possível importar o schema do banco de dados";
                    }
                } else {
                    $errors[] = "Erro ao conectar ao banco de dados: " . $connection['error'];
                }
            }
        } else {
            $errors[] = "Erro na conexão com o banco de dados: " . $connection['error'];
        }
    }
}

// Verificar requisitos
$requirements = checkRequirements();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Sistema de Esquadrias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .install-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 50px auto;
            max-width: 800px;
        }
        .step-indicator {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .step {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #dee2e6;
            color: white;
            text-align: center;
            line-height: 40px;
            margin: 0 10px;
        }
        .step.active {
            background: #007bff;
        }
        .step.completed {
            background: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-container p-5">
            <div class="text-center mb-4">
                <i class="fas fa-building fa-3x text-primary mb-3"></i>
                <h2>Sistema de Instalação de Esquadrias</h2>
                <p class="text-muted">Configuração automática do sistema</p>
            </div>
            
            <!-- Indicador de Passos -->
            <div class="step-indicator text-center">
                <div class="step active">1</div>
                <div class="step">2</div>
                <div class="step">3</div>
            </div>
            
            <?php if (isset($success)): ?>
                <!-- Sucesso na Instalação -->
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle fa-2x mb-3"></i>
                    <h4>Instalação Concluída!</h4>
                    <p><?php echo htmlspecialchars($successMessage); ?></p>
                    <hr>
                    <p><strong>Credenciais de Acesso:</strong></p>
                    <p><strong>Usuário:</strong> <?php echo htmlspecialchars($admin_user); ?></p>
                    <p><strong>Senha:</strong> <?php echo htmlspecialchars($admin_password); ?></p>
                    <hr>
                    <a href="index.php" class="btn btn-success btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Acessar Sistema
                    </a>
                </div>
                
            <?php elseif (isset($errors) && !empty($errors)): ?>
                <!-- Erros -->
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Erros na Instalação:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
            <?php else: ?>
                <!-- Verificação de Requisitos -->
                <?php if (!empty($requirements['errors'])): ?>
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-times-circle"></i> Requisitos não atendidos:</h5>
                        <ul class="mb-0">
                            <?php foreach ($requirements['errors'] as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($requirements['warnings'])): ?>
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Avisos:</h5>
                        <ul class="mb-0">
                            <?php foreach ($requirements['warnings'] as $warning): ?>
                                <li><?php echo htmlspecialchars($warning); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($requirements['errors'])): ?>
                    <!-- Formulário de Instalação -->
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3"><i class="fas fa-database"></i> Configuração do Banco</h5>
                                
                                <div class="mb-3">
                                    <label for="host" class="form-label">Host do Banco</label>
                                    <input type="text" class="form-control" id="host" name="host" value="localhost" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Usuário do Banco *</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Senha do Banco *</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="dbname" class="form-label">Nome do Banco *</label>
                                    <input type="text" class="form-control" id="dbname" name="dbname" value="esquadrias_db" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="mb-3"><i class="fas fa-user-shield"></i> Usuário Administrador</h5>
                                
                                <div class="mb-3">
                                    <label for="admin_user" class="form-label">Nome de Usuário</label>
                                    <input type="text" class="form-control" id="admin_user" name="admin_user" value="admin" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="admin_password" class="form-label">Senha *</label>
                                    <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                                    <div class="form-text">Mínimo 6 caracteres</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmar Senha *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-rocket"></i> Instalar Sistema
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4 p-3 bg-light rounded">
                        <h6><i class="fas fa-info-circle"></i> Informações Importantes:</h6>
                        <ul class="mb-0 small">
                            <li>O usuário do banco deve ter permissões para criar bancos de dados</li>
                            <li>Certifique-se de que o MySQL/MariaDB está rodando</li>
                            <li>Após a instalação, remova este arquivo por segurança</li>
                            <li>Altere a senha padrão do administrador após o primeiro login</li>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validação de confirmação de senha
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('admin_password').value;
            const confirm = this.value;
            
            if (password !== confirm) {
                this.setCustomValidity('As senhas não coincidem');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Validação de tamanho mínimo da senha
        document.getElementById('admin_password').addEventListener('input', function() {
            if (this.value.length < 6) {
                this.setCustomValidity('A senha deve ter pelo menos 6 caracteres');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
