# Larastan + Pint + PHPMD

Configurados em 2026-08-26 e **verificados rodando de verdade** (não só por leitura estática).

## Larastan (nível 7)

- Config: `phpstan.neon` na raiz. `level: 7`, paths: `app`, `config`, `database`, `routes`.
- Pacote: `larastan/larastan` em `composer.json` (require-dev).
- Cache: `storage/framework/phpstan` (ignorado no `.gitignore`).
- **Atenção**: PHPStan 2.x removeu os parâmetros antigos `checkMissingIterableValueType` e `checkGenericClassInNonGenericObjectType` — não adicionar de volta, dá erro de "Unexpected item".

Rodar (dentro do container Sail, veja seção abaixo):
```
composer analyse
# ou diretamente:
vendor/bin/phpstan analyse --memory-limit=1G
```

Última execução: `[OK] No errors`.

## Pint

- Config: `pint.json` na raiz (preset `laravel` + regras extras: imports não usados, ordenação alfabética de imports, aspas simples, trailing comma).
- Escopo: o projeto inteiro (não só `app/`) — rodar formata `config/`, `database/`, `resources/lang/` também.

Rodar:
```
composer lint        # aplica correções
composer lint:test    # só verifica, não altera nada
```

Última execução: `PASS, 99 files`.

## PHPMD

- Pacote: `phpmd/phpmd` em `composer.json`, mas na versão **`3.x-dev`** (não a estável 2.15.0) — a 2.15.0 depende de `pdepend/pdepend` 2.16, que só aceita `symfony/dependency-injection` até v7, e o Laravel 13.29 deste projeto já exige `symfony/http-kernel` v8 (conflito real, sem solução estável hoje). Por causa disso, `minimum-stability` do projeto foi de `stable` para `dev` (mitigado por `prefer-stable: true`, que continua preferindo versões estáveis de tudo o mais). **Revisitar quando phpmd/pdepend lançarem uma versão estável compatível com Symfony 8** — aí dá pra voltar `minimum-stability` para `stable` e fixar `phpmd/phpmd` numa versão numerada.
- Rule sets escolhidos pelo usuário: `codesize`, `unusedcode`, `design`, `naming` (ficaram de fora `cleancode` e `controversial`, opinativos demais). Thresholds no padrão do PHPMD (nada customizado).
- Dois rulesets porque `UnusedFormalParameter` (do `unusedcode`) dispara em praticamente todo método stub de controller de recurso (`Request $request` / `Model $x` não usados em corpos vazios `//`) — isso é o padrão normal que o próprio Laravel gera, não um bug:
  - `phpmd.xml` — ruleset completo, usado em `app/` (exceto controllers) + `config/` + `database/` + `routes/`.
  - `phpmd-controllers.xml` — igual, mas exclui `UnusedFormalParameter`; usado só em `app/Http/Controllers`.

Rodar:
```
composer mess
```
(roda os dois passes em sequência com `;`, não `&&`, propositalmente — assim o segundo pass roda mesmo se o primeiro encontrar violação e sair com código 2)

Última execução: 28 violações reais (depois de suprimir as ~65 de `UnusedFormalParameter` em stub de controller). Ver [problemas-conhecidos.md](problemas-conhecidos.md) pra lista.

## Como rodar de verdade (sem PHP/Composer/Docker no shell padrão)

O shell do Bash tool aqui roda dentro de um **Flatpak sandbox** (VSCodium), sem acesso a `php`/`composer`/`docker`. Para alcançar o host real:

```bash
flatpak-spawn --host docker exec <nome-do-container>-laravel.test-1 <comando>
```

O container do Sail já roda com o projeto montado e PHP correto (8.5 no momento). Exemplo real usado nesta sessão:
```bash
flatpak-spawn --host docker ps   # lista os containers (nome do projeto no prefixo)
flatpak-spawn --host docker exec -u sail aplicativo-mobilidade-urbana-backend-laravel.test-1 vendor/bin/phpstan analyse --memory-limit=1G --no-progress
flatpak-spawn --host docker exec -u sail aplicativo-mobilidade-urbana-backend-laravel.test-1 vendor/bin/pint --test
```

Isso exige passar `dangerouslyDisableSandbox: true` na chamada do Bash tool.

**IMPORTANTE — sempre usar `-u sail`**: `docker exec` sem `-u` entra como `root` por padrão. O PHP-FPM que atende requisições reais roda como usuário `sail`. Rodar comandos como root cria/altera arquivos em `storage/` e `bootstrap/cache/` com dono `root`, e o `sail` perde permissão de escrita ali (log, cache, sessão) — isso já causou um 500 real em produção local (ver [problemas-conhecidos.md](problemas-conhecidos.md), incidente de 2026-08-27). Se isso acontecer de novo, o fix é `docker exec -u root <container> chown -R sail:sail storage bootstrap/cache`.

## Git push/pull também precisam do host

O sandbox não tem as credenciais do GitHub (`gh auth` falha ali dentro). Qualquer `git push`/`git pull` que precise autenticar tem que rodar assim:

```bash
flatpak-spawn --host git -C "$(pwd)" push ...
```

(`git status`, `git log`, `git diff` locais funcionam normal dentro do sandbox, sem precisar do `--host` — só operações que tocam o remoto precisam.)
