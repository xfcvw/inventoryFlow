# InventoryFlow conectado ao cronograma de aulas

A ideia deste arquivo é mostrar onde cada assunto estudado aparece dentro de um projeto real.

## 1. Lógica de programação e algoritmos

Procure principalmente:

- `StockService.php`
- `OrderController.php`
- `PlanService.php`
- `app.js`

Conceitos:

```text
Entrada -> Processamento -> Saída
SE / SENÃO
repetições
variáveis
funções
regras de negócio
```

Exemplo de regra:

```text
SE quantidade_saida > estoque
    mostrar erro
SENÃO
    diminuir estoque
FIMSE
```

No Laravel essa ideia está dentro do `StockService`.

---

## 2. Condicionais

Exemplos reais:

- usuário possui permissão?
- plano permite criar mais produtos?
- estoque é suficiente?
- pedido já movimentou estoque?
- pedido foi cancelado?
- convite ainda está válido?

Arquivos:

- `EnsureWorkspaceRole.php`
- `PlanService.php`
- `StockService.php`
- `OrderController.php`
- `Invitation.php`

---

## 3. Repetições / arrays

No JavaScript:

```javascript
products.map(...)
products.filter(...)
items.reduce(...)
```

No backend:

```php
foreach ($order->items as $item) {
    // movimentar cada item
}
```

A lógica é a mesma estudada em Portugol/Java, mudando apenas a sintaxe.

---

## 4. POO / Java

Mesmo o backend sendo PHP, os conceitos de orientação a objetos são transferíveis.

```text
Class
Object
Attribute
Method
Relationship
Responsibility
```

Exemplo equivalente conceitual:

```java
class Product {
    String name;
    double price;
}
```

No Laravel:

```php
class Product extends Model
{
}
```

Estude:

- Models
- Services
- Controllers

Veja como responsabilidades foram separadas em classes.

---

## 5. Design Thinking / UX

Problema:

> Pequenos e-commerces precisam acompanhar produtos, estoque e pedidos sem perder rastreabilidade.

Usuários:

- Owner
- Admin
- Manager
- Member
- Viewer

Necessidades diferentes geraram permissões diferentes.

Isso é aplicação prática de entender usuário, contexto e jornada.

---

## 6. Jornada do usuário

Jornada de onboarding:

```text
Register
   |
Create Workspace
   |
Dashboard
   |
Create categories/suppliers
   |
Create products
   |
Receive stock
   |
Create customer
   |
Create order
   |
Process order
   |
Reports
```

Jornada de funcionário convidado:

```text
Invitation email
   |
Create account / login
   |
Accept invitation
   |
Workspace selected
   |
Features based on role
```

---

## 7. Hierarquia visual

No Dashboard:

```text
label pequeno
NUMBER grande
contexto pequeno
```

Exemplo:

```text
MONTH REVENUE
R$ 14.900
this month
```

Isso faz o olhar identificar a informação principal rapidamente.

---

## 8. Cor

O sistema usa:

- azul como cor principal/interação
- verde para sucesso/entrada
- amarelo para atenção/baixo estoque
- vermelho para exclusão/saída/erro

Isso demonstra uso funcional da cor, não apenas decoração.

---

## 9. Tipografia

A interface usa diferença de:

- tamanho
- peso
- contraste
- espaçamento

para criar hierarquia.

Procure `app.css` e compare:

- `.eyebrow`
- `.page-heading h1`
- `.stat-card strong`
- `.muted-text`

---

## 10. HTML5

Arquivos Blade ainda são HTML.

Estude:

- `form`
- `input`
- `select`
- `button`
- `table`
- `section`
- `article`
- `header`
- `nav`
- `main`

Arquivos:

- `resources/views/login.blade.php`
- `resources/views/register.blade.php`
- `resources/views/app.blade.php`

---

## 11. CSS3

Arquivo:

```text
public/assets/app.css
```

Assuntos:

- Box Model
- Flexbox
- CSS Grid
- responsividade
- media queries
- pseudo-estados hover/focus
- variáveis CSS
- espaçamento
- modal
- sidebar
- cards

---

## 12. Projeto conceitual de banco

Entidades:

```text
User
Workspace
Product
Category
Supplier
Warehouse
Customer
Order
OrderItem
InventoryMovement
Subscription
Invitation
AuditLog
```

Relacionamentos:

```text
Workspace 1:N Product
Workspace 1:N Customer
Workspace 1:N Order
Workspace N:N User
Order 1:N OrderItem
Product N:N Warehouse (via ProductWarehouseStock)
```

Desenhar esse DER é um excelente exercício.

---

## 13. Normalização

Exemplos do projeto:

### N:N User x Workspace

Foi criada:

```text
user_workspace
```

### N:N Product x Warehouse

Foi criada:

```text
product_warehouse_stocks
```

### Pedido com muitos produtos

Foi criada:

```text
order_items
```

Isso evita colunas repetidas e listas dentro de um campo.

---

## 14. SQL DML

CRUD do ProductController corresponde conceitualmente a:

```sql
INSERT
SELECT
UPDATE
DELETE
```

Filtros usam:

```sql
WHERE
ORDER BY
```

---

## 15. JOIN

O relatório de vendas precisa juntar:

```text
orders
   |
   +-- order_items
```

Conceito SQL:

```sql
JOIN orders ON orders.id = order_items.order_id
```

---

## 16. GROUP BY

Para descobrir produtos mais vendidos:

```sql
GROUP BY product_name, sku
```

E depois:

```sql
SUM(quantity)
SUM(subtotal)
```

Veja `ReportController.php`.

---

## 17. Funções agregadoras

InventoryFlow usa conceitos equivalentes a:

```sql
COUNT()
SUM()
AVG()
```

Exemplos:

- total de pedidos
- receita
- ticket médio
- valor do estoque

---

## 18. Chave primária e estrangeira

Exemplo:

```text
products.id       -> PK
products.workspace_id -> FK
products.category_id  -> FK
```

As migrations mostram isso explicitamente.

---

## 19. Integração Full Stack

Fluxo ideal para estudar:

```text
HTML
 |
JavaScript
 |
HTTP JSON
 |
Laravel Route
 |
Controller
 |
Model / Service
 |
SQL
 |
PostgreSQL
```

Esse é o ponto em que as matérias deixam de parecer separadas.

---

## 20. Git e desenvolvimento profissional

Use commits por funcionalidade:

```text
feat: add customer management
feat: add order items
feat: add warehouse inventory
fix: prevent negative stock
test: cover order stock restoration
docs: explain SaaS tenancy
```

Isso cria histórico compreensível para o GitHub e para recrutadores.
