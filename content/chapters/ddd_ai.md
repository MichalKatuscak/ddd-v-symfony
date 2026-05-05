---
route: ddd_ai
path: /ddd-a-umela-inteligence
title: DDD a umělá inteligence – co říkají autority
page_title: "DDD a umělá inteligence – co říkají autority | DDD Symfony"
meta_description: "Vztah DDD a AI nástrojů očima Erica Evanse, Martina Fowlera, Kenta Becka a DHH. Ubiquitous Language jako rozhraní pro LLM, Bounded Contexts a kvalita kódu."
meta_keywords: "DDD AI, domain-driven design umělá inteligence, DDD LLM, Eric Evans AI, Martin Fowler AI, Kent Beck AI, DDD bounded context AI, ubiquitous language LLM"
og_type: article
published: "2026-03-27"
modified: "2026-05-03"
breadcrumb_name: DDD a AI
schema_type: TechArticle
schema_headline: "DDD a umělá inteligence – co říkají autority"
chapter_number: "ai"
category: Reference
ebook: false
deck: "Přehled názorů předních autorit softwarového inženýrství na vztah Domain-Driven Designu a umělé inteligence – Eric Evans, Martin Fowler, Kent Beck, DHH a další. Jejich pozice, argumenty a data."
reading_time: 20
difficulty: 1
---

Umělá inteligence mění způsob, jakým navrhujeme, píšeme a provozujeme software. To přirozeně
vyvolává otázku: jsou některé architektonické přístupy pro éru AI vhodnější než jiné? Nabízí
Domain-Driven Design výhody, které s příchodem LLM nabývají na váze – nebo naopak přidává
zbytečnou komplexitu v době, kdy AI dokáže generovat kód z jednoduchého popisu?

Tento článek mapuje, co o vztahu DDD a umělé inteligence říkají přední autority softwarového
inženýrství – Eric Evans, Martin Fowler, Kent Beck, Vaughn Vernon, Nick Tune, Alberto Brandolini
a DHH. Cílem je objektivní přehled jejich pozic, argumentů a dat, nikoli obhajoba ani kritika
konkrétního přístupu. Článek není srovnání ani návod krok za krokem – je to průřez tím, co víme a čeho
se ještě teprve učíme.

## ai.01 Ubiquitous language jako rozhraní pro LLM {#ubiquitous-language}

Jedním z nejkonkrétnějších výroků o vztahu DDD a AI pochází přímo od Erica Evanse. Na konferenci
Explore DDD 2024 Evans popisoval experiment, ve kterém tým doladil (fine-tuning) LLM na ubiquitous language
jednoho bounded contextu. Šlo o terminologii, pravidla a výrazy, které tým denně používal
v diskusích s doménovými experty. Výsledek byl podle Evanse překvapivě přesvědčivý: specializovaný model
byl levnější v provozu i přesnější než univerzální model, který musel doménu vyvozovat z kontextu
v promptu.

> „Because some parts of a complex system never fit into structured parts
> of domain models, we throw those over to humans to handle. Maybe we'll have
> some hard-coded, some human-handled, and a third, LLM-supported category.“
>
> – Eric Evans, Explore DDD 2024 (via InfoQ)

Evans dále navrhl, že několik fine-tuned modelů, každý určený pro jiný účel,
představuje silné oddělení zodpovědností – a že vytrénovaný jazykový model
lze chápat jako bounded context. V téže přednášce předpověděl, že NLP úlohy –
klasifikace záměrů, extrakce entit, shrnutí dokumentů – se stanou
plnohodnotnými subdoménami v DDD modelu. Stejně jako dnes
máme samostatné bounded contexty pro platby, notifikace nebo inventory, budeme mít bounded
context pro „rozumění textu“ nebo „extrakci strukturovaných dat“. Tato předpověď rezonuje
s tím, jak velké firmy dnes budují AI platformy – jako interní služby se svými API hranicemi,
nikoli jako průřezovou vrstvou přes celý systém.

Martin Fowler na toto téma navazuje z jiného úhlu. Ve svých poznámkách o přípravě na
nedeterministické výpočty zmiňuje DSL a doménově specifický jazyk jako nástroj pro rigorózní
promptování LLM. Argument je jednoduchý: čím precizněji definujeme pojmy a vztahy v ubiquitous
language, tím menší je prostor pro ambiguitu v promptu, tím předvídatelnější jsou výstupy
modelu. Precizní jazyk redukuje entropii na vstupu a tím i rozptyl na výstupu.

Protiváhu k tomuto nadšení tvoří David Heinemeier Hansson (DHH). Na konferenci Rails World 2025
a v rozhovorech pro The New Stack DHH argumentoval, že Ruby je dostatečně čitelné na to,
aby LLM chápal kód bez speciální terminologie. Preferovaným formátem pro AI je podle něj Markdown,
nikoli doménový jazyk definovaný formálními pravidly. DHH poukazuje na to, že Rails 8.1 přidal
nativní Markdown rendering právě proto, že to je formát, ve kterém AI přirozeně komunikuje.
Z jeho pohledu je ubiquitous language užitečná myšlenka pro komplexní enterprise systémy.
Pro většinu webových aplikací je ale konvence nad konfigurací – prostá angličtina nebo čeština
v komentářích a názvech – dostatečně výmluvná.

Pro úplnost: velké jazykové modely pracují s přirozeným jazykem jako svým primárním médiem.
Ubiquitous language v DDD je precizní podmnožina přirozeného jazyka – terminologie domény
zbavená nejednoznačností a obohacená o doménová pravidla. To z ní dělá přirozený most mezi
doménovými experty a LLM: pojmy, které jsou jasné lidem, jsou jasné i modelu. Otázka zní,
zda náklady na vybudování a udržení ubiquitous language jsou proporcionální k výhodám –
a odpověď se liší projekt od projektu. Definici a roli ubiquitous language v DDD popisuje
kapitola [Základní koncepty DDD](/zakladni-koncepty#ubiquitous-language).

## ai.02 Bounded contexts a kvalita generovaného kódu {#bounded-contexts}

Existují data. Ne rozsáhlé akademické studie s tisíci vzorků, ale praktické měření z reálných
projektů, která začínají ukazovat konzistentní obraz. Přehled dostupných zdrojů – od příspěvků
praktiků přes konferenční záznamy po preprint na arXiv – naznačuje, že hranice bounded contextu
mají měřitelný dopad na kvalitu kódu generovaného LLM.

:::callout{type="pattern"}
### Přehled dostupných dat: bounded contexts a AI generovaný kód {#data-table-heading}

<div class="table-responsive">
<table class="table table-bordered">
    <thead>
        <tr>
            <th scope="col">Metrika</th>
            <th scope="col">Bez DDD hranic</th>
            <th scope="col">S bounded contexts</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Produkčně použitelný kód (bez úprav)</td>
            <td>~55 %</td>
            <td>~88 %</td>
        </tr>
        <tr>
            <td>Porušení architektonických hranic</td>
            <td>35 %</td>
            <td>&lt; 5 %</td>
        </tr>
        <tr>
            <td>Kontext v promptu zahrnující celou codebase</td>
            <td>100 %</td>
            <td>15–25 %</td>
        </tr>
    </tbody>
</table>
</div>
<p class="table-note">
    <em>Zdroj: UnderstandingData.com (James Phoenix, 2026). Data jsou orientační odhady
    autora blogpostu – nepocházejí z kontrolované studie, nemají definovanou metodologii
    ani vzorek. Uvádíme je jako ilustraci pozorovaného trendu, nikoliv jako vědecký důkaz.</em>
</p>
:::

Nick Tune je jedním z nejvíce aktivních praktiků na průsečíku DDD a AI. V článku pro O'Reilly
Radar (2026) popisuje, jak použil Claude Code k reverznímu inženýrství softwarové architektury –
automatickému mapování end-to-end toků, závislostí a hranic v existující kódové bázi.
V návazném článku pak ukazuje, jak lze pomocí knihovny ts-morph deterministicky extrahovat
architektonické vzory, které slouží jako vstup pro AI agenty. Tune vidí DDD
bounded contexts jako přirozený rámec pro tento přístup – každý kontext má svůj rámec
(ve smyslu CLAUDE.md nebo Cursor rules), svou terminologii a své invarianty. AI agent
pracující uvnitř jednoho bounded contextu potřebuje znát méně – a tím dělá méně chyb.

Tune také poukazuje na zajímavý fenomén: dnešní AI nástroje si de facto vybudovaly
vlastní verzi bounded contextu na úrovni konfigurace. Cursor používá soubory
`.cursor/rules/*.mdc`, GitHub Copilot má `.github/copilot-instructions.md`,
Claude Code používá `CLAUDE.md`. Každý z těchto souborů definuje pravidla,
terminologii a omezení pro konkrétní kontext – přesně to, co DDD nazývá bounded context
s ubiquitous language. Tím, že vývojáři tyto soubory píší, provádějí implicitně DDD
modelování, aniž by to tak nutně nazývali.

Protiváhu tvoří data z GitClear z roku 2024, analyzovaná Visual Studio Magazine. Code churn
je podíl řádků přepsaných nebo smazaných do dvou týdnů od vytvoření. Podle tohoto výzkumu
se u AI generovaného kódu v roce 2024 přibližně zdvojnásobil oproti stavu
z roku 2021 před nástupem AI. GitClear hovoří o kódu, který je „lokálně koherentní,
ale architektonicky nekonzistentní“. Každý soubor nebo funkce může být syntakticky správná
a pro svůj bezprostřední účel funkční. Větší architektonické vzory – hranice
mezi moduly, zachování invariantů nebo konzistentní pojmenování napříč kódovou bází – jsou
ale porušeny. Bounded contexts jsou právě odpovědí na tento problém, ale je otázka, zda samotná
existence bounded contextu stačí, nebo zda AI agent potřebuje explicitní instruktáž o každém
pravidle uvnitř kontextu.

## ai.03 Testování jako kontrolní mechanismus pro AI {#testovani}

Kent Beck – autor TDD, autor Extreme Programming – se od začátku roku 2024 intenzivně
věnuje otázce, jak AI mění způsob programování. Podle shrnutí v The Pragmatic Engineer
je TDD při práci s AI agenty obzvlášť cenné. Beck rozlišuje mezi dvěma módy.
*Augmented coding* znamená, že vývojář používá AI jako asistenta a zachovává zodpovědnost
za rozhodnutí. *Vibe coding* znamená, že vývojář přijímá vše, co AI vygeneruje,
bez porozumění a bez verifikace.

> „In vibe coding you don't care about the code, just the behavior of the system.
> In augmented coding you care about the code, its complexity, the tests,
> & their coverage.“
>
> – Kent Beck, Augmented Coding: Beyond the Vibes (Substack, 2024)

Beckův argument je, že testy jsou jediným mechanismem, který AI nemůže zfalšovat. Předpokládejme,
že AI generuje kód a existuje sada testů specifikující chování z pohledu domény –
nikoli implementační detaily, ale doménová pravidla. Selhání testu je pak objektivním
signálem, že AI se odchýlila od záměru. TDD tak ve spolupráci s AI plní roli, která
v tradičním vývoji náleží code review: průběžná verifikace toho, zda kód dělá to,
co má. Beck přiznává, že sám testuje méně věcí než dříve. Testy, které píše, jsou
ale úmyslnější – zaměřené na doménová pravidla a hraniční případy, nikoli na hlavní scénář.

Martin Fowler přichází s podobným, ale méně optimistickým rámcem. V rozhovoru pro
The New Stack Fowler přirovnává AI k „pochybnému kolegovi“ – kolaborátorovi, jehož
výstup je třeba pečlivě revidovat, nikoli slepě přijímat.

> „You've got to treat every slice as a PR from a rather dodgy collaborator
> who's very productive in the lines-of-code sense of productivity,
> but you know you can't trust a thing that they're doing.“
>
> – Martin Fowler, The New Stack, 2024

Fowler zdůrazňuje, že nedeterminismus LLM – stejná otázka, jiný výsledek – od základu
mění způsob, jakým přemýšlíme o testování. Tradiční testování předpokládá deterministický
systém: stejný vstup, stejný výstup, vždy. Pro AI komponenty to neplatí. Fowler volá
po nových metrikách a nových přístupech, ale přiznává, že komunita je teprve na začátku
tohoto hledání.

Protiváhou k tomuto techno-optimismu je DHH, jehož vyjádření jsou záměrně provokativní.
V sérii článků a přednášek DHH popisuje vlastní zkušenost s intenzivním používáním
AI asistentů a vyvozuje z ní znepokojivý závěr:

> „I can literally feel competence draining out of my fingers!“
>
> – DHH, The New Stack, 2025

DHH varuje před nebezpečím, kdy vývojář přestane rozumět kódu, který provozuje – stane
se manažerem projektu AI místo inženýrem. Tento argument není anti-AI – DHH AI používá –
ale je to varování, že nekritické přijetí AI výstupu degraduje schopnost rozpoznat,
kdy AI dělá chybu. Bez doménového porozumění nejsou testy dostatečné: vývojář, který
nechápe doménu, nepíše správné testy, a AI pak plní testy generováním falešně pozitivního
kódu.

Kontext pro DDD komunitu: TDD a code review nejsou specifické pro DDD, ale DDD komunita
je s nimi historicky propojena. Taktické DDD vzory – agregáty s invarianty, doménové
události jako přirozené kontrakty – jsou přirozeně testovatelné na úrovni domény.
Agregát definuje pravidlo; test verifikuje pravidlo; AI generuje implementaci; test
signalizuje odchylku. Tento cyklus je odolnější než testování implementačních detailů.
Konkrétní strategie testování DDD modelů – unit testy agregátů, integrační testy
přes Messenger, contract testy mezi kontexty – popisuje kapitola
[Testování DDD](/testovani-ddd).

## ai.04 AI v doménové komplexitě vs. CRUD {#komplexita-vs-crud}

Evans ve své Explore DDD 2024 přednášce navrhl novou taxonomii softwarových rozhodnutí –
tři kategorie, které rozšiřují tradiční DDD rozlišení o AI vrstvu. První kategorie jsou
**hard-coded decisions**: pravidla absolutní, neměnná a se závažnými důsledky při porušení.
Příkladem je požadavek, že záporný stav účtu musí projít explicitním
schválením. Druhá kategorie jsou **human-handled decisions**: situace tak
komplexní nebo citlivé, že musí rozhodovat člověk. Třetí, nová kategorie jsou
**LLM-supported decisions**: situace, kde přesnost 80–90 % je přijatelná,
kde rozhodnutí lze revidovat a kde náklady na chybu jsou nízké.

Tato taxonomie má přímý dopad na to, kde AI dává smysl a kde ne. Ve vysoce komplexní
doméně – pojišťovnictví, bankovnictví, zdravotnictví – je podíl hard-coded decisions
vysoký a náklady na chybu vysoké. Paradoxně to jsou domény, kde DDD přináší největší
hodnotu, ale kde je AI nejnebezpečnější, pokud není správně ohraničena. LLM-supported
decisions existují i zde – například kategorizace dokumentů nebo návrh odpovědi zákaznickému
servisu – ale musí být jasně odděleny od hard-coded logiky.

Vaughn Vernon přidává konkrétní technický vzor: LLM jako „fix suggester“. Vernon
navrhuje, aby LLM v produkčním systému navrhoval opravy pro selhání. Tyto opravy
musí projít verifikací – ať už automatizovanou nebo lidskou – před aplikací. Hovoří
o konceptu *self-healing software*: systém, který detekuje anomálie, požádá LLM
o návrh opravy, verifikuje ji testy a teprve pak ji aplikuje. DDD bounded context
v tomto scénáři definuje pravidla verifikace: co smí LLM změnit a co musí zůstat
neměnné.

Referenční implementace Microsoftu – eShopOnContainers – ilustruje toto rozlišení
na praktickém příkladu. Modul `Ordering` používá plné taktické DDD:
agregáty, doménové události, CQRS. Modul `Catalog` je prostý CRUD
s Entity Framework. Toto rozlišení není historická nehoda – je to záměrné rozhodnutí
přiřadit komplexitu tam, kde leží doménová komplexita. S příchodem AI toto rozhodnutí
nabývá nové dimenze: kde leží hranice mezi tím, co AI může autonomně rozhodovat,
a kde musí platit explicitní doménová pravidla?

DHH nabízí radikální protiváhu:

> „A lot of people, I think, are very uncomfortable with the fact that they are
> essentially crud monkeys. They just make systems that create, read, update,
> or delete rows in a database and they have to compensate for that existential
> dread by over-complicating things.“
>
> – DHH, Lex Fridman Podcast

DHH otevřeně říká, že většina vývojářské práce je „CRUD monkeying“ – psaní
aplikací, které přijímají data, ukládají je a zobrazují. Pro tuto kategorii
aplikací je DDD přeceňované – a AI, která generuje CRUD kód z jednoduchého popisu,
je přirozeným řešením bez potřeby doménového modelu. Hlavní otázka, na kterou
DHH odpovídá jinak než Evans, je: jak velký podíl softwarového průmyslu tvoří
skutečně komplexní domény versus CRUD monkeying? A mění AI tuto hranici? Buď tím,
že CRUD kód zlevní natolik, že zbyde čas na komplexní doménu, nebo tím,
že komplexní doménové problémy de facto „zjednoduší“ na LLM-supported decisions.
Pro praktické rozhraničení toho, kdy DDD nasazovat a kdy ne, viz kapitolu
[Kdy DDD nepoužívat](/kdy-nepouzivat-ddd).

## ai.05 Architektonické nástroje a kontext pro AI {#nastroje}

Jedním z nejzajímavějších trendů posledních dvou let je konvergence AI nástrojů
k de facto implementaci DDD konceptů na úrovni konfigurace. Cursor IDE používá
soubory v adresáři `.cursor/rules/` s příponou `.mdc`:
každý soubor definuje pravidla pro konkrétní část projektu, terminologii a omezení.
GitHub Copilot přidal podporu pro `.github/copilot-instructions.md`:
globální instrukce pro všechny konverzace v daném repozitáři. Claude Code používá
`CLAUDE.md` na úrovni projektu i adresáře – přesně tato stránka,
na které čtete tento článek, se řídí `CLAUDE.md` v kořenovém adresáři
repozitáře.

Všechny tyto soubory sdílejí strukturu nápadně podobnou tomu, co DDD
nazývá bounded context s ubiquitous language. Definují terminologii (jak se jmenují
věci v projektu), pravidla (co smí a nesmí), kontext (co AI ví o projektu)
a omezení (co AI dělat nebude). Nick Tune a další DDD praktici tuto paralelu
aktivně využívají. Cursor rules a CLAUDE.md píší jako explicitní bounded context
dokumenty, čímž propojují formální DDD terminologii s praktickými AI nástroji.

Akademický výzkum tuto praxi začíná zkoumat systematicky. Preprint na arXiv
z roku 2026 (Wiegand et al.) zkoumá automatizaci tvorby doménových metamodelů v DDD
pomocí generativní AI – konkrétně generování doménově specifických
JSON objektů. Výsledky jsou předběžné, ale naznačují, že strukturovaný, explicitní
kontext vede k lepším výsledkům než nestrukturovaný nebo implicitní.

ThoughtWorks Technology Radar vol. 33 (duben 2025) sice přímo nezmiňuje DDD
v kontextu AI, ale obsahuje několik relevantních blipů. V kategorii Adopt je „Using GenAI
to understand legacy codebases“. V kategorii Assess je „Context engineering“
a „Anchoring coding agents to a reference application“. Tyto
techniky sdílejí společný princip: čím přesnější kontext AI dostane, tím lepší
jsou její výstupy – princip, který je DDD bounded contextům vlastní.

Pro vyváženost dodejme: tyto nástroje fungují i bez DDD. Jednoduchý kód s jasnými
konvencemi – convention over configuration v Rails stylu – může být pro AI stejně
čitelný jako explicitně modelovaný bounded context. Pokud projekt dodržuje konzistentní
pojmenování, má dobré testy a je dobře rozčleněn do adresářů, AI agent se v něm orientuje
i bez formálního DDD modelu. Proslulý článek „DHH Is Wrong“ a série na toto téma
ilustrují, že konvence může být stejně účinná jako explicitní modelování. Otázkou
zůstává, co se stane, když projekt vyroste za hranice, kde konvence stačí.

## ai.06 Otevřené otázky a limity {#otevrene-otazky}

Martin Fowler opakovaně zdůrazňuje, že oblast AI a softwarové architektury je v roce 2026
teprve na začátku. Nedeterminismus LLM – stejný prompt, jiný výstup – zatím nemá
uspokojivou metriku. Jak měříme architektonickou konzistenci generovaného kódu?
Jak verifikujeme, že AI respektuje hranice bounded contextu, když každé volání
API může vrátit jiný výsledek? Fowler hovoří o tom, že „stále se učíme“ –
a to je poctivý popis stavu oboru.

V datech zmíněných výše: 88 % produkčně použitelného kódu s bounded contexts zní dobře,
ale co těch 12 %? A kde selhávají – v okrajových případech, v porušení invariantů, v chybném
pojmenování? Odpověď rozhoduje, zda bounded contexts jsou dostatečnou zárukou. Případnou
dodatečnou vrstvu verifikace mohou tvořit architektonické testy (ArchUnit, deptrac)
nebo explicitní bounded context registry.

Alberto Brandolini – autor EventStorming – se k propojení AI a doménového modelování
veřejně vyjadřuje zdrženlivě. Vzdělávací firma Avanscoperta, kterou spoluzaložil,
nabízí workshopy zaměřené na AI-augmentované vývojové postupy (např. „The Agentic
Developer Workshop“), ale ty nejsou přímo zaměřené na kombinaci DDD a AI.
EventStorming zůstává v Brandoliniho pojetí fundamentálně lidskou aktivitou –
sdílené pochopení domény se buduje v konverzaci, nikoliv v promptu.

Sam Newman – autor Building Microservices – se k AI zatím jasně nevyjádřil
v kontextu DDD. Jeho pozice k distribuovaným systémům je dlouhodobě konzervativní:
mikroservisy jako poslední možnost, nikoli jako výchozí architektura. Tato konzervativní
pozice pravděpodobně platí i pro AI: nasadit LLM do produkčního systému je distribuovaná
závislost se všemi problémy distribuovaných systémů – latencí, spolehlivostí, verzováním,
monitoringem.

Otevřené otázky, na které obor zatím nemá odpověď:

- **Mění AI hranici, kde DDD dává smysl?** Pokud AI zlevní generování
  CRUD kódu natolik, že vývojáři mají více kapacity na komplexní logiku, může
  rozšířit množinu projektů, kde se DDD investice vyplatí.
- **Stane se ubiquitous language standardem pro AI kontexty?**
  Cursor rules a CLAUDE.md jsou ad hoc řešení. Mohla by DDD komunita přispět
  formálnější strukturou pro definici AI kontextů?
- **Jaká bude role architekta v AI-augmentovaném týmu?** Pokud AI
  generuje implementaci, architekt se stává primárně autorem kontextů, pravidel
  a verifikačních mechanismů – což je blíže k DDD modelování než k psaní kódu.
- **Co se stane s juniorními vývojáři?** DDD předpokládá, že tým
  rozumí doméně. Pokud AI generuje kód, kterému junioři nerozumí, jak se budují
  doménové znalosti pro příští generaci?

## ai.07 Závěr {#zaver}

:::callout{type="pattern"}
### Spektrum pozic: od synergie DDD a AI po důraz na jednoduchost {#spectrum-heading}

<div class="table-responsive">
<table class="table table-bordered">
    <thead>
        <tr>
            <th scope="col">Autor</th>
            <th scope="col">Pozice</th>
            <th scope="col">Hlavní argument</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Eric Evans</strong></td>
            <td>Silně pro DDD + AI</td>
            <td>Fine-tuned LLM na ubiquitous language; NLP úlohy jako subdomény</td>
        </tr>
        <tr>
            <td><strong>Nick Tune</strong></td>
            <td>Silně pro DDD + AI</td>
            <td>Bounded contexts jako přirozený rámec pro AI agenty</td>
        </tr>
        <tr>
            <td><strong>Vaughn Vernon</strong></td>
            <td>Pro DDD + AI</td>
            <td>LLM jako návrhovač oprav, verifikovaný doménovými pravidly</td>
        </tr>
        <tr>
            <td><strong>Kent Beck</strong></td>
            <td>Pro strukturovaný design</td>
            <td>TDD jako kontrolní mechanismus pro AI; augmented coding</td>
        </tr>
        <tr>
            <td><strong>Martin Fowler</strong></td>
            <td>Nuancovaně pro DDD</td>
            <td>AI jako „dodgy collaborator“; potřeba nových metrik</td>
        </tr>
        <tr>
            <td><strong>Alberto Brandolini</strong></td>
            <td>Opatrný</td>
            <td>EventStorming zůstává lidskou aktivitou</td>
        </tr>
        <tr>
            <td><strong>DHH</strong></td>
            <td>Protiváha</td>
            <td>Jednoduchost a konvence stačí pro většinu aplikací</td>
        </tr>
    </tbody>
</table>
</div>
:::

Syntéza pozic autorit vede k opatrnému, ale celkem konzistentnímu závěru: většina
předních myslitelů v oblasti softwarového inženýrství vidí potenciální synergii
mezi DDD principy a praktickým využitím AI. Evans a Tune jsou nejkonkrétnější –
sdílejí praktické vzory a data. Fowler a Beck jsou opatrně optimističtí a zdůrazňují
potřebu nových nástrojů a metrik. Brandolini zachovává lidský prvek v centru.

DHH tvoří důležitou protiváhu: připomíná, že velká část softwarového průmyslu
je stále CRUD, že jednoduchost má svou hodnotu a že AI může být účinná i bez
formálního doménového modelování. Tato pozice není špatná – je to připomínka,
že DDD není odpověď na každou otázku.

Konvergence, kterou vidíme v roce 2026, je tato: struktura pomáhá. Ať už jde o
DDD bounded contexts, Cursor rules nebo CLAUDE.md – explicitní, sdílený kontext
zlepšuje výsledky AI. DDD přináší bohatou tradici myšlení o tom, jak takový
kontext definovat. Není to jediná cesta – konvence, dobré testy a čistý kód
mohou dosáhnout podobného efektu – ale je to vyzkoušená a dobře zdokumentovaná
cesta.

Rozhodovat se má smysl podle domény, týmu a projektu. Kde leží doménová
komplexita? Kde jsou náklady na chybu vysoké? Kde bude systém žít pět let?
Tyto otázky – ne přítomnost nebo nepřítomnost AI v toolchainu – by měly řídit
architektonické rozhodnutí.

:::callout{type="note"}
**Hlavní závěry:**

- **Evans:** Fine-tuned LLM na ubiquitous language bounded contextu
  může být levnější a přesnější než univerzální model. NLP úlohy se stávají
  plnohodnotnými subdoménami.
- **Fowler:** AI jako „dodgy collaborator“ – výstup je třeba
  pečlivě verifikovat. Precizní jazyk redukuje entropii AI výstupu.
- **Beck:** Augmented coding udržuje kvalitu kódu i s AI – testy
  definují doménová pravidla, která AI nemůže obejít.
- **Tune:** Living docs exportované z bounded contexts slouží jako
  kontext pro AI agenty – praktický výsledek DDD modelování.
- **DHH:** Pro CRUD aplikace je DDD přeceňované. Konvence může být
  stejně účinná jako explicitní modelování – a AI generuje CRUD kód výborně.
- **Brandolini:** EventStorming zůstává fundamentálně lidskou
  aktivitou. AI může automatizovat rutinní části, ale sdílené pochopení
  domény se buduje v konverzaci.
:::

:::faq{}
- question: Proč AI nástroje generují lepší kód v projektech s Ubiquitous Language?
  answer: 'Ubiquitous Language poskytuje LLM jednoznačný slovník, který se objevuje napříč dokumentací, testy i kódem. Model při generování dostává konzistentní pojmy z kontextu a produkuje výstup, který zapadá do existujícího modelu bez překladu. Bez Ubiquitous Language AI často zavádí vlastní pojmenování, které se rozchází s doménou, a tým pak tráví čas jeho přepisováním. Evans popisuje tuto synergii jako možnost fine-tuningu LLM přímo na slovníku bounded contextu. Podrobný rozbor v <a href="#ubiquitous-language">sekci Ubiquitous language jako rozhraní pro LLM</a>.'
- question: Jak Bounded Contexts ovlivňují kvalitu kódu generovaného AI?
  answer: 'Bounded Context vymezuje srozumitelný rozsah, ve kterém se AI pohybuje – místo „celé aplikace“ pracuje s jedním modelem, jednou sadou pravidel a jedním slovníkem. Menší, dobře ohraničený kontext znamená méně protichůdných informací v promptu a menší prostor pro halucinace. Bounded Contexts také přirozeně navazují na struktury jako Cursor rules nebo CLAUDE.md, které AI nástrojům dávají konkrétní pracovní perimetr. Rozbor v <a href="#bounded-contexts">sekci Bounded contexts a kvalita generovaného kódu</a>.'
- question: Jakou roli hrají testy při práci s AI?
  answer: 'Testy fungují jako kontrolní mechanismus, který zachytává rozdíl mezi tím, co AI vygenerovala, a tím, co doména skutečně požaduje. Kent Beck hovoří o konceptu augmented coding: AI píše kód, testy potvrzují chování, a teprve když oba stojí spolu, jde změna do kódové báze. Bez testů se riziko nevyřešených chyb z AI výstupu kumuluje, protože LLM kód působí syntakticky správně, i když na úrovni chování selhává. Praktický rozbor v <a href="#testovani">sekci Testování jako kontrolní mechanismus pro AI</a>.'
- question: Kde jsou limity AI v doménově komplexním kódu?
  answer: 'AI zatím dobře zvládá rutinní úlohy (boilerplate, CRUD, jednoduché transformace), ale naráží u kódu, který odráží nekonzistentní doménovou realitu nebo vyžaduje modelování nových pravidel se stakeholdery. Martin Fowler popisuje AI jako „dodgy collaborator“, jejíž výstup je třeba pečlivě verifikovat – zejména u operací s vysokými náklady chyby. Otevřené otázky se týkají metrik kvality doménového modelu, role člověka v EventStormingu a dlouhodobého dopadu AI na kompetence vývojářů. Viz <a href="#otevrene-otazky">sekci Otevřené otázky a limity</a>.'
:::

## ai.08 Zdroje a další čtení {#zdroje}

:::callout{type="note"}
**Primární zdroje:**

- **Evans, E. – Explore DDD 2024 (InfoQ):**
  <a href="https://www.infoq.com/news/2024/03/Evans-ddd-experiment-llm/" target="_blank" rel="noopener noreferrer">DDD and Experiment With LLM – InfoQ, 2024</a>.
  Stěžejní přednáška, ve které Evans popisuje fine-tuning LLM na ubiquitous language
  a navrhuje taxonomii hard-coded / human-handled / LLM-supported decisions.
- **Fowler, M. – The New Stack:**
  <a href="https://thenewstack.io/martin-fowler-on-preparing-for-ais-nondeterministic-computing/" target="_blank" rel="noopener noreferrer">Martin Fowler on Preparing for AI's Nondeterministic Computing</a>.
  Fowlerovy úvahy o nedeterminismu AI a potřebě nových metrik a přístupů k testování.
- **Beck, K. – Substack (Tidy First):**
  <a href="https://tidyfirst.substack.com/p/augmented-coding-beyond-the-vibes" target="_blank" rel="noopener noreferrer">Augmented Coding: Beyond the Vibes</a>.
  Beckova definice augmented coding vs. vibe coding a argument pro TDD jako superschopnost
  v éře AI.
- **Beck, K. – The Pragmatic Engineer:**
  <a href="https://newsletter.pragmaticengineer.com/p/tdd-ai-agents-and-coding-with-kent" target="_blank" rel="noopener noreferrer">TDD, AI Agents, and Coding with Kent Beck</a>.
  Rozhovor s Beckem o TDD, AI agentech a budoucnosti programování.
- **DHH – The New Stack:**
  <a href="https://thenewstack.io/dhh-on-ai-vibe-coding-and-the-future-of-programming/" target="_blank" rel="noopener noreferrer">DHH on AI, Vibe Coding, and the Future of Programming</a>.
  DHH o nebezpečí ztráty kompetence, konvenci nad modelováním a roli AI v Rails ekosystému.
- **DHH – Lex Fridman Podcast:**
  <a href="https://lexfridman.com/dhh-david-heinemeier-hansson-transcript/" target="_blank" rel="noopener noreferrer">DHH: Programming, AI, Startups, and Open Source</a>.
  Rozhovor, ve kterém DHH popisuje vývojáře jako „CRUD monkeys“ a varuje před over-engineering.

**Praktické zdroje od DDD praktiků:**

- **Tune, N. – O'Reilly Radar:**
  <a href="https://www.oreilly.com/radar/reverse-engineering-your-software-architecture-with-claude-code-to-help-claude-code/" target="_blank" rel="noopener noreferrer">Reverse Engineering Your Software Architecture with Claude Code to Help Claude Code</a>.
  Praktický průvodce použitím Claude Code a ts-morph k extrakci architektonických vzorů
  a tvorbě living docs pro AI agenty.
- **Tune, N. – Medium:**
  <a href="https://medium.com/nick-tune-tech-strategy-blog/enterprise-wide-software-architecture-as-ddd-living-documentation-33f3d8b4ddfc" target="_blank" rel="noopener noreferrer">Enterprise-Wide Software Architecture as DDD Living Documentation</a>.
  Jak DDD bounded contexts slouží jako základ pro living dokumentaci přístupnou AI agentům.
- **ThoughtWorks – Technology Radar vol. 33:**
  <a href="https://www.thoughtworks.com/about-us/news/2025/thoughtworks-tech-radar-33-rapid-ai" target="_blank" rel="noopener noreferrer">ThoughtWorks Tech Radar 33 – Rapid AI</a>.
  Obsahuje blipy „Context engineering“ (Assess), „Using GenAI to understand legacy
  codebases“ (Adopt) a další AI-relevantní techniky.

**Výzkumné zdroje:**

- **UnderstandingData.com:**
  <a href="https://understandingdata.com/posts/ddd-bounded-contexts-for-llms/" target="_blank" rel="noopener noreferrer">DDD Bounded Contexts for LLMs</a>.
  Praktická analýza dopadu bounded contexts na kvalitu kódu generovaného LLM;
  zdroj dat v tabulce výše.
- **Wiegand et al. – arXiv 2026:**
  <a href="https://arxiv.org/html/2601.20909" target="_blank" rel="noopener noreferrer">Leveraging Generative AI for Enhancing Domain-Driven Software Design</a>.
  Preprint zkoumající automatizaci tvorby doménových metamodelů pomocí generativní AI.
- **GitClear / Visual Studio Magazine:**
  <a href="https://visualstudiomagazine.com/articles/2024/01/25/copilot-research.aspx" target="_blank" rel="noopener noreferrer">Coding on Copilot: 2023 Data Suggests Downward Pressure on Code Quality</a>.
  Výzkum GitClear o zdvojnásobení code churn u AI generovaného kódu; analýza
  architektonické nekonzistence lokálně koherentního kódu.
:::
