-- Pro PDF (typst) odstraňujeme interní cross-referenční odkazy a explicitní
-- ID nadpisů. Důvody:
--   1. Stejný anchor (např. #summary, #further-reading) se opakuje napříč
--      kapitolami a typst odmítá duplicitní labely.
--   2. Některé interní odkazy v textu míří na anchor, který neexistuje
--      (typo nebo přejmenovaná sekce). Na webu se to zobrazí jako mrtvý
--      odkaz, ale typst kompilaci shodí.
-- V PDF jako sekvenčním médiu interní odkazy stejně nejsou kritické.

function Header(el)
  el.identifier = ''
  el.attr = pandoc.Attr('', el.classes, {})
  return el
end

function Link(el)
  local target = el.target or ''
  if target:sub(1, 1) == '#' then
    -- Zachovej viditelný text, zahoď cíl.
    if #el.content == 0 then
      return pandoc.Str('')
    end
    return pandoc.Emph(el.content)
  end
  return el
end

-- Span s ID se může vyskytovat pro kotvy v textu – odstraníme ID, ať
-- nevznikají duplicitní labely.
function Span(el)
  if el.identifier ~= '' then
    el.identifier = ''
  end
  return el
end

-- Div s ID – stejný důvod.
function Div(el)
  if el.identifier ~= '' then
    el.identifier = ''
  end
  return el
end
