-- Script para corrigir erros e avisos do Supabase

-- 1. Corrigir a função trigger_set_timestamp com search_path
-- Define um search_path fixo para a função, evitando possíveis vulnerabilidades.
CREATE OR REPLACE FUNCTION trigger_set_timestamp()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql
SET search_path = public;

-- 2. Habilitar Row-Level Security (RLS) para todas as tabelas
-- O RLS é uma camada de segurança essencial no Supabase para controlar o acesso aos dados.
ALTER TABLE "obras" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "users" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "locais" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "comodos" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "funcionarios" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "instalacoes" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "fotos" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "tipos_esquadria" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "historico_alteracoes" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "notifications" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "activity_logs" ENABLE ROW LEVEL SECURITY;
ALTER TABLE "user_obra_permissions" ENABLE ROW LEVEL SECURITY;

-- 3. Criar políticas permissivas para todas as tabelas
-- ATENÇÃO: Estas são políticas permissivas que garantem que sua aplicação continue funcionando.
-- Para um ambiente de produção, você pode querer criar regras mais restritivas no futuro.
CREATE POLICY "Permitir acesso total para todos os usuários" ON "obras" FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Permitir acesso total para todos os usuários" ON "users" FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Permitir acesso total para todos os usuários" ON "locais" FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Permitir acesso total para todos os usuários" ON "comodos" FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Permitir acesso total para todos os usuários" ON "funcionarios" FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Permitir acesso total para todos os usuários" ON "instalacoes" FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Permitir acesso total para todos os usuários" ON "fotos" FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Permitir acesso total para todos os usuários" ON "tipos_esquadria" FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Permitir acesso total para todos os usuários" ON "historico_alteracoes" FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Permitir acesso total para todos os usuários" ON "notifications" FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Permitir acesso total para todos os usuários" ON "activity_logs" FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Permitir acesso total para todos os usuários" ON "user_obra_permissions" FOR ALL USING (true) WITH CHECK (true);
