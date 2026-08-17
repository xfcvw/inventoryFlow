# Deploy no Google Cloud

O deploy real exige sua conta, billing, projeto e permissões. O código já está preparado para a arquitetura:

```text
Internet -> Cloud Run (Nginx + PHP-FPM + Laravel) -> Cloud SQL PostgreSQL
```

## 1. Login e projeto

```bash
gcloud auth login
gcloud config set project SEU_PROJECT_ID
```

## 2. Banco

Crie uma instância PostgreSQL no Cloud SQL, a database `inventoryflow` e um usuário específico para a aplicação.

## 3. Artifact Registry

```bash
gcloud artifacts repositories create inventoryflow --repository-format=docker --location=southamerica-east1
```

## 4. Build

Construa `Dockerfile.cloudrun` e envie a imagem para seu Artifact Registry. Você pode fazer isso com Cloud Build ou Docker local.

## 5. Segredos

Não grave `APP_KEY` nem `DB_PASSWORD` no Git. Use Secret Manager / variáveis seguras do Cloud Run.

Para gerar uma chave Laravel:

```bash
php artisan key:generate --show
```

## 6. Cloud Run

No serviço Cloud Run:
- use a imagem gerada;
- conecte a instância Cloud SQL;
- configure as variáveis de `cloudrun.env.example`;
- use `DB_HOST=/cloudsql/PROJECT:REGION:INSTANCE`;
- `APP_DEBUG=false`.

O Cloud Run fornece a variável `PORT`; o entrypoint usa esse valor no Nginx.

## 7. Migrations

Execute uma etapa controlada de release:

```bash
php artisan migrate --seed --force
```

Em produção real, prefira uma etapa/Job de migration, em vez de executar migrations em toda inicialização da aplicação.

## 8. Antes de publicar para recrutadores

- HTTPS ativo
- senha forte do banco
- `.env` fora do Git
- `APP_DEBUG=false`
- testes passando
- sem dados pessoais reais

## Sessões em produção

O exemplo de produção usa `SESSION_DRIVER=database`. Isso evita depender do sistema de arquivos efêmero de uma instância do Cloud Run para manter a sessão do usuário. A migration `create_sessions_table` já está incluída no projeto.
