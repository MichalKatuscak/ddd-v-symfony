-- Transformuje :::{.diagram fig="02.1" title="..." src="images/..."} na figure element.
-- SVG soubory jsou v public/ (resource-path=public/).

function Div(el)
  if not el.classes:includes("diagram") then return nil end

  local src   = el.attributes["src"]   or ""
  local title = el.attributes["title"] or ""
  local fig   = el.attributes["fig"]   or ""

  if src == "" then
    io.stderr:write("WARN: diagram bez src atributu (fig=" .. fig .. ")\n")
    return nil
  end

  local caption_text = title
  if fig ~= "" then
    caption_text = "Diagram " .. fig .. ": " .. title
  end

  local img = pandoc.Image(
    pandoc.Inlines({pandoc.Str(title)}),
    src,
    title
  )

  local caption_para = pandoc.Para(
    pandoc.Inlines({pandoc.Emph(pandoc.Inlines({pandoc.Str(caption_text)}))})
  )

  local fig_id = "fig-" .. fig:gsub("%.", "-")

  return pandoc.Div(
    pandoc.Blocks({pandoc.Para(pandoc.Inlines({img})), caption_para}),
    pandoc.Attr(fig_id, {"diagram-figure"}, {})
  )
end
