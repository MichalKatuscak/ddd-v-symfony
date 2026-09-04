# Studie: Bounded Context a Context Mapping

- **Kapitola:** `content/chapters/context_mapping.md` (č. 03, kategorie Základy, 894 řádků)
- **Cesta:** /context-mapping
- **Typ kapitoly:** hybridní
- **Datum studie:** 2026-09-03

## 1. Mapa současné kapitoly

| Sekce | Rozsah | Co tvrdí | Zdroje | Poznámka |
|---|---|---|---|---|
| Úvod | 22–24 | BC definuje hranici, Context Mapping popisuje, co se na ní děje. Evans 2003, kap. 14. | [1], [2] | Odkazy na `/co-je-ddd`, `/zakladni-koncepty`, `/event-storming`, `/cqrs`. |
| 03.01 Co je Context Map | 26–47 | Mapa je organizační a politická, ne UML. Dvě složky: vizuální + textová. Callout „kdy mapu nakreslit“. | [1] | Parafráze Evansova „Therefore“ je věcně správná, ale zkrácená. |
| 03.02 Osm typů vztahů | 49–77 | Osm vztahů; sedm z Evanse 2003, Partnership z DDD Reference 2015. BBoM jako devátý vzor. Tabulka `vztah / symetrie / coupling / použití / kdo rozhoduje`. Rozhodovací pravidlo. | [1], [3] | Jádro kapitoly. Tabulka míchá dvě různé kategorie (viz sekce 5). |
| 03.03 Partnership | 79–115 | Symetrický vztah, společný release. Příklad Catalog + Pricing. Symfony: monorepo, jeden `composer.json`. Anti-vzor „Partnership jako default“. Indikátory rozpadu. | [3] | Parafráze Evanse na ř. 83 přesná. Chybí Evansův mechanismus společné testovací sady. |
| 03.04 Shared Kernel | 117–208 | Malý sdílený modul, změna vyžaduje souhlas všech. Ukázka `Money`. Composer path repository. Anti-vzor „rozjetý SK“. SK ≠ utility knihovna. | [1], [3] | Nejdelší kódová část kapitoly. Tvrzení o „přirozeném průniku“ nedohledáno. |
| 03.05 Customer/Supplier | 210–297 | Downstream má hlas, upstream rozhoduje o dodávce. Příklad Catalog → Ordering. Messenger external transport + handler. Plánovací rituály. | [1], [3] | Chybí Evansův důraz na společné akceptační testy v CI upstreamu. |
| 03.06 Conformist | 299–383 | Downstream přijímá cizí model 1:1. Příklad Stripe + Reporting. Kompromis, warn callout, přechodný stav. | [3] | Evansův argument o sdíleném ubiquitous language chybí. |
| 03.07 ACL | 385–532 | Tři odpovědnosti (schema mapping, concept translation, anti-corruption). `LegacyBillingTranslator` + test. Anti-vzor prosakující ACL. Vazba na Strangler Fig. | [3] | Nejsilnější sekce kapitoly. Chybí protistrana: kdy ACL nestavět. |
| 03.08 OHS | 534–625 | Upstream publikuje stabilní protokol pro mnoho konzumentů. REST/gRPC/event stream. Ukázka s versioningem. Strategie verzování, politika zastarávání. | [3] | Verzování je autorská nadstavba, ne Evansova definice. `Deprecation: true` je zastaralé. |
| 03.09 Published Language | 627–745 | PL je formát, OHS kanál. JSON Schema, OpenAPI, AsyncAPI, CloudEvents, Avro. Ukázka schématu + validátoru. Schema-first vs. code-first. | [3], [4] | Chybí Evansův hlavní argument (doménový model jako výměnný formát zmrzne). |
| 03.10 Separate Ways | 747–781 | Vědomá neintegrace. Příklad Marketing + SendGrid. Kdy zvážit, anti-vzor „z lenosti“. | [3] | Rozsahem přiměřené. |
| 03.11 Praktický postup | 783–825 | Workshop 90 minut v pěti krocích, textový popis vztahu, verzování mapy. | – | Bez zdroje; autorský postup. Nekoresponduje s dnešní komunitní praxí (viz sekce 3). |
| 03.12 Big Ball of Mud | 827–857 | Foote & Yoder 1997, symptomy, příčiny, cesta ven přes Strangler Fig. | [5] | Citace ověřena doslovně. Chybí Evansovo doporučení pro BBoM jako vzor. |
| 03.13 Shrnutí + FAQ | 859–884 | Pět bodů shrnutí, šest FAQ včetně „ACL vs. Adapter“ a „víc vztahů mezi 2 BC“. | – | FAQ o kombinaci vztahů je věcně nejlepší část kapitoly – patří výš. |
| 03.14 Další četba | 886–894 | Evans 2003, Evans 2015, Vernon 2013, Foote & Yoder, Fowler, Vernon 2016, DDD Crew. | [1]–[6] | Chybí Khononov (2021), Context Mapper, DDD Starter Modelling Process. |

Kapitola je katalog vzorů s dobrou implementační stránkou. Nejvíc prostoru dostává ACL (148 řádků) a Shared Kernel (92 řádků), tedy dva vzory, které mají zjevný kódový projev. Naopak strategická rovina – kdo v organizaci o vztahu rozhoduje, jak se mapa udržuje, jak souvisí s týmovou strukturou – je odbytá jedním autorským workshopovým postupem bez zdroje. Kapitola také konzistentně podává katalog jako plochý seznam osmi rovnocenných „vztahů“, což neodpovídá ani Evansovi, ani dnešní komunitní praxi. Symfony vrstva je konkrétní a použitelná, ale zastarala oproti Symfony 8.1.

## 2. Kanonické zdroje k tématu

### Evans 2003 – co v knize skutečně je

Kapitola 14 *Maintaining Model Integrity* obsahuje v tomto pořadí: Bounded Context, Continuous Integration, Context Map, *Testing at the Context Boundaries*, *Organizing and Documenting Context Maps*, *Relationships Between Bounded Contexts*, Shared Kernel, Customer/Supplier Development Teams, Conformist, Anticorruption Layer, Separate Ways, Open Host Service, Published Language [1]. Vztahových vzorů je tedy **sedm**. Partnership ani Big Ball of Mud v knize z roku 2003 nejsou.

### Evans 2015 – DDD Reference, ověřeno v primárním zdroji

Obsah *Domain-Driven Design Reference* [2] označuje hvězdičkou tři hesla a v patičce obsahu uvádí legendu:

> `* New term introduced since the 2004 book.`

Hvězdičku nesou přesně tři hesla: **Domain Events**, **Partnership** a **Big Ball of Mud**. Část IV *Context Mapping for Strategic Design* obsahuje Context Map, Partnership \*, Shared Kernel, Customer/Supplier Development, Conformist, Anticorruption Layer, Open-host Service, Published Language, Separate Ways, Big Ball of Mud \*.

**Atribuce v kapitole je tím potvrzena.** Partnership i Big Ball of Mud skutečně přibyly až v DDD Reference. Drobnost: Evans sám odkazuje na „the 2004 book“ (rok copyrightu), kniha používá 2003 (rok vydání). Obojí je obhajitelné, ale je vhodné to nemíchat v jedné větě.

Původ Big Ball of Mud je v Reference uveden explicitně, včetně URL:

> `(see http://www.laputan.org/mud/mud.html. Brian Foote and Joseph Yoder)`

a v textu vzoru: „The big ball of mud is actually quite practical for some situations (as described in the original article by Foote and Yoder), but it almost completely prevents the subtlety and precision needed for useful models.“ [2]

### Foote & Yoder 1997 – ověření citace

Esej *Big Ball of Mud* [5] byla prezentována na Fourth Conference on Pattern Languages of Programs (PLoP '97 / EuroPLoP '97), Monticello, Illinois, září 1997; technical report #WUCS-97-34, Washington University; knižně jako kapitola 29 v *Pattern Languages of Program Design 4* (Addison-Wesley, 2000). Online verze je datována 26. 6. 1999.

Citace na ř. 829 je **doslovně správná**, včetně „bailing wire“ (autoři používají tento tvar, ne „baling“). Věta „Big Ball of Mud je v praxi de facto standardní architektura“ (ř. 842) odpovídá abstraktu: „what is, in effect, the de-facto standard software architecture is seldom discussed“.

Esej obsahuje sedm vzorů: Big Ball of Mud, Throwaway Code, Piecemeal Growth, Keep It Working, Shearing Layers, Sweeping It Under The Rug, Reconstruction. **Reconstruction je u autorů legitimní vzor**, což je relevantní pro tvrzení kapitoly na ř. 851 (viz sekce 5).

### Evansovy definice, které kapitola parafrázuje

Vybrané doslovné pasáže z [2], proti kterým lze parafráze v kapitole ověřit:

- **Context Map:** „Identify each model in play on the project and define its bounded context. … Describe the points of contact between the models, outlining explicit translation for any communication, highlighting any sharing, isolation mechanisms, and levels of influence. **Map the existing terrain. Take up transformations later.**“
- **Partnership:** „When teams in two contexts will succeed or fail together, a cooperative relationship **often emerges**. … Institute a process for coordinated planning of development and joint management of integration. … For example, a special test suite can be defined that proves the interface meets the expectations of the client system, which can be run as part of continuous integration on the server system.“
- **Shared Kernel:** „Designate with an explicit boundary some subset of the domain model that the teams agree to share. **Keep this kernel small.** … Define a continuous integration process that will keep the kernel model tight and align the ubiquitous language of the teams.“
- **Customer/Supplier:** „… downstream priorities factor into upstream planning. Negotiate and budget tasks for downstream requirements … **Jointly developed automated acceptance tests can validate the expected interface from the upstream.** Adding these tests to the upstream team's test suite, to be run as part of its continuous integration, will free the upstream team to make changes without fear of side effects downstream.“
- **Conformist:** „… choosing conformity enormously simplifies integration. **Also, you will share a ubiquitous language with your upstream team.**“
- **Open-host Service:** „Define a protocol that gives access to your subsystem as a set of services. … **This places the provider of the service in the upstream position. Each client is downstream, and typically some of them will be conformist and some will build anticorruption layers.**“
- **Published Language:** „**If one is used as a data interchange language, it essentially becomes frozen and cannot respond to new development needs.** … Published language is often combined with open-host service.“
- **Big Ball of Mud:** „**Draw a boundary around the entire mess and designate it a big ball of mud. Do not try to apply sophisticated modeling within this context. Be alert to the tendency for such systems to sprawl into other contexts.**“

Evansova definice Bounded Contextu v Reference zní: „A description of a boundary (typically a subsystem, or the work of a particular team) within which a particular model is defined and applicable.“ [2]

### Fowler

Bliki *Bounded Context* [6], 15. 1. 2014. Argument: „total unification of the domain model for a large system will not be feasible or cost-effective“; hranice určuje především lidská kultura a jazyk, technická reprezentace až druhotně. Fowler explicitně připouští, že BC může existovat i uvnitř jedné aplikace (oddělení in-memory a relačního modelu). Článek nespojuje BC s deployment jednotkou.

## 3. Stav praxe a posuny

**DDD Crew jako de facto komunitní standard (2020–).** Repozitář `ddd-crew/context-mapping` [7], licence CC BY 4.0, obsahuje cheat sheet a Miro starter kit. Za cheat sheet jsou v repozitáři uvedeni **Kacper Gunia a Nick Tune**. Michael Plöd tento materiál používá ve svých přednáškách a jeho profil na DDD Europe 2020 uvádí, že cheat sheet a Miro šablonu publikoval on – autorství je tedy rozporné a stojí za ruční ověření.

Zásadní přínos DDD Crew je **kategorizace týmových závislostí nad vzory**, kterou kapitola nemá:

- *Mutually Dependent* – „Two teams or bounded contexts are mutually dependent when their software artifacts or systems need to be delivered together to be successful and work.“
- *Upstream Downstream* – „Actions of an upstream team will have an effect on the downstream team, but actions of the downstream do not have a significant impact on the upstream team.“
- *Free* – „A Bounded Context or a team that works in it is free if changes in other Bounded Contexts do not influence its success or failure.“ [7]

Vzorů je v tomto katalogu **devět** (osm Evansových vztahů + Big Ball of Mud). Praktické doporučení repozitáře jde proti představě jedné velké mapy: dělat víc menších map, každou k jedné konkrétní otázce, a u každé mapy vysvětlit použité vzory stakeholderům.

**Context Mapper (contextmapper.org)** [8] jde o krok dál a v DSL odděluje **typ vztahu** od **role**. Symetrické vztahy: Partnership (P), Shared Kernel (SK). Asymetrické: Upstream-Downstream (obecný) a Customer-Supplier (C/S) jako jeho specializace. Role se zapisují v hranatých závorkách u konce vztahu – upstream: OHS, PL; downstream: ACL, CF. Zápis vypadá takto:

```
VoyagePlanningContext [D,ACL] <- [U,OHS,PL] LocationContext
```

Vztah nese i atribut `downstreamRights` s hodnotami `INFLUENCER`, `OPINION_LEADER`, `VETO_RIGHT`, `DECISION_MAKER`, `MONOPOLIST` – tedy explicitní model mocenské asymetrie, který v Evansově katalogu chybí a který kapitola pokrývá jen sloupcem „kdo o něm rozhoduje“.

**Context Map přestal být jediným strategickým artefaktem.** DDD Starter Modelling Process [9] (ddd-crew, CC BY 4.0) má osm kroků: Understand (Business Model Canvas), Discover (EventStorming), Decompose (Context Maps), Strategize (Core Domain Charts), Connect (Domain Message Flow Modelling), Organise (Context Maps + Team Topologies), Define (Bounded Context Canvas), Code (Aggregate Design Canvas). Context Map se objevuje **dvakrát**, pokaždé s jiným účelem: nejdřív k dekompozici, později k organizačnímu zarovnání. Domain Message Flow Modelling a Bounded Context Canvas jsou dnes standardní doplňky – to je odpověď na otázku, jak se mapy udržují: neudržuje se jedna velká mapa, generují se cílené artefakty k jednotlivým rozhodnutím.

**Team Topologies a Context Mapping.** Brandolini [10] (2021) porovnává obojí: Team Topologies dává „reference towards a desirable to-be state“, Context Mapping „more fine-grained patterns for assessing the current state“. Mapování interakčních módů: Collaboration ↔ Partnership / Customer-Supplier, X-as-a-Service ↔ Open-host Service. Plöd [11] formuluje osu nákladů: Partnership vyžaduje málo kódu a hodně konverzační šířky pásma, ACL naopak hodně kódu a žádnou konverzaci.

**Consumer-driven contracts.** Evansovo doporučení „společně vyvíjené akceptační testy v CI upstreamu“ z roku 2003 má dnes jméno a nástroje: Consumer-Driven Contracts (Ian Robinson, martinfowler.com, 2006) [12] a Pact [13]. Robinson rozlišuje provider contract, consumer contract a consumer-driven contract; poslední jmenovaný je „closed and complete with respect to the entire set of functionality demanded by existing consumers“. Toto je přímá realizace Customer/Supplier vztahu a v kapitole chybí.

**Shared Kernel se v praxi opustil jako výchozí volba.** Khononov [14] argumentuje, že Shared Kernel podkopává samotnou myšlenku Bounded Contextu a vyžaduje silné zdůvodnění; kritérium použití je poměr nákladů na duplicitu vůči nákladům na koordinaci. Tam, kde jsou náklady na integraci vyšší než čas na duplikaci komponent, je duplicita pragmatická volba. Kapitola SK podává neutrálně jako jednu z osmi rovnocenných možností.

**ACL má dnes protistranu.** Microsoft Azure Architecture Center [15] uvádí explicitní „when NOT to use“: „The new and legacy systems have no significant semantic differences. In this scenario, it's important to focus the anti-corruption layer on translation logic. Avoid placing business rules or orchestration in the layer.“ Dále vyjmenovává náklady: přidaná latence, další služba k provozu a monitoringu, otázka škálování, riziko, že se vrstva stane bottleneckem, a rozhodnutí, zda je ACL trvalý nebo se po migraci odstraní.

**Bounded Context vs. modul vs. deployment jednotka.** Evansova definice („typically a subsystem, or the work of a particular team“) [2] ani Fowler [6] deployment jednotku nezmiňují. Modular monolith je jednotně nasazovaná aplikace, jejíž moduly odpovídají Bounded Contextům – BC je tedy logická hranice, ne fyzická. Kniha to řeší v kapitole 19 (`microservices_and_ddd.md:26` „Mýtus microservice = Bounded Context“), ale kapitola 03, která má BC v názvu, se k tomu nevyjadřuje a na kapitolu 19 neodkazuje.

## 4. Symfony / PHP specifika

**Verze.** Symfony 8.0 vyšla v listopadu 2025, minimální PHP je 8.4.0, podpora skončila v červenci 2026 [16]. Aktuálně udržovaná linie je 8.1, Symfony 8.2 je plánována na listopad 2026. Kniha cílí na „Symfony 8“ – ukázky by měly odpovídat alespoň 8.1.

**Messenger a cizí zprávy.** Poznámka na ř. 248–250 je věcně správná: `routing` řídí odesílání, ne příjem, a pro konzumaci cizích zpráv je potřeba vlastní serializer transportu. Konfigurační volba se jmenuje `serializer` v `options` transportu, implementuje se `Symfony\Component\Messenger\Transport\Serialization\SerializerInterface`, výchozí je `PhpSerializer` [17].

**Symfony 8.1 přineslo tři věci přímo k tématu kapitoly** [18]:

1. `#[AsMessage(serializedTypeName: 'catalog.product.price_changed')]` – nahradí PHP FQCN v hlavičce `type` vlastní hodnotou. To je přesně mechanismus Published Language na úrovni Messengeru: jméno zprávy je součást publikovaného kontraktu, ne interní detail namespace producenta.
2. `MessageDecodingFailedException` – selhání dekódování už neputuje tiše pryč, ale prochází standardní failure pipeline (retry transport, failure transport), s možností pozdějšího přehrání přes `DecodeFailedMessageMiddleware`. Tohle je infrastrukturní protějšek třetí odpovědnosti ACL („anti-corruption“) z ř. 397.
3. `--fetch-size` u `messenger:consume`, `--no-reset=N`, `AmqpPriorityStamp`, `ListableReceiverInterface` pro Redis transport, `BatchHandlerInterface::getIdleTimeout()`.

**AMQP transport.** Volby použité v ukázce na ř. 226–245 (`exchange.name`, `exchange.type`, `queues[].binding_keys`, `retry_strategy.max_retries/delay/multiplier`) odpovídají dokumentaci [17]. Dokumentace navíc nabízí `max_delay` a `jitter` (výchozí 0.1), které v ukázce chybí – u integrace mezi BC jsou obojí relevantní.

**OHS a PL v ekosystému.** API Platform generuje JSON Schema a OpenAPI z anotovaných resource tříd [19], tedy code-first cestu z ř. 742. Pro schema-first cestu kapitola zmiňuje `jane-php/open-api`; pro validaci payloadu proti JSON Schema se v PHP používají `opis/json-schema` a `justinrainbow/json-schema` – ukázka na ř. 700–733 konkrétní knihovnu nejmenuje, což je pro implementační kapitolu málo (nepodařilo se ověřit, kterou knihovnu ukázka předpokládá).

**HTTP hlavičky pro zastarávání.** RFC 9745 *The Deprecation HTTP Response Header Field* (Standards Track, březen 2025) [20] definuje `Deprecation` jako Item Structured Header typu Date podle RFC 9651. Příklad ze specifikace: `Deprecation: @1688169599`. Hodnota `true` pocházela z dřívějšího draftu a **není platná**. `Sunset` je definován v RFC 8594 a používá jiný formát data; timestamp v `Sunset` nesmí být dřívější než v `Deprecation`.

## 5. Sporné a chybně podávané body

**1. Kolik je vztahů a co vůbec „vztah“ je.** Kapitola pracuje s osmi vztahy plus BBoM jako devátým vzorem. Evans 2003 má sedm, DDD Reference devět hesel, DDD Crew devět vzorů. Problém není počet, ale to, že tabulka na ř. 55–64 dává do jednoho sloupce věci různého řádu. Partnership a Shared Kernel jsou symetrické vztahy dvou týmů. Customer/Supplier je asymetrický vztah. **Conformist, ACL, OHS a Published Language nejsou vztahy, ale role na jednom konci asymetrického vztahu.** Context Mapper [8] to modeluje explicitně; Evans to naznačuje u OHS („each client is downstream, and typically some of them will be conformist and some will build anticorruption layers“) [2]. Kapitola si toho je vědoma – FAQ na ř. 878 problém řeší správně – ale hlavní tabulka mu odporuje. Doporučení: přepracovat 03.02 na dvouúrovňový model a FAQ odpověď posunout do hlavního textu.

**2. Sloupec „Coupling“ v tabulce.** Conformist je označen jako „Střední“ coupling, Customer/Supplier také „Střední“. Conformist je přitom nejtěsnější možná vazba na cizí model: downstream nemá vlastní model vůbec. Sloupec navíc nerozlišuje coupling modelu od couplingu release cyklu, což jsou u těchto vzorů dvě různé osy. Doporučení: buď sloupec rozdělit, nebo ho nahradit dvojicí „vazba modelu“ a „vazba plánování“.

**3. Partnership: vzniká, nebo se volí?** Kapitola na ř. 81 tvrdí: „Není to ‚náhodná spolupráce‘ – Partnership je vědomé strategické rozhodnutí.“ Evans píše opak jako výchozí pozorování: „a cooperative relationship **often emerges**“ [2]. Teprve „Therefore“ říká, aby se partnerství vědomě uzavřelo. Rozdíl je podstatný: Evansova rada je *pojmenovat a zformalizovat to, co už fakticky platí*, ne *rozhodnout se pro Partnership od stolu*. Doporučení: přeformulovat na „Partnership typicky vzniká sám; úkolem mapy je ho pojmenovat a doplnit mu proces“.

**4. „OHS bez verzování není OHS“ (ř. 624).** Evansova definice OHS o verzování nemluví vůbec. Mluví o publikovaném protokolu, jeho rozšiřování a o jednorázovém translátoru pro idiosynkratické potřeby jediného týmu [2]. Tvrzení kapitoly je rozumný praktický názor, ale je podáno jako definice. Doporučení: ponechat obsah, změnit rámování na autorský postoj („Bez politiky verzování je OHS deklarace bez závazku“) a nevydávat ho za Evansovu definici.

**5. „Big Ball of Mud se nedá opravit rewriteem. Jediný funkční postup je Strangler Fig.“ (ř. 851).** Foote & Yoder uvádějí *Reconstruction* jako jeden ze sedmi vzorů své eseje, tedy rewrite je u původních autorů legitimní volba [5]. Evansovo doporučení v DDD Reference je navíc úplně jiné a v kapitole chybí: nakreslit kolem toho hranici, označit to jako big ball of mud, nepokoušet se uvnitř o sofistikované modelování a hlídat, aby se to nešířilo do dalších kontextů [2]. Doporučení: doplnit Evansovo „Therefore“ jako primární radu a zmírnit „jediný funkční postup“ na „nejčastěji funkční postup“.

**6. Shared Kernel jako rovnocenná volba.** Kapitola SK popisuje neutrálně, s varováním proti růstu. Khononov [14] jde dál: SK podkopává myšlenku Bounded Contextu a má se používat jen tehdy, když jsou náklady na duplicitu vyšší než náklady na koordinaci. Kapitola náklady na koordinaci zmiňuje, ale kritérium neformuluje. Doporučení: doplnit explicitní rozhodovací kritérium duplicita vs. koordinace.

**7. Conformist jako čistá ztráta.** Kapitola vyjmenovává, co Conformist ušetří a co zaplatí (ř. 362–376), ale vynechává Evansův přínos: „you will share a ubiquitous language with your upstream team“ [2]. U integrace se standardem (ISO 20022, iCalendar) je sdílený jazyk hlavní důvod, proč Conformist zvolit, ne vedlejší efekt.

**8. ACL vs. Adapter.** FAQ na ř. 876–877 rozlišuje správně. Stojí za zmínku, že mainstreamová dokumentace toto rozlišení nedělá – Azure Architecture Center [15] popisuje ACL jako „facade or adapter layer“ a jako samostatnou nasaditelnou službu. Kapitola má obhajitelnou pozici, ale mohla by uvést, že se výklady liší.

**9. „Textová složka je důležitější než obrázek“ (ř. 34).** Autorský názor bez zdroje. DDD Crew doporučuje opak co do formy (vizuální cheat sheet, Miro), ale shodně co do rozsahu: raději víc malých map k jedné otázce než jedna velká [7]. Doporučení: tvrzení buď podložit, nebo přeformulovat jako doporučení „malé cílené mapy“ místo „text nad obrázkem“.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | sporné | `context_mapping.md:51–64` | Plochá taxonomie osmi „vztahů“; Conformist/ACL/OHS/PL jsou role, ne vztahy | Přepracovat na dvouúrovňový model: typ vztahu (symetrický / upstream-downstream / žádný) + role na konci vztahu |
| G2 | chybí | sekce 03.02 | Chybí kategorizace týmových závislostí Mutually Dependent / Upstream-Downstream / Free | Doplnit jako vstupní filtr před volbou vzoru; zdroj [7] |
| G3 | nepodložené | `context_mapping.md:121` | „Kernel má být menší než přirozený průnik obou modelů“ – v DDD Reference stojí jen „Keep this kernel small“ | Ověřit v knize z 2003; nepotvrdí-li se, nahradit doslovným „kernel držte malý“ |
| G4 | chybí | sekce 03.04 | Chybí Evansovo doporučení definovat pro kernel vlastní continuous integration proces | Doplnit jednu pasáž; CODEOWNERS je organizační, CI je kontraktní |
| G5 | chybí | `context_mapping.md:362–376` | Chybí Evansův přínos Conformistu: sdílený ubiquitous language s upstreamem | Doplnit do seznamu „Conformist ušetří“ |
| G6 | chybí | sekce 03.08 | Chybí Evansova věta, že OHS staví poskytovatele do upstream pozice a jeho klienti jsou mix conformistů a ACL | Doplnit – spojuje OHS s dvěma dalšími vzory a vysvětluje jejich kombinaci |
| G7 | zastaralé | `context_mapping.md:619` | `Deprecation: true` neodpovídá RFC 9745 (Structured Field typu Date) | Opravit na `Deprecation: @1688169599` a doplnit odkaz na RFC 9745 + RFC 8594 |
| G8 | nepodložené | `context_mapping.md:624` | „OHS bez verzování není OHS“ podáno jako definice | Přerámovat jako autorské doporučení |
| G9 | chybí | sekce 03.05 | Chybí Evansovy společné akceptační testy v CI upstreamu a jejich dnešní podoba (consumer-driven contracts, Pact) | Nová podsekce ~25 řádků |
| G10 | chybí | sekce 03.07 | Chybí „kdy ACL nestavět“: latence, provozní náklady, riziko bottlenecku, zanedbatelný sémantický rozdíl | Nová podsekce ~20 řádků; zdroj [15] |
| G11 | mělké | `context_mapping.md:28–30` | Definice Context Mapy vynechává „levels of influence“ a „Map the existing terrain. Take up transformations later.“ | Doplnit; deskriptivnost mapy je dnes zmíněna až v BBoM callout na ř. 856 |
| G12 | chybí | sekce 03.11 | Postup je autorský, bez zdroje; chybí doporučení dělat víc malých map a zasazení do DDD Starter Modelling Process | Doplnit odkaz na [7] a [9], zmínit kroky Decompose a Organise |
| G13 | chybí | celá kapitola | Vztah Bounded Context ↔ modul ↔ deployment jednotka není otevřen, ačkoli BC je v názvu kapitoly | Nová sekce ~30 řádků v 03.01, s odkazem na `/microservices-a-ddd#mytus` |
| G14 | sporné | `context_mapping.md:851` | „BBoM se nedá opravit rewritem, jediný postup je Strangler Fig“ – Foote & Yoder mají Reconstruction jako vzor | Zmírnit tvrzení, doplnit protiargument |
| G15 | chybí | sekce 03.12 | Chybí Evansovo „Therefore“ pro BBoM: ohraničit, nemodelovat uvnitř, hlídat sprawl | Doplnit jako první doporučení v „Cesta ven“ |
| G16 | zastaralé | `context_mapping.md:248–250` | Poznámka o serializeru nezná `#[AsMessage(serializedTypeName:)]` a `MessageDecodingFailedException` ze Symfony 8.1 | Aktualizovat; obojí je přímo k tématu PL a ACL |
| G17 | mělké | sekce 03.09 | Chybí Evansův hlavní argument pro PL: doménový model použitý jako výměnný formát zmrzne | Doplnit dvě věty na začátek sekce |
| G18 | sporné | `context_mapping.md:81` | „Partnership je vědomé strategické rozhodnutí“ vs. Evansovo „a cooperative relationship often emerges“ | Přeformulovat; doplnit Evansův mechanismus společné testovací sady v CI |
| G19 | chybí | sekce 03.02, 03.13 | Chybí mapování na interakční módy Team Topologies, ačkoli shrnutí Team Topologies zmiňuje | Doplnit tabulku Collaboration ↔ Partnership/C-S, X-as-a-Service ↔ OHS |
| G20 | chybí | sekce 03.11 | Chybí Context Mapper jako nástroj pro verzovatelnou, v CI generovanou mapu | Doplnit do „Verzování Context Mapy“; zdroj [8] |
| G21 | nepodložené | `context_mapping.md:55–64` | Sloupec „Coupling“ hodnotí Conformist jako střední coupling bez zdůvodnění | Rozdělit na vazbu modelu a vazbu plánování, nebo sloupec vypustit |
| G22 | chybí | sekce 03.14 | Další četba neobsahuje Khononova (2021), Context Mapper ani DDD Starter Modelling Process | Doplnit tři položky |

## 7. Doporučení k přepisu

**P1-1 — Přestavět sekci 03.02 na dvouúrovňový model.** Nejdřív typ vztahu (symetrický: Partnership, Shared Kernel; asymetrický upstream/downstream, jehož specializací je Customer/Supplier; žádný: Separate Ways), potom role na konci vztahu (upstream: OHS, Published Language; downstream: Conformist, ACL). Bez toho kapitola učí taxonomii, která si sama odporuje – FAQ na ř. 878 ji vyvrací. Zároveň se tím vyřeší G21, protože každá úroveň má vlastní osu couplingu. *Přepis sekce 03.02, ~50 řádků; dotkne se i tabulky a rozhodovacího pravidla.*

**P1-2 — Opravit `Deprecation: true` na formát podle RFC 9745.** Věcná chyba v ukázce, kterou čtenář zkopíruje do produkce. RFC 9745 je Standards Track z března 2025 a definuje hodnotu jako Structured Field typu Date. `Sunset` (RFC 8594) používá jiný formát data a nesmí být dřívější než `Deprecation`. *Oprava dvou vět na ř. 619 plus poznámka o obou RFC.*

**P1-3 — Doplnit Evansovo doporučení pro Big Ball of Mud a zmírnit tvrzení o rewritu.** Sekce 03.12 popisuje symptomy a příčiny, ale primární radu vzoru vynechává: ohraničit nepořádek, nemodelovat uvnitř, hlídat šíření do jiných kontextů. Tvrzení „jediný funkční postup je Strangler Fig“ navíc odporuje původním autorům, kteří mají Reconstruction jako plnohodnotný vzor. *Přepis podsekce „Cesta ven“, ~15 řádků.*

**P1-4 — Doplnit sekci „Kdy ACL nestavět“.** ACL dostává v kapitole 148 řádků a nemá jedinou stránku protiargumentů. Chybí latence, provozní režie další vrstvy, riziko, že se ACL stane bottleneckem, a případ zanedbatelného sémantického rozdílu, kde je ACL čistý overhead. Kapitola bez toho učí ACL jako výchozí volbu, což je v rozporu s jejím vlastním varováním před dogmatismem. *Nová podsekce v 03.07, ~20 řádků.*

**P1-5 — Přerámovat „OHS bez verzování není OHS“ na autorský postoj.** Tvrzení je podáno jako definice a Evansovu definici popírá. Obsah je užitečný, forma zavádějící – čtenář si odnese, že mu Evans předepisuje verzování API. *Přepis callout na ř. 623–625, oprava tří vět.*

**P1-6 — Doplnit vztah Bounded Context ↔ modul ↔ deployment jednotka do sekce 03.01.** Kapitola má Bounded Context v názvu, ale hranici nikde nevymezuje vůči modulu ani vůči nasazovací jednotce. Evans definuje BC jako „typically a subsystem, or the work of a particular team“, Fowler připouští BC uvnitř jedné aplikace. Bez toho čtenář odejde s implicitní rovnicí BC = služba, kterou kapitola 19 pak musí vyvracet. *Nová podsekce v 03.01, ~30 řádků, s odkazy na `/microservices-a-ddd#mytus` a `/subdomeny#subdomeny-na-bc`.*

**P1-7 — Ověřit nebo přeformulovat tvrzení o „přirozeném průniku“ u Shared Kernelu.** Ř. 121 připisuje Evansovi formulaci, kterou se v DDD Reference nepodařilo najít; primární zdroj má jen „Keep this kernel small“. Buď dohledat v knize z 2003, nebo nahradit doslovným zněním. *Oprava jedné věty.*

**P2-1 — Doplnit consumer-driven contracts do sekce 03.05.** Evans jako mechanismus Customer/Supplier jmenuje společně vyvíjené akceptační testy přidané do CI upstreamu. To je přesně consumer-driven contracts (Robinson 2006) a Pact. Kapitola má místo toho „plánovací rituály“, tedy jen organizační polovinu vzoru. *Nová podsekce ~25 řádků, ideálně s PHP příkladem.*

**P2-2 — Aktualizovat Messenger část na Symfony 8.1.** `#[AsMessage(serializedTypeName: ...)]` je přímý nástroj Published Language: jméno zprávy se stává publikovaným kontraktem místo interního FQCN. `MessageDecodingFailedException` a `DecodeFailedMessageMiddleware` dávají anti-corruption vrstvě infrastrukturní oporu. Obojí patří do 03.05 a 03.09. *Přepis poznámky na ř. 248–250 a doplnění ~20 řádků do 03.09.*

**P2-3 — Doplnit Evansovy vynechané pasáže u Conformistu, OHS a Published Language.** Tři konkrétní věty ze tří vzorů (sdílený ubiquitous language, upstream pozice OHS a mix conformistů a ACL mezi klienty, zmrznutí doménového modelu použitého jako výměnný formát). Každá z nich mění vyznění sekce, ve které chybí. *Tři doplňky po 2–4 větách.*

**P2-4 — Přepsat sekci 03.11 tak, aby stála na komunitní praxi.** Doplnit doporučení dělat víc malých map k jedné otázce místo jedné velké, zasadit Context Mapping do DDD Starter Modelling Process (kroky Decompose a Organise), zmínit Domain Message Flow Modelling a Bounded Context Canvas jako doplňkové artefakty a Context Mapper jako nástroj pro verzovanou mapu generovanou v CI. *Přepis sekce 03.11, ~30 řádků změn.*

**P2-5 — Doplnit mapování na interakční módy Team Topologies.** Collaboration ↔ Partnership / Customer-Supplier, X-as-a-Service ↔ Open-host Service. Shrnutí kapitoly Team Topologies zmiňuje, ale konkrétní most mezi oběma slovníky chybí. Zároveň to zpevní křížový odkaz na kapitolu 05. *Malá tabulka + odstavec, ~12 řádků.*

**P2-6 — Doplnit rozhodovací kritérium pro Shared Kernel.** Náklady na duplicitu vs. náklady na koordinaci, s explicitní poznámkou, že dnešní doporučení je SK spíš nepoužívat. *Doplnění ~10 řádků do 03.04.*

**P3-1 — Rozšířit Další četbu.** Khononov *Learning Domain-Driven Design* (2021), Context Mapper, DDD Starter Modelling Process, Robinson *Consumer-Driven Contracts*. *Čtyři položky.*

**P3-2 — Sjednotit datování Evansovy knihy.** Kapitola píše 2003 (rok vydání), Evans sám v Reference odkazuje na „the 2004 book“ (rok copyrightu). Stojí za jednu poznámku pod čarou, aby čtenář nemátl obojí. *Jedna věta.*

**P3-3 — Doplnit `max_delay` a `jitter` do ukázky retry strategie.** U integrace mezi Bounded Contexty jsou obojí relevantní a výchozí `jitter` je 0.1. *Dva řádky v YAML ukázce.*

## 8. Otevřené otázky pro autora

1. **Kolik prostoru dát rozlišení vztah vs. role?** P1-1 je největší zásah do kapitoly. Alternativa je nechat plochou osmičku jako didaktické zjednodušení a rozlišení uvést jen v jednom callout. Riziko: čtenář si osmičku zapamatuje a v praxi ji nedokáže použít, protože reálné vztahy jsou kombinace.
2. **Má kapitola udržet název „osm vztahů“?** Je v `page_title`, `meta_description` a `schema_headline`. Změna na devět nebo na „vztahy a role“ znamená zásah do SEO metadat a případně do přesměrování. DDD Crew i DDD Reference mají devět.
3. **Patří sekce o BC vs. modul vs. deployment jednotka sem, nebo do kapitoly 01?** Kapitola 01 definici BC už zavádí (`/co-je-ddd#bounded-context`). Argument pro kapitolu 03: má BC v názvu a čtenář sem přijde s otázkou „jak velký je jeden BC“.
4. **Zůstane Stripe jako příklad Conformistu?** Příklad je srozumitelný, ale vede k tvrzení, že Conformist je vždy jen úspora. U integrace se standardem (ISO 20022, CloudEvents) je Conformist naopak správná volba se sdíleným ubiquitous language. Zvážit druhý příklad.
5. **Kolik Symfony detailu snese strategická kapitola?** Kapitola je zařazena do Základů (`lvl 3`, 28 minut) a už dnes obsahuje 8 kódových bloků. Doporučení P2-1 a P2-2 přidají další. Alternativa: implementační detaily přesunout do kapitoly 19 nebo do `/outbox-pattern` a zde nechat jen odkazy.
6. **Verifikace autorství cheat sheetu DDD Crew.** Repozitář uvádí Kacpera Gunię a Nicka Tuneho, profil Michaela Plöda na DDD Europe tvrdí, že cheat sheet publikoval on. Pokud kniha bude cheat sheet citovat, je vhodné to vyřešit ručně (issue v repozitáři, e-mail).

## 9. Bibliografie

### Ověřené zdroje

`[1]` Eric Evans — *Domain-Driven Design: Tackling Complexity in the Heart of Software*, Addison-Wesley, 2003. Obsah kapitoly 14 ověřen přes InformIT (nakladatelský výpis obsahu) a dddcommunity.org. https://www.informit.com/store/domain-driven-design-tackling-complexity-in-the-heart-9780321125217 (přístup 2026-09-03)

`[2]` Eric Evans — *Domain-Driven Design Reference: Definitions and Pattern Summaries*, Domain Language, Inc., 2015, CC BY 4.0. Primární zdroj, stažen a ověřen v plném znění. https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf (přístup 2026-09-03)

`[3]` Vaughn Vernon — *Implementing Domain-Driven Design*, Addison-Wesley, 2013, kap. 3 „Context Maps“ (sekce: Why Context Maps Are So Essential, Drawing Context Maps, Projects and Organizational Relationships, Mapping the Three Contexts). Obsah ověřen přes knihovní katalog a nakladatelský výpis; text kapitoly nebyl k dispozici.

`[4]` JSON Schema, OpenAPI, AsyncAPI, CloudEvents — specifikace uvedené v kapitole. https://json-schema.org/, https://www.openapis.org/, https://www.asyncapi.com/, https://cloudevents.io/ (přístup 2026-09-03)

`[5]` Brian Foote, Joseph Yoder — *Big Ball of Mud*, PLoP '97 / EuroPLoP '97, Monticello, Illinois, září 1997; Technical Report #WUCS-97-34, Washington University; knižně *Pattern Languages of Program Design 4*, Addison-Wesley, 2000, kap. 29. Online verze datována 26. 6. 1999. http://www.laputan.org/mud/ (přístup 2026-09-03, citace ověřena doslovně)

`[6]` Martin Fowler — *Bounded Context* (bliki), 15. 1. 2014. https://martinfowler.com/bliki/BoundedContext.html (přístup 2026-09-03)

`[7]` DDD Crew — *Context Mapping* (cheat sheet, Miro starter kit), CC BY 4.0; za cheat sheet uvedeni Kacper Gunia a Nick Tune. https://github.com/ddd-crew/context-mapping (přístup 2026-09-03)

`[8]` Context Mapper — *Context Map* (dokumentace DSL). https://contextmapper.org/docs/context-map/ (přístup 2026-09-03)

`[9]` DDD Crew — *Domain-Driven Design Starter Modelling Process*, CC BY 4.0. https://ddd-crew.github.io/ddd-starter-modelling-process/ (přístup 2026-09-03)

`[10]` Alberto Brandolini — *About Team Topologies and Context Mapping*, Avanscoperta Blog, 22. 4. 2021. https://blog.avanscoperta.it/2021/04/22/about-team-topologies-and-context-mapping/ (přístup 2026-09-03)

`[11]` Michael Plöd — *Systems Thinking by combining Team Topologies with Context Maps*, INNOQ / Øredev 2023. https://www.innoq.com/en/talks/2023/11/systems-thinking-by-combining-team-topologies-with-context-maps-oredev-2023/ (přístup 2026-09-03)

`[12]` Ian Robinson — *Consumer-Driven Contracts: A Service Evolution Pattern*, martinfowler.com, 12. 6. 2006. https://martinfowler.com/articles/consumerDrivenContracts.html (přístup 2026-09-03)

`[13]` Pact — *Introduction*, docs.pact.io. https://docs.pact.io/ (přístup 2026-09-03)

`[14]` Vlad Khononov — *Learning Domain-Driven Design: Aligning Software Architecture and Business Strategy*, O'Reilly, 2021. Tvrzení o Shared Kernelu a o kritériu duplicita vs. koordinace pochází ze sekundárních shrnutí knihy, ne z původního textu — viz „Neověřené“.

`[15]` Microsoft — *Anti-Corruption Layer pattern*, Azure Architecture Center, aktualizováno 2026-05. https://learn.microsoft.com/en-us/azure/architecture/patterns/anti-corruption-layer (přístup 2026-09-03)

`[16]` Symfony — *Symfony 8.0 Release*: vydání listopad 2025, minimální PHP 8.4.0, konec podpory červenec 2026. https://symfony.com/releases/8.0 (přístup 2026-09-03)

`[17]` Symfony — *Messenger: Sync & Queued Message Handling* (volba `serializer`, `SerializerInterface`, AMQP options, `retry_strategy`). https://symfony.com/doc/current/messenger.html (přístup 2026-09-03)

`[18]` Symfony Blog — *New in Symfony 8.1: Messenger Improvements* (`#[AsMessage(serializedTypeName:)]`, `MessageDecodingFailedException`, `DecodeFailedMessageMiddleware`, `--fetch-size`, `AmqpPriorityStamp`). https://symfony.com/blog/new-in-symfony-8-1-messenger-improvements (přístup 2026-09-03)

`[19]` API Platform — *JSON Schema Support*. https://api-platform.com/docs/core/json-schema/ (přístup 2026-09-03)

`[20]` IETF — RFC 9745 *The Deprecation HTTP Response Header Field*, Standards Track, březen 2025; navazuje na RFC 8594 (`Sunset`). https://www.rfc-editor.org/rfc/rfc9745.html (přístup 2026-09-03)

### Doplňkový nález z *DDD Distilled* (2026-09-04, vlastní výtisk)

Vernon má k Published Language konkrétní implementační pravidlo, které se do kapitoly hodí
a kapitola ho nemá:

> *„As recommended in Implementing Domain-Driven Design [IDDD], and specifically in Chapter 13,
> ‚Integrating Bounded Contexts‘, consumers should not use the event types (e.g., classes) of an
> event publisher. Rather, they should depend only on the schema of the events, that is, their
> Published Language. This generally means that if the events are published as JSON […] the
> consumer should consume the events by parsing them.“*

Je to konkrétní odpověď na otázku, kterou si čtenář položí u každé integrace přes události: smím
sdílet třídy událostí mezi kontexty? Vernon říká ne – závislost má být na schématu, ne na typu.
**Doporučení: doplnit do sekce o Published Language;** podpírá to i pravidlo z `CLAUDE.md`, že
agregáty se odkazují jen přes ID, a rozšiřuje je na hranice bounded contextů.

### Neověřené / nedohledané

- **Shared Kernel – OVĚŘENO 2026-09-04 proti tištěné knize Evans 2003, s. 229 (vlastní výtisk).**
  Formulace „menší než přirozený průnik obou modelů“ v knize **není**. Slovo „intersection“ se
  v souvislosti se Shared Kernelem nevyskytuje vůbec (jediné výskyty jsou „intersection of jargons“
  a „intersections with other technologies“). Není tam ani „Keep this kernel small“ – ta věta
  přibyla až v *DDD Reference* (2015). Evansovo znění z roku 2003 je toto:

  > *„Therefore: Designate some subset of the domain model that the two teams agree to share.
  > Of course this includes, along with this subset of the model, the subset of code or of the
  > database design associated with that part of the model. This explicitly shared stuff has
  > special status, and shouldn’t be changed without consultation with the other team.
  > Integrate a functional system frequently, but somewhat less often than the pace of CONTINUOUS
  > INTEGRATION within the teams. At these integrations, run the tests of both teams.“*

  **Tři věci, které kapitola vynechává nebo posouvá.**

  Za prvé, velikost. Evans neargumentuje průnikem, ale náklady: *„It may be too much overhead to
  fully synchronize the entire model and code base, but a carefully selected subset can provide
  much of the benefit for less cost.“* Kritérium je tedy „pečlivě vybraná podmnožina“, ne
  geometrický vztah ke sjednocení modelů.

  Za druhé, provozní režim. Kapitola končí u zákazu jednostranné změny, Evans pokračuje: testy obou
  týmů musí projít při každé změně, týmy pracují na oddělených kopiích kernelu a slučují je
  v intervalech – *„on a team that CONTINUOUSLY INTEGRATES daily or better, the KERNEL merger
  might be weekly“*. To je to, co ze Shared Kernelu dělá použitelný vzor, a v kapitole to chybí.

  Za třetí, a to je nejdůležitější: **Evans a kapitola doporučují do Shared Kernelu opačné věci.**
  Evans píše *„The SHARED KERNEL is often the CORE DOMAIN, some set of GENERIC SUBDOMAINS, or
  both“*, kdežto kapitola na `context_mapping.md:126` doporučuje elementární hodnotové objekty
  (`Money`, `Currency`, `EmailAddress`, `UserId`). Evansův cíl je přitom explicitní: *„The goal is
  to reduce duplication (but not to eliminate it […]) and make integration between the two
  subsystems relatively easy.“*

  **Doporučení:** formulaci o průniku vyškrtnout, doplnit režim společné integrace a testů,
  a rozhodnout se u obsahu kernelu. Doporučení sdílet elementární VO je obhajitelné, ale je to
  autorský posun proti Evansovi – nemá se podávat jako jeho pozice.
- **Vernon, *IDDD* kap. 3 – OVĚŘENO 2026-09-04 z plného textu (vlastní výtisk). Tvrzení
  na ř. 51 a 631 sedí.** Kapitola 3 se jmenuje *Context Maps* a člení se na *Why Context Maps Are
  So Essential*, *Drawing Context Maps*, *Projects and Organizational Relationships* a *Mapping the
  Three Contexts*. Katalog vztahů v ní je, včetně Published Language: *„Published Language: The
  translation between the models of two Bounded Contexts requires a common language. Use
  a well-documented shared language that can express the necessary domain information.“*

  **Detail, který se do kapitoly hodí:** Vernon zavádí pro kreslení map ustálené zkratky –
  *„ACL for Anticorruption Layer, OHS for Open Host Service, PL for Published Language“*. Pokud
  kapitola diagramy kontextových map obsahuje, stojí za to je použít; jsou v komunitě zavedené.

  Zajímavá je i jeho poznámka, že Open Host Service a Published Language se v praxi kombinují
  s Anticorruption Layer, a že to *„is not a contradiction“* – vzory se nevylučují.
- **Khononov 2021.** Tvrzení o Shared Kernelu, o kategorizaci vzorů a o kritériu duplicita vs. koordinace pochází ze sekundárních shrnutí a recenzí knihy. Před citací v knize ověřit proti originálu (kap. 4 „Integrating Bounded Contexts“).
- **Autorství cheat sheetu DDD Crew.** Repozitář [7] uvádí Kacpera Gunię a Nicka Tuneho; profil Michaela Plöda na DDD Europe 2020 uvádí, že cheat sheet a Miro šablonu publikoval on. Rozpor nevyřešen.
- **Plödovy slidy „Combining Team Topologies with Context Maps“.** PDF na res.cloudinary.com se nepodařilo převést na text (obrázkové slidy). Tvrzení o osách „hodně kódu / hodně konverzace“ pochází z textového shrnutí přednášky, ne ze slidů samotných.
- **Podepisování Messenger payloadů – OVĚŘENO 2026-09-04: funkce existuje, ale je ze Symfony 7.4,
  ne z 8.** V CHANGELOGu `symfony/messenger` stojí pod hlavičkou **7.4** záznam
  „Support signing messages per handler“. Zmínka z blogu třetí strany se tedy potvrdila věcně
  a vyvrátila v dataci. **Doporučení: funkci zmiňovat s verzí 7.4 a nepodávat ji jako novinku
  Symfony 8.** Pro knihu cílící na Symfony 8 je použitelná bez výhrad, jen to není novinka.
- **JSON Schema knihovna v ukázce na ř. 700–733.** Ukázka `OrderPlacedValidator` nejmenuje konkrétní PHP knihovnu. Před přepisem rozhodnout mezi `opis/json-schema` a `justinrainbow/json-schema` a ověřit aktuální API.
- **Fowler, StranglerFigApplication – DATOVÁNO 2026-09-04.** Původní *Strangler Application*
  vyšel **29. 6. 2004**, přejmenování na *Strangler Fig Application* proběhlo **29. 4. 2019**
  (revizní poznámka: „Changed URL and name to Strangler Fig Application April 29 2019“). Rozporné
  údaje z prvního průchodu vznikly tím, že stránka nese datum poslední revize, ne původního
  vydání; původní znění je zachované na `OriginalStranglerFigApplication.html`.

