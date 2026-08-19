# O que falta para transformar o MVP local em um SaaS comercial de produção

O código entrega um SaaS MVP completo para estudo e demonstração local. Produção comercial exige tarefas externas que não podem ser embutidas prontas sem contas/credenciais.

## 1. Cobrança real

Hoje existe simulador de billing.

Para cobrar de verdade é necessário escolher e configurar um provedor de pagamentos e implementar:

- checkout
- webhooks assinados
- falha de pagamento
- renovação
- cancelamento
- reembolso
- portal do cliente
- impostos/documentação conforme jurisdição

## 2. E-mail de produção

Mailpit serve apenas para desenvolvimento.

Produção precisa de serviço SMTP/API e domínio configurado.

## 3. Domínio e HTTPS

- domínio
- certificado TLS
- cookies seguros
- APP_URL correto

## 4. Banco gerenciado

Em produção prefira PostgreSQL gerenciado, backup automático e plano de recuperação.

## 5. Secrets

Nunca envie `.env` real ao GitHub.

Use secret manager da plataforma.

## 6. Observabilidade

Adicionar:

- monitoramento
- alertas
- métricas
- centralização de logs
- rastreamento de erros

## 7. Backup

Defina política de backup e teste restauração.

## 8. Segurança

Antes de uso comercial:

- revisão de autorização
- dependency audit
- rate limiting por endpoints críticos
- políticas de senha
- MFA se necessário
- headers de segurança
- CSP
- revisão de upload se futuramente existir
- pentest conforme criticidade

## 9. Privacidade

Defina:

- política de privacidade
- termos
- retenção de dados
- exclusão/exportação de conta
- requisitos legais do país de operação

## 10. Escala

Depois que houver necessidade real:

- cache externo
- filas
- workers
- CDN
- object storage
- múltiplas instâncias

Não adicione complexidade antes de existir necessidade.
