# Studie: Předmluva

- **Kapitola:** `content/chapters/preface.md` (č. 00, kategorie Úvod, 202 řádků)
- **Cesta:** /predmluva
- **Typ kapitoly:** narativní
- **Datum studie:** 2026-09-03

Poznámka k záběru: předmluva není kapitola o jednom DDD vzoru. Rešerše proto míří na dvě
osy. První je žánrová: jak funkci předmluvy plní referenční knihy o DDD a jaká je zavedená
struktura front matter u odborných titulů. Druhá je verifikační: zda tvrzení předmluvy sedí
s tím, co kniha na těchto 26 souborech skutečně obsahuje. Druhá osa přinesla většinu nálezů.

## 1. Mapa současné kapitoly

| Sekce | Rozsah | Co tvrdí | Zdroje | Poznámka |
|---|---|---|---|---|
| Úvodní bloky (bez nadpisu) | ř. 21–23 | Evans 2003 má 560 stran teorie, mezi ním a PHP projektem leží vrstva implementačních detailů; Vernon ji zaplnil pro Javu a C#; pro PHP/Symfony podobná kniha nebyla | Evans 2003, Vernon 2013 – jmenovitě, bez URL | Klíčové poziční tvrzení knihy; viz G1, G2 |
| P.01 Pro koho je tato kniha | ř. 25–37 | Předpokládá PHP/Symfony/OOP, nepředpokládá DDD; pět rolí čtenáře | žádné | Role jsou dobře odlišené, tech lead nese sporný slib (G6) |
| P.01 → Co tato kniha není | ř. 39–44 | Čtyři negativní vymezení (ne úvod do PHP, ne kuchařka, ne kompletní reference, ne návod na management) | odkaz na symfony.com/doc, /zdroje | Bod 4 koliduje s popisem role tech leada |
| P.01 → Předpoklady | ř. 46–56 | PHP 8.1+, Symfony 6+, OOP, designové vzory, relační DB; nejnáročnější jsou ES, Ságy, microservices | žádné | Property hooks se v knize nevyskytují (G4); „Symfony 6+" vs. cílení na Symfony 8 (G20) |
| P.02 Co kniha pokrývá | ř. 58–102 | Osm částí, mapa kapitol 1–24, poznámka o webové kapitole mimo hlavní řadu | Brandolini, Skelton & Pais 2019 | Číslování částí odpovídá `Chapters::all()`; obsah dvou částí neodpovídá (G5, G18) |
| P.03 Jak číst tuto knihu | ř. 104–156 | Pět čtecích cest podle role, s odkazy na konkrétní kapitoly | 20 interních odkazů | Všech 20 cílů existuje; tatáž funkce je ještě na dvou dalších místech webu (G7) |
| P.04 Konvence v knize | ř. 158–194 | Hlas, styl kódu, callouty, diagramy, vnitřní odkazy, citace + seznam sedmi hlavních zdrojů | 7 bibliografických záznamů | Chybí FAQ bloky, kanonické API ukázek, obtížnost/doba čtení (G8, G10) |
| P.05 Co dál | ř. 196–202 | Kudy pokračovat, cheat sheet, glosář, živý dokument s erraty | ddd-v-symfony.cz | Errata a komentáře čtenářů v repozitáři neexistují (G11) |

Charakter kapitoly. Předmluva je nejsilnější v P.03: pět čtecích cest je konkrétních,
odkazy vedou na existující cesty a cesty se navzájem neduplikují. Slabší je P.02, která
mapuje obsah knihy z paměti a na třech místech se rozchází se skutečným obsahem kapitol.
P.04 popisuje formální konvence sazby, ale mlčí o konvencích, které čtenář v kódu potká
hned v kapitole 6 – o `AggregateRoot`, o pojmenování událostí, o `public readonly` VO.
Nejslabší je otevírací blok: nese hlavní poziční tvrzení knihy a je zároveň jediné
tvrzení, které rešerše vyvrací. Kapitola nepoužívá žádný z vlastních blokových prvků
knihy (`grep -c ':::'` = 0) a je jediná z 26 kapitol bez FAQ bloku.

## 2. Kanonické zdroje k tématu

Referenční knihy o DDD řeší v předmluvě čtyři funkce: motivaci autora, cílovou skupinu
s předpoklady, navigaci obsahem a konvence.

**Evans 2003.** Kniha vyšla 20. 8. 2003 u Addison-Wesley, má 560 stran, ISBN 0-321-12521-5
[1]. Front matter tvoří Foreword, Preface, Acknowledgments. Předmluva sama definuje tři
věci: proč jsou modely v srdci softwaru, jak číst pattern language knihy a pro koho je
určená. Údaj „560 stran" v předmluvě naší knihy je tedy věcně správný; sporné je jen
označení „teorie" (viz sekce 5).

**Evans, *DDD Reference* (2015).** Referenční PDF na domainlanguage.com nese v názvu
souboru datum vydání `DDD_Reference_2015-03.pdf` a licenci Creative Commons Attribution 4.0
[2]. Prodejní záznamy uvádějí u tištěného vydání Dog Ear Publishing rok 2014 [3]. Konvence
knihy (rok 2015, viz `CLAUDE.md`) odpovídá primárnímu zdroji a není třeba ji měnit.

**Vernon 2013.** *Implementing Domain-Driven Design* obsahuje sekci „Guide to This Book",
která čtenáři explicitně vysvětluje, že DDD je pattern language – vzory se odkazují
navzájem, takže čtenář narazí na vzor dřív, než ho kniha probere, a má se s tím počítat
[4]. To je funkce, kterou předmluva naší knihy neplní: čtecí cesty říkají *pořadí*, ale
nikoli *co dělat, když v kapitole 7 narazí na CQRS z kapitoly 12*.

**Khononov 2021.** Předmluva *Learning Domain-Driven Design* má pět pojmenovaných sekcí:
Why I Wrote This Book, Who Should Read This Book, Navigating the Book, Example Domain
(WolfDesk) a Conventions Used in This Book [5]. Struktura je zhruba stejná jako P.01–P.04,
s jedním rozdílem: Khononov má sekci „Why I Wrote This Book" a sekci s průběžnou příkladovou
doménou. Naše předmluva nemá ani jedno – motivace je zkrácená na dva odstavce bez nadpisu
a kanonický příklad (`Order`, `Email`, `Money`) není nikde představen, přestože se táhne
celou knihou.

**Millett & Tune 2015.** *Patterns, Principles and Practices of DDD* (Wrox, 4. 5. 2015)
deklaruje v úvodu záměr podat filozofii DDD prakticky pro zkušené vývojáře stavějící
aplikace pro komplexní domény [6]. Vymezení cílové skupiny přes *zkušenost s komplexitou*,
ne přes seniority titul, je použitelný protipříklad k pětiroli v P.01.

**Standardní struktura front matter (O'Reilly).** Publikační praxe O'Reilly ustálila sekce
„Who Should Read This Book", „Conventions Used in This Book" a „Using Code Examples" [7][8].
Poslední z nich – licenční a praktická poznámka o převzetí kódu – v naší předmluvě chybí,
přestože kniha na řádku 23 explicitně slibuje ukázky „které můžete převzít do svého projektu".

**Evansova vlastní korekce.** Na QCon London 2009 Evans uvedl, že stavební bloky
(value objects, entity, factories, repositories, services) jsou v knize přeceněné, že
agregát je třeba chápat jako hranici konzistence pro transakce, distribuci a souběžnost,
a že context boundaries a core domain měly přijít mnohem dřív [9]. Na DDD Europe 2016
zopakoval kritiku „over-emphasis on building blocks" [10]. Tato korekce je přímé
zdůvodnění pořadí, které naše kniha zvolila (strategie 1–5 před taktikou 6–9), a předmluva
ho nikde neuvádí.

## 3. Stav praxe a posuny

**Předmluvy se čtou selektivně a čtecí cesty se staly standardem.** Vernon i Khononov mají
sekci navigace obsahem; Richards & Ford mají v úvodu „Roadmap" [11]. Naše P.03 jde dál –
role-based cesty jsou konkrétnější než roadmapa. Posun proti stavu praxe je tedy pozitivní
a stojí za to ho udržet jako odlišující prvek, ne ho rozmělnit do tří kopií.

**Literatura o DDD v PHP mezitím vznikla.** *Domain-Driven Design in PHP* (Buenosvinos,
Soronellas, Akbary) vyšlo u Packt v červnu 2017 [12] a druhé vydání žije na Leanpubu
s poslední aktualizací 6. 4. 2026 [13]. Matthias Noback vydal *Advanced Web Application
Architecture* 2. 7. 2020 [14] a *Object Design Style Guide* u Manningu v prosinci 2019 [15];
obě knihy řeší přesně tu vrstvu mezi Evansem a rámcem, kterou předmluva označuje za
nezaplněnou. Web knihy si toho je vědom: `templates/ddd/resources.html.twig:280` a `:331`
odkazují na DDD in PHP jako doporučený zdroj. Předmluva a stránka zdrojů si tedy protiřečí.

**Referenční tituly stárnou rychleji než dřív.** *Team Topologies* má od 23. 9. 2025 druhé
vydání s novým forewordem, přílohou případových studií a afterwordem [16]. Khononov vydal
26. 9. 2024 *Balancing Coupling in Software Design* v Addison-Wesley Signature Series [17],
což je dnes relevantní zdroj pro kapitoly 3, 9 a 19. Newman má druhé vydání *Building
Microservices* z roku 2021 [18], Richardson *Microservices Patterns* z 19. 11. 2018 a
u Manningu rozpracované druhé vydání [19]. Seznam hlavních zdrojů v P.04 (ř. 188–194) je
tedy stav k roku 2021 a v jednom případě (Team Topologies 2019) už ukazuje na starší vydání.

**Konsenzus se posunul od taktiky ke strategii.** Od Evansovy korekce z let 2009 a 2016
[9][10] přes Khononova 2021 [5] je pořadí „nejdřív subdomény a hranice, pak agregáty"
většinové. Kniha ho drží, ale předmluva ho podává jako organizační rozhodnutí bez opory.
Jedna věta s odkazem na Evansovu vlastní revizi z toho udělá argument.

## 4. Symfony / PHP specifika

**Symfony 8.0** vyšlo v listopadu 2025 a vyžaduje PHP 8.4.0 nebo vyšší [20]. K datu této
studie je 8.0 už neudržované, doporučená větev je 8.1, nejnovější je 8.2 [20]. Pro předmluvu
z toho plyne konkrétní důsledek: věta „Symfony 6+" v Předpokladech (ř. 51) je nekonzistentní
s tvrzením „Kód cílí na PHP 8.4 a Symfony 8 s Doctrine ORM 3" (ř. 168). Čtenář na Symfony 6
nemůže ukázky spustit, protože Symfony 8 na PHP 8.1 vůbec nenaběhne. Sekce má odlišit
*předpokládanou znalost* od *runtime požadavku*.

**PHP 8.4** přineslo property hooks i asymetrickou viditelnost `public private(set)` [21].
Předmluva (ř. 50) uvádí obojí jako rysy, které „některé příklady používají". Grep přes
`content/chapters/*.md` najde asymetrickou viditelnost dvakrát – `aggregate_design.md:466`
(`public private(set) OrderStatus $status`) a odkaz na ni v `event_sourcing.md:671`.
Property hooks se v knize nevyskytují ani jednou. Buď se doplní příklad, nebo se tvrzení
opraví.

**Ověřování ukázek.** Repozitář má `scripts/lint-php-snippets.php`, který spouští `php -l`
nad každým PHP blokem v kapitolách, a CI job Lint & Tests ho volá. To je konkrétní věcný
argument o kvalitě ukázek, který patří do P.04 „Styl kódu" – a zároveň náhrada za chybějící
sekci typu „Using Code Examples" [7].

**Kanonické API ukázek.** `CLAUDE.md` fixuje konvence, se kterými čtenář pracuje od kapitoly
6 dál: bázová třída `AggregateRoot` s `record()` / `releaseEvents()`, události v minulém čase
bez sufixu `Event`, hodnotové objekty s `public readonly` vlastnostmi (`$email->value`),
`Money` s `amountInCents` a enum `Currency`, factory `Order::place()`, reference mezi
agregáty jen přes ID, ID přes `symfony/uid` `Uuid::v7()`, pojmenované doménové výjimky.
Předmluva o žádné z nich nemluví. Khononov na tomtéž místě představuje průběžnou doménu
WolfDesk [5]; ekvivalent zde je `Order` / `Email` / `Money`.

**Volba mapování.** P.04 (ř. 170) správně popisuje pragmatickou volbu (atributy Doctrine
na doménových třídách) a odkazuje na `/implementace-v-symfony#persisted-object-pattern`.
Kotva existuje a je konzistentně používaná v `architectural_styles.md`, `performance_aspects.md`,
`migration_from_crud.md` a `ddd_pain_points.md`. Tento bod je v pořádku a je jedním z mála
míst, kde předmluva plní roli závazného rozhodnutí pro celou knihu.

## 5. Sporné a chybně podávané body

**„Pro PHP a Symfony zatím podobně systematická kniha nebyla" (ř. 21).** Proti stojí DDD
in PHP [12][13] a Nobackovy dvě knihy [14][15]. Obhajitelná verze tvrzení existuje a je
užší: DDD in PHP je z roku 2017, silné v taktických vzorech a hexagonální architektuře,
slabší ve strategickém designu, a nepracuje se Symfony 8, Doctrine ORM 3 ani s Messengerem
v dnešní podobě; Noback řeší architekturu aplikace, ne DDD jako celek; česky nevyšlo nic.
Doporučení pro knihu: tvrzení nezrušit, ale zúžit a jmenovat, vůči čemu se kniha vymezuje.
Zamlčet existující tituly a zároveň je doporučovat na `/zdroje` je horší varianta než je
zmínit.

**„560 stran teorie" (ř. 21).** Počet stran sedí [1], označení „teorie" je zjednodušení –
kapitola 7 Evansovy knihy je rozsáhlý implementační příklad a Part II obsahuje kód.
Obě strany: pro čtenáře je Evans prakticky nepoužitelný jako implementační manuál pro PHP,
což je jádro pravdy v té větě; proti mluví to, že Evans sám kód má. Doporučení: přeformulovat
na „560 stran, které kód v PHP nikdy neukážou", což je pravdivé i pro čtenáře přesvědčivé.

**Vernon „zaplnil mezeru pro Javu a C#" (ř. 21).** Přesné je, že *Implementing DDD* používá
příklady v Javě a odkazuje na C# verze; „zaplnil" je silné slovo vzhledem k tomu, že sám
Vernon vydal 2016 *Distilled* právě proto, že IDDD bylo pro mnoho čtenářů nepřístupné.
Nález nízké závažnosti, ale stojí za zmírnění.

**Kdo je „nejnáročnější" (ř. 56).** Předmluva jmenuje Event Sourcing, Ságy a microservices.
`Chapters::all()` má `lvl => 4` u osmi kapitol: 07, 13, 14, 15, 16, 19, 20, 24. Nejtěžší
kapitolou taktického designu je podle samotné P.02 (ř. 70) kapitola 7. Tvrzení předmluvy
si tedy odporuje s vlastním katalogem i s vlastní sekcí o dvě obrazovky výš.

**Errata a komentáře čtenářů (ř. 202).** V repozitáři není route ani šablona pro errata
(`grep -rn "errat" content/ templates/ src/` vrací jediný zásah – právě tuto větu) a nikde
není komentářový mechanismus. Tvrzení popisuje neexistující funkci webu.

**Tištěná a EPUB verze (ř. 102).** Poznámka tvrdí, že kapitola o AI v tištěné a EPUB verzi
není. V repozitáři není žádný build tištěné ani EPUB verze. Buď jde o plán, který se má
formulovat jako plán, nebo o tvrzení k odstranění.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|---|---|---|---|
| G1 | nepodložené | `preface.md:21` | „Pro PHP a Symfony zatím podobně systematická kniha nebyla" – vyvráceno DDD in PHP [12][13] a Nobackem [14][15]; web knihy DDD in PHP sám doporučuje (`resources.html.twig:280`) | Zúžit tvrzení, jmenovat existující tituly a rozdíl (Symfony 8, strategický design, čeština) |
| G2 | sporné | `preface.md:21` | „560 stran teorie" – počet stran správně [1], „teorie" zjednodušuje | Přeformulovat na „stran bez jediného řádku PHP" |
| G3 | chybí | `preface.md:21–23` | Chybí ekvivalent sekce „Why I Wrote This Book" [5] a odkaz na `/o-autorovi` (route existuje, `DddController.php:128`) | Doplnit odstavec o pozici autora a odkaz |
| G4 | nepodložené | `preface.md:50` | „property hooks" – v knize nejsou nikde; asymetrická viditelnost jen v `aggregate_design.md:466` a `event_sourcing.md:671` | Property hooks vyškrtnout nebo doplnit příklad |
| G5 | sporné | `preface.md:88` | Část 5 (kap. 16–17) přisuzuje „hot aggregates"; ty jsou v sekci 07.09 (`aggregate_design.md:681, 701`) | Opravit mapu obsahu |
| G6 | sporné | `preface.md:34` vs. `:44` | Role tech leada slibuje „argumenty pro management opřené o DORA metriky", „Co kniha není" to popírá; kniha argumenty má (`team_topologies.md:653, 747`) | Sjednotit: kniha argumenty dává, negarantuje výsledek jednání |
| G7 | nadbytečné | `preface.md:104–156` | Čtecí cesty existují třikrát: zde, `what_is_ddd.md:289` (01.12) a `cheat_sheet.html.twig:317` (cs.04), s odlišnými sadami rolí i kapitol | Určit jeden kanonický zdroj (P.03), ostatní dvě místa zkrátit na odkaz |
| G8 | chybí | `preface.md:158–194` | Konvence nezmiňují FAQ bloky (25 z 26 kapitol), sekce „Další četba" (9 kapitol), obtížnost a dobu čtení | Doplnit do P.04 |
| G9 | chybí | `preface.md:62–100` | P.02 jmenuje kapitoly, ale odkazuje jen dvě (`/kdy-nepouzivat-ddd`, `/ddd-a-umela-inteligence`); zbylých ~20 kapitol bez odkazu | Prolinkovat každou zmíněnou kapitolu |
| G10 | mělké | `preface.md:166–170` | „Styl kódu" nepředstavuje kanonické API ukázek z `CLAUDE.md` ani průběžný příklad `Order`/`Email`/`Money` | Nová podsekce s výčtem konvencí a jedním minimálním příkladem |
| G11 | nepodložené | `preface.md:202` | Errata a komentáře čtenářů na webu neexistují | Odstranit, nebo doplnit skutečnou stránku errat |
| G12 | nepodložené | `preface.md:102` | Tvrzení o tištěné a EPUB verzi bez existujícího buildu | Odstranit, nebo formulovat jako záměr |
| G13 | mělké | `preface.md:120–124` | Cesta pro seniora je „lineární čtení 1–24" plus rychlá varianta – nejchudší z pěti cest | Přepsat na výběr podle typu problému, ne podle seniority |
| G14 | sporné | `preface.md:56` | „Nejnáročnější jsou ES, Ságy a microservices" vs. `Chapters.php` (`lvl => 4` u osmi kapitol včetně 07) | Opřít větu o `difficulty` z katalogu, nebo vypustit výčet |
| G15 | chybí | `preface.md:60` | Pořadí částí (strategie před taktikou) není zdůvodněné, ačkoli má oporu v Evansově vlastní korekci [9][10] | Doplnit dvě věty s citací |
| G16 | nadbytečné | `preface.md:162, 166, 172, 176, 180, 184` | Šest H3 v P.04 bez `{#anchor}`, na rozdíl od zbytku kapitoly i knihy | Doplnit kotvy |
| G17 | mělké | `preface.md:39–44` | „Co kniha není" postrádá nejdůležitější vymezení: kniha nenahrazuje Evanse a Vernona a není referencí Doctrine ani Messengeru | Doplnit dvě položky |
| G18 | nepodložené | `preface.md:88` | „architektonické testy s Deptrac/PHPArkitect" – PHPArkitect je v `testing_ddd.md` jen v `meta_keywords`, v textu není | Uvést jen Deptrac, nebo doplnit PHPArkitect do kapitoly 17 |
| G19 | chybí | celá kapitola | Jediná z 26 kapitol bez FAQ bloku a bez jediného `:::` bloku | Doplnit krátký FAQ (schema.org FAQPage, konzistence webu) |
| G20 | sporné | `preface.md:51` vs. `:168` | „Symfony 6+" jako předpoklad vs. „kód cílí na Symfony 8"; Symfony 8.0 vyžaduje PHP 8.4 [20] | Rozlišit předpokládanou znalost a runtime požadavek |
| G21 | chybí | `preface.md:23, 166–170` | Slib „ukázky můžete převzít" bez poznámky o licenci a o tom, jak jsou ověřované (`scripts/lint-php-snippets.php`) | Doplnit ekvivalent „Using Code Examples" [7] |
| G22 | sporné | `preface.md:21, 202` | „Tato kniha vznikla z opakované zkušenosti" a „Připomínky vítám" vs. vlastní pravidlo v `preface.md:164` („Žádné osobní komentáře autora") | Buď pravidlo v P.04 zpřesnit (týká se výkladových pasáží), nebo obě věty odosobnit |

## 7. Doporučení k přepisu

**P1-1 — Přepsat poziční tvrzení v úvodním bloku.** Věta o neexistující systematické PHP
knize je nejexponovanější tvrzení celé knihy a je nepravdivá v současném znění. DDD in PHP
existuje, žije ve druhém vydání a stránka `/zdroje` ho sama doporučuje. Nové znění má
jmenovat, vůči čemu se kniha vymezuje, a rozdíl opřít o rok vydání, o poměr strategie
a taktiky a o cílený stack. Odhad: přepis dvou vět v ř. 21, plus jedna nová věta.

**P1-2 — Sjednotit tvrzení o verzích PHP a Symfony.** Předpoklady mluví o PHP 8.1+ a
Symfony 6+, styl kódu o PHP 8.4 a Symfony 8; Symfony 8.0 přitom PHP 8.4 vyžaduje [20].
Zároveň vypadnou property hooks, které se v knize nevyskytují. Bez opravy čtenář zjistí
nesoulad při prvním `composer create-project`. Odhad: přepis ř. 50–51 a jedné věty v ř. 168.

**P1-3 — Opravit mapu obsahu v P.02.** Hot aggregates patří do kapitoly 7, ne do části 5;
PHPArkitect v kapitole 17 není. Mapa obsahu je jediná věc, kvůli které čtenář předmluvu
reálně otevře podruhé, a musí odpovídat souborům. Odhad: oprava dvou vět (ř. 88).

**P1-4 — Odstranit rozpor u role tech leada.** Řádek 34 slibuje argumenty pro management
opřené o DORA metriky, řádek 44 tvrdí, že kniha návod na prosazení DDD u managementu není.
Kapitola 5 argumenty skutečně obsahuje (`team_topologies.md:653` a `:747`). Přesná formulace:
kniha dodává měřitelné argumenty, negarantuje výsledek jednání. Odhad: oprava dvou vět.

**P1-5 — Vyřešit tvrzení o erratech a o tištěné/EPUB verzi.** Obojí popisuje neexistující
artefakty. Buď vzniknou (stránka errat je levná, route + šablona), nebo obě věty odejdou.
Neopravená stojí důvěryhodnost celé předmluvy, protože jde o kontrolovatelná tvrzení.
Odhad: oprava dvou vět (ř. 102, ř. 202), případně nová stránka mimo tuto kapitolu.

**P1-6 — Rozhodnout jediné místo pro čtecí cesty.** Dnes jsou na třech místech s odlišným
obsahem (P.03, `what_is_ddd.md:289`, `cheat_sheet.html.twig:317`) a už se rozešly – junior
cesta v cheat sheetu obsahuje kapitolu 09, v předmluvě ne. Kanonické místo má být P.03;
kapitola 1 a cheat sheet mají odkazovat, ne kopírovat. Kapitola 1 na `/predmluva#jak-cist`
už odkazuje, ale zároveň seznam duplikuje. Odhad: přepis sekce 01.12 (zkrátit na ~8 řádků)
a sekce cs.04 v šabloně; P.03 zůstává.

**P2-1 — Prolinkovat kapitoly v P.02.** Sekce jmenuje zhruba dvacet kapitol a odkazuje dvě.
Předmluva je přirozený rozcestník a interní prolinkování je u tohoto webu uvedená ranková
páka. Odhad: doplnění ~20 odkazů, bez změny textu.

**P2-2 — Doplnit do P.04 kanonické konvence kódu.** Čtenář potká `AggregateRoot`,
`record()` / `releaseEvents()`, události v minulém čase bez sufixu, `public readonly` VO,
`Uuid::v7()` a `Order::place()` poprvé v kapitole 6 bez varování. Khononov na témž místě
představuje průběžnou doménu [5]. Odhad: nová podsekce ~20 řádků s jedním minimálním
příkladem agregátu.

**P2-3 — Doplnit chybějící konvence webu.** FAQ bloky (25 kapitol), sekce „Další četba"
(9 kapitol), obtížnost a doba čtení u každé kapitoly. Čtenář je vidí na každé stránce
a předmluva je nevysvětlí. Odhad: 6–8 řádků do P.04.

**P2-4 — Přidat sekci „Proč tato kniha vznikla" a odkaz na `/o-autorovi`.** Referenční
tituly tuto funkci mají (Khononov „Why I Wrote This Book"), naše předmluva ji má zkrácenou
do dvou vět bez nadpisu, přičemž stránka o autorovi na webu existuje a předmluva na ni
neodkazuje. Odhad: přepis úvodního bloku, ~10 řádků.

**P2-5 — Zdůvodnit pořadí částí Evansovou vlastní korekcí.** Evans na QCon 2009 řekl, že
stavební bloky přecenil a že context boundaries a core domain měly přijít dřív [9]; na
DDD Europe 2016 kritiku zopakoval [10]. Kniha to pořadím dodržuje, ale mlčí o důvodu.
Dvě věty proměňují organizační rozhodnutí v argument. Odhad: 3 řádky do P.02.

**P2-6 — Přepsat cestu pro seniora.** „Lineární čtení 1–24" není doporučení. Užitečnější
je vstup podle problému: velká služba bez hranic, pomalý onboarding, regrese napříč
featurami. Odhad: přepis ř. 120–124.

**P2-7 — Doplnit poznámku o ověřování a převzetí ukázek.** Kniha slibuje převzetí kódu
(ř. 23), ale neříká pod jakou licencí a jak jsou ukázky ověřené. Repozitář má
`scripts/lint-php-snippets.php` s CI kontrolou – to je konkrétní tvrzení, které
marketingový jazyk nepotřebuje. Odhad: 4 řádky do P.04.

**P3-1 — Doplnit kotvy k šesti H3 v P.04** (ř. 162, 166, 172, 176, 180, 184). Ostatní
nadpisy kapitoly kotvy mají a jiné kapitoly na konvence odkazují. Odhad: šest oprav.

**P3-2 — Doplnit FAQ blok.** Předmluva je jediná kapitola bez něj. Typické otázky:
musím číst lineárně, stačí mi Symfony 6, musím znát Evanse předem. Odhad: ~15 řádků.

**P3-3 — Rozšířit „Co kniha není" o dvě položky:** nenahrazuje Evanse a Vernona (což je
dnes v textu jen implicitně) a není referencí Doctrine ani Messengeru. Odhad: dva bullety.

**P3-4 — Aktualizovat seznam hlavních zdrojů v P.04.** *Team Topologies* má druhé vydání
z 23. 9. 2025 [16]; Khononovo *Balancing Coupling* (2024) [17] je dnes relevantní pro
kapitoly 3, 9 a 19. Odhad: dvě položky seznamu.

**P3-5 — Vyřešit napětí mezi autorským hlasem a vlastním pravidlem.** P.04 zakazuje osobní
komentáře autora, přitom kapitola otevírá zkušenostní větou a končí „Připomínky vítám".
Buď se pravidlo zpřesní na výkladové pasáže, nebo se obě věty odosobní. Odhad: oprava
formulace v ř. 164 nebo v ř. 21 a 202.

## 8. Otevřené otázky pro autora

1. **Vymezení vůči DDD in PHP.** Má se předmluva vůči knize Buenosvinose a spol. vymezit
   jmenovitě, nebo jen obecně („existující PHP literatura je z doby před Symfony 6")?
   Jmenovité vymezení je poctivější a `/zdroje` ji stejně doporučuje.
2. **Kde žijí čtecí cesty.** P.03 je dnes nejlepší část kapitoly. Má zůstat kanonickým
   místem, nebo se má těžiště přesunout do cheat sheetu a předmluva má jen odkazovat?
3. **Tištěná a EPUB verze.** Je to plán, nebo pozůstatek? Odpověď rozhodne, zda poznámka
   u kapitoly o AI zůstane, změní formulaci, nebo odejde.
4. **Errata.** Vzniká stránka errat, nebo se věta z ř. 202 odstraní?
5. **Rozsah P.04.** Doplnění kanonických API ukázek, FAQ a poznámky o licenci kódu zvedne
   kapitolu zhruba na 260–280 řádků. Je to přijatelné pro předmluvu, nebo se má část
   konvencí přesunout do samostatné stránky (např. `/konvence`)?
6. **Pět rolí, nebo méně.** Cesta pro seniora se dnes od cesty „migrující z CRUD" liší
   málo. Má se ponechat pět rolí kvůli rozpoznatelnosti, nebo sloučit na čtyři a získat
   prostor pro hlubší popis?
7. **Průběžná doména.** Khononov má WolfDesk, kniha má rozptýlené `Order` / `Email` /
   `Money`. Má předmluva zavést jednu deklarovanou příkladovou doménu, nebo zůstat
   u ad-hoc příkladů podle kapitoly?

## 9. Bibliografie

### Ověřené zdroje

`[1]` Eric Evans — *Domain-Driven Design: Tackling Complexity in the Heart of Software*,
Addison-Wesley, 20. 8. 2003, 560 s., ISBN 978-0-321-12521-7.
https://www.informit.com/store/domain-driven-design-tackling-complexity-in-the-heart-9780321125217
(přístup 2026-09-03)

`[2]` Eric Evans — *Domain-Driven Design Reference: Definitions and Pattern Summaries*,
2015, CC BY 4.0. https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf
(přístup 2026-09-03)

`[3]` Prodejní záznam téhož titulu (Dog Ear Publishing, ISBN 978-1-4575-0119-7) s rokem 2014.
https://www.abebooks.com/9781457501197/Domain-Driven-Design-Reference-Definitions-Pattern-1457501198/plp
(přístup 2026-09-03)

`[4]` Vaughn Vernon — *Implementing Domain-Driven Design*, Addison-Wesley, 2013;
sekce „Guide to This Book". https://www.informit.com/store/implementing-domain-driven-design-9780133039894
(přístup 2026-09-03)

`[5]` Vlad Khononov — *Learning Domain-Driven Design*, O'Reilly, 2021; Preface se sekcemi
Why I Wrote This Book / Who Should Read This Book / Navigating the Book / Example Domain:
WolfDesk / Conventions Used in This Book.
https://www.oreilly.com/library/view/learning-domain-driven-design/9781098100124/preface01.html
(obsah sekcí ověřen z katalogového popisu; plný text nedostupný, 403)

`[6]` Scott Millett, Nick Tune — *Patterns, Principles, and Practices of Domain-Driven
Design*, Wrox, 4. 5. 2015, ISBN 978-1-118-71470-6.
https://www.amazon.com/Patterns-Principles-Practices-Domain-Driven-Design/dp/1118714709
(přístup 2026-09-03)

`[7]` O'Reilly Media — struktura front matter odborných titulů (sekce Who Should Read This
Book / Conventions Used in This Book / Using Code Examples), příklad:
https://www.oreilly.com/library/view/kafka-the-definitive/9781491936153/preface01.html
(katalogový záznam ověřen, plný text 403)

`[8]` O'Reilly Style Guide. https://oreillymedia.github.io/production-resources/styleguide/
(přístup 2026-09-03)

`[9]` Gojko Adzic — zápis z QCon London 2009, Eric Evans: „What I've learned about DDD
since the book" (přecenění building blocks; agregát jako hranice konzistence; context
boundaries a core domain měly přijít dřív).
https://gojko.net/2009/03/12/qcon-london-2009-eric-evans-what-ive-learned-about-ddd-since-the-book/
(přístup 2026-09-03)

`[10]` Vladik Khononov — „Tackling Complexity in the Heart of DDD", 2016 (shrnuje Evansovu
kritiku „over-emphasis on building blocks" z DDD Europe 2016).
https://vladikk.com/2016/04/05/tackling-complexity-ddd/ (přístup 2026-09-03)

`[11]` Mark Richards, Neal Ford — *Fundamentals of Software Architecture*, O'Reilly, 2020;
úvod obsahuje „Roadmap". https://www.oreilly.com/library/view/fundamentals-of-software/9781492043447/
(přístup 2026-09-03)

`[12]` Carlos Buenosvinos, Christian Soronellas, Keyvan Akbary — *Domain-Driven Design in
PHP*, Packt, červen 2017, ISBN 978-1-78728-494-4.
https://www.amazon.com/Domain-Driven-Design-PHP-Carlos-Buenosvinos/dp/1787284948
(přístup 2026-09-03)

`[13]` Titíž — *Domain-Driven Design in PHP – 2nd Edition*, Leanpub, poslední aktualizace
6. 4. 2026. https://leanpub.com/ddd-in-php (přístup 2026-09-03)

`[14]` Matthias Noback — *Advanced Web Application Architecture*, vlastním nákladem,
2. 7. 2020. https://matthiasnoback.nl/2020/07/release-of-web-application-architecture-book/
(přístup 2026-09-03)

`[15]` Matthias Noback — *Object Design Style Guide*, Manning, prosinec 2019.
https://matthiasnoback.nl/2019/12/the-release-of-object-design-style-guide/
(přístup 2026-09-03)

`[16]` Matthew Skelton, Manuel Pais — *Team Topologies, 2nd Edition*, IT Revolution,
23. 9. 2025, 304 s., ISBN 978-1-966280-00-2.
https://itrevolution.com/product/team-topologies-second-edition/ (přístup 2026-09-03)

`[17]` Vlad Khononov — *Balancing Coupling in Software Design*, Addison-Wesley Signature
Series, 26. 9. 2024, 320 s., ISBN 978-0-13-735348-4.
https://www.amazon.com/Balancing-Coupling-Software-Design-Addison-wesley/dp/0137353480
(přístup 2026-09-03)

`[18]` Sam Newman — *Building Microservices, 2nd Edition*, O'Reilly, 2021, 616 s.
https://samnewman.io/books/building_microservices_2nd_edition/ (přístup 2026-09-03)

`[19]` Chris Richardson — *Microservices Patterns*, Manning, 19. 11. 2018, 520 s.;
druhé vydání rozpracované. https://www.manning.com/books/microservices-patterns
(přístup 2026-09-03)

`[20]` Symfony — Release stránka Symfony 8.0: vydáno listopad 2025, vyžaduje PHP 8.4.0+,
konec podpory červenec 2026, aktuální větve 8.1 a 8.2.
https://symfony.com/releases/8.0 (přístup 2026-09-03)

`[21]` PHP — PHP 8.4 Release Announcement (property hooks, asymmetric visibility).
https://www.php.net/releases/8.4/en.php (přístup 2026-09-03)

### Neověřené / nedohledané

- **Plný text předmluv Evanse (2003), Vernona (2013) a Khononova (2021).** Ověřeny byly
  názvy a struktura sekcí z katalogových a recenzních zdrojů; O'Reilly i domainlanguage.com
  vracejí na plné texty HTTP 403. Doporučuji ověřit ručně z tištěných výtisků, pokud se
  budou v přepisu citovat konkrétní formulace.
- **Druhé vydání *Learning Domain-Driven Design*.** Jeden katalogový záznam naznačuje
  e-book s datem 14. 7. 2025, ale žádný zdroj neuvádí explicitně „2nd Edition" ani nové
  ISBN. Před citací v knize ověřit přímo u O'Reilly.
- **Introduction Milletta & Tuneho, sekce „Who This Book Is For“ – OVĚŘENO 2026-09-04 ze
  zakoupeného výtisku (Wrox, 2015, 795 stran). Sekce toho jména v knize není.** Prohledán plný
  text: „Who This Book Is For“ 0 výskytů, stejně jako „This book is for“ a „intended audience“.
  Obsah uvádí Introduction na straně XXXV, ale členění na podsekce s vymezením čtenáře nemá.

  **Doporučení: na tuto sekci se neodkazovat.** Pokud je v předmluvě potřeba doložit, komu je
  kniha určena, lze vyjít z jejího vlastního členění (Part I *The Principles and Practices of
  Domain-Driven Design*, kapitoly 1 *What Is Domain-Driven Design?*, 2 *Distilling the Problem
  Domain*, 3 *Focusing on the Core Domain*, 4 *Model-Driven Design*), které naznačuje záběr
  od základů po implementaci.
- **Doslovné znění Evansovy kritiky z DDD Europe 2016.** Ověřeno jen zprostředkovaně
  přes [10]; záznam keynote se nepodařilo dohledat. Pokud kniha bude citovat přímo,
  je třeba dohledat video nebo přepis.
- **Tištěná a EPUB verze – OVĚŘENO 2026-09-04 v repozitáři. Build existuje, tvrzení studie bylo
  nepřesné.** Adresář `ebook/` obsahuje kompletní pipeline: `book.yaml`, `build.sh`,
  `preprocess.php`, `epub.css`, plus adresáře `filters`, `chapters` a `output`. Konfigurace tedy
  v repozitáři je, jen není navázaná na `composer.json` ani `package.json`.

  Doplňující zjištění: frontmatter klíč `ebook: false` nese **jediná kapitola** – `ddd_ai.md:16`,
  a předmluva to zdůvodňuje rychlým vývojem tématu. Po přepisu podle P1-3 (těžiště k modelování)
  by se to mělo přehodnotit, protože přepsaná kapitola by stárla pomaleji.