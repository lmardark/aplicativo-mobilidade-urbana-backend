# Revisão do banco de dados (09/10/2026)

Feita a pedido do usuário: "verificação geral do banco de dados, do que pode
ser reutilizado". Contagens do banco de desenvolvimento nesse dia.

## Já resolvido nesta rodada

- **Duas tabelas de cidade.** `municipios` (vazia) era só o alvo das chaves de
  `corridas.cidade_id` e `tarifas.cidade_id`; `cidades` (IBGE + UF) servia à
  publicidade. Migration `2026_10_09_000004` aponta as duas chaves para
  `cidades` e apaga `municipios`. Corridas: `nullOnDelete` (antes apagar a
  cidade apagava as corridas). Tarifas: `restrictOnDelete`. O
  `CidadeController::destroy` responde 409 se a cidade tiver banners ou tarifas.
- **Notificações misturando papéis.** `notificacoes.perfil` (passageiro/
  motorista), filtro `?perfil=` no endpoint, as antigas classificadas pelo título.
- **Prioridade de produto.** `produtos_corridas.prioritario` (Expresso);
  reaproveitável por qualquer categoria "prioritária" futura.

## Pode ser reaproveitado

| O quê | Situação | Sugestão |
|---|---|---|
| `produto_categorias` + `ProdutoCategoriaController` + rotas em `routes/api/produto.php` | 0 linhas; substituída por `produtos_corridas.grupo/tipo_veiculo` | Virar o catálogo dos grupos (nome exibido "Normal"/"Expresso", descrição, ícone, ordem). Hoje esses textos estão fixos no app (`tituloParaExibicao`, `ROTULO_DO_GRUPO`). Ou apagar. |
| `corrida_descontos` + model/controller/factory; `corrida_financeiros.valor_descontos` | 0 linhas, coluna sempre nula | Base para cupons/promoções, inclusive ligados aos banners da publicidade. |
| `users.type` (cast `UserType` no model) | A coluna não existe no banco | Quando o papel de gestão virar prioridade, criar a coluna e reaproveitar o enum (gestao/passageiro/motorista). |
| `passageiros.media_avaliacao` | Sempre nula; a nota é calculada a cada pedido (AVG em `avaliacoes_corridas`) | Gravar a média ao salvar cada avaliação e ler daqui (menos consultas). |
| `tarifas.cidade_id`, `cotacoes_corrida.cidade_id`, `corridas.cidade_id` | Sempre nulas | Com `cidades` unificada, o painel da agência já pode criar tarifa por cidade (o `ResolverTarifaService` já prefere a da cidade). Falta resolver a cidade da origem ao cotar. |
| `corridas.multiplicador_dinamico`, `corrida_financeiros.valor_dinamico_aplicado`, `subsidiado_plataforma` | Sempre nulos | Reservados para preço dinâmico e subsídio; manter se a ideia continua de pé. |

## Pode ser removido ou limpo

| O quê | Situação | Sugestão |
|---|---|---|
| `personal_access_tokens` (Sanctum) | O app autentica por JWT; tabela sem uso | Remover o Sanctum ou deixar sem uso consciente. |
| `cotacoes_corrida` | 177 linhas e crescendo (uma por cotação), sem limpeza | Agendar limpeza das expiradas (ex.: mais de 1 dia) com `Prunable`. Não há chave estrangeira apontando para ela. |
| `corridas.distancia_ate_motorista` | Sempre nula; duplicada por `distancia_motorista_aceite_km` (usada) | Remover. |
| Produto `moto_eletrica` | Tarifa inativa (decisão: não existe moto elétrica) | Apagar o produto. |
| Produtos `Entrega Carro/Moto` | Sem tarifa, `ordem` 0; fluxo de entrega não existe (aba "Em breve") | Manter para quando a entrega existir. |
| `veiculos.status` | Texto livre; só "aprovado" em uso | Definir os valores (o painel agora oferece Aprovado/Em análise/Reprovado). |
