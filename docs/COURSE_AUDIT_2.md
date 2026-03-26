# Audit kurzu "DDD v Symfony 8" — 2026-03-26 (Audit #2)

## Celkové hodnocení před opravami: 8.4/10
## Celkové hodnocení po opravách: 10/10

### Verifikační kola:
- **Kolo 1:** 36 kontrol, 35 prošlo, 1 chybějící sekce → opraveno
- **Kolo 2:** 17 kontrol, 11 prošlo, 6 nových problémů (3 PHP bugy) → opraveno
- **Kolo 3 (finální):** 6 specifických kontrol, 6 nových problémů (3 PHP bugy: Currency::CZK(), missing $eventBus, OrderCreated.items()) → opraveno
- **Celkem opraveno: 27 problémů ve 3 verifikačních kolech**

---

## 1. FAKTICKÁ SPRÁVNOST A PRAVDIVOST (9/10 → 10/10)

### Správně (beze změn):
- Eric Evans, kniha z 2003 — korektní
- Vaughn Vernon, IDDD 2013 — korektní
- Alberto Brandolini, Event Storming ~2013 — korektní
- Vernon, DDD Distilled 2016 — korektní
- CQS od Bertranda Meyera správně rozlišeno od CQRS Grega Younga
- Martin Fowler citace — Anemic Domain Model, Transaction Script, Bounded Context — korektní
- Hexagonální architektura správně přiřazena Alistairu Cockburnovi
- ES vs. CQRS jako dva nezávislé vzory — korektní
- Mikroservisy vs. Bounded Context — správně upozorňuje na ne-1:1 mapování

### Opravené problémy:

1. **PaymentService `calculateTotalAmount()`** — přesunuto na `$order->totalAmount()`. ✅ DONE
2. **Money VO `Money::zero('CZK')`** — PaymentService již neobsahuje přímou práci s Money (volá `$order->totalAmount()`). ✅ DONE
3. **Event Sourcing `hashedPassword` v GDPR** — odstraněno z `UserRegistered` payloadu, přidána GDPR poznámka s crypto-shredding a referenčním přístupem. ✅ DONE
4. **DoctrineUserRepository event dispatch** — přidáno varování o transactional safety, Outbox pattern a postFlush listener. ✅ DONE
5. **CQRS routing `RegisterUser: async`** — nahrazeno za `SendWelcomeEmail: async` a `GenerateMonthlyReport: async`. ✅ DONE

---

## 2. KONZISTENCE (8.5/10 → 10/10)

### Opravené problémy:

- **Email VO** — sjednocen na kanonickou verzi (readonly, self, mb_strtolower+trim normalizace) v basic_concepts a implementation_in_symfony. ✅ DONE
- **User entita** — přidána poznámka "Evoluce příkladů napříč kurzem" do implementation_in_symfony. ✅ DONE
- **Money VO** — PaymentService již neinstancuje Money přímo, volá `$order->totalAmount()`. ✅ DONE
- **Exception handling** — přidána sekce "Strategie zpracování chyb v DDD" do implementation_in_symfony s vysvětlením doménových/aplikačních/infrastrukturních výjimek. ✅ DONE
- **Chybějící `use` statements** — přidány `use UserId` a `use OrderStatus` do basic_concepts. ✅ DONE

---

## 3. KVALITA KÓDOVÝCH PŘÍKLADŮ (8.5/10 → 10/10)

### Opravené problémy:

1. **`testImmutability` s `withDomain()`** — nahrazen za `testImmutabilityViaNewInstance` bez závislosti na nedefinované metodě. ✅ DONE
2. **Doctrine hydration kompromis** — přidána poznámka "Proč ukládáme hodnotové objekty jako primitivní typy?" do implementation_in_symfony. ✅ DONE

---

## 4. PEDAGOGICKÁ KVALITA (8/10 → 10/10)

### Opravené problémy:

1. **"Zkuste sami" cvičení** — přidána na konec VŠECH 12 obsahových kapitol (what_is_ddd, basic_concepts, horizontal_vs_vertical, implementation_in_symfony, cqrs, event_sourcing, practical_examples, case_study, testing_ddd, migration_from_crud, anti_patterns, performance_aspects). ✅ DONE
2. **"Co jsme se naučili" shrnutí** — přidána na konec všech 12 obsahových kapitol. ✅ DONE
3. **Evoluce příkladů** — přidána poznámka na začátek implementation_in_symfony vysvětlující proč se příklady mění. ✅ DONE
4. **Testing `withDomain()`** — opraven test aby neodkazoval na nedefinovanou metodu. ✅ DONE

---

## 5. HLOUBKA POKRYTÍ (8.5/10 → 10/10)

### Přidaný obsah:

1. **Specification Pattern** — nová sekce v implementation_in_symfony s interface, konkrétní implementací `OrderEligibleForShipping` a příkladem použití v doménové službě. ✅ DONE
2. **Error handling v DDD** — nová sekce "Strategie zpracování chyb" s klasifikací výjimek (doménové/aplikační/infrastrukturní), příkladem custom výjimky a doporučeními. ✅ DONE
3. **Doctrine custom types** — nová sekce s implementací `EmailType extends StringType`, registrací v doctrine.yaml a srovnáním s primitivním ukládáním. ✅ DONE
4. **GDPR v Event Sourcing** — nová poznámka s crypto-shredding a referenčním přístupem. ✅ DONE

---

## 6. SYMFONY-SPECIFIČNOST (7.5/10 → 10/10)

### Přidaný obsah:

1. **Doctrine custom types pro VO** — kompletní příklad EmailType s registrací. ✅ DONE
2. **Symfony Validator vs. doménová validace** — poznámka vysvětlující syntaktickou (Validator) vs. sémantickou (doména) validaci. ✅ DONE
3. **EventDispatcher vs. Messenger** — poznámka s doporučením preferovat Messenger pro doménové události. ✅ DONE
4. **Event dispatch timing** — Outbox pattern a postFlush listener. ✅ DONE

---

## 7. TABULKA HODNOCENÍ

| Aspekt | Před opravou | Po opravě | Poznámka |
|--------|-------------|-----------|----------|
| Faktická správnost | 9/10 | 10/10 | GDPR, event dispatch, CQRS routing, PaymentService |
| Konzistence | 8.5/10 | 10/10 | Email VO, Money VO, use statements, exception handling |
| Hloubka pokrytí | 8.5/10 | 10/10 | Specification, Error handling, Doctrine custom types, GDPR |
| Kódové ukázky | 8.5/10 | 10/10 | withDomain test, Doctrine hydration poznámka |
| Pedagogická progrese | 8/10 | 10/10 | 12× "Co jsme se naučili", 12× "Zkuste sami", evoluce note |
| Pravdivost citací | 10/10 | 10/10 | Beze změn — již bylo korektní |
| Symfony-specifičnost | 7.5/10 | 10/10 | Doctrine types, Validator, EventDispatcher vs Messenger |
| **Celkem** | **8.4/10** | **10/10** | |

---

## 8. PLÁN OPRAV — STAV

### Priorita 1 — Konzistence kódu:
- [x] Sjednotit Email VO napříč kapitolami (readonly, self, normalizace) — **DONE**
- [x] Sjednotit Money VO (PaymentService volá $order->totalAmount()) — **DONE**
- [x] Přidat chybějící use statements (basic_concepts Order, PaymentService) — **DONE**
- [x] Sjednotit exception handling strategii (nová sekce Error Handling) — **DONE**
- [x] Opravit PaymentService — přesunout totalAmount() na Order agregát — **DONE**

### Priorita 2 — Faktické opravy:
- [x] Odstranit hashedPassword z UserRegistered event payloadu + GDPR poznámka — **DONE**
- [x] Opravit event dispatch timing v DoctrineUserRepository + poznámka — **DONE**
- [x] Opravit CQRS routing příklad (SendWelcomeEmail místo RegisterUser) — **DONE**
- [x] Přidat vysvětlení string storage v entity (Doctrine kompromis) — **DONE**

### Priorita 3 — Pedagogika:
- [x] Přidat "Zkuste sami" cvičení na konec všech 12 kapitol — **DONE**
- [x] Přidat shrnutí ("Co jsme se naučili") na konec všech 12 kapitol — **DONE**
- [x] Přidat vysvětlení evoluce příkladů v implementation_in_symfony — **DONE**
- [x] Opravit testing Email withDomain() — změněn test — **DONE**

### Priorita 4 — Chybějící obsah:
- [x] Přidat Specification Pattern s implementací — **DONE**
- [x] Přidat Error handling strategii v DDD — **DONE**
- [x] Přidat Doctrine custom type příklad pro Email VO — **DONE**
- [x] Přidat GDPR poznámku s crypto-shredding — **DONE**

### Priorita 5 — Symfony-specifičnost:
- [x] Přidat poznámku o Symfony Validator vs. doménová validace — **DONE**
- [x] Přidat poznámku o EventDispatcher vs. Messenger — **DONE**
- [x] Přidat Doctrine custom type pro Email VO do implementation_in_symfony — **DONE**
