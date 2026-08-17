# InventoryFlow — cada componente explicado

Este é o mapa de estudo do projeto. A ideia não é decorar o código: é entender a responsabilidade de cada camada.

## 1. HTML / Blade

Arquivos: `resources/views/login.blade.php` e `resources/views/app.blade.php`.

HTML cria a estrutura da interface. Blade é o sistema de templates do Laravel. Com ele podemos misturar HTML com comandos do servidor, por exemplo:

```blade
{{ auth()->user()->name }}
```

Esse trecho coloca na página o nome do usuário autenticado.

## 2. CSS

Arquivo: `public/assets/app.css`.

Controla aparência, Grid, Flexbox, responsividade, modais, estados de foco e hover. O CSS não cadastra produtos nem acessa o banco; ele cuida da apresentação.

## 3. JavaScript

Arquivos: `public/assets/app.js` e `public/assets/i18n.js`.

O JavaScript reage a cliques e formulários e chama a API com `fetch()`.

Fluxo de cadastro:

```text
Formulário -> JavaScript -> POST /api/products -> Laravel -> PostgreSQL -> JSON -> JavaScript -> tabela
```

## 4. Português / Inglês

`i18n.js` possui um objeto `translations` com `en` e `pt`. Elementos HTML usam `data-i18n`. O idioma escolhido fica no `localStorage` porque é apenas uma preferência de interface.

Produtos, pedidos e usuários NÃO usam mais LocalStorage; eles ficam no PostgreSQL.

## 5. PHP

PHP é executado no servidor. O navegador não recebe os controllers PHP; recebe HTML ou JSON produzidos por eles.

## 6. Laravel

Laravel organiza o projeto e fornece rotas, controllers, validação, autenticação, sessões, ORM, migrations e testes.

Entrada da aplicação: `public/index.php`.
Configuração principal: `bootstrap/app.php`.

## 7. Rotas Web

Arquivo: `routes/web.php`.

Rotas web cuidam de páginas e login. Exemplo:

```php
Route::post('/login', [AuthController::class, 'store']);
```

## 8. Rotas da API

Arquivo: `routes/api.php`.

Exemplo:

```php
Route::apiResource('products', ProductController::class);
```

Isso cria endpoints REST de listar, criar, consultar, atualizar e excluir produtos.

## 9. Controllers

Pasta: `app/Http/Controllers`.

Controllers recebem a requisição e coordenam o que deve acontecer.

`ProductController`: CRUD de produtos.
`InventoryController`: entrada/saída e histórico.
`OrderController`: pedidos.
`DashboardController`: indicadores.
`AuthController`: login/logout.

## 10. Models / Eloquent

Pasta: `app/Models`.

Models representam entidades do banco. `Product` representa a tabela `products`. O Eloquent permite usar PHP em vez de escrever SQL para todas as operações.

```php
$request->user()->products()->create($validated);
```

significa: crie um produto pertencente ao usuário atual.

## 11. PostgreSQL

Serviço `db` no `docker-compose.yml`.

Guarda persistentemente usuários, produtos, movimentações e pedidos. O volume `inventoryflow_pgdata` mantém os dados mesmo se o container for recriado.

## 12. Migrations

Pasta: `database/migrations`.

Migrations versionam a estrutura do banco. `php artisan migrate` cria as tabelas de maneira reproduzível.

## 13. Seeder

Arquivo: `database/seeders/DatabaseSeeder.php`.

Cria dados de demonstração, inclusive:

```text
demo@inventoryflow.com
inventory123
```

A senha é transformada em hash antes de ser armazenada.

## 14. Autenticação

`AuthController` usa:

```php
Auth::attempt($credentials)
```

O Laravel compara a senha enviada com o hash do banco. Após login, o ID da sessão é regenerado.

## 15. Sessão

A sessão permite que o servidor reconheça o navegador depois do login. O navegador envia um cookie de sessão nas próximas requisições.

## 16. CSRF

Blade usa `@csrf` nos formulários. A tela da aplicação também expõe o token em uma meta tag, e o JavaScript o manda no header `X-CSRF-TOKEN` nas operações que alteram dados.

## 17. Sanctum

`bootstrap/app.php` ativa `statefulApi()`. As rotas da API usam `auth:sanctum`.

Resultado: a SPA no mesmo site pode usar a sessão Laravel para acessar a API; uma requisição não autenticada recebe erro 401.

## 18. REST API

Métodos principais:

```text
GET = ler
POST = criar
PUT = atualizar
DELETE = excluir
```

Exemplo: `PUT /api/products/5` altera o produto 5.

## 19. JSON

JavaScript e Laravel trocam objetos JSON. O backend valida o JSON antes de salvar.

## 20. Validação no servidor

Nunca devemos confiar apenas no `<input>`. O controller valida novamente:

```php
'price' => ['required', 'numeric', 'min:0']
```

Assim, até uma chamada manual inválida é recusada.

## 21. Isolamento por usuário

Todas as consultas usam o usuário autenticado. Isso impede que um usuário liste produtos de outro apenas alterando a URL.

## 22. Regra de estoque

Uma saída maior que o estoque disponível gera erro de validação. A regra está no servidor, portanto não depende do JavaScript.

## 23. `DB::transaction()`

A atualização do estoque e a criação do histórico ficam na mesma transação. Se uma etapa falhar, a operação pode ser revertida como um conjunto.

## 24. `lockForUpdate()`

Ao movimentar estoque, o registro do produto é bloqueado dentro da transação. Isso ajuda quando duas requisições tentam alterar a mesma quantidade quase ao mesmo tempo.

## 25. Docker

`Dockerfile` descreve a imagem do serviço PHP/Laravel. Ele instala as extensões PHP necessárias e o Composer.

## 26. Docker Compose

`docker-compose.yml` coordena:

```text
nginx -> app -> db
```

Um comando sobe a stack completa.

## 27. PostgreSQL healthcheck

`pg_isready` informa quando o banco está pronto. O serviço Laravel espera por essa condição antes de iniciar.

## 28. Nginx

Arquivo: `docker/nginx/default.conf`.

Nginx recebe HTTP. Arquivos estáticos são servidos pelo diretório `public`; requisições PHP são encaminhadas ao PHP-FPM.

## 29. PHP-FPM

É o processo que executa PHP para o Nginx.

```text
Navegador -> Nginx -> PHP-FPM -> Laravel
```

## 30. `.env`

Guarda configuração por ambiente: banco, APP_KEY, modo debug etc. `.env` está no `.gitignore`. `.env.example` serve como modelo sem segredos de produção.

## 31. APP_KEY

É usada por recursos criptográficos do Laravel. No ambiente de produção ela deve ser secreta e estável.

## 32. Git

Git registra versões do projeto. Faça commits pequenos e descritivos para mostrar evolução real.

## 33. GitHub Actions / CI

Arquivo: `.github/workflows/tests.yml`.

A cada push/pull request, o workflow instala PHP e dependências e executa os testes. Isso é integração contínua (CI).

## 34. PHPUnit / testes

Pasta: `tests/Feature`.

Há testes de login, criação de produto, isolamento entre usuários, estoque negativo, entrada de estoque e pedidos.

## 35. Dockerfile do Cloud Run

`Dockerfile.cloudrun` monta Nginx + PHP-FPM + Laravel em um único container de entrada para o Cloud Run.

## 36. Cloud SQL

No deploy sugerido, o PostgreSQL local é substituído pelo Cloud SQL PostgreSQL. A aplicação continua usando o driver `pgsql`.

## 37. O que está realmente “ativo”

No código estão implementados frontend, API, autenticação, integração PostgreSQL, Docker, Nginx, testes e CI. Neste ambiente foi possível validar sintaxe de PHP/Blade, JavaScript, JSON, YAML e scripts shell. A execução integrada dos containers e dos testes Laravel deve ser feita no seu computador com Docker Desktop, pois este ambiente de geração não disponibiliza Docker/Composer para subir a stack completa.

GitHub Actions só executará depois que o projeto for enviado para um repositório GitHub. Cloud Run/Cloud SQL só serão efetivamente publicados depois que você usar uma conta Google Cloud e configurar credenciais e faturamento.

## 38. Ordem certa para estudar

1. `resources/views/login.blade.php`
2. `resources/views/app.blade.php`
3. `public/assets/app.css`
4. `public/assets/i18n.js`
5. função `api()` em `app.js`
6. CRUD em `app.js`
7. `routes/web.php`
8. `routes/api.php`
9. `AuthController`
10. `ProductController`
11. `InventoryController`
12. Models
13. Migrations
14. Seeder
15. Docker Compose
16. Nginx
17. testes
18. CI
19. deploy

## Como explicar em entrevista

Depois de estudar, você deve conseguir dizer com suas próprias palavras algo como:

> O InventoryFlow começou como uma interface em HTML, CSS e JavaScript e evoluiu para uma aplicação full stack. Eu substituí a persistência no navegador por uma API Laravel conectada ao PostgreSQL, implementei autenticação por sessão e Sanctum, validação no backend e regras de estoque com transação. A stack local usa Docker Compose com Nginx, PHP-FPM e PostgreSQL, e as principais regras são cobertas por testes automatizados.
