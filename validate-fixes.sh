#!/bin/bash

# Validação de Integridade - 4 Bug Fixes + Consolidações
# Uso: bash validate-fixes.sh

set -e

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║  VALIDAÇÃO DE INTEGRIDADE - REGRESSÃO FUNCIONAL                ║"
echo "║  Projeto: WollBurger | Data: 09/05/2026                        ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

ERRORS=0
WARNINGS=0
PASSES=0

# Cores ANSI
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

pass() {
  echo -e "${GREEN}✓${NC} $1"
  PASSES=$((PASSES + 1))
}

fail() {
  echo -e "${RED}✗${NC} $1"
  ERRORS=$((ERRORS + 1))
}

warn() {
  echo -e "${YELLOW}⚠${NC} $1"
  WARNINGS=$((WARNINGS + 1))
}

section() {
  echo ""
  echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
  echo -e "${BLUE}$1${NC}"
  echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

# ════════════════════════════════════════════════════════════════════
section "1. VALIDAÇÃO DE SINTAXE"
# ════════════════════════════════════════════════════════════════════

echo "PHP Files:"
php_files=(
  "app/views/cart/partials/totals.php"
  "app/views/public/checkout.php"
)

for f in "${php_files[@]}"; do
  if php -l "$f" > /dev/null 2>&1; then
    pass "$f"
  else
    fail "$f"
  fi
done

echo ""
echo "JavaScript Files:"
js_files=(
  "public/assets/checkout.js"
  "public/assets/cart.js"
)

for f in "${js_files[@]}"; do
  if [ -f "$f" ]; then
    if node --check "$f" > /dev/null 2>&1; then
      pass "$f"
    else
      fail "$f"
    fi
  else
    warn "$f (não encontrado)"
  fi
done

# ════════════════════════════════════════════════════════════════════
section "2. BUG FIX #1: BARRA DE PROGRESSO"
# ════════════════════════════════════════════════════════════════════

echo "Validações:"

# 1a: unified-progress__fill adicionado ao HTML
if grep -q 'class="unified-progress__fill"' app/views/cart/partials/totals.php; then
  pass "[HTML] <div class=\"unified-progress__fill\"></div> adicionado"
else
  fail "[HTML] unified-progress__fill não encontrado"
fi

# 1b: CSS .unified-progress__bar simplificado
if grep -q "\.unified-progress__bar{.*width:100%" app/assets/cart.css 2>/dev/null || \
   grep -q "\.unified-progress__bar{.*width:100%" public/assets/cart.css 2>/dev/null; then
  pass "[CSS] .unified-progress__bar com width:100% (sem gradient inválido)"
else
  warn "[CSS] .unified-progress__bar - verificar width"
fi

# 1c: CSS .unified-progress__fill com width var
if grep -q "\.unified-progress__fill{.*width:var(--progress-width" public/assets/cart.css; then
  pass "[CSS] .unified-progress__fill com width:var(--progress-width)"
else
  fail "[CSS] .unified-progress__fill width var não encontrado"
fi

# ════════════════════════════════════════════════════════════════════
section "3. BUG FIX #2: SELECT BAIRRO BLOQUEADO"
# ════════════════════════════════════════════════════════════════════

if grep -q "zoneSelect.disabled = !cityId;" public/assets/checkout.js && \
   ! grep -q "zoneSelect.disabled = !cityId || zones.length === 0;" public/assets/checkout.js; then
  pass "Condição corrigida: !cityId (sem || zones.length === 0)"
  pass "Select bairro habilita assim que há cidade selecionada"
else
  fail "zones.length === 0 ainda está na condição OU não encontrado"
fi

# ════════════════════════════════════════════════════════════════════
section "4. BUG FIX #3: TOTAIS ZERADOS"
# ════════════════════════════════════════════════════════════════════

# Verificar guard if (data.selectedZoneId > 0)
if grep -q "if (data.selectedZoneId > 0 && opt && zoneSelect.value !== '')" public/assets/checkout.js; then
  pass "Guard updateSummary adicionado: if (data.selectedZoneId > 0)"
  pass "Subtotal/Total só atualizam com zona válida selecionada"
else
  fail "Guard updateSummary não encontrado"
fi

# ════════════════════════════════════════════════════════════════════
section "5. BUG FIX #4: CLIQUE PAGAMENTO"
# ════════════════════════════════════════════════════════════════════

# Verificar delegação com closest
if grep -q "const paymentsContainer = document.getElementById('checkout-payment');" public/assets/checkout.js && \
   grep -q "e.target.closest('\[data-payment-select=\"1\"\]')" public/assets/checkout.js; then
  pass "Delegação implementada com closest() no container"
  pass "Cliques em filhos (SVG, texto) agora propagam para seleção"
else
  fail "Delegação com closest() não encontrada"
fi

# ════════════════════════════════════════════════════════════════════
section "6. CONSOLIDAÇÕES: SVG HELPERS"
# ════════════════════════════════════════════════════════════════════

# Verificar que shared helper foi criado
if [ -f "app/views/shared/helpers/svg_helper.php" ]; then
  pass "app/views/shared/helpers/svg_helper.php criado"
  
  if grep -q "function svg_shared" app/views/shared/helpers/svg_helper.php; then
    pass "Função svg_shared() definida"
  else
    fail "Função svg_shared() não encontrada"
  fi
else
  fail "shared svg_helper não criado"
fi

echo ""
echo "Verificando require_once nos helpers locais:"

helpers=(
  "cart"
  "addresses"
  "product"
  "customization"
  "checkout-success"
  "order"
  "profile"
)

shared_loaded=0
for h in "${helpers[@]}"; do
  helper_file="app/views/$h/helpers/svg_helper.php"
  if [ -f "$helper_file" ]; then
    if grep -q "require_once.*shared/helpers/svg_helper.php" "$helper_file"; then
      pass "$h/helpers/svg_helper.php carrega shared"
      shared_loaded=$((shared_loaded + 1))
    else
      warn "$h/helpers/svg_helper.php não carrega shared"
    fi
  fi
done

if [ "$shared_loaded" -eq 7 ]; then
  pass "Todos 7 helpers carregam shared (100%)"
else
  warn "Apenas $shared_loaded/7 helpers carregam shared"
fi

# ════════════════════════════════════════════════════════════════════
section "7. CONSOLIDAÇÕES: DATA-CONFIRM"
# ════════════════════════════════════════════════════════════════════

if [ -f "public/assets/shared.js" ]; then
  pass "public/assets/shared.js criado"
  
  if grep -q "data-confirm" public/assets/shared.js; then
    pass "Listener data-confirm implementado em shared.js"
  else
    fail "Listener data-confirm não encontrado em shared.js"
  fi
else
  fail "shared.js não criado"
fi

echo ""
echo "Verificando remoção do listener duplicado:"

if grep -q "Listener data-confirm consolidado" public/assets/order.js; then
  pass "order.js: listener removido, comentário adicionado"
else
  warn "order.js: verificar se listener foi removido"
fi

if grep -q "Listener data-confirm consolidado" public/assets/profile.js; then
  pass "profile.js: listener removido, comentário adicionado"
else
  warn "profile.js: verificar se listener foi removido"
fi

# ════════════════════════════════════════════════════════════════════
section "8. CONSOLIDAÇÕES: HELPERS GLOBAIS"
# ════════════════════════════════════════════════════════════════════

helpers_global=(
  "badgePromo"
  "normalize_color_hex"
  "price_br"
)

for helper_func in "${helpers_global[@]}"; do
  if grep -q "function $helper_func" app/core/CommonHelpers.php; then
    pass "CommonHelpers::$helper_func() definida"
  else
    fail "CommonHelpers::$helper_func() não encontrada"
  fi
done

# ════════════════════════════════════════════════════════════════════
section "9. INJEÇÃO DO SHARED.JS NAS VIEWS"
# ════════════════════════════════════════════════════════════════════

if grep -q 'src=".*shared\.js"' app/views/order/order.php; then
  pass "order.php carrega shared.js"
else
  warn "order.php não carrega shared.js"
fi

if grep -q 'src=".*shared\.js"' app/views/profile/profile.php; then
  pass "profile.php carrega shared.js"
else
  warn "profile.php não carrega shared.js"
fi

# ════════════════════════════════════════════════════════════════════
section "10. RESUMO FINAL"
# ════════════════════════════════════════════════════════════════════

echo ""
echo -e "${GREEN}Validações Passaram:${NC} $PASSES"
echo -e "${RED}Erros Encontrados:${NC}   $ERRORS"
echo -e "${YELLOW}Avisos:${NC}              $WARNINGS"
echo ""

if [ "$ERRORS" -eq 0 ]; then
  echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
  echo -e "${GREEN}║  ✓ VALIDAÇÃO COMPLETA - PRONTO PARA REGRESSÃO FUNCIONAL         ║${NC}"
  echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
  echo ""
  echo "Próximos passos:"
  echo "1. Abrir browser em http://localhost:8088/burger (ou seu domínio)"
  echo "2. Abrir DevTools (F12) - Console + Network"
  echo "3. Executar fluxos de teste críticos:"
  echo "   - Navegação → Produto → Carrinho → Checkout → Pedido"
  echo "   - Validar BUG FIX #2: Trocar cidade → bairro habilita"
  echo "   - Validar BUG FIX #3: Subtotal/Total não zerados"
  echo "   - Validar BUG FIX #4: Clique em PIX (em qualquer parte do card)"
  echo "4. Monitorar: 0 console errors, 0 requisições 404"
  exit 0
else
  echo -e "${RED}╔════════════════════════════════════════════════════════════════╗${NC}"
  echo -e "${RED}║  ✗ VALIDAÇÃO COM ERROS - CORRIJA ANTES DE CONTINUAR            ║${NC}"
  echo -e "${RED}╚════════════════════════════════════════════════════════════════╝${NC}"
  exit 1
fi
