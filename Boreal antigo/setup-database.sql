-- ========================================
-- SCRIPT DE CONFIGURACAO DO BANCO DE DADOS
-- SISTEMA DE INSTALACAO DE ESQUADRIAS
-- ========================================

-- Criar o banco de dados
CREATE DATABASE IF NOT EXISTS bichosdobairro2 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Usar o banco criado
USE bichosdobairro2;

-- Verificar se o banco foi criado
SELECT 
    SCHEMA_NAME as 'Banco de Dados',
    DEFAULT_CHARACTER_SET_NAME as 'Charset',
    DEFAULT_COLLATION_NAME as 'Collation'
FROM INFORMATION_SCHEMA.SCHEMATA 
WHERE SCHEMA_NAME = 'bichosdobairro2';

-- ========================================
-- INSTRUCOES DE USO:
-- ========================================
-- 1. Abra o MySQL Workbench
-- 2. Conecte ao seu servidor local (localhost:3306)
-- 3. Execute este script (Ctrl+Shift+Enter)
-- 4. Verifique se o banco foi criado
-- 5. Execute o arquivo database/schema.sql para criar as tabelas
-- ========================================

-- Para executar o schema completo, use:
-- SOURCE C:/Boreal/Programa/database/schema.sql;
