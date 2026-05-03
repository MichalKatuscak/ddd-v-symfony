-- Obaluje callout fenced divy do typst `#block(...)` se světlým pozadím
-- a barevným levým pruhem podle typu. Tělo divu zůstává jako pandoc Blocks
-- a je vyrenderováno typst writerem normálně, takže nadpisy, bold, kód
-- a listy uvnitř callouta fungují.
--
-- Spárováno s preprocess.php (target=pdf), které pro `:::callout{type=…}`
-- emituje fenced div `::::::: callout-<typ>`.

local STYLES = {
  note      = {label = 'Poznámka',  stripe = '#2563eb', bg = '#eff6ff'},
  warning   = {label = 'Pozor',     stripe = '#d97706', bg = '#fff8e6'},
  tip       = {label = 'Tip',       stripe = '#059669', bg = '#ecfdf5'},
  important = {label = 'Důležité',  stripe = '#dc2626', bg = '#fef2f2'},
  pattern   = {label = 'Vzor',      stripe = '#7c3aed', bg = '#f5f3ff'},
  anti      = {label = 'Anti-vzor', stripe = '#dc2626', bg = '#fef2f2'},
}

local function pickType(classes)
  for _, cls in ipairs(classes) do
    -- Class má tvar `callout-<typ>` (warn už je v preprocess přemapovaný na warning)
    local typ = cls:match('^callout%-(.+)$')
    if typ and STYLES[typ] then
      return typ
    end
  end
  return nil
end

function Div(el)
  local typ = pickType(el.classes)
  if not typ then return nil end
  local style = STYLES[typ]

  -- Otevírací typst raw – `#block` s konfigurací, otevíráme content blok `[`.
  local open = pandoc.RawBlock('typst',
    '#block(' ..
      'fill: rgb("' .. style.bg .. '"), ' ..
      'stroke: (left: 4pt + rgb("' .. style.stripe .. '")), ' ..
      'inset: 12pt, ' ..
      'radius: 3pt, ' ..
      'width: 100%, ' ..
      'breakable: true, ' ..
      '[#text(weight: "bold", fill: rgb("' .. style.stripe .. '"))[' ..
        style.label ..
      ']\n\n')

  -- Uzavírací typst raw – konec `[...]` argumentu a celé funkce.
  local close = pandoc.RawBlock('typst', '])')

  -- Sekvence pre + body + post: pandoc vyrenderuje body uvnitř naší typst
  -- raw obálky. Body může obsahovat libovolné Blocks včetně vnořených Divů
  -- (rekurzi řeší pandoc tím, že na každý Div sám zavolá tento filter).
  local out = {open}
  for _, b in ipairs(el.content) do
    table.insert(out, b)
  end
  table.insert(out, close)
  return out
end
