# Problemas conhecidos / pendências

## Mapa da home: margem lateral/superior do cartão não funciona — 2026-09-01

Pedido do usuário: mapa da home reduzido, em cartão com margem e cantos
arredondados (igual ao 99), "Para onde vamos?" sempre visível (não mais
sheet colapsável). Implementado em `home.tsx`/`FolhaInferior.tsx`
(`corpoPrincipal` > `mapaCardWrapper` + `folhaInferiorWrapper` estático).

**Funcionou**: altura reduzida do mapa (~320px fixo), cantos
**inferiores** arredondados, `FolhaInferior` virou painel estático (sem
`@gorhom/bottom-sheet`, era o pedido — "Para onde vamos" parar de ficar
colapsado), offset artificial de latitude removido (tanto em `home.tsx`
quanto em `Map.tsx`, 7 ocorrências de `OFFSET_LATITUDE`/`offsetLatitude`
— existia pra compensar o sheet antigo cobrindo a base do mapa, não faz
mais sentido), botão de centralizar localização reposicionado
(`bottom:32,right:16` → `bottom:12,right:12`, mais colado no canto do
cartão menor).

**Não funcionou, apesar de várias tentativas**: margem lateral
(`marginHorizontal`) e superior (`marginTop`) do cartão do mapa — o
`MapView` nativo do Android continua renderizando de ponta a ponta
(edge-to-edge) na horizontal e colado embaixo do header, ignorando
completamente esses dois eixos, mesmo com:
1. `marginHorizontal`/`marginTop` no `StyleSheet` (abordagem óbvia).
2. `width` explícito em pixels (`useWindowDimensions` - 32) em vez de
   depender de flex.
3. `position:"absolute"` com `top`/`left`/`right` explícitos em vez de
   margin (eixo de layout diferente do Yoga, hipótese descartada).
4. `renderToHardwareTextureAndroid`+`needsOffscreenAlphaCompositing`
   (fix documentado no ecossistema RN pra "MapView ignora cantos
   arredondados no Android") — essa combinação **piorou**: o mapa parou
   de renderizar completamente (ficou em branco), só os elementos
   RN puros por cima (botão de localizar, logo do Google) continuaram
   aparecendo. Revertido.
5. `collapsable={false}` no wrapper (hipótese de view flattening do RN
   "achatando" o container) — sem efeito.

Teste de diagnóstico decisivo: coloquei `backgroundColor:"red"` no
wrapper do cartão (temporário) — **nenhum pixel vermelho apareceu em
lugar nenhum**, nem nas bordas onde o mapa "deveria" deixar a cor
aparecer. Ou seja, não é só o `MapView` nativo ignorando o pai — o
próprio `View` wrapper não está sendo posicionado/dimensionado com
`top`/`left`/`right` do jeito esperado nesse ambiente (Expo Go + essa
versão de `react-native-maps`/RN), só a **altura** (via flex ratio OU
`height` explícito) é respeitada de forma confiável.

**Atualização (mesmo dia, sessão seguinte)**: em algum momento entre essas
tentativas, o recorte (`overflow:hidden`+`borderRadius`) passou a
funcionar visualmente — cantos arredondados nos 4 lados e margem lateral
aparente confirmados numa captura de tela ao vivo. Só que isso revelou um
problema NOVO e mais sério: o pontinho azul da localização do usuário
não fica no centro do cartão, fica bem acima do centro vertical. Hipótese
atual: o `overflow:hidden` está recortando corretamente a APARÊNCIA, mas
a câmera interna do `MapView` nativo (a lógica que decide "em que pixel
desenhar esse lat/lng") continua calculando como se o mapa estivesse no
tamanho ERRADO (provavelmente o tamanho que tinha na primeira medição,
antes do cartão assentar no layout final) — ou seja, o recorte visual e a
matemática da câmera não usam a mesma referência de tamanho.

Tentativa adicional: forçar um remonte completo do `MapView` via `key`
somente depois que o wrapper do cartão já reportou um `onLayout` com
dimensões reais (não mais confiando só no primeiro `isMapReady`).
**Testado ao vivo: não resolveu** — o ponto continua sem centralizar
logo após abrir o app, confirmado pelo usuário.

**Atualização final (revert do cartão)**: o cartão do mapa foi revertido
por completo (ver `ui-arquivada-mapa-card-confirmar-destino.md` — pedido
do chefe, não é mais usado). Com o mapa de volta a ocupar a tela inteira
(sem `overflow:hidden` recortando nada), testei de novo ao vivo — **o
ponto ainda não parece perfeitamente centralizado** na primeira
captura de tela após o login (aparece mais pra cima do que o centro
geométrico da tela). Ou seja, o problema pode não ser 100% exclusivo do
cartão — pode ter uma segunda causa (ex.: a região usada na primeira
renderização ainda não é a definitiva, e a câmera nativa não reanima
sozinha até a próxima atualização real de GPS) que não foi isolada.
**Não investigado mais a fundo por prioridade** (sessão seguiu pro
revert visual + documentação). Se for retomar: comparar o
comportamento ANTES de toda essa leva de mudanças desta sessão (pra
saber se já era assim, ou se é regressão de alguma das mudanças em
`Map.tsx`/`home.tsx` mantidas depois do revert — o `useEffect` de
`jaCentralizouAoFicarPronto` e a remoção do `OFFSET_LATITUDE` continuam
ativos mesmo com o cartão revertido).

**Estado atual**: mapa mais baixo (altura reduzida, bom), cantos
arredondados nos 4 lados (bom), mas o ponto de localização não centraliza
direito verticalmente — usuário confirmou ao vivo que não está
centralizando. Não investigado até o fim — pode ser bug específico da
versão do `react-native-maps` (`1.20.1`) combinada com Expo Go, e merece
um teste num build de desenvolvimento de verdade (EAS dev client) antes
de insistir mais em soluções via styling puro, já que Expo Go tem suas
próprias limitações de renderização nativa documentadas nesta sessão
(ver seção sobre a chave de Maps).

## Mapa "não carrega" numa wifi específica — causa real encontrada (2026-09-01)

## Mapa "não carrega" numa wifi específica — causa real encontrada (2026-09-01)

Usuário relatou que numa rede wifi específica o mapa não carregava. Achado
via `curl` direto (não é chute):

- A chave do Google Maps usada pro **SDK nativo do Android**
  (`app.json` → `expo.android.config.googleMaps.apiKey`,
  `AIzaSyD5zpg...dZzZ8`) pertence a um projeto do Google Cloud **com
  faturamento (billing) desativado**. Testando essa chave direto na
  Directions API (mesmo projeto, billing é por projeto, não por produto):
  `REQUEST_DENIED` — `"You must enable Billing on the Google Cloud
  Project"`.
- A chave usada pelo app de verdade pra rota/busca de endereço
  (`EXPO_PUBLIC_GOOGLE_MAPS_API_KEY` no frontend e `GOOGLE_MAPS_API_KEY` no
  backend, `AIzaSyBzAb...5BFs`) é **outra chave, outro projeto**, com
  billing ativo — testada com sucesso (Directions e Places retornaram
  dados reais).
- Ao vivo, testando no dispositivo agora (mesma wifi que o usuário
  mencionou): o mapa **carregou normalmente** depois de alguns segundos
  (tiles, nomes de rua, POIs de Porto Velho, tudo certo) — rodando via
  **Expo Go**. Hipótese mais provável: o Expo Go não usa a chave nativa do
  `app.json` pra pré-visualização (ela só é embutida de fato num build
  real — EAS build/APK/TestFlight), então o problema de billing não afeta
  o app rodando assim. A demora aparente pode ser só a latência mais alta
  dessa rede (~70-140ms de ping até servidores do Google, medido) deixando
  o carregamento de tiles mais lento que o usuário está acostumado, dando
  a impressão de "não carrega".

**Risco real, não cosmético**: num build de produção (APK gerado, TestFlight,
loja), o app VAI usar a chave nativa do `app.json`, e o mapa **vai falhar
de verdade** em qualquer rede, porque o projeto dela tem billing
desativado — isso é uma bomba-relógio, independente da wifi.

**Ação recomendada** (não feita ainda, precisa decisão/acesso do usuário):
ativar billing no projeto do Google Cloud dono da chave
`AIzaSyD5zpg...dZzZ8`, ou trocar essa chave do `app.json` pela mesma chave
já usada e comprovadamente ativa (`AIzaSyBzAb...5BFs`) se ela também tiver
"Maps SDK for Android" habilitado no Cloud Console — mais simples que gerir
duas chaves/dois projetos separados.

## Testes de segurança — fluxo de autenticação — 2026-09-01

Pedido explícito do usuário: testar segurança do fluxo de auth, ignorando o
código de verificação em si (mockado, dev) e a exigência fraca de senha
(decisão já tomada). Testado via curl direto na API.

### Corrigido

- **Nenhum endpoint de auth tinha limite de tentativas** (`auth/login`,
  `auth/register`, `auth/verificar-codigo`, `auth/enviar-codigo`,
  `auth/verifica-se-conta-existe`) — 15-20 chamadas seguidas sem nenhum
  bloqueio, sem nenhum 429. Isso permitia força bruta ilimitada (senha,
  telefone, e principalmente o código de 4 dígitos — só 10 mil
  combinações). Corrigido com `throttle:10,1` (10 requisições/minuto por
  IP) no grupo desses 5 endpoints em `routes/api.php`. Testado ao vivo:
  10 primeiras chamadas → 200, a partir da 11ª → 429.

### Testado e confirmado seguro (nada a corrigir)

- **SQL injection**: tentativas no e-mail do login e no telefone da busca
  de conta — Eloquent parametriza as queries, sem efeito.
- **Mass assignment no cadastro**: enviar `id`, `status`, `is_admin` extra
  no payload de registro — ignorado, porque o controller usa só o array
  validado (`$request->validate()`), nunca `$request->all()`.
- **JWT adulterado**: assinatura alterada ou removida (`alg=none` style) —
  rejeitado com 401 nos dois casos.
- **Vazamento de senha na resposta**: confirmado que nenhuma resposta de
  registro/login retorna o hash da senha.

## Terreno preparado: login social (Laravel Socialite) — 2026-09-01

Pedido do usuário: "prepare o terreno" pra login com Google e Apple, sem
implementar o fluxo ainda (frontend já tem os botões, só visuais, ver
`frontend.md`).

- Instalado `laravel/socialite` (`composer require laravel/socialite -W`).
  Precisou de `-W` porque a versão estável mais recente (v5.31) exige
  `guzzlehttp/guzzle ^6.0|^7.0`, e o projeto tinha guzzle 8.1.0 fixado no
  lock file. Resultado do `-W`: guzzle **rebaixado** pra 7.15.5 (versão
  bem suportada, sem breaking changes relevantes pro projeto) e
  `laravel/framework` subiu de v13.29.0 pra v13.30.0 (patch/minor, sem
  risco). Nenhum pacote core foi rebaixado de forma preocupante — testado
  Pint/Larastan depois, tudo passando.
- `config/services.php`: adicionadas entradas `google` e `apple`
  (`client_id`/`client_secret`/`redirect`), lendo de env vars novas
  (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`,
  `APPLE_CLIENT_ID`, `APPLE_CLIENT_SECRET`, `APPLE_REDIRECT_URI`) — todas
  vazias por enquanto, adicionadas em `.env` e `.env.example`.
- **Não implementado ainda**: rotas, controller, e o fluxo OAuth em si
  (redirect/callback, criar ou logar usuário a partir do provider). Nem
  registrado o driver "Apple" (Socialite core não traz Apple nativamente
  de fábrica em todas as versões — precisa confirmar se a v5.31 inclui ou
  se precisa de um pacote adicional tipo `socialiteproviders/apple` quando
  for implementar de verdade).

## Fluxo de autenticação (login por telefone) — 2026-09-01

Usuário pediu pra caçar todos os erros possíveis no fluxo de auth. Larastan
nível 7 e Pint continuam passando limpos depois de tudo abaixo.

### Corrigido e testado de ponta a ponta (curl direto na API)

- **Bug crítico: `LoginController::verificarCodigo()` nunca comparava o
  código digitado com o código gerado.** Só checava se existia *algum*
  registro no cache pra aquele telefone — qualquer código de 4 dígitos
  autenticava, desde que houvesse uma solicitação pendente. Corrigido pra
  comparar `$codigo === $registro`. Testado: código errado → 401 ("Código
  inválido"), código certo → 200 com token.
- **`AuthProvider.logout()` (frontend) nunca chamava o backend** — só limpava
  `SecureStore` local. O JWT continuava válido no servidor até expirar
  sozinho. Corrigido pra chamar `POST /auth/logout` (já existia e faz
  `$guard->logout()`, ou seja, blacklist do token). Cuidado tomado: o handler
  de 401 automático (`setUnauthorizedHandler`) **não** chama esse `logout()`
  completo — usa uma função local-only (`limparSessaoLocal`), porque se o
  token que gerou o 401 já é inválido, chamar `/auth/logout` de novo geraria
  outro 401 → outro `onUnauthorized` → loop.
- **Bug de robustez no backend: requisição autenticada sem token válido E sem
  header `Accept: application/json` quebrava com 500** em vez de 401. Causa:
  Laravel registra por padrão `redirectGuestsTo(fn () => route('login'))` —
  como essa API não tem rota web `login`, `route('login')` lançava
  `RouteNotFoundException` antes mesmo da `AuthenticationException` ser
  lançada. Corrigido em `bootstrap/app.php`: `redirectGuestsTo(fn () => null)`
  + handler de exceção force JSON pra `AuthenticationException`. **O app real
  nunca foi afetado** (o axios sempre manda esse header) — é hardening pra
  qualquer outro client (Postman, curl, etc). Testado sem o header: 401 limpo
  agora.
- **`register.tsx` (frontend): telefone digitado no login era descartado**
  ao cair na tela de cadastro (por isso `telefone` sempre ficava `null` no
  banco quando o cadastro vinha desse fluxo). Corrigido: `login.tsx` passa o
  telefone como param de rota, `register.tsx` recebe e inclui no payload.
- **`register.tsx`: "conta já existe ou telefone já vinculado"** — agora
  trata explicitamente: se o backend rejeitar por `email`/`telefone`/`cpf`
  duplicado (422), mostra aviso e redireciona pro `/login` depois de 2.5s.
- **`login.tsx`: catch de `verificarSeContaExiste()` era só `console.log`**,
  sem nenhum feedback pro usuário (spinner parava e nada acontecia).
  Corrigido com mensagem de erro visível.
- **`register.tsx`: senha fraca (ex: "123") só dava erro na última etapa**
  (tela de termos), depois de preencher tudo. Adicionada validação real no
  passo da senha (8+ caracteres, 1 maiúscula, 1 número, 1 símbolo — com
  checklist visual ao vivo) e no passo da data de nascimento (18+ anos,
  calculando idade de verdade a partir da data, não só formato). Backend
  espelha as mesmas regras (`regex` pra senha, `before_or_equal` calculado
  pra idade) com mensagens customizadas. Erros do backend pra esses dois
  campos também voltam pra etapa certa (senha → step 3, idade → step 5), não
  mais pra tela de termos.

### Encontrado, não corrigido — decisão do usuário

- **`User` usa `SoftDeletes`, mas `unique:users,email/telefone/cpf` não
  exclui registros soft-deleted.** Uma conta apagada (soft delete) trava
  esses três campos pra sempre — ninguém consegue recadastrar com o mesmo
  e-mail/telefone/CPF depois. Pra corrigir: `Rule::unique('users', 'email')`
  não ajuda sozinho, precisa excluir `whereNull('deleted_at')` explicitamente
  ou decidir se cadastro deve fazer `forceDelete` em vez de soft delete.
  Achado testando o fluxo de logout (criei/apaguei conta de teste e ela
  continuou "ocupando" o telefone).
- **CPF só é validado por tamanho (`size:11`), sem dígito verificador** —
  nem no frontend (`cpf.length === 14` com máscara) nem no backend. Aceita
  qualquer sequência de 11 dígitos formatada como CPF, mesmo inválida
  (ex: `111.111.111-11`).

## Revisão do backend — 2026-08-28

Auditoria ampla (agente Explore) além da já feita em 2026-08-26/27. Larastan
nível 7 e Pint continuam passando limpos depois de todas as mudanças abaixo
(reverificado ao final).

### Corrigido e testado de ponta a ponta

- **Validação adicionada em `MotoristaController` e `VeiculosController`**
  (mesmo padrão do `UsuarioController`): `store()`/`update()` de ambos
  aceitavam `$request->input()` direto, sem validar nada — campo obrigatório
  faltando ou `placa`/`renavam` duplicados (unique constraint) estourava como
  500 cru em vez de 422. `adicionarVeiculoAoMotorista()` também passou a
  validar `motorista_id`/`veiculo_id` (`exists:` nas tabelas). Testado ao vivo:
  payload vazio → 422, payload válido → 201.
- **`VeiculosController::destroy(Veiculo $veiculos)` → `$veiculo`** (singular).
  A rota é `veiculos/{veiculo}` (wildcard singular); o parâmetro plural fazia
  `Str::snake('veiculos')` ('veiculos') não bater com o wildcard ('veiculo'),
  então o binding implícito do Laravel pulava a resolução e injetava um
  `Veiculo` vazio/não salvo em vez do registro pedido pela URL. Só não
  quebrava porque o método é um stub vazio ainda — teria sido um bug real
  assim que alguém implementasse o `destroy`.
- **`.env.example` não tinha `JWT_SECRET`** — adicionado com comentário
  (`php artisan jwt:secret`), já que sem isso um ambiente novo falha com erro
  de token não assinado sem nenhuma pista do que fazer.
- **Foreign keys e índice faltando, corrigidos e aplicados no banco local**:
  `corridas.{motorista_id,passageiro_id,veiculo_id,tarifa_id,produto_id,cidade_id}`,
  `motoristas.user_id`, `motorista_veiculos.{motorista_id,veiculo_id}`,
  `tarifas.{cidade_id,produto_id}` eram todas `integer()` soltas, sem
  `constrained()` — corridas podiam referenciar motorista/veículo/passageiro
  já deletado, e os índices que o próprio código já comentava
  (`// INDEX motorista_id` etc.) nunca existiram.
  - **Como foi resolvido**: `tarifas`, `produtos_corridas` e `municipios` são
    criadas **depois** de `corridas` na ordem de migrations, então não dá pra
    colocar `foreignId()->constrained()` direto no `Schema::create` de
    `corridas`/`tarifas` (a tabela referenciada ainda não existiria nesse
    ponto). Solução: os arquivos de create-table originais ficaram como
    estavam (só limpeza de comentário morto em `corridas`), e uma migration
    nova (`2026_08_28_195915_add_foreign_keys_to_corridas_and_tarifas_tables.php`)
    adiciona todas as 8 FKs + o índice de `status_corrida` via
    `Schema::table(...)->foreign(...)`. Rodei essa migration de verdade
    (`php artisan migrate --step`, não destrutiva) e confirmei via
    `pg_constraint`/`pg_indexes` que todas as constraints existem agora no
    banco local. Testei `POST /auth/register` depois — 201 normal, nada
    quebrou.
  - Isso substitui a abordagem anterior (editar os creates originais) porque
    editar um create-table **já rodado** não muda o banco atual (mesmo
    problema do `produto_categorias` abaixo), e colocar a FK inline nos
    creates originais + também numa migration corretiva causaria erro de
    "constraint already exists" em qualquer `migrate:fresh` futuro.

### Pendente — decisão do usuário (não implementado ainda)

- **Não existe nenhuma autorização no app.** Zero `Policy`/`Gate::`/
  `authorize()` em todo o `app/`. Qualquer usuário autenticado (`auth:jwt`)
  acessa/edita/apaga dado de **qualquer outro usuário** só adivinhando o ID:
  `UsuarioController::show()` (perfil de qualquer um),
  `MotoristaController::show()/update()` (CNH de qualquer motorista),
  `MotoristaDocumentoController` (lista/apaga documento de qualquer
  motorista). O enum `UserType` já tem um papel `gestao`, sugerindo que a
  intenção original era ter controle de acesso por papel — nunca foi
  implementado. Usuário pediu pra só documentar por enquanto, decidir o
  modelo de acesso depois (dono do recurso + `gestao` com acesso total foi a
  sugestão feita, não confirmada).
- **`MotoristaDocumentoController::show(int $motoristaDocumentoId)`** na
  verdade filtra por `motorista_id` e devolve uma lista paginada, não busca
  um documento único pelo próprio ID — nome do parâmetro e semântica REST não
  batem com o que o método faz.
- **`UsuarioController::store()` vs `register()`** — duplicam quase a mesma
  lógica de criar usuário (hash de senha, login JWT, payload de resposta)
  com validação e campos ligeiramente diferentes (`store()` aceita `foto`,
  está atrás de `auth:jwt`; `register()` é público). Risco de divergirem mais
  com o tempo — vale considerar consolidar num serviço compartilhado.

## Pendente de ação manual

- **Migration `produto_categorias` já rodou com `produto_id` como `boolean`** em vez de FK inteira. O arquivo de migration já foi corrigido (`foreignId('produto_id')->constrained('produtos_corridas')`), mas isso **não altera a coluna já criada** no banco local (Postgres via Sail). É preciso rodar `migrate:fresh` (perde dados locais) ou uma migration corretiva (`ALTER TABLE`) antes que `ProdutoCategoria::produto()` funcione de verdade. Não foi executado automaticamente por ser uma operação destrutiva — aguardando autorização do usuário.
- `phpmd/phpmd` está fixado em `3.x-dev` (não uma versão estável) por conflito de dependência com Symfony 8 — ver [ferramentas.md](ferramentas.md). Revisitar quando sair uma versão estável compatível.
- **A Directions API do Google Cloud (usada por `EstimarRotaService` no backend E por `MapViewDirections` direto no app) está bloqueada por falta de faturamento habilitado no projeto.** Testado direto: `status: REQUEST_DENIED`, `error_message: "You must enable Billing on the Google Cloud Project"`. É por isso que a rota nunca é traçada no mapa. Só o usuário pode resolver, em console.cloud.google.com → Faturamento → vincular método de pagamento. (A Places API (New), usada na busca de endereço, funciona sem essa exigência aparecer — só a Directions/Routes parece exigir faturamento ativo.)

## PHPMD — 28 violações reais restantes (2026-08-26)

Depois de suprimir `UnusedFormalParameter` nos controllers (ver [ferramentas.md](ferramentas.md)), sobraram:

- **`app/Http/Controllers/Controller.php`**: `NumberOfChildren` (18 filhos, limite 15) — consequência direta de ter 18 controllers de domínio; não é um problema de verdade, é só o threshold padrão não prever tantos recursos.
- **`app/Http/Controllers/Corrida/CorridaController.php`**: 11 `LongVariable` + 3 `UnusedLocalVariable`, todos dentro do rascunho `store()` (nomes tipo `$estimativaIntinerarioMotoristaAteOrigem`). É o mesmo método WIP já anotado acima — só vale renomear/limpar quando esse método for implementado de verdade.
- **`app/Http/Controllers/Usuario/UsuarioController.php`**: `TooManyPublicMethods` (13, limite 10) — a classe mistura CRUD de usuário + upload de foto + arquivar/restaurar; dá pra separar em outro controller se quiser reduzir isso. + 5x `ShortVariable` (`$id`) — comum em Laravel, baixa prioridade.
- **`app/Services/EstimarRotaService.php`**: `executar()` com 112 linhas e `calcularRota()` com 120 (limite 100) — dá pra quebrar em métodos privados menores. + 2x `ShortVariable` (`$a`, `$b` no `usort`).
- **`database/factories/UserFactory.php`**: `UnusedFormalParameter` em `$attributes` (não está sob `app/Http/Controllers`, então não foi suprimido) — checar se é intencional (padrão de factory state) ou dá pra remover o parâmetro.
- **`database/seeders/DatabaseSeeder.php`**: `ExcessiveMethodLength` (202 linhas, mas quase tudo comentado) + `UnusedLocalVariable` (`$userMotorista`) — é WIP, mesmo do seeder já mencionado acima.

Nenhum desses foi corrigido ainda — são finding reais mas de baixa urgência (nenhum quebra nada), deixados para o usuário decidir prioridade.

## Incidentes de infraestrutura

- **2026-08-27**: `POST /api/auth/register` retornando 500 pro app mobile (log de acesso mostrava o 500, mas `storage/logs/laravel.log` não tinha nenhuma entrada nova — pista de que o próprio log estava quebrado). Causa: comandos `docker exec` rodados sem `-u sail` (entram como root por padrão) deixaram ~2400 arquivos em `storage/` e `bootstrap/cache/` com dono `root`; o PHP-FPM real roda como `sail` e ficou sem permissão de escrita em log/cache/sessão. Corrigido com `docker exec -u root <container> chown -R sail:sail storage bootstrap/cache`. Reproduzi `POST /auth/register` direto (curl dentro do container) depois do fix e voltou 201 normal — apaguei o usuário de teste criado (`teste.debug@example.com`, telefone `69999912504`) logo em seguida pra não colidir com o teste real do usuário. Ver [ferramentas.md](ferramentas.md): a partir de agora, **sempre `docker exec -u sail`**.

## Incidentes de git

- **2026-08-26**: `.claude/settings.local.json` foi commitado sem querer (commit `dde5481`, já estava em `origin/dev`). Corrigido com `git rm --cached` + `git commit --amend --no-edit` (sem alterar mensagem, sem co-author) + `git push --force-with-lease origin dev` → novo hash `ee5af2a`. Adicionado `/.claude/settings.local.json` ao `.gitignore` pra não repetir. Push precisou ser feito via `flatpak-spawn --host git ...` (sandbox não tem credenciais do GitHub) — ver [ferramentas.md](ferramentas.md).
- Regra geral do projeto: Claude nunca faz `git add`/`commit`/`push` por conta própria — só quando o usuário pede explicitamente naquele momento.

## Corrigidos em 2026-08-26 (histórico, útil se algo estranho aparecer)

- `EstimarRotaService` chamava `array_first()`/`array_last()` (funções que não existem mais no Laravel) → `Arr::first()`/`Arr::last()`.
- `UsuarioController::store()` chamava `$user->createToken()` (Sanctum) num model sem `HasApiTokens` → trocado para JWT, igual ao `register()`.
- `CorridaFinaceiroController` tinha type-hint para classe inexistente (`CorridaFinaceiro` em vez de `CorridaFinanceiro`).
- `routes/api.php`: `corridas-negociacoes` apontava para `CorridaController` em vez de `CorridaNegociacoesController`; rota `corridas-negociada` chamava método inexistente (criado, ligado ao `SimularCorridaNegociadaService` que já existia mas não era usado).
- `MotoristaVeiculo`: as duas relations usavam a mesma FK (`motorista_id`), e `motorista()` apontava para `User::class` em vez de `Motorista::class`.
- `database/factories/PrudutoCategoriaFactory.php` (typo) → renomeado para `ProdutoCategoriaFactory`.
- `database/factories/CorridaDestinoFactory.php` tinha `@extends Factory<CorrridaDestino>` (typo, classe não existia).
- `config/app.php` tinha import morto de `SebastianBergmann\FileIterator\Facade` (removido pelo Pint).
- **2026-08-27**: `CorridaController::buscarEndereco()` usava a **Places API legada** do Google (`maps.googleapis.com/maps/api/place/textsearch/json`), que não estava habilitada no projeto do Google Cloud da chave `GOOGLE_MAPS_API_KEY` (`status: REQUEST_DENIED`, confirmado testando direto). Migrado pra **Places API (New)** (`POST places.googleapis.com/v1/places:searchText`, com headers `X-Goog-Api-Key`/`X-Goog-FieldMask` em vez de query string, e campos de resposta `places[].displayName.text`/`formattedAddress`/`location.{latitude,longitude}` em vez de `results[].name`/`formatted_address`/`geometry.location.{lat,lng}`). Testado de ponta a ponta com resultado real.
- **2026-08-27**: junto com o acima, `buscarEndereco()` fazia `return response()->json(null)` quando não havia resultado — mas `response()->json(null)` no Laravel serializa pra `{}` (objeto vazio, *truthy* em JS), não pra `null`. O front (`FolhaBuscarEndereco.tsx`/`ParaOndeVamos.tsx`) via `{}` como sucesso, desestruturava campos inexistentes e mostrava um item de busca com nome em branco. Corrigido pra `response()->json(null, 404)` — agora o axios cai no `catch` de verdade, e o front foi ajustado pra limpar a lista de resultados (`setListaEnderecos([])`) nesse caso.

## Ainda incompleto (não é bug, é WIP deixado pelo dev original)

- `CorridaController::store()` monta dados de estimativa de rota mas não persiste nada nem retorna resposta — claramente um rascunho.
- Vários controllers (`CorridaDestinoController`, `CorridaDescontoController`, `CorridaFinaceiroController`, `AvaliacoesCorridaController`, `CorridaNegociacoesController`, `StatusBuscaController`) têm todos os métodos como stub (`//`). As rotas existem (`Route::apiResource(...)`) mas não fazem nada ainda.

## 2026-10-27 — Pontos de embarque sugeridos removidos

`GET /api/pontos-embarque` (`CorridaController::pontosDeEmbarque` e
`PontosDeEmbarqueService`, Google Geocoding v4 "destinations") foi removido, com o
teste `PontosDeEmbarqueTest`, a pedido do usuário: a busca de locais próximos na
escolha do embarque dava problema e o embarque voltou a ser só o ponto marcado.
`AjustarPontoEmbarqueService` (ajuste para a via mais próxima) continua. O código
antigo está em `git show 3578c97 -- app/Services/PontosDeEmbarqueService.php`.
