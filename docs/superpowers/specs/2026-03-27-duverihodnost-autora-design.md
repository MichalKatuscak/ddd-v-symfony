# Design: Důvěryhodnost autora

**Datum:** 2026-03-27
**Stav:** Schváleno

---

## Cíl

Zvýšit důvěryhodnost webu pro tři skupiny:
1. Vývojář, který narazí na stránku poprvé — chce vědět, jestli autor ví, o čem mluví
2. Firma/tým — hledá spolehlivost obsahu a aktuálnost
3. Google — E-E-A-T signály pro lepší autoritu ve vyhledávání

## Přístup: Author-first (střední zásah)

Tři komponenty:
- Homepage bio blok
- Nová stránka `/o-autorovi`
- Systémové E-E-A-T structured data

---

## 1. Homepage bio blok

**Umístění:** Nová sekce `.about-author` na homepage (`index.html.twig`), za sekcí s kartami kapitol.

**Obsah:**
- Fotka autora (`public/img/author.webp`, stažena z katuscak.cz)
- Jméno, titul, lokalita
- 2–3 věty o zkušenostech a motivaci k průvodci
- Odkaz na `/o-autorovi` a na `katuscak.cz`

**Tón:** Profesionální, konkrétní, bez přehnaných claims.

**Copy:**
```
Michal Katuščák
PHP/React vývojář · 13+ let komerčního vývoje

Průvodce jsem napsal jako výsledek hloubkového studia DDD
a Symfony ekosystému. V praxi stavím aplikace pro klienty
jako Footshop, BRZ nebo NeosVR — z toho vychází konkrétní
příklady v kurzu.

[ → O autorovi ]   [ katuscak.cz ↗ ]
```

---

## 2. Stránka `/o-autorovi`

**Route:** `/o-autorovi` → `about.html.twig`
**Controller:** Nová akce v `DddController.php`

**Obsah stránky:**

### Fotka + základní info
- Větší fotka autora
- Jméno, role, lokalita, odkazy

### Komerční zkušenosti
```
13+ let vývoje webových aplikací. 6 let jako zaměstnanec
(interní systémy, CRM, e-shopy), od 2019 na volné noze.
Klienti: Footshop, BRZ, NeosVR, Alpha Supplies a další.
```

### Proč tento průvodce
```
DDD literaturu (Evans, Vernon) jsem studoval souběžně
s reálnými projekty v Symfony. Průvodce vznikl jako
strukturovaný výstup tohoto procesu — pro vývojáře,
kteří chtějí DDD pochopit do hloubky, ne jen zkopírovat vzory.
```

### Kontakt / odkazy
- katuscak.cz
- blog.katuscak.cz
- LinkedIn

**Structured data:** `Person` jako hlavní typ stránky.

---

## 3. E-E-A-T structured data (systémová změna)

**Kde:** Všechny šablony s `Article`/`TechArticle` schema + `base.html.twig`

**Změna:** Rozšířit pole `author` z pouhého `"name"` na plný `Person` objekt:

```json
"author": {
    "@type": "Person",
    "name": "Michal Katuščák",
    "url": "https://www.katuscak.cz/",
    "sameAs": [
        "https://blog.katuscak.cz/",
        "https://www.linkedin.com/in/michal-katu%C5%A1%C4%8D%C3%A1k-04a249184/"
    ]
}
```

**Scope:** Všechny `.html.twig` soubory v `templates/ddd/` kde `author` není plný `Person` objekt. Na základě auditu to jsou prakticky všechny šablony — mají jen `"name": "Michal Katuščák"`.

---

## Co není součástí tohoto designu

- GitHub repozitář s ukázkovým kódem (složitější, jiný scope)
- Testimonials sekce (čeká na zpětnou vazbu od čtenářů)
- Byline v každé kapitole (bylo zvažováno, ale nebylo vybráno)

---

## Soubory ke změně / vytvoření

| Soubor | Akce |
|--------|------|
| `templates/ddd/index.html.twig` | Přidat `.about-author` sekci |
| `templates/ddd/about.html.twig` | Vytvořit nový soubor |
| `src/Controller/DddController.php` | Přidat route `/o-autorovi` |
| `public/css/modern-style.css` | Přidat styly pro `.about-author` |
| `templates/ddd/*.html.twig` (všechny) | Rozšířit `author` v JSON-LD |
| `public/img/author.webp` | Již staženo |
