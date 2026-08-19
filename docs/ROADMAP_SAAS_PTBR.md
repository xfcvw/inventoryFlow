# Roadmap do InventoryFlow SaaS

## Fase 1 — Fundação SaaS ✅

- cadastro de usuário;
- criação de workspace;
- multi-tenancy;
- troca de workspace;
- roles / RBAC;
- plano Free / Pro / Business;
- limites de produto no backend;
- produtos isolados por workspace;
- pedidos isolados por workspace;
- estoque isolado por workspace;
- auditoria básica da movimentação;
- testes de isolamento e permissões.

## Fase 2 — Modelo comercial do e-commerce

- Customers;
- Categories como tabela;
- Suppliers;
- Order Items;
- total do pedido calculado pelo backend;
- baixa automática de estoque ao concluir pedido;
- impedir dupla baixa de estoque;
- relatórios com JOIN / GROUP BY / SUM.

## Fase 3 — Equipes

- tela de membros;
- convite por email;
- tokens de convite;
- aceitar / recusar convite;
- alterar role;
- remover membro;
- limite de membros por plano.

## Fase 4 — Notificações e auditoria

- notificações de estoque baixo;
- histórico de ações;
- quem criou/editou/excluiu;
- central de notificações;
- filas para tarefas assíncronas.

## Fase 5 — Relatórios

- valor total do estoque;
- produtos com maior movimentação;
- vendas por período;
- pedidos por status;
- CSV;
- PDF;
- gráficos.

## Fase 6 — Billing

Somente depois de toda a arquitetura anterior estar sólida:

- subscriptions;
- checkout de um provedor de pagamento;
- webhook;
- upgrade/downgrade;
- cancelamento;
- período de teste;
- página Billing.

## Fase 7 — Produção

- domínio;
- HTTPS;
- GCP;
- PostgreSQL gerenciado;
- backups;
- logs;
- monitoramento;
- rate limiting;
- email real;
- variáveis secretas.
