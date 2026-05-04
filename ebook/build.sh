#!/usr/bin/env bash
set -euo pipefail

# ─── Kontrola závislostí ───────────────────────────────────────────────────
if ! command -v pandoc &>/dev/null; then
  echo "Pandoc není nainstalován. Spusťte: sudo apt install pandoc"
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
CONTENT_DIR="$ROOT_DIR/content/chapters"
OUTPUT_DIR="$SCRIPT_DIR/output"
TEMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TEMP_DIR"' EXIT

# Typst pro PDF hledá obrázky relativně k CWD pandocu, ne k resource-path.
# Cesty v preprocessed PDF souborech jsou tvaru `public/images/...` – musíme
# tedy stát v projektovém rootu.
cd "$ROOT_DIR"

# ─── Pořadí kapitol (dle Chapters.php) ────────────────────────────────────
CHAPTER_ORDER=(
  preface
  what_is_ddd
  subdomains
  context_mapping
  event_storming
  team_topologies
  basic_concepts
  aggregate_design
  lesser_known_patterns
  architectural_styles
  implementation_in_symfony
  authorization_in_ddd
  cqrs
  event_sourcing
  sagas
  outbox_pattern
  performance_aspects
  testing_ddd
  migration_from_crud
  microservices_and_ddd
  ddd_pain_points
  anti_patterns
  when_not_to_use_ddd
  practical_examples
  case_study
)

# ─── Preprocessing: převést interní syntaxi na Pandoc Markdown ────────────
# Preprocess vytváří dvě varianty – EPUB používá raw HTML pro vlastní stylování,
# PDF používá native pandoc syntax (image, fenced code) kvůli sazbě v typstu.
echo "Připravuji kapitoly (EPUB + PDF varianty)..."
PROCESSED_EPUB=()
PROCESSED_PDF=()
for slug in "${CHAPTER_ORDER[@]}"; do
  src="$CONTENT_DIR/${slug}.md"
  if [[ ! -f "$src" ]]; then
    echo "  ⚠  Chybí: ${slug}.md – přeskočeno"
    continue
  fi
  dst_epub="$TEMP_DIR/${slug}.epub.md"
  dst_pdf="$TEMP_DIR/${slug}.pdf.md"
  php "$SCRIPT_DIR/preprocess.php" --target=epub "$src" > "$dst_epub"
  php "$SCRIPT_DIR/preprocess.php" --target=pdf  "$src" > "$dst_pdf"
  PROCESSED_EPUB+=("$dst_epub")
  PROCESSED_PDF+=("$dst_pdf")
done

if [[ ${#PROCESSED_EPUB[@]} -eq 0 ]]; then
  echo "Žádné kapitoly nenalezeny."
  exit 1
fi

echo "Zpracováno ${#PROCESSED_EPUB[@]} kapitol."

# ─── EPUB ─────────────────────────────────────────────────────────────────
echo "Generuji EPUB..."
pandoc "${PROCESSED_EPUB[@]}" \
  --from "markdown+raw_html+markdown_in_html_blocks-tex_math_dollars" \
  --metadata-file="$SCRIPT_DIR/book.yaml" \
  --css="$SCRIPT_DIR/epub.css" \
  --toc \
  --toc-depth=2 \
  --split-level=1 \
  --highlight-style=tango \
  --resource-path="$ROOT_DIR/public" \
  -o "$OUTPUT_DIR/ddd-v-symfony.epub"

echo "✓ EPUB: ebook/output/ddd-v-symfony.epub"

# ─── PDF ──────────────────────────────────────────────────────────────────
# Preferujeme typst – umí SVG nativně, nepotřebuje LaTeX.

if command -v typst &>/dev/null; then
  echo "Generuji PDF (engine: typst)..."

  pandoc "${PROCESSED_PDF[@]}" \
    --from "markdown+raw_html+markdown_in_html_blocks-tex_math_dollars" \
    --metadata-file="$SCRIPT_DIR/book.yaml" \
    --pdf-engine=typst \
    --toc \
    --toc-depth=2 \
    --highlight-style=tango \
    --resource-path="$ROOT_DIR/public" \
    --wrap=none \
    --lua-filter="$SCRIPT_DIR/filters/pdf_internal_links.lua" \
    --lua-filter="$SCRIPT_DIR/filters/pdf_callout.lua" \
    -V mainfont="DejaVu Serif" \
    -V monofont="DejaVu Sans Mono" \
    -V sansfont="DejaVu Sans" \
    -o "$OUTPUT_DIR/ddd-v-symfony.pdf"

  echo "✓ PDF:  ebook/output/ddd-v-symfony.pdf"
else
  echo "⚠  PDF přeskočeno – typst nenalezen."
  echo "   Instalace: https://github.com/typst/typst/releases"
fi
