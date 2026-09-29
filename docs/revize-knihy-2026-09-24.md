# Revize knihy 23.–24. 9. 2026 – souhrnný report

Rozhodnutí pro opravy napříč knihou:

# Rozhodnutí pro opravy rozporů napříč knihou (závazná pro všechny opravné agenty)

Zdroje nálezů (ve stejném adresáři reports/): consistency-code.md ([R-N]), consistency-terms.md ([K-N]), cross-g.md (rozpory hlášené redaktory kapitol). Každý nález obsahuje návrh – ber ho jako výchozí, ale před aplikací si ověř aktuální text (kapitoly mezitím prošly jazykovou redakcí, citace nemusí sedět doslovně) a kanonické definice (CLAUDE.md, kap. 06 basic_concepts, 07 aggregate_design, 10 implementation_in_symfony).

Níže rozhodnutí tam, kde nález nabízí víc variant nebo kde se dotýká více kapitol:

1. **Dispatch událostí pod `doctrine_transaction` (R-1):** platí pravidlo kap. 15: synchronní in-process dispatch na `event.bus` uvnitř transakce je v pořádku (rollback vrátí i zápisy posluchačů); co opouští proces (broker, e-mail, cizí služba), jde přes Outbox. Pod middlewarem `flush()` ≠ commit – komentáře „commit" u flush opravit („zápis SQL; commit řídí middleware"). Explicitní `flush()` v handleru není „výjimka", má důvod (zachycení unique constraintu apod.) – formulovat důvod, ne výjimečnost.
2. **Struktura projektu (R-2):** Ordering má vrstvy `Domain/Application/Infrastructure`, příkaz `PlaceOrder`/`PlaceOrderHandler` (ne `CreateOrder`); ve stromu kap. 10 smazat abstraktní `Id.php`. UserManagement smí zůstat ve feature slicích – pod stromem jednou větou říct, že kniha ukazuje obě organizace (odkaz na Vertical Slice v kap. 09). Kap. 18 použije FQCN `RegisterUser` z kap. 10.
3. **Signatury událostí (R-3, R-5, R-18):** kanonické jsou definice z kap. 07 (#references-by-id) a `OrderPlaced` z kap. 06. `OrderItemAdded` už `occurredAt` má (doplněno v kap. 07) – ověř, že volání `new OrderItemAdded(...)` v kap. 06 i jinde odpovídá. Kap. 21 (21.06) ukáže neměnnost události na kanonickém tvaru (nepoužívat pod kanonickým FQCN jinou signaturu). `placeWithItems()` v kap. 15 začíná `self::place()` → pořadí OrderPlaced, OrderItemAdded…, OrderConfirmed. `placedAt` v kap. 08/21: přiznat v komentáři nebo přejmenovat na `createdAt`.
4. **Obsah události (R-4):** doménové události objednávky nesou VO; `UserRegistered` v kap. 10 nese primitivy – doplnit jednu větu proč (odebírá ji i jiný kontext / posílá se async, test v kap. 17 na tom stojí).
5. **Kap. 18 User (R-6):** aktivační model je rozšíření kanonického `User` z kap. 10 → zachovat tabulku `users`, `createdAt`, getter `hashedPassword()`; `nextIdentity()` → `UserId::generate()`; `UserRegistrationPolicy` s kontrolou duplicity vypustit, nebo definovat a výslovně doplnit, že bez unique constraintu je TOCTOU (odkaz `/implementace-v-symfony#register-race-heading` – ověř kotvu); zakázaná doména v příkladu `mailinator.com` místo `example.com`/`test.com`.
6. **Doménové události přes hranici (R-7, K „konzumace doménové události cizím kontextem"):** v kap. 14, 19, 23 přidat krátké přiznání zjednodušení („pro stručnost…; v produkci integrační událost, viz Outbox Pattern") tam, kde kód konzumuje doménovou událost cizího kontextu. Kód nepřepisovat masivně.
7. **Platební událost:** událost kontextu Payment o úspěšné platbě se jmenuje `PaymentSucceeded` (43 výskytů, kap. 14 a 04). V kap. 04, 13, 21 sjednotit `PaymentReceived` → `PaymentSucceeded`, pokud nejde o jiný, výslovně odlišný model (v ES kapitole může jít o vlastní agregát – pak ponechat a nic neměnit). Na straně Order je to `OrderPaid`.
8. **Storno (R-9):** kanonicky `cancel(string $reason, \DateTimeImmutable $when)`, odmítá jen `Shipped`/`Delivered`, `Paid → Cancelled` povoleno. Kap. 06 a 04 sjednotit (v kap. 04 může komentář říct, že jde o náčrt z workshopu, ale pravidlo má být stejné).
9. **Tabulka `order_summary` (R-10):** v kap. 16 použít `order_dashboard` se sloupci podle DDL v kap. 12, nebo vlastní jméno bez odkazu „viz kapitola CQRS" – vyber variantu s menším zásahem. Kap. 13 (ES) má vlastní projekci, neměnit.
10. **Doctrine v doméně (cross-g g3/g7):** kniha vědomě mapuje atributy `Doctrine\ORM\Mapping` na doménových třídách (kap. 10 #mapping-volba-heading). Pravidla Deptrac/phparkitect v kap. 08, 17, 19 proto zakazují doméně Doctrine runtime (EntityManager, QueryBuilder, `Doctrine\ORM` mimo `Mapping`), ale povolují `Doctrine\ORM\Mapping` a `Doctrine\Common\Collections`. Kde text/konfigurace tvrdí „doména nesmí znát Doctrine ORM" absolutně, upřesnit.
11. **`OrderStatus`:** jen `Draft/Confirmed/Paid/Shipped/Delivered/Cancelled`. `case Placed` v kap. 18 nahradit, nebo výslovně označit za legacy enum migrovaného projektu. Read model dashboardu (kap. 12) zobrazuje `'placed'` – jednou větou vysvětlit, že read model má vlastní popisek, nebo sjednotit na `confirmed` (menší zásah vyhrává; pozor, kap. 11 čte tentýž read model).
12. **Kap. 16 `UserEmailChanged`:** listener přepsat na `#[AsMessageHandler(bus: 'event.bus')]` a říct, že `UserEmailChanged` je rozšíření kanonického `User` (který `changeEmail()`… ověř, co kanonický `User` má). Nadpis „ULID jako kompromis" → „Časově řazené identifikátory: UUID v7 a ULID" – ALE pozor, kotvu `{#…}` neměnit.
13. **Předmluva vs. huby (R-20):** části v předmluvě sladit s huby v src/Catalog/Chapters.php (ověř `group` každé kapitoly). Kotvy `#cast-N` zachovat. Totéž zkontrolovat v what_is_ddd.md #jak-cist.
14. **Reading time:** subdomains.md `reading_time` vs `Chapters.php` – zjisti, které číslo se kde zobrazuje, a sjednoť na hodnotu v Chapters.php (Chapters.php needituj – patří agentovi C; hodnotu frontmatteru změň ty). 
15. **Holý `#[AsMessageHandler]` (R-15):** doplnit `bus:` tam, kde kapitola pracuje s více busy; v kap. 24 stačí jedna věta, pokud má jednu sběrnici.
16. **Odkazové texty na kap. 18:** sjednotit na „Migrace z CRUD na DDD" (odkazy mířící na /migrace-z-crud).
17. **Kap. 23 kontext „Order" vs `Ordering`:** v próze sjednotit na `Ordering` (diagram SVG neměnit).
18. **Terminologické varianty z consistency-terms.md (nízká závažnost):** sjednotit na převládající úzus v knize (agent si ho ověří grepem), jen v próze, ne v citacích ani názvech zdrojů.

Obecně: kód měnit minimálně a vždy po změně `php scripts/lint-php-snippets.php <soubor>`. U každé změněné kapitoly `modified: 2026-09-23` (už většinou je). Kotvy, citace a URL neměnit. Jazyk nových vět: stručně, přirozeně česky, podle CLAUDE.md.

---
<!-- reports/g1.md -->
# g1 – preface.md (00), what_is_ddd.md (01), subdomains.md (02)

## Opravené nepravdy / věcné chyby
- preface.md:predpoklady – „Aktuální LTS je Symfony 7.4 s PHP 8.2, a tam část ukázek neběží … pro tuhle knihu [volbu] nemáte“ → 7.4 si *vystačí* s PHP 8.2 a běží i s ORM 2; ukázky neběží až na takovém stacku. Symfony 7.4 na PHP 8.4 + ORM 3 umí `public private(set)` i ORM 3, takže původní tvrzení bylo nepřesné. Věta „volbu nemáte“ vypuštěna, ponecháno „kniha proto cílí na Symfony 8“.
- preface.md (úvod) – „Každá kapitola obsahuje funkční kód“ × strategické kapitoly bez kódu (CLAUDE.md, sekce Živé ukázky) → „Ukázky jsou funkční kód, ne pseudokód.“
- preface.md:cast-5 – text odkazu „Výkonové aspekty“ → skutečný název kapitoly „Read modely, projekce a výkon“ (Chapters.php).
- what_is_ddd.md (úvod) – „Místo čtyř typů zákazníka v jednom modelu mít čtyři Bounded Contexts“ – koncepční chyba (BC se nedělí podle typu zákazníka; kapitola sama v #bounded-context dělí Customer podle Ordering/Support) → „oddělené Bounded Contexts (objednávky, fakturace, podpora), kde každý má svého `Customer`“.
- what_is_ddd.md (úvod) – výčet čtyř míst, hned za ním „napříč pěti soubory“ → rozpor odstraněn; totéž „rozteklá napříč pěti soubory“ v callout #priklad-platba-heading → „po servisních třídách“.
- what_is_ddd.md:summary – „DDD strukturuje práci do tří vrstev“ s ACL mezi implementačními vzory × diagram 01.4-A („dvě úrovně“) a #strategic-design (ACL je vztah na Context Map) → dvě úrovně + implementační vzory; ACL přesunut ke strategickému designu, doplněna Core Domain.
- what_is_ddd.md:summary – „přináší měřitelnou hodnotu“ × #kritika („přínosy jsou zkušenostní, ne změřené“) → „kde se přesné modelování … vrací“.
- what_is_ddd.md:jak-cist – „Pro citace … v každé kapitole je sekce Další četba (jako tato)“ – nemá ji každá kapitola (i předmluva píše „u řady kapitol“) a 01.12 není Další četba → „u řady kapitol … (v této kapitole 01.11)“.
- what_is_ddd.md:implementation – krok 7 „hraniční scénáře integrační“ (nejasné, působilo jako edge cases) → „napojení na databázi a další infrastrukturu testy integrační“.
- subdomains.md:proc-subdomeny – „Jak potvrzuje úvodní kapitola: chyba ve volbě subdomény vás bude stát násobně víc…“ – kap. 01 nic takového netvrdí → tvrzení podáno jako vlastní, odkaz zachován („proto úvodní kapitola řadí strategický design před taktický“).
- subdomains.md:proc-subdomeny – Khononovovi přisouzeno tučné tvrzení „klasifikace je první nástroj DDD a nejlevnější … změní distribuci milionů korun“ (neověřitelné, koruny zjevně nejsou jeho) → Khononov klasifikací výklad otevírá (kap. 1, doloženo); „nejlevnější nástroj … miliony korun“ je nyní tvrzení knihy.
- subdomains.md:symfony-implications – composer.json mapoval jen `App\Core\`, `App\Supporting\`, `App\Generic\`, `App\SharedKernel\` → `App\Kernel` (src/Kernel.php) by nešel autoloadovat, aplikace by nenastartovala. Nahrazeno výchozím `"App\\": "src/"`, text upraven („autoload žádné zvláštní mapování nepotřebuje“), popisek bloku změněn na „composer.json (platí pro obě varianty)“.
- subdomains.md:symfony-implications – `Pricelist` importoval `App\Core\Pricing\Domain\ValueObject\Money` × kanonické `App\SharedKernel\Domain\Money` (předmluva #styl-kodu, 30+ výskytů v knize) → `use App\SharedKernel\Domain\Money;`, řádek `ValueObject/Money.php` odstraněn ze stromu, `Money` doplněn do výčtu obsahu SharedKernel. Lint OK.
- subdomains.md:shift-core-to-supporting (+ FAQ + shrnutí bod 5) – „cloud storage … komoditizován cloud providerem (AWS S3, Azure Blob, GCS)“, FAQ „Dropbox po nástupu S3“ – chronologicky chybně (S3 2006 je starší než Dropbox 2008, Dropbox na S3 běžel) → komoditizace *synchronizace souborů* přes Google Drive, OneDrive, iCloud Drive (2012–2014).
- subdomains.md:shift-supporting-to-generic × tabulky v #subdomeny-na-bc a #sourcing (Customer Support / Helpdesk = Supporting s Zendeskem) × „helpdesk se posunul do Generic“ → rozlišeno: do Generic se posunul ticketing, Supporting zůstávají firemní pravidla podpory nad ním; doplněn odkaz na #subdomeny-na-bc. Zároveň „většina středních firem“ (neověřitelné) → „řada“.
- subdomains.md:symfony-implications – „re-evaluuje se každý rok až dva“ × 12–18 měsíců v #evoluce a ve shrnutí → sjednoceno na 12–18 měsíců.
- subdomains.md:dvs-template – „agilní formát o 15–20 řádcích“ × šablona má ~35 řádků → „zkrácené na jednu stránku markdownu“ (souhlasí s krokem 5 „1 stránka A4“).
- subdomains.md:rozpoznat-core – otázky 4 a 5 končily absolutně („je to Core“) × pravidlo „tři a více ANO = kandidát“ → „nejspíš diferenciátor“ / „indikátor Core“.
- subdomains.md:symfony-implications – „Doctrine typ `uuid` … musíte ho zaregistrovat, jinak … výjimkou o neznámém typu“ – nepravda. DoctrineBundle přidává `RegisterUidTypePass` (symfony/doctrine-bridge) a ten typy `uuid`/`ulid` registruje automaticky, jakmile existuje `symfony/uid`. Ověřeno ve vendoru doctrine-bundle 2.18 (`DoctrineBundle.php:78`, `RegisterUidTypePass.php`). → Text nyní říká, že registrace je automatická, výjimka nastane jen bez `symfony/uid` a explicitní YAML je potřeba jen mimo DoctrineBundle. YAML blok ponechán. Popisek bloku „(výřez: mapping podle kontextů)“ neodpovídá obsahu, markup jsem ale neměnil.
- subdomains.md frontmatter `deck` – gramatická chyba „jakou seniority“ → „jakou senioritu“ (jediná změna frontmatteru kromě `modified`).

## NEJISTÉ (neopraveno)
- subdomains.md:symfony-implications – „Matthias Noback doporučuje právě ji: na první úrovni Bounded Context nebo subdoména, uvnitř vrstvy.“ – atribuce neověřena (bez zdroje).
- subdomains.md:rozpoznat-core – „OAuth 2.1 / OpenID Connect“ jako tržní standard; OAuth 2.1 byl podle mých znalostí stále IETF draft, ne RFC. Stejně v callout #custom-auth-warning-heading.
- subdomains.md:evoluce – „Pohyb po [Wardleyho] ose jde jedním směrem“ vs. hned následující sekce „Z Generic do Core“. Příklad se Stripe se týká jiné firmy, takže přímý rozpor to není, ale čtenář ho tak může vnímat.
- what_is_ddd.md:further-reading – DDD Reference „na 52 stranách“ – počet stran neověřen (pamatuji spíš kolem 59).
- preface.md (úvod) – „*Domain-Driven Design in PHP* … vyšlo v roce 2017 a druhé vydání žije dál na Leanpubu“ – Leanpub verze vyšla už 2016, Packt 2017. Existence „druhého vydání“ neověřena.

## Nekonzistence vůči jiným kapitolám (neopraveno)
- subdomains.md frontmatter `reading_time: 21` × src/Catalog/Chapters.php `'time' => 18` – sjednotit (TOC ukazuje jiný údaj než hlavička kapitoly).
- preface.md „Část 2 – Taktický design (kap. 6–9)“ × Chapters.php řadí kap. 09 (architectural_styles) do hubu `architecture` spolu s 10–11. Obdobně what_is_ddd.md#jak-cist. Buď přesunout kap. 9 do „Části 3“, nebo to v předmluvě nechat jako záměrné.

## Shrnutí jazykových úprav
- Kalky a „dává smysl“: 9× nahrazeno („vyplatí se“, „hodí se“, „stojí za to“). Dále „je o tom, kdy“ → „rozebírá, kdy“; „řeší *jak* …“ bez čárky a „styl zaměřený na *jak nasazovat*“ → „řeší, *jak* …“; „Ne proto, že … Ale protože …“ → přímé věty; „onboarding … než začne dělat smysluplné PR“ → „Nový kolega potřebuje dva měsíce, než pošle první smysluplný PR“.
- Kýč a pointy: vyřazeno „Strategie se zhmotnila v adresáři“, „To je strategická investice“, „Přesně tak má Generic vypadat“, „je to *strategický kompas*“, „80 % iluze“, „Nezpochybňujte ji, kupte si ji rozumně“, „Tabulka, kterou by měl mít na zdi každý…“, „Do dvou let firmu předběhne…“ (hypotéza podaná jako fakt) a kontrast „není kosmetická – vynucuje“.
- Soudy psané imperativem převedeny na oznamovací věty (vendor lock-in callout, „pracujte s ním jako s pozorováním“, „Pravidlo: chraňte se…“). Kroky (audit, workshop, pattern callouty) ponechány v imperativu.
- Opakování a paralelismy: „Glosář… Glosář…“, „Pokud váháte… Pokud váháte…“, „pak… pak…“, dvojí „Pořadí kapitol je promyšlené“. Sjednocen „adaptér“ v próze (dříve střídavě „adapter“), velká písmena v seznamu 01.08, anglické uvozovky '…' → „…“, „0.1 %“ → „0,1 %“.
- Gramatika: „core, které vytváří“ → „které ji odlišují“; „Hidden Core je ta, kterou“ → „jádro, které“; „Supporting je jednoduchý … sedí na ni“ → shoda rodu; „investiční prioritu na další 12–18 měsíců“ → „na dalších“.
- Zkrácení je mírné (preface −2,4 %, what_is_ddd −1 %, subdomains −1 % slov), protože část slov přibyla při věcných opravách (LTS, helpdesk, composer). Texty jsou z velké části hutné. U what_is_ddd jsem kvůli SEO nechal meta/description beze změny a úvod upravil jen lokálně (první věta „Než se ponoříme do definic, podíváme se…“ → „Před definicemi nejdřív konkrétní situace, ve které DDD pomáhá.“).

Kontroly: lint-php-snippets 3 bloky / 0 chyb; check_anchors v mých souborech OK (jediný nález /glosar#term-partnership je v cizím souboru); check_faq_yaml OK; check_tonality – 0 nálezů ve všech třech souborech. `modified: 2026-09-23` nastaveno ve všech třech.

---
<!-- reports/g2.md -->
# g2 – context_mapping (03), event_storming (04), team_topologies (05)

## Opravené nepravdy / věcné chyby
- team_topologies.md:#cognitive-load – „Cíl: maximalizovat intrinsic + germane, minimalizovat extraneous" a „intrinsic se nedá snížit" → Skelton & Pais (kap. 3) radí intrinsic zátěž minimalizovat (zaškolení, volba technologií), extraneous odstranit a prostor nechat germane. Text i odrážka o intrinsic load opraveny.
- team_topologies.md:#westrum – řádek tabulky „Chyby: Trestány / Vedou k hledání viníků / Vedou k učení" neodpovídal Westrumovi (failure → scapegoating / justice / inquiry; „hledání viníků" je patologická, ne byrokratická reakce) → „Selhání: Hledá se obětní beránek / Vyvozuje se odpovědnost / Hledají se příčiny". Zároveň „hlavní prediktor úspěchu DevOps transformací" → „prediktor výkonu doručování softwaru" (Accelerate netvrdí „hlavní").
- team_topologies.md:#inverse-checklist – DORA lead time „z PR-merge do produkce" a change failure rate „% deploy s rollbackem" byly v rozporu s definicemi v 05.09 (od commitu; podíl nasazení, která způsobí incident) → sjednoceno s 05.09 / DORA.
- team_topologies.md:#summary – „7 BC v Context Mapu a 3 týmy … vaše BC neexistují" odporovalo vlastnímu pravidlu (tým smí vlastnit 2–3 BC; 05.02 „7 BC a 4 týmy" = vědomé rozhodnutí) → přeformulováno na BC, ke kterým nejde jmenovat tým.
- team_topologies.md:FAQ „Mohu mít 1 tým, který vlastní 5 BC" – „Vernon (2013) připouští … v praxi 1–2, výjimečně 3" připisovalo Vernonovi čísla, která podle vlastního shrnutí kapitoly nejsou od něj → „kolik, neuvádí; tato kniha doporučuje 1–2, výjimečně 3".
- team_topologies.md:FAQ „management nesouhlasí" – nepodložené „eliminuje 30–50 % předávání" → „část předávání tím zmizí" (kapitola jinde výslovně přiznává, že měření chybí).
- team_topologies.md:#inverse-real-world-heading – „Mikroservisy přišly automaticky" o Amazonu 2002 (anachronismus, pojem vznikl ~2011) → „architektura služeb za API z něj vyplynula".
- team_topologies.md:#scenar-startup – hosting „Heroku, Vercel, …" pro Symfony monolit (Vercel PHP nativně nepodporuje) → „Upsun (dříve Platform.sh), Heroku, Railway, Fly.io".
- team_topologies.md:#dalsi-cetba – číslování seznamu neodpovídalo inline citacím [[1]]–[[9]] (např. [[4]] v textu = 2. vydání TT, v seznamu Accelerate) → seznam přečíslován podle inline odkazů. Tune (2024) doplněn o spoluautora Perrina (shodně s kap. 04). „Accelerate od Forsgrena" → „od Nicole Forsgren".
- team_topologies.md:#cognitive-load-rubric – rozpor „jednou za kvartál" × „vyplní se na konci sprintu" → sjednoceno na kvartál (rubrika sama má „Akce na další kvartál"). Tabulka velikostí týmu: „7–9 lidí" → „6–9" (6 chybělo).
- context_mapping.md:#acl – „ACL je nejčastěji používaný vztah" odporovalo vlastní taxonomii kapitoly (ACL je role, ne vztah) → „ze všech rolí". Totéž u definic Conformist a OHS („asymetrický vztah"/„vzor" → role downstreamu/upstreamu).
- context_mapping.md:#acl – citace Azure Architecture Center [5] stála v odstavci o zanedbatelném sémantickém rozdílu, ale tvrdila něco jiného (vrstva bez obchodních pravidel). Obsah zachován: tvrzení o obchodních pravidlech přesunuto k pravidlu „ACL drží jednu odpovědnost", k sémantickému rozdílu doplněno, co Azure skutečně uvádí (vzor se nemusí hodit, když mezi systémy nejsou výrazné sémantické rozdíly).
- context_mapping.md:#acl (kód LegacyBillingTranslator) – docblock translátoru stál nad DTO (dva docblocky za sebou) → přesunut nad `final class LegacyBillingTranslator`. Anglické uvozovky v českých komentářích → „“.
- context_mapping.md:#shared-kernel – `EmailAddress` → `Email` (kanonický VO podle CLAUDE.md).
- event_storming.md:#design-level-comment-heading – ukázka `throw new EmptyOrderException();` × `EmptyOrderException::cannotConfirm()` v kódu Order o dva odstavce výš; „Invariant Order-3" × totéž pravidlo jako „Inv-1" v 04.12 → sjednoceno na `EmptyOrderException::cannotConfirm()` a „Inv-1".
- event_storming.md:#dl-mapping – komentář `// Domain/Order.php` × namespace `App\Ordering\Domain\Model` → `// Domain/Model/Order.php`.
- event_storming.md:#bp-postup – pivotal event `PaymentSettled` × `PaymentReceived` ve zbytku kapitoly (sekvence 04.05.3, events.md) → `PaymentReceived`.
- event_storming.md:#tdd-events – test volá nedefinované `$this->collectEvents()` → doplněn komentář, že jde o vynechaný testovací spy (logika beze změny).
- event_storming.md:#anti-word-heading – vymyšlené „ztratíte 80 % informace" → „ztratí většinu informace".

## NEJISTÉ (neopraveno)
- context_mapping.md:#separate-ways – „Když se zákazník odhlásí v Identity, Marketing pořád může poslat kampaň … Zato je levné a v souladu s legislativou." – Při Separate Ways se Marketing o odvolání souhlasu v Identity nikdy nedozví; GDPR/ePrivacy (i CAN-SPAM se lhůtou 10 pracovních dnů) vyžaduje odvolání respektovat. Tvrzení „v souladu s legislativou" je přinejmenším sporné; příklad by potřeboval buď jediné místo pro opt-out (SendGrid), nebo slabší formulaci. Nechávám autorovi, jde o zásah do příkladu.
- context_mapping.md:#conformist (kód StripePaymentReportRepository) – `generateMonthlyRevenue()` nepočítá tržby a měsíc shora neohraničí (bere vše od 1. dne měsíce, limit 100). Jako ilustrace Conformistu funguje, název metody ale slibuje víc. Návrh: přejmenovat na `listPaymentsSince()` nebo doplnit horní mez.
- context_mapping.md:#published-language – `jane-php/open-api` je název z Jane ≤ 5; aktuální balíček je `jane-php/open-api-3`. Neověřeno, kterou verzi text míní.
- context_mapping.md:#ohs – „Časový bod v Sunset nesmí předcházet ten v Deprecation" – RFC 9745 to podle mé paměti formuluje jako SHOULD NOT, ne MUST NOT. Neověřeno.
- event_storming.md:#proc-workshop – „Evans … píše, že Ubiquitous Language nelze odvodit z dokumentů; vzniká pouze v dialogu" – Evans staví UL na mluvené řeči a dokumenty označuje za doplněk, „nelze" a „pouze" jsou ale silnější než jeho text. Kandidát na zjemnění.
- team_topologies.md:#scenar-enterprise – „10–15 stream-aligned týmů" při 200+ lidech × orientační 75 % lidí ve stream-aligned týmech (≈150 lidí → při 5–9 lidech ~17–30 týmů). Nesedí řádově, ale jde o orientační čísla; nechávám autorovi.

## Nekonzistence vůči jiným kapitolám (neopraveno)
- event_storming.md „`PaymentReceived`" (nyní v celé kap. 04) × sagas.md:1854 „sága `OrderProcess` čeká na `PaymentSettled`" – návrh: sjednotit jméno platební události napříč knihou (kanonicky u Order je `OrderPaid`, na straně Payment BC jedno jméno – `PaymentReceived`, nebo `PaymentSettled`).
- context_mapping.md / event_storming.md odkazový text „Migrace z CRUD do DDD" / „Migrace z CRUD na DDD" × titul migration_from_crud.md „Migrace z CRUD architektury na DDD" – návrh: odkazové texty sjednotit na „Migrace z CRUD na DDD".
- team_topologies.md skloňovalo „Context Map" v mužském rodě (Context Mapem, v Context Mapu) × context_mapping.md v ženském (Context Mapy, na Context Mapě) – v kap. 05 sjednoceno na ženský rod; jinde v knize jsem mužský tvar nenašel.

## Shrnutí jazykových úprav
- Kalky a anglický slovosled: „Tato kapitola je o…" → „Kapitola popisuje…", „Team Topologies není o mikroservisech" → „Team Topologies mikroservisy neřeší", „dává smysl pro Catalog model" → „patří do Catalog modelu", „konzumuje 15–30 minut" → „zabere 15–30 minut", „Toto je výchozí stav…" → „Ve zralé organizaci je to výchozí stav…", zbytečné „tento/tato/toto" škrtnuty, podmínky „Pokud…" místy nahrazeny „Je-li/Má-li…".
- Kýč a pointy: „Toto je důvod, proč je ACL tak silný – a tak křehký", „jeden z nich vyhraje a ten druhý se rozsype", „Nesoulad je pomalý jed", „Multiplikátorů nemá být víc než multiplicandů", „To je smutná, ale realistická diagnóza", „Zbývá jen práce", „úzkoúhlý teleobjektiv" – vyřazeno nebo přepsáno na věcné tvrzení.
- Vatové úvody a řečnické otázky: „Proč tu mapu nakreslit? Protože…", „Představme si situaci:", „Zde je seznam…", „Pojmenování… Detail viz…" přepsány na přímé věty; zdvojené věty (např. „Reorganizace bez podpory shora se v praxi neudělá vůbec" po téže myšlence) škrtnuty.
- Imperativy v soudech převedeny na oznamovací větu (např. „Použít jen tehdy, když…" → „Patří jen tam, kde…"), imperativ ponechán v krocích, patternech a nápravách.
- Anglicismy a terminologie: „bottleneck" → „úzké hrdlo", „versionování" → „verzování", „roadmap" (m.) → „roadmapa", „customer satisfaction" → „spokojenost zákazníků", pathological/bureaucratic/generative v próze → patologická/byrokratická/generativní (anglické termíny ponechány v záhlaví tabulky); „translator/translátor" sjednoceno na „translátor".
- Rozsah: zasaženo zhruba 150 odstavců a odrážek; přepsané pasáže jsou o 10–20 % kratší, celkový počet slov včetně kódu klesl o 2–3 % (8902→8682, 8563→8399, 7792→7702). Věcný obsah, citace, čísla a příklady zachovány, texty, které už byly hutné, jsem neměnil.

## Kontrolní skripty
- `php scripts/lint-php-snippets.php context_mapping.md event_storming.md team_topologies.md` → 12 bloků, 0 chyb
- `php scripts/check_anchors.php` → OK
- `php scripts/check_faq_yaml.php` → OK
- `php scripts/check_tonality.php <soubor>` → 0 nálezů ve všech třech souborech
- `modified:` nastaveno na 2026-09-23 u všech tří kapitol

---
<!-- reports/g3.md -->
# g3 – kap. 06 basic_concepts, 07 aggregate_design, 08 lesser_known_patterns

Kanonické API z kap. 06 (AggregateRoot, record/releaseEvents, Email, Money, Currency, Order, OrderItem, identifikátory) NEBYLO změněno.

## Opravené nepravdy / věcné chyby
- lesser_known_patterns:spec-doctrine – „stejnojmenný článek Benjamina Eberleie (2013) se Specification zabývá jen okrajově“ → Eberleiův článek *On Taming Repository Classes in Doctrine* (4. 3. 2013) má Specification jako hlavní téma (specifikace upravující QueryBuilder) a inspiroval `happyr/doctrine-specification`; Gomez (2015) na něj navazuje. Ověřeno: beberlei.de/2013/03/04/doctrine_repositories.html a README balíčku na Packagistu (964 tis. instalací, doctrine/orm ^2.17 || ^3.0, takže tvrzení „přes 900 tisíc, ORM 3“ sedí).
- lesser_known_patterns:spec-doctrine – `matchWithCustomer()` dělal `->join('o.customer', 'c')`, přitom `Order` nemá asociaci na Customer (drží jen `CustomerId`, konvence „reference jen přes ID“) → `matchWithItems()` s `join('o.items', 'i')` + věta, proč join vede přes asociaci uvnitř agregátu. ZMĚNA KÓDU.
- lesser_known_patterns:spec-doctrine – `toExpression()` filtruje přes vlastnosti `totalAmount`/`totalCurrency`, které kanonický `Order` nemá → doplněna věta, že dotazová podoba předpokládá denormalizovaný součet (Criteria pracuje jen s mapovanými vlastnostmi).
- lesser_known_patterns:ds-priklad – docblock MoneyTransferService „bez vedlejších efektů na kolaborátorech“, přitom mění oba účty → „mezi voláními nic nedrží, mění jen agregáty, které dostane“ (komentář).
- lesser_known_patterns:ds-priklad – převod mezi dvěma účty v jedné transakci je v kap. 07 (07.05) výslovně anti-vzor; kap. 08 ho předkládala jako vzorovou doménovou službu bez komentáře → doplněn odstavec, který rozpor pojmenuje a odkazuje na #transactional-consistency a #breaking-the-rule; u „koordinátora dvou agregátů“ poznámka o pravidlu jeden agregát na transakci.
- lesser_known_patterns:fac-class – „Statická metoda nemůže DI přijímat bez service locatoru“ – vyvrací to vlastní `fromImport(…, CustomerLookup $lookup, …)` → přeformulováno: jednu službu jde předat parametrem, s více závislostmi je přehlednější factory class z containeru (próza, komentář v kódu i FAQ).
- lesser_known_patterns:mod-composer – services.yaml vylučoval `src/*/Domain/` a zpět registroval jen `Domain/Service/`, takže `OrderFromCartFactory` (v `Domain/Factory/`, potřebuje DI) by nebyla službou → přidán blok `App\Ordering\Domain\Factory\`. ZMĚNA KONFIGURACE.
- lesser_known_patterns:mod-bc – strom adresářů (`Domain/Order.php`, `Domain/OrderRepository.php`, `Infrastructure/Doctrine/OrderMapping.orm.xml`) odporoval namespacům v kódu (`Domain\Model\Order`, `Domain\Repository\OrderRepository`, `Infrastructure\Repository\DoctrineOrderRepository`, atributové mapování) i textu o PSR-4 → strom sjednocen; `use App\Ordering\Domain\Pricing\PricingService` → `Domain\Service\PricingService` (sedí se stromem i services.yaml). ZMĚNA KÓDU (use).
- lesser_known_patterns:mod-phparkitect – pravidlo „Domain nesmí importovat nic z Doctrine“ odporovalo specifikacím v téže kapitole (Doctrine\Common\Collections\Criteria) → „ze Symfony ani z Doctrine ORM“, `doctrine/collections` jako vědomá výjimka.
- lesser_known_patterns:type-pack-heading – „Symfony skeleton historicky zaváděl src/Entity, src/Service…“ → skeleton + MakerBundle zakládají Entity/Repository/Controller dodnes, `src/Service/` si přidávají týmy.
- lesser_known_patterns:mod-kontrakt – odkaz `(/ddd-a-\nmicroservices#modular-monolith)` byl rozlomený přes řádek → spojen.
- lesser_known_patterns:fac-definice – `Order::physical()` / `Order::digital()` → `placePhysical()` / `placeDigital()` (jména z kódu).
- lesser_known_patterns:úvod – „čtyři pilíře: Entity, Value Object, Aggregate a … Domain Service a Factory“ (vyjmenováno pět) → přepsáno.
- lesser_known_patterns:summary – „Evansova část III, *Supple Design*“ → „kapitola *Supple Design*“ (v knize je to kap. 10 v části III „Refactoring Toward Deeper Insight“; „část III Supple Design“ platí jen pro DDD Reference). „PHP i DQL podobu“ → „dotazovou podobu (Doctrine Criteria)“.
- lesser_known_patterns:fac-static – `private readonly OrderId $id / CustomerId $customerId` → `public readonly` (konvence knihy `$order->id`, NotInBlacklist v téže kapitole čte `$candidate->customerId`). ZMĚNA KÓDU (viditelnost, varianta Order, ne kanonický).
- aggregate_design:transactional-consistency – Helland (2007) „šest let před Vernonem“ → „čtyři roky před Vernonovou esejí“ (kapitola cituje eseje z 2011).
- aggregate_design:vernon-rules – sága jako „dvoufázový proces“ (plete se s 2PC) → „vícekrokový proces“.
- aggregate_design:references-by-id – „Při souběžném přístupu vzniká skrytý zámek“ (lazy load v Doctrine nic nezamyká) → skrytá vazba: změnu provedenou přes proxy uloží nejbližší flush().
- aggregate_design:hot-aggregate (+FAQ) – „ES eliminuje race condition na update“ → zápis append-only, souběh hlídá očekávaná verze streamu, konflikt jde posoudit podle přibylých událostí.
- aggregate_design:references-by-id – próza tvrdila, že každá událost nese `occurredAt`, `OrderItemAdded` ho neměl → doplněn `public \DateTimeImmutable $occurredAt` plněný v konstruktoru (stejný vzor jako `OrderPlaced` v kap. 06). Signatura konstruktoru beze změny (3 argumenty), volání v jiných kapitolách nerozbije. ZMĚNA KÓDU.
- aggregate_design:references-by-id – „všechny tři výjimky mají pojmenované továrny“ – `OrderLockedBySagaException` se v kap. 14 i zde vytváří konstruktorem → próza opravena.
- aggregate_design:references-by-id – komentář o čase ve storno lhůtě stál nad `lockForSaga()` místo nad `cancel()` → přesunut (bez změny počtu řádků v bloku). Výčet přechodů v próze doplněn o `confirm()`; smíšené uvozovky „…" v komentáři opraveny.
- aggregate_design:symfony-doctrine – anotační syntaxe `cascade={"persist","remove"}`, `@Version` (ORM 3 anotace nemá) → `cascade: ['persist', 'remove']`, `#[ORM\Version]` (próza, komentář v repozitáři, FAQ). Komentář u Money: „#[ORM\Embedded] v Order ani OrderItem“ → jen OrderItem (Order Money neembedduje).
- aggregate_design:eventual-consistency – `OrderCanceledDueToOutOfStock` → `OrderCancelledDueToOutOfStock` (kniha píše Cancelled).
- aggregate_design:design-canvas – „Dvě pole nemá žádná z klasických knih“ + výčet tří polí, z nichž Throughput/Size je výslovně Vernonova metoda → „dvě části formuláře jako kolonku nenajdete“.
- aggregate_design:symfony-doctrine – odkaz na kap. 08 s textem „Méně známé vzory“ → skutečný název „Doplňující taktické vzory“.
- basic_concepts:entity-identity – „Rodné číslo, ISBN i IČO se mění a recyklují“ (IČO se podle zákona znovu nepřiděluje) → „mění se, bývají přidělené chybně nebo duplicitně“.
- basic_concepts:money – „Částka je celé číslo v haléřích“ u vícesměnového Money → „v nejmenších jednotkách měny (haléřích, centech)“.
- basic_concepts:aggregates – „ani jedna Doctrine anotace“ → „atribut“ (PHP 8 / ORM 3).

## NEJISTÉ (neopraveno)
- aggregate_design:vernon-rules – „Dovětek o čí práci Vernon do formulace pravidla přidal ve třetím dílu série“ – podle mé paměti je „Ask Whose Job It Is“ už v Part II (*Making Aggregates Work Together*). Ověřit v PDF eseje.
- aggregate_design:vernon-rules – „Vernon to podkládá číslem z projektu, který analyzoval: zhruba 70 %…“ – u Vernona si pamatuji spíš obecné „až 70 % agregátů“ bez vazby na jeden analyzovaný projekt. Ověřit formulaci.
- aggregate_design:references-by-id – „Ve třetím dílu série jeho tým kvůli režii dotazů zvolí přímou lazy-loaded referenci“ a breaking-the-rule „Vernon celou sérii uzavírá poznámkou, že se pro porušení vodítek nehledají výmluvy“ – nemám jak ověřit.
- aggregate_design:workflow – Vernonův výpočet „dvanáct tasků, dvanáct záznamů o přeodhadu, celkem nejvýš pětadvacet objektů“ – aritmetika sedí jen pro jeden task s 12 záznamy (1+12+12); ověřit, jak přesně Vernon počítá.
- aggregate_design:symfony-doctrine – highlights bloku `Order.php` (25,32,33,37–39,62–68) podle mého počítání míří na prázdné řádky a komentáře, ne na zvýrazňovaný kód (asi posun po dřívějších úpravách). Markup jsem neměnil; řádky 1–68 bloku zůstaly beze změny.
- lesser_known_patterns:spec-doctrine – datum Gomezova článku „7. 2. 2015“ jsem neověřil.

## Nekonzistence vůči jiným kapitolám (neopraveno)
- aggregate_design (a basic_concepts) mapují Doctrine atributy přímo na doménových třídách (`src/Ordering/Domain/Model/Order.php` s `use Doctrine\ORM\Mapping`) × testing_ddd #architektonicke-testy: Deptrac hlásí `Doctrine\ORM\Mapping` v Domain jako porušení („UserManagement\Domain\Model\User must not depend on Doctrine\ORM\Mapping\Column“). Kap. 08 teď Doctrine ORM v Domain zakazuje (v souladu s kap. 17). Návrh: v kap. 17 (nebo 07) jednou větou říct, že atributy na doméně jsou vědomý kompromis knihy a Deptrac pravidlo platí pro variantu s Persisted Object Pattern, nebo Deptrac v kap. 17 povolit `Doctrine\ORM\Mapping` jako výjimku.
- aggregate_design `OrderItemAdded` má nově `occurredAt` (plní se v konstruktoru) × event_sourcing `final class OrderItemAdded extends DomainEvent` s jinou stavbou – ES varianta je samostatný model, jen pro kontrolu, že se neodkazuje na tvar z kap. 07.

## Shrnutí jazykových úprav
- Kalky a nominalizace: „dává smysl“ → „má význam / vyplatí se“, „Pokud byste musel“ → „museli“, „nedotahuje do“ → „nesahá do“, „Compiler-friendly invariant“ → „Invariant hlídá jazyk, ne disciplína“, „data structure“ → „datová struktura“, „Na vině je reconstitution“ → „Důvodem je“.
- Kýč a patos pryč: „čtyři pilíře“, „model se rozplyne“, „zabíjí škálování“, „uživatelská zkušenost se hroutí“, „Intuice je to chybná“, „Rozhraní vypadá triviálně, ale stojí za ním celá architektonická volba“, „moduly nekoukají do sebe“, „Není to PHP feature, není to namespace – je to princip“.
- Vata a řečnické otázky: vyřazen přechod „Začneme vzorem, který…“, řečnické úvody („Kdo událost vytvoří…? Odpověď má dvě části.“, „Co odlišuje uživatele…? Identita.“), callout „Co od kapitoly očekávat“ zkrácen z 8 na 4 věty, duplicitní odstavce (Khononovova diagnostika v 07.05, Evansova výjimka pro vnitřní člen v 07.12, getter/private(set) po ukázce) nahrazeny odkazem.
- Imperativ v soudech → oznamovací věty (FAQ kap. 07 „Použijte ji…“ → „Hodí se…“, „modelujte Section“ → „vznikne Section“, „Nedoporučujeme“ → „Kniha ho nedoporučuje“, kap. 08 „inlinujte / smazat“ → „patří inline / patří smazat“).
- Rytmus seznamů: v kap. 07 list „Typické chyby“ zbaven tučných leadů a rozkolísán; dlouhé řádky a věty přes 25 slov rozděleny.
- Zkrácení: prózy kap. 08 zhruba o 12–15 %, kap. 06 a 07 o 5–8 % (obě už byly hutné); celkový počet slov v souborech (včetně kódu) 22 194 → 21 922.

---
<!-- reports/g4.md -->
# g4 – architectural_styles.md (09), implementation_in_symfony.md (10)

## Opravené nepravdy / věcné chyby
- architectural_styles.md:layered-symfony-heading – „`make:repository` generuje strukturu“ → MakerBundle žádný `make:repository` nemá; repository vzniká s `make:entity`, formulář přes `make:form`. `Service/` skeleton negeneruje, text teď mluví jen o `Controller/`, `Entity/`, `Repository/`.
- architectural_styles.md:layered-priklad-heading – „Doménová vrstva potřebuje knihovnu z Infrastructure, aby se vůbec dala zkompilovat“ → PHP atributy se vyhodnocují až při reflexi; nově „importuje knihovnu z Infrastructure a nese její metadata“.
- architectural_styles.md:layered-controller-heading – próza tvrdila, že entitu načítá Application Service; v kódu ji načítá controller a předává ji službě. Próza teď odpovídá kódu.
- architectural_styles.md:onion – Onion „zavádí Dependency Rule“ → pravidlo „závislosti jen dovnitř“ je Palermovo, ale název Dependency Rule pochází od R. C. Martina (Clean Architecture, 2012). Doplněna správná atribuce.
- architectural_styles.md:clean – „zdrojový kód směřuje jen směrem dovnitř“ → „závislosti ve zdrojovém kódu směřují jen dovnitř“ (Martinova formulace).
- architectural_styles.md:clean-pattern-heading – „Symfony Messenger Bus ≈ ‚interactor‘ routing v Clean“: to je chybně. U Martina *interactor* = sám use case. Opraveno, bus v Clean přímý protějšek nemá. Nadsázka „Clean Architecture zadarmo“ nahrazena věcným tvrzením.
- architectural_styles.md:hexagonal-event-port-heading – „Doména volá EventPublisher::publish()“ → v ukázce ho volá use case (aplikační vrstva).
- architectural_styles.md:proc-styl – mezi věcmi, kvůli kterým nejde testovat bez infrastruktury, byly uvedené „Doctrine anotace“. To je v rozporu s kap. 10 (atributy jsou inertní metadata) → nahrazeno přímými voláními EntityManageru. Na několika místech (strom, tabulka, anti-vzor 2 včetně textu nadpisu, bullet u Layered) je „anotace“ nahrazeno „atributy“, protože ORM 3 anotace nemá. Kotvy zůstaly beze změny.
- architectural_styles.md:anti-1-heading – výčet má 6 souborů, text uváděl „sedm“ → „šest“.
- architectural_styles.md:clean-priklad-heading – docblock `PlaceOrderRequest::$items` neobsahoval `unitPriceInCents`, přitom ho use case čte → doplněno.
- architectural_styles.md – vnitřní nekonzistence namespaců: `Model\OrderId` (port, adaptér, stromy) × `ValueObject\OrderId` (handlery). Sjednoceno na `ValueObject\OrderId` (konvence celé knihy, 45 výskytů). Stromy a callout „Konvence struktury v této knize“ používaly `Shared/`, kód ale `App\SharedKernel\…` → sjednoceno na `SharedKernel/` (konvence ostatních kapitol).
- architectural_styles.md:symfony-config-heading – popisek bloku „config/services.yaml (výřez: sběrnice)“ neodpovídal obsahu (importy a exclude) → „(výřez: importy a vyloučení)“.
- architectural_styles.md:further-reading – číslování seznamu literatury nesedělo s citacemi `[[N]]` v textu (např. [4] v textu = Garrido, v seznamu = Palermo). Seznam je přeřazený tak, aby 1–12 odpovídalo citacím. Anglické zbytky („foundational patterns“, „Original … article“) přeloženy.
- implementation_in_symfony.md:enum-usage-heading – „`markPaid()` nevydá nic“ je v rozporu s kanonickým rozhodnutím (markPaid nahrává OrderPaid) i s předchozí větou → „`markPaid()` vydá `OrderPaid`, `transitionTo()` vydá `OrderStatusChanged`“.
- implementation_in_symfony.md:payment-aggregate-heading – „invariant vynucuje typový systém“ → vynucuje ho agregát (zapouzdření), ne typový systém.
- implementation_in_symfony.md:anti-payment-service-heading – anti-vzor volal `$order->id()`, `Order` má `public readonly OrderId $id` → `$order->id`.
- implementation_in_symfony.md:doctrine-hydration-heading – „Doctrine ORM 3 to řeší přes custom DBAL types“ naznačovalo novinku ORM 3; custom typy i embeddables existují dávno → formulace bez vazby na verzi. Odstraněna meta-zmínka „v dřívějších verzích průvodce“ (tamtéž a v #register-race-heading).
- implementation_in_symfony.md:email-validate-limits-heading – „egulias/email-validator používá Symfony Validator pod kapotou“ → používá ho jen `Assert\Email` v režimu `strict` (výchozí html5 jde přes regex; sama kapitola to v #validace-kde-heading uvádí).
- implementation_in_symfony.md:php84-vo-zapis – „readonly zakazuje zápis i zevnitř třídy“ → „po inicializaci zakazuje jakýkoli zápis, i zevnitř třídy“.
- implementation_in_symfony.md:user-name-vo-heading – `UserName` byla `final class` s readonly vlastností, přitom text tvrdí „VO v celém průvodci zůstávají `final readonly`“ → `final readonly class` (lint OK).
- implementation_in_symfony.md:project-structure – strom měl `Ordering/Domain/ValueObject/Money.php`, kód i komentář v services.yaml ale `App\SharedKernel\Domain\Money` → Money, Currency a AggregateRoot přesunuty do `SharedKernel/Domain/`, v Ordering nahrazeny `OrderStatus.php`.
- implementation_in_symfony.md:ddd-vs-symfony-boundary – „doména bez závislosti na frameworku, Doctrine jde vyměnit bez dotčení domény“ bylo v rozporu s výchozí volbou atributů → doplněno, že jedinou stopou jsou mapovací atributy a při výměně ORM se přepíšou metadata.
- implementation_in_symfony.md:domain-events – „agregát událost publikuje“ × #repositories „publikuje aplikační vrstva“ → „agregát zaznamená“.
- implementation_in_symfony.md:dispatcher-vs-messenger-heading – „každá zpráva projde JSON serializací“ platí jen u asynchronního transportu → zpřesněno.
- implementation_in_symfony.md:exception-types-heading – `DuplicateEmailException` byla uvedená jako aplikační výjimka, přitom leží v `Domain\Exception` a sekce #duplicate-email-exception-heading ji nazývá doménovou → přesunuta mezi doménové. Odstraněna `InvalidEmailException`, kterou nic nevyhazuje (Email VO hází `\InvalidArgumentException`).
- implementation_in_symfony.md:specification-pattern – „s jedinou metodou isSatisfiedBy()“ hned vedle kombinátorů and/or/not → „s metodou“.
- implementation_in_symfony.md:symfony-idiomy-asalias – směr aliasu byl obráceně („DoctrineUserRepository jako alias na rozhraní“) → rozhraní je alias na implementaci.
- implementation_in_symfony.md:alias-vs-class-heading – „`class:` → dva EntityManagery, dvě sady listenerů“ je chybně: služby jsou sdílené, obě instance dostanou týž EM → přepsáno (zdvojí se vnitřní stav a dekorace/tagy).
- implementation_in_symfony.md:autowiring-bc-example-heading – „Poslední čtyři řádky jsou aliasy“ → aliasů je osm. Nepravdivé tvrzení „Symfony je neuhodne, protože rozhraní a třída leží v jiném jmenném prostoru“ je v rozporu s kap. 09 (#hexagonal-symfony-di-heading): automatický alias vzniká, když jediná implementace leží ve stejném bloku `resource`. Opraveno. Vypuštěno i tvrzení, že chybějící alias se projeví až za běhu jako „handler not found“: chybějící autowiring závislost používané služby shodí kompilaci kontejneru.
- implementation_in_symfony.md:autowiring-bc-example-heading (callout Výhody) – vypuštěno tvrzení, že se „nechtěný import třídy z cizího kontextu pozná přímo v konfiguraci“. Importy tříd se v services.yaml neprojeví.

## NEJISTÉ (neopraveno)
- architectural_styles.md:hexagonal-granularita-heading – „Mechanismus pod porty pojmenoval Gerard Meszaros v roce 2011 jako Configurable Dependency“. Rok 2011 nejde bez zdroje ověřit (Meszarosova kniha xUnit Test Patterns je z roku 2007). Doporučuji ověřit v rozhovoru [4] nebo rok vypustit.
- architectural_styles.md:clean-objectmapper-heading „stabilní od verze 8.0 (v 7.3 byla experimentální)“ × implementation_in_symfony.md:persisted-object-pattern „od Symfony 7.4 … stabilní komponenta symfony/object-mapper“. Obě kapitoly jsou moje, ale nevím, zda byla experimentální značka odstraněná už v 7.4. Verze 7.4 a 8.0 vycházely současně, takže tvrzení si možná neodporují. Je potřeba ověřit na symfony.com a formulace sjednotit.
- implementation_in_symfony.md:doctrine-custom-types – „XML je po odstranění annotation a YAML driveru v ORM 3 jedinou neatributovou variantou“. ORM 3 má podle všeho pořád PHP mapping (`StaticPHPDriver`/`PHPDriver` z doctrine/persistence). Pokud platí, patří sem „vedle málo používaného PHP mappingu“.
- implementation_in_symfony.md:php84-vo-zapis – „Hook se spustí při každém zápisu včetně hydratace z databáze“. ORM 3.4 podle mých informací při hydrataci obchází hooky přes raw value (`setRawValue`). Tomu by odpovídal i callout #property-hooks-orm-heading, podle kterého DQL pracuje s uloženou hodnotou. Pokud je to tak, argument odstavce neplatí. Doporučuji ověřit.
- implementation_in_symfony.md:email-validate-limits-heading – „FILTER_VALIDATE_EMAIL ověřuje syntaxi podle zjednodušeného RFC 5322“. Manuál PHP uvádí RFC 822 (addr-spec) bez komentářů, foldingu a domén bez tečky.
- implementation_in_symfony.md:form-nad-commandem-heading – u `data_class` na readonly commandu s povinným konstruktorem pravděpodobně selže už instancování (`ArgumentCountError`/`empty_data`), ne až PropertyAccess s `NoSuchPropertyException`. Závěr (formulář vrací pole) platí tak jako tak.

## Nekonzistence vůči jiným kapitolám (neopraveno)
- implementation_in_symfony.md:error-handling „rozšířený aktivační model … v kapitolách [Migrace z CRUD] a [Anti-vzory]“ × kanonické rozhodnutí „aktivační model je v kap. 18“ (Migrace z CRUD). Stojí za ověření, zda se v anti_patterns.md aktivační model skutečně objevuje. Pokud ne, odkaz na Anti-vzory zrušit.
- implementation_in_symfony.md strom: `Checkout/Command/CreateOrder.php` + `CreateOrderHandler.php` (stejně basic_concepts.md:976 „CreateOrderHandler.php“) × kanonická factory `Order::place()` a `PlaceOrder`/`PlaceOrderHandler` v architectural_styles.md, cqrs apod. Návrh: přejmenovat command na `PlaceOrder`.
- architectural_styles.md:hexagonal-priklad-heading `OrderRepository::findByCustomer(string $customerId)` × kanonický vlastník `CustomerId` (VO). Návrh: typovat `CustomerId`.
- architectural_styles.md (Hexagonal) má porty v `Domain/Port/`, implementation_in_symfony.md a zbytek knihy `Domain/Repository/`. Jde o záměrnou ilustraci stylu, jen pro informaci.

## Shrnutí jazykových úprav
- Kalky a hovorové tvary: „tří-vrstvé“ → „třívrstvé“, „principiálními vrstvami“ → „hlavními“, „přibývají na komplexitě“ → „nabývají na složitosti“, „přes který se dá lehce přenést“ (ve smyslu „snadno přehlédnout“) → „který se často přehlédne“, „tenhle/tohohle/tyhle/tady“ → „tento/tohoto/tyto/zde“, „confirmed objednávka“ → „potvrzená“, „v ságe“ → „v sáze“, „getry/setry“ → „gettery/settery“, „Premature inverze“ → „Předčasná inverze“, „Cena pure varianty“ → „Cena čisté varianty“. Anglické popisky tabulky přeloženy („Junior friendly“, „tight/loose“, „CQRS přirozenost“).
- Kýč a patos: „Sem teče modelovací úsilí, sem teče čas…, sem teče…“ → jedna věta. Pryč je „Clean Architecture zadarmo“, „kde projekt vyhrává konkurenční bitvu“, „Junior to nezvládne“, „krásná struktura“, „hodně rituálu“, „dramatický rozdíl v chování“ i vymyšlené „5 ms místo 500“.
- Vata a opakování: škrtnuté úvody „Pro úplnost ukázka…“, „Následující sekce…“, „Tato volba není univerzální pravda“, wikipedijní úvod Layered sekce a zopakovaný popis horizontálního dělení v 09.06. Pryč jsou i pointy opakující předchozí větu („Ne každá část projektu si zaslouží stejnou investici.“, „Není co amortizovat.“).
- Imperativ v soudech převeden na oznamovací tvar („Nemíchejte styly…“ → „Uvnitř jednoho BC se styly nemíchají“, „Nikdy nemigrujte všechno najednou“ → „Migrace všeho najednou nese vysoké riziko regresí“).
- Zkrácení: kap. 09 zhruba o 190 slov (≈ 2 % celku, v próze bez kódu ≈ 7–8 %). Kap. 10 byla hutná a slovně je zhruba stejně dlouhá, protože opravy nepravd (hranice domény, aliasy, markPaid) text mírně prodloužily.

Kontroly: lint-php-snippets (49 bloků, 0 chyb), check_anchors OK, check_faq_yaml OK, check_tonality 0 nálezů v obou souborech.

---
<!-- reports/g5.md -->
# g5 – authorization_in_ddd.md (kap. 11), cqrs.md (kap. 12)

## Opravené nepravdy / věcné chyby
- authorization_in_ddd.md:ctyri-vrstvy – „`IDDD_Samples` … samostatný modul vedle Ordering a Collaboration“ → „vedle Collaboration a Agile PM“ (repozitář IDDD_Samples má moduly iddd_identityaccess, iddd_collaboration, iddd_agilepm, iddd_common; Ordering v něm není).
- authorization_in_ddd.md:no-symfony-acl – „ACL byla z jádra odstraněna ve verzi 6.0“ → „Podpora ACL zmizela ze SecurityBundle ve verzi 4.0“ (UPGRADE-4.0: ACL ze SecurityBundle odstraněno, náhradou symfony/acl-bundle; deprecace už v 3.4).
- authorization_in_ddd.md:aggregate-level, aggregate-trace-heading, aggregate-403-vs-409-heading, summary – stav „PLACED“ / „status === PLACED“ neexistuje; kanonický `OrderStatus` má Draft/Confirmed/Paid/Shipped/Delivered/Cancelled a `cancel()` v téže kapitole blokuje jen Shipped/Delivered + lhůtu. Přepsáno na „objednávka není odeslaná ani doručená a od potvrzení neuplynulo 24 h“; shrnutí nově uvádí i `CancellationWindowExpiredException`.
- authorization_in_ddd.md:aggregate-trace-heading krok 8 – „Controller vrátí 200 OK“ × kód `OrderController::cancel()` vrací `redirectToRoute` → „Controller přesměruje na detail objednávky“. Krok 5 doplněn o to, že asynchronní (kanonická) varianta handleru volá `isOwnedBy()` s `actorId` místo `isGranted()`.
- authorization_in_ddd.md:policy-based – poznámka mluvila o pravidle `subject.status.value == "placed"`, kód `CancelOrderPolicy` má `"confirmed"` → sjednoceno na `"confirmed"`.
- authorization_in_ddd.md:policy-based – tvrzení „subjektem politiky bývá snapshot, agregát s privátním stavem nemůže“ a tabulka „Subjekt: musí být snapshot s veřejnými poli“ kolidovaly s testem `CancelOrderPolicyTest`, který jako subjekt předává přímo `Order` (kanonický agregát má `public private(set)` / `public readonly`). Přeformulováno: subjekt musí mít veřejně čitelný stav – snapshot, nebo agregát s `private(set)`.
- authorization_in_ddd.md:multi-tenancy – `filename` bloku doctrine.yaml „(výřez: mapování identity)“ → „(výřez: filtr tenanta)“ (blok obsahuje jen konfiguraci filtru). Zároveň přesunut odstavec „Hodnotu parametru dodává kernel event listener…“ přímo před kód listeneru (dřív ho od kódu odděloval odstavec o one-to-one).
- authorization_in_ddd.md:audit-log-heading + FAQ – „GDPR čl. 30 vyžaduje audit log každého autorizačního rozhodnutí“ / „GDPR Article 30“ odstraněno: čl. 30 GDPR upravuje záznamy o činnostech zpracování, ne logování přístupových rozhodnutí. Nyní „Regulované domény (zdravotnictví, finance) často vyžadují…“, ve FAQ „(PCI DSS, zdravotnictví)“.
- cqrs.md:ec-priklad-heading – nadpis „ID generované na klientovi – command nemusí vracet hodnotu“ tvrdil opak kódu (`PlaceOrderController` bere `OrderId` z `HandledStamp`, komentář výslovně říká, že identitu generuje agregát) → „PHP: Post-Redirect-Get po vytvoření objednávky“ (kotva zachována).
- cqrs.md:command-navratova-hodnota-heading – „ID generovat na klientovi … Toto je preferovaný přístup“ kolidovalo s kanonickým `PlaceOrder` (ID vrací handler, generuje továrna). Obě varianty nyní popsány s trade-offem a odkazem na 12.12.
- cqrs.md:symfony-messenger – „Každý bus má vlastní … transport“ – bus v Messengeru transport nemá (transport se volí routingem zprávy). Škrtnuto.
- cqrs.md:async – „Tato konfigurace směruje příkazy pro odesílání e-mailů a generování reportů na asynchronní transport“ – v uvedeném YAML žádný takový routing není (jediný řádek je zakomentovaný `InvoiceIssued`). Přepsáno: konfigurace dává `async_events` retry strategii a přidává `async_priority_high`.
- cqrs.md:error-handling – výčet retry klíčů uváděl `jitter: 0.1`, konfigurace ve 12.13 má `0.2` → `jitter: 0.2 … (výchozí 0.1)`.
- cqrs.md:idempotence-heading – „Porovnává se řetězec“ (v PostgreSQL se porovnává TIMESTAMP s parametrem přetypovaným na timestamp) → „Čas se do dotazu předává jako řetězec, takže na formátu záleží“.
- cqrs.md:worker-heading – komentář „Konzumace zpráv z obou front“ u příkazu se třemi transporty → „ze všech tří front – pořadí určuje přednost“.

## NEJISTÉ (neopraveno)
- authorization_in_ddd.md:no-symfony-acl – „`symfony/acl-bundle` deklaruje ve svém posledním vydání podporu Symfony 4.4 až 7.0“ – nepodařilo se ověřit aktuální constraint posledního vydání.
- authorization_in_ddd.md:abac-vlastni-vs-voter – `Security::getAccessDecision()` (Symfony 7.3) – nejsem si jistý, zda metoda existuje na `Security`, nebo jen na `AbstractController`/jako parametr `isGranted(..., ?AccessDecision)`. Kniha ji používá na dvou místech (ukázka ExplainedAccessDenied + callout o audit logu).
- authorization_in_ddd.md:audit-log-heading – „od Symfony 7.4 i `$extraData`“ na `Vote` a „služba security.authorization_checker je od Symfony 6.0 privátní“ – neověřeno.
- authorization_in_ddd.md:abac-vlastni-vs-voter – „AuthZEN Authorization API 1.0, schválené v lednu 2026“ – datum neověřeno.
- authorization_in_ddd.md:rebac-php-heading – „`evansims/openfga-php` je na Packagistu označený jako abandoned“ – neověřeno (balíček byl v roce 2025 aktivně vyvíjen).
- authorization_in_ddd.md:edge – `logout: { target: login }` bez routy pro `/logout`; Symfony dokumentace pro logout path routu vyžaduje. Může jít o chybějící routu v ukázce.
- authorization_in_ddd.md:edge – šablona posílá `_csrf_token`, ale `form_login` nemá `enable_csrf: true`; token se tak neověřuje. Neškodné, ale nekonzistentní.
- authorization_in_ddd.md:multi-tenancy – „Schema-based … lepší performance než row-based“ – sporné paušální tvrzení (záleží na počtu tenantů/schémat).
- cqrs.md:symfony-messenger – „`broadway/broadway` je archivovaný“ – neověřeno.
- cqrs.md:failed-monitoring-heading – „volbu `--format` má z příkazů messengeru jen `messenger:stats`“ – neověřeno.

## Nekonzistence vůči jiným kapitolám (neopraveno)
- migration_from_crud.md (Recept 8) „`enum OrderStatus: string { case Placed = 'placed'; case Cancelled = 'cancelled'; }`“ × basic_concepts.md / implementation_in_symfony.md kanonický `OrderStatus` (Draft/Confirmed/Paid/Shipped/Delivered/Cancelled, bez Placed) – návrh: v migraci použít `Confirmed` nebo výslovně označit jako zjednodušený legacy enum.
- cqrs.md projektor zapisuje do `order_dashboard.status` řetězec `'placed'`, zatímco doménový stav je `confirmed`. Je to read model, takže vlastní slovník smí mít, ale čtenáři to nikde neřeknou; authorization_in_ddd.md (OrderDetailReadModel, OrderListReadModel) z téže projekce čte `status` a zobrazuje ho. Návrh: jednou větou v cqrs.md vysvětlit, že dashboard používá vlastní label, nebo projektor sjednotit na `confirmed`.

## Shrnutí jazykových úprav
- Kalky a anglicismy: „dává smysl“ → „hodí se / vyplatí se“, „permissions / subset / hyper-specific / framework-agnostic“ → „oprávnění / závislosti / úzce specifické / nezávislá na frameworku“, „filter neaplikuje na native SQL“ → „filtr se neuplatní na nativní SQL“ (i v nadpisu, kotva beze změny), „data leakují“ → „data unikají“, „order/customer/cancellation window“ v próze → „objednávka/zákazník/storno lhůta“.
- Kýč a patos: „kód se rozpadne“, „se rozpadá v doménové logice“, „V praxi je to den, kdy tým narazí na strop“, „Nejde o bug, ale o…“, „V další sekci ten rámec dáme dohromady“, „V této kapitole zavedeme“ – nahrazeno věcnými větami nebo škrtnuto.
- Vatové přechody a opakování: škrtnuty úvody typu „Existuje několik osvědčených vzorů…“, „Pro úplnost si projděme…“, „Tím končí popis základní infrastruktury…“, „Kompletnější přehled najdete…“, věty parafrázující předchozí (např. „Query handler neprochází přes doménový model“ po tomtéž tvrzení).
- Nominalizace a „tento/tato“: „rozhodovací proces přes všechny Votery“ → „projde rozhodováním“, „Command sám o sobě neobsahuje… je to pouhý přepravní kontejner dat“ → „Doménovou logiku neobsahuje, jen data přepravuje“; seznam vlastností commandu přepsán do jedné souvětné řady bez tučných leadů (rozbití uniformního rytmu).
- Imperativ v soudech převeden na oznamovací tvar („Pro tyto scénáře zajistěte…“ → „V těchto scénářích konzistenci zajišťuje write strana“, „V produkčním systému musíte monitorovat“ → „V produkci se … monitoruje“); „tady“ → „zde“ v komentářích kódu (kromě `RegisterUser`, který je shodný s kap. 10); anglické uvozovky "…" v YAML/PHP komentářích → „…“.
- Zkrácení: próza zhruba o 8–12 % (celé soubory včetně kódu: kap. 11 13 465 → 13 193 slov, kap. 12 10 103 → 9 884 slov); věcný obsah, čísla, citace a odkazy zachovány.

---
<!-- reports/g6.md -->
# g6 – event_sourcing.md (kap. 13), sagas.md (kap. 14)

## Opravené nepravdy / věcné chyby
- event_sourcing.md:#outbox-zaruky-heading – „Doctrine transport … používá `LISTEN/NOTIFY` sám od verze 7.1“ → od Symfony 5.1 (tehdy vznikl `PostgreSqlConnection` s volbou `use_notify`, výchozí true). Opraveno i tvrzení „Relay pak nečeká na tik pollingu“: notifikace budí worker konzumující z transportu, ne relay čtoucí `event_store`. Věta o vlastní implementaci pro relay nad `event_store` zůstala.
- event_sourcing.md:#rebuild-command-heading (odstavec pod příkazy messenger:failed) – `--class-filter` „omezí výpis i retry“ → omezí výpis a (od 7.3) mazání. Ověřeno ve vendor/symfony/messenger 8.0: `FailedMessagesRetryCommand` volbu `--class-filter` nemá, `FailedMessagesRemoveCommand` ano (CHANGELOG 7.3).
- event_sourcing.md:tamtéž – `messenger:consume --keepalive` „od Symfony 7.3“ → od 7.2 (CHANGELOG Messenger 7.2: asynchronní notifikace transportů přes `pcntl_alarm()`).
- event_sourcing.md:#aggregate-s-es – odrážka tvrdila, že `apply*()` jsou „private/protected“. Komentář v `applyEvent()` i v `Order` správně říká, že musí být protected (private metoda potomka nejde volat ze scope rodiče). Sjednoceno na protected.
- event_sourcing.md:#domenove-udalosti – formát `<bounded_context>.<past_tense_verb_noun>` neodpovídal příkladům (`order_placed` = podstatné jméno + sloveso) → `<bounded_context>.<podstatné_jméno>_<sloveso_v_minulém_čase>`.
- event_sourcing.md:FAQ „Co je Event Store“ – `readStream(streamId)` → `loadStream()`, jak se metoda jmenuje v kapitole.
- event_sourcing.md:#cas-udalosti-heading – „Projekce v následující sekci“ → odkaz na [Projekce](#projekce). Callout je v 13.04, projekce v 13.07.
- sagas.md:#configurable-timeouts-heading – vnitřní rozpor „Změna hodnoty se přitom dotkne i ság, které už běží: … nová konfigurace je zpětně nepřepíše“ → „nedotkne“.
- sagas.md:#saga-unit-test-heading (komentář v testu) – „promoted property nelze deklarovat by-reference“ je nepravda. Ověřeno na PHP 8.4.16: `__construct(public array &$c)` referenci naváže. Komentář přepsán na popis toho, co kód dělá. Kód se nezměnil.
- sagas.md:#worker-command-heading – „`--time-limit` zajistí, že se worker po hodině automaticky restartuje“ → worker se ukončí a znovu ho spustí správce procesů (supervisor, systemd). Opraven i rozpor „pro každý transport oddělené workery“ × příkaz, který konzumuje oba transporty najednou.
- sagas.md:#multi-worker-heading – „(Messenger `numprocs > 1`)“ → `numprocs` je volba supervisoru, ne Messengeru.
- sagas.md:#idempotent-saga-transitions-heading, odrážka UNIQUE constraint – „handler ji zachytí a načte existující ságu“ neodpovídalo kódu `onOrderPlaced()` (resetuje EntityManager a skončí) → sjednoceno s kódem.
- sagas.md:#perzistence-stavu – „`onPaymentSucceeded()` žádný guard nemá“ – guard na terminální stav metoda má → „nemá guard proti opakovanému doručení“.
- sagas.md:#messenger-implementace – popis toku „agregát `Order` publikuje `OrderPlaced` na event bus“ byl v rozporu s calloutem „Kterou událost sága konzumuje“ → tok začíná integrační událostí `OrderPlacedIntegrationEvent` z outboxu.
- sagas.md:#step-handlers-heading – `ShipmentCreated` nenese „`ShipmentId` z brány“, ale řetězec (VO skládá až `ShipOrderHandler`) → „s identifikátorem zásilky ze služby“.
- sagas.md: interní čísla sekcí – „sekce 14.03“ u kompenzací → 14.02. Sjednocen i formát odkazů („sekci 9/7/11/6“ → 14.09/14.07/14.11/14.06), a to v próze i v komentářích kódu.
- sagas.md:#distributed-deadlock-heading – `PaymentSettled` → `PaymentSucceeded`, jak se událost jmenuje ve zbytku kapitoly.
- sagas.md: FAQ o kompenzacích – příklad `CancelPayment`/`AuthorizePayment` neodpovídal kapitole → `RefundCustomer`/`ChargeCustomer`. FAQ o idempotenci doporučovalo „identifikátor zprávy“, což kapitola výslovně odmítá (transportní ID) → „identifikátor události (ne transportní ID zprávy)“. Odkaz vede na #idempotent-saga-transitions-heading místo #messenger-implementace.
- sagas.md:#step-handlers-heading – odkaz `(/autorizace-v-ddd#async-\nauthorization)` byl rozlomený přes řádek a hrozilo, že se nevyrenderuje. Spojen do jednoho řádku, cíl zůstal stejný.

## NEJISTÉ (neopraveno)
- event_sourcing.md:#rebuild-command-heading – „`--fetch-size` (Symfony 8.1)“. Ve vendoru je Messenger 8.0, ověřit to nejde.
- event_sourcing.md:#hotove-knihovny-heading – Broadway 3.0.1 „abandoned“ v srpnu 2026, Ecotone 2.0 v betě, prooph bundle „končí u Symfony 7“. Jsou to časově citlivá tvrzení, ověřit je nejde. Text sám vyzývá ke kontrole na Packagistu.
- event_sourcing.md:#gdpr-event-store-heading – námitka „právníka Harrisona J. Browna“ u Verraese. Zdroj jsem neověřil.
- sagas.md:#terminologicka-konvence – CQRS Journey prý „navrhuje dělicí čáru: process manager uvnitř jednoho BC, sága přes hranice“. Nemám jistotu, že Reference 6 takové dělení formuluje.
- sagas.md:#kdy-saga-nestaci – Garcia-Molina a Salem prý uvádějí převod peněz jako protipříklad. sagas.md:#parallel-compensation-heading – „původní článek tomu říká cascading rollback“. Obojí jsem v textu článku neověřil.
- sagas.md:#selhani-kompenzace × tabulka v 14.02 – rezervace skladu je „pivot“ a `CreateShipment` je retriable krok bez kompenzace. Kapitola přitom `CancelShipment` jako kompenzaci zavádí a používá (storno, opožděné `ShipmentCreated`). Není to chyba, protože storno zvenčí je jiný scénář než selhání kroku. Čtenáře to ale může mást. Zvážit jednu vysvětlující větu.

## Nekonzistence vůči jiným kapitolám (neopraveno)
- Žádnou jsem nenašel. Tvrzení „`placeWithItems()` volá `lockForSaga()`“ (sagas.md) sedí s outbox_pattern.md:511. Kotvy do jiných kapitol prošly `check_anchors.php`.

## Shrnutí jazykových úprav
- Vata a úvody sekcí: škrtnuto „V následujících sekcích si projdeme…“, „Předchozí sekce ukázaly… Nyní propojíme…“, „Tato sekce dává rozhodovací rámec…“, „Představme si reálnou situaci:“ a wikipedijní úvod 13.05 („Event Store je append-only úložiště…“, podruhé po pojmech).
- Řečnické otázky a patos přepsány na oznamovací věty. Například „Proč nelze zabalit… do jediné transakce? … Koncept atomické transakce se zde rozpadá.“ → „Atomická transakce přes ně neexistuje.“ Dále „Kdo detekuje, že proces visí? … kdo zjistí…?“, „Co se stane, když PaymentSucceeded nikdy nedorazí?“ a „Otázka tedy zní: jak přečíst…?“. Škrtnuto „jež je základním pilířem DDD“, „V produkčním systému je to nepřijatelné.“ a pointa „Nejde o bug, nýbrž o vlastnost architektury“.
- Duplicity v kap. 14: odstavec „Právě tady se pozná, jestli je proces domyšlený“ byl v textu dvakrát, odstavec o adaptérech („jediné místo, kde na knize (ne)záleží“) také dvakrát. K tomu výčet portů třikrát a „liší se jen volanou službou“ dvakrát. Zůstal vždy jeden výskyt.
- Kalky a gramatika: „Vzniká je konstruktor“ → „Vytváří je“, „Restart projektoru není událost“ → „nic nerozbije“, „brání storna“ → „brání stornu“, „celé event history“ → „celá historie událostí“, „Upcasters“ → „Upcastery“, „Stream archivation“ (nadpis) → „Archivace streamů“, dvojí „přitom“ v jedné větě. Imperativ v doporučeních („sáhněte nejdřív“, „vyprázdněte“) je přepsaný na oznamovací soud.
- Osobní hlas autora „Uvádím je“ → „Výpis je zde proto“. „Tady“ v komentářích → „Zde“. Anglické zavírací uvozovky (2×) → „“.
- Zkrácení: prózy v kap. 14 ubylo zhruba o 10 % (−28 řádků netto), hlavně v 14.01, 14.04, 14.05 a u handlerů kroků. Kap. 13 je o 8 řádků kratší, zásahy v ní jsou spíš věcné a lokální. Kód se měnil jen v komentářích.

Kontroly: lint-php-snippets (oba soubory) 56 bloků / 0 chyb; check_anchors OK; check_faq_yaml OK; check_tonality 0 nálezů v obou souborech. Dva nálezy „hype“ ve výpisu patří cizím souborům (architectural_styles, outbox_pattern).

---
<!-- reports/g7.md -->
# g7 – kap. 15 (outbox_pattern), 16 (performance_aspects), 17 (testing_ddd)

## Opravené nepravdy / věcné chyby

### outbox_pattern.md
- #schema – „Outbox tabulka má deset sloupců“ → „jedenáct“ (entita, migrace i tabulka významů mají 11 sloupců).
- #schema (migrace) – SQL komentář uvnitř `CREATE TABLE` přesunut do PHP komentáře nad `up()`; text pod ukázkou sám před SQL komentáři v DDL varuje. Highlights opraveny na řádky `CREATE INDEX` (dřív ukazovaly na náhodné sloupce).
- #aggregate-publishes (Order) – duplicitní řádkový komentář stál mezi docblockem a metodou (docblock se tím odtrhl od `placeWithItems()`); sloučeno do docblocku.
- #dispatch-command-heading – komentář odkazoval na `OrderPlaced::$eventId` → `OrderPlacedIntegrationEvent::$eventId` (doménová `OrderPlaced` `eventId` nemá); zdvojený komentář o jménu transportu sloučen.
- #inbox (DbalInboxRepository) – `markProcessed()` polykal `UniqueConstraintViolationException`. Odporovalo to komentáři v `OrderPlacedReadModelUpdater` („druhý dostane výjimku a Messenger retry-uje“) i anti-vzoru „UNIQUE jako pojistka“: na MySQL by se spolknutou výjimkou commitnul i duplicitní vedlejší efekt. Try/catch odstraněn, výjimka shodí transakci a zprávu zastaví `isProcessed()` při retry. **Pozor: `ddd-symfony-examples/Chapter11_OutboxPattern` může mít původní variantu – sjednotit.**
- #inbox – „Inbox není doménová entita, implementace je na DBAL“ vedle entity `App\Inbox\Domain\InboxMessage` → přeformulováno (entita mapuje schéma, zápis jde přes DBAL).
- #outbox-lag-heading – lag definován jako „čas průměrného eventu“, ale SQL i metrika měří nejstarší pending řádek → definice sjednocena s metrikou.
- #cleanup-command-heading – nadpis říká „MySQL“, ale kód MySQL výslovně odmítá (LIMIT v `IN (SELECT …)`). Kód přepsán na odvozenou tabulku, která projde na MySQL, PostgreSQL i SQLite. Opraveno i tvrzení „příkaz běží jednou za měsíc / chyba se ukáže po měsíci“ – cron běží každých 5 minut a syntaktická chyba padá hned, jen potichu.
- #cleanup-command-heading – „Velký delete drží zámky na celé tabulce“ → dlouhá transakce se zámky na velkém počtu řádků, může blokovat INSERTy (zámek celé tabulky to není).
- #dlq-heading – příčiny trvalého selhání publishe uváděly „schema změnu v subscriberu“ a „poison message, který shodí consumera“ – ty relay nijak neovlivní (publikuje do async transportu). Nahrazeno příčinami na straně relaye: nedenormalizovatelný payload, `message_type` mimo whitelist, poškozený payload.
- #vacuum-heading – „INSERT vytváří mrtvé řádky (kvůli MVCC)“ je chyba; mrtvé řádky vytváří UPDATE a DELETE. Doplněno, že změna indexovaného `status` vylučuje HOT update. „Index nabobtná na 10×“ → „na násobky“ (neověřitelné číslo).
- #vacuum-tuning-heading – dotaz na `pg_stat_user_indexes` používal sloupce `tablename`/`indexname`, které v tom pohledu neexistují (patří `pg_indexes`) → `relname`/`indexrelname`, zbytečný JOIN na `pg_class` odstraněn.
- #partitioning-heading – PostgreSQL nemá `DROP PARTITION` → `DETACH PARTITION` + `DROP TABLE`; „nemá zámky na celé tabulce“ → běžný DETACH bere exkluzivní zámek rodiče, `CONCURRENTLY` (PG 14+) slabší. „Staré partice se nevakuují vůbec“ zmírněno.
- #distributed-relay-heading – „leadera do 5 s nahradí jiný“ při TTL lease 10 s → „nejpozději po vypršení lease (10 s)“.
- #backpressure-heading + FAQ „dlouhodobý výpadek brokera“ – FAQ tvrdila, že při výpadku řádky zůstávají `pending` (self-healing), ale `markFailed()` z 15.03 by za ~1 minutu přesunul všechny zdravé řádky do `failed`. Doplněn odstavec: chyba spojení s brokerem se do `attempts` nezapočítává, relay čeká s backoffem na úrovni workeru; FAQ doplněna o bod (d). Nesmyslná věta „Pokud broker chybí déle než N dní…“ (pending se nekompaktuje) odstraněna.
- #anti-no-unique-heading – text tvrdil, že UNIQUE na `outbox.id` zachytí retry „s týmž UUID“; `id` je PK a retry handleru generuje nové UUID. Přepsáno: dedup dělá `eventId` v inboxu, UNIQUE `(event_id, consumer)` je jediná obrana proti souběhu.
- #migrace-krok-2-heading – `OutboxMessage::fromIntegrationEvent($integrationEvent)` s jedním argumentem neodpovídá signatuře (4 parametry) → `fromIntegrationEvent(...)` s odkazem na 15.04.
- #summary – „`wrapInTransaction` v handleru garantuje atomicitu“ odporovalo doporučení z 15.04 (vybrat jedno místo, kanonicky middleware) → „jedna transakce: middleware, nebo wrapInTransaction“.
- FAQ „batch v relayi“ – „kadence dovolí 1 000 zpráv/s“ neplatí: smyčka spí jen při prázdném outboxu; opraveno, rada „zkraťte interval na 50 ms“ (při rostoucím lagu bez účinku) vypuštěna.
- FAQ NoSQL – „Cassandra nemá multi-row atomicitu“ → nemá ACID transakce; logged batch zaručí jen konečné provedení všech zápisů bez izolace.
- 15.04 úvod – „Agregát neví nic o Doctrine“ odporuje kanonickému mapování atributy na doménových třídách (/implementace-v-symfony#mapping-volba-heading) → Doctrine z výčtu vypuštěn.

### performance_aspects.md
- 16.04 (SalesReportQueryService) – komentář „'999' by se seřadilo za '10000'“ je obráceně: při textovém řazení DESC by '999' šlo *před* '10000' → opraveno.
- 16.05 – ULID/UUID v7 „vždy větší než předchozí, monotónně rostoucí“ platí jen v rámci jednoho generátoru → „časově řazené, až na drobné odchylky mezi generátory“.
- 16.06 read-only – „dvě páky“ + „Druhou pákou je…“ = tři; navíc „alternativní strategie“ v množném čísle (NOTIFY v ORM 3 už není). Přeformulováno: read-only entity jako první páka, `DEFERRED_EXPLICIT` jako konkrétní alternativa s jejím rizikem.
- 16.07 – „dirty reads“ je jiný pojem → „četl by se zastaralý stav“; zámek Cache Contracts koordinuje jen procesy na témže serveru (LockRegistry přes flock) – doplněno.
- 16.08 – „Tisíce záznamů se v jednom PHP procesu synchronně nezpracovávají“ odporovalo `ImportProductsHandler` o dvě sekce výš → zmírněno.
- #partitioning-heading – `DROP PARTITION` (PG ho nemá) → `DETACH PARTITION` + smazání; „reference přes ID musí být kompozitní“ neplatí pro UUID v7 – doplněna druhá cesta (range partitioning přímo podle UUID v7).
- #replicy-pooling-heading – lag: `pg_last_xact_replay_timestamp()` vrací čas, ne lag → `now() - …`. PgBouncer: věta o „statementech emulovaných na straně klienta, které se nedostanou do LRU cache“ byla zavádějící (emulované prepares žádnou cache nepotřebují); nahrazeno přesným omezením – SQL `PREPARE`/`EXECUTE` PgBouncer nesleduje. Opraveno i dvojznačné „Ten“ (odkazovalo na „starší PgBouncer“).
- 16.10 – panel Doctrine v Profileru nezapíná `doctrine.dbal.logging`, ale `doctrine.dbal.profiling` (výchozí `%kernel.debug%`); stack trace dotazů jen s `profiling_collect_backtrace`. Blackfire neprofiluje „každý request“, jen profilovaný. MySQL `EXPLAIN` neukazuje „Full Table Scan“ → `Table scan` / `type: ALL`.
- Závěr kapitoly – pořadí „měřit → CQRS → hranice → N+1“ odporovalo žebříčku eskalace v 16.04 (projekce až poslední) → přepsáno v souladu s žebříčkem.
- FAQ – fetch join `JOIN` → `LEFT JOIN` (kapitola sama vysvětluje, proč ne INNER); „velký agregát načítá při každé operaci desítky entit“ odporovalo výkladu o lazy kolekcích; rebuild read modelu z událostí jen tam, kde jsou události uložené.

### testing_ddd.md
- 17.02 – „Test přenesený z PHPUnit 9 skončí chybou, ne tichou změnou chování“ platí jen pro `@dataProvider`; `@test` bez prefixu se tiše přestane spouštět → doplněno.
- 17.03 (DomainEventAssertions) – komentář „`DomainEvent` dědí jediná událost v celé knize“ je nepravda: v kap. 13 ho dědí `OrderPlaced`, `OrderItemAdded`, `OrderConfirmed`, `OrderShipped` (a tentýž soubor je o sekci níž používá) → opraveno.
- #integracni-testy – „Bundle se nezapíná v konfiguraci Symfony, ale v PHPUnitu“ odporovalo 17.09 (bundle musí být v `config/bundles.php`) → dvě registrace. „Jediné vážné omezení: DDL implicitně commitne“ platí jen pro MySQL/MariaDB (PostgreSQL má transakční DDL) → upřesněno.
- 17.09 – „contrib balíček, takže ho Flex nezaregistruje“ → contrib recept Flex spustí po potvrzení / s `allow-contrib`.
- #testovani-asynchronnich-toku – `eventId` „(ULID)“ a ULID v ukázce → UUID v7 (konvence knihy i kap. 15: `Uuid::v7()`).
- Test outboxu – `PlaceOrderCommand` → `PlaceOrder` (název z kap. 15); `messageType()` jako metoda a hodnota `'order.placed'` → veřejná vlastnost s FQCN `OrderPlacedIntegrationEvent::class`; transport `async` → `async_events` (TransportNamesStamp v 15.05); `execute([])` by relay nechal běžet hodinu (výchozí `--time-limit=3600`) → `['--time-limit' => 1]`.
- 17.08 – úvodní příklad „někdo přidá `use Doctrine\ORM\Mapping` do entity“ a ukázkový výstup Deptracu „User must not depend on Doctrine\ORM\Mapping\Column“ odporovaly kanonickému mapování atributy na doménových třídách (kap. 10); navíc uvedená konfigurace s `paths('./src')` vendor třídy do vrstev nezařadí, takže by takové porušení vůbec nehlásila. Příklad i výstup změněny na závislost Domain → Infrastructure; FAQ „doména nezávisí na Doctrine“ → „na infrastruktuře“.
- 17.09 – „Tři testovací sady“ a nadpis „se třemi testsuitami“ při čtyřech `<testsuite>` → „čtyři“ (**změněn text nadpisu callout bloku bez kotvy**).
- Seznam chyb – příklad `InvalidEmailException` odporoval testu `Email`, který čeká `\InvalidArgumentException` → `EmptyOrderException` (z `OrderTest`).

## NEJISTÉ (neopraveno)
- outbox_pattern.md:#single-worker-heading, #distributed-relay-heading, tabulka v #relay-cdc-heading – „jeden PHP proces zvládne řádově jednotky tisíc zpráv za sekundu“ (3×). Sériová smyčka s čekáním na ACK brokera a UPDATE+flush na každou zprávu dá spíš stovky/s; text sám dodává, že číslo je potřeba změřit. Doporučuji „stovky až nízké tisíce“.
- outbox_pattern.md:#migrace-krok-3-heading – postup nasadí relay (krok 3) dřív, než subscribeři dostanou Inbox (krok 4), a text sám přiznává, že duplicity mezitím nikdo neodchytí. Věcně je bezpečnější pořadí Inbox → relay; neměnil jsem strukturu postupu.
- outbox_pattern.md:#schema – komentář v migraci radí `options: ['precision' => 6]`, entita `OutboxMessage` ho ale u `datetime_immutable` sloupců nemá. Nejsem si jistý podporou volby `precision` pro datetime v DBAL 4, proto entitu neměním.
- outbox_pattern.md:#partitioning-sql-heading – partitioned tabulka nemá `aggregate_type`/`aggregate_id` a `status` má VARCHAR(20) místo 16. Nejspíš záměrná zkratka, ale s 15.03 se liší.
- performance_aspects.md:#lazy-objects-heading – „DoctrineBundle 3 má nativní lazy objekty zapnuté napevno“ neověřeno.
- testing_ddd.md:17.01 – „Ham Vocke … místo poměru se ptá, kolik integračních bodů jeden test ověřuje“ – parafráze, kterou jsem ve Vockeho článku neověřil.
- testing_ddd.md:#architektonicke-testy – „qossmic/deptrac je od listopadu 2024 abandoned“ a „řada 4.x generuje `deptrac.php` přes `init`“ neověřeno.

## Nekonzistence vůči jiným kapitolám (neopraveno)
- performance_aspects.md (16.07, cache invalidace) „`#[AsEventListener(event: UserEmailChanged::class)]`“ × implementation_in_symfony.md (kanonický `User::changeEmail()` nenahrává žádnou událost; doménové události jdou přes Messenger `event.bus` s `#[Target('event.bus')]`). Listener na EventDispatcheru událost z Messengeru nedostane a `UserEmailChanged` v kanonickém modelu neexistuje. Návrh: přepsat listener na `#[AsMessageHandler(bus: 'event.bus')]` a v textu říct, že `UserEmailChanged` je rozšíření modelu, nebo příklad přesunout na existující událost.
- performance_aspects.md „### ULID jako kompromis“ (nadpis) × CLAUDE.md konvence (UUID v7 výchozí, ULID jen alternativa). Text pod nadpisem už UUID v7 doporučuje; nadpis jsem neměnil. Návrh: „Časově řazené identifikátory: UUID v7 a ULID“.
- testing_ddd.md (17.08, původní příklad s `Doctrine\ORM\Mapping`) × implementation_in_symfony.md#mapping-volba-heading – v kap. 17 opraveno; stojí za kontrolu, jestli phparkitect/Deptrac pravidla v lesser_known_patterns.md (#mod-phparkitect) a microservices_and_ddd.md (#phparkitect-rules-heading) nezakazují doméně Doctrine, což by kanonický kód shodilo.
- outbox_pattern.md DbalInboxRepository (výjimka se už nepolyká) × repo ddd-symfony-examples/Chapter11_OutboxPattern – ověřit a sjednotit.

## Shrnutí jazykových úprav
- Kalky a anglicismy: „Problém je v tom, že…“, „Toto je záměrná volba“, „DB write succeeded, broker dispatch failed“ → české popisky, „hrát roli“ → „vystupovat dvojím způsobem“, „Throughput“ → „propustnost“, „assertions“ → „aserce“, „fieldy“ → „vlastnosti“, „plain PHP array“ → „prosté PHP pole“, „Tradeoff“ → „Cena“, „do Prometheu“ → „do Promethea“, „ne-idempotentní“ → „neidempotentní“, „čistý PHP“ → „čisté PHP“, „na konci dne“ → „až v CI“.
- Kýč a patos: vypuštěno „ve tři hodiny ráno“ (2×), „atomicita je iluze“, „jádrem DDD“, „DB CPU vystřelí … lag exploduje“, „jen o něm nikdo nemluví“, „pravidlem, ne výjimkou“, „XA je definitivně mimo hru“; řečnická otázka na začátku 15.09 a „Caching v DDD má jednu vstupní otázku: co cachovat?“ nahrazeny oznamovací větou.
- Imperativ v soudech převeden na oznamovací způsob („Napříč agregáty žádné globální pořadí nečekejte“ → „Globální pořadí … neexistuje“; „Nezasahujte do produkčních INSERTů“, „Doporučení: pokud možno preferovat…“, „V kontextu výkonu si pamatujte“); imperativ ponechán v postupech a v pattern/warn calloutech.
- Vatové úvody a přechody: „Pozornost si zaslouží…“, „Stojí za povšimnutí, že…“, „Níže jsou ty nejčastější“, „Tok je následující:“, „Dál projdeme…“, zdvojené věty ve 17.02 a 16.10 („Optimalizujte pouze podle naměřených dat“ po „Zlatém pravidle“); sloučení krátkých vět s opakovaným podmětem; „Tady“ → „Zde“ v kódu (2×), anglické uvozovky v komentáři supervisord → české.
- Gramatika: „Tabulka zatím nikdo nepoužívá“ → „Tabulku…“, „Monitoring … je nezbytné“ → „nezbytný“, „vede k zbytečně“ → „vede ke“, „vlézá do RAM“ → „vejde se“, „Příznak“ (nadpis se 4 položkami) → „Příznaky“.
- Délka: próza zhruba beze změny (slova +1 % v každé kapitole). Škrty vaty (řádově −5 %) vyrovnaly věcné doplňky – odstavec o výpadku brokera vs. `markFailed()`, MVCC/HOT ve vacuum sekci, partitioning podle UUID v7, přesnější PgBouncer a DAMA/Flex. Kapitoly byly spíš hutné, větší škrty by šly na úkor obsahu.

Kontroly: lint-php-snippets (3 soubory) 51 bloků / 0 chyb; check_anchors OK; check_faq_yaml OK; check_tonality 0 nálezů ve všech třech souborech. `modified: 2026-09-23` ve všech třech.

---
<!-- reports/g8.md -->
# g8 – kap. 18 (migration_from_crud), 19 (microservices_and_ddd), 20 (ddd_pain_points)

## Opravené nepravdy / věcné chyby
- microservices_and_ddd.md:#kolik-dat-heading – „Fowler (2017) rozlišuje dva režimy“ → rozlišuje čtyři vzory (notification, event-carried state transfer, event sourcing, CQRS), pro integraci jsou podstatné dva. Vlastní „Další četba“ té kapitoly už uváděla čtyři.
- microservices_and_ddd.md:#mytus + #further-reading – Richardson kap. 2 zúžen na „decomposition by business capability“ → dekompozice podle business capabilities **a subdomén** (Microservices Patterns má oba vzory a DDD odpovídá ten druhý).
- microservices_and_ddd.md:#de-microservicing-heading – Prime Video „(květen 2023)“ → článek z března 2023 (22. 3.), pozornost vzbudil v květnu.
- microservices_and_ddd.md:#service-mesh-heading – ze seznamu mesh odstraněn AWS App Mesh (AWS oznámilo ukončení podpory k 30. 9. 2026).
- microservices_and_ddd.md:#symfony-microservice-heading – „integrace probíhá **výhradně** asynchronně“ byl rozpor se sekcí 19.05 (sync dotazy a validace) → asynchronní je integrace reagující na změny stavu, sync dotazy stranou.
- microservices_and_ddd.md:#summary – „sdílená knihovna je první ze tří příznaků distributed monolithu“ → 19.04 jich uvádí pět (a knihovnu jako pátý); nyní „patří k příznakům ze sekce 19.04“. Kritérium pro samostatnou service ve shrnutí bylo „když má tým NEBO data NEBO…“ → sjednoceno s heuristikou 19.02 (aspoň 4 podmínky).
- microservices_and_ddd.md:#phparkitect-heading + #summary – „hranice vynucené stejně tvrdě jako za HTTP/AMQP“ → phparkitect hlídá závislosti v kódu, ne sdílené tabulky; doplněna tato výhrada.
- microservices_and_ddd.md:#contract-testing-heading – „příznak č. 4 z předchozí sekce“ → ze sekce 19.04 (předchozí je 19.06).
- microservices_and_ddd.md:#async-first-pravidlo-heading – „při sync volání je publisher závislý“ → volající.
- migration_from_crud.md:#command-extraction-heading + #recept-event-publish-uvnitr-heading – rozpor: komentář v `RegisterUserHandler` tvrdil, že události sbírá „outbox listener po flushi“ (a odkazoval na Recept 7), Recept 7 i kapitola Outbox říkají, že je handler vyzvedne přes `releaseEvents()` a zapíše do outboxu. Komentář sjednocen s Receptem 7 a kap. Outbox (zápis do outboxu před flushem, v téže transakci); Recept 7 krok 2 upřesněn stejně.
- migration_from_crud.md:#command-extraction-heading – filename bloku „PŘED“ zněl „UserController.php (po migraci)“ → „(před zavedením CQRS)“ (kód je stav před).
- migration_from_crud.md:FAQ „hlavní rizika“ – jako riziko uvedeno „přímé ukládání doménové logiky do Doctrine entit“, což je v rozporu s tělem (logika do entity patří, anti-vzor je „ORM diktující tvar modelu“) → opraveno.
- migration_from_crud.md:#cqrs-postupne – rozpor „CQRS se zavádí od write side“ × „read model nad legacy schématem jako první krok je bezpečnější“ → první odstavec přeformulován (write side = kde model už existuje), druhý výslovně navazuje („z toho neplyne, že se čtení odkládá na konec“). Nadpis „Začít s Command stranou“ ponechán.
- ddd_pain_points.md:#a1-transakce – ukázka transakce přes dva agregáty používala `App\Ordering\…\OrderRepository` ve službě `App\Warehouse\…`, tedy cross-BC transakci, kterou text o dva odstavce níž označuje za zápach → agregát přejmenován na `TransferOrder` v kontextu Warehouse (skladový převod), próza upravena. Lint OK.
- ddd_pain_points.md:#a1-transakce – „Unit of Work je session-scoped“ (hibernatovský pojem) → žije po celý request / zpracování zprávy; „neúmyslně **načtená** entita se commitne“ → **změněná** (nezměněnou flush nezapíše).
- ddd_pain_points.md:#a2-spinavy-em – „lazy-init kolekce“ jako příklad getteru, který Doctrine vyhodnotí jako změnu → inicializace PersistentCollection změnu nevyvolá; nahrazeno getterem, který ukládá dopočítanou hodnotu do mapované vlastnosti.
- ddd_pain_points.md:#doctrine – „interní model Doctrine stavěný pro jednoduchý CRUD“ → vypuštěno (Doctrine je Data Mapper určený právě pro oddělení modelu od persistence).
- ddd_pain_points.md:#a5-identity – „DB generování ID šetří jeden dotaz“ (u sekvencí je tomu naopak) → „je pohodlné“; výčet továren doplněn o `placeDigital()` (kap. /mene-zname-vzory má placePhysical i placeDigital – kanonické rozhodnutí).
- ddd_pain_points.md:#b3-idempotence – „nebo `TTL` index“ u DBAL tabulky: relační DB (MySQL/PostgreSQL) TTL index nemají → úklid cronem nebo Symfony Schedulerem. Odstraněno i neověřitelné „Dokumentace Symfony na to upozorňuje přímo“.
- ddd_pain_points.md:#b1-outbox (note) – Doctrine Transport „garantuje at-least-once bez vlastního kódu“ doplněno o podmínku: atomicky s agregátem jen při dispatchi uvnitř téže transakce a přes totéž spojení.
- ddd_pain_points.md:#b2-debugging – YAML s registrací CorrelationIdMiddleware měl filename „(výřez: idempotence)“ → „(výřez: correlation ID)“.
- ddd_pain_points.md:#c3-acl – „Ares vrací XML nebo pole“ → ARES vrací JSON (staré XML rozhraní bylo v roce 2023 nahrazeno REST API).
- ddd_pain_points.md:#c1-validace – „Symfony Validator (anotace na DTO)“ → atributy.
- ddd_pain_points.md:#d3-voter – „logika ve Voteru se stane netestovatelnou bez Symfony kontejneru“ (Voter jde otestovat bez kontejneru) → test potřebuje bezpečnostní token a objekty frameworku; „doménová metoda je čistá funkce“ (kontrola 24 h závisí na čase) → „na frameworku nezávisí“.
- ddd_pain_points.md:#e1-management – „Tři z těch metrik pocházejí ze sady DORA“ → dvě (change lead time, change fail rate); regression rate a onboarding time v DORA nejsou.
- ddd_pain_points.md:FAQ Doctrine – „DDD agregát vyžaduje neměnnost“ (agregát je měnný, neměnné jsou VO) a „Doctrine očekává veřejné nebo reflektované atributy“ → přeformulováno (reflexe + Unit of Work × invarianty v agregátu, neměnné VO).
- ddd_pain_points.md:FAQ – rozpor „migrovat podle Bounded Contextu, ne podle modulu“ × E2 „vyberte jeden modul“ → „po jednotlivých Bounded Contextech“; názvy odkazovaných sekcí v FAQ sjednoceny s nadpisy („Business case…“, „Postupné zavedení“).

## NEJISTÉ (neopraveno)
- microservices_and_ddd.md:IntegrationEventSerializer (docblock) – „zpráva spadne do dead-letter exchange“ × o dva řádky níž „receiver ji zachytí a pošle do failure pipeline“. Při `MessageDecodingFailedException` AmqpReceiver zprávu rejectne (do DLX jen pokud je na frontě nakonfigurovaná); zda ji Symfony 8 pošle do failure transportu, jsem neověřil. Doporučuji sjednotit na jedno tvrzení po ověření v kódu `AmqpReceiver`/`Worker`.
- microservices_and_ddd.md:FAQ BFF – „BFF je v DDD terminologii typicky Open Host Service s Published Language“. OHS je protokol otevřený všem konzumentům, BFF je naopak backend na míru jednomu klientovi; přirovnání je sporné. Návrh: „BFF je spíš konzument s vlastním ACL vůči volaným službám; OHS by byl až veřejný protokol, který nabízí víc klientům.“
- microservices_and_ddd.md:#symfony (provoz) – `messenger:consume --fetch-size` – existenci přepínače v Symfony 8 jsem neověřil (`--keepalive` existuje od 7.2).
- ddd_pain_points.md:#a2-spinavy-em tabulka – „separátní EntityManager nakonfigurovaný jako read-only“: Doctrine žádný read-only EM nemá; read-only lze entitu (`#[ORM\Entity(readOnly: true)]`), dotaz (`Query::HINT_READ_ONLY`) nebo instanci (`UnitOfWork::markReadOnly()`). Návrh: nahradit těmito mechanismy.
- ddd_pain_points.md:#a4-lazy-loading – „nad odpojenou entitou inicializace selže s EntityNotFoundException“: u odpojené (detach) proxy si nejsem jistý, že inicializace selže (initializer zůstává navázaný na persister); jistá je jen varianta „záznam mezitím zmizel“.
- ddd_pain_points.md:#c3-acl – ukázka adaptéru používá Stripe Charges API se `source` tokenem; Stripe ho vede jako legacy (doporučuje PaymentIntents). Pro ilustraci ACL to nevadí, ale čtenář může kód převzít.
- ddd_pain_points.md:#c2-stavy note – odkaz na `symfony/symfony-docs#10819` jako „otevřenou otázku“ jsem neověřil.
- migration_from_crud.md:#strangler-fig – datum přejmenování 29. 4. 2019 ponecháno, neověřoval jsem ho proti revizní poznámce na Fowlerově webu.

## Nekonzistence vůči jiným kapitolám (neopraveno)
- Žádná nová tvrdá nekonzistence. Rozpor mezi kap. 18 (`RegisterUserHandler`: „outbox listener po flushi“) a outbox_pattern.md#aggregate-publishes („Application handler ten seznam vezme a zařadí do outbox tabulky v téže transakci“) jsem odstranil na straně kap. 18.
- microservices_and_ddd.md „Symfony Messenger ovšem žádný vestavěný relay mezi dvěma transporty nemá“ × ddd_pain_points.md#b1-outbox (note) „Doctrine Transport … garantuje at-least-once bez vlastního kódu“: v souladu jen pokud se Doctrine transport konzumuje přímo handlery (ne jako outbox pro AMQP). Nyní je note doplněna o podmínku; doporučuji zkontrolovat, že outbox_pattern.md formuluje totéž.

## Shrnutí jazykových úprav
- Kalky a vata: „Problém přijde, když aplikace přeroste do větší komplexity“ → „Problém nastane, když aplikace zesložití“; „Proč o něm mluvit v kapitole o microservices?“ (řečnická otázka) → oznamovací věta; „Konsensus to ale není, a je poctivé to říct.“ → „Konsensus to ale není.“; „Tímto vzorem dosáhneme čtyř důležitých vlastností“ → „Vzor zajistí čtyři vlastnosti“; „dělat rozhodnutí“ → „přijmout“; „v Context Mapu“ → „na Context Mapě“; „obojí svět“ → „oba světy“; „eufemizmy“ → „eufemismy“; „Limity narazíte“ → „Na limity narazí“; „Handler ale nikdy nezavolal“ → „se nezavolal“.
- Kýč a pointy: vyškrtnuty koncové pointy „Microservices nejsou cílem, ale nástrojem.“, „Microservice je optimalizace, kterou si zasloužíte…“, opakované „Microservices jsou především operační problém.“ ve shrnutí, „Stejné slovo, dvě různé situace.“, „noční můra“, „fatálními chybami“, „migrační utrpení“; „Nejen lepší architektura“ → věcné „obhajitelné, ne správné“.
- Anglicismy v próze: „change API smluv“, „failure jednoho znamená failure“, „log line“, „Aggregate metody dělají transitions“, „validate input → … → vrátit response“, „ordering problémy“ → české ekvivalenty; v kap. 19 sjednocen pravopis „monolit/monolitu“ tam, kde nejde o termín modular/distributed monolith.
- Soudy z imperativu do oznamovacího způsobu (C1 „Hlavní pravidlo“, poznámka k polymorfním VO, Pact, Doctrine Transport); odkazy s čísly kapitol („kapitole 21 – …“, „kapitola 14 – …“) převedeny na názvy.
- Zkrácení: odstraněny duplicity (dvojí vysvětlení IntegrationEventSerializeru v 19.08, dvojí „zaregistrujte typ“ v A3, rekapitulace předchozích kapitol v úvodu 19, parafráze v úvodech sekcí). Kapitoly jsou převážně hutné a plné kódu; úbytek slov celkem ~1,5 % (kap. 19 ~1,5 %, kap. 18 ~1,4 %, kap. 20 ~0,2 % – zde přibyla upřesnění), v upravených prózových pasážích 10–30 %.
- `modified:` nastaveno na 2026-09-23 ve všech třech kapitolách; jiná frontmatter pole neměněna.

Kontroly: lint-php-snippets (32 bloků, 0 chyb), check_anchors OK, check_faq_yaml OK, check_tonality – 0 nálezů v přidělených souborech.

---
<!-- reports/g9.md -->
# g9 – kap. 21 (anti_patterns), 22 (when_not_to_use_ddd), 23 (practical_examples), 24 (case_study), AI (ddd_ai)

## Opravené nepravdy / věcné chyby
- ddd_ai.md:nastroje – „Soubory s instrukcemi se ustálily do tří rozšířených formátů“ (Cursor, Copilot, CLAUDE.md) bylo k 9/2026 neúplné → „do několika formátů“ + věta o nástrojově neutrálním `AGENTS.md`, který čtou Cursor, Copilot i OpenAI Codex a který zastřešuje Linux Foundation (agents.md; Cursor Rules docs; GitHub Copilot coding agent podporuje AGENTS.md nativně).
- ddd_ai.md:otevrene-otazky – „Fowler … zdůrazňuje, že oblast … je v roce 2026 teprve na začátku“ → bez roku (citovaný zdroj je z prosince 2025). Neověřitelná přímá citace „stále se učíme“ převedena na parafrázi.
- ddd_ai.md FAQ 1 – odpověď tvrdila jako fakt, že AI generuje lepší kód s Ubiquitous Language, zatímco sekce ai.02 výslovně říká, že tvrdá data chybí → doplněno „Kontrolovaná měření k tomu zatím chybějí, mechanismus je ale zřejmý.“
- Ověřeno bez změny (Packagist 23. 9. 2026): `symfony/ai-platform`, `-agent`, `-bundle`, `-store` jsou stále na v0.13.0 (30. 8. 2026); `php-llm/llm-chain` abandoned → `symfony/ai-agent`; `symfony/ai` neexistuje (404).
- anti_patterns.md:prilis-velky-agregat – položka „Ztracené zápisy, ne konflikty“ popisovala scénář, kde se nic neztrácí (write skew), a počty nedávaly smysl („nejvýš tři“ → „zůstane šest“) → „Tiše porušený invariant místo konfliktu“: obě transakce vidí dvě objednávky, obě jednu přidají, zůstanou čtyři. Totéž srovnáno ve FAQ („vedou ke ztraceným zápisům“ → „tiše poruší invariant“).
- anti_patterns.md:prilis-velky-agregat (kód) – komentář „Stejná továrna jako u kanonického Order“ nepravdivý: kanonická `place()` nahrává `OrderPlaced`, výřez ne → komentář přiznává rozdíl („Kanonická verze navíc nahrává OrderPlaced…“). Událost jsem do kódu nepřidal, protože v téže kapitole (21.06) má `OrderPlaced` jinou signaturu (viz nekonzistence níže).
- anti_patterns.md:primitive-obsession – „ukázka výše je zkrácená na to, co odlišuje…“ o `Money`, který v ukázce vůbec není → „ukázka výše ji proto neopakuje“.
- anti_patterns.md FAQ – „místo Money dvojice float“ → „dvojice float a string“ (odpovídá ukázce: `float $amount` + `string $currency`).
- anti_patterns.md FAQ „Musí být doménová událost neměnná? Ano.“ odporovalo textu 21.06 („zpravidla“, metadata se doplňují) → „Ano, co do obsahu; doplnit technická metadata při publikování je běžné.“
- case_study.md:project-model-heading + Task – text radil dát identifikátoru „běžnou private vlastnost … jako to dělá Task níže“, ale `Task` měl `private readonly TaskId $id` (stejný problém). Opraveno v kódu (`private TaskId $id` s komentářem) i v textu: výjimku `Attempting to change readonly property` vyhazuje Doctrine (`ReflectionReadonlyProperty`) u `readonly` vlastnosti s objektovou hodnotou, protože nově sestavený VO není tatáž instance; `getReference()` narazí na identifikátor, `refresh()` na každou takovou vlastnost včetně `createdAt`. Původní formulace „PHP to u readonly odmítne“ nebyla přesná (hláška je Doctrine).
- case_study.md:trade-off-retro-heading – „Předchozích pět voleb vyšlo. Tři další … nevyšly“ odporovalo třetí položce (prázdná `TaskAssignmentService` = rozhodnutí 4 nevyšlo) → „Tři věci by tým dnes udělal jinak.“ a položka odkazuje na rozhodnutí 4 („platí anti-vzor a služba má zmizet“).
- case_study.md:domain-events-heading (callout) – „Tlustý payload … je anti-vzor“ odporovalo kap. 06 (Event-Carried State Transfer jako legitimní varianta) → anti-vzorem je reference na živý agregát; tlustá událost s kopií hodnot je výslovně legitimní volba.
- case_study.md:read-model-projection-heading – „TaskAssigned a TaskStatusChanged konzumenta nemají“ vs. kontextová mapa a ponaučení 4 (ActivityTracking reaguje na události) → upřesněno, že handler ActivityTrackingu kapitola nevypisuje, takže v ukázkách konzumenta nemají.
- practical_examples.md:e-commerce-structure – `CartAlreadyCheckedOutException` používaná v `Cart::checkout()` chyběla ve stromu projektu → doplněna.

## NEJISTÉ (neopraveno)
- case_study.md:read-model-reconciliation-heading – „deduplikaci převezme `DeduplicateMiddleware` se stampem `DeduplicateStamp`, které Messenger nabízí od verze 7.3“ – middleware (7.3) podle mého chápání brání duplicitnímu *odeslání* zprávy, dokud původní čeká ve frontě (zámek se po zpracování uvolní); opakované doručení už zpracované události tak nemusí zachytit. Ověřit v dokumentaci/kódu Messengeru, případně přeformulovat na „omezí duplicitní odeslání“.
- when_not_to_use_ddd.md:hybrid-subdomain – tabulka připisuje Khononovovi „Supporting → Lehké DDD (entity + repository, žádné agregáty) nebo Active Record“. Khononov v *Learning DDD* váže styl hlavně na složitost byznys logiky (Transaction Script / Active Record / Domain Model / Event-sourced DM); „lehké DDD bez agregátů“ je spíš interpretace knihy. Ověřit a případně označit jako autorské zjednodušení.
- ddd_ai.md:bounded-contexts – „V návazném článku ukazuje, jak lze pomocí knihovny ts-morph…“, ale seznam zdrojů připisuje ts-morph témuž článku z O'Reilly Radar (únor 2026). Buď jde o jeden článek, nebo chybí odkaz na návazný text.
- practical_examples.md:user-aggregate – „`final` u entit mapovaných Doctrine projde, protože nativní lazy objekty z entity nedědí“ platí jen se zapnutými nativními lazy objekty (ORM 3.4+/DoctrineBundle 3 výchozí). Pro Symfony 8 s DoctrineBundle 3 to sedí; neměnil jsem.
- practical_examples.md FAQ – CodelyTV/php-ddd-example jako příklad „stejného členění“ (vertical slice + CQRS): repo je členěné `src/<BC>/<Modul>/{Application,Domain,Infrastructure}` s use-case podsložkami v Application. Změkčil jsem na „podobné členění podle případů užití s CQRS sběrnicí“; úplnou přesnost neověřoval.

## Nekonzistence vůči jiným kapitolám (neopraveno)
- anti_patterns.md 21.06 `OrderPlaced(OrderId, CustomerId, Money $totalAmount, int $itemCount, occurredAt)` v `App\Ordering\Domain\Event` („immutable varianta“) × basic_concepts.md ř. ~848 kanonická `OrderPlaced(OrderId $orderId, CustomerId $customerId)` se stejným FQCN. Návrh: v 21.06 buď použít kanonickou signaturu s `occurredAt`/`recordedAt`, nebo třídu přejmenovat/umístit jinam a v názvu souboru říct, že jde o tlustou variantu (a proč).
- anti_patterns.md 21.04 `Order` nastavuje `placedAt` v konstruktoru jako `readonly` × aggregate_design.md kanonický `public private(set) ?\DateTimeImmutable $placedAt = null`, nastavovaný při potvrzení. Výřez; sjednotit nebo přiznat v komentáři.
- anti_patterns.md 21.09 `App\Insurance\Domain\ValueObject\Money` s `adjustFor()` × kanonický `App\SharedKernel\Domain\Money` (použitý ve 21.03 téže kapitoly). Legitimní jiný kontext, ale čtenář to může brát jako druhou definici; případně komentář.
- when_not_to_use_ddd.md 22.07 ✅ ukázka `Order::cancel()` – ověřeno proti aggregate_design.md ř. 597–630: signatura, výjimka i `OrderCancelled` sedí; výřez jen vynechává zámek ságy a idempotentní větev pro už zrušenou objednávku. Není třeba měnit, jen pro úplnost.
- practical_examples.md 23.01 próza/tabulka/FAQ mluví o kontextu „Order“, strom a namespace používají `Ordering` (a diagram „Cart a Order“). Sjednotit na `Ordering`, pokud se bude upravovat i SVG diagramu.

## Shrnutí jazykových úprav
- Cca 125 zásahů v próze napříč pěti soubory, žádné v nadpisech ani kotvách. Rozvláčné úvody kapitol zkráceny o 30–40 % (např. anti_patterns 21.01: vypuštěny věty „DDD nabízí strukturu … úskalí“ a „Anti-vzory je proto potřeba znát stejně dobře jako vzory“), jinde próza kratší zhruba o 5–15 %; věcný obsah, čísla a citace zachovány.
- Rétorické otázky a vata: ddd_ai úvod („Jsou některé architektonické přístupy…? Nabízí DDD…?“ → jedna oznamovací věta), závěr ai.08 (tři řečnické otázky → jedna věta), „Za pozornost stojí i to, kam se nástroje samy posunuly“ → „Nástroje se mezitím posunuly podobným směrem“, „Riziko má konkrétní mechanismus a stojí za to ho pojmenovat“ → „Riziko má konkrétní mechanismus“.
- Kalky a nominalizace: „Do téhož obrázku zapadá“ → „K tomu sedí“, „Sázka nejde na…“ → „DHH nesází na…“, „Špatně zvolená aplikace DDD“ → „Nasazené na nesprávném místě“, „Pro projekt s ETA 1–2 roky před koncem životnosti“ → „U systému, kterému zbývá rok či dva provozu“, „incrementu“ → „přírůstku“, „replay-em“ → „přehráním“, „optimistické locking konflikty“ → „konflikty optimistického zámku“, „Nuancovaně pro DDD“ → „Opatrně pro DDD“, „honest CRUD“ → „přiznaný CRUD“.
- Přebytečné tučné písmo a „viz“ navigace v úvodech kap. 21, 22, 23 nahrazeny větami se slovesem („rozebírá“, „katalogizuje“); imperativy v soudech („DDD pak zaveďte selektivně“, „Přiznejte si…“) převedeny na oznamovací nebo označenou nápravu.
- Opakování a paralelismy: „Pro malý dataset … Jakmile dataset naroste“, „Tato kapitola … Tato kapitola“, „Jde o autorský odhad“ zdvojené s „není absolutní … orientační bod“, sloučené věty v 21.02 (callout „Proč je anémický model problém“) a 21.07.
- Gramatika: chybějící čárky (DHH odstavec, callout event stormingu), „sdílí“ → „mají stejnou stavbu“, „Value Objektů“ → „hodnotových objektů“, „auditních logách“ → „lozích“, pleonasmus „opakovala znovu“, anglická uzavírací uvozovka v 21.04 → „“.

Kontroly: `lint-php-snippets.php` (5 souborů) 0 chyb; `check_anchors.php` OK; `check_faq_yaml.php` OK; `check_tonality.php` 0 nálezů ve všech pěti souborech (5 hlášených nálezů je v cizích souborech). `modified: 2026-09-23` nastaveno u všech pěti kapitol; frontmatter jinak beze změny.

---
<!-- reports/consistency-code.md -->
# Konzistence napříč knihou – kód, křížové odkazy, mechanismy Symfony/Doctrine

Stav k 23. 9. 2026, pracovní kopie (souběžně upravovaná jinými agenty – citace ověřené proti
aktuálnímu obsahu souborů na konci práce). Záměrné anti-vzory označené jako chybné jsou vynechané.
Syntaktickou platnost odkazů `/cesta#kotva` jsem neřešil (hlídá ji `scripts/check_anchors.php`).

Poznámka k číslovaným odkazům: všechny výskyty „NN.MM“ a „kapitola N“ v textu jsem strojově
spároval s nadpisy (`secrefs.py`). Žádný nemíří na neexistující sekci ani na chybnou kapitolu;
„předchozí kapitola“ v kap. 11 i 14 sedí. Rozpory níže jsou věcné, ne číselné.

---

## [R-1] Kdy a kde se dispatchují doménové události pod `doctrine_transaction` – čtyři různé odpovědi – závažnost: vysoká

- basic_concepts.md (#aggregate-root-lifecycle): „$this->em->flush();          // zápis do DB; transakci vlastní aplikační vrstva“
- basic_concepts.md (#aggregate-root-lifecycle): „Nasazení middlewaru proto vyžaduje“ … „[Outbox](/outbox-pattern), ne dispatch přímo z handleru.“
- implementation_in_symfony.md (#double-transaction-heading): „Synchronní dispatch v handleru tedy běží“ … „listenery už“ „reagovaly na událost, která se nikdy nestala. Spolehlivé řešení je opět“ [Outbox]
- implementation_in_symfony.md (#command-handler-example-heading): „Výjimečně explicitní flush.“ – a hned poté handler dispatchuje z handleru pod middlewarem, tj. přesně to, co kap. 6 zakazuje.
- authorization_in_ddd.md (CancelOrderHandler), sagas.md (#step-handlers-heading), ReleaseOrderLockHandler, case_study.md: explicitní `$this->em->flush();` je rutina, ne výjimka.
- sagas.md (#step-handlers-heading): „Dispatch uvnitř transakce je tu v pořádku: event.bus je“ „synchronní a nic neopouští proces.“
- outbox_pattern.md (#anti-publish-before-commit-heading): „Doménová událost poslaná“ „na synchronní `event.bus` uvnitř téže transakce dual-write není.“
- outbox_pattern.md (#naive-publish-heading): „// 1) Zápis do DB (commit Doctrine).“ – přitom `save()` podle kap. 10 jen persistuje a commit dělá middleware až po návratu handleru, takže dispatch v naivní ukázce běží **před** commitem.
- case_study.md (#create-project-handler-heading): „$this->em->flush();                       // commit zápisu“ a „Pořadí `save()` → `flush()` → `releaseEvents()` → `dispatch()` je záměrné. Publikovat před commitem“ – pod kanonickým `command.bus` flush commit není.
- Kanonicky správně: kap. 12 (#messenger-config-heading) i 15 (#anti-publish-before-commit-heading) zavádějí `doctrine_transaction` na `command.bus`. Pravidlo, které kniha v praxi používá, formuluje až kap. 15: synchronní in-process dispatch uvnitř transakce je v pořádku, do brokera/cizí služby jen přes outbox. Kapitoly 6 a 10 tvrdí opak (in-transaction dispatch = problém, nutný Outbox) a komentáře v kap. 6, 15 a 24 zaměňují flush za commit.
- Návrh opravy:
  - kap. 6, odstavec pod ukázkou: „Pod middlewarem `doctrine_transaction` je situace jiná. Transakci otevře před handlerem a commituje ji až po jeho návratu, takže `flush()` sám nic nepotvrzuje a dispatch běží uvnitř otevřené transakce. Synchronním posluchačům ve stejném procesu to nevadí – spadne-li transakce, zmizí i jejich zápisy. Co opouští proces (broker, e-mail, cizí služba), musí jít přes [Outbox](/outbox-pattern#anti-publish-before-commit-heading).“
  - kap. 6, komentář: `$this->em->flush();          // zápis SQL; commit řídí doctrine_transaction middleware`
  - kap. 10, callout #double-transaction-heading, poslední odstavec: „…Synchronní dispatch v handleru tedy běží uvnitř otevřené transakce. Posluchačům v témže procesu to nevadí, rollback vrátí i jejich zápisy. Vedlejší efekty mimo proces (e-mail, broker) by ale reagovaly na událost, která se nikdy nestala – ty patří do [Outboxu](/outbox-pattern).“
  - kap. 10, komentář „Výjimečně explicitní flush“ přeformulovat na důvod, ne výjimečnost (flush kvůli zachycení unique constraintu), aby nekolidoval s tím, že explicitní flush mají i handlery v kap. 11, 14 a 24.
  - kap. 15 naivní handler: `// 1) Zápis do DB – pod doctrine_transaction se commitne až po návratu handleru.` a v odrážce „Broker dispatch succeeded, DB write failed“ doplnit, že s middlewarem je to výchozí pořadí, ne jen „když někdo otočí pořadí“.
  - kap. 24: `$this->em->flush(); // zápis; commit vlastní middleware command busu` a větu „Publikovat před commitem …“ přepsat na „Publikovat před flushem …“.

## [R-2] Kanonická struktura projektu v kap. 10 odporuje zbytku knihy – závažnost: vysoká

- implementation_in_symfony.md (#project-structure): „Id.php         # Abstraktní ID“ (ve `SharedKernel/Domain/ValueObject/`)
- basic_concepts.md (#entity-identity): „Opakování je záměrné. Sdílený předek by sice ušetřil řádky“ … dovolil by předat `ProductId` místo `CustomerId`.
- performance_aspects.md (#uuid-vs-integer): „i UserId - každý ve svém kontextu, žádný ve sdíleném jádru.“
- implementation_in_symfony.md (#project-structure): „CreateOrderHandler.php“ ve feature složce `Ordering/Checkout/Command/`; také basic_concepts.md (#aggregate-root-lifecycle) `filename="src/Ordering/Application/Command/CreateOrderHandler.php (výřez)"`.
- Zbytek knihy (cqrs.md, outbox_pattern.md, sagas.md, authorization_in_ddd.md, event_storming.md): příkaz se jmenuje `PlaceOrder`/`PlaceOrderCommand` a leží v `App\Ordering\Application\Command`, handlery v `App\Ordering\Application\Handler` – adresář `Ordering/Application/` ve stromu kap. 10 vůbec není.
- UserManagement: implementation_in_symfony.md (#command-handler-example-heading) „namespace App\UserManagement\Registration\Command;“ vs. migration_from_crud.md (#command-extraction-heading) „namespace App\UserManagement\Application\Command;“ – dvě třídy `RegisterUser`/`RegisterUserHandler` s různým FQCN pro týž use case; performance_aspects.md má `App\UserManagement\Application\Query` vedle `App\UserManagement\Profile\Query` z kap. 10/12.
- Kanonicky správně: identifikátory bez sdíleného předka (kap. 6, 16); Ordering podle kap. 12–15 používá vrstvy `Domain/Application/Infrastructure` (to potvrzuje i subdomains.md: „na první úrovni Bounded Context nebo subdoména, uvnitř vrstvy“).
- Návrh opravy: ve stromu kap. 10 smazat `SharedKernel/Domain/ValueObject/Id.php`; Ordering větev přepsat na
  ```
  ├── Ordering/
  │   ├── Domain/{Model,ValueObject,Event,Exception,Repository}/
  │   ├── Application/
  │   │   ├── Command/PlaceOrder.php
  │   │   ├── Handler/PlaceOrderHandler.php
  │   │   └── Query/…
  │   └── Infrastructure/{Repository,Doctrine}/
  ```
  a v kap. 6 přejmenovat výřez na `src/Ordering/Application/Handler/PlaceOrderHandler.php (výřez)`. Pokud má UserManagement zůstat ve feature slicích, napsat to výslovně pod strom („Ordering drží vrstvy, UserManagement feature slice – obě varianty kniha používá, viz kap. 9 Vertical Slice“). V kap. 18 buď použít FQCN z kap. 10, nebo říct, že `Application\Command` je cílový stav migrovaného legacy projektu, ne kanonický projekt.

## [R-3] Signatury doménových událostí `Order*` se mezi kapitolami liší – závažnost: vysoká

- aggregate_design.md (#references-by-id): `OrderConfirmed(OrderId $orderId, CustomerId $customerId, \DateTimeImmutable $occurredAt)`, `OrderShipped(OrderId, ShipmentId, occurredAt)`; text: „Události, které přechody nahrávají, mají jednotný tvar“ … „takže pole nese každá z nich“.
- basic_concepts.md (#aggregate-root-lifecycle): „$this->record(new OrderConfirmed($this->id));“ + „`OrderConfirmed` je obdoba `OrderPlaced` z předchozí sekce.“
- ddd_pain_points.md (#c2-stavy): „$this->record(new OrderConfirmed($this->id));“ a „$this->record(new OrderShipped($this->id, $trackingNumber));“, `public function ship(TrackingNumber $trackingNumber): void` – kanonický `ship(ShipmentId)`.
- anti_patterns.md (#udalosti-spravne-heading), „správná“ varianta se stejným FQCN `App\Ordering\Domain\Event\OrderPlaced`: „public Money $totalAmount,“ + `public int $itemCount` – kanonické `Order::place()` volá `new OrderPlaced($id, $customerId)` (10 kapitol) a v okamžiku `place()` žádný součet neexistuje.
- aggregate_design.md: `OrderItemAdded` nese jen `orderId, productId, quantity` – bez `occurredAt`, ačkoli o tři řádky výš text tvrdí, že pole nese „každá z nich“.
- Kanonicky správně: definice z kap. 7 (#references-by-id) a `OrderPlaced(OrderId, CustomerId)` z kap. 6 (#domain-events).
- Návrh opravy:
  - kap. 6: `$this->record(new OrderConfirmed($this->id, $this->customerId, new \DateTimeImmutable()));`
  - kap. 20 C2: `public function ship(ShipmentId $shipmentId): void` … `$this->record(new OrderShipped($this->id, $shipmentId, new \DateTimeImmutable()));` a `confirm()` jako v kap. 6. Pokud má C2 ukázat `TrackingNumber` záměrně, přidat komentář „varianta: číslo zásilky místo ShipmentId, kanonický model viz Návrh agregátu“.
  - kap. 21: přejmenovat soubor/třídu na `OrderPlacedWithTotals` nebo ukázat neměnnost na kanonickém tvaru (`OrderId`, `CustomerId`, `occurredAt`) a `recordedAt` ponechat.
  - kap. 7: buď do `OrderItemAdded` přidat `public \DateTimeImmutable $occurredAt` (a upravit volání v `addItem()` v kap. 6 a 7), nebo větu zúžit: „…takže pole nesou všechny přechodové události (`OrderConfirmed` až `OrderCancelled`).“

## [R-4] Konvence obsahu doménové události: hodnotové objekty, nebo primitivy? – závažnost: střední

- aggregate_design.md (#references-by-id): „mají jednotný tvar: identita agregátu jako hodnotový“ objekt.
- outbox_pattern.md (#place-order-handler-heading): „Doménová událost se do outboxu nedává přímo: nese hodnotové“ objekty.
- sagas.md (#order-placed-event-heading): doménová `OrderPlaced` „nese hodnotové objekty a zůstává uvnitř kontextu“.
- implementation_in_symfony.md (#domain-event-example-heading): `UserRegistered` – „// Událost nese primitivy – serializuje se bez závislosti na VO třídách.“
- Kanonicky správně: CLAUDE.md konvenci výslovně neurčuje; kniha ale dvakrát (kap. 7, 15) tvrdí VO jako pravidlo a u `UserRegistered` ho bez komentáře porušuje. Test v kap. 17 (`$events[0]->email` jako string) stojí na primitivní variantě.
- Návrh opravy (menší zásah): do kap. 10 pod ukázku `UserRegistered` doplnit větu „Na rozdíl od událostí objednávky (VO, viz Návrh agregátu) nese `UserRegistered` primitivy: odebírá ji kontext Identity a posílá se async, takže tvar volíme jako u integrační události.“ – nebo sjednotit na VO a upravit test v kap. 17.

## [R-5] `Order::placeWithItems()` (kap. 15) nahrává `OrderPlaced` až po `OrderConfirmed` a tvrdí shodný konstruktor – závažnost: střední

- outbox_pattern.md (#order-aggregate-heading): po `addItem()`, `confirm()` a `lockForSaga()` teprve „$order->record(new OrderPlaced($order->id, $customerId));“
- outbox_pattern.md (#place-order-handler-heading): „confirm() OrderConfirmed a nakonec OrderPlaced“.
- aggregate_design.md (#references-by-id): `placeWithFirstItem()` volá `self::place()` → `OrderPlaced` je první.
- testing_ddd.md (nadpis „Testování agregátů“ pod #unit-testy-domeny, test `testReleasesRecordedEventsInOrder`): „$this->assertInstanceOf(OrderPlaced::class, $events[0]);“ a komentář „// OrderPlaced + OrderItemAdded + OrderConfirmed“.
- outbox_pattern.md (#order-aggregate-heading): „// Stejný konstruktor jako ve zbytku knihy; položky přibývají metodou.“ – výřez ale nenastavuje `$this->status = OrderStatus::Draft`, takže `addItem()` v něm spadne na neinicializované vlastnosti.
- Kanonicky správně: pořadí událostí `OrderPlaced` → `OrderItemAdded`… → `OrderConfirmed` (kap. 7 a 17).
- Návrh opravy: v `placeWithItems()` začít `$order = self::place(OrderId::generate(), $customerId);`, smazat pozdní `record(new OrderPlaced…)` a komentář v handleru upravit na „`place()` nahraje OrderPlaced, `addItem()` OrderItemAdded, `confirm()` OrderConfirmed“. Do konstruktoru výřezu doplnit `$this->status = OrderStatus::Draft;`, nebo místo celého konstruktoru napsat `// konstruktor viz Návrh agregátu`.

## [R-6] Kap. 18 „cílový stav migrace“ přepisuje kanonický `User` pod stejným FQCN a láme kap. 10, 11 a 17 – závažnost: střední

- migration_from_crud.md (#before-after-heading): „#[ORM\Table(name: 'um_users')]“, „public readonly \DateTimeImmutable $registeredAt;“, vlastnost `HashedPassword $password`, getter `hashedPassword()` chybí.
- implementation_in_symfony.md (#entities): `#[ORM\Table(name: 'users')]`, `public readonly \DateTimeImmutable $createdAt;`, `public function hashedPassword(): HashedPassword`.
- authorization_in_ddd.md (#registration-two-writes-heading): „passwordHash: $user->hashedPassword()->value,“ – s `User` z kap. 18 neprojde.
- migration_from_crud.md (#command-extraction-heading): „$this->users->nextIdentity(),“ a „$this->policy->assertEmailIsUnique($email);“ – `nextIdentity()` kanonický `UserRepository` z kap. 10 nemá a `UserRegistrationPolicy` není v knize nikde definovaná.
- cqrs.md (#command-handler-heading): „Za pozornost stojí, co v něm **není**: kontrola duplicity přes `findByEmail()`“ – kap. 10/12 kontrolu přes repozitář odmítají kvůli TOCTOU, kap. 18 ji jako cílový stav zavádí.
- migration_from_crud.md (#email-vo-heading): `Email::fromUserInput()` odmítá „['example.com', 'test.com']“.
- testing_ddd.md (#test-doubles): posílá „new RegisterUser(name: 'Jan Novák', email: 'jan@example.com'“ přes handler, který volá `fromUserInput()` – s `Email` z kap. 18 test spadne na `ForbiddenEmailDomainException`.
- Kanonicky správně: podle rozhodnutí z 9/2026 je aktivační model v kap. 18 **rozšíření** kanonického `User` z kap. 10, ne jiný model.
- Návrh opravy: v kap. 18 zachovat `createdAt`, `hashedPassword` + getter `hashedPassword()` a tabulku `users` (nebo přidat komentář, proč nový kontext dostává `um_users`, a že getter `hashedPassword()` zůstává z kap. 10); `nextIdentity()` nahradit `UserId::generate()`; `UserRegistrationPolicy` buď definovat a dodat, že spoléhá navíc na unique constraint (odkaz `/implementace-v-symfony#register-race-heading`), nebo vypustit. Zakázané domény v příkladu změnit na `mailinator.com`, aby nekolidovaly s `example.com` v testech kap. 17.

## [R-7] Doménové události přes hranici kontextu – kniha pravidlo zavede a pak porušuje – závažnost: střední

- basic_concepts.md (#domain-events): „Poslat `OrderPlaced` ven proto znamená zveřejnit vnitřní model“.
- implementation_in_symfony.md (#project-structure): „cross-context import doménových tříd je signál“ chybějící ACL.
- sagas.md (#order-placed-event-heading): „Konzumuje proto **integrační** událost“ – ale tatáž kapitola: „use App\Payment\Domain\Event\PaymentSucceeded;“ ve Warehouse handleru i v `OrderProcessManager` a routing „'App\Payment\Domain\Event\PaymentSucceeded': async_events“ (#choreografie-messenger-heading, #messenger-yaml-heading).
- microservices_and_ddd.md (#messenger-publisher-heading): „'App\Ordering\Domain\Event\OrderPlaced': events_out“ – doménová třída na outbox transport (překlad obstará serializer, ale text kap. 15 říká opak: do outboxu jde integrační tvar).
- Kanonicky správně: přes hranici kontextu jde integrační událost s primitivy (kap. 6, 15, 14.07).
- Návrh opravy: v kap. 14 u první ukázky choreografie (#choreografie-handlers-heading) přidat poznámku „Pro stručnost konzumují Warehouse i Ordering přímo doménové události kontextu Payment. V produkci by Payment publikoval `PaymentSucceededIntegrationEvent` stejně jako Ordering v kapitole Outbox.“ V kap. 19 u routingu doplnit „serializer výše je překladová vrstva – na drát nejde tvar doménové třídy“.

## [R-8] `messenger.yaml` v kap. 9 se rozchází s „kanonickou konfigurací celé knihy“ – závažnost: střední

- cqrs.md (#messenger-config-heading): „jména transportů a busů“ … „jsou všude stejná“; transporty `async_commands`, `async_events`, routing jen integrační události.
- architectural_styles.md (#symfony-messenger-heading): „async: '%env(MESSENGER_TRANSPORT_DSN)%'“ a „App\Ordering\Domain\Event\OrderPlaced: async“ (+ `OrderCancelled`) – doménové události s VO asynchronně, tedy přesně to, před čím varuje kap. 15.
- Návrh opravy:
  ```yaml
          transports:
              async_events: '%env(MESSENGER_TRANSPORT_DSN)%'
          routing:
              # Doménové události zůstávají synchronní; ven jde integrační tvar
              # (viz kapitola Outbox Pattern).
              App\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent: async_events
  ```
  a do názvu bloku doplnit „(výřez – plná konfigurace v kapitole o CQRS)“.

## [R-9] Pravidla a signatura storna objednávky – závažnost: střední

- implementation_in_symfony.md (#enum-example-heading): „self::Paid => [self::Shipped, self::Cancelled],“; aggregate_design.md i authorization_in_ddd.md: storno odmítnou jen `Shipped`/`Delivered`, signatura `cancel(string $reason, \DateTimeImmutable $when)`.
- basic_concepts.md (#aggregates): `public function cancel(): void` a „if ($this->status !== OrderStatus::Draft && $this->status !== OrderStatus::Confirmed) {“ – zaplacenou objednávku zrušit nejde.
- event_storming.md (#dl-mapping): „public function cancel(string $reason): void“ se stejnou podmínkou a hláškou „Cannot cancel a shipped order“ (odmítá i `Paid`); test v #tdd-mapping volá „$order->cancel('customer request');“.
- Kanonicky správně: kap. 7/10/11 – `Paid → Cancelled` je povolený přechod, `cancel(reason, when)`.
- Návrh opravy: kap. 6: `public function cancel(string $reason, \DateTimeImmutable $when): void` s podmínkou `if (in_array($this->status, [OrderStatus::Shipped, OrderStatus::Delivered], true))`; kap. 4 totéž + v testu `$order->cancel('customer request', new \DateTimeImmutable());`. Pokud má workshop v kap. 4 záměrně jiné pravidlo, napsat to do komentáře („draft z workshopu; kanonický přechod viz Návrh agregátu“).

## [R-10] Tabulka `order_summary` má tři neslučitelná schémata; odkaz na CQRS míří na jinou tabulku – závažnost: střední

- event_sourcing.md (#pozice-projekce): „CREATE TABLE order_summary (“ se sloupci `order_id VARCHAR(36) PK`, `total_amount`, `placed_at`.
- performance_aspects.md (#keyset-paging-heading): „SELECT id, created_at, total_amount_in_cents“ … `FROM order_summary`.
- performance_aspects.md (#uuid-vs-integer): „#[ORM\Table(name: 'order_summary')]“ se sloupci `id` (UUID) a `orderNumber`; komentář „(viz kapitola CQRS)“ – kap. 12 ale plní tabulku `order_dashboard`.
- Návrh opravy: v kap. 16 přejmenovat na `order_dashboard` a sloupce sladit s DDL v kap. 12 (`order_id`, `placed_at`, `total_amount`), nebo pojmenovat vlastní tabulku (`order_feed`) a komentář změnit na „projektor v duchu kapitoly CQRS“.

## [R-11] Kap. 14 tvrdí, že `markPaid()`/`ship()` idempotentní nejsou – kanonická verze už je – závažnost: střední

- sagas.md (#idempotent-saga-transitions-heading): „`markPaid()` a `ship()` ale ne, přestože jdou přes tentýž asynchronní transport.“
- aggregate_design.md (#references-by-id): `markPaid()` začíná `if ($this->status === OrderStatus::Paid) { return; }`, `ship()` totéž pro `Shipped`.
- Návrh opravy: „Druhá polovina obrany patří do agregátu. `cancel()`, `markPaid()` i `ship()` v kanonické verzi z Návrhu agregátu končí při opakovaném doručení tiše. Bez té větve by opakované `MarkOrderPaid` skončilo po třech pokusech v DLQ s hláškou …:“ – výpis pak uvést jako připomenutí, ne jako doplněk.

## [R-12] Kap. 11 slibuje detail v kap. 21, který tam není – závažnost: nízká

- authorization_in_ddd.md (#anti-symfony-user-domain-heading): „Detail v [kapitole o anti-vzorech](/anti-vzory).“
- anti_patterns.md: žádná sekce o závislosti domény na Symfony Security / `UserInterface` (21.07 řeší opačný směr – doménovou logiku v infrastruktuře).
- Návrh opravy: odkaz nahradit `[architektonickým testem](#testing-architecture-heading)` (už ve větě je) a větu „Detail v kapitole o anti-vzorech“ smazat, nebo odkázat na `/anti-vzory#logika-v-infrastrukture` se slovy „opačný směr téhož porušení vrstev rozebírá …“.

## [R-13] Repozitář v kap. 9 vrací `?Order`, kanonický `get()` hází výjimku – závažnost: nízká

- architectural_styles.md (#hexagonal-priklad-heading): „public function get(OrderId $id): ?Order;“ v `App\Ordering\Domain\Port\OrderRepository`.
- basic_concepts.md (#repositories): „proto `get()` vrací `Order` a hází výjimku“; kanonický namespace `App\Ordering\Domain\Repository`.
- Návrh opravy: `public function get(OrderId $id): Order; // @throws OrderNotFoundException` a v adaptéru `?? throw OrderNotFoundException::withId($id)`; pod strom doplnit, že `Port/` je hexagonální pojmenování adresáře `Repository/` ze zbytku knihy.

## [R-14] Varianta „Doctrine transport jako outbox“ v kap. 15 – nikdo nedispatchuje integrační událost – závažnost: nízká

- outbox_pattern.md (#doctrine-transport-outbox-heading): varianta běží „i bez relay commandu“, ale routing v #doctrine-transport-routing-heading má komentář „# Relay posílá integrační tvar, ne doménovou událost.“
- `PlaceOrderHandler` (#place-order-handler-heading) integrační událost jen ukládá přes `$this->outbox->store(...)`; na sběrnici ji v této variantě nic nepošle.
- Návrh opravy: komentář změnit na „# Handler dispatchne integrační tvar na event.bus místo outbox->store()“ a pod YAML přidat dvouřádkový výřez handleru s `$this->eventBus->dispatch($integrationEvent);`.

## [R-15] Holý `#[AsMessageHandler]` a event bus bez `#[Target]` proti pravidlu kap. 12 – závažnost: nízká

- cqrs.md (#messenger-config-heading): holý `#[AsMessageHandler]` → „handler se zaregistruje na“ **všechny** sběrnice; „Obojí se řeší explicitně“.
- case_study.md (#create-project-handler-heading) „#[AsMessageHandler]“ (a dalších 8×), stejně event_storming.md, event_sourcing.md, performance_aspects.md, migration_from_crud.md, microservices_and_ddd.md, context_mapping.md.
- Návrh opravy: doplnit `bus: 'command.bus'` / `'event.bus'` / `'query.bus'`, nebo v kap. 24 jednou větou říct, že případová studie má jen jednu sběrnici (a proto default).

## [R-16] Listener v kap. 23 ukládá objednávku bez flushe a bez vydání událostí – závažnost: nízká

- practical_examples.md (#cart-checkout-to-order): handler na `event.bus` končí „$this->orders->save($order);“ – `save()` podle kap. 10 jen persistuje a `event.bus` nemá `doctrine_transaction`; `OrderPlaced` se nevyzvedne; objednávka zůstane v `Draft`, takže ji sága nemůže posunout (`markPaid()` vyžaduje `Confirmed`).
- Návrh opravy: dispatchnout `PlaceOrder` na `command.bus` (transakce + outbox z kap. 15), nebo doplnit `$order->confirm(); … $this->em->flush(); foreach ($order->releaseEvents() …)` a komentář, proč.

## [R-17] „Výřez kanonického Order“ v kap. 22 kanonický není – závažnost: nízká

- when_not_to_use_ddd.md (#pseudo-ddd-heading): „Výřez kanonického Order z kapitoly Návrh agregátu“ – třída je `final`, konstruktor nenastavuje `status`, `cancel()` nemá zámek ságy ani idempotentní větev pro `Cancelled`.
- Návrh opravy: napsat „Zkrácená podoba kanonického Order …“ a do těla `cancel()` dát `// … zámek ságy a idempotence viz Návrh agregátu`.

## [R-18] Význam `placedAt` – závažnost: nízká

- aggregate_design.md (#references-by-id): „// Čas potvrzení drží agregát“ – `placedAt` se nastavuje v `confirm()`.
- lesser_known_patterns.md (#fac-static): „private readonly \DateTimeImmutable $placedAt,“ – nastavuje se při vzniku (`placePhysical`).
- anti_patterns.md (#agregat-spravny-heading): `$this->placedAt = new \DateTimeImmutable();` v konstruktoru.
- Návrh opravy: v kap. 8 a 21 buď přejmenovat na `createdAt`, nebo do komentáře „na rozdíl od kanonického modelu, kde `placedAt` znamená čas potvrzení“.

## [R-19] Drobné odchylky od kanonického modelu bez přiznání – závažnost: nízká

- migration_from_crud.md (#recept-fields-jako-stav-heading): „case Placed = 'placed'“ – kanonický enum má `Draft/Confirmed/Paid/Shipped/Delivered/Cancelled`. Návrh: `enum OrderStatus: string { case Draft = 'draft'; case Confirmed = 'confirmed'; /* … */ case Cancelled = 'cancelled'; }`.
- basic_concepts.md (#aggregates) a implementation_in_symfony.md (#enum-usage-heading): „public function status(): OrderStatus“ – kap. 7 tvrdí, že getter `status()` díky `public private(set)` odpadá. Návrh: v kap. 6 přidat komentář „základní podoba; od PHP 8.4 viz asymetrická viditelnost v Návrhu agregátu“.
- event_storming.md (#dl-mapping), ddd_pain_points.md (#c2-stavy): `throw new InvalidOrderStateTransitionException('…')` s anglickou/volnou hláškou místo pojmenované továrny `cannotTransition()`/`notAllowedInState()` z kap. 10.
- aggregate_design.md (#references-by-id): komentář „// Čas přebírá parametr, ne new \DateTimeImmutable() uvnitř“ stojí nad `lockForSaga()`, patří nad `cancel()`.
- event_sourcing.md: `src/Identity/Domain/Event/UserRegistered.php` (kanonicky `UserManagement`) a `namespace App\Infrastructure\Ordering;` (jinde vždy `App\<Context>\Infrastructure`). Kapitola vlastní model přiznává pro `Order`, pro tyto dvě odchylky ne.

## [R-20] Předmluva dělí kapitoly jinak než katalog/hub stránky – závažnost: nízká

- preface.md (#cast-2): „Část 2 – Taktický design (kap. 6–9)“; (#cast-3) „kap. 10–11“; (#cast-4) „kap. 12–15“; (#cast-5) „Část 5 – Výkon a testování (kap. 16–17)“.
- src/Catalog/Chapters.php: hub `tactics` = 06–08, `architecture` = 09–11, `patterns` = 12–16, `practice` = 17–22.
- Návrh opravy: buď sladit části předmluvy s huby (Taktika 6–8, Architektura 9–11, Vzory 12–16, Praxe 17–22), nebo jednou větou říct, že tištěné části a webové rozcestníky dělí knihu jinak.

---
<!-- reports/consistency-terms.md -->
# Konzistence pojmů a faktů napříč knihou – nálezy

Rozsah: content/chapters/*.md (00–24 + ai), templates/ddd/glossary.html.twig, templates/ddd/cheat_sheet.html.twig.
Stav textu: 2026-09-23 (souběžně probíhají jazykové úpravy, citace jsou doslovné k tomuto datu).
Atribuce a roky (Evans 2003/2015, Vernon 2011/2013/2016, Khononov 2021, Young 2010, Fowler, Brandolini 2013, Skelton & Pais 2019/2025, Hohpe & Woolf 2003, Garcia-Molina & Salem 1987, Cockburn 2005, Palermo 2008, Martin 2012/2017, Dahan 2007, Helland 2007, Evans a Fowler 1997, Evans QCon London 2016) jsou napříč knihou jednotné a ověřené. Jediná odchylka je K-19.

---

## [K-1] Definice doménové události: glosář tvrdí opak kapitoly 06 – závažnost: vysoká
- glossary.html.twig (term-domenova-udalost): „Doménové události slouží k volnému propojení mezi agregáty i Bounded Contexty. Musí obsahovat všechna data potřebná k rekonstrukci změny stavu – nespoléhají na pozdější dotazování.“
- basic_concepts.md (domain-events): „Uvnitř jednoho kontextu se osvědčí tenká varianta, jakou ukazuje `OrderPlaced`: příjemce má k agregátu přístup a duplikovaná data by se dřív nebo později rozešla.“ a „Hranice kontextu rozděluje události na doménové a integrační. Doménová událost mluví jazykem `Ordering` a zůstává uvnitř.“
- anti_patterns.md (infra-spravne-heading): „Doménová událost je vnitřní věc kontextu, integrační událost je veřejný kontrakt vůči okolí.“
- outbox_pattern.md (domain-event-heading): „Ta nese hodnotové objekty a zůstává uvnitř kontextu. Do outboxu jde **integrační** událost…“
- Co je správně a proč: Kniha jinde soustavně rozlišuje doménovou a integrační událost (zdroj [13] v kap. 06, Microsoft/C. de la Torre). Kapitola 06 výslovně doporučuje tenké události uvnitř kontextu (Fowler: notifikace vs. Event-Carried State Transfer). Glosář tvrdí opak v obou bodech. Integrační událost navíc v glosáři vůbec nemá heslo.
- Návrh sjednocení: V glosáři nahradit druhou a třetí větu textem: „Doménové události propojují agregáty uvnitř jednoho Bounded Contextu. Hranici kontextu překračuje až integrační událost, tedy samostatný veřejný kontrakt s primitivními daty. Kolik dat událost nese, je rozhodnutí: uvnitř kontextu obvykle stačí identifikátory.“ Doplnit heslo „Integrační událost (Integration Event)“ s odkazem na /zakladni-koncepty#domain-events a /outbox-pattern.

## [K-2] Bounded Context jako „jednotka nasazení“, který není modul – závažnost: vysoká
- glossary.html.twig (term-ohraniceny-kontext): „Bounded Context je fundamentální jednotkou návrhu i nasazení v DDD. Určuje hranice týmů, nasazení, databázových schémat i kontraktů API. Nezaměňujte ho s modulem nebo namespacem – jde o organizační i technický konstrukt.“
- context_mapping.md (bc-modul-deployment): „**Modul** je promítnutí té hranice do kódu. V Symfony je to namespace `App\Ordering` a adresář pod ním. Jeden BC = jeden modul je rozumné výchozí mapování. - **Nasazovací jednotka** je provozní rozhodnutí.“ a „Rovnice „Bounded Context = microservice“ je tedy zkratka, ne definice.“
- microservices_and_ddd.md (frontmatter deck): „Bounded Context je logická hranice modelu; microservice je fyzická hranice deploymentu.“ Tabulka v (mytus-pravda-heading): „Existuje i v monolitu | Ano, vždy – jako modul“.
- Co je správně a proč: BC je hranice modelu a jazyka (Evans 2003, kap. 14). Nasazení je nezávislé rozhodnutí a Evans sám v roce 2019 označil „mikroslužba = BC“ za zjednodušení (what_is_ddd.md, zdroj [7]). Glosář tak odporuje kapitolám 01, 03 a 19.
- Návrh sjednocení: „Explicitní hranice, uvnitř níž platí jeden doménový model a jeho Ubiquitous Language. Bounded Context je logická hranice. V kódu ji obvykle promítá jeden modul (namespace), provozně může běžet v modulárním monolitu i jako samostatná služba. Mapování na týmy, schémata a nasazení je samostatné rozhodnutí (viz Context Mapping, DDD a microservices).“

## [K-3] Způsobuje velký agregát víc konfliktů? Dvě kapitoly tvrdí opak – závažnost: vysoká
- aggregate_design.md (aggregate-size): „**Konkurence.** Větší agregát = větší zámek = více konfliktů mezi uživateli. Pokud `Project` drží všechny `Task`y, dvě paralelní úpravy úkolů si konkurují…“
- performance_aspects.md (FAQ, runtime-optimalizace-heading): „Příliš velký agregát načítá při každé operaci desítky vnitřních entit a vede k častým konfliktům optimistického zamykání.“
- case_study.md (trade-off-aggregate-size-heading): „menší agregát = menší zámek = vyšší propustnost.“
- anti_patterns.md (agregat-problemy-heading): „`#[ORM\Version]` na kořeni se zvedne jen při změně vlastností **kořene**; přidání potomka do `OneToMany` kolekce ho nechá být. […] Velký agregát tedy nepřináší víc konfliktů, ale invariant, který se tiše poruší.“
- aggregate_design.md (further-reading) sám cituje: „doctrine/orm, issue #3620 […] – doklad, že Doctrine verzi kořene při změně potomka nezvyšuje.“
- Co je správně a proč: Obecně (Vernon 2011) platí, že větší agregát znamená víc souběžných kolizí, pokud se verze kořene zvyšuje při každé změně. V Doctrine ORM 3 se to bez `OPTIMISTIC_FORCE_INCREMENT` nebo ručního bumpu verze neděje. Výsledkem je tiché porušení invariantu (anti_patterns má pravdu pro výchozí Doctrine). Kapitola 07 dokonce cituje doklad, který její vlastní tvrzení v 07.04 vyvrací.
- Návrh sjednocení: V 07.04 první odrážku přepsat: „**Konkurence.** Pokud každá změna zvedá verzi kořene, velký agregát znamená víc konfliktů mezi uživateli. Doctrine ale verzi kořene při změně potomka sám nezvedne. Bez ručního bumpu nebo `LockMode::OPTIMISTIC_FORCE_INCREMENT` proto konflikt nevznikne vůbec a invariant se tiše poruší (viz [Anti-vzory](/anti-vzory#agregat-problemy-heading)).“ Obdobně upravit FAQ v kap. 16 a formulaci v kap. 24.

## [K-4] Převod peněz a dva agregáty v jedné transakci: tři kapitoly, tři postoje – závažnost: vysoká
- aggregate_design.md (transactional-consistency): převod v jedné transakci je označen jako „ŠPATNĚ“ a správná verze je „// SPRÁVNĚ: jeden agregát na transakci, sága přes doménovou událost“.
- lesser_known_patterns.md (ds-priklad): „Ukázka se drží Evansova výkladu: služba mění oba účty a handler, který ji volá, je uloží jednou transakcí. Kapitola Návrh agregátu tentýž převod uvádí jako anti-vzor…“ (rozpor zde přiznán a vysvětlen)
- implementation_in_symfony.md (kdy-domain-service-heading): „**Operace nad 2+ agregáty.** Klasický `MoneyTransferService::transfer($from, $to, $amount)`. […] (Pozor: ukládá se pořád v jedné transakci na jeden agregát, viz agregát = transakční hranice.)“
- implementation_in_symfony.md (payment-handler-heading): „Handler zapisuje dva agregáty v jedné transakci. […] Držíme ji vědomě: přechod `Order` do stavu `Paid` a vznik odpovídajícího `Payment` tvoří jediný invariant…“
- aggregate_design.md (breaking-the-rule): „Khononov k tomu přidává diagnostiku, ne zákaz: potřeba commitnout změny ve více agregátech signalizuje špatně vedenou transakční hranici.“
- Co je správně a proč: Závorka v kap. 10 je věcně nepravdivá: `transfer($from, $to)` mění dva agregáty, které se pak uloží společně (kap. 08 to říká výslovně). Zdůvodnění u `RecordPaymentHandler` („jediný invariant“ přes dva agregáty) podle vlastní definice knihy znamená špatně vedenou hranici agregátu. Není to žádná ze čtyř Vernonových výjimek, které kap. 07 vyjmenovává.
- Návrh sjednocení: V kap. 10 závorku nahradit: „(Služba mění oba účty. Uložit je v jedné transakci je vědomé porušení vodítka „jeden agregát na transakci“; bez výjimky podle [Kdy se vodítko poruší](/navrh-agregatu#breaking-the-rule) převod rozloží sága, viz [Doplňující taktické vzory](/mene-zname-vzory#ds-priklad).)“ U `RecordPaymentHandler` zdůvodnění opřít o Vernonovu výjimku, nebo přiznat, že jde o signál k přehodnocení hranice: „Podle Khononova je taková potřeba signálem, že hranice mezi `Order` a `Payment` stojí jinde. Kniha odchylku drží kvůli jednoduchosti ukázky.“

## [K-5] Cheat sheet: čísla kapitol v „Reading paths“ neodpovídají katalogu – závažnost: vysoká
- cheat_sheet.html.twig (reading-paths-heading): „17 – Read modely, projekce a výkon“, „20 – DDD a microservices“, „19 – Migrace z CRUD na DDD“, „22 – Anti-vzory“, „13 – CQRS v Symfony“, „14 – Event Sourcing“, „15 – Sagy a Process Manager“, „16 – Outbox Pattern“, „18 – Testování v DDD“.
- Frontmatter `chapter_number`: performance_aspects 16, microservices_and_ddd 19, migration_from_crud 18, anti_patterns 21, cqrs 12, event_sourcing 13, sagas 14, outbox_pattern 15, testing_ddd 17.
- Co je správně a proč: Všechna čísla od kapitoly 10 výš jsou posunutá o +1 (pozůstatek staršího číslování). Kapitoly 01–09 sedí.
- Návrh sjednocení: 16 – Read modely, projekce a výkon; 19 – DDD a microservices; 18 – Migrace z CRUD na DDD; 21 – Anti-vzory; 12 – CQRS v Symfony 8; 13 – Event Sourcing; 14 – Ságy a Process Managery; 15 – Outbox Pattern; 17 – Testování DDD kódu. Nejlépe číslo i název generovat z `Chapters::all()`, ne psát ručně.

## [K-6] CQRS jako „rozšíření CQS“: glosář tvrdí to, co kapitola 12 vyvrací – závažnost: střední
- glossary.html.twig (term-cqrs): „Zavedl ho Greg Young jako rozšíření principu CQS Bertranda Meyera na architektonickou úroveň.“
- glossary.html.twig (term-cqs): „CQRS aplikuje tento princip na architektonické úrovni – odděluje celé modely, nejen metody.“
- cqrs.md (what-is-cqrs): „Young v *CQRS Documents* dodává, že první roky se o vzoru mluvilo jako o rozšíření CQS na vyšší úrovni. Tuto formulaci sám označuje za nepřesnou.“ Totéž opakuje FAQ v cqrs.md: „sám Young ale označuje formulaci „CQRS je rozšíření CQS“ za nepřesnou“.
- Co je správně a proč: Young, *CQRS Documents* (2010): CQRS vychází z CQS, ale je to samostatný vzor, ne jeho rozšíření.
- Návrh sjednocení (term-cqrs): „Pojmenoval ho Greg Young (kolem roku 2010). Kořeny sahají ke CQS Bertranda Meyera, Young ale formulaci „CQRS je rozšíření CQS“ sám označuje za nepřesnou a pojímá CQRS jako samostatný vzor.“ V term-cqs větu změnit na „CQRS z tohoto principu vychází, ale neaplikuje ho mechanicky: odděluje celé modely…“.

## [K-7] CQRS: „vlastní úložiště“ jako součást definice vs. mýtus „dvě databáze“ – závažnost: střední
- cqrs.md (cqs-vs-cqrs): „CQRS rozděluje jeden doménový model na dva, každý s vlastní sadou tříd, vlastním úložištěm a vlastním optimalizačním profilem.“
- cqrs.md (cqrs-myty-heading): „**„CQRS vyžaduje dvě databáze.“** Young popisuje read stranu jako tenkou vrstvu, která čte z **téže** databáze jako write strana […] Oddělené úložiště je jedna z možností, ne součást definice vzoru.“
- glossary.html.twig (term-cqrs): „Každá strana má vlastní model“ (bez úložiště, v pořádku).
- Co je správně a proč: Platí formulace z callout „Tři mýty“ (Young 2010, Azure Architecture Center). Věta v 12.02 z úložiště dělá definiční znak.
- Návrh sjednocení: „…rozděluje jeden doménový model na dva, každý s vlastní sadou tříd a vlastním optimalizačním profilem. Oddělené úložiště je volitelné (viz Tři mýty o CQRS).“

## [K-8] Kompenzace ság: „komutativní“ (glosář) vs. „v opačném pořadí“ (kap. 14) – závažnost: střední
- glossary.html.twig (term-kompenzace): „…a uvnitř ságy by měly být **commutative**, aby pořadí kompenzací při paralelním běhu neovlivnilo výsledek.“
- sagas.md (backward-recovery): „…vrátit systém do konzistentního stavu kompenzačními akcemi v **opačném pořadí** dokončených kroků.“ Diagram 14.9-A: „Kompenzační flow – rollback ságy v opačném pořadí“. Také sagas.md (2pc-heading): „…systém provede kompenzace všech předchozích úspěšných kroků – v opačném pořadí.“
- sagas.md (izolace-sag): „*Commutative updates* jsou operace navržené tak, aby na pořadí nezáleželo – připsání a odepsání částky komutuje…“ (Richardsonovo protiopatření proti izolačním anomáliím mezi souběžnými ságami)
- Co je správně a proč: Garcia-Molina & Salem (1987) i Richardson (2018) kompenzují v opačném pořadí. „Commutative updates“ je u Richardsona protiopatření proti dirty reads mezi ságami, ne požadavek na kompenzace. Glosář oba pojmy smíchal.
- Návrh sjednocení: „Kompenzační kroky musí být idempotentní a tolerantní k absenci kompenzovaného kroku. Spouštějí se v opačném pořadí dokončených kroků. (Komutativní operace jsou samostatné protiopatření proti izolačním anomáliím mezi souběžnými ságami, viz /sagy-a-process-managery#izolace-sag.)“ Zároveň nahradit anglické „commutative“ českým „komutativní“.

## [K-9] Smí jiný kontext odebírat doménovou událost přímo? – závažnost: střední
- basic_concepts.md (domain-events): „Doménová událost mluví jazykem `Ordering` a zůstává uvnitř. […] Poslat `OrderPlaced` ven proto znamená zveřejnit vnitřní model se vším, co z toho plyne.“
- anti_patterns.md (infra-spravne-heading): „Jakmile obojí sdílí jednu sběrnici, kdokoli si na doménovou událost pověsí handler a její tvar se tím stane veřejným API, které už nelze měnit.“
- practical_examples.md (cart-checkout-to-order): „V monolitu handler odebírá doménovou událost přímo. Jakmile se kontext Order osamostatní, potřebuje vlastní integrační DTO…“ Ukázka `PlaceOrderOnCartCheckedOut` importuje `App\Cart\Domain\Event\CartCheckedOut`. Tabulka (tri-projekty-vedle-sebe): „doménová událost mezi kontexty“.
- microservices_and_ddd.md (symfony-monolith-heading): „Doménový event se v jednom BC dispatchne, handler v druhém BC ho přijme, namapuje na **vlastní integration event DTO** a spustí lokální command.“
- Co je správně a proč: Kapitoly 06, 15 a 21 formulují pravidlo bez výjimky pro monolit. Kapitoly 19 a 23 výjimku pro modulární monolit dělají, a to právě tím způsobem, před kterým kap. 21 varuje (sdílená sběrnice). Obě pozice jsou obhajitelné, kniha ale musí mít jednu.
- Návrh sjednocení: Buď (a) do kap. 06 doplnit výslovnou výjimku: „V modulárním monolitu kniha připouští zkratku: sousední kontext doménovou událost odebírá přímo a hned ji překládá na vlastní typ. Tvar události se tím stává kontraktem, proto zkratka končí nejpozději při oddělení kontextu.“ Anebo (b) v kap. 19 a 23 nahradit přímý odběr integrační událostí (`CartCheckedOutIntegrationEvent` s primitivy) a text „doménová událost mezi kontexty“ změnit na „integrační událost mezi kontexty“.

## [K-10] Co smí obsahovat Shared Kernel – závažnost: střední
- glossary.html.twig (term-sdilene-jadro): „dva Bounded Contexty sdílejí podmnožinu doménového modelu – společné entity, hodnotové objekty nebo doménové události.“
- microservices_and_ddd.md (modular-monolith-symfony-heading): „Nepatří sem agregáty ani doménové eventy jednotlivých kontextů; jejich sdílení porušuje definici Bounded Contextu.“
- context_mapping.md (shared-kernel): „Evans […] doporučuje explicitní hranicí vyznačit podmnožinu doménového modelu, na jejímž sdílení se týmy dohodly. […] Kernel zahrnuje i příslušný kód či návrh databáze.“ a „typicky elementární VO: `Money`, `Currency`, `Email`, `UserId`“.
- Co je správně a proč: Evans (2003) obsah Shared Kernelu neomezuje na hodnotové objekty. Tvrzení z kap. 19, že sdílení entit nebo událostí „porušuje definici Bounded Contextu“, je přísnější než zdroj a odporuje glosáři. Doporučení „drž SK u elementárních VO“ je legitimní heuristika, ale ne definice.
- Návrh sjednocení (kap. 19): „Kniha do něj dává jen hodnotové objekty jako `Money` a `Currency`. Evans obsah kernelu neomezuje, sdílené entity nebo události ale zvětšují plochu, na které se musí oba týmy shodnout.“ V glosáři doplnit: „V praxi se drží malý, typicky jen elementární hodnotové objekty.“

## [K-11] Sága: kapitola 14 deklaruje jinou konvenci, než jakou pak definuje – závažnost: střední
- sagas.md (terminologicka-konvence): „Třetí čára vede podle způsobu koordinace: sága jede na událostech a kompenzacích, process manager překládá události na příkazy. Kniha vychází z třetího dělení […] „Sága“ je zastřešující termín pro koordinátor dlouhotrvajícího procesu s perzistentním stavem. „Process Manager“ označuje jeho orchestrovanou podobu…“
- tamtéž, o druhé konvenci: „Richardson naopak ságou nazývá obojí a orchestraci bere jako implementační detail.“
- sagas.md (FAQ): „Sága je v této knize obecný pojem pro dlouhotrvající transakci napříč více službami, rozdělenou na sérii lokálních transakcí propojených kompenzacemi. […] sága může být choreografická i orchestrovaná, Process Manager je vždy orchestrátor.“
- Co je správně a proč: Definice „sága = zastřešující pojem, PM = orchestrovaná podoba“ odpovídá Richardsonově (druhé) konvenci, ne třetí. Uvnitř kapitoly se navíc liší, co sága je: jednou „koordinátor s perzistentním stavem“ (choreografie žádný koordinátor nemá), jindy „transakce rozdělená na lokální transakce“. Glosář (term-saga) i cqrs.md (saga) přebírají verzi „PM = orchestrovaná podoba ságy“, jsou tedy konzistentní s definicí, ne s deklarovanou konvencí.
- Návrh sjednocení: „Kniha se drží Richardsonova pojetí s jedním upřesněním. „Sága“ je zastřešující pojem pro dlouhotrvající proces rozložený na lokální transakce s kompenzacemi, choreografický i orchestrovaný. „Process Manager“ je orchestrační komponenta s perzistentním stavem, která takovou ságu řídí.“ Stejnou definici použít ve FAQ.

## [K-12] Glosář: Messenger handler „implementuje doménovou logiku“ – závažnost: střední
- glossary.html.twig (false friends – Handler): „V Symfony Messenger: třída implementující doménovou logiku spuštěnou message busem.“
- glossary.html.twig (term-aplikacni-sluzba): „Neobsahuje žádnou doménovou logiku – ta patří do entit, hodnotových objektů a doménových služeb.“ Tentýž glosář v mapování: „#[AsMessageHandler] handler → Application Service / Use Case“.
- architectural_styles.md (layered-vrstvy-heading): „**Application Layer** – orchestrace use casů […] Tenké třídy, žádná doménová logika“.
- Co je správně a proč: Handler je v knize aplikační služba (orchestrace), doménová logika patří agregátu. Heslo „Handler“ je v rozporu s heslem „Aplikační služba“ na téže stránce.
- Návrh sjednocení: „V Symfony Messenger: třída s `#[AsMessageHandler]`, kterou message bus zavolá pro danou zprávu. Orchestruje use case, doménovou logiku deleguje na agregát.“

## [K-13] Autorizace jako generická subdoména – závažnost: střední
- glossary.html.twig (term-genericka-subdomena): „Příklady: autentizace a autorizace (Keycloak, Auth0), fakturace (Stripe)…“
- authorization_in_ddd.md (ctyri-vrstvy): „Typologicky jde o generickou subdoménu: kupuje se (Keycloak, Auth0, OIDC provider)… Autorizační *rozhodnutí* přitom zůstává v konzumujícím kontextu, protože závisí na jeho entitách a stavech.“
- Co je správně a proč: Kapitola 11 označuje za Generic identitu a správu rolí, ne autorizační pravidla (Voter a invarianty v konzumujícím kontextu). Glosář to slučuje. Kap. 02 i 24 mluví jen o autentizaci („UserManagement | Generic | registrace, přihlášení, reset hesla“).
- Návrh sjednocení: „Příklady: autentizace a správa identit (Keycloak, Auth0), platby (Stripe), odesílání e-mailů… Autorizační pravidla nad vlastními entitami ale zůstávají v konzumujícím kontextu.“ („fakturace (Stripe)“ zároveň opravit na „platby“, protože Stripe je v kap. 02 příklad plateb: „Payments (Stripe)“.)

## [K-14] Factory a invariant vzniku objednávky – závažnost: střední
- glossary.html.twig (term-tovarna): „Neměla by obsahovat doménovou logiku – pouze koordinaci konstrukce.“
- lesser_known_patterns.md (fac-definice): „Evans […] doporučuje přesunout odpovědnost za vytváření […] zvlášť když vznik vyžaduje pravidla nebo polymorfismus.“ a „Vznik agregátu vyžaduje validaci […] (např. *„nový Order musí mít alespoň 1 položku, jinak agregát neexistuje“*).“
- glossary.html.twig (term-invariant): „„Objednávka musí mít alespoň jednu položku, aby mohla být potvrzena.““
- outbox_pattern.md (order-aggregate-heading): „// Druhá továrna vedle kanonického Order::place(OrderId, CustomerId).“ Kanonická továrna tedy vytváří objednávku bez položek.
- Co je správně a proč: Evans (2003, kap. 6) přiřazuje továrně odpovědnost za invarianty při vzniku, tedy doménovou logiku. Glosář tvrdí opak kapitoly 08. Invariant o položkách má v knize dvě podoby: „při vzniku“ (kap. 08) a „při potvrzení“ (glosář, kanonické `Order::place()`).
- Návrh sjednocení: Glosář: „Továrna odpovídá za to, že vzniklý objekt splňuje invarianty. Nemá ale obsahovat logiku, která se týká pozdějšího chování objektu.“ Příklad v kap. 08 změnit tak, aby nekolidoval s kanonickým `Order::place()`, například: „*„objednávka založená z košíku (`placeWithItems`) musí mít alespoň jednu položku“*“.

## [K-15] Kdo generuje identitu: klient, nebo agregát? – závažnost: nízká až střední
- cqrs.md (ec-priklad-heading, komentář v kódu): „PlaceOrderHandler vrací OrderId – identitu generuje agregát, ne kontroler. Kdyby ji určoval klient, obešel by tím továrnu.“
- practical_examples.md (create-post-handler): „Handler nic nevrací a identifikátor příspěvku přichází v commandu. Kontroler ho vygeneruje přes `PostId::generate()` ještě před dispatchem.“
- case_study.md (create-project-handler-heading): „Jakmile by šel na asynchronní transport, muselo by ID vzniknout u volajícího a putovat uvnitř příkazu.“
- event_storming.md (dl-mapping): návrat hodnoty „pouze u synchronně zpracovaných zpráv“.
- Co je správně a proč: Obě varianty jsou legitimní a kap. 23 a 24 je popisují neutrálně. Kap. 12 variantu „ID od klienta“ odsuzuje jako obcházení továrny, přestože ji kap. 23 používá a kap. 24 ji předepisuje pro async.
- Návrh sjednocení (komentář v cqrs.md): „// PlaceOrderHandler vrací OrderId – funguje jen na synchronní sběrnici. Pro async transport musí ID vzniknout u volajícího a přijít v commandu (viz Praktické příklady).“

## [K-16] Umístění controlleru a Voteru do vrstev – závažnost: nízká
- glossary.html.twig (false friends – Controller): „patří do `App\<BC>\Infrastructure\Http\`. Nikoli do Application a už vůbec ne do Domain.“
- cqrs.md (ec-priklad-heading): `filename="src/Ordering/Application/Controller/PlaceOrderController.php"`, `namespace App\Ordering\Application\Controller;`
- authorization_in_ddd.md (úvod): „od HTTP firewallu přes Symfony Voter v aplikační vrstvě až po doménové invarianty“. Kód téže kapitoly: `src/Ordering/Infrastructure/Security/OrderVoter.php`. Glosář: „Voter → `App\<BC>\Infrastructure\Security\`“.
- Co je správně a proč: Konvence knihy (architectural_styles.md: „controllery řadí do infrastruktury“) staví controller i Voter do Infrastructure. Z aplikační vrstvy se Voter jen volá.
- Návrh sjednocení: V cqrs.md přesunout na `src/Ordering/Infrastructure/Http/PlaceOrderController.php`. V úvodu kap. 11: „…přes Symfony Voter, který aplikační vrstva volá z handleru, až po doménové invarianty…“.

## [K-17] Outbox: titulek slibuje doménové eventy, obsah posílá integrační – závažnost: nízká až střední
- outbox_pattern.md (frontmatter title): „Outbox Pattern – spolehlivé publikování doménových eventů“; úvod: „když agregát po commitu publikuje doménovou událost, **spolehlivě dorazí do message brokeru**“.
- outbox_pattern.md (domain-event-heading): „Není to táž třída jako doménová `OrderPlaced` […] Do outboxu jde **integrační** událost“; tabulka sloupců: „`message_type` | FQCN integrační události“.
- Co je správně a proč: Kapitola sama (a K-1) rozlišuje doménovou a integrační událost. Titulek a úvod používají opačný pojem a navíc hovorové „eventů“.
- Návrh sjednocení: title „Outbox Pattern – spolehlivé publikování událostí“ (bez přívlastku), v úvodu „…publikuje událost ven z kontextu…“.

## [K-18] „Exactly-once efekt“ i pro e-mail a platbu – nepravda – závažnost: střední
- outbox_pattern.md (exactly-once-effect-heading): „Outbox s Inboxem poskytují *exactly-once efekt na straně subscribera*. Zpráva může do brokera dorazit a opustit ho víckrát, ale vedlejší efekt (úprava read modelu, odeslání e-mailu, strhnutí platby) proběhne *právě jednou*.“
- sagas.md (nevratne-akce-heading) a ddd_pain_points.md (b3-idempotence) pracují s tím, že externí efekty idempotentní nejsou („Výsledkem může být dvojitá platba […] nebo zdvojený email.“).
- Co je správně a proč: Inbox zaručí právě jeden efekt jen tam, kde zápis efektu a zápis do inbox tabulky commitnou v téže DB transakci, tedy u read modelu. Odeslání e-mailu nebo volání platební brány v transakci není. Když proces spadne po odeslání a před commitem, efekt se zopakuje. Pomůže jen idempotence na straně příjemce (Idempotency-Key u Stripe), kterou kapitola zmiňuje až u HTTP. Helland (2007) říká totéž: exactly-once vzniká jen v rámci jedné transakční entity.
- Návrh sjednocení: „…ale vedlejší efekt v téže databázi (úprava read modelu) proběhne *právě jednou*. U externích efektů (e-mail, platba) Inbox duplicitu jen zmenší. Právě jednou je zajistí až idempotence příjemce, typicky `Idempotency-Key` u platební brány.“

## [K-19] Meyer, OOSC: rok a kapitola v glosáři – závažnost: nízká
- glossary.html.twig (term-cqs): „Zdroj: Bertrand Meyer, *Object-Oriented Software Construction* (1988), kap. 23“
- aggregate_design.md (further-reading): „Bertrand Meyer, *Object-Oriented Software Construction*, 2. vydání (Prentice Hall, 1997)“
- Co je správně a proč: CQS je v obou vydáních. Kapitola 23 („Principles of class design“, oddíl o vedlejších efektech funkcí) ale odpovídá číslování 2. vydání (1997), ne 1. vydání (1988). Drobnost vedle: glosář cituje „Evans & Fowler, *Specification* (whitepaper, 1997)“, zatímco kap. 08 správně uvádí „*Specifications*“.
- Návrh sjednocení: „Bertrand Meyer, *Object-Oriented Software Construction*, 2. vydání (1997), kap. 23; princip poprvé v 1. vydání (1988)“; „Evans & Fowler, *Specifications* (1997)“.

## [K-20] Kdy snapshotovat: tři různá čísla – závažnost: nízká
- event_sourcing.md (snapshotting): „práh závisí na doméně, typicky se pohybuje od stovek po tisíce událostí.“
- event_sourcing.md (snapshot-php-heading): `SNAPSHOT_INTERVAL = 50; // snapshot každých 50 událostí`
- performance_aspects.md (snapshotting-prehled-heading): „Pro agregát s 100 eventy je to okamžité; pro 1000 eventů to začíná být znát […] Hodnoty kolem 50 až 100 jsou rozšířená pracovní heuristika“.
- Co je správně a proč: Kap. 16 sama říká, že 100 událostí je „okamžité“, a přesto doporučuje snapshot po 50–100. Kap. 13 říká stovky až tisíce, ale v kódu má 50.
- Návrh sjednocení: V obou kapitolách: „Snapshot se zavádí, až když replay měřitelně zpomalí; to bývá u stovek až tisíců událostí. Interval (v ukázce 50) je laditelný parametr, ne doporučení.“ V kap. 16 větu „Hodnoty kolem 50 až 100…“ nahradit touto formulací.

## [K-21] „Pravidlo“, nebo „vodítko“: jeden agregát na transakci – závažnost: nízká
- aggregate_design.md: nadpis „## 07.02 Čtyři pravidla podle Vaughna Vernona“, hned pod ním „Vernon je nenazývá pravidly, ale *rules of thumb*, tedy vodítky.“ Nadpis 07.05 „Pravidlo „jeden agregát na transakci“ je jedno z nejpřísnějších v DDD“ vs. „### Kdy se vodítko poruší“.
- glossary.html.twig (term-agregat): „v jedné transakci se smí modifikovat maximálně jeden agregát.“
- ddd_pain_points.md (a1-transakce): „DDD říká, že jedna transakce smí měnit nejvýše jeden agregát.“
- Co je správně a proč: Vernon (2011, Part II) mluví o rules of thumb a vyjmenovává důvody k porušení. Kniha to jednou přiznává a jinde formuluje absolutně.
- Návrh sjednocení: Nadpis 07.02 „Čtyři vodítka podle Vaughna Vernona“. V glosáři: „Výchozí vodítko: jedna transakce mění jeden agregát; výjimky rozebírá kapitola Návrh agregátu.“ V kap. 20: „Vernonovo vodítko říká, že…“.

## [K-22] Glosář používá české termíny, které kapitoly nepoužívají – závažnost: střední (terminologie)
- glossary.html.twig: hesla „Konformista“ (5×), „Anti-korupční vrstva“ (6×), „Otevřený hostitelský servis“, „Zveřejněný jazyk“, „Zákazník–Dodavatel“, „Sdílené jádro“, „Klíčová doména“, „Všudypřítomný jazyk“, „Čtecí model“, „Snímek“.
- Kapitoly: „Conformist“ 41×, „Anti-Corruption Layer“ 49×, „Open Host Service“ 22×, „Published Language“ 25×, „Customer/Supplier“ 26×, „Shared Kernel“, „Core Domain“ 43×, „Ubiquitous Language“ 92× (Všudypřítomný jazyk 1×), „read model“ 162× (čtecí model mimo glosář 1×). Termíny „Konformista“, „Anti-korupční“, „Otevřený hostitelský“, „Zveřejněný jazyk“ a „Klíčová doména“ se v kapitolách nevyskytují ani jednou.
- Co je správně a proč: Převládající úzus knihy je anglický termín (u taktických vzorů česky: agregát, hodnotový objekt, repozitář). Glosář jako jediný místo zavádí vlastní překlady, takže čtenář podle termínu z kapitoly heslo nenajde. Totéž u „Separate Ways“, které glosář naopak ponechává anglicky.
- Návrh sjednocení: U strategických vzorů udělat hlavním heslem anglický termín a český uvést v závorce, tedy obráceně než nyní: „Conformist (konformista)“, „Anti-Corruption Layer (ACL)“, „Open Host Service“, „Published Language“, „Customer/Supplier“, „Shared Kernel (sdílené jádro)“, „Core Domain (klíčová doména)“, „Ubiquitous Language (všudypřítomný jazyk)“, „Read model (čtecí model)“, „Snapshot“. „Otevřený hostitelský servis“ vypustit úplně (nejde o ustálený český termín). Do glosáře doplnit hesla Partnership a Big Ball of Mud, protože kap. 01 i 03 mluví o „osmi“ vztazích plus devátém a glosář jich má jen sedm.

## [K-23] Customer/Supplier: tři zápisy – závažnost: nízká
- context_mapping.md 24×, team_topologies.md 2×: „Customer/Supplier“
- what_is_ddd.md, subdomains.md, case_study.md, microservices_and_ddd.md: „Customer-Supplier“
- glossary.html.twig: „Customer–Supplier“ / „Zákazník–Dodavatel“; team_topologies.md (x-as-a-service) navíc „Customer / Supplier“.
- Převládající úzus: „Customer/Supplier“ (kapitola, která vzor definuje).
- Návrh: sjednotit na „Customer/Supplier“ (bez mezer) ve všech souborech.

## [K-24] Bounded Context: velikost písmen a skloňování – závažnost: nízká
- Převládá „Bounded Context“ (279×). Malými písmeny „bounded context“ 58×, hlavně ddd_ai.md (17×) a case_study.md (14×), dále what_is_ddd.md (8×), cheat_sheet (2×: „bounded contexts“).
- Množné číslo: anglické „Bounded Contexts“ v české větě 88× („dva Bounded Contexts“, „mezi Bounded Contexts“) vs. počeštěné „Bounded Contexty“ 20× (event_storming, microservices, glosář), „Bounded Contextů“ 13×, „Bounded Contextech“ 1×. V jedné kapitole se často střídá obojí (microservices_and_ddd: Contexts 4×, Contexty 5×).
- Český ekvivalent „ohraničený kontext“ jen 9× (what_is_ddd, basic_concepts, context_mapping, microservices_and_ddd, team_topologies, ddd_ai, when_not_to_use_ddd), glosář ho má jako hlavní heslo.
- Návrh: „Bounded Context“ vždy s velkými písmeny. Plurál skloňovat česky („Bounded Contexty, Bounded Contextů, v Bounded Contextech“), anglické „Bounded Contexts“ ponechat jen v názvech a citacích. „Ohraničený kontext“ uvést jednou v kap. 01 jako překlad a dál nepoužívat.

## [K-25] Další terminologické dublety – závažnost: nízká
- Doménová událost: „doménová událost“ 122× vs. „doménový event“ 17× (outbox_pattern 8×, event_storming 3×, microservices 3×, aggregate_design, context_mapping, cheat_sheet). Návrh: „doménová událost“; „event“ jen v hovorových složeninách typu „event bus“.
- Služby: „Domain Service“ 65× (lesser_known_patterns 40×, architectural_styles 14×) vs. „doménová služba“ 34×; „Application Service“ 57× vs. „aplikační služba“ 11×. U ostatních taktických vzorů kniha překládá (agregát 906× vs. Aggregate 75×, hodnotový objekt 106× vs. Value Object 42×, repozitář 193×). Návrh: v próze „doménová/aplikační služba“, anglicky jen v nadpisech vzorů.
- Ságy: kapitola se jmenuje „Ságy a Process Managery“, ale cheat_sheet.html.twig má „Sagy a Process Manager“ (2×), case_study.md (trade-off) odkaz „[Sagas a Process Manager]“ a microservices_and_ddd.md v próze „Saga“ („## 19.06 Distribuované transakce – Saga, ne 2PC“, „Saga zpracovává krok po kroku“). Návrh: „sága/ságy“, v odkazech přesný název kapitoly.
- Agregát v cheat sheetu: „Komplexní vznik aggregátu“, „DDD Entity může být uvnitř Aggregátu“ (překlep „aggregát“). Návrh: „agregátu“.
- Event Storming: „Event Storming“ 95× vs. „EventStorming“ 25× (i uvnitř event_storming.md 48:16, subdomains 5:5). Brandolini píše „EventStorming“; kniha ale převážně „Event Storming“ včetně názvu kapitoly. Návrh: „Event Storming“ všude, „EventStorming“ jen v názvech Brandoliniho textů.
- Anti-vzor: „anti-vzor“ 215× vs. „antivzor“ 19× (microservices 7×, team_topologies 7×) vs. „anti-pattern“ 6×. Návrh: „anti-vzor“.
- Modulární monolit: „modular monolith“ 30× (microservices 28×) vs. „modulární monolit“ 9×. Stejně „microservices“ 138× vs. „mikroslužby“ 3×, přičemž practical_examples.md odkazuje „[DDD a mikroslužby](/ddd-a-microservices)“ a kapitola se jmenuje „DDD a microservices“. Návrh: „modulární monolit“; u microservices zvolit jednu formu a odkazy psát přesným názvem kapitoly.
- Context Map: „Context Map“ 45× vs. „kontextová mapa“ 8× (case_study 5×, practical_examples 2×, ddd_pain_points) vs. „mapa kontextů“ (glosář 5×, what_is_ddd). Návrh: „Context Map“.
- Core Domain: „Core Domain“ 43× vs. hybridní „Core Doména/Doméně“ 9× (subdomains, glosář, event_sourcing) vs. „jádrová doména“ (subdomains, anti_patterns) vs. „klíčová doména“ (jen glosář). Návrh: „Core Domain“, česky skloňovat „Core Domain“ nesklonně, nebo jednotně „Core doména“.

## [K-26] Drobné číselné nesoulady v rámci kapitol – závažnost: nízká
- team_topologies.md: „### Scénář B – Scale-up, 20 lidí […] **Doporučení:** 2–3 stream-aligned týmy“ vs. FAQ: „Padesátičlenná firma odpovídá scénáři B (scale-up): typicky 4–6 stream-aligned týmů + 1 mini-Platform team“. Návrh: FAQ „…leží mezi scénáři B a C: typicky 4–6 stream-aligned týmů…“.
- event_storming.md (event-storming-co): „Brandolini s ním počítá jako s **celodenním** formátem pro 20–30 lidí“ vs. (bp-priprava) a FAQ: „Primární zdroje uvádějí pro Big Picture 15–30 lidí, typicky 25–30“. Návrh: sjednotit na „15–30 lidí, typicky 25–30“.
- aggregate_design.md (invariants): „Hot Spot sticky (purpurové/červené)“ vs. event_storming.md (notace): „**Růžová**, natočená do kosočtverce | Hot Spot“. Návrh: „Hot Spot sticky (růžové, natočené)“.
- what_is_ddd.md (strategic-design): „**Shared Kernel** […] ze všech vztahů vyžaduje nejvíc koordinace“ vs. context_mapping.md (partnership): „Kdo váhá, pravděpodobně potřebuje Customer/Supplier nebo Shared Kernel“, kde je Partnership ten nejnáročnější („těsná (společný release)“). Návrh (kap. 01): „…vyžaduje souhlas obou stran s každou změnou sdíleného kódu.“

---
<!-- reports/fix-B.md -->
# Fix-B – opravy v kapitolách 00–10

Soubory: preface, what_is_ddd, subdomains, context_mapping, event_storming, team_topologies,
basic_concepts, aggregate_design, lesser_known_patterns, architectural_styles, implementation_in_symfony.
Kotvy ani URL se neměnily. `modified: 2026-09-23` bylo ve všech souborech už nastavené.

## consistency-code.md

- **R-1** basic_concepts: komentář u flush → „zápis SQL; commit řídí doctrine_transaction middleware“; odstavec pod ukázkou přepsán podle pravidla z kap. 15 (synchronní posluchači v procesu OK, ven přes Outbox s odkazem na #anti-publish-before-commit-heading); navazující odstavec o pořadí upřesněn na variantu bez middlewaru, Bogard už není „druhý tábor“. implementation_in_symfony: věta „publikuje až po commitu“ → „až po flush()“; callout #event-dispatch-heading přepsán (dispatch před commitem i po commitu, oba problémy jen pro efekty mimo proces); callout #double-transaction-heading podle návrhu; komentář „Výjimečně explicitní flush“ → důvod (unique constraint), text v #register-race-heading bez „odchylky od pravidla“. event_storming: odrážka „Kdy se události publikují“ sladěna s pravidlem.
- **R-2** implementation_in_symfony: Ordering ve stromu má Domain/Application(Command/PlaceOrder, Handler/PlaceOrderHandler, Query)/Infrastructure(Repository, Http); `SharedKernel/Domain/ValueObject/Id.php` smazán; pod strom věta o dvou organizacích (UserManagement slice × Ordering vrstvy, odkaz /architektonicke-styly#vertical-slice) a o ID bez sdíleného předka. basic_concepts: výřez přejmenován na `src/Ordering/Application/Handler/PlaceOrderHandler.php`.
- **R-3** basic_concepts: `new OrderConfirmed($this->id, $this->customerId, new \DateTimeImmutable())`. aggregate_design: `OrderItemAdded` už `occurredAt` má (plní konstruktor), volání se třemi argumenty sedí – beze změny.
- **R-4** implementation_in_symfony: pod `UserRegistered` doplněno, proč nese primitivy (odebírá ji i kontext Identity → tvar integrační události).
- **R-8** architectural_styles: transport `async_events`, routing jen `OrderPlacedIntegrationEvent` s komentářem, název bloku „(výřez – plná konfigurace v kapitole o CQRS)“.
- **R-9** basic_concepts: `cancel(string $reason, \DateTimeImmutable $when)`, odmítá jen Shipped/Delivered. event_storming: totéž + komentář „náčrt z workshopu bez zámku ságy“, `OrderCancelled` dostává `$when`, test volá `cancel('customer request', new \DateTimeImmutable())`.
- **R-13** architectural_styles: port `get(OrderId): Order` s `@throws OrderNotFoundException`, adaptér `?? throw OrderNotFoundException::withId($id)`, komentář, že `Port/` = hexagonální jméno pro `Repository/`.
- **R-15** context_mapping: `#[AsMessageHandler(bus: 'event.bus')]` + worker `messenger:consume from_catalog --bus=event.bus` (cizí zpráva nemá BusNameStamp). event_storming: `PlaceOrderHandler` → `bus: 'command.bus'`.
- **R-18** lesser_known_patterns: u `placedAt` komentář, že zde jde o čas vzniku, v kanonickém modelu o čas potvrzení.
- **R-19** basic_concepts i implementation_in_symfony (#enum-usage-heading): komentář u `status()` odkazující na `public private(set)` z kap. 07. event_storming: `new InvalidOrderStateTransitionException('…')` → pojmenované továrny `notAllowedInState()`/`cannotTransition()`. aggregate_design: komentář „Čas přebírá parametr“ už stojí nad `cancel()` – beze změny. Navíc basic_concepts: `$item->productId()` → `$item->productId` (OrderItem má public readonly vlastnost).
- **R-20** preface: části sladěny s huby (Část 2 = 6–8, Část 3 = Architektura a implementace 9–11, Část 4 = Pokročilé vzory a výkon 12–16, Část 5 = Testování 17), úvodní věta vyjmenuje huby; kotvy #cast-1…8 zachovány. what_is_ddd #jak-cist: přehled přepsán na tytéž hranice.
- R-5, R-6, R-7, R-10, R-11, R-12, R-14, R-16, R-17 – přeskočeno, míří jen na cizí kapitoly.

## consistency-terms.md

- **K-3** aggregate_design 07.04: odrážka Konkurence přepsána – verze kořene se změnou potomka sama nezvedne, s vědomým bumpem velký agregát = víc konfliktů, bez něj tiché porušení invariantu (odkazy #doctrine-limits a /anti-vzory#agregat-problemy-heading).
- **K-4** implementation_in_symfony: závorka u `MoneyTransferService` nahrazena (služba mění oba účty, vědomé porušení vodítka, jinak sága, odkaz /mene-zname-vzory#ds-priklad); zdůvodnění `RecordPaymentHandler` přepsáno – žádná Vernonova výjimka, Khononovův signál špatné hranice, drženo kvůli jednoduchosti ukázky. lesser_known_patterns #ds-priklad rozpor už přiznává – beze změny.
- **K-14** lesser_known_patterns #fac-definice: příklad invariantu → „objednávka založená rovnou s položkami (`placePhysical()`) musí mít alespoň jednu“.
- **K-21** aggregate_design: nadpis 07.02 „Čtyři vodítka…“, „páté vodítko“, 07.05 „Vodítko „jeden agregát…““; basic_concepts „Vodítko „jeden agregát na transakci““; kap. 10 „odchylka od vodítka“.
- **K-23** Customer/Supplier sjednoceno v subdomains, what_is_ddd, team_topologies a v nadpisu 03.05 context_mapping.
- **K-24** „bounded context“ → „Bounded Context“ v próze what_is_ddd a architectural_styles; „ohraničený kontext“ v basic_concepts (tělo 06.01, výčet Vernonových cest) a context_mapping nahrazen, v kap. 01 ponechán jako překlad. Množné „Bounded Contexts“ ponecháno – převládající úzus (88×). Nadpis 06.01 „Ohraničené kontexty (Bounded Contexts)“ ponechán kvůli jednotnému vzoru nadpisů kap. 06.
- **K-25** „doménový event“ → „doménová událost“ (aggregate_design ×2, context_mapping ×3, event_storming ×2 vč. nadpisu 04.12.2); odrážka „Doménové eventy přes outbox“ v 07.08 navíc sladěna s tím, že do outboxu jde integrační událost. „EventStorming“ → „Event Storming“ v próze event_storming a subdomains (ponecháno ve jménech Brandoliniho textů a v „jméno EventStorming“). „Core Doména/Domény/Doméně“ → „Core Domain“ v subdomains. „antivzor“ v team_topologies je jen v kotvách – nezměněno. Domain/Application Service → česky: přeskočeno, převládající úzus podle grepu je anglický (65×), plošný přepis ~50 výskytů v kap. 08 a 09 by byl velký zásah bez jasného úzu.
- **K-26** team_topologies FAQ: 50 lidí „mezi scénáři B a C“; event_storming: Big Picture „15–30 lidí, typicky 25–30“; aggregate_design: Hot Spot „růžové, natočené“; what_is_ddd: Shared Kernel „každá změna sdíleného kódu vyžaduje souhlas obou“.
- **K-9** přeskočeno pro mé soubory – decisions.md bod 6 řeší přiznáním v kap. 14, 19, 23; kap. 06 pravidlo formuluje správně.
- K-1, K-2, K-5–K-8, K-10–K-13, K-15–K-20, K-22 – glosář, cheat sheet nebo cizí kapitoly; v mých souborech (context_mapping #shared-kernel, architectural_styles) nic v rozporu.

## cross-g.md

- **g1** subdomains `reading_time` 21 → 18 (Chapters.php). Stejný nesoulad opraven i v event_storming (27 → 25) a team_topologies (26 → 22). Předmluva viz R-20.
- **g2** PaymentReceived → PaymentSucceeded v celé kap. 04 (pravidlo o minulém čase, pivotní události, sekvence Process Modellingu, seznam událostí Payment BC). Odkazové texty „Migrace z CRUD“ / „Migrace z CRUD do DDD“ → „Migrace z CRUD na DDD“ v preface, context_mapping, architectural_styles (FAQ), implementation_in_symfony.
- **g3/g7** (Doctrine v doméně, decisions bod 10) lesser_known_patterns #mod-phparkitect: pravidlo 2 zakazuje běhovou část Doctrine ORM, povoluje `Doctrine\ORM\Mapping` a `doctrine/collections` (odkaz #mapping-volba-heading); pravidlo 1 „domain events“ → „integrační události“; FAQ příklad „nesmí znát Doctrine ORM“ → „nesmí volat EntityManager“. architectural_styles tvrdí čistou doménu jen pro Hexagonal s výslovnou poznámkou – beze změny.
- **g4** strom kap. 10 (CreateOrder → PlaceOrder) viz R-2; `findByCustomer(string)` → `CustomerId` v portu i adaptéru; odkaz na Anti-vzory v #error-handling ověřen (aktivační model v anti_patterns.md je) – ponechán.
- g5, g6, g8, g9 – míří na cizí kapitoly.

## Kontroly

- `php scripts/lint-php-snippets.php` (11 souborů): 111 bloků, 0 chyb.
- `php scripts/check_anchors.php`: OK. `php scripts/check_faq_yaml.php`: OK.
- `php scripts/check_tonality.php` pro každý z 11 souborů: 0 nálezů.

---
<!-- reports/fix-C.md -->
# Opravy agenta C (kap. 11–24, ddd_ai, Chapters.php)

## consistency-code.md
- R-1: outbox_pattern (naivní handler) komentář „commit až po návratu handleru"; odrážka „Dispatch do brokera prošel…" říká, že pod `doctrine_transaction` jde o výchozí pořadí. case_study: komentář u flush „zápis SQL; commit řídí middleware", odstavec o pořadí přepsán (před flushem / uvnitř transakce / async zpráva odejde před commitem), warn callout a trade-off 1 opraveny (bez outboxu nedostupný transport shodí i zápis projektu). authorization/sagas: bez změny (explicitní flush řeší kap. 10).
- R-2: migration_from_crud – `RegisterUser`/`RegisterUserHandler` přesunuty do `App\UserManagement\Registration\Command` (pořadí polí jako v kap. 10), controller `use` upraven. performance_aspects – `GetUserProfileHandler` s cache → `App\UserManagement\Profile\Query` („verze s cache"). testing_ddd – namespace/filename testu → `Registration\Command`.
- R-3: ddd_pain_points C2 – `confirm()`/`ship(ShipmentId)` s kanonickými událostmi a `cannotTransition()`, komentář „zkrácená podoba". anti_patterns 21.06 – `OrderPlaced` v kanonickém tvaru (OrderId, CustomerId, volitelný occurredAt) + recordedAt, bez Money/itemCount; komentář, že `new OrderPlaced($id, $customerId)` funguje.
- R-5: outbox_pattern – `placeWithItems()` začíná `self::place()`, pozdní `record(OrderPlaced)` smazán, konstruktor nastavuje `status = Draft` (+ deklarace `$status`, import OrderStatus místo OrderPlaced), komentář v handleru s novým pořadím. testing_ddd už sedí.
- R-6: migration_from_crud User – `hashedPassword` + getter `hashedPassword()`, `createdAt` místo `registeredAt`; `nextIdentity()` z repozitáře odstraněn, handler `UserId::generate()`; `UserRegistrationPolicy` vypuštěna, handler chytá `UniqueConstraintViolationException` → `DuplicateEmailException`, próza odkazuje na `/implementace-v-symfony#register-race-heading`; zakázané domény `mailinator.com`, `guerrillamail.com`. ODCHYLKA od rozhodnutí 5: tabulka zůstala `um_users` (kapitola ji zdůvodňuje souběhem s legacy `users`, které Strangler Fig potřebuje), doplněn komentář, že se po odstavení legacy přejmenuje na `users`. anti_patterns 21.02 (výřez téhož modelu) sjednocen (hashedPassword, createdAt, getter). ddd_pain_points: smazána věta „Příklad s nextIdentity() je v kapitole Migrace".
- R-7: sagas – odstavec za choreografickými handlery přiznává zkratku (přímá konzumace cizích doménových událostí, v produkci integrační přes outbox). microservices – komentář v routingu: serializer je překladová vrstva, na drát jde integrační payload; highlights bloku opraveny na routing.
- R-10: performance_aspects – keyset paging nad `order_dashboard` se sloupci z kap. 12; `OrderSummaryRow` → tabulka `order_feed`, komentář „v duchu kapitoly CQRS".
- R-11: sagas – text říká, že kanonické `cancel()/markPaid()/ship()` idempotentní jsou; výpis uveden jako připomenutí.
- R-12: authorization – odkaz na `/anti-vzory#logika-v-infrastrukture` jako „opačný směr téhož porušení".
- R-14: outbox_pattern – komentář routingu „Handler dispatchne integrační tvar", pod YAML výřez `$this->eventBus->dispatch($integrationEvent)`; doplněno, že doctrine transport sám do brokera nepřeposílá.
- R-15: `bus:` doplněn v event_sourcing (6×), performance_aspects (4×), migration_from_crud, case_study (9× + zmínka v textu). Přeskočeno: microservices `OrderPlacedReceivedHandler` (billing-svc má jedinou sběrnici).
- R-16: practical_examples – listener dispatchne `PlaceOrder` na `command.bus` (transakce, potvrzení, outbox řeší handler z kap. 15), komentář proč.
- R-17: when_not_to_use_ddd – „Zkrácená podoba…", třída bez `final`, komentář o zámku ságy a idempotenci.
- R-18: anti_patterns 21.04 – `placedAt` → `createdAt` s komentářem.
- R-19: migration Recept 8 – enum Draft/Confirmed/…/Cancelled, legacy `'PLACED'` → `Confirmed` přes ACL; ddd_pain_points pojmenované továrny výjimek; event_sourcing – odstavec přiznává `Identity` a sdílené `App\Infrastructure\…`.
- R-4, R-8, R-9, R-13, R-20: mimo mé soubory (R-9 v authorization už kanonické). Chapters.php huby beze změny.

## consistency-terms.md
- K-3: performance_aspects FAQ a case_study trade-off (Doctrine verzi kořene při změně potomka nezvedne → tiché porušení, odkaz na anti-vzory). anti_patterns už správně.
- K-7: cqrs 12.02 – úložiště volitelné, odkaz na tři mýty.
- K-9: practical_examples a microservices (monolit: překlad na integrační událost, jinak porušení pravidla 3 phparkitect) – viz i R-7.
- K-10: microservices – věta o Shared Kernelu podle návrhu.
- K-11: sagas – konvence podle Richardsona s upřesněním (PM = orchestrační komponenta), FAQ sjednoceno.
- K-15: cqrs – komentář v kontroleru (vrácení ID jen synchronně).
- K-16: cqrs – `PlaceOrderController` → `src/Ordering/Infrastructure/Http/` (+ dovětek kvůli check_duplicate_listings); authorization úvod – Voter „volá aplikační vrstva z handleru".
- K-17: outbox – title, schema_headline, meta_description, úvod bez „doménových eventů"; Chapters.php `d` → „událostí".
- K-18: outbox 15.x exactly-once callout – právě jednou jen efekt v téže DB, externí efekty vyžadují idempotenci příjemce (Idempotency-Key, odkaz na #idempotency-api).
- K-19: Meyer v mých souborech bez roku/kapitoly – nic k opravě.
- K-20: event_sourcing (interval 50 = laditelný parametr) a performance_aspects (věta o 50–100 nahrazena).
- K-21: ddd_pain_points A1 „Vernonovo vodítko".
- K-23: case_study Customer/Supplier (3×).
- K-24: „bounded context" → „Bounded Context" v próze všech mých souborů (meta_keywords ponechány).
- K-25: „doménový event" → „doménová událost" (outbox, microservices); „Saga" → „sága" (microservices, nadpisy bez změny kotev); „kontextová mapa" → „Context Map" (case_study, practical_examples, ddd_pain_points); „Core Doméně" → „Core Domain" (ES); odkazy „[Ságy a Process Managery]", „[DDD a microservices]". Domain Service a modular monolith ponechány (převládá anglický úzus).
- K-1, K-2, K-4, K-5, K-6, K-8, K-12, K-13, K-14, K-22, K-26: glosář/cheat sheet/cizí kapitoly – přeskočeno.

## cross-g.md
- g1 reading time: pro mé kapitoly jsem sjednotil Chapters.php `time` na hodnoty z frontmatteru (authorization 34, sagas 43, performance 36, testing 40, migration 33, microservices 34, anti_patterns 38, practical 16, case_study 55). Důvod: frontmatter byl zvednut vědomě při revizích (např. authorization 25→34), katalog zůstal pozadu. Pozor: jiný směr než u subdomains (tam agent snížil frontmatter).
- g2: `PaymentSettled` v mých souborech už není; odkazové texty sjednoceny na „Migrace z CRUD na DDD" (10×), Chapters.php `t` kap. 18 → „Migrace z CRUD na DDD".
- g3/g7 Doctrine v doméně: testing Deptrac a microservices phparkitect Doctrine nezakazují; anti_patterns 21.07 pravidlo upřesněno (runtime Doctrine zakázán, Mapping/Collections povoleno); migration 18.07 „nezná EntityManager ani SQL". Navíc Recept 3 `OrderId` s `string $value`.
- g4: aktivační model v anti_patterns 21.02 opravdu je → odkaz v kap. 10 platí.
- g5: cqrs – věta, že dashboard má vlastní popisky (`placed` pro Confirmed); migration Recept 8 viz R-19.
- g7: performance – listener na `#[AsMessageHandler(bus: 'event.bus')]`, věta o `UserEmailChanged` jako rozšíření; nadpis „Časově řazené identifikátory: UUID v7 a ULID" (nadpis neměl kotvu). DbalInboxRepository nedotčen.
- g8: ddd_pain_points B1 note a outbox doctrine-transport – oboje říká, že transport do brokera sám nepřeposílá.
- g9: anti_patterns 21.06/21.04/21.09 (komentář u Insurance Money); practical_examples próza/tabulka/FAQ „Ordering" (titulek diagramu ponechán kvůli SVG). `PaymentReceived` → `PaymentSucceeded` v ES (65) a anti_patterns FAQ.

## Kontroly
lint-php-snippets (244 bloků, 0 chyb), check_anchors OK, check_faq_yaml OK, check_tonality bez nálezů ve všech 15 souborech, check_use_statements/messenger_routing/named_arguments/property_access/static_calls OK, check_duplicate_listings OK (po doplnění dovětků), `php -l src/Catalog/Chapters.php` OK, cache:clear OK.

---
<!-- reports/fix-templates.md -->
# Opravy šablon glosáře a cheat sheetu (2026-09-24)

Upraveny pouze `templates/ddd/glossary.html.twig` a `templates/ddd/cheat_sheet.html.twig`. Kapitoly ani `src/` nejsou změněné. Kotvy `id="term-…"`, `ff-…` a id sekcí zůstaly beze změny.

## Glosář – věcné nálezy
- K-1: heslo Doménová událost přepsáno podle kap. 06. Události propojují agregáty uvnitř kontextu, hranici kontextu překračuje integrační událost a množství dat je rozhodnutí (uvnitř kontextu stačí ID). Doplněno: bez přípony „Event“, nahrávání mimo `__construct`.
- Nové heslo `term-integracni-udalost`: Integrační událost (Integration Event), odkazy na /zakladni-koncepty#domain-events a /outbox-pattern#domain-event-heading.
- K-2: Bounded Context je logická hranice, modul je jeho promítnutí do kódu, nasazení je samostatné rozhodnutí, „BC = microservice“ je zkratka.
- K-6 a K-7: CQRS už není „rozšíření CQS“ (Young to sám označuje za nepřesné) a oddělené úložiště je volitelné. Upraveno i heslo CQS.
- K-19: Meyer, OOSC, 2. vydání (1997), kap. 23, princip poprvé v 1. vydání (1988). Evans & Fowler *Specifications* (1997), opravena i chronologie vzniku vzoru.
- K-8: kompenzace se spouštějí v opačném pořadí. Komutativní operace jsou popsané jako Richardsonovo protiopatření proti izolačním anomáliím, odkaz na #izolace-sag.
- K-10: Shared Kernel – Evans obsah neomezuje, v praxi elementární VO. Imperativ „Používejte“ nahrazen oznamovací větou.
- K-12: false friend Handler – orchestruje use case a doménovou logiku deleguje. Totéž je v mapovací tabulce.
- K-13: Generic Subdomain – „autentizace a správa identit“, „platby (Stripe)“. Autorizační pravidla zůstávají v konzumujícím kontextu.
- K-14: Továrna odpovídá za invarianty při vzniku, logiku pozdějšího chování nenese.
- K-21: agregát – „výchozí vodítko: jedna transakce mění jeden agregát“ s odkazem na výjimky.
- K-11: sága je definovaná jako lokální transakce s kompenzacemi, Process Manager jako orchestrační komponenta s perzistentním stavem. Odkaz na #terminologicka-konvence.
- K-3: z hesla Agregát vypuštěno tvrzení o konfliktech. K optimistickému zamykání doplněna poznámka o `#[ORM\Version]` a potomcích s odkazem na /anti-vzory#agregat-problemy-heading.
- Doménová výjimka: příklady nahrazeny kanonickými (`InvalidOrderStateTransitionException`, `DuplicateEmailException`, `EmptyOrderException`), `\DomainException` je označená jako zkratka.
- Deptrac: odkaz vede na github.com/deptrac/deptrac jako v kap. 09, název sjednocen na PHPArkitect.

## Glosář – terminologie (K-22, K-23, K-25)
- Hlavní heslo je nově anglický termín a český ekvivalent stojí v závorce: Core Domain (jádrová doména), Supporting/Generic Subdomain, Ubiquitous Language, Bounded Context, Context Map (kontextová mapa), Shared Kernel, Customer/Supplier, Conformist, Anti-Corruption Layer (ACL), Open Host Service (OHS), Published Language (PL), Read model (čtecí model), Snapshot (snímek), Vertical Slice Architecture, Test double.
- Vypuštěny výrazy „Otevřený hostitelský servis“, „Zveřejněný jazyk“, „Klíčová doména“ a „Zdrojování událostí“.
- Nová hesla `term-partnership` a `term-big-ball-of-mud` s atribucí podle CLAUDE.md (Evans, *DDD Reference* 2015; Foote & Yoder 1997).
- Odkazy „Viz také“ používají nové názvy hesel a nově míří na konkrétní kotvy kapitol. Všechny kotvy jsou ověřené.
- Upravený formát `<dt>` dál parsuje `SearchIndexBuilder` (66 hesel).

## Cheat sheet
- K-5: čísla a názvy kapitol v průchodech knihou se generují z `ddd_chapters()` (katalog). Doba čtení je součet `time` z katalogu, dřív byly hodiny odhadnuté ručně. Počet kapitol se také generuje.
- Nadpisy a tabulky převedené do češtiny (Rozhodovací strom, Mapování Symfony ↔ DDD, Průchody knihou…). Id sekcí zůstala.
- Odstraněné tykání („Použij“, „rozlišuj“, „kvalifikuj“) a překlepy „aggregát“. „Sagy a Process Manager“ je nově „Ságy a Process Managery“, „bounded contexts“ je nově „Bounded Contexty“.
- Mapování: async transport už není „integration channel“. Přes hranici kontextu jde jen integrační událost přes Outbox. Handler doménové události patří do aplikační vrstvy a `Repository (Symfony)` se změnilo na `Repository (Doctrine)`.
- Vypuštěn duplicitní callout se Zdroji.
- Title, meta description, keywords a JSON-LD headline jsou česky.
- `article_modified_time` je v obou šablonách nastavený na 2026-09-24.

## Ověření
- `lint:twig templates/ddd/`: OK. `check_tonality.php`: 0 nálezů. Skript šablony v `templates/ddd/*.html.twig` opravdu kontroluje, ověřeno testovacím souborem.
- `cache:clear`: OK. `/glosar` a `/cheat-sheet` jsou vykreslené přes kernel se stavem 200 a validním JSON-LD. Servery na portech 8000 a 8001 patří jinému projektu (vrací 404).

## Mimo rozsah (nezměněno)
- `src/Catalog/Chapters.php:63`: popis cheat sheetu je anglicky („Pattern decision tree + … reading paths“).
- `templates/ddd/resources.html.twig:88`: překlep „aggregátů“.

---
<!-- reports/overeni.md -->
# Ověření NEJISTÝCH tvrzení – revize 9/2026

Ověřeno 24. 9. 2026. Kódová tvrzení proti zdrojům Symfony 8.1.7 / 8.0.7 (vendor repa), Doctrine ORM 3.7.2, DBAL 4.4.4, DoctrineBundle 3.3.2 (instalace ve scratchpadu `src8/`) a PHP 8.4.16; literatura a balíčky proti primárním zdrojům (URL u každé položky). Repozitář nebyl editován.

# === g1.md
## [O-1] subdomains.md – typ `uuid` je nutné registrovat ručně (#symfony-implications)
- Stav: NEPRAVDA
- Doslovná citace z aktuálního textu kapitoly: „Jeden detail, na kterém ukázka bez konfigurace spadne: Doctrine typ `uuid` není součástí ORM 3. Dodává ho most na `symfony/uid` a musíte ho zaregistrovat, jinak mapování `#[ORM\Column(type: "uuid")]` skončí výjimkou o neznámém typu.“ Za ní následuje blok `config/packages/doctrine.yaml (výřez: mapping podle kontextů)` s `types: uuid: Symfony\Bridge\Doctrine\Types\UuidType`.
- Zdroj / důkaz: DoctrineBundle 3.3.2, `src/DoctrineBundle.php`: `$container->addCompilerPass(new RegisterUidTypePass());`. Symfony 8.1 `doctrine-bridge/DependencyInjection/CompilerPass/RegisterUidTypePass.php`: pokud existuje `Symfony\Component\Uid\AbstractUid` a typ ještě není definovaný, doplní do `doctrine.dbal.connection_factory.types` `uuid` → `UuidType` i `ulid` → `UlidType`. První část věty platí (typ není v ORM ani v DBAL, dodává ho `symfony/doctrine-bridge`), druhá ne: v Symfony aplikaci s DoctrineBundle a `symfony/uid` se typ registruje sám a ukázka bez YAML nespadne. Popisek bloku („výřez: mapping podle kontextů“) navíc obsahu neodpovídá.
- Nové znění: „Typ `uuid` v `#[ORM\Column(type: "uuid")]` nepochází z Doctrine ORM, ale z mostu `symfony/doctrine-bridge`. DoctrineBundle ho zaregistruje sám, jakmile je nainstalovaný `symfony/uid`, takže konfigurace v `doctrine.yaml` není potřeba.“ YAML blok vypustit. Pokud má zůstat pro čtenáře mimo DoctrineBundle, přejmenovat popisek na „(výřez: ruční registrace typu mimo DoctrineBundle)“.

## [O-2] subdomains.md – Noback: kontext na první úrovni, uvnitř vrstvy
- Stav: PRAVDA
- Doslovná citace z aktuálního textu kapitoly (ř. 278): „Matthias Noback doporučuje právě ji: na první úrovni Bounded Context nebo subdoména, uvnitř vrstvy.“
- Zdroj / důkaz: Noback, *Layers, ports & adapters – Part 2, Layers* (2017), https://matthiasnoback.nl/2017/08/layers-ports-and-adapters-part-2-layers/ : „Directly beneath my `src/` directory I have a directory for every Bounded Context that I distinguish in my application.“ a „Inside each Bounded Context directory I add three directories, one for every layer I'd like to distinguish: Domain, Application, Infrastructure“. Part 3 (https://matthiasnoback.nl/2017/08/layers-ports-and-adapters-part-3-ports-and-adapters/): „I recommend reflecting them in the project's directory/namespace structure as well“ (Infrastructure dál dělí na `<Port>/<Adapter>/`). Poznámka: Noback mluví jen o Bounded Contextu, slovo „subdoména“ v tom kontextu nepoužívá. Pokud má být citace přesná, stačí „na první úrovni Bounded Context, uvnitř vrstvy“ – věcně to ale nemění smysl. Přímý odkaz na zdroj v textu chybí; doplnit lze odkaz na Part 2.

## [O-3] subdomains.md – OAuth 2.1 jako tržní standard (#rozpoznat-core, #custom-auth-warning-heading)
- Stav: NEPRAVDA (OAuth 2.1 není RFC, je to stále pracovní návrh IETF)
- Doslovná citace z aktuálního textu kapitoly:
  - ř. 125: „Příklad: OAuth 2.1 / OpenID Connect pro autentizaci, ISO 8583 pro karetní platby, RFC 5321 pro SMTP.“
  - ř. 172: „*Existuje tržní standard?* (Ano: OAuth 2.1, OpenID Connect, hotové implementace.)“
- Zdroj / důkaz: https://datatracker.ietf.org/doc/draft-ietf-oauth-v2-1/ (API `doc.json`): `draft-ietf-oauth-v2-1`, rev **16** z 3. 9. 2026, stav „Active / I-D Exists / WG Document“, `std_level: null`, platnost do 7. 3. 2027. Do IESG ani do fronty RFC Editoru se ještě nedostal. Standardem je OAuth 2.0 (RFC 6749/6750), aktualizovaný bezpečnostním BCP RFC 9700 (leden 2025, BCP 240), a OpenID Connect Core 1.0 (OIDF Final).
- Nové znění:
  - ř. 125: „Příklad: OAuth 2.0 / OpenID Connect pro autentizaci, ISO 8583 pro karetní platby, RFC 5321 pro SMTP.“
  - ř. 172: „(Ano: OAuth 2.0, OpenID Connect, hotové implementace.)“
  - Pokud má OAuth 2.1 v textu zůstat: „OAuth 2.0 (s konsolidací, kterou připravuje návrh OAuth 2.1) / OpenID Connect“.

## [O-4] subdomains.md – „Pohyb po ose jde jedním směrem“ × „Z Generic do Core“ (#evoluce)
- Stav: PRAVDA (ve Wardleyho modelu se komponenty vyvíjejí jen zleva doprava; rozpor s následující sekcí je jen zdánlivý)
- Doslovná citace z aktuálního textu kapitoly: „Pohyb po ose jde jedním směrem a rovnou předepisuje metodu: co je v genesis, se staví doma; co dorazilo do product, se kupuje; co je commodity, se pronajímá. Tři posuny níže jsou tři různá místa na téže ose.“
- Zdroj / důkaz: Wardleyho evoluční osa genesis → custom-built → product → commodity popisuje jednosměrný vývoj komponenty pod tlakem trhu (nabídky a poptávky). Sekce „Z Generic do Core“ ale popisuje jinou firmu (Stripe), která nad komoditou postavila novou činnost v genesis. Čtenář to přesto může číst jako pohyb proti směru osy, protože klasifikace subdomény je vztažená k firmě, kdežto osa k trhu.
- Doporučené doplnění (nepovinné, za větu o jednom směru): „Posun z Generic do Core osu neobrací. Firma nad komoditou staví novou činnost, která na ose začíná znovu vlevo.“

## [O-5] what_is_ddd.md – DDD Reference „na 52 stranách“
- Stav: PRAVDA
- Doslovná citace (ř. 301): „definice a shrnutí všech vzorů na 52 stranách zdarma; nejlevnější vstup do tématu“
- Zdroj / důkaz: https://www.domainlanguage.com/wp-content/uploads/2016/05/DDD_Reference_2015-03.pdf – `pdfinfo`: 59 stran PDF. Z toho 7 stran úvodu číslovaných římsky (titul, obsah i–iii, Acknowledgements iv, Definitions vi, Pattern Language Overview vii) a vlastní text číslovaný 1–52 (poslední vzor Pluggable Component Framework na s. 52). „52 stran“ tedy odpovídá číslovanému textu. Kdo otevře PDF, uvidí 59 stran; kdyby to mělo vadit, lze psát „na zhruba šedesáti stranách“.

## [O-6] preface.md – DDD in PHP: 2017 a druhé vydání na Leanpubu
- Stav: PRAVDA
- Doslovná citace (ř. 24): „*Domain-Driven Design in PHP* (Buenosvinos, Soronellas, Akbary) vyšlo v roce 2017 a druhé vydání žije dál na Leanpubu.“
- Zdroj / důkaz: Packt vydal knihu v červnu 2017 (ISBN 9781787284944, https://www.packtpub.com/en-us/product/domain-driven-design-in-php-9781787284944). Leanpub https://leanpub.com/ddd-in-php: titul „Domain-Driven Design in PHP – 2n Edition“, oddíl „About the Second Edition“, 100 % hotovo, 401 stran, poslední aktualizace 2026-04-06. Ukázka (http://samples.leanpub.com/ddd-in-php-sample.pdf): „This version was published on 2026-04-06“, „© 2014 – 2026“, podtitul „…with PHP 8.5 examples“. Na Leanpubu kniha vycházela průběžně už od let 2014–2016 (první commit prosinec 2014), takže „vyšlo v roce 2017“ platí pro tištěné vydání u Packtu.
- **Související nález (stejný odstavec, NEPRAVDA pro 2. vydání):** věta „…strategickému designu dává málo prostoru a nepracuje se Symfony 8 ani s Doctrine ORM 3.“ Ukázka 2. vydání výslovně píše „Since the first edition, Doctrine has evolved to version 3.x“ a ukazuje mapování Value Objects atributy `#[ORM\Embeddable]` / `#[ORM\Embedded]`; příklady jsou v PHP 8.5. O Symfony 8 se v ukázce nic nenašlo (zmiňuje Silex a „any modern Symfony application“). Návrh: „První je podrobný v taktických vzorech a hexagonální architektuře, strategickému designu ale dává málo prostoru a se Symfony 8 soustavně nepracuje.“


# === g2.md
## [O-7] context_mapping.md – Separate Ways mezi Identity a Marketingem „v souladu s legislativou“ (#separate-ways)
- Stav: NEPRAVDA
- Doslovná citace z aktuálního textu kapitoly (ř. 946–951):
  „- Marketing si drží *vlastní* mailing list v SendGridu.
  - Při odhlášení v hlavičce mailu (CAN-SPAM compliance) SendGrid zákazníka odhlásí lokálně.
  - Když se zákazník odhlásí v Identity, Marketing pořád může poslat kampaň, ale bude tam legal opt-out v patičce a SendGrid honoruje globální unsubscribe.
  Řešení má své ústupky. Mohou nastat krátká okna, kdy odhlášený zákazník dostane jednu kampaň navíc. Zato je **levné** a **v souladu s legislativou**. A rozhodnutí padlo vědomě.“
- Zdroj / důkaz:
  - GDPR (nařízení 2016/679) čl. 7 odst. 3: subjekt má právo souhlas kdykoli odvolat a odvolání musí být stejně snadné jako udělení. Správcem je firma jako celek, ne Bounded Context. Odvolání podané v Identity tedy váže i Marketing.
  - GDPR čl. 21 odst. 2 a 3: proti zpracování pro účely přímého marketingu lze kdykoli vznést námitku; poté se osobní údaje pro tyto účely již nezpracovávají. Jde o absolutní právo bez balančního testu.
  - Zákon č. 480/2004 Sb. § 7 odst. 2 a 3 (transpozice čl. 13 směrnice 2002/58/ES): elektronická obchodní sdělení jen s předchozím souhlasem, u stávajících zákazníků s možností odmítnutí; odmítnutí je nutné respektovat. § 7 odst. 4 písm. c) vyžaduje platnou adresu pro odhlášení v každé zprávě – odkaz v patičce je tedy povinné minimum, ne náhrada za respektování odhlášení podaného jinde. (https://www.zakonyprolidi.cz/cs/2004-480)
  - Při Separate Ways se Marketing o odhlášení v Identity **nikdy** nedozví. Nejde o „krátké okno“, ale o trvalý stav: zákazník bude dostávat kampaně až do chvíle, kdy se odhlásí znovu v SendGridu. To je porušení čl. 7 odst. 3 / čl. 21 odst. 3 GDPR i § 7 zákona 480/2004 Sb. CAN-SPAM je americký zákon a pro českého čtenáře není měřítkem; navíc i on vyžaduje odhlášení vyřídit do 10 pracovních dnů.
  - Separate Ways je obhajitelné jen tehdy, když marketingový souhlas žije na jediném místě (Marketing / SendGrid) a Identity žádné marketingové odhlášení nenabízí.
- Nové znění (ř. 946–951):
  „- Marketing si drží *vlastní* mailing list v SendGridu. Je to jediné místo, kde se souhlas s obchodními sděleními uděluje i odvolává.
  - Odhlášení přes odkaz v patičce nebo přes hlavičku `List-Unsubscribe` zapíše SendGrid do globálního seznamu odhlášených.
  - Identity o marketingovém souhlasu nic neví. Stránka s preferencemi v zákaznickém účtu jen odkazuje na centrum preferencí v SendGridu.

  Řešení má své ústupky. Zákazník spravuje e-maily jinde než zbytek účtu a Marketing nemůže cílit kampaně podle dat z Identity. Zato je **levné** a odhlášení platí hned, jak požaduje GDPR (čl. 7 odst. 3 a čl. 21 odst. 3) i zákon č. 480/2004 Sb. Kdyby se zákazník mohl odhlásit i v Identity, Separate Ways by nestačilo. Marketing by se o odhlášení nedozvěděl a každá další kampaň by byla porušením zákona, ne ústupkem. A rozhodnutí padlo vědomě.“

## [O-8] context_mapping.md – `generateMonthlyRevenue()` nepočítá tržby ani neohraničí měsíc (#conformist)
- Stav: NEPRAVDA (název metody slibuje víc, než kód dělá)
- Doslovná citace z aktuálního textu kapitoly: `public function generateMonthlyRevenue(int $year, int $month): array` volá `getRecentPayments(new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))` a ta posílá `'created' => ['gte' => $since->getTimestamp()], 'limit' => 100`.
- Zdroj / důkaz: kód v kapitole. Dotaz nemá horní mez (`lt`), takže pro minulý měsíc vrátí i platby z pozdějších měsíců. `limit: 100` bez stránkování vrátí nejvýš prvních 100 plateb. Metoda nic nesčítá, jen mapuje pole. Jako ilustrace Conformistu funguje, ale `revenue` v názvu je zavádějící.
- Nové znění (kód): přejmenovat na `listMonthlyPayments(int $year, int $month): array` a doplnit horní mez a stránkování:
  ```php
  $from = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
  $payments = $this->stripe->paymentIntents->all([
      'created' => ['gte' => $from->getTimestamp(), 'lt' => $from->modify('+1 month')->getTimestamp()],
      'limit'   => 100,
  ])->autoPagingIterator();
  ```
  (případně ponechat kód a přejmenovat jen metodu na `listPaymentsSince()`).

## [O-9] context_mapping.md – `jane-php/open-api` (#published-language)
- Stav: NEPRAVDA (balíček je opuštěný a nepodporuje Symfony 6+)
- Doslovná citace z aktuálního textu kapitoly: „**Schema-first** – zdrojem pravdy je JSON Schema nebo OpenAPI, kód z něj vzniká generováním (např. `jane-php/open-api`).“ (ř. 926)
- Zdroj / důkaz: https://packagist.org/packages/jane-php/open-api.json – `"abandoned": "jane-php/open-api-3"`; poslední vydání v5.3.2 z 27. 2. 2020 s `symfony/serializer: ^3.2 || ^4.0 || ^5.0`; GitHub `janephp/open-api` je archivovaný. Nástupce https://packagist.org/packages/jane-php/open-api-3.json – poslední v7.14.3 (31. 8. 2026), `php: ^8.1`, `symfony/serializer` a `symfony/yaml: ^5.4 || ^6.4 || ^7.0 || ^8.0`, balíček není abandoned. Monorepo `janephp/janephp` je aktivní (push 23. 9. 2026).
- Nové znění: „(např. `jane-php/open-api-3`)“

## [O-10] context_mapping.md – Sunset nesmí předcházet Deprecation (#ohs)
- Stav: PRAVDA (RFC 9745 používá MUST NOT)
- Doslovná citace z aktuálního textu kapitoly: „Časový bod v `Sunset` nesmí předcházet ten v `Deprecation`.“ (ř. 795)
- Zdroj / důkaz: https://www.rfc-editor.org/rfc/rfc9745 (Standards Track, březen 2025), sekce 4 „Sunset“: „The timestamp given in the Sunset HTTP header field MUST NOT be earlier than the one given in the Deprecation header field.“ Ostatní údaje odstavce sedí: Standards Track, březen 2025, formát `@<unix timestamp>` (příklad `Deprecation: @1688169599` je přímo z RFC).

## [O-11] event_storming.md – Evans: UL „nelze odvodit z dokumentů; vzniká pouze v dialogu“
- Stav: NEPRAVDA (přehnané, Evans nic takového netvrdí)
- Doslovná citace (ř. 30): „Eric Evans v *Domain-Driven Design* (2003) píše, že [Ubiquitous Language](/co-je-ddd#ubiquitous-language-v-praxi) nelze odvodit z dokumentů; vzniká pouze v dialogu.“
- Zdroj / důkaz: *DDD Reference* (2015), vzor Ubiquitous Language: „Play with the model as you talk about the system. Describe scenarios out loud…“ a „Within a bounded context, use the same language in diagrams, writing, and especially speech.“ a „Resolve confusion over terms in conversation…“. Evans tedy zdůrazňuje řeč („especially speech“), ale psaný text a diagramy řadí mezi místa, kde se jazyk používá. V knize z roku 2003 kap. 2 („Communication and the Use of Language“) se to rozvíjí v oddílech „Modeling Out Loud“ a „Documents and Diagrams“ / „Written Design Documents“ (dokumenty mají doplňovat kód a řeč). V kap. 1 (knowledge crunching, „Continuous Learning“) Evans mezi zdroje znalostí počítá vedle expertů i uživatele stávajícího systému a čtení odborné literatury o doméně (plný text knihy z roku 2003 nebyl při ověření k dispozici; kap. 1–2 citovány podle obsahu a známých pasáží, rozhodující doklad je *DDD Reference*). Tvrzení „nelze odvodit z dokumentů“ a „pouze v dialogu“ u Evanse není. (Pozn.: ř. 820 „doménové znalosti nejde přečíst, musí se objevit v dialogu“ není připsaná Evansovi, je to autorský soud. Je ostrý, ale jako názor obstojí.)
- Nové znění: „Eric Evans v *Domain-Driven Design* (2003) staví [Ubiquitous Language](/co-je-ddd#ubiquitous-language-v-praxi) na mluvené řeči: model se tříbí tím, že o něm tým mluví nahlas. Dokumenty podle něj řeč a kód jen doplňují.“

## [O-12] team_topologies.md – „10–15 stream-aligned týmů“ při 200+ lidech (#scenar-enterprise)
- Stav: NEPRAVDA (vnitřní rozpor s čísly téže kapitoly)
- Doslovná citace z aktuálního textu kapitoly: „- **10–15 stream-aligned týmů**, každý vlastní 1 BC (případně 2 související supporting BC).“ a o kus níž „I ve dvousetčlenné firmě mají stream-aligned týmy **výrazně převažovat**, orientačně tři čtvrtiny lidí.“
- Zdroj / důkaz: aritmetika z čísel kapitoly. 75 % z 200 lidí = 150 lidí ve stream-aligned týmech. Kapitola uvádí velikost týmu 5–9 lidí („**Velikost:** 5–9 lidí“), takže 150 lidí dá 17–30 týmů. Při 10–15 týmech by měl tým 10–15 lidí, tedy nad horní hranicí, kterou kapitola sama doporučuje. Nesedí ani poměr 6:1 až 9:1 (stream-aligned k ostatním týmům) z téže kapitoly: 10–15 proti 3–7 ostatním týmům dává nejvýš 5:1.
- Nové znění: „- **15–25 stream-aligned týmů** po 6–9 lidech, každý vlastní 1 BC (případně 2 související supporting BC).“


# === g3.md
## [O-13] aggregate_design.md – „Ask Whose Job It Is“ ve třetím dílu
- Stav: PRAVDA
- Doslovná citace (ř. 89–90): „Dovětek o čí práci Vernon do formulace pravidla přidal ve třetím dílu série a rozebírá ho sekce [Invarianty](#invariants).“
- Zdroj / důkaz: Vernon, *Effective Aggregate Design* (https://www.dddcommunity.org/library/vernon_2011/, PDF Vernon_2011_1–3.pdf). Oddíl „Ask Whose Job It Is“ je už v **Part II**: vodítko od Evanse („ask whether it's the job of the user executing the use case to make the data consistent… If it is another user's job, or the job of the system, allow it to be eventually consistent“) a „This is a great tip to add to aggregate rules of thumb… This guideline is used later in Part III“. Ve **Part III** tým otázku aplikuje („Whose job is it to bring a backlog item's status into consistency…“) a závěrečný výčet pravidel má formulaci „Use Eventual Consistency Outside the Boundary (after asking whose job it is)“. Tvrzení o *formulaci pravidla* ve třetím dílu tedy sedí. Volitelné zpřesnění: „Otázku, čí je to práce, Vernon zavádí ve druhém dílu a ve třetím ji připojuje přímo k formulaci pravidla.“

## [O-14] aggregate_design.md – 70 % agregátů
- Stav: NEPRAVDA (číslo nepochází z Vernonova projektu, ale od Niclase Hedhmana)
- Doslovná citace (ř. 81–83): „Vernon to podkládá číslem z projektu, který analyzoval: zhruba 70 % agregátů tvořil samotný kořen s několika hodnotovými objekty, zbývajících 30 % mělo dvě až tři entity celkem.“ Totéž ve FAQ (ř. 1464): „Vernon u projektu, který analyzoval, napočítal zhruba 70 % agregátů tvořených jen kořenem s hodnotovými objekty a 30 % se dvěma až třemi entitami.“
- Zdroj / důkaz: Part I: „On one project for the financial derivatives sector using [Qi4j], Niclas [Hedhman] reported that his team was able to design approximately 70% of all aggregates with just a root entity containing some value-typed properties. The remaining 30% had just two to three total entities. This doesn't indicate that all domain models will have a 70/30 split.“ Podíly sedí, autor dat ne.
- Nové znění (ř. 81–83): „Vernon to podkládá zkušeností Niclase Hedhmana z projektu pro finanční deriváty: zhruba 70 % agregátů tvořil samotný kořen s několika hodnotovými objekty, zbývajících 30 % mělo dvě až tři entity celkem. Vernon dodává, že to není univerzální poměr.“
- Nové znění (FAQ ř. 1464, dotčená věta): „Vernon cituje projekt Niclase Hedhmana, kde zhruba 70 % agregátů tvořil jen kořen s hodnotovými objekty a 30 % mělo dvě až tři entity.“

## [O-15] aggregate_design.md – přímá lazy-loaded reference ve třetím dílu
- Stav: PRAVDA
- Doslovná citace (ř. 368–369): „Výjimku připouští i sám Vernon. Ve třetím dílu série jeho tým kvůli režii dotazů zvolí přímou lazy-loaded referenci na cizí agregát a mapování k ní přizpůsobí.“
- Zdroj / důkaz: Part II, „Reason Four: Query Performance“: „There may be times when it's best to hold direct object references to other aggregates. This could be used to ease repository query performance issues… One example of breaking the rule of reference by identity is given in Part III.“ Part III: vývojář navrhne kvůli „possible overhead of the story attribute“ vyčlenit `useCaseDefinition`, případně jako samostatný agregát: „this could be a good time to break the rule to reference external aggregates only by identity. It seems like a suitable modeling choice to use a direct object reference, and declare its object-relational mapping so as to lazily load it. Perhaps that makes sense.“ Závěr: „For now they will plan around the specialized use case definition holder.“ Výhrada: v Part III jde o režii načítání objemného atributu (story), ne dotazů v užším smyslu, a rozhodnutí je formulováno opatrně („Perhaps that makes sense“, „plan around“). Volitelné zpřesnění: „Ve třetím dílu série jeho tým zvažuje, že objemný popis use case vyčlení do samostatného agregátu a odkáže na něj přímou lazy-loaded referencí, a s touto variantou pak plánuje.“

## [O-16] aggregate_design.md – „celou sérii uzavírá poznámkou… nehledají výmluvy“
- Stav: NEPRAVDA (poznámka uzavírá druhý díl, ne celou sérii)
- Doslovná citace (ř. 305–306): „Vernon celou sérii uzavírá poznámkou, že se pro porušení vodítek nehledají výmluvy.“
- Zdroj / důkaz: Part II, závěrečný oddíl „Adhering to the Rules“: „Certainly we don't go in search of excuses to break the aggregate rules of thumb. In the long run, adhering to the rules will benefit our projects.“ Po něm následuje jen „Looking Forward to Part III“. Part III končí větou „If we adhere to the rules, we'll have consistency where necessary, and support optimally performing and highly scalable systems, all while capturing the ubiquitous language…“ – o výmluvách tam nic není.
- Nové znění: „Vernon druhý díl série uzavírá poznámkou, že se pro porušení vodítek nehledají výmluvy.“

## [O-17] aggregate_design.md – 12 tasků, 12 záznamů, nejvýš 25 objektů
- Stav: NEPRAVDA (čísla jsou Vernonova, ale text zamlčuje, co počítají)
- Doslovná citace (ř. 1367–1369): „Vernon to předvádí na backlog itemu: dvanáctidenní sprint, dvanáct tasků na jeden backlog item a dvanáct záznamů o přeodhadu. Celkem nejvýš pětadvacet objektů, tedy malý agregát.“
- Zdroj / důkaz: Part III, „Estimating Aggregate Cost“: 12denní sprint, 12 přeodhadů na task, 12 tasků na backlog item – „12 tasks, each with 12 estimation logs, or 144 total collected objects per backlog item“. Pětadvacítka je počet objektů v paměti na jeden požadavek při lazy loadingu: „When using lazy loading for the tasks and estimation logs, we would have as many as 12 plus 12 collected objects in memory at one time per request… In the end the aggregate design requires one backlog item, 12 tasks, and 12 log entries, or 25 objects maximum total. That's not very many; it's a small aggregate.“ Agregát jako celek má tedy až 145 objektů. Text knihy to podává jako celkovou velikost agregátu, a „dvanáct záznamů“ bez „na task“ je zavádějící.
- Nové znění: „Vernon to předvádí na backlog itemu: dvanáctidenní sprint, dvanáct tasků na backlog item a u každého tasku dvanáct záznamů o přeodhadu, tedy 144 vnořených objektů. Při líném načítání ale jeden požadavek drží v paměti nejvýš pětadvacet objektů: backlog item, dvanáct tasků a záznamy jediného z nich. Takový agregát Vernon označuje za malý.“

## [O-18] aggregate_design.md – highlights bloku `Order.php` míří mimo zvýrazňovaný kód
- Stav: NEPRAVDA (markup)
- Doslovná citace z aktuálního textu kapitoly: `:::code{language="php" filename="src/Ordering/Domain/Model/Order.php" highlights="25,32,33,37,38,39,62,63,64,65,66,67,68"}`
- Zdroj / důkaz: očíslování bloku (renderer `src/Content/Block/CodeBlockRenderer.php` bere čísla řádků bloku od `<?php` = 1). Řádek 25 = `use App\SharedKernel\Domain\AggregateRoot;`, 32–33 = `@var` a `private Collection $items;` (sedí), 37 = `public private(set) OrderStatus $status;` (sedí), 38 = prázdný, 39 = komentář, 62 = prázdný, 63 = `return $order;`, 64 = `}`, 65 = prázdný, 66–68 = komentář k `placeWithFirstItem()`. Zvýrazněné řádky 38–39 a 62–68 tedy míří na prázdné řádky a komentáře. Pravděpodobný záměr: kořen, kolekce, stav a zápis události v `place()`.
- Nové znění: `highlights="30,32,33,37,58,59,60,61,62,63"` (30 = `class Order extends AggregateRoot`, 58–63 = `place()` s `record(new OrderPlaced(…))`). Blok 07.08 „Order.php (mapování)“ je v pořádku jen zčásti (22 = sloupec `placedAt`, 39–41 = komentáře a prázdný řádek); pokud má zvýraznit mapování agregátních hranic, stačí `highlights="31,32,33,34,35,36,37,42,43,44"`.

## [O-19] lesser_known_patterns.md – datum článku Kévina Gomeze (#spec-doctrine)
- Stav: PRAVDA
- Doslovná citace z aktuálního textu kapitoly: „Kévin Gomez na něj navázal textem *On Taming Repository Classes in Doctrine… Among other things* (7. 2. 2015).“ (a v literatuře „Gomez, K., *On Taming Repository Classes in Doctrine… Among other things* (2015)“)
- Zdroj / důkaz: https://blog.kevingomez.fr/2015/02/07/on-taming-repository-classes-in-doctrine-among-other-things./ – v hlavičce článku „February 7, 2015 · Kévin Gomez“, v JSON-LD `"datePublished":"2015-02-07T00:00:00Z"`. Článek skutečně navazuje na Eberleie („a - rather old but nonetheless interesting - post written by @beberlei“). Specifikace v něm vracejí Doctrine `Criteria` místo úprav `QueryBuilderu` a nakonec vyústí v knihovnu RulerZ. Autor je Kévin Gomez, ne Benjamin Eberlei; kapitola má obě atribuce správně.
- Nové znění: –


# === g4.md
## [O-20] architectural_styles.md – Meszaros 2011, Configurable Dependency
- Stav: PRAVDA
- Doslovná citace (ř. 239): „Mechanismus pod porty pojmenoval Gerard Meszaros v roce 2011 jako **Configurable Dependency**. Konkrétní implementaci takové závislosti určuje až sestavení aplikace zvenčí [[4]](https://jmgarridopaz.github.io/content/interviewalistair.html).“
- Zdroj / důkaz: Zdroj [4] je rozhovor J. M. Garrida de Paz s Cockburnem (publikováno 9. 9. 2020), https://jmgarridopaz.github.io/content/interviewalistair.html. Cockburn v něm: „Gerard Mezaros, absolutely the best patterns writer I know, was visiting me in 2011, and we discussed why they are different. He came up with the name 'Configurable Dependency' for the master pattern that describes changing technologies at an interface…“ Podobně https://jmgarridopaz.github.io/content/confdep.html („Gerard Meszaros today pointed out that the property we want is that the dependency is configurable“). Diskuse na GOOS listu z 21. 3. 2012 už Cockburnovu formulaci cituje. V *xUnit Test Patterns* (2007) se název „Configurable Dependency“ nevyskytuje – kniha má vzory Dependency Injection, Dependency Lookup a Configurable Test Double (ověřeno na xunitpatterns.com; stránka Dependency Injection výraz „Configurable Dependency“ neobsahuje). Podezření redaktora na rok 2007 se tedy nepotvrdilo; rok 2011 odpovídá citovanému zdroji. Pozn.: v rozhovoru je jméno zkomoleno na „Mezaros“, správně Meszaros – kniha má správně.

## [O-21] architectural_styles.md – ObjectMapper „stabilní od verze 8.0“ (#clean-objectmapper-heading)
- Stav: NEPRAVDA (stabilní už od 7.4)
- Doslovná citace z aktuálního textu kapitoly: „Symfony 8 na to má komponentu `symfony/object-mapper`, stabilní od verze 8.0 (v 7.3 byla experimentální).“ (ř. 1020)
- Zdroj / důkaz: `vendor/symfony/object-mapper/CHANGELOG.md` (8.1): sekce **7.4** – „The component is not marked as `@experimental` anymore“; sekce 7.3 – „Add the component as experimental“. https://symfony.com/doc/7.3/object_mapper.html: „The ObjectMapper component was introduced in Symfony 7.3 as an experimental feature.“ https://symfony.com/doc/7.4/object_mapper.html: jen „introduced in Symfony 7.3“, bez značky experimental. Symfony 7.4 i 8.0 vyšly v listopadu 2025 (symfony.com/releases/7.4.json, 8.0.json).
- Nové znění: „Symfony 8 na to má komponentu `symfony/object-mapper`. Vznikla v 7.3 jako experimentální a od 7.4, vydané současně s 8.0, je stabilní.“

## [O-22] implementation_in_symfony.md – ObjectMapper „od Symfony 7.4 … stabilní“ (#persisted-object-pattern)
- Stav: PRAVDA
- Doslovná citace z aktuálního textu kapitoly: „Ruční mapper je největší nákladová položka vzoru a od Symfony 7.4 na něj existuje stabilní komponenta `symfony/object-mapper`“ (ř. 898–899)
- Zdroj / důkaz: viz O-11a (CHANGELOG 7.4, dokumentace 7.3 vs. 7.4).
- Doporučení ke sjednocení (nepovinné): obě kapitoly říkají „stabilní od 7.4“; zmínku o experimentální 7.3 stačí ponechat v jedné z nich (architectural_styles).

## [O-23] implementation_in_symfony.md – XML jako „jediná neatributová varianta“ v ORM 3 (#doctrine-custom-types)
- Stav: NEPRAVDA
- Doslovná citace z aktuálního textu kapitoly: „Po odstranění annotation a YAML driveru v ORM 3 je navíc jedinou neatributovou variantou [[8]](https://github.com/doctrine/orm/blob/3.7.x/UPGRADE.md) a nástroje kolem Doctrine ji obsluhují hůř než atributy.“
- Zdroj / důkaz: `doctrine/persistence` 4.2 dál obsahuje `Mapping/Driver/PHPDriver.php` (metadata v samostatných PHP souborech) a `StaticPHPDriver.php` (statická metoda `loadMetadata()` v entitě). ORM UPGRADE.md: „Remove `StaticPHPDriver` and `DriverChain` … Use `Doctrine\Persistence\Mapping\Driver\StaticPHPDriver`“ – drivery se jen přesunuly do persistence. DoctrineBundle 3.3 `DoctrineExtension.php`: „Can only configure "xml", "php", "staticphp" or "attribute" through the DoctrineBundle“. PHP mapping je tedy stále podporovaný.
- Nové znění: „Po odstranění annotation a YAML driveru v ORM 3 zbývá vedle atributů XML a málo používané PHP mapování [[8]](https://github.com/doctrine/orm/blob/3.7.x/UPGRADE.md). Nástroje kolem Doctrine obsluhují obě varianty hůř než atributy.“

## [O-24] implementation_in_symfony.md – hook se spouští „včetně hydratace z databáze“ (#php84-vo-zapis)
- Stav: NEPRAVDA (Doctrine při hydrataci hooky obchází, argument odstavce se obrací)
- Doslovná citace z aktuálního textu kapitoly: „Property hooks svádějí přesunout validaci z konstruktoru do `set` hooku. V doménovém modelu je to past. Hook se spustí při každém zápisu včetně hydratace z databáze, kdežto konstruktor Doctrine obchází. Validace by se tedy spouštěla právě tam, kde je zbytečná, a mlčela tam, kde na ní záleží.“
- Zdroj / důkaz: Doctrine ORM 3.7.2, `Mapping/PropertyAccessors/PropertyAccessorFactory.php`: na PHP ≥ 8.4 vždy `RawValuePropertyAccessor`; jeho docblock: „It works based on the raw values of a property, which for a case of property hooks is the backed value. If we kept using setValue/getValue, this would go through the hooks“. Zápis jde přes `ReflectionProperty::setRawValueWithoutLazyInitialization()` / `setRawValue()`, tedy mimo `set` hook. Hydratace tak obchází hook stejně jako konstruktor. Druhý argument pro `readonly` ale platí: PHP 8.4 hooky na `readonly` vlastnosti nepovolí (`php -r` → „Fatal error: Hooked properties cannot be readonly“).
- Nové znění: „Property hooks svádějí přesunout validaci z konstruktoru do `set` hooku. U hodnotového objektu to nic nepřináší. Hook nejde kombinovat s `readonly` (PHP ohlásí „Hooked properties cannot be readonly“), takže by VO přišel o neměnnost. Doctrine navíc při hydrataci zapisuje surovou hodnotu přes `ReflectionProperty::setRawValue()` a hook obchází stejně jako konstruktor. Validace v hooku tedy chrání přesně tytéž zápisy jako validace v konstruktoru.“

## [O-25] implementation_in_symfony.md – `FILTER_VALIDATE_EMAIL` a „zjednodušené RFC 5322“ (#email-validate-limits-heading)
- Stav: NEPRAVDA
- Doslovná citace z aktuálního textu kapitoly: „PHP `FILTER_VALIDATE_EMAIL` ověřuje syntaxi podle zjednodušeného RFC 5322.“
- Zdroj / důkaz: https://www.php.net/manual/en/filter.constants.php – „The validation is performed against the `addr-spec` syntax in RFC 822. However, comments, whitespace folding, and dotless domain names are not supported, and thus will be rejected.“ Zbytek calloutu sedí: adresy bez tečky v doméně odmítá, `FILTER_FLAG_EMAIL_UNICODE` povoluje Unicode jen v lokální části, IDN domény tedy bez `idn_to_ascii()` neprojdou.
- Nové znění: „PHP `FILTER_VALIDATE_EMAIL` ověřuje syntaxi `addr-spec` podle RFC 822, bez komentářů, zalamování a domén bez tečky.“

## [O-26] implementation_in_symfony.md – `data_class` na readonly commandu končí `NoSuchPropertyException` (#form-nad-commandem-heading)
- Stav: NEPRAVDA (nepřesné; závěr „formulář vrací pole“ platí)
- Doslovná citace z aktuálního textu kapitoly: „U readonly commandu s konstruktorem naráží `data_class` na promované `readonly` vlastnosti: PropertyAccess do nich zapsat neumí a formulář skončí na `NoSuchPropertyException`.“
- Zdroj / důkaz: symfony/form 8.1, `Extension/Core/Type/FormType.php`: výchozí `empty_data` pro `data_class` je `new $class()`. Spuštěný test (`final readonly class RegisterUser { __construct(public string $email, public string $name) }`): bez předaných dat skončí `submit()` na `ArgumentCountError: Too few arguments to function RegisterUser::__construct()`. S předvyplněnou instancí skončí na `NoSuchPropertyException: The property "email" in class "RegisterUser" is a promoted readonly property`. Obvyklý případ (formulář bez dat) tedy padá už na konstruktoru.
- Nové znění: „U readonly commandu s povinným konstruktorem `data_class` nefunguje. Bez předaných dat se Form pokusí zavolat `new RegisterUser()` bez argumentů a skončí na `ArgumentCountError`. S předvyplněnou instancí zase PropertyAccess do promovaných `readonly` vlastností nezapíše a vyhodí `NoSuchPropertyException`.“


# === g5.md
## [O-27] authorization_in_ddd.md – `symfony/acl-bundle` podporuje „4.4 až 7.0“ (#no-symfony-acl)
- Stav: NEPRAVDA (v detailu; závěr „pro Symfony 8 to volba není“ platí)
- Doslovná citace z aktuálního textu kapitoly: „Podpora ACL zmizela ze SecurityBundle ve verzi 4.0 a samostatný `symfony/acl-bundle` deklaruje ve svém posledním vydání podporu Symfony 4.4 až 7.0.“ (ř. 652)
- Zdroj / důkaz: https://packagist.org/packages/symfony/acl-bundle.json – poslední vydání **v2.4.0 z 24. 4. 2024**: `symfony/security-bundle`, `symfony/http-kernel`, `symfony/dependency-injection`: `^4.4|^5.0|^6.0|^7.0`. Omezení `^7.0` pokrývá celou řadu 7.x (včetně 7.4 LTS), ne jen 7.0. Symfony 8 chybí. Balíček není na Packagistu abandoned ani archivovaný na GitHubu (poslední push 20. 5. 2026, ale bez nového vydání).
- Nové znění: „… a samostatný `symfony/acl-bundle` deklaruje ve svém posledním vydání (2.4.0 z dubna 2024) podporu Symfony 4.4 až 7.x. Symfony 8 mezi nimi není.“

## [O-28] authorization_in_ddd.md – `Security::getAccessDecision()` od Symfony 7.3 (#abac-vlastni-vs-voter)
- Stav: NEPRAVDA (v detailu verze; pro Symfony 8 metoda existuje a ukázky jsou správně)
- Doslovná citace z aktuálního textu kapitoly: „Od Symfony 7.3 to umí Security komponenta sama. Voter přijímá volitelný parametr `?Vote $vote` a může do něj zapsat důvod, aplikační vrstva pak čte celé rozhodnutí přes `Security::getAccessDecision()`:“
- Zdroj / důkaz: security-core CHANGELOG, sekce 7.3: „Add ability for voters to explain their vote“. security-bundle CHANGELOG, sekce 7.4: „Add `Security::getAccessDecision()` and `getAccessDecisionForUser()` helpers“. V 8.1 metoda existuje: `public function getAccessDecision(mixed $attributes, mixed $subject = null): AccessDecision` (`Symfony\Bundle\SecurityBundle\Security`). `AccessDecision` má veřejné `$isGranted` a `$votes`, `Vote` má `$voter`, `$result`, `$reasons`, `$extraData`. Ukázka `ExplainedAccessDenied` i callout o audit logu jsou tedy funkční.
- Nové znění: „Od Symfony 7.3 to umí Security komponenta sama. Voter přijímá volitelný parametr `?Vote $vote` a může do něj zapsat důvod. Aplikační vrstva pak čte celé rozhodnutí přes `Security::getAccessDecision()`, které přibylo ve verzi 7.4:“

## [O-29] authorization_in_ddd.md – `$extraData` od 7.4 a privátní `security.authorization_checker` od 6.0 (#audit-log-heading)
- Stav: PRAVDA
- Doslovná citace z aktuálního textu kapitoly: „Má to dvě omezení: služba je od Symfony 6.0 privátní, takže se do aplikace dostane jen typehintem přes autowiring“ a „Tam jsou k dispozici jednotlivé `Vote` objekty s vlastnostmi `$voter`, `$result`, `$reasons` a od Symfony 7.4 i `$extraData`.“
- Zdroj / důkaz: security-core CHANGELOG 7.4: „Add `extraData` property to `Vote` objects“; `Authorization/Voter/Vote.php` (8.1) má `public string $voter; public int $result; public array $reasons = []; public array $extraData = [];`. security-bundle CHANGELOG 6.0: „The `security.authorization_checker` and `security.token_storage` services are now private“ (5.3 je označil za deprecated). `Resources/config/security.php` (8.1) službu registruje bez `->public()` a aliasuje ji na `AuthorizationCheckerInterface`.

## [O-30] authorization_in_ddd.md – AuthZEN Authorization API 1.0 „schválené v lednu 2026“ (#abac-vlastni-vs-voter)
- Stav: PRAVDA
- Doslovná citace z aktuálního textu kapitoly: „Rozhraní mezi nimi standardizuje AuthZEN Authorization API 1.0, schválené v lednu 2026 [[9]](https://openid.net/wg/authzen/).“ (ř. 1561)
- Zdroj / důkaz: https://openid.net/authorization-api-1-0-final-specification-approved/ – oznámení „Authorization API 1.0 Final Specification Approved“, publikováno 12. 1. 2026; hlasování členů OIDF: 81 pro, 1 proti, 25 se zdrželo (kvórum 28,3 %). Stránka WG https://openid.net/wg/authzen/ uvádí Final Specification v lednu 2026 (Implementer's Draft listopad 2024). Specifikace: https://openid.net/specs/authorization-api-1_0.html.

## [O-31] authorization_in_ddd.md – `evansims/openfga-php` abandoned (#rebac-php-heading)
- Stav: PRAVDA
- Doslovná citace z aktuálního textu kapitoly: „Komunitní `evansims/openfga-php` je na Packagistu označený jako abandoned.“ (ř. 1572)
- Zdroj / důkaz: https://packagist.org/packages/evansims/openfga-php.json – `"abandoned": true` **bez uvedeného nástupce**; poslední vydání v1.6.0 (7. 7. 2025). GitHub `evansims/openfga-php`: `archived: true`. Totéž platí pro `evansims/openfga-laravel` a `evansims/openfga-mcp`. Organizace `openfga` na GitHubu má SDK jen pro .NET, Go, Javu, JS a Python (`openfga/php-sdk` neexistuje) – sousední věta kapitoly o oficiálních SDK tedy také platí. Na Packagistu jiný udržovaný OpenFGA klient pro PHP není.

## [O-32] authorization_in_ddd.md – `logout: { target: login }` bez routy `/logout` (#edge)
- Stav: PRAVDA (v projektu založeném přes Flex routa vzniká automaticky)
- Doslovná citace z aktuálního textu kapitoly: „logout: { target: login }“
- Zdroj / důkaz: SecurityBundle od 6.4 obsahuje `Routing/LogoutRouteLoader.php`, který pro `logout.path` každého firewallu založí routu `_logout_<firewall>`. Flex recept `symfony/security-bundle` (adresáře 6.4, 7.4, 8.2) instaluje `config/routes/security.yaml` s `_security_logout: { resource: security.route_loader.logout, type: service }`. Ve standardní Symfony 8 aplikaci tedy `/logout` funguje i bez ručně definované routy.
- Doporučené doplnění (nepovinné, komentář v YAML): „# Routu /logout zakládá `security.route_loader.logout` z Flex receptu (config/routes/security.yaml).“

## [O-33] authorization_in_ddd.md – šablona posílá `_csrf_token`, `form_login` ho neověřuje (#edge)
- Stav: NEPRAVDA (ukázka je nekonzistentní a token se nekontroluje)
- Doslovná citace z aktuálního textu kapitoly: v `security.yaml` blok `form_login:` s `login_path: login`, `check_path: login`, `default_target_path: app_profile` (bez `enable_csrf`); v šabloně `<input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">`.
- Zdroj / důkaz: security-bundle 8.1 `FormLoginFactory.php`: `$this->addOption('enable_csrf', false);`; `FormLoginAuthenticator.php`: `if ($this->options['enable_csrf']) { … }`, výchozí `csrf_parameter` `_csrf_token` a `csrf_token_id` `authenticate`. Bez `enable_csrf: true` autentikátor token nečte ani neověřuje, přihlašovací formulář tedy zůstává bez ochrany proti login CSRF.
- Nové znění (YAML): do bloku `form_login` doplnit řádek `enable_csrf: true` (id tokenu `authenticate` v šabloně už odpovídá výchozímu).

## [O-34] authorization_in_ddd.md – schema-based „lepší performance než row-based“ (#multi-tenancy)
- Stav: NELZE OVĚŘIT (paušální tvrzení; výkon závisí na počtu tenantů a zátěži)
- Doslovná citace z aktuálního textu kapitoly: „**Schema-based** – sdílená databáze, samostatné schema per tenant (PostgreSQL `SET search_path`). Střední izolace, lepší performance než row-based.“
- Zdroj / důkaz: menší tabulky a indexy na tenanta zrychlí dotazy velkých tenantů, ale tisíce schémat znamenají tisíce kopií každé tabulky a indexu. To zatěžuje systémový katalog PostgreSQL, zpomaluje migrace (každá běží N-krát) a komplikuje pooling spojení, protože `search_path` je stav session (PgBouncer v transaction módu ho nepřenáší). Row-based s indexem vedeným od `tenant_id` nebo s partitioningem podle tenanta bývá při velkém počtu malých tenantů rychlejší. Obecné „lepší“ tedy neplatí.
- Nové znění: „**Schema-based** – sdílená databáze, samostatné schema per tenant (PostgreSQL `SET search_path`). Střední izolace. Výkon závisí na počtu tenantů: menší tabulky pomohou velkým tenantům, tisíce schémat ale zatíží katalog, migrace i connection pooling.“

## [O-35] cqrs.md – `broadway/broadway` je archivovaný (#symfony-messenger)
- Stav: PRAVDA
- Doslovná citace z aktuálního textu kapitoly: „Dedikované PHP knihovny z let 2014–2018 mezitím skončily (`broadway/broadway` je archivovaný) nebo roky nedostaly commit (`prooph/service-bus`, `SimpleBus`).“ (ř. 195–197)
- Zdroj / důkaz: https://api.github.com/repos/broadway/broadway – `archived: true`, poslední push 9. 8. 2026; Packagist `"abandoned": true`. Doplňkově: `prooph/service-bus` poslední push 25. 8. 2021, `SimpleBus/SimpleBus` 12. 2. 2024 – „roky bez commitu“ sedí.

## [O-36] cqrs.md – `--format` má z příkazů Messengeru jen `messenger:stats` (#failed-monitoring-heading)
- Stav: PRAVDA
- Doslovná citace z aktuálního textu kapitoly: „kontrolující `messenger:stats failed --format=json`; volbu `--format` má z příkazů messengeru jen `messenger:stats`, `messenger:failed:show` ji nezná“
- Zdroj / důkaz: symfony/messenger 8.0.7 (vendor repa) i 8.1.7: `addOption('format', …)` / `InputOption('format'` se vyskytuje jen v `Command/StatsCommand.php`. `FailedMessagesShowCommand` má volby `max`, `transport`, `stats`, `class-filter`.


# === g6.md
## [O-37] event_sourcing.md – `--fetch-size` (Symfony 8.1) (#rebuild-command-heading)
- Stav: PRAVDA
- Doslovná citace z aktuálního textu kapitoly: „Při vysokém průtoku událostí snižuje režii `--fetch-size` (Symfony 8.1), který si z transportu“
- Zdroj / důkaz: symfony/messenger CHANGELOG, sekce 8.1: „Add a `--fetch-size` option to the `messenger:consume` command to control how many messages are fetched per iteration“; `ConsumeMessagesCommand.php` (8.1.7): `new InputOption('fetch-size', null, InputOption::VALUE_REQUIRED, 'The number of messages to fetch per call to the transport', 1)`. Ve vendoru repa (8.0.7) volba chybí, což verzi 8.1 potvrzuje.

## [O-38] event_sourcing.md – Broadway, Ecotone 2.0, prooph bundle (#hotove-knihovny-heading)
- Stav: PRAVDA
- Doslovná citace z aktuálního textu kapitoly:
  - „Řada 2.0 je zatím v beta verzi.“ (Ecotone, ř. 172)
  - „doprovodný Symfony bundle se ale od roku 2024 nehnul a končí u Symfony 7.“ (prooph, ř. 173)
  - „Broadway do tohoto seznamu už nepatří. V srpnu 2026 vyšla verze 3.0.1 označená jako `abandoned` a repozitář skončil v archivu.“ (ř. 175–176)
- Zdroj / důkaz:
  - Broadway: Packagist – 3.0.0 i 3.0.1 vydány 9. 8. 2026, balíček `"abandoned": true`; GitHub `archived: true`. (Drobnost: Packagist značí jako abandoned balíček, ne konkrétní verzi. Formulace je přijatelná.)
  - Ecotone: `ecotone/ecotone` a `ecotone/symfony-bundle` – nejnovější **2.0.0-beta.1** (28. 8. 2026), stabilní řada 1.x končí 1.326.1 (22. 8. 2026). Obě řady deklarují `symfony/*: ^6.4|^7.0|^8.0`.
  - prooph bundle: `prooph/event-store-symfony-bundle` poslední vydání v0.11.2 (28. 5. 2024), všechny `symfony/*` `^5.4 || ^6.4 || ^7.0`; GitHub poslední push 10. 7. 2024, není archivovaný, není abandoned.

## [O-39] event_sourcing.md – „námitka právníka Harrisona J. Browna“ (#gdpr-event-store-heading)
- Stav: NEPRAVDA (osoba i námitka existují, Brown ale není právník)
- Doslovná citace z aktuálního textu kapitoly: „Verraes k tomu ale uvádí námitku právníka Harrisona J. Browna: zašifrovaný osobní údaj je pořád osobní údaj, bez ohledu na to, kdo drží klíč. Přidává i technickou výhradu – šifra, která je dnes neprolomitelná, jí za deset let být nemusí.“
- Zdroj / důkaz: Verraes, *Eventsourcing Patterns: Crypto-Shredding* (13. 5. 2019), https://verraes.net/2019/05/eventsourcing-patterns-throw-away-the-key/ – doplněk „UPDATE: Harrison J. Brown wrote the following answer to that question: … I spoke to my lawyer about this and they said … Encrypted personal data is still personal data, regardless of whether anyone has the key. So, if were asked by a data subject to delete their personal data and all you did was delete the encryption key you would not be complying with the removal request … I think your Forgettable Payloads pattern … is more appropriate for personal data.“ Brown (@harrisonbro) je vývojář z Velké Británie, který se na věc zeptal svého právníka. Právníkem sám není a článek ho tak nepředstavuje. Technická výhrada pochází od Verraese („Today’s unbreakable encryption could be tomorrow’s infosec disaster.“), „deset let“ v originále není. Brownovo doporučení (crypto-shredding na obchodně citlivá data, forgettable payloads na osobní údaje) přitom kryje následující odstavec kapitoly.
- Nové znění: „Verraes k tomu ale připojuje odpověď Harrisona J. Browna, který věc konzultoval s právníkem. Zašifrovaný osobní údaj je podle GDPR pořád osobní údaj, bez ohledu na to, zda klíč ještě existuje, a samotné zničení klíče žádosti o výmaz nevyhoví. Verraes sám přidává technickou výhradu: dnes neprolomitelná šifra může být zítra bezpečnostním problémem.“

## [O-40] sagas.md – dělicí čára v CQRS Journey (#terminologicka-konvence)
- Stav: PRAVDA
- Doslovná citace z aktuálního textu kapitoly: „Tým Microsoft patterns & practices termín „sága“ v průvodci *CQRS Journey* záměrně opustil a mluví jen o Process Manageru s odkazem na starší a odlišný význam toho slova. Navrhuje také dělicí čáru, kterou praxe nepřevzala: process manager routuje zprávy uvnitř jednoho Bounded Contextu, sága řídí proces přes hranice kontextů.“
- Zdroj / důkaz: CQRS Journey, Reference 6: A Saga on Sagas, https://learn.microsoft.com/en-us/previous-versions/msp-n-p/jj591569(v=pandp.10) – „we prefer to use the term *process manager* … There is a well-known, pre-existing definition of the term *saga* that has a different meaning“ (odkaz na Garcii-Molinu a Salema). Dělicí čára je tam doslova, dvakrát (v úvodu i v oddíle „Sagas and CQRS“): „Typically, you would expect to see a process manager routing messages between aggregates within a bounded context, and you would expect to see a saga managing a long-running business process that spans multiple bounded contexts.“ Obava z úkolu, že CQRS Journey jen vyhrazuje ságu pro Garcia-Molinův význam, se nepotvrdila: dokument dělá obojí. Sedí i #logika-v-process-manageru: „the process manager does not perform any business logic. It only routes messages, and in some cases translates between message types“ a „Business logic belongs in the aggregate types.“
- Nové znění: –

## [O-41] sagas.md – převod peněz jako protipříklad u Garcii-Moliny a Salema (#kdy-saga-nestaci)
- Stav: PRAVDA (s drobnou výhradou)
- Doslovná citace z aktuálního textu kapitoly: „Garcia-Molina se Salemem uvádějí protipříklad, který funguje dodnes jako test: převod peněz mezi dvěma účty. První krok částku odepíše, druhý ji připíše. V mezidobí není nikde.“
- Zdroj / důkaz: Garcia-Molina, H. & Salem, K., *Sagas*, SIGMOD 1987, https://www.cs.cornell.edu/andru/cs711/2002fa/reading/sagas.pdf, oddíl 9: „In T1, L performs some actions and then withdraws a certain amount of money from an account stored in the database. This amount is stored in a temporary, local variable until during T3 the funds are placed in some other account(s). After T1 completes, the database is left in an inconsistent state because some money is ‚missing‘ … Therefore, L cannot be run as a saga.“ Výhrada: článek hned nabízí východisko, a to peníze na cestě uložit do databáze („we add a relation for funds in transit“). Pak je databáze konzistentní a proces ságou být může. Kritérium kapitoly („mezistav musí mít jméno“) to spíš potvrzuje. Věta „Peníze, které nejsou na žádném účtu, jméno nemají“ ale čte příklad přísněji než autoři.
- Nové znění (volitelné doplnění za „…ani na vteřinu.“): „Autoři zároveň ukazují východisko: peníze na cestě dostanou vlastní místo v databázi. Mezistav tím získá jméno a proces ságou být může.“

## [O-42] sagas.md – „původní článek tomu říká cascading rollback“ (#parallel-compensation-heading)
- Stav: NEPRAVDA (nepřesné: termín v článku je, popisuje ale jinou situaci)
- Doslovná citace z aktuálního textu kapitoly: „Druhá větev může v okamžiku kompenzace stále běžet a kompenzace první větve jí zpod rukou vezme předpoklad, se kterým pracuje. Původní článek o ságách tomu říká cascading rollback a rozebírá ho právě u fork/join.“
- Zdroj / důkaz: týž článek, oddíl 8 „Parallel Sagas“. Termín se objevuje u *dopředné obnovy po pádu systému* a týká se savepointů. Proces vzniklý forkem po T1 si udělal savepoint, a když se T1 kompenzuje, savepoint je k ničemu: „the save-point made by the second process is not useful. It depends on the execution of T1 which is being compensated for. This problem is known as cascading roll backs. It has been analyzed in a scenario where processes communicate via messages or shared data objects [Gray78, Rand78]“. Jde tedy o převzatý termín z dřívější literatury a o obnovu ze savepointů, ne o souběh kompenzace s běžící větví. U zpětné obnovy článek výslovně říká, že datové závislosti mezi paralelními procesy pořadí kompenzací neřídí: „If T1 and T2 have executed in parallel processes and T2 has read data written by T1, compensating for T1 does not force us to compensate for T2 first.“ Fork/join se v oddíle opravdu rozebírá.
- Nové znění: „Časování je ale zrádnější. Druhá větev může v okamžiku kompenzace stále běžet a kompenzace první větve jí zpod rukou vezme předpoklad, se kterým pracuje. Původní článek o ságách na příbuzný jev naráží u paralelních ság s operacemi fork a join. Savepoint větve, která navazuje na kompenzovanou transakci, ztrácí platnost, a autoři to s odkazem na starší literaturu nazývají cascading rollbacks. Datové závislosti mezi větvemi přitom jejich mechanismus při kompenzaci nesleduje.“

## [O-43] sagas.md – rezervace skladu jako pivot × `CancelShipment` jako kompenzace (#selhani-kompenzace)
- Stav: PRAVDA (věcně bez chyby, jde o dva různé scénáře)
- Doslovná citace z aktuálního textu kapitoly: „**Pivot transaction** – bod rozhodnutí. Jakmile commitne, sága už necouvá a poběží dopředu až do konce. V našem procesu je pivotem rezervace skladu.“ a „**Retriable transactions** – kroky po pivotu (`CreateShipment`, `ShipOrder`). Kompenzaci nemají“; zároveň tabulka „| `CreateShipment` | `CancelShipment` | Pouze do okamžiku odeslání |“ a `onOrderCancelled` s větví `'shipment_created' => … new CancelShipment(…)`.
- Zdroj / důkaz: kód kapitoly. Richardsonova klasifikace popisuje selhání kroku uvnitř ságy. `CancelShipment` se v kapitole volá jen při stornu objednávky zvenčí (`onOrderCancelled`, opožděné `ShipmentCreated` ve stavu Compensating). Rozpor tedy není, čtenář ho ale vidět může.
- Doporučené doplnění (nepovinné, za odrážku Retriable transactions): „Klasifikace platí pro selhání kroku uvnitř ságy. Storno objednávky zvenčí je jiný scénář: sága ho obslouží jako nový požadavek a zásilku zruší přes `CancelShipment`, dokud nebyla odeslána.“


# === g7.md
## [O-44] outbox_pattern.md – „jeden PHP proces zvládne řádově jednotky tisíc zpráv za sekundu“ (3×)
- Stav: NELZE OVĚŘIT (číslo je nadsazené pro relay, který kapitola sama ukazuje)
- Doslovná citace z aktuálního textu kapitoly: „Jeden PHP proces zvládne řádově jednotky tisíc zpráv za sekundu. Na každou dělá deserializaci, publish s čekáním na ACK a UPDATE řádku, takže výsledek určuje latence brokera a databáze, ne PHP.“; tabulka „| Scale-out | jednotky tisíc zpráv/s na worker, lineárně s replikami přes SKIP LOCKED | dáno Kafkou, o dva řády výš |“; „**omezená propustnost**, protože jeden PHP proces odbaví řádově jednotky tisíc zpráv za sekundu.“
- Zdroj / důkaz: relay v kapitole (`OutboxDispatchCommand`) zpracovává batch sériově: `dispatch()` a pak `markSent()` s vlastním UPDATE a commitem pro každou zprávu. Commit s fsync plus síťový round-trip do databáze a publish do brokera stojí typicky 1–3 ms, tedy stovky zpráv za sekundu, s rychlým lokálním úložištěm nízké tisíce. Veřejný benchmark tohoto uspořádání neexistuje. Text sám dodává, že číslo je potřeba změřit.
- Nové znění: na všech třech místech „řádově stovky až nízké tisíce zpráv za sekundu“ (v tabulce „stovky až nízké tisíce zpráv/s na worker, …“).

## [O-45] outbox_pattern.md – relay (krok 3) nasazený před Inboxem (krok 4) (#migrace-krok-3-heading)
- Stav: NEPRAVDA (postup sám přiznává, že duplicity mezi kroky 3 a 4 nikdo neodchytí)
- Doslovná citace z aktuálního textu kapitoly: „Od této chvíle worker publikuje eventy z outboxu; dokud je aktivní i legacy publish, dostane broker *obě* verze. Subscribeři ale ještě nemají Inbox, takže duplicitu nikdo neodchytí.“
- Zdroj / důkaz: logika postupu. Mezi krokem 3 a 4 (který kapitola odhaduje na „typicky týdny“) dostane každý subscriber každou událost dvakrát a bez Inboxu ji dvakrát zpracuje. Bezpečné pořadí je Inbox → relay. Předpoklad: legacy dispatch v kroku 2 musí nést stejné `eventId` jako řádek v outboxu, jinak Inbox obě kopie nespáruje. Na anchory `migrace-krok-*` nic z jiných kapitol neodkazuje (grep přes `content/`, `templates/`, `src/`), kroky jde prohodit.
- Nové znění: prohodit obsah kroků 3 a 4 (anchory mohou zůstat podle pořadí). Krok 3: „### Krok 3: Přidat inbox subscriberům jeden po druhém“ + stávající text kroku 4 a věta: „Legacy dispatch z kroku 2 musí nést stejné `eventId` jako řádek v outboxu, jinak Inbox obě kopie nespáruje.“ Krok 4: „### Krok 4: Nasadit relay command“ + „Implementujte `OutboxDispatchCommand` ze sekce [15.05](#relay) a nasaďte ho pod supervisorem. Dokud je aktivní i legacy publish, dostane broker *obě* verze každé události. Inbox u subscriberů druhou kopii zahodí.“ Krok 5 pak začíná „Až relay běží stabilně, smažte v handleru původní `$bus->dispatch()`…“.

## [O-46] outbox_pattern.md – `options: ['precision' => 6]` u `datetime_immutable` (#schema)
- Stav: NEPRAVDA (DBAL 4 volbu `precision` u datetime ignoruje a zapisuje bez mikrosekund)
- Doslovná citace z aktuálního textu kapitoly: „// Pozor na přesnost časových sloupců: migrations:diff vygeneruje // z entity DATETIME bez (6). Na MySQL by se tím z outboxu ztratilo // subsekundové řazení, takže přesnost patří do mapování: // options: ['precision' => 6].“
- Zdroj / důkaz: doctrine/dbal 4.4.4 (poslední vydání na Packagistu) `AbstractMySQLPlatform::getDateTimeTypeDeclarationSQL()` vrací pevně `'DATETIME'`, `PostgreSQLPlatform` `'TIMESTAMP(0) WITHOUT TIME ZONE'`. Klíč `precision` se u datetime nečte (totéž ve větvi 5.0.x). `DateTimeImmutableType::convertToDatabaseValue()` formátuje přes `getDateTimeFormatString()` = `'Y-m-d H:i:s'`, takže mikrosekundy se nezapíšou ani do ručně vytvořeného sloupce `DATETIME(6)`. Subsekundové řazení tedy standardní typ Doctrine nezajistí vůbec. Entita bez `precision` je proto vůči DBAL v pořádku, chybný je komentář.
- Nové znění komentáře: „// Pozor na přesnost časových sloupců: typ datetime_immutable v DBAL 4 // vytvoří DATETIME bez (6) a čas zapíše bez mikrosekund. Subsekundové // řazení outboxu proto potřebuje vlastní DBAL typ s formátem 'Y-m-d H:i:s.u' // a columnDefinition 'DATETIME(6)'; bez něj řadí relay podle času // s přesností na sekundy. Komentář stojí zde, ne uvnitř CREATE TABLE – // SQL komentáře v DDL rozhodí introspekci schématu.“

## [O-47] outbox_pattern.md – partitioned tabulka se liší od schématu z 15.03 (#partitioning-sql-heading)
- Stav: NEPRAVDA (nesoulad s kanonickým schématem kapitoly)
- Doslovná citace z aktuálního textu kapitoly: „CREATE TABLE outbox ( id UUID NOT NULL, message_type VARCHAR(255) NOT NULL, payload JSONB NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending', … attempts INT NOT NULL DEFAULT 0, …“
- Zdroj / důkaz: kanonická migrace v 15.03 má `aggregate_type VARCHAR(255) NOT NULL`, `aggregate_id VARCHAR(64) NOT NULL`, `status VARCHAR(16)` a výslovně bez DEFAULT klauzulí („Výchozí hodnoty (status, attempts) drží entita, ne DEFAULT klauzule; jinak se schéma a mapování rozejdou a schema:validate hlásí rozpor.“). Partitioned varianta nemá sloupce agregátu, má jinou délku `status` a DEFAULT klauzule, které kapitola o kus výš zakazuje.
- Nové znění (SQL): doplnit za `message_type` řádky `aggregate_type VARCHAR(255) NOT NULL,` a `aggregate_id VARCHAR(64) NOT NULL,`, změnit `status VARCHAR(16) NOT NULL,` a `attempts INT NOT NULL,` (bez DEFAULT). Pokud má varianta zůstat zkrácená, přidat nad blok větu: „Výpis ukazuje jen sloupce, na kterých partitioning záleží; ostatní odpovídají schématu z [15.03](#schema).“

## [O-48] performance_aspects.md – „DoctrineBundle 3 je má zapnuté napevno“ (#lazy-objects-heading)
- Stav: PRAVDA
- Doslovná citace z aktuálního textu kapitoly: „PHP 8.4 přidalo lazy objekty přímo do jazyka (`ReflectionClass::newLazyGhost()`, `newLazyProxy()`, `ReflectionProperty::skipLazyInitialization()`) a DoctrineBundle 3 je má zapnuté napevno.“
- Zdroj / důkaz: viz [M-final]. DoctrineBundle 3.3.2 `DoctrineExtension.php`: `'enableNativeLazyObjects' => true`; `Configuration.php` odmítne `false` („can no longer be disabled“); UPGRADE-3.0 odstranil volby `auto_generate_proxy_classes`, `proxy_dir`, `proxy_namespace` („no-ops when enabling native lazy objects“).

## [O-49] testing_ddd.md – Ham Vocke se „místo poměru ptá, kolik integračních bodů jeden test ověřuje“ (17.01)
- Stav: NEPRAVDA (parafráze posouvá Vockeho tezi)
- Doslovná citace z aktuálního textu kapitoly: „Ham Vocke jde ještě dál a Cohnovo pojmenování vrstev označuje za zjednodušující; místo poměru se ptá, kolik integračních bodů jeden test skutečně ověřuje“
- Zdroj / důkaz: Vocke, *The Practical Test Pyramid* (martinfowler.com, 2018): „Don't become too attached to the names of the individual layers in Cohn's test pyramid.“; „Write tests with different granularity. The more high-level you get the fewer tests you should have.“; „I like to treat integration testing more narrowly and test one integration point at a time by replacing separate services and databases with test doubles.“ Vocke poměr neopouští (drží tvar pyramidy). Jeden integrační bod na test doporučuje jen pro integrační testy, nenabízí ho jako náhradní měřítko celé sady.
- Nové znění: „Ham Vocke jde ještě dál: na názvech Cohnových vrstev podle něj nezáleží. Trvá jen na dvou věcech: testy mají mít různou granularitu a čím výš v pyramidě, tím méně jich má být. Integrační testy pojímá úzce, každý ověřuje jediný integrační bod a zbytek nahradí test doubles.“

## [O-50] testing_ddd.md – `qossmic/deptrac` abandoned od listopadu 2024; řada 4.x a `deptrac.php` (#architektonicke-testy)
- Stav: PRAVDA
- Doslovná citace z aktuálního textu kapitoly: „Balíček `qossmic/deptrac` je od listopadu 2024 na Packagistu označený jako abandoned a nahradil ho `deptrac/deptrac` … Řada 4.x drží konfiguraci ve výchozím souboru `deptrac.php` s typovaným API; YAML zůstává podporovaný, ale `vendor/bin/deptrac init` generuje PHP.“
- Zdroj / důkaz: https://packagist.org/packages/qossmic/deptrac.json – `"abandoned": "deptrac/deptrac"`; poslední vydání `qossmic/deptrac` 2.0.4 z 21. 11. 2024, `deptrac/deptrac` vydal 2.0.3 a 2.0.4 týž den (Packagist datum označení abandoned neukazuje, časování to ale podporuje). Aktuální `deptrac/deptrac` 4.7.2 (15. 9. 2026). Větev 4.x (výchozí na GitHubu): `ConfigFileResolver.php` hledá `deptrac.php`, pak `deptrac.yaml`. `InitCommand.php` zapisuje výchozí `deptrac.php` ze šablony `config/deptrac_template.php`. (Stará větev `main` má ještě `deptrac_template.yaml`; kapitola správně mluví o řadě 4.x.)


# === g8.md
## [O-51] microservices_and_ddd.md – `IntegrationEventSerializer`: dead-letter exchange × failure pipeline (docblock)
- Stav: NEPRAVDA (obě věty neplatí současně a každá platí pro jinou verzi)
- Doslovná citace z aktuálního textu kapitoly: „dokud nedoplníme, zpráva spadne do dead-letter exchange.“ a „Selhání dekódování MUSÍ být MessageDecodingFailedException. Jen tu receiver zachytí a pošle do failure pipeline; jiná výjimka shodí worker, zpráva zůstane neackovaná a po restartu se vrátí znovu - nekonečná smyčka nad jedinou vadnou zprávou.“
- Zdroj / důkaz: `symfony/amqp-messenger` v8.0.11 `AmqpReceiver.php`: `catch (MessageDecodingFailedException $exception) { $this->rejectAmqpEnvelope($amqpEnvelope, $queueName); throw $exception; }` – na 8.0 zprávu rejectne bez requeue (do DLX jen tehdy, když ji má fronta nastavenou, jinak se zahodí) a do failure transportu nejde. `symfony/messenger` CHANGELOG 8.1: „Receivers no longer delete messages on decode failure; they are routed through the normal retry/failure transport path“; `AmqpReceiver` 8.1.7 vrací `MessageDecodingFailedException::wrap(...)`. Jinou výjimku receiver v obou verzích nezachytí, věta o nekonečné smyčce tedy platí. V kombinaci s `max_retries: 0` z konfigurace transportu jde vadná zpráva na 8.1 rovnou do failure transportu.
- Nové znění (docblock):
  „ * Když publisher přidá nový event_type, doplníme tu řádek;
   * dokud nedoplníme, dekódování selže.
   *
   * Selhání dekódování MUSÍ být MessageDecodingFailedException.
   * Od Symfony 8.1 s ní receiver pošle zprávu běžnou retry/failure cestou
   * do failure transportu. Na 8.0 ji AmqpReceiver jen rejectne, takže
   * přežije pouze v dead-letter exchange nastavené na frontě. Jiná výjimka
   * shodí worker, zpráva zůstane neackovaná a po restartu se vrátí
   * znovu – nekonečná smyčka nad jedinou vadnou zprávou.“

## [O-52] microservices_and_ddd.md – BFF jako Open Host Service s Published Language (FAQ)
- Stav: NEPRAVDA
- Doslovná citace z aktuálního textu kapitoly: „V DDD terminologii je to typicky <strong>Open Host Service</strong> (OHS) s <strong>Published Language</strong>, doplněný Anti-Corruption Layerem proti volaným službám … BFF nepatří do žádného doménového Bounded Contextu; je to integrační vrstva, vlastní BC sám o sobě (typicky „Web Frontend BC“).“
- Zdroj / důkaz: Evans, *DDD Reference* (2015), Open-host Service: protokol, který dává přístup k subsystému jako sadě služeb a je „otevřený“ všem, kdo potřebují integraci. BFF je podle definice (Sam Newman, *Backends For Frontends*, 2015) backend na míru jedinému klientovi, tedy opak otevřeného protokolu. Vůči volaným službám je downstream konzumentem. Poslední věta si navíc odporuje („nepatří do žádného BC“ × „vlastní BC sám o sobě“).
- Nové znění: „V DDD terminologii je BFF downstream konzument volaných služeb: využívá jejich Open Host Service a Published Language a proti jejich modelům staví Anti-Corruption Layer, viz <a href="/context-mapping#ohs">Open Host Service</a> a <a href="/context-mapping#published-language">Published Language</a>. Sám OHS není. OHS je protokol otevřený všem konzumentům, kdežto BFF slouží jedinému klientovi. Doménovou logiku nenese; je to integrační vrstva s vlastním modelem obrazovek, kterou lze vést jako samostatný kontext (typicky „Web Frontend“).“

## [O-53] microservices_and_ddd.md – `messenger:consume --fetch-size` (#symfony)
- Stav: PRAVDA (přepínač existuje od Symfony 8.1; v 8.0 chybí)
- Doslovná citace z aktuálního textu kapitoly: „Messenger na provoz nabízí i dva novější přepínače: `--keepalive` brání předčasnému redelivery u dlouho běžících handlerů a `--fetch-size` snižuje počet dotazů do transportu při dávkovém zpracování.“
- Zdroj / důkaz: viz [M-fetch-es]; `--keepalive` je v `ConsumeMessagesCommand` 8.0 i 8.1. Kniha cílí na Symfony 8 a vendor repa má 8.0.7, kde `--fetch-size` není.
- Doporučené zpřesnění (nepovinné): „… a `--fetch-size` (od Symfony 8.1) snižuje počet dotazů do transportu při dávkovém zpracování.“

## [O-54] ddd_pain_points.md – „separátní EntityManager nakonfigurovaný jako read-only“ (#a2-spinavy-em)
- Stav: NEPRAVDA (Doctrine ORM 3 ani DoctrineBundle read-only EntityManager nemají)
- Doslovná citace z aktuálního textu kapitoly: „| Celý controller je read-only | Injektujte separátní `EntityManager` nakonfigurovaný jako read-only (second EM v Symfony) |“
- Zdroj / důkaz: konfigurace EntityManageru v DoctrineBundle 3.3 (`Configuration.php`) žádnou volbu read-only nemá. ORM 3 nabízí read-only na třech úrovních: entita `#[ORM\Entity(readOnly: true)]`, dotaz `Query::HINT_READ_ONLY` (`$query->setHint(Query::HINT_READ_ONLY, true)`) a instance `$em->getUnitOfWork()->markReadOnly($entity)`. Druhý EM jde nasměrovat na read-only databázového uživatele nebo repliku, ale Doctrine sama změny v něm nesleduje o nic méně.
- Nové znění: „| Celý controller je read-only | Dotazy s hintem `Query::HINT_READ_ONLY`, případně entity mapované jako `#[ORM\Entity(readOnly: true)]` – UnitOfWork je při `flush()` nekontroluje |“

## [O-55] ddd_pain_points.md – „nad odpojenou entitou inicializace selže s EntityNotFoundException“ (#a4-lazy-loading)
- Stav: NEPRAVDA (platí jen varianta „záznam mezitím zmizel“)
- Doslovná citace z aktuálního textu kapitoly: „Když ji zavoláte nad odpojenou entitou nebo nad záznamem, který mezitím z databáze zmizel, inicializace selže s `EntityNotFoundException`. Platí to pro klasické proxy třídy i pro nativní lazy objekty PHP 8.4.“
- Zdroj / důkaz: ORM 3.7.2 `Proxy/ProxyFactory.php`: inicializátor nativního lazy ghostu i klasické proxy volá `$entityPersister->loadById($identifier, …)` a výjimku hází jen při `$original === null`. Stav entity v UnitOfWork nekontroluje. Spuštěný test (SQLite, ORM 3.7.2, nativní lazy objekty): po `$em->detach($order)` i po `$em->clear()` se `$order->customer->name` načte bez chyby. Po smazání řádku zákazníka hodí přístup `Doctrine\ORM\EntityNotFoundException`.
- Nové znění: „Když záznam, na který proxy ukazuje, mezitím z databáze zmizel, inicializace selže s `EntityNotFoundException`. Odpojení entity samo chybu nevyvolá: inicializátor proxy stav v UnitOfWork nekontroluje a data dočte přes persister, dokud je spojení otevřené. Platí to pro klasické proxy třídy i pro nativní lazy objekty PHP 8.4.“

## [O-56] ddd_pain_points.md – Stripe Charges API se `source` tokenem (#c3-acl)
- Stav: NEPRAVDA (ukázka používá API, které Stripe označuje za deprecated a pro nové integrace ho nepovoluje)
- Doslovná citace z aktuálního textu kapitoly: „**Problém:** Stripe vrací `\Stripe\Charge`, …“ (ř. 803) a v kódu `$charge = $this->stripe->charges->create([ 'amount' => …, 'currency' => …, 'source' => $token->value, ]);` (ř. 838–842), refund přes `'charge' => $id->value` (ř. 852).
- Zdroj / důkaz: https://docs.stripe.com/payments/charges-api – „[Deprecated] Card payments on the Charges API … Learn how to charge, save, and authenticate cards with Stripe's legacy APIs.“ a „We've deprecated use of the Charges API to create card payments and plan to remove support. Use Checkout or the Payment Intents API instead. **New integrations can't use the Charges API to create payments.**“ Charges API nepodporuje SCA ani 3D Secure. Reference https://docs.stripe.com/api/payment_intents/create: `payment_method` (ID PaymentMethod), `confirm=true`, `error_on_requires_action` (jen s `confirm=true`); Refund přijímá `payment_intent`.
- Nové znění (ř. 803): „**Problém:** Stripe vrací `\Stripe\PaymentIntent`, ARES vrací JSON, Fakturoid vlastní DTO.“
  Nové znění adaptéru (`PaymentToken` nese ID PaymentMethod `pm_…` vytvořené přes Stripe.js; port se nemění):
  ```php
  public function charge(Money $amount, PaymentToken $token): PaymentId
  {
      try {
          $intent = $this->stripe->paymentIntents->create([
              'amount'                   => $amount->amountInCents,
              'currency'                 => strtolower($amount->currency->value),
              'payment_method'           => $token->value,
              'payment_method_types'     => ['card'],
              'confirm'                  => true,
              'error_on_requires_action' => true,
          ]);
      } catch (\Stripe\Exception\CardException $e) {
          throw new PaymentFailedException($e->getMessage(), previous: $e);
      }

      if ($intent->status !== 'succeeded') {
          throw new PaymentFailedException(sprintf('Platba skončila ve stavu „%s“.', $intent->status));
      }

      return PaymentId::fromString($intent->id);
  }

  public function refund(PaymentId $id, Money $amount): void
  {
      $this->stripe->refunds->create([
          'payment_intent' => $id->value,
          'amount'         => $amount->amountInCents,
      ]);
  }
  ```
  Volitelně jedna věta pod ukázkou: „Ověření 3D Secure, které si banka může vyžádat, adaptér nezvládne a platbu odmítne. Plná integrace ho řeší na klientovi přes Stripe.js; pro ilustraci ACL to podstatné není.“
  (Pozn.: `payment_method_types` je ve Stripe API vedené jako legacy vedle `automatic_payment_methods`, ale pro čistě kartovou serverovou platbu bez přesměrování je to nejkratší platná varianta. Alternativa: `'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never']`.)

## [O-57] ddd_pain_points.md – `symfony/symfony-docs#10819` jako „otevřená otázka“ (#c2-stavy note)
- Stav: NEPRAVDA
- Doslovná citace z aktuálního textu kapitoly: „Oficiální stanovisko Symfony k tomuto rozdělení neexistuje. Napětí mezi konfiguračním workflow a modelem, který má o sobě vědět všechno sám, je v projektu vedeno jako otevřená otázka (`symfony/symfony-docs#10819`).“ (ř. 795–798)
- Zdroj / důkaz: https://api.github.com/repos/symfony/symfony-docs/issues/10819 – issue „[Workflow] How workflows makes sense with DDD“ (Nyholm, 29. 12. 2018), **closed 19. 2. 2019** (`state_reason: completed`). Javier Eguiluz ho uzavřel s odůvodněním, že dokumentace DDD nevysvětluje a správci ji neumějí udržovat („we can't add something like this to docs … because it talks about a particular architecture (DDD) that we never show or explain in the docs“). Nyholm pak téma zpracoval v blogovém článku (https://engineering.eneba.com/blog/symfony-state-machines-and-domain-driven-design.html). Poslední komentář z roku 2023 upozorňuje, že v jeho návrhu Workflow prosakuje do domény.
- Nové znění: „Oficiální stanovisko Symfony k tomuto rozdělení neexistuje. Návrh doplnit do dokumentace návod na Workflow v DDD správci v roce 2019 odmítli s tím, že dokumentace DDD nevysvětluje (`symfony/symfony-docs#10819`).“

## [O-58] migration_from_crud.md – přejmenování StranglerApplication → StranglerFigApplication (#strangler-fig)
- Stav: PRAVDA
- Doslovná citace z aktuálního textu kapitoly: „Původní zápis vyšel 29. června 2004 pod názvem *Strangler Application*. Dne 29. dubna 2019 Fowler vzor přejmenoval na *Strangler Fig Application*: zkrácené „strangler“ se odtrhlo od botanické metafory a začalo vyznívat násilně.“
- Zdroj / důkaz: https://martinfowler.com/bliki/OriginalStranglerFigApplication.html – datum „29 June 2004“, sekce Revisions: „Changed URL and name to Strangler Fig Application April 29 2019“. Důvod sedí s poznámkou na https://martinfowler.com/bliki/StranglerFigApplication.html: „This led to people often using ‚strangler‘ and forgetting the botanical origin of the name … I became concerned about this due to its connotations of violence.“ Upozornění: odkaz [2] v kapitole teď vede na nový text z 22. 8. 2024, který Fowler napsal místo původního bliki. Původní zápis s revizní poznámkou je na URL `OriginalStranglerFigApplication.html`. Datum v kapitole je správně. Kdo chce zdroj data dohledat, potřeboval by ale odkaz na původní stránku.
- Nové znění: – (volitelně změnit odkaz u věty „Původní zápis vyšel…“ na https://martinfowler.com/bliki/OriginalStranglerFigApplication.html)


# === g9.md
## [O-59] case_study.md – `DeduplicateMiddleware` jako náhrada idempotence projekce (#read-model-reconciliation-heading)
- Stav: NEPRAVDA (middleware brání duplicitnímu odeslání, opakované doručení nezachytí)
- Doslovná citace z aktuálního textu kapitoly: „Nebo deduplikaci převezme `DeduplicateMiddleware` se stampem `DeduplicateStamp`, které Messenger nabízí od verze 7.3.“
- Zdroj / důkaz: symfony/messenger CHANGELOG 7.3: „Add `DeduplicateMiddleware` and `DeduplicateStamp`“ (verze sedí). `Middleware/DeduplicateMiddleware.php` (8.1.7): zámek podle klíče stampu se bere jen u zprávy bez `ReceivedStamp`, tedy při odeslání, a při neúspěchu se zpráva tiše zahodí. Na straně příjmu se zámek po zpracování uvolní (s `onlyDeduplicateInQueue` už před ním). Přijatá zpráva jde do handleru bez kontroly. Opakované doručení téže zprávy (at-least-once, pád workeru před ACK, retry) tak middleware propustí. Stejnou událost odeslanou znovu po jejím zpracování také.
- Nové znění: „Řešení vede přes identitu události: každá ponese vlastní `eventId` a projekce si zpracovaná ID zapamatuje. Dnešní třídy nesou jen `occurredAt`, takže jde o změnu payloadu. `DeduplicateMiddleware` se stampem `DeduplicateStamp` (Messenger od 7.3) ho nenahradí. Zámek drží jen od odeslání do zpracování, takže zastaví duplicitní odeslání čekající zprávy, ale opakované doručení propustí.“

## [O-60] when_not_to_use_ddd.md – Khononov: „Supporting → lehké DDD nebo Active Record“ (#hybrid-subdomain)
- Stav: NEPRAVDA (nepřesná atribuce)
- Doslovná citace z aktuálního textu kapitoly: „Khononov v *Learning DDD* (2021) volí architekturu **podle typu subdomény**“ a řádek tabulky „| **Supporting Subdomain** | Lehké DDD (entity + repository, žádné agregáty) nebo Active Record | …“
- Zdroj / důkaz: Khononov, *Learning Domain-Driven Design* (O'Reilly 2021), kap. 5–7 a kap. 10 *Design Heuristics*. Rozhodovací strom volí *vzor byznys logiky* podle její složitosti, typ subdomény slouží jako vodítko. U peněz, auditní stopy nebo hloubkové analytiky vychází Event-sourced Domain Model. Složitá logika vede na Domain Model, typicky v core subdoméně. Jednoduchá logika vede na Active Record, pokud jsou složité datové struktury, jinak na Transaction Script, typicky v supporting a generic subdoméně. Sekundární shrnutí stromu: https://dev.to/dmitrii-abramov/what-architectural-style-should-you-use-a-guide-to-tactical-ddd-decision-tree-1gf7 („Supporting/Generic … Are the data structures complex? No → Transaction Script … Yes → Active Record“). Varianta „lehké DDD: entity + repository, žádné agregáty“ u Khononova není a Transaction Script v tabulce chybí. Plný text knihy (O'Reilly, studylib) nešel stáhnout (403/429). Závěr proto stojí na znalosti knihy a sekundárních shrnutích; atribuci „lehkého DDD“ Khononovovi ale žádný z nich nepodporuje.
- Nové znění: úvodní věta „Khononov v *Learning DDD* (2021) volí vzor pro byznys logiku podle její složitosti a typ subdomény mu slouží jako vodítko. Jednoduchá logika podpůrných a generických subdomén vede na Transaction Script, nebo na Active Record, když jsou datové struktury složité. Core subdoména dostane Domain Model, a pokud jde o peníze nebo auditní stopu, jeho event-sourced podobu. Tabulka tuto heuristiku přenáší do Symfony; „lehké DDD“ je zjednodušení této knihy, ne Khononovův termín:“ a řádek tabulky „| **Supporting Subdomain** | Transaction Script nebo Active Record; v Doctrine lehké DDD (entita + repozitář, bez agregátních hranic) | Pravidla existují, ale nejsou diferenciační. Plné DDD je over-engineering. |“ (Odstavec o pojišťovně na ř. 405–406 s „lehkým DDD“ tak může zůstat.)

## [O-61] ddd_ai.md – ts-morph v O'Reilly článku vs. návazný článek (#bounded-contexts)
- Stav: NEPRAVDA (próza sedí, popis v seznamu zdrojů ne)
- Doslovná citace z aktuálního textu kapitoly: próza: „V článku pro O'Reilly Radar (únor 2026) popisuje, jak použil Claude Code k reverznímu inženýrství softwarové architektury. … V návazném článku ukazuje, jak lze pomocí knihovny ts-morph deterministicky extrahovat architektonické vzory…“; seznam zdrojů u O'Reilly článku: „Použití Claude Code a ts-morph k extrakci architektonických vzorů, včetně autorova varování o podstatných nepřesnostech generovaného popisu.“
- Zdroj / důkaz: O'Reilly Radar, Nick Tune, *Reverse Engineering Your Software Architecture with Claude Code to Help Claude Code* (6. 2. 2026), https://www.oreilly.com/radar/reverse-engineering-your-software-architecture-with-claude-code-to-help-claude-code/ – ts-morph zmiňuje jen jako odkaz jinam: „I explored the challenge of accuracy in this later post showing how we can use deterministic tools like ts-morph…“. Varování tam je: „there have been significant inaccuracies that I had to spot and correct.“ Návazný článek je Nick Tune, *Extracting your software architecture with ts-morph* (22. 10. 2025), https://nick-tune.me/blog/2025-10-22-extracting-your-software-architecture-with-ts-morph/. O'Reilly text je přetisk blogpostu z 19. 10. 2025 (https://nick-tune.me/blog/2025-10-19-reverse-engineering-your-software-architecture-with-claude-c/). Pořadí „návazný“ tedy platí vůči originálu, i když ts-morph článek vyšel dřív než přetisk v O'Reilly.
- Nové znění: popis u O'Reilly článku v seznamu zdrojů „Použití Claude Code k mapování toků, závislostí a hranic v existující kódové bázi, včetně autorova varování o podstatných nepřesnostech generovaného popisu.“ a nová položka seznamu:
  „- **Tune, N. – nick-tune.me, říjen 2025:** <a href="https://nick-tune.me/blog/2025-10-22-extracting-your-software-architecture-with-ts-morph/" target="_blank" rel="noopener noreferrer">Extracting your software architecture with ts-morph</a>. Návazný text: deterministická extrakce architektonického modelu z kódu pomocí ts-morph.“

## [O-62] practical_examples.md – `final` u entit díky nativním lazy objektům (#user-aggregate)
- Stav: PRAVDA
- Doslovná citace z aktuálního textu kapitoly: „A `final` u entit mapovaných Doctrine projde, protože nativní lazy objekty z entity nedědí.“
- Zdroj / důkaz: DoctrineBundle 3.x nastavuje ORM `enableNativeLazyObjects` natvrdo na `true` (`DoctrineExtension.php`: `'enableNativeLazyObjects' => true`). Volbu `enable_native_lazy_objects: false` odmítne („The setting "enable_native_lazy_objects" can no longer be disabled and should not be set“) a UPGRADE-3.1 ji vede jako deprecated, „as native lazy objects are now always enabled“. `ProxyFactory` pak volá `ReflectionClass::newLazyGhost()`, který s `final` třídou funguje. Pro Symfony 8 s DoctrineBundle 3 tvrzení platí bez výhrad.

## [O-63] practical_examples.md – CodelyTV/php-ddd-example jako příklad podobného členění (FAQ)
- Stav: PRAVDA
- Doslovná citace z aktuálního textu kapitoly: „Kombinace se v ukázkách opakuje záměrně; podobné členění podle případů užití s CQRS sběrnicí drží i veřejné referenční projekty, například <code>CodelyTV/php-ddd-example</code>.“
- Zdroj / důkaz: https://github.com/CodelyTV/php-ddd-example (větev `main`, poslední commit 6. 8. 2024, nearchivováno, popis „Hexagonal Architecture + DDD + CQRS in PHP using Symfony 7“). Struktura je `src/<BC>/<Modul>/{Application,Domain,Infrastructure}`. V `Application` jsou podsložky pro případy užití, každá s vlastním command/query a handlerem, např. `src/Mooc/Courses/Application/Create/{CreateCourseCommand,CreateCourseCommandHandler,CourseCreator}.php` nebo `src/Mooc/Videos/Application/Find/{FindVideoQuery,FindVideoQueryHandler,VideoResponse}.php`. Sběrnice jsou v `src/Shared/Domain/Bus/{Command,Query,Event}`. Změkčená formulace „podobné členění podle případů užití s CQRS sběrnicí“ tomu odpovídá. Ostřejší „stejné členění“ nebo „vertical slice“ by neseděly, protože vrstvy jsou dělené hexagonálně po modulech. Poznámka: repozitář cílí na Symfony 7 a od srpna 2024 se nevyvíjí. Jako příklad členění to nevadí.
- Nové znění: –


---
<!-- reports/apply-overeni-B.md -->
# Aplikace ověření – kapitoly skupiny B

Rozsah: preface, what_is_ddd, subdomains, context_mapping, event_storming, team_topologies,
basic_concepts, aggregate_design, lesser_known_patterns, architectural_styles,
implementation_in_symfony. Aplikovány jen položky NEPRAVDA / NELZE OVĚŘIT; položky PRAVDA
(O-2, O-4, O-5, O-13, O-15, O-19, O-20, O-22) včetně nepovinných zpřesnění nechány beze změny.

## NEPRAVDA / NELZE OVĚŘIT

- **O-1** (subdomains, uuid) – provedeno částečně. Text už byl dřív opravený (DoctrineBundle
  registruje typ sám). Upraven jen závěr odstavce („explicitní registrace v projektu
  s DoctrineBundle nic nemění, jen typ zviditelní“) a popisek YAML bloku
  „(výřez: mapping podle kontextů)“ → „(výřez: explicitní registrace typu)“. Blok ponechán.
  Původní věta „jen mimo DoctrineBundle“ se tloukla s tím, že blok je konfigurace DoctrineBundle.
- **O-3** (subdomains, OAuth 2.1) – provedeno: na obou místech „OAuth 2.0“.
- **O-6** (preface, související nález) – provedeno: „strategickému designu ale dává málo
  prostoru a se Symfony 8 soustavně nepracuje“ (vypuštěno tvrzení o Doctrine ORM 3).
- **O-7** (context_mapping, Separate Ways a GDPR) – provedeno: souhlas žije jen v SendGridu,
  odhlášení přes patičku / `List-Unsubscribe`, Identity o souhlasu neví; ústupky přepsány,
  doplněna podmínka, bez které by šlo o porušení zákona. CAN-SPAM i „krátká okna“ pryč.
- **O-8** (context_mapping, `generateMonthlyRevenue`) – provedeno: `getRecentPayments()` →
  `getPaymentsInMonth(int $year, int $month)` s horní mezí `lt` (+1 month)
  a `autoPagingIterator()` + `iterator_to_array()`; veřejná metoda přejmenována na
  `listMonthlyPayments()`. Na metody nic jiného neodkazuje (grep content/templates/src).
- **O-9** (context_mapping) – provedeno: `jane-php/open-api-3`.
- **O-11** (event_storming, Evans) – provedeno: Evans staví UL na mluvené řeči, dokumenty
  řeč a kód jen doplňují. Kotva odkazu zachována.
- **O-12** (team_topologies) – provedeno: „15–25 stream-aligned týmů po 6–9 lidech“.
- **O-14** (aggregate_design, 70/30) – provedeno v textu i ve FAQ: data připsána projektu
  Niclase Hedhmana, doplněno, že to Vernon nepovažuje za univerzální poměr.
- **O-16** (aggregate_design) – provedeno: „Vernon druhý díl série uzavírá…“.
- **O-17** (aggregate_design, 12/12/25) – provedeno: 12 tasků, u každého 12 záznamů = 144
  objektů; pětadvacet je maximum v paměti na požadavek při líném načítání.
- **O-18** (aggregate_design, highlights) – provedeno, přepočítáno na aktuální podobu bloků
  (od ověření se změnily). `Order.php`: `30,32,33,37,58–64` (třída, kolekce, stav, `place()`
  s `record()`). `Order.php (mapování)`: `32–38,40,41,43–45` (OneToMany s kaskádou,
  komentáře „žádné ManyToOne“, `#[ORM\Version]`); původní 22 a 39 mířily na prázdné řádky.
- **O-21** (architectural_styles, ObjectMapper) – provedeno (ověřeno i proti CHANGELOG
  ve scratchpadu): vznikl v 7.3 jako experimentální, stabilní od 7.4 vydané současně s 8.0.
- **O-23** (implementation, XML jediná neatributová) – provedeno: vedle atributů zbývá XML
  a málo používané PHP mapování.
- **O-24** (implementation, hook při hydrataci) – provedeno (ověřeno: `RawValuePropertyAccessor`
  v ORM 3.7.2, `php -r` → „Hooked properties cannot be readonly“). Argument přepsán:
  hook nejde s `readonly`, Doctrine ho při hydrataci obchází přes `setRawValue()`.
- **O-25** (implementation, FILTER_VALIDATE_EMAIL) – provedeno: `addr-spec` podle RFC 822,
  bez komentářů a zalamování (domény bez tečky řeší už následující odrážka).
- **O-26** (implementation, data_class) – provedeno: bez dat `ArgumentCountError`
  z `new RegisterUser()`, s předvyplněnou instancí `NoSuchPropertyException`.

## Ostatní
- `modified` u 8 změněných kapitol posunuto na 2026-09-24.
- what_is_ddd, basic_concepts, lesser_known_patterns: žádná položka NEPRAVDA / NELZE OVĚŘIT.
- Kontroly: lint-php-snippets (75 bloků, 0 chyb), check_anchors OK, check_faq_yaml OK,
  check_tonality 0 nálezů ve všech 8 změněných souborech. Nic necommitováno.

---
<!-- reports/apply-overeni-C.md -->
# Aplikace ověření – kapitoly skupiny C (11–24, ddd_ai)

Aplikovány položky NEPRAVDA / NELZE OVĚŘIT a rozpory, které zadání jmenuje. Položky PRAVDA
(O-29, O-30, O-31, O-32, O-35, O-36, O-37, O-38, O-40, O-41, O-48, O-50, O-58, O-62, O-63) nechány
beze změny, včetně nepovinných doplnění (O-32 komentář k logout routě, O-41 „peníze na cestě",
O-58 odkaz na OriginalStranglerFigApplication). Výjimky: O-43 a O-53 (viz níže).

## authorization_in_ddd.md
- **O-27** – provedeno: acl-bundle 2.4.0 (duben 2024), Symfony 4.4 až 7.x, „Symfony 8 mezi nimi není".
- **O-28** – provedeno: věta rozdělena, `Security::getAccessDecision()` „přibylo ve verzi 7.4".
- **O-33** – provedeno: do `form_login` doplněno `enable_csrf: true` s komentářem (šablona posílá
  `_csrf_token` s id `authenticate`, bez volby ho form_login nečte). Blok nemá highlights.
- **O-34** – provedeno: schema-based „Střední izolace. Výkon závisí na počtu tenantů…".

## event_sourcing.md
- **O-39** – provedeno: Brown není právník, věc konzultoval s právníkem; výhrada o šifře připsána
  Verraesovi, bez „deseti let".

## sagas.md
- **O-42** – provedeno (ověřeno v sagas.txt, oddíl 8): cascading rollbacks = savepoint navazující
  větve ztrácí platnost; pořadí kompenzací mechanismus podle datových závislostí neřídí.
- **O-43** (rozpor pivot × CancelShipment) – doplněn odstavec za Richardsonovu klasifikaci:
  klasifikace je pro selhání kroku uvnitř ságy, storno zvenčí je jiný scénář, proto
  `CancelShipment` figuruje v tabulce 14.02 (#kompenzacni-transakce).

## outbox_pattern.md
- **O-44** – provedeno na všech třech místech: „stovky až nízké tisíce zpráv za sekundu".
- **O-45** – provedeno: kroky 3 a 4 prohozeny (Inbox → relay), kotvy zůstaly podle pořadí
  (nikdo na ně neodkazuje – ověřeno grepem). Doplněna podmínka shodného `eventId` legacy
  dispatche a outbox řádku; krok 5 začíná „Až relay běží stabilně…".
- **O-46** – provedeno a ověřeno v DBAL 4.4.4 (MySQL `DATETIME`, PG `TIMESTAMP(0)`, formát
  `Y-m-d H:i:s`, introspekce fsp nečte). Komentář v migraci přepsán; aby kód seděl s textem,
  migrace teď vytváří `DATETIME` (ne `DATETIME(6)`), highlights přepočítány (50,51 → 52,53 = index),
  věta pod migrací „`DATETIME` typem `TIMESTAMPTZ`". V sekci o pořadí zpráv (#relay-ordering-heading)
  doplněno, že se sekundovou přesností shody `occurred_at` nejsou výjimkou.
  Poznámka (neřešeno, mimo nález): PG varianta s `TIMESTAMPTZ` neodpovídá tomu, co DBAL vygeneruje
  pro `datetime_immutable` (`TIMESTAMP(0) WITHOUT TIME ZONE`); tabulka sloupců i partitioned SQL
  používají TIMESTAMPTZ jako popisný typ.
- **O-47** – provedeno: partitioned tabulka má `aggregate_type`, `aggregate_id`, `status VARCHAR(16)`,
  bez DEFAULT klauzulí, pořadí sloupců jako v 15.03; pod blokem věta, že se liší jen PK
  (PostgreSQL vyžaduje sloupec partitioningu v PK).

## testing_ddd.md
- **O-49** – provedeno: Vocke tvar pyramidy drží, na názvech vrstev nezáleží, dvě pravidla
  (granularita, méně testů výš), integrační testy úzce po jednom integračním bodu.

## microservices_and_ddd.md
- **O-51** – provedeno (ověřeno: messenger CHANGELOG 8.1, AmqpReceiver 8.1 `wrap()`): docblock
  rozlišuje 8.1 (failure transport) a 8.0 (reject → jen DLX na frontě). Highlights bloku mířily
  po prodloužení docblocku na komentář; nastaveny na `TYPE_MAP` (27–30).
- **O-52** – provedeno: FAQ o BFF – downstream konzument OHS/PL, sám OHS není, samostatný kontext
  „Web Frontend"; odstraněn vnitřní rozpor.
- **O-53** (PRAVDA, nepovinné) – provedeno: „`--fetch-size` (od Symfony 8.1)", protože kniha
  jinde verzi u téhož přepínače uvádí (ES).

## ddd_pain_points.md
- **O-54** – provedeno: `Query::HINT_READ_ONLY` / `#[ORM\Entity(readOnly: true)]`, „Read-only
  EntityManager Doctrine nemá" (ověřeno v ORM 3.7.2).
- **O-55** – provedeno (ověřeno v ProxyFactory): chyba jen u zmizelého záznamu, odpojení samo ne.
- **O-56** – provedeno: `\Stripe\PaymentIntent`, adapter přes `paymentIntents->create`
  (`payment_method`, `confirm`, `error_on_requires_action`, kontrola `status`), refund přes
  `payment_intent`; pod ukázkou 3 věty o Charges API a 3D Secure. Port beze změny.
- **O-57** – provedeno: issue #10819 správci v roce 2019 uzavřeli/odmítli.

## case_study.md
- **O-59** – provedeno (ověřeno v DeduplicateMiddleware 8.1): řešení přes `eventId`, middleware
  ho nenahradí, zámek jen od odeslání do zpracování.

## cqrs.md (související, mimo seznam O-N)
- Odstavec o `DeduplicateMiddleware` tvrdil totéž co case_study („druhé doručení téže zprávy se
  v okně TTL zahodí"). Opraveno konzistentně s O-59: zámek vzniká při odeslání, opakované
  doručení přijaté zprávy (retry, pád před ACK) nezastaví. TTL 300 s ověřen v DeduplicateStamp.

## when_not_to_use_ddd.md
- **O-60** – provedeno: úvod přepsán na Khononovovu heuristiku (složitost logiky, typ subdomény
  jako vodítko; Transaction Script / Active Record / Domain Model / event-sourced), řádek
  Supporting „Transaction Script nebo Active Record; v Doctrine lehké DDD…", věta, že „lehké DDD"
  je zjednodušení knihy. Upraven i popis Khononova v literatuře („Zdroj heuristiky…").

## ddd_ai.md
- **O-61** – provedeno: popis O'Reilly článku bez ts-morph, nová položka „Extracting your software
  architecture with ts-morph" (URL ověřena, title sedí).

## Beze změny (žádná položka NEPRAVDA / NELZE OVĚŘIT)
performance_aspects (O-48 PRAVDA), migration_from_crud (O-58 PRAVDA), practical_examples
(O-62, O-63 PRAVDA), anti_patterns (žádná položka).

## Ostatní
- `modified: 2026-09-24` v 11 změněných kapitolách (authorization, event_sourcing, sagas, outbox,
  testing, microservices, ddd_pain_points, case_study, when_not_to_use_ddd, ddd_ai, cqrs).
- Kontroly: lint-php-snippets (194 bloků, 0 chyb), check_anchors OK, check_faq_yaml OK,
  check_tonality 0 nálezů ve všech 11 souborech; navíc check_duplicate_listings, use_statements,
  messenger_routing, named_arguments, property_access, static_calls, toplevel_code OK.
  Em dash v žádném ze souborů. Nic necommitováno.
