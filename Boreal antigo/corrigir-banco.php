<?php
/**
 * SCRIPT DE CORREÇÃO DEFINITIVA DO BANCO DE DADOS
 */

// Configurações do banco
$config = [
    'host' => 'xmysql.bichosdobairro.com.br',
    'dbname' => 'bichosdobairro2',
    'username' => 'bichosdobairro2',
    'password' => '!Boreal.123456',
    'charset' => 'utf8mb4',
    'port' => '3306'
];

$COLOR_SUCCESS = "\033[32m";
$COLOR_ERROR = "\033[31m";
$COLOR_INFO = "\033[34m";
$COLOR_RESET = "\033[0m";

echo "{$COLOR_INFO}=================================================={$COLOR_RESET}\n";
echo "{$COLOR_INFO}INICIANDO SCRIPT DE CORREÇÃO DO BANCO DE DADOS{$COLOR_RESET}\n";
echo "{$COLOR_INFO}=================================================={$COLOR_RESET}\n\n";

try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "{$COLOR_SUCCESS}PASSO 1: Conexão com o banco de dados estabelecida com sucesso.{$COLOR_RESET}\n";
} catch (PDOException $e) {
    echo "{$COLOR_ERROR}ERRO DE CONEXÃO: " . $e->getMessage() . "{$COLOR_RESET}\n";
    exit(1);
}

try {
    echo "{$COLOR_INFO}PASSO 2: Desativando verificação de chaves estrangeiras...{$COLOR_RESET}\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if (empty($tables)) {
        echo "{$COLOR_INFO}Nenhuma tabela encontrada para apagar.{$COLOR_RESET}\n";
    } else {
        echo "{$COLOR_INFO}PASSO 3: Apagando todas as tabelas existentes...{$COLOR_RESET}\n";
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            echo "  - Tabela `{$table}` apagada.\n";
        }
        echo "{$COLOR_SUCCESS}Todas as tabelas antigas foram apagadas com sucesso.{$COLOR_RESET}\n";
    }

    echo "{$COLOR_INFO}PASSO 4: Reativando verificação de chaves estrangeiras...{$COLOR_RESET}\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');

} catch (PDOException $e) {
    echo "{$COLOR_ERROR}ERRO AO APAGAR TABELas: " . $e->getMessage() . "{$COLOR_RESET}\n";
    exit(1);
}


try {
    $schemaFile = __DIR__ . '/database/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Arquivo 'database/schema.sql' não encontrado.");
    }
    $sql = file_get_contents($schemaFile);
    if (empty($sql)) {
        throw new Exception("Arquivo 'database/schema.sql' está vazio.");
    }

    echo "{$COLOR_INFO}PASSO 5: Executando 'database/schema.sql' para recriar a estrutura correta...{$COLOR_RESET}\n";
    $pdo->exec($sql);
    echo "{$COLOR_SUCCESS}Tabelas e dados iniciais recriados com sucesso!{$COLOR_RESET}\n";

} catch (Exception $e) {
    echo "{$COLOR_ERROR}ERRO AO RECRIAÇÃO DO BANCO: " . $e->getMessage() . "{$COLOR_RESET}\n";
    exit(1);
}

echo "\n{$COLOR_SUCCESS}=================================================={$COLOR_RESET}\n";
echo "{$COLOR_SUCCESS}          CORREÇÃO CONCLUÍDA COM SUCESSO!         {$COLOR_RESET}\n";
echo "{$COLOR_SUCCESS}=================================================={$COLOR_RESET}\n";
echo "{$COLOR_INFO}O banco de dados foi recriado com a estrutura correta.{$COLOR_RESET}\n";
echo "{$COLOR_INFO}A tabela 'users' agora deve existir.{$COLOR_RESET}\n\n";

?>
