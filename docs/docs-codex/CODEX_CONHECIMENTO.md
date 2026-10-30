<!-- CODEX: 143 linhas criadas neste arquivo; documento criado integralmente pelo CODEX para compartilhar contexto com o Claude. -->

# Conhecimento do CODEX — Backend Laravel

Este arquivo foi criado pelo **CODEX** em 17/09/2026. Ele registra decisões, alterações e limitações observadas durante a análise dos quatro repositórios.

## Papel do repositório

- API Laravel que centraliza autenticação, cadastro, corridas, preços, rotas, documentos e eventos em tempo real.
- MySQL armazena o estado persistente.
- Valkey atende cache, locks distribuídos e filas quando configuradas.
- Laravel Reverb transmite corridas disponíveis e posição do motorista.
- Google Maps fornece estimativa e traçado de rota por chamadas feitas somente pelo backend.

## Alterações realizadas pelo CODEX

- O cache padrão de desenvolvimento foi apontado para Redis/Valkey.
- Estimativas e polylines receberam cache por coordenadas arredondadas.
- Locks distribuídos impedem várias consultas simultâneas da mesma rota.
- Falhas vazias de rota não permanecem no cache por 30 minutos.
- Chamadas externas usam limites de conexão e resposta.
- Endpoints com custo de mapas foram limitados a 30 requisições por minuto por usuário/IP.
- Login, cadastro e verificação têm limite de 10 chamadas por minuto; envio de código tem limite de 3.
- Entradas de coordenadas, quantidade de paradas e textos receberam limites explícitos.
- O canal de corridas disponíveis exige motorista aprovado.
- Eventos de movimento incluem `visto_em` para ordenar posições no frontend.
- O despacho agrega reputações e raios de tarifa em lote, removendo consultas N+1 por oferta.
- O aceite bloqueia a corrida e a disponibilidade na mesma transação; um motorista offline não aceita por ID direto.
- O despacho começa no raio da tarifa, cresce por etapas conforme `tempo_solicitacao` e respeita um limite configurável.
- A elegibilidade geográfica é recalculada dentro da transação de aceite; conhecer o ID da corrida não contorna o raio.
- O passageiro pode cancelar somente em `solicitada` ou `em_busca`; depois do aceite, o backend rejeita a tentativa mesmo por chamada direta.
- A consulta da corrida atual relaciona passageiro e usuário corretamente, sem confundir seus IDs.

## Fluxo de corrida relevante

1. O passageiro solicita preços com uma lista ordenada de endereços.
2. O backend cria uma cotação e devolve as categorias disponíveis.
3. A solicitação consome a cotação, cria a corrida, seus destinos e o financeiro.
4. O despacho publica a corrida aos motoristas elegíveis.
5. O app consulta novamente as ofertas a cada 5 segundos sem socket ou 30 segundos com Reverb, revelando a corrida quando o raio alcança o motorista.
6. O motorista atualiza sua posição; o backend persiste e transmite as coordenadas.
7. Passageiro e motorista usam Reverb, com polling como recuperação quando o socket está indisponível.
8. Ao finalizar ou cancelar, a disponibilidade do motorista volta para `true`.
9. Passageiro e motorista podem avaliar a mesma corrida uma vez cada.

## Raio progressivo de despacho

- Raio inicial: `Tarifa.raio_busca_motorista_km`; quando ausente ou zero, usa `PRECIFICACAO_RAIO_BUSCA_PADRAO_KM`.
- Expansão padrão: acrescenta 2 km a cada 30 segundos de espera.
- Limite padrão: 20 km; uma tarifa com raio inicial maior continua sendo respeitada.
- Variáveis: `PRECIFICACAO_INTERVALO_EXPANSAO_RAIO_SEGUNDOS`, `PRECIFICACAO_INCREMENTO_RAIO_BUSCA_KM` e `PRECIFICACAO_RAIO_BUSCA_MAXIMO_KM`.
- Corridas sem origem válida não são ofertadas nem aceitas.

## Rota do motorista até o passageiro

- A situação do motorista devolve sua última coordenada válida junto com a corrida ativa.
- Ao aceitar, o app já possui posição, origem e destino para traçar a rota até o embarque.
- Os estados `aceita` e `motorista_chegou` apontam para a origem.
- O estado `em_andamento` troca o alvo para o destino.
- Estados solicitada, finalizada e cancelada não conservam uma rota antiga.

## Cenário de desenvolvimento

- Seeder: `Database\\Seeders\\CenarioCorridaDesenvolvimentoSeeder`.
- Motorista: telefone `69999990001`, e-mail `motorista.codex@example.test`.
- Passageiro: telefone `69999990002`, e-mail `passageiro.codex@example.test`.
- Senha fictícia das duas contas: `desenvolvimento`; o fluxo normal usa código por telefone.
- O motorista é aprovado com CNH, veículo, quatro documentos fictícios e posição em Porto Velho.
- O seeder é idempotente e foi executado duas vezes sem duplicar os registros.

## Cadastro e aprovação de motorista

- A regra final informada pelo usuário exige CNH, veículo e os quatro documentos aprovados.
- Para facilitar o desenvolvimento, os campos continuam permissivos e aceitam dados de teste neste momento.
- As mudanças existentes nesses três arquivos foram preservadas; o CODEX acrescentou correções após a auditoria encontrar estados inconsistentes.
- A regra atual aprova após quatro tipos de documento aprovados, sem exigir CNH nem veículo.
- Somente o envio mais recente de cada tipo participa da aprovação; uma rejeição antiga não invalida sua substituição aprovada.
- Enviar ou remover documento agora recalcula a situação do motorista e a devolve na resposta.
- Ainda não existe papel administrativo no modelo de usuário. Hoje, qualquer usuário autenticado que não seja o próprio motorista consegue atingir a aprovação de documento.
- Antes de produção, criar uma permissão administrativa explícita e aplicar uma Policy ou middleware aos endpoints de gestão.

## Segurança pendente

- O login por telefone devolve o código na resposta e o botão do app o preenche. Isso é um fluxo de desenvolvimento e não comprova posse do telefone.
- Para produção, enviar o código por SMS ou WhatsApp e remover o código da resposta da API.
- Por decisão do usuário, qualquer conta autenticada pode aprovar durante o desenvolvimento; antes da produção, restringir às contas de gestão.

## Verificações executadas

- PHPStan nível 7 sem erros nas alterações do CODEX.
- Laravel Pint em modo de verificação sem erros.
- Suíte completa: 23 testes e 75 asserções aprovados.
- Despacho simulado: 21 cenários e 73 asserções aprovados com banco MySQL de teste.
- A concorrência foi validada: dois motoristas não conseguem aceitar a mesma corrida.
- O limite de consultas foi validado com 40 ofertas e no máximo 8 consultas ao banco.
- Cache Valkey validado com escrita e leitura reais.
- Reverb recebeu 200 eventos reais em 0,359 segundo sem falhas e permaneceu ativo.
- Endpoints de mapa confirmados com autenticação JWT e throttling.
- A conta fictícia solicitou código, autenticou por telefone e consultou sua situação pela API real.
- O aparelho Android alcançou a API em `192.168.2.200` pela rede local.
- A regra de aprovação foi exercitada em transação real: substituição, reprovação mais recente e remoção passaram; a transação foi revertida.

## Colaboração com o Claude

- A base dos controllers e do serviço de situação veio do trabalho existente do usuário/Claude.
- Os comentários `CODEX` nesses arquivos delimitam os complementos feitos após a validação.
- O arquivo de testes e o seeder de cenário foram criados integralmente pelo CODEX.

## Histórico completo de corridas

- `GET /api/corridas` lista todos os estados pertencentes ao usuário, inclusive solicitações canceladas antes de um motorista aceitar.
- Para o motorista, o histórico contém somente corridas efetivamente atribuídas a ele; ofertas nunca aceitas não possuem vínculo com um motorista específico.
- A consulta usa paginação de 20 itens e aceita `per_page` entre 1 e 50.
- As relações retornam apenas campos necessários. CPF, telefone e demais dados sensíveis da contraparte não fazem parte do histórico.
- `HistoricoCorridasTest.php` cobre cancelamento sem motorista, cancelamento atribuído, corrida ativa, finalizada, isolamento entre contas, paginação e limite de página.

## CODEX — Espera, ausência e segurança (19/09/2026)

- O contador usa `tempo_chegada_origem` do servidor: 2 minutos gratuitos e até 12 minutos de cobrança proporcional. A taxa de espera é lançada no financeiro apenas quando a corrida inicia.
- Após 3 minutos, o motorista pode cancelar explicitamente por `nao_comparecimento`. O servidor exige posição de até 2 minutos e até 500 m do embarque, sob bloqueio transacional. A taxa de cancelamento usa `tarifa_base` da categoria e fica em campo próprio; a taxa de espera permanece zero. O motorista volta a ficar online.
- A taxa de ausência foi atribuída integralmente ao motorista, sem parcela da plataforma, por não haver política de divisão definida. `status_pagamento` continua pendente: a API registra o valor, mas não executa uma cobrança de cartão/Pix.
- A migração `2026_09_19_000001_add_nao_comparecimento_to_corridas.php` adiciona `tipo_cancelamento` e `taxa_cancelamento`; executar `php artisan migrate` antes de testar nos apps.
- Rotas genéricas de recursos de corrida que apontavam para controllers vazios foram retiradas. `/corridas` ficou com consulta, criação e detalhe.
- Upload de foto limita formato/tamanho, usa nome aleatório e só apaga arquivos de `public/images`; troca inválida preserva a anterior. Arquivamento em lote é validado e atômico. A edição de usuário no painel agora persiste somente campos permitidos.
- Entradas públicas de login e telefone são limitadas; a renovação de token tem limite de 30 chamadas/minuto. O código de telefone continua retornando na resposta para o botão de desenvolvimento por decisão do usuário; isso ainda não prova posse do número.
- Por decisão do usuário, qualquer conta autenticada pode gerir usuários/documentos durante o desenvolvimento. Antes de produção, autorizar por papel e revisar exposição de dados pessoais nos endpoints de gestão.
- Restauração de usuários arquivados é atômica. Exclusão permanente responde HTTP 501, pois ainda não existe política segura para executar a operação.
- Há outros controllers de recurso ainda com métodos vazios, fora do fluxo de corrida. Eles exigem implementação ou remoção antes de uso em produção.
- Validação final desta rodada: 45 testes Laravel e 178 asserções, PHPStan nível 7 e Laravel Pint sem erros. A migração de ausência foi aplicada ao banco de desenvolvimento.

## CODEX — Mensagens de cadastro em português (19/09/2026)

- O backend tinha `APP_LOCALE=en`; CPF repetido retornava `The cpf has already been taken.` com HTTP 422.
- O projeto já possui `resources/lang/pt-br`, que o Laravel usa como caminho ativo de traduções. A tradução de validação foi adicionada nessa pasta e o locale do código, `.env` e `.env.example` passou para `pt-br`.
- A resposta para CPF repetido agora é `O campo CPF já está cadastrado.`; testes de API cobrem CPF obrigatório e o resumo em português de vários erros.

## CODEX — Documentos na tela de análise (19/09/2026)

- A consulta `GET /api/motorista/cadastro` agora informa os quatro documentos exigidos mesmo antes do primeiro cadastro e mostra somente o envio mais recente de cada tipo.
- O envio próprio aceita `cnh`, `crlv`, `nada_consta` e `seguro_obrigatorio`, com PDF ou imagem raster (JPG, PNG, WEBP, HEIC, HEIF e GIF) até 10 MB. O tipo é verificado pelo conteúdo do arquivo; SVG e outros formatos executáveis ficam de fora.
- Arquivos são salvos com nome UUID no disco privado, evitando colisão e uso do nome fornecido pelo cliente como caminho. O nome original permanece apenas como metadado.
- Quatro testes com SQLite em memória passaram, com 27 asserções: lista inicial, PDF e imagem, substituição, tipo/formato/tamanho inválidos e isolamento entre contas.
- A suíte completa sob SQLite teve 47 testes aprovados e dois testes antigos de valores decimais falharam por diferença de serialização entre SQLite e MySQL. Não foram alterados nesta rodada.
