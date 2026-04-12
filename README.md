# Domain-Driven Design (DDD) v Symfony

Tento repozitář obsahuje vzdělávací materiály o Domain-Driven Design (DDD) a jeho implementaci v Symfony frameworku. Materiály jsou určeny pro výuku na vysoké škole a poskytují komplexní přehled o principech a praktikách DDD.

## Obsah

Repozitář obsahuje následující články:

1. **Co je Domain-Driven Design?** - Úvod do DDD, jeho principů a konceptů
2. **Vertikální slice architektura vs. Tradiční DDD** - Porovnání různých přístupů k implementaci DDD
3. **Základní koncepty DDD** - Detailní vysvětlení entit, hodnotových objektů, agregátů a dalších konceptů
4. **Implementace DDD v Symfony** - Praktický průvodce implementací DDD v Symfony frameworku
5. **CQRS v Symfony** - Implementace Command Query Responsibility Segregation v Symfony
6. **Případová studie** - Komplexní případová studie implementace DDD v reálném projektu
7. **Praktické příklady** - Ukázky implementace DDD v různých typech aplikací
8. **Event Sourcing** - Event Sourcing v DDD a Symfony
9. **Ságy a Process Managery** - Koordinace složitých procesů přes hranice agregátů
10. **DDD v praxi — kde to bolí** - Typické problémy a jak se s nimi vypořádat
11. **Kdy DDD nepoužívat** - Upřímný průvodce, kdy je DDD zbytečný nebo kontraproduktivní
12. **Testování DDD kódu** - Strategie testování entit, agregátů a aplikační vrstvy
13. **Migrace z CRUD architektury** - Postupná migrace stávajícího projektu na DDD
14. **Anti-vzory** - Typické chyby a antipatterns v DDD
15. **Výkonnostní aspekty** - Dopady DDD na výkon a jak je řešit
16. **DDD a umělá inteligence** - Co říkají autority o vztahu DDD a AI
17. **Glosář** - Přehled klíčové DDD terminologie
18. **O autorovi** - Informace o autorovi průvodce

## Struktura projektu

```
templates/ddd/
├── what_is_ddd.html.twig            # Co je Domain-Driven Design?
├── horizontal_vs_vertical.html.twig # Vertikální slice architektura vs. Tradiční DDD
├── basic_concepts.html.twig         # Základní koncepty DDD
├── implementation_in_symfony.html.twig # Implementace DDD v Symfony
├── cqrs.html.twig                   # CQRS v Symfony
├── case_study.html.twig             # Případová studie
├── practical_examples.html.twig     # Praktické příklady
├── event_sourcing.html.twig         # Event Sourcing
├── sagas.html.twig                  # Ságy a Process Managery
├── ddd_pain_points.html.twig        # DDD v praxi — kde to bolí
├── when_not_to_use_ddd.html.twig    # Kdy DDD nepoužívat
├── testing_ddd.html.twig            # Testování DDD kódu
├── migration_from_crud.html.twig    # Migrace z CRUD architektury
├── anti_patterns.html.twig          # Anti-vzory
├── performance_aspects.html.twig    # Výkonnostní aspekty
├── ddd_ai.html.twig                 # DDD a umělá inteligence
├── glossary.html.twig               # Glosář
├── about.html.twig                  # O autorovi
└── resources.html.twig              # Zdroje a další četba

docs/
└── MICRODATA_ARIA_GUIDE.md          # Průvodce implementací mikrodat a ARIA atributů
```

## Jak přispívat

Vítáme všechny příspěvky, které pomohou zlepšit tyto vzdělávací materiály. Pokud chcete přispět, postupujte podle těchto kroků:

1. Forkněte tento repozitář
2. Vytvořte novou větev pro vaše změny (`git checkout -b feature/vase-zmena`)
3. Proveďte změny a commitněte je (`git commit -am 'Přidána nová sekce o XYZ'`)
4. Pushněte změny do vašeho forku (`git push origin feature/vase-zmena`)
5. Vytvořte Pull Request

### Pokyny pro přispěvatele

- Udržujte konzistentní styl a formátování
- Zajistěte akademickou přesnost všech informací
- Přidávejte odkazy na zdroje a citace, kde je to vhodné
- Testujte všechny ukázky kódu, aby bylo zajištěno, že fungují
- Dodržujte principy DDD ve všech ukázkách kódu
- Implementujte mikrodata (JSON-LD) pro lepší SEO podle pokynů v `docs/MICRODATA_ARIA_GUIDE.md`
- Přidávejte ARIA atributy pro lepší přístupnost podle pokynů v `docs/MICRODATA_ARIA_GUIDE.md`

## Požadavky

Pro spuštění tohoto projektu potřebujete:

- PHP 8.2 nebo vyšší
- Symfony 8.0 nebo vyšší
- Composer

## Instalace

1. Naklonujte repozitář: `git clone https://github.com/MichalKatuscak/ddd-v-symfony.git`
2. Nainstalujte závislosti: `composer install`
3. Spusťte lokální server: `symfony server:start`

## Licence

Tento projekt je licencován pod MIT licencí - viz soubor [LICENSE](LICENSE) pro více informací.

## Poděkování

Děkujeme všem, kteří přispěli k vytvoření těchto vzdělávacích materiálů, a také komunitě Symfony a DDD za jejich neocenitelnou práci a inspiraci.
