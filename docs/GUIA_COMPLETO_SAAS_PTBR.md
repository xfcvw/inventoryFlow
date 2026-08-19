# Guia Completo do InventoryFlow SaaS

Este documento foi criado para você estudar o SaaS já pronto sem precisar tentar entender o projeto inteiro de uma vez.

## 1. O que significa o InventoryFlow ser um SaaS

SaaS significa **Software as a Service**. A mesma aplicação atende vários clientes/empresas.

No InventoryFlow, cada empresa é representada por um `Workspace`.

```text
InventoryFlow
    |
    +-- Workspace: Loja A
    |      +-- usuários
    |      +-- produtos
    |      +-- clientes
    |      +-- pedidos
    |      +-- depósitos
    |      +-- estoque
    |
    +-- Workspace: Loja B
           +-- dados completamente separados
```

Esse modelo é chamado de **multi-tenancy**.

A regra central é: uma empresa nunca deve conseguir acessar os dados de outra.

---

# 2. Fluxo completo de uma requisição

Exemplo: o usuário cadastra um produto.

```text
Usuário preenche formulário
        |
        v
JavaScript lê os campos
        |
        v
POST /api/products
        |
        v
routes/api.php
        |
        v
Middleware auth:sanctum
        |
        v
Middleware workspace
        |
        v
Middleware workspace.role
        |
        v
ProductController::store()
        |
        +--> valida os dados
        +--> verifica limite do plano
        +--> cria o produto
        +--> cria estoque no depósito padrão
        +--> registra auditoria
        |
        v
PostgreSQL
        |
        v
JSON retorna ao navegador
        |
        v
JavaScript atualiza a tabela
```

Essa única operação utiliza frontend, HTTP, rotas, middleware, controller, services, model, SQL e banco de dados.

---

# 3. `app/Models`

Models representam entidades do sistema e se conectam às tabelas do banco usando Eloquent ORM.

## User

Representa uma conta de usuário.

Relacionamentos importantes:

```text
User N:N Workspace
User 1:N Product criado
User 1:N Order criado
User 1:N InventoryMovement
User 1:N AuditLog
```

O relacionamento N:N entre User e Workspace usa a tabela intermediária `user_workspace`.

## Workspace

É a fronteira do tenant.

Um Workspace possui:

- usuários
- produtos
- pedidos
- clientes
- fornecedores
- categorias
- depósitos
- movimentações
- convites
- assinaturas
- logs de auditoria

## Product

Representa um produto do catálogo.

Campos principais:

- `workspace_id`: empresa dona do produto
- `user_id`: quem cadastrou
- `category_id`: categoria
- `supplier_id`: fornecedor
- `name`
- `sku`
- `barcode`
- `price`
- `cost_price`
- `stock`: quantidade total agregada
- `min_stock`
- `active`

## ProductWarehouseStock

Separa estoque por depósito.

Exemplo:

```text
Mouse Gamer

São Paulo: 20
Rio:       15
----------------
Total:     35
```

Cada linha relaciona:

```text
Product + Warehouse + Quantity
```

## Customer

Representa um cliente reutilizável em vários pedidos.

Isso evita repetir nome, e-mail e telefone em cada pedido.

## Order

Representa o cabeçalho de uma venda.

Possui:

- cliente
- depósito
- subtotal
- desconto
- imposto
- total
- status
- notas

## OrderItem

Representa cada produto dentro de um pedido.

```text
Order #100
  +-- 2x Keyboard
  +-- 1x Mouse
  +-- 3x Cable
```

Relação:

```text
Order 1:N OrderItem
```

## InventoryMovement

É o histórico imutável de entrada e saída.

Exemplo:

```text
Produto: Keyboard
Depósito: Main
Tipo: OUT
Quantidade: 2
Motivo: Order #15
Responsável: Vinicius
Saldo depois: 8
```

## Category

Normaliza categorias do catálogo.

Em vez de repetir texto livre em todos os produtos, cada produto pode apontar para uma categoria.

## Supplier

Cadastro de fornecedor.

## Warehouse

Depósito/local de estoque.

## Invitation

Convite para uma pessoa participar de um Workspace.

Possui token aleatório e data de expiração.

## Subscription

Registra o plano atual do Workspace.

A versão local usa `provider = local` para simular billing sem cobrança real.

## AuditLog

Registra ações importantes.

Exemplo:

```text
product.created
order.status_changed
inventory.moved
team.role_updated
billing.plan_changed
```

---

# 4. `app/Http/Controllers`

Controllers recebem requisições e coordenam as ações.

## AuthController

Responsável por login/logout.

Não confundir autenticação com autorização:

- autenticação = quem é você?
- autorização = você pode fazer isso?

## RegistrationController

Cadastro comum:

```text
Create User
    |
Create Workspace
    |
Attach User as owner
    |
Create default Warehouse
    |
Create Free Subscription
```

Cadastro por convite:

```text
Invitation
    |
Create User
    |
Attach to existing Workspace
    |
Use invited role
```

Tudo é feito em transação quando necessário para evitar estado incompleto.

## ProductController

CRUD do produto.

- `index`: lista
- `store`: cria
- `show`: consulta um
- `update`: altera
- `destroy`: exclui

## InventoryController

Controla entrada e saída manual de estoque.

A regra pesada está em `StockService`.

## OrderController

É um dos arquivos mais importantes para estudar.

Um pedido possui vários itens. Quando o pedido entra em `processing` ou `completed`, o estoque é reduzido automaticamente.

Ao cancelar um pedido que já movimentou estoque, o sistema devolve as quantidades.

## TeamController

Lista membros, altera cargo e remove membros.

## InvitationController

Cria convites e envia e-mail pelo Mailpit no ambiente local.

## BillingController

Simula mudança entre Free / Pro / Business.

Não cobra dinheiro, mas os limites mudam de verdade.

## ReportController

Executa consultas agregadas:

- soma de faturamento
- quantidade de pedidos
- ticket médio
- produtos mais vendidos
- valor do estoque
- baixo estoque

É um ótimo arquivo para estudar `SUM`, `AVG`, `GROUP BY` e joins.

---

# 5. `app/Services`

Services existem para tirar regras complexas de dentro dos Controllers.

## StockService

Centraliza as regras de estoque.

Fluxo de saída:

```text
buscar estoque
     |
lockForUpdate
     |
quantidade solicitada > disponível?
     | yes
     +--> erro
     |
     no
     |
subtrair
     |
atualizar total do produto
     |
criar InventoryMovement
     |
se estoque baixo -> Notification
```

### Por que `DB::transaction()`?

Porque várias alterações precisam acontecer juntas.

Se qualquer etapa falhar, o banco desfaz tudo.

### Por que `lockForUpdate()`?

Para reduzir problema de concorrência.

Duas pessoas não devem conseguir retirar as últimas unidades ao mesmo tempo usando o mesmo valor antigo.

## PlanService

Centraliza limites do plano.

Exemplo:

```text
Free
- 50 products
- 1 member
- 1 warehouse
- no reports
```

Os Controllers perguntam ao PlanService se a ação é permitida.

## AuditService

Evita repetir em todos os Controllers o código de criação de auditoria.

---

# 6. Middleware

Middleware fica entre a requisição e o Controller.

## EnsureWorkspaceSelected

Confirma:

1. existe usuário autenticado
2. existe Workspace selecionado
3. usuário pertence ao Workspace
4. adiciona Workspace à requisição

## EnsureWorkspaceRole

Confirma se o cargo está na lista permitida.

Exemplo:

```php
->middleware('workspace.role:owner,admin,manager')
```

Isso é segurança de backend.

Esconder um botão no JavaScript não é segurança.

---

# 7. RBAC

RBAC = Role-Based Access Control.

Cargos:

```text
Owner
Admin
Manager
Member
Viewer
```

Uma hierarquia simplificada:

```text
Owner   -> tudo
Admin   -> administração operacional
Manager -> catálogo, estoque, clientes e pedidos
Member  -> clientes, estoque e pedidos
Viewer  -> leitura
```

A interface melhora a UX escondendo ações não permitidas, mas quem realmente protege é o middleware do Laravel.

---

# 8. `database/migrations`

Migration é controle de versão do esquema do banco.

Exemplo conceitual:

```php
$table->foreignId('workspace_id')->constrained();
```

vira uma chave estrangeira no banco.

As migrations mostram o modelo físico do InventoryFlow.

Entidades principais:

```text
users
workspaces
user_workspace
products
categories
suppliers
customers
warehouses
product_warehouse_stocks
inventory_movements
orders
order_items
invitations
subscriptions
audit_logs
notifications
```

---

# 9. Normalização aplicada

## Cliente

Versão simples ruim:

```text
orders.customer = "João"
```

Versão mais normalizada:

```text
customers
  id = 5
  name = João

orders
  customer_id = 5
```

Mantemos também `orders.customer` como snapshot/compatibilidade, mas o relacionamento verdadeiro é `customer_id`.

## Pedido e itens

Nunca guardar algo como:

```text
products = "Mouse x2, Keyboard x1"
```

Usamos:

```text
orders
order_items
```

Isso permite consultas, somas e relatórios corretos.

## Estoque por depósito

Não criamos colunas:

```text
stock_sp
stock_rj
stock_bh
```

Usamos uma tabela intermediária:

```text
product_warehouse_stocks
```

Assim novos depósitos podem ser adicionados sem alterar a estrutura do produto.

---

# 10. `routes/api.php`

A API organiza o contrato entre frontend e backend.

Exemplos:

```text
GET    /api/products
POST   /api/products
PUT    /api/products/{product}
DELETE /api/products/{product}

GET    /api/orders
POST   /api/orders
PUT    /api/orders/{order}

GET    /api/reports/overview
```

Métodos HTTP:

- GET = consultar
- POST = criar/executar ação
- PUT = atualizar
- DELETE = excluir

---

# 11. `public/assets/app.js`

É o frontend interativo.

Principais conceitos que você deve procurar:

- `const` / `let`
- funções
- `async` / `await`
- `fetch`
- eventos
- arrays
- `.map()`
- `.filter()`
- `.reduce()`
- manipulação do DOM
- objetos JSON

## Função `api()`

Centraliza todas as requisições.

Em vez de escrever `fetch` completo em cada função, usamos uma única função para:

- enviar headers
- enviar CSRF
- interpretar JSON
- tratar erro 401
- mostrar erros de validação

Esse é um exemplo de reutilização de código.

---

# 12. Orders e lógica de programação

Um pedido é ótimo para estudar algoritmo.

Entrada:

```text
Produto A, quantidade 2, preço 100
Produto B, quantidade 1, preço 50
Desconto 20
Taxa 10
```

Processamento:

```text
subtotal = 2*100 + 1*50 = 250
total = 250 - 20 + 10 = 240
```

Saída:

```text
Order total = 240
```

Depois entra a regra de estoque.

---

# 13. Reports e SQL

O relatório de produtos mais vendidos usa a ideia:

```sql
SELECT product_name,
       SUM(quantity) AS units,
       SUM(subtotal) AS revenue
FROM order_items
JOIN orders ON orders.id = order_items.order_id
WHERE workspace_id = ?
GROUP BY product_name
ORDER BY units DESC;
```

Isso conecta diretamente com:

- JOIN
- GROUP BY
- SUM
- ORDER BY
- WHERE

---

# 14. Docker

O projeto roda em vários serviços:

```text
Docker Compose
    |
    +-- app      -> PHP-FPM / Laravel
    +-- nginx    -> servidor HTTP
    +-- db       -> PostgreSQL
    +-- mailpit  -> captura de e-mails de desenvolvimento
```

## Por que não colocar tudo no mesmo container?

Separar responsabilidades aproxima o ambiente da arquitetura usada em aplicações reais e facilita trocar/escalar componentes.

---

# 15. Nginx

Fluxo:

```text
localhost:8080
    |
Nginx
    |
public/index.php
    |
PHP-FPM
    |
Laravel
```

Arquivos CSS/JS podem ser servidos diretamente pelo Nginx.

---

# 16. Mailpit

No ambiente local, e-mails não são enviados para pessoas reais.

Eles são capturados pelo Mailpit.

Acesse:

```text
http://localhost:8025
```

Teste:

- convite de equipe
- reset de senha

Isso permite aprender e-mail sem risco de spam ou necessidade de serviço externo.

---

# 17. Billing

A arquitetura de plano já existe:

```text
Workspace
   |
Subscription
   |
PlanService
   |
limits
```

O `BillingController` muda plano localmente.

O que falta para cobrança real é integrar um provedor externo:

```text
Checkout
Webhooks
Customer ID do provedor
Subscription ID do provedor
assinatura de webhook
tratamento de falha de pagamento
cancelamento
notas fiscais/obrigações conforme o país
```

Por isso o simulador é a melhor versão para estudo local.

---

# 18. Audit log

Exemplo:

```text
2026-08-18 10:30
Vinicius
product.updated
Product #15
{"price": 299.90}
```

Auditoria ajuda em:

- investigação de erro
- segurança
- rastreabilidade
- suporte

---

# 19. Testes

Execute:

```bash
docker compose exec app php artisan test
```

Existem testes para:

- autenticação
- criação de Workspace
- isolamento entre tenants
- produto
- estoque negativo
- movimentação
- pedido com itens
- redução de estoque
- cancelamento e devolução de estoque
- convite
- planos
- relatórios bloqueados no Free

---

# 20. Como estudar este projeto

Não tente aprender por ordem de pastas.

Use fluxos.

## Fluxo A — Login

```text
login.blade.php
routes/web.php
AuthController
User
sessions
```

## Fluxo B — Produto

```text
app.blade.php
app.js
routes/api.php
ProductController
PlanService
Product
Migration
PostgreSQL
```

## Fluxo C — Estoque

```text
app.js
InventoryController
StockService
ProductWarehouseStock
InventoryMovement
Notification
```

## Fluxo D — Pedido

```text
OrderController
Order
OrderItem
StockService
InventoryMovement
```

## Fluxo E — SaaS

```text
Workspace
user_workspace
Middleware
TeamController
Invitation
Subscription
PlanService
```
