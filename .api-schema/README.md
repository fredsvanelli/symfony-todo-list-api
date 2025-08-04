# Tasks Checklist API - Bruno Collection

Esta coleção contém todos os endpoints da API de Tasks Checklist para uso com o Bruno.

## 📋 Endpoints Disponíveis

### 🔐 Autenticação
1. **Register user** - `POST /api/auth/register`
   - Registra um novo usuário
   - Não requer autenticação

2. **Login user** - `POST /api/auth/login`
   - Faz login do usuário e retorna JWT token
   - Não requer autenticação
   - **Resposta inclui**: `token`, `message`, `user`
   - **🔧 Semi-automático**: Valida o token e mostra instruções para copiá-lo

### 📊 Status & Health
3. **Status check** - `GET /api/status`
   - Verifica o status da API
   - Não requer autenticação

4. **Health check** - `GET /api/health`
   - Verifica a saúde da API e conectividade com banco
   - Não requer autenticação

### ✅ Tasks (Requerem Autenticação)
5. **Lists tasks** - `GET /api/tasks`
   - Lista todas as tasks do usuário autenticado (paginação)
   - Requer JWT token
   - **10 tasks por página**

6. **List tasks page 2** - `GET /api/tasks?page=2`
   - Lista a segunda página de tasks
   - Requer JWT token
   - **Exemplo de paginação**

7. **Create task** - `POST /api/tasks`
   - Cria uma nova task
   - Requer JWT token

8. **Get task** - `GET /api/tasks/{id}`
   - Obtém uma task específica
   - Requer JWT token

9. **Update task** - `PATCH /api/tasks/{id}`
   - Atualiza uma task (atualização parcial)
   - Requer JWT token

10. **Delete task** - `DELETE /api/tasks/{id}`
    - Remove uma task
    - Requer JWT token

## 🔧 Como Usar

### 1. Instalar Bruno
```bash
npm install -g @usebruno/cli
```

### 2. Abrir a Coleção
```bash
bruno open .api-schema
```

### 3. Fluxo de Uso

#### Passo 1: Registrar Usuário
1. Execute "Register user"
2. Use um email válido e senha com pelo menos 6 caracteres

#### Passo 2: Fazer Login
1. Execute "Login user"
2. Use as mesmas credenciais do registro
3. **Copie o token JWT** da resposta (campo `token`)
4. **Cole o token** na variável `jwt_token` em `environments/local.bru`

#### Passo 3: Usar Endpoints de Tasks
Agora você pode executar todos os endpoints de tasks que requerem autenticação.

## 🔧 Scripts de Validação

### Login User - Validação de Token

O endpoint "Login user" inclui testes que:

#### **Validação de Resposta:**
- Verifica se o status é 200
- Confirma que o token foi retornado
- Valida que o token é uma string válida

#### **Validação de Formato JWT:**
- Verifica se o token tem o formato correto (3 partes separadas por pontos)
- Exibe preview do token no console
- Fornece instruções para copiar o token

### Como Funciona:

1. **Execute o login** → Testes validam a resposta
2. **Copie o token** → Da resposta para `environments/local.bru`
3. **Token disponível** → Como `{{jwt_token}}` em todos os outros requests
4. **Headers automáticos** → `Authorization: Bearer {{jwt_token}}` é aplicado

## 📄 Paginação

A API de tasks suporta paginação com as seguintes características:

### Parâmetros
- **page** (opcional): Número da página (padrão: 1)
- **items_per_page**: Fixo em 10 itens por página

### Estrutura da Resposta
O objeto de paginação contém:
- **current_page**: Número da página atual
- **total_pages**: Total de páginas
- **total_items**: Total de tasks
- **items_per_page**: Itens por página (10)
- **has_next_page**: Boolean indicando se há próxima página
- **has_previous_page**: Boolean indicando se há página anterior
- **next_page**: Número da próxima página (null se não houver)
- **previous_page**: Número da página anterior (null se não houver)

### Exemplos de Uso

#### Primeira Página (padrão)
```bash
curl -k https://localhost/api/tasks
```

#### Segunda Página
```bash
curl -k "https://localhost/api/tasks?page=2"
```

#### Última Página
```bash
curl -k "https://localhost/api/tasks?page=3"
```

## 📝 Exemplos de Uso

### Registrar Usuário
```json
{
  "email": "user@example.com",
  "password": "123456"
}
```

### Fazer Login (Resposta)
```json
{
  "message": "Login successful",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "user": {
    "id": 2,
    "email": "user@example.com"
  }
}
```

### Criar Task
```json
{
  "title": "Minha nova task",
  "description": "Descrição da task",
  "isDone": false
}
```

### Atualizar Task
```json
{
  "title": "Task atualizada",
  "isDone": true
}
```

### Resposta Paginada
```json
{
  "tasks": [
    {
      "id": 1,
      "title": "Task 1",
      "description": "Descrição",
      "isDone": false,
      "createdAt": "2025-08-03T18:30:30+00:00",
      "updatedAt": "2025-08-03T18:30:30+00:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 3,
    "total_items": 25,
    "items_per_page": 10,
    "has_next_page": true,
    "has_previous_page": false,
    "next_page": 2,
    "previous_page": null
  }
}
```

## 🔒 Autenticação

Todos os endpoints de tasks requerem autenticação JWT. O token deve ser incluído no header:

```
Authorization: Bearer {{jwt_token}}
```

### 🔧 Configuração do Token

**Passo a passo:**

1. Execute o endpoint "Login user"
2. **Copie o token** da resposta (campo `token`)
3. **Abra** `environments/local.bru`
4. **Cole o token** na variável `jwt_token`:

```javascript
vars {
  jwt_token: "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
  base_url: "https://localhost"
}
```

**Logs do console:**
```
✅ JWT token received successfully
Token preview: eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
📋 Please copy the token from the response and set it in environments/local.bru
```

## 🚨 Tratamento de Erros

### 401 Unauthorized
- Token JWT inválido ou expirado
- Credenciais incorretas no login

### 403 Forbidden
- Tentativa de acessar task de outro usuário

### 400 Bad Request
- Dados inválidos na requisição
- Validação falhou

### 422 Unprocessable Entity
- Erros de validação nos dados

## 🔄 Atualizações

Esta coleção é atualizada automaticamente quando novos endpoints são adicionados à API. Mantenha os arquivos sincronizados com a documentação da API.

## 📁 Estrutura dos Arquivos

```
.api-schema/
├── bruno.json                 # Configuração da coleção
├── environments/
│   └── local.bru             # Variáveis de ambiente
├── Register user.bru          # Registro de usuário
├── Login user.bru             # Login de usuário (com validação)
├── Status check.bru           # Status da API
├── Health check.bru           # Health check
├── Lists tasks.bru            # Listar tasks (paginação)
├── List tasks page 2.bru      # Listar tasks página 2
├── Create task.bru            # Criar task
├── Get task.bru               # Obter task
├── Update task.bru            # Atualizar task
├── Delete task.bru            # Deletar task
└── README.md                  # Este arquivo
``` 
