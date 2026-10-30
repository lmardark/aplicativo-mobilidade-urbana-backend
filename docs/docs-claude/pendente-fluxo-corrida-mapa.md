# Pendente: UI/UX do fluxo de pedir corrida (mapa) — 2026-09-01

Pedido do usuário, ainda **não implementado** — só documentado aqui pra
trabalhar depois. Ordem de prioridade combinada no início da sessão:
(1) login/cadastro ✅ feito, (2) home/mapa — parcialmente feito (ver
`frontend.md`), (3) **fluxo de pedir corrida** — isso aqui, (4) telas
secundárias.

## O que o usuário pediu pra revisar

> "vou falar problemas relacionados ao fluxo de pedir uber, preciso que veja
> a UI e o UX dos componentes, e quero que conserte, principalmente a parte
> de paradas e o pedido"

Componentes envolvidos (já mapeados nesta sessão, ver histórico):
`ParaOndeVamos.tsx`, `ViagemComParada.tsx`, `FolhaEscolherOferta.tsx`,
`Map.tsx`, orquestrados por `src/app/(main)/home.tsx`.

## Bug da rota fantasma — ✅ CORRIGIDO (2026-09-01)

Causa raiz confirmada por inspeção de código (sem precisar reproduzir):
a seta de voltar da `FolhaEscolherOferta` (`onClose={handleVoltarParaHome}`
em `home.tsx`) só escondia a folha (`showFolhaEscolherOferta=false`) e
**nunca resetava o `itinerario`** — diferente de `handleCancelarViagem`
(usado pelo voltar da `ViagemComParada`), que sempre zerou o itinerário
corretamente. Como o mapa só desenha `MapViewDirections`/`Polyline` quando
`itinerarioMapa.length >= 2`, e o itinerário permanecia intacto com as
coordenadas antigas, a rota ficava "fantasma" na tela.

Corrigido em `src/app/(main)/home.tsx`, `handleVoltarParaHome` (~linha 244):
agora ele também zera o itinerário (mantendo a origem) e recentraliza o
mapa na localização do usuário, espelhando exatamente o que
`handleCancelarViagem` já fazia. Verificado tsc/eslint/prettier limpos
(warnings restantes são pré-existentes, não relacionados).

## Bug relatado (o mais concreto, prioridade alta) — histórico da investigação

> "quando colocado um endereço e ao gerar rota, se voltar uma pagina ou
> clicar na seta para voltar a rota continua aparecendo na tela"

Ou seja: usuário monta um itinerário, a rota é desenhada no mapa
(`MapViewDirections` em `Map.tsx`), e ao voltar (botão "voltar" ou navegação
pra trás) pra uma etapa anterior do fluxo, **a rota antiga continua
desenhada**, mesmo que o itinerário tenha sido resetado/alterado.

### Hipótese de causa (não confirmada, precisa investigar)

Olhando `home.tsx`, `handleCancelarViagem` já reseta o `itinerario` mantendo
só a origem:

```tsx
setItinerario((prev) => [
  prev[0],
  { name: "", formattedAddress: "", latitude: 0, longitude: 0, distancia: "0km", order: 1 },
]);
```

Isso deveria fazer `itinerarioMapa` (filtrado por `.filter(item => item.name && item.latitude && item.longitude)`)
cair pra 1 item, e o `Map.tsx` só desenha `MapViewDirections` quando
`itinerarioParaRota.length >= 2` — então em teoria a rota devia sumir.
Possíveis culpados a investigar:

1. **Cache de rota novo** (adicionado nesta mesma sessão, ver
   `frontend.md`) — o cache é keyed pela combinação exata de coordenadas.
   Se o usuário volta e refaz o MESMO itinerário, o cache reaproveita a
   rota antiga (isso é o comportamento *desejado* do cache). Mas se o botão
   "voltar" não está de fato levando o `itinerario` de volta a
   `length < 2` — por exemplo, se algum botão de "voltar" nos componentes
   de sub-tela (`ParaOndeVamos`, `ViagemComParada`) só fecha o modal
   (`onClose`) sem chamar `handleCancelarViagem`/resetar o itinerário — a
   rota antiga nunca teria motivo pra sumir porque o itinerário nunca foi
   realmente limpo.
2. **Múltiplos pontos de "voltar"** — existe o botão físico do Android,
   `router.back()`, `onClose` dos componentes, e o `backFloatingButton` em
   `home.tsx` (que só aparece quando `showViagemComParada` e chama
   `handleCancelarViagem`). Se ALGUM desses caminhos de voltar não estiver
   ligado ao reset do itinerário, a rota fica órfã na tela.
3. **`MapViewDirections`/`Polyline` não desmonta de fato** — o
   `react-native-maps` às vezes mantém overlays "fantasma" se o componente
   pai não realmente sai da árvore (precisa confirmar se isso acontece
   aqui).

### Como investigar quando for mexer nisso

- Reproduzir o passo a passo exato: qual tela, qual botão de voltar,
  em que ponto do fluxo (antes ou depois da rota aparecer).
- Conferir se `itinerarioMapa` (em `home.tsx`) realmente zera pra
  `length < 2` no momento do "voltar" usado — dar um `console.log`
  temporário ou usar o debugger.
- Se for o cache de rota: considerar limpar a entrada do cache
  correspondente (ou simplesmente não usar o cache quando o usuário
  "cancela" a viagem, só quando ele volta a montar o MESMO itinerário de
  propósito).

## "Poucas inconsistências" — paradas — ✅ RESOLVIDO (2026-09-01)

Usuário confirmou os 4 pontos (adicionar/remover, reordenar, visual,
integração com mapa). Reordenar virou drag-and-drop de verdade, "Par"
cortado nos marcadores foi corrigido, hint de limite de paradas
adicionado. Detalhes completos em `frontend.md`, seções "Paradas:
reordenar virou arrastar-e-soltar" e "Bug do texto 'Par' cortado".

## (histórico da investigação original, antes de resolver — mantido como referência)

O usuário mencionou que a parte de **paradas** (múltiplos destinos no
itinerário, `ViagemComParada.tsx`) tem problemas de UI/UX, sem detalhar
exatamente quais. Precisa perguntar/observar de novo quando for atacar essa
parte — não foi especificado o suficiente pra agir sem adivinhar.

## Não fazer sem confirmar de novo

Não comece a mexer nesses componentes sem antes:
1. Pedir pro usuário reproduzir o bug da rota fantasma com mais detalhe
   (qual tela exata, qual botão).
2. Perguntar quais inconsistências específicas de paradas estão
   incomodando (a mensagem original foi vaga nesse ponto).
