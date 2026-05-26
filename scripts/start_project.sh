#!/usr/bin/env bash

set -euo pipefail

# Resolve diretório raiz do projeto mesmo quando chamado de outro local.
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo "[1/4] Entrando no diretório do projeto..."
cd "$PROJECT_DIR"

if command -v colima >/dev/null 2>&1; then
  echo "[2/4] Garantindo runtime Docker (Colima) ativo..."
  colima start
else
  echo "[2/4] Colima não encontrado. Pulando esta etapa."
fi

echo "[3/4] Subindo containers com Docker Compose..."
docker compose up -d

echo "[4/4] Status dos containers:"
docker compose ps

echo ""
echo "Projeto iniciado com sucesso."
echo "App: http://localhost:8088"
echo "phpMyAdmin: http://localhost:8081"
