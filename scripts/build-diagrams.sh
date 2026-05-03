#!/usr/bin/env bash
# Renderuje všechny .puml/.uml z templates/diagrams/ do public/images/diagrams/ jako SVG.
# Po renderu nahradí font-family z DejaVu Sans na Inter (browser webfont).
#
# Single source of truth:
#   templates/diagrams/*.puml  – zdrojové soubory (verzované, editovatelné)
#   public/images/diagrams/*.svg – build artefakty (servíruje webserver)
#   templates/diagrams/theme.iuml – sdílená tmavá paleta (kopíruje se do public/)
#
# Pozn.: kapitoly odkazují diagramy přes src="images/diagrams/<dir>/<file>.svg",
# což je relativní cesta ke `public/images/diagrams/`.

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC_DIR="$ROOT/templates/diagrams"
OUT_DIR="$ROOT/public/images/diagrams"

cd "$ROOT"

# 0) Připravit výstupní adresář a synchronizovat strukturu zdrojových adresářů.
mkdir -p "$OUT_DIR"
# Kopírovat theme.iuml do public/ (PlantUML !include vyžaduje relativní cestu k theme).
cp "$SRC_DIR/theme.iuml" "$OUT_DIR/theme.iuml"

# 1) Najdi všechny zdrojové soubory (kromě theme.iuml).
mapfile -t SOURCES < <(find "$SRC_DIR" \( -name "*.puml" -o -name "*.uml" \) ! -name "theme.iuml" | sort)

echo "Renderuji ${#SOURCES[@]} diagramů z $SRC_DIR → $OUT_DIR…"

# 2) Renderuj každý do odpovídajícího adresáře v public/.
for src in "${SOURCES[@]}"; do
    rel_dir="${src#"$SRC_DIR/"}"
    rel_dir="$(dirname "$rel_dir")"
    out_subdir="$OUT_DIR/$rel_dir"
    mkdir -p "$out_subdir"
    echo "  • $src → $out_subdir"
    plantuml -charset UTF-8 -tsvg -o "$out_subdir" "$src"
done

# 3) Post-process: nahraď font-family DejaVu Sans → Inter v každém SVG.
#    PlantUML používá DejaVu pro layout (jediný spolehlivý systémový font s českými glyphy),
#    ale browser by měl zobrazit Inter (loaded webfont).
echo "Post-processuji SVG (font, bílé výplně activity stop kruhů)…"
find "$OUT_DIR" -name "*.svg" -print0 | while IFS= read -r -d '' svg; do
    sed -i 's/font-family="DejaVu Sans"/font-family="Inter, ui-sans-serif, system-ui, -apple-system, sans-serif"/g; s/font-family:DejaVu Sans/font-family:Inter, ui-sans-serif, system-ui, -apple-system, sans-serif/g; s/fill="#FFFFFF"/fill="#11141A"/g' "$svg"
done

echo "Hotovo."
