-- Adiciona a coluna de controle na tabela de obras para habilitar/desabilitar o recurso
ALTER TABLE `obras`
ADD COLUMN `usa_hierarquia_locais` BOOLEAN NOT NULL DEFAULT FALSE;

-- Cria a tabela para armazenar os locais de forma hierárquica (Bloco, Andar, Apartamento, Cômodo)
CREATE TABLE `locais` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_obra` INT NOT NULL,
    `parent_id` INT NULL,
    `nome` VARCHAR(255) NOT NULL COMMENT 'Nome do local, ex: Bloco A, 1º Andar, Apto 101, Sala de Estar',
    `tipo` VARCHAR(50) NOT NULL COMMENT 'Tipo do local, ex: bloco, andar, apartamento, comodo',
    `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_obra`) REFERENCES `obras`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`parent_id`) REFERENCES `locais`(`id`) ON DELETE CASCADE
) COMMENT 'Tabela para gerenciamento hierárquico de locais na obra';
