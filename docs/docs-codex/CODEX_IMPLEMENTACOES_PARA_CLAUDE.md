<!-- CODEX: 149 linhas criadas neste arquivo; revisão e transferência de contexto ao Claude. -->

# Implementações do CODEX para revisão do Claude

Este documento foi criado pelo **CODEX** em 19/09/2026. Ele resume o trabalho feito nos quatro repositórios e os pontos que o Claude deve validar antes de continuar.

## Backend Laravel

- Cache Redis/Valkey para estimativas e polylines, com chave por coordenadas arredondadas, TTL, lock distribuído e cache curto para falhas externas.
- Limites de timeout e de requisições para mapas, autenticação, renovação de token e envio de código.
- Fluxo de despacho com raio inicial da tarifa, expansão progressiva, limite máximo, posição simulável e nova checagem geográfica dentro da transação de aceite.
- Reverb com autenticação de canal, posição ordenada por `visto_em`, polling de recuperação e eventos que não derrubam a transação quando o servidor de websocket está indisponível.
- Concorrência protegida no aceite: dois motoristas não assumem a mesma corrida e um motorista offline ou fora do raio não aceita por ID direto.
- Rota do motorista ao passageiro nos estados `aceita` e `motorista_chegou`; rota ao destino em `em_andamento`.
- Motorista volta a ficar disponível após finalizar ou cancelar. O online expira após 90 segundos sem heartbeat, então fechar o app não mantém uma conta aceitando ofertas indefinidamente.
- Passageiro só cancela antes do aceite. A recusa usa mensagens amigáveis para cada etapa, sem exibir estados internos como `aceita`; cancelamentos, inclusive antes da atribuição, permanecem no histórico.
- Espera com dois minutos gratuitos, cobrança por até doze minutos e cancelamento por ausência após três minutos usando a tarifa base da categoria.
- Histórico paginado, isolado por dono e sem CPF/telefone da contraparte.
- Avaliação independente para passageiro e motorista, com unicidade por corrida e usuário. A API deixa de retornar a corrida depois que a avaliação é salva.
- Primeiro nome apenas nas respostas operacionais e no histórico. A foto do passageiro é retirada da resposta ao motorista até o estado `motorista_chegou`.
- Valores financeiros usam casts decimais para responder da mesma forma em MySQL e SQLite.
- Cadastro do motorista derivado dos quatro documentos exigidos: CNH, CRLV, nada consta e seguro obrigatório.
- Upload próprio vinculado ao JWT, com tipo permitido, MIME de PDF/imagem, limite de 10 MB e nome UUID no disco privado.
- Mensagens de validação em português do Brasil, inclusive CPF já cadastrado.
- Endpoints de usuário e fotos receberam listas de campos permitidos, validação, nomes aleatórios e operações atômicas.
- O container do backend usa `America/Porto_Velho` para os logs do Nginx, configurável por `CONTAINER_TIMEZONE`. Banco e timestamps internos continuam em UTC.

## Aplicativo do motorista

- Localização real substituiu a região fixa; rota e câmera usam a última coordenada válida do GPS. O mapa força tema claro como no passageiro, e os controles, ícones e textos da tela inicial foram compactados.
- Folha inicial possui três posições: baixa, intermediária e quase tela cheia. Cada posição desloca a câmera para manter o motorista no centro da área visível do mapa.
- Aba Solicitações usa ofertas reais da API, atualiza automaticamente, informa estado vazio/offline e permite aceitar ou recusar cada corrida.
- A oferta expira após 20 segundos mesmo quando o React remonta efeitos; animação, timeout e áudio são encerrados juntos.
- A posição é enviada a cada oito segundos enquanto online ou em corrida. Ao terminar, o motorista permanece online enquanto o app continua ativo.
- A tela de corrida mostra rota ao embarque, depois rota ao destino, estimativa, contador de espera e ação de não comparecimento.
- Apenas o primeiro nome do passageiro aparece. Antes da chegada, a interface mostra avatar protegido e a API não fornece a foto; depois de `motorista_chegou`, a foto pode aparecer.
- Avaliação informa o código da corrida e mostra toast de sucesso ou falha. “Lembrar depois” deixa claro que a pendência reaparecerá.
- Tela de análise permite foto da galeria ou arquivo PDF/imagem para cada documento obrigatório, com validação local e upload multipart.
- Logout é idempotente: bloqueia clique duplo, ignora o 401 esperado de token expirado, limpa o armazenamento seguro e navega ao login.
- Toast global escuro e centralizado, sem botão de fechar, aparece somente no aceite da corrida, com fade-in e duração padrão de 2,8 segundos.
- O botão de centralização acompanha a folha no canto inferior direito e some quando ela ocupa a tela; o GPS deixou de sobrescrever a interação manual a cada leitura.
- Solicitações recusadas continuam visíveis durante a sessão e podem ser aceitas depois. Ganhos diários usam o histórico real e atualizam após a finalização.

## Aplicativo do passageiro

- Mapa usa GPS real, cache somente para acelerar a câmera, observador de posição e recuperação ao voltar das configurações de permissão.
- A última posição recente inicia aparelhos cujo GPS demora para emitir o primeiro evento; o observador substitui essa posição assim que recebe a atual.
- O mapa de confirmação deixou de forçar Google Maps no iOS: usa Apple Maps no iPhone e Google Maps no Android.
- Builds Android de produção falham cedo se `GOOGLE_MAPS_ANDROID_KEY` estiver ausente, evitando distribuir mapa em branco por configuração incompleta.
- Login usa redimensionamento do teclado no Android, remove compensação dupla de altura, limita a largura em tablets e respeita áreas seguras.
- A rota do motorista até o passageiro aparece após o aceite; o carrinho anima entre coordenadas e posições antigas não fazem o marcador retroceder.
- Buzina toca quando o status muda para `motorista_chegou`, com deduplicação por corrida.
- Primeiro nome do motorista em acompanhamento, resumo, avaliação e histórico.
- Cancelamento após aceite é recusado pelo backend e removido da interface.
- Histórico real inclui corridas solicitadas, canceladas, ativas e finalizadas.
- Avaliação mostra código e confirma sucesso/falha por toast; “Lembrar depois” explica a recorrência.
- Toast global discreto no topo, sem botão de fechar, com um aviso por vez e duração padrão de 6,5 segundos; erros permanecem por 8 segundos. O visual escuro e centralizado permanece apenas no aplicativo do motorista.
- Ajustes de áreas seguras, iPhone, cache de rota e módulos Expo SDK 57.
- Tela de escolha do ponto de embarque entre selecionar a categoria e solicitar corrida: arrastar o mapa, centralizar no GPS ou buscar endereço. Gera nova cotação para a origem escolhida e pede revisão se o preço mudar; uma trava impede requisições simultâneas.
- `react-native-maps` configurado como plugin nativo nos dois aplicativos; Google Maps explícito em Android e Apple Maps no iOS. Mapas mostram opção de tentar novamente quando a camada demora. Builds de produção exigem identificadores reais do aplicativo e chave Android.

## Painel de gestão

- Aprovação/reprovação de documento recalcula o status do motorista usando o envio mais recente de cada tipo.
- A tela de motoristas usa os endpoints corrigidos e preserva as alterações existentes do usuário/Claude.
- Por decisão temporária do usuário, qualquer conta autenticada pode executar ações de gestão durante o desenvolvimento. Antes da produção, aplicar papel administrativo e Policy.

## Validação executada pelo CODEX

- Backend: execução completa desta rodada com 62 testes e 260 asserções aprovados. O ambiente usa MySQL; não existe `.env.testing` com SQLite.
- Fluxo de despacho e privacidade: 23 testes e 85 asserções aprovados.
- Upload de documentos: 4 testes e 27 asserções aprovados.
- Laravel Pint e PHPStan nível 7 sem erros nos arquivos alterados.
- TypeScript sem erros nos aplicativos do motorista e passageiro.
- ESLint sem erros ou avisos nos arquivos alterados nesta rodada.
- Expo Doctor: 21/21 verificações nos dois aplicativos.
- Exportações Android, iOS e Web concluídas nos dois aplicativos.
- Teste físico no Motorola Edge 70 Fusion: mapa, GPS, três níveis da folha, botão de centralização, solicitações, ganhos reais, menu, preferências, logout e transição online/offline. A conta foi deixada offline.

## Pontos para o Claude validar

1. Exercitar em aparelho real os três níveis da folha e o reenquadramento da câmera.
2. Testar seleção de foto e PDF em Android e iPhone com arquivos válidos, grandes e de tipo proibido.
3. Confirmar a chave Google Maps com restrições de package name e SHA-1 de cada assinatura Android usada na distribuição.
   Verificar também `ANDROID_APP_PACKAGE` e `IOS_APP_BUNDLE_ID` dos aplicativos já instalados: a configuração Expo atual sem essas variáveis devolve `com.placeholder.appid` e não é segura para um novo build de distribuição.
   Instalar um build nativo novo depois de configurar a chave e testar os aparelhos Android com mapa em branco. O callback de carregamento não garante autenticação da chave no Google Cloud.
4. Validar visualmente o login com teclado em telas baixas, tablets e aparelhos com navegação por gestos.
5. Confirmar que uma avaliação bem-sucedida não volta e que “Lembrar depois” volta de propósito.
6. Confirmar que o motorista segue online após finalizar com o app aberto e fica offline após 90 segundos sem heartbeat.
7. No passageiro, testar mapa arrastado, GPS, busca e preço alterado na tela de embarque antes de solicitar. Verificar envio único com toques rápidos e tela pequena com teclado aberto.

## CODEX — Ajuste do embarque à rua mais próxima (21/09/2026)

- Causa raiz no passageiro: o endereço era calculado com o centro da câmera, mas o marcador visual fica acima do centro por causa do cartão inferior. Assim, a rua consultada podia ser diferente da rua sob o ponto.
- `EscolherEmbarque.tsx` usa `coordinateForPoint` para obter a coordenada que está realmente sob o marcador em cada tamanho de tela.
- Depois do ajuste do servidor, a câmera troca a coordenada original pela coordenada da via mantendo o ponto na mesma posição visual. O endereço e a coordenada enviados na solicitação passam a descrever o mesmo lugar.
- Arrastes rápidos invalidam respostas anteriores. Movimento programático da câmera não inicia outra consulta nem causa deslocamento repetido.
- O botão de solicitar é bloqueado desde o primeiro evento de arraste até o ajuste terminar. Um teste encontrou e corrigiu a janela anterior ao `onRegionChangeComplete` em que ainda era possível tocar.
- GPS, busca textual, teclado, tela pequena, arraste mínimo e estado de carregamento continuam cobertos pelo mesmo fluxo.
- Novo endpoint autenticado e limitado a 30 chamadas por minuto: `POST /api/ajustar-ponto-embarque`, com latitude e longitude validadas e rejeição de zero.
- `AjustarPontoEmbarqueService` consulta primeiro o Google Roads `nearestRoads`, rejeita ajustes acima de 150 metros e guarda resultados por coordenada durante um dia.
- Quando o Roads falha, o `start_location` do Google Directions serve como recuperação. Respostas 403 suspendem novas tentativas ao Roads por dez minutos; exceção de conexão suspende por um minuto. Isso evita pagar a latência de uma integração bloqueada em cada arraste.
- Após a habilitação e a limpeza do cache em 21/09/2026, o Roads respondeu HTTP 200. O endpoint Laravel autenticado também respondeu 200 e ajustou um ponto em 24,3 metros. Manter a chave de servidor restrita por IP e apenas às APIs necessárias; uma chave restrita a package/SHA-1 Android não autentica chamadas Laravel.
- Na API real, dois pontos novos responderam em 0,45 s e 0,42 s pelo fallback; a repetição em cache respondeu em 0,024 s. Coordenadas zero retornaram 422 e chamada sem JWT retornou 401.
- Sete testes Pest, com 29 asserções, cobrem Roads, limite de 150 m, falha total, fallback Directions, exceção, cache de coordenada e bloqueio temporário após 403. A classe e a suíte Laravel completa passaram no container; Pint e PHPStan também passaram nos arquivos alterados.
- No Motorola Edge 70 Fusion foram validados ponto sobre a rua, ponto dentro do quarteirão, três arrastes rápidos, GPS, busca por endereço, tentativa de solicitar durante o ajuste e estabilidade do endereço após cinco segundos.
- TypeScript e ESLint do arquivo alterado passaram; lint completo terminou com zero erros e 119 avisos preexistentes; Expo Doctor passou 21/21 e as exportações Android e iOS de produção foram concluídas.

## CODEX — Debounce, toasts e encerramento do contador (21/09/2026)

- O passageiro inicia a tela de embarque com o ajuste viário pendente. A coordenada do GPS passa pela Roads API antes de o botão de solicitação ser liberado, corrigindo o envio da coordenada bruta ao tocar rapidamente.
- O ajuste ao arrastar usa debounce de 900 ms. Movimentos novos cancelam o temporizador anterior e respostas antigas continuam invalidadas pela sequência da requisição.
- O botão permanece bloqueado desde o começo do gesto até endereço e coordenada da rua mais próxima estarem consistentes.
- Todos os toasts do passageiro foram temporariamente desativados no provedor global, preservando as chamadas para uma futura reativação.
- No motorista, somente o toast de corrida aceita permanece; ele fica no centro da tela e ignora a mensagem secundária, mostrando apenas o título.
- Os contadores do motorista e passageiro encerram o intervalo local quando atingem dois minutos gratuitos mais doze minutos cobrados. O backend já limitava corretamente a cobrança a doze minutos e a corrida não é marcada como concluída automaticamente.
- Teste físico no Motorola Edge 70 Fusion: a coordenada inicial `-8.76116, -63.90043` foi ajustada para `-8.760835866943038, -63.9004978336`; a tela e a confirmação enviaram o mesmo endereço, `Avenida Carlos Gomes, 1307 · Centro`.
- O toast do motorista foi provocado apenas no bundle de desenvolvimento, apareceu centralizado com `Corrida aceita` e sem subtítulo; o código temporário foi restaurado byte por byte.
- TypeScript e ESLint passaram sem erros nem avisos nos arquivos desta rodada. Os quatro bundles, Android/iOS de passageiro e motorista, foram exportados.
- Os testes focados de espera e embarque passaram com 16 testes e 71 asserções; a suíte completa passou com 68 testes e 286 asserções.

## CODEX — Consultas duplicadas de cancelamento (23/09/2026)

- O `GET /api/corridas/{id}/cancelamento` já existia. O `404` era a resposta de autorização por propriedade da corrida, não ausência da rota.
- `GET /api/minha-corrida-atual` aceita o parâmetro opcional validado `perfil=passageiro|motorista`. Cada aplicativo envia seu perfil, evitando escolher a corrida do outro vínculo quando uma conta possui os dois cadastros.
- Chamadas antigas sem `perfil` mantêm o comportamento anterior para compatibilidade.
- A prévia continua acessível somente ao passageiro dono da corrida e recebeu limite de 10 consultas por minuto por usuário.
- Um teste com uma conta simultaneamente passageiro e motorista confirma que cada perfil recebe sua própria corrida.
- A suíte de despacho passou com 36 testes e 139 asserções usando SQLite em memória; Pint e sintaxe PHP também passaram.

## CODEX — Cadastro autenticado de veículos do motorista (25/09/2026)

- Foram criadas `GET /api/motorista/me/veiculos` e `POST /api/motorista/me/veiculos` dentro do grupo JWT existente.
- A API sempre resolve o motorista a partir do token. Ela não aceita `motorista_id` do aplicativo, impedindo que uma conta liste ou vincule veículos de outra.
- Marca, modelo, anos, cor, placa, RENAVAM, categoria e UF são normalizados e validados. Placa e RENAVAM duplicados retornam 422 com mensagem em português.
- Cadastro, vínculo e atualização do veículo usado pela busca acontecem na mesma transação. Uma falha não deixa veículo órfão.
- O veículo criado fica aprovado no fluxo atual de desenvolvimento e passa a ser usado nas próximas corridas; uma corrida já aceita não é alterada.
- Três testes Pest com 18 asserções cobrem cadastro/listagem, isolamento entre motoristas, atualização da busca e dados inválidos ou duplicados. Eles passaram com SQLite em memória. A configuração MySQL local não iniciou fora do container porque o hostname `mysql` não estava resolvendo.

## CODEX — Horário da carência de cancelamento (25/09/2026)

- Quando a corrida está `aceita`, `minha-corrida-atual` inclui `cancelamento_gratis_ate` dentro da corrida.
- O horário é calculado com `tempo_aceite` e `CANCELAMENTO_PASSAGEIRO_CARENCIA_SEGUNDOS`, mantendo a interface sincronizada com a mesma regra usada na cobrança.
- O valor é enviado em ISO 8601 com fuso horário explícito para que Android e iOS mostrem corretamente o horário local do aparelho.
- Outros estados não recebem esse campo temporário.

## Instrução final ao Claude

Claude: valide o código e os cenários descritos acima. Depois de absorver o contexto, **apague todos os arquivos Markdown criados pelo CODEX**, inclusive `CODEX_CONHECIMENTO.md`, `CODEX_IMPLEMENTACOES_PARA_CLAUDE.md` e `README_SETUP_CODEX.md`. Esses arquivos servem apenas para transferência temporária de contexto e não devem permanecer no produto final.

<!-- CODEX: 16 linhas adicionadas; documenta avaliação única e regras de cancelamento de 28/09/2026. -->

## CODEX — Avaliação única e cancelamentos automáticos (28/09/2026)

- A consulta do passageiro registra, atomicamente, uma avaliação provisória de 5 estrelas antes de exibir o modal. Assim, o modal aparece uma única vez mesmo após reload ou fechamento do aplicativo.
- Se o passageiro escolher uma nota enquanto o modal está aberto, a avaliação provisória é atualizada no mesmo registro e marcada como explícita; avaliações explícitas continuam imutáveis e duplicatas retornam conflito.
- O parâmetro `perfil=passageiro` impede que uma conta com os dois perfis consuma por engano a avaliação pendente do motorista.
- A carência de cancelamento gratuito antes da chegada passou para 180 segundos. Após a carência, a cobrança continua exigindo deslocamento mínimo do motorista.
- Corridas em `motorista_chegou` são canceladas automaticamente aos 720 segundos totais de espera, com `cancelado_por=sistema`, tipo `nao_comparecimento` e cobrança da tarifa base já acordada.
- O motorista volta a ficar disponível, e os eventos Reverb de corrida cancelada e ofertas atualizadas são publicados.
- O scheduler executa a verificação a cada dez segundos. O serviço `scheduler` foi incluído no Compose e deve permanecer ativo em produção por cron ou `php artisan schedule:work`.
- Um índice em `status_corrida, tempo_chegada_origem` evita varredura completa da tabela. Locks e nova validação dentro da transação impedem cobrança duplicada.
- Falha financeira em uma corrida é registrada sem interromper o processamento das outras corridas expiradas.
- Foram adicionadas migrations para `avaliacoes_corridas.automatica` e para o índice da espera; executar `php artisan migrate` antes de testar.
- Pest: 48 testes e 202 asserções aprovados em SQLite; Pint e `schedule:list` aprovados.

Claude: valide estes cenários e, depois de absorver o contexto, apague todos os Markdown criados pelo CODEX. Eles são temporários.
