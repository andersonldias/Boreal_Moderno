-- Estrutura do Banco de Dados Padronizada para Instalação de Esquadrias
-- Banco: bichosdobairro2

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `nome` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100),
    `telefone` VARCHAR(20),
    `role` VARCHAR(20) NOT NULL DEFAULT 'funcionario' CHECK (`role` IN ('gestor', 'funcionario')),
    `active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de obras
CREATE TABLE IF NOT EXISTS `obras` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(200) NOT NULL,
    `endereco` TEXT NOT NULL,
    `cliente` VARCHAR(200) NOT NULL,
    `data_inicio` DATE,
    `tipo_construcao` VARCHAR(100),
    `num_pavimentos` INT,
    `num_unidades` INT,
    `status` VARCHAR(20) DEFAULT 'planejada' CHECK (`status` IN ('planejada', 'em_andamento', 'em_finalizacao', 'concluida', 'pausada')),
    `observacoes` TEXT,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- Tabela de cômodos
CREATE TABLE IF NOT EXISTS `comodos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `obra_id` INT NOT NULL,
    `nome` VARCHAR(100) NOT NULL,
    `tipo_esquadria` VARCHAR(100) NOT NULL,
    `modelo` VARCHAR(100),
    `dimensoes` VARCHAR(100),
    `status` VARCHAR(20) DEFAULT 'nao_instalado' CHECK (`status` IN ('nao_instalado', 'instalado', 'em_instalacao')),
    `observacao` TEXT,
    `data_instalacao` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`obra_id`) REFERENCES `obras`(`id`) ON DELETE CASCADE
);

-- Tabela de funcionários (equipes)
CREATE TABLE IF NOT EXISTS `funcionarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL,
    `funcao` VARCHAR(100) NOT NULL,
    `telefone` VARCHAR(20),
    `email` VARCHAR(100),
    `active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de instalações (relacionamento entre cômodos e funcionários)
CREATE TABLE IF NOT EXISTS `instalacoes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `comodo_id` INT NOT NULL,
    `funcionario_id` INT NOT NULL,
    `data_instalacao` DATETIME NOT NULL,
    `observacoes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`comodo_id`) REFERENCES `comodos`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios`(`id`) ON DELETE CASCADE
);

-- Tabela de fotos e evidências
CREATE TABLE IF NOT EXISTS `fotos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `obra_id` INT NOT NULL,
    `comodo_id` INT,
    `titulo` VARCHAR(255) NOT NULL,
    `descricao` TEXT,
    `tipo` VARCHAR(50),
    `filename` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` INT,
    `mime_type` VARCHAR(100),
    `uploaded_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`obra_id`) REFERENCES `obras`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`comodo_id`) REFERENCES `comodos`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- Tabela de tipos de esquadria personalizados por obra
CREATE TABLE IF NOT EXISTS `tipos_esquadria` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `obra_id` INT NOT NULL,
    `nome` VARCHAR(100) NOT NULL,
    `descricao` TEXT,
    `especificacoes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`obra_id`) REFERENCES `obras`(`id`) ON DELETE CASCADE
);

-- Tabela de histórico de alterações
CREATE TABLE IF NOT EXISTS `historico_alteracoes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tabela` VARCHAR(50) NOT NULL,
    `registro_id` INT NOT NULL,
    `campo` VARCHAR(50) NOT NULL,
    `valor_anterior` TEXT,
    `valor_novo` TEXT,
    `user_id` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- Tabela de notificações
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `type` VARCHAR(20) DEFAULT 'info' CHECK (`type` IN ('info', 'warning', 'error', 'success')),
    `read_status` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Tabela de logs de atividades
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT,
    `ip_address` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- Tabela de permissões de usuários por obra
CREATE TABLE IF NOT EXISTS `user_obra_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `obra_id` INT NOT NULL,
    `can_view` BOOLEAN DEFAULT TRUE,
    `can_edit` BOOLEAN DEFAULT FALSE,
    `can_install` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`obra_id`) REFERENCES `obras`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_obra` (`user_id`, `obra_id`)
);

-- Índices para melhorar performance
CREATE INDEX `idx_obras_status` ON `obras`(`status`);
CREATE INDEX `idx_comodos_obra_id` ON `comodos`(`obra_id`);
CREATE INDEX `idx_comodos_status` ON `comodos`(`status`);
CREATE INDEX `idx_instalacoes_comodo_id` ON `instalacoes`(`comodo_id`);
CREATE INDEX `idx_instalacoes_funcionario_id` ON `instalacoes`(`funcionario_id`);
CREATE INDEX `idx_fotos_comodo_id` ON `fotos`(`comodo_id`);
CREATE INDEX `idx_notifications_user_id` ON `notifications`(`user_id`);
CREATE INDEX `idx_notifications_read_status` ON `notifications`(`read_status`);
CREATE INDEX `idx_activity_logs_user_id` ON `activity_logs`(`user_id`);
CREATE INDEX `idx_activity_logs_created_at` ON `activity_logs`(`created_at`);

-- Inserir usuário administrador padrão
-- Senha: admin123 (hash bcrypt)
INSERT INTO `users` (`username`, `password`, `nome`, `email`, `role`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin@boreal.com', 'gestor');

-- Inserir funcionário de exemplo
INSERT INTO `funcionarios` (`nome`, `funcao`, `telefone`, `email`) VALUES 
('João Silva', 'Instalador', '(11) 99999-9999', 'joao@boreal.com'),
('Maria Santos', 'Auxiliar de Instalação', '(11) 88888-8888', 'maria@boreal.com');

-- Inserir obra de exemplo
INSERT INTO `obras` (`nome`, `endereco`, `cliente`, `data_inicio`, `status`, `created_by`) VALUES 
('Residencial Solar', 'Rua das Flores, 123 - São Paulo/SP', 'Construtora ABC Ltda', '2024-01-15', 'em_andamento', 1);

-- Inserir cômodos de exemplo
INSERT INTO `comodos` (`obra_id`, `nome`, `tipo_esquadria`, `modelo`, `dimensoes`) VALUES 
(1, 'Sala', 'Janela', 'Maxi-Ar', '1.20 x 1.50'),
(1, 'Cozinha', 'Janela', 'Maxi-Ar', '1.00 x 1.20'),
(1, 'Quarto 1', 'Janela', 'Maxi-Ar', '1.20 x 1.50'),
(1, 'Quarto 2', 'Janela', 'Maxi-Ar', '1.20 x 1.50'),
(1, 'Banheiro', 'Janela', 'Maxi-Ar', '0.60 x 0.80');

-- Inserir permissões de exemplo
INSERT INTO `user_obra_permissions` (`user_id`, `obra_id`, `can_view`, `can_edit`, `can_install`) VALUES 
(1, 1, 1, 1, 1);