@echo off
echo ========================================
echo VERIFICACAO AUTOMATICA DO SISTEMA
echo SISTEMA DE INSTALACAO DE ESQUADRIAS
echo ========================================
echo.
echo Este script ira verificar TUDO automaticamente
echo e mostrar exatamente onde esta o problema
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

REM Verificar se estamos no diretório correto
if not exist "index.php" (
    echo ERRO: Arquivo index.php nao encontrado!
    echo Execute este script na pasta do projeto.
    echo.
    pause
    exit /b 1
)

echo ✅ Arquivo index.php encontrado
echo.

REM Iniciar servidor PHP temporário
echo Iniciando servidor PHP temporário...
echo.

REM Verificar se porta 8000 está livre
netstat -an | find ":8000" >nul 2>&1
if %errorlevel% equ 0 (
    echo ⚠️ Porta 8000 ja esta em uso
    echo Tentando porta 8001...
    set PORT=8001
) else (
    echo ✅ Porta 8000 disponivel
    set PORT=8000
)

echo.
echo Iniciando servidor na porta %PORT%...
echo.

REM Iniciar servidor PHP em background
start /B php -S localhost:%PORT%

REM Aguardar servidor iniciar
echo Aguardando servidor iniciar...
timeout /t 3 /nobreak >nul

REM Abrir navegador automaticamente
echo Abrindo navegador para verificação automática...
start http://localhost:%PORT%/

echo.
echo ========================================
echo VERIFICACAO AUTOMATICA INICIADA!
echo ========================================
echo.
echo 🌐 Navegador aberto automaticamente
echo 🔗 URL: http://localhost:%PORT%/
echo.
echo O sistema ira mostrar automaticamente:
echo ✅ Status do banco de dados
echo ✅ Status da tabela usuarios
echo ✅ Status do usuario admin
echo ✅ Status das funcoes PHP
echo ✅ Status das sessoes
echo.
echo Aguarde a verificacao terminar...
echo.

REM Aguardar usuário
echo Pressione qualquer tecla quando a verificacao terminar...
pause >nul

REM Parar servidor
echo.
echo Parando servidor...
taskkill /F /IM php.exe >nul 2>&1
echo Servidor parado.

echo.
echo ========================================
echo PROXIMOS PASSOS:
echo ========================================
echo.
echo ✅ Verificacao automatica concluida
echo.
echo Baseado no resultado:
echo 1. Identifique o problema na caixa azul
echo 2. Corrija o erro encontrado
echo 3. Teste novamente o sistema
echo.
pause
