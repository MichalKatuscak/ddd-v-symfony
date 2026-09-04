# Studie: CQRS v Symfony 8

- **Kapitola:** `content/chapters/cqrs.md` (č. 12, kategorie Vzory, 1550 řádků)
- **Cesta:** /cqrs
- **Typ kapitoly:** hybridní
- **Datum studie:** 2026-09-03

Poznámka k metodě: rozpočet na `WebSearch` byl v této session vyčerpán (200/200), rešerše proto
proběhla přes cílený `WebFetch` primárních URL, přes `gh api` (zdroje symfony-docs, stav
repozitářů) a přes lokálně nainstalovaný `vendor/symfony/messenger` **v8.0.7**, proti kterému byla
ověřena všechna API tvrzení. U každého zdroje v sekci 9 je uveden způsob získání.

## 1. Mapa současné kapitoly

| sekce | rozsah | co tvrdí | zdroje | poznámka |
|---|---|---|---|---|
| 12.01 Co je CQRS | 22–50 | Dva modely místo jednoho; CQRS = rozšíření CQS; nezávislost na ES | [1] Young PDF, [2] Fowler CQS | Odkazy jsou správné, ale interpretace „rozšíření“ je sporná (viz 5.1) |
| 12.02 CQS vs. CQRS | 51–98 | CQS = pravidlo metody, CQRS = architektura; tabulka; 4 úrovně zavedení | žádné | Tabulka úrovní 1–4 je vlastní konstrukce bez zdroje, ale prakticky užitečná |
| 12.03 Výhody | 99–122 | Oddělení odpovědností, škálovatelnost (10:1–100:1), testovatelnost | žádné | Poměr čtení/zápisu má doložitelný zdroj, který se necituje (viz G8) |
| 12.04 Výzvy a omezení | 123–159 | 4–6 tříd místo jedné, synchronizace, eventual consistency, zaučení; callout „kdy nepoužívat“ | žádné | Kritéria jsou čistě technická; chybí Dahanovo kritérium kolaborativnosti |
| 12.05 Symfony Messenger | 160–261 | Dva busy jako základ CQRS; `doctrine_transaction` konflikt; proč dva busy | žádné | Nejproblémovější sekce: Symfony docs dnes doporučují opak (viz G1) |
| 12.06 Commands | 262–323 | Immutabilní DTO, validační atributy, pojmenování; návratová hodnota | žádné | Dobré. Chybí Dahanův rozdíl validace vs. business rule |
| 12.07 Queries | 324–394 | Query DTO; filtrování a stránkování | žádné | Solidní, bez sporných tvrzení |
| 12.08 Handlers | 395–501 | `#[AsMessageHandler]`, rozdíl command vs. query handler | žádné | Kód porušuje kanonické konvence knihy (viz G10, G11) |
| 12.09 ViewModely a Read modely | 502–608 | ViewModel jako čisté DTO; read repozitář přes DBAL; „proč ne ORM“ | žádné | Přímo odporuje kapitole 16.04, která pro read stranu používá DQL (viz G6) |
| 12.10 Command a Query Buses | 609–751 | Named autowiring; `HandlerFailedException`; `HandledStamp` v controlleru | žádné | Ignoruje `HandleTrait` a `#[Target]`, které dokumentace doporučuje (viz G2) |
| 12.11 Optimalizace read modelů | 752–914 | Tabulka strategií; projektor; idempotence; kdo události odešle; rebuild | odkaz na Outbox a ES | Nejsilnější sekce kapitoly. Chybí `DeduplicateMiddleware` |
| 12.12 Eventual consistency | 915–1058 | Dva diagramy; tabulka UI strategií; read-your-writes přes HTTP hlavičky | žádné | Read-your-writes sekce je nadprůměrná a v české literatuře ojedinělá |
| 12.13 Asynchronní zpracování | 1059–1143 | Transporty, retry strategie, priority fronty, provoz workerů | žádné | Konfigurace je platná, ale neúplná vůči Symfony 8 |
| 12.14 Chyby a DLQ | 1144–1210 | Retry klíče, failed transport, konzolové příkazy, monitoring | žádné | Obsahuje ověřenou faktickou chybu (viz G3) |
| 12.15 Middleware | 1211–1297 | Vlastní logovací middleware, registrace, pořadí | žádné | Korektní, `MiddlewareInterface` i `StackInterface` sedí na 8.0 |
| 12.16 Testování CQRS | 1298–1526 | Unit testy handlerů, integrační test projektoru | odkaz na /testovani-ddd | Nejdelší sekce (229 řádků) na téma, které má vlastní kapitolu |
| 12.17 Saga / Process Manager | 1527–1538 | 12 řádků + odkaz na kapitolu 14 | odkaz na /sagy-a-process-managery | Pahýl v pozici číslované sekce |
| FAQ | 1539–1550 | 5 otázek | – | FAQ 1 opakuje spornou atribuci z 12.01 |

Kapitola je psaná jako implementační příručka k Symfony Messengeru, ne jako výklad vzoru. Zhruba
1100 z 1550 řádků (sekce 12.05–12.16) tvoří konfigurace, PHP ukázky a provozní rady; samotnému
vzoru – jeho původu, motivaci a hranicím použitelnosti – patří necelých 140 řádků na začátku.
Kapitola tak dobře odpovídá na otázku „jak to napsat v Symfony“ a slabě na otázku „proč a kdy“.
Nejvíc prostoru dostává testování (229 řádků), přestože má vlastní kapitolu; naopak úplně chybí
task-based UI, u Younga výchozí bod celého vzoru. Nikde také nezazní, že CQRS jde implementovat
bez message busu – Messenger je od 12.05 podán jako předpoklad, ne jako jedna z možností.

## 2. Kanonické zdroje k tématu

**Bertrand Meyer a CQS.** Command-Query Separation pochází z Meyerovy *Object-Oriented Software
Construction* [4]. Fowler ji na bliki [2] shrnuje jako rozdělení metod na queries (vracejí hodnotu,
nemění pozorovatelný stav) a commands/modifiers (mění stav, nevracejí hodnotu), a uvádí kanonickou
výjimku – `pop()` na zásobníku: „So I prefer to follow this principle when I can, but I'm prepared
to break it to get my pop." Kapitola výjimku nezmiňuje, ač souvisí s její sekcí o návratových
hodnotách commandů.

**Greg Young a CQRS.** Autorství vzoru je u Younga nesporné. Rozhodující je ale to, jak sám popisuje
vztah ke CQS – v *CQRS Documents* [1], kapitola „Command and Query Responsibility Segregation",
podkapitola „Origins":

> „Command and Query Responsibility Segregation was originally considered just to be an extension of
> this concept. For a long time it was discussed simply as CQS at a higher level. Eventually after
> much confusion between the two concepts it was correctly deemed to be a different pattern."

Young tedy explicitně říká, že formulace „CQRS je rozšíření CQS" byla ranou a později opravenou
interpretací. Jeho vlastní definice je přitom pozoruhodně skromná: „in CQRS objects are split into
two objects, one containing the Commands one containing the Queries" a hned nato „The pattern
although not very interesting in and of itself becomes extremely interesting when viewed from an
architectural point of view." CQRS je u Younga rozdělení objektu; architektonické důsledky jsou
příležitost, kterou to rozdělení otevírá, ne součást definice.

Ve stejném dokumentu Young dokládá tři věci, které kapitola tvrdí bez zdroje, a jednu, kterou
netvrdí vůbec:

- **Poměr čtení k zápisu.** „In most systems, especially web systems, the Query side generally
  processes a very large number of transactions as a percentage of the whole (often times 2 or more
  orders of magnitude)." To je přímá opora pro heuristiku 10:1 až 100:1 na řádku 116.
- **Sdílená databáze stačí.** Young zavádí „Thin Read Layer", který „reads directly from the
  database and projects DTOs" – tedy z téže databáze. Oddělené úložiště není součástí vzoru.
- **Normalizace vs. denormalizace.** Command side u Younga míří k 3NF, query side k 1NF.
- **Task Based User Interface.** *CQRS Documents* věnují task-based UI samostatnou kapitolu
  **před** kapitolou o CQRS. Commandy u Younga vznikají z úloh, které uživatel v UI provádí, ne
  z formulářů mapovaných na entity. Kapitola 12 tuto vrstvu neotevírá vůbec.

**Vztah k Event Sourcingu.** Young v kapitole „CQRS and Event Sourcing" [1] mluví o „symbiotic
relationship" a argumentuje nákladově: bez ES je nutné udržovat relační write model, relační read
model *a* event model pro jejich synchronizaci; s ES je event model zároveň persistencí write
strany. Nejde tedy o závislost, ale o úsporu. Tvrzení kapitoly (řádky 44–50) je správné.

**Udi Dahan a „Clarified CQRS" (2009) [5].** Dahan posouvá těžiště od techniky k doméně. CQRS podle
něj reaguje na dva jevy: **collaboration** (více aktérů mění tatáž data podle kontextově závislých
pravidel) a **staleness** (zobrazená data jsou už ve chvíli zobrazení zastaralá). Klíčové je jeho
odmítnutí technických předpokladů: „How you process the commands is an implementation detail of
CQRS." Event sourcing i oddělené databáze jsou volby, ne požadavky. Dahan také ostře odděluje
**validaci** (kontextově nezávislá, o strukturu commandu) od **business rules** (kontextově závislé;
validní command může selhat, protože se mezitím změnily podmínky). Tento rozdíl kapitola nikde
nedělá, přestože v sekci 12.06 zavádí `validation` middleware a v 12.08 doménovou kontrolu duplicity.

**Dahan „When to avoid CQRS" (2011) [6].** Nejtvrdší korektiv od člověka, který vzor pomáhal
popularizovat: „Most people using CQRS (and Event Sourcing too) shouldn't have done so." Jeho
kritérium je kolaborativnost domény. Nákupní košík není kolaborativní („There aren't any use cases
where users operate on each others' carts – ergo, not collaborative, therefore not a good candidate
for CQRS"), a proto pro CQRS nekandiduje – bez ohledu na to, jak asymetrická je zátěž čtení a
zápisu. Dále: „CQRS should not be your top-level architectural pattern" a CQRS patří „inside a
service boundary only".

**Fowler [3], 2011.** Bliki zápis je opatrný: „you should be very cautious about using CQRS"; CQRS
má být aplikováno na konkrétní bounded contexts, ne na celý systém. Kapitola tuto opatrnost přebírá
v calloutu 12.04, ale bez citace.

## 3. Stav praxe a posuny

**Od architektury k lokálnímu vzoru.** Mezi roky 2010 a 2015 se CQRS v komunitě podával jako
architektonický styl s obrázkem dvou databází a fronty mezi nimi. Dnešní konsenzus, který drží
Dahan [6], Fowler [3] i dokumentace Azure Architecture Center [8], je opačný: CQRS se rozhoduje
per bounded context a nejčastější nasazená podoba je nejjednodušší – oddělené handlery a oddělené
čtecí dotazy nad **jednou** databází. Kapitola tuto trajektorii popisuje správně ve své tabulce
úrovní 1–4 a v doporučení „začněte na úrovni 1 nebo 2"; nikde ji ale nepřipisuje zdrojům a nikde
neříká, že úroveň 3–4 zůstává v praxi menšinová.

**Ustálení kritérií „kdy ano".** Azure Architecture Center [8] shrnuje dnes běžně přijímaný seznam:
kolaborativní prostředí, task-based UI, samostatné ladění výkonu čtení a zápisu, oddělení
vývojových týmů, evoluce systému a integrace. Naopak „might not be suitable when: the domain or
the business rules are simple". Dva z těchto bodů – task-based UI a oddělení týmů – v kapitole
chybí, přitom druhý je v praxi častější reálná motivace než škálování.

**Eventual consistency se přestala řešit jen v UI.** Richardson [7] vede „replication lag /
eventually consistent views" jako hlavní nevýhodu vzoru; Azure [8] doplňuje, že do jedné
distribuované transakce nejde zapojit databázi i broker, a proto je nutný **Transactional Outbox**
a **idempotentní konzument**. Tady je kapitola nadprůměrná: outbox má vlastní kapitolu (15) a
sekce 12.11 na ni odkazuje včetně vysvětlení, proč dispatch po `flush()` není atomický. Sekce
o read-your-writes přes verzi zápisu v HTTP hlavičce (12.12, řádky 1004–1058) jde nad rámec toho,
co nabízí většina anglicky psaných zdrojů.

**Ekosystém PHP se zúžil.** Dedikované CQRS/message-bus knihovny z éry 2014–2018 jsou dnes mrtvé
nebo dormantní: `broadway/broadway` je **archivovaný** [12], `prooph/service-bus` má poslední
commit ze srpna 2021 [13], `SimpleBus/message-bus` z dubna 2022 [14]. Aktivní zůstávají
`thephpleague/tactician` [15] a `ecotoneframework/ecotone` [16], který ale není knihovna, nýbrž
celý framework s vlastní filozofií. Pro Symfony aplikaci je dnes výchozí volbou Messenger nebo
žádná knihovna. Kapitola nezmiňuje ani jednu z těchto možností.

## 4. Symfony / PHP specifika

Ověřeno proti `vendor/symfony/messenger` **v8.0.7** nainstalovanému v tomto repozitáři a proti
zdroji `messenger.rst` z větve `7.4` symfony-docs (v 7.4 byl soubor `messenger/multiple_buses.rst`
sloučen do hlavního `messenger.rst`, sekce `.. _messenger-multiple-buses:`).

**Doporučení dokumentace se otočilo.** Sekce „Multiple Buses, Command & Event Buses" [9] dnes
obsahuje tip, který kapitola nereflektuje:

> „A single bus is a **good default**. Add another bus only when you need a different middleware
> stack, not because an architecture pattern suggests it. […] If you cannot identify a concrete
> behavioral difference between the buses, you likely do not need more than one yet."

Zbytek konfiguračního bloku kapitola reprodukuje věrně (`default_bus`, `buses`, middleware
`validation` a `doctrine_transaction`), ale vynechává **event bus** a nové `allow_no_senders`
(výchozí `true`, ověřeno v `messenger.rst`) vedle `allow_no_handlers` (výchozí `false`).

**Handlery nejsou samy o sobě vázané na bus.** Dokumentace [9]: „By default, each handler will be
available to handle messages on *all* of your buses." Omezení se dělá tagem
`messenger.message_handler` s klíčem `bus`, nebo – což dokumentace neukazuje, ale atribut to
podporuje – přímo `#[AsMessageHandler(bus: 'command.bus')]`. Ověřeno ve
`vendor/symfony/messenger/Attribute/AsMessageHandler.php`: parametry jsou `bus`, `fromTransport`,
`handles`, `method`, `priority`, `sign`. Argument o „type safety" v sekci 12.05 tedy platí jen
pro injektování busu, ne pro směrování ke handlerům.

**Získání výsledku dotazu.** Kapitola používá na řádku 730 přímé
`$envelope->last(HandledStamp::class)->getResult()`. Dokumentace [10] i komponenta nabízejí
`HandleTrait`, jehož `handle()` navíc **ověřuje, že handler byl právě jeden** – jinak vyhodí
`LogicException` s hlášením „was handled zero times" nebo „was handled multiple times" (ověřeno ve
`vendor/symfony/messenger/HandleTrait.php`). Varianta v kapitole při chybějícím handleru selže na
volání metody nad `null`. Dokumentace zároveň ukazuje injektování busu přes
`#[Target('query.bus')]` (atribut existuje ve `vendor/symfony/dependency-injection/Attribute/Target.php`)
jako alternativu k pojmenovanému autowiringu.

**Směrování přes atribut.** Od Symfony 7.2 existuje `#[AsMessage(transport: …)]`
(`vendor/symfony/messenger/Attribute/AsMessage.php`, CHANGELOG 7.2). Zpráva si tak nese transport
sama a klíč `routing:` v YAML není nutný. Kapitola pracuje výhradně s YAML routingem.

**Idempotence má od 7.3 nástroj v komponentě.** `Middleware/DeduplicateMiddleware.php` a
`Stamp/DeduplicateStamp.php` (CHANGELOG 7.3) staví na `symfony/lock`; `DeduplicateStamp` bere
`key`, `ttl` (výchozí 300.0 s) a `onlyDeduplicateInQueue`. Doplňuje to callout „Idempotence
projektorů" (12.11, ř. 862–882), který nabízí jen `ON DUPLICATE KEY UPDATE` a sledování pozice.

**Retry a failure transport.** Klíče v kapitole (`max_retries`, `delay`, `multiplier`, `max_delay`)
jsou platné. Chybí `jitter` (přidán do `MultiplierRetryStrategy` v 7.1 „to randomize delay and
prevent the thundering herd effect"), `service` pro vlastní `RetryStrategyInterface` a
**per-transport `failure_transport`** [11] – kapitola zná jen globální.

**Provoz workerů a diagnostika.** Ověřeno v `Command/ConsumeMessagesCommand.php`: kromě
`--time-limit`, `--memory-limit` a `--limit` existují `--failure-limit`, `--all`,
`--exclude-receivers` (7.4), `--queues`, `--no-reset`, `--bus` a `--keepalive`; kapitola zmiňuje
jen dvě. `Command/StatsCommand.php` (`messenger:stats`) má `--format` s výchozí hodnotou `txt`
(formát `text` byl v 8.0 odstraněn), naproti tomu `Command/FailedMessagesShowCommand.php` má jen
`id`, `--max`, `--transport`, `--stats` a `--class-filter` – **žádné `--format`**. Viz nález G3.

**Rozbalování výjimek a PHP 8.4.**
`Exception/WrappedExceptionsInterface::getWrappedExceptions()` má v 8.0 signaturu
`(?string $class = null, bool $recursive = false)`; ruční `foreach` s `instanceof` v kapitole
(řádky 664–670) lze nahradit `$e->getWrappedExceptions(\DomainException::class)`. Kapitola také
míchá dva zápisy neměnnosti: `final class RegisterUser` s `public readonly` u každé property
(12.06) proti `final readonly class UserProfileViewModel` (12.09).

## 5. Sporné a chybně podávané body

**5.1 „CQRS je rozšíření CQS".** Kapitola to tvrdí na řádcích 28–31, celou sekcí 12.02 a v FAQ
(řádek 1541). Young [1] přesně tuto formulaci označuje za ranou a následně opravenou – „it was
correctly deemed to be a different pattern". Obě strany sporu: historicky vzor z CQS skutečně
vyrostl a Young sám v „Origins" začíná Meyerem, takže tvrzení není nepravdivé, jen zastaralé.
**Doporučení knihy:** zachovat CQS jako historický kořen, ale explicitně dodat, že sám autor vzoru
od formulace „rozšíření" ustoupil, a citovat pasáž z *CQRS Documents*. Tabulka v 12.02 pak dává
ještě větší smysl, protože ukazuje právě to, čím se vzory liší.

**5.2 Kritérium nasazení: technické vs. doménové.** Kapitola nabízí čistě technická kritéria
(rozdílné datové struktury, škálování, zkušenost týmu). Dahan [5][6] staví na kolaborativnosti
domény a nekolaborativní doménu z CQRS vylučuje i při silné asymetrii zátěže. Azure [8] přidává
task-based UI a oddělení týmů. Žádný ze zdrojů druhé straně přímo neodporuje – jsou to doplňkové
osy. **Doporučení:** doplnit doménovou osu vedle technické a Dahanův příklad košíku uvést jako
protipříklad k čistě výkonnostní argumentaci.

**5.3 Kolik busů.** Kapitola v 12.05 argumentuje pro dva busy jako pro výchozí stav CQRS
v Symfony. Dokumentace Symfony [9] dnes doporučuje jeden bus jako výchozí a druhý až při
prokazatelně jiném middleware stacku. Spor je reálný, ale řešitelný: kapitola sama uvádí právě
takový důvod (`doctrine_transaction` na commandech, ne na dotazech), takže po doplnění citace
z dokumentace obě pozice splynou. **Doporučení:** převzít formulaci „dva busy proto, že mají jiný
middleware, ne proto, že to vzor vyžaduje".

**5.4 Doctrine ORM na read straně.** Callout „Proč ne Doctrine ORM pro read stranu?" (řádek 599)
tvrdí, že ORM je pro read stranu „zbytečná režie" a doporučuje DBAL. Kapitola 16.04
(`content/chapters/performance_aspects.md:310`) používá v query handleru `EntityManagerInterface`
a DQL `NEW` expression s odůvodněním, že hydratuje přímo do DTO „bez vytváření spravovaných
doménových entit". Obě tvrzení jsou obhajitelná a v knize stojí proti sobě bez jakéhokoli
propojení. **Doporučení:** sjednotit – DQL `NEW` je legitimní střední cesta (žádná identity map,
žádné ruční SQL) a patří do tabulky strategií v 12.11.

**5.5 Mýtus „CQRS potřebuje frontu".** Kapitola ho nikde netvrdí, ale ani nevyvrací, a strukturou
(12.05 Messenger → 12.13 async → 12.14 DLQ) ho živí. Azure [8] uvádí přímo: „Messaging isn't a
requirement for CQRS." Dahan [5]: „How you process the commands is an implementation detail."
**Doporučení:** doplnit explicitní vyvrácení tří mýtů (ES, dvě databáze, asynchronnost) do 12.01
nebo do FAQ; FAQ dnes vyvrací jen mýtus o ES.

**5.6 Návratová hodnota commandu.** Callout na řádcích 313–322 je věcně dobrý, ale opomíjí
Fowlerovu výjimku (`pop()`) [2] – nejcitovanější argument pro to, že striktní CQS se v praxi
porušuje vědomě. **Doporučení:** doplnit jednou větou.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | sporné | `cqrs.md:160`–261, sekce 12.05 | Dva busy podány jako výchozí stav CQRS; Symfony docs 7.4+ doporučují jeden bus jako default a druhý až při jiném middleware stacku | Převzít formulaci z dokumentace a citovat ji; argument „různý middleware" kapitola už má |
| G2 | chybí | `cqrs.md:730` | Výsledek dotazu se bere přes `$envelope->last(HandledStamp::class)->getResult()`; při nula handlerech pád na `null`. `HandleTrait` (ověření „právě jeden handler") ani `#[Target]` se nezmiňují | Ukázat `HandleTrait` / vlastní `QueryBus` třídu jako doporučenou variantu |
| G3 | zastaralé | `cqrs.md:1206` | Doporučuje monitoring přes `messenger:failed:show --format=json`; tento příkaz `--format` **nemá** (ověřeno ve `FailedMessagesShowCommand.php`) | Nahradit `messenger:stats failed --format=json`; `--format` má jen `StatsCommand` |
| G4 | chybí | sekce 12.05, 12.11, 12.13 | Kapitola konfiguruje jen `command.bus` a `query.bus`, ale na řádku 890 mluví o dispatchi událostí „na event bus" a na řádku 1140 routuje `OrderPlaced` – žádný event bus přitom není definován | Doplnit `event.bus` do konfigurace v 12.05 včetně `allow_no_handlers: true` |
| G5 | chybí | sekce 12.01 / 12.04 | Task-based UI, u Younga samostatná kapitola **před** CQRS a zdroj commandů, není zmíněno | Nová podsekce ~25 řádků; commandy vznikají z úloh, ne z formulářů nad entitami |
| G6 | sporné | `cqrs.md:599` | „Proč ne Doctrine ORM pro read stranu" přímo odporuje kapitole 16.04, která používá DQL `NEW` v query handleru | Doplnit DQL `NEW` do tabulky strategií a odkázat na 16.04 místo protikladu |
| G7 | chybí | sekce 12.04 | Kritéria „kdy nasadit" jsou jen technická; chybí Dahanova kolaborativnost, task-based UI a oddělení týmů | Doplnit doménovou osu s Dahanovým příkladem košíku |
| G8 | nepodložené | `cqrs.md:116` | Poměr 10:1 až 100:1 označen jako „zkušenostní heuristika, ne měřený standard“ – přitom má doložitelný zdroj | Citovat Younga [1]: „often times 2 or more orders of magnitude" |
| G9 | chybí | `cqrs.md:862`–882 | Idempotence projektorů zná jen upsert a sledování pozice; `DeduplicateMiddleware` + `DeduplicateStamp` (Symfony 7.3+) chybí | Doplnit ~15 řádků včetně poznámky o závislosti na `symfony/lock` |
| G10 | sporné | `cqrs.md:441` | `throw new \DomainException('User with this email already exists')` – `CLAUDE.md` předepisuje pojmenované výjimky a jmenuje přímo `DuplicateEmailException` | Nahradit `DuplicateEmailException`; opravit i test na řádku 1361 a controller na 666 |
| G11 | sporné | `cqrs.md:437`, `:449` | `new Email($command->email)` nad surovým vstupem z formuláře, ačkoli konvence knihy má `Email::fromUserInput()`; `new UserId()` bez argumentu neodpovídá konvenci `Uuid::v7()` | Sjednotit s konvencemi z `CLAUDE.md` |
| G12 | chybí | `cqrs.md:1088`–1104 | `retry_strategy` bez `jitter` (Symfony 7.1+) a bez `service`; chybí per-transport `failure_transport` | Doplnit oba klíče a per-transport variantu |
| G13 | chybí | sekce 12.13 | `#[AsMessage(transport: …)]` (Symfony 7.2+) jako alternativa k YAML `routing:` | Doplnit ~8 řádků |
| G14 | mělké | `cqrs.md:200`–212, sekce 12.05 | Bullet „Type safety" tvrdí, že oddělené busy brání záměnám; handlery jsou ale ve výchozím stavu registrované na **všech** busech | Doplnit `#[AsMessageHandler(bus: 'command.bus')]` nebo tag `messenger.message_handler` |
| G15 | chybí | sekce 12.06 | Dahanův rozdíl mezi validací (kontextově nezávislou) a business rules (kontextově závislými) chybí, ačkoli kapitola obojí implementuje | Doplnit odstavec; vysvětluje, proč `validation` middleware nenahrazuje doménovou kontrolu |
| G16 | nadbytečné | `cqrs.md:1527`–1538, sekce 12.17 | Dvanáctiřádkový pahýl s odkazem na kapitolu 14 v pozici číslované sekce | Zrušit jako sekci; převést na `note` callout na konci 12.13 |
| G17 | nadbytečné | `cqrs.md:1298`–1526, sekce 12.16 | 229 řádků testování (15 % kapitoly) u tématu s vlastní kapitolou /testovani-ddd | Zkrátit na ~90 řádků: nechat test projektoru (specifický pro CQRS), zbytek odkázat |
| G18 | chybí | sekce 12.05 | Nikde nezazní, že CQRS jde implementovat bez message busu; Messenger je podán jako předpoklad | Jeden odstavec před konfigurací + zmínka o stavu ekosystému [12]–[16] |
| G19 | nepodložené | `cqrs.md:952` | Nadpis „Optimistická aktualizace – controller vrací data z command handleru" popírá vlastní kód, který ID generuje na klientovi (`Uuid::v7()`) právě proto, aby handler nic nevracel | Přejmenovat na „ID generované na klientovi – command nemusí vracet hodnotu" |
| G20 | mělké | sekce 12.01, FAQ | Vyvrácen je jen mýtus „CQRS vyžaduje ES"; mýty „vyžaduje dvě databáze" a „vyžaduje asynchronní zpracování" chybí, ač je zdroje explicitně vyvracejí | Doplnit oba do 12.01 s citací Younga [1] a Azure [8] |
| G21 | nepodložené | sekce 12.01–12.04 | Osm odstavců faktických tvrzení o původu, výhodách a mezích vzoru se opírá o dvě URL v úvodu | Doplnit citace Dahana [5][6] a Fowlera [3] |
| G22 | zastaralé | `cqrs.md:1119`–1141 | `messenger:consume` popsán jen s `--time-limit` a `--memory-limit`; chybí `--failure-limit`, `--all`, `--queues`, `--keepalive` | Rozšířit výčet; `--failure-limit` je pro provoz projekcí relevantní |

## 7. Doporučení k přepisu

**P1-1 — Opravit `messenger:failed:show --format=json` na `messenger:stats failed --format=json`.**
Ověřeno proti `vendor/symfony/messenger/Command/FailedMessagesShowCommand.php` v8.0.7: příkaz
option `--format` nemá. Čtenář, který podle kapitoly postaví cron monitoring, dostane chybu.
Rozsah: `oprava jedné věty` (G3).

**P1-2 — Doplnit `event.bus` do konfigurace v 12.05.** Kapitola dispatchuje doménové události
(řádek 890) a routuje je na transport (řádek 1140), aniž by bus, na kterém se to děje, kdekoli
existoval. To je vnitřní rozpor, který čtenář odhalí až za běhu, a navíc se bez `allow_no_handlers`
chová jinak než command bus. Rozsah: `přepis konfiguračního bloku v 12.05, ~20 řádků` (G4).

**P1-3 — Přeformulovat argument pro dva busy podle dnešní dokumentace Symfony.** Dokumentace [9]
dnes explicitně varuje před přidáváním busu „because an architecture pattern suggests it". Kapitola
tvrdí opak a Symfony je přitom jejím primárním zdrojem pro tuto oblast. Argument „různý middleware"
už kapitola má, takže jde o změnu rámování, ne obsahu. Rozsah: `přepis úvodu 12.05 a callout
„Proč dva oddělené busy", ~25 řádků` (G1, G14).

**P1-4 — Nuancovat atribuci CQRS ke CQS.** Young sám označuje formulaci „rozšíření CQS" za
opravenou. Kapitola ji drží na třech místech (řádek 28, sekce 12.02, FAQ 1541), takže se propaguje
i do strukturovaných dat stránky. Oprava přitom sekci 12.02 posiluje – celá její tabulka je právě
o tom, čím se vzory liší. Rozsah: `oprava tří pasáží + citace, ~15 řádků` (5.1, G21).

**P1-5 — Sjednotit ukázky s kanonickými konvencemi knihy.** `\DomainException` místo
`DuplicateEmailException`, `new Email()` nad surovým vstupem místo `Email::fromUserInput()`,
`new UserId()` místo `Uuid::v7()`. `CLAUDE.md` jmenuje `DuplicateEmailException` přímo jako
kanonický příklad, takže kapitola porušuje konvenci na jejím vlastním modelovém případu. Zásah
sahá do tří ukázek (handler, controller, test). Rozsah: `oprava ~10 řádků` (G10, G11).

**P1-6 — Opravit nadpis ukázky v 12.12.** „Controller vrací data z command handleru" popisuje
pravý opak toho, co kód dělá a co doporučuje callout v 12.06. Rozsah: `oprava nadpisu` (G19).

**P2-1 — Nová podsekce o task-based UI.** U Younga je task-based UI vstupní branou k CQRS: bez něj
commandy degenerují na `UpdateOrder` a vzor ztrácí smysl. Kapitola přitom v 12.06 správně
požaduje pojmenování podle záměru, ale nevysvětluje, odkud ten záměr bere. Doplnění uzavírá
logickou mezeru mezi „pojmenujte command podle záměru" a „vytvořte formulář". Rozsah:
`nová sekce ~30 řádků, zařadit za 12.02` (G5).

**P2-2 — Rozšířit kritéria nasazení o doménovou osu.** Dahanova kolaborativnost je nejcitovanější
kritérium pro CQRS a zároveň to, které nejčastěji chybí v tutoriálech. Jeho příklad nákupního
košíku (silná asymetrie čtení/zápisu, ale nulová kolaborace, tedy žádný CQRS) funguje jako
protipříklad k celé sekci 12.03. Rozsah: `přepis calloutu „Kdy nepoužívat CQRS" a doplnění
~20 řádků do 12.04` (G7).

**P2-3 — Vyvrátit tři mýty explicitně.** Kapitola vyvrací jen mýtus o Event Sourcingu. Mýty
„CQRS vyžaduje dvě databáze" (Young: Thin Read Layer čte z téže databáze) a „CQRS vyžaduje
asynchronní zpracování" (Azure: „Messaging isn't a requirement for CQRS") jsou v české komunitě
stejně rozšířené. Rozsah: `callout ~18 řádků v 12.01 + rozšíření FAQ o jednu otázku` (G20).

**P2-4 — Doplnit `HandleTrait` do 12.10.** Dokumentovaný způsob získání výsledku dotazu, který
navíc vynucuje právě jeden handler; současná varianta selže na `null`. Zároveň je to přirozené
místo pro `#[Target('query.bus')]`. Rozsah: `přepis ukázky query bus controlleru + ~20 řádků`
(G2).

**P2-5 — Doplnit `DeduplicateMiddleware` do calloutu o idempotenci.** Od Symfony 7.3 je
deduplikace součástí komponenty a čtenář ji nemusí psát ručně. Callout dnes nabízí jen dvě
databázové techniky. Rozsah: `~15 řádků v 12.11` (G9).

**P2-6 — Zkrátit sekci 12.16.** 229 řádků testování je 15 % kapitoly u tématu, které má vlastní
kapitolu. Test projektoru je pro CQRS specifický a má zůstat; unit testy command a query handleru
se od běžných testů služeb neliší a stačí odkaz. Uvolněný prostor pokryje P2-1 a P2-2 bez nárůstu
délky kapitoly. Rozsah: `zkrácení 12.16 z ~229 na ~90 řádků` (G17).

**P2-7 — Sjednotit doporučení k ORM na read straně s kapitolou 16.** Dvě kapitoly téže knihy dnes
říkají o téže věci opak. DQL `NEW` expression je legitimní třetí možnost mezi ORM entitami a ručním
SQL a patří do tabulky strategií v 12.11. Rozsah: `přepis calloutu na řádku 599 + řádek v tabulce`
(G6).

**P2-8 — Doplnit rozdíl validace vs. business rules.** Kapitola zavádí `validation` middleware
i doménovou kontrolu duplicity, ale nikde neříká, proč jsou to dvě různé věci a proč první
nenahrazuje druhou. Dahanova formulace (kontextově nezávislé vs. kontextově závislé) je krátká a
přesná. Rozsah: `~12 řádků v 12.06` (G15).

**P3-1 — Zrušit sekci 12.17 jako číslovanou sekci.** Dvanáct řádků odkazu nezaslouží úroveň H2 a
uživateli v obsahu slibuje výklad, který na stránce není. Callout na konci 12.13 splní totéž.
Rozsah: `přesun 12 řádků` (G16).

**P3-2 — Doplnit `#[AsMessage(transport:)]`, `jitter`, per-transport `failure_transport` a chybějící
přepínače `messenger:consume`.** Drobná modernizace konfiguračních ukázek na Symfony 8. Rozsah:
`~20 řádků rozptýlených v 12.13 a 12.14` (G12, G13, G22).

**P3-3 — Odstavec o tom, že CQRS nepotřebuje bus, plus stav PHP ekosystému.** `broadway/broadway`
je archivovaný, `prooph/service-bus` a `SimpleBus` roky bez commitu; čtenář, který si je najde sám,
si zaslouží vědět, na čem je. Zároveň sjednotit zápis neměnnosti DTO na `final readonly class` –
kapitola dnes používá oba zápisy vedle sebe. Rozsah: `~12 řádků v 12.05 + oprava dvou ukázek` (G18).

## 8. Otevřené otázky pro autora

1. **Kolik prostoru dát task-based UI?** Je to u Younga vstupní brána k CQRS, ale v knize
   pro Symfony vývojáře to znamená otevřít téma návrhu formulářů, které nikde jinde není. Stačí
   30 řádků s odkazem na Event Storming (kap. 5), nebo si to zaslouží víc?
2. **Kam s Dahanovou kolaborativností?** Je to nejsilnější kritérium „kdy CQRS ano", ale rozporuje
   běžnou motivaci čtenáře (výkon čtení). Podat ho jako hlavní osu, nebo jako korektiv vedle
   technické osy?
3. **Zkrácení sekce 12.16 o testování** – uvolní 140 řádků, ale kapitola ztratí ucelenou testovací
   pasáž. Přesunout do /testovani-ddd, nebo držet vlastní sekci i za cenu duplicity?
4. **Sekce 12.17 (Saga)** – zrušit, nebo naopak rozšířit o to, co je na sagách specificky CQRS
   (konzument událostí a producent commandů, tedy spojka mezi oběma stranami)? Druhá varianta
   odstraňuje duplicitu s kapitolou 14 jiným způsobem.
5. **Konzistence s kapitolou 16.04** je změna napříč kapitolami. Sjednotit ve prospěch DBAL
   (kap. 12), DQL `NEW` (kap. 16), nebo obojí podat jako volbu podle složitosti dotazu?
6. **Cílová úroveň CQRS.** Kapitola doporučuje úroveň 1–2, ale všechny rozsáhlé ukázky (projektor,
   async, DLQ, rebuild) patří na úroveň 3. Srovnat poměr prostoru s doporučením?
7. **Naming napříč knihou:** kapitola 12 používá `PlaceOrder`, kapitola 13 na řádku 79
   `PlaceOrderCommand`. Má kapitola 12 konvenci explicitně zformulovat (analogicky k pravidlu
   „události bez suffixu Event")?

## 9. Bibliografie

### Ověřené zdroje

- `[1]` Greg Young — *CQRS Documents*, 2010. https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf
  (redirect na https://cqrs.wordpress.com/wp-content/uploads/2010/11/cqrs_documents.pdf).
  **Přímý fetch**, PDF staženo a text extrahován lokálně přes `pdftotext -layout`; citace z kapitol
  „Command and Query Responsibility Segregation" (Origins, The Query Side, The Command Side) a
  „CQRS and Event Sourcing". Přístup 2026-09-03.
- `[2]` Martin Fowler — *CommandQuerySeparation*, bliki. https://martinfowler.com/bliki/CommandQuerySeparation.html
  **Přímý fetch**, přístup 2026-09-03. Atribuce Meyerovi a výjimka `pop()`.
- `[3]` Martin Fowler — *CQRS*, bliki, 14. 7. 2011. https://martinfowler.com/bliki/CQRS.html
  **Přímý fetch**, 2026-09-03. „you should be very cautious about using CQRS"; per bounded context.
- `[4]` Bertrand Meyer — *Object-Oriented Software Construction*, Prentice Hall. Původ CQS.
  **Neověřeno přímo** (kniha); doloženo přes [1] a [2], které ji shodně uvádějí jako zdroj.
- `[5]` Udi Dahan — *Clarified CQRS*, 9. 12. 2009. https://udidahan.com/2009/12/09/clarified-cqrs/
  **Přímý fetch**, 2026-09-03. Collaboration a staleness; validace vs. business rules; „How you
  process the commands is an implementation detail of CQRS."
- `[6]` Udi Dahan — *When to avoid CQRS*, 22. 4. 2011. https://udidahan.com/2011/04/22/when-to-avoid-cqrs/
  **Přímý fetch**, přístup 2026-09-03. „Most people using CQRS (and Event Sourcing too) shouldn't
  have done so."; příklad nákupního košíku; „CQRS should not be your top-level architectural pattern".
- `[7]` Chris Richardson — *Pattern: CQRS*, microservices.io. https://microservices.io/patterns/data/cqrs.html
  **Přímý fetch**, 2026-09-03. Replication lag jako hlavní nevýhoda vzoru.
- `[8]` Microsoft — *CQRS Pattern*, Azure Architecture Center, revize 2025-02-20.
  https://learn.microsoft.com/en-us/azure/architecture/patterns/cqrs **Přímý fetch**, přístup
  2026-09-03. „Messaging isn't a requirement for CQRS."; kritéria „when to use / might not be
  suitable"; odkaz na Transactional Outbox a idempotentního konzumenta. Terciární zdroj – v pořadí
  důvěryhodnosti knihy nízko, použit jen pro doložení dnešního konsenzu, ne pro atribuce.
- `[9]` Symfony — *Messenger: Multiple Buses, Command & Event Buses*.
  https://symfony.com/doc/current/messenger/multiple_buses.html — **přímý fetch stránky se ukázal
  neúplný** (sekce oříznuta), proto ověřeno ze zdroje: `gh api` na `symfony/symfony-docs`, soubor
  `messenger.rst`, větev `7.4`, sekce `.. _messenger-multiple-buses:` (soubor
  `messenger/multiple_buses.rst` v 7.4 už neexistuje, byl sloučen). Tip „A single bus is a good
  default"; `allow_no_handlers` / `allow_no_senders`; „Restrict Handlers per Bus". Přístup 2026-09-03.
- `[10]` Symfony — *Messenger: Getting Results from your Handlers*.
  https://symfony.com/doc/current/messenger/handler_results.html **Přímý fetch** + ověření ze
  zdroje `messenger.rst` (7.4). `HandledStamp::getResult()`, `HandleTrait`, `#[Target('query.bus')]`.
  Přístup 2026-09-03.
- `[11]` Symfony — *Messenger: Sync & Queued Message Handling*.
  https://symfony.com/doc/current/messenger.html **Přímý fetch**, přístup 2026-09-03.
  `retry_strategy`, globální i per-transport `failure_transport`, příkazy `messenger:failed:*`.
- `[12]`–`[16]` Stav PHP knihoven, **ověřeno přes `gh api repos/…`** 2026-09-03:
  `broadway/broadway` `archived=true` [12]; `prooph/service-bus` poslední push 2021-08-25 [13];
  `SimpleBus/message-bus` 2022-04-11 [14]; `thephpleague/tactician` 2025-12-21 [15];
  `ecotoneframework/ecotone` 2026-08-28 [16].
- `[17]` Symfony Messenger **v8.0.7**, lokální instalace v `vendor/symfony/messenger`.
  **Přímé čtení zdrojového kódu** 2026-09-03: `Attribute/AsMessageHandler.php`,
  `Attribute/AsMessage.php`, `HandleTrait.php`, `Middleware/DeduplicateMiddleware.php`,
  `Stamp/DeduplicateStamp.php`, `Exception/WrappedExceptionsInterface.php`,
  `Command/FailedMessagesShowCommand.php`, `Command/StatsCommand.php`,
  `Command/ConsumeMessagesCommand.php`, `CHANGELOG.md`. Nejspolehlivější zdroj pro všechna
  Symfony tvrzení v sekci 4.

### Doověřeno druhým průchodem (2026-09-04)

`[33]` Greg Young – *CQRS is not an Architecture*, 9. 9. 2012.
https://gregfyoung.wordpress.com/2012/09/09/cqrs-is-not-an-architecture/ (mirror původního
codebetter.com, který už nerezolvuje). Doslovně: „CQRS can be called an architectural pattern.
Just like Transaction Script is an architectural pattern.“ – „CQRS and Event Sourcing are not
architectural styles.“ – „CQRS and Event Sourcing describe something **inside a single system
or component**.“

**Nový nález (G-A1, sporné) – `cqrs.md:57` a `cqrs.md:26`.** Kapitola tvrdí opak Youngova
vlastního vymezení. Na ř. 57 stojí: „Greg Young posunul tuto myšlenku na **architektonickou
úroveň**: CQRS není pravidlo pro jednotlivé metody, ale **rozhodnutí o struktuře celé
aplikace**“, tabulka pod tím uvádí úroveň „Architektura celé aplikace“ a ř. 26 mluví o přenesení
principu „na úroveň architektury“. Young [33] přitom explicitně říká, že CQRS popisuje něco
uvnitř jediného systému nebo komponenty, a že architekturou není. FAQ na ř. 1541 formuluje totéž
správně („architektonický vzor“).

**Doporučení:** sjednotit na Youngovu terminologii. „Architektonický vzor“ ano, „rozhodnutí
o struktuře celé aplikace“ a řádek tabulky „Architektura celé aplikace“ přepsat. Rozdíl není
slovíčkaření: kapitola tím čtenáři sděluje, že CQRS je celosystémové rozhodnutí, zatímco Young
i Dahan [6] shodně varují před nasazením CQRS jako vzoru nejvyšší úrovně.

### Neověřené / nedohledané

- **Greg Young, „CQRS is not an architecture“ – DOHLEDÁNO 2026-09-04, viz [33].** První průchod
  hledal na `gregyoung.wordpress.com`; správná doména Youngova blogu je **`gregfyoung`** s „f“.
  Text je živý a citovatelný.
- **Vernon, *Implementing Domain-Driven Design* (2013)** a **Khononov, *Learning Domain-Driven
  Design* (2021)** — oba mají k CQRS relevantní kapitoly, ale jde o knihy a v této session je
  nebylo možné ověřit. Před přepisem sekce 12.04 by stálo za to dohledat, jak kritéria nasazení
  formulují oni.
- **Symfony 8.0 dokumentace `messenger.html`** — fetchována verze `current`, u které nelze
  z obsahu stránky potvrdit, že odpovídá 8.0. Všechna konfigurační a API tvrzení v sekci 4 jsou
  proto navíc ověřena proti nainstalované komponentě `v8.0.7` [17]; kde se zdroje lišily, platí [17].
- **Tvrzení „4–6 tříd místo jedné"** (`cqrs.md:127`) — nepodařilo se dohledat zdroj. Jde
  pravděpodobně o autorský odhad; pokud zůstane, měl by být označen stejně jako poměr 10:1
  („zkušenostní heuristika").
