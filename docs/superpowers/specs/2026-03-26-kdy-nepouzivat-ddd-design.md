# Design: Kdy DDD nepoužívat — upřímně

**Datum:** 2026-03-26
**Typ:** Nová standalone stránka

## Shrnutí

Přidat novou stránku „Kdy DDD nepoužívat" jako samostatnou kapitolu průvodce. Stránka bude psaná tvrdě a upřímně — bez omluv a bez salámování. Cíl: budovat důvěru čtenáře tím, že průvodce přizná limity DDD místo aby ho prodával za každou cenu.

## Umístění v projektu

- **Route:** `/kdy-nepouzivat-ddd`
- **Route name:** `when_not_to_use_ddd`
- **Template:** `templates/ddd/when_not_to_use_ddd.html.twig`
- **Controller:** nová akce `whenNotToUseDdd()` v `DddController.php`
- **Index:** přidat kartu do feature gridu na `index.html.twig`
- **Navigace:** přidat odkaz do nav menu v `base.html.twig`

## Struktura obsahu

### Úvod (3–4 věty)
Přímé přiznání: DDD není vhodné pro každý projekt. Špatná aplikace DDD stojí čas, peníze a morálku týmu. Tato stránka říká, kdy DDD vynechat.

### Hlavní sekce: 7 situací kdy DDD nepoužívat

Každá situace obsahuje:
- Nadpis situace
- Vysvětlení proč DDD nepadí
- Konkrétní alternativa s odůvodněním

| # | Situace | Alternativa |
|---|---------|-------------|
| 1 | CRUD admin / jednoduchý backoffice | EasyAdmin, Symfony forms |
| 2 | Startup — doména se mění každý sprint | Flat MVC, rychlé iterace |
| 3 | Tým 1–2 lidi bez doménových expertů | Vrstvená architektura |
| 4 | Data pipeline / ETL / reporty | Service layer bez agregátů |
| 5 | Projekt s životností kratší než 1 rok | Prostý Symfony controller |
| 6 | Nikdo v týmu DDD nezná, čas na učení není | Počkej na správný projekt |
| 7 | Doména je nejasná, experti nejsou k dispozici | Event Storming napřed, kód potom |

### Závěr: Kdy DDD naopak smysl má
Krátká tabulka nebo seznam — aby stránka nebyla jen cynická, ale poskytla čtenáři orientaci.

## SEO & metadata

- **Meta description:** Kdy DDD opravdu nepoužívat — upřímný průvodce pro PHP vývojáře. 7 konkrétních situací s alternativami.
- **Keywords:** kdy nepoužívat DDD, DDD nevhodné, DDD alternativy, DDD limity
- JSON-LD: `TechArticle`, breadcrumb
- OG/Twitter tagy

## Tón

Přímý, bez zjemňování. Každý bod začíná tvrzením, ne otázkou. Žádné „možná", „záleží na kontextu" bez konkrétního obsahu. Alternativy jsou konkrétní, ne vágní.
