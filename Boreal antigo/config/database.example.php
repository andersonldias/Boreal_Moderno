<?php
/**
 * Arquivo de exemplo para configuração do banco de dados
 * 
 * INSTRUÇÕES:
 * 1. Copie este arquivo para 'database.php'
 * 2. Configure as credenciais do seu banco de dados
 * 3. Certifique-se de que o banco de dados existe
 * 4. Importe o arquivo 'database/schema.sql' no seu banco
 */

// Configurações do banco de dados
$host = 'localhost';           // Host do banco de dados
$dbname = 'esquadrias_db';     // Nome do banco de dados
$username = 'seu_usuario';     // Usuário do banco de dados
$password = 'sua_senha';       // Senha do banco de dados

// Configurações adicionais
$charset = 'utf8mb4';          // Charset para suporte completo a Unicode
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    // Criar conexão PDO
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Testar conexão
    $pdo->query('SELECT 1');
    
} catch(PDOException $e) {
    // Em produção, não exibir detalhes do erro
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        die("Erro na conexão com o banco de dados: " . $e->getMessage());
    } else {
        die("Erro na conexão com o banco de dados. Verifique as configurações.");
    }
}

/**
 * CONFIGURAÇÕES RECOMENDADAS PARA PRODUÇÃO:
 * 
 * 1. Use um usuário dedicado para o banco (não root)
 * 2. Configure permissões mínimas necessárias
 * 3. Use senhas fortes
 * 4. Configure backup automático
 * 5. Use SSL para conexões remotas
 * 
 * EXEMPLO DE USUÁRIO MYSQL:
 * 
 * CREATE USER 'esquadrias_user'@'localhost' IDENTIFIED BY 'senha_forte_aqui';
 * GRANT SELECT, INSERT, UPDATE, DELETE ON esquadrias_db.* TO 'esquadrias_user'@'localhost';
 * FLUSH PRIVILEGES;
 * 
 * CONFIGURAÇÕES DE SEGURANÇA ADICIONAL:
 * 
 * 1. Renomeie o arquivo de configuração
 * 2. Configure permissões de arquivo adequadas
 * 3. Use variáveis de ambiente quando possível
 * 4. Configure firewall para limitar acesso ao banco
 * 5. Monitore logs de acesso
 */
?>
