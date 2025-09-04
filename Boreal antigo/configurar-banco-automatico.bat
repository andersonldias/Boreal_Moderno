@echo off
echo ========================================
echo CONFIGURACAO AUTOMATICA DO BANCO EXTERNO
echo SISTEMA DE INSTALACAO DE ESQUADRIAS
echo ========================================
echo.
echo Este script ira configurar automaticamente
echo o banco externo xmysql.bichosdobairro.com.br
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
if not exist "configurar-banco-automatico.php" (
    echo ERRO: Arquivo configurar-banco-automatico.php nao encontrado!
    echo Execute este script na pasta do projeto.
    echo.
    pause
    exit /b 1
)

echo ✅ Arquivo de configuração encontrado
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
echo Abrindo navegador para configuracao automatica...
start http://localhost:%PORT%/configurar-banco-automatico.php

echo.
echo ========================================
echo CONFIGURACAO INICIADA!
echo ========================================
echo.
echo 🌐 Navegador aberto automaticamente
echo 🔗 URL: http://localhost:%PORT%/configurar-banco-automatico.php
echo.
echo O script ira:
echo 1. Testar conexao com banco externo
echo 2. Criar todas as tabelas automaticamente
echo 3. Inserir dados iniciais
echo 4. Verificar se tudo funcionou
echo.
echo Aguarde a configuracao terminar...
echo.

REM Aguardar usuário
echo Pressione qualquer tecla quando a configuracao terminar...
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
echo ✅ Banco configurado automaticamente
echo.
echo Agora execute:
echo 1. iniciar-sistema.bat
echo 2. Acesse http://localhost:8000
echo 3. Login: admin / admin123
echo.
pause
