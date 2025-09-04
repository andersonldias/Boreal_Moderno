-- Corrige a chave estrangeira da tabela de instalações para apontar para a tabela `locais` em vez da antiga `comodos`.

-- 1. Remove a chave estrangeira antiga que aponta para a tabela `comodos`.
-- O nome da constraint (`instalacoes_ibfk_1`) foi obtido da mensagem de erro.
ALTER TABLE `instalacoes` DROP FOREIGN KEY `instalacoes_ibfk_1`;

-- 2. Adiciona a nova chave estrangeira correta, apontando para a tabela `locais`.
ALTER TABLE `instalacoes` ADD CONSTRAINT `fk_instalacoes_locais` 
    FOREIGN KEY (`comodo_id`) REFERENCES `locais`(`id`) ON DELETE CASCADE;
