---
route: event_storming
path: /event-storming
title: Event Storming a Domain Storytelling
page_title: "Event Storming a Domain Storytelling: workshop | DDD Symfony"
meta_description: "Jak připravit, vést a vyhodnotit workshop Event Storming a Domain Storytelling. Na konci máte Bounded Contexty, eventy a první kandidáty na agregáty."
meta_keywords: "Event Storming, Domain Storytelling, Alberto Brandolini, Stefan Hofer, Henning Schwentner, Domain Discovery, DDD workshop, Big Picture, Process Level, Design Level, Pivotal Event, Hot Spot, Bounded Context"
og_type: article
published: "2026-04-29"
modified: 2026-09-06
breadcrumb_name: Event Storming
schema_type: TechArticle
schema_headline: "Event Storming a Domain Storytelling – workshop pro objevení domény"
chapter_number: "04"
category: Základy
deck: "Před první řádkou kódu byste měli odejít od počítače. Event Storming Alberta Brandoliniho a Domain Storytelling Hofera & Schwentnera jsou dvě prověřené workshopové techniky, jak v jedné místnosti dostat do shody vývojáře s doménovými experty. Průvodce, který v Symfony projektu funguje."
reading_time: 27
difficulty: 2
github_examples: null
---

DDD nezačíná u kódu. Začíná v místnosti, ve které proti sobě sedí lidé, kteří kód píší, a lidé, kteří doménu reálně provozují. Tato kapitola popisuje dvě konkrétní techniky, jak takovou místnost zařídit. Cílem je strávit v ní dvě až čtyři hodiny smysluplně a odejít s něčím, co se dá zítra otevřít v IDE. Půjde o **Event Storming** Alberta Brandoliniho (2013) a **Domain Storytelling** Stefana Hofera a Henninga Schwentnera (2021). Obě techniky řeší stejný problém, totiž extrakci tacitních doménových znalostí. Liší se cestou. Po této kapitole budete vědět, kterou kdy zvolit a jak ji prakticky uřídit.

## 04.01 Proč workshop, proč ne čtení dokumentace {#proc-workshop}

Standardní reakce vývojářského týmu, který má zahájit nový projekt nebo přepsat existující, je *„dejte nám specifikaci a my to naprogramujeme“*. Specifikace ale typicky neexistuje ve formě, která by stačila. Existují wiki stránky staré tři roky, e-mailová vlákna, ticketovací systém s 1 800 issues a čtyři lidé, kteří „to vědí“. Žádný z těchto zdrojů není autoritativní. Každý zachycuje doménu z jiného úhlu, v jiné době a často si protiřečí.

To je v pořádku. Doména žije v hlavách doménových expertů jako *znalostní síť*; přečíst ji jako knihu nelze. Když se obchodní ředitel a šéf logistiky rozcházejí v tom, co znamená „odeslaná objednávka“, je to signál. Existují dva pohledy, a tedy pravděpodobně i dva [Bounded Contexty](/zakladni-koncepty#bounded-contexts). Workshop je formát, ve kterém tyto kontradikce **vidíte v reálném čase** a řešíte je společně. Wiki vám je nikdy neukáže; vždy zachytí pohled toho, kdo ji psal.

Eric Evans v *Domain-Driven Design* (2003) píše, že [Ubiquitous Language](/co-je-ddd#ubiquitous-language-v-praxi) nelze odvodit z dokumentů; vzniká pouze v dialogu. Brandolini, Hofer a Schwentner přidávají k tomuto pozorování praktickou metodologii: konkrétní notaci, konkrétní harmonogram, konkrétní role v místnosti.

:::callout{type="note"}
### Co dostanete z workshopu, co z dokumentace nikdy {#why-workshop-heading}

- **Kontradikce v reálném čase.** Když dva experti řeknou totéž jinak, vidíte to a řešíte hned.
- **Slovník, který si lidé sami vytvořili.** Kód pak může používat přesně ty výrazy.
- **Sdílená paměť události.** Tým si pamatuje „když jsme řešili Stripe, padlo, že refundy jsou async“. Wiki se zapomene.
- **Hot Spots.** Místa, která doména nemá vyřešená. Z dokumentace byste je neodhalili, protože ta je vždy psaná jako „hotová“.
:::

## 04.02 Event Storming – co to je a co umí {#event-storming-co}

**Event Storming** zavedl italský konzultant Alberto Brandolini v roce 2013. Princip je přímočarý: účastníci v reálném čase pokládají na dlouhou stěnu (nebo Miro/Mural board) **oranžové sticky notes s doménovými událostmi vyjádřenými v minulém čase**. Postupně z nich vzniká časová osa toho, co se v doméně děje. Jak osa roste, přidávají se další barvy: modrá pro Commands, žlutá pro Actors, růžová pro Hot Spots. Obraz domény se postupně vyjasňuje.

Vznik techniky byl pragmatický. V roce 2012 ji Brandolini předvedl na Italian Agile Day jako *event-based modelling workshop*, tedy jako zkratku místo kreslení přesného UML diagramu. Jméno *EventStorming* jí dal až v létě 2013 po experimentech v Belgii a Polsku; v listopadu téhož roku vyšel první blogový post. Původní účel byl taktický: rychle najít hranice agregátů a kontextů. Strategické použití přišlo později. V přednášce *50.000 Orange Stickies Later* (2017) autor tu trajektorii shrnuje sám: z náhrady za diagram se stala učební pomůcka a nakonec platforma pro kolaborativní modelování od byznysu po implementaci.

Dnes technika existuje ve třech formátech. Kanonické názvy uvádí web eventstorming.com i Brandoliniho firma Avanscoperta; pro druhý a třetí se v komunitě vžily kratší zkratky *Process Level* a *Design Level*.

1. **Big Picture EventStorming** – strategická úroveň. Otázka: *„Co se v naší doméně vůbec děje?“* Cílem je objevit Bounded Contexty a hlavní procesy. Trvání 2–4 h, 15–30 účastníků.
2. **Process Modelling EventStorming** (komunitně *Process Level*) – operační úroveň. Otázka: *„Jak konkrétně běží jeden zvolený proces?“* Cílem je popsat jeden Bounded Context detailněji, včetně Commands, Actors, Policies a externích systémů. Zavádí přísnější gramatiku notace, do návrhu softwaru ale nevstupuje. Trvání 4–8 h.
3. **Software Design EventStorming** (komunitně *Design Level*) – taktická úroveň. Otázka: *„Jak se tato část modelu přeloží do tříd?“* Cílem jsou kandidáti na [agregáty](/zakladni-koncepty#aggregates), invariantní pravidla a první draft API. Trvání 2–6 h, typicky per BC.

Vaughn Vernon v *Domain-Driven Design Distilled* (Addison-Wesley, 2016, kap. 7) řadí Event Storming mezi nástroje, které urychlují učení a cestu k pracovnímu modelu domény. Doporučuje ho jako první techniku, kterou tým zavede, než se pustí do taktických DDD vzorů.

:::diagram{fig="04.2-A" title="Tři úrovně Event Stormingu – od strategického přehledu k taktickému návrhu" src="images/diagrams/17_event_storming/big_picture_levels.svg"}
:::

## 04.03 Notace – barvy a tvary {#notace}

Paletu barev popisuje Brandolini v knize *Introducing EventStorming*. Ta vychází na Leanpubu průběžně od roku 2013 a hotová dodnes není: k datu psaní uvádí Leanpub 70 % obsahu a poslední aktualizaci ze srpna 2021. Nejúplnější veřejně dostupnou legendu proto udržuje ddd-crew v *EventStorming Glossary & Cheat Sheet*. Každá barva má jeden význam a tým by se ho měl držet – jakmile začnete improvizovat, ztrácíte schopnost rychle „číst“ cizí mapu.

| Barva a tvar | Prvek | Formát | Příklad | Význam |
|---|---|---|---|---|
| **Oranžová** sticky | Domain Event | všechny | `OrderPlaced` | Něco, co se v doméně stalo. **Vždy v minulém čase.** |
| **Modrá** sticky | Command / Action | Process Modelling a výš | `PlaceOrder` | Záměr, který vede k eventu. Imperativ. Se stakeholdery se lépe drží slovo *Action*. |
| Malá **žlutá** sticky | Actor / Agent | všechny | `Customer`, `Cashier` | Kdo command iniciuje. Osoba, role, tým i celé oddělení. |
| **Šedá** sticky (kanonicky široká růžová) | External System | všechny | `Stripe`, `SendGrid` | Systém mimo naši doménu, se kterým komunikujeme. Patří sem i sdílený excelový soubor. |
| **Růžová**, natočená do kosočtverce | Hot Spot | všechny | „Co když platba selže?“ | Otázka, kontroverze, nevyjasněné místo. **Nediskutuje se** hned, jen se zaznamenává. Natočení je součást notace. |
| **Zelená** sticky | Opportunity | Big Picture | „Refund by mohl být samoobslužný“ | Pozitivní protějšek hot spotu: místo, kde vidíte příležitost. |
| Malá **červená / zelená** | Value | Big Picture | „−2 dny čekání“ | Záporná nebo kladná hodnota kroku pro zákazníka. |
| **Lila** větší sticky | Policy / Reactive logic | Process Modelling | „When OrderPlaced ⇒ send confirmation“ | Reaktivní pravidlo: „kdykoliv se stane X, udělej Y“. |
| **Zelená** sticky | Query Model / Read Model | Process Modelling | „Order detail page“ | Projekce, na základě které se actor rozhodne pro command. |
| Velká **žlutá** sticky | Constraint (dříve Aggregate) | Software Design | `Order` | Konzistenční hranice a pravidlo, které v ní musí platit. |
| **Fialová** sticky / čára | Bounded Context | Big Picture | „Ordering BC“ | Hranice mezi modely. Kanonicky se kreslí páskou nebo čarou, ne lepí. |

Barevné konvence se mezi facilitátory liší. Brandolini ve své knize značí hot spoty fialovou;
tabulka výše používá růžovou a fialovou vyhrazuje pro hranice kontextů. Před workshopem se proto
vyplatí legendu vyvěsit na stěnu, ať se skupina nedohaduje o významu barvy místo o doméně.

Dvě položky v tabulce potřebují komentář. Zelená nese ve dvou formátech dva různé významy, Opportunity
v Big Picture a Query Model v Process Modellingu; v jednom workshopu se oba prvky nepotkají, takže
záměna nehrozí. A velká žlutá lepka se v glosáři ddd-crew jmenuje **Constraint**, ne Aggregate. Posun
je jazykový, protože slovo *agregát* doménovému expertovi nic neříká. Tato kniha u pojmu agregát
zůstává, protože ho čtenář potřebuje pro kód; na cizí mapě se tentýž prvek jmenuje Constraint.

:::callout{type="pattern"}
### Pravidlo minulého času {#past-tense-rule-heading}

Hlavní jazykové pravidlo Event Stormingu: **doménové eventy se píšou v minulém čase**. Píšete `OrderPlaced`, ne `PlaceOrder`. `PaymentReceived`, ne `ReceivePayment`. `ShipmentDispatched`, ne `DispatchShipment`.

Důvod není kosmetický. Minulý čas vás *jazykově nutí* mluvit o tom, co už nastalo (a tedy o doménové realitě), místo toho, co bychom rádi (záměru či featuře). Tento posun perspektivy rozhoduje. Zabraňuje workshopu sklouznout do diskuse o tom, co bude umět formulář, a drží ho u toho, jak doména opravdu funguje. Brandolini tomu věnuje v knize samostatnou sekci o notaci: každá barva i slovesný čas má pevně daný význam.

Když si nejste jistí, zda je sticky event, command, nebo policy: zkuste si ji přečíst nahlas. Zní v minulém čase? Event. V imperativu? Command. „Když se stane X, dělej Y“? Policy.
:::

Pro online workshopy má Brandolini na Miroverse dvě vlastní šablony, [Process Modelling](https://miro.com/templates/eventstorming-process-modelling/) a [Software Design](https://miro.com/miroverse/eventstorming-software-design-template/). Komunitních šablon je v Miru víc, barvy v nich ale nemusí odpovídat legendě výše. Pro offline workshop odpovídají stejné barvy balení Post-It 3M (oranžová má kód *Vital Orange*, růžová *Power Pink*). Workshop spotřebuje stovky sticky notes, zásoba proto musí být velká.

## 04.04 Big Picture workshop – návod krok za krokem {#big-picture}

Big Picture je první workshop, který tým s novou doménou (nebo s migrací z existujícího CRUD systému, viz [kapitola o migraci](/migrace-z-crud)) udělá. Cílem není dokonalý model, ale **společná mapa** toho, co se v doméně děje, a identifikace 3–7 Bounded Contextů.

### 04.04.1 Příprava (-1 týden) {#bp-priprava}

Přípravu nelze obejít:

- **Místnost a stěna.** 6–8 m dlouhá rovná stěna bez dveří a nábytku v cestě. Brandolini požaduje *unlimited modeling surface*, souvislou plochu, kterou workshop nesmí vyčerpat. Místnost naopak potřebuje otevíratelné okno; skupina dvaceti lidí vydýchá vzduch dřív, než se čeká. Online varianta stojí na *frame* 12 000 × 4 000 px v Miro nebo Mural.
- **Účastníci.** Primární zdroje uvádějí pro Big Picture 15–30 lidí, typicky 25–30; ddd-crew mluví o 10 až 30 a více u jednoho papírového rolu. Velká skupina se neřeší redukcí lidí, ale tím, že se u stěny sama rozpadne na hloučky, které pracují paralelně. Musí tam být **alespoň 2 doménoví experti** (lidé, kteří doménu reálně provozují, ne PM-ové). Z vývojářské strany 3–5 vývojářů včetně tech leada, plus jeden facilitátor (viz níže). Sestava kolem deseti lidí se uřídí snadněji, je to ale vědomý kompromis: část pohledů na doménu v místnosti chybí.
- **Materiál.** 5–10 balíčků oranžových stickies (3M Post-It, 76×76 mm), 2 balíčky růžových, 2 modrých, 1 malý žlutý, 1 velký žlutý (Constraint), 1 šedý, 1 zelený, 1 lila (světle fialový), 1 tmavě fialový. Černé fixy Sharpie pro každého (žádná kuličková pera, text nebude čitelný z 2 m).
- **Catering.** Káva, voda, ovoce, oběd. Workshop unaví – bez cateringu padá energie po 90 minutách.
- **Pozvánka.** Účastníci dostanou předem jednostránkovou agendu. Doménoví experti se z ní dozvědí, že *nebudou prezentovat slidy*, ale budou „vyprávět příběh“.

### 04.04.2 Postup workshopu (2–4 hodiny) {#bp-postup}

1. **(10 min) Brief a startovací event.** Facilitátor v 5 minutách vysvětlí pravidla: oranžová = co se stalo, minulý čas, lepit kamkoliv. Pak workshop odstartuje tím, že napíše první event, o kterém ví, že nastává v doméně, a nalepí ho doprostřed stěny, například `OrderPlaced`.
2. **(20–30 min) Chaotic exploration.** Všichni dostanou stejně oranžových stickies (~15 každý) a píší události, které je napadnou. **Lepí kamkoliv** bez pořadí. Jde o záměrný chaos – chcete, aby si lidé vzpomněli na vše, ne aby okamžitě strukturovali. Facilitátor sbírá poznámky a tlačí lidi: „a co se stane potom? a předtím?“.
3. **(30 min) Enforcing the timeline.** Facilitátor začne přesouvat eventy doleva (dříve) a doprava (později). Vznikne časová osa. Účastníci do toho mluví: „ne, refund je až po reklamaci, posuň to“. Duplicitní eventy se slučují, ale jen se souhlasem účastníků.
4. **(30–45 min) Pivotal Events.** Facilitátor identifikuje *zlomové body*, tedy eventy, kolem kterých se přirozeně sdružuje skupina ostatních. V e-shopu typicky: `CustomerRegistered`, `OrderPlaced`, `PaymentSettled`, `ShipmentDispatched`, `OrderClosed`. Značí se svislou čarou napříč celou časovou osou, která ji rozdělí na úseky. Typicky 3–7 pivotal events.
5. **(30–45 min) Hot Spots.** Kdykoliv během workshopu zazní otázka, kterou nikdo neumí hned zodpovědět („Co když zákazník zaplatí dvakrát?“), **nediskutuje se**. Místo toho se napíše na růžovou sticky a nalepí přesně tam, kde otázka vznikla. Po 45 minutách máte typicky 8–15 hot spotů. To je *nejcennější výstup* Big Picture.
6. **(20–30 min) Bounded Context boundaries.** Facilitátor s týmem hledá místa, kde se mění slovník: kde *tentýž* pojem znamená něco jiného, kde končí jeden příběh a začíná jiný. Označí je fialovými stickies nebo silnými fialovými čarami. Typicky 3–7 BC.
7. **(15 min) Foto a transkripce.** Širokoúhlé foto stěny v originálu, pak detailní fotky po sekcích. Vše uložit do `docs/discovery/<datum>/` v repu. Online workshop: Miro export jako PNG i jako board (link).

### 04.04.3 Jak poznat hranici kontextu {#jak-poznat-hranici}

Krok 6 stojí a padá na tom, zda hranici poznáte, když na ni narazíte. Čtyři heuristiky, které na stěně fungují nejspolehlivěji:

- **Lingvistické švy.** Stejné slovo, jiný význam. „Objednávka“ pro prodejce znamená košík se slevami, pro sklad seznam položek k vychystání a pro účtárnu podklad faktury. Jakmile jedno slovo nese tři definice, máte před sebou tři kontexty, ne jeden.
- **Pivotní eventy (pivotal events).** Zlomová událost mění význam entity. Před `OrderPlaced` je objednávka editovatelným návrhem; po něm je závazkem vůči zákazníkovi. Entita, která událostí mění povahu, typicky překračuje hranici: z jednoho kontextu vstupuje do druhého.
- **Hranice oddělení.** Levný první odhad. Tam, kde si firma předává práci (prodej → sklad → účtárna), se obvykle mění slovník i pravidla. Slepě se ale přebírat nedají; org chart bývá historický, ne doménový.
- **Vlastnictví dat.** Otázka „kdo smí tohle pole změnit?“ má uvnitř jednoho kontextu jedinou odpověď. Pokud cenu produktu mění dva týmy podle dvou různých pravidel, nejde o jedno pole se dvěma editory, ale o dva koncepty ve dvou kontextech.

Žádná z heuristik není sama o sobě rozhodující. Hledáte místa, kde se jich protne víc najednou – lingvistický šev na hranici oddělení s vlastním vlastnictvím dat je téměř jistá hranice BC. Vazbu mezi pivotními událostmi a hranicemi kontextů rozebírá Brandolini v eseji *Discovering Bounded Contexts with EventStorming* ve sborníku *Domain-Driven Design: The First 15 Years* (Leanpub, 2017). Pojmenované vztahy mezi nalezenými kontexty pak popisuje kapitola [Context Mapping](/context-mapping).

### 04.04.4 Co máte na konci Big Picture {#bp-vystup}

- Časová osa s 30–100 doménovými eventy.
- 3–7 identifikovaných pivotal eventů.
- 3–7 vyznačených Bounded Contextů.
- 8–15 hot spotů jako budoucí tickety.
- Foto / Miro export.

Co **nemáte** a ani by nemělo být cílem: kompletní model, schéma databáze, finální seznam tříd. Big Picture je strategický nástroj – taktiku řeší až Software Design.

### 04.04.5 Online varianta – nastavení Miro/Mural {#bp-online}

Kolik se online ztratí, záleží na tom, který ze tří formátů děláte. Brandolini to rozepsal v textu *Remote EventStorming* (březen 2020). Software Design online snese nejvíc: malý rozsah, 90 minut, technické publikum. Process Modelling jde podmínečně: půlden, 5–15 lidí, tým už formát zná z prezenční verze a každá třetí session je naživo. K Big Picture má jedinou větu: *„Don't even try.“* Vlastní pokus označil za dysfunkční i s expertními účastníky, protože online mizí paralelní konverzace u části stěny, řeč těla i celodenní ponoření. Doporučuje také remote sezení vůbec nenazývat EventStormingem, aby si tým se jménem techniky nespojil špatnou zkušenost.

Přesto se online Big Picture dělá, protože doménoví experti sedí ve třech městech a alternativou nebývá offline workshop, ale žádný workshop. Následující postup je vědomý kompromis se známou cenou. Co se dodržet dá:

1. **Frame 12 000 × 4 000 px.** Týmy často podcení velikost plátna. Big Picture na 50+ eventů potřebuje hodně horizontálního prostoru, jinak se účastníci začnou navzájem překrývat. V Miro založte nový board a první frame udělejte explicitně s těmito rozměry, parametr *Frame size*.
2. **Předpřipravená paleta.** Vlevo na boardu položte 7–9 zdrojových stickies (jednu od každé barvy) a kolem nich rámeček s popiskem „*Kopírujte odsud (Ctrl+D duplikuje)*“. Účastníci si stickies kopírují, místo aby pracně otevírali sticky picker.
3. **Voice-only, kamery vypnuté.** Kamery odvádějí pozornost od boardu; všichni se musí dívat na stejné plátno. Výjimka: úvodních 5 minut představení a pak při hot-spot diskusích.
4. **Breakout místnosti pro dvě fáze.** Při Pivotal Events fázi rozdělte skupinu do 2–3 breakout místností po 4 lidech. Každá skupina si v Miru pracuje na jednom segmentu časové osy. Po 20 minutách se vše vrátí zpět do hlavní místnosti a synchronizuje. Bez breakoutů online workshop kolabuje na jednoho aktivního a pět pasivních pozorovatelů.
5. **Přestávky každých 60 minut.** Online unavuje rychleji než offline. Vložte 10minutové přestávky a nezkracujte je.
6. **Asynchronní příprava.** Pošlete účastníkům 24 hodin předem otevřený Miro board s úvodním textem a požádejte je, aby *před* workshopem nalepili 5–10 eventů, které je napadnou. Workshop pak nezačíná u prázdné stěny.

### 04.04.6 Kdy Big Picture *nedělat* {#bp-when-again}

- **Zralý produkt s ustáleným modelem.** Když tým pracuje v jedné doméně tři roky a má aktuální Context Map, nový Big Picture typicky neodhalí nic nového. Víc přinese Process Modelling nad konkrétním bolavým BC.
- **Tým není ochotný diskutovat.** Big Picture stojí na otevřené debatě. Pokud je v týmu strach z konfrontace nebo silně hierarchická kultura, musí nejdřív padnout tato bariéra. Jinak workshop produkuje falešný konsenzus.
- **Doménoví experti jsou v různých časových pásmech bez přesahu.** Big Picture musí proběhnout najednou. Když se nenajde 3–4hodinové okno, kdy jsou všichni hlavní hráči online, náhradou je série Domain Storytelling sezení 1:1 se sloučenými výstupy.

:::callout{type="warn"}
### Facilitátor musí být neutrální {#facilitator-rule-heading}

Facilitátor drží proces, ne obsah. Jakmile má na výsledném modelu vlastní zájem, protlačí ho – vědomě, nebo nevědomky. Tech lead je nejčastější případ, externí konzultant se silným názorem ale škodí stejně. Koho rolí pověřit a jak situaci řešit, rozebírá [anti-vzor v sekci 04.08](#anti-tech-lead-heading).
:::

## 04.05 Process Modelling – jeden BC, hlubší detail {#process-level}

Po Big Picture máte 3–7 Bounded Contextů. Process Modelling si vždy bere **jeden BC najednou** a zhušťuje ho. Cílem je dostat se ke struktuře, která se v Symfony reálně přeloží do `Command` tříd, `Handler`ů a `Event`ů na message busu (podrobně v kapitole [CQRS](/cqrs)).

### 04.05.1 Co Process Modelling přidává oproti Big Picture {#pl-co-pridava}

K eventům přibývají modré **Commands**, tedy záměry, které k nim vedou, a žlutí **Actors**, kteří je spouštějí. Lila stickies nesou **Policies**, reaktivní pravidla typu „kdykoliv X, udělej Y“. Šedá patří **External Systems**, třetím stranám. A zelené **Read Models** zachycují projekce, podle kterých se actor rozhoduje.

### 04.05.2 Postup (4–8 hodin per BC) {#pl-postup}

1. Otevřete **jen události a hot spoty** z Big Picture, které spadají do cílového BC. Zbytek skryjte (jiný frame v Miru, papírová stěna jen pro tento BC).
2. Pro každou událost zpětně doplňte: **jaký command k ní vedl?** a **kdo ten command vyvolal?** Vznikne sekvence `Actor → Command → Event`.
3. Pro každou událost dopředně doplňte: **co se v reakci stane?** Lila (světle fialové) policy stickies. „Kdykoliv `OrderPlaced`, pošli potvrzovací mail“.
4. Identifikujte commands, které volají externí systémy nebo na ně reagují (šedé). „Po `PaymentRequested` volám Stripe, čekám na `StripePaymentSucceeded`“.
5. Pro každý command identifikujte, jaký **read model** actor potřebuje vidět, aby command spustil. „Cashier potvrdí objednávku, když vidí, že platba prošla – read model `OrderDetail` musí obsahovat `paymentStatus`“.
6. Aktualizujte hot spoty. Některé z Big Picture se na této úrovni vyřeší, jiné se rozpadnou na podrobnější (např. „Co když Stripe vrátí 500?“).

### 04.05.3 Příklad – Ordering BC e-shopu {#pl-priklad}

Sekvence pro hlavní scénář:

:::code{language="plaintext" filename="Sekvence Process Modelling – hlavní scénář"}
Customer (actor)
    → PlaceOrder (command)
        → OrderPlaced (event)
            → "Reserve stock" (policy)
                → ReserveStock (command, jiný BC: Warehouse)
            → "Send confirmation email" (policy)
                → SendGrid (external system)
            → "Initiate payment" (policy)
                → ChargeCard (command, jiný BC: Payment)
                    → Stripe (external system)
                    → PaymentReceived (event)
:::

Sekvence ještě není kód, slouží jako mapa pro implementaci. Ale je z ní **okamžitě vidět**, že budete potřebovat:

- Application Service `PlaceOrderHandler` v Ordering BC.
- Process Manager, který koordinuje `OrderPlaced → ReserveStock → ChargeCard` přes BC hranice (podrobně v kapitole [Ságy a process managery](/sagy-a-process-managery)).
- Adaptér k Stripe (anti-corruption layer).
- Read model `OrderDetailView` pro UI.

### 04.05.4 Co máte na konci Process Modellingu {#pl-vystup}

- Pro každý BC: detailní mapu commands, events, policies, externals, read models.
- Seznam **kandidátů na Application Services** (1 command typicky = 1 service / 1 handler).
- Seznam **kandidátů na ságy / process managery** (každý policy přes hranici BC).
- Seznam externích systémů, pro každý plánovaný ACL.
- Aktualizovaný seznam hot spotů, vyřešené i nové.

## 04.06 Software Design – pro každý BC zvlášť {#design-level}

Software Design je nejtaktičtější formát Event Stormingu a první, který se přibližuje kódu. Cílem je pro každý Bounded Context identifikovat **agregáty**, jejich **invariantní pravidla** a způsob, jakým commands modifikují stav agregátu.

### 04.06.1 Co Software Design přidává {#dl-co-pridava}

- **Constraints**, v této knize agregáty (velké žluté lepky) – konzistenční hranice. Každý command má jeden agregát, který ho obsluhuje.
- **Invariants** – pravidla, která agregát musí dodržet. Píšou se jako bullet pointy na sticky agregátu.
- **Pre-conditions** – co musí být splněno, aby command směl projít.

### 04.06.2 Postup (2–6 hodin per BC) {#dl-postup}

1. Vezměte mapu z předchozí úrovně a pro každý **command** položte velkou žlutou sticky agregátu, který ho obsluhuje. Stejný agregát pro více commandů je v pořádku; znamená to jen, že třída bude mít víc metod.
2. Pod každý agregát vypište jeho **invarianty**. „Order: nemůže být confirmed bez aspoň jedné položky“, „Order: po cancelled už nelze confirm“, „Order: součet item.quantity * item.price = total“.
3. Pro každý command vyznačte **pre-conditions**: „`ConfirmOrder` vyžaduje, aby `Order` byl ve stavu `Draft` a měl alespoň jeden item“.
4. Označte hot spoty, které vám chybí pro úplnou specifikaci agregátu („Co když má položka nulovou cenu? Jde o legitimní freebie nebo chybu?“).

### 04.06.3 Mapping z workshopu do Symfony {#dl-mapping}

Workshop: `Customer → PlaceOrder → Order Aggregate → OrderPlaced`

Symfony / PHP draft (toto je první draft, ne finální kód):

:::code{language="php" filename="Symfony / PHP draft Ordering BC"}
// Application/Command/PlaceOrderCommand.php
namespace App\Ordering\Application\Command;

final readonly class PlaceOrderCommand
{
    public function __construct(
        public CustomerId $customerId,
        /** @var list<OrderItemDto> */
        public array $items,
    ) {}
}

// Domain/Order.php
namespace App\Ordering\Domain\Model;

use App\SharedKernel\Domain\AggregateRoot;

final class Order extends AggregateRoot
{
    /** @var list<OrderItem> */
    private array $items = [];

    private function __construct(
        private readonly OrderId $id,
        private readonly CustomerId $customerId,
        private OrderStatus $status,
    ) {}

    public static function place(OrderId $id, CustomerId $customerId): self
    {
        $order = new self($id, $customerId, OrderStatus::Draft);
        $order->record(new OrderPlaced($id, $customerId));

        return $order;
    }

    public function id(): OrderId
    {
        return $this->id;
    }

    public function addItem(ProductId $productId, int $quantity, Money $unitPrice): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new InvalidOrderStateTransitionException('Cannot add items to a non-draft order');
        }

        $this->items[] = new OrderItem($productId, $quantity, $unitPrice);
    }

    public function confirm(): void
    {
        // Invariant z workshopu: confirm jen ze stavu Draft
        if ($this->status !== OrderStatus::Draft) {
            throw new InvalidOrderStateTransitionException('Cannot confirm a non-draft order');
        }

        // Invariant z workshopu: objednávka musí mít aspoň jednu položku
        if ($this->items === []) {
            throw new EmptyOrderException();
        }

        $this->status = OrderStatus::Confirmed;
        $this->record(new OrderConfirmed($this->id));
    }

    public function cancel(string $reason): void
    {
        // Invariant z workshopu: zrušit lze draft i potvrzenou objednávku
        if ($this->status !== OrderStatus::Draft && $this->status !== OrderStatus::Confirmed) {
            throw new InvalidOrderStateTransitionException('Cannot cancel a shipped order');
        }

        $this->status = OrderStatus::Cancelled;
        $this->record(new OrderCancelled($this->id, $reason));
    }

    public function totalAmount(): Money
    {
        if ($this->items === []) {
            throw new EmptyOrderException('Cannot calculate total of an empty order');
        }

        $total = Money::zero($this->items[0]->unitPrice()->currency);

        foreach ($this->items as $item) {
            $total = $total->add($item->unitPrice()->multiply($item->quantity()));
        }

        return $total;
    }
}

// Application/Handler/PlaceOrderHandler.php
namespace App\Ordering\Application\Handler;

#[AsMessageHandler]
final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private OrderIdGenerator $ids,
    ) {}

    public function __invoke(PlaceOrderCommand $cmd): OrderId
    {
        $order = Order::place($this->ids->next(), $cmd->customerId);

        foreach ($cmd->items as $item) {
            $order->addItem($item->productId, $item->quantity, $item->unitPrice);
        }

        $this->orders->save($order);

        return $order->id();
    }
}
:::

**Každý prvek z workshopu má v kódu protějšek.** Command sticky → `PlaceOrderCommand`. Constraint (agregát) → třída `Order`. Invariant z bullet pointu → `throw` v doménové metodě. Event sticky → `OrderPlaced` zaznamenaný přes `record()`.

Překlad ale není mechanický. Tři rozhodnutí padají mimo místnost a na stěně pro ně není barva:

- **Kdy se eventy publikují.** Ukázka je jen zaznamenává do agregátu. Kdo je pošle na sběrnici a jak se to sladí s commitem databázové transakce, řeší [Outbox Pattern](/outbox-pattern). Dispatch hned za `save()` je dual-write a rozbije se při první výjimce mezi zápisem a odesláním.
- **Jestli command něco vrací.** `__invoke()` zde vrací `OrderId`. Volající tu hodnotu dostane jen přes `HandledStamp` nebo `HandleTrait` a pouze u synchronně zpracovaných zpráv; na asynchronním transportu běží handler ve worker procesu a návratová hodnota se k odesílateli nedostane.
- **Na jakou sběrnici to jde.** Symfony má `MessageBusInterface`. Oddělená command a event sběrnice je až věc konfigurace `framework.messenger.buses` a aliasů, podrobně v kapitole [CQRS](/cqrs).

:::callout{type="pattern"}
### Komentář v kódu = pojítko s workshopem {#design-level-comment-heading}

Když píšete invariantní check v doménové třídě, dejte k němu komentář s odkazem na workshop:

:::code{language="php" filename="src/Ordering/Domain/Model/Order.php (fragment)"}
// Invariant Order-3 (workshop 2026-04-29):
// "Order nemůže být confirmed bez aspoň jedné položky."
// Hot spot Order-7 (otevřený): co když je položka backorder?
if ($this->items === []) {
    throw new EmptyOrderException();
}
:::

Tato vazba má praktický dopad. Za půl roku nový vývojář ví, odkud pravidlo pochází, a může si ho ověřit u doménového experta. Nesmaže ho v dobré víře jako „divnou validaci“.
:::

## 04.07 Domain Storytelling – alternativa pro malé týmy {#domain-storytelling}

**Domain Storytelling** představili Stefan Hofer a Henning Schwentner v knize stejného jména (Addison-Wesley, 2021). Stejně jako Event Storming řeší extrakci doménových znalostí, ale jinou cestou: místo časové osy událostí kreslíte **příběh** o práci doménového experta ve standardizované piktogramové notaci.

### 04.07.1 Notace {#ds-notace}

- **Actor** – postavička panáčka. Kdo v doméně něco dělá. „Customer“, „Cashier“, „Warehouse worker“. Může to být i jiný systém nebo celá organizace. Každý actor se v jednom příběhu kreslí **jen jednou**.
- **Work Object** – piktogram věci, se kterou actor pracuje. Dokument (objednávka), peníze, e-mail, balík, zboží. Kreslí se **znovu u každé aktivity**, i když jde o tutéž věc, protože se během příběhu mění její stav nebo médium.
- **Activity** – šipka se slovesem, opatřená pořadovým číslem. Číslo patří **aktivitě**, ne jednotlivé šipce: krok, ve kterém actor předává work object dalšímu actorovi, se kreslí dvěma šipkami, ale nese jedno číslo.
- **Annotation** – textová bublina s poznámkou: varianta, volitelný krok, možná chyba, doménový pojem.
- **Group** – rámeček kolem skupiny aktivit. Ohraničuje opakovaný úsek, lokalitu, organizační hranici nebo subdoménu.

Věta příběhu má pevnou gramatiku: **kdo** (actor) dělá **co** (activity) **s čím** (work object) **s kým** (jiný actor). Jeden příběh se drží v rozmezí deseti až dvaceti kroků. Delší se rozpadne na dva.

### 04.07.2 Scope – jaký příběh vlastně kreslíte {#ds-scope}

Domain story bez určeného scope dopadne tak, že si polovina místnosti myslí, že popisuje dnešek, a druhá polovina, že návrh. Hofer se Schwentnerem proto každý příběh zařazují ve třech osách:

1. **Granularita.** Hrubý příběh (coarse-grained) dává přehled o celém procesu. Jemný (fine-grained) rozepisuje jeden úsek do detailu, ve kterém se dá programovat.
2. **Čas.** AS-IS zachycuje, jak práce probíhá dnes. TO-BE, jak má probíhat po změně.
3. **Čistota domény.** Pure příběh popisuje doménu bez softwaru, digitalized včetně systémů, které v ní figurují. Jeden obrázek je buď jedno, nebo druhé; míchat obojí nelze.

Typická cesta projektu vede třemi příběhy. Jemný AS-IS pure ukáže, jak lidé pracují dnes. Hrubý AS-IS pure z toho udělá mapu. Jemný TO-BE digitalized popíše cílový stav i se softwarem. Trojici os se vyplatí napsat do rohu plátna dřív, než padne první šipka. Jinak se o ni skupina pohádá v půlce příběhu.

### 04.07.3 Konkrétní příklad – proces objednávky v e-shopu {#ds-priklad}

Story *„Customer places an order“* v Domain Storytelling notaci, čtená v pořadí čísel:

1. **Customer** →(1) *browses* → **Catalog**
2. **Customer** →(2) *adds product to* → **Cart**
3. **Customer** →(3) *submits* → **Order** → **Order System**
4. **Order System** →(4) *requests payment from* → **Payment Gateway** (annotation: „Stripe; async webhook“)
5. **Payment Gateway** →(5) *confirms payment to* → **Order System**
6. **Order System** →(6) *sends* → **Confirmation Email** → **Customer**
7. **Order System** →(7) *creates* → **Shipment Order** → **Warehouse**

Sedm vět, sedm čísel. Krok 3 se kreslí dvěma šipkami (od actora k work objectu a od work objectu k druhému actorovi), pořadové číslo ale nese celá aktivita, ne šipka. Kresba je úmyslně jednoduchá – ručně nakreslené piktogramy nebo nástroj [egon.io](https://egon.io/) (open source, v prohlížeči). Příběh je čitelný shora dolů ve sledu čísel a každá aktivita má slovesné jméno.

### 04.07.4 Domain Storytelling vs. Event Storming – kdy zvolit co {#ds-vs-es}

| Kritérium | Event Storming | Domain Storytelling |
|---|---|---|
| Velikost skupiny | 15–30 pro Big Picture, 5–15 pro Process Modelling | Malá: vypravěč, posluchači, moderátor s modelářem |
| Doba trvání | 2–8 h | Jedno kratší sezení na příběh |
| Šíře záběru | Celý systém / podstatná část | Jeden konkrétní proces |
| Hloubka záběru | Mělčí, ale široký | Hluboká, úzká |
| Hlavní výstup | Bounded Contexty + eventy | Sekvence kroků s actor a work object |
| Dovednost facilitátora | Vyšší (mnoho lidí, chaos) | Nižší (lineární proces) |
| Doporučený nástroj | Stěna + Post-It nebo Miro | egon.io, papír, Miro |
| Kdy zvolit | Nový BC, migrace, strategický přehled | Hluboká diskuse o jednom procesu, malý tým, omezený čas |

Hofer a Schwentner v knize zdůrazňují, že obě techniky se **nekonkurují**, ale doplňují. Event Storming ukáže, jaké procesy v doméně existují (širokoúhlý objektiv). Domain Storytelling v každém z nich pak odkryje detail (teleobjektiv). Doporučují kombinovat: Big Picture pro strategický přehled, Domain Storytelling pro jednotlivé hlavní procesy a Process Modelling se Software Designem pro implementaci.

:::callout{type="note"}
### Nástroje pro Domain Storytelling {#ds-tooling-heading}

- **[egon.io](https://egon.io/)** – open-source nástroj přímo v prohlížeči. Drag-and-drop actor / work object / activity, export do SVG. Vhodný pro online workshopy a archivaci.
- **Miro / Mural** – pomocí vlastních tvarů. Méně specializované, ale tým ho už typicky zná.
- **Papír A1 + tlustý fix** – pro offline workshop nejrychlejší. Po kresbě vyfotit a uložit do `docs/discovery/`.

Knihu *Domain Storytelling* doplňuje volně přístupný web [domainstorytelling.org](https://domainstorytelling.org/) s vzorovými stories i šablonami.
:::

### 04.07.5 Praktický egon.io walkthrough {#ds-egon-walkthrough}

[egon.io](https://egon.io/) je open-source webová aplikace (postavená na bpmn-js), která Domain Storytelling notaci plně implementuje. Pro tým, který nechce kupovat Miro licence nebo tahat papír, je to vhodný nástroj. Postup pro první sezení:

1. **Otevřete egon.io v prohlížeči** – nevyžaduje registraci. Vlevo nahoře je toolbar s ikonkami: actor (panáček), work object (obdélník), activity (šipka).
2. **Začněte s actorem.** Přetáhněte ikonu „person“ na plátno a pojmenujte ji rolí, ne osobou: `Customer`, ne `Petr Novák`. Jméno se v exportu objeví u každé aktivity, takže na jeho volbě záleží.
3. **Přidejte work object.** Druhý nejčastější tvar – věc, se kterou actor pracuje. V e-shopu typicky `Cart`, `Order`, `Invoice`, `ShipmentLabel`.
4. **Spojte je activity.** Klik na actora, drag na work object; egon.io vytvoří očíslovanou šipku. Slovesné jméno (*browses*, *submits*, *confirms*) se píše do labelu šipky.
5. **Buďte struční.** Jeden Domain Storytelling diagram by měl mít **jeden lineární příběh** s 5–15 aktivitami. Když jich máte 30, rozdělte ho na dva diagramy.
6. **Export do SVG.** Menu vpravo nahoře → Download → SVG. Soubor pojmenujte `<datum>-<story-name>.svg` a uložte do `docs/discovery/<datum>/storytelling/`. SVG je textový formát, ve kterém git přehledně zobrazuje rozdíly a v PR review vidíte změny.

Egon.io ukládá příběh ve vlastním textovém formátu `.egn` (vedle exportu `.egn.svg`). Soubor patří do repa vedle SVG. Příběh tak lze verzovat, po změně znovu otevřít v egon.io a SVG přegenerovat.

## 04.08 Anti-vzory workshopů {#anti-vzory}

Workshop bez přípravy a pevného vedení je horší než žádný. Vytvoří zdání shody, která neexistuje, a tým podle něj implementuje chybný model. Brandolini vede na eventstorming.com katalog sedmnácti pojmenovaných patternů a anti-patternů; kde se s ním následující vzory kryjí, je kanonické jméno uvedeno v závorce. Zde je seznam nejčastějších a jejich řešení.

:::callout{type="warn"}
### „Doménoví experti nemají čas, uděláme to bez nich.“ {#anti-no-experts-heading}

**Bez doménových expertů jde jen o brainstorming vývojářů**, kteří si vymýšlejí, jak doména funguje. Výstup vypadá podobně, ale je nepoužitelný – chybí mu validní kontradikce a hot spoty.

**Řešení:** místo čtyř hodin stačí *90 minut Big Picture*. Téměř vždy se to dá v kalendáři vyargumentovat. A pokud opravdu nikdo z expertů nemůže, workshop se odkládá – rezervovaná místnost není důvod ho konat.
:::

:::callout{type="warn"}
### „Začneme rovnou u Software Designu, na Big Picture nemáme čas.“ {#anti-skip-bp-heading}

Kanonicky *Rush to the goal*. Když přeskočíte Big Picture, modelujete agregáty bez znalosti, ve kterém Bounded Contextu leží. Výsledek: *God Aggregate* typu `Order`, který obsahuje payment status, shipping data, fakturační adresu a kupóny, protože nikdo neoznačil, že tyto pojmy patří do různých BC.

**Řešení:** Big Picture proběhne, i kdyby mělo trvat jen 90 minut. Bez něj vede Software Design skoro vždy k nesprávnému rozdělení agregátů.
:::

:::callout{type="warn"}
### „Workshop facilituje senior dev / tech lead.“ {#anti-tech-lead-heading}

Senior vývojář při facilitaci podsouvá technický pohled. Eventy strukturuje podle toho, co se dá hezky implementovat, ne podle toho, jak doména reálně funguje. Doménoví experti to vycítí a začnou potlačovat svůj jazyk ve prospěch toho „technicky čistého“.

**Řešení:** rozhodující není role, ale neutralita. Nejsnáz ji udrží PM, agile coach, designer nebo externí konzultant. Externista se silným názorem na architekturu ale škodí stejně jako tech lead. Když jinou možnost nemáte, domluvte se předem, že facilitátor obsah nenavrhuje a promluví, jen když se ho někdo přímo zeptá. Brandolini k tomu přidává pattern *Time-boxed Leadership*: styl vedení se během workshopu mění, obsah ale zůstává skupině.
:::

:::callout{type="warn"}
### „Zápis = Word dokument.“ {#anti-word-heading}

Když převedete vizuální workshop do lineárního textu, ztratíte 80 % informace – rozložení v prostoru, vztahy, blízkost hot spotů k eventům. Wordový dokument o osmi stranách nikdo nepřečte; foto a Miro export se otevřou na 5 sekund a všichni si vzpomenou, co kde stálo.

**Řešení:** širokoúhlé foto stěny v originálu (4K), detailní fotky po sekcích, Miro link s read-only přístupem pro celý tým. Vše do `docs/discovery/<datum>/` v repu, vedle čistého `events.md` s prostým seznamem objevených eventů (řádek na event).
:::

:::callout{type="warn"}
### „Po workshopu se to nezapíše do kódu.“ {#anti-no-followup-heading}

Workshop, který skončí slávou, fotkou stěny a sdílením ve Slacku, ale jehož výstup se nepromítne do kódu, je za 3 měsíce zapomenutý. Slovník, který v místnosti vznikl, se v kódu nepoužije, a Ubiquitous Language opět degeneruje.

**Řešení:** první PR po workshopu pojmenuje třídy přesně podle workshopu (`OrderPlaced`, ne `OrderSavedEvent`) a doplní komentáře s odkazem na hot spoty. Jeden hot spot z workshopu = jeden ticket v issue trackeru.
:::

:::callout{type="warn"}
### „Big Picture musíme dotáhnout k dokonalosti.“ {#anti-perfectionism-heading}

Kanonicky *Deliverable Obsession*. Big Picture nemá být dokonalý; je to první mapa neznámého území. Pokud na něm strávíte 8 hodin a budete debatovat o tom, zda `OrderShipped` je `ShipmentDispatched` nebo `OrderDispatched`, ztrácíte čas. Rozhodnutí padne až v Process Modellingu, kde uvidíte kontext.

**Řešení:** timebox jsou čtyři hodiny. Pak workshop končí, i kdyby polovina hot spotů byla nevyřešená – to je v pořádku. Hot spoty *mají* zůstat otevřené.
:::

## 04.09 Co Event Storming neumí {#co-neumi}

Předchozí sekce je o tom, jak workshop pokazí lidé. Následuje seznam toho, co technika neumí ani ve chvíli, kdy ji vedete správně.

**Happy path vytlačí zbytek.** Časová osa se staví jako příběh a příběhy se vyprávějí od začátku do úspěšného konce. Storna, částečné refundy, ruční zásahy podpory a timeouty externích systémů se na stěnu dostanou jen tehdy, když se na ně někdo cíleně zeptá. Obrana stojí jednu otázku, položenou po dokončení osy u každé pivotní události: „co se stane, když tohle selže?“.

**Nefunkční požadavky nemají kam sednout.** Latence, dostupnost, retenční lhůty, GDPR, objem dat, cena provozu. Žádná barva pro ně v notaci není a workshop je systematicky přehlíží. Pokud na nich stojí architektura, patří do samostatného sezení; Event Storming je nenahradí.

**Mapa žije jen tak dlouho, dokud ji někdo udržuje.** Stěna je artefakt jednoho dne. Bez převodu do repa a do kódu z ní za tři měsíce zbude fotka, na kterou se nikdo nedívá. Sekce 04.10 proto není administrativní příloha workshopu, ale podmínka toho, aby po něm něco zbylo.

**Výsledek závisí na facilitátorovi víc, než je zdrávo.** Tatáž skupina se stejnou doménou vyprodukuje se dvěma facilitátory dvě různé mapy. Technika sama žádnou korekci neobsahuje, a proto se doporučuje mapu po pár týdnech znovu otevřít s odstupem, nejlépe s někým, kdo u prvního workshopu nebyl.

Poslední limit je nejtišší. Konsenzus dvaceti lidí, ze kterých patnáct sedí v jednom oddělení, popisuje pohled toho oddělení, ne doménu. Hot spoty tu díru odhalí jen zčásti: ptají se na to, co skupina *ví*, že neví.

Nic z toho není důvod workshop nedělat. Je to důvod nečekat, že z něj vypadne hotová specifikace.

## 04.10 Po workshopu – co s výstupem {#po-workshopu}

Workshop bez follow-upu je promarněná investice. Zde je seznam **4 konkrétních artefaktů**, které musí jít do repa do 24 hodin po skončení workshopu.

### 04.10.1 Foto / Miro link {#post-1-foto}

Širokoúhlé foto stěny v originálu, detailní fotky po sekcích, Miro export PNG i link. Uložit do:

:::code{language="plaintext" filename="docs/discovery/<datum>/"}
docs/discovery/2026-04-29-big-picture/
├── 00-wide-angle.jpg
├── 01-customer-area.jpg
├── 02-payment-area.jpg
├── 03-shipment-area.jpg
├── 99-miro-export.png
└── README.md
:::

`README.md` obsahuje datum, účastníky, BC a link na živý Miro board.

### 04.10.2 Aktualizovaná Context Map {#post-2-bc}

Z fialových BC stickies aktualizujte [Context Map](/context-mapping) v `docs/context-map.png`. Pokud ji ještě nemáte, vytvořte ji teď. Pro každý BC zkontrolujte, který tým ho vlastní a do které kategorie ([core / supporting / generic](/subdomeny)) spadá.

### 04.10.3 Seznam doménových eventů {#post-3-events}

Plain-text soubor s jedním eventem na řádek. Slouží jako reference pro budoucí PR. Když vývojář přidává nový event, ověří v něm, zda už nějaký podobný neexistuje.

:::code{language="plaintext" filename="docs/discovery/2026-04-29-big-picture/events.md"}
# docs/discovery/2026-04-29-big-picture/events.md

## Ordering BC
- OrderPlaced
- OrderConfirmed
- OrderCancelled
- OrderItemAdded
- OrderItemRemoved

## Payment BC
- PaymentRequested
- PaymentReceived
- PaymentFailed
- PaymentRefunded

## Shipment BC
- ShipmentCreated
- ShipmentDispatched
- ShipmentDelivered
- ShipmentReturned
:::

### 04.10.4 Hot Spots → tickety {#post-4-hotspots}

Každý hot spot z workshopu = jeden ticket v issue trackeru, ve formátu „*Discovery question*“ nebo „*Domain question*“, s odkazem na fotku/Miro. Ticket dostane doménový expert, ne vývojář – odpověď leží v doméně, ne v kódu.

:::code{language="plaintext" filename="Šablona ticketu z hot spotu"}
Title: [Discovery] Co když platba selže po vytvoření zásilky?
Labels: discovery, ordering-bc
Assignee: @business-expert-name
Description:
Hot spot z Big Picture workshopu 2026-04-29 (foto: docs/discovery/2026-04-29-big-picture/02-payment-area.jpg).
Tým si není jist, zda se zásilka vrací zpět, nebo se účet zákazníka jen označí jako neuhrazený.
Potřebujeme jednoznačné rozhodnutí před implementací Process Manager v Ordering BC.
:::

### 04.10.5 Doporučená struktura repa po prvním workshopu {#post-5-repo}

Aby výstup workshopu nezapadl ve Slacku, založte v Symfony projektu rovnou tuto adresářovou strukturu. Každý soubor má jasný účel a nikdo nemusí hádat, kam co patří:

:::code{language="plaintext" filename="Doporučená struktura repa"}
my-symfony-app/
├── docs/
│   ├── discovery/
│   │   └── 2026-04-29-big-picture/
│   │       ├── 00-wide-angle.jpg
│   │       ├── 01-customer-area.jpg
│   │       ├── 02-payment-area.jpg
│   │       ├── 03-shipment-area.jpg
│   │       ├── 99-miro-export.png
│   │       ├── events.md           ← seznam doménových eventů (text)
│   │       ├── hot-spots.md        ← otázky k vyřešení
│   │       └── README.md           ← účastníci, datum, link na Miro
│   ├── context-map.png             ← aktualizovaná z workshopu
│   ├── context-map.md              ← textový popis vztahů mezi BC
│   └── ubiquitous-language.md      ← rostoucí slovník pojmů
├── src/
│   ├── Ordering/                   ← jeden BC z workshopu = jeden namespace
│   ├── Payment/
│   └── Shipment/
└── ...
:::

Adresář `docs/discovery/` je **append-only**: staré workshopy nemažete, jen přidáváte nové s novým datem. Tým tak má historii, jak se mapa domény vyvíjela. Re-storming, tedy opakovaný workshop nad toutéž doménou (sekce 04.11), pak porovná `docs/discovery/2026-04-29-big-picture/events.md` s `docs/discovery/2026-10-15-re-storming/events.md`.

Adresáře `src/Ordering`, `src/Payment`, `src/Shipment` zrcadlí tři z pěti fialových stickies z workshopu, ty Bounded Contexty, které dostaly vlastní kód; jejich vnitřní členění podle vrstev popisuje [struktura podle subdomén](/subdomeny#symfony-implications). Když nový vývojář otevře projekt, vidí strukturu odpovídající tomu, co viděl na fotce ze workshopu. Tato vazba mezi *artefaktem v repu* a *artefaktem ze stěny* chrání jazyk workshopu před tím, aby se po půl roce vytratil z kódu.

### 04.10.6 První PR po workshopu {#post-6-prvni-pr}

První pull request po workshopu by měl být **malý a explicitně značený** jako follow-up, ne velký commit s implementací první feature. Doporučená velikost:

- Vytvoření `docs/discovery/<datum>/` se všemi výstupy workshopu.
- Aktualizace `docs/context-map.md` a `docs/ubiquitous-language.md`.
- Založení prázdných namespace adresářů (`src/<BC>/Domain/`) s krátkým `README.md` v každém: kdy vznikl, z jakého workshopu, co obsahuje.
- Tickety pro hot spoty (případně přes script, který je vytvoří hromadně).

Žádný kód doménové logiky. Tento PR má jediný úkol: **uložit společnou paměť workshopu do repa, než ji všichni zapomenou.** Implementace prvního agregátu přijde v dalším PR, který už staví na Software Designu.

:::callout{type="pattern"}
### Workshop commit message konvence {#commit-disclaimer-heading}

Pro PR navazující na workshop používejte konzistentní commit message, aby je šlo v gitu vyhledat:

:::code{language="plaintext" filename="git commit message konvence"}
docs(discovery): big picture workshop 2026-04-29

Účastníci: 4 doménoví experti, 5 vývojářů, 1 PM, 1 facilitátor.
Identifikováno: 5 BC (Ordering, Payment, Shipment, Catalog, Identity),
               12 hot spotů, 47 doménových eventů.
Miro: https://miro.com/board/xyz123 (read-only)
Foto: docs/discovery/2026-04-29-big-picture/
:::

Za rok, když si potřebujete dohledat „kdy jsme rozhodli, že refunds patří do Payment BC, ne do Ordering“, `git log --grep="discovery"` vás dovede k odpovědi za 5 vteřin.
:::

## 04.11 Pravidelné re-stormingy {#re-storming}

Doména se vyvíjí. Pivotní událost, která dnes platí (`OrderPlaced`), může za rok ztratit význam, protože podnikání přešlo na model *subscription* a ústředním eventem se stane `SubscriptionRenewed`. Když tým neudělá nový workshop, kód a doména se rozejdou – a nikdo si toho hned nevšimne, protože jednotlivé PR vypadají rozumně.

### 04.11.1 Doporučená frekvence {#re-cadence}

- **Pravidelně**: 1× za 6 měsíců nebo 1× za rok velký Big Picture re-storming pro celý systém. Rozhoduje stáří produktu: startup může re-stormovat čtvrtletně, zralý produkt jednou ročně.
- **Po velkém produktovém rozhodnutí**: nový tržní segment, nový obchodní model, akvizice. Re-storming proběhne *před* implementací, ne po ní.
- **Při akutních problémech**: tým má pocit, že kód „nedává smysl“ nebo že feature requesty se opakovaně modelují špatně. Pak je čas znovu vytáhnout stickies.

### 04.11.2 Diff jako priorita refaktoringu {#re-diff}

Po re-stormingu porovnejte novou mapu se starou, uloženou v `docs/discovery/<starý-datum>/`. Místa, kde se mapa změnila **nejvíc**, jsou **kandidáti na refaktoring**. Tam doména kódu reálně „utekla“ dopředu. Naopak místa, kde se mapa změnila málo, jsou stabilní a kód v nich je pravděpodobně v pořádku.

Re-storming typicky dělá menší skupina (3–5 lidí z původního workshopu) a trvá kratší dobu, protože hodně mapy se zachová.

## 04.12 Most z workshopu do testů {#workshop-to-tdd}

Software Design EventStorming přirozeně ústí v test-driven development. Každý invariant napsaný na sticky agregátu je **jeden test case**. Totéž platí pro hot spot, který se během workshopu vyřešil. Tým, který z workshopu odejde a nezačne psát testy podle invariantů, ztrácí polovinu jeho hodnoty.

### 04.12.1 Mapping invariantů na PHPUnit testy {#tdd-mapping}

Sticky agregátu z workshopu:

:::code{language="plaintext" filename="Sticky agregátu Order"}
Order Aggregate
- Inv-1: nemůže být confirmed bez aspoň jedné položky
- Inv-2: po cancelled už nelze confirm
- Inv-3: součet item.quantity * item.price = total
- Inv-4: confirm vyžaduje, aby payment byl Settled (hot spot Order-9)
:::

Přímý překlad do testů:

:::code{language="php" filename="tests/Ordering/OrderTest.php"}
final class OrderTest extends TestCase
{
    // Inv-1 (workshop 2026-04-29)
    #[Test]
    public function confirm_throws_when_order_has_no_items(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());

        $this->expectException(EmptyOrderException::class);
        $order->confirm();
    }

    // Inv-2 (workshop 2026-04-29)
    #[Test]
    public function cannot_confirm_after_cancellation(): void
    {
        $order = $this->orderWithOneItem();
        $order->cancel('customer request');

        $this->expectException(InvalidOrderStateTransitionException::class);
        $order->confirm();
    }

    // Inv-3 (workshop 2026-04-29)
    #[Test]
    public function total_equals_sum_of_line_subtotals(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->addItem(ProductId::generate(), 2, new Money(100, Currency::CZK));
        $order->addItem(ProductId::generate(), 1, new Money(50, Currency::CZK));

        self::assertSame(250, $order->totalAmount()->amountInCents);
    }
}
:::

Komentáře `Inv-1 (workshop 2026-04-29)` nejsou kosmetika – ukazují na původ pravidla. Když test selže za půl roku a nový vývojář chce zjistit, proč pravidlo existuje, doloví ho přes git blame nebo podle data workshopu.

### 04.12.2 Doménové eventy jako testy {#tdd-events}

Z Process Modellingu máte sekvenci `Command → Event → Policy → Command`. Tato sekvence je acceptance test:

:::code{language="php" filename="tests/Ordering/PlaceOrderHandlerTest.php"}
final class PlaceOrderHandlerTest extends KernelTestCase
{
    // Workshop scenario "Customer places an order" (2026-04-29)
    #[Test]
    public function place_order_emits_OrderPlaced_and_triggers_payment(): void
    {
        $bus = self::getContainer()->get(MessageBusInterface::class);
        $events = $this->collectEvents();

        $bus->dispatch(new PlaceOrderCommand(
            CustomerId::generate(),
            [new OrderItemDto(ProductId::generate(), 1, new Money(100, Currency::CZK))],
        ));

        self::assertCount(1, $events->ofType(OrderPlaced::class));
        // Policy z workshopu: OrderPlaced ⇒ ChargeCard
        self::assertCount(1, $events->commands(ChargeCardCommand::class));
    }
}
:::

Toto má dva přínosy. První: testy jsou *čitelné pro doménové experty*. Pojmenování přesně odpovídá workshopu, takže nevývojář si test může přečíst a potvrdit, že vyjadřuje to, co měl na mysli. Druhý: testy jsou **ochrana před regresí**. Když někdo za rok refaktoruje a omylem porušuje invariant z workshopu, test ho chytí.

Podrobně viz kapitolu [Testování v DDD](/testovani-ddd): testovací strategie, doménové testy, integrační testy se Symfony Messenger.

## 04.13 Shrnutí {#summary}

Event Storming a Domain Storytelling jsou dvě konkrétní, prověřené techniky, jak před první řádkou kódu dostat doménu na společný papír. Obě stojí na stejném předpokladu: doménové znalosti nelze přečíst – musí se v dialogu objevit.

- **Event Storming** ve třech formátech (Big Picture / Process Modelling / Software Design) je nástroj pro *širokoúhlé* mapování domény. Big Picture objevuje Bounded Contexty a pivotní události. Process Modelling zhušťuje jeden BC do sekvencí Command-Event-Policy. Software Design z nich dodá agregáty s invarianty.
- **Domain Storytelling** je *úzkoúhlý teleobjektiv* pro hloubkovou diskusi nad jedním procesem v malé skupině. Notace actor-work object-activity se čte bez zaškolení a hodí se pro kontexty, kde Event Storming je „příliš velký“.
- **Workshop začíná u doménového experta, ne u datového modelu.** Eventy se píšou v minulém čase, agregáty se objevují až nakonec.
- **Workshop bez follow-upu je promarněný.** Foto, eventy, hot spoty a Context Map musí jít do repa do 24 hodin a do kódu do 1–2 sprintů.
- **Re-storming je pravidelná činnost.** Doména se vyvíjí; mapa zastará. 1× za 6–12 měsíců nebo po každém velkém produktovém rozhodnutí.

Po prvním Event Stormingu typicky následuje implementace prvního Bounded Contextu; viz kapitoly o [základních konceptech DDD](/zakladni-koncepty), [CQRS](/cqrs), [Event Sourcingu](/event-sourcing) a [ságách](/sagy-a-process-managery). Pokud migrujete z legacy CRUD systému, pokračujte kapitolou [Migrace z CRUD na DDD](/migrace-z-crud).

:::faq{}
- question: Kolik lidí by mělo být na Event Storming workshopu?
  answer: 'Primární zdroje uvádějí pro Big Picture 15–30 lidí, typicky 25–30. Velká skupina není chyba: u dostatečně dlouhé stěny se sama rozpadne na hloučky, které pracují paralelně, a facilitátor je průběžně stahuje k celku. Menší sestava kolem deseti lidí (2–4 doménoví experti, 3–5 vývojářů, PM, facilitátor) se uřídí snadněji, je to ale kompromis: část pohledů na doménu v místnosti chybí. Pro Process Modelling a Software Design stačí 4–8 lidí; tam jde o detail jednoho BC. Detailní rozpis v <a href="#big-picture">sekci 04.04</a>.'
- question: Dá se Event Storming dělat online?
  answer: 'Záleží na formátu a autor techniky je v tom vyhraněný. Brandolini v textu <em>Remote EventStorming</em> (2020) považuje Software Design online za dobře proveditelný, Process Modelling za podmínečně proveditelný (půlden, 5–15 lidí, každá třetí session naživo) a k Big Picture píše doslova „Don&#39;t even try“. Online mizí paralelní konverzace u části stěny, řeč těla i celodenní ponoření. Když jinou možnost nemáte, dělejte online Big Picture jako vědomý kompromis: breakout místnosti pro paralelní diskuse, kratší bloky, přestávky každou hodinu. Postup je v <a href="#bp-online">sekci 04.04.5</a>.'
- question: Jak vést hot spoty během workshopu?
  answer: 'Pravidlo zní: <strong>nediskutuje se, jen se zaznamenává</strong>. Když během workshopu zazní otázka, kterou nikdo neumí hned zodpovědět, facilitátor ji okamžitě napíše na růžovou sticky a nalepí přesně tam, kde otázka vznikla, a workshop pokračuje dál. Pokus o vyřešení hot spotu hned vždy konzumuje 15–30 minut a typicky se nedořeší, protože odpověď leží mimo místnost. Po workshopu se každý hot spot stane ticketem přiřazeným doménovému expertovi, ne vývojáři.'
- question: 'Kdo platí workshop: produkt, nebo vývoj?'
  answer: 'Nejlépe oba společně. Workshop je investice do společné Ubiquitous Language a slovníku, který používají obě strany. Pokud ho zaplatí jen jedna, druhá strana ho nevezme vážně. Pokud přesto platí jen jeden, pak vývoj: bez workshopu vyrobí špatný model a bude ho refaktorovat tři sprinty. To stojí mnohonásobně víc než 4 hodiny doménových expertů.'
- question: Co když doménoví experti používají hovorovou češtinu a slang („chronický neplatič nás zase odbil“)?
  answer: 'Workshop dělejte v jazyce, který experti používají v reálné práci – typicky v češtině s vlastním slangem. Slang se neopravuje; <em>je</em> Ubiquitous Language. Když expert říká „chronický neplatič“, napište to na sticky tak, jak to řekl. V kódu pak modelujte koncept s tímto jménem (např. <code>ChronicLatePayer</code>); synonymum používané v týmu doplňte jako PHPDoc komentář. Ztratit jazyk = ztratit slovník = za rok zase nikdo neví, o čem mluvíme.'
- question: Když máme jen sólo vývojáře a PM, dá se Event Storming dělat ve dvou?
  answer: 'Ne, Event Storming ve dvou ztrácí smysl; stojí na konfrontaci více pohledů. Místo toho použijte <a href="#domain-storytelling">Domain Storytelling</a>, který malou skupinu snese. PM hraje doménového experta, vývojář kreslí story, debatujete krok za krokem. Za jedno sezení dostanete použitelný výstup pro jeden konkrétní proces. Jen si předem ujasněte scope příběhu podle <a href="#ds-scope">sekce 04.07.2</a>. Až přibude třetí člen týmu nebo se uvolní více doménových expertů, přejděte k Big Picture Event Stormingu.'
:::

## 04.14 Další četba {#further-reading}

- [Alberto Brandolini – *Introducing EventStorming* (Leanpub)](https://leanpub.com/introducing_eventstorming). Kniha přímo od autora techniky s popisem všech tří formátů, příklady i anti-patterny. Vychází průběžně od roku 2013 a dokončená není: k datu psaní uvádí Leanpub 70 % obsahu, poslední aktualizaci ze srpna 2021 a glosář zhruba ze dvou pětin.
- [eventstorming.com](https://www.eventstorming.com/) – oficiální web techniky, kde Brandolini publikuje šablony, fotografie z workshopů a aktuální postupy.
- [Stefan Hofer & Henning Schwentner – *Domain Storytelling: A Collaborative, Visual, and Agile Way to Build Domain-Driven Software* (Addison-Wesley, 2021)](https://domainstorytelling.org/). Komplexní kniha o Domain Storytellingu s notací, příklady a integrací s DDD.
- [egon.io](https://egon.io/) – open-source webový nástroj pro Domain Storytelling. Drag-and-drop editor, export do SVG.
- [Vaughn Vernon – *Domain-Driven Design Distilled* (Addison-Wesley, 2016)](https://www.amazon.com/Domain-Driven-Design-Distilled-Vaughn-Vernon/dp/0134434420), kapitola 7 obsahuje stručný úvod do Event Stormingu jako součásti DDD strategie.
- [Eric Evans – *Domain-Driven Design: Tackling Complexity in the Heart of Software* (Addison-Wesley, 2003)](https://www.domainlanguage.com/ddd/). Kniha, ze které DDD vychází; Ubiquitous Language a Bounded Context jsou základem všech workshopových technik.
- [ddd-crew – *EventStorming Glossary & Cheat Sheet*](https://github.com/ddd-crew/eventstorming-glossary-cheat-sheet) – nejúplnější veřejná legenda notace včetně formátů, autor Kenny Baas-Schwegler, licence CC BY 4.0.
- [Alberto Brandolini – *Remote EventStorming*](https://blog.avanscoperta.it/2020/03/26/remote-eventstorming/) – stanovisko autora k online workshopům, odstupňované podle formátu.
- [Vlad Khononov – *Learning Domain-Driven Design* (O'Reilly, 2021)](https://www.oreilly.com/library/view/learning-domain-driven-design/9781098100124/), kapitola 12 shrnuje Event Storming v deseti krocích z pohledu praktika, který techniku nasazuje u zákazníků.
- [Evelyn van Kelle, Gien Verschatse, Kenny Baas-Schwegler – *Collaborative Software Design* (Manning, 2024)](https://www.manning.com/books/collaborative-software-design). O facilitační vrstvě, kterou Event Storming předpokládá, ale neučí: ranking v místnosti, kognitivní zkreslení, práce s odporem.
- [Nick Tune, Jean-Georges Perrin – *Architecture Modernization* (Manning, 2024)](https://www.manning.com/books/architecture-modernization) – Big Picture EventStorming jako jeden ze čtyř nástrojů modernizace, vedle Wardley Mappingu a Team Topologies.
- [Event Modeling](https://eventmodeling.org/) – sesterská technika Adama Dymitruka (2018) s pouze dopřednou časovou osou a UI vrstvou. Pro návrh event-sourced systému bližší nástroj než Software Design EventStorming, viz kapitola [Event Sourcing](/event-sourcing).
- Oficiální Miro šablony Brandoliniho na Miroverse: [Process Modelling](https://miro.com/templates/eventstorming-process-modelling/) a [Software Design](https://miro.com/miroverse/eventstorming-software-design-template/).
