-- ========================================
-- SCRIPT PARA CONFIGURAR BANCO EXTERNO
-- SISTEMA DE INSTALACAO DE ESQUADRIAS
-- ========================================
-- 
-- Este script deve ser executado no MySQL Workbench
-- conectado ao servidor: xmysql.bichosdobairro.com.br
-- 
-- INSTRUCOES:
-- 1. Abra o MySQL Workbench
-- 2. Crie uma nova conexao para xmysql.bichosdobairro.com.br
-- 3. Conecte usando as credenciais:
--    - Host: xmysql.bichosdobairro.com.br
--    - Port: 3306
--    - Usuario: bichosdobairro2
--    - Senha: !Boreal.123456
-- 4. Execute este script (Ctrl+Shift+Enter)
-- ========================================

-- Verificar se conseguimos conectar
SELECT 
    'Conexao estabelecida com sucesso!' as Status,
    NOW() as Data_Hora,
    USER() as Usuario_Conectado,
    DATABASE() as Banco_Atual;

-- Verificar se o banco existe
SELECT 
    SCHEMA_NAME as 'Banco de Dados',
    DEFAULT_CHARACTER_SET_NAME as 'Charset',
    DEFAULT_COLLATION_NAME as 'Collation'
FROM INFORMATION_SCHEMA.SCHEMATA 
WHERE SCHEMA_NAME = 'bichosdobairro2';

-- Usar o banco correto
USE bichosdobairro2;

-- Verificar tabelas existentes
SHOW TABLES;

-- ========================================
-- CRIAR TABELAS SE NAO EXISTIREM
-- ========================================

-- TABELA: usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('gestor', 'funcionario') NOT NULL DEFAULT 'funcionario',
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- TABELA: obras
CREATE TABLE IF NOT EXISTS obras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    endereco TEXT NOT NULL,
    cliente VARCHAR(200) NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(100),
    data_inicio DATE,
    data_prevista_termino DATE,
    status ENUM('planejamento', 'em_andamento', 'concluida', 'pausada', 'cancelada') DEFAULT 'planejamento',
    progresso INT DEFAULT 0,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- TABELA: comodos
CREATE TABLE IF NOT EXISTS comodos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo VARCHAR(50),
    area DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE CASCADE
);

-- TABELA: funcionarios
CREATE TABLE IF NOT EXISTS funcionarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    telefone VARCHAR(20),
    email VARCHAR(100),
    funcao VARCHAR(100),
    especialidade VARCHAR(100),
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- TABELA: tipos_esquadria
CREATE TABLE IF NOT EXISTS tipos_esquadria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    categoria VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TABELA: instalacoes
CREATE TABLE IF NOT EXISTS instalacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT NOT NULL,
    comodo_id INT NOT NULL,
    tipo_esquadria_id INT NOT NULL,
    funcionario_id INT,
    descricao TEXT,
    quantidade INT DEFAULT 1,
    dimensoes VARCHAR(100),
    material VARCHAR(100),
    cor VARCHAR(50),
    status ENUM('pendente', 'em_andamento', 'concluida', 'problema') DEFAULT 'pendente',
    data_instalacao DATE,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE CASCADE,
    FOREIGN KEY (comodo_id) REFERENCES comodos(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_esquadria_id) REFERENCES tipos_esquadria(id),
    FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id)
);

-- TABELA: fotos
CREATE TABLE IF NOT EXISTS fotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instalacao_id INT,
    obra_id INT,
    comodo_id INT,
    nome_arquivo VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo ENUM('antes', 'durante', 'depois', 'problema', 'outro') DEFAULT 'outro',
    caminho VARCHAR(500) NOT NULL,
    tamanho INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instalacao_id) REFERENCES instalacoes(id) ON DELETE CASCADE,
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE CASCADE,
    FOREIGN KEY (comodo_id) REFERENCES comodos(id) ON DELETE CASCADE
);

-- TABELA: historico_alteracoes
CREATE TABLE IF NOT EXISTS historico_alteracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabela VARCHAR(50) NOT NULL,
    registro_id INT NOT NULL,
    campo VARCHAR(100) NOT NULL,
    valor_anterior TEXT,
    valor_novo TEXT,
    usuario_id INT,
    data_alteracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- TABELA: notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensagem TEXT NOT NULL,
    tipo ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    lida BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- TABELA: activity_logs
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    acao VARCHAR(100) NOT NULL,
    tabela VARCHAR(50),
    registro_id INT,
    detalhes TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- TABELA: user_obra_permissions
CREATE TABLE IF NOT EXISTS user_obra_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    obra_id INT NOT NULL,
    permissao ENUM('leitura', 'edicao', 'administracao') DEFAULT 'leitura',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_obra (usuario_id, obra_id)
);

-- ========================================
-- INSERIR DADOS INICIAIS
-- ========================================

-- Inserir tipos de esquadria básicos
INSERT IGNORE INTO tipos_esquadria (nome, descricao, categoria) VALUES
('Janela', 'Janela de vidro com moldura', 'Vidro'),
('Porta', 'Porta interna ou externa', 'Porta'),
('Porta de Correr', 'Porta de correr com trilho', 'Porta'),
('Janela de Correr', 'Janela de correr com trilho', 'Vidro'),
('Box de Banheiro', 'Box para banheiro', 'Vidro'),
('Guarda-Corpo', 'Proteção para sacadas', 'Proteção'),
('Tela', 'Tela mosquiteira', 'Proteção');

-- Inserir usuário administrador padrão (senha: admin123)
INSERT IGNORE INTO usuarios (username, password, nome, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin@sistema.com', 'gestor');

-- ========================================
-- VERIFICAR INSTALACAO
-- ========================================

-- Verificar todas as tabelas criadas
SELECT 'TABELAS CRIADAS:' as Info;
SHOW TABLES;

-- Verificar estrutura das tabelas principais
SELECT 'ESTRUTURA DAS TABELAS:' as Info;
DESCRIBE usuarios;
DESCRIBE obras;
DESCRIBE funcionarios;
DESCRIBE instalacoes;

-- Verificar dados inseridos
SELECT 'DADOS INICIAIS:' as Info;
SELECT COUNT(*) as Total_Usuarios FROM usuarios;
SELECT COUNT(*) as Total_Tipos_Esquadria FROM tipos_esquadria;

-- ========================================
-- TESTE DE CONEXAO
-- ========================================
SELECT 
    'TESTE FINAL:' as Status,
    'Banco configurado com sucesso!' as Mensagem,
    NOW() as Data_Configuracao;

-- ========================================
-- INSTRUCOES FINAIS
-- ========================================
-- 
-- ✅ Banco configurado com sucesso!
-- 
-- Agora você pode:
-- 1. Executar o iniciar-sistema.bat
-- 2. Acessar http://localhost:8000
-- 3. Fazer login com:
--    - Usuario: admin
--    - Senha: admin123
-- 
-- ========================================
