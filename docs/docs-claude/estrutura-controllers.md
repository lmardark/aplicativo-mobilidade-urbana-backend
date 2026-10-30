# Estrutura de controllers

Reorganizado em 2026-08-26: antes tudo ficava solto em `app/Http/Controllers/*.php` misturando lógicas de domínios diferentes. Agora cada domínio tem sua própria subpasta:

```
app/Http/Controllers/
  Controller.php              # classe base abstrata
  Auth/                       # LoginController, LogoutController
  Usuario/                    # UsuarioController
  Corrida/                    # CorridaController, CorridaDestinoController,
                               # CorridaDescontoController, CorridaFinaceiroController,
                               # CorridaNegociacoesController, AvaliacoesCorridaController,
                               # StatusBuscaController
  Motorista/                  # MotoristaController, MotoristaDocumentoController
  Passageiro/                 # PassageiroController
  Produto/                    # ProdutoCategoriaController, ProdutosCorridaController
  Veiculo/                    # VeiculosController
  Tarifa/                     # TarifaController
  Estimativa/                 # EstimativasController
```

Regra para novos controllers: sempre criar dentro da subpasta de domínio correspondente (criar uma nova subpasta se for um domínio novo), nunca direto em `app/Http/Controllers/`.

Models continuam flat em `app/Models` (não foram reorganizados — convenção padrão do Laravel, e não era o ponto de dor do usuário).
