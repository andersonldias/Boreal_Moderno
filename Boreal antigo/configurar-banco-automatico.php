<?php
/**
 * SCRIPT AUTOMÁTICO PARA CONFIGURAR BANCO EXTERNO (VERSÃO CORRIGIDA)
 * Sistema de Instalação de Esquadrias
 * 
 * Este script vai:
 * 1. Testar a conexão com o banco externo
 * 2. Ler o arquivo database/schema.sql
 * 3. Executar o schema para criar todas as tabelas e inserir dados iniciais
 * 4. Verificar se a tabela 'users' foi criada com sucesso
 */

echo "<!DOCTYPE html>";
echo "<html lang='pt-BR'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Configuração Automática do Banco</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".success { color: #28a745; font-weight: bold; }";
echo ".error { color: #dc3545; font-weight: bold; }";
echo ".warning { color: #ffc107; font-weight: bold; }";
echo ".info { color: #17a2b8; font-weight: bold; }";
echo ".step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; border-radius: 4px; }";
echo ".result { background: #e9ecef; padding: 15px; margin: 10px 0; border-radius: 4px; }";
echo "pre { background-color: #333; color: #fff; padding: 10px; border-radius: 5px; white-space: pre-wrap; word-wrap: break-word; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1><span style='font-size: 2em;'>🔧</span> CONFIGURAÇÃO AUTOMÁTICA DO BANCO EXTERNO (CORRIGIDO)</h1>";
echo "<p><strong>Sistema:</strong> Sistema de Instalação de Esquadrias</p>";
echo "<p><strong>Banco:</strong> xmysql.bichosdobairro.com.br</p>";
echo "<hr>";

// Configurações do banco
$config = [
    'host' => 'xmysql.bichosdobairro.com.br',
    'dbname' => 'bichosdobairro2',
    'username' => 'bichosdobairro2',
    'password' => '!Boreal.123456',
    'charset' => 'utf8mb4',
    'port' => '3306'
];

echo "<div class='step'>";
echo "<h3><span style='font-size: 1.5em;'>📋</span> PASSO 1: Testando Conexão</h3>";

try {
    // Testar conexão
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
    
    echo "<p>🔄 Tentando conectar ao banco externo...</p>";
    
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "<p class='success'>✅ Conexão estabelecida com sucesso!</p>";
    
    // Informações do servidor
    $serverInfo = $pdo->query("SELECT VERSION() as versao, NOW() as data_hora")->fetch();
    echo "<div class='result'>";
    echo "<p><strong>Versão MySQL:</strong> {$serverInfo['versao']}</p>";
    echo "<p><strong>Data/Hora:</strong> {$serverInfo['data_hora']}</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ ERRO DE CONEXÃO: " . $e->getMessage() . "</p>";
    echo "<p>Verifique se o servidor está acessível e as credenciais estão corretas.</p>";
    echo "</div>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

echo "</div>";

echo "<div class='step'>";
echo "<h3><span style='font-size: 1.5em;'>🔨</span> PASSO 2: Executando o Schema do Banco de Dados</h3>";

$schemaFile = __DIR__ . '/database/schema.sql';

if (!file_exists($schemaFile)) {
    echo "<p class='error'>❌ ERRO: Arquivo <code>database/schema.sql</code> não encontrado!</p>";
    echo "<p>Verifique se o arquivo existe no diretório <code>database</code>.</p>";
    echo "</div>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

echo "<p class='info'>ℹ️ Lendo o arquivo <code>database/schema.sql</code>...</p>";
$sql = file_get_contents($schemaFile);

if (empty($sql)) {
    echo "<p class='error'>❌ ERRO: O arquivo <code>database/schema.sql</code> está vazio!</p>";
    echo "</div>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

try {
    echo "<p>🔄 Executando o script SQL para criar tabelas e inserir dados...</p>";
    $pdo->exec($sql);
    echo "<p class='success'>✅ Script SQL executado com sucesso!</p>";
} catch (PDOException $e) {
    echo "<p class='error'>❌ ERRO ao executar o script SQL: " . $e->getMessage() . "</p>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    echo "</div>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

echo "</div>";

echo "<div class='step'>";
echo "<h3><span style='font-size: 1.5em;'>🔍</span> PASSO 3: Verificação Final</h3>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $userCount = $stmt->fetch()['total'];
    echo "<p class='success'>✅ Tabela <code>users</code> encontrada. Total de usuários: {$userCount}</p>";
} catch (PDOException $e) {
    echo "<p class='error'>❌ ERRO ao verificar a tabela <code>users</code>: " . $e->getMessage() . "</p>";
    echo "<p class='warning'>A configuração pode não ter sido bem-sucedida.</p>";
    echo "</div>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

$finalTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "<p class='info'>ℹ️ Tabelas encontradas no banco de dados:</p>";
echo "<div class='result'>";
echo "<ul>";
foreach ($finalTables as $table) {
    echo "<li><code>{$table}</code></li>";
}
echo "</ul>";
echo "</div>";

echo "</div>";

echo "<hr>";
echo "<div class='result' style='background-color: #d4edda; border-left: 4px solid #28a745;'>";
echo "<h2><span style='font-size: 2em;'>🎉</span> CONFIGURAÇÃO CONCLUÍDA COM SUCESSO!</h2>";
echo "<p><strong>O banco de dados foi configurado corretamente.</strong></p>";
echo "<p>Agora você pode fechar esta janela e seguir os próximos passos no terminal.</p>";
echo "<ol>";
echo "<li>Execute o <code>iniciar-sistema.bat</code></li>";
echo "<li>Acessse <code>http://localhost:8000</code></li>";
echo "<li>Faça login com:";
echo "<ul>";
echo "<li><strong>Usuário:</strong> admin</li>";
echo "<li><strong>Senha:</strong> admin123</li>";
echo "</ul>";
echo "</li>";
echo "</ol>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>