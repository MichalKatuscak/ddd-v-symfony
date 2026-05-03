-- Transformuje :::{.callout type="note"} na HTML div se správnými třídami.
-- Typy: note, warning, tip, important

local ICONS = {
  note      = "ℹ",
  warn      = "⚠",
  warning   = "⚠",
  tip       = "💡",
  important = "★",
  pattern   = "◆",
}

function Div(el)
  if not el.classes:includes("callout") then return nil end

  local ctype = el.attributes["type"] or "note"
  local icon  = ICONS[ctype] or "ℹ"

  -- Přidat ikonu jako první element obsahu
  local icon_span = pandoc.Span(
    {pandoc.Str(icon)},
    pandoc.Attr("", {"callout-icon"}, {})
  )
  local icon_para = pandoc.Para({icon_span})
  table.insert(el.content, 1, icon_para)

  -- Normalizovat warn → warning
  local css_type = ctype == "warn" and "warning" or ctype

  -- Nastavit třídy
  el.classes = pandoc.List({"callout", "callout-" .. css_type})
  el.attributes["type"] = nil

  return el
end
