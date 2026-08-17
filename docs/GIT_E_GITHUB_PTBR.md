# Git e GitHub

O projeto já possui `.gitignore` e pode ser inicializado como repositório.

```bash
git init
git add .
git commit -m "feat: create InventoryFlow full-stack foundation"
```

Commits futuros:

```text
feat: improve product validation
test: cover inventory business rules
fix: prevent duplicate product sku
docs: update deployment guide
```

Depois de criar um repositório vazio no GitHub:

```bash
git remote add origin URL_DO_REPOSITORIO
git branch -M main
git push -u origin main
```

Nunca envie `.env`.


## composer.lock

Na primeira execução com Docker, se o projeto ainda não tiver `composer.lock`, o Composer resolverá as versões permitidas pelo `composer.json` e criará o arquivo. Depois que a aplicação iniciar e os testes passarem, faça commit do `composer.lock`. Isso deixa instalações futuras mais reproduzíveis.
