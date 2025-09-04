# 🔧 Configuração do Supabase no Vercel

## Problema Identificado
O aplicativo está apresentando erros 404 e 400 porque as variáveis de ambiente do Supabase não estão configuradas no Vercel.

## Erros Encontrados
- ❌ **Erro 404**: `nplwljglrgttagcugjlz.supabase.co/auth/v1/token` - URL incorreta
- ❌ **Erro 400**: Requisição inválida - Cliente Supabase não inicializado

## Solução: Configurar Variáveis de Ambiente no Vercel

### Passo 1: Obter Credenciais do Supabase
1. Acesse [https://supabase.com/dashboard](https://supabase.com/dashboard)
2. Selecione seu projeto
3. Vá em **Settings** > **API**
4. Copie os seguintes valores:
   - **Project URL** (exemplo: `https://abcdefghijklmnop.supabase.co`)
   - **anon public** key (chave longa que começa com `eyJ...`)

### Passo 2: Configurar no Vercel
1. Acesse seu projeto no [Vercel Dashboard](https://vercel.com/dashboard)
2. Vá em **Settings** > **Environment Variables**
3. Adicione as seguintes variáveis:

| Nome | Valor | Ambiente |
|------|-------|----------|
| `NEXT_PUBLIC_SUPABASE_URL` | `https://seu-projeto.supabase.co` | Production, Preview, Development |
| `NEXT_PUBLIC_SUPABASE_ANON_KEY` | `eyJ...` (sua chave anônima) | Production, Preview, Development |

### Passo 3: Redeploy
Após configurar as variáveis:
1. Vá em **Deployments**
2. Clique nos três pontos do último deployment
3. Selecione **Redeploy**

## Verificação
1. Acesse `/test-supabase` no seu aplicativo
2. Clique em "Executar Teste"
3. Verifique se todos os testes passam

## Estrutura do Projeto
```
src/
├── utils/
│   └── supabase.js          # Configuração do cliente Supabase
├── app/
│   ├── login/
│   │   └── page.js          # Página de login
│   └── test-supabase/
│       └── page.js          # Página de diagnóstico
```

## Código de Configuração
O arquivo `src/utils/supabase.js` foi atualizado para:
- ✅ Verificar variáveis de ambiente
- ✅ Fornecer mensagens de erro claras
- ✅ Usar valores de fallback para desenvolvimento
- ✅ Logs detalhados para debug

## Próximos Passos
1. Configure as variáveis de ambiente no Vercel
2. Faça redeploy do aplicativo
3. Teste o login
4. Se persistir erro, verifique se o projeto Supabase está ativo
