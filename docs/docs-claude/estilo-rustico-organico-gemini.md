# Estilo "Rústico Orgânico" (brief do Gemini) — arquivado, NÃO implementado

O usuário achou interessante um brief de design gerado pelo Gemini e pediu
pra só guardar aqui, sem aplicar ainda. Isso é uma proposta alternativa à
paleta "Petróleo/Teal" (que também está arquivada, ver
`redesign-petroleo-teal-arquivado.md`) e ao visual atual (amarelo/laranja,
clone do 99, restaurado nesta mesma sessão a pedido do chefe do usuário).

**Não implementar sem pedido explícito.** Três direções de design já foram
exploradas/descartadas ou pausadas nesta sessão (teal, o visual atual do
99, e agora esse) — confirmar com o usuário antes de gastar trabalho
aplicando qualquer uma.

## Prompt original (verbatim, enviado pelo usuário)

```
Atue como um Designer de Interface (UI/UX) especialista em aplicativos móveis modernos. Quero que você crie o layout para um aplicativo de transporte de passageiros (estilo Uber/99). O design deve seguir estritamente o conceito "Rústico Orgânico", transmitindo as sensações de leveza visual, interface acolhedora/natural e segurança máxima.

Siga as especificações detalhadas abaixo:

1. PALETA DE CORES (Rústico Orgânico)
- Fundo Geral (Leveza): #FDFBF7 (Areia bem claro/Off-white natural)
- Elementos, Textos e Detalhes: #3E2723 (Marrom Café Profundo)
- Botões de Ação Principal (Segurança/CTA): #2E7D32 (Verde Floresta Seguro)
- Bordas e Linhas Divisórias Neutras: #D7CCC8 (Tom argila suave)

2. COMPONENTES E INPUTS (CAMPOS DE TEXTO)
- Não use inputs apenas sublinhados. Todos os inputs devem ser caixas fechadas com cantos levemente arredondados (border-radius entre 8px e 12px).
- O fundo dos inputs deve ser ligeiramente contrastante com o fundo do app (ex: branco puro #FFFFFF).
- A borda padrão do input deve ser fina (#D7CCC8). No estado "Active/Focused" (quando selecionado), a borda deve mudar para o Verde Seguro (#2E7D32).

3. BOTÕES E CARDS
- Todos os botões principais de ação (como "Confirmar Viagem" ou "Chamar Motorista") devem usar o Verde Seguro (#2E7D32) com texto em branco ou creme, cantos arredondados (8px a 12px) e sem sombras pesadas (flat ou soft shadow).
- Os cartões (cards) de informações de viagem ou seleção de categorias devem ter bordas finas de 1px na cor marrom café ou argila, mantendo o visual limpo e leve.

4. TIPOGRAFIA
- Títulos Principais e de Destaque: Use uma fonte Serifada discreta e elegante (estilo Merriweather ou Playfair Display) para trazer o toque humano/rústico.
- Textos de Suporte, Inputs, Menus e Números: Use uma fonte Sem Serifa extremamente limpa, moderna e leve (estilo Inter ou Plus Jakarta Sans) para garantir legibilidade rápida na rua.

5. TELA SOLICITADA
Gere o layout da [Insira aqui o nome da tela, ex: Tela Inicial de pedido de corrida, com o mapa ao fundo, o card de destino e a seleção de categorias de veículos]. Garanta que o mapa use um estilo visual personalizado mais neutro/areia para combinar com a paleta, evitando o azul/amarelo vibrante padrão.
```

## Observações técnicas rápidas (pra quando for implementar, se pedido)

- As fontes serifadas (Merriweather/Playfair Display) e sem-serifa
  (Inter/Plus Jakarta Sans) não fazem parte das fontes padrão do sistema —
  precisariam ser carregadas via `expo-font` + `@expo-google-fonts/*`
  (pacotes já existem no ecossistema Expo pra ambas).
- O item 5 pede pra o **mapa em si** (tiles do Google Maps) usar um estilo
  customizado tom areia — isso é possível via `customMapStyle` no
  `react-native-maps` (array de regras JSON no estilo do Google Maps
  Styling Wizard), não implementado ainda em `Map.tsx`.
- Essa paleta é radicalmente diferente da atual (verde floresta + marrom
  café + areia vs. amarelo/laranja atual) — se for aplicada, é uma troca
  completa, não um ajuste incremental.
