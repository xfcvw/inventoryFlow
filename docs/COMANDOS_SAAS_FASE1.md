# Comandos — SaaS Fase 1

## Subir o projeto

```powershell
docker compose up -d --build
```

## Ver containers

```powershell
docker compose ps
```

## Ver migrations

```powershell
docker compose exec app php artisan migrate:status
```

## Converter dados antigos para Workspace

```powershell
docker compose exec app php artisan inventoryflow:upgrade-saas
```

## Ver rotas

```powershell
docker compose exec app php artisan route:list
```

## Rodar testes

```powershell
docker compose exec app php artisan test
```

## Limpar cache Laravel

```powershell
docker compose exec app php artisan optimize:clear
```

## Logs Laravel/PHP

```powershell
docker compose logs app --tail=100
```

## Logs Nginx

```powershell
docker compose logs nginx --tail=100
```

## Entrar no PostgreSQL

```powershell
docker compose exec db psql -U inventoryflow -d inventoryflow
```

Depois você pode estudar SQL com:

```sql
SELECT id, name, plan FROM workspaces;
SELECT user_id, workspace_id, role FROM user_workspace;
SELECT id, workspace_id, name, sku FROM products;
```
