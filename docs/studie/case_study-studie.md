# Studie: Případová studie

- **Kapitola:** `content/chapters/case_study.md` (č. 24, kategorie Syntéza, 1579 řádků)
- **Cesta:** /pripadova-studie
- **Typ kapitoly:** narativní
- **Datum studie:** 2026-09-04

Poznámka k metodě: rozpočet na `WebSearch` byl v této session vyčerpaný (200/200). Rešerše proto
proběhla přes `WebFetch`, `curl` na `raw.githubusercontent.com`, GitHub REST API a Packagist API,
a proti lokálně nainstalovanému `vendor/symfony/` **v8.0.7**; Doctrine v repozitáři nainstalovaná
není, její API se ověřovalo proti zdrojům `doctrine/orm` a `doctrine/dbal`. Způsob získání je
u každého zdroje v sekci 9. Kapitola je poslední v knize, takže ji studie posuzuje jako
**syntézu**: zda vede čtenáře od domény ke kódu, zda cituje vzory tam, kde je použila, a zda
nepodává smyšlený projekt jako doložený.

## 1. Mapa současné kapitoly

| sekce | rozsah | co tvrdí | zdroje | poznámka |
|---|---|---|---|---|
| 24.01 Úvod | 21–29 | Označení „Ilustrativní scénář"; CRUD `TaskService` s 800 řádky jako protiklad | žádné | Jediné místo, kde je fiktivnost přiznána (G1) |
| 24.02 Požadavky | 31–42 | Osm odrážek funkčních požadavků + „musí být škálovatelný a udržitelný" | žádné | Chybí business model, uživatelé a cíle – krok *Understand* [7] |
| 24.03 Doménová analýza | 44–127 | Tři kroky event stormingu: sběr událostí, seskupení do subdomén, hranice kontextů | Brandolini jmenován bez URL | Neodkazuje na vlastní kapitolu 04 (G7, G11) |
| 24.04 Architektura | 129–347 | 5 bounded contexts, kontextová mapa, taktické vzory, 167řádkový adresářový strom | 1 diagram | Strom je 11 % kapitoly a částečně duplikuje 23.03 (G32) |
| 24.05 Implementace | 348–1091 | `Project`, `Task`, události, VO, 2 commandy, 2 handlery, query, doménová služba | odkazy na kap. 05, 06, 10, 13 | Nejdelší blok (744 ř.). Chybí repozitáře, kontrolery, `releaseEvents()` |
| 24.06 Read modely a projekce | 1092–1442 | Read tabulka, projekce nad 4 událostmi, DBAL dotaz, idempotence, reconciler | odkazy na kap. 12, 16, 13 | Nejsilnější sekce a zároveň nositel dvou technických chyb (G16, G17) |
| 24.07 Výzvy a rozhodnutí | 1443–1543 | Pět dvojic „otázka / volba / cena", u tří i alternativa | žádné | Jediné místo s rozhodovacími momenty; všech pět ale dopadlo dobře |
| 24.08 Ponaučení | 1545–1563 | Deset bodů „z provozu"; mj. odezva „pod 50 ms" | žádné | Metriky bez zdroje u fiktivního projektu (G2, G3) |
| FAQ | 1565–1579 | 7 otázek, mj. „investice se mnohonásobně vyplatila" | – | Renderuje se jako `FAQPage` JSON-LD (`templates/_partials/faq.html.twig:26`) |

Kapitola má dvě nestejné poloviny. První (24.01–24.04, 347 ř.) je strategická, ale z 60 % ji tvoří
adresářový strom; strategické rozhodování zabírá zhruba 60 řádků. Druhá (24.05–24.06, 1095 ř.) je
implementační a technicky nejhutnější část knihy; všech 18 PHP bloků prochází `php -l`. Sekce 24.07
je jediné místo, kde autor váhá mezi variantami, a je proto didakticky nejcennější. Chybí
sociotechnický rozměr a jakákoli sebekritika: žádné rozhodnutí se neukázalo jako chybné, žádná
hranice se neposunula, žádný refaktor neproběhl. Šest calloutů – pět `note`, jeden `warn`, ani
jeden `anti` nebo `pattern`; jediný diagram na 1579 řádků je nejnižší poměr v knize.

## 2. Kanonické zdroje k tématu

**Případová studie jako žánr.** Všechny čtyři referenční knihy staví na jediné průběžné doméně,
ne na sadě izolovaných ukázek: Evans [1] na lodní přepravě (implementace `citerus/dddsample-core`
[5]), Vernon [2] na fiktivní SaaSOvation se třemi kontexty (`VaughnVernon/IDDD_Samples` [6]),
Khononov [4] na help-desk SaaS WolfDesk. Podstatný je Vernonův `iddd_agilepm`: **Agile Project
Management context** je prakticky totožná doména jako tato případová studie (projekty, backlog
items, týmy, přiřazování), a identita je u něj samostatný kontext `iddd_identityaccess`, se kterým
ostatní pracují přes překlad, ne přes import cizí doménové třídy. Kapitola volí opak (5.2, 5.5)
a nezmiňuje, že kanonický vzorový projekt téže domény se rozhodl jinak.

**Postup, který má případová studie sledovat.** `ddd-crew/ddd-starter-modelling-process` [7]
definuje osm kroků: **Understand, Discover, Decompose, Strategize, Connect, Organise, Define,
Code**. Kapitola pokrývá Discover (24.03 krok 1), Decompose (24.03 krok 2), Connect (24.04 mapa),
Define (24.04 taktika) a Code (24.05–24.06). Chybí tři:

- *Understand* – „Align our focus with the organisation's business model, the needs of its users,
  and its short, medium, and long-term goals." Kapitola začíná osmi funkčními požadavky.
- *Strategize* – „Strategically map out your sub-domains to identify core domains: the parts of
  the domain which have the greatest potential for business differentiation." Doporučeným
  nástrojem jsou Core Domain Charts. Kapitola neklasifikuje ani jednu z pěti subdomén.
- *Organise* – „Organise autonomous teams that are optimised for fast flow and aligned with
  context boundaries." Kapitola má jeden tým a téma neotevírá, ač kniha má kapitolu o Team
  Topologies.

Pro krok *Connect* doporučuje [7] Domain Message Flow Modelling, pro *Define* Bounded Context
Canvas [9]. Obojí je přesně to, co v syntetické kapitole chybí: mapa říká, *jaké vztahy* kontexty
mají, ne *jaké zprávy* si posílají v konkrétním use casu.

**Jak vznikají hranice kontextů.** Fowler [10]: „Usually the dominant one is human culture, since
models act as Ubiquitous Language, you need a different model when the language changes." Kapitola
tuto logiku v 24.03 používá (slovo „uživatel" znamená v každém kontextu něco jiného) – je to
nejsilnější argument strategické části a chybí mu jen zdroj.

**Doménová vs. integrační událost.** Microsoft .NET Microservices eBook [11]: „Semantically,
domain and integration events are the same thing… However, their implementation must be
different… Domain events can generate integration events to be published outside of the
microservice boundaries." Verraes [12] dodává praktický vzor: vystavování interních doménových
událostí navenek váže veřejné API na vnitřní strukturu kontextu, explicitně veřejná má být jen
malá podmnožina. Kapitola publikuje `ProjectCreated` s parametry `ProjectId` a `UserId` –
doménovými VO vydávajícího kontextu – na sběrnici, ze které je konzumuje jiný kontext, a označí
to za *Open Host Service / Published Language* (řádky 172–175). Rozdíl kniha nikde nezavádí
(`basic_concepts-studie.md` G13, `context_mapping-studie.md` G17); tady se projeví nejsilněji.

**Subdoména vs. bounded context.** 24.03 krok 3 převádí subdomény na kontexty 1:1 bez komentáře,
ačkoli vlastní kapitola 02 (`subdomains.md:158`) rozlišuje 1:1, 1:N (typické pro Core) a N:1
(typické pro Supporting/Generic). Případová studie z toho používá jen výsledek.

## 3. Stav praxe a posuny

**Modulární monolit a rekonciliace.** Kapitola volí monolit a říká, kde by se rozhodnutí změnilo
(24.07.2, 24.07.3); to odpovídá dnešní praxi a je to silná stránka, chybí jen spojení s vlastní
kapitolou 19 (`/ddd-a-microservices`). Sekce 24.06 je pak v české odborné literatuře ojedinělá –
většina textů o CQRS končí projekcí a mlčí o tom, co dělat, když projekce zaostane; tady je
rekonciliace dovedená do spustitelného console commandu. Posun oproti stavu před pěti lety je,
že se dnes bere jako součást návrhu projekce, ne jako havarijní skript.

**Referenční open-source projekty v PHP.** Kapitola žádný nezmiňuje, ačkoli čtenář po padesáti
minutách čtení potřebuje kód, který si může spustit. `CodelyTV/php-ddd-example` [13] je
nejrozšířenější PHP reference (struktura `src/<BC>/<Module>/{Application,Domain,Infrastructure}`,
`AggregateRoot` s `record()` a `pullDomainEvents()`); kapitola má jinou adresářovou konvenci, aniž
by se vůči ní vymezila. Živě udržovaný je `jorge07/symfony-7-es-cqrs-boilerplate` [14];
`dddshelf/last-wishes` [15] se od 2019 nehýbe, `broadway/broadway` je archivovaný a prooph z
většiny utlumený, živé alternativy jsou `ecotone/ecotone` a `patchlevel/event-sourcing` [16].
Stavy a čísla v sekci 9.

**„Co bychom udělali jinak" jako součást žánru.** Case study bez retrospektivy je prezentace.
Kapitola má v 24.07 pět kompromisů, ale všech pět dopadlo dobře a žádný se zpětně nerevidoval.
Chybí hranice vedená špatně, agregát, který se ukázal jako příliš velký, událost, kterou nikdo
nekonzumoval. Kniha přitom má kapitolu 20 `/ddd-v-praxi-kde-to-boli` s dvaceti takovými problémy –
kapitola 24 na ni neodkazuje.

## 4. Symfony / PHP specifika

**`#[ORM\Entity(readOnly: true)]` (řádek 1124) je věcná chyba.** Dokumentace Doctrine ORM [8]:
„Specifies that this entity is marked as read only and **not considered for change-tracking**.
Entities of this type can be persisted and removed though." Důsledek: `onMemberAdded()`,
`onMemberRemoved()` a `onTaskCreated()` mění načtenou instanci `ProjectListView` a volají `flush()`,
ale UnitOfWork pro tuto entitu changeset nepočítá – UPDATE se nevygeneruje; stejně tichá je opravná
větev reconcileru. Funguje jen `persist()`. Označení „read model" k `readOnly: true` svádí, ale
zápisovou stranou téhle tabulky je právě projekce.

**`#[ORM\Index(flags: ['gin'])]` (řádek 1123) na PostgreSQL nic nedělá.** Atribut `Index`
parametr `flags` má, ale `PostgreSQLPlatform` v DBAL 3.10.x ani 4.4.x nepřepisuje
`getCreateIndexSQLFlags()`; základní implementace v `AbstractPlatform` vrací jen `'UNIQUE '` nebo
prázdný řetězec (`AbstractMySQLPlatform` přepsání má) [17]. Vygenerované DDL bude běžný B-tree
index nad `jsonb` sloupcem, který dotaz s `@>` nepoužije – výkonnostní pointa celé sekce 24.06 tím
padá. Řešení je ruční migrace `CREATE INDEX … USING gin (member_ids)`.

**`Types::JSONB` existuje – od DBAL 4.3.0.** Poznámka na řádku 1110 je správná. Verze 4.3.0
(2025-07-10) navíc v `UPGRADE.md` říká: „The `jsonb` column platform option has been deprecated.
To define a `JSONB` column, use the `JSONB` type instead." [17] Kapitola tedy jako hlavní variantu
ukazuje postup, který je od 4.3 deprecated, a doporučenou odsouvá do závorky. V DBAL 3.x je
`options: ['jsonb' => true]` jediná cesta a ORM 3 povoluje `^3.8.2 || ^4`, takže obojí je
legitimní – kapitola má jen říct, která verze čemu odpovídá.

**`public readonly` na Doctrine entitě má nevyslovenou podmínku.** `Project` má
`public readonly ProjectId $id` i `public readonly UserId $ownerId`. Dokumentace ORM [18]: „An
entity class can be final or read-only when you use native lazy objects." Ty se zapínají
`$config->enableNativeLazyObjects(true)` na PHP 8.4 a ORM je od 3.5 doporučuje. Kapitola podmínku
nezmiňuje a přímo si protiřečí: komentář na řádku 383 („ne final – Doctrine proxy z entity dědí")
popisuje starý režim generovaných proxy, ve kterém by `readonly` vlastnosti téže třídy nefungovaly.
Buď native lazy objects (pak může být `final` a `readonly` platí), nebo staré proxy (pak `readonly`
padá) – kapitola má obojí najednou, a uvnitř téže kapitoly navíc stojí `final class Task` (řádek
543), podle stromu také persistovaný. Totéž eviduje `implementation_in_symfony-studie.md` jako G1.

**Messenger.** `#[AsMessageHandler]` na metodě je platné –
`#[\Attribute(TARGET_CLASS | TARGET_METHOD | IS_REPEATABLE)]` [22]; atribut má navíc parametr
`handles` pro případ, kdy se type-hint uhodnout nedá, což kapitola u svého tvrzení o „obecném
type-hintu `object`" nezmiňuje. Výchozí `max_retries: 3` uvádí správně [20]. Neuvádí ale, že
selhaný `flush()` uzavírá EntityManager (provoz projekcí proto potřebuje `doctrine_close_connection`
/ `doctrine_ping_connection` [20]), ani že Messenger má od 7.3 `DeduplicateMiddleware` a
`DeduplicateStamp`, v 8.1 doplněné o `ReleaseDeduplicationLockOnFailureListener` – tedy přesně
nástroj, který 24.06.4 popisuje ručně (`cqrs-studie.md` G9). Navrhovaný `last_event_id` je navíc
neproveditelný: **žádná z pěti tříd událostí nenese vlastní identifikátor**, jen `occurredAt`.

**Drobnosti.** `extends Command` je od Symfony 7.3 „legacy syntax" – dokumentace [19] doporučuje
invokable commands, formální deprecation ale neproběhla. `match ([$this, $next])` v
`TaskStatus::canTransitionTo()` funguje (`match` porovnává `===`, pole po prvcích, případy enumu
jsou singletony; ověřeno `php -r` [24]), je ale neobvyklé a nekomentované. `member_ids @> :userId`
spoléhá na odvození typu parametru; `:userId::jsonb` je robustnější. `#[ORM\Version]` odpovídá
konvenci knihy, ale žádný handler verzi nepředává do `find()` a `OptimisticLockException` se
neošetřuje, ač 24.07.5 argumentuje zámky.

## 5. Sporné a chybně podávané body

**5.1 Směr vztahu TaskManagement ⟷ CommentManagement.** Text (řádky 165–167) říká, že
CommentManagement je upstream („vystavuje API pro komentáře nad úkoly, TaskManagement je downstream
zákazník"). Zdroj diagramu (`templates/diagrams/15_case_study/context_map.puml`) má `TM -> CM`
a legendu „Upstream určuje kontrakt, downstream se přizpůsobuje"; předchozí šipka `PM -> TM` fixuje
směr jako upstream → downstream. Šipka tedy říká opak než text. Věcně dává smysl šipka: komentář
drží `TaskId`. **Doporučení:** opravit text, ne diagram.

**5.2 Shared Kernel, který se tak nejmenuje.** Diagram obsahuje package `Shared Kernel`
s `UserId / ProjectId / TaskId`. Text termín „Shared Kernel" **neobsahuje ani jednou** (ověřeno
`grep -ci`); mluví o „sdílených identifikátorech" (řádky 176–180) a nadpis 24.07.2 zní „Sdílené
identifikátory vs. duplikace", ačkoli jeho anchor je `#trade-off-shared-kernel-heading`. Text navíc
popisuje jiný vzor než diagram: „IDs žijí ve vlastnických kontextech a ostatní kontexty tyto
hodnotové objekty importují" je jednosměrná závislost na doménové vrstvě cizího kontextu, kdežto
Shared Kernel je podle Evanse [1] společně vlastněná podmnožina modelu se závazkem obou týmů.
Kniha má na Shared Kernel sekci `03.04`, na kterou se neodkazuje. **Doporučení:** rozhodnout, který
ze dvou vzorů je použit, pojmenovat ho a odkázat.

**5.3 „Port plní funkci ACL i tam, kde se nepřekládají typy."** (řádky 189–196.) Evansův ACL je
definován překladem mezi dvěma modely; vrstva bez translace je dependency inversion, tedy port
a adaptér. Kapitola sama přiznává, že se nic nepřekládá, a přesto vzor nazve ACL
(`context_mapping-studie.md` řeší příbuzný problém jako G1: ACL, OHS a PL jsou role na konci
vztahu, ne typy vztahů). **Doporučení:** nazvat věc portem; ACL jako to, čím se stane, až přibude
překlad.

**5.4 OHS / Published Language nad doménovými typy.** Kapitola označuje integraci
s ActivityTracking za OHS/PL (řádky 172–175), ale publikované události nesou `ProjectId`, `UserId`,
`TaskId` a `TaskStatus` – interní doménové typy vydávajícího kontextu. Smysl Published Language je
opačný: samostatný výměnný formát, aby doménový model nezmrzl na konzumentech (Verraes [12], .NET
eBook [11]). Kapitola tak ilustruje anti-vzor pod jménem vzoru. **Doporučení:** buď zavést tenkou
integrační událost s primitivy a verzí, nebo vztah přeznačit na „doménové události na sdílené
sběrnici" a rozdíl proti PL pojmenovat, s citací [11] a [12].

**5.5 UserManagement jako plnohodnotný vlastní kontext.** Kapitola 02 má warn callout
„Ilustrativní scénář: custom auth jako rozpočtová past" (`subdomains.md:139`) se závěrem
„autentizace je **Generic subdoména** u 99 % organizací" a tabulku (`subdomains.md:170`) mapující
Identity/Auth na externí IdP přes ACL. Případová studie staví registraci, autentizaci a heslo jako
vlastní bounded context, importuje jeho `UserId` do všech ostatních a nikde nezmíní, že jde proti
doporučení vlastní kapitoly; Vernon [2] v analogické doméně dělá totéž co kapitola 02.
**Doporučení:** buď volbu obhájit (kontext je potřeba, aby vznikl vztah v mapě), nebo ji převést
na Generic + ACL. Obojí je legitimní; mlčení není.

**5.6 Doménová služba, která jen deleguje.** `TaskAssignmentService::assignTask()` volá
`Task::assign()` a nic víc; 24.07.4 ji obhajuje jako „místo pro rozšíření". Kapitola 10
(`implementation_in_symfony.md:1010`) přitom říká: „Před sáhnutím po doménové službě stojí vždy
jedna otázka: nepatří to do agregátu? … Domain service na to je anti-vzor, který oslabuje agregát
a vede k anemickému modelu." Kniha si tu protiřečí. **Doporučení:** buď službu odstranit, nebo na
10.09 odkázat a vysvětlit, proč tady výjimka platí.

**5.7 Události zaznamenané v `__construct`.** `Project::__construct()` (řádek 401) a
`Task::__construct()` (řádek 570) volají `record()`. `CLAUDE.md`: „Events are recorded in named
constructors / domain methods, never in `__construct` (reconstitution must not emit events)."
Kanonická `Order::place()` (`basic_concepts.md:576`) nejdřív vytvoří instanci a teprve pak volá
`record()`; `Project::create()` i `Task::create()` jsou tu prázdné wrappery nad `new self()`.
Doctrine při rekonstituci konstruktor nevolá, takže chyba je latentní – konvence ji přesto zakazuje
a nejdelší souvislá ukázka v knize je nejhorší místo pro porušení.

**5.8 Idempotence, která není idempotencí.** 24.06.4 tvrdí, že `onProjectCreated` je idempotentní,
protože „při kolizi PK skončí výjimkou – tu Messenger podle retry strategie několikrát zopakuje
a zprávu pak odloží na failure transport". To je popis selhání a jeho zametení; idempotentní by byl
upsert. **Doporučení:** opravit kód na upsert, nebo tvrzení přeformulovat a přiznat kompromis.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | nepodložené | `case_study.md:23` vs. `:16`, `:1565`–1579 | „Ilustrativní scénář" stojí jen v úvodním odstavci; `deck` i `meta_description` prodávají „kompletní projekt", FAQ mluví o výsledcích a renderuje se jako `FAQPage` JSON-LD | Označení zopakovat v decku, v úvodu 24.08 a v první FAQ odpovědi |
| G2 / G3 | nepodložené | `:1546`, `:1560`, `:1561`, `:1574` | „odezvu výpisu pod 50 ms" a „rozdíl mezi 5 ms a 200 ms" jsou měřená data u smyšleného projektu; „Z provozu vyplynulo deset bodů" a „Workshop trval dva dny" podávají fikci jako zkušenost | Čísla nahradit řádovým tvrzením nebo doložit měřením; 24.08 přerámovat na „co z tohoto návrhu plyne" |
| G4 | chybí | sekce 24.03 | Subdomény nejsou klasifikované na Core / Supporting / Generic; Core Domain Charts [7] ani kapitola 02 se nezmiňují | Nová podsekce ~25 ř. mezi krok 2 a krok 3 + odkaz na `/subdomeny` |
| G5 | sporné | `:139`–143 vs. `subdomains.md:139`, `:170` | UserManagement postaven vlastní, ač kap. 02 označuje custom auth za Generic a za „drahý anti-vzor" | Buď volbu obhájit odstavcem, nebo převést na Generic + ACL (5.5) |
| G6 | sporné | `:165`–167 vs. `context_map.puml` | Text a šipka diagramu určují opačný upstream u TaskManagement ⟷ CommentManagement | Opravit text podle diagramu (5.1) |
| G7 | chybí | celá kapitola | Odkazy vedou jen na 9 kapitol; chybí `/event-storming` (ač 24.03 dělá ES), `/context-mapping` (ač 24.04 kreslí mapu), `/subdomeny`, `/outbox-pattern`, `/navrh-agregatu`, `/prakticke-priklady`, `/ddd-a-microservices`, `/ddd-v-praxi-kde-to-boli` | Doplnit odkaz na konci každé sekce („vzor zavedla kapitola X") |
| G8 | sporné | `:176`–180, `:816`–822, sekce 24.07.2 | Diagram používá termín Shared Kernel, text ne, a popsaný mechanismus je jiný vzor | Pojmenovat vzor a odkázat na `/context-mapping#shared-kernel` (5.2) |
| G9 | sporné | `:189`–196 | Port bez translace pojmenován ACL | Nazvat portem; ACL jako budoucí stav (5.3) |
| G10 | sporné | `:172`–175 vs. `:660`–760 | OHS / Published Language deklarovaný nad událostmi, které nesou interní doménové VO | Zavést integrační událost, nebo vztah přeznačit; citovat [11], [12] (5.4) |
| G11 | chybí | sekce 24.03 | Z event stormingu zůstal jen seznam vět; žádný artefakt (pivotal events, hot spots, policies, agregáty), názvosloví neodpovídá kap. 04 | Sjednotit názvosloví s kap. 04 a doplnit alespoň obrázek boardu |
| G12 / G13 | chybí | sekce 24.02, celá kapitola | Krok *Understand* [7] (business model, uživatelé, cíle – kapitola má osm funkčních odrážek) a krok *Organise* [7] (jeden tým, žádný Conwayův rozměr, žádný odkaz na kapitolu Team Topologies) | ~15 ř. o produktu a ~10 ř. o týmech + odkaz |
| G14 | chybí | sekce 24.04 | Krok *Connect* [7] doporučuje Domain Message Flow Modelling; mapa ukazuje typy vztahů, ne tok zpráv v use casu | Diagram toku zpráv pro „přiřazení úkolu" |
| G15 | **chybí** | `:891`–899, celá 24.05 | `releaseEvents()` se v kapitole nevolá ani jednou; nikde není vidět, kdo událost publikuje, kdy se commituje transakce ani na jaký bus se odesílá – a celá 24.06 na tom stojí | Doplnit handler podle `basic_concepts.md:600`–612 (save → flush → releaseEvents → dispatch); podpora [21] |
| G16 | **zastaralé** | `:1124` | `#[ORM\Entity(readOnly: true)]` vypíná change-tracking [8]; tři ze čtyř metod projekce a opravná větev reconcileru se tiše neuloží | Odstranit `readOnly: true` |
| G17 | **nepodložené** | `:1123` | `flags: ['gin']` nemá v Doctrine DBAL na PostgreSQL žádný efekt – `PostgreSQLPlatform` `getCreateIndexSQLFlags()` nepřepisuje [17]; vznikne B-tree index, který `@>` nepoužije | Vytvořit GIN index ruční migrací a v textu to říct |
| G18 | zastaralé | `:1109`–1111 | `options: ['jsonb' => true]` je od DBAL 4.3.0 deprecated ve prospěch `Types::JSONB`; kapitola má pořadí obráceně [17] | Prohodit: `Types::JSONB` jako hlavní, `options` jako varianta pro DBAL 3.x |
| G19 | sporné | `:383` vs. `:388`, `:543` | Komentář „ne final – Doctrine proxy z entity dědí" popisuje starý režim, ve kterém `public readonly` vlastnosti téže třídy nefungují; `readonly` i `final class Task` předpokládají native lazy objects [18] | Sjednotit na native lazy objects (ORM 3.5+, PHP 8.4) a komentář přepsat |
| G20 | sporné | `:401`, `:570` | `record()` v `__construct` proti výslovnému pravidlu `CLAUDE.md` i proti `basic_concepts.md:576` | Přesunout `record()` do `create()` |
| G21 | sporné | `:794`, `:834`, `:894` | `new ProjectId()` s magickým `''` defaultem; zbytek knihy má `XxxId::generate()` (18 výskytů v 10 kapitolách) | Sjednotit na `ProjectId::generate()`; týž nález má `cqrs-studie.md` G11 |
| G22 | sporné | `:470`, `:616`, `:971`, `:975` | Čtyřikrát `\DomainException`, ač strom deklaruje `SharedKernel/Domain/Exception/DomainException.php` a `CLAUDE.md` vyžaduje pojmenované výjimky; „Úkol nebyl nalezen" navíc není doménové pravidlo | Zavést `ProjectOwnerCannotBeRemovedException`, `InvalidTaskStateTransitionException`, `TaskNotFoundException` |
| G23 | chybí | `:523`–653 | `Task` nemá jediný mapovací atribut, `Project` má plnou sadu | Doplnit mapping, nebo u obou uvést jen doménovou část a mapping odkázat do kap. 10 |
| G24 | sporné | `:1316`–1327 | Tvrzení o idempotenci `onProjectCreated` popisuje selhání + retry + DLQ; navrhovaný `last_event_id` je neproveditelný, protože události nemají ID; `DeduplicateMiddleware` (7.3) a `ReleaseDeduplicationLockOnFailureListener` (8.1) se nezmiňují [20] | Upsert, `eventId` do všech pěti tříd událostí, odstavec o deduplikačním middleware |
| G25 / G26 | mělké | `:1341`–1414 | Reconciler prochází `$this->projects->all()` (hydratace všech agregátů bez dávkování a `clear()`; `aggregate_design.md:498` doporučuje repozitáře bez obecných výběrových metod) a nedorovnává `taskCount`, `ownerId`, `createdAt` ani sirotčí view; nad `ORDER BY updated_at` není index | Dávkovat po N, `clear()`, `--limit`, `--dry-run`; doplnit výčet toho, co reconciler vědomě neřeší |
| G27 | sporné | `:520`–522 | `rename()` a `changeDescription()` neemitují událost a drift se „dorovná reconcilerem" | Buď doplnit `ProjectRenamed`, nebo z toho udělat šestý explicitní trade-off |
| G28 | chybí | `:1436`–1441, `:1456` | Warn callout označuje outbox za předpoklad, ale odkazuje na `/event-sourcing`; kniha má kapitolu 15 `/outbox-pattern`. 24.07.1 mluví o „replay z outbox tabulky", která v kapitole neexistuje | Opravit odkaz a přiznat, že outbox není součástí ukázky |
| G29 | sporné | sekce 24.07.4 vs. `implementation_in_symfony.md:1010` | Obhajoba delegující doménové služby proti explicitnímu anti-vzoru z kap. 10 | Viz 5.6 |
| G30 | chybí | `:416`–418 | `#[ORM\Version]` deklarován, ale žádný handler verzi nepředává a `OptimisticLockException` se neošetřuje, ač 24.07.5 argumentuje zámky | Doplnit do `AssignTaskHandler`, nebo verzní sloupec vypustit |
| G31 / G32 | chybí | celá kapitola | Žádná autorizace (kdo smí přidat člena, kdo přiřadit úkol; kniha má kapitolu 11 `/autorizace-v-ddd`), žádný test, repozitář ani kontroler – přitom ponaučení 7 (`:1559`) mluví o testovací strategii | Odstavec o autorizaci + jeden test agregátu a jeden test projekce (~50 ř.) |
| G33 / G34 | nadbytečné + mělké | `:181`–347, `:158`–160 | Adresářový strom 167 řádků = 11 % kapitoly a větev `UserManagement` opakuje `practical_examples.md:213`–226; zároveň jediný diagram na 1579 řádků, bez sekvence command → agregát → událost → projekce → reconciler | Strom zkrátit na dva kontexty; uvolněné místo dát sekvenčnímu diagramu ve 24.06 |
| G35 | chybí | sekce 24.07, 24.08 | Žádné rozhodnutí se neukázalo jako chybné; chybí „co bychom dnes udělali jinak" | Nová podsekce ~30 ř. s odkazem na `/ddd-v-praxi-kde-to-boli` |
| G36 | chybí | konec kapitoly | Poslední kapitola knihy nemá „Další četba" ani jediný bibliografický odkaz; Brandolini je jmenován bez zdroje (`:49`) | Sekce ~20 ř.: Evans, Vernon (`iddd_agilepm`), Khononov, ddd-crew, CodelyTV |
| G37 / G38 | sporné + mělké | `:891`–899, `:940`–950, `:1281`–1285 | `CreateProjectHandler` generuje ID uvnitř a vrací `string`, čímž vynucuje synchronní bus, a kapitola to nekomentuje; `member_ids @> :userId` je bez `::jsonb` castu; rozhraní `ProjectChecker` je jen v docblocku | Zmínit ID generované volajícím (`cqrs-studie.md` G19), doplnit cast, `ProjectChecker` vypsat jako blok |
| G39 | zastaralé | `:1341`–1350 | `ReconcileProjectListView extends Command` – od Symfony 7.3 „legacy syntax", doporučené jsou invokable commands [19] | Přepsat na `__invoke`, nebo volbu jednou větou zdůvodnit |

## 7. Doporučení k přepisu

**P1-1 — Opravit persistenci read modelu: `readOnly: true` pryč, GIN index ručně.** `readOnly: true`
vypíná change-tracking, takže tři ze čtyř metod projekce a opravná větev reconcileru se tiše
neprovedou; `flags: ['gin']` v Doctrine na PostgreSQL GIN index negeneruje, takže dotaz
`member_ids @> …` skončí seq scanem. Kapitola publikuje kód, který nefunguje, a ještě u něj tvrdí
výkonnostní přínos. Doloženo [8] a [17]. Rozsah: `oprava dvou řádků + ~10 řádků` (G16, G17).

**P1-2 — Doplnit chybějící článek řetězu: `releaseEvents()` a dispatch.** Sekce 24.06 stojí na tom,
že agregáty publikují události, ale ve 24.05 není vidět, kdo je vyzvedne, kdy se commituje
transakce a na jaký bus se odesílá. Čtenář, který kapitolu čte jako referenci, dostane systém,
který nikdy nic nepublikuje. Vzor je v knize hotový (`basic_concepts.md:600`–612) a shoduje se
s Nobackem [21]. Rozsah: `přepis dvou handlerů, ~25 řádků` (G15).

**P1-3 — Přesunout `record()` z konstruktorů do factory metod.** `CLAUDE.md` to zakazuje výslovně
a kanonická `Order::place()` dělá pravý opak. Nejdelší souvislá ukázka v knize porušuje konvenci,
kterou kniha vyžaduje všude jinde. Rozsah: `oprava čtyř míst ve dvou ukázkách` (G20).

**P1-4 — Doplnit klasifikaci subdomén a vyrovnat se s UserManagement.** Bez kroku *Strategize* [7]
dostane každý z pěti kontextů stejnou investici do modelu – přesně to, co kapitola 02 pojmenovává
jako anti-vzor „všechno je Core". Kapitola navíc staví vlastní autentizaci, kterou tatáž kapitola
označuje za drahý anti-vzor u 99 % organizací. Rozsah: `nová podsekce ve 24.03, ~25 řádků` (G4, G5).

**P1-5 — Opravit kontextovou mapu.** Obrácený upstream u TaskManagement ⟷ CommentManagement (text
proti diagramu), Shared Kernel použitý v diagramu a nepojmenovaný v textu, OHS/PL deklarovaný nad
událostmi s interními doménovými typy. Mapa je hlavní strategický výstup kapitoly a její jediný
diagram; chyba se propaguje do celé druhé poloviny.
Rozsah: `přepis řádků 160–180, ~20 řádků` (G6, G8, G10).

**P1-6 — Sjednotit označení fiktivnosti a odstranit nedoložené metriky.** „Ilustrativní scénář"
stojí jen v jednom odstavci; `deck`, `meta_description`, sekce 24.08 („z provozu") i FAQ mluví jako
o proběhlém projektu. FAQ se navíc renderuje jako `FAQPage` JSON-LD, takže se „investice se
mnohonásobně vyplatila" a čísla „50 ms" / „5 ms vs. 200 ms" dostávají do strukturovaných dat
stránky. Rozsah: `oprava decku, dvou vět a dvou FAQ odpovědí` (G2 / G3, G1).

**P1-7 — Rozhodnout režim Doctrine a sjednotit kód s kanonickým API.** Kapitola má na jedné entitě
`public readonly` vlastnosti (vyžadují native lazy objects) a zároveň komentář o dědičnosti proxy
(starý režim), a na druhé `final class` bez mappingu. K tomu `ProjectId::generate()` místo
`new ProjectId()` a pojmenované výjimky místo čtyř `\DomainException`.
Rozsah: `oprava ~20 řádků v pěti ukázkách` (G19, G21, G22, G23).

**P1-8 — Odkázat každou sekci zpět na kapitolu, která vzor zavedla.** Kapitola dělá event storming
bez odkazu na kapitolu 04, kreslí kontextovou mapu bez odkazu na kapitolu 03 a opírá projekci
o outbox s odkazem na kapitolu 13 místo na kapitolu 15. Syntetická kapitola, která neodkazuje na
to, co syntetizuje, není syntéza. Rozsah: `~10 odkazů` (G7, G28).

**P2-1 — Doplnit sekci „Co bychom dnes udělali jinak".** Pět kompromisů v 24.07 je nejlepší část
kapitoly, ale všech pět dopadlo dobře. Případová studie bez jediného špatného rozhodnutí čte jako
prezentace. Kandidáti jsou přímo v textu: chybějící `ProjectRenamed` (dnes zalepené reconcilerem),
doménová služba bez obsahu, sdílené `UserId`. Rozsah: `nová podsekce ~30 řádků` (G35, G27).

**P2-2 — Doplnit `eventId` a přepsat pasáž o idempotenci.** Sloupec `last_event_id`, který kapitola
navrhuje, s aktuálními třídami událostí nejde naplnit; `public string $eventId` odemkne deduplikaci
i korelaci v logu. Zároveň přeformulovat tvrzení o idempotenci `onProjectCreated`, které dnes
popisuje selhání, a zmínit `DeduplicateMiddleware` (Messenger 7.3) [20]. Rozsah: `~25 řádků` (G24).

**P2-3 — Zpevnit reconciler.** Dávkování, `EntityManager::clear()`, `--dry-run` a `--limit`, plus
výčet toho, co nedorovnává. V současné podobě se na tisících projektů, o kterých mluví ponaučení 8,
zadusí pamětí. Rozsah: `přepis execute(), ~30 řádků` (G25, G26).

**P2-4 — Zkrátit adresářový strom a doplnit sekvenční diagram.** 167 řádků stromu nese málo
informace na řádek a větev `UserManagement` opakuje kapitolu 23; uvolněné místo použít na sekvenci
command → agregát → událost → projekce → reconciler. Rozsah: `−90 řádků + diagram` (G33 / G34).

**P2-5 — Doplnit „Další četba".** Poslední kapitola knihy nemá jediný bibliografický odkaz.
Minimálně Evans a `citerus/dddsample-core`, Vernon a `iddd_agilepm` (stejná doména), Khononov,
`ddd-crew/ddd-starter-modelling-process` a jedna spustitelná PHP reference.
Rozsah: `nová sekce ~20 řádků` (G36).

**P2-6 — Doplnit krok *Understand* a rozměr týmů.** Čím se produkt liší a proč vzniká (dnes jen
osm funkčních odrážek), a jak by pět kontextů vypadalo při dvou a při pěti týmech; obojí kniha
pokrývá vlastními kapitolami. Rozsah: `~25 řádků` (G12 / G13).

**P3 (dohromady ~90 řádků).** U `jsonb` prohodit pořadí: `Types::JSONB` (DBAL 4.3+) jako hlavní
varianta, `options: ['jsonb' => true]` jako fallback pro DBAL 3.x s poznámkou o deprecation [17]
(G18). Dokončit řez ukázek: `Task` mapping, `ProjectChecker` jako kód, `::jsonb` cast, test agregátu
a test projekce, odstavec o autorizaci, přepis reconcileru na invokable command (G23, G31 / G32,
G37 / G38, G39). Doplnit Domain Message Flow Modelling [7] pro „přiřazení úkolu" – jeden obrázek
nahradí to, co kapitola vysvětluje na třech místech textem (G14).

## 8. Otevřené otázky pro autora

1. **Referenční implementace, nebo zpráva z návrhu?** Po zkrácení stromu a doplnění P1/P2 vyjde
   podobný rozsah, ale s jiným rozložením: méně kódu, víc rozhodování. To je vědomá změna žánru.
2. **Držet pět bounded contextů, nebo tři?** Pět kontextů pro systém na správu úkolů je na hraně
   over-engineeringu a kapitola 22 `/kdy-nepouzivat-ddd` na to má kritéria. Tři (Identity jako
   Generic, ProjectManagement, TaskManagement) by kapitolu zkrátily a daly příležitost ukázat
   sloučení jako doložené rozhodnutí.
3. **Custom auth: obhájit, nebo přepsat?** Přepis na Generic + externí IdP vyřeší G5 i G8 naráz
   (sdílené `UserId` by se stalo překladem v ACL), ale ubere jeden kontext a jeden vztah v mapě.
4. **Který režim Doctrine kniha předpokládá?** Native lazy objects (ORM 3.5+, PHP 8.4) mění odpověď
   na `final` i na `readonly` vlastnosti v entitách. Rozhodnutí patří do kapitoly 10, ale kapitola
   24 je místo, kde se nekonzistence projeví nejviditelněji.
5. **Kolik prostoru dát testům?** Ponaučení 7 mluví o testovací strategii, aniž by kapitola ukázala
   jediný test. Buď test doplnit, nebo ponaučení přeformulovat na odkaz na kapitolu 17.
6. **Zůstane FAQ v současné podobě?** Odpovědi se renderují do `FAQPage` JSON-LD a čtou se jako
   výsledky reálného projektu. Alternativa: přepsat je na otázky o *návrhu*, ne o *výsledcích*.
7. **Odkázat na cizí open-source projekt, nebo pořídit vlastní?** Repozitář s kódem z kapitoly by
   byl nejsilnější závěr knihy, ale je to závazek na údržbu.

## 9. Bibliografie

### Ověřené zdroje

Knihy (bez čísel stránek podle konvence z `CLAUDE.md`):

- **[1]** Eric Evans — *Domain-Driven Design*. Addison-Wesley, 2003. Shared Kernel, ACL, OHS,
  Published Language, průběžný příklad lodní přepravy.
- **[2]** Vaughn Vernon — *Implementing Domain-Driven Design*. Addison-Wesley, 2013. SaaSOvation
  a tři kontexty, mezi nimi Agile Project Management – doména shodná s touto kapitolou.
- **[3]** Eric Evans — *Domain-Driven Design Reference*, 2015. Podle `CLAUDE.md` zdroj pro Partnership.
- **[4]** Vlad Khononov — *Learning Domain-Driven Design*. O'Reilly, 2021. Klasifikace subdomén
  (v knize citováno v `subdomains.md:158`).

Repozitáře, dokumentace a weby (datum přístupu 2026-09-04; způsob získání v závorce):

- **[5]** `citerus/dddsample-core` — 5 290 hvězd, push 2025-06-02, MIT; **[6]** `VaughnVernon/IDDD_Samples`
  — 3 943 hvězd, push 2023-09-09, adresáře `iddd_identityaccess`, `iddd_collaboration`,
  `iddd_agilepm`; **[9]** `ddd-crew/bounded-context-canvas` — 2 050 hvězd, CC-BY-SA-4.0.
  (GitHub REST API přes `curl`, u [6] i výpis kořene přes `/contents/`)
- **[7]** `ddd-crew/ddd-starter-modelling-process` — osm kroků Understand → Discover → Decompose →
  Strategize → Connect → Organise → Define → Code; nástroje Core Domain Charts, Domain Message Flow
  Modelling, Bounded Context Canvas. 6 007 hvězd, push 2026-08-23, CC-BY-SA-4.0. Doslovné citace
  popisů kroků odtud. (GitHub REST API + `curl` na raw `README.md`)
- **[8]** Doctrine ORM — *Attributes Reference*, `readOnly` u `#[Entity]`: „Specifies that this
  entity is marked as read only and not considered for change-tracking. Entities of this type can
  be persisted and removed though." (`WebFetch` na doctrine-project.org + `curl` na raw
  `docs/en/reference/attributes-reference.rst`; parametr ověřen i v `src/Mapping/Entity.php`)
- **[10]** Martin Fowler — *BoundedContext* (bliki): „Usually the dominant one is human culture,
  since models act as Ubiquitous Language, you need a different model when the language changes."
  (`WebFetch`, martinfowler.com/bliki/BoundedContext.html)
- **[11]** Microsoft — *.NET Microservices Architecture*, „Domain events: Design and
  implementation": „Semantically, domain and integration events are the same thing… their
  implementation must be different… Domain events can generate integration events to be published
  outside of the microservice boundaries." (`WebFetch`, learn.microsoft.com)
- **[12]** Mathias Verraes — *Patterns for Decoupling in Distributed Systems: Explicit Public
  Events* (2019): veřejná má být jen explicitně označená podmnožina událostí. (`WebFetch`,
  verraes.net/2019/05/patterns-for-decoupling-distsys-explicit-public-events/)
- **[13]** `CodelyTV/php-ddd-example` — 3 148 hvězd, push 2024-08-06, bez licence; struktura
  `src/<BC>/<Module>/{Application,Domain,Infrastructure}`; `AggregateRoot` s `record()` a
  `pullDomainEvents()`. **[14]** `jorge07/symfony-7-es-cqrs-boilerplate` — 1 088 hvězd, push
  2026-08-09 (dřívější název `symfony-6-…`). **[15]** `dddshelf/last-wishes` — 655 hvězd, push
  2019-05-01; organizace `dddinphp` pod tímto jménem neexistuje. **[16]** Packagist: `symfony/uid`
  347 254 686 stažení, `ramsey/uuid` 782 040 000, `ecotone/ecotone` 606 825,
  `patchlevel/event-sourcing` 432 414 – žádný `abandoned`; `broadway/broadway` archivovaný.
  (GitHub REST API vč. redirectů + `curl` na raw README, `AggregateRoot.php` a packagist.org)
- **[17]** Doctrine DBAL: `PostgreSQLPlatform` v 3.10.x ani 4.4.x nepřepisuje
  `getCreateIndexSQLFlags()` (na rozdíl od `AbstractMySQLPlatform`), takže `flags: ['gin']` nemá
  vliv na DDL; `Types::JSONB` přibylo v **4.3.0** (2025-07-10) a `UPGRADE.md` téže verze označuje
  option `jsonb` za deprecated; `getJsonTypeDeclarationSQL()` vrací `'JSONB'` při neprázdné option.
  (`curl` na raw zdroje obou větví + GitHub REST API `releases/tags/4.3.0`)
- **[18]** Doctrine ORM — `architecture.rst`: „An entity class can be final or read-only when you
  use native lazy objects."; `advanced-configuration.rst`: „With PHP 8.4 we recommend that you use
  native lazy objects… `$config->enableNativeLazyObjects(true);`"; `UPGRADE.md` (3.5) označuje
  jejich nepoužití na PHP 8.4+ za deprecated. (`curl` na raw `doctrine/orm/HEAD`)
- **[19]** Symfony — *Console*, „Legacy Syntax to Define Commands": „Both syntaxes are supported,
  but invokable commands are recommended." Invokable commands od 7.3. (`curl` + CHANGELOG)
- **[20]** Symfony — *Messenger*: „a message will be retried 3 times before being discarded or sent
  to the failure transport"; middleware `doctrine_ping_connection`, `doctrine_close_connection`,
  `doctrine_transaction`; `DeduplicateMiddleware` a `DeduplicateStamp` od 7.3,
  `ReleaseDeduplicationLockOnFailureListener` od 8.1. (`curl` + CHANGELOG komponenty)
- **[21]** Matthias Noback — *Collecting events and the event dispatching command bus* (2015),
  doplňkově *Doctrine ORM and DDD aggregates* (2018) a *DDD and your database* (2020): sbírat
  události v paměti, dispatchovat po commitu. (`WebFetch`, matthiasnoback.nl)

Lokálně ověřeno proti `vendor/` v tomto repozitáři: **[22]** `symfony/messenger` v8.0.7,
`Attribute/AsMessageHandler.php` — `#[\Attribute(TARGET_CLASS | TARGET_METHOD | IS_REPEATABLE)]`,
parametry `bus`, `fromTransport`, `handles`, `method`; **[23]** `symfony/console` v8.0.7,
`Attribute/AsCommand.php` — `#[\Attribute(TARGET_CLASS)]`; **[24]** chování `match` nad polem
dvou enum případů ověřeno spuštěním `php -r` na PHP 8.4.

Interní zdroje knihy: **[25]** `CLAUDE.md` (record v named constructors, pojmenované výjimky,
`Uuid::v7()`, události bez sufixu „Event"); **[26]** `basic_concepts.md:531`–619 (lifecycle
agregátu, pořadí save → flush → dispatch); **[27]** `subdomains.md:139`–180 (custom auth jako
Generic, mapování subdomén); **[28]** `implementation_in_symfony.md:1003`–1015 (doménová služba pro
invariant jednoho agregátu jako anti-vzor); **[29]** `aggregate_design.md:496`–502 (repozitář jen
s `get()` / `save()`); **[30]** studie `cqrs-studie.md` (G9, G11, G19), `basic_concepts-studie.md`
(G8, G13, G16), `implementation_in_symfony-studie.md` (G1, G15, G20), `context_mapping-studie.md`
(G1, G17), `event_storming-studie.md` (G25).

### Neověřené / nedohledané

- **Verze ORM s `enableNativeLazyObjects()` – DOHLEDÁNO 2026-09-04. Je to 3.4.0, ne 3.5.**
  Metoda přibyla v PR [#11853](https://github.com/doctrine/orm/pull/11853) *Add support for PHP 8.4
  Lazy Objects RFC with configuration flag* (commit 29. 3. 2025) a vyšla v **ORM 3.4.0 dne
  14. 6. 2025**, kde je i v release notes. V **3.5.0** k tomu přibyla deprecace: nepoužívat nativní
  lazy objekty na PHP 8.4+ je od té verze zastaralé. `UPGRADE.md` mluví o „3.5 a výše“ proto, že
  popisuje ten deprecation krok, ne zavedení metody. **Oprava G19: psát „ORM 3.4+“ pro dostupnost
  a „od 3.5 je starý režim deprecated“ jako samostatnou větu.**
- **Verzování veřejných událostí jako kontraktu – DOVĚŘENO 2026-09-04, původní závěr platí.**
  Verraesův katalog *DDD and Messaging Architectures* (verraes.net/2019/05/ddd-msg-arch/) prošel
  celý: verzování, zpětnou kompatibilitu ani „událost jako kontrakt“ neřeší nikde. Dva jeho vzory
  jsou ale pro P1-5 použitelné a doložitelné: **Explicit Public Events** (malá podmnožina událostí
  je vědomě veřejná, zbytek je privátní by default) a **Segregated Event Layers**. Ty pokrývají
  *oddělení* veřejného kontraktu; samotné *verzování* u Verraese není a je nutné je opřít o Younga
  (*Versioning in an Event Sourced System*). **Doporučení: v P1-5 tyto dva zdroje rozdělit podle
  role, ne citovat Verraese na verzování.**
- **Chování `@>` s parametrem bez `::jsonb` castu napříč PDO režimy** – tvrzení v sekci 4 stojí na
  obecné znalosti typové inference PostgreSQL. A **zda `flags: ['gin']` neinterpretuje nějaká
  nadstavba** (custom platform, third-party balíček) – ověřeno bylo jen jádro DBAL, G17 tedy platí
  pro čistou instalaci.
