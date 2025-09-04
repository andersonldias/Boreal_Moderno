@echo off
cd /d "%~dp0"
echo ========================================
echo SISTEMA DE INSTALACAO DE ESQUADRIAS
echo ========================================
echo.

REM --- Verificacoes Iniciais ---
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo ERRO: PHP nao encontrado!
    echo Instale o PHP ou adicione ao PATH do sistema.
    echo.
    pause
    exit /b 1
)

if not exist "index.php" (
    echo ERRO: Arquivo index.php nao encontrado!
    echo Execute este script na pasta do projeto.
    echo.
    pause
    exit /b 1
)

echo Verificacoes OK. Iniciando servidor...
echo.

REM --- Iniciar o servidor em uma nova janela ---
REM O titulo da nova janela ser "Servidor PHP"
start "Servidor PHP" cmd /c "echo Servidor rodando em http://localhost:8000 & echo Pressione Ctrl+C ou feche esta janela para parar. & php -S localhost:8000"

REM --- Aguardar e abrir o navegador ---
echo Aguardando o servidor iniciar...
timeout /t 4 /nobreak >nul

echo Abrindo o navegador...
start http://localhost:8000

echo.
echo ========================================
echo SUCESSO!
echo ========================================
echo.
echo O servidor esta rodando em uma janela separada.
echo Para parar o servidor, feche a janela "Servidor PHP".
echo.
pause