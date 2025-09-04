-- Altera a tabela de instalações para suportar múltiplos funcionários por instalação

-- Primeiro, verificamos se a coluna antiga existe para evitar erros em re-execuções
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE() AND table_name = 'instalacoes' AND column_name = 'funcionario_id') > 0,
    "ALTER TABLE `instalacoes` DROP FOREIGN KEY `instalacoes_ibfk_2`",
    "SELECT 1"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE() AND table_name = 'instalacoes' AND column_name = 'funcionario_id') > 0,
    "ALTER TABLE `instalacoes` DROP COLUMN `funcionario_id`",
    "SELECT 1"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionamos a nova coluna se ela não existir
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE() AND table_name = 'instalacoes' AND column_name = 'funcionarios_ids') = 0,
    "ALTER TABLE `instalacoes` ADD COLUMN `funcionarios_ids` VARCHAR(255) NULL COMMENT 'IDs dos funcionários separados por vírgula'",
    "SELECT 1"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
