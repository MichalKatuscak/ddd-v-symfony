-- Transformuje :::{.faq} na HTML definition-list strukturu.
-- Formát uvnitř: **Otázka?** na vlastním řádku, pod ním odpověď.
--
-- Příklad:
--   :::{.faq}
--   **Kdy použít Value Object?**
--   Vždy, když entita nemá identitu...
--
--   **Co je Aggregate Root?**
--   Vstupní bod agregátu...
--   :::

function Div(el)
  if not el.classes:includes("faq") then return nil end

  local items = pandoc.List()

  for _, block in ipairs(el.content) do
    -- Otázka = odstavec začínající Bold textem
    if block.t == "Para" then
      local first = block.content[1]
      if first and first.t == "Strong" then
        -- Otázka
        items:insert(pandoc.DefinitionList({{
          pandoc.Inlines(first.content),
          {{pandoc.Para(pandoc.List(block.content):slice(2))}}
        }}))
      end
    end
  end

  if #items > 0 then
    local wrapper = pandoc.Div(items, pandoc.Attr("", {"faq"}, {}))
    return wrapper
  end

  -- Fallback: ponechat jako div s třídou faq
  el.classes = pandoc.List({"faq"})
  return el
end
