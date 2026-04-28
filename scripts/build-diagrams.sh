#!/usr/bin/env bash
# Renderuje všechny .puml/.uml v templates/diagrams/ do SVG.
# Po renderu nahradí font-family z DejaVu Sans na Inter (browser webfont).
# Theme: templates/diagrams/theme.iuml — sdílená tmavá paleta.

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DIAGRAMS_DIR="$ROOT/templates/diagrams"

cd "$ROOT"

# 1) Najdi všechny zdrojové soubory (kromě theme.iuml)
mapfile -t SOURCES < <(find "$DIAGRAMS_DIR" \( -name "*.puml" -o -name "*.uml" \) ! -name "theme.iuml" | sort)

echo "Renderuji ${#SOURCES[@]} diagramů…"

# 2) Renderuj každý do svého adresáře
for src in "${SOURCES[@]}"; do
    dir=$(dirname "$src")
    echo "  • $src"
    plantuml -charset UTF-8 -tsvg -o "$dir" "$src"
done

# 3) Post-process: nahraď font-family DejaVu Sans → Inter v každém SVG.
#    PlantUML používá DejaVu pro layout (jediný spolehlivý systémový font s českými glyphy),
#    ale browser by měl zobrazit Inter (loaded webfont).
echo "Post-processuji SVG (font, bílé výplně activity stop kruhů)…"
find "$DIAGRAMS_DIR" -name "*.svg" -print0 | while IFS= read -r -d '' svg; do
    sed -i 's/font-family="DejaVu Sans"/font-family="Inter, ui-sans-serif, system-ui, -apple-system, sans-serif"/g; s/font-family:DejaVu Sans/font-family:Inter, ui-sans-serif, system-ui, -apple-system, sans-serif/g; s/fill="#FFFFFF"/fill="#11141A"/g' "$svg"
done

echo "Hotovo."
