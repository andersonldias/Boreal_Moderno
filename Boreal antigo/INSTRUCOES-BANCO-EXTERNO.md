# 🗄️ INSTRUÇÕES PARA CONFIGURAR BANCO EXTERNO

## 🎯 **OBJETIVO**
Configurar o banco de dados externo `xmysql.bichosdobairro.com.br` para o Sistema de Instalação de Esquadrias.

## 📋 **DADOS DE CONEXÃO**
```
DB_HOST=xmysql.bichosdobairro.com.br
DB_NAME=bichosdobairro2
DB_USER=bichosdobairro2
DB_PASS=!Boreal.123456
DB_CHARSET=utf8mb4
DB_PORT=3306
```

## 🚀 **OPÇÃO 1: MySQL Workbench (RECOMENDADO)**

### **Pré-requisitos:**
- ✅ MySQL Workbench instalado
- ✅ Acesso à internet
- ✅ Credenciais do banco válidas

### **Passos:**

#### **1. Criar Nova Conexão:**
- Abra o MySQL Workbench
- Clique em **"+"** para nova conexão
- Configure:
  - **Connection Name:** `Bichos do Bairro - Externo`
  - **Hostname:** `xmysql.bichosdobairro.com.br`
  - **Port:** `3306`
  - **Username:** `bichosdobairro2`
  - **Password:** `!Boreal.123456`
- Clique em **"Test Connection"** para verificar
- Clique em **"OK"** para salvar

#### **2. Conectar ao Banco:**
- Clique na conexão criada
- Digite a senha: `!Boreal.123456`
- Clique em **"OK"**

#### **3. Executar Script de Configuração:**
- Abra o arquivo `setup-external-database.sql`
- Execute todo o script (Ctrl+Shift+Enter)
- Aguarde a execução completa

#### **4. Verificar Instalação:**
```sql
-- Verificar se o banco existe
SHOW DATABASES;

-- Usar o banco
USE bichosdobairro2;

-- Verificar tabelas criadas
SHOW TABLES;

-- Verificar dados iniciais
SELECT COUNT(*) as Total_Usuarios FROM usuarios;
SELECT COUNT(*) as Total_Tipos_Esquadria FROM tipos_esquadria;
```

## 🔧 **OPÇÃO 2: phpMyAdmin (Se disponível)**

### **Passos:**
1. Acesse o phpMyAdmin do servidor externo
2. Faça login com as credenciais:
   - Usuário: `bichosdobairro2`
   - Senha: `!Boreal.123456`
3. Selecione o banco `bichosdobairro2`
4. Importe o arquivo `setup-external-database.sql`

## 🔧 **OPÇÃO 3: Linha de Comando MySQL**

### **Via SSH (se tiver acesso):**
```bash
mysql -h xmysql.bichosdobairro.com.br -u bichosdobairro2 -p bichosdobairro2 < setup-external-database.sql
```

### **Via MySQL Client:**
```bash
mysql -h xmysql.bichosdobairro.com.br -u bichosdobairro2 -p bichosdobairro2
```
Depois execute: `source setup-external-database.sql;`

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
- Verifique se o servidor está acessível
- Confirme as credenciais
- Verifique se a porta 3306 está aberta
- Teste a conexão no Workbench primeiro

### **Erro de Permissão:**
- Certifique-se de que o usuário tem privilégios
- Verifique se o banco existe
- Confirme se o usuário tem acesso ao banco

### **Erro de Schema:**
- Execute novamente o script
- Verifique se não há erros de sintaxe SQL
- Confirme se todas as tabelas foram criadas

### **Problemas de Rede:**
- Verifique a conectividade com o servidor
- Teste ping: `ping xmysql.bichosdobairro.com.br`
- Verifique firewall/proxy

## 📞 **SUPORTE**

Se encontrar problemas:
1. Teste a conexão no MySQL Workbench
2. Verifique os logs de erro
3. Confirme se as credenciais estão corretas
4. Teste a conectividade de rede

## 🔄 **ALTERNATIVAS**

### **Se o banco externo não funcionar:**
1. Use a configuração local (`database.local.php`)
2. Execute `setup-local.bat` para criar banco local
3. Use XAMPP/WAMP local

---

**🎉 Banco externo configurado com sucesso!**

**Próximo passo:** Execute `iniciar-sistema.bat` e teste o sistema!
