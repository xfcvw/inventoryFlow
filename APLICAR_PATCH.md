# Como atualizar o InventoryFlow que já está no seu PC

Se você já possui uma versão anterior rodando no Docker, não precisa apagar o banco.

## 1. Faça backup da pasta

Copie a pasta atual antes de substituir arquivos.

## 2. Pare os containers

Na pasta atual:

```powershell
docker compose down
```

Não use `docker compose down -v`, porque `-v` remove o volume do PostgreSQL e pode apagar seus dados locais.

## 3. Copie os arquivos novos

Extraia o pacote completo e copie o conteúdo por cima da sua pasta atual.

Se quiser preservar configurações personalizadas, mantenha seu `.env` antigo.

## 4. Se preservar o `.env` antigo, adicione Mailpit opcionalmente

```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=hello@inventoryflow.local
MAIL_FROM_NAME=InventoryFlow
```

Sem isso, o restante do SaaS continua funcionando; apenas os e-mails podem ir para log dependendo da configuração antiga.

## 5. Suba novamente

```powershell
docker compose up -d --build
```

O entrypoint executa migrations e o comando de upgrade automaticamente.

## 6. Veja o status

```powershell
docker compose ps
```

Você deve ver:

```text
inventoryflow_app
inventoryflow_nginx
inventoryflow_db
inventoryflow_mailpit
```

## 7. Teste

```powershell
docker compose exec app php artisan test
```

## 8. Acesse

```text
InventoryFlow: http://localhost:8080
Mailpit:       http://localhost:8025
```

## Importante sobre dados antigos

O comando:

```bash
php artisan inventoryflow:upgrade-saas
```

foi atualizado para:

- criar Workspace para usuários antigos
- manter produtos/pedidos/movimentações no Workspace
- criar depósito padrão
- criar registro de assinatura local
- criar estoque por depósito usando o estoque antigo
- associar movimentações e pedidos antigos ao depósito padrão quando necessário
