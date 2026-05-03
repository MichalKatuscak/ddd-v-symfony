# Revize příručky – feedback medior PHP/Symfony vývojáře

**Datum:** 2026-05-03
**Trigger:** Procházka příručky v prohlížeči (1440 px i 390 px) z perspektivy medior PHP/Symfony vývojáře.

## Klíčová rozhodnutí

- **Mapping:** atributy jako default napříč všemi 25 kapitolami. XML mapping zachovat jen jako historickou poznámku. Přidat sekci „Persisted Object Pattern" jako čistou DDD variantu pro puristy (Khononov, *Learning DDD*, kap. 11) – doména POPO + samostatná persistence model + mapper.
- **User entita v kap. 11:** kompletní přepis podle vzoru `Order` z kap. 7 (final, `extends AggregateRoot`, factory, VO pro `UserName`).
- **Postup:** sériově fáze 1 → 4, po každé fázi commit.

## Fáze 1 – UX bloker (rychlé, izolované)

| # | Téma | Soubor | Stav |
|---|---|---|---|
| 1 | `chapter_nav` grid layout: `grid-column: 1 / -1` | `assets/styles/article.css` | |
| 2 | Cheat sheet tabulky přetékají 700 px sloupec | `assets/styles/article.css` + `cheat_sheet.md` | |
| 3 | Sticky TOC neprosakuje přes článek a footer | `assets/styles/article.css` | |

## Fáze 2 – Faktické chyby v kódu

| # | Téma | Soubor | Stav |
|---|---|---|---|
| 4 | `services.yaml` alias syntaxe (`@...` místo `class:`) | `implementation_in_symfony.md` | |
| 5 | Race condition v `RegisterUserHandler` (DB unique + ACL) | `implementation_in_symfony.md` | |
| 6 | Outbox jako primární `repo.save()` příklad | `implementation_in_symfony.md` | |
| 7 | `PaymentService` invariant přesunout do agregátu `Order` | `implementation_in_symfony.md` | |

## Fáze 3 – Konzistence kapitoly 11

| # | Téma | Soubor | Stav |
|---|---|---|---|
| 8 | Mapping na atributy + Persisted Object sekce | `implementation_in_symfony.md` (+ aggregate_design pokud potřeba) | |
| 9 | User entita: final, AggregateRoot, factory, `UserName` VO | `implementation_in_symfony.md` | |
| 10 | Symfony idiomy: `MapRequestPayload`, `#[AsAlias]`, Voters, kernel testy | `implementation_in_symfony.md` | |
| 11 | EventDispatcher vs Messenger – nuance | `implementation_in_symfony.md` | |

## Fáze 4 – Didaktika a drobnosti

| # | Téma | Soubor | Stav |
|---|---|---|---|
| 12 | Homepage CTA – odstranit „rovnou na agregáty 07" | `templates/ddd/index.html.twig` | |
| 13 | Hub stránky – „proč tato sekce, kdo to čte" | `templates/ddd/hub_*.html.twig` | |
| 14 | Kap. 1 – eliminovat duplicitní bullet listy | `what_is_ddd.md` | |
| 15 | CQRS `doctrine_transaction` middleware vs „1 agregát = 1 transakce" | `cqrs.md` | |
| 16 | Email VO – limity `FILTER_VALIDATE_EMAIL` | `implementation_in_symfony.md` | |

## Faktické chyby zachycené během procházky (referenční)

1. **Rozpor mapping** – kap. 11 zakazuje atributy, kap. 7 je používá.
2. **services.yaml** – `class:` místo aliasu vytvoří dvě instance.
3. **Race v `findByEmail` → `save`** – chybí DB unique constraint + překlad výjimky.
4. **Outbox** jako „v produkci použijte" callout – primární kód má být rovnou outbox.
5. **`User::email()` getter** rekonstruuje `Email` VO při každém volání → re-validace.
6. **`User::changeName(string)`** – přijímá string, ne VO.
7. **`PaymentService::processPayment`** – invariant patří do agregátu `Order`.
8. **`art-nav` grid layout** – vykreslení do TOC sloupce, překryv.
9. **Cheat sheet tabulky** – `scrollWidth` 791–1259 px ve sloupci 698 px.
10. **CQRS** – `doctrine_transaction` middleware obalí celý handler do transakce, koliduje s pravidlem 1 agregát = 1 transakce z kap. 7.
11. **CTA „agregáty 07"** – difficulty 4/4 jako zkratka pro nováčka, vede k přeskočení Bounded Contexts a Event Stormingu.
