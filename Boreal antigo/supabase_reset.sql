-- Script de Reset do Banco de Dados Supabase
-- Este script irá apagar o schema public e todos os seus objetos, e depois recriá-lo com base no esquema fornecido.
-- ATENÇÃO: Isso irá apagar permanentemente todos os dados no seu schema public.
-- Para usar este script, copie e cole o seu conteúdo no editor SQL do Supabase.

-- 1. Apagar o schema public
DROP SCHEMA public CASCADE;

-- 2. Recriar o schema public
CREATE SCHEMA public;

-- 3. Conceder permissões ao novo schema
GRANT USAGE ON SCHEMA public TO postgres, anon, authenticated, service_role;

-- 4. Executar o script de criação do esquema

-- Função para atualizar a coluna updated_at
CREATE OR REPLACE FUNCTION trigger_set_timestamp()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Tabela: users
CREATE TABLE IF NOT EXISTS "users" (
    "id" SERIAL PRIMARY KEY,
    "username" VARCHAR(50) UNIQUE NOT NULL,
    "password" VARCHAR(255) NOT NULL,
    "nome" VARCHAR(100) NOT NULL,
    "email" VARCHAR(100),
    "telefone" VARCHAR(20),
    "role" VARCHAR(20) NOT NULL DEFAULT 'funcionario' CHECK ("role" IN ('gestor', 'funcionario')),
    "active" BOOLEAN DEFAULT TRUE,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TRIGGER set_timestamp
BEFORE UPDATE ON "users"
FOR EACH ROW
EXECUTE PROCEDURE trigger_set_timestamp();

-- Tabela: obras
CREATE TABLE IF NOT EXISTS "obras" (
    "id" SERIAL PRIMARY KEY,
    "nome" VARCHAR(200) NOT NULL,
    "endereco" TEXT NOT NULL,
    "cliente" VARCHAR(200) NOT NULL,
    "data_inicio" DATE,
    "tipo_construcao" VARCHAR(100),
    "num_pavimentos" INT,
    "num_unidades" INT,
    "status" VARCHAR(20) DEFAULT 'planejada' CHECK ("status" IN ('planejada', 'em_andamento', 'em_finalizacao', 'concluida', 'pausada')),
    "observacoes" TEXT,
    "created_by" INT,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "usa_hierarquia_locais" BOOLEAN DEFAULT FALSE,
    FOREIGN KEY ("created_by") REFERENCES "users"("id") ON DELETE SET NULL
);
CREATE TRIGGER set_timestamp
BEFORE UPDATE ON "obras"
FOR EACH ROW
EXECUTE PROCEDURE trigger_set_timestamp();

-- Tabela: locais (para estrutura hierárquica)
CREATE TABLE IF NOT EXISTS "locais" (
    "id" SERIAL PRIMARY KEY,
    "id_obra" INT NOT NULL,
    "parent_id" INT,
    "nome" VARCHAR(255) NOT NULL,
    "tipo" VARCHAR(50) NOT NULL, -- ex: 'bloco', 'andar', 'apartamento', 'comodo'
    "created_at" TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("id_obra") REFERENCES "obras"("id") ON DELETE CASCADE,
    FOREIGN KEY ("parent_id") REFERENCES "locais"("id") ON DELETE CASCADE
);
CREATE TRIGGER set_timestamp
BEFORE UPDATE ON "locais"
FOR EACH ROW
EXECUTE PROCEDURE trigger_set_timestamp();

-- Tabela: comodos (legado)
CREATE TABLE IF NOT EXISTS "comodos" (
    "id" SERIAL PRIMARY KEY,
    "obra_id" INT NOT NULL,
    "nome" VARCHAR(100) NOT NULL,
    "tipo_esquadria" VARCHAR(100) NOT NULL,
    "modelo" VARCHAR(100),
    "dimensoes" VARCHAR(100),
    "status" VARCHAR(20) DEFAULT 'nao_instalado' CHECK ("status" IN ('nao_instalado', 'instalado', 'em_instalacao')),
    "observacao" TEXT,
    "data_instalacao" TIMESTAMP,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("obra_id") REFERENCES "obras"("id") ON DELETE CASCADE
);
CREATE TRIGGER set_timestamp
BEFORE UPDATE ON "comodos"
FOR EACH ROW
EXECUTE PROCEDURE trigger_set_timestamp();

-- Tabela: funcionarios
CREATE TABLE IF NOT EXISTS "funcionarios" (
    "id" SERIAL PRIMARY KEY,
    "nome" VARCHAR(100) NOT NULL,
    "funcao" VARCHAR(100) NOT NULL,
    "telefone" VARCHAR(20),
    "email" VARCHAR(100),
    "active" BOOLEAN DEFAULT TRUE,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TRIGGER set_timestamp
BEFORE UPDATE ON "funcionarios"
FOR EACH ROW
EXECUTE PROCEDURE trigger_set_timestamp();

-- Tabela: instalacoes
CREATE TABLE IF NOT EXISTS "instalacoes" (
    "id" SERIAL PRIMARY KEY,
    "comodo_id" INT NOT NULL,
    "funcionario_id" INT NOT NULL,
    "data_instalacao" TIMESTAMP NOT NULL,
    "observacoes" TEXT,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela: fotos
CREATE TABLE IF NOT EXISTS "fotos" (
    "id" SERIAL PRIMARY KEY,
    "obra_id" INT NOT NULL,
    "comodo_id" INT,
    "titulo" VARCHAR(255) NOT NULL,
    "descricao" TEXT,
    "tipo" VARCHAR(50),
    "filename" VARCHAR(255) NOT NULL,
    "uploaded_by" INT,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("obra_id") REFERENCES "obras"("id") ON DELETE CASCADE,
    FOREIGN KEY ("comodo_id") REFERENCES "comodos"("id") ON DELETE CASCADE,
    FOREIGN KEY ("uploaded_by") REFERENCES "users"("id") ON DELETE SET NULL
);

-- Tabela: tipos_esquadria
CREATE TABLE IF NOT EXISTS "tipos_esquadria" (
    "id" SERIAL PRIMARY KEY,
    "obra_id" INT NOT NULL,
    "nome" VARCHAR(100) NOT NULL,
    "descricao" TEXT,
    "especificacoes" TEXT,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("obra_id") REFERENCES "obras"("id") ON DELETE CASCADE
);

-- Tabela: historico_alteracoes
CREATE TABLE IF NOT EXISTS "historico_alteracoes" (
    "id" SERIAL PRIMARY KEY,
    "tabela" VARCHAR(50) NOT NULL,
    "registro_id" INT NOT NULL,
    "campo" VARCHAR(50) NOT NULL,
    "valor_anterior" TEXT,
    "valor_novo" TEXT,
    "user_id" INT,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE SET NULL
);

-- Tabela: notifications
CREATE TABLE IF NOT EXISTS "notifications" (
    "id" SERIAL PRIMARY KEY,
    "user_id" INT NOT NULL,
    "title" VARCHAR(200) NOT NULL,
    "message" TEXT NOT NULL,
    "type" VARCHAR(20) DEFAULT 'info' CHECK ("type" IN ('info', 'warning', 'error', 'success')),
    "read_status" BOOLEAN DEFAULT FALSE,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE CASCADE
);

-- Tabela: activity_logs
CREATE TABLE IF NOT EXISTS "activity_logs" (
    "id" SERIAL PRIMARY KEY,
    "user_id" INT,
    "action" VARCHAR(100) NOT NULL,
    "details" TEXT,
    "ip_address" VARCHAR(45),
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE SET NULL
);

-- Tabela: user_obra_permissions
CREATE TABLE IF NOT EXISTS "user_obra_permissions" (
    "id" SERIAL PRIMARY KEY,
    "user_id" INT NOT NULL,
    "obra_id" INT NOT NULL,
    "can_view" BOOLEAN DEFAULT TRUE,
    "can_edit" BOOLEAN DEFAULT FALSE,
    "can_install" BOOLEAN DEFAULT FALSE,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE CASCADE,
    FOREIGN KEY ("obra_id") REFERENCES "obras"("id") ON DELETE CASCADE,
    UNIQUE ("user_id", "obra_id")
);

-- Índices
CREATE INDEX IF NOT EXISTS "idx_obras_status" ON "obras"("status");
CREATE INDEX IF NOT EXISTS "idx_comodos_obra_id" ON "comodos"("obra_id");
CREATE INDEX IF NOT EXISTS "idx_comodos_status" ON "comodos"("status");
CREATE INDEX IF NOT EXISTS "idx_instalacoes_comodo_id" ON "instalacoes"("comodo_id");
CREATE INDEX IF NOT EXISTS "idx_instalacoes_funcionario_id" ON "instalacoes"("funcionario_id");
CREATE INDEX IF NOT EXISTS "idx_fotos_comodo_id" ON "fotos"("comodo_id");
CREATE INDEX IF NOT EXISTS "idx_notifications_user_id" ON "notifications"("user_id");
CREATE INDEX IF NOT EXISTS "idx_notifications_read_status" ON "notifications"("read_status");
CREATE INDEX IF NOT EXISTS "idx_activity_logs_user_id" ON "activity_logs"("user_id");
CREATE INDEX IF NOT EXISTS "idx_activity_logs_created_at" ON "activity_logs"("created_at");
CREATE INDEX IF NOT EXISTS "idx_locais_id_obra" ON "locais"("id_obra");
CREATE INDEX IF NOT EXISTS "idx_locais_parent_id" ON "locais"("parent_id");

-- Dados padrão
INSERT INTO "users" ("username", "password", "nome", "email", "role") VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin@boreal.com', 'gestor');

INSERT INTO "funcionarios" ("nome", "funcao", "telefone", "email") VALUES 
('João Silva', 'Instalador', '(11) 99999-9999', 'joao@boreal.com'),
('Maria Santos', 'Auxiliar de Instalação', '(11) 88888-8888', 'maria@boreal.com');

-- Conceder privilégios em todas as tabelas no schema public
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO postgres, anon, authenticated, service_role;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO postgres, anon, authenticated, service_role;
GRANT ALL PRIVILEGES ON ALL FUNCTIONS IN SCHEMA public TO postgres, anon, authenticated, service_role;