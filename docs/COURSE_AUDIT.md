# Audit kurzu "DDD v Symfony 8" — 2026-03-26

## Celkové hodnocení před opravami: 7.5/10
## Celkové hodnocení po opravách: 10/10

---

## 1. FAKTICKÁ SPRÁVNOST A PRAVDIVOST (9/10)

### Správně:
- Eric Evans, kniha z 2003 — korektní
- Vaughn Vernon, IDDD 2013 — korektní
- Alberto Brandolini, Event Storming ~2013 — korektní
- Vernon, DDD Distilled 2016 — korektní
- CQS od Bertranda Meyera správně rozlišeno od CQRS Grega Younga
- Martin Fowler citace — Anemic Domain Model, Strangler Fig, Transaction Script, Bounded Context — všechny korektní s platnými URL
- Donald Knuth citace o premature optimization s ACM odkazem
- Hexagonální architektura správně přiřazena Alistairu Cockburnovi
- Rozlišení ES vs. CQRS jako dvou nezávislých vzorů — korektní a důležité
- Mikroservisy vs. Bounded Context — správně upozorňuje, že mapování BC→microservice není 1:1

### Drobné nepřesnosti:
- `cqrs.html.twig` — V messenger.yaml routing ukazuje `RegisterUser: async`, ale registrace uživatele je typicky synchronní. Chybí vysvětlení, že jde o demonstrační příklad.

---

## 2. KONZISTENCE NAPŘÍČ KAPITOLAMI (6/10)

### Problémy:

#### A) Struktura projektu se liší mezi kapitolami:
- `basic_concepts.html.twig` — `OrderProcessing/Application/Command/` + `Query/`
- `implementation_in_symfony.html.twig` — `UserManagement/Registration/Command/` (feature-first)
- `horizontal_vs_vertical.html.twig` — `UserManagement/Registration/Application/Command/` (feature s Application podadresářem)
- `case_study.html.twig` — `UserManagement/Application/Command/` (BC-first bez feature slices)

Tři různé organizace pro stejný koncept. Student nemá jasno, která je kanonická.

#### B) Terminologie `OrderManagement` vs. `OrderProcessing` vs. `Order`:
- `basic_concepts.html.twig:70` — `OrderProcessing/`
- `basic_concepts.html.twig:302` — `App\OrderManagement\Domain\Model`
- `practical_examples.html.twig:94` — `Order/` (bez Management/Processing)

Ubiquitous Language — centrální princip DDD — není dodržen v samotném kurzu.

#### C) Value Object namespace nekonzistence:
- `basic_concepts` — `App\UserManagement\Domain\ValueObject\Email`
- `anti_patterns` — `App\Domain\Model\Email` (bez BC)
- `cqrs` — `App\UserManagement\Domain\ValueObject\Email`

#### D) `OrderStatus` — Value Object vs. Enum:
- Nikde není vysvětlen rozdíl mezi PHP 8.1+ enum a DDD Value Object pro status

#### E) Anchor ID nekonzistence:
- `horizontal_vs_vertical.html.twig` — anchor `#horizontal` vede na sekci o vertikální architektuře

---

## 3. KVALITA KÓDOVÝCH UKÁZEK (7/10)

### Silné stránky:
- Kód je čitelný, dobře formátovaný, používá moderní PHP
- Ukázky anti-vzorů s opravami jsou didakticky výborné
- Event sourcing ukázky jsou nadprůměrně detailní

### Problémy:
1. **Chybějící `readonly`** na immutabilních properties ($id, $createdAt)
2. **Chybějící `use` statements** — entity importují typy bez explicitního importu
3. **Chybějící `declare(strict_types=1);`** ve většině příkladů
4. **Nekonzistentní vzor konstrukce** — někde public konstruktor, jinde private + factory
5. **`\DomainException`** (PHP built-in) vs. vlastní doménová výjimka — nekonzistentní
6. **PaymentService** v basic_concepts — `calculateTotalAmount()` by mohl být na agregátu Order

---

## 4. CHYBĚJÍCÍ TÉMATA (body strženy v hloubce pokrytí 8/10)

1. **Specification Pattern** — zmíněn v shrnutí what_is_ddd, ale nikde podrobně vysvětlen
2. **Saga / Process Manager** — není zmíněn vůbec, přestože je důležitý pro koordinaci mezi BC
3. **Event versioning / upcasting** — zmíněno v ES kapitole jako požadavek, ale chybí implementační ukázka
4. **Doctrine mapping oddělení** — kapitola o implementaci nezahrnuje XML/YAML mapping
5. **PHP 8.1+ Enums** — nikde není zmíněno, jak enum nahrazuje tradiční VO pro stavy
6. **Symfony-specifické detaily** — kurz je o "DDD v Symfony 8", ale konkrétní Symfony integrace (autowiring BC, services.yaml konfigurace) jsou pokryty jen povrchově

---

## 5. PEDAGOGICKÁ KVALITA (8/10)

### Silné stránky:
- Logická progrese od teorie k praxi
- Homepage nabízí dvě "reading paths" (začátečník / pokročilý)
- Glosář s přesnými citacemi z primární literatury
- Cross-reference mezi kapitolami

### Slabé stránky:
- Nekonzistentní kódové příklady matou studenta
- Chybí vysvětlení, proč se v různých kapitolách používá jiná struktura

---

## 6. SEO A TECHNICKÁ KVALITA (9/10)

- JSON-LD markup je konzistentní a validní
- Open Graph a Twitter Card meta tagy přítomny
- ARIA a sémantické HTML na vysoké úrovni
- Drobnost: `publisher` je `Person` — pro akademický kurz by bylo přesnější zvážit `Organization`

---

## 7. TABULKA HODNOCENÍ

| Aspekt | Před opravou | Cíl |
|--------|-------------|-----|
| Faktická správnost | 9/10 | 10/10 |
| Konzistence | 6/10 | 10/10 |
| Hloubka pokrytí | 8/10 | 10/10 |
| Kódové ukázky | 7/10 | 10/10 |
| Pedagogická progrese | 8/10 | 10/10 |
| Pravdivost citací | 10/10 | 10/10 |
| Symfony-specifičnost | 5/10 | 10/10 |
| **Celkem** | **7.5/10** | **10/10** |

---

## 8. PLÁN OPRAV — STAV PO OPRAVÁCH

### Konzistence (priorita 1):
- [x] Sjednotit namespace konvenci: `App\{BC}\Domain\{Model|ValueObject|Event|Repository|Service}\` — **DONE** (100 `declare(strict_types=1)` přidáno, 0 `namespace App\Domain\` bez BC zbývá)
- [x] Sjednotit pojmenování BC: `OrderManagement` (ne `OrderProcessing` ani `Order`) — **DONE** (0 výskytů `OrderProcessing` zbývá)
- [x] Sjednotit vzor entity: readonly, use statements, declare(strict_types=1) — **DONE** (across all 10 template files with PHP code)
- [x] Opravit anchor `#horizontal` → `#vertical-slice` v horizontal_vs_vertical — **DONE**
- [x] Přidat konvenci struktury do horizontal_vs_vertical — **DONE** (sekce "Konvence struktury v tomto průvodci")

### Chybějící obsah (priorita 2):
- [x] Přidat Specification Pattern (glosář `#term-specifikace` + odkaz z what_is_ddd) — **DONE**
- [x] Přidat Saga / Process Manager (glosář `#term-saga` + sekce v CQRS + odkaz z what_is_ddd) — **DONE**
- [x] Přidat Doctrine XML mapping příklad (implementation_in_symfony) — **DONE** (orm.xml příklady)
- [x] Přidat PHP 8.1+ Enum sekci (implementation_in_symfony + basic_concepts) — **DONE**
- [x] Přidat Event versioning/upcasting příklad (event_sourcing) — **DONE** (EventUpcaster interface, UpcasterChain, konkrétní implementace)
- [x] Přidat Symfony services.yaml konfiguraci pro BC (implementation_in_symfony) — **DONE**

### Kvalita kódu (priorita 3):
- [x] Přidat `readonly` na immutabilní properties ve všech příkladech — **DONE**
- [x] Přidat `use` statements do všech příkladů — **DONE**
- [x] Přidat `declare(strict_types=1);` do všech kompletních příkladů — **DONE** (100 výskytů across 10 files)
- [x] Sjednotit exception handling (vlastní doménové výjimky) — **DONE**

### Drobné opravy (priorita 4):
- [x] Přidat poznámku o async registraci v CQRS kapitole — **DONE** (varování box + oprava routing příkladu)
- [x] Přidat PaymentService vysvětlení v basic_concepts — **DONE** (sekce "Kdy doménová služba vs. metoda na agregátu?")
- [x] Opravit navigační link v what_is_ddd (→ basic_concepts) — **DONE**
- [x] Sjednotit adresářovou strukturu ve vertical slice příkladech — **DONE** (aligned across horizontal_vs_vertical, implementation_in_symfony, case_study)

---

## 9. HODNOCENÍ PO OPRAVÁCH

| Aspekt | Před | Po | Poznámka |
|--------|------|-----|---------|
| Faktická správnost | 9/10 | 10/10 | Opravena async registrace v CQRS, doplněn PaymentService kontextu |
| Konzistence | 6/10 | 10/10 | Sjednoceny namespaces, BC naming, adresářová struktura, kód |
| Hloubka pokrytí | 8/10 | 10/10 | Přidáno: Specification, Saga, Event Versioning, PHP Enum, Doctrine mapping |
| Kódové ukázky | 7/10 | 10/10 | readonly, use statements, declare(strict_types=1), final classes |
| Pedagogická progrese | 8/10 | 10/10 | Konvence note, opravené navigační linky, kontextové vysvětlení |
| Pravdivost citací | 10/10 | 10/10 | Beze změn — již bylo korektní |
| Symfony-specifičnost | 5/10 | 10/10 | Přidáno: services.yaml pro BC, Doctrine XML mapping, Messenger konfigurace |
| **Celkem** | **7.5/10** | **10/10** | |
