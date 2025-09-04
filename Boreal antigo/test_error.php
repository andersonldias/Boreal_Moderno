<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<div style='font-family: monospace; border: 1px solid #ccc; padding: 10px;'>";
echo "<h1>Diagnóstico de Inclusão de Arquivos</h1>";

echo "<p>Iniciando teste...</p>";

try {
    echo "<p>Tentando incluir <strong>config/database.php</strong>...</p>";
    require_once 'config/database.php';
    echo "<p style='color: green;'><strong>SUCESSO:</strong> config/database.php incluído.</p>";
} catch (Throwable $t) {
    echo "<p style='color: red;'><strong>ERRO FATAL em config/database.php:</strong> " . $t->getMessage() . "</p>";
    echo "</div>";
    exit;
}

try {
    echo "<p>Tentando incluir <strong>includes/functions.php</strong>...</p>";
    require_once 'includes/functions.php';
    echo "<p style='color: green;'><strong>SUCESSO:</strong> includes/functions.php incluído.</p>";
} catch (Throwable $t) {
    echo "<p style='color: red;'><strong>ERRO FATAL em includes/functions.php:</strong> " . $t->getMessage() . "</p>";
    echo "</div>";
    exit;
}

echo "<hr>";
echo "<p style='color: blue;'><strong>Diagnóstico concluído.</strong> Se você vê esta mensagem, os arquivos principais foram carregados sem erros de sintaxe.</p>";
echo "</div>";

?>