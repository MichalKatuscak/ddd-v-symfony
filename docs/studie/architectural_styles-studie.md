# Studie: Architektonické styly – Hexagonal, Onion, Clean

- **Kapitola:** `content/chapters/architectural_styles.md` (č. 09, kategorie Architektura, 1284 řádků)
- **Cesta:** /architektonicke-styly
- **Typ kapitoly:** definiční
- **Datum studie:** 2026-09-03

## 1. Mapa současné kapitoly

| Sekce | Řádky | Co tvrdí | Zdroje v textu | Poznámka |
|---|---|---|---|---|
| Deck + úvod | 22–25 | DDD a architektonický styl jsou ortogonální osy | – | Dobrá teze, drží se celou kapitolou |
| 09.01 Proč styl ≠ DDD | 26–48 | Modelovací osa vs. strukturální osa; Evans věnuje vrstvám jen krátkou kapitolu | Evans 2003 [23] | Nejsilnější sekce kapitoly, zároveň nejkratší |
| 09.02 Layered | 49–197 | Fowler 3 vrstvy, Evans 4 vrstvy; Symfony skeleton = Layered; Doctrine atributy = domain leak; anti-vzor Anemic Domain Model | Fowler PoEAA [24], Evans [23], Fowler *AnemicDomainModel* | Přesné. Ukázka entity + controlleru, kdy se hodí / nehodí |
| 09.03 Hexagonal | 198–576 | Cockburn 2005; driving/driven porty; Symfony struktura; outbound port + adaptér; inbound port; autowiring a alias; EventPublisher port; anti-vzor Anemic Hexagonal | Cockburn 2005 [1] | Nejdelší sekce (379 řádků, 30 % kapitoly). Šest kódových ukázek. Chybí granularita portů a 2024 kniha |
| 09.04 Onion | 577–724 | Palermo 2008 + čtvrtý díl 2013; čtyři koncentrické vrstvy; tři odlišnosti proti Hexagonal; Domain vs. Application Service | Palermo 2008 [4] | Odlišnosti proti Hexagonal jsou popsané dobře; Palermovy vlastní „four tenets" nepoužity |
| 09.05 Clean | 725–915 | Martin 2012 blog + 2017 kniha; čtyři prstence; Dependency Rule; use case jako prvotřídní koncept; Request/Response DTO; vztah k CQRS handleru | Martin 2012 [6] | Kniha 2017 zmíněna jednou větou, v bibliografii chybí |
| 09.06 Vertical Slice | 916–1016 | Bogard 2018; horizontální vs. vertikální dělení; konvence struktury knihy; srovnávací tabulka; ortogonalita k Hexagonal | Bogard 2018 [10], MS docs | Bogardova centrální teze („most abstractions melt away") chybí |
| 09.07 Srovnání | 1017–1058 | Rozhodovací matice devíti kritérií napříč pěti styly; doporučená výchozí volba Hexagonal + Vertical Slice + CQRS | – | Matice je bez zdroje, čísla („< 50 endpointů", „4–6 souborů") jsou autorské odhady |
| 09.08 Hybrid | 1059–1142 | Diferencovaná investice podle typu subdomény; jeden BC = jeden styl | Vernon IDDD [7 v kap.] | Věcně v pořádku, dobře navazuje na kapitolu Subdomény |
| 09.09 Anti-vzory | 1143–1184 | Šest anti-vzorů: Hexagonal kult, domain leakage, Anemic Hexagonal, port jen pro repository, premature DI, architecture astronaut | – | Dva ze šesti jsou jen odkazem zpět na callout v 09.03 |
| 09.10 Symfony 8 specifika | 1185–1250 | Bundly nepoužívat; per-context konfigurace; vyloučení doménových modelů z kontejneru; Messenger jako Command Bus | – | Sekce bez jediného odkazu na symfony.com. Dvě z tvrzení jsou fakticky vadná (viz G1, G2, G11) |
| 09.11 Shrnutí + FAQ | 1251–1272 | Čtyři body shrnutí, šest FAQ položek | – | FAQ opakuje chybu z 09.03 o autowiringu |
| 09.12 Další četba | 1273–1284 | Deset položek | – | Chybí Cockburnova kniha 2024 a Martinova kniha 2017 |

**Celkový charakter.** Kapitola je katalog: pět stylů, u každého definice, Symfony struktura, kdy ano, kdy ne,
anti-vzor. Struktura je konzistentní a čtivá. Prostor je rozdělen nerovnoměrně – Hexagonal dostal skoro tolik
řádků jako Onion, Clean a Vertical Slice dohromady, protože nese většinu kódových ukázek.
Odbytá je naopak historická přesnost: kapitola cituje původní články z let 2005–2018, ale ignoruje, že autoři
se k vzorům později vraceli a korigovali je (Cockburn 2020 a 2024, Palermo 2013, Martin 2017).
Druhá slabina je sekce 09.10 – jediná ryze Symfony sekce v kapitole nemá žádný odkaz na dokumentaci
a obsahuje dvě nepřesnosti, které čtenář při psaní kódu narazí.

## 2. Kanonické zdroje k tématu

**Cockburn, Ports & Adapters (2005).** Článek je datovaný 4. 9. 2005 a nese označení „version 0.9, to be
updated after reader comments" [1]. Deklarovaný záměr: *„Allow an application to equally be driven by users,
programs, automated test or batch scripts, and to be developed and tested in isolation from its eventual
run-time devices and databases."* Hexagon jako tvar Cockburn vysvětluje takto: *„The hexagon is not a hexagon
because the number six is important, but rather to allow the people doing the drawing to have room to insert
ports and adapters as they need, not being constrained by a one-dimensional layered drawing."* [1]
Tedy ne „jádro má víc než dvě strany", ale „kreslicí plocha na porty".

**Granularita portů je v originále explicitní téma.** Cockburn píše: *„A port identifies a purposeful
conversation. There will typically be multiple adapters for any one port, for various technologies that may
plug into that port."* A dál: *„At the one extreme, every use case could be given its own port, producing
hundreds of ports for many applications. […] My selection tends to favor a small number, two, three or four
ports."* [1] V příkladu z Known Uses jmenuje čtyři přirozené porty celého systému: weather feed, administrator,
notified subscribers, subscriber database.

**Cockburnovo pozdější upřesnění.** V rozhovoru s Juanem Manuelem Garridem de Paz (9. 9. 2020) říká, že do roku
2017 kolovaly nesprávné výklady jeho vzoru, a jmenuje hlavní chybu: *„the main error is usually using only one
technology per port, or port per technology, when the whole point of a port is to allow technology
substitutions."* [3] V témže rozhovoru Garrido de Paz formuluje – bez Cockburnovy námitky – že *„Hexagonal
Architecture doesn't say how to organize the code inside the hexagon. Splitting it into application and domain
layers is a DDD topic."* [3] Cockburn tam také připisuje Gerardu Meszarosovi (2011) pojmenování zastřešujícího
vzoru **Configurable Dependency** – „changing technologies at an interface" – jako mechanismu, na kterém porty
stojí [3]. A vysvětluje adopci vzoru DDD komunitou kolem roku 2012 jako *„an act of self-defense"* před
technologiemi prorůstajícími do modelu.

**Kniha existuje a je ověřená.** Alistair Cockburn a Juan Manuel Garrido de Paz, *Hexagonal Architecture
Explained: How the Ports & Adapters Architecture Simplifies Your Life, and How to Implement It*,
Humans & Technology Press, 8. 5. 2024, ISBN 978-1-7375197-8-2 [2]. Garrido de Paz zemřel v dubnu 2024,
zhruba dva týdny před uzavřením textu; knihu dokončil Cockburn sám.

**Palermo, Onion Architecture (2008).** První díl je z 29. 7. 2008 [4], čtvrtý díl *After Four Years*
z 19. 8. 2013 [5]. Datace v kapitole tedy sedí. Palermo v roce 2013 shrnuje vzor do čtyř tezí:
aplikace je postavená kolem nezávislého objektového modelu; vnitřní vrstvy definují rozhraní, vnější je
implementují; směr vazby míří do středu; celé jádro se dá zkompilovat a spustit bez infrastruktury [5].
Explicitně dodává, že Onion nezávisí na DDD, na CQRS ani na IoC kontejneru – proti běžnému výkladu, že jde
o „DDD architekturu" [5].

**Martin, Clean Architecture (2012 a 2017).** Blogpost je z 13. 8. 2012 [6]. Martin v něm jmenuje čtyři zdroje:
Hexagonal Architecture (Cockburn), Onion Architecture (Palermo), DCI (Coplien, Reenskaug) a BCE (Jacobson).
Dependency Rule formuluje jako *„source code dependencies can only point inwards"*. Ke čtyřem prstencům
výslovně dodává: *„No, the circles are schematic. You may find that you need more than just these four."* [6]
Druhé pravidlo, které kapitola implicitně používá, ale necituje: přes hranici se předávají *„simple data
structures"*, nikdy databázové řádky ani entity [6]. Kniha *Clean Architecture: A Craftsman's Guide to Software
Structure and Design* vyšla u Prentice Hall v roce 2017 [7].

**Bogard, Vertical Slice Architecture (2018).** Datum 19. 4. 2018 [10]. Bogardova formulace: *„minimize coupling
between slices, and maximize coupling in a slice."* Jeho kritika vrstvových stylů je ostřejší, než kapitola
připouští – vadí mu povinný řetěz „Controller MUST talk to a Service that MUST use a Repository" a tvrdí, že
u slice *„most abstractions melt away"*. Podstatná část jeho argumentu: vzor doménové logiky se volí per slice,
ne per aplikace – triviální slice může být Transaction Script, složitý bohatý model [10].

**Fowler, PresentationDomainDataLayering (26. 8. 2015).** Zdroj, který kapitola nezná a který jí přitom dává
teoretickou oporu: *„once any of these layers gets too big you should split your top level into domain oriented
modules which are internally layered."* [9] Tedy moduly první, vrstvy až uvnitř nich. Fowler tam také varuje
před organizací týmů podle vrstev.

**Noback, Layers, ports & adapters (2017).** Nejbližší primární zdroj pro PHP. Noback pracuje se **třemi**
vrstvami – Domain, Application, Infrastructure – a UI počítá do Infrastructure [11]. Formuluje pravidlo:
*„you should only depend on things that are in the same or in a deeper layer."* Porty definuje jako
organizační pojem uvnitř infrastrukturní vrstvy, adaptéry jako podadresáře pod nimi [11].

## 3. Stav praxe a posuny

**Ztotožňování tří stylů je dnes většinový výklad – a je nepřesné.** Praxe konverguje k tomu, co Herberto Graça
popsal jako Explicit Architecture (16. 11. 2017): Ports & Adapters dodává hrubé rozdělení na UI / jádro /
infrastrukturu, Onion přidává dovnitř jádra DDD vrstvy, Clean přidává směrové pravidlo a explicitní use casy
[12]. Graça je popisuje jako doplňkové, ne zaměnitelné. Kapitola tuto skladbu v podstatě používá (sekce 09.04
„Rozdíl proti Hexagonal", 09.05 „Co Clean přidává"), jen ji nepodkládá.

**Cockburn se vůči vrstevnatým výkladům vymezuje – ale jinak, než se v komunitě traduje.** Nenajde se jeho
výrok „hexagonal is not layered". Co se najde: originál nepředepisuje žádné vnitřní vrstvení, jen vnitřek
a vnějšek [1]; rozdělení na Application a Domain je podle Garrida de Paz DDD téma, ne hexagonální [3];
a hlavní chybou v praxi je podle Cockburna jeden port na technologii místo portu jako „purposeful
conversation" s několika vyměnitelnými adaptéry [3]. To poslední je posun, který kapitola vůbec nereflektuje.

**Třetí osa dělení – modul, ne vrstva ani slice.** Vedle horizontálního a vertikálního dělení se za posledních
zhruba osm let etabloval modulární monolit: jedna jednotka nasazení, uvnitř moduly s vynucovaným veřejným API.
Kamil Grzybek (2. 12. 2019) definuje modul třemi vlastnostmi – nezávislost a zaměnitelnost, kompletní business
funkčnost („business modules organized as vertical slices, not technical layers"), definované rozhraní:
*„Everything that we share outside becomes the public API of the module."* [13] Simon Brown na to jde ze strany
balíčkování („package by layer / by feature / by component") a doporučuje spoléhat na kompilátor, ne na
disciplínu [8]. Kapitola pojem modulární monolit nezná, byť ho kniha probírá v kapitole 19 [/ddd-a-microservices#modular-monolith].

**Vynucování pravidel se přesunulo do CI.** Dependency Rule dnes nikdo nekontroluje code review; kontroluje ji
statická analýza. V PHP je referenční nástroj Deptrac (typickou konfigurací jsou právě vrstvy Domain /
Application / Infrastructure) [22]. Kniha to má v kapitole 17.08 [/testovani-ddd#architektonicke-testy];
kapitola 09 se na to neodkazuje, přestože sekce 09.04 tvrdí, že „každá závislost se dá zkontrolovat statickou
analýzou".

**Kritika stylů zdomácněla.** Standardní výhrady – počet souborů na jednu triviální featuru, mapovací vrstvy,
které jen kopírují pole, rozhraní s jedinou implementací – kapitola pokrývá v sekci 09.09 dobře a bez apologie.
Zde je na tom kapitola lépe než většina online obsahu. Slabší je opačná strana: chybí Bogardův argument, že
odpovědí není měkčí Clean, ale jiná jednotka dělení [10], a Fowlerův argument, že vrstvení není správná
dekompozice nejvyšší úrovně [9].

## 4. Symfony / PHP specifika

**Symfony 8.0 vyšlo 27. 11. 2025, minimální PHP je 8.4** [20]. To odpovídá konvencím knihy.

**Autowiring rozhraní – kapitola tvrdí opak toho, co dokumentace.** Symfony dokumentace k autowiringu obsahuje
tip: *„When loading services automatically with resource, if only one service is discovered that implements an
interface, configuring the alias is not mandatory and Symfony will automatically create one."* [14] Chování
existuje minimálně od řady 3.x/4.x (starší znění dodává podmínku, že rozhraní musí být objeveno ve stejném
souboru/resource). V hexagonální struktuře knihy je `Domain/Port/OrderRepository` i
`Infrastructure/Persistence/DoctrineOrderRepository` pod `src/`, takže alias vznikne sám. Explicitní alias je
potřeba až při druhé implementaci, nebo když je adresář s rozhraním vyloučený z `resource`.

**Nástroje pro víc implementací téhož portu.** `#[AsAlias]` na implementaci (id lze vynechat, pokud třída
implementuje právě jedno rozhraní); pojmenované autowiring aliasy plus `#[Target('...')]` na parametru
konstruktoru [14]. Doporučená formulace pro knihu: alias v `services.yaml` nebo `#[AsAlias]`, výběr mezi víc
adaptéry přes `#[Target]`, nikdy `#[Autowire(service: ...)]` v aplikační vrstvě – tuhle poslední část kapitola
už má a je správná.

**Vyloučení doménových tříd z kontejneru.** Dokumentace ke Service Containeru na otázku, zda se registruje
každá třída v `src/`, odpovídá: *„As long as you keep your imported services as private, all classes in `src/`
that are not explicitly used as services are automatically removed from the final container."* [15] Vyloučení
`Domain/Model/` je tedy hygiena (menší kontejner, čitelnější konfigurace, žádné náhodné použití agregátu jako
služby), ne ochrana před tím, že by Symfony do entit něco injektovalo.

**Messenger, tři sběrnice.** `default_middleware` má tři klíče: `enabled` (default `true`),
`allow_no_handlers` (default `false`) a `allow_no_senders` (default `true`) [18]. Sémantika posledního je
v `SendMessageMiddleware`: při `allowNoSenders: false` a chybějícím senderu se vyhodí
`NoSenderForMessageException` [19]. Query bus bez transportu tedy s `allow_no_senders: false` spadne
na každém dispatchi.

**ObjectMapper – nová odpověď na hlavní cenovou námitku.** Komponenta `symfony/object-mapper` je od Symfony 8.0
stabilní (v 7.3 experimentální). Dokumentace ji sama uvádí jako nástroj pro převod DTO na entity a zpět
a jmenuje mezi jejími případy užití implementaci hexagonálních architektur [17]. Používá se přes
`ObjectMapperInterface::map()` a atribut `#[Map(target: ...)]`. To se přímo dotýká sekcí 09.05
(Request/Response DTO) a antivzoru 2 (Persisted Object Pattern) – největší uváděná cena těchto stylů je právě
ruční mapování.

**Doctrine ORM 3.** Anotace v docblocích jsou odstraněné, atributy a XML mapping zůstávají podporované
[21]. Kniha má kanonické rozhodnutí (atributy na agregátu jako výchozí, Persisted Object Pattern jako čistá
varianta, XML mapping odmítnut jako „znečištění jiným formátem"; `implementation_in_symfony.md:628`). Kapitola
09 je s tím konzistentní, jen na to na dvou místech správně odkazuje.

**Bundly.** Oficiální Best Practices: *„Don't create bundles to organize your own internal application logic:
use PHP namespaces (under the `App\` namespace) to structure your code instead."* [16] Tvrzení kapitoly
v 09.10 je správné, jen bez odkazu. Tamtéž je doložitelné, že výchozí skeleton `src/` (Controller, Entity,
Repository, Form, Security) je Layered – to kapitola tvrdí v 09.02 a je to podložitelné [16].

## 5. Sporné a chybně podávané body

**1. „Autowiring rozhraní nenaváže."** Kapitola to tvrdí dvakrát (`:399` a ve FAQ `:1268`). Dokumentace tvrdí
opak [14]. Není to jen detail: čtenář podle kapitoly napíše alias, který nepotřebuje, a hlavně si odnese
špatný mentální model kontejneru. **Doporučení:** opravit na „alias vzniká automaticky, pokud rozhraní
implementuje právě jedna objevená služba; explicitní alias potřebujete při víc implementacích nebo když je
zdrojový adresář vyloučený" – a rovnou tím propojit s vyloučeními v 09.10.

**2. Port = jedno rozhraní na jednu závislost.** Antivzor 4 (`:1165`) říká: „Každá výstupní závislost domény
dostane port." Cockburn říká něco jiného: port je „purposeful conversation" s typicky víc adaptéry, a rozumný
počet portů je dva až čtyři [1]; jeden port na technologii označuje za hlavní chybu praxe [3]. Obě polohy mají
zastánce – Noback používá spíš jemnější granularitu blízkou kapitole [11], zatímco Graça i Cockburn drží
hrubší [1][12]. **Doporučení:** kapitola má právo držet pragmatickou granularitu, ale musí přiznat, že to není
Cockburnova definice, a rozdíl pojmenovat.

**3. Vysvětlení tvaru hexagonu.** Kapitola (`:202`): „Cockburn původně chtěl ukázat, že jádro má víc než dvě
strany." Originál: aby měl kreslíř místo na porty a nebyl svázaný jednorozměrným vrstvovým schématem [1].
Významový rozdíl je malý, ale u definiční kapitoly stojí za přesnou formulaci.

**4. Onion jako inherentně DDD styl.** Kapitola popisuje Onion přes Domain Model / Domain Services /
Application Services. Palermo v roce 2013 výslovně píše, že Onion na DDD ani CQRS ani IoC kontejneru nezávisí
[5]. **Doporučení:** ponechat DDD čtení (kniha je o DDD), ale dodat větu, že to je čtení, ne definice.

**5. Čtyři prstence Clean jako pevný počet.** Martin sám: prstence jsou schéma, můžete jich potřebovat víc [6].
Kapitola je podává jako výčet. Jedna věta to spraví.

**6. Tři vrstvy vs. čtyři.** 09.02 učí Evansovy čtyři vrstvy (UI, Application, Domain, Infrastructure), ale
všechny Symfony struktury v kapitole (09.03, 09.04, 09.06, 09.08) používají tři adresáře – Domain,
Application, Infrastructure – s controllery uvnitř Infrastructure. To je Nobackova a v PHP převládající
konvence [11]. Kapitola ten přechod nikde nekomentuje a čtenář ho může číst jako nedůslednost.

**7. Rozhodovací matice bez zdroje.** `:1023–1034` a `:1053–1056` obsahují čísla („< 50 endpointů", „50–500",
„enterprise 200+", „4–6 souborů"), která nemají a nemohou mít zdroj. Jsou užitečná, ale prezentovaná jako
fakt. **Doporučení:** označit je jako autorské pravidlo palce, ne jako měřený údaj.

**8. Vernon jako opora hybridního přístupu.** Callout `:1136` připisuje Vernonovi poměr „80 % investice do
20 % kódu". Diferencovaná investice podle typu subdomény u Vernona i Evanse je, konkrétní poměr 80/20 je
pravděpodobně autorská ilustrace. **Doporučení:** buď dohledat, nebo poměr vypustit a nechat jen kvalitativní
tvrzení.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | zastaralé/nepodložené | `architectural_styles.md:397–399`, `:1268` | „Autowiring rozhraní nenaváže ani při jediné implementaci." Dokumentace [14] tvrdí opak. | Přepsat obě místa, doplnit podmínku (objevené rozhraní v `resource`) a `#[Target]` pro víc implementací |
| G2 | sporné | `:1224–1247` | `query.bus` má `allow_no_senders: false`; každý sync query dispatch vyhodí `NoSenderForMessageException` [18][19] | Odstranit `allow_no_senders: false`; `allow_no_handlers: false` je default, také lze vypustit |
| G3 | chybí | sekce 09.03, `:1165–1169` | Granularita portů. Cockburn: port = „purposeful conversation", 2–4 porty, víc adaptérů na port [1]; „port per technology" označen za hlavní chybu [3] | Nová podsekce v 09.03 (~25 řádků) + revize antivzoru 4 |
| G4 | chybí | `:1273–1284` | Cockburn & Garrido de Paz, *Hexagonal Architecture Explained* (2024) [2] – autoritativní současný zdroj k tématu kapitoly | Doplnit do Další četby a do 09.03 zmínit, že vzor má od 2024 knižní referenci |
| G5 | chybí | `:725–743` | Martinova kniha *Clean Architecture* (2017) [7] není v bibliografii; pravidlo „přes hranici jen jednoduché datové struktury" [6] není citováno, byť ho ukázky používají | Doplnit do Další četby + jedna věta k DTO |
| G6 | mělké | `:200–203` | Vysvětlení tvaru hexagonu je parafráze, která posouvá význam | Nahradit Cockburnovou formulací [1] |
| G7 | chybí | sekce 09.03 | Cockburn nepředepisuje vnitřní vrstvení; rozdělení Application/Domain je DDD dodatek [3] | Doplnit do 09.03 nebo do 09.04 „Rozdíl proti Hexagonal" |
| G8 | chybí | sekce 09.03 | Configurable Dependency (Meszaros 2011), kterou Cockburn uvádí jako vzor pod porty [3] | Jedna až dvě věty, propojit s DI sekcí `:397` |
| G9 | chybí | sekce 09.06 | Modulární monolit / package by component jako třetí osa dělení [8][13]; kniha ho má v kap. 19 | Odstavec v 09.06 + odkaz `/ddd-a-microservices#modular-monolith` |
| G10 | chybí | sekce 09.01 nebo 09.06 | Fowler, *PresentationDomainDataLayering* (2015): moduly nahoře, vrstvy uvnitř [9] | Doplnit jako oporu tezi kapitoly o Vertical Slice |
| G11 | nepodložené | `:1218` | „Pokud je necháte registrovat jako služby, riskujete, že Symfony do nich zkusí injektovat závislosti." Nepoužité privátní služby kontejner odstraňuje [15] | Přeformulovat důvod (velikost kontejneru, čitelnost, prevence omylu) |
| G12 | chybí | sekce 09.05, `:1153–1157`, 09.10 | Symfony 8 ObjectMapper (stabilní v 8.0), dokumentace ho sama váže na hexagonální architekturu [17] | Nová pasáž v 09.10 nebo callout v 09.05 (~15 řádků) |
| G13 | mělké | `:944–1015` | Vertical Slice bez Bogardovy centrální teze („most abstractions melt away", vzor per slice) [10]; kapitola ukazuje měkčí variantu se sdíleným doménovým modelem BC | Doplnit Bogardovu pozici a pojmenovat, že konvence knihy je vědomě měkčí |
| G14 | chybí | sekce 09.04 | Palermovy čtyři teze z roku 2013 a jeho výrok, že Onion nezávisí na DDD/CQRS/IoC [5] | Doplnit do 09.04, ~8 řádků |
| G15 | nepodložené | `:1023–1034`, `:1053–1056` | Čísla v rozhodovací matici a ve „třech otázkách" jsou autorské odhady podané jako fakt | Označit jako pravidlo palce |
| G16 | chybí | sekce 09.04 (`:717`), 09.10 | Vynucení Dependency Rule statickou analýzou (Deptrac) [22]; kniha to má v 17.08 | Odkaz `/testovani-ddd#architektonicke-testy` + jméno nástroje |
| G17 | nadbytečné | `:1159–1169` | Antivzory 3 a 4 jsou jen odkazy zpět na callout v 09.03, bez nového obsahu | Sloučit do jednoho bodu nebo je nahradit novým obsahem (např. „port per technology") |
| G18 | mělké | `:1189–1191` | Tvrzení o bundlech je správné, ale bez odkazu na Best Practices [16] | Doplnit citaci |
| G19 | sporné | `:1136–1140` | Poměr „80 % investice do 20 % kódu" připsaný Vernonovi | Dohledat, nebo poměr vypustit |
| G20 | mělké | `:555`, `:1021` | Diagram 09.3-A „Čtyři architektonické styly" je vložen v sekci Hexagonal, tedy dřív, než jsou Onion a Clean zavedeny | Přesunout do 09.07 k druhému diagramu, nebo změnit záběr obrázku |
| G21 | nepodložené | `:1283` | Odkaz `docs.microsoft.com` (legacy doména), `:1279` odkaz na IDDD vede na Amazon | Aktualizovat na `learn.microsoft.com`, u knihy použít bibliografický záznam |

## 7. Doporučení k přepisu

**P1-1 — Opravit tvrzení o autowiringu rozhraní na dvou místech.** Symfony alias pro rozhraní s jedinou
objevenou implementací vytvoří samo [14]. Kapitola tvrdí opak a staví na tom celou pasáž o Dependency Rule
v konfiguraci. Oprava zároveň otevírá užitečnou nuanci: vyloučení adresářů v 09.10 může auto-alias vypnout.
Odhad: přepis `:397–415` a jedné FAQ odpovědi, ~20 řádků.

**P1-2 — Opravit `messenger.yaml`.** `allow_no_senders: false` na query busu rozbije každý synchronní dotaz
[18][19]. Jde o kopírovatelnou ukázku, takže chyba se dostane do projektů čtenářů. Odhad: oprava tří řádků
YAML + věta v komentáři.

**P1-3 — Doplnit granularitu portů a přepsat antivzor 4.** Kapitola dnes učí „port na každou výstupní
závislost", což je přesně vzorec, který Cockburn označuje za hlavní chybu praxe [3]. Bez toho je definiční
kapitola o Hexagonal v rozporu s primárním zdrojem. Odhad: nová podsekce v 09.03 ~25 řádků + přepis
`:1165–1169`.

**P1-4 — Opravit odůvodnění vyloučení doménových tříd z kontejneru.** Tvrzení o injektování závislostí do
entit neodpovídá tomu, jak kontejner funguje [15]. Praktické doporučení (vyloučit) zůstává, mění se důvod.
Odhad: přepis dvou vět.

**P1-5 — Doplnit dva chybějící primární zdroje: Cockburn & Garrido de Paz (2024) a Martin (2017).** Definiční
kapitola o třech stylech, která u dvou z nich cituje jen blogpost a knihu ignoruje, je bibliograficky vadná.
U Cockburnovy knihy stojí za zmínku i to, že vznikla jako reakce na rozšířené dezinterpretace [2][3].
Odhad: dvě položky v 09.12 + dvě věty v textu.

**P2-1 — Přidat podsekci o modulárním monolitu a o Fowlerově „moduly první, vrstvy uvnitř".** Kapitola dnes
staví dvě osy (vrstvy × feature) a chybí jí třetí (modul s vynucovaným veřejným API). Fowler [9] a Grzybek
[13] jí přitom dávají oporu pro doporučení, ke kterému kapitola sama dochází. Odhad: nová podsekce v 09.06,
~30 řádků, plus odkaz na kapitolu 19.

**P2-2 — Doplnit Symfony 8 ObjectMapper.** Největší uváděná cena Clean a Persisted Object Patternu je ruční
mapování; Symfony 8 pro to má stabilní komponentu a dokumentace ji přímo váže na hexagonální architekturu
[17]. Bez toho kapitola působí, jako by cenový argument platil v plné síle. Odhad: callout v 09.05 nebo
podsekce v 09.10, ~20 řádků.

**P2-3 — Zesílit Vertical Slice o Bogardovu skutečnou pozici.** Kapitola ukazuje měkkou variantu (sdílený
doménový model BC, slice jen pro aplikační vrstvu). Bogard jde dál – ruší abstrakce a volí vzor per slice
[10]. Rozdíl je legitimní, ale musí být přiznaný, jinak kapitola připisuje Bogardovi něco jiného, než napsal.
Odhad: přepis `:944–970`, ~15 řádků.

**P2-4 — Doplnit Palermovy čtyři teze a jeho odstup od DDD.** Sekce 09.04 dnes stojí na parafrázi prvního
dílu; čtvrtý díl z roku 2013 dává přesnější a citovatelnější formulaci [5]. Odhad: ~10 řádků do 09.04.

**P2-5 — Propojit s architektonickými testy.** Tvrzení „každá závislost se dá zkontrolovat statickou
analýzou" (`:717`) volá po odkazu na kapitolu 17.08 a po jménu nástroje [22]. Odhad: dvě věty a jeden odkaz.

**P2-6 — Označit čísla v rozhodovací matici jako pravidlo palce.** Devět kritérií napříč pěti styly je
užitečná tabulka, ale prahové hodnoty jsou autorský odhad. Stačí jedna uvozovací věta nad tabulkou. Odhad:
oprava jedné věty.

**P3-1 — Doplnit Configurable Dependency (Meszaros 2011) jako pojmenování mechanismu pod porty** [3].
Vysvětluje, proč se driving a driven adaptéry chovají architektonicky jinak. Odhad: dvě věty.

**P3-2 — Sjednotit výklad tří vs. čtyř vrstev.** Jedna věta v 09.02 nebo 09.03 o tom, že PHP praxe UI
neodděluje a řadí ji do Infrastructure [11], odstraní zdánlivou nedůslednost mezi textem a všemi ukázkami
struktury.

**P3-3 — Přesunout diagram 09.3-A.** Obrázek srovnávající čtyři styly stojí v sekci, kde jsou zavedené dva.
Odhad: přesun dvou řádků.

**P3-4 — Zredukovat duplicitu v 09.09.** Antivzory 3 a 4 nepřinášejí nový obsah; místo lze využít pro
„port per technology" z P1-3. Odhad: přepis ~10 řádků.

**P3-5 — Hygiena odkazů.** `docs.microsoft.com` → `learn.microsoft.com`, IDDD bez odkazu na Amazon.

## 8. Otevřené otázky pro autora

1. **Má kapitola držet pragmatickou granularitu portů, nebo Cockburnovu?** Kniha zatím učí „jeden port na
   závislost", což je pro Symfony projekty čitelné a odpovídá tomu, jak se v PHP repository interfaces
   používají. Cockburnova hrubší granularita je věrnější originálu, ale hůř se mapuje na Doctrine repozitáře.
   Rozhodnutí ovlivní i kapitoly 10 a 17.
2. **Kolik prostoru dát modulárnímu monolitu tady, když ho probírá kapitola 19?** Varianty: odstavec s
   odkazem, nebo plnohodnotná šestá „osa" v přehledu stylů. Riziko druhé varianty je duplicita mezi 09 a 19.
3. **Zůstane rozhodovací matice v číselné podobě?** Alternativa je kvalitativní tabulka bez prahů. Číselná
   verze je pro čtenáře užitečnější, ale nepodložitelná.
4. **Má kapitola relitigovat XML mapping?** Kniha má kanonické rozhodnutí v kapitole 10
   (`implementation_in_symfony.md:628`) – XML mapping odmítnut. Doctrine ORM 3 ho ale dál podporuje [21]
   a část komunity ho používá právě kvůli čisté doméně. Studie doporučuje rozhodnutí nerozporovat,
   jen ho v 09.03 zmínit jednou větou jako existující třetí cestu.
5. **Zkrátit sekci 09.03?** 379 řádků z 1284 je nepoměr, který vzniká šesti kódovými ukázkami. Část z nich
   (EventPublisher port, InMemory double) se překrývá s kapitolami 10 a 17.
6. **Zůstane Vertical Slice v této kapitole, nebo dostane vlastní?** S doplněním modulárního monolitu a
   Bogardovy pozice naroste sekce 09.06 na dvojnásobek a kapitola překročí 1400 řádků.

## 9. Bibliografie

### Ověřené zdroje

`[1]` Alistair Cockburn — *Hexagonal Architecture (Ports and Adapters)*, verze 0.9, 4. 9. 2005.
https://alistair.cockburn.us/hexagonal-architecture/ (přístup 2026-09-03)

`[2]` Alistair Cockburn, Juan Manuel Garrido de Paz — *Hexagonal Architecture Explained: How the Ports &
Adapters Architecture Simplifies Your Life, and How to Implement It*. Humans & Technology Press, 8. 5. 2024.
ISBN 978-1-7375197-8-2.

`[3]` Juan Manuel Garrido de Paz — *Interview with Alistair Cockburn*, 9. 9. 2020.
https://jmgarridopaz.github.io/content/interviewalistair.html (přístup 2026-09-03)

`[4]` Jeffrey Palermo — *The Onion Architecture: Part 1*, 29. 7. 2008.
https://jeffreypalermo.com/2008/07/the-onion-architecture-part-1/ (přístup 2026-09-03)

`[5]` Jeffrey Palermo — *Onion Architecture: Part 4 – After Four Years*, 19. 8. 2013.
https://jeffreypalermo.com/2013/08/onion-architecture-part-4-after-four-years/ (přístup 2026-09-03)

`[6]` Robert C. Martin — *The Clean Architecture*, 13. 8. 2012.
https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html (přístup 2026-09-03)

`[7]` Robert C. Martin — *Clean Architecture: A Craftsman's Guide to Software Structure and Design*.
Prentice Hall, 2017.

`[8]` Simon Brown — *Modular monolith and „package by component"*. https://simonbrown.je/modular-monolith/
(přístup 2026-09-03). Doprovodná prezentace: https://static.simonbrown.je/modular-monoliths.pdf

`[9]` Martin Fowler — *PresentationDomainDataLayering*, 26. 8. 2015.
https://martinfowler.com/bliki/PresentationDomainDataLayering.html (přístup 2026-09-03)

`[10]` Jimmy Bogard — *Vertical Slice Architecture*, 19. 4. 2018.
https://www.jimmybogard.com/vertical-slice-architecture/ (přístup 2026-09-03)

`[11]` Matthias Noback — *Layers, ports & adapters*, díl 1 (31. 7. 2017), díl 2 (2. 8. 2017), díl 3 (3. 8. 2017).
https://matthiasnoback.nl/2017/07/layers-ports-and-adapters-part-1-introduction/ ·
https://matthiasnoback.nl/2017/08/layers-ports-and-adapters-part-2-layers/ ·
https://matthiasnoback.nl/2017/08/layers-ports-and-adapters-part-3-ports-and-adapters/ (přístup 2026-09-03)

`[12]` Herberto Graça — *DDD, Hexagonal, Onion, Clean, CQRS, … How I put it all together*, 16. 11. 2017.
https://herbertograca.com/2017/11/16/explicit-architecture-01-ddd-hexagonal-onion-clean-cqrs-how-i-put-it-all-together/
(přístup 2026-09-03)

`[13]` Kamil Grzybek — *Modular Monolith: A Primer*, 2. 12. 2019.
https://www.kamilgrzybek.com/blog/posts/modular-monolith-primer (přístup 2026-09-03)

`[14]` Symfony — *Defining Service Dependencies Automatically (Autowiring)*.
https://symfony.com/doc/current/service_container/autowiring.html (přístup 2026-09-03)

`[15]` Symfony — *Service Container*. https://symfony.com/doc/current/service_container.html (přístup 2026-09-03)

`[16]` Symfony — *Symfony Best Practices*. https://symfony.com/doc/current/best_practices.html (přístup 2026-09-03)

`[17]` Symfony — *ObjectMapper*. https://symfony.com/doc/current/object_mapper.html (přístup 2026-09-03)

`[18]` symfony/framework-bundle — `DependencyInjection/Configuration.php`, uzel `messenger.buses.*.default_middleware`.
https://raw.githubusercontent.com/symfony/framework-bundle/7.3/DependencyInjection/Configuration.php (přístup 2026-09-03)

`[19]` symfony/messenger — `Middleware/SendMessageMiddleware.php`, parametr `$allowNoSenders`.
https://raw.githubusercontent.com/symfony/messenger/7.3/Middleware/SendMessageMiddleware.php (přístup 2026-09-03)

`[20]` Symfony — *Symfony 8.0.0 released*, 27. 11. 2025. https://symfony.com/blog/symfony-8-0-0-released
(přístup 2026-09-03)

`[21]` Doctrine — *XML Mapping*, ORM 3.6.
https://www.doctrine-project.org/projects/doctrine-orm/en/3.6/reference/xml-mapping.html (přístup 2026-09-03)

`[22]` Deptrac. https://github.com/deptrac/deptrac · Symfony blog, *Clean software architecture with Deptrac*.
https://symfony.com/blog/clean-software-architecture-with-deptrac (přístup 2026-09-03)

`[23]` Eric Evans — *Domain-Driven Design: Tackling Complexity in the Heart of Software*. Addison-Wesley, 2003.

`[24]` Martin Fowler — *Patterns of Enterprise Application Architecture*. Addison-Wesley, 2002.

### Neověřené / nedohledané

- **Obsah knihy [2].** Studie pracuje s ověřenými metadaty (autoři, vydavatel, datum, ISBN) a s okolnostmi
  vzniku, ne s textem knihy. Před psaním sekce 09.03 by ji autor měl přečíst – zejména kvůli tomu, jak
  Cockburn v ní formuluje vztah k Onion a Clean.
- **Fowlerova kritika hexagonální symetrie.** Anglická Wikipedie ji připisuje Fowlerovi a *PoEAA* (tvrzení,
  že symetrie zakrývá asymetrii mezi poskytovatelem a konzumentem služby). V dohledatelném textu *PoEAA*
  se hexagonální architektura neprobírá a rok uvedený u citace (2003) neodpovídá vydání knihy (2002).
  Necitovat, dokud se nedohledá původní formulace.
- **Simon Brown, „The Missing Chapter"** (kap. 34 v [7]). Existence kapitoly je doložená, ale studie
  pracuje jen se sekundárními shrnutími, ne s textem.
- **Poměr „80 % investice do 20 % kódu" u Vaughna Vernona** (`architectural_styles.md:1138`). Kvalitativní
  doporučení diferencované investice je u Vernona i Evanse doložené, konkrétní poměr se nepodařilo dohledat.
- **Evansova formulace o komunikaci směrem nahoru** (observery, callbacky) ve vrstvené architektuře.
  Kapitola tvrdí jen „nikdy nahoru"; přesná Evansova formulace vyžaduje kontrolu v knize.
- **`#[AsAlias]` s parametrem `target` – OVĚŘENO 2026-09-04 proti CHANGELOGu
  `symfony/dependency-injection` (větev 8.1): tvrzení sedí.** Záznam pod hlavičkou 8.1 zní
  „Add `target` parameter to `#[AsAlias]` to create target-specific autowiring aliases“.
  Pro kontext je užitečná i historie atributu, protože kapitola pracuje s víc implementacemi
  téhož portu: `#[AsAlias]` existuje od **6.3**, argument `when` přibyl v **7.3**, možnost atribut
  rozšířit v **7.4** a `target` až v **8.1**. Jako novinku 8.1 lze tedy označit `target`, ne atribut
  jako takový. Deprecation párování podle jména parametru zůstává neověřená.
  Dokumentace na `symfony.com/doc/current` je uvádí jako 8.1; kniha cílí na 8.0, takže je do textu nezahrnovat
  bez rozhodnutí autora, na kterou minor verzi kniha míří.
- **Kniha „Explicit Architecture: Just Enough Structure to Survive"** připisovaná Herbertu Graçovi.
  Titul koluje v AI generovaných shrnutích; Graça na svém webu uvádí, že žádnou knihu nenapsal. Necitovat.
