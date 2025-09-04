@echo off
echo ========================================
echo DIAGNOSTICO AVANCADO - ERRO HTTP 500
echo SISTEMA DE INSTALACAO DE ESQUADRIAS
echo ========================================
echo.
echo Este script ira fazer um diagnostico AVANCADO
echo para identificar exatamente onde esta o erro HTTP 500
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
if not exist "diagnostico-avancado.php" (
    echo ERRO: Arquivo diagnostico-avancado.php nao encontrado!
    echo Execute este script na pasta do projeto.
    echo.
    pause
    exit /b 1
)

echo ✅ Arquivo de diagnóstico avançado encontrado
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
echo Abrindo navegador para diagnóstico avançado...
start http://localhost:%PORT%/diagnostico-avancado.php

echo.
echo ========================================
echo DIAGNOSTICO AVANCADO INICIADO!
echo ========================================
echo.
echo 🌐 Navegador aberto automaticamente
echo 🔗 URL: http://localhost:%PORT%/diagnostico-avancado.php
echo.
echo O script ira verificar:
echo 1. Cada arquivo individualmente
echo 2. Inclusoes de arquivos
echo 3. Funcionalidades criticas
echo 4. Sessoes e autenticacao
echo 5. Permissoes e estrutura
echo.
echo Aguarde o diagnostico terminar...
echo.

REM Aguardar usuário
echo Pressione qualquer tecla quando o diagnostico terminar...
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
echo ✅ Diagnostico avançado concluido
echo.
echo Baseado no resultado:
echo 1. Identifique o arquivo com problema
echo 2. Corrija os erros encontrados
echo 3. Teste novamente o sistema
echo.
pause
