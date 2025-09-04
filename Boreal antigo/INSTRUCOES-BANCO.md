# 📋 INSTRUÇÕES PARA CONFIGURAR O BANCO DE DADOS

## 🎯 **OBJETIVO**
Configurar o banco de dados `bichosdobairro2` localmente para o Sistema de Instalação de Esquadrias.

## 🚀 **OPÇÃO 1: Script Automático (.bat)**

### **Pré-requisitos:**
- ✅ XAMPP ou WAMP instalado
- ✅ MySQL rodando na porta 3306
- ✅ PHP no PATH do sistema

### **Passos:**
1. **Inicie o XAMPP/WAMP:**
   - Abra o painel de controle
   - Inicie o MySQL
   - (Opcional) Inicie o Apache

2. **Execute o script:**
   - Dê duplo clique em `setup-local.bat`
   - Aguarde a configuração automática

3. **Verifique se funcionou:**
   - Deve aparecer "CONFIGURACAO CONCLUIDA!"
   - Banco `bichosdobairro2` criado
   - Schema importado

## 🗄️ **OPÇÃO 2: MySQL Workbench (Manual)**

### **Pré-requisitos:**
- ✅ MySQL Workbench instalado
- ✅ Servidor MySQL rodando (localhost:3306)

### **Passos:**

#### **1. Criar o Banco:**
- Abra o MySQL Workbench
- Conecte ao servidor local (localhost:3306)
- Execute o arquivo `setup-database.sql`
- Verifique se o banco foi criado

#### **2. Importar o Schema:**
- Execute o arquivo `import-schema.sql`
- Este arquivo contém todas as tabelas e dados iniciais

#### **3. Verificar a Instalação:**
```sql
-- Verificar se o banco existe
SHOW DATABASES;

-- Usar o banco
USE bichosdobairro2;

-- Verificar tabelas criadas
SHOW TABLES;

-- Verificar estrutura das tabelas principais
DESCRIBE usuarios;
DESCRIBE obras;
DESCRIBE funcionarios;
```

## 🔧 **OPÇÃO 3: phpMyAdmin (Se disponível)**

### **Passos:**
1. Acesse `http://localhost/phpmyadmin`
2. Clique em "Novo" para criar banco
3. Nome: `bichosdobairro2`
4. Collation: `utf8mb4_unicode_ci`
5. Importe o arquivo `import-schema.sql`

## 📊 **ESTRUTURA DO BANCO**

### **Tabelas Principais:**
- **`usuarios`** - Usuários do sistema (gestores e funcionários)
- **`obras`** - Projetos de construção
- **`comodos`** - Cômodos das obras
- **`funcionarios`** - Equipe de trabalho
- **`instalacoes`** - Instalações de esquadrias
- **`fotos`** - Galeria de fotos das instalações
- **`tipos_esquadria`** - Tipos de esquadrias disponíveis

### **Tabelas de Suporte:**
- **`historico_alteracoes`** - Log de mudanças
- **`notifications`** - Notificações do sistema
- **`activity_logs`** - Log de atividades
- **`user_obra_permissions`** - Permissões de usuários

## 🔑 **CREDENCIAIS PADRÃO**

### **Usuário Administrador:**
- **Username:** `admin`
- **Password:** `admin123`
- **Role:** `gestor`

## ✅ **VERIFICAÇÃO FINAL**

### **Após configurar o banco:**
1. Execute `iniciar-sistema.bat`
2. Acesse `http://localhost:8000` (ou 8001)
3. Faça login com as credenciais padrão
4. Verifique se o dashboard carrega corretamente

## 🆘 **SOLUÇÃO DE PROBLEMAS**

### **Erro de Conexão:**
- Verifique se o MySQL está rodando
- Confirme as credenciais (usuário: `root`, senha: vazia)
- Verifique se a porta 3306 está livre

### **Erro de Permissão:**
- Certifique-se de que o usuário `root` tem privilégios
- Verifique se o banco foi criado corretamente

### **Erro de Schema:**
- Execute novamente o `import-schema.sql`
- Verifique se não há erros de sintaxe SQL

## 📞 **SUPORTE**

Se encontrar problemas:
1. Verifique os logs do MySQL
2. Confirme se todos os pré-requisitos estão atendidos
3. Execute os scripts na ordem correta

---

**🎉 Sistema configurado com sucesso!**
