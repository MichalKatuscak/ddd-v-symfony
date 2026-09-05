# Stav prací na studiích + jak navázat

Poslední aktualizace: 2026-09-04 — **studie hotové, ověřování uzavřeno, všech 26 kapitol zrevidováno**

## Co to je

Ke každé z 26 kapitol vzniká jedna deep-research studie v `docs/studie/<kapitola>-studie.md`.
Studie je podklad pro budoucí přepis kapitoly, ne návrh textu. Formát řídí `docs/studie/_SABLONA.md`.

Studie **nesahají na `content/`**. Soubory nesmí ležet v `content/chapters/` — `ChapterRouteLoader`
tam dělá `glob('*.md')` a čte z každého souboru frontmatter `path`/`route`, takže by aplikace spadla.

## Stav: 26 z 26 hotovo

Ke každé kapitole existuje studie. Celkem 10 199 řádků, zhruba 660 nálezů v gap analýzách
a 136 doporučení priority P1. Kontrola úplnosti:

```bash
for f in content/chapters/*.md; do n=$(basename $f .md); \
  [ -f "docs/studie/$n-studie.md" ] || echo "CHYBI: $n"; done
```

| Kapitola | Řádků | Nálezů | P1 |
|---|---:|---:|---:|
| preface | 421 | 22 | 6 |
| what_is_ddd | 287 | 26 | 5 |
| subdomains | 433 | 22 | 5 |
| context_mapping | 272 | 22 | 7 |
| event_storming | 450 | 30 | 5 |
| team_topologies | 309 | 28 | 5 |
| basic_concepts | 429 | 25 | 6 |
| aggregate_design | 258 | 24 | 6 |
| lesser_known_patterns | 405 | 24 | 5 |
| architectural_styles | 422 | 21 | 5 |
| implementation_in_symfony | 403 | 21 | 4 |
| authorization_in_ddd | 259 | 25 | 6 |
| cqrs | 450 | 22 | 6 |
| event_sourcing | 439 | 21 | 4 |
| sagas | 441 | 24 | 4 |
| outbox_pattern | 427 | 20 | 4 |
| performance_aspects | 250 | 44 | 5 |
| testing_ddd | 448 | 20 | 5 |
| migration_from_crud | 449 | 25 | 7 |
| microservices_and_ddd | 507 | 26 | 7 |
| ddd_pain_points | 444 | 34 | 4 |
| anti_patterns | 250 | 38 | 5 |
| when_not_to_use_ddd | 447 | 21 | 4 |
| practical_examples | 397 | 26 | 4 |
| case_study | 452 | 27 | 8 |
| ddd_ai | 450 | 23 | 4 |

## Doověřovací backlog – uzavřen 2026-09-04

První průchod studiemi vyčerpal rozpočet `WebSearch` (200 dotazů/session) zhruba u sedmé studie
a zbytek běžel na cíleném `WebFetch`. To stačilo na verifikaci API a dokumentace, ale ne na výrok,
u kterého neznáme URL. Limit je od té doby zvednutý přes
`CLAUDE_CODE_MAX_WEB_SEARCHES_PER_SESSION` v `~/.claude/settings.json`.

Druhý průchod s funkčním hledáním backlog vyřídil. Sedm z původních sedmi položek je rozhodnutých,
otevřená zůstává jedna dílčí otázka.

### Co se ověřilo

**1. `ddd_ai`, tabulka 2.6 – devět z jedenácti nedohledaných výroků rozhodnuto.** Nový poměr:
OK 13 · OK\* 12 · **NE 13** · ? 2. Dva výroky se potvrdily s posunem zdroje (A13, A21), sedm se
vyvrátilo (A17, A18, A22, A23, A28, A29, A35). Tři z nich nejsou jen nedoložené – dohledaný zdroj
tvrdí opak toho, co jim kapitola připisuje:

- **Beck (A17)** – kapitola tvrdí, že testuje méně věcí. Jeho esej *Augmented Coding: Beyond the
  Vibes* (25. 6. 2025) říká „In augmented coding you care about the code, its complexity, the tests,
  & their coverage“ a jeho systémový prompt trvá na plném TDD cyklu.
- **Tune (A28, A29)** – kapitola z něj dělá zastánce CLAUDE.md jako bounded-context dokumentu.
  Jeho článek z 13. 8. 2026 se jmenuje *Enforced application architecture for agents and humans*
  s podtitulem „Enforcing application architecture **instead of relying on markdown files**“.
  Doména z prvního průchodu (nicktune.uk) neexistuje, správná je **nick-tune.me**.

Doloženo bylo naopak: DHH o Ruby („conveys so much more concept per character“) z Lex Fridmanova
podcastu, ne z TNS; a myšlenka „rigorózní jazyk na vstupu → spolehlivější výstup“ z článku
*DSLs Enable Reliable Use of LLMs* (14. 7. 2026) – jehož autorem je ale **Unmesh Joshi**, ne Fowler.
Bonusem jsou čerstvá data GitClear za 2025 a 2026, která projekci z ledna 2024 nahrazují měřením,
a stav Symfony AI: `php-llm/llm-chain` je **abandoned** ve prospěch `symfony/ai-agent`, komponenty
Symfony AI jsou na v0.13.0 (30. 8. 2026), tedy aktivní, ale stále 0.x.

**2. Udi Dahan, *Sagas: not just for workflows* – článek toho jména neexistuje.** Titul koluje
komunitou jako zkomolenina. Myšlenku nese *No more workflow for nServiceBus – please welcome the
Saga* (17. 12. 2007), který je živý a citovatelný. Kapitola 14 přitom Dahana vůbec necituje, takže
není co opravovat – nález je vstup pro přepis.

**3. Greg Young, „CQRS is not an architecture“ – dohledáno.** První průchod hledal na
`gregyoung.wordpress.com`; správná doména je **`gregfyoung`** s „f“. Text z 9. 9. 2012 je živý.
**Zároveň z toho plyne nová faktická chyba v kapitole 12:** `cqrs.md:57` tvrdí, že Young povýšil
CQRS na „rozhodnutí o struktuře celé aplikace“, tabulka pod tím uvádí úroveň „Architektura celé
aplikace“. Young říká pravý opak – CQRS „describe something inside a single system or component“
a architekturou není, je architektonický vzor. FAQ na ř. 1541 to má správně.

**4. Nat Pryce, Test Data Builder – částečně.** Doména natpryce.com odmítá spojení i podruhé.
Existence, autorství, název a **rok 2007** jsou ale doložené nezávisle přes Marka Seemanna
(blog.ploeh.dk, 2017). Pro citaci použít knihu *GOOS* (2009).

**5. Newman, *Building Microservices* 2. vyd. – obsah ověřen** proti autorovu rozpisu na
samnewman.io. Dvě čísla kapitol v knize nesedí: `microservices_and_ddd.md:870` odkazuje na kap. 4
(*Microservice Communication Styles*) kvůli velikosti service – patří to do kap. 2 (*How to Model
Microservices*); `:893` uvádí „kapitola 14 pro migraci“, kap. 14 jsou ale *User Interfaces*, migrace
je kap. 3. Odkazy na ř. 32, 60 a 98 sedí.

**6. Vernon, *IDDD*, kap. 14 – částečně.** Kapitola se jmenuje *Application* a autorizaci skutečně
obsahuje: Application Services u něj prosazují bezpečnostní oprávnění. Výhrada je k formulaci –
`authorization_in_ddd.md:124` slibuje „vrstvení“ autorizace, Vernon ji přitom umisťuje do jediné
vrstvy.

**7. Shared Kernel, `context_mapping.md:121` – ověřeno proti plnému textu DDD Reference.**
Formulace „menší než přirozený průnik“ tam není; Evans má jen „Keep this kernel small“. Navíc
kapitola vynechává druhou půlku vzoru: continuous integration proces, který má kernel držet těsný
a sladit ubiquitous language obou týmů. Atribuci připsat Reference (2015), ne knize z 2003.

### Co zůstává otevřené

**Alberto Brandolini k AI a EventStormingu** (výrok A38). Vlastní vyjádření se nedohledalo ani
podruhé. Nepřímý doklad je anotace jeho workshopu *AI-Powered Domain-Driven Design* (Berlín,
1.–4. 12. 2026): „combine the power of modern AI tools where they have the most impact, while still
maximizing learning through hands-on activities“. To je pozice kombinace, ne obrany lidské
exkluzivity – tedy jiná, než jakou mu kapitola připisuje. Doporučení studie: citovat anotaci
workshopu a nedoplňovat vlastní odhad.

Detaily a citovatelná znění jsou v sekci 9 příslušných studií; u `ddd_ai` navíc v přepsané sekci 8.

## Druhé kolo ověřování (2026-09-04)

Po uzavření backlogu vyšlo najevo, že těch sedm položek byl výběr, ne úplný seznam. V sekcích
„Neověřené / nedohledané“ napříč 26 studiemi bylo **165 položek**. Rozpadají se na technické
(verze, URL, chování knihoven – vyhledáním řešitelné), knižní (za paywallem nebo jen v tisku)
a na data, která neexistují.

### Pět studií vzniklo bez vyhledávání

`case_study`, `event_sourcing`, `practical_examples`, `when_not_to_use_ddd` a částečně
`subdomains` samy v poznámce k metodě hlásí vyčerpaný rozpočet `WebSearch` (200/200). U nich
nechyběly jen doklady, ale i nálezy – gap analýza se dělala naslepo. Všech pět je doladěných.
Co se našlo:

- **`subdomains.md:436–439` je rozbitá ukázka.** Auth0 PHP SDK v8 má signaturu
  `get(string $id, ?RequestOptions $options = null): ResponseInterface`; metoda **null vrátit
  nemůže**. Podmínka `if ($profile === null) { throw new UserNotFoundException(); }` proto nikdy
  neplatí a neexistujícího uživatele ukázka zamlčí. Chyba kódu, ne stylu.
- **`event_sourcing`: tři ze čtyř verzí Symfony byly špatně.** `use_notify` je od 7.1,
  `#[AsMessage]` od 7.2, `keepalive` od 7.3; jen `--fetch-size` je z 8.1. První průchod je četl
  z dokumentace `current` a všechny označil za novinky 8.1. Kdyby kapitola vznikla podle té studie,
  nesla by čtyři chybné verze.
- **Broadway je archivovaná** (Packagist `abandoned`, repozitář archived), zatímco kapitola píše
  „funguje, ale tempo vývoje zvolnilo“. Naopak tvrzení o proophu je v pořádku – řada 7.x žije
  (v Packagist feedu ji zakrývá starší `v8.0.0-RC-1` z roku 2019).
- **Kurrent**: přejmenování z Event Store oznámeno **24. 12. 2024**, EventStoreDB → KurrentDB.
- **ORM `enableNativeLazyObjects()` je z 3.4.0** (14. 6. 2025), ne z 3.5; `UPGRADE.md` mluví
  o 3.5 proto, že tam popisuje deprecation starého režimu.
- **`practical_examples`**: klíč `github_examples` v kapitole nikdy nebyl (`git log -S` nic
  nevrací) a diagramy nezůstaly neaktualizované záměrně – od obsahového commitu 27. 4. 2025 se
  jich dotkly jen dva technické commity, zatímco text prošel deseti obsahovými.

### Doctrine: průřezová chyba doložena

Nejzávažnější nález druhého kola. Ověřeno proti `UPGRADE-3.0.md` a `Configuration.php` ve větvi
3.3.x `doctrine/DoctrineBundle`:

- `enable_lazy_ghost_objects`, `auto_generate_proxy_classes`, `proxy_dir` a `proxy_namespace`
  jsou v **DoctrineBundle 3 odstraněné**. YAML ukázka v `aggregate_design.md:668–669` dvě z nich
  nastavuje, takže na Symfony 8 neprojde kompilací kontejneru.
- `enable_native_lazy_objects` má `defaultTrue()` a **nelze vypnout** (validace `thenInvalid`);
  od 3.1 je deprecated, protože „native lazy objects are now always enabled“. Nativní lazy objekty
  nedědí z entity, takže **entita mapovaná Doctrine `final` být může**.
- Tvrzení „ne final – Doctrine proxy z entity dědí“ stojí v **šesti kapitolách**:
  `aggregate_design` (599–610, 668–669), `case_study` (369–370, 391), `implementation_in_symfony`
  (315), `migration_from_crud` (369), `performance_aspects` (112, 576), `practical_examples` (268).

Zpřesněny i dvě sousední věci: `PARTIAL` nebyl odstraněn natrvalo – v ORM 3.0 zmizel a **v 3.2 se
vrátil pro array hydration** (ověřeno v kódu 3.6.x), takže rozpor mezi `UPGRADE.md` a stránkou
`partial-objects.html` je zdánlivý. `EXTRA_LAZY` platí beze změny. A `ddd_pain_points.md:238`
tvrdí, že `fetch: 'EAGER'` na kolekci „načte v jednom JOIN“ – Doctrine u to-many asociací pouští
**druhý dotaz**, JOIN dělá jen u to-one.

### Další ověřené technické položky

| Položka | Výsledek |
|---|---|
| PgBouncer 1.21 a `max_prepared_statements` | verze sedí; omezení na statementy vedené protokolem DB |
| DORA benchmarky | **report 2025 zrušil škálu Elite/High/Medium/Low** a nahradil ji sedmi profily; ideál CFR je 0–2 %, ne „pod 15 %“ |
| `#[AsAlias]` s `target` | Symfony 8.1 ✅ (atribut sám od 6.3, `when` od 7.3) |
| `dama/doctrine-test-bundle` | `use_savepoints` se nastavuje **jen na DBAL < 4**; na stacku knihy je zbytečné |
| Temporal PHP SDK | `temporal/sdk` v2.18 (17. 8. 2026), stabilní; oficiální PHP klient pro Camundu 8 neexistuje |
| phparkitect | 1.3.0 (31. 7. 2026), `php ^8.0` |
| Simon Brown, *Distributed big balls of mud* | 6. 7. 2014; původní doména mrtvá, reprinty žijí |
| Brandolini, *Introducing Event Storming* | 18. 11. 2013 potvrzeno |

### Třetí dávka: API z kódu, původ čísel, mrtvé odkazy

Ověřeno čtením zdrojáků místo dokumentace – u tří položek se ukázalo, že dokumentace vedla jinam:
**`HINT_ENABLE_DISTINCT`** patří `Paginator`u, ne `Query` (zápis, který studie předpokládala, by
spadl); **`Vote::getReasons()` neexistuje**, důvody se čtou z veřejné vlastnosti `$reasons`;
`setEagerFetchBatchSize()` má **výchozí hodnotu 100**, takže mechanismus běží i bez nastavení.
Doloženy jsou i `PrimaryReadReplicaConnection` a pětice `iterate*` metod v DBAL 4.4.x.

Původ tří často citovaných čísel:

- **Standish Group / „50 % nepoužívaných funkcí“** (`migration_from_crud`, `what_is_ddd`)
  nepochází z reportu 2014, ale z keynote Jima Johnsona na **XP 2002**. Původní čísla jsou 45 %
  nikdy a 19 % zřídka. Podle rozboru Mikea Cohna stál celý výzkum na **čtyřech interních
  aplikacích**: „Yes, four applications. And, yes, all internal-use applications.“
  **Doporučení: číslo z knihy vyškrtnout.**
- **Knuth** (`performance_aspects`): doloženo plné znění pasáže. Kapitola cituje jen prostřední
  větu a vynechává podmínku „97 % of the time“ i pointu „Yet we should not pass up our
  opportunities in that critical 3%“. Knuth optimalizaci nezakazuje, vymezuje ji.
- **Prime Video**: článek z **května 2023** (ne března), původní URL vrací 301, blog byl zrušen.
  Jde o jednu komponentu jednoho týmu, ne o obrat celého Prime Videa – podávat to jako obecný
  argument proti microservices je přesně chyba, kterou rozebírá Cockcroft.

Dále: **`symfony/feature-flags` neexistuje** (živé jsou `flagception/flagception-bundle` 6.1.1
a `unleash/client` v2.11, obojí třetí strana), `DispatchAfterCurrentBusMiddleware` existuje
a od 5.3 nevyhazuje výjimku mimo kontext dispatche, Blackfire Builds jsou zdokumentované
a tvrzení kapitoly o blokování merge platí.

Jedna položka se přesunula mezi úkoly: **chování Doctrine SQLFilteru u neowning strany
one-to-one** se z dokumentace vyčíst nedá, rozhoduje persister. Chce reprodukční test, ne rešerši
– a stojí na tom bezpečnostní doporučení kapitoly 11.

### Čtvrtá dávka a stav ke konci

Doplněny verze a datace: podepisování Messenger zpráv per handler je ze **Symfony 7.4**, ne z 8;
**Symfony 8.1.0 vyšlo 29. 5. 2026**; Kévin Gomez publikoval *On Taming Repository Classes*
**7. 2. 2015** (pozor na záměnu se stejnojmenným Eberleiho textem z 2013); Fowlerův
*Strangler Application* je z **29. 6. 2004** a na *Strangler Fig* se přejmenoval **29. 4. 2019**,
takže datace „(Martin Fowler, 2004)“ v knize je správná. Symfony Workflow oficiální stanovisko
k doménovému modelu nemá, ale téma je vedeno jako otevřené issue symfony-docs#10819.

**Stav po druhém kole: ze 171 položek je 44 uzavřených, 127 otevřených.** Otevřené se dělí na
37 technických, 51 knižních, 15 „data neexistují“ a 24 ostatních. Technické zbytky jsou
jednotlivosti bez průřezového dopadu – chování `@>` bez `::jsonb` castu, dekorace privátní služby
`security.authorization_checker`, `#[IsGranted]` s asynchronním busem, `EntityManager::clear()`
v ORM 3, referenční DDD projekt na Symfony 8, adopce OPA/Cerbos v PHP.

### Kolik z otevřených položek skutečně blokuje přepis

Počet otevřených položek sám o sobě neříká, jestli lze přepisovat kapitoly. Rozhodující je, kolik
z nich nese tvrzení, na kterém kniha staví. Po roztřídění podle toho, zda položka odkazuje na
konkrétní řádek kapitoly:

**Blokujících je 25, neblokujících 97.** Zbylých 97 jsou podněty na doplnění, alternativní zdroje
nebo věci mimo text kapitol – ty přepis nezdrží.

Z těch 25 blokujících ale rešerše vyřeší jen část. Dělí se takto:

- **Knižní citace** (Vernon *IDDD*, Khononov *Learning DDD*, Richardson *Microservices Patterns*).
  Potřebují výtisk, ne hledání. Týká se to `aggregate_design`, `anti_patterns`, `context_mapping`,
  `sagas`, `subdomains`, `when_not_to_use_ddd` a `architectural_styles`.
- **Čísla bez zdroje**: multiplikátory 5–10X (`when_not_to_use_ddd.md:379-381`), retence 7–30 dní
  (`ddd_pain_points.md:613`), „4–6 tříd místo jedné“ (`cqrs.md:127`), poměr 80/20 u Vernona,
  návratnost investice do DDD. **Tyto se nedohledají, protože ta data neexistují.** Rozhodnutí je
  autorské: vyškrtnout, nebo označit za autorský odhad.
- **Technické**, které rešerše zvládne – vyřešeno v páté dávce (níže).

### Pátá dávka: blokující technické položky

- **Pat Helland, *Life beyond Distributed Transactions*** – CIDR 2007, s. 132–141, volné PDF na
  `ics.uci.edu`. Nese větu, která patří přímo do kapitoly o agregátech: entity *„may be atomically
  updated within the entity but never atomically updated across entities“*. Hranice transakce
  formulovaná nezávisle na DDD a o šest let dřív než Vernonův *Effective Aggregate Design*.
- **Práh snapshotu** – Youngovo „zhruba tisíc událostí, možná víc“ je doložené (Code on the Beach
  2014). Cennější než číslo je jeho argument, proč snapshoty patří do oddělené tabulky: snapshot
  v event logu je nucen být na poslední verzi a u vytížených agregátů vyrábí smyčku konfliktů.
- **Dahan, *Don’t Create Aggregate Roots*** – obsah ověřen, text je citovatelný. Je ale v napětí
  s kanonickým `Order::place()`; patří do knihy jako protihlas, ne jako pravidlo k převzetí.
- **`public readonly` u `#[ORM\Id]`** (`aggregate_design.md:564`, `:567`) – podpora je od ORM 2.11,
  jenže hlášení `doctrine/orm#10032` a `#10660` popisují `LogicException` právě u readonly ID
  a readonly kolekcí. Obě pocházejí z éry proxy tříd a s nativními lazy objekty důvod pro první
  z nich odpadá. **Zda to platí i pro mazání a kolekce, ukáže jen test na ORM 3** – do té doby
  ukázku nepovažovat za bezpečnou.

### Co z technických položek zbývá

Kategorie A měla 67 položek; ověřené jsou hlavní skupiny (Doctrine, Symfony verze, balíčky,
datace, benchmarky). Nedotčené zůstávají jednotlivosti, u nichž nález nemá průřezový dopad:
Kévin Gomez (2015, mrtvý server), články k Prime Video, Blackfire Builds, chování Doctrine
SQLFilteru u one-to-one na neowning straně, `dispatch_after_current_bus`,
`Query::HINT_ENABLE_DISTINCT`, `PrimaryReadReplicaConnection`, verze `setEagerFetchBatchSize()`.

Kategorie B (47 knižních citací – Vernon, Khononov, Millett & Tune, Team Topologies, Kleppmann,
Richardson) je vyhledáním neřešitelná; ověřit lze obsahy a výtahy, ne doslovná znění. Kategorie C
(16 položek – ROI DDD, učící křivka, bug rate, benchmarky PHP relay workeru) jsou data, která
neexistují; ta tvrzení je nutné z knihy vyškrtnout, ne dohledat.
## Šesté kolo: knižní citace ověřené proti vlastním výtiskům (2026-09-04)

Knižní kategorie, kterou rešerše uzavřít nemohla, se otevřela – Michal zpřístupnil své zakoupené
knihy přes síťový share. Ověřeno proti plnému textu **Evans 2003**, **Vernon *IDDD***,
**Vernon *DDD Distilled***, **Freeman & Pryce *GOOS*** a **Kleppmann *DDIA***.

### Tři tvrzení padla

**1. Shared Kernel (`context_mapping.md:121`).** Formulace „menší než přirozený průnik obou
modelů“ v Evansovi 2003 není; slovo „intersection“ se u vzoru nevyskytuje vůbec. Není tam ani
„Keep this kernel small“ – ta věta přibyla až v *DDD Reference* (2015). Závažnější je obsahový
rozpor: Evans píše *„The SHARED KERNEL is often the CORE DOMAIN, some set of GENERIC SUBDOMAINS,
or both“*, kdežto kapitola doporučuje sdílet elementární hodnotové objekty. To je autorský posun,
ne Evansova pozice. Kapitola navíc vynechává celý provozní režim vzoru – společnou integraci,
běh testů obou týmů a slučování oddělených kopií kernelu.

**2. Poměr „80 % investice do 20 % kódu“ (`architectural_styles.md:1141`).** Vernon toto číslo
neuvádí. V *IDDD* je „80 percent“ **jediný výskyt v celé knize** a týká se návrhu doménových
událostí; „20 percent“ tam není vůbec, v *DDD Distilled* ani jedno. Věcné jádro (diferencovaná
investice) přitom správné je – vyškrtnout stačí to číslo a nahradit je Vernonovými kritérii.

**3. Supporting subdomény (`subdomains.md:56`).** Kapitola tvrdí, že Vernon doporučuje „lehčí
variantu DDD“. Vernon říká opak: u Supporting subdomény, kterou nelze pořídit jako hotovou
Generic, jsou taktické vzory *„a good opportunity to invest“*, pokud tým je umí, model je
inovativní a má vydržet roky.

### Co se potvrdilo a získalo doslovné znění

- **Evansovo „harsh reality“** – věta, kterou kapitola cituje jako „Not all of a large system will
  be well designed“, v knize v této podobě není. Skutečné znění: *„The harsh reality is that not
  all parts of the design are going to be equally refined. Priorities must be set.“*
- **DDD-Lite** – Vernon termín používá, ale jako varování: *„practicing DDD-Lite leads to the
  construction of inferior domain models.“* Nepodávat ho jako legitimní odlehčenou variantu.
- **Supporting Subdomain** – termín u Vernona doložen z primárního zdroje, konvence z `CLAUDE.md`
  je tím potvrzená.
- **1 BC = 1 tým** – Vernon to formuluje jako preferenci („it is best for“) a výslovně varuje:
  *„a single Bounded Context is not an attempt to limit flexibility to team organization.“*
- **Autorizace** – Vernon ji umisťuje do Application Services (*„The Application Service took care
  of security and object translation“*), ne do vrstev. Výhrada k formulaci „vrstvení“ trvá.
- **Kap. 4 *Architecture*** obsahuje sekci „Long-Running Processes, aka Sagas“; kap. 13 k tomu
  přidává stavové automaty a timeouty – pro kapitolu o sagách praktičtější.
- **Kap. 5** vypisuje čtyři strategie identity, **kap. 11** staví do popředí factory metodu na
  agregátním kořeni (opora pro kanonické `Order::place()`), **kap. 12** vede rozdíl
  collection-oriented vs. persistence-oriented, **kap. 3** obsahuje katalog vztahů i zkratky
  ACL/OHS/PL pro kreslení map.
- **Anemický model** – Vernon má diagnostický test dvou otázek; převzít formu testu je pro
  kapitolu cennější než citát.
- **Test Data Builder** – nově citovatelný z *GOOS*, s. 258, včetně příkladu `OrderBuilder`, takže
  nedostupný Pryceův blogpost není potřeba. Bibliografie *GOOS* zároveň dala přesný záznam
  Schuh & Punke: *ObjectMother: Easing Test Object Creation In XP*, XP Universe, 2001.

### Co ze share chybí

**Khononov *Learning Domain-Driven Design*, Richardson *Microservices Patterns*, Team Topologies
a Millett & Tune** na share nejsou. Blokující položky, které na nich stojí, tedy zůstávají
otevřené – týká se to hlavně `subdomains`, `when_not_to_use_ddd`, `anti_patterns` a `sagas`.
Newmanovo *Building Microservices* je k dispozici jen v **1. vydání (2015)**, zatímco kniha cituje
2. vydání (2021); čísla kapitol z něj ověřit nelze.

### Výsledky reprodukčních testů (2026-09-04)

Otázky, které rešerše nemohla uzavřít, ověřeny spuštěním na **Doctrine ORM 3.6.8, DBAL 4.4.4,
PHP 8.4.16**, SQLite in-memory, s `enableNativeLazyObjects(true)`.

**1. `final` + `public readonly` u `#[ORM\Id]` + `readonly` embedded — funguje.** Prošlo všech
šest scénářů: persist, hydratace přes `find()`, `getReference()` (vrací lazy objekt **téže třídy**,
ne podtřídu), inicializace lazy objektu přístupem k vlastnosti, update i **remove**. Poslední
jmenovaný je přesně případ z `doctrine/orm#10032`; na ORM 3 s nativními lazy objekty už nepadá.
Úpravy kapitol jsou tím podložené měřením, ne jen dokumentací.

**2. `readonly` u kolekce — stále nefunguje.** `doctrine/orm#10660` platí i v 3.6.8: persist
agregátu s `private readonly Collection $items` skončí `LogicException: Attempting to change
readonly property`. Zajímavé je, že *hydratace* z databáze projde – selhává až zápis, protože
Doctrine potřebuje `ArrayCollection` nahradit `PersistentCollection`. **Kniha readonly kolekce
nikde nemapuje, takže se jí to netýká**; poznámka je tu pro případ, že by to někdo doplnil.

**3. SQLFilter u neowning strany one-to-one — díra potvrzena.** Test se dvěma tenanty, filtr
nastavený na tenant A:

| Přístup | Výsledek |
|---|---|
| DQL `SELECT p FROM Profile p` | vrátí jen tenanta A – filtr aplikován |
| `find(Profile::class, 'p2')` (tenant B) | `null` – filtr aplikován |
| `$profileB->account` (neowning one-to-one) | **vrátí Account cizího tenanta – filtr NEaplikován** |

Bezpečnostní doporučení kapitoly 11 tím stojí na měření. Promítnuto do textu na dvě místa:
mezi omezení SQLFilteru a jako varování do sekce o multi-tenancy.


## Pravidlo: co dělat s tím, co se nepodařilo ověřit

Otázka „když to neověříme, musí citace pryč?“ má čtyři různé odpovědi podle toho, o jaký případ
jde. Plošné mazání by z knihy odstranilo i tvrzení, která jsou správná – jen neozdrojovaná.
Plošné ponechání by naopak nechalo stát atribuce, za kterými nikdo nestojí.

**1. Vyvrácené tvrzení – pryč celé, nestačí sundat jméno.**
Tam, kde dohledaný zdroj říká opak, nepomůže přeformulovat na autorský názor: tvrzení samo je
špatně. Sem patří Beckovo „testuje méně věcí“, Tuneovo CLAUDE.md jako bounded-context dokument,
poměr „80 % investice do 20 % kódu“, Standishových „50 % nepoužívaných funkcí“, Youngovo povýšení
CQRS na architekturu, Shared Kernel „menší než přirozený průnik“.

**2. Číslo bez zdroje – číslo pryč, teze zůstává.**
Číslo předstírá přesnost, kterou čtenář nemá jak ověřit. Ve všech případech ale teze přežije bez
něj: „4–6 tříd místo jedné“ → „CQRS znamená víc tříd“; retence „7–30 dní“ → „retenci nastavte
podle objemu a doby, po kterou může dorazit duplikát“. Alternativa k vyškrtnutí je označit hodnotu
jako ilustrativní, ale jen tam, kde slouží jako příklad, ne jako doporučení.

**3. Zdroj existuje a je věrohodný, jen jsme ho nečetli – pryč jde citace, ne tvrzení.**
Knihy, které nemáme (Khononov, Richardson, Team Topologies, Millett & Tune), a texty za paywallem.
Vyškrtnout věcné tvrzení by knihu poškodilo. Co musí pryč: **doslovné citace** textů, které jsme
nečetli, a **čísla kapitol nebo stran**, která jsme neověřili. Parafráze věcného tvrzení
s uvedením autora a knihy zůstává – to je běžná odborná praxe.

**4. Nedostupné doslovné znění, ale doložená existence – parafrázovat.**
Cockcroftův článek: autor, název i URL potvrzené, jen Medium blokuje čtení. Parafráze je legitimní,
uvozovky ne.

**Řídící princip.** Nedoložená atribuce jmenované osobě je horší než žádná citace, protože čtenáři
sugeruje autoritu, která za tvrzením nestojí. Proto: **každý výrok připsaný jmenované osobě musí
mít u sebe zdroj, nebo být přepsán jako autorské tvrzení.** Naopak věcné tvrzení, které je
obhajitelné z praxe, může v knize zůstat i bez citace – jen se nesmí tvářit, že za ním stojí
někdo jiný.

## Sedmé kolo: zakoupené knihy (2026-09-04)

Michal koupil šest chybějících titulů a dal je na share do `Knihy/DDD`. Ověřeno proti plnému textu
**Khononov *Learning DDD*** (1. vyd. 2021), **Richardson *Microservices Patterns*** (1. vyd. 2018),
**Skelton & Pais *Team Topologies*** (1. vyd. 2019), **Millett & Tune** (2015),
**Brandolini *Introducing EventStorming*** a **Newman *Building Microservices*** (2. vyd.).

### Khononov: tři z pěti tvrzení v knize nejsou

- **„Tři strategie“ pro large-collection problem** (`aggregate_design.md:687-699`) – v knize
  nejsou. „large collection“ 0 výskytů, „three strategies“ 0, „collection“ celkem 6× v celé knize.
  Druhá strategie se navíc opírá o Doctrine `EXTRA_LAZY`; Khononov píše v C#. Strategie jsou věcně
  správné, jen nejsou jeho – **atribuci odstranit**.
- **Telco příklad** (`when_not_to_use_ddd.md:388–390`) – neexistuje. „telco“ 0, „telecom“ 1 (Nokia
  jako příklad firmy měnící domény). Jediná zmínka o akvizici u Khononova vyznívá **opačně**:
  *„The company became profitable very quickly, and eventually it was acquired by its biggest
  client.“*
- **1:N mapování u Core subdomén** (`subdomains.md:158`) – Khononov říká opak. Varuje:
  *„One thing to beware of is splitting a coherent functionality into multiple bounded contexts.
  Such division will hinder the ability to evolve each context independently.“* Legitimní důvody
  pro extrakci uvádí jiné než kapitola – oddělení vývojových cyklů a nezávislé škálování.
- **„Páté pravidlo“** (`:81-84`) – jádro platí, formulace je silnější. Khononovovou hranicí je
  **databázová transakce**, ne command: *„one aggregate per database transaction“*.
- **Uber a Google** (`subdomains.md:50`) – potvrzeno doslovně, včetně matchingu jezdců
  a ranking algoritmu.

**Vysvětlen původ jedné citace napříč knihou.** Věta „Not all of a large system will be well
designed“, kterou kapitola připisuje Evansovi, je **Khononovova parafráze** – používá ji dvakrát
a pokaždé ji Evansovi připisuje. V Evansově knize 2003 v této podobě není; Evans má *„The harsh
reality is that not all parts of the design are going to be equally refined.“* Naše kniha tedy
převzala parafrázi jako citát.

### Ostatní knihy

- **Richardson** – kapitola 4 je skutečně o sagách, citace sedí. Všechna countermeasures v knize
  jsou (semantic lock 15×, commutative updates, pessimistic view, reread value), stejně jako
  compensatable a pivot transaction. Jediná oprava: on píše **„retriable“** (18×), kapitola
  „Retryable“ – ten tvar se v knize nevyskytuje ani jednou.
- **Team Topologies** – poměr **6:1 až 9:1** v knize je, ale jako **TIP** opřený o to, co
  organizace samy hlásí. Kapitola tu výhradu musí zachovat, jinak dělá z doporučení naměřenou
  hodnotu. Varování před měřením cognitive load přes LOC je doslovné a ostřejší, než kapitola
  naznačuje – *„misguided“* – včetně odkazu na výzkum Graylina Jaye (2009).
- **Brandolini** – čtyři barvy legendy sedí doslovně (oranžová, modrá, lila, žlutá), jedna je
  v rozporu: **Hot Spot je u něj fialový** (čtyři různé formulace v knize), zatímco kapitola má
  růžovou pro Hot Spot a fialovou pro Bounded Context, pro který Brandolini žádnou barvu nezavádí.
- **Newman 2. vyd.** – struktura kapitol potvrzena z primárního zdroje, nález G23 tím doložen.
  Pojem *information hiding* zavádí kapitola 1, ne 2.
- **Millett & Tune** – sekce „Who This Book Is For“ v knize **není** (0 výskytů); neodkazovat na ni.

### Co zůstává

Nekoupen zůstal jen **Hohpe & Woolf, *Enterprise Integration Patterns*** (2003) – jedna
neblokující položka. Zbývající otevřené položky se knihami nevyřeší: čtyři čísla bez zdroje,
tvrzení o rozšíření atributů v open source a Vernonův neexistující text k DDD a AI.

## Knihy, které chybí k dokončení ověření

Stav k 2026-09-04. Bibliografické údaje ověřené, ne psané z paměti. Seřazeno podle toho, kolik
otevřených položek každá kniha zavře.

| # | Kniha | Vydání | ISBN-13 | Položek |
|---|---|---|---|---:|
| 1 | **Vlad Khononov – *Learning Domain-Driven Design: Aligning Software Architecture and Business Strategy*** | O'Reilly, 1. vydání, 2021 | 9781098100131 | **19** (5 blokujících) |
| 2 | **Alberto Brandolini – *Introducing EventStorming*** | Leanpub, průběžné vydávání | bez ISBN | 4 |
| 3 | **Chris Richardson – *Microservices Patterns: With examples in Java*** | Manning, **1. vydání**, 2018 | 9781617294549 | 3 (1 blokující) |
| 4 | **Scott Millett, Nick Tune – *Patterns, Principles, and Practices of Domain-Driven Design*** | Wrox, 2015 | 9781118714706 | 3 |
| 5 | **Matthew Skelton, Manuel Pais – *Team Topologies: Organizing Business and Technology Teams for Fast Flow*** | IT Revolution, 1. vydání, 2019 | 9781942788812 | 3 |
| 6 | **Sam Newman – *Building Microservices: Designing Fine-Grained Systems*** | O'Reilly, **2. vydání**, 2021 | 9781492034025 | 1 |
| 7 | **Gregor Hohpe, Bobby Woolf – *Enterprise Integration Patterns*** | Addison-Wesley, 2003 | 9780321200686 | 1 |

### Na co si dát pozor při nákupu

**Khononov** má jen jedno vydání. Copyright uvádí „October 2021: First Edition“ a k tomu
„2024-03-22: Second Release“ – to je dotisk, ne druhé vydání. Nehledat novější.

**Richardson** má od roku 2018 i druhé vydání, ale kniha cituje **první (2018)**, a to na třech
místech výslovně. Kupovat první; druhé by čísla kapitol posunulo a nic by neověřilo.

**Newman** je opačný případ: kniha cituje **druhé vydání (2021)** a na share je jen první (2015).
Struktura kapitol se mezi vydáními zásadně liší a pojem „information hiding“ je novinkou druhého.
Nález G23 je ale už ověřený z autorova rozpisu obsahu na samnewman.io, takže kniha zavře jen
jednu drobnost – nejnižší priorita ze všech.

**Team Topologies** vyšlo v roce 2025 ve druhém vydání (ISBN 9781966280002). Otevřené položky se
vztahují k prvnímu, ale jedna z nich zní právě „co se ve druhém vydání změnilo“. Pokud se kupuje
jen jedno, praktičtější je **první (2019)**, protože proti němu se ověřují konkrétní tvrzení
kapitoly; druhé vydání je bonus.

**Brandolini** je na Leanpubu za dobrovolnou cenu (minimum ~10 USD) a kniha je dlouhodobě
nedokončená (~70 %). To není překážka – potřebné pasáže jsou v hotové části.

### Co se nekoupí

Zbylé otevřené položky knihami nevyřeší nic:

- **Čtyři čísla bez zdroje** – multiplikátory 5–10X, retence 7–30 dní, „4–6 tříd místo jedné“,
  návratnost investice do DDD. Ta data neexistují; rozhodnutí je autorské.
- **„Drtivá většina open-source projektů používá atributy“** – nikdo to neměřil.
- **Vernonův text k DDD a AI** – neexistuje; kapitola cituje jen jeho poznámku z InfoQ.

## Revize všech 26 kapitol dokončena (2026-09-04)

Každá kapitola prošla revizí podle `docs/prompts/review-chapter.md` s příslušnou studií jako
podkladem. Revize běžely po jedné kapitole v odděleném kontextu; výsledek jsem u každé ověřil
proti primárním zdrojům a commitoval zvlášť.

**Rozsah:** 26 souborů, +6266 / −2345 řádků (26 930 → 30 877 řádků, +15 %).

### Co se opravilo v kódu

Ukázky, které by nefungovaly:

- `subdomains` – Auth0 SDK v8 vrací PSR-7 odpověď, ne `null`; ošetření chybějícího uživatele
  nikdy neproběhlo.
- `practical_examples` – `HashedPassword::fromHasher()` nemohl fungovat, protože Symfony
  `hashPassword()` vyžaduje instanci uživatele. Kontrola duplicity přes `findByEmail()` měla
  TOCTOU mezeru.
- `anti_patterns` – enum `UserStatus` měl casy v PascalCase, ale kód je volal jako `self::PENDING`.
- `aggregate_design` – YAML nastavoval `enable_lazy_ghost_objects` a `auto_generate_proxy_classes`,
  obě v DoctrineBundle 3 odstraněné.
- `case_study` – `readOnly: true` na read modelu vypíná sledování změn, takže by každý `UPDATE`
  z projekce tiše zmizel; `flags: ['gin']` Doctrine ignoruje.
- `architectural_styles` – `allow_no_senders: false` na query busu by rozbil každý synchronní dotaz.
- `microservices_and_ddd` – decode-only serializer by při retry spadl na `encode()`.
- `cqrs` – `--format=json` má `messenger:stats`, ne `messenger:failed:show`.

### Co se opravilo ve faktech

Vyvrácené atribuce: Beck, Tune a DHH v kapitole o AI (třináct výroků ze čtyřiceti), Young a povaha
CQRS, poměr 80/20 připsaný Vernonovi, Khononovův telco příklad a jeho údajné doporučení 1:N
mapování, Vernonova „strategická hodnota“, Seemann jako obhájce anemického modelu.

Čísla bez zdroje zmizela napříč knihou: multiplikátory nákladů, návratnost investice, retence
deduplikační tabulky, „4–6 tříd“, podíl konfliktů zámku, replikační lag, prahy pro partitioning,
Standishova statistika. Kde to šlo, nahradil je nástroj místo tvrzení – například měření lagu přes
`pg_last_xact_replay_timestamp()`.

Zastaralý stav: Broadway je archivovaná, `qossmic/deptrac` opuštěný, DORA zrušila škálu
Elite/High/Medium/Low, Event Store se přejmenoval na Kurrent, Team Topologies vyšlo podruhé.

### Průřezová konzistence

`AggregateRoot` je bajt po bajtu shodný mezi kapitolami 6 a 10 (kontrolováno hashem). `place()`
je všude statická factory, stavové přechody se jmenují `confirm()` nebo `submitForApproval()`.
Enum casy jsou PascalCase, události bez sufixu `Event`, události se nenahrávají v konstruktoru,
`Money` má napříč třemi definicemi kompatibilní API.

### Stav kontrol

- `php -l`: **300 bloků, 0 chyb**
- `lint:twig`: 30 souborů OK
- interní odkazy: **563, 0 rozbitých**; žádná duplicitní kotva uvnitř kapitoly
- typografie: 0 em dash, 0 anglických uvozovek, 0 výskytů „Tady“
- `reading_time` přepočítán u všech kapitol, které změnily délku

## Osmé kolo: nezávislá kontrola celé knihy (2026-09-05)

Knihu prošlo pět nezávislých agentů, kteří dostali zadání **nálezy hlásit, ne opravovat**,
a záměrně nedostali seznam už provedených oprav – měli hledat čerstvýma očima. Čtyři
audity kódu (po skupinách kapitol), jeden audit konzistence napříč knihou a jeden audit
hlasu a jazyka. Každý nález jsem ověřoval sám, než jsem podle něj cokoli změnil.

### Nálezy, které vyvrátily tvrzení knihy (ověřeno spuštěním)

| Kapitola | Tvrzení knihy | Skutečnost |
|---|---|---|
| `aggregate_design` | zmizelý `requiresSQLCommentHint()` v DBAL 4 generuje prázdné migrace | `columnsEqual()` srovnává SQL deklaraci, ne PHP typ; `getUpdateSchemaSql()` vrací prázdné pole |
| `aggregate_design` | nad neinicializovanou kolekcí `matching()` nekonvertuje enum | opačně: s enumem vyjdou obě cesty stejně (2 = 2), rozchází se surová DB hodnota (2 vs. 0) |
| `ddd_pain_points` | `clear($entityName)` vyvolá chybu | PHP přebytečný argument ignoruje – metoda tiše odpojí celou Identity Map, což je horší |
| `context_mapping` | `opis/json-schema` umí schéma načíst z URL | žádný HTTP fetch nemá; bez `registerFile()` skončí `RuntimeException: Schema not found` |
| `performance_aspects` | `max_prepared_statements` má v PgBouncer 1.21 výchozí 200 | 1.21 volbu zavedla s výchozí **0**; hodnota 200 je výchozí až od **1.24** (NEWS.md) |
| `testing_ddd` | od PHPUnit 10 se soubor jmenuje `phpunit.dist.xml`, ne `phpunit.xml.dist` | hledá se v pořadí `phpunit.xml`, `phpunit.dist.xml`, `phpunit.xml.dist` – starý název funguje dál |

Agent u PgBounceru tipoval verzi 1.22; správná je 1.24 podle changelogu. Reprodukční
skripty zůstaly ve scratchpadu (`ormtest/e_difftype.php`, `e_extralazy.php`, `d_clear.php`,
`a_idvo.php`).

### Kód, který by v Symfony 8 spadl

- `Voter::voteOnAttribute()` bez čtvrtého parametru `?Vote $vote = null` – fatal error
  „Declaration must be compatible" (ověřeno ve zdrojáku security-core 8.0 i spuštěním).
- `AccessDecision::isGranted()` – je to veřejná vlastnost, ne metoda.
- `Request::get()` – v 8.0 odstraněna.
- `UserInterface::eraseCredentials()` – v 8.0 odstraněna.
- `Currency::equals()` – `Currency` je string-backed enum bez metod.
- `Money::multiply(0.21)` na `multiply(int)` – `TypeError`; `subtract()`, `toMinorUnits()`
  a `fromAmount()` v knize nikdy definované nebyly.
- VO jako `#[ORM\Id]` bez `__toString()` – `persist()` padne na
  `UnitOfWork::getIdHashByIdentifier` (ověřeno spuštěním na ORM 3.6.8).

### Věcné chyby v logice ukázek

- `migration_from_crud`: `activate()` token neporovnával s tím, který entita vydala –
  libovolný platný token aktivoval libovolný účet.
- `ddd_pain_points`: `catch (UniqueConstraintViolationException)` obepínal i handler,
  takže unique violation z domény se vydávala za duplicitní zprávu a Messenger ji potvrdil.
- `case_study`: projekce byla TOCTOU – dva workery vidí `null`, druhý flush padne na PK.
- `microservices_and_ddd`: serializer házel `\RuntimeException`; jen
  `MessageDecodingFailedException` receiver rozpozná, jiná výjimka shodí worker
  a zpráva se po restartu vrátí (potvrzeno CHANGELOGem Messengeru 8.1).
- `practical_examples`: `Cart::checkout()` neměnil stav, takže dvojklik vyrobil dvě objednávky.
- `performance_aspects`: `ORDER BY` nad `::text` aliasem řadil lexikograficky;
  `BETWEEN` nad timestampem uřízl poslední den; `INNER JOIN o.items` vynechal
  objednávky bez položek.

### Sjednocení napříč knihou

Audit konzistence našel dvanáct rozporů v API. Rozhodnutí a poměry:

| Věc | Bylo | Je |
|---|---|---|
| Událost z `place()` | `OrderCreated` (5 kapitol) vs. `OrderPlaced` (15) | `OrderPlaced` |
| Namespace BC | `App\Ordering` (231) / `App\OrderManagement` (89) / `App\Order` (17) | `App\Ordering` |
| Sdílené jádro | `App\Shared` (22) vs. `App\SharedKernel` (62) | `App\SharedKernel` |
| `Money` a `Currency` | čtyři různé namespace | `App\SharedKernel\Domain` |
| `OrderStatus` | `{Created,…}` vs. `{Draft,…}`, plus nedefinovaný `Placed` | `{Draft, Confirmed, Paid, Shipped, Delivered, Cancelled}` |
| Součet objednávky | `totalAmount()` (6) / `total()` (3) / `getTotal()` (1) | `totalAmount()` |
| Konstrukce ID | veřejný konstruktor (5) vs. privátní + `fromString()` (2) | veřejný validující konstruktor + `fromString()` alias |
| Zápis VO | tři styly `Email` | `final readonly class` s promoted property |
| Prázdná objednávka | `EmptyOrderNotAllowedException` / `EmptyOrder` / `EmptyOrderException` | `EmptyOrderException` |
| Sufix commandu | 6 bez sufixu vs. 24 se sufixem | sufix `Command`, konvence doplněna do předmluvy |
| Rozhraní repozitáře | `OrderSagaRepositoryInterface` (jediné se sufixem) | `OrderSagaRepository` + `DoctrineOrderSagaRepository` |
| Zápis UUID | `Uuid::v7()->toRfc4122()` vs. `(string) Uuid::v7()` | `(string) Uuid::v7()` |

`SignedMoney` byl slibovaný, ale nikde nedefinovaný typ – odkaz i zmínka odstraněny.

### Co jsem po ověření nezměnil

- **Terminologie „kořen agregátu" vs. „aggregate root".** Glosář uvádí český termín jako
  hlavní a anglický v závorce; kapitoly to dodržují. Není to rozpor.
- **14 z 15 nálezů skriptu `check_tonality.php`.** „Klíčová doména" je překlad Core Domain;
  „mocný framework" a „Je to elegantnější" jsou citované protipříklady; „snadno zapomene"
  a „vypadá jednoduše" nejsou marketing. Keyword matcher tyto kontexty nerozliší.
- **Rozšířené signatury `Order::place()`** ve třech kapitolách. Bez nich by nešlo ukázat
  jejich téma (invariant vymáhaný signaturou, payload události). Odchylka je nově
  přiznaná v textu, ne zamlčená.
- **Holá `\DomainException` v Layered ukázce.** Zkratka je záměrná a nově přiznaná.
- **`recordEvent()` v Event Sourcingu.** Odchylka od `record()` byla přiznaná už dřív.

### Stav po tomto kole

`php -l` 301 bloků / 0 chyb, `lint:twig` 30 souborů OK, kotvy a interní odkazy
1088 kotev / 0 rozbitých, 0 em dashů, 0 anglických uvozovek, 0 „Tady" v kapitolách.

Dva agenti (kontrola faktů a citací, kontrola kódu) padli na limitu API dřív, než
stihli odevzdat report. Jejich záběr částečně pokryly čtyři skupinové audity kódu.

## Deváté kolo: hlas, strukturní signály a citace (2026-09-05)

Kolo mělo tři části: dokončit jazykovou revizi, zbavit text signálů generovaného
obsahu a doověřit citace proti primárním zdrojům. Většinu jsem odpracoval sám –
agenti na Fable opakovaně padali na limitu API.

### Hlas a jazyk

Jednoznačná porušení: tykání v AAA calloutu (`testing_ddd`) – jediné v celé knize;
klišé „není jen X, je Y"; paralelismus sousedních vět; dvojí sloveso ve větě;
„Zdravé optimum" bez zdůvodnění; redundantní věta opakující předchozí odstavec.

**Imperativ (78 míst)** jsem rozhodoval případ od případu. Převedeno 14 tam, kde
nesl *soud* nebo srovnání („Nepoužívejte Event Sourcing paušálně" → „Event Sourcing
se nenasazuje paušálně"). Ponecháno 61 tam, kde nese *krok* – nápravy, rollout
Outboxu, workshop postupy, navigace. Pravidlo v `CLAUDE.md` tenhle rozdíl
nepopisovalo, proto bylo zpřesněno a doplněno o test „krok, nebo soud?".

### Strukturní signály generovaného textu

| Signál | Nález | Zásah |
|---|---|---|
| Rytmus seznamů | `microservices` 8 tučných listů, `what_is_ddd` 8, `architectural_styles` 6 | čtyři seznamy přepsány do prózy, včetně dvou shrnutí kapitol, která kopírovala osnovu místo syntézy |
| Mřížka sekcí | `architectural_styles` měla u všech čtyř stylů „Kdy se X hodí" / „Kdy X nedává smysl" | první převedena na prózu |
| Formulka „V praxi" | 81× ve 21 kapitolách, z toho 21× na začátku věty | sentence-initial výskyty 21 → 6 |
| Wikipedijní úvody | 10 nálezů | 2 opraveny, zbytek je legitimní zavedení termínu |

**Měření, které změnilo plán.** Chystal jsem se krátit 409 vět nad 25 slov podle
pravidla v `CLAUDE.md`. První skript ale dělil věty na koncích řádků, takže měřil
zalomení, ne délku – vycházel průměr 7,8 slova u hard-wrapped kapitol. Po opravě
(spojení odstavců před dělením): **průměr 13,9 slova, variační koeficient 0,53**.
To je lidské pásmo; generovaný text bývá pod 0,40. Plošné krácení by rytmus
srovnalo a text by paradoxně vypadal generovaněji. Rozděleny jen tři věty přes
45 slov. **Je to vědomá odchylka od pravidla „do ~25 slov" ve prospěch čtenáře.**

### Citace

200 URL prověřeno na dostupnost: 193 vrací 200, šest je 403 od vydavatelů
blokujících roboty (ACM, O'Reilly, BMJ, kalele.io) a jeden reálně nefunguje –
**`https://ddd-v-symfony.cz` se nedá přeložit na IP**. Kniha na něj v předmluvě
posílá čtenáře. Doména buď ještě neexistuje, nebo je jiná; rozhodnutí je na autorovi.

Ověřeno proti primárním zdrojům (Evans 2003, DDD Reference 2015, IDDD, Distilled,
martinfowler.com):

| Tvrzení | Výsledek |
|---|---|
| „Transient references to internal members can be passed out for use within a single operation only" | doslova v Evans 2003 |
| Vernonův „generous number of seconds, minutes, hours, or even days" | doslova v IDDD |
| „four primary ways to generate Entity unique identities" | doslova v IDDD |
| Big Ball of Mud a Partnership jako vzory v *DDD Reference* | oba tam jsou (str. 30 a 37) |
| Evans 2003 slovo „supporting subdomain" nepoužívá jako vzor | potvrzeno – jen popisně malými písmeny |
| „Give the MODULES names that become part of the UBIQUITOUS LANGUAGE" | doslova v Evans 2003 |
| Douglas Martin „alternative to good design is bad design" | doslova v *DDD Distilled* |
| „natural correlation between service and context boundaries" | doslova u Lewise a Fowlera |
| IDDD kapitoly 2, 10, 12, 13 a jejich názvy | všechny sedí |

**Dvě opravy:**

1. `event_sourcing` uváděl kurzívou *„current state is derived from the history of
   events"* jako formulaci principu. Ta věta není z Fowlerova článku ani odjinud –
   vypadala jako citace bez zdroje. Nahrazena Fowlerovou skutečnou definicí.
2. `team_topologies` tvrdil, že Sweller (1988) rozlišuje tři typy kognitivní zátěže.
   Studie z roku 1988 teorii zavedla, ale trojici intrinsic / extraneous / germane
   doplnili až Sweller, van Merriënboer a Paas v roce **1998**; termín intrinsic
   zavedli Chandler a Sweller na začátku 90. let.

**Číslování citací:** tři kapitoly měly po dřívějším odstraňování neověřitelných
zdrojů díry (`architectural_styles` [[1]]..[[16]] bez 8, 9, 12, 14). Přečíslováno.
Ověřeno, že žádné číslo neukazuje na dvě URL a žádná URL nemá dvě čísla.

### Vlastní chyby v tomto kole

Zaznamenávám je, protože se opakují: dvakrát mi dílčí záměna slova rozbila větnou
stavbu (`CQRS se přitom nasazuje legitimní scénáře`), jednou se náhrada duplikovala
s existujícím textem, jednou jsem vložil metodu do špatné třídy a jednou použil
ASCII uvozovku místo české. Všechno odhalila kontrola po sobě, ne test.
**Poučení: po každé dávkové náhradě si přečíst výsledný odstavec, ne jen ověřit,
že se řetězec našel.**

### Oprava: knihovna byla k dispozici celou dobu

Tenhle odstavec původně tvrdil, že Millett & Tune a Khononova „nemám k dispozici".
**Byla to chyba — obě knihy, a s nimi Brandolini, Newman, Richardson, Skelton & Pais,
Hohpe & Woolf, Fowlerův PoEAA a plný *DDD Distilled*, leží na sdílení
`//katuscakovi/Work/Knihy/DDD/` a `/software-development/`.** Prohlásil jsem je za
nedostupné, aniž bych se tam podíval, ačkoli autor sdílení dřív sám nabídl.

**Poučení: než označím tvrzení za neověřitelné, projít sdílenou knihovnu.**

Po doověření proti primárním textům:

| Tvrzení | Výsledek |
|---|---|
| Millett & Tune, kapitola 3 „Focusing on the Core Domain" | sedí (str. 31) |
| „code for replacement rather than reuse" pro Supporting subdomény | sedí — kniha píše „coding for replacement rather than reuse" |
| Khononov, kapitola 1 „Analyzing Business Domains" | sedí |
| Khononov: tři osy klasifikace | sedí — Table 1-1 (competitive advantage / complexity / volatility) |
| **Khononov: „dal by se prodat jako samostatný byznys?"** | **NESEDÍ — v knize ani v jeho článku není; opraveno** |
| **Brandolini: „color grammar"** | **NESEDÍ — slovo „grammar" v knize není ani jednou; opraveno** |
| Vernon *Distilled*: „consumers should not use the event types" | sedí (plná kniha) |
| Newman: „Microservice Pain Points", *information hiding* | sedí |
| Richardson: *countermeasures*, *semantic lock*, *commutative updates* | sedí |
| Skelton & Pais: čtyři typy týmů, X-as-a-Service, kognitivní zátěž | sedí |
| Brandolini: *unlimited modeling surface*, Italian Agile Day, pivotal events | sedí |

**Nuance u Swellera, aby se oprava nevracela zpět:** *Team Topologies* samy píší
„characterized in 1988 by John Sweller… Sweller defines three different kinds", tedy tu
zkrácenou verzi, kterou kniha převzala. Oprava na historicky přesné znění (trojici doplnili
Sweller, van Merriënboer a Paas 1998) je zpřesněním vůči Skeltonovi a Paisovi, ne opravou
chybné citace. Zaznamenáno i ve studii kapitoly.

### Co zůstává neověřené

- Tvrzení opřená o placené zdroje bez volně dostupného textu a bez výtisku v knihovně.
- Přednášky a konferenční keynote, u nichž existuje jen novinový referát.

## Jak zadat studii (šablona promptu pro agenta)

Model: opus. Jeden agent = jedna kapitola. Paralelně max 4–5, jinak hrozí session limit.

```
Pracuješ v repozitáři /home/michal/Work/ddd-v-symfony — česká odborná kniha „DDD v Symfony 8".

ÚKOL: Vytvoř deep-research studii ke kapitole `content/chapters/<X>.md`
a ulož ji do `docs/studie/<X>-studie.md`.

POSTUP:
1. Přečti `docs/studie/_SABLONA.md` — závazná šablona a pravidla studie.
2. Přečti `CLAUDE.md` — kanonické API knihy.
3. Přečti celou kapitolu.
4. Prozkoumej kontext: `src/Catalog/Chapters.php`, nadpisy sousedních kapitol
   (`grep -n '^## ' content/chapters/*.md`), a hotové studie v `docs/studie/`
   k tématům, která se překrývají.
5. Proveď skutečnou webovou rešerši. Primární zdroje podle pořadí v šabloně.
6. Napiš studii podle sekcí 1–9 v šabloně. 250–450 řádků.

DŮLEŽITÉ:
- NEEDITUJ nic v `content/`. Zapisuješ jediný soubor.
- Nevymýšlej si zdroje ani URL. Co neověříš, patří do „Neověřené / nedohledané".
- Studie je česky, věcně, bez marketingového jazyka.
- Vrať shrnutí do 10 řádků: počet nálezů, počet P1, 2–3 nejdůležitější zjištění.
```

Do zadání přidej metadata kapitoly (číslo, kategorie, cesta, počet řádků, typ podle
`docs/prompts/review-chapter.md`) a odstavec „specifika této kapitoly" — co konkrétně ověřit.

## Co přijde potom

Studie jsou vstup, ne výstup. Doporučené pořadí prací:

**1. Průřezové nálezy dřív než jednotlivé kapitoly.** Opakují se napříč studiemi, takže
oprava po kapitolách by je řešila dvacetkrát a pokaždé jinak.

- **Rozcházející se kanonické API.** `Order` má nejméně tři varianty (veřejný konstruktor
  v ch06 a ch21, `place()` jako stavový přechod v ch21, `place()` jako factory v ch05/07/08/10,
  instanční `place()` v ch20). `Money` je definován dvakrát odlišně (ch03 `final readonly class`
  vs. ch21 `final class`), `Money::zero()` se používá v ch06 a definuje jen v ch21.
  `AggregateRoot` má dvě různé vnitřní implementace (ch06 vs. ch10).
- **Události nahrávané v konstruktoru** — `practical_examples.md:162` a `case_study.md`,
  proti výslovnému pravidlu v `CLAUDE.md`.
- **Sufix `Event`** u názvů událostí — `anti_patterns.md`, zmínka v `testing_ddd.md`.
- **Doctrine zastarale** – **doloženo primárními zdroji, viz „Druhé kolo ověřování“**:
  `enable_lazy_ghost_objects`, `auto_generate_proxy_classes`,
  tvrzení „entita nesmí být `final`" (ch07 i ch10 — na Symfony 8 / ORM 3.7 už neplatí),
  `fetch: 'EAGER'` popsaný jako JOIN (ch16 i ch20), odstraněné partial objects a `iterate()`.
- **Idempotency klíč z `TransportMessageIdStamp`** — ch14 i ch20, dokumentace Messengeru
  přesně tuto konstrukci odmítá.
- **Duplicity mezi kapitolami**: 12.17 vs. 14 (sagy), 12.11 vs. 16 (read modely, dokonce
  stejná kotva `#read-model-optimalizace`), 12.16 vs. 17 (testování, a navzájem si protiřečí),
  ch20 duplikuje ~250 řádků z ch10, 11, 15 a 18.
- **Nepodložená čísla** napříč knihou (nákladové modely, výkonnostní údaje, časové odhady)
  a nedůsledné značení „Ilustrativní scénář".
- **Chybné atribuce jmenovaným autorům.** Doověřovací průchod jich potvrdil sedm jen v `ddd_ai`,
  z toho tři případy, kdy dohledaný zdroj tvrdí opak. Mimo `ddd_ai` navíc: Young a povaha CQRS
  (ch12), dvě čísla Newmanových kapitol (ch19), Evans a Shared Kernel (ch03), Vernon a vrstvení
  autorizace (ch11). Vzor je pokaždé stejný – tvrzení bez uvedeného zdroje se ukáže jako autorská
  konstrukce připsaná autoritě. **Praktický důsledek pro přepis: každý výrok připsaný jmenované
  osobě musí mít u sebe zdroj, nebo být přeformulován jako autorský názor.**

**2. Z P1 doporučení sestavit jeden prioritizovaný seznam.** 136 položek je moc na jeden
průchod — rozdělit na faktické chyby (kód, který nefunguje, a chybné atribuce) a na
strukturální práci (chybějící témata, proporce kapitol). Doověřené atribuce z backlogu patří
do první skupiny a jsou připravené k opravě.

**3. Teprve pak přepisovat kapitolu po kapitole** podle `docs/prompts/review-chapter.md`,
s příslušnou studií jako podkladem.
