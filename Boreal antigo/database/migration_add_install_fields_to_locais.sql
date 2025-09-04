-- Adiciona campos de instalação na tabela de locais para unificar as informações
-- Estes campos serão usados principalmente quando o tipo for 'comodo'

ALTER TABLE `locais`
ADD COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'nao_instalado',
ADD COLUMN `observacao` TEXT NULL,
ADD COLUMN `data_instalacao` DATETIME NULL,
ADD COLUMN `tipo_esquadria` VARCHAR(255) NULL,
ADD COLUMN `modelo` VARCHAR(255) NULL,
ADD COLUMN `dimensoes` VARCHAR(100) NULL;
