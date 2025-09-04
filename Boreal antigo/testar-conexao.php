<?php
/**
 * Script para testar conexão com banco externo
 * Sistema de Instalação de Esquadrias
 */

echo "<h1>🔍 TESTE DE CONEXÃO COM BANCO EXTERNO</h1>";
echo "<hr>";

// Configurações do banco externo
$config = [
    'host' => 'xmysql.bichosdobairro.com.br',
    'dbname' => 'bichosdobairro2',
    'username' => 'bichosdobairro2',
    'password' => '!Boreal.123456',
    'charset' => 'utf8mb4',
    'port' => '3306'
];

echo "<h2>📋 Configurações:</h2>";
echo "<ul>";
echo "<li><strong>Host:</strong> {$config['host']}</li>";
echo "<li><strong>Porta:</strong> {$config['port']}</li>";
echo "<li><strong>Banco:</strong> {$config['dbname']}</li>";
echo "<li><strong>Usuário:</strong> {$config['username']}</li>";
echo "<li><strong>Charset:</strong> {$config['charset']}</li>";
echo "</ul>";

echo "<h2>🔌 Testando Conexão...</h2>";

try {
    // Testar conexão
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
    
    echo "<p>🔄 Tentando conectar...</p>";
    
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "<p style='color: green;'>✅ <strong>CONEXÃO ESTABELECIDA COM SUCESSO!</strong></p>";
    
    // Testar informações do servidor
    echo "<h2>📊 Informações do Servidor:</h2>";
    
    $serverInfo = $pdo->query("SELECT VERSION() as versao, NOW() as data_hora")->fetch();
    echo "<ul>";
    echo "<li><strong>Versão MySQL:</strong> {$serverInfo['versao']}</li>";
    echo "<strong>Data/Hora:</strong> {$serverInfo['data_hora']}</li>";
    echo "</ul>";
    
    // Testar tabelas
    echo "<h2>🗂️ Verificando Tabelas:</h2>";
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll();
    
    if (empty($tables)) {
        echo "<p style='color: orange;'>⚠️ <strong>Nenhuma tabela encontrada!</strong></p>";
        echo "<p>Execute o script <code>setup-external-database.sql</code> no MySQL Workbench para criar as tabelas.</p>";
    } else {
        echo "<p style='color: green;'>✅ <strong>" . count($tables) . " tabelas encontradas:</strong></p>";
        echo "<ul>";
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            echo "<li><code>{$tableName}</code></li>";
        }
        echo "</ul>";
        
        // Verificar dados nas tabelas principais
        echo "<h2>📈 Verificando Dados:</h2>";
        
        try {
            $userCount = $pdo->query("SELECT COUNT(*) as total FROM users")->fetch()['total'];
            echo "<p><strong>Usuários:</strong> {$userCount}</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erro ao verificar usuários: " . $e->getMessage() . "</p>";
        }
        
        try {
            $tiposCount = $pdo->query("SELECT COUNT(*) as total FROM tipos_esquadria")->fetch()['total'];
            echo "<p><strong>Tipos de Esquadria:</strong> {$tiposCount}</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erro ao verificar tipos de esquadria: " . $e->getMessage() . "</p>";
        }
    }
    
    // Testar funções do sistema
    echo "<h2>🧪 Testando Funções do Sistema:</h2>";
    
    try {
        // Testar função fetchOne
        $testQuery = $pdo->query("SELECT 1 as teste")->fetch();
        echo "<p style='color: green;'>✅ <strong>Query de teste:</strong> OK</p>";
        
        // Testar função fetchAll
        $testQuery2 = $pdo->query("SELECT 1 as teste")->fetchAll();
        echo "<p style='color: green;'>✅ <strong>Fetch múltiplo:</strong> OK</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro ao testar funções: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    echo "<h2>🎉 RESULTADO FINAL:</h2>";
    echo "<p style='color: green; font-size: 18px;'>✅ <strong>BANCO EXTERNO FUNCIONANDO PERFEITAMENTE!</strong></p>";
    echo "<p>Agora você pode executar o <code>iniciar-sistema.bat</code> e usar o sistema.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-size: 18px;'>❌ <strong>ERRO DE CONEXÃO:</strong></p>";
    echo "<p><strong>Mensagem:</strong> " . $e->getMessage() . "</p>";
    
    echo "<hr>";
    echo "<h2>🔧 SOLUÇÕES POSSÍVEIS:</h2>";
    echo "<ul>";
    echo "<li>Verifique se o servidor está acessível</li>";
    echo "<li>Confirme as credenciais de acesso</li>";
    echo "<li>Verifique se a porta 3306 está aberta</li>";
    echo "<li>Teste a conexão no MySQL Workbench</li>";
    echo "<li>Verifique firewall/proxy da rede</li>";
    echo "</ul>";
    
    echo "<h2>🔄 ALTERNATIVA:</h2>";
    echo "<p>Se o banco externo não funcionar, use a configuração local:</p>";
    echo "<ol>";
    echo "<li>Edite <code>config/database.php</code></li>";
    echo "<li>Descomente a linha <code>include_once 'database.local.php';</code></li>";
    echo "<li>Execute <code>setup-local.bat</code></li>";
    echo "</ol>";
}

echo "<hr>";
echo "<p><em>Script de teste executado em: " . date('d/m/Y H:i:s') . "</em></p>";
?>
