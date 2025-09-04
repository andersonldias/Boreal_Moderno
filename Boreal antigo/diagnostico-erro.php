<?php
/**
 * DIAGNÓSTICO COMPLETO DO ERRO HTTP 500
 * Sistema de Instalação de Esquadrias
 */

// Ativar exibição de erros para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>";
echo "<html lang='pt-BR'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Diagnóstico de Erro HTTP 500</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }";
echo ".container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".success { color: #28a745; font-weight: bold; }";
echo ".error { color: #dc3545; font-weight: bold; }";
echo ".warning { color: #ffc107; font-weight: bold; }";
echo ".info { color: #17a2b8; font-weight: bold; }";
echo ".step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; border-radius: 4px; }";
echo ".result { background: #e9ecef; padding: 15px; margin: 10px 0; border-radius: 4px; }";
echo ".code { background: #f1f3f4; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>🔍 DIAGNÓSTICO COMPLETO DO ERRO HTTP 500</h1>";
echo "<p><strong>Sistema:</strong> Sistema de Instalação de Esquadrias</p>";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<hr>";

echo "<div class='step'>";
echo "<h3>📋 PASSO 1: Verificando Sistema PHP</h3>";

// Verificar versão do PHP
echo "<p><strong>Versão PHP:</strong> " . phpversion() . "</p>";

// Verificar extensões necessárias
$extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
echo "<p><strong>Extensões necessárias:</strong></p>";
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✅ {$ext}: OK</p>";
    } else {
        echo "<p class='error'>❌ {$ext}: FALTANDO</p>";
    }
}

// Verificar configurações do PHP
echo "<p><strong>Configurações PHP:</strong></p>";
echo "<p>display_errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "</p>";
echo "<p>error_reporting: " . ini_get('error_reporting') . "</p>";
echo "<p>max_execution_time: " . ini_get('max_execution_time') . "s</p>";
echo "<p>memory_limit: " . ini_get('memory_limit') . "</p>";

echo "</div>";

echo "<div class='step'>";
echo "<h3>🗂️ PASSO 2: Verificando Arquivos do Sistema</h3>";

$requiredFiles = [
    'index.php',
    'dashboard.php',
    'config/database.php',
    'includes/functions.php',
    'configurar-banco-automatico.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "<p class='success'>✅ {$file}: Existe</p>";
    } else {
        echo "<p class='error'>❌ {$file}: NÃO ENCONTRADO</p>";
    }
}

echo "</div>";

echo "<div class='step'>";
echo "<h3>🔌 PASSO 3: Testando Conexão com Banco Externo</h3>";

// Configurações do banco
$config = [
    'host' => 'xmysql.bichosdobairro.com.br',
    'dbname' => 'bichosdobairro2',
    'username' => 'bichosdobairro2',
    'password' => '!Boreal.123456',
    'charset' => 'utf8mb4',
    'port' => '3306'
];

echo "<p><strong>Configurações do banco:</strong></p>";
echo "<div class='code'>";
echo "Host: {$config['host']}<br>";
echo "Porta: {$config['port']}<br>";
echo "Banco: {$config['dbname']}<br>";
echo "Usuário: {$config['username']}<br>";
echo "Charset: {$config['charset']}<br>";
echo "</div>";

try {
    // Testar conectividade de rede primeiro
    echo "<p>🔄 Testando conectividade de rede...</p>";
    
    $connection = @fsockopen($config['host'], $config['port'], $errno, $errstr, 10);
    if ($connection) {
        echo "<p class='success'>✅ Conectividade de rede: OK</p>";
        fclose($connection);
    } else {
        echo "<p class='error'>❌ Conectividade de rede: FALHOU (Erro {$errno}: {$errstr})</p>";
    }
    
    // Testar conexão PDO
    echo "<p>🔄 Testando conexão PDO...</p>";
    
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
    
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 10, // Timeout de 10 segundos
    ]);
    
    echo "<p class='success'>✅ Conexão PDO: OK</p>";
    
    // Testar informações do servidor
    $serverInfo = $pdo->query("SELECT VERSION() as versao, NOW() as data_hora")->fetch();
    echo "<div class='result'>";
    echo "<p><strong>Versão MySQL:</strong> {$serverInfo['versao']}</p>";
    echo "<p><strong>Data/Hora:</strong> {$serverInfo['data_hora']}</p>";
    echo "</div>";
    
    // Verificar se o banco existe
    $databases = $pdo->query("SHOW DATABASES")->fetchAll();
    $dbExists = false;
    foreach ($databases as $db) {
        if ($db['Database'] === $config['dbname']) {
            $dbExists = true;
            break;
        }
    }
    
    if ($dbExists) {
        echo "<p class='success'>✅ Banco de dados '{$config['dbname']}' existe</p>";
    } else {
        echo "<p class='error'>❌ Banco de dados '{$config['dbname']}' NÃO EXISTE</p>";
        echo "<p>O banco precisa ser criado primeiro!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ ERRO DE CONEXÃO PDO: " . $e->getMessage() . "</p>";
    echo "<p><strong>Código do erro:</strong> " . $e->getCode() . "</p>";
    
    // Sugestões baseadas no código de erro
    switch ($e->getCode()) {
        case 2002:
            echo "<p class='warning'>⚠️ <strong>Problema:</strong> Não foi possível conectar ao servidor MySQL</p>";
            echo "<p><strong>Soluções:</strong></p>";
            echo "<ul>";
            echo "<li>Verifique se o servidor está online</li>";
            echo "<li>Verifique se a porta 3306 está aberta</li>";
            echo "<li>Teste a conectividade de rede</li>";
            echo "</ul>";
            break;
            
        case 1045:
            echo "<p class='warning'>⚠️ <strong>Problema:</strong> Acesso negado (credenciais incorretas)</p>";
            echo "<p><strong>Soluções:</strong></p>";
            echo "<ul>";
            echo "<li>Verifique usuário e senha</li>";
            echo "<li>Confirme se o usuário tem permissão de acesso</li>";
            echo "</ul>";
            break;
            
        case 1049:
            echo "<p class='warning'>⚠️ <strong>Problema:</strong> Banco de dados não existe</p>";
            echo "<p><strong>Soluções:</strong></p>";
            echo "<ul>";
            echo "<li>O banco precisa ser criado primeiro</li>";
            echo "<li>Use o script de configuração automática</li>";
            echo "</ul>";
            break;
            
        default:
            echo "<p class='warning'>⚠️ <strong>Problema:</strong> Erro desconhecido</p>";
            echo "<p>Verifique os logs do servidor MySQL</p>";
    }
}

echo "</div>";

echo "<div class='step'>";
echo "<h3>🔧 PASSO 4: Testando Arquivo de Configuração</h3>";

try {
    echo "<p>🔄 Testando inclusão do arquivo de configuração...</p>";
    
    // Simular o que acontece no index.php
    ob_start();
    include_once 'config/database.php';
    $output = ob_get_clean();
    
    if (isset($pdo)) {
        echo "<p class='success'>✅ Arquivo de configuração carregado com sucesso</p>";
        echo "<p><strong>Variável \$pdo:</strong> " . (isset($pdo) ? 'Definida' : 'Não definida') . "</p>";
    } else {
        echo "<p class='error'>❌ Arquivo de configuração não definiu \$pdo</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro ao carregar configuração: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div class='step'>";
echo "<h3>🧪 PASSO 5: Testando Funções do Sistema</h3>";

try {
    if (isset($pdo)) {
        // Testar função fetchOne
        $testQuery = $pdo->query("SELECT 1 as teste")->fetch();
        echo "<p class='success'>✅ Função fetchOne: OK</p>";
        
        // Testar função fetchAll
        $testQuery2 = $pdo->query("SELECT 1 as teste")->fetchAll();
        echo "<p class='success'>✅ Função fetchAll: OK</p>";
        
        // Testar função executeQuery
        if (function_exists('executeQuery')) {
            $stmt = executeQuery("SELECT 1 as teste");
            if ($stmt) {
                echo "<p class='success'>✅ Função executeQuery: OK</p>";
            } else {
                echo "<p class='error'>❌ Função executeQuery: Falhou</p>";
            }
        } else {
            echo "<p class='warning'>⚠️ Função executeQuery: Não definida</p>";
        }
    } else {
        echo "<p class='error'>❌ Não é possível testar funções sem conexão PDO</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro ao testar funções: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div class='step'>";
echo "<h3>📊 PASSO 6: Verificando Tabelas (se conexão OK)</h3>";

if (isset($pdo) && $dbExists) {
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll();
        
        if (empty($tables)) {
            echo "<p class='warning'>⚠️ Nenhuma tabela encontrada no banco</p>";
            echo "<p><strong>Solução:</strong> Execute o script de configuração automática para criar as tabelas</p>";
        } else {
            echo "<p class='success'>✅ " . count($tables) . " tabelas encontradas:</p>";
            echo "<ul>";
            foreach ($tables as $table) {
                $tableName = array_values($table)[0];
                echo "<li><code>{$tableName}</code></li>";
            }
            echo "</ul>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro ao verificar tabelas: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Não é possível verificar tabelas sem conexão válida</p>";
}

echo "</div>";

echo "<hr>";
echo "<div class='result'>";
echo "<h2>🎯 RESUMO DO DIAGNÓSTICO</h2>";

if (isset($pdo) && $dbExists) {
    echo "<p class='success'>✅ <strong>CONEXÃO COM BANCO: OK</strong></p>";
    echo "<p>O problema pode estar em outro lugar do sistema.</p>";
} else {
    echo "<p class='error'>❌ <strong>PROBLEMA PRINCIPAL: Conexão com banco falhou</strong></p>";
    echo "<p>Este é provavelmente o motivo do erro HTTP 500.</p>";
}

echo "<h3>🔧 PRÓXIMOS PASSOS:</h3>";
echo "<ol>";
echo "<li><strong>Se a conexão falhou:</strong> Resolva o problema de banco primeiro</li>";
echo "<li><strong>Se a conexão OK:</strong> Verifique outros arquivos do sistema</li>";
echo "<li><strong>Execute novamente:</strong> configurar-banco-automatico.bat</li>";
echo "</ol>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
