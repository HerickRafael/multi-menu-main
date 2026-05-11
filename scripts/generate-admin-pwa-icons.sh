#!/bin/bash

# Script para gerar ícones do PWA Admin
# Requer: ImageMagick (convert) e um ícone base de 512x512

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ICONS_DIR="$SCRIPT_DIR/../public/assets/icons/admin"
SOURCE_ICON="$SCRIPT_DIR/../public/assets/icons/admin/icon-source.png"

# Criar diretório se não existir
mkdir -p "$ICONS_DIR"

# Se não houver ícone fonte, criar um placeholder SVG e convertê-lo
if [ ! -f "$SOURCE_ICON" ]; then
    echo "Ícone fonte não encontrado. Criando placeholder..."
    
    # Criar SVG placeholder
    cat > "$ICONS_DIR/icon-source.svg" << 'EOF'
<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">
  <defs>
    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#5B21B6"/>
      <stop offset="100%" style="stop-color:#7C3AED"/>
    </linearGradient>
  </defs>
  <rect width="512" height="512" rx="96" fill="url(#grad)"/>
  <g transform="translate(128, 128)" fill="none" stroke="white" stroke-width="16" stroke-linecap="round" stroke-linejoin="round">
    <path d="M24 24h208a16 16 0 0116 16v176a16 16 0 01-16 16H24a16 16 0 01-16-16V40a16 16 0 0116-16z"/>
    <path d="M64 8v32"/>
    <path d="M192 8v32"/>
    <path d="M8 88h240"/>
    <circle cx="80" cy="144" r="16" fill="white"/>
    <circle cx="176" cy="144" r="16" fill="white"/>
    <path d="M80 192c16 24 80 24 96 0"/>
  </g>
</svg>
EOF
    
    # Converter SVG para PNG (requer ImageMagick)
    if command -v convert &> /dev/null; then
        convert -background none -resize 512x512 "$ICONS_DIR/icon-source.svg" "$SOURCE_ICON"
        rm "$ICONS_DIR/icon-source.svg"
    else
        echo "ImageMagick não encontrado. Instale com: sudo apt install imagemagick"
        echo "Ou forneça manualmente um ícone de 512x512 em: $SOURCE_ICON"
        exit 1
    fi
fi

echo "Gerando ícones a partir de: $SOURCE_ICON"

# Tamanhos necessários
SIZES=(72 96 120 128 144 152 180 192 256 384 512)

for SIZE in "${SIZES[@]}"; do
    OUTPUT="$ICONS_DIR/icon-${SIZE}x${SIZE}.png"
    echo "  Gerando: icon-${SIZE}x${SIZE}.png"
    convert "$SOURCE_ICON" -resize ${SIZE}x${SIZE} "$OUTPUT"
done

# Favicon
echo "  Gerando: favicon-32x32.png"
convert "$SOURCE_ICON" -resize 32x32 "$ICONS_DIR/favicon-32x32.png"

echo "  Gerando: favicon-16x16.png"
convert "$SOURCE_ICON" -resize 16x16 "$ICONS_DIR/favicon-16x16.png"

# Apple Touch Icon
echo "  Gerando: apple-touch-icon.png"
convert "$SOURCE_ICON" -resize 180x180 "$ICONS_DIR/apple-touch-icon.png"

# Badge para notificações
echo "  Gerando: badge-72x72.png"
convert "$SOURCE_ICON" -resize 72x72 "$ICONS_DIR/badge-72x72.png"

# Ícones de shortcuts
echo "  Gerando: orders-icon.png"
convert "$SOURCE_ICON" -resize 96x96 "$ICONS_DIR/orders-icon.png"

echo "  Gerando: kds-icon.png"
convert "$SOURCE_ICON" -resize 96x96 "$ICONS_DIR/kds-icon.png"

echo "  Gerando: products-icon.png"
convert "$SOURCE_ICON" -resize 96x96 "$ICONS_DIR/products-icon.png"

echo "  Gerando: financial-icon.png"
convert "$SOURCE_ICON" -resize 96x96 "$ICONS_DIR/financial-icon.png"

# Splash screens para iOS
echo "Gerando splash screens para iOS..."

generate_splash() {
    local WIDTH=$1
    local HEIGHT=$2
    local NAME="splash-${WIDTH}x${HEIGHT}.png"
    echo "  Gerando: $NAME"
    
    # Criar splash com fundo gradiente e ícone centralizado
    convert -size ${WIDTH}x${HEIGHT} \
        -define gradient:angle=135 \
        gradient:'#5B21B6-#7C3AED' \
        \( "$SOURCE_ICON" -resize 25% -gravity center \) \
        -gravity center -composite \
        "$ICONS_DIR/$NAME"
}

generate_splash 640 1136   # iPhone 5/SE
generate_splash 750 1334   # iPhone 6/7/8
generate_splash 1242 2208  # iPhone 6+/7+/8+
generate_splash 1125 2436  # iPhone X/XS
generate_splash 1170 2532  # iPhone 12/13
generate_splash 1284 2778  # iPhone 12/13 Pro Max

# Browserconfig para Windows
cat > "$ICONS_DIR/browserconfig.xml" << EOF
<?xml version="1.0" encoding="utf-8"?>
<browserconfig>
  <msapplication>
    <tile>
      <square70x70logo src="/assets/icons/admin/icon-72x72.png"/>
      <square150x150logo src="/assets/icons/admin/icon-152x152.png"/>
      <square310x310logo src="/assets/icons/admin/icon-384x384.png"/>
      <TileColor>#5B21B6</TileColor>
    </tile>
  </msapplication>
</browserconfig>
EOF

echo ""
echo "✓ Todos os ícones foram gerados em: $ICONS_DIR"
echo ""
echo "Arquivos gerados:"
ls -la "$ICONS_DIR"
