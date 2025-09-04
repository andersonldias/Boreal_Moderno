@echo off
echo ========================================
echo CONFIGURACAO DO AMBIENTE LOCAL
echo ========================================
echo.
echo Este script ira configurar o banco de dados local
echo.

REM Verificar se XAMPP/WAMP está rodando
echo Verificando se o servidor MySQL esta rodando...
netstat -an | find ":3306" >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo ⚠️  AVISO: Porta 3306 nao esta em uso!
    echo.
    echo Certifique-se de que:
    echo 1. XAMPP ou WAMP esteja instalado
    echo 2. Servico MySQL esteja rodando
    echo 3. Apache esteja rodando
    echo.
    echo Apos iniciar os servicos, execute este script novamente.
    echo.
    pause
    exit /b 1
)

echo ✅ MySQL esta rodando na porta 3306
echo.

REM Verificar se PHP está disponível
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo ERRO: PHP nao encontrado!
    echo Instale o PHP ou adicione ao PATH do sistema.
    echo.
    pause
    exit /b 1
)

echo ✅ PHP encontrado
echo.

REM Criar banco de dados
echo Criando banco de dados local...
php -r "
try {
    \$pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '');
    \$pdo->exec('CREATE DATABASE IF NOT EXISTS bichosdobairro2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo 'Banco de dados criado com sucesso!';
} catch (PDOException \$e) {
    echo 'Erro ao criar banco: ' . \$e->getMessage();
}
"
echo.

REM Importar schema
echo Importando estrutura do banco...
if exist "database\schema.sql" (
    php -r "
    try {
        \$pdo = new PDO('mysql:host=localhost;dbname=bichosdobairro2;charset=utf8mb4', 'root', '');
        \$sql = file_get_contents('database/schema.sql');
        \$pdo->exec(\$sql);
        echo 'Schema importado com sucesso!';
    } catch (PDOException \$e) {
        echo 'Erro ao importar schema: ' . \$e->getMessage();
    }
    "
) else (
    echo ⚠️  Arquivo database\schema.sql nao encontrado!
)

echo.
echo ========================================
echo CONFIGURACAO CONCLUIDA!
echo ========================================
echo.
echo ✅ Banco de dados local criado
echo ✅ Schema importado
echo ✅ Configuracao local ativada
echo.
echo Agora execute o iniciar-sistema.bat
echo.
pause
