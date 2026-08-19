# InventoryFlow SaaS — Fase 1 explicada

Esta fase transforma a aplicação de um sistema pessoal em uma base **multi-tenant**.

## 1. O que muda ao virar SaaS

Antes:

```text
User -> Products
User -> Orders
User -> Inventory Movements
```

Agora:

```text
User -> Workspace <- outros usuários
             |
             +-> Products
             +-> Orders
             +-> Inventory Movements
```

O `Workspace` representa a empresa / loja / organização que está usando o InventoryFlow.
Ele é a fronteira de dados do tenant.

## 2. Multi-tenancy

Multi-tenancy significa que uma mesma aplicação atende vários clientes, mas os dados de cada cliente permanecem separados.

Exemplo:

```text
Workspace A: Nox Store
- Mouse
- Keyboard

Workspace B: Tech Shop
- Monitor
- Headset
```

Quando um usuário está no Workspace B, os Controllers consultam `workspace_id = B`.
Eles não consultam apenas `user_id`.

Isso é mais correto para SaaS porque uma empresa pode ter vários usuários trabalhando nos mesmos dados.

---

# PASTAS E ARQUIVOS NOVOS

## app/Models/Workspace.php

Representa a tabela `workspaces`.

Responsabilidades:

- identificar uma empresa/tenant;
- saber quem é o owner;
- saber qual plano ela usa;
- relacionar usuários;
- relacionar produtos;
- relacionar pedidos;
- relacionar movimentações.

### Relação com POO / Java

Em Java você aprende:

```java
class Empresa {
    String nome;
}
```

No Laravel:

```php
class Workspace extends Model
```

A linguagem mudou, mas o conceito de classe, objeto, atributo e relacionamento continua.

---

## app/Models/User.php

O usuário agora possui:

```text
current_workspace_id
```

Isso informa em qual workspace ele está trabalhando naquele momento.

Também existe a relação:

```php
public function workspaces(): BelongsToMany
```

Um usuário pode participar de vários workspaces e um workspace pode ter vários usuários.

### Relação com Banco de Dados

Isto é um relacionamento N:N.

```text
users
   \ 
    user_workspace
   /
workspaces
```

A tabela `user_workspace` resolve esse relacionamento.

---

## database/migrations/...create_workspaces_table.php

Cria a tabela:

```text
workspaces
- id PK
- name
- slug
- owner_id FK
- plan
- created_at
- updated_at
```

### Ligação com a aula de banco

- `id` = chave primária;
- `owner_id` = chave estrangeira;
- `users -> workspaces` = relacionamento;
- migration = modelo físico do banco expresso em código.

---

## database/migrations/...create_user_workspace_table.php

Cria a tabela associativa:

```text
user_workspace
- user_id
- workspace_id
- role
```

Exemplo:

```text
user_id = 10
workspace_id = 2
role = manager
```

Significa que o usuário 10 é gerente do workspace 2.

### Relação com normalização

Não colocamos uma lista de usuários dentro de uma coluna do workspace.
Criamos uma tabela própria para representar a associação.

---

## app/Http/Middleware/EnsureWorkspaceSelected.php

Middleware é uma camada executada antes do Controller.

Fluxo:

```text
Request
  |
  v
Auth
  |
  v
EnsureWorkspaceSelected
  |
  v
Controller
```

Ele confirma:

1. existe usuário autenticado;
2. existe workspace selecionado;
3. o usuário realmente pertence a esse workspace.

Se uma dessas condições falhar, a requisição é bloqueada.

### Relação com lógica de programação

É uma sequência de condições:

```text
SE usuário não existe
    bloquear

SE workspace não existe
    bloquear

SE usuário não pertence ao workspace
    bloquear

SENÃO
    continuar
```

É a mesma estrutura lógica estudada em Portugol / Java, aplicada em uma aplicação real.

---

## app/Http/Middleware/EnsureWorkspaceRole.php

Implementa RBAC: Role-Based Access Control.

Papéis usados:

```text
owner
admin
manager
member
viewer
```

Exemplo:

```text
viewer
- pode visualizar
- não pode cadastrar

manager
- pode cadastrar
- pode editar

admin
- pode cadastrar
- editar
- excluir

owner
- controle total
```

O frontend esconde/desabilita algumas ações para UX, mas a segurança real está no backend.

Isto é importante:

```text
Frontend = conveniência
Backend  = autoridade
```

Nunca devemos confiar apenas em esconder um botão com JavaScript.

---

## config/plans.php

Define os limites dos planos.

```text
Free
- 50 produtos
- 1 membro

Pro
- 2000 produtos
- 5 membros

Business
- ilimitado
```

Esses limites são regras de negócio.

### Relação com condições

```text
SE quantidade de produtos >= limite do plano
    rejeitar novo produto
SENÃO
    cadastrar
```

---

## app/Services/PlanService.php

Colocamos a lógica de planos em uma classe própria para não deixar o ProductController responsável por tudo.

Isso aplica a ideia de **separação de responsabilidades**.

Ruim:

```text
ProductController
- cadastrar produto
- calcular plano
- enviar email
- gerar relatório
- cobrar cartão
- etc.
```

Melhor:

```text
ProductController -> produtos
PlanService       -> limites dos planos
```

---

# REGISTRO SAAS

## RegistrationController.php

O cadastro agora recebe:

```text
Nome
Email
Senha
Nome do workspace
```

Fluxo:

```text
POST /register
      |
      v
validar dados
      |
      v
criar User
      |
      v
criar Workspace
      |
      v
ligar User <-> Workspace como owner
      |
      v
salvar current_workspace_id
      |
      v
login automático
```

Tudo ocorre dentro de uma transação de banco.

Se criar o usuário funcionar, mas criar o workspace falhar, não queremos deixar uma conta incompleta.

---

# PRODUTOS EM MULTI-TENANCY

Antes o ProductController fazia consultas por usuário:

```text
user_id = usuário logado
```

Agora faz:

```text
workspace_id = workspace atual
```

Isto permite que dois usuários da mesma empresa vejam os mesmos produtos.

Ao mesmo tempo, outra empresa não vê esses produtos.

### SQL conceitual

```sql
SELECT *
FROM products
WHERE workspace_id = 2;
```

Não:

```sql
SELECT *
FROM products;
```

Esse `WHERE` é uma das partes mais importantes da arquitetura multi-tenant.

---

# SKU POR WORKSPACE

Antes a unicidade era:

```text
user_id + sku
```

Agora:

```text
workspace_id + sku
```

Assim duas empresas diferentes podem possuir o SKU `KB-001`, mas a mesma empresa não pode cadastrar o mesmo SKU duas vezes.

### Relação com SQL

Isso é uma constraint UNIQUE composta.

---

# INVENTÁRIO COM AUDITORIA

Uma movimentação agora registra:

```text
workspace_id
user_id
product_id
type
quantity
```

Portanto conseguimos responder:

- em qual empresa aconteceu?
- quem realizou?
- em qual produto?
- foi entrada ou saída?
- qual quantidade?
- quando?

Isso começa a formar um audit trail.

---

# WORKSPACE SWITCHER

A interface possui um seletor de workspace.

Quando o usuário troca:

```text
JavaScript
  |
  POST /api/workspaces/{id}/switch
  |
Laravel verifica se o usuário pertence ao workspace
  |
current_workspace_id é alterado
  |
Dashboard recarrega
```

Isso demonstra integração Frontend -> API -> Backend -> Banco.

---

# COMO ISSO SE RELACIONA COM O CRONOGRAMA DE AULAS

## Lógica / Portugol

Você encontra:

- `if` para permissões;
- `if` para estoque;
- `if` para limites do plano;
- entrada -> processamento -> saída;
- repetição para listas e tabelas.

## Java / POO

Mesmo sendo PHP, continuam presentes:

- classes;
- objetos;
- métodos;
- atributos;
- encapsulamento;
- separação de responsabilidades.

## UX/UI

A interface mostra:

- workspace atual;
- papel do usuário;
- plano atual;
- feedback de permissões;
- página de configurações;
- formulário de cadastro.

## HTML

Blade continua gerando:

- formulários;
- inputs;
- selects;
- tabelas;
- sections;
- buttons.

## CSS

Usamos:

- Grid;
- Flexbox;
- responsividade;
- estados visuais;
- hierarquia;
- componentes.

## Banco de Dados

A Fase 1 introduz diretamente:

- entidade Workspace;
- N:N User <-> Workspace;
- tabela associativa;
- PK e FK;
- constraints UNIQUE;
- índices;
- normalização.

## SQL

As operações equivalem conceitualmente a:

```sql
SELECT
INSERT
UPDATE
DELETE
WHERE
JOIN
COUNT
SUM
```

## Integração Full Stack

Fluxo completo:

```text
HTML / Blade
   |
JavaScript
   |
HTTP / JSON
   |
Routes
   |
Middleware
   |
Controller
   |
Model / Eloquent
   |
PostgreSQL
```

---

# COMO ATUALIZAR UMA INSTALAÇÃO EXISTENTE

Depois de substituir os arquivos pela Fase 1:

```bash
docker compose up -d --build
```

O entrypoint executa:

```text
php artisan migrate
php artisan inventoryflow:upgrade-saas
php artisan db:seed
```

O comando `inventoryflow:upgrade-saas` cria um workspace para usuários antigos e conecta os produtos/pedidos/movimentações já existentes.

Para rodar manualmente:

```bash
docker compose exec app php artisan inventoryflow:upgrade-saas
```

Para verificar rotas:

```bash
docker compose exec app php artisan route:list
```

Para testar:

```bash
docker compose exec app php artisan test
```
