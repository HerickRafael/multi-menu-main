#!/bin/bash
# Script para gerar ícones PWA a partir de um logo
# Uso: ./generate-pwa-icons.sh logo.png

set -e

INPUT="${1:-public/uploads/logo.png}"
OUTPUT_DIR="public/assets/icons"

if [ ! -f "$INPUT" ]; then
    echo "Arquivo não encontrado: $INPUT"
    echo "Uso: $0 <caminho-para-logo.png>"
    exit 1
fi

# Verificar se ImageMagick está instalado
if ! command -v convert &> /dev/null; then
    echo "ImageMagick não está instalado. Instalando..."
    apt-get update && apt-get install -y imagemagick
fi

mkdir -p "$OUTPUT_DIR"

# Tamanhos padrão para PWA
SIZES=(72 96 128 144 152 180 192 384 512)

echo "Gerando ícones PWA..."

for SIZE in "${SIZES[@]}"; do
    echo "  - icon-${SIZE}x${SIZE}.png"
    convert "$INPUT" -resize "${SIZE}x${SIZE}" -background white -gravity center -extent "${SIZE}x${SIZE}" "$OUTPUT_DIR/icon-${SIZE}x${SIZE}.png"
done

# Ícones especiais
echo "  - favicon-32x32.png"
convert "$INPUT" -resize "32x32" "$OUTPUT_DIR/icon-32x32.png"

echo "  - apple-touch-icon.png (180x180)"
convert "$INPUT" -resize "180x180" -background white -gravity center -extent "180x180" "$OUTPUT_DIR/apple-touch-icon.png"

# Ícones para shortcuts
echo "  - cart-icon.png"
convert -size 96x96 xc:transparent -fill "#5B21B6" -draw "circle 48,48 48,8" -fill white -pointsize 40 -gravity center -annotate +0+5 "🛒" "$OUTPUT_DIR/cart-icon.png" 2>/dev/null || echo "    (usando fallback)"

echo "  - profile-icon.png"
convert -size 96x96 xc:transparent -fill "#5B21B6" -draw "circle 48,48 48,8" -fill white -pointsize 40 -gravity center -annotate +0+5 "👤" "$OUTPUT_DIR/profile-icon.png" 2>/dev/null || echo "    (usando fallback)"

echo ""
echo "✅ Ícones gerados em: $OUTPUT_DIR"
echo ""
echo "Próximos passos:"
echo "1. Verifique os ícones gerados"
echo "2. Customize as cores no manifest.webmanifest se necessário"
echo "3. Rebuild a imagem Docker"
