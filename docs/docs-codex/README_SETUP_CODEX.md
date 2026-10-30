<!-- CODEX: 197 linhas criadas neste arquivo; guia de setup criado integralmente pelo CODEX para a equipe e para o Claude. -->

# Setup do backend Laravel

Este guia prepara a API, o banco MySQL, o cache Valkey, a fila e o Laravel Reverb usados pelos outros três repositórios.

## Requisitos

- Docker com Docker Compose.
- Git.
- Portas livres para API, MySQL, Valkey, RustFS, Mailpit e Reverb.
- Uma chave da Google Maps Platform para cálculo e busca de rotas.

O container oficial do projeto usa PHP 8.5 e Node.js 24. O `composer.json` aceita PHP 8.3 ou superior, mas o fluxo com Docker é o recomendado.

## 1. Criar o ambiente

```bash
cp .env.example .env
```

Revise pelo menos estas variáveis:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=backend_mobilidade
DB_USERNAME=sail
DB_PASSWORD=password

CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=valkey
REDIS_PORT=6379
QUEUE_CONNECTION=database

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=app-local
REVERB_APP_KEY=chave-local
REVERB_APP_SECRET=segredo-local
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

GOOGLE_MAPS_API_KEY=sua-chave

PRECIFICACAO_RAIO_BUSCA_PADRAO_KM=5
PRECIFICACAO_INTERVALO_EXPANSAO_RAIO_SEGUNDOS=30
PRECIFICACAO_INCREMENTO_RAIO_BUSCA_KM=2
PRECIFICACAO_RAIO_BUSCA_MAXIMO_KM=20
```

Dentro da rede Docker, o MySQL atende na porta `3306`. Não use o valor `3606` que ainda aparece no `.env.example` antigo.

Gere valores locais exclusivos para as credenciais do Reverb. Os aplicativos recebem somente `REVERB_APP_KEY`; o segredo permanece no backend.

Para `GOOGLE_MAPS_API_KEY`, use uma chave própria de servidor com **Directions API**, **Geocoding API** e **Roads API** habilitadas. Restrinja essa chave pelo IP do servidor e pela lista de APIs. Não reutilize aqui a chave Android restrita por package name e SHA-1: esse tipo de restrição faz o Roads responder HTTP 403 às chamadas do Laravel. Depois de alterar a chave ou habilitar uma API, execute `php artisan optimize:clear` e teste `POST /api/ajustar-ponto-embarque` com um JWT válido.

O despacho começa no raio definido pela tarifa ou em `PRECIFICACAO_RAIO_BUSCA_PADRAO_KM`. A cada intervalo configurado, soma o incremento ao raio até o limite máximo. Com os valores acima, uma tarifa de 5 km passa para 7 km após 30 segundos, 9 km após 60 segundos e para de crescer em 20 km. Se a tarifa já tiver raio maior que o limite global, o valor da tarifa é preservado.

## 2. Subir os containers

```bash
docker compose up -d --build
docker compose exec laravel.test composer install
```

O Compose inicia:

- API Laravel em `${APP_PORT:-80}`.
- MySQL em `${FORWARD_DB_PORT:-3306}`.
- Valkey em `${FORWARD_VALKEY_PORT:-6379}`.
- RustFS em `9000` e console em `9001`.
- Mailpit SMTP em `1025` e painel em `8025`.
- Reverb dentro do container Laravel em `8080`, gerenciado pelo Supervisor.

## 3. Publicar o Reverb para aparelhos físicos

O `compose.yaml` atual não publica a porta interna do Reverb. Crie `compose.override.yaml` no backend:

```yaml
services:
    laravel.test:
        ports:
            - "${FORWARD_REVERB_PORT:-6001}:8080"
```

Recrie o container:

```bash
docker compose up -d --build
```

A porta externa `6001` evita conflito com o painel Quasar, que usa `8080`. Nos aplicativos móveis, configure `EXPO_PUBLIC_REVERB_PORT=6001`.

## 4. Preparar o Laravel

```bash
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan jwt:secret
docker compose exec laravel.test php artisan migrate
docker compose exec laravel.test php artisan storage:link
```

Em um banco de desenvolvimento vazio, os dados base podem ser criados com:

```bash
docker compose exec laravel.test php artisan migrate:fresh --seed
```

`migrate:fresh` apaga todas as tabelas. Use somente em banco local descartável.

Crie ou atualize as contas fictícias de testes de corrida:

```bash
docker compose exec laravel.test php artisan db:seed --class=CenarioCorridaDesenvolvimentoSeeder
```

O seeder é idempotente e pode ser executado novamente.

## 5. Iniciar a fila

Abra um terminal dedicado:

```bash
docker compose exec laravel.test php artisan queue:work --tries=1 --timeout=90
```

A fila precisa permanecer ativa para que eventos transmitidos pelo Reverb sejam processados sem atraso.

## 6. Contas locais

| Perfil     | Telefone      | E-mail                          | Senha de desenvolvimento |
| ---------- | ------------- | ------------------------------- | ------------------------ |
| Motorista  | `69999990001` | `motorista.codex@example.test`  | `desenvolvimento`        |
| Passageiro | `69999990002` | `passageiro.codex@example.test` | `desenvolvimento`        |

O login por telefone solicita um código e, durante o desenvolvimento, o endpoint devolve esse código para o botão **Receber código**. Esse comportamento deve ser removido antes de produção.

## 7. Ordem para iniciar os quatro projetos

1. Backend e serviços com `docker compose up -d`.
2. Worker da fila.
3. Painel de gestão, quando necessário.
4. Aplicativo do passageiro.
5. Aplicativo do motorista.

## 8. Verificações

```bash
docker compose exec laravel.test php artisan about
docker compose exec laravel.test php artisan route:list
docker compose exec laravel.test php artisan test
docker compose exec laravel.test composer analyse
docker compose exec laravel.test composer lint:test
docker compose logs -f laravel.test
```

Para validar Valkey:

```bash
docker compose exec valkey valkey-cli ping
```

A resposta esperada é `PONG`.

## 9. Acesso pela rede local

Descubra o IPv4 da máquina com `ip -4 addr`. Se o IP for `192.168.1.50` e `APP_PORT` for `80`:

- API para os celulares: `http://192.168.1.50/api`.
- Reverb para os celulares: `ws://192.168.1.50:6001`.
- API para o painel no mesmo computador: `http://localhost/api`.

Libere as portas no firewall local e mantenha computador e celulares na mesma rede Wi-Fi.

## 10. Solução de problemas

- **Erro de banco:** confirme `DB_HOST=mysql` e `DB_PORT=3306` dentro do container.
- **Rotas sem traçado:** confira `GOOGLE_MAPS_API_KEY` e a habilitação das APIs necessárias na Google Maps Platform.
- **Tempo real inativo:** confira worker, porta `6001`, chave compartilhada e logs do `laravel.test`.
- **Cache antigo:** execute `docker compose exec laravel.test php artisan optimize:clear`.
- **Permissão em arquivos:** execute comandos Laravel por `docker compose exec laravel.test` para manter o mesmo usuário do container.

## Segurança

- Nunca versione `.env`, credenciais reais, chaves privadas ou tokens.
- Restrinja as chaves do Google por aplicativo, pacote, assinatura e API permitida.
- Use `APP_DEBUG=false`, HTTPS/WSS e segredos novos fora do ambiente local.
- Não execute `migrate:fresh`, seeders de teste ou contas CODEX em produção.
