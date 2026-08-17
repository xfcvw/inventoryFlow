# InventoryFlow

InventoryFlow é um sistema web bilíngue de gestão de estoque e pedidos criado como projeto e portfólio.

## Stack ativa

- PHP 8.4 no container
- Laravel 13
- Laravel Sanctum
- PostgreSQL 18
- HTML5 / CSS3 / JavaScript
- REST API
- Docker + Docker Compose
- Nginx + PHP-FPM
- PHPUnit
- GitHub Actions
- preparação para Google Cloud Run + Cloud SQL

## Funcionalidades

- login real com usuário salvo no PostgreSQL;
- sessão Laravel e proteção CSRF;
- API autenticada;
- dashboard;
- CRUD de produtos;
- pesquisa e filtros;
- estoque mínimo;
- entrada e saída de estoque;
- bloqueio de estoque negativo;
- histórico de movimentações;
- pedidos e mudança de status;
- dados isolados por usuário;
- português e inglês;
- layout responsivo;
- testes automatizados.

## Rodar no Windows / Docker Desktop

1. Extraia o projeto.
2. Abra a pasta no VS Code.
3. Garanta que o Docker Desktop esteja aberto.
4. No terminal:

```bash
docker compose up --build
```

A primeira execução instala as dependências, espera o PostgreSQL, roda migrations e seeders e inicia a aplicação.

Abra:

```text
http://localhost:8080
```

### Login de demonstração

```text
E-mail: demo@inventoryflow.com
Senha: inventory123
```

## Parar

```bash
docker compose down
```

Apagar também o banco local:

```bash
docker compose down -v
```

## Testes

```bash
docker compose exec app php artisan test
```

## API

```text
GET    /api/me
GET    /api/dashboard
GET    /api/products
POST   /api/products
GET    /api/products/{product}
PUT    /api/products/{product}
DELETE /api/products/{product}
GET    /api/inventory/movements
POST   /api/inventory/movements
GET    /api/orders
POST   /api/orders
GET    /api/orders/{order}
PUT    /api/orders/{order}
DELETE /api/orders/{order}
```


## Verificação deste pacote

Antes de empacotar, os arquivos PHP/Blade, JavaScript, JSON, YAML e shell foram verificados quanto à sintaxe. A execução integrada com containers e os testes Laravel precisam ser feitos no seu computador com Docker Desktop, porque o ambiente usado para gerar o pacote não possui Docker/Composer disponíveis para subir a stack completa.

Depois da primeira inicialização bem-sucedida, execute `docker compose exec app php artisan test`. O primeiro `composer install` também criará `composer.lock`; faça commit dele no Git.

## Estudar o projeto

Leia primeiro:

```text
docs/COMPONENTES_EXPLICADOS_PTBR.md
```

Depois:

```text
docs/GIT_E_GITHUB_PTBR.md
deploy/DEPLOY_GCP.md
```
