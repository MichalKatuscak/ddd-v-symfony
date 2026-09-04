# Studie: Kdy DDD nepoužívat – upřímně

- **Kapitola:** `content/chapters/when_not_to_use_ddd.md` (č. 22, kategorie Praxe, 468 řádků)
- **Cesta:** /kdy-nepouzivat-ddd
- **Typ kapitoly:** narativní
- **Datum studie:** 2026-09-04

## 1. Mapa současné kapitoly

| Sekce | Rozsah | Co tvrdí | Zdroje | Poznámka |
|---|---|---|---|---|
| Úvod + rozcestník | 21–28 | Vymezení vůči kap. 20 a 21; DDD není pro každý projekt | – | Referenční vzor tónu celé knihy (`docs/prompts/review-chapter.md`, Vzor B). Nesahat na rytmus. |
| 22.01 Rozhodovací strom | 30–38 | Pět bran, na kteroukoli „ne“ = DDD není správná volba | – | Text říká „pět otázek“, obsah bran je jen v SVG. Strom je AND-hradlo na úrovni celého projektu. |
| 22.02 CRUD admin | 40–129 | CRUD nemá doménovou logiku, DDD zde přidá 6 tříd místo 1; EasyAdmin | Evans (přednášky, bez identifikace), Fowler AnemicDomainModel | Nejlepší srovnávací ukázka v kapitole. Atribuce Evanse je vágní. |
| 22.03 Startup | 131–195 | Před product-market fit nemá taktické DDD co modelovat; strategické nástroje ano | – | Rozlišení strategické/taktické je nejsilnější myšlenka kapitoly, ale žije jen zde. |
| 22.04 Malý tým bez expertů | 197–214 | Bez expertů vzniká model podle představ vývojáře | Vernon *IDDD* (bez konkrétního místa) | Nejkratší sekce (18 řádků), jediná alternativa je jedna věta. |
| 22.05 Data pipeline / ETL | 216–232 | Bez invariantů nejsou potřeba agregáty | Evans, definice agregátu (2003) | Autorský výklad citátu („data changes“ vs. „data transfer“) jde za to, co Evans napsal. |
| 22.06 Životnost < 1 rok | 234–256 | DDD se vrací po 6–12 měsících | Vernon *DDD Distilled*, „strategická hodnota“ | Čísla 1 rok / 6–12 měsíců nemají zdroj. Fowlerova design payoff line říká opak. |
| 22.07 Tým DDD nezná | 258–327 | Pseudo-DDD je horší než žádné DDD; ukázka `OrderAggregate` se setterem | – | Ukázka „správné DDD“ odpovídá konvencím knihy (`AggregateRoot`, `record()`). |
| 22.08 Nejasná doména | 329–348 | Nikdo doménu nechápe, ani experti | – | Nejslabší sekce: 20 řádků, obsahově se překrývá s 22.03 a 22.04. |
| 22.09 Hybrid podle subdomény | 350–411 | Khononov: architektura per typ subdomény; migration cost paradox; pseudo-DDD | Khononov *Learning DDD* (2021) | Obsahově nejhodnotnější sekce a zároveň jediná, která popírá strukturu zbytku kapitoly. |
| 22.10 Kdy DDD smysl má | 413–431 | Pět podmínek, stačí „většina“ | – | Přímý rozpor s rozhodovacím stromem, který vyžaduje všech pět. |
| FAQ | 433–442 | 4 otázky | – | Konzistentní s tělem kapitoly. |
| 22.11 Zdroje | 444–468 | 4 knihy, 2 články | – | Chybí Khononov, přestože sekce 22.09 na něm stojí. |

Kapitola má 468 řádků a je nejkratší v celé kategorii Praxe (`migration_from_crud.md` 1049,
`ddd_pain_points.md` 1074, `anti_patterns.md` 1146). Přitom je to uzel, na který odkazuje
předmluva třikrát, kapitola 1 čtyřikrát, kapitoly 2, 18, 21 a 24. Prostor dostává sedm
situací popsaných v podobné délce a se stejnou strukturou (odstavec + „Doporučené
alternativy“). Odbývá to, co čtenář po přečtení potřebuje nejvíc: jak rozhodnutí udělat
sám na svém projektu. Rozhodovací strom je jediný nástroj a nefunguje – je binární,
celoprojektový a v rozporu se sekcí 22.09, která tvrdí, že rozhodnutí je per subdoména.
Ekonomická čísla (5×, 6–12 měsíců, X / 5–10X / 3–4X) nesou váhu argumentu a nemají zdroj.

## 2. Kanonické zdroje k tématu

**Evans, *DDD Reference* (2015)** `[1]`. Ověřeno z PDF. Tři věci, které kapitola potřebuje
a nemá. Za prvé definici rozsahu: *„Domain-Driven Design is an approach to the development
of **complex** software in which we: 1. Focus on the core domain. 2. Explore models in
a creative collaboration of domain practitioners and software practitioners. 3. Speak
a ubiquitous language within an explicitly bounded context."* Slovo *complex* je v definici,
ne v komentáři – nejsilnější dostupná opora pro tezi kapitoly, a kapitola ji necituje.

Za druhé vzor `Core Domain`: *„It is harsh reality that not all parts of the design are
going to be equally refined. Priorities must be set."* a *„Justify investment in any other
part by how it supports the distilled core."* To je přesně tvrzení, které kapitola na
řádcích 48–51 přisuzuje blíže neurčeným Evansovým přednáškám. Existuje v citovatelné podobě.

Za třetí dva strategické vzory, které kapitola nezná: `Separate Ways` (*„Integration is
always expensive, and sometimes the benefit is small… Declare a bounded context to have no
connection to the others at all, allowing developers to find simple, specialized solutions
within this small scope."*) a `Big Ball of Mud` (*„The big ball of mud is actually quite
practical for some situations… Draw a boundary around the entire mess and designate it a big
ball of mud. **Do not try to apply sophisticated modeling within this context.**"*).
Big Ball of Mud je Evansova vlastní pojmenovaná odpověď na otázku „kde uvnitř existujícího
systému nemodelovat". Obojí kniha pokrývá v `context_mapping.md:747` a `:827`; kapitola 22
na to neodkazuje.

**Evans, *Getting Started with DDD When Surrounded by Legacy Systems* (2013)** `[2]`.
Ověřeno z PDF na domainlanguage.com. Otevírá větou *„Attempts to employ Domain-Driven Design
(DDD) tactics in the context of a legacy system almost always disappoint."* a pokračuje:
*„legacy replacement is usually a bad strategy, only made worse by simultaneously introducing
dramatic changes in process. Introducing a difficult new set of development principles and
techniques is best done incrementally, as in a pilot project."* Evans pro to má jméno –
**Bubble Context**: malý bounded context vymezený Anticorruption Layerem, který *„does not
require a big commitment to DDD. It allows even a small team to achieve a modest objective
involving some intricate domain logic and, ideally, one with some strategic value."* Slovo
„bubble" se v celé knize nevyskytuje ani jednou.

**Evans, QCon London 2009, *What I've learned about DDD since the book*** `[3]`. Existence
a abstrakt ověřeny na dddcommunity.org. V abstraktu popisuje posun důrazu: *„Ubiquitous
Language and Context Mapping and Core Domain are at the center, with aggregates in close
orbit. Why, I ask myself, did I put context mapping in Chapter 14? Core domain in Chapter
15?!"* Těžiště DDD se tedy posunulo od taktických vzorů ke strategickým. Obsah mluveného
slova jsem neověřoval (video bez přepisu) – patří do neověřených.

**Vernon, *DDD Distilled* (2016), kapitola 1** `[4]`. Ověřeno z oficiálních sample pages
vydavatele (PDF s celou kapitolou 1 a rejstříkem). Klíčové: *„it is a set of advanced
techniques to be used on complex software projects."* A protiváha, kterou kapitola nemá:
*„the imagined economy of No Design is a fallacy"* a citovaný Douglas Martin: *„The
alternative to good design is bad design, not no design at all."* Vernonova pozice není
„když ne DDD, tak nic", ale „když ne DDD, tak jiný promyšlený návrh". Rejstřík **neobsahuje
heslo „strategic value"**; tvrzení kapitoly na `:248–249` se z tohoto zdroje potvrdit
nepodařilo.

**Khononov – vazba typu subdomény na architekturu.** Primární dohledatelný zdroj je blogpost
*Revisiting the Basics of Domain-Driven Design* (2018) `[5]`, tři roky před *Learning DDD*:
*„For Core subdomains, use the heavy artillery. Implement the Domain Model or Event-Sourced
Domain Model pattern… For Supporting subdomains, use simple solutions. Transaction Script or
Active Record patterns are enough."* Tabulka v `:355–359` je s tím v souladu. Post navíc
obsahuje to, co kapitole chybí nejvíc – **operativní heuristiky pro posouzení složitosti**:
popisují experti systém v CRUD termínech (jednoduché); točí se logika kolem validace vstupu
(jednoduché); jsou tam složité algoritmy a výpočty (složité); jsou tam invarianty, které je
nutné vynutit (složité); jaká by byla cyklomatická složitost a kolik je scénářů provedení
(složité). K tomu pravidlo pro hraniční případ: *„if a supporting domain is complex, and
there are reasons for its complexity, then it might be a Core subdomain in disguise."*
A explicitní připuštění projektu bez Core subdomény: *„There might be no Core subdomains to
be implemented in the software… Do not over-engineer the solution if it provides no business
value."*

**Fowler – ekonomika návrhu.** Čtyři texty tvoří jediný veřejně dostupný argumentační rámec
pro „vyplatí se investice do návrhu". *Design Stamina Hypothesis* (2007) `[6]` zavádí
**design payoff line** jako průsečík dvou křivek kumulované funkcionality; Fowler ji sám
označuje za hypotézu, ne měřitelný fakt (*„We CannotMeasureProductivity nor can we measure
design quality"*), a k poloze té čáry říká: *„I take the view that it's much lower than most
people think: usually **weeks not months**."* *Yagni* (2015) `[7]` rozkládá náklad
spekulativní funkce na cost of build, delay a carry, s odkazem na Kohaviho data z Microsoftu,
že jen ⅓ nasazených funkcí zlepšila metriku, pro kterou vznikly. *Sacrificial Architecture*
(2014) `[8]` dokládá eBay (perl 1995 → C++ 1997 → Java 2002), Googlem praktikované *„design
for ~10X growth, but plan to rewrite before ~100X"* a podmínku legitimního přepisu: *„The
team that writes the sacrificial architecture is the team that decides it's time to sacrifice
it."* *MonolithFirst* (2015) `[9]` a *MicroservicePremium* (2015) `[10]` nabízejí přenositelnou
strukturu argumentu – prémie za styl, výhodná až nad prahem složitosti: *„don't even consider
microservices unless you have a system that's too complex to manage as a monolith."*

**Fowler – *Anemic Domain Model* (2003)** `[11]`. Kapitola článek cituje na `:125`, ale jen
k odlišení pojmů. Nejcitovanější věta je přitom přesně argument, který kapitola staví
v pasáži o pseudo-DDD (`:407–409`): *„the problem with anemic domain models is that they
incur all of the costs of a domain model, without yielding any of the benefits."* A o kus
dál: *„As I discussed in P of EAA, **Domain Models aren't always the best tool**."*

**Fowler – *Transaction Script* (P of EAA, 2003)** `[12]`. Kapitola pojem nepoužívá, přestože
Khononovova tabulka na něj odkazuje: *„Organizes business logic by procedures where each
procedure handles a single request from the presentation… making calls directly to the
database or through a thin database wrapper."* Pozn.: vzor nemá stránku v bliki, žije
v `eaaCatalog` (`martinfowler.com/bliki/TransactionScript.html` vrací 404).

**Verraes** `[13]`, `[14]`. Definice DDD jako *design discipline*, ne sady vzorů, s poznámkou
*„it aims to be pragmatic. **You don't apply DDD everywhere, you do it where it will have the
most impact.**"* – jednověté shrnutí sekce 22.09 od uznávaného praktika. Druhý text nabízí
**Repair/Replace heuristiku**: *„If the system had good design, repair it; If the system had
bad design, replace it."* Postavená na Gallově zákoně a temporálním modelování, ne na odhadu
ceny. Přímá alternativa k nákladovému modelu v `:379–381`.

**Khorikov, *Domain-centric vs data-centric approaches* (2015)** `[15]`. Tentýž tvar křivky
jako Fowler, ale přímo na volbě doménový model vs. Transaction Script: *„The data-centric
approach is often easier to start with… After a certain point, the effort required to evolve
such a system explodes."* A přiznaná cena: *„The main drawback with the domain-centric style
of thinking is its learning curve."*

**Spolsky, *Things You Should Never Do, Part I* (2000)** `[16]`. Kanonický zdroj pro odpor
k big-bang rewrite (Netscape, Borland, Word Pyramid). Argument: přepis zahazuje nashromážděné
opravy chyb, ne kód. Kapitola v `:380` big-bang rewrite oceňuje číslem, ale neopírá se
o žádnou z existujících prací.


## 3. Stav praxe a posuny

**Rozhodnutí se přesunulo z projektu na subdoménu.** V roce 2003 zněla otázka „aplikovat DDD
na tomto projektu?". Dnes zní „který bounded context si zaslouží kterou míru investice".
Khononov `[5]` z toho udělal kaskádu (typ subdomény → vzor obchodní logiky → architektonický
styl → testovací strategie), Verraes `[13]` totéž shrnuje jednou větou. Kapitola posun zná,
ale zavírá ho do jedné sekce na konci; sedm situací před ní i strom mluví o celém projektu.

**Rozdělení strategické vs. taktické DDD se stalo standardní odpovědí.** Evansův posun důrazu
ke Context Mappingu a Core Domain `[3]`, Verraesova definice DDD jako disciplíny `[13]`
i Bubble Context `[2]` vedou ke stejnému závěru: „ne DDD" v praxi téměř vždy znamená „ne
taktickým vzorům", ne „ne Ubiquitous Language a hranicím". Kapitola to říká jednou,
v `:141–145`, a jinde ne.

**Anemický model se přestal chápat jako binární chyba.** Fowlerova formulace „všechny náklady
bez přínosu" `[11]` se dnes používá jako test návratnosti: anemický model je vadný jen tam,
kde se platí režie doménového modelu. Nad tenkou vrstvou (Transaction Script) je to legitimní
volba. Kapitola to v `:124–128` naznačuje jako terminologickou poznámku, ne jako kritérium.

**Náklady na „lehkou" variantu klesly.** V roce 2015 znamenal CRUD admin ručně psaný
controller a formuláře. Dnes EasyAdmin 5, Sonata Admin 4 a API Platform 4 pokrývají celou
kategorii konfigurací. Práh, od kterého se doménový model vyplatí, se tím posouvá nahoru.

**Rewrite přestal být tabu.** Fowler `[8]` a Verraes `[14]` nabízejí podmínky, za nichž je
náhrada správná volba: systém byl špatně navržený; tým, který kód psal, sám rozhodl, že je
čas jej obětovat. Spolsky `[16]` reprezentuje starší absolutní pozici. Kapitola v `:375`
uvádí „standardní rozhodnutí" bez uvedení, kdo ho zastává, a bez protiargumentu.

## 4. Symfony / PHP specifika

Kapitola má na 468 řádcích jednu ukázku EasyAdmin, jednu Doctrine entitu a dvě doménové
třídy. V knize o Symfony je to málo, zejména proto, že „alternativy" jsou nejakčnější část
každé sekce a zůstávají u názvů.

**EasyAdmin.** Aktuální je `easycorp/easyadmin-bundle` v5.5.1, PHP ≥ 8.2,
`symfony/framework-bundle ^6.4.33|^7.0|^8.0`, jen Doctrine ORM entity (ODM nepodporováno)
`[17]`, `[18]`. API v ukázce na `:99–113` (`AbstractCrudController`, `getEntityFqcn()`,
`configureFields()`) je v 5.x nadále platné – ukázka nepotřebuje opravu, jen zmínku o verzi.

**Sonata Admin.** FAQ na `:435` ji zmiňuje, tělo kapitoly ne. `sonata-project/admin-bundle`
4.43.0, PHP ^8.2, Symfony `^6.4 || ^7.3 || ^8.0` `[18]`.

**API Platform.** Kapitola nezmiňuje vůbec. Pro „CRUD s validací nad Doctrine" je to
v Symfony 8 nejpřímější cesta k API bez doménového modelu. `api-platform/core` stabilní
4.3.17, PHP ≥ 8.2, `symfony/http-kernel ^7.4 || ^8.0` `[18]`.

**MakerBundle.** `symfony/maker-bundle` v1.67.0, podporuje `symfony/* ^8.0` `[18]`. Past:
aktuální dokumentace dokumentuje jen pět příkazů (`make:command`, `make:controller`,
`make:entity`, `make:validator`, `make:voter`) a `make:crud` mezi nimi není `[19]`, ale
ve zdrojích `src/Maker/MakeCrud.php` existuje `[20]`. Pokud kapitola `make:crud` zmíní,
uvést, že je nedokumentovaný – ne že byl odstraněn.

**Data pipeline (22.05).** Kapitola doporučuje „obyčejné PHP objekty" a Messenger.
Pojmenovaná alternativa v ekosystému: `flow-php/etl` 0.43.0, PHP 8.3–8.5, DataFrame API nad
CSV/JSON/XML/Parquet/RDBMS `[21]`.

**Active Record v Symfony neexistuje.** Khononovova tabulka (`:355–359`) doporučuje pro
Supporting subdoménu Transaction Script nebo Active Record. Doctrine ORM 3.6 `[18]` je Data
Mapper; Active Record nemá v Symfony nativní podobu. Praktický ekvivalent je Doctrine entita
s veřejnými settery obsluhovaná tenkou servisní třídou, tedy vědomě anemický model. Kapitola
tuhle překladovou vrstvu neposkytuje a čtenář, který tabulku vezme doslova, narazí.

**Symfony 8.** Stabilní větev 8.1 (PHP ≥ 8.4), 7.4 LTS z listopadu 2025, 8.2 ve vývoji `[22]`.
Pro kapitolu bez verzově citlivého kódu bez důsledku, ale označení „Symfony 8" je platné.

## 5. Sporné a chybně podávané body

**Nákladový model X / 5–10X / 3–4X (`:379–381`).** Tři čísla nesou celý závěr sekce („migrace
je výhodná jen když přínos převýší trojnásobek ceny migrace") a nemají zdroj. Rešerše žádný
primární zdroj s těmito multiplikátory nenašla. Existující literatura o ceně přepisu je
kvalitativní: Spolsky `[16]` argumentuje ztrátou nashromážděných oprav, Fowler `[8]` podmínkou
vlastnictví rozhodnutí, Verraes `[14]` heuristikou repair/replace. Doporučení: čísla doložit,
nebo nahradit poměrem bez pseudopřesnosti a doplnit tři jmenované heuristiky. Studie
`migration_from_crud-studie.md` doporučuje sjednotit ekonomiku migrace do jedné sekce
a odkazovat sem – pokud čísla padnou zde, musí se to promítnout i tam.

**Hranice „jeden rok" (`:234–247`) versus design payoff line.** Kapitola tvrdí, že investice
do DDD se vrací po 6–12 měsících a pod rok se nevyplatí. Fowler `[6]` na téže otázce říká
opak – *„usually weeks not months"* – a dodává, že poloha té čáry je sporná i mezi lidmi,
kteří hypotézu přijímají. Rozdíl je částečně v předmětu: Fowler mluví o návrhu obecně,
kapitola o taktickém DDD. Ten rozdíl ale kapitola nedělá. Doporučení: přiznat odhad, ukotvit
ho ve Fowlerově hypotéze a rozlišit payoff line pro „nějaký návrh" od payoff line pro plné
taktické DDD.

**Rozhodovací strom vs. „většina podmínek" (`:30–38` vs. `:413–427`).** Strom (ověřeno ve
zdroji `templates/diagrams/9_when_not_to_use_ddd/plant.uml`) je řetěz pěti AND-hradel: každé
„ne" ukončuje větev doporučením DDD nepoužít. Sekce 22.10 naproti tomu říká *„Smysl má, když
platí většina z těchto podmínek."* Dvě neslučitelná rozhodovací pravidla pro pět téměř
identických kritérií. Čtenář, který splní čtyři z pěti, dostane z jedné části kapitoly „ano"
a z druhé „ne".

**Rozhodovací strom vs. sekce 22.09.** Strom začíná uzlem „Nový projekt / architektonické
rozhodnutí" a končí uzlem „DDD dává smysl" – je tedy celoprojektový. Sekce 22.09 tvrdí, že
*„Volba ,celé DDD ano, nebo celé ne' málokdy odpovídá realitě projektu"*. Kapitola sama sebe
vyvrací a nikde to nekomentuje.

**Výklad definice agregátu (`:224–225`).** Citace *„cluster of associated objects that we
treat as a unit for the purpose of data changes"* pochází z Evansovy knihy z roku 2003;
v *DDD Reference* (2015) `[1]` v této podobě není (ověřeno grepem celého PDF). Citace samotná
je v pořádku. Sporný je autorský výklad *„podstatné slovní spojení je ,data changes' s
doménovými pravidly, nikoli ,data transfer'"* – Evans o „data transfer" nepíše nic. Silnější
a doložitelná opora pro tutéž myšlenku je Reference: *„Define properties and invariants for
the aggregate as a whole and give enforcement responsibility to the root"*.

**Vernon a „strategická hodnota" (`:248–249`).** V ověřené kapitole 1 *DDD Distilled* `[4]`
ani v rejstříku knihy toto tvrzení není. Nejbližší doložitelné je Vernonova opačně orientovaná
teze o tom, že alternativou dobrého návrhu je špatný návrh, ne žádný. Buď atribuci doložit
z kapitoly 3 (*Dealing with Complexity*), nebo ji nahradit.

**„Over-engineering CRUDu je jiný problém než anemický model" (`:124–128`).** Terminologicky
správné. Ale `:407–409` popisuje pseudo-DDD slovy *„má všechny náklady DDD… a žádný přínos"* –
doslova Fowlerovou definicí problému anemického modelu `[11]`. Dvě místa téže kapitoly
používají tentýž argument, jednou s odmítnutím Fowlerova pojmu a jednou s jeho nepřiznaným
převzetím. Rozlišení v `:124–128` ponechat, v 22.09 Fowlera přiznat.

**Telco příklad (`:388–390`).** Tvrzení o telcu, které tři roky migrovalo na DDD a platformu
pak při akvizici nahradilo, se z veřejně dostupných Khononovových textů ověřit nepodařilo.
Patří do neověřených, dokud ho autor nedohledá v *Learning DDD*.

**Chybějící protipól: kdy „ne DDD" znamená „nic".** Vernon `[4]` varuje před „imagined economy
of No Design". Kapitola opakovaně nabízí „Flat MVC" a „prostý controller + Doctrine", ale
nikde neřekne, že i ty se navrhují (moduly, hranice, pojmenování). Fowler `[8]` to formuluje
přesně: *„Knowing your architecture is sacrificial doesn't mean abandoning the internal
quality of the software. Good modularity is a vital part of a healthy code base."* Bez téhle
věty se dá kapitola číst jako povolení k nepořádku.


## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | nepodložené | `:379–381` | Nákladový model X / 5–10X / 3–4X bez zdroje; nese celý závěr sekce | Doložit, nebo nahradit kvalitativním modelem opřeným o `[8]`, `[14]`, `[16]` |
| G2 | sporné | `:243–247` | „6–12 měsíců / jeden rok" v přímém rozporu s Fowlerovou design payoff line („weeks not months") `[6]` | Přiznat odhad, ukotvit ve Fowlerovi, rozlišit payoff line návrhu obecně a taktického DDD |
| G3 | sporné | `:30–38` vs. `:413–427` | Strom vyžaduje všech 5 kritérií, sekce 22.10 „většinu" z 5 téměř identických | Sjednotit na jedno pravidlo; strom předělat na skóre nebo na per-subdoménovou větev |
| G4 | sporné | `:30–38` vs. `:350–353` | Strom je celoprojektový, sekce 22.09 tvrdí, že celoprojektové rozhodnutí neodpovídá realitě | Strom předělat: vstupem je subdoména, ne projekt |
| G5 | chybí | celá kapitola | Evansovy vzory `Big Ball of Mud` a `Separate Ways` `[1]` – jeho vlastní pojmenovaná odpověď na „kde uvnitř systému nemodelovat" | Doplnit odstavec + odkaz na `/context-mapping#big-ball-of-mud` a `#separate-ways` |
| G6 | chybí | `:325`, `:347` | Evansův **Bubble Context** `[2]` – pojmenovaný postup pro „zkuste DDD v části systému"; slovo „bubble" v knize není ani jednou | Pojmenovat vzor a odkázat; případně nechat detail kapitole 18 |
| G7 | mělké | `:141–145` | Rozdělení strategické vs. taktické DDD je nejsilnější myšlenka kapitoly, ale žije jen v sekci o startupech | Povýšit na princip celé kapitoly (samostatná sekce hned za úvodem) |
| G8 | chybí | `:350–411` | Khononovovy heuristiky pro posouzení složitosti domény (CRUD termíny / validace / algoritmy / invarianty / cyklomatická složitost) `[5]` | Doplnit jako rozhodovací checklist – kapitola nemá jiný operativní nástroj než strom |
| G9 | chybí | `:355–359` | Tabulka doporučuje Active Record, který v Symfony/Doctrine nemá podobu | Doplnit sloupec „jak to vypadá v Symfony" nebo poznámku o Data Mapperu |
| G10 | nepodložené | `:48–51` | Evans „ve svých přednáškách" – neidentifikovatelná atribuce | Nahradit citovatelným místem: `Core Domain` v *DDD Reference* `[1]` |
| G11 | nepodložené | `:248–249` | Vernon a „strategická hodnota" se v ověřené kapitole 1 ani rejstříku *Distilled* nenachází | Doložit z kapitoly 3, nebo nahradit |
| G12 | nepodložené | `:388–390` | Telco příklad připsaný Khononovovi; z veřejných zdrojů neověřitelný | Dohledat v *Learning DDD*, nebo označit jako ilustrativní scénář |
| G13 | sporné | `:224–225` | Výklad „data changes vs. data transfer" je autorský, ne Evansův | Nahradit argumentem o invariantech, doložitelným z Reference `[1]` |
| G14 | chybí | `:46`, `:407–409` | Fowlerova věta *„all of the costs… without any of the benefits"* `[11]` je přesně argument kapitoly a není citovaná | Citovat Fowlera v pasáži o pseudo-DDD |
| G15 | chybí | `:189–195`, `:251–256` | Chybí protipól „ne DDD ≠ ne návrh" (Vernon `[4]`, Fowler `[8]`) | Jeden odstavec, ideálně v 22.10 nebo v závěru |
| G16 | mělké | `:216–232` | Sekce o ETL doporučuje „obyčejné PHP objekty"; ekosystém má `flow-php/etl` `[21]` | Doplnit konkrétní nástroj |
| G17 | mělké | `:117–129` | Alternativy zmiňují EasyAdmin; API Platform (CRUD API bez doménového modelu) v kapitole není | Doplnit API Platform `[18]` do alternativ |
| G18 | nadbytečné | `:329–348` vs. `:197–214` | Sekce 22.08 a 22.04 se překrývají natolik, že kapitola musí dvakrát vysvětlovat, čím se liší (`:206–208`, `:334–337`) | Sloučit do jedné sekce „doménová znalost není dostupná" se dvěma variantami |
| G19 | chybí | `:444–468` | Zdroje neuvádějí Khononova, přestože sekce 22.09 stojí celá na něm | Doplnit *Learning DDD* (2021) a Fowlerovy Design Stamina / Yagni |
| G20 | chybí | `:350–353` | Kapitola nikde neodkazuje na `/subdomeny`, ačkoli 22.09 pracuje s klasifikací subdomén; zpětný odkaz existuje jen z `subdomains.md:151` | Doplnit odkaz na `/subdomeny#tri-kategorie` a na `/architektonicke-styly` |
| G21 | mělké | `:35–38` | Text říká „pět otázek", jejich znění je jen v SVG; obsah diagramu není v textu | Vypsat pět bran v textu, aby kapitola fungovala i bez obrázku |

## 7. Doporučení k přepisu

**P1-1 — Sjednotit rozhodovací pravidlo a předělat rozhodovací strom na per-subdoménový.**
Kapitola dnes obsahuje tři neslučitelná rozhodovací pravidla: AND-řetěz v diagramu, „většinu
z pěti podmínek" v 22.10 a tabulku podle typu subdomény v 22.09. Čtenář, který kapitolu čte
kvůli rozhodnutí, odejde bez rozhodnutí. Nový strom má začínat výběrem subdomény, ne projektu,
a končit jedním ze tří výstupů: plné taktické DDD / lehký model / CRUD nebo koupené řešení.
Řeší G3, G4, G21. Odhad: přepis sekce 22.01 (~30 řádků) + nový `.puml` a SVG.

**P1-2 — Doložit nebo přepsat nákladový model v `:379–381`.** Tři multiplikátory bez zdroje
jsou v kapitole, kterou zbytek knihy cituje jako autoritu na ekonomiku rozhodnutí. Pokud zdroj
neexistuje, nahradit je kvalitativním rámcem: Verraesova repair/replace heuristika `[14]`,
Fowlerova podmínka „tým, který kód psal, rozhoduje o jeho obětování" `[8]`, Spolskyho argument
o ztracených opravách `[16]`. Řeší G1, částečně G12. Odhad: přepis calloutu (~20 řádků).

**P1-3 — Opravit tři atribuce.** Evans „ve svých přednáškách" (`:48`) → `Core Domain`
v *DDD Reference* `[1]` s doslovnou větou o „harsh reality". Vernon a „strategická hodnota"
(`:248`) → doložit z *Distilled* kap. 3 nebo nahradit. Výklad „data changes vs. data transfer"
(`:224`) → argument o invariantech. Řeší G10, G11, G13. Odhad: tři pasáže, ~15 řádků.

**P2-1 — Povýšit rozdělení strategické / taktické DDD na princip celé kapitoly.** Dnes se
objevuje jednou, v sekci o startupech. Přitom je to odpověď, kterou dávají všechny primární
zdroje shodně: Evans `[3]`, Verraes `[13]`, Evansův Bubble Context `[2]`. Bez ní kapitola
v sedmi ze sedmi situací doporučuje zahodit i Ubiquitous Language a hranice, což nikdo
z citovaných autorů netvrdí. Řeší G7. Odhad: nová sekce za úvodem ~25 řádků + úprava
formulací v 22.02–22.08.

**P2-2 — Doplnit rozhodovací checklist na posouzení složitosti domény.** Kapitola nemá
nástroj, kterým by čtenář změřil vlastní projekt. Khononovových pět otázek `[5]` je
otestovaných, krátkých a přeložitelných na příklad z knihy (`Order`, `Underwriting`).
Umístit do 22.09 nebo těsně před 22.10. Řeší G8. Odhad: nová podsekce ~30 řádků.

**P2-3 — Doplnit Evansovy vzory pro „kde nemodelovat".** `Big Ball of Mud` a `Separate Ways`
`[1]` jsou Evansova pojmenovaná odpověď na otázku kapitoly a kniha je má zpracované
v `context_mapping.md:747` a `:827`. Bubble Context `[2]` je pojmenovaná podoba doporučení,
které kapitola dává vlastními slovy na `:325`. Jde o pojmenování a prolinkování, ne o nový
výklad. Řeší G5, G6, G20. Odhad: dva odstavce (~20 řádků) + tři interní odkazy.

**P2-4 — Doplnit protipól „ne DDD neznamená ne návrh".** Kapitola sedmkrát nabízí jednodušší
architekturu a ani jednou neřekne, že i ta se navrhuje. Vernonova věta o „imagined economy of
No Design" `[4]` a Fowlerova o modularitě obětované architektury `[8]` to pokrývají. Bez toho
lze kapitolu číst jako povolení k nepořádku – a je to kapitola, na kterou předmluva odkazuje
čtenáře, který ještě nic o DDD nepřečetl. Řeší G15. Odhad: odstavec v 22.10 (~10 řádků).

**P2-5 — Zkonkrétnit alternativy o skutečné balíčky.** API Platform 4 pro CRUD API `[18]`,
`flow-php/etl` pro ETL `[21]`, poznámka že EasyAdmin je verze 5 a podporuje Symfony 8 `[17]`.
V knize o Symfony je „servisní vrstva s obyčejnými PHP objekty" nedostatečná odpověď.
Řeší G16, G17. Odhad: úpravy tří calloutů „Doporučené alternativy", ~15 řádků.

**P3-1 — Sloučit sekce 22.04 a 22.08.** Kapitola musí dvakrát vysvětlovat, čím se od sebe
liší (`:206–208`, `:334–337`), což je signál, že jde o jednu situaci se dvěma variantami.
Sloučením vznikne místo pro P2-2 bez nárůstu délky. Sníží počet situací na šest, což znamená
úpravu titulku, decku, FAQ i `Chapters.php`. Řeší G18. Odhad: ~35 řádků.

**P3-2 — Doplnit sloupec „jak to vypadá v Symfony" do tabulky typů subdomén.** Doporučení
Active Record pro Supporting je v Doctrine ORM 3 (Data Mapper) neproveditelné doslova.
Řeší G9. Odhad: rozšíření tabulky `:355–359`, ~5 řádků.

**P3-3 — Doplnit zdroje.** Khononov *Learning DDD* (2021), Fowler *Design Stamina Hypothesis*
a *Yagni*, Evansův legacy paper. Řeší G19. Odhad: ~8 řádků v 22.11.


## 8. Otevřené otázky pro autora

1. **Odkud jsou čísla X / 5–10X / 3–4X?** Pokud existuje interní zdroj nebo konkrétní
   projekt, dá se to označit jako „ilustrativní scénář" podle konvence knihy. Pokud ne,
   P1-2 znamená přepis calloutu a promítnutí do studie kapitoly 18.
2. **Telco příklad z `:388–390` – je v *Learning DDD*?** Autor má knihu; rešerše ho z
   veřejných zdrojů nedohledala.
3. **Má kapitola zůstat na 468 řádcích?** Je to nejkratší kapitola v Praxi a nejvíce
   odkazovaný rozhodovací uzel knihy. P1 + P2 doporučení ji dostanou zhruba na 600–650.
   Alternativa: přijmout, že stručnost je součástí tónu (Vzor B), a omezit se na P1 + P2-1.
   Rozhodnutí patří autorovi, protože jde o referenční vzor tónu pro celou knihu.
4. **Sloučit 22.04 a 22.08?** Sníží počet situací ze sedmi na šest. „Sedm situací" je
   v titulku, decku, meta description i v `Chapters.php`. Změna se dotkne pěti míst.
5. **Kolik prostoru dostane ekonomika migrace zde vs. v kapitole 18?** Dnes je model
   v kapitole 22 a kapitola 18 o něm neví. Buď zůstane zde a 18 odkáže, nebo se přesune
   do 18 a zde zbude odstavec. Rozhodnout před přepisem obou.
6. **Zůstane rozhodovací strom obrázkem?** Pokud ano, jeho obsah patří i do textu (G21):
   kapitola je dnes bez SVG nečitelná v části, která má být její nejužitečnější.

## 9. Bibliografie

Vše získáno přímým `curl` (WebSearch v této session nedostupný); PDF převedena `pdftotext`,
HTML strojově zbaveno značek. Datum přístupu u všech webových zdrojů: 2026-09-04.

### Ověřené zdroje

`[1]` Eric Evans — *Domain-Driven Design Reference*, 2015. PDF: domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf — citace ověřeny grepem plného textu (Core Domain, Generic Subdomains, Separate Ways, Big Ball of Mud, Aggregates, úvodní definice DDD).

`[2]` Eric Evans — *Getting Started with DDD When Surrounded by Legacy Systems*, 2013. PDF: domainlanguage.com/wp-content/uploads/2016/04/GettingStartedWithDDDWhenSurroundedByLegacySystemsV1.pdf — ověřeny čtyři strategie a citace o legacy replacement.

`[3]` Eric Evans — *What I've learned about DDD since the book* (QCon London 2009). dddcommunity.org/library/evans_2009_1/ — ověřen abstrakt, nikoli obsah videozáznamu.

`[4]` Vaughn Vernon — *Domain-Driven Design Distilled*, Addison-Wesley, 2016, ISBN 978-0-134-43442-1. Kapitola 1 a rejstřík z oficiálních sample pages: informit.com/content/images/9780134434421/samplepages/9780134434421.pdf

`[5]` Vlad Khononov — *Revisiting the Basics of Domain-Driven Design*, 2018. vladikk.com/2018/01/26/revisiting-the-basics-of-ddd/

`[6]` Martin Fowler — *Design Stamina Hypothesis*, 2007. martinfowler.com/bliki/DesignStaminaHypothesis.html
`[7]` Martin Fowler — *Yagni*, 2015. martinfowler.com/bliki/Yagni.html
`[8]` Martin Fowler — *Sacrificial Architecture*, 2014. martinfowler.com/bliki/SacrificialArchitecture.html
`[9]` Martin Fowler — *Monolith First*, 2015. martinfowler.com/bliki/MonolithFirst.html
`[10]` Martin Fowler — *Microservice Premium*, 2015. martinfowler.com/bliki/MicroservicePremium.html
`[11]` Martin Fowler — *Anemic Domain Model*, 2003. martinfowler.com/bliki/AnemicDomainModel.html

`[12]` Martin Fowler — *Transaction Script* (P of EAA catalog), 2003. martinfowler.com/eaaCatalog/transactionScript.html — pozn.: `bliki/TransactionScript.html` vrací HTTP 404.

`[13]` Mathias Verraes — *What is Domain-Driven Design (DDD)*, 2021. verraes.net/2021/09/what-is-domain-driven-design-ddd/

`[14]` Mathias Verraes — *The Repair/Replace Heuristic for Legacy Software*, 2016. verraes.net/2016/04/repair-replace-heuristic-for-legacy-software/

`[15]` Vladimir Khorikov — *Domain-centric vs data-centric approaches to software development*, 2015. enterprisecraftsmanship.com/posts/domain-centric-vs-data-centric-approaches/

`[16]` Joel Spolsky — *Things You Should Never Do, Part I*, 2000. joelonsoftware.com/2000/04/06/things-you-should-never-do-part-i/

`[17]` EasyAdmin dokumentace (5.x, „current"). symfony.com/bundles/EasyAdminBundle/current/index.html — ověřeny požadavky PHP 8.2+, Symfony 6.4+, jen Doctrine ORM.

`[18]` Packagist metadata (`repo.packagist.org/p2/…`), JSON: `easycorp/easyadmin-bundle` v5.5.1; `sonata-project/admin-bundle` 4.43.0; `api-platform/core` stabilní 4.3.17 (5.0 v alfě); `symfony/maker-bundle` v1.67.0; `doctrine/orm` 3.6.8; `symfony/messenger` v8.1.6.

`[19]` SymfonyMakerBundle dokumentace („current"). symfony.com/bundles/SymfonyMakerBundle/current/index.html — dokumentuje jen `make:command`, `make:controller`, `make:entity`, `make:validator`, `make:voter`.

`[20]` symfony/maker-bundle, `src/Maker` na větvi `main` (GitHub API) — ověřena existence `MakeCrud.php`.

`[21]` Flow PHP — `flow-php/etl` 0.43.0, PHP 8.3–8.5. flow-php.com

`[22]` Symfony — přehled vydání: 8.1 stabilní (PHP ≥ 8.4), 7.4 LTS z listopadu 2025, 8.2 ve vývoji. symfony.com/releases

`[23]` Interní: `templates/diagrams/9_when_not_to_use_ddd/plant.uml` — zdroj rozhodovacího stromu; potvrzuje AND-strukturu pěti bran a celoprojektový vstupní uzel.

`[24]` Interní: `docs/studie/migration_from_crud-studie.md`, `docs/studie/subdomains-studie.md`.

### Neověřené / nedohledané

- **Multiplikátory X / 5–10X / 3–4X** (`when_not_to_use_ddd.md:379–381`). Žádný primární
  zdroj s těmito čísly nenalezen. Nutné dohledat ručně, nebo označit jako autorský odhad.
- **Telco příklad připsaný Khononovovi** (`:388–390`). Ve veřejně dostupných textech
  (vladikk.com, ukázkové kapitoly) není. Nutné ověřit přímo v *Learning DDD* (2021).
- **Vernonova formulace o „strategické hodnotě“ (`:248–249`) – OVĚŘENO 2026-09-04 proti plnému
  textu *DDD Distilled* (vlastní výtisk). Formulace tam není a Vernonův argument míří jinam.**
  Řetězec „strategic value“ se v knize nevyskytuje **ani jednou**. Co Vernon skutečně píše:

  > *„DDD is a set of tools that assist you in designing and implementing software that delivers
  > high value, both strategically and tactically. Your organization can’t be the best at
  > everything, so it had better choose carefully at what it must excel. […] Your organization
  > will benefit most from software models that explicitly reflect its core competencies.“*

  To je argument o tom, **kam uvnitř systému investovat modelovací úsilí** – organizace nemůže
  vynikat ve všem, tak ať vybere Core Domain a tam soustředí síly. Kapitola z toho ale dělá
  kritérium, **zda vůbec DDD nasadit** („pokud je strategická hodnota nízká, DDD se nevyplatí“).
  To Vernon neříká; jeho věta předpokládá, že DDD už používáte, a radí, kde ho použít naplno.

  **Doporučení: citaci přepsat.** Vernonovu větu lze v kapitole použít, ale jako argument pro
  diferencovanou investici, ne pro odmítnutí DDD. Kritérium „kdy DDD nenasazovat“ je nutné opřít
  o něco jiného, nebo je podat jako autorské.
- **Obsah Evansova QCon 2009 vystoupení.** Ověřen jen abstrakt; veřejný přepis neexistuje.
  Pokud má kapitola citovat Evansovy „přednášky", je nutné citovat *DDD Reference*.
- **Evansova věta „Not all of a large system will be well designed“ – OVĚŘENO 2026-09-04 proti
  tištěné knize (vlastní výtisk). V této podobě v knize není.** Skutečné znění z roku 2003:

  > *„The harsh reality is that not all parts of the design are going to be equally refined.
  > Priorities must be set. To make the domain model an asset, the model’s critical core has to be
  > sleek and fully leveraged to create application functionality. But scarce, highly skilled
  > developers tend to gravitate to technical infrastructure or neatly definable domain problems
  > that can be understood without specialized domain knowledge.“*

  Evans pokračuje pozorováním, že specializované jádro – ta část modelu, která aplikaci odlišuje
  a dělá z ní obchodní aktivum – pak obvykle skládají méně zkušení vývojáři feature po feature,
  bez využití konceptuální síly modelu.

  Pro úplnost: *DDD Reference* (2015) má tutéž větu s drobně jiným úvodem („It is harsh reality
  that…“). Rozdíl je zanedbatelný, ale při citaci je třeba vybrat jeden zdroj.

  **Doporučení: citovat doslovné znění z knihy 2003 a zachovat pokračování o prioritách.** Věta
  samotná zní jako rezignace; teprve s druhou půlkou je z ní argument pro soustředění na jádro,
  což je přesně to, co kapitola potřebuje.
- **Khononovova kapitola o architektuře per subdoménu v *Learning DDD* (2021).** Ověřena je
  jen její blogová předloha z roku 2018 `[5]`. Formulace v knize se může lišit.
- **Doctrine ORM 3 a Active Record – OVĚŘENO 2026-09-04, původní závěr potvrzen.** Stránka
  *Architecture* v dokumentaci ORM `current` ani jinde vzory jménem neporovnává; nejblíž je věta
  „Doctrine ORM aims to simplify the translation between database rows and the PHP object model.“
  Explicitní vymezení vůči Active Record v oficiální dokumentaci **není**. **Doporučení: tvrzení
  ponechat jako věcné (Doctrine je Data Mapper a Active Record nenabízí), ale neopírat je
  o citaci dokumentace, protože ta citace neexistuje.**
