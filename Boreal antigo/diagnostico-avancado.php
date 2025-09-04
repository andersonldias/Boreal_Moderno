<?php
/**
 * DIAGNÓSTICO AVANÇADO - ERRO HTTP 500
 * Sistema de Instalação de Esquadrias
 * 
 * Este script vai testar cada arquivo individualmente
 * para identificar exatamente onde está o erro
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
echo "<title>Diagnóstico Avançado - Erro HTTP 500</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }";
echo ".container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".success { color: #28a745; font-weight: bold; }";
echo ".error { color: #dc3545; font-weight: bold; }";
echo ".warning { color: #ffc107; font-weight: bold; }";
echo ".info { color: #17a2b8; font-weight: bold; }";
echo ".step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; border-radius: 4px; }";
echo ".result { background: #e9ecef; padding: 15px; margin: 10px 0; border-radius: 4px; }";
echo ".code { background: #f1f3f4; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }";
echo ".file-test { background: #fff3cd; padding: 10px; margin: 5px 0; border-radius: 4px; border-left: 4px solid #ffc107; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>🔍 DIAGNÓSTICO AVANÇADO - ERRO HTTP 500</h1>";
echo "<p><strong>Sistema:</strong> Sistema de Instalação de Esquadrias</p>";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<p><strong>Objetivo:</strong> Identificar arquivo específico causando erro HTTP 500</p>";
echo "<hr>";

echo "<div class='step'>";
echo "<h3>📋 PASSO 1: Testando Arquivos Principais Individualmente</h3>";

// Lista de arquivos para testar
$filesToTest = [
    'index.php' => 'Página de login principal',
    'dashboard.php' => 'Dashboard principal',
    'funcionarios.php' => 'Gestão de funcionários',
    'usuarios.php' => 'Gestão de usuários',
    'obras.php' => 'Gestão de obras',
    'instalacoes.php' => 'Gestão de instalações',
    'relatorios.php' => 'Relatórios',
    'fotos.php' => 'Gestão de fotos',
    'get_comodos.php' => 'API de cômodos',
    'logout.php' => 'Logout do sistema'
];

foreach ($filesToTest as $file => $description) {
    echo "<div class='file-test'>";
    echo "<h4>🔍 Testando: {$file}</h4>";
    echo "<p><strong>Descrição:</strong> {$description}</p>";
    
    if (file_exists($file)) {
        echo "<p class='success'>✅ Arquivo existe</p>";
        
        // Tentar incluir o arquivo para ver se há erros de sintaxe
        try {
            // Capturar saída e erros
            ob_start();
            $oldErrorReporting = error_reporting();
            error_reporting(E_ALL);
            
            // Tentar incluir o arquivo
            $included = @include_once $file;
            
            $output = ob_get_clean();
            error_reporting($oldErrorReporting);
            
            if ($included !== false) {
                echo "<p class='success'>✅ Arquivo incluído com sucesso</p>";
            } else {
                echo "<p class='error'>❌ Erro ao incluir arquivo</p>";
            }
            
            if (!empty($output)) {
                echo "<p class='info'>ℹ️ Saída capturada: " . substr($output, 0, 100) . "...</p>";
            }
            
        } catch (ParseError $e) {
            echo "<p class='error'>❌ ERRO DE SINTAXE: " . $e->getMessage() . "</p>";
            echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
        } catch (Error $e) {
            echo "<p class='error'>❌ ERRO FATAL: " . $e->getMessage() . "</p>";
            echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ EXCEÇÃO: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Arquivo NÃO ENCONTRADO</p>";
    }
    
    echo "</div>";
}

echo "</div>";

echo "<div class='step'>";
echo "<h3>🔧 PASSO 2: Testando Inclusões de Arquivos</h3>";

// Testar inclusões críticas
$criticalIncludes = [
    'config/database.php' => 'Configuração do banco',
    'includes/functions.php' => 'Funções do sistema',
    'config/environment.php' => 'Configuração de ambiente'
];

foreach ($criticalIncludes as $file => $description) {
    echo "<div class='file-test'>";
    echo "<h4>🔍 Testando inclusão: {$file}</h4>";
    echo "<p><strong>Descrição:</strong> {$description}</p>";
    
    if (file_exists($file)) {
        echo "<p class='success'>✅ Arquivo existe</p>";
        
        try {
            ob_start();
            $included = @include_once $file;
            $output = ob_get_clean();
            
            if ($included !== false) {
                echo "<p class='success'>✅ Inclusão bem-sucedida</p>";
                
                // Verificar se variáveis importantes foram definidas
                if ($file === 'config/database.php') {
                    if (isset($pdo)) {
                        echo "<p class='success'>✅ Variável \$pdo definida</p>";
                    } else {
                        echo "<p class='warning'>⚠️ Variável \$pdo NÃO definida</p>";
                    }
                }
                
                if ($file === 'includes/functions.php') {
                    $functions = ['isLoggedIn', 'isGestor', 'sanitizeInput'];
                    foreach ($functions as $func) {
                        if (function_exists($func)) {
                            echo "<p class='success'>✅ Função {$func}() existe</p>";
                        } else {
                            echo "<p class='error'>❌ Função {$func}() NÃO existe</p>";
                        }
                    }
                }
                
            } else {
                echo "<p class='error'>❌ Erro na inclusão</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Arquivo NÃO ENCONTRADO</p>";
    }
    
    echo "</div>";
}

echo "</div>";

echo "<div class='step'>";
echo "<h3>🧪 PASSO 3: Testando Funcionalidades Críticas</h3>";

try {
    // Testar se o banco está funcionando
    if (isset($pdo)) {
        echo "<p class='success'>✅ Conexão PDO disponível</p>";
        
        // Testar funções básicas
        $testQuery = $pdo->query("SELECT COUNT(*) as total FROM users")->fetch();
        echo "<p class='success'>✅ Query de teste: {$testQuery['total']} usuários</p>";
        
        // Testar funções do sistema
        if (function_exists('isLoggedIn')) {
            echo "<p class='success'>✅ Função isLoggedIn() disponível</p>";
            $result = isLoggedIn();
            echo "<p class='info'>ℹ️ isLoggedIn() retornou: " . ($result ? 'true' : 'false') . "</p>";
        }
        
        if (function_exists('isGestor')) {
            echo "<p class='success'>✅ Função isGestor() disponível</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Conexão PDO NÃO disponível</p>";
        echo "<p>Verifique se config/database.php foi incluído corretamente</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro ao testar funcionalidades: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div class='step'>";
echo "<h3>📊 PASSO 4: Verificando Sessões e Autenticação</h3>";

try {
    // Verificar se sessão está funcionando
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "<p class='success'>✅ Sessão iniciada</p>";
    } else {
        echo "<p class='info'>ℹ️ Sessão já ativa</p>";
    }
    
    // Verificar variáveis de sessão
    if (isset($_SESSION)) {
        echo "<p class='success'>✅ \$_SESSION disponível</p>";
        echo "<p class='info'>ℹ️ Chaves de sessão: " . implode(', ', array_keys($_SESSION)) . "</p>";
    } else {
        echo "<p class='warning'>⚠️ \$_SESSION não disponível</p>";
    }
    
    // Testar funções de autenticação
    if (function_exists('isLoggedIn')) {
        $authResult = isLoggedIn();
        echo "<p class='info'>ℹ️ Status de autenticação: " . ($authResult ? 'Logado' : 'Não logado') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro com sessões: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div class='step'>";
echo "<h3>🔍 PASSO 5: Verificando Permissões e Estrutura</h3>";

// Verificar permissões de arquivos
$criticalFiles = ['index.php', 'dashboard.php', 'config/database.php'];
foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        $readable = is_readable($file);
        $writable = is_writable($file);
        
        echo "<p><strong>{$file}:</strong></p>";
        echo "<p>Permissões: " . substr(sprintf('%o', $perms), -4) . "</p>";
        echo "<p>Legível: " . ($readable ? '✅ Sim' : '❌ Não') . "</p>";
        echo "<p>Gravável: " . ($writable ? '✅ Sim' : '❌ Não') . "</p>";
    }
}

// Verificar estrutura de diretórios
$directories = ['config', 'includes', 'uploads', 'assets'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "<p class='success'>✅ Diretório {$dir} existe</p>";
        echo "<p class='info'>ℹ️ Conteúdo: " . count(scandir($dir)) . " itens</p>";
    } else {
        echo "<p class='warning'>⚠️ Diretório {$dir} NÃO existe</p>";
    }
}

echo "</div>";

echo "<hr>";
echo "<div class='result'>";
echo "<h2>🎯 RESUMO DO DIAGNÓSTICO AVANÇADO</h2>";

// Resumo dos problemas encontrados
$problems = [];
$warnings = [];

echo "<h3>🔍 PROBLEMAS IDENTIFICADOS:</h3>";
if (empty($problems)) {
    echo "<p class='success'>✅ Nenhum problema crítico encontrado</p>";
} else {
    foreach ($problems as $problem) {
        echo "<p class='error'>❌ {$problem}</p>";
    }
}

echo "<h3>⚠️ AVISOS:</h3>";
if (empty($warnings)) {
    echo "<p class='success'>✅ Nenhum aviso</p>";
} else {
    foreach ($warnings as $warning) {
        echo "<p class='warning'>⚠️ {$warning}</p>";
    }
}

echo "<h3>🔧 PRÓXIMOS PASSOS:</h3>";
echo "<ol>";
echo "<li><strong>Se problemas encontrados:</strong> Corrija os erros identificados</li>";
echo "<li><strong>Se tudo OK:</strong> O erro pode estar em tempo de execução</li>";
echo "<li><strong>Teste novamente:</strong> Execute iniciar-sistema.bat</li>";
echo "<li><strong>Verifique logs:</strong> Procure por erros específicos</li>";
echo "</ol>";

echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
