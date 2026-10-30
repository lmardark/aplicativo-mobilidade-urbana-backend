# Visão geral

Backend Laravel (v13, PHP ^8.3) de um app de mobilidade urbana (estilo Uber/99). Auth via JWT (`php-open-source-saver/jwt-auth`), banco Postgres, ambiente de dev via Laravel Sail (Docker Compose).

## Domínios principais

- **Auth/Usuario** — login (JWT + código de verificação por telefone), cadastro de usuário.
- **Motorista** — motoristas, documentos de motorista, veículos vinculados ao motorista.
- **Passageiro** — passageiros.
- **Veiculo** — veículos.
- **Corrida** — o núcleo do domínio: corrida em si, destinos da corrida, financeiro da corrida, descontos, negociações, avaliações, status de busca.
- **Produto** — produtos de corrida (ex: "Pop", "Negocia", "Moto") e suas categorias.
- **Tarifa** — tarifas por cidade/produto.
- **Estimativa** — cálculo de rota/distância via Google Maps Directions API (`EstimarRotaService`).

## Fluxo de negociação de corrida (parcialmente implementado)

`SimularCorridaNegociadaService` calcula valores (motorista/passageiro/taxa de plataforma) a partir de distância, tempo e diferença negociada. Está ligado à rota `GET corridas-negociada` via `CorridaController::simularCorridaNegociada()`. O restante do fluxo de negociação (persistência da negociação, aceite do motorista etc.) ainda não está implementado — ver [problemas-conhecidos.md](problemas-conhecidos.md).

## Convenções

- Controllers organizados por domínio em subpastas (ver [estrutura-controllers.md](estrutura-controllers.md)).
- Models ficam flat em `app/Models` (convenção padrão do Laravel).
- **Nunca comentar código** — nomes de identificadores devem ser autoexplicativos. Comentário só quando o *porquê* não é óbvio (uma decisão não trivial, uma pegadinha).
- **Nunca `git add`/`commit`/`push`** — só o usuário faz isso.
