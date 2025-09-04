# Sistema de Instalação de Esquadrias

Sistema web completo para gerenciamento de instalações de esquadrias (janelas, portas, etc.) em obras de construção civil.

## 🏗️ Funcionalidades

### 📊 Dashboard
- Visão geral das obras e instalações
- Estatísticas em tempo real
- Notificações do sistema
- Atividades recentes

### 🏢 Gerenciamento de Obras
- Cadastro e edição de obras
- Controle de status (Planejada, Em Andamento, Em Finalização, Concluída, Pausada)
- Acompanhamento de progresso
- Gestão de clientes e datas

### 👥 Funcionários
- Cadastro de equipe de instalação
- Controle de funções e especialidades
- Status ativo/inativo
- Histórico de trabalhos

### 👤 Usuários do Sistema
- Gestores e funcionários
- Controle de permissões por obra
- Gerenciamento de perfis
- Alteração de senhas

### 🔧 Instalações
- Controle de cômodos por obra
- Status de instalação (Não Instalado, Em Instalação, Instalado)
- Atribuição de funcionários
- Observações e detalhes técnicos

### 📈 Relatórios
- Gráficos interativos com Chart.js
- Estatísticas por período
- Performance de funcionários
- Progresso das obras
- Exportação de dados

### 📸 Gestão de Fotos
- Upload de fotos das instalações
- Organização por obra e cômodo
- Galeria com lightbox
- Categorização por tipo (Antes, Durante, Depois, Problema, etc.)
- Drag & drop para upload

## 🛠️ Tecnologias Utilizadas

- **Backend**: PHP 7.4+
- **Banco de Dados**: MySQL/MariaDB
- **Frontend**: Bootstrap 5.3, HTML5, CSS3, JavaScript
- **Gráficos**: Chart.js
- **Ícones**: Font Awesome 6.0
- **Galeria**: Lightbox2
- **Segurança**: CSRF protection, password hashing, session management

## 📋 Requisitos do Sistema

- PHP 7.4 ou superior
- MySQL 5.7 ou MariaDB 10.2+
- Extensões PHP: PDO, PDO_MySQL, GD (para manipulação de imagens)
- Servidor web (Apache/Nginx)
- 100MB de espaço em disco (mínimo)

## 🚀 Instalação

### 1. Clone o Repositório
```bash
git clone [URL_DO_REPOSITORIO]
cd sistema-esquadrias
```

### 2. Configure o Banco de Dados
- Crie um banco de dados MySQL
- Importe o arquivo `database/schema.sql`
- Configure as credenciais em `config/database.php`

### 3. Configure o Servidor Web
- Configure o servidor para apontar para o diretório do projeto
- Certifique-se de que o diretório `uploads/fotos/` tenha permissões de escrita

### 4. Acesse o Sistema
- Acesse `http://seu-dominio.com`
- Use as credenciais padrão:
  - **Usuário**: admin
  - **Senha**: admin123

## ⚙️ Configuração

### Banco de Dados
Edite o arquivo `config/database.php`:

```php
<?php
$host = 'localhost';
$dbname = 'esquadrias_db';
$username = 'seu_usuario';
$password = 'sua_senha';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
?>
```

### Permissões de Diretório
```bash
chmod 755 uploads/
chmod 755 uploads/fotos/
```

## 🔐 Segurança

### Recursos Implementados
- **Autenticação**: Sistema de login seguro
- **Autorização**: Controle de acesso baseado em perfis
- **CSRF Protection**: Proteção contra ataques CSRF
- **SQL Injection**: Uso de prepared statements
- **XSS Protection**: Escape de dados de saída
- **Session Security**: Gerenciamento seguro de sessões

### Boas Práticas
- Altere a senha padrão do admin
- Use HTTPS em produção
- Configure firewall adequadamente
- Mantenha o sistema atualizado

## 📱 Interface do Usuário

### Design Responsivo
- Interface adaptável para desktop, tablet e mobile
- Sidebar colapsável
- Cards com hover effects
- Modais para ações importantes

### Navegação
- Menu lateral com ícones intuitivos
- Breadcrumbs para orientação
- Filtros avançados
- Busca em tempo real

## 📊 Estrutura do Banco de Dados

### Tabelas Principais
- **users**: Usuários do sistema
- **obras**: Projetos de construção
- **comodos**: Cômodos das obras
- **funcionarios**: Equipe de instalação
- **instalacoes**: Registro de instalações
- **fotos**: Documentação fotográfica
- **notifications**: Sistema de notificações
- **activity_logs**: Log de atividades

## 🔄 Funcionalidades Avançadas

### Sistema de Notificações
- Notificações em tempo real
- Marcação de leitura
- Histórico de notificações

### Log de Atividades
- Registro de todas as ações
- Rastreabilidade completa
- Auditoria do sistema

### Permissões Granulares
- Controle de acesso por obra
- Perfis de usuário flexíveis
- Restrições por funcionalidade

## 📈 Relatórios Disponíveis

### Estatísticas Gerais
- Total de obras e funcionários
- Progresso das instalações
- Performance da equipe

### Gráficos Interativos
- Status das obras (doughnut chart)
- Instalações por mês (line chart)
- Funcionários por função (bar chart)
- Top funcionários (horizontal bar chart)

### Filtros Avançados
- Período personalizado
- Obra específica
- Tipo de instalação
- Status de progresso

## 🖼️ Sistema de Fotos

### Recursos
- Upload múltiplo de formatos (JPG, PNG, GIF)
- Drag & drop para upload
- Preview antes do envio
- Organização por categoria
- Galeria com lightbox
- Compressão automática

### Categorias
- Antes da Instalação
- Durante a Instalação
- Depois da Instalação
- Problemas/Defeitos
- Detalhes Técnicos

## 🚀 Deploy em Produção

### Configurações Recomendadas
- **PHP**: 8.0+ com OPcache habilitado
- **MySQL**: 8.0+ com configurações otimizadas
- **Servidor**: Nginx com PHP-FPM
- **SSL**: Certificado válido
- **Backup**: Automatizado diário

### Otimizações
- Compressão de imagens
- Cache de consultas
- Minificação de CSS/JS
- CDN para recursos estáticos

## 🐛 Solução de Problemas

### Problemas Comuns
1. **Erro de conexão com banco**: Verifique credenciais e permissões
2. **Upload de fotos falha**: Verifique permissões do diretório uploads/
3. **Página em branco**: Verifique logs de erro do PHP
4. **Sessão expira**: Ajuste configurações de sessão

### Logs
- Erros PHP: `/var/log/php_errors.log`
- Erros MySQL: `/var/log/mysql/error.log`
- Logs do sistema: `includes/functions.php` (função logActivity)

## 🤝 Contribuição

### Como Contribuir
1. Fork o projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

### Padrões de Código
- PSR-12 para PHP
- Comentários em português
- Nomes de variáveis descritivos
- Tratamento de erros adequado

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo LICENSE para mais detalhes.

## 📞 Suporte

### Contato
- **Email**: suporte@empresa.com
- **Documentação**: [URL_DOCS]
- **Issues**: GitHub Issues

### Comunidade
- Fórum de discussão
- Canal no Discord
- Grupo no WhatsApp

## 🔮 Roadmap

### Versão 2.0
- [ ] App mobile nativo
- [ ] API REST completa
- [ ] Integração com WhatsApp
- [ ] Sistema de orçamentos
- [ ] Relatórios em PDF

### Versão 1.5
- [ ] Dashboard personalizável
- [ ] Notificações push
- [ ] Backup automático
- [ ] Multi-idioma
- [ ] Temas personalizáveis

---

**Desenvolvido com ❤️ para a indústria da construção civil**

*Sistema de Instalação de Esquadrias - Versão 1.0*
