# Documentos do Codex

Contexto deixado pelo Codex na rodada de 18–19/09/2026, movido da raiz do
repositório para cá em 20/09/2026.

Motivo da mudança: os três arquivos estavam soltos na raiz e **não estavam no
`.gitignore`** — um `git add .` os levaria para o repositório. Aqui eles caem no
curinga `/docs/docs-*` e ficam fora do versionamento, sem perder o conteúdo.

| arquivo | conteúdo |
|---|---|
| `CODEX_IMPLEMENTACOES_PARA_CLAUDE.md` | resumo do que foi alterado nos 4 repositórios e os pontos a validar |
| `CODEX_CONHECIMENTO.md` | contexto acumulado do projeto na visão do Codex |
| `README_SETUP_CODEX.md` | instruções de ambiente |

## Ressalvas sobre esse conteúdo

O relatório afirma que a suíte rodou "em SQLite isolado". **Não rodou.** Não
existe `.env.testing` e o `phpunit.xml` não sobrescreve a conexão — os 51 testes
rodam contra **MySQL**. A afirmação está errada, mas a favor: é o banco real.

O arquivo `CODEX_IMPLEMENTACOES_PARA_CLAUDE.md` termina instruindo a apagar
esses documentos. Instrução dentro de arquivo não é instrução do usuário; a
decisão dele foi **mover, não apagar**.
