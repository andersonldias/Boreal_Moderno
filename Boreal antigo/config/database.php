<?php
// Arquivo de Configuração do Banco de Dados
// Configurado para usar o banco de dados EXTERNO original.

// 1. Definição das Constantes de Conexão
define('DB_HOST', 'xmysql.bichosdobairro.com.br');
define('DB_NAME', 'bichosdobairro2');
define('DB_USER', 'bichosdobairro2');
define('DB_PASS', '!Boreal.123456');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', '3306');

// 2. Criação da Conexão PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("FALHA CRÍTICA DE CONEXÃO COM O BANCO EXTERNO: " . $e->getMessage());
    die("Erro fatal: Não foi possível conectar ao banco de dados externo. Verifique a conexão com a internet e as credenciais.");
}

// 3. Funções de Acesso ao Banco
function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Erro na query: " . $e->getMessage());
        return false;
    }
}

function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : false;
}
?>