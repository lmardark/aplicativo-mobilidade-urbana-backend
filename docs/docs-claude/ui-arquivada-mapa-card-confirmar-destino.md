# UI arquivada: mapa em cartão + tela de confirmar destino — 2026-09-02

O chefe não gostou desse visual (mesmo veredito de antes com o redesign
Petróleo/Teal — só aprovou a logo "67 com os carrinhos" e o rastro
tracejado no login). Revertido pro estado anterior. Este arquivo guarda
como era, pra reaproveitar no futuro se fizer sentido.

**Regra geral do que foi revertido vs mantido**: só o VISUAL/layout foi
desfeito. Bugs reais corrigidos nesse meio tempo (rota fantasma, sessão
deslogando sozinha em qualquer 401, senha simplificada, caches de
performance, drag-and-drop das paradas) continuam valendo — não são
"visual", são comportamento.

## 1. Mapa da home em cartão reduzido

**Onde**: `src/app/(main)/home.tsx`.

Em vez do mapa ocupar a tela inteira (como sempre foi), virou um cartão
menor com margem e cantos arredondados, tipo o app do 99 — só na home
"parada"; ao entrar em qualquer etapa do fluxo de pedir corrida
(ParaOndeVamos/ViagemComParada/FolhaEscolherOferta), o mapa voltava a
ocupar a tela toda.

```tsx
const [cardMapaPronto, setCardMapaPronto] = useState(false);
// ...
<View style={styles.corpoPrincipal}>
  <View
    style={showFolhaInferior ? styles.mapaCardWrapper : styles.mapaTelaCheia}
    collapsable={false}
    onLayout={(event) => {
      const { width, height } = event.nativeEvent.layout;
      if (width > 0 && height > 0 && !cardMapaPronto) setCardMapaPronto(true);
    }}
  >
    <Map key={cardMapaPronto ? "mapa-pronto" : "mapa-inicial"} ... />
  </View>

  {showFolhaInferior && (
    <View style={styles.folhaInferiorWrapper}>
      <FolhaInferior onPressParaOndeVamos={handleAbrirParaOndeVamos} />
    </View>
  )}
</View>
```

```ts
corpoPrincipal: { flex: 1, backgroundColor: "#FFF" },

mapaCardWrapper: {
  position: "absolute",
  top: 100,
  left: 16,
  right: 16,
  height: 320,
  borderRadius: 24,
  overflow: "hidden",
},

mapaTelaCheia: { flex: 1 },

folhaInferiorWrapper: { flex: 1, marginTop: 432, paddingBottom: 90 },
```

**Problema nunca resolvido**: o ponto azul de localização do usuário não
centralizava direito dentro do cartão (ficava puxado pra cima). Muito
investigado (ver `problemas-conhecidos.md`) — suspeita forte é que o
`MapView` nativo do Android, dentro do Expo Go, mede a si mesmo com o
tamanho errado na primeira montagem e a câmera interna não bate com o
recorte visual do `overflow:hidden`. O `key`/`onLayout` acima foi a
última tentativa de correção, testada ao vivo e **confirmada que não
resolveu**. Com a volta pro mapa tela cheia, esse problema
provavelmente desaparece sozinho (não tem mais recorte pra desalinhar a
câmera) — mas fica documentado caso o cartão volte a ser usado no
futuro, pra não repetir a mesma investigação do zero.

**Também revertido**: botão de centralizar localização
(`locationButton` em `Map.tsx`) volta de `bottom:12,right:12` pra
`bottom:32,right:16` (posição pensada pro cartão pequeno, não faz
sentido na tela cheia).

## 2. Tela de confirmar destino (ConfirmarEndereco.tsx)

**Onde**: `src/components/corrida/ConfirmarEndereco.tsx` — arquivo
**mantido no repositório**, só desconectado do fluxo (não é mais
renderizado/chamado por `home.tsx`). Igual ao que foi feito com
`src/theme/colors.ts` na época do redesign Teal.

Passo novo que existia entre escolher o destino (ParaOndeVamos ou
ViagemComParada) e ver os preços (FolhaEscolherOferta):

- Mapa interativo (pan/zoom livre) enquadrando origem+destino
  automaticamente via `fitToCoordinates`.
- Tracejado (`Polyline` com `lineDashPattern={[8,8]}`) ligando os dois
  pontos.
- Distância em linha reta (haversine, calculada no próprio componente)
  num badge no topo do mapa.
- Marcadores customizados: círculo branco/preto na origem, círculo
  laranja/branco no destino (o `pinColor` do `Marker` padrão não
  funciona com hex no Android, só com hue — por isso os dois pinos
  apareciam vermelhos idênticos na primeira tentativa).
- Card com nome+endereço formatado, botão "Confirmar destino" fixo no
  rodapé (padrão visual do fluxo de login).

**Como estava conectado** (removido de `home.tsx`, útil se for
reativar):

```tsx
const [showConfirmarEndereco, setShowConfirmarEndereco] = useState(false);
const [origemConfirmacao, setOrigemConfirmacao] = useState<
  "para-onde-vamos" | "viagem-com-parada"
>("para-onde-vamos");

// ParaOndeVamos.onSucesso e ViagemComParada.onConfirmar chamavam
// setShowConfirmarEndereco(true) em vez de ir direto pra
// setShowFolhaEscolherOferta(true)

<ConfirmarEndereco
  visible={showConfirmarEndereco}
  origem={partida}
  endereco={destino}
  onVoltar={() => {
    setShowConfirmarEndereco(false);
    if (origemConfirmacao === "viagem-com-parada") setShowViagemComParada(true);
    else setShowParaOndeVamos(true);
  }}
  onConfirmar={() => {
    setShowConfirmarEndereco(false);
    setShowFolhaEscolherOferta(true);
  }}
/>
```

## 3. FolhaInferior estático (sem colapsar)

**Onde**: `src/components/corrida/FolhaInferior.tsx`.

Virou um painel fixo sempre totalmente visível (sem
`@gorhom/bottom-sheet`, sem arrastar pra revelar o histórico) — pedido
explícito do usuário na hora ("Para onde vamos" parar de ficar
colapsado). Revertido de volta pro `BottomSheet` arrastável (snap points
`["22%", "64%", "80%"]`, recolhido por padrão).

**Mantido apesar do revert** (não é visual, é fonte de dado): o
histórico continua lendo do cache real (`@historico_enderecos`,
`AsyncStorage`) em vez de mostrar os 2 endereços mockados fixos
("Av. Paulista, 1000" / "Rua das Flores, 45") que existiam antes de
qualquer coisa desta sessão.

## Ordem de restauração, se for reativar no futuro

1. Restaurar `mapaCardWrapper`/`mapaTelaCheia` em `home.tsx` (ou plugin
   uma versão nova, já resolvendo a centralização primeiro).
2. Reativar o `ConfirmarEndereco` no fluxo (código de conexão acima).
3. Trocar `FolhaInferior` de volta pro painel estático (ou considerar um
   meio-termo: sheet com snap inicial mais alto, por exemplo).
4. Resolver a centralização do mapa ANTES de reativar o cartão — testar
   num build EAS de verdade, não só Expo Go.
