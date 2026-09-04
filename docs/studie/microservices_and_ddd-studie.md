# Studie: DDD a microservices

- **Kapitola:** `content/chapters/microservices_and_ddd.md` (č. 19, kategorie Praxe, 914 řádků)
- **Cesta:** /ddd-a-microservices
- **Typ kapitoly:** hybridní
- **Datum studie:** 2026-09-03

> Poznámka k rešerši: rozpočet na `WebSearch` byl v této session vyčerpán (200/200) už při prvním
> dotazu. Celá rešerše proběhla přes cílené `WebFetch` primárních URL. V bibliografii je u každého
> zdroje uveden způsob získání. Tři relevantní zdroje se získat nepodařilo (403 / 301 / 404) –
> viz sekce 9, „Neověřené".

## 1. Mapa současné kapitoly

| Sekce | Rozsah | Co tvrdí | Zdroje | Poznámka |
|---|---|---|---|---|
| Úvod | 22–24 | Navazuje na BC, Context Mapping, ságy. Tři „přehlížené pravdy". | odkazy na kapitoly | Dobré ukotvení |
| 19.01 Mýtus BC = microservice | 26–57 | BC = logická hranice, service = fyzická. Tři varianty mapování 1:1 / 1:N / N:1. | Newman 2021 kap. 2, Richardson 2018 kap. 2, Vernon 2013 kap. 2 | Nejsilnější sekce. Chybí Lewis & Fowler 2014 a Evans osobně. |
| 19.02 Kdy 1 BC = 1 service | 58–92 | Šest podmínek pro vlastní službu, ilustrativní e-shop, heuristika „4+ bodů". | Newman 2021, Skelton & Pais 2019 | Heuristika 4/6 je vlastní vynález knihy, není označena |
| 19.03 Modular monolith | 94–262 | Modular monolith jako výchozí volba. Struktura `src/`, phparkitect, kdy odejít. | Fowler *MonolithFirst* 2015, Newman kap. 3 | Nejdelší nekódová sekce. Chybí Shopify, Grzybek, DHH, Brown |
| 19.04 Distributed monolith | 263–397 | 5 příznaků, proč je horší než monolit, hybridní topologie, de-microservicing, tabulka nákladů | Newman (bez lokace), Prime Video 2023, Cockcroft | Nejproblematičtější sekce: nepodložená čísla, sporná atribuce Prime Video |
| 19.05 Sync vs. async | 399–436 | Kdy sync, kdy async, pravidlo „async-first", srovnávací tabulka | Richardson 2018 kap. 3 | Jen 38 řádků na klíčové téma. Chybí Fowler 2017 (event notification vs. ECST) |
| 19.06 Distribuované transakce | 437–460 | 2PC nepoužitelné, saga jako odpověď, choreografie vs. orchestrace | odkaz na kap. 14 a 15 | 24 řádků, převážně rozcestník. Přiměřené |
| 19.07 Provoz | 462–501 | Service mesh, three pillars observability, discovery, deployment | žádné | Celá sekce bez jediného zdroje. Chybí Fowler *MicroservicePrerequisites* |
| 19.08 Symfony konkrétně | 503–779 | Messenger sync vs. AMQP, outbox transport, vlastní IntegrationEvent DTO, custom serializer, handler | symfony.com implicitně | 277 řádků = 30 % kapitoly. Obsahuje pravděpodobnou runtime chybu (viz G14) |
| 19.09 Migrace | 780–826 | Strangler Fig, tři fáze, zákaz big-bangu | Fowler 2004, Newman | Doporučuje dual-write bez varování; kap. 15 dual-write označuje za problém |
| 19.10 Anti-vzory | 828–872 | 5 anti-vzorů se symptomem, důsledkem a opravou | Newman kap. 4 | Věcně v pořádku, částečně duplikuje 19.04 |
| 19.11 Shrnutí | 874–889 | 7 doporučení | – | Konzistentní s tělem |
| 19.12 Další četba | 891–899 | 7 titulů | – | Chybí Lewis & Fowler 2014, Evans, Vogels 2023 |
| FAQ | 901–914 | 6 otázek: velikost service, 2 BC v jedné service, kdy migrovat, BFF, GraphQL Federation, vlastnictví Customer | – | FAQ nese témata, která patří do těla (BFF, velikost service, data ownership) |

Kapitola má jasnou a správnou tezi: Bounded Context je logická hranice, microservice fyzická, a
rovnice mezi nimi je hypotéza, ne příkaz. Tuto tezi drží konzistentně od úvodu po shrnutí. Prostor
ale rozděluje nerovnoměrně. Třicet procent délky připadá na dva YAML soubory a jeden serializer
v 19.08; distribuovaná konzistence dostane 24 řádků a integrace přes hranici služby 38. Provozní
sekce 19.07, na kterou se kapitola dvakrát odvolává jako na rozhodující argument, nemá jediný
zdroj a je psaná jako výčet nástrojů. Nejslabší místo je 19.04, kde se míchá dobře podložená
argumentace s tabulkou vymyšlených čísel a s převyprávěním případu Amazon Prime Video, které
neodpovídá tomu, co se skutečně stalo. Témata, která zadání označuje za centrální – data
ownership, database per service, API gateway a BFF, určování velikosti služby – kapitola buď
odbývá jednou větou, nebo je odsouvá do FAQ.

## 2. Kanonické zdroje k tématu

**Původ termínu.** Pojem „microservices" v dnešním významu ustavili James Lewis a Martin Fowler
článkem *Microservices* z 25. 3. 2014 [1]. Definují devět charakteristik (komponentizace přes
služby, organizace podle business capabilities, produkty místo projektů, chytré endpointy a hloupé
roury, decentralizovaná governance, decentralizovaná správa dat, automatizace infrastruktury,
design for failure, evoluční design). Kapitola tento článek necituje vůbec, přestože právě zde
vzniká formulace, proti které argumentuje: *„there is a natural correlation between service and
context boundaries that helps clarify"* [1]. Autoři přitom v závěru sami relativizují:
*„one reasonable argument we've heard is that you shouldn't start with a microservices
architecture. Instead begin with a monolith, keep it modular, and split it into microservices once
the monolith becomes a problem."* [1]

**Bounded Context.** Fowler v bliki *BoundedContext* (15. 1. 2014) [3] uvádí, že hranice kontextu
sleduje především lidskou kulturu a jazyk: *„Usually the dominant one is human culture, since
models act as Ubiquitous Language, you need a different model when the language changes."* Zde
také zavádí termín, který kapitola používá v FAQ – *„completely different models of common
concepts with mechanisms to map between these polysemic concepts"* [3].

**Evans k microservices.** Eric Evans měl na QCon London 2016 přednášku *DDD & Microservices: At
Last, Some Boundaries!* [22]. Jeho pozice je zajímavější, než kapitola naznačuje: neztotožňuje
microservice s Bounded Contextem, ale argumentuje opačným směrem, než kapitola – že logické
rozdělení v praxi selhává (*„logical partitioning doesn't work in practice"*) a microservices
dodávají hranicím fyzickou vynutitelnost, kterou modul uvnitř monolitu nemá. Vztah je tedy podle
Evanse **enablement, ne ekvivalence**. Kapitola Evanse k tomuto tématu necituje vůbec, přestože je
to autor samotného pojmu Bounded Context. Poznámka: obsah přednášky mám z metadat a show notes
stránky InfoQ, ne z přehrání záznamu – doporučuji před citací v knize záznam ověřit.

**Newman.** *Building Microservices, 2nd ed.* (O'Reilly, 2021) [13]. Části: I. Foundation
(kap. 1–4: základy, modelování, Splitting the Monolith, komunikační styly), II. Implementation
(kap. 5–13), III. People (kap. 14–16). Newmanova pozice k monolith-first je starší a explicitněji
formulovaná v blogu *Microservices For Greenfield?* (7. 4. 2015) [11]: *„Only split around those
boundaries that are very clear at the beginning, and keep the rest on the more monolithic side."*
a *„if you struggle to manage two services, managing 10 is going to be difficult."* Tamtéž:
*„I remain convinced that it is much easier to partition an existing, 'brownfield' system than to
do so up front with a new, greenfield system."*

**Richardson.** *Microservices Patterns* (Manning, 2018) a průběžně udržovaný pattern language na
microservices.io [15]. Pro tuto kapitolu jsou relevantní: Decompose by business capability /
Decompose by subdomain, Database per Service [16], Shared Database [17], Saga, Transactional
Outbox [19], API Gateway [18], Backends for Frontends, Strangler Application [20]. Podstatné je,
že Richardson *Shared Database* vede jako plnohodnotný pattern s výčtem výhod (*„A developer uses
familiar and straightforward ACID transactions to enforce data consistency"*, *„A single database
is simpler to operate"*) a nevýhod [17], zatímco stránka *Database per Service* zároveň odkazuje
na „Shared Database anti-pattern" [16]. Sám Richardson tedy sdílenou databázi nepodává jako
jednoznačný anti-vzor, ale jako volbu s cenou.

**Monolith-first a jeho protipól.** Fowler formuloval doporučení ve dvou textech:
*MicroservicePremium* (13. 5. 2015) [4] – *„don't even consider microservices unless you have a
system that's too complex to manage as a monolith"* a *„the majority of software systems should be
built as a single monolithic application"* – a *MonolithFirst* (3. 6. 2015) [5] – *„you shouldn't
start a new project with microservices, even if you're sure your application will be big enough to
make it worthwhile."* Fowler sám přiznává slabinu důkazní báze: *„I don't feel I have enough
anecdotes yet to get a firm handle on how to decide."* [5] Jádrem argumentu je cena refaktoru:
*„Any refactoring of functionality between services is much harder than it is in a monolith."* [5]

Protipól publikoval Fowler na vlastním webu o šest dní později: Stefan Tilkov, *Don't start with a
monolith* (9. 6. 2015) [6]. Tilkov tvrdí: *„I'm firmly convinced that starting with a monolith is
usually exactly the wrong thing to do"* a *„It's extremely hard to split up an existing monolith
into separate pieces"*, protože části monolitu si vytvoří závislosti přes sdílené knihovny,
databázi a doménové objekty. Kapitola tento spor vůbec neotevírá.

**Provozní předpoklady.** Fowler, *MicroservicePrerequisites* (28. 8. 2014) [2], uvádí tři:
rapid provisioning, basic monitoring, rapid application deployment, plus organizační posun
k DevOps. *„If you don't have these capabilities now, you should ensure you develop them so they
are ready by the time you put a microservice system into production."* To je přesně obsah sekce
19.07, jen se zdrojem.

**Kompromisy.** Fowler, *Microservice Trade-Offs* (1. 7. 2015) [7]. Přínosy: strong module
boundaries, independent deployment, technology diversity. Náklady: distribuce (*„Remote calls are
slow…these response times add up to some horrible latency characteristics"*), eventual consistency
(*„Business logic can end up making decisions on inconsistent information, when this happens it
can be extremely hard to diagnose what went wrong"*) a provozní složitost (*„Complexity isn't
eliminated, it's merely shifted around to the interconnections between services"*).

**Strangler Fig.** Fowler [9]. Původní bliki je z roku 2004 pod názvem *StranglerApplication*,
současná verze stránky nese datum 22. 8. 2024 a je přepsaná: definuje čtyři aktivity (vyjasnit
cílový stav, dekomponovat na nahraditelné části, dodávat inkrementálně, měnit organizační praxi) a
zdůrazňuje *transitional architecture* – dočasnou architekturu, která umožní koexistenci a kterou
je nutné později zahodit.

## 3. Stav praxe a posuny

**Modulární monolit není novinka roku 2023.** Kapitola v 19.04 tvrdí, že „trend microservices za
každou cenu z let 2014–2018 se po dekádě provozu obrátil". Data tomu neodpovídají. Protipozice
existuje po celou dobu trvání trendu a od uznávaných autorů: Fowler *MicroservicePremium* a
*MonolithFirst* (2015) [4][5], Newman *Microservices For Greenfield?* (2015) [11], DHH *The
Majestic Monolith* (29. 2. 2016) [26] – *„The problem with prematurely turning your application
into a range of services is chiefly that it violates the #1 rule of distributed computing: Don't
distribute your computing!"* a *„Run a small team, not a tech behemoth? Embrace the monolith and
make it majestic."* Kapitola si sama protiřečí: v 19.03 cituje Fowlera z roku 2015 a v 19.04 tvrdí,
že obrat nálady nastal po roce 2023.

**Co se skutečně změnilo, je terminologie a nástroje.** Termín „modular monolith" se ustálil
kolem let 2019–2020. Kamil Grzybek, *Modular Monolith: A Primer* (2. 12. 2019) [25], definuje tři
vlastnosti modulu: nezávislost a slabá vazba, business-centric dělení („vertical slices", ne
technické vrstvy) a definovaný kontrakt s enkapsulací. Shopify, *Deconstructing the Monolith*
(Kirsten Westeinde, 21. 2. 2019) [24], je nejcitovanější praktický případ: reorganizace ~6 000
tříd Rails monolitu podle doménových komponent a interní nástroj **Wedge**, který
*„tracks the progress of each component towards its goal of isolation"* analýzou call grafu a
označuje porušení hranic v CI. To je přesně role, kterou v kapitole hraje phparkitect – a Shopify
je pro tento argument podstatně silnější doklad než kterýkoli zdroj, který kapitola uvádí. Shopify
mikroslužby odmítl mimo jiné proto, že *„we'd have to maintain multiple different test & deployment
pipelines and take on infrastructural overhead for each service"* [24].

**Případ Amazon Prime Video (2023) – co se skutečně stalo.** Původní článek *Scaling up the Prime
Video audio/video monitoring service and reducing costs by 90%* vyšel v březnu 2023 na blogu Prime
Video Tech. Blog byl mezitím zrušen, URL dnes přesměrovává na aboutamazon.com, takže původní text
se nepodařilo načíst. Z diskuse na Hacker News nad tímto článkem [28] a z reakce Wernera Vogelse
[27] vyplývá následující: původní řešení nebyla architektura mikroslužeb v obvyklém smyslu, ale
**serverless pipeline orchestrovaná přes AWS Step Functions s Lambda funkcemi a S3 jako úložištěm
mezivýsledků**. Tým ji nahradil **jedním procesem běžícím jako ECS task**, kde se snímky předávají
v paměti. Úspora se týkala **jedné komponenty jedné služby**, ne přechodu produktu z mikroslužeb
na monolit.

Autoritativní rámování dodal Werner Vogels v článku *Monoliths are not dinosaurs* (5. 5. 2023)
[27]: *„There is no one-size-fits-all."* Vogels staví vedle sebe dva příklady z Prime Video – live
sports streaming jako distribuovaný workflow a monitoring jako monolit – a vyvozuje z toho
argument pro *evolvable architectures*: *„Evaluating your systems regularly is as important, if not
more so, than building them in the first place."* Nikoli tedy „microservices selhaly", ale
„architektura se má revidovat, když se změní zátěžový profil".

Kapitola tuto část podává nepřesně na třech místech (viz G6). Text Adriana Cockcrofta na Medium,
na který se kapitola odvolává, se nepodařilo načíst (HTTP 403) – jeho citaci *„prodávány jako
odpověď na všechno"* nelze v této studii potvrdit.

**Velikost služby.** Zhamak Dehghani, *How to break a Monolith into Microservices* (24. 4. 2018,
martinfowler.com) [10], formuluje pravidlo „macro first, then micro": *„Start with larger services
around a logical domain concept, and break the service down into multiple services when the teams
are operationally ready."* Zároveň varuje před nejčastější chybou migrace – vytvořit novou službu a
nezrušit původní cestu v monolitu. Tohle je konkrétnější odpověď na otázku velikosti než „velikost
je vedlejší", kterou kapitola nabízí v FAQ.

**Integrace: čtyři vzory místo dvou.** Fowler, *What do you mean by „Event-Driven"?* (7. 2. 2017)
[8], rozlišuje Event Notification (událost jen oznamuje změnu, příjemce si musí dotáhnout data),
Event-Carried State Transfer (událost nese data, příjemce si drží vlastní kopii), Event Sourcing a
CQRS. Rozdíl mezi prvními dvěma je centrální rozhodnutí při návrhu integračních událostí mezi
službami a určuje, zda subscriber potřebuje zpětné synchronní volání. Kapitola pracuje jen s osou
sync/async a tento rozdíl nepojmenuje, ačkoli její vlastní `OrderPlacedReceived` (řádky 646–656)
je učebnicový Event-Carried State Transfer.

## 4. Symfony / PHP specifika

**Messenger v Symfony 8.** Dokumentace [29] pokrývá Symfony 8.1. Transporty: AMQP, Doctrine
(včetně PostgreSQL LISTEN/NOTIFY), Redis Streams, Beanstalkd, Amazon SQS, in-memory, sync.
Routing lze konfigurovat v YAML/PHP nebo přes atribut `#[AsMessage('async')]` na třídě zprávy;
konfigurace má přednost před atributem. Zajímavé novinky pro tuto kapitolu: `--fetch-size`
(dávkové vyzvedávání, 8.1), `PriorityStamp` pro AMQP a Beanstalkd (8.1), `--keepalive` (brání
předčasnému redelivery u Beanstalkd, SQS, Doctrine, Redis), regex matching v `messenger:consume`,
`PostgreSqlNotifyOnIdleListener`. Kapitola žádnou z těchto možností nezmiňuje.

**Chybějící outbox relay je potvrzený fakt.** Symfony Messenger nemá vestavěný transactional
outbox ani relay mezi dvěma transporty [29]. Kapitola to na řádku 574 uvádí správně. Doctrine
transport ale umožňuje, aby `send()` proběhl ve stejné DB transakci jako doménový commit – to je
mechanismus, na kterém výklad stojí a který by měl být explicitně pojmenovaný.

**Provoz workerů je v PHP samostatná nákladová položka.** Symfony doporučuje [30] Supervisor nebo
systemd, `--limit`, `--memory-limit=128M`, `--time-limit=3600` a při každém deployi
`messenger:stop-workers`. Citace z dokumentace: *„Some services (like Doctrine's EntityManager)
will consume more memory over time. So, instead of allowing your worker to run forever, use a flag
like `messenger:consume --limit=10`."* Podstatné pro tuto kapitolu: každá extrahovaná služba
přidává vlastní sadu worker procesů, vlastní supervisor konfiguraci a vlastní krok v deploy
skriptu. To je konkrétní podoba „operační daně", kterou kapitola popisuje obecně.

**Ekonomika PHP je jiná než ekonomika, kterou předpokládá literatura o microservices.** Toto téma
kapitola neotevírá vůbec a je to největší obsahová mezera vzhledem k zaměření knihy. Fakta:

- PHP v klasickém režimu (php-fpm, mod_php) je *shared-nothing* – aplikace se bootstrapuje při
  každém requestu. Neexistuje in-process cache ani connection pool sdílený mezi requesty. Každá
  extrahovaná služba tedy platí bootstrap znovu, na svém vlastním requestu.
- Synchronní volání mezi službami blokuje php-fpm child po celou dobu round-tripu. Ve výchozím
  nastavení je počet childů pevný, takže fan-out N synchronních volání spotřebovává konkurenční
  kapacitu volajícího lineárně. V runtime s neblokujícím I/O (Node, Go) je stejné čekání téměř
  zdarma. Anti-vzor „synchronní orchestrace všeho přes REST" (19.10, bod 3) je tedy v PHP dražší
  než v prostředí, ze kterého pochází zdrojová literatura – a kapitola to neříká.
- Mitigace na straně Symfony existuje: `HttpClient` umí souběžné requesty a streamované odpovědi,
  takže fan-out lze provést paralelně místo sériově. Kapitola `HttpClient` nezmiňuje.
- Worker módy tuto ekonomiku mění. FrankenPHP worker mode [31] drží aplikaci v paměti mezi
  requesty (*„keep your PHP app in memory"*), Symfony resetuje většinu stavu sám a služby s
  request-scoped stavem mají implementovat `Symfony\Contracts\Service\ResetInterface`. Totéž
  principiálně řeší RoadRunner a Swoole. S worker módem se PHP chová blíž dlouhoběžícím runtime a
  cena synchronního volání klesá – ale přibývá riziko úniku stavu mezi requesty.
- Opačným směrem: nasazovací jednotka je v PHP levná. Není co kompilovat, není warm-up JVM.
  Technicky extrahovat službu je v PHP jednodušší než v Javě. Náklad je celý na straně provozu,
  což *posiluje* hlavní tezi kapitoly, jen jinými argumenty, než jaké kapitola používá.

**phparkitect.** Nástroj [32] existuje a API v kapitole odpovídá skutečnosti: `ClassSet::fromDir()`,
`Rule::allClasses()->that(...)->should(...)->because(...)`, `Config::add()`. Dostupné výrazy
zahrnují `ResideInOneOfTheseNamespaces`, `NotDependsOnTheseNamespaces`,
`DependsOnlyOnTheseNamespaces`, `NotHaveDependencyOutsideNamespace`, dále pravidla pro pojmenování
a dědičnost. Za zmínku stojí `DependsOnlyOnTheseNamespaces` – pro modulární monolit je to
pozitivní varianta pravidla (whitelist místo blacklistu), která se s přibývajícími kontexty
neškáluje do nesmyslu jako výčet zakázaných namespaců v ukázce na řádcích 188–192. Konkrétní verzi
balíčku a minimální verzi PHP se ze stránky projektu vyčíst nepodařilo.

## 5. Sporné a chybně podávané body

**a) Monolith-first není konsensus.** Kapitola podává MonolithFirst jako ustálené doporučení
(řádek 98). Fowler sám přiznává slabou důkazní bázi [5] a na vlastním webu publikoval protipozici
Stefana Tilkova [6], která tvrdí přesný opak. Doporučení pro knihu: uvést oba postoje a rozhodnout
spor argumentem, který je pro cílového čtenáře relevantní – Tilkovův argument („monolit se pak
nedá rozdělit") platí tehdy, když se hranice uvnitř monolitu nevynucují; s phparkitect v CI je
Tilkovova námitka z velké části adresovaná. To je poctivější než protistranu zamlčet.

**b) Rovnice BC = microservice: Evansova pozice je jiná, než kapitola předpokládá.** Kapitola
argumentuje deflačně (slogan je polopravda, mapování je i 1:N a N:1). Evans [22] argumentuje
z druhé strany: logické hranice v praxi neobstojí a microservice je dává vynutit. Obě tvrzení
mohou platit současně, ale kapitola bez Evansovy verze vypadá, jako by DDD komunita microservices
odmítala. To není přesné. Doporučení: doplnit Evansovu pozici a rozdíl explicitně pojmenovat.

**c) Sdílená databáze jako absolutní anti-vzor.** Kapitola ji uvádí jako „nejjasnější příznak"
distributed monolithu (řádek 274) a jako anti-vzor č. 2 (řádek 840). Richardson [17] ji vede jako
pattern s výhodami i nevýhodami. Rozdíl je v tom, *co* je sdíleno: sdílené schéma s zápisem z více
služeb je skutečně anti-vzor; jedna databázová instance s oddělenými schématy a jasným vlastníkem
je běžný a legitimní mezikrok, který Newman doporučuje pro greenfield [11]. Kapitola tento rozdíl
nedělá, přestože ho na řádku 67 v závorce sama naznačuje. Doporučení: rozlišit „shared database
instance" a „shared schema".

**d) Vlastní číselné heuristiky vydávané za odbornou znalost.** Prahy „30 lidí", „4+ ze 6 bodů",
„30 % kapacity do platformy", „> 50 % na platformu", „MTTR > 60 minut", „hranice se 6 měsíců
neměnila" nejsou z žádného zdroje. Jako heuristiky knihy jsou v pořádku a čtenáři pomáhají, ale
sousedí s větami typu „Newman opakovaně připomíná" (řádek 108), což vytváří dojem atribuce.
Doporučení: heuristiky ponechat a explicitně je označit jako heuristiky této knihy.

**e) Nákladová tabulka (řádky 385–397).** Ani jedno z čísel nemá zdroj. Uvozující věta odkazuje na
Newmanovu sekci „Microservice Pain Points" jako na zdroj *kategorií*, ale čtenář to snadno přečte
jako zdroj čísel. Kalkulace v USD s platem 80 000 USD/rok je pro českého čtenáře málo použitelná a
časově nestabilní. Doporučení: ponechat kategorie, čísla buď smazat, nebo označit jako ilustrativní
scénář podle konvence knihy pro fiktivní případy.

**f) Prime Video.** Popis „vrátil video monitoring stack z microservices do monolitu" a atribuce
„oficiální článek na Amazon engineering blog" neodpovídají doložitelným skutečnostem (viz sekce 3).
Původní architektura byla serverless orchestrace (Step Functions, Lambda, S3), blog byl Prime Video
Tech, ne obecný Amazon engineering blog, a rozsah změny byla jedna komponenta. Kapitola sice
Cockcroftovu námitku zmiňuje, ale až po tom, co disputovanou verzi uvedla jako fakt.

**g) Dual-write v migračním postupu.** Řádek 810 doporučuje „období dual-write – monolit i nová
service obě píší". Kapitola 15 (`outbox_pattern.md:39`) má sekci „15.01 Dual-write problem", kde je
dual-write označen za problém, který outbox řeší. Bez vysvětlení, proč je při migraci přijatelný
(je – jde o řízený a časově omezený stav s reconciliací), si dvě kapitoly knihy protiřečí.

**h) SharedKernel jako „technické typy".** Řádky 153–161 zavádějí namespace `SharedKernel`
s komentářem „Sdílené technické typy (NE doménové)" a dávají do něj `Money` a `Currency`.
Kapitola 3 (`context_mapping.md:117–128`) definuje Shared Kernel podle Evanse jako *podmnožinu
doménového modelu* a jako typické příklady uvádí `Money`, `Currency`, `EmailAddress`, `UserId`.
Obě kapitoly tedy o stejném namespace tvrdí opak. Podle CLAUDE.md jsou navíc `Money` a `Currency`
kanonické doménové value objecty knihy.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | chybí | celá kapitola | Lewis & Fowler *Microservices* (2014) není citován. Je to zdroj definice i formulace „natural correlation between service and context boundaries", proti které kapitola argumentuje. | Doplnit do 19.01 a do 19.12 |
| G2 | chybí | `microservices_and_ddd.md:26–57` | Eric Evans k tématu není citován. Jeho pozice (microservice dodává BC vynutitelnou hranici) mění vyznění sekce. | Doplnit odstavec do 19.01, zdroj [22] |
| G3 | sporné | `microservices_and_ddd.md:98` | MonolithFirst podán jako konsensus. Tilkovova protipozice na Fowlerově vlastním webu chybí. | Doplnit protiargument a rozhodnutí knihy, ~12 řádků |
| G4 | chybí | celá kapitola | Chybí celé téma **data ownership / database per service** jako pozitivní vzor. Kapitola má jen anti-vzor sdílené DB. Chybí důsledky: cross-service dotazy, API composition, CQRS read modely, reporting, duplikace dat. | Nová sekce ~55 řádků |
| G5 | nepodložené | `microservices_and_ddd.md:385–397` | Celá nákladová tabulka a kalkulace 340k USD bez zdroje, prezentované jako „rozhodovací číslo". | Kategorie ponechat, čísla označit jako ilustrativní nebo smazat |
| G6 | sporné | `microservices_and_ddd.md:333–341` | Prime Video: špatná atribuce blogu, popis původní architektury jako microservices, „obrat trendu po dekádě" v rozporu s vlastní citací Fowlera 2015. | Přepsat podle sekce 3 této studie, doplnit Vogels 2023 [27] |
| G7 | nepodložené | `microservices_and_ddd.md:334–336` | Citace Cockcrofta („prodávány jako odpověď na všechno") – primární zdroj se nepodařilo ověřit (HTTP 403). | Ověřit ručně, nebo citaci vypustit a nahradit Vogelsem [27] |
| G8 | chybí | `microservices_and_ddd.md:462–501` | Sekce 19.07 nemá jediný zdroj, přestože Fowler *MicroservicePrerequisites* [2] pokrývá přesně její tezi. | Doplnit citaci a Fowlerovy tři předpoklady, ~8 řádků |
| G9 | mělké | `microservices_and_ddd.md:399–436` | Sync/async má 38 řádků. Chybí Fowlerovo rozlišení Event Notification vs. Event-Carried State Transfer [8], které určuje návrh integračních událostí. | Rozšířit 19.05 o ~25 řádků |
| G10 | mělké | `microservices_and_ddd.md:908–911` | API gateway a BFF žijí jen ve FAQ. Zadání i literatura [12][18] je řadí mezi základní integrační vzory. | Přesunout do těla jako podsekci 19.05, ~30 řádků |
| G11 | mělké | `microservices_and_ddd.md:902` + `864–870` | Velikost služby je v FAQ a v anti-vzoru. Chybí pozitivní heuristika. Dehghani „macro first, then micro" [10]. | Podsekce v 19.02, ~15 řádků |
| G12 | chybí | celá kapitola | **PHP/Symfony ekonomika microservices**: shared-nothing model, blokující php-fpm child při sync volání, `HttpClient` concurrency, worker módy (FrankenPHP/RoadRunner), cena N sad Messenger workerů. | Nová sekce v 19.08 nebo samostatná, ~50 řádků |
| G13 | chybí | `microservices_and_ddd.md:503–779` | Nezmíněny relevantní novinky Messenger v Symfony 8.1: `#[AsMessage]`, `--keepalive`, `--fetch-size`, `PriorityStamp`, `failure_transport`. | Doplnit do 19.08, ~15 řádků |
| G14 | sporné | `microservices_and_ddd.md:602–604` + `720–724` | `events_in` má `retry_strategy: max_retries: 5`, ale serializer je decode-only a `encode()` hází `\LogicException`. Retry v Messenger znovu odesílá envelope přes sender téhož transportu, což `encode()` volá. Kombinace pravděpodobně selže při první chybě. | Ověřit runtime; buď implementovat `encode()`, nebo retry přesunout na jiný mechanismus |
| G15 | sporné | `microservices_and_ddd.md:753–769` vs. `777` | Text tvrdí, že `wasProcessed` a `markProcessed` musí běžet v jedné transakci se zpracováním. Ukázaný kód to nedělá – dispatch jde přes command bus s vlastní transakcí, `markProcessed` je mimo. | Sjednotit kód s textem, nebo text opravit |
| G16 | sporné | `microservices_and_ddd.md:153–161` | `SharedKernel` popsán jako „technické typy (NE doménové)" s `Money`/`Currency` uvnitř – v rozporu s kap. 3 a s CLAUDE.md. | Přejmenovat namespace, nebo formulaci uvést do souladu s kap. 3 |
| G17 | sporné | `microservices_and_ddd.md:810` | Dual-write doporučen bez odkazu na to, že kap. 15 ho označuje za problém. | Doplnit jednu větu s odkazem na `/outbox-pattern#dual-write` a s podmínkami, za kterých je přijatelný |
| G18 | chybí | `microservices_and_ddd.md:517` | `IntegrationEventSerializer` a vlastní DTO subscribera **je** Anti-Corruption Layer, ale kapitola vzor nepojmenuje. Stejně tak nemapuje typy vztahů z Context Map na integrační mechanismy. | Doplnit mapování ACL / OHS+PL / Conformist / Separate Ways → mechanismus, ~20 řádků |
| G19 | mělké | `microservices_and_ddd.md:435`, `277` | Testování napříč službami: contract testing (Pact, consumer-driven contracts) jen jako buňka v tabulce a jako symptom anti-vzoru. | Podsekce v 19.07, ~15 řádků |
| G20 | mělké | `microservices_and_ddd.md:776` | Verzování integračních událostí odbyto jednou větou. Chybí tolerant reader, expand-contract, postup pro breaking change. | Rozšířit v 19.08, ~15 řádků |
| G21 | nepodložené | `microservices_and_ddd.md:265` | „Sam Newman ho označuje za jednu z nejhorších architektur" – bez dohledatelné lokace. | Doplnit lokaci, nebo přeformulovat bez atribuce |
| G22 | sporné | `microservices_and_ddd.md:53` | Tabulka uvádí jako zdroj definice microservice „Newman 2021, Richardson 2018". Definice pochází z Lewis & Fowler 2014, Newman 1. vyd. 2015. | Opravit atribuci |
| G23 | **chyba, ověřeno** | `microservices_and_ddd.md:870`, `893` | Ověřeno proti autorovu rozpisu obsahu [13]. Kap. 1 *What Are Microservices?*, 2 *How to Model Microservices*, 3 *Splitting the Monolith*, 4 *Microservice Communication Styles*, 5 *Implementing Microservice Communication*, 6 *Workflow*, 7 *Build*, 14 *User Interfaces*. Dva odkazy nesedí: **ř. 870** připisuje kap. 4 doporučení, aby velikost service vznikala z domény – kap. 4 je ale o komunikačních stylech, tohle téma patří do kap. 2, jejíž anotace zní „the importance of information hiding, coupling, cohesion, and domain-driven design in helping find the right boundaries“. **Ř. 893** uvádí „kapitola 14 pro migraci“ – kap. 14 jsou *User Interfaces*, migrace je kap. 3. Odkazy na ř. 32 (kap. 2), 60 a 98 (kap. 3) naopak sedí. | Ř. 870: kap. 4 → **kap. 2**. Ř. 893: „kapitola 14 pro migraci“ → **„kapitola 3 pro migraci“**, a rozsah „kapitoly 5–7 pro integraci“ → **4–6** (kap. 7 je *Build*) |
| G24 | nadbytečné | `microservices_and_ddd.md:828–872` | Anti-vzory 1–4 z 19.10 z velké části opakují 19.04 (5 příznaků) a kapitolu 21. | Zkrátit na křížové odkazy, ponechat jen nano-services jako nový obsah |
| G25 | sporné | `microservices_and_ddd.md:913` | FAQ tvrdí „Tomu se říká polysemic concept v Context Mappingu" a odkazuje na `/context-mapping`, kde se termín nevyskytuje. | Doplnit termín do kap. 3, nebo citovat Fowlera [3] přímo zde |
| G26 | zastaralé | `microservices_and_ddd.md:786`, `897` | Strangler Fig datován 2004; současná stránka je přepis z 2024 s jiným obsahem (transitional architecture, čtyři aktivity). | Uvést oba údaje, nebo citovat současnou verzi |

## 7. Doporučení k přepisu

**P1-1 — Přepsat pasáž o Prime Video a de-microservicingu podle doložitelných faktů.**
Kapitola dnes tvrdí, že tým „vrátil video monitoring stack z microservices do monolitu" na základě
„oficiálního článku na Amazon engineering blogu". Ověřitelné je: šlo o serverless pipeline nad Step
Functions a Lambda s S3 pro mezivýsledky, nahrazenou jedním ECS taskem, v rozsahu jedné komponenty
jedné služby, publikovanou na blogu Prime Video Tech (dnes offline). Autoritativní rámec dodává
Werner Vogels *Monoliths are not dinosaurs* [27]. Zároveň je nutné opravit tvrzení „trend se po
dekádě obrátil" – protipozice existuje od roku 2014. *Odhad: přepis ~20 řádků v sekci 19.04.*

**P1-2 — Doplnit sekci o data ownership a database per service.**
Je to jedno z centrálních témat vztahu DDD a microservices a kapitola ho pokrývá jen negativně
(anti-vzor sdílené DB). Chybí pozitivní formulace vzoru [16], rozdíl mezi sdílenou instancí a
sdíleným schématem, a hlavně důsledky: jak se dělá dotaz přes hranici (API composition, CQRS read
model), jak se řeší reporting a analytika, kdo vlastní duplikovaná data a podle jakých pravidel.
Bez toho čtenář ví, co nedělat, ale ne co dělat. *Odhad: nová sekce ~55 řádků.*

**P1-3 — Doplnit PHP/Symfony ekonomiku microservices.**
Kniha je o DDD v Symfony, ale tato kapitola je napsaná tak, že by mohla stát v jakékoli knize o
jakémkoli jazyce. Shared-nothing model PHP mění kalkulaci: synchronní volání blokuje php-fpm
child, fan-out spotřebovává konkurenční kapacitu lineárně, každá služba platí bootstrap na každém
requestu. Zároveň je nasazovací jednotka v PHP levná a worker módy (FrankenPHP [31], RoadRunner)
tuto ekonomiku posouvají. Také: N služeb = N sad Messenger workerů se supervisorem, memory limity
a `messenger:stop-workers` v každém deploy skriptu [30]. *Odhad: nová sekce ~50 řádků, nebo
podsekce v 19.08.*

**P1-4 — Doplnit Evanse a Lewise & Fowlera do 19.01.**
Sekce argumentuje proti sloganu, jehož zdroj necituje, a k tématu necituje autora pojmu Bounded
Context. Evansova pozice [22] navíc vyznění sekce mění: microservices dodávají hranicím fyzickou
vynutitelnost, kterou logické rozdělení nemá. To není v rozporu s tezí kapitoly, ale doplňuje ji o
stranu, která dnes chybí. *Odhad: ~20 řádků v 19.01 plus dva záznamy v 19.12.*

**P1-5 — Vyřešit nákladovou tabulku v 19.04.**
Šest řádků čísel bez zdroje, uvozených odkazem na Newmana, plus kalkulace v USD. Buď čísla smazat
a ponechat kategorie jako checklist, nebo je celé označit jako ilustrativní scénář podle konvence
knihy pro fiktivní případy. Ponechat je v současné podobě znamená vydávat odhad za zjištění.
*Odhad: přepis ~15 řádků.*

**P1-6 — Opravit rozpor SharedKernel mezi kap. 19 a kap. 3.**
Kapitola 3 definuje Shared Kernel podle Evanse jako sdílenou podmnožinu doménového modelu a jako
příklady uvádí přesně `Money` a `Currency`. Kapitola 19 dává tytéž třídy do `SharedKernel`
s komentářem, že jde o „technické typy (NE doménové)". Jedno z toho musí ustoupit; jednodušší je
opravit formulaci v kap. 19. *Odhad: oprava dvou vět a jednoho komentáře v ukázce.*

**P1-7 — Ověřit a opravit `IntegrationEventSerializer` a idempotenci.**
Dvě věci: (a) decode-only serializer s `encode()` házejícím `\LogicException` v kombinaci
s `retry_strategy` na témže transportu s vysokou pravděpodobností selže při první chybě, protože
retry envelope znovu odesílá; (b) text na řádku 777 požaduje `wasProcessed`/`markProcessed`
v jedné transakci se zpracováním, ukázaný kód to nedělá. Obojí patří ověřit spuštěním, ne úvahou.
*Odhad: úprava dvou ukázek, ~20 řádků.*

**P2-1 — Otevřít spor monolith-first vs. Tilkov.**
Kapitola podává doporučení jako uzavřené. Fowler sám má pochyby [5] a protipozici publikoval na
vlastním webu [6]. Uvedení sporu kapitolu zesílí – hlavně proto, že Tilkovova námitka („monolit se
pak nedá rozdělit") je adresovatelná právě tím phparkitect pravidlem, které kapitola zavádí.
*Odhad: ~12 řádků v 19.03.*

**P2-2 — Rozšířit 19.05 o event notification vs. event-carried state transfer.**
Osa sync/async je jen polovina rozhodnutí. Druhá polovina – kolik dat nese událost – určuje, zda
subscriber potřebuje zpětné volání, a je zdrojem většiny reálného couplingu. Fowler [8]. Kapitolina
vlastní ukázka `OrderPlacedReceived` je ECST a nikde to není řečeno. *Odhad: ~25 řádků.*

**P2-3 — Přesunout API gateway a BFF z FAQ do těla.**
Externí API je samostatná skupina vzorů [18][12] a v kapitole o hranicích služeb patří do textu,
ne do doplňkových otázek. Zároveň patří říct, že BFF je z pohledu Context Mappingu Open Host
Service, což FAQ říká správně, ale na místě, kde to čtenář nenajde. *Odhad: ~30 řádků v 19.05.*

**P2-4 — Namapovat vztahy z Context Map na integrační mechanismy.**
Kapitola opakovaně odkazuje na Context Mapping, ale nikdy neřekne, který vztah odpovídá kterému
mechanismu: ACL → vlastní integration event DTO a serializer (což kapitola dělá, aniž by to
pojmenovala), OHS + Published Language → veřejné API a schéma událostí, Conformist → přebírání
schématu publishera, Separate Ways → žádná integrace. To je nejlevnější způsob, jak kapitolu
propojit se zbytkem knihy. *Odhad: ~20 řádků, ideálně tabulka v 19.05 nebo 19.08.*

**P2-5 — Doplnit zdroje do sekce 19.07 a téma contract testing.**
Fowlerovy tři prerekvizity [2] podepřou tezi sekce jedním odstavcem. Contract testing (Pact,
consumer-driven contracts) je kanonická odpověď na „end-to-end test vyžaduje všechny services",
což kapitola uvádí jako příznak anti-vzoru, aniž by nabídla řešení. *Odhad: ~20 řádků.*

**P2-6 — Doplnit velikost služby jako pozitivní heuristiku.**
Dehghani „macro first, then micro" [10] a Newmanovo information hiding [13] dávají odpověď
konkrétnější než „velikost je vedlejší". Patří do 19.02, ne do FAQ. *Odhad: ~15 řádků.*

**P3-1 — Zkrátit 19.10.** Anti-vzory 1–4 opakují 19.04 a kapitolu 21. Ponechat nano-services a
zbytek zkrátit na odkazy. *Odhad: úspora ~25 řádků.*

**P3-2 — Doplnit novinky Symfony 8.1 Messenger** (`#[AsMessage]`, `--keepalive`, `--fetch-size`,
`PriorityStamp`, `failure_transport`) do 19.08. *Odhad: ~15 řádků.*

**P3-3 — Zvážit `DependsOnlyOnTheseNamespaces` v phparkitect ukázce.** Whitelist se škáluje lépe
než výčet zakázaných namespaců, který je nutné rozšiřovat s každým novým kontextem. *Odhad: úprava
jednoho pravidla.*

**P3-4 — Doplnit termín „polysemic concept" do kapitoly 3**, na kterou FAQ odkazuje, nebo v FAQ
citovat Fowlera [3] přímo. *Odhad: jedna věta.*

## 8. Otevřené otázky pro autora

1. **Rozsah 19.08.** Sekce má 277 řádků, 30 % kapitoly, a je to převážně konfigurace a serializer.
   Část výkladu (outbox, inbox, idempotence) duplikuje kapitolu 15. Má se 19.08 zkrátit ve prospěch
   témat z P1, nebo je detailní Symfony ukázka to, kvůli čemu čtenář kapitolu otevírá?

2. **Kam s PHP ekonomikou.** Patří téma shared-nothing / worker módů do této kapitoly, nebo do
   kapitoly o výkonnostních aspektech? Argument pro tuto kapitolu: mění rozhodnutí o hranicích.
   Argument proti: kapitola už je nejdelší v kategorii Praxe.

3. **Nákladová čísla.** Má kniha uvádět vlastní odhady provozních nákladů, i když jsou označené
   jako ilustrativní? Riziko: rychle zestárnou a čtenáři je budou citovat jako fakt.

4. **Jak daleko jít s Prime Video.** Případ se dá zmínit jednou větou jako varování před
   převyprávěnými historkami, nebo rozebrat na 20 řádků jako ukázku toho, jak se architektonické
   příběhy deformují. Druhá varianta je poučnější a delší.

5. **De-microservicing.** Podsekce (19.04, řádky 331–377) je vlastní konstrukce knihy bez opory ve
   zdrojích. Držet ji jako názor knihy, nebo ji zkrátit na odstavec?

6. **Hloubka contract testingu.** Pact je v PHP ekosystému slabě zastoupený. Má smysl vzor
   doporučovat, nebo jen pojmenovat problém a odkázat na kapitolu o testování?

7. **Cílový čtenář.** Kapitola střídá registr „tým s 30 inženýry, Kubernetes, service mesh"
   a „Symfony vývojář, který zvažuje druhý deployment". Většina čtenářů knihy bude spíš ve druhé
   situaci. Má se kapitola posunout k menším instalacím?

## 9. Bibliografie

Způsob získání je uveden u každého záznamu. `WebSearch` nebyl použit ani jednou – rozpočet session
byl vyčerpán (200/200) při prvním pokusu.

### Ověřené zdroje

`[1]` James Lewis, Martin Fowler — *Microservices*, 25. 3. 2014. https://martinfowler.com/articles/microservices.html (přímý fetch, 2026-09-03)
`[2]` Martin Fowler — *MicroservicePrerequisites*, 28. 8. 2014. https://martinfowler.com/bliki/MicroservicePrerequisites.html (přímý fetch, 2026-09-03)
`[3]` Martin Fowler — *BoundedContext*, 15. 1. 2014. https://martinfowler.com/bliki/BoundedContext.html (přímý fetch, 2026-09-03)
`[4]` Martin Fowler — *MicroservicePremium*, 13. 5. 2015. https://martinfowler.com/bliki/MicroservicePremium.html (přímý fetch, 2026-09-03)
`[5]` Martin Fowler — *MonolithFirst*, 3. 6. 2015. https://martinfowler.com/bliki/MonolithFirst.html (přímý fetch, 2026-09-03)
`[6]` Stefan Tilkov — *Don't start with a monolith*, 9. 6. 2015. https://martinfowler.com/articles/dont-start-monolith.html (přímý fetch, 2026-09-03)
`[7]` Martin Fowler — *Microservice Trade-Offs*, 1. 7. 2015. https://martinfowler.com/articles/microservice-trade-offs.html (přímý fetch, 2026-09-03)
`[8]` Martin Fowler — *What do you mean by „Event-Driven"?*, 7. 2. 2017. https://martinfowler.com/articles/201701-event-driven.html (přímý fetch, 2026-09-03)
`[9]` Martin Fowler — *Strangler Fig Application* (původně *StranglerApplication*, 2004; současná verze stránky 22. 8. 2024). https://martinfowler.com/bliki/StranglerFigApplication.html (přímý fetch, 2026-09-03)
`[10]` Zhamak Dehghani — *How to break a Monolith into Microservices*, 24. 4. 2018. https://martinfowler.com/articles/break-monolith-into-microservices.html (přímý fetch, 2026-09-03)
`[11]` Sam Newman — *Microservices For Greenfield?*, 7. 4. 2015. https://samnewman.io/blog/2015/04/07/microservices-for-greenfield/ (přímý fetch, 2026-09-03)
`[12]` Sam Newman — *Backends For Frontends*, 18. 11. 2015. https://samnewman.io/patterns/architectural/bff/ (přímý fetch, 2026-09-03)
`[13]` Sam Newman — *Building Microservices, 2nd ed.*, O'Reilly, 2021 (obsah a struktura podle stránky knihy). https://samnewman.io/books/building_microservices_2nd_edition/ (přímý fetch, 2026-09-03)
`[14]` Sam Newman — *Monolith to Microservices*, O'Reilly. https://samnewman.io/books/monolith-to-microservices/ (přímý fetch, 2026-09-03)
`[15]` Chris Richardson — *Microservice Architecture pattern language*. https://microservices.io/patterns/ (přímý fetch, 2026-09-03)
`[16]` Chris Richardson — *Pattern: Database per service*. https://microservices.io/patterns/data/database-per-service.html (přímý fetch, 2026-09-03)
`[17]` Chris Richardson — *Pattern: Shared database*. https://microservices.io/patterns/data/shared-database.html (přímý fetch, 2026-09-03)
`[18]` Chris Richardson — *Pattern: API Gateway / Backends for Frontends*. https://microservices.io/patterns/apigateway.html (přímý fetch, 2026-09-03)
`[19]` Chris Richardson — *Pattern: Transactional outbox*. https://microservices.io/patterns/data/transactional-outbox.html (přímý fetch, 2026-09-03)
`[20]` Chris Richardson — *Pattern: Strangler application*. https://microservices.io/patterns/refactoring/strangler-application.html (přímý fetch, 2026-09-03)
`[21]` Chris Richardson — *Pattern: Decompose by subdomain*. https://microservices.io/patterns/decomposition/decompose-by-subdomain.html (přímý fetch, 2026-09-03)
`[22]` Eric Evans — *DDD & Microservices: At Last, Some Boundaries!*, QCon London 2016. https://www.infoq.com/presentations/ddd-microservices-2016/ (přímý fetch metadat a show notes, 2026-09-03; záznam nebyl přehrán)
`[23]` Indu Alagarsamy — *Practical DDD: Bounded Contexts + Events => Microservices*, QCon New York 2019. https://www.infoq.com/presentations/microservices-ddd-bounded-contexts/ (přímý fetch, 2026-09-03)
`[24]` Kirsten Westeinde (Shopify) — *Deconstructing the Monolith: Designing Software that Maximizes Developer Productivity*, 21. 2. 2019. https://shopify.engineering/deconstructing-monolith-designing-software-maximizes-developer-productivity (přímý fetch, 2026-09-03)
`[25]` Kamil Grzybek — *Modular Monolith: A Primer*, 2. 12. 2019. https://www.kamilgrzybek.com/blog/posts/modular-monolith-primer (přímý fetch, 2026-09-03)
`[26]` David Heinemeier Hansson — *The Majestic Monolith*, 29. 2. 2016. https://signalvnoise.com/the-majestic-monolith/ (přímý fetch, 2026-09-03)
`[27]` Werner Vogels — *Monoliths are not dinosaurs*, 5. 5. 2023. https://www.allthingsdistributed.com/2023/05/monoliths-are-not-dinosaurs.html (přímý fetch, 2026-09-03)
`[28]` Hacker News — diskuse k článku *Scaling up the Prime Video audio/video monitoring service and reducing costs by 90%*, květen 2023. https://news.ycombinator.com/item?id=35811741 (přímý fetch, 2026-09-03; sekundární zdroj, použit jen k rekonstrukci původní architektury)
`[29]` Symfony — *Messenger: Sync & Queued Message Handling* (dokumentace Symfony 8.1). https://symfony.com/doc/current/messenger.html (přímý fetch, 2026-09-03)
`[30]` Symfony — *Messenger: Deploying to Production*. https://symfony.com/doc/current/messenger.html#deploying-to-production (přímý fetch, 2026-09-03)
`[31]` FrankenPHP — *Worker mode*. https://frankenphp.dev/docs/worker/ (přímý fetch, 2026-09-03)
`[32]` PHPArkitect. https://github.com/phparkitect/arkitect (přímý fetch, 2026-09-03)
`[33]` Martin Fowler — *PresentationDomainDataLayering*, 26. 8. 2015. https://martinfowler.com/bliki/PresentationDomainDataLayering.html (přímý fetch, 2026-09-03)

### Neověřené / nedohledané

- **Prime Video – DOHLEDÁNO 2026-09-04, oba texty existují a jsou citovatelné.**
  Původní článek *Scaling up the Prime Video audio/video monitoring service and reducing costs
  by 90%* vyšel na **primevideotech.com v květnu 2023**. Věcné jádro: tým Video Quality Analysis
  narazil na náklady orchestrace přes AWS Step Functions a na Tier-1 volání do S3, které sloužilo
  jako mezisklad videosnímků. Sloučením komponent do jednoho procesu odpadl mezisklad (data tečou
  v paměti) a infrastrukturní náklady klesly o více než 90 %.

  Protiváha je rovněž dohledatelná: **Adrian Cockcroft – *So many bad takes – What is there to
  learn from the Prime Video microservices to monolith story*** na `adrianco.medium.com`.

  **Doporučení pro kapitolu:** citovat oba a hlídat rozsah tvrzení. Nejde o opuštění microservices
  napříč Prime Videem, ale o **jednu komponentu jednoho týmu**, kde se distribuovaný návrh
  nevyplatil. Podávat to jako obecný argument proti microservices je přesně ta chyba, kterou
  Cockcroft rozebírá.

  **Pozor na URL a dataci.** Původní adresa na primevideotech.com vrací HTTP 301 na
  aboutamazon.com, blog byl zrušen; citovat je tedy nutné přes archivovanou kopii nebo přes
  reprinty. Datum je **květen 2023** (první průchod uváděl březen).
- **Adrian Cockcroft – *So many bad takes…* – ČÁSTEČNĚ, 2026-09-04.** Existence, autorství,
  přesný název i URL (`adrianco.medium.com`) jsou potvrzené. Doslovné znění ne: Medium vrací
  HTTP 403 i na přímý `curl` s běžnou hlavičkou User-Agent a odpovídá stránkou „Enable JavaScript
  and cookies to continue“. Citace na řádcích 334–336 kapitoly zůstává neověřená.
  **Doporučení: parafrázovat Cockcroftovu tezi místo přímé citace, dokud text nepřečte člověk
  v prohlížeči.**
- **Simon Brown – *Distributed big balls of mud* – DATOVÁNO 2026-09-04: 6. 7. 2014.** Původní
  URL na codingthearchitecture.com je mrtvá, text ale existuje v několika reprintech (DZone,
  paradox1x.org) a je doložitelný i diskusí na Hacker News z téhož období. Podtitul zní
  „If you can't build a monolith, what makes you think microservices are the answer?“ a hlavní teze
  je, že návrhové myšlení potřebné pro dobré microservices je totéž jako pro dobře strukturovaný
  monolit. **Doporučení: citovat s rokem 2014 a odkazovat na reprint, ne na mrtvou doménu.**
  Původní URL:
  přesměrovává na simonbrown.je, kde článek není. Známý citát („If you can't build a
  well-structured monolith…") by byl silnou oporou pro sekci 19.04 – dohledat aktuální umístění.
- **Eric Evans, vystoupení na Explore DDD / DDD Europe 2017–2019 k rovnici BC = microservice.**
  Nedohledáno. Stránka řečníka DDD Europe 2019 abstrakty neuvádí. Ověřená je pouze přednáška
  z QCon London 2016 [22], a to jen z metadat, ne ze záznamu.
- **Vaughn Vernon k microservices** (*DDD Distilled*, 2016; *IDDD*, 2013). Nedohledáno online.
  Kapitola Vernona cituje na řádku 42 – tvrzení je věrohodné, ale neověřené.
- **Čísla kapitol Newmanovy *Building Microservices, 2nd ed.*** – **OVĚŘENO 2026-09-04** proti
  autorovu vlastnímu rozpisu obsahu na samnewman.io [13]. Výsledek u nálezu G23 níže.
- **phparkitect – OVĚŘENO 2026-09-04 z Packagistu.** `phparkitect/phparkitect` **1.3.0
  (31. 7. 2026)**, vyžaduje `php ^8.0`. Projekt je aktivní. Pro srovnání: alternativa
  `phpat/phpat` je na 0.12.4 (17. 3. 2026), `php ^8.1` – tedy stále série 0.x.
