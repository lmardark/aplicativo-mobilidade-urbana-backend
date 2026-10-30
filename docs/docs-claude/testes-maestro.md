# Testes E2E com Maestro — aprendizados (2026-09-01)

Registro de tudo que foi descoberto tentando testar o fluxo de login/cadastro
no dispositivo real via Maestro, pra não perder tempo redescobrindo isso numa
sessão futura.

## Setup

- Maestro instalado em `~/.maestro/bin` (host, fora do sandbox — usar
  `flatpak-spawn --host` com `dangerouslyDisableSandbox: true`).
- Dispositivo Android via USB, depuração ativada, `adb devices` precisa
  listá-lo.
- App aberto via deep link: `exp://<ip-da-maquina>:8081/--/<rota>` (ex:
  `/--/login`, `/--/register`).
- Fluxos ficam em `.maestro/*.yaml`, **não versionados** (`.gitignore`).

## O problema principal: digitação não é confiável

Maestro injeta texto via ação de acessibilidade do Android
(`ACTION_SET_TEXT`), não via eventos de teclado reais. Isso tem uma
incompatibilidade intermitente com `TextInput` **controlado** do React
Native (`value={estado}` + `onChangeText`):

- Às vezes funciona perfeitamente, digitando caractere por caractere com
  `inputText` separado por campo.
- Às vezes o campo fica **completamente vazio** mesmo com o log do Maestro
  dizendo `COMPLETED` em cada `inputText` — sem nenhum erro, sem nenhuma
  pista. Só se percebe olhando o screenshot.
- Às vezes só os **primeiros 1-2 caracteres** somem (ex: "69977776666"
  virou "7777666 6") — parece que os primeiros toques disparam antes da
  ponte nativa (JS bridge) estar pronta pra receber eventos de texto.

**Não existe uma correção definitiva encontrada.** O que ajuda (mas não
garante):

- Usar `inputText` **um caractere por vez** em vez de uma string única —
  mais confiável que mandar tudo de uma vez, mas ainda falha às vezes.
- Esperar (`waitForAnimationToEnd` + `extendedWaitUntil` no placeholder)
  antes de começar a digitar.
- **Sempre conferir com `takeScreenshot` logo depois de digitar**, antes de
  seguir pro próximo passo — se o campo estiver vazio, o fluxo vai falhar
  silenciosamente mais adiante e o erro real (campo vazio) só aparece se
  você olhar o screenshot no meio do caminho.
- Se falhar, só rodar de novo. Na sessão de hoje, o mesmo script idêntico
  falhou ~5 vezes e funcionou ~2 vezes, sem mudança nenhuma no código.

## `tapOn` por texto: cuidado com texto composto

`tapOn: "algum texto"` exige que o texto do elemento **bata inteiro** com o
regex (não é "contém"). Isso quebra em blocos como:

```
<Text>Li e aceito os <Text style={link}>Termos de Uso...</Text></Text>
```

porque o node de texto renderizado é a string inteira concatenada. Solução:
usar um regex com wildcard (`"Li e aceito os.*"`) ou tocar em outro texto
próximo que seja um match exato.

## `tapOn` por coordenada: prefira ler a árvore de UI, não estimar no olho

Estimar `point: "x%,y%"` olhando o screenshot é impreciso e quebra fácil
(qualquer mudança de layout desloca tudo). Jeito confiável:

1. Rodar o flow até o passo desejado com um `takeScreenshot` logo antes.
2. Abrir o JSON de `screen-hierarchy` gerado nesse passo
   (`~/.maestro/tests/<timestamp>/<flow>/screen-hierarchy/*.json`).
3. Procurar o elemento pelo texto/classe e pegar o campo `bounds`
   (`[x1,y1][x2,y2]`, em pixels absolutos da tela).
4. Calcular o centro e usar `tapOn: { point: "X,Y" }` **sem `%`** (pixels
   absolutos, não porcentagem) — mais preciso que estimar visualmente.

Script rápido pra extrair bounds de elementos clicáveis:

```python
import json
d = json.load(open("<caminho-do-hierarchy.json>"))
def walk(n):
    if isinstance(n, dict):
        a = n.get("attributes", {})
        if a.get("bounds") and a.get("clickable") == "true":
            print(a["bounds"], repr(a.get("text",""))[:40])
        for c in n.get("children", []):
            walk(c)
    elif isinstance(n, list):
        for c in n:
            walk(c)
walk(d)
```

## `hideKeyboard` pode navegar pra fora da tela

O comando `hideKeyboard` do Maestro manda um back-press do Android. Em telas
com `expo-router`, isso pode ser interceptado como "voltar de rota" em vez
de só fechar o teclado — literalmente navegou pra fora da tela de login no
meio do fluxo. **Não usar.** Alternativa: tocar em algum texto neutro na
tela (ex: um título, não interativo) pra tirar o foco do campo — o teclado
android some sozinho, sem navegar.

Nesse app especificamente, o teclado nem precisa ser fechado manualmente na
maioria dos casos: com `KeyboardAvoidingView behavior="height"` (Android), o
botão de avançar já fica visível acima do teclado.

## Interrupções externas (não são bugs do app)

Aconteceram várias vezes durante os testes de hoje, sempre em dispositivo
físico de uso pessoal:

- Ligação recebida — derrubou o Expo Go pro launcher do sistema.
- Notificação heads-up do WhatsApp cobrindo a tela bem no momento do toque.
- Tela apagando sozinha (timeout de tela) entre uma execução e outra.

Mitigação: `adb shell cmd notification set_dnd priority` antes de rodar (não
elimina ligações, só notificações não-prioritárias) e
`adb shell input keyevent KEYCODE_WAKEUP` antes de cada execução. Não dá pra
eliminar 100% — é o telefone real do usuário.

## Fluxo funcional confirmado (referência)

`.maestro/03-login-ate-home.yaml` — login por telefone (conta já existente)
até a home. Testado com sucesso em 2026-09-01, confirma:

- Envio do telefone e verificação de conta existente funcionando.
- Tela de código abrindo, "Receber código" preenchendo e autenticando.
- Redirecionamento pra home, sessão persistida.
- Visual da home carregando (mapa, saudação, menu inferior).

Requer uma conta cadastrada com o telefone `69977776666` (ajustar o arquivo
se o telefone de teste mudar) — criar via
`POST /api/auth/register` direto no backend (mais rápido e confiável que
passar pelo cadastro completo via UI) e apagar depois do teste
(`User::withTrashed()->where(...)->forceDelete()`, não usar `delete()`
porque o model tem `SoftDeletes` e `delete()` deixa o telefone/e-mail
"ocupados" mesmo apagado).

## Conta de teste: SEMPRE copiar o telefone, nunca digitar de memória (2026-09-01)

Perdi uma boa quantidade de tempo numa sessão só por causa disso: criei a
conta de teste via `tinker` digitando o telefone de memória e trocando um
dígito (`69997776666` em vez do `69977776666` que o `03-login-ate-home.yaml`
realmente digita no campo). A API dizia `contaExiste:true` quando eu
testava via `curl` com o número errado — parecia tudo certo — mas o app
continuava mandando pra tela de cadastro porque o número **realmente
digitado na tela** não tinha conta. Sintoma enganoso: nenhum erro, nenhum
sinal, só "por algum motivo continua indo pro cadastro".

**Lição**: antes de recriar a conta de teste, `grep` o telefone exato no
próprio `.yaml` (ou copiar daqui, `69977776666`) em vez de digitar de
memória. E depois de criar, conferir com
`curl "http://<ip>/api/auth/verifica-se-conta-existe?telefone=<numero exato do yaml>"`
— não só qualquer número parecido.

Comando pra recriar a conta rapidamente (mais confiável que passar pela UI):

```
docker exec -u sail aplicativo-mobilidade-urbana-backend-laravel.test-1 \
  php artisan tinker --execute="
App\Models\User::create([
  'name' => 'Teste Maestro',
  'data_nascimento' => '1995-01-01',
  'telefone' => '69977776666',
  'cpf' => '11122233397', // trocar se colidir com CPF único de outra conta de teste
  'email' => 'teste.maestro.'.time().'@example.com',
  'status' => 'ativo',
  'password' => bcrypt('senha1234'),
]);
"
```

Lembrar de rodar `forceDelete()` nela ao final dos testes (ver seção acima).

## `uiautomator dump` falha com o easter egg dos carrinhos ativo

Desde que `CarrosAleatorios.tsx` foi adicionado nas telas de auth, `adb shell
uiautomator dump` passou a falhar com `ERROR: could not get idle state`
sempre que uma dessas telas está em foreground. Causa: o componente tem uma
animação em loop contínuo (`Animated.loop` do tremor do motor + o
`setInterval` do rastro) — a UI literalmente nunca fica "idle", e é isso que
o uiautomator espera pra conseguir tirar o dump da árvore de acessibilidade.
Não afeta o Maestro em si (ele não depende do dump pra rodar), só quebra a
técnica manual de "extrair `bounds` do JSON de hierarchy" quando usada fora
de um flow do Maestro (via `adb shell uiautomator dump` direto). Se precisar
do dump numa tela com os carrinhos, ou aceita a falha e usa outra técnica
(estimar por screenshot + fórmula de escala), ou tira o dump durante um
`takeScreenshot`/passo de um flow Maestro normal (que usa outro mecanismo,
não quebra).

## `adb shell input tap`: mesma flakiness dos toques do Maestro, sem sinal de erro

Confirmado nesta sessão que a instabilidade não é exclusiva do
`inputText` do Maestro — `adb shell input tap x y` manual (usado pra
depuração fora de um flow) também falha silenciosamente às vezes: o comando
retorna sem erro, mas o toque não produz nenhum efeito visível no app (tela
seguinte só aparece plotando uma sequência igual de comandos de novo, sem
mudança nenhuma no meio). Nenhum padrão claro de quando falha. Mitigação:
sempre tirar screenshot logo depois de cada tap manual pra confirmar que
algo mudou antes de seguir pro próximo passo — igual à recomendação já feita
pra digitação.

## Bounds do `uiautomator dump`/hierarchy podem não bater com a área realmente tocável em BottomSheet recolhido

Tentando abrir `ParaOndeVamos` a partir da home (tocando em "Para onde
vamos?" dentro do `FolhaInferior`, que é um `@gorhom/bottom-sheet` no
snapPoint recolhido "22%"): o `uiautomator dump` reportou bounds exatos
pro texto/TouchableOpacity, tocar exatamente no centro desses bounds (duas
vezes, pra descartar flakiness) não abriu nada, sem erro. Hipótese: o
bottom sheet clipa visualmente o conteúdo no snap recolhido, mas a árvore
de acessibilidade pode reportar os bounds "lógicos" do layout (como se
estivesse totalmente expandido), não a região realmente visível/tocável
quando recolhido. Não foi confirmado a causa exata nem resolvido — só
documentado pra não repetir a mesma tentativa. Alternativas a tentar numa
próxima vez: arrastar o handle pra expandir o sheet primeiro (`swipe` de
baixo pra cima no handle, mas isso também não funcionou numa tentativa
rápida) ou usar `maestro studio` interativo pra inspecionar em tempo real.

## Recomendação geral

Pra fluxos com digitação, a verificação mais confiável continua sendo testar
a lógica via `curl` direto na API (backend) — isso está sempre 100%
determinístico — e usar Maestro só pra confirmar que a tela renderiza e
navega certo, aceitando que pode precisar rodar mais de uma vez.
