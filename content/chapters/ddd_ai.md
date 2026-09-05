---
route: ddd_ai
path: /ddd-a-umela-inteligence
title: DDD a umělá inteligence – co říkají autority
page_title: "DDD a umělá inteligence – co říkají autority | DDD Symfony"
meta_description: "Vztah DDD a AI nástrojů očima Erica Evanse, Martina Fowlera, Kenta Becka a DHH. Ubiquitous Language jako rozhraní pro LLM, Bounded Contexts a kvalita kódu."
meta_keywords: "DDD AI, domain-driven design umělá inteligence, DDD LLM, Eric Evans AI, Martin Fowler AI, Kent Beck AI, DDD bounded context AI, ubiquitous language LLM"
og_type: article
published: "2026-03-27"
modified: "2026-07-08"
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

Jsou některé architektonické přístupy s nástupem LLM vhodnější než jiné? Nabízí Domain-Driven Design
výhody, které teď nabývají na váze – nebo naopak přidává zbytečnou komplexitu v době,
kdy AI dokáže generovat kód z krátkého popisu?

Kapitola mapuje, co o vztahu DDD a umělé inteligence říkají přední autority softwarového
inženýrství – Eric Evans, Martin Fowler, Kent Beck, Vaughn Vernon, Nick Tune, Alberto Brandolini
a DHH. Jde o přehled jejich pozic, argumentů a dat, nikoli obhajobu ani kritiku
konkrétního přístupu. Druhý směr téhož vztahu – DDD jako metoda pro stavbu systému, jehož
součástí je jazykový model – dostal vlastní sekci.

U každého výroku je uveden rok, kdy zazněl. Stav je zmapován k září 2026 a pozice se v tomto
tématu mění po měsících, ne po letech. Část následujícího textu zestárne dřív než zbytek knihy.

## ai.01 Ubiquitous language jako rozhraní pro LLM {#ubiquitous-language}

Jeden z nejkonkrétnějších výroků o vztahu DDD a AI pochází přímo od Erica Evanse. Na konferenci
Explore DDD 2024 navrhl doladit (fine-tuning) model na ubiquitous language jednoho bounded
contextu – na terminologii, pravidla a výrazy, které tým denně používá v diskusích s doménovými
experty. Vytrénovaný model je podle něj sám o sobě bounded context a několik takových modelů
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
dokumentů jsou typické příklady. Stejně jako dnes máme samostatné bounded contexty pro platby,
notifikace nebo sklad, budeme mít kontext pro „rozumění textu“ nebo „extrakci strukturovaných
dat“. Předpověď odpovídá tomu, jak velké firmy AI platformy staví – jako interní služby
s vlastními API hranicemi, ne jako průřezovou vrstvu přes celý systém.

Martin Fowler na to navazuje z jiného úhlu. V rozhovoru o přípravě na nedeterministické
výpočty (2025) jmenuje domain-driven design a doménově specifické jazyky jako cestu
k rigoróznějšímu promptování LLM. Rozpracovaný argument vyšel na jeho webu z pera Unmeshe
Joshiho: obecný jazyk nabízí spoustu způsobů, jak vyjádřit tentýž záměr, kdežto DSL tu
variabilitu odřízne, takže modelu stačí pár příkladů a syntaxi generuje spolehlivě. Pevný
jazyk na vstupu znamená méně entropie na výstupu.

Opačný pól drží David Heinemeier Hansson (DHH). V rozhovoru pro Lex Fridman Podcast (2025)
argumentoval, že Ruby má vyšší přenosovou kapacitu než jiné jazyky – na jeden znak unese
víc významu. Při spolupráci s AI je to podle něj výhoda: oba, člověk i model, potřebují
kódu rozumět rychle. Sázka tedy nejde na formální doménový jazyk, ale na hustotu
vyjádření a na konvence samotného frameworku.

Do téhož obrázku zapadá Rails 8.1 s nativním renderingem Markdownu; release notes ho
zdůvodňují tím, že se Markdown stal lingua franca AI nástrojů.

Velké jazykové modely pracují s přirozeným jazykem jako svým primárním médiem.
Ubiquitous language v DDD je precizní podmnožina přirozeného jazyka – terminologie domény
zbavená nejednoznačností a obohacená o doménová pravidla. Funguje proto jako most mezi
doménovými experty a LLM: pojmy srozumitelné lidem jsou srozumitelné i modelu. Otázka zní,
zda náklady na vybudování a udržení ubiquitous language odpovídají získaným výhodám –
a odpověď se liší projekt od projektu. Definici a roli ubiquitous language v DDD popisuje
kapitola [Základní koncepty DDD](/zakladni-koncepty#ubiquitous-language).

## ai.02 Bounded contexts a kvalita generovaného kódu {#bounded-contexts}

Tvrdá data k tomu, jak hranice bounded contextu ovlivňují kód generovaný LLM, zatím nejsou.
Kontrolovaná studie s definovanou metodologií a vzorkem chybí. K dispozici jsou zkušenosti
praktiků, měření kvality kódu, která o DDD nemluví, a jeden preprint, který srovnání nedělá.
Tomu odpovídá i jistota závěrů, které z toho v této sekci plynou.

Jediná konkrétní čísla, která se k tématu dají dohledat, pocházejí z blogpostu Jamese
Phoenixe: přesnost kolem 55 %
bez explicitních hranic proti 88 % s nimi, porušení architektonických hranic v 35 % případů
proti méně než pěti procentům. Autor je uvádí jako vlastní odhad bez metodologie i vzorku,
takže kapitola na nich nic nestaví. Zůstávají jako ilustrace toho, co praktici pozorují.

Nick Tune je jedním z nejaktivnějších praktiků na průsečíku DDD a AI. V článku pro O'Reilly
Radar (únor 2026) popisuje, jak použil Claude Code k reverznímu inženýrství softwarové
architektury – automatickému mapování end-to-end toků, závislostí a hranic v existující
kódové bázi. V návazném článku ukazuje, jak lze pomocí knihovny ts-morph deterministicky
extrahovat architektonické vzory, které slouží jako vstup pro AI agenty. K výsledku sám
připojuje varování: v generovaném popisu architektury byly podstatné nepřesnosti, které
musel odhalit a opravit.

Kniha z toho vyvozuje vlastní úvahu: agent pracující uvnitř jednoho bounded contextu
potřebuje znát méně, a čím míň musí uhodnout, tím míň chyb udělá.

Za pozornost stojí i to, kam se nástroje samy posunuly. Cursor, GitHub Copilot i Claude Code
čtou soubory s pravidly, terminologií a omezeními pro konkrétní část kódu – tedy něco,
co se bounded contextu s ubiquitous language podobá. Formáty rozebírá
[sekce ai.06](#nastroje).

Podobnost má ale mez a Tune na ni upozorňuje z vlastní zkušenosti: generovaný kód se
architektonickými pravidly zapsanými v markdown souborech prostě neřídí. Jeho závěr je
proto opačný, než by analogie svedla čekat – architekturu je potřeba vynucovat
deterministicky, ne ji popsat a doufat.

Druhý pohled přinášejí data z GitClear. Code churn je podíl řádků přepsaných nebo smazaných
do dvou týdnů od vytvoření; jeho zdvojnásobení firma v lednu 2024 ohlásila jako projekci.
Pozdější reporty už stojí na naměřených hodnotách. Podíl řádků spojených s refaktoringem klesl
ze čtvrtiny v roce 2021 pod desetinu v roce 2024, klonované řádky vzrostly z 8,3 % na 12,3 %
a kopírovaný kód poprvé překonal přesouvaný. Report za rok 2026 na vzorku 623 milionů změn
ukazuje duplicitu bloků o 81 % vyšší než v roce 2023. Ani jedno měření o DDD nemluví a příčinu
neprokazuje – ukazuje jen, kterým směrem se kvalita kódu za éry asistentů posunula.

Každý soubor nebo funkce přitom může být syntakticky správná a pro svůj bezprostřední účel
funkční. Drhnou až větší celky: hranice mezi moduly, zachování invariantů, konzistentní
pojmenování napříč kódovou bází. Bounded contexts na tenhle typ problému míří. Otevřená
zůstává otázka, zda samotná existence bounded contextu stačí, nebo zda AI agent potřebuje
explicitní instruktáž o každém pravidle uvnitř kontextu.

## ai.03 Testování jako kontrolní mechanismus pro AI {#testovani}

Kent Beck, autor TDD a Extreme Programming, se otázce, jak AI mění způsob programování,
věnuje veřejně od roku 2025. Podle shrnutí v The Pragmatic Engineer (červen 2025)
je TDD při práci s AI agenty obzvlášť cenné. Beck rozlišuje mezi dvěma módy.
*Augmented coding* znamená, že vývojář používá AI jako asistenta a zachovává zodpovědnost
za rozhodnutí. *Vibe coding* znamená, že vývojář přijímá vše, co AI vygeneruje,
bez porozumění a bez verifikace.

> „In vibe coding you don't care about the code, just the behavior of the system. […]
> In augmented coding you care about the code, its complexity, the tests,
> & their coverage.“
>
> – Kent Beck, Augmented Coding: Beyond the Vibes (Substack, 2025)

Testy tu slouží jako objektivní signál: existuje-li sada testů popisující doménová pravidla,
ne implementační detaily, pak selhání testu ukazuje, že se model odchýlil od záměru. TDD ve
spolupráci s AI tak přebírá část role code review.

Spoléhat na testy jako na nefalšovatelnou pojistku ale nelze. Beck sám mezi varovné signály
řadí okamžik, kdy agent podvádí tím, že testy vypíná nebo maže, aby prošly. Kontrolní
mechanismus tedy funguje jen tak dlouho, dokud na něj někdo dohlíží.

Martin Fowler přichází s podobným, ale méně optimistickým rámcem. V rozhovoru, který
referoval The New Stack (prosinec 2025), přirovnává AI k „pochybnému kolegovi“ –
spolupracovníkovi, jehož výstup je třeba pečlivě revidovat, nikoli slepě přijímat.

> „You've got to treat every slice as a PR from a rather dodgy collaborator
> who's very productive in the lines-of-code sense of productivity,
> but you know you can't trust a thing that they're doing.“
>
> – Martin Fowler, The New Stack, 2025

Fowler zdůrazňuje, že nedeterminismus LLM – stejná otázka, jiný výsledek – od základu
mění způsob, jakým přemýšlíme o testování. Tradiční testování předpokládá deterministický
systém: stejný vstup, stejný výstup, vždy. Pro AI komponenty to neplatí. Fowler volá
po nových metrikách a nových přístupech, ale přiznává, že komunita je teprve na začátku
tohoto hledání.

Třetí hlas patří DHH a jeho vyjádření jsou záměrně provokativní. V rozhovoru s Lexem
Fridmanem (červenec 2025), který referoval i The New Stack, popisuje, proč asistenta
nepustí k řízení psaní kódu: Cursor a Windsurf zkusil a odmítl. Odtud pochází jeho
nejcitovanější věta k tématu:

> „I can literally feel competence draining out of my fingers!“
>
> – DHH, The New Stack, 2025

DHH varuje před nebezpečím, kdy vývojář přestane rozumět kódu, který provozuje – stane
se manažerem projektu AI místo inženýrem. Sám přitom AI používá celý den, jen jinak.
Jde mu o to, že nekritické přijetí výstupu degraduje schopnost rozpoznat chybu.
Bez doménového porozumění nejsou testy dostatečné: vývojář, který nechápe doménu, nepíše
správné testy, a AI pak plní testy generováním falešně pozitivního kódu.

Riziko má konkrétní mechanismus a stojí za to ho pojmenovat. Jazykový model predikuje
pravděpodobné pokračování textu. Nemá v sobě nic, co by odlišilo kód správný od kódu, který
se v trénovacích datech vyskytoval nejčastěji. Doménový invariant je přitom tvrzení opačné
povahy: říká, co je nepřípustné, i když by to bylo běžné a na první pohled rozumné.
Objednávka se po expedici needituje, i když v devíti z deseti podobných tříd setter je.
Zde leží hranice generování a zároveň důvod, proč agregát s explicitním invariantem obstojí
lépe než anemický model – porušení je v něm vidět.

Kontext pro DDD komunitu: TDD ani code review nejsou vzory DDD, ale jeho komunita je s nimi
historicky propojena. Taktické vzory – agregáty s invarianty, doménové události jako
kontrakty – se testují na úrovni domény bez zvláštní přípravy.
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
**LLM-supported decisions**: situace, kde rozhodnutí lze revidovat a kde náklady na chybu
jsou nízké. Konkrétní práh přesnosti Evans neuvádí; prakticky leží tam, kde zbytek chyb
odchytí revize.

Taxonomie má přímý dopad na to, kde AI dává smysl a kde ne. Ve vysoce komplexní
doméně – pojišťovnictví, bankovnictví, zdravotnictví – převažují hard-coded decisions
a chyba stojí hodně. Paradoxně to jsou domény, kde DDD přináší největší
hodnotu, ale kde je AI nejnebezpečnější, pokud není správně ohraničena. LLM-supported
decisions existují i zde – například kategorizace dokumentů nebo návrh odpovědi zákaznickému
servisu – ale musí být jasně odděleny od hard-coded logiky.

Vaughn Vernon přidává konkrétní technický vzor: LLM jako „fix suggester“
(Explore DDD 2024, via InfoQ). Ve Vernonově vizi *self-healing software* reaguje
nástroj typu ChatGPT na runtime výjimky a navrhne opravu ve formě pull requestu.
Návrh projde revizí – lidskou nebo automatizovanou – a teprve pak se aplikuje.
DDD bounded context v tomto scénáři definuje pravidla verifikace: co smí LLM
změnit a co musí zůstat neměnné.

Referenční implementace Microsoftu – eShop, dříve eShopOnContainers – ilustruje toto rozlišení
na praktickém příkladu. Modul `Ordering` používá plné taktické DDD:
agregáty, doménové události, CQRS. Modul `Catalog` je prostý CRUD
s Entity Framework. Rozdělení vzniklo záměrně, ne historickou nehodou: implementační
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

DHH otevřeně říká, že většina vývojářské práce je „CRUD monkeying“ – psaní
aplikací, které přijímají data, ukládají je a zobrazují. Pro tuto kategorii
aplikací je DDD přeceňované – a AI, která generuje CRUD kód z jednoduchého popisu,
je přirozeným řešením bez potřeby doménového modelu. Hlavní otázka, na kterou
DHH odpovídá jinak než Evans, je: jak velký podíl softwarového průmyslu tvoří
skutečně komplexní domény versus CRUD monkeying? A mění AI tuto hranici? Buď tím,
že CRUD kód zlevní natolik, že zbude čas na komplexní doménu, nebo tím,
že komplexní doménové problémy de facto „zjednoduší“ na LLM-supported decisions.
Pro praktické rozhraničení toho, kdy DDD nasazovat a kdy ne, viz kapitolu
[Kdy DDD nepoužívat](/kdy-nepouzivat-ddd).

## ai.05 DDD při stavbě systému, jehož součástí je LLM {#llm-jako-komponenta}

Předchozí sekce řeší jeden směr: pomáhá DDD, když kód generuje model? Evans mezitím publikoval
materiál k opačnému směru – jak modelovat systém, ve kterém je jazykový model jednou
z komponent. Na rozdíl od keynote z roku 2024 jde o jeho vlastní texty a o vzory, které kniha
učí jinde.

V článku *AI Components for a Deterministic System* (srpen 2025) Evans popisuje aplikaci,
která pomocí LLM klasifikuje domény v cizí kódové bázi; jméno *Domain Navigator* jí dává
až navazující text z ledna 2026. Odnáší si z ní
rozlišení, které stojí za převzetí: klasifikační úloha není modelovací úloha.
Klasifikace je opakovatelná, má správnou odpověď a model v ní vyniká. Modelování opakovatelné
není a správnou odpověď nemá. Smíchané do jednoho promptu vracejí výstupy, které nejde mezi
běhy porovnat. Evansovo řešení: nejdřív ustavit kanonickou taxonomii, teprve pak podle ní
klasifikovat.

Druhý článek, *Context Mapping with an AI-based Component* (leden 2026), kreslí context mapu
systému, jehož komponentou je LLM. Jeho závěry jsou pro návrh přímo použitelné:

- **LLM je bounded context.** Má vlastní jazyk, vlastní model konzistence a vlastní kontrakty.
  Nakreslit ho na context mapě jako samostatný kontext je přesnější než chápat ho jako knihovnu.
- **Anticorruption layer není volitelný.** Překlad mezi deterministickou aplikací
  a probabilistickou komponentou znamená víc než rozparsovat JSON: odpověď se validuje proti
  povolené taxonomii a teprve pak mapuje na doménový typ. Vzor popisuje kapitola
  [Context Mapping](/context-mapping#acl).
- **Kontext se pojmenovává konkrétním modelem**, ne obecným „LLM“. Modely nejsou zaměnitelné.
- **Taxonomie patří do Published Language.** Evans používá klasifikaci NAICS; sdílený slovník
  mezi aplikací a modelem hraje stejnou roli jako
  [Published Language](/context-mapping#published-language) mezi dvěma týmy.

Evans zároveň přiznává, že hranice mezi anticorruption layer a Conformistem je v reálném
systému šedá. Kdo přijme výstupní formát modelu beze změny, dělá Conformist – a nese důsledky, až se
formát změní.

V Symfony má vzor konkrétní podobu. Doménové rozhraní patří do `Domain/`, adaptér volající
poskytovatele přes `symfony/http-client` do `Infrastructure/`, validace odpovědi a mapování
na hodnotový objekt do téhož adaptéru. Volání modelu je I/O s latencí, selháním a nestabilním
výstupem, takže patří do Messenger handleru s retry strategií, ne do synchronního průchodu
controllerem. Rozvrstvení popisují kapitoly [Architektonické styly](/architektonicke-styly)
a [Implementace DDD v Symfony 8](/implementace-v-symfony).

Stav PHP ekosystému k září 2026: balíček `php-llm/llm-chain` je na Packagistu označen jako
abandoned s náhradou `symfony/ai-agent`. Symfony AI existuje jako sada komponent
(`symfony/ai-platform`, `-agent`, `-bundle`, `-store` plus bridge balíčky pro jednotlivé
poskytovatele) ve shodné verzi 0.13.0. Vývoj běží, ale série 0.x nedává záruku zpětné
kompatibility. Samostatný balíček `symfony/ai` neexistuje.

## ai.06 Architektonické nástroje a kontext pro AI {#nastroje}

Soubory s instrukcemi pro agenta se ustálily do tří rozšířených formátů. Cursor čte adresář
`.cursor/rules/` s příponou `.mdc`; každý soubor nese pravidla, terminologii a omezení pro
konkrétní část projektu. GitHub Copilot čte `.github/copilot-instructions.md`, tedy globální
instrukce pro všechny konverzace v repozitáři. Claude Code používá `CLAUDE.md` na úrovni
projektu i jednotlivých adresářů – vlastní `CLAUDE.md` má v kořeni repozitáře i tento web.

Žádný z těch formátů se na DDD neodvolává a žádná autorita je jako bounded context dokumenty
nedoporučuje. Podobnost je věcí pozorování, ne doktríny – a Tuneova zkušenost citovaná
v [sekci ai.02](#bounded-contexts) ukazuje, kde končí: pravidlo zapsané v markdownu není
pravidlo vynucené.

Akademický výzkum tuto praxi teprve začíná zkoumat. Preprint Wieganda a kol., publikovaný
na arXiv v lednu 2026 jako součást sborníku Upper-Rhine AI Symposium 2024, zkouší
automatizovat tvorbu doménových metamodelů generativní AI:
model Code Llama doladěný na datech z reálných DDD projektů generuje doménově
specifické JSON objekty a autoři měří, zda jsou syntakticky správné. Odpověď je
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
a bounded context je jedna z odpovědí na otázku, kde ho oříznout.

Druhá strana mince: ty samé nástroje fungují i bez DDD. Kód psaný podle jasných konvencí –
convention over configuration v Rails stylu – bývá pro agenta stejně čitelný jako explicitně
modelovaný bounded context. V projektu s ustáleným pojmenováním, slušnou testovou sadou
a čitelným rozčleněním do adresářů se agent zorientuje bez formálního DDD modelu.
Disciplinovaná konvence plní podobnou roli jako explicitní model. Otevřená zůstává otázka,
co se stane, až projekt vyroste za hranice, kde konvence stačí.

## ai.07 Otevřené otázky a limity {#otevrene-otazky}

Martin Fowler opakovaně zdůrazňuje, že oblast AI a softwarové architektury je v roce 2026
teprve na začátku. Nedeterminismus LLM – stejný prompt, jiný výstup – zatím nemá
uspokojivou metriku. Jak měříme architektonickou konzistenci generovaného kódu?
Jak verifikujeme, že AI respektuje hranice bounded contextu, když každé volání
API může vrátit jiný výsledek? Fowler hovoří o tom, že „stále se učíme“ –
a to je poctivý popis stavu oboru.

Chybí i odpověď na otázku, kde přesně generovaný kód uvnitř dobře vymezeného kontextu
selhává. V okrajových případech? V porušení invariantů? V pojmenování, které se rozchází
s modelem? Bez toho nelze říct, jestli je hranice kontextu dostatečnou zárukou, nebo jen
zmenšuje prostor pro chybu. Dodatečnou vrstvu verifikace mohou tvořit architektonické testy
(deptrac, ArchUnit) nebo explicitní registr kontextů.

Alberto Brandolini, autor EventStormingu, stojí na straně kombinace. Jeho workshop
*AI-Powered Domain-Driven Design* v Avanscopertě slibuje nasadit AI nástroje tam,
kde mají největší dopad, a přitom zachovat učení skrz praktická cvičení; účastníci
mají vážit lo-fi, hands-on a AI postupy proti sobě a znát meze každého z nich.
Vlastní vyjádření k tomu, nakolik EventStorming zůstává lidskou aktivitou, se nepodařilo
dohledat – anotace workshopu je zatím jediný doklad jeho pozice.

Sam Newman – autor Building Microservices – se k AI v kontextu DDD zatím jasně
nevyjádřil. Jeho pozice k distribuovaným systémům je dlouhodobě konzervativní:
mikroservisy jako poslední možnost, nikoli jako výchozí architektura. Zda tato
zdrženlivost platí i pro AI, je autorský odhad, nikoli referovaná pozice. Nasazení LLM
do produkčního systému je nicméně distribuovaná závislost se všemi problémy
distribuovaných systémů – latencí, spolehlivostí, verzováním, monitoringem.

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
            <td>Navrhuje fine-tuning na ubiquitous language; LLM je bounded context, ACL nad ním nutnost</td>
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
            <td>Nuancovaně pro DDD</td>
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

Syntéza pozic vede k opatrnému, ale poměrně konzistentnímu závěru: většina jmenovaných vidí
mezi DDD principy a prací s AI potenciální synergii. Nejkonkrétnější je Evans, protože jako
jediný publikoval vlastní model systému s jazykovou komponentou. Fowler a Beck jsou opatrně
optimističtí a volají po nových nástrojích a metrikách. Brandolini AI do modelovacích
workshopů pouští a nechává účastníky zvážit, kde pomůže a kde překáží.

DHH tvoří důležitý opačný hlas: připomíná, že velká část softwarového průmyslu
je stále CRUD, že jednoduchost má svou hodnotu a že AI dovede být účinná i bez
formálního doménového modelování. Jeho pozice DDD neodporuje. Ukazuje jen, že DDD
nemá odpověď na každou otázku.

Zůstává jediná věc, na které se shodnou skoro všichni: struktura pomáhá. Explicitní, sdílený
kontext zlepšuje výsledky AI a DDD nabízí vyzkoušený slovník pro jeho popis. Tuneova
zkušenost k tomu přidává omezení, které je při čtení nadšených textů snadné přehlédnout:
popsaná struktura není vynucená struktura. Konvence, testy a deterministické kontroly
dosáhnou podobného účinku; spolu s doménovým modelem fungují líp než každé zvlášť.

Rozhodovat se má smysl podle domény, týmu a projektu. Kde leží doménová
komplexita? Kde jsou náklady na chybu vysoké? Kde bude systém žít pět let?
Architektonické rozhodnutí by mělo vyplývat z těchto otázek, ne z přítomnosti
nebo nepřítomnosti AI v toolchainu.

:::faq{}
- question: Proč AI nástroje generují lepší kód v projektech s Ubiquitous Language?
  answer: 'Ubiquitous Language poskytuje LLM jednoznačný slovník, který se objevuje napříč dokumentací, testy i kódem. Model při generování dostává konzistentní pojmy z kontextu a produkuje výstup, který zapadá do existujícího modelu bez překladu. Bez Ubiquitous Language AI často zavádí vlastní pojmenování, které se rozchází s doménou, a tým pak tráví čas jeho přepisováním. Evans na tom v roce 2024 postavil návrh doladit LLM přímo na slovníku jednoho bounded contextu. Podrobný rozbor v <a href="#ubiquitous-language">sekci Ubiquitous language jako rozhraní pro LLM</a>.'
- question: Jak Bounded Contexts ovlivňují kvalitu kódu generovaného AI?
  answer: 'Bounded Context vymezuje srozumitelný rozsah, ve kterém se AI pohybuje – místo „celé aplikace“ pracuje s jedním modelem, jednou sadou pravidel a jedním slovníkem. Menší, dobře ohraničený kontext znamená méně protichůdných informací v promptu a menší prostor pro halucinace. Podobný perimetr vymezují i konfigurační soubory agentů (Cursor rules, CLAUDE.md), praxe ale ukazuje, že popsané pravidlo agent dodrží hůř než pravidlo vynucené nástrojem. Rozbor v <a href="#bounded-contexts">sekci Bounded contexts a kvalita generovaného kódu</a>.'
- question: Jakou roli hrají testy při práci s AI?
  answer: 'Testy fungují jako kontrolní mechanismus, který zachytává rozdíl mezi tím, co AI vygenerovala, a tím, co doména skutečně požaduje. Kent Beck hovoří o konceptu augmented coding: AI píše kód, testy potvrzují chování, a teprve když oba stojí spolu, jde změna do kódové báze. Bez testů se riziko nevyřešených chyb z AI výstupu kumuluje, protože LLM kód působí syntakticky správně, i když na úrovni chování selhává. Pojistka má ale mez: Beck sám mezi varovné signály řadí agenta, který testy vypíná nebo maže. Praktický rozbor v <a href="#testovani">sekci Testování jako kontrolní mechanismus pro AI</a>.'
- question: Kde jsou limity AI v doménově komplexním kódu?
  answer: 'AI zatím dobře zvládá rutinní úlohy (boilerplate, CRUD, jednoduché transformace), ale naráží u kódu, který odráží nekonzistentní doménovou realitu nebo vyžaduje modelování nových pravidel se stakeholdery. Martin Fowler popisuje AI jako „dodgy collaborator“, jejíž výstup je třeba pečlivě verifikovat – zejména u operací s vysokými náklady chyby. Otevřené otázky se týkají metrik kvality doménového modelu, role člověka v EventStormingu a dlouhodobého dopadu AI na kompetence vývojářů. Viz <a href="#otevrene-otazky">sekci Otevřené otázky a limity</a>.'
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
  Context mapa systému s LLM komponentou: LLM jako bounded context, anticorruption layer,
  Published Language, hranice vůči Conformistu.
- **Evans, E. – Explore DDD 2024 (InfoQ):**
  <a href="https://www.infoq.com/news/2024/03/Evans-ddd-experiment-llm/" target="_blank" rel="noopener noreferrer">DDD and Experiment With LLM – InfoQ, 2024</a>.
  Novinový referát keynote, ve které Evans navrhuje fine-tuning LLM na ubiquitous language
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
  Použití Claude Code a ts-morph k extrakci architektonických vzorů, včetně autorova
  varování o podstatných nepřesnostech generovaného popisu.
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
