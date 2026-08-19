# Ordem de estudo do SaaS pronto

O projeto está pronto para ser usado como laboratório. Não tente ler tudo em ordem alfabética.

## Nível 1 — Entender a interface

1. `resources/views/app.blade.php`
2. `public/assets/app.css`
3. `public/assets/app.js`

Objetivo: entender HTML, CSS, eventos e DOM.

## Nível 2 — Primeiro fluxo backend

1. `routes/api.php`
2. `ProductController.php`
3. `Product.php`
4. migration de products

Objetivo: entender rota -> controller -> model -> banco.

## Nível 3 — Banco

Desenhe o DER usando as migrations.

Depois abra PostgreSQL e tente consultas SELECT manualmente.

## Nível 4 — Estoque

1. `InventoryController.php`
2. `StockService.php`
3. `ProductWarehouseStock.php`
4. `InventoryMovement.php`

Objetivo: transação, validação e concorrência.

## Nível 5 — Pedidos

1. `OrderController.php`
2. `Order.php`
3. `OrderItem.php`

Objetivo: relação 1:N, cálculo de total e integração com estoque.

## Nível 6 — SaaS

1. `Workspace.php`
2. `User.php`
3. `EnsureWorkspaceSelected.php`
4. `EnsureWorkspaceRole.php`
5. `TeamController.php`
6. `InvitationController.php`
7. `PlanService.php`
8. `BillingController.php`

Objetivo: multi-tenancy, RBAC e planos.

## Nível 7 — Relatórios

Abra `ReportController.php` e escreva manualmente as consultas SQL equivalentes.

## Nível 8 — Infraestrutura

1. `Dockerfile`
2. `docker-compose.yml`
3. `docker/nginx/default.conf`
4. `docker/php/entrypoint.sh`

Objetivo: entender o caminho browser -> Nginx -> PHP-FPM -> Laravel -> PostgreSQL.

## Nível 9 — Qualidade

1. `tests/`
2. `.github/workflows/tests.yml`
3. audit log
4. validações

Objetivo: aprender como evitar regressões.
