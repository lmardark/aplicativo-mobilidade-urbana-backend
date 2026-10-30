# Redesign "Petróleo / Teal" — arquivado em 2026-09-01

O chefe do usuário não gostou do redesign completo, só do logo "67" com os
carrinhos. Esse redesign foi revertido nas telas (voltou pra paleta
amarelo/laranja original + inputs com sublinhado), mas fica documentado
aqui caso precisem voltar pra ele depois.

**O que foi feito no revert** (mesma sessão, logo depois do feedback):
todas as cores (`colors.*`) voltaram pros hex originais em `login.tsx`,
`loginEmail.tsx`, `register.tsx`, `CodigoVerificacao.tsx`, `MenuInferior.tsx`,
`FolhaInferior.tsx`, `Map.tsx` (botão de localização). Inputs em caixa
arredondada voltaram a ser sublinhado único. O componente `AppLogo` (67 +
carrinhos) ficou — mas com cor própria fixa (`LOGO_COLOR`, preto a pedido do
usuário) em vez de puxar de `colors.primary`, porque a paleta ao redor dele
mudou mas o logo em si não devia mudar. `ErrorBanner`, validação de senha/
idade, remoção de botões mortos, otimizações de API — tudo isso ficou, não
fazia parte do que o chefe rejeitou (ver seção "O que foi mantido" abaixo).
Também corrigido nesse processo: fonte do campo de telefone grande demais
pro placeholder mais longo (`(69) 91234-5678`) estava cortando nas bordas —
reduzida de 24 pra 18; conteúdo do cadastro (`register.tsx`) ficava colado
no topo com muito espaço vazio sobrando embaixo em etapas curtas — trocado
pra `justifyContent: "center"`.

## Onde está preservado

Todo o trabalho está intacto no histórico do git do frontend, commits
`76778fc` até `1d1190e` (nessa ordem):

1. `76778fc` — cria `src/theme/colors.ts`, `AppLogo.tsx`, `ErrorBanner.tsx`,
   `icons/GoogleIcon.tsx`
2. `e9ea32c` — fix logout/restoreSession (não é visual, não precisa reverter)
3. `80305e2` — transições de tela mais suaves (não é visual, mantido)
4. `1c51358` — login.tsx: redesign + fix de erro
5. `b6e3387` — loginEmail.tsx: redesign
6. `c8082b3` — register.tsx: validação + fix + redesign
7. `1d1190e` — CodigoVerificacao.tsx: fix + redesign

Pra restaurar visualmente uma tela específica: `git show <hash>:<caminho>` ou
`git diff <hash-antes>..<hash>` pra ver exatamente o que mudou naquele commit,
e reaplicar a parte de estilo manualmente (os commits mixam correção de bug
com redesign no mesmo arquivo, então não dá pra reverter em bloco sem também
desfazer as correções que o usuário quer manter).

## O que era o redesign

- **Paleta**: `src/theme/colors.ts` — primary `#0F5257` (petróleo), accent,
  background, text/textSecondary/textMuted, border, disabledBg/disabledText,
  error/errorLight.
- **Inputs**: caixa com `borderWidth` + `borderRadius: 16` no lugar do
  sublinhado único embaixo do campo.
- **Botões**: `nextButtonActive`/`resendButtonActive` etc. usando
  `colors.primary` (teal) no lugar de `#FFD200` (amarelo do 99).
- **Logo**: "67" com dois carrinhos flanqueando (`AppLogo.tsx`) — **esse
  elemento foi aprovado e continua em uso**, só que agora com cor própria
  fixa (não mais amarrado à paleta revertida).

## O que foi mantido apesar do revert (pedido explícito do usuário)

- `AppLogo` (logo "67" + carrinhos) em todas as telas de auth.
- `ErrorBanner` (banner de erro com ícone, já era vermelho/neutro, não
  precisou mudar de cor).
- Remoção de elementos mortos: ícone de "adicionar contato" no telefone,
  botão de "scan" no cabeçalho principal, bolinha de notificação falsa.
- Correção "Pay" → "Pagar" no menu inferior.
- Validação de senha forte (8+ caracteres, maiúscula, número, símbolo) e
  idade mínima (18 anos) no cadastro, com checklist/erro na própria etapa.
- Otimizações de consumo de API (debounce na rota, mínimo de 3 caracteres na
  busca de endereço).
- Todas as correções de bugs de lógica do fluxo de login/cadastro (ver
  `problemas-conhecidos.md`).
