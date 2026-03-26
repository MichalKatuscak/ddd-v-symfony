# Design: ddd-symfony-examples — živé ukázky pro studenty

**Datum:** 2026-03-26
**Autor:** Michal Katuščák
**Status:** Schváleno

---

## Cíl

Vytvořit samostatný GitHub repozitář `ddd-symfony-examples` s plně funkčními, spustitelnými ukázkami kódu, které odpovídají kapitolám příručky [ddd-v-symfony](https://github.com/...). Student si repozitář naklonuje, spustí tři příkazy a má funkční demo v prohlížeči.

---

## Architektura

**Typ:** Modulární monorepo — jeden Symfony 8 projekt, kapitoly jako PHP namespacy.

**Proč ne jinak:**
- Micro-repozitáře: duplicitní infrastruktura, 17× setup
- Git větve/tagy: matoucí pro méně zkušené studenty, nelze mít více kapitol otevřených zároveň

---

## Struktura repozitáře

```
ddd-symfony-examples/
├── src/
│   ├── Chapter01_WhatIsDDD/
│   ├── Chapter03_BasicConcepts/
│   ├── Chapter04_Implementation/
│   ├── Chapter05_CQRS/
│   ├── Chapter06_EventSourcing/
│   ├── Chapter07_Sagas/
│   ├── Chapter08_Testing/
│   ├── Chapter09_Migration/
│   └── Shared/                    ← společná rozhraní (DomainEvent, ValueObject, etc.)
├── templates/examples/
│   └── {chapter}/index.html.twig
├── config/
├── migrations/
├── .env                           ← SQLite by default
├── docker-compose.yml             ← volitelné, pro PostgreSQL
├── Makefile
└── README.md
```

Kapitoly bez zajímavého kódu (Glosář, Zdroje, Bezpečnostní zásady) nejsou zahrnuty. Celkem cca 8–10 kapitol.

---

## Struktura každé kapitoly

```
ChapterXX_Topic/
├── Domain/
│   ├── {Aggregate}/
│   │   ├── {Aggregate}.php
│   │   ├── {AggregateId}.php      ← Value Object
│   │   └── {AggregateDomainEvent}.php
│   └── Repository/
│       └── {Aggregate}RepositoryInterface.php
├── Application/
│   ├── {Command}/
│   │   ├── {Action}Command.php
│   │   └── {Action}Handler.php
│   └── {Query}/
│       ├── {Get}Query.php
│       └── {Get}Handler.php
├── Infrastructure/
│   └── Persistence/
│       └── Doctrine{Aggregate}Repository.php
├── UI/
│   └── ExamplesController.php     ← route /examples/{chapter-slug}
└── README.md                      ← CZ popis + odkaz na článek v příručce
```

---

## Doména

- **E-shop** (Order, Product, Cart, Payment) — pro kapitoly CQRS, Event Sourcing, Ságy
- **Task manager** (Project, Task, Member) — pro kapitoly Testování, Migrace z CRUD

Shoduje se s doménami používanými v příručce (praktické příklady + případová studie).

---

## Technický stack

| Komponenta | Volba | Důvod |
|---|---|---|
| Framework | Symfony 8 | Shoduje se s příručkou |
| ORM | Doctrine + SQLite | Nulová konfigurace pro studenty |
| Messaging | Symfony Messenger | CQRS handlery, ságy |
| Events | Symfony EventDispatcher | Domain events |
| Testy | PHPUnit | Standardní v Symfony ekosystému |

---

## Obsah každé ukázky

Každá kapitola obsahuje:
1. **Doménové třídy** — plně funkční, s PHPDoc
2. **Spustitelné demo** — dostupné přes prohlížeč na `/examples/{slug}`
3. **Jednoduché HTML UI** — formulář → akce → výsledek (bez JavaScriptu)
4. **PHPUnit testy** doménových tříd
5. **README.md** s CZ popisem a odkazem na odpovídající článek v příručce

---

## Setup pro studenta

```bash
git clone https://github.com/...ddd-symfony-examples
composer install
symfony server:start
# Otevřít http://localhost:8000/examples
```

### Makefile

```bash
make install    # composer install + doctrine:database:create + migrations
make test       # phpunit --testdox
make reset      # smaže a znovu vytvoří SQLite DB
```

---

## Propojení s příručkou (ddd-v-symfony)

**V repozitáři ukázek:**
- Každá ukázka zobrazuje banner: *"Tato ukázka patří ke kapitole X → [odkaz]"*

**V příručce (ddd-v-symfony):**
- Ke každé relevantní kapitole přidat tlačítko/odkaz: *"Živá ukázka na GitHubu"*
- Odkaz vede přímo na příslušný adresář kapitoly v repozitáři

---

## Co není v scope

- Autentizace a autorizace
- API endpointy (REST/GraphQL)
- Frontend framework (Vue, React)
- CI/CD pipeline
- Deployment
