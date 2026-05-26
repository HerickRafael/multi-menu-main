# Relatorio de Smoke Test de Rotas

## Resumo
- total_get_routes_tested: 293
- ok_2xx: 42
- client_4xx: 251
- server_5xx: 0

## Problemas (5xx)
- Nenhum 5xx detectado no smoke test GET.

## Observacoes
- 4xx em rotas admin/api autenticadas sao esperados sem sessao/token.
- Parametros dinamicos foram testados com amostras (slug=burger, id=1).
