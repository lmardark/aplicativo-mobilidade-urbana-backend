# Frontend (app-mobilidade-urbana-frontend-passageiro)

Projeto irmão (React Native / Expo Router, SDK 54), em
`../app-mobilidade-urbana-frontend-passageiro` (fora deste repo). Auditado e
corrigido em 2026-08-27 na mesma sessão que o trabalho do backend.

> ⚠️ **2026-09-02**: o chefe não gostou do visual novo (mapa em cartão
> reduzido + tela de confirmar destino) — revertido de volta pro mapa
> tela cheia e pro fluxo direto (sem passo de confirmação), mantendo só
> os bugs corrigidos nesse meio tempo. As seções abaixo sobre "mapa
> reduzido em cartão", "ConfirmarEndereco" e "FolhaInferior estático"
> descrevem uma UI que **não está mais ativa** — ficou arquivada em
> `ui-arquivada-mapa-card-confirmar-destino.md` pra uso futuro.

## Redesign do fluxo de login/cadastro — 2026-09-01

Primeira etapa do redesign de UI/UX (prioridade combinada: login/cadastro →
home/mapa → pedido de corrida → telas secundárias). Paleta escolhida pelo
usuário: **"Petróleo / Teal"** — cor ainda não usada por 99/Uber/inDrive/
Urbano Norte. Nome do app definido pelo usuário: **"67"**.

- **`src/theme/colors.ts`** (novo): paleta centralizada — `primary`
  (`#0F5257`, petróleo), `accent`, `background`, `text`/`textSecondary`/
  `textMuted`, `border`, `disabledBg`/`disabledText`, `error`/`errorLight`.
  Todas as telas de auth (`login.tsx`, `loginEmail.tsx`, `register.tsx`)
  migradas dos hex soltos (`#FF5500`, `#FFD200`, "99" amarelo/laranja — visual
  praticamente clonado do 99) pra essa paleta.
- **`src/components/AppLogo.tsx`** (novo): logo "67" com dois carrinhos
  (`FontAwesome5 car-side`) flanqueando, virados pra fora (esquerda espelhado,
  direita original), alinhados na baseline do texto (não centralizado — teve
  que compensar o respiro que `Text` deixa abaixo da linha de base numérica
  com `marginBottom` calculado). Animação de entrada: carros começam pequenos
  e sobrepostos ("atrás" do texto) e crescem até a posição final
  (`Animated.timing`, 1400ms — ajustado depois de feedback de "rápido
  demais"). Usado nas 3 telas de auth.
- **`src/components/ErrorBanner.tsx`** (novo): banner de erro padronizado
  (ícone + fundo `errorLight` + texto), substituindo `<Text style={erroText}>`
  solto que ficava mal posicionado. Usado em `login.tsx`, `loginEmail.tsx`,
  `register.tsx` (cadastro geral, senha, idade).
- **`src/components/icons/GoogleIcon.tsx`** (novo): logomarca oficial do "G"
  colorido do Google via `react-native-svg` (paths oficiais, 4 cores) —
  usuário foi explícito que o ícone do Google **não pode** ser recolorido pra
  combinar com o app, tem que ficar reconhecível.
- **Login social**: adicionados botões "Continuar com Google" e "Continuar
  com Apple" (Apple só em iOS, seguindo a HIG) em `login.tsx`. **Só visual —
  não fazem OAuth de verdade.** Isso exige credenciais que só o usuário pode
  gerar (Client ID OAuth no Google Cloud Console, conta paga de Apple
  Developer) + endpoints novos no backend pra validar o token de cada
  provedor. `onPress` atual só mostra "em breve" no `ErrorBanner`.
- **Inputs modernizados**: trocado o padrão "underline embaixo do campo" por
  caixa com borda arredondada (`borderRadius: 16`) nas 3 telas — inclui o
  campo de telefone (com seletor de país embutido na mesma caixa, texto
  centralizado) e os campos de cadastro.
- **Removido**: badge fake "🤑 Receba cupom de desconto" (login.tsx, não
  existe cupom nenhum de verdade); ícone de "adicionar contato" no campo de
  telefone (não tinha `onPress`, não fazia nada, só confundia); botão de
  voltar em `login.tsx` (essa tela é a raiz real do app — `index.tsx`
  redireciona direto pra `/login` quando deslogado, não existe tela anterior
  pra voltar). `loginEmail.tsx`/`register.tsx` mantiveram o botão de voltar
  porque ali tem destino de verdade (voltam pro `/login`).
- **Transições de tela**: `Stack` raiz (`_layout.tsx`) ganhou
  `screenOptions={{ animation: "slide_from_right" }}` como padrão (antes era
  o default abrupto do native-stack), com `fade` só nas entradas/saídas do
  fluxo de auth pro app principal (`index`, `splash`, `(main)`).

### Validação (senha forte + idade mínima) — pedido explícito do usuário

- Senha: mínimo 8 caracteres, 1 maiúscula, 1 número, 1 símbolo. Checklist
  visual ao vivo no passo 3 do cadastro (cada regra fica verde conforme é
  satisfeita), em vez de só bloquear o botão sem explicar o porquê.
- Idade: mínimo 18 anos, calculado de verdade a partir da data de nascimento
  (não só validando o formato) — trata corretamente o caso de "faz 18 anos
  amanhã, ainda tem 17 hoje" (testado via script Node isolado, ver
  `problemas-conhecidos.md`).
- Backend espelha as duas regras (`regrasCadastro()`/`mensagensCadastro()`
  em `UsuarioController`) — ver `problemas-conhecidos.md` pro detalhe.

## Deslogava sozinho + mapa não centralizava no primeiro login — 2026-09-01

Dois bugs reais reportados pelo usuário depois de testar bastante ao vivo:

- **Voltava pro login mesmo autenticado**: `src/Services/api.ts` tinha um
  interceptor de resposta do axios que chamava o handler global de
  "deslogar" em **qualquer** 401, de **qualquer** endpoint — incluindo
  endpoints públicos como `verificar-codigo` (que legitimamente responde
  401 pra "código errado", nada a ver com sessão expirada). Um 401
  vindo de uma tela pública enquanto o usuário estava logado em outra
  aba derrubava a sessão de verdade (`limparSessaoLocal()` +
  `router.replace("/login")` em `AuthProvider.tsx`). Corrigido: o
  interceptor agora só dispara o logout automático se a requisição que
  falhou **tinha mesmo um Bearer token anexado** (`error.config.headers.
  Authorization`) — só nesse caso um 401 significa "seu token não vale
  mais".
- **Mapa não centralizava na localização real no primeiro login**: sem
  cache de localização salvo ainda (`@last_user_location` no
  `AsyncStorage`), é comum o GPS retornar a primeira posição **antes**
  do `MapView` terminar de inicializar. O `animateToRegion` que
  centraliza o mapa só rodava se `isMapReady` já fosse `true` nesse
  exato momento — e como a flag `userInitialRegion.current` já ficava
  marcada como "resolvido" nesse primeiro disparo, a tentativa nunca
  era repetida depois. Resultado: mapa carregava no `DEFAULT_REGION`
  (São Paulo) e nunca ia pra localização de verdade. Corrigido com um
  efeito adicional em `Map.tsx` que centraliza assim que `isMapReady`
  fica `true`, usando a localização já resolvida (se houver) — com uma
  ref (`jaCentralizouAoFicarPronto`) garantindo que só faz isso uma vez
  (senão brigaria com atualizações de localização em tempo real, que
  também setam `userLocation`).

Ambos verificados: `tsc`/`eslint`/`prettier` limpos.

## Histórico de endereços no FolhaInferior — 2026-09-01

Pedido do usuário: o painel estático da home mostrava sempre os mesmos 2
endereços mockados ("Av. Paulista, 1000" / "Rua das Flores, 45"), mesmo
depois de já ter buscado endereços de verdade. Trocado pra ler do mesmo
cache que `ParaOndeVamos.tsx` já usa (`@historico_enderecos` no
`AsyncStorage`) — mostra as buscas recentes de verdade agora. Recarrega
toda vez que o painel remonta (ele já desmonta/remonta sozinho quando
`ParaOndeVamos`/`ViagemComParada` abrem e fecham, então pega histórico
novo automaticamente sem precisar de lógica extra). Estado vazio (sem
nenhuma busca ainda) mostra um texto explicativo em vez de lista vazia.
Toque num item do histórico abre `ParaOndeVamos` (mesmo comportamento do
campo de busca) — **não** preenche o destino direto ainda, isso exigiria
passar `itinerario`/`setItinerario` como props novas pro `FolhaInferior`,
não fiz por enquanto pra manter o escopo do pedido.

## Bugs encontrados testando login de novo ao vivo — 2026-09-01

- **`CodigoVerificacao.tsx` sem o `CarrosAleatorios`**: era a única das 4
  telas de auth sem o easter egg de fundo — usuário notou a
  inconsistência visual. Adicionado igual às outras 3.
- **Código de verificação mockado expirava rápido demais**:
  `LoginController::enviarCodigo()` guardava o código fake no cache com
  TTL de só 60 segundos. Testando manualmente (não via Maestro automático,
  que é rápido), é fácil levar mais de 60s entre pedir o código e
  confirmar — dava um 401 "de verdade" mesmo o fluxo sendo só visual/dev.
  TTL aumentado pra 30 minutos (`now()->addMinutes(30)`). Verificado:
  Pint/Larastan nível 7 limpos.
- **Sessão expirou durante teste manual**: não é bug — token JWT expirou
  de verdade depois de ~2h de sessão de testes, o app corretamente
  deslogou (não ficou em loop). Confirmado via logcat:
  `AxiosError: Request failed with status code 401` em
  `buscar-endereco` (rota protegida) — comportamento esperado, só
  achei bom documentar que não é regressão.
- **401 persistindo mesmo depois do TTL de 30min**: confirmei via `curl`
  direto (pedir código, esperar 65s, verificar) que o backend sozinho
  funciona perfeitamente — o TTL não era mais o problema. Causa real:
  `CodigoVerificacao.tsx`'s botão "Receber código" **não tinha nenhuma
  trava contra clique duplo**. Cada toque gera um código NOVO no backend
  (sobrescrevendo o anterior no cache); se tocado duas vezes, a primeira
  chamada (que só confirma o código 1s depois, por causa da animação de
  preenchimento) acaba verificando um código que a segunda chamada já
  substituiu — 401 "de verdade", mesmo com TTL generoso. Corrigido com
  um estado `recebendoCodigo` que desabilita o botão (visualmente
  cinza + `disabled`) do toque até a verificação daquele código
  específico realmente terminar (sucesso ou erro).

## Ajustes finais no fluxo de auth (pós-revert) — 2026-09-01

Depois do revert de design (ver `redesign-petroleo-teal-arquivado.md`),
mais uma rodada de ajustes pedidos pelo usuário nas mesmas telas
(`login.tsx`, `loginEmail.tsx`, `register.tsx`):

- **Senha simplificada**: removida a exigência de maiúscula/número/símbolo
  — fica só mínimo de 8 caracteres (frontend E backend,
  `UsuarioController::regrasCadastro()`). O checklist visual de requisitos
  foi removido, virou só um texto "Mínimo de 8 caracteres".
- **Fonte dos inputs reduzida** (24→18 em `login.tsx`, 22→18 em
  `register.tsx`/`loginEmail.tsx`) — o placeholder mais longo do telefone
  (`(69) 91234-5678`) estava sendo cortado nas bordas em telas menores.
- **Layout padronizado com rodapé fixo**: o usuário gostou especificamente
  do padrão de `loginEmail.tsx` (botões de navegação ancorados embaixo da
  tela, acima do teclado, via `flex:1 + justifyContent:"space-between"` —
  chamado de `stepContainer` no styles). Esse padrão foi replicado pra
  TODAS as etapas do `register.tsx` (6 steps reestruturados) e pro
  `login.tsx`. Antes, o conteúdo ficava colado no topo com bastante espaço
  vazio sobrando embaixo, principalmente em etapas com só um campo.
- **Tecla "avançar" do teclado**: todo `TextInput` do fluxo agora tem
  `returnKeyType="go"/"next"/"done"` (dependendo se o campo está válido e é
  o último de dois campos numa etapa) + `onSubmitEditing` chamando a mesma
  ação do botão — dá pra completar cada etapa sem tirar o dedo do teclado.
  No step 5 do cadastro (CPF + data de nascimento, dois campos), o CPF usa
  `returnKeyType="next"` + um `ref` (`dataNascimentoRef`) pra pular o foco
  pro campo de data.
- **Badge "Acesse sua conta com segurança"** removida do `loginEmail.tsx`
  (pedido do usuário, sem justificativa específica além de não gostar).
- **Easter egg**: `src/components/CarrosAleatorios.tsx` (novo) — carrinhos
  decorativos que atravessam a tela em linha reta, em intervalos aleatórios
  (nunca sincronizados), com leve tremor (efeito "motor ligado") e um
  rastro tipo faixa de pista ("-") que vai surgindo atrás do carro e
  sumindo com fade-out. Mantém a proporção do ícone (`FontAwesome5
  car-side`), só varia o tamanho geral. Usa a mesma lógica de espelhamento
  (`scaleX: -1`) do `AppLogo` pro carro apontar na direção que anda.
  Renderizado como camada de fundo (`position:absolute`,
  `pointerEvents="none"`) nas 3 telas de auth. Intervalo de aparição:
  900ms–3.5s entre cada carro (ajustado pra "mais carrinhos" a pedido do
  usuário), duração do cruzamento 4–7s — múltiplos carros costumam
  coexistir na tela.
- **`AppLogo` cor própria**: não usa mais `colors.primary` (paleta que foi
  revertida) — tem uma constante `LOGO_COLOR` fixa (preto, a pedido do
  usuário), já que o logo em si foi a única parte do redesign aprovada e
  não deve mudar se a paleta ao redor mudar de novo no futuro.

### Nota sobre testes automatizados neste redesign

Tentativa de cobrir o fluxo de cadastro via Maestro (digitar e-mail/senha)
esbarrou de novo na mesma limitação já documentada: a injeção de texto via
ADB do Maestro não é confiável com `TextInput` controlado do RN nesse
ambiente (funciona uma hora, não funciona na próxima, mesma sequência exata
de comandos — não é problema de script). Duas interrupções externas reais
também atrapalharam tentativas (ligação recebida, notificação do WhatsApp
cobrindo a tela no momento do toque). A lógica de validação (senha/idade) foi
verificada à parte via script Node standalone reproduzindo as mesmas funções
puras — 10/10 casos de teste passaram, incluindo o edge case do aniversário.
O visual foi conferido via screenshot real no dispositivo a cada ajuste
pedido pelo usuário.

## Otimização de consumo de API (ideias 4/5/6) — 2026-09-01

Implementadas as 3 ideias aprovadas da lista de otimização de consumo de
API dada nesta sessão:

- **#4 — cache backend de busca de endereço**: `CorridaController::
  buscarEndereco()` agora envolve a chamada da Places API em
  `Cache::remember($chave, now()->addHours(6), fn() => ...)`, chave =
  `'busca-endereco:'.md5(mb_strtolower(trim($endereco)))`. Endereços
  populares buscados por usuários diferentes reaproveitam a mesma resposta.
  Testado ao vivo: 1ª chamada 1.471s (API real), 2ª chamada idêntica
  0.035s (~42x mais rápido, cache hit confirmado). Limitação conhecida:
  `Cache::remember()` não cacheia retornos `null` (resultado "não
  encontrado") — aceitável, o guard de mínimo 3 caracteres já filtra a
  maioria das buscas inúteis.
- **#5 — cache local persistente de busca (frontend)**: `ParaOndeVamos.tsx`
  ganhou um cache via `AsyncStorage` (`@cache_busca_enderecos`, TTL 24h,
  máximo 50 entradas, mais antigas descartadas). Busca primeiro no cache
  local antes de bater na API.
- **#6 — cache de geocodificação reversa + intervalo do watch**:
  `Map.tsx` ganhou `cacheGeocodeReverso` (módulo-level `Map` — cuidado,
  precisou `new globalThis.Map()` porque o componente já se chama `Map`,
  sombreando a classe global) keyed por coordenada arredondada a 4 casas
  decimais, TTL 10min — evita repetir geocodificação reversa se o usuário
  está parado/quase parado no mesmo lugar. `watchPositionAsync` ajustado de
  `timeInterval:5000, distanceInterval:10` pra `8000/20` (menos
  atualizações = menos geocodificação disparada).

## Easter egg dos carrinhos — ajuste de rastro

`CarrosAleatorios.tsx`: a pedido do usuário, o rastro tracejado atrás de
cada carrinho ficou mais longo/espalhado — `INTERVALO_RASTRO_MS` 220→140
(tracinhos mais próximos) e `DURACAO_FADE_RASTRO_MS` 900→1800 (cada
tracinho demora mais pra sumir, então mais aparecem juntos na tela).

## Bug da rota fantasma — corrigido e confirmado ao vivo — 2026-09-01

Ver detalhes completos em `pendente-fluxo-corrida-mapa.md`. Resumo: a seta
de voltar da `FolhaEscolherOferta` (`handleVoltarParaHome` em `home.tsx`)
não resetava o itinerário como o voltar da `ViagemComParada` já fazia —
por isso a rota ficava desenhada no mapa mesmo depois de "cancelar".
Corrigido espelhando a lógica de `handleCancelarViagem`. **Confirmado ao
vivo no dispositivo**: gerar rota → ir até a tela de escolher oferta →
voltar → mapa limpo, sem rota fantasma.

## Bug do texto "Par" cortado nos marcadores de parada — corrigido

`Map.tsx`: os marcadores de "Parada N" no mapa às vezes renderizavam com
o texto cortado (“Par” em vez de “Parada 1”) — bug clássico do
`react-native-maps` no Android, que tira uma "foto" (snapshot) do
conteúdo customizado do marker antes do texto terminar de fazer layout,
quando `tracksViewChanges` está `false`. Corrigido com
`tracksViewChanges={true}` explícito nos markers do itinerário — como o
itinerário tem no máximo ~5 itens, o custo de performance é desprezível.

## Paradas: reordenar virou arrastar-e-soltar (drag-and-drop) — 2026-09-01

Pedido do usuário depois de ver as setas ▲▼ funcionando: "seria melhor se
fosse drag and drop". Trocado por `react-native-draggable-flatlist`
(instalado, compatível com `react-native-gesture-handler`/`reanimated` já
usados pelo `@gorhom/bottom-sheet`).

- Em `ViagemComParada.tsx`: a origem (sempre primeira) e o slot vazio de
  "adicionar parada" (sempre último, quando existe) ficam **fora** do
  `DraggableFlatList` — só as paradas realmente preenchidas do meio são
  arrastáveis. `keyExtractor` usa `${lat}-${lon}-${name}` (estável mesmo
  quando a ordem muda, ao contrário de um índice).
- **Conflito de gesto com o BottomSheet**: os dois gestos de pan (arrastar
  parada vs. arrastar o sheet) brigavam entre si. Resolvido com um estado
  `arrastandoParada` que desliga `enableContentPanningGesture`/
  `enableHandlePanningGesture` do `BottomSheet` enquanto uma parada está
  sendo arrastada (`onDragBegin`/`onDragEnd` do `DraggableFlatList`).
- **UX do handle**: inicialmente usava `onLongPress` (precisava segurar
  ~150ms antes de poder arrastar) — usuário achou o delay incômodo depois
  de testar ao vivo. Trocado pra `onPressIn` — arrasta assim que encosta
  no ícone ≡ (`reorder-three`), sem precisar segurar.
- **Pendente/observado**: o usuário relatou que, depois de soltar uma
  parada, existe um pequeno intervalo antes de conseguir iniciar outro
  arraste. Não investigado a fundo ainda — suspeita é alguma animação de
  "assentar" interna do `react-native-draggable-flatlist` após o drop.
  Baixa prioridade, não bloqueia o uso.
- Confirmado ao vivo no dispositivo: arrastar "R. Santa Luzia, 600" por
  cima de "R. Buenos Aires, 23..." trocou a ordem (1↔2), o marcador de
  destino no mapa e a rota atualizaram junto, e o BottomSheet não fechou
  nem foi arrastado junto (a mitigação do conflito de gesto funcionou).

## Passo novo: confirmar endereço antes de ver preços — ✅ concluído (2026-09-01)

Pedido do usuário: revisar todo o fluxo de "pedir corrida". Implementado,
testado ao vivo no dispositivo e confirmado funcionando.

- **`src/components/corrida/ConfirmarEndereco.tsx`** (novo): tela cheia
  entre escolher o destino e ver os preços (`FolhaEscolherOferta`).
  Decisão inicial foi mapa estático (sem pan/zoom), mas o usuário pediu
  depois pra deixar **interativo** (mover/aproximar de verdade) — revertido
  esse ponto. Funcionalidades:
  - Mapa interativo (`MapView` sem `scrollEnabled`/`zoomEnabled` travados)
    mostrando **origem e destino** (antes só mostrava o destino — precisou
    de uma prop `origem` nova).
  - `fitToCoordinates([origem, destino], {edgePadding, animated:true})`
    num `useEffect` (com `mapRef`) pra enquadrar os dois pontos assim que
    a tela abre, mesmo padrão já usado em `Map.tsx` pra rotas.
  - `Polyline` tracejada (`lineDashPattern={[8,8]}`) ligando origem→destino.
  - Distância em linha reta (haversine, calculada no próprio componente —
    não existia utilitário de distância real no frontend, só valores mock
    tipo `"5.4km"` fixo em `FolhaBuscarEndereco.tsx`) num badge no topo
    do mapa ("≈ X km em linha reta"). É só um contexto de escala rápido;
    a distância/tempo de rota de verdade continua vindo depois, na tela
    de preços, via Directions API.
  - Card com nome+endereço formatado, botão "Confirmar destino" fixo
    embaixo — mesmo padrão visual do fluxo de login (botão sempre no
    rodapé, cores consistentes #FFD200/#FF5500/#000).
- **`home.tsx`**: novo estado `showConfirmarEndereco` +
  `origemConfirmacao` (`"para-onde-vamos" | "viagem-com-parada"`, pra
  saber pra qual tela reabrir se o usuário apertar "voltar" na
  confirmação) + prop `origem={partida}` pro `ConfirmarEndereco`. Tanto
  `ParaOndeVamos.onSucesso` quanto `ViagemComParada.onConfirmar` agora
  abrem `ConfirmarEndereco` em vez de ir direto pra `FolhaEscolherOferta`.
- **Ícones dos pinos**: inicialmente usei `pinColor` do `Marker` padrão
  pra diferenciar origem/destino — não funcionou, os dois apareciam
  vermelhos e idênticos (no Android, `pinColor` só aceita hue, não hex,
  limitação conhecida do `react-native-maps`). Trocado por conteúdo
  customizado: origem = bolinha branca com centro preto (mesmo visual do
  `Map.tsx`). Primeira tentativa do destino usava um ícone de pin
  (`Ionicons "location"`) dentro do círculo laranja — usuário notou que
  ficava "torto"/assimétrico, porque o formato de gota do ícone não é
  simétrico dentro de um círculo. Trocado por bolinha laranja com
  pontinho branco no centro, espelhando exatamente o padrão da origem
  (só invertendo as cores) — fica visualmente simétrico e consistente.
  `tracksViewChanges={true}` nos dois (mesma lição do bug do "Par"
  cortado — só 2 markers aqui, custo desprezível).
- Testado ao vivo: mapa interativo funcionando, dois pinos diferenciados
  (origem preta, destino laranja), tracejado preto ligando os dois, badge
  de distância correto (testado com 1.6km e 5.4km entre pontos diferentes),
  enquadramento automático ajustando zoom/posição pros dois pontos caberem
  na tela. `tsc`/`eslint`/`prettier` limpos.

## Reorganização de `src/components/` em pastas por domínio — 2026-09-01

Pedido do usuário: "existe muitos componentes soltos, poderia criar novas
pastas se necessário e reorganizar, para não ficar poluído e confuso?"
~50 arquivos soltos direto em `src/components/` foram movidos pra pastas
por domínio (mantendo as que já existiam: `usuario/`, `central-de-ajuda/`,
`icons/`):

- `common/` — `AppLogo.tsx`, `ErrorBanner.tsx` (genéricos, não amarrados a
  nenhum domínio específico).
- `auth/` — `CarrosAleatorios.tsx`, `CodigoVerificacao.tsx`.
- `corrida/` — `Map.tsx`, `ParaOndeVamos.tsx`, `FolhaBuscarEndereco.tsx`,
  `FolhaInferior.tsx`, `MenuInferior.tsx`, `HistoricoCorridas.tsx`,
  `HistoricoCorridasDetalhes.tsx`, `Buscando.tsx`, `DefinirDestino.tsx`,
  `ConfirmarEndereco.tsx` (novo, ver seção acima) — além do
  `ViagemComParada.tsx`/`FolhaEscolherOferta.tsx` que já estavam lá.
- `entrega/`, `pagamento/`, `motorista/`, `layout/`, `preferencias/` —
  demais componentes agrupados por assunto (motorista = telas de
  ganhos/ofertas do lado motorista, aparentemente não usadas ainda por
  nenhuma rota — `Radar.tsx`/`GanhoDiario.tsx` confirmados órfãos, sem
  nenhum import em lugar nenhum).
- `ListaRevisarMinhaTarifa.tsx` e `CentralAjuda.tsx` movidos pra dentro de
  `central-de-ajuda/` (eram os únicos dois arquivos soltos que na
  verdade pertenciam a esse domínio).

**Processo**: script Python normalizou todos os imports (tanto
`@/components/Nome` quanto relativos `./Nome`/`../Nome`) pra sempre usar
o alias `@/components/<novo-caminho>` antes de mover os arquivos —
evitou ter que recalcular profundidade de `../` manualmente pra cada
arquivo. Depois dos `mv`, sobrou limpar à mão: 2 `require()` de assets
estáticos (`../../assets/...`) que precisavam de mais um `../` (não são
pegos pelo `tsc`, só quebram em runtime no Metro), e 2 bugs de tipo
pré-existentes e não relacionados encontrados de brinde rodando `tsc`
no projeto todo (`FolhaInferiorMotorista.tsx` usava `user.nome` em vez
de `user.name`; `InformacoesDestinario.tsx` não passava `servico`/
`onSelecionarEndereco` pro `FolhaBuscarEndereco`). Verificado:
`tsc`/`eslint`/`prettier` limpos no projeto inteiro depois.

**Cuidado pra próxima sessão**: depois de mover muitos arquivos de uma
vez, o Metro pode mostrar erros de "Unable to resolve" antigos/story do
bundle anterior por um tempo (não é regressão nova) — conferir sempre o
estado atual dos arquivos com `tsc` (que lê do disco, não usa cache do
Metro) antes de assumir que algo quebrou de verdade.

## Reorganização de `routes/api.php` (backend) — 2026-09-01

Mesmo pedido do usuário, só que pro `routes/api.php` do backend: dividido
em `routes/api/{auth,usuario,veiculo,motorista,passageiro,corrida,tarifa,
produto,estimativa}.php`, um por domínio de controller (espelha
`App\Http\Controllers\<Dominio>`). `routes/api.php` agora só monta os
dois grupos de middleware (`throttle:10,1` pras rotas públicas de auth,
`auth:jwt` pro resto) e dá `require` em cada arquivo de domínio — os
arquivos de domínio não se preocupam com middleware, herdam do grupo que
os inclui. Verificado: `route:list` mostra as 98 rotas certinho, Pint e
Larastan nível 7 limpos.

## Conta de teste recriada com telefone errado — lição aprendida

Ver `testes-maestro.md`, seção "SEMPRE copiar o telefone, nunca digitar
de memória" — perdi tempo testando com um número de telefone diferente
por 1 dígito do que o Maestro realmente digita, o que fazia o app
(corretamente) tratar como conta inexistente e ir pro cadastro, mesmo com
uma conta "Teste" de fato existindo no banco (só que com o telefone
errado).

## Diagnóstico: mapa "não carrega" numa wifi específica

Ver `problemas-conhecidos.md`. Achado real, verificado via `curl`: a
chave nativa do Google Maps (`app.json` →
`android.config.googleMaps.apiKey`) pertence a um projeto do Google Cloud
com **billing desativado** — isso vai quebrar o mapa de verdade num build
de produção (EAS/APK), independente de wifi. No Expo Go (like o
dispositivo de teste usado nesta sessão) o mapa carregou normal, só mais
devagar que o de costume nessa rede — hipótese é que o Expo Go não usa
essa chave nativa pra pré-visualização.

## Ferramentas configuradas

- **ESLint**: já existia (`eslint-config-expo`). Zerados os 20 erros reais
  encontrados (ver abaixo); restam só warnings de `react-hooks/exhaustive-deps`
  (esperado em componentes de animação, não corrigido em massa de propósito).
- **Prettier**: não existia config nem estava listado como dependência direta
  (só via `prettier-plugin-tailwindcss`, que ficava sem efeito nenhum sem
  config apontando pra ele). Criado `.prettierrc.json` + `.prettierignore`,
  scripts `npm run format` / `format:check`. Todo o projeto já foi formatado.
- **Maestro** (testes E2E mobile): instalado no host (`~/.maestro/bin`), fluxo
  de smoke test em `.maestro/00-smoke.yaml` — **não é versionado**
  (`.gitignore`), fica só local. Abre o app via Expo Go por deep link
  (`exp://<ip-da-maquina>:8081`) e tira screenshot. Requer dispositivo Android
  conectado via USB com depuração ativada (`adb devices` precisa listá-lo).

## Bugs reais corrigidos

- **`babel-preset-expo` não resolvia** (`Cannot find module`) — só existia
  como dependência transitiva do `expo`, instalada aninhada em
  `node_modules/expo/node_modules/`. Corrigido declarando explicitamente em
  `package.json`.
- **Cadastro**: `data_nascimento` só validava `length === 10` (não se dia/mês
  eram um calendário real — "60/30/3038" passava); `finalizarCadastro` não
  usava `await`/`try-catch` em `register()` e já navegava pra home antes da
  resposta, escondendo erros numa promise não tratada. Corrigido nos dois
  lados (frontend valida direito + backend agora valida antes de inserir).
- **Busca de endereço mostrando nome em branco**: causa raiz dupla —
  1) a Places API (legada) do Google não estava habilitada no projeto
     (migrado pro backend usar Places API (New));
  2) `response()->json(null)` no Laravel vira `{}` (truthy em JS), não `null`
     — o front tratava como sucesso e desestruturava campos inexistentes.
  Corrigido nos dois lados; front também sanitiza o histórico do
  `AsyncStorage` que podia ter entradas antigas sem o campo `name`.
- **Crash no itinerário com parada**: `item.name.trim()` assumia `name`
  sempre string; podia vir `undefined` de cache/API.
- **PII nos logs**: `AuthProvider` logava a senha em texto puro (payload de
  cadastro) e o objeto completo do usuário no console. Removido/guardado
  atrás de `__DEV__`.
- **Sem tratamento de token expirado**: nenhuma tela reagia a 401 de forma
  consistente. Adicionado interceptor de resposta em `Services/api.ts` que
  desloga e redireciona pro `/login` automaticamente.
- **Chave de estilo duplicada** (`PerfilUsuario.tsx`): duas entradas
  `avatarWrapper` no mesmo `StyleSheet.create`, a segunda sobrescrevia a
  primeira e quebrava o tamanho do avatar.
- **Mapa não carregava no Android**: faltava
  `expo.android.config.googleMaps.apiKey` no `app.json`.
- **Rota não era traçada no mapa**: Directions API do Google bloqueada por
  falta de faturamento habilitado no projeto do Google Cloud — **isso só o
  usuário resolve** (console.cloud.google.com → Faturamento). Não é bug de
  código.
- **Arquivos órfãos removidos**: pasta `components/` na raiz (boilerplate
  padrão do `create-expo-app`, com imports já quebrados e nada em `src/`
  referenciando), `dialogBase.js` (rascunho abandonado, tinha até sintaxe
  TS inválida num arquivo `.js`), `telaEmBranco.tsx`, e dois arquivos
  duplicados "copy" (`ParaOndeVamos copy.tsx`, `ViagemComParada copy.tsx`).

## Responsividade (pedido explícito do usuário: "funcionar em todos os celulares")

- **`Dimensions.get("window")` capturado uma única vez fora do componente**
  (não reativo — não atualiza em rotação, split-screen, dobráveis) em
  **~58 arquivos**. Todos migrados pra `useWindowDimensions()` via script
  automatizado (testado em amostra antes de aplicar em massa). Dois arquivos
  (`CameraFotoPerfil.tsx`, `ModalSelos.tsx`) tinham estilos estáticos no
  `StyleSheet.create` dependendo desses valores fora do componente — movidos
  pra estilo inline no local de uso.
- **`SafeAreaProvider` ausente na raiz do app** — vários componentes já
  usavam `useSafeAreaInsets()` sem provider nenhum configurado. Adicionado
  em `src/app/_layout.tsx`.
- **Menu lateral (`SideMenu`) com largura fixa (280px)** enquanto a animação
  calculava 78% da largura real da tela — descompasso real em qualquer
  aparelho que não fosse ~360dp. Corrigido pra usar o mesmo valor calculado.
- **`paddingTop: 45/50` chutado como aproximação da status bar**, em vez de
  `useSafeAreaInsets()` de verdade, encontrado em **~57 arquivos** (mesmo
  padrão: um estilo `header` estático com o padding fixo, usado como
  `style={styles.header}` simples). **Ainda pendente** — usuário confirmou
  que quer que eu continue, aplicar o mesmo tipo de script/correção que foi
  usado pro `Dimensions.get`.

## Convenção de commit deste usuário (ver também memória do Claude)

Quando pedir pra commitar: sem `Co-Authored-By`, e atômico por arquivo/
contexto (nunca misturar mudanças não relacionadas no mesmo commit). Nunca
incluir `.claude/` nem `docs/docs-claude/` (aqui é só a raiz do backend que
tem essa pasta; o frontend não tem `docs-claude/` próprio ainda). `.maestro/`
também não deve ser versionado no frontend — já está no `.gitignore` de lá.
