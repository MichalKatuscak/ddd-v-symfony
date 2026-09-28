---
route: ddd_ai
path: /ddd-a-umela-inteligence
title: DDD a umělá inteligence – co říkají autority
page_title: "DDD a umělá inteligence – co říkají autority | DDD Symfony"
meta_description: "Vztah DDD a AI nástrojů očima Erica Evanse, Martina Fowlera, Kenta Becka a DHH. Ubiquitous Language jako rozhraní pro LLM, Bounded Contexts a kvalita kódu."
meta_keywords: "DDD AI, domain-driven design umělá inteligence, DDD LLM, Eric Evans AI, Martin Fowler AI, Kent Beck AI, DDD bounded context AI, ubiquitous language LLM"
og_type: article
published: "2026-03-27"
modified: 2026-09-28
breadcrumb_name: DDD a AI
schema_type: TechArticle
schema_headline: "DDD a umělá inteligence – co říkají autority"
chapter_number: "ai"
category: Reference
ebook: false
deck: "Co o vztahu DDD a umělé inteligence říkají Eric Evans, Martin Fowler, Kent Beck, DHH a další: jejich postoje, argumenty a data."
reading_time: 24
difficulty: 1
github_examples: null
---

S nástupem LLM se otevřela otázka, jestli Domain-Driven Design získává na ceně, nebo jen
přidává komplexitu v době, kdy AI generuje kód z krátkého popisu.

Kapitola mapuje, co o vztahu DDD a umělé inteligence říkají přední autority softwarového
inženýrství: Eric Evans, Martin Fowler, Kent Beck, Vaughn Vernon, Nick Tune, Alberto Brandolini
a DHH. Jde o přehled jejich pozic, argumentů a dat, ne o obhajobu ani kritiku
konkrétního přístupu. Opačný směr téhož vztahu, DDD jako metoda pro stavbu systému
s jazykovým modelem uvnitř, má vlastní sekci.

U každého výroku je uveden rok, kdy zazněl, a stav odpovídá září 2026. Pozice se v tomto
tématu mění po měsících, takže část textu zestárne dřív než zbytek knihy.

## ai.01 Ubiquitous Language jako rozhraní pro LLM {#ubiquitous-language}

Jeden z nejkonkrétnějších návrhů pochází od Erica Evanse. Na konferenci
Explore DDD 2024 navrhl doladit (fine-tuning) model na Ubiquitous Language jednoho
Bounded Contextu, tedy na terminologii, pravidla a výrazy, které tým denně používá
v diskusích s doménovými experty. Doladěný model je podle něj sám o sobě Bounded Context a několik takových modelů
vedle sebe znamená silné oddělení zodpovědností. Fine-tuning navíc dělá levný model levnějším
a rychlejším.

Jde o návrh, ne o zprávu z provedeného experimentu. Evans k tomu sám přidal výhradu, že jeho
závěry platí ke dni, kdy je vyslovil – v březnu 2024.

> „Because some parts of a complex system never fit into structured parts
> of domain models, we throw those over to humans to handle. Maybe we'll have
> some hard-coded, some human-handled, and a third, LLM-supported category.“
>
> – Eric Evans, Explore DDD 2024 (via InfoQ)

V téže přednášce Evans předpověděl, že úlohy zpracování přirozeného jazyka se stanou
plnohodnotnými subdoménami DDD modelu. Klasifikace záměrů, extrakce entit nebo shrnutí
dokumentů jsou typické příklady. Stejně jako dnes máme samostatné Bounded Contexty pro platby,
notifikace nebo sklad, budeme mít kontext pro „rozumění textu“ nebo „extrakci strukturovaných
dat“. Předpověď odpovídá tomu, jak velké firmy AI platformy staví. Jsou to interní služby
s vlastními API hranicemi, ne průřezová vrstva přes celý systém.

Martin Fowler na to navazuje z jiného úhlu. V rozhovoru o přípravě na nedeterministické
výpočty (2025) jmenuje domain-driven design a doménově specifické jazyky jako cestu
k rigoróznějšímu promptování LLM. Rozpracovaný argument vyšel na jeho webu z pera Unmeshe
Joshiho. Obecný jazyk nabízí spoustu způsobů, jak vyjádřit tentýž záměr; DSL tu
variabilitu odřízne. Modelu pak stačí pár příkladů a syntaxi generuje spolehlivě. Pevný
jazyk na vstupu znamená méně entropie na výstupu.

Opačný pól drží David Heinemeier Hansson (DHH). V Lex Fridman Podcastu (2025)
argumentoval, že Ruby má vyšší přenosovou kapacitu než jiné jazyky, protože na jeden znak
unese víc významu. Při spolupráci s AI je to podle něj výhoda: člověk i model potřebují
kódu rozumět rychle. DHH tedy nesází na formální doménový jazyk, ale na hustotu
vyjádření a na konvence frameworku.

K tomu sedí i Rails 8.1 s nativním renderingem Markdownu; release notes ho
zdůvodňují tím, že se Markdown stal lingua franca AI nástrojů.

Velké jazykové modely pracují s přirozeným jazykem jako svým primárním médiem.
Ubiquitous Language v DDD je precizní podmnožina přirozeného jazyka, tedy terminologie
domény zbavená nejednoznačností a obohacená o doménová pravidla. Funguje proto jako most mezi
doménovými experty a LLM: pojmy srozumitelné lidem jsou srozumitelné i modelu. Otázka zní,
zda náklady na vybudování a udržení Ubiquitous Language odpovídají získaným výhodám.
Odpověď se liší projekt od projektu. Definici a roli Ubiquitous Language v DDD popisuje
kapitola [Základní koncepty DDD](/zakladni-koncepty#ubiquitous-language).

## ai.02 Bounded Contexts a kvalita generovaného kódu {#bounded-contexts}

Tvrdá data k tomu, jak hranice Bounded Contextu ovlivňují kód generovaný LLM, zatím nejsou.
Kontrolovaná studie s definovanou metodologií a vzorkem chybí. K dispozici jsou zkušenosti
praktiků, měření kvality kódu, která o DDD nemluví, a jeden preprint, který srovnání nedělá.
Tomu odpovídá i jistota závěrů této sekce.

Jediná konkrétní čísla, která se k tématu dají dohledat, pocházejí z blogpostu Jamese
Phoenixe. Přesnost kolem 55 % bez explicitních hranic proti 88 % s nimi. Porušení
architektonických hranic v 35 % případů proti méně než pěti procentům. Autor je uvádí jako vlastní odhad bez metodologie i vzorku,
takže kapitola na nich nic nestaví. Zůstávají jako ilustrace toho, co praktici pozorují.

Nick Tune je jedním z nejaktivnějších praktiků na průsečíku DDD a AI. V článku pro O'Reilly
Radar (únor 2026) popisuje, jak použil Claude Code k reverznímu inženýrství softwarové
architektury, tedy k automatickému mapování end-to-end toků, závislostí a hranic
v existující kódové bázi. V návazném článku ukazuje, jak lze pomocí knihovny ts-morph deterministicky
extrahovat architektonické vzory, které slouží jako vstup pro AI agenty. K výsledku sám
připojuje varování: v generovaném popisu architektury byly podstatné nepřesnosti, které
musel odhalit a opravit.

Kniha z toho vyvozuje vlastní úvahu: agent pracující uvnitř jednoho Bounded Contextu
potřebuje znát méně, a čím míň musí uhodnout, tím míň chyb udělá.

Nástroje se mezitím posunuly podobným směrem. Cursor, GitHub Copilot i Claude Code
čtou soubory s pravidly, terminologií a omezeními pro konkrétní část kódu, tedy něco,
co se Bounded Contextu s Ubiquitous Language podobá. Formáty rozebírá
[sekce ai.06](#nastroje).

Podobnost má ale mez a Tune na ni upozorňuje z vlastní zkušenosti: generovaný kód se
architektonickými pravidly zapsanými v markdown souborech spolehlivě neřídí. Jeho závěr je
proto opačný, než by analogie svedla čekat – architekturu je potřeba vynucovat
deterministicky, ne ji popsat a doufat.

Druhý pohled přinášejí data z GitClear. Code churn je podíl řádků přepsaných nebo smazaných
do dvou týdnů od vytvoření; jeho zdvojnásobení firma v lednu 2024 ohlásila jako projekci.
Pozdější reporty už stojí na naměřených hodnotách. Podíl řádků spojených s refaktoringem klesl
ze čtvrtiny v roce 2021 pod desetinu v roce 2024. Klonované řádky vzrostly z 8,3 %
na 12,3 % a kopírovaný kód poprvé překonal přesouvaný. Report za rok 2026 na vzorku 623 milionů změn
ukazuje duplicitu bloků o 81 % vyšší než v roce 2023. Žádné z těch měření o DDD nemluví
ani neprokazuje příčinu; ukazují jen, kterým směrem se kvalita kódu za éry asistentů posunula.

Každý soubor nebo funkce přitom může být syntakticky správná a pro svůj bezprostřední účel
funkční. Drhnou až větší celky: hranice mezi moduly, zachování invariantů, konzistentní
pojmenování v celé kódové bázi. Bounded Contexts na tenhle typ problému míří. Otevřená
zůstává otázka, zda samotná existence Bounded Contextu stačí, nebo zda AI agent potřebuje
výslovnou instruktáž o každém pravidle uvnitř kontextu.

## ai.03 Testování jako kontrolní mechanismus pro AI {#testovani}

Kent Beck, autor TDD a Extreme Programming, se otázce, jak AI mění způsob programování,
věnuje veřejně od roku 2023, kdy po prvním vyzkoušení ChatGPT napsal, že hodnota 90 % jeho
dovedností klesla na nulu. Podle shrnutí v The Pragmatic Engineer (červen 2025)
je TDD při práci s AI agenty obzvlášť cenné. Beck rozlišuje dva režimy.
Při *augmented coding* vývojář používá AI jako asistenta a odpovědnost za rozhodnutí
si ponechává. Při *vibe coding* naopak přijímá vše, co AI vygeneruje, bez porozumění
a bez ověření.

> „In vibe coding you don't care about the code, just the behavior of the system. […]
> In augmented coding you care about the code, its complexity, the tests,
> & their coverage.“
>
> – Kent Beck, Augmented Coding: Beyond the Vibes (Substack, 2025)

Testy tu slouží jako objektivní signál. Když sada testů popisuje doménová pravidla,
ne implementační detaily, selhání testu ukazuje, že se model odchýlil od záměru. TDD ve
spolupráci s AI tak přebírá část role code review.

Spoléhat na testy jako na nefalšovatelnou pojistku ale nelze. Beck sám mezi varovné signály
řadí okamžik, kdy agent podvádí tím, že testy vypíná nebo maže, aby prošly. Kontrolní
mechanismus tedy funguje jen tak dlouho, dokud na něj někdo dohlíží.

Martin Fowler přichází s podobným, ale méně optimistickým rámcem. V rozhovoru, který
referoval The New Stack (prosinec 2025), přirovnává AI k „pochybnému kolegovi“,
ke spolupracovníkovi, jehož výstup se musí pečlivě revidovat, ne slepě přijímat.

> „You've got to treat every slice as a PR from a rather dodgy collaborator
> who's very productive in the lines-of-code sense of productivity,
> but you know you can't trust a thing that they're doing.“
>
> – Martin Fowler, The New Stack, 2025

Podle Fowlera nedeterminismus LLM mění samotné uvažování o testování. Tradiční testování
předpokládá, že stejný vstup dá vždy stejný výstup, a u AI komponent to neplatí. Fowler
volá po nových metrikách a přístupech, ale přiznává, že komunita je teprve na začátku
tohoto hledání.

Třetí hlas patří DHH a jeho vyjádření jsou záměrně provokativní. V rozhovoru s Lexem
Fridmanem (červenec 2025), který referoval i The New Stack, vysvětluje, proč asistentovi
nepřenechá psaní kódu. Cursor a Windsurf zkusil a odmítl. Odtud pochází jeho
nejcitovanější věta k tématu:

> „I can literally feel competence draining out of my fingers!“
>
> – DHH, The New Stack, 2025

DHH varuje, že vývojář přestane rozumět kódu, který provozuje, a z inženýra se stane
správce AI. Sám přitom AI používá celý den, jen jinak. Vadí mu nekritické přijímání
výstupu, protože otupuje schopnost rozpoznat chybu.

Bez porozumění doméně nestačí ani testy. Vývojář, který doménu nechápe, napíše špatné
testy a AI pak dodá kód, který jimi projde, a přesto je chybný.

Riziko má konkrétní mechanismus. Jazykový model predikuje
pravděpodobné pokračování textu. Nemá v sobě nic, co by odlišilo kód správný od kódu, který
se v trénovacích datech vyskytoval nejčastěji. Doménový invariant je přitom tvrzení opačné
povahy: říká, co je nepřípustné, i když by to bylo běžné a na první pohled rozumné.
Objednávka se po expedici needituje, i když v devíti z deseti podobných tříd setter je.
Zde leží hranice generování a zároveň důvod, proč agregát s explicitním invariantem obstojí
lépe než anémický model – porušení je v něm vidět.

TDD ani code review nejsou vzory DDD, komunita kolem DDD je s nimi ale historicky
propojená. Taktické vzory se testují na úrovni domény bez zvláštní přípravy: agregát s invarianty,
doménová událost jako kontrakt. Agregát definuje pravidlo, test ho ověřuje, AI generuje
implementaci a test hlásí odchylku. Takový cyklus je odolnější než testy implementačních detailů.
Konkrétní strategie testování DDD modelů popisuje kapitola [Testování DDD](/testovani-ddd):
unit testy agregátů, integrační testy přes Messenger a contract testy mezi kontexty.

## ai.04 AI v doménové komplexitě vs. CRUD {#komplexita-vs-crud}

V přednášce na Explore DDD 2024 navrhl Evans taxonomii softwarových rozhodnutí o třech
kategoriích; třetí z nich přidává AI. První kategorií jsou
**hard-coded decisions**: pravidla absolutní, neměnná a se závažnými důsledky při porušení.
Příkladem je požadavek, že záporný stav účtu musí projít výslovným
schválením. Druhou tvoří **human-handled decisions**: situace tak
komplexní nebo citlivé, že musí rozhodovat člověk. Třetí, novou kategorií jsou
**LLM-supported decisions**: situace, kde rozhodnutí lze revidovat a chyba stojí málo. Konkrétní práh přesnosti Evans neuvádí; prakticky leží tam, kde zbytek chyb
odchytí revize.

Z taxonomie plyne, kam AI patří a kam ne. V pojišťovnictví, bankovnictví nebo zdravotnictví převažují hard-coded decisions a chyba
stojí hodně. Právě v těchto doménách přináší DDD největší
hodnotu a AI je v nich nejnebezpečnější, pokud jí nikdo nevymezí hranice. LLM-supported decisions existují i tady, například kategorizace dokumentů nebo návrh
odpovědi zákaznickému servisu. Musí ale zůstat jasně oddělené od hard-coded logiky.

Vaughn Vernon přidává konkrétní technický vzor: LLM jako „fix suggester“
(Explore DDD 2024, via InfoQ). Ve Vernonově vizi *self-healing software* reaguje
nástroj typu ChatGPT na výjimky za běhu a navrhne opravu ve formě pull requestu.
Návrh projde revizí, lidskou nebo automatizovanou, a teprve pak se aplikuje.
Bounded Context v tomto scénáři určuje pravidla ověření: co smí LLM
změnit a co musí zůstat beze změny.

Referenční implementace Microsoftu eShop (dříve eShopOnContainers) to rozlišení ukazuje na
praktickém příkladu. Modul `Ordering` používá plné taktické DDD:
agregáty, doménové události, CQRS. Modul `Catalog` je prostý CRUD
s Entity Framework. Rozdělení vzniklo záměrně, ne historickou nehodou. Implementační
komplexita patří tam, kde leží komplexita doménová. S příchodem AI se k této úvaze
přidává nová otázka: kde leží hranice mezi tím, co AI může autonomně rozhodovat,
a kde musí platit explicitní doménová pravidla?

DHH nabízí radikální protiváhu:

> „A lot of people, I think, are very uncomfortable with the fact that they are
> essentially crud monkeys. They just make systems that create, read, update,
> or delete rows in a database and they have to compensate for that existential
> dread by over-complicating things.“
>
> – DHH, Lex Fridman Podcast

DHH otevřeně říká, že velká část vývojářské práce je „CRUD monkeying“, tedy psaní
aplikací, které přijímají data, ukládají je a zobrazují. Složitá architektura je pro ně
podle něj zbytečná komplikace. Z toho se nabízí závěr, který DHH sám nevyslovuje,
protože psaní kódu asistentovi nepřenechává: takový kód vygeneruje AI z krátkého popisu
a doménový model k tomu potřeba není. Evans a DHH se rozcházejí hlavně v odhadu, jak
velký podíl softwarového průmyslu tvoří skutečně komplexní domény. Otevřené zůstává,
jestli AI tuto hranici posune: buď CRUD kód zlevní natolik, že na náročnou doménu zbude
čas, nebo se složité doménové problémy smrsknou na LLM-supported decisions.
Kdy DDD nasadit a kdy ne, rozebírá kapitola
[Kdy DDD nepoužívat](/kdy-nepouzivat-ddd).

## ai.05 DDD při stavbě systému, jehož součástí je LLM {#llm-jako-komponenta}

Předchozí sekce řeší jeden směr: pomáhá DDD, když kód generuje model? Evans mezitím publikoval
materiál k opačnému směru. Popisuje, jak modelovat systém, ve kterém je jazykový model
jednou z komponent. Na rozdíl od keynote z roku 2024 jde o jeho vlastní texty a o vzory, které kniha
učí jinde.

V článku *AI Components for a Deterministic System* (srpen 2025) Evans popisuje aplikaci,
která pomocí LLM klasifikuje domény v cizí kódové bázi. Jméno *Domain Navigator*
jí dává až navazující text z ledna 2026. Užitečné je rozlišení, které z ní
plyne: klasifikační úloha není modelovací úloha.
Klasifikace je opakovatelná, má správnou odpověď a model v ní vyniká. Modelování opakovatelné
není a správnou odpověď nemá. Smíchané do jednoho promptu vracejí výstupy, které nejde mezi
běhy porovnat. Evansovo řešení: nejdřív ustavit kanonickou taxonomii, teprve pak podle ní
klasifikovat.

Druhý článek, *Context Mapping with an AI-based Component* (leden 2026), kreslí Context Mapu
systému, jehož komponentou je LLM. Jeho závěry jsou pro návrh přímo použitelné:

- **LLM je Bounded Context.** Má vlastní jazyk, vlastní model konzistence a vlastní kontrakty.
  Nakreslit ho na Context Mapě jako samostatný kontext je přesnější než chápat ho jako knihovnu.
- **Anti-Corruption Layer není volitelný.** Překlad mezi deterministickou aplikací
  a probabilistickou komponentou znamená víc než rozparsovat JSON. Odpověď se validuje proti
  povolené taxonomii a teprve pak mapuje na doménový typ. Vzor popisuje kapitola
  [Context Mapping](/context-mapping#acl).
- **Kontext se pojmenovává konkrétním modelem**, ne obecným „LLM“. Modely nejsou zaměnitelné.
- **Taxonomie patří do Published Language.** Evans používá klasifikaci NAICS; sdílený slovník
  mezi aplikací a modelem hraje stejnou roli jako
  [Published Language](/context-mapping#published-language) mezi dvěma týmy.

Evans zároveň přiznává, že hranice mezi Anti-Corruption Layer a Conformistem je v reálném
systému šedá. Kdo přijme výstupní formát modelu beze změny, je Conformist – a nese důsledky, až se
formát změní.

V Symfony má vzor konkrétní podobu. Doménové rozhraní patří do `Domain/`, adaptér volající
poskytovatele přes `symfony/http-client` do `Infrastructure/`, validace odpovědi a mapování
na hodnotový objekt do téhož adaptéru. Volání modelu je I/O s latencí, selháním a nestabilním
výstupem, takže patří do Messenger handleru s retry strategií, ne do synchronního průchodu
controllerem. Rozvrstvení popisují kapitoly [Architektonické styly](/architektonicke-styly)
a [Implementace v Symfony 8](/implementace-v-symfony).

Stav PHP ekosystému k září 2026: balíček `php-llm/llm-chain` je na Packagistu označen jako
abandoned s náhradou `symfony/ai-agent`. Symfony AI existuje jako sada komponent
(`symfony/ai-platform`, `-agent`, `-bundle`, `-store` plus bridge balíčky pro jednotlivé
poskytovatele) ve shodné verzi 0.13.0. Vývoj běží, ale série 0.x nedává záruku zpětné
kompatibility. Samostatný balíček `symfony/ai` neexistuje.

## ai.06 Architektonické nástroje a kontext pro AI {#nastroje}

Soubory s instrukcemi pro agenta se ustálily do několika formátů. Cursor čte adresář
`.cursor/rules/` se soubory `.mdc`; každý nese pravidla, terminologii a omezení pro
konkrétní část projektu. GitHub Copilot čte `.github/copilot-instructions.md`, tedy globální
instrukce pro všechny konverzace v repozitáři. Claude Code používá `CLAUDE.md` na úrovni
projektu i jednotlivých adresářů – vlastní `CLAUDE.md` má v kořeni repozitáře i tento web.
Nástrojově neutrální `AGENTS.md` čte většina agentů včetně Cursoru, Copilotu a OpenAI Codexu;
od prosince 2025 formát spravuje Agentic AI Foundation pod Linux Foundation.

Žádný z těch formátů se na DDD neodvolává a žádná z citovaných autorit je nedoporučuje
jako dokumenty Bounded Contextu. Podobnost je věcí pozorování, ne doktríny – a Tuneova zkušenost citovaná
v [sekci ai.02](#bounded-contexts) ukazuje, kde končí: pravidlo zapsané v markdownu není
pravidlo vynucené.

Akademických prací k tématu je k září 2026 málo. Preprint Wieganda a kol., publikovaný
na arXiv v lednu 2026 jako součást sborníku Upper-Rhine AI Symposium 2024, zkoumá,
jestli doménové metamodely dokáže vytvořit generativní AI. Model Code Llama doladěný
na datech z reálných DDD projektů generuje doménově specifické JSON objekty a autoři
měří, zda jsou syntakticky správné. Odpověď je
kladná, a to i na běžné grafické kartě.

Jde ovšem o důkaz proveditelnosti jediného postupu, ne o srovnání. Zda strukturovaný
kontext vede k lepším výstupům než nestrukturovaný, tahle práce neměří – kontrolní
skupina v ní chybí.

ThoughtWorks Technology Radar DDD v kontextu AI přímo nezmiňuje, ale několik jeho blipů
k tématu patří. „Using GenAI to understand legacy codebases“ je od vydání 33 (listopad 2025)
v kategorii Adopt. Tamtéž se poprvé objevilo „Context engineering“ a „Anchoring coding agents
to a reference application“, obojí v Assess; vydání 34 (duben 2026) posunulo context
engineering do Adopt. Tím se z ad hoc praxe stala pojmenovaná disciplína: sestavit modelu
právě ten kontext, který pro úlohu potřebuje. Sevřenější kontext znamená přesnější výstupy –
a Bounded Context je jedna z odpovědí na otázku, kde ho oříznout.

Tytéž nástroje ale fungují i bez DDD. Kód psaný podle jasných konvencí (convention over
configuration) bývá pro agenta stejně čitelný jako explicitně modelovaný Bounded Context:
s ustáleným pojmenováním, slušnou testovou sadou a čitelným členěním adresářů se agent
zorientuje i bez formálního doménového modelu.
Otevřená zůstává otázka, co se stane, až projekt přeroste hranici, do které konvence stačí.

## ai.07 Otevřené otázky a limity {#otevrene-otazky}

Martin Fowler opakovaně připomíná (například v prosinci 2025), že spojení AI a softwarové
architektury je teprve na začátku. Nedeterminismus LLM, kdy tentýž prompt vrátí jiný výstup,
nemá uspokojivou metriku. Neví se, jak měřit architektonickou konzistenci generovaného kódu
ani jak ověřit, že AI respektuje hranice Bounded Contextu, když každé volání API může vrátit
jiný výsledek. Podle Fowlera se to obor teprve učí.

Chybí i odpověď na otázku, kde přesně generovaný kód uvnitř dobře vymezeného kontextu
selhává: v okrajových případech, v porušení invariantů, nebo v pojmenování, které se
rozchází s modelem. Bez toho nelze říct, jestli je hranice kontextu dostatečnou zárukou, nebo jen
zmenšuje prostor pro chybu. Dodatečnou vrstvu verifikace mohou tvořit architektonické testy
(deptrac, ArchUnit) nebo explicitní registr kontextů.

Alberto Brandolini, autor Event Stormingu, stojí na straně kombinace. Jeho workshop
*AI-Powered Domain-Driven Design* v Avanscopertě slibuje nasadit AI nástroje tam,
kde mají největší dopad, a přitom zachovat učení praktickými cvičeními. Účastníci
mají vážit lo-fi, hands-on a AI postupy proti sobě a znát meze každého z nich.
Vlastní vyjádření k tomu, nakolik Event Storming zůstává lidskou aktivitou, se nepodařilo
dohledat – anotace workshopu je k září 2026 jediný doklad jeho pozice.

Sam Newman, autor *Building Microservices*, se k AI v kontextu DDD do září 2026 jasně
nevyjádřil. Jeho pozice k distribuovaným systémům je dlouhodobě konzervativní:
microservices jako poslední možnost, nikoli jako výchozí architektura. Přenést tuto
zdrženlivost na AI je úvaha této knihy, ne Newmanova pozice. LLM nasazené
do produkčního systému je ovšem distribuovaná závislost se všemi problémy
distribuovaných systémů: s latencí, spolehlivostí, verzováním a monitoringem.

Otevřené otázky, na které obor zatím nemá odpověď:

- **Mění AI hranici, kde DDD dává smysl?** Pokud AI zlevní generování CRUD kódu
  natolik, že vývojářům zbude víc kapacity na složitou logiku, může se DDD
  vyplatit i tam, kde se dnes nevyplatí.
- **Stane se Ubiquitous Language standardem pro AI kontexty?**
  Cursor rules, CLAUDE.md i AGENTS.md sjednocují formát souboru, ne jeho obsah. Mohla by
  DDD komunita přispět formálnější strukturou pro definici AI kontextů?
- **Jaká bude role architekta v AI-augmentovaném týmu?** Pokud AI
  generuje implementaci, architekt se stává hlavně autorem kontextů, pravidel
  a verifikačních mechanismů. To má blíž k DDD modelování než k psaní kódu.
- **Co se stane s juniorními vývojáři?** DDD předpokládá, že tým
  rozumí doméně. Pokud AI generuje kód, kterému junioři nerozumí, jak se budují
  doménové znalosti pro příští generaci?

## ai.08 Závěr {#zaver}

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
            <td>Navrhuje fine-tuning na Ubiquitous Language; LLM je Bounded Context, ACL nad ním nutnost</td>
        </tr>
        <tr>
            <td><strong>Nick Tune</strong></td>
            <td>Pro AI, skeptický k popisné dokumentaci</td>
            <td>Architekturu je nutné vynucovat deterministicky, ne popsat v markdownu</td>
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
            <td>Opatrně pro DDD</td>
            <td>AI jako „dodgy collaborator“; potřeba nových metrik</td>
        </tr>
        <tr>
            <td><strong>Alberto Brandolini</strong></td>
            <td>Kombinuje</td>
            <td>Vede workshop AI-Powered DDD; váží lo-fi a AI postupy proti sobě</td>
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

Syntéza pozic vede k opatrnému, ale poměrně konzistentnímu závěru. Většina jmenovaných vidí
mezi DDD principy a prací s AI potenciální synergii. Nejkonkrétnější je Evans, protože jako
jediný publikoval vlastní model systému s jazykovou komponentou. Fowler a Beck jsou opatrně
optimističtí a volají po nových nástrojích a metrikách. Brandolini AI do modelovacích
workshopů pouští a nechává účastníky zvážit, kde pomůže a kde překáží.

DHH tvoří důležitý opačný hlas. Připomíná, že velká část softwarového průmyslu
je stále CRUD, že jednoduchost má svou hodnotu a že AI dovede být účinná i bez
formálního doménového modelování. Jeho pozice DDD neodporuje. Ukazuje jen, že DDD
nemá odpověď na každou otázku.

Zůstává jediná věc, na které se shodnou skoro všichni: struktura pomáhá. Výslovně popsaný, sdílený
kontext zlepšuje výsledky AI a DDD nabízí vyzkoušený slovník pro jeho popis. Tuneova
zkušenost k tomu přidává omezení, které se při čtení nadšených textů přehlíží:
popsaná struktura není vynucená struktura. Konvence, testy a deterministické kontroly
dosáhnou podobného účinku; spolu s doménovým modelem fungují líp než každé zvlášť.

Architektonické rozhodnutí proto vychází z domény, týmu a projektu: kde leží doménová
komplexita, kde je chyba drahá a jestli systém bude žít pět let. Přítomnost AI
v toolchainu o něm nerozhoduje.

:::faq{}
- question: Proč AI nástroje generují lepší kód v projektech s Ubiquitous Language?
  answer: 'Kontrolovaná měření k tomu zatím chybějí, mechanismus je ale zřejmý. Ubiquitous Language poskytuje LLM jednoznačný slovník, který se objevuje v dokumentaci, testech i kódu. Model při generování dostává konzistentní pojmy z kontextu a produkuje výstup, který zapadá do existujícího modelu bez překladu. Bez Ubiquitous Language AI často zavádí vlastní pojmenování, které se rozchází s doménou, a tým pak tráví čas jeho přepisováním. Evans na tom v roce 2024 postavil návrh doladit LLM přímo na slovníku jednoho Bounded Contextu. Podrobný rozbor v <a href="#ubiquitous-language">sekci Ubiquitous Language jako rozhraní pro LLM</a>.'
- question: Jak Bounded Contexts ovlivňují kvalitu kódu generovaného AI?
  answer: 'Bounded Context vymezuje srozumitelný rozsah, ve kterém se AI pohybuje. Místo „celé aplikace“ pracuje s jedním modelem, jednou sadou pravidel a jedním slovníkem. Menší, dobře ohraničený kontext znamená méně protichůdných informací v promptu a menší prostor pro halucinace. Podobný perimetr vymezují i konfigurační soubory agentů (Cursor rules, CLAUDE.md), praxe ale ukazuje, že popsané pravidlo agent dodrží hůř než pravidlo vynucené nástrojem. Rozbor v <a href="#bounded-contexts">sekci Bounded Contexts a kvalita generovaného kódu</a>.'
- question: Jakou roli hrají testy při práci s AI?
  answer: 'Testy fungují jako kontrolní mechanismus, který zachytává rozdíl mezi tím, co AI vygenerovala, a tím, co doména skutečně požaduje. Kent Beck hovoří o konceptu augmented coding: AI píše kód, testy potvrzují chování, a teprve když oba stojí spolu, jde změna do kódové báze. Bez testů se riziko nevyřešených chyb z AI výstupu kumuluje, protože LLM kód působí syntakticky správně, i když na úrovni chování selhává. Pojistka má ale mez: Beck sám mezi varovné signály řadí agenta, který testy vypíná nebo maže. Praktický rozbor v <a href="#testovani">sekci Testování jako kontrolní mechanismus pro AI</a>.'
- question: Kde jsou limity AI v doménově komplexním kódu?
  answer: 'AI zatím dobře zvládá rutinní úlohy (boilerplate, CRUD, jednoduché transformace), ale naráží u kódu, který odráží nekonzistentní doménovou realitu nebo vyžaduje modelování nových pravidel se stakeholdery. Martin Fowler popisuje AI jako „dodgy collaborator“, jehož výstup se musí pečlivě verifikovat, zejména u operací s vysokými náklady chyby. Otevřené otázky se týkají metrik kvality doménového modelu, role člověka v Event Stormingu a dlouhodobého dopadu AI na kompetence vývojářů. Viz <a href="#otevrene-otazky">sekci Otevřené otázky a limity</a>.'
:::

## ai.09 Zdroje a další čtení {#zdroje}

:::callout{type="note"}
**Primární zdroje:**

- **Evans, E. – Domain Language, srpen 2025:**
  <a href="https://www.domainlanguage.com/articles/ai-components-deterministic-system/" target="_blank" rel="noopener noreferrer">AI Components for a Deterministic System</a>.
  Evansův vlastní text: aplikace Domain Navigator a rozlišení klasifikační vs. modelovací
  úlohy.
- **Evans, E. – Domain Language, leden 2026:**
  <a href="https://www.domainlanguage.com/articles/context-mapping-an-ai-based-component/" target="_blank" rel="noopener noreferrer">Context Mapping with an AI-based Component</a>.
  Context mapa systému s LLM komponentou: LLM jako Bounded Context, Anti-Corruption Layer,
  Published Language, hranice vůči Conformistu.
- **Evans, E. – Explore DDD 2024 (InfoQ):**
  <a href="https://www.infoq.com/news/2024/03/Evans-ddd-experiment-llm/" target="_blank" rel="noopener noreferrer">DDD and Experiment With LLM – InfoQ, 2024</a>.
  Novinový referát keynote, ve které Evans navrhuje fine-tuning LLM na Ubiquitous Language
  a taxonomii hard-coded / human-handled / LLM-supported decisions. Zachycuje i reakce
  dalších praktiků včetně Vernonova konceptu „fix suggester“. Evans v ní sám upozorňuje,
  že jeho závěry platí ke dni 14. 3. 2024.
- **Fowler, M. – The New Stack, prosinec 2025:**
  <a href="https://thenewstack.io/martin-fowler-on-preparing-for-ais-nondeterministic-computing/" target="_blank" rel="noopener noreferrer">Martin Fowler on Preparing for AI's Nondeterministic Computing</a>.
  Referát rozhovoru pro podcast The Pragmatic Engineer. Zdroj citátu „dodgy collaborator“
  a věty, která jako cestu vpřed jmenuje domain-driven design i doménově specifické jazyky.
- **Joshi, U. – martinfowler.com, červenec 2026:**
  <a href="https://martinfowler.com/articles/llm-and-dsls.html" target="_blank" rel="noopener noreferrer">DSLs Enable Reliable Use of LLMs</a>.
  Rozpracovaný argument o DSL jako způsobu, jak omezit variabilitu vstupu. Autorem je
  Unmesh Joshi, článek vychází na Fowlerově webu jako hostovaný.
- **Beck, K. – Substack (Tidy First), duben 2023:**
  <a href="https://newsletter.kentbeck.com/p/90-of-my-skills-are-now-worth-0" target="_blank" rel="noopener noreferrer">90% of My Skills Are Now Worth $0</a>.
  Beckova první reakce na ChatGPT a úvaha, které dovednosti ztrácejí hodnotu.
- **Beck, K. – Substack (Tidy First), červen 2025:**
  <a href="https://tidyfirst.substack.com/p/augmented-coding-beyond-the-vibes" target="_blank" rel="noopener noreferrer">Augmented Coding: Beyond the Vibes</a>.
  Definice augmented coding vs. vibe coding. Beck zde popisuje i varovné signály, mezi něž
  řadí agenta vypínajícího nebo mažícího testy.
- **Beck, K. – The Pragmatic Engineer, červen 2025:**
  <a href="https://newsletter.pragmaticengineer.com/p/tdd-ai-agents-and-coding-with-kent" target="_blank" rel="noopener noreferrer">TDD, AI Agents, and Coding with Kent Beck</a>.
  Rozhovor s Beckem o TDD, AI agentech a budoucnosti programování.
- **DHH – Lex Fridman Podcast, červenec 2025:**
  <a href="https://lexfridman.com/dhh-david-heinemeier-hansson-transcript/" target="_blank" rel="noopener noreferrer">DHH: Programming, AI, Startups, and Open Source</a>.
  Zdroj citátů „crud monkeys“ i „competence draining out of my fingers“ a argumentu
  o hustotě významu na znak v Ruby.
- **DHH – The New Stack, červenec 2025:**
  <a href="https://thenewstack.io/dhh-on-ai-vibe-coding-and-the-future-of-programming/" target="_blank" rel="noopener noreferrer">DHH on AI, Vibe Coding, and the Future of Programming</a>.
  Referát téhož rozhovoru, ne samostatné vystoupení.
- **Ruby on Rails 8.1 Release Notes:**
  <a href="https://guides.rubyonrails.org/8_1_release_notes.html" target="_blank" rel="noopener noreferrer">Markdown Rendering</a>.
  Zdůvodnění nativního renderingu Markdownu: „Markdown has become the lingua franca of AI.“

**Praktické zdroje od DDD praktiků:**

- **Tune, N. – O'Reilly Radar, únor 2026:**
  <a href="https://www.oreilly.com/radar/reverse-engineering-your-software-architecture-with-claude-code-to-help-claude-code/" target="_blank" rel="noopener noreferrer">Reverse Engineering Your Software Architecture with Claude Code to Help Claude Code</a>.
  Použití Claude Code k mapování toků, závislostí a hranic v existující kódové bázi,
  včetně autorova varování o podstatných nepřesnostech generovaného popisu.
- **Tune, N. – nick-tune.me, říjen 2025:**
  <a href="https://nick-tune.me/blog/2025-10-22-extracting-your-software-architecture-with-ts-morph/" target="_blank" rel="noopener noreferrer">Extracting your software architecture with ts-morph</a>.
  Návazný text: deterministická extrakce architektonického modelu z kódu pomocí ts-morph.
- **Tune, N. – nick-tune.me, srpen 2026:**
  <a href="https://nick-tune.me/blog/2026-08-13-enforced-application-architecture-for-agents-and-humans/" target="_blank" rel="noopener noreferrer">Enforced Application Architecture for Agents and Humans</a>.
  Argument proti spoléhání na markdown soubory: architekturu je potřeba vynucovat
  deterministicky.
- **Tune, N. – nick-tune.me, říjen 2025:**
  <a href="https://nick-tune.me/blog/2025-10-26-enterprise-wide-software-architecture-as-ddd-living-document/" target="_blank" rel="noopener noreferrer">Enterprise-Wide Software Architecture as DDD Living Documentation</a>.
  Agregace architektonických dat napříč doménami do jednoho modelu systému.
- **ThoughtWorks – Technology Radar:**
  <a href="https://www.thoughtworks.com/radar/techniques/context-engineering" target="_blank" rel="noopener noreferrer">Context engineering</a>.
  Postup z Assess (vol. 33, listopad 2025) do Adopt (vol. 34, duben 2026); tamtéž blipy
  „Using GenAI to understand legacy codebases“ a „Anchoring coding agents to a reference
  application“.

**Výzkumné zdroje:**

- **GitClear, únor 2025:**
  <a href="https://www.gitclear.com/ai_assistant_code_quality_2025_research" target="_blank" rel="noopener noreferrer">AI Copilot Code Quality: 2025 Look Back at 12 Months of Data</a>.
  Pokles podílu refaktorovaných řádků a růst klonovaného kódu.
- **GitClear, leden 2026:**
  <a href="https://www.gitclear.com/the_ai_code_quality_maintainability_gap" target="_blank" rel="noopener noreferrer">The Maintainability Gap: AI Code Quality in 2026</a>.
  623 milionů analyzovaných změn; duplicita bloků o 81 % vyšší než v roce 2023.
- **GitClear / Visual Studio Magazine, leden 2024:**
  <a href="https://visualstudiomagazine.com/articles/2024/01/25/copilot-research.aspx" target="_blank" rel="noopener noreferrer">Coding on Copilot: 2023 Data Suggests Downward Pressure on Code Quality</a>.
  Definice code churn a původní projekce jeho zdvojnásobení pro rok 2024.
- **Wiegand et al. – arXiv, leden 2026:**
  <a href="https://arxiv.org/html/2601.20909" target="_blank" rel="noopener noreferrer">Leveraging Generative AI for Enhancing Domain-Driven Software Design</a>.
  Fine-tuning modelu Code Llama na generování doménových JSON objektů; text je součástí
  sborníku Upper-Rhine Artificial Intelligence Symposium 2024.
- **UnderstandingData.com:**
  <a href="https://understandingdata.com/posts/ddd-bounded-contexts-for-llms/" target="_blank" rel="noopener noreferrer">DDD Bounded Contexts for LLMs</a>.
  Osobní blog Jamese Phoenixe bez uvedené metodologie; zdroj čísel citovaných v sekci ai.02.
:::
