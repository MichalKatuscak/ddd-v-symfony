# Stav prací na studiích + jak navázat

Poslední aktualizace: 2026-09-04 – **všech 26 studií hotovo, doověřovací backlog uzavřen**

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
