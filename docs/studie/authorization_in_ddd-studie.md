# Studie: Autorizace v DDD na Symfony

- **Kapitola:** `content/chapters/authorization_in_ddd.md` (č. 11, kategorie Architektura, 1101 řádků)
- **Cesta:** /autorizace-v-ddd
- **Typ kapitoly:** hybridní (definiční rámec 11.02 + narativní trace a anti-vzory)
- **Datum studie:** 2026-09-03

## 1. Mapa současné kapitoly

| Sekce | Rozsah | Co tvrdí | Zdroje | Poznámka |
|---|---|---|---|---|
| úvod + deck | 21–26 | Autorizace patří do 4 vrstev; kapitola navazuje na kap. 10 a předchází CQRS | – | Prolinkování na 5 kapitol, všechny cesty existují |
| 11.01 Tři chyby | 27–107 | Autorizace v controlleru / celá ve Voteru / na úrovni DB řádků | – | Žádný externí zdroj; tvrzení o četnosti („objevují se pravidelně") je bez opory |
| 11.02 Čtyři vrstvy | 108–125 | Edge / Use Case / Aggregate / Field; „každé rozhodnutí patří do právě jedné vrstvy" | [1] Symfony Security, [2] NIST SP 800-162, Vernon IDDD kap. 14 | Nosná myšlenka kapitoly. Vlastní taxonomie, nenapojená na standardní PEP/PDP slovník |
| 11.03 Edge | 126–181 | `access_control`, default deny, JWT vs. session; Stripe API keys jako analogie | [3] OIDC Core, [4] Stripe keys | Pokrývá jen `path` + `roles`; ostatní matchery chybí |
| 11.04 Use Case – Voter | 182–324 | 1 use case = 1 atribut; Voter nesmí fetchovat z DB ani znát doménové invarianty; volání z handleru přes `AuthorizationCheckerInterface` | – | Jádro kapitoly. Chybí `#[IsGranted]`, `AccessDecisionManager`, strategie, `CacheableVoterInterface` |
| 11.05 Async | 325–388 | Ve workeru není token → `actorId` v commandu; role se ověřují proti aktuálnímu stavu, ne ze snapshotu | – | Věcně dobrá sekce, ale ignoruje oficiální `isGrantedForUser()` ze Symfony 7.3 |
| 11.06 Aggregate-level | 389–487 | Doménový invariant patří do agregátu, hlásí se výjimkou; 403 vs. 409; end-to-end trace | – | Nejsilnější část z pohledu DDD. Bez citace, ačkoli jde o standardní argument |
| 11.07 Field-level | 488–566 | Twig `if` (riziko leaku) vs. query filter / read model | [5] OWASP A01:2021 | Řeší jen viditelnost sloupců, ne filtrování řádků v seznamech ani zápisová pole |
| 11.08 Policy-based (ABAC) | 567–696 | Od ~stovky pravidel vlastní `Policy`/`Rule`/`PolicyEvaluator` nad ExpressionLanguage; OPA jako externí engine | [2] NIST SP 800-162 | Nejspornější sekce. Reimplementuje to, co Symfony nabízí nativně |
| 11.09 Multi-tenancy | 697–806 | Row/schema/database strategie; Doctrine SQLFilter + kernel listener s prioritou 7; fail-closed se musí vyrobit | odkaz na Symfony EventDispatcher docs | Technicky správné (priorita 8 firewallu ověřena), ale bez databázové vrstvy (RLS) |
| 11.10 Testování | 807–977 | Pyramida: unit agregát, unit Voter s mock tokenem, e2e `WebTestCase`, tabulkový test policy | – | Chybí `loginUser()`, architektonický test zákazu importu Security v doméně |
| 11.11 Anti-vzory | 978–1043 | 4 anti-vzory se strukturou symptom–důsledek–náprava | – | Anti-vzory 1 a 2 duplikují 11.01 a 11.04 |
| 11.12 Shrnutí + FAQ | 1044–1091 | Rekapitulace 4 vrstev, checklist 7 bodů, 6 FAQ, callout o audit logu | – | Checklist je nejakčnější část kapitoly |
| 11.13 Další četba | 1092–1101 | 8 odkazů | – | Odkaz na Vernona vede na Amazon, ne na primární zdroj |

Kapitola má jasnou tezi a drží ji od začátku do konce: autorizační rozhodnutí má právě jedno správné místo a to místo se dá určit podle otázky, na kterou rozhodnutí odpovídá. Nejvíc prostoru dostávají Voter (143 řádků) a policy-based ABAC (130 řádků). Doménová vrstva, kde je vlastní DDD příspěvek kapitoly, dostává 99 řádků a je paradoxně méně rozpracovaná než ilustrativní ABAC vrstva, kterou si čtenář v praxi nejspíš nepostaví.

Odbytá jsou tři témata. Symfony Security komponenta je zredukovaná na `Voter` + `AuthorizationCheckerInterface`; polovina relevantního API (`#[IsGranted]`, `AccessDecisionManager`, strategie rozhodování, `Vote`, `isGrantedForUser`) se v textu nevyskytuje ani jednou. Autorizace na read straně končí u skrývání sloupců, ačkoli praktický problém je filtrování seznamů. A kanonické DDD zakotvení chybí úplně: slovní spojení „Bounded Context" se v kapitole o autorizaci neobjeví.

## 2. Kanonické zdroje k tématu

**Evans (2003, 2015).** *Domain-Driven Design* ani *DDD Reference* autorizaci nezpracovávají jako vzor. Nejde o opomenutí studie – téma v knize prostě není. Praktický důsledek pro kapitolu: nelze se odvolat na „Evans říká"; každé tvrzení o umístění autorizace musí stát na jiné opoře.

**Vernon (2013), *Implementing Domain-Driven Design*.** Vernon téma řeší strukturálně, ne jako taktický vzor. Identity & Access je u něj samostatný Bounded Context. Referenční implementace `IDDD_Samples` [12] má vedle `iddd_collaboration` a `iddd_agilepm` modul `iddd_identityaccess`, který podle README používá ORM, vystavuje RESTful klientské rozhraní a publikuje doménové události přes REST a RabbitMQ. To je podstatnější sdělení než jakýkoli konkrétní `SecurityVoter`: **autorizace je vlastní subdoména s vlastním modelem, kterou ostatní konteksty konzumují přes Open Host Service, ne rozsypaná sada `if`ů uvnitř Ordering kontextu.** Kapitola 11 tento rámec nezmiňuje, ačkoli se na Vernonovu kapitolu 14 odvolává (řádek 124).

**NIST SP 800-162** [2] – *Guide to Attribute Based Access Control (ABAC) Definition and Considerations*, Hu, Ferraiolo, Kuhn, Schnitzer, Sandlin, Miller, Scarfone, leden 2014, aktualizace srpen 2019. Definuje ABAC jako vyhodnocení atributů subjektu, objektu, operace a případně prostředí proti pravidlům. Toto je zdroj, který kapitola cituje dvakrát (řádky 124, 687) – citace sedí, ale kapitola z něj nepřebírá to nejužitečnější: funkční dekompozici na PEP (Policy Enforcement Point), PDP (Policy Decision Point), PIP (Policy Information Point) a PAP (Policy Administration Point).

**Zanzibar** [10] – Pang R. et al., *Zanzibar: Google's Consistent, Global Authorization System*, USENIX ATC '19, Renton WA. Zavádí relationship tuples, konfigurační jazyk namespace a konzistenční tokeny (zookies). Provozní čísla: biliony ACL, miliony autorizačních dotazů za sekundu, p95 latence pod 10 ms, dostupnost nad 99,999 % za tři roky provozu. Zanzibar je primární zdroj pro ReBAC a v kapitole chybí.

**Khononov (2021), Millett & Tune (2015).** Ani jedna z těchto knih autorizaci jako vzor nezpracovává. Millett & Tune se tématu dotýkají nepřímo přes Bounded Context a supporting subdomain, Khononov přes subdoménovou klasifikaci. Autorizace je typická **generic subdomain** – kupuje se (Keycloak, Auth0, OIDC provider), nemodeluje se. To zapadá do kapitoly 02 knihy a stojí za explicitní propojení: investovat modelovací úsilí do vlastního policy enginu je v drtivé většině projektů špatná alokace.

Pro kapitolu z toho plyne důležité upozornění pro autora: **neexistuje kanonický DDD zdroj, který by řekl „autorizace patří do aplikační vrstvy".** Toto tvrzení je konsenzus praxe, ne citovatelný vzor. Kapitola ho dnes podává jako danost. Poctivější je přiznat, že literatura mlčí, a argumentovat vlastní úvahou – to je zároveň silnější pozice než opřít se o citaci, která tvrzení neunese.

**OWASP Top 10 (2021), A01 Broken Access Control** [5]. Kapitola tento zdroj cituje jen v jedné tabulce (řádek 563). Přímo relevantní preventivní body: „deny by default" mimo veřejné zdroje, implementovat kontrolu jednou a znovu ji používat, modelovat kontrolu tak, aby vynucovala vlastnictví záznamu, logovat selhání, a **zahrnout funkční testy access controlu do unit a integračních testů**. Statistika kategorie: 34 mapovaných CWE, průměrná incidence 3,81 %, maximální 55,97 %, 318 487 výskytů, 19 013 CVE. Klíčová věta pro sekci 11.07: „Access control is only effective in trusted server-side code or server-less API, where the attacker cannot modify the access control check or metadata."

Za zmínku stojí, že A01 je od roku 2021 kategorie číslo jedna. Úvod k Top 10 2021 [27] to formuluje takto: Broken Access Control „moves up from the fifth position to the category with the most serious web application security risk". Kapitola tento posun neuvádí, ačkoli je to nejsilnější dostupný argument, proč téma zaslouží vlastní kapitolu.

## 3. Stav praxe a posuny

**RBAC → ABAC → ReBAC.** Kapitola končí u ABAC a implicitně naznačuje, že za ním už je jen OPA. Průmyslový posun posledních zhruba pěti let jde jinudy. OpenFGA [11] definuje ReBAC jako podmínění přístupu vztahy mezi uživateli a objekty *a mezi objekty navzájem* („uživatel může vidět dokument, pokud má přístup k jeho nadřazené složce"), a explicitně jej označuje za nadmnožinu RBAC, která pokrývá i ABAC scénáře, pokud se atributy vyjádří jako vztahy. Tatáž stránka konstatuje, že RBAC „breaks down with hierarchy, sharing, or multi-tenancy" – tedy přesně v situacích, které kapitola 11.09 řeší Doctrine filtrem.

**Zanzibar implementace.** OpenFGA je projekt CNCF, ukládá object-relation-user tuples a odpovídá na check i reverzní dotazy nad výsledným grafem [11]. SpiceDB od Authzed [13] jde stejnou cestou se schema jazykem (`.zed`), který rozlišuje *relations* (zapsané hrany) a *permissions* (počítané množiny) a podporuje sjednocení, průnik, vyloučení a „arrow" operátor pro průchod přes vazby.

**Realita PHP ekosystému je ale slabá a kapitola to má říct otevřeně.** Ověřeno přes Packagist API (3. 9. 2026): `evansims/openfga-php` má 18 636 stažení a je **označený jako abandoned**; `evansims/openfga-laravel` rovněž abandoned. Oficiální SDK OpenFGA existují pro Node.js, Go, .NET, Python a Javu [14] – PHP mezi nimi není. Pro SpiceDB žádný PHP balíček na Packagistu není. Cerbos má oficiální `cerbos/cerbos-sdk-php` (7 210 stažení), tedy funkční, ale s minimální adopcí. Praktický závěr pro Symfony projekt: ReBAC engine se integruje přes HTTP/gRPC vlastním klientem, ne přes zavedený balíček.

**Standardizace rozhraní.** OpenID Foundation AuthZEN Working Group schválila **Authorization API 1.0 jako finální specifikaci v lednu 2026** (předtím Implementer's Draft, listopad 2024) [15]. Cílem je interoperabilita mezi PEP a PDP bez ohledu na to, zda za PDP stojí Rego/OPA, XACML, IDQL nebo Zanzibar model. Pro knihu psanou v roce 2026 je to relevantní posun: Symfony Voter se stává PEP a volitelně deleguje na vzdálený PDP standardizovaným protokolem.

**Symfony ACL je mrtvá větev.** ACL byla z jádra odstraněna v Symfony 6.0 (SecurityBundle CHANGELOG: „removed `acl` configuration and related services"). `symfony/acl-bundle` má poslední vydání v2.4.0 z 24. dubna 2024 a deklaruje podporu Symfony 4.4–7.0 [16]. Pro Symfony 8 tedy použitelný není. Kapitola má „ACL" v `page_title` a v `meta_keywords`, ale v textu ACL nevysvětluje – čtenář, který přijde přes toto klíčové slovo, nedostane odpověď.

**Co komunita opustila.** Symfony ACL (viz níže) je nejzřetelnější případ, ale ne jediný. Ustoupil i vzor „autorizace v doménovém modelu", tedy předání uživatele nebo role do doménové metody. Kapitola ho správně označuje za anti-vzor 4 (řádky 1023–1042); argumentace se ale opírá jen o testovatelnost. Silnější argument je modelový: role a oprávnění jsou jazyk *jiné* subdomény. Jakmile se objeví v `Order::cancel()`, Ordering kontext mluví cizím ubiquitous language.

Naopak přibylo tvrzení, které stojí za pozornost, protože jde proti zjednodušené verzi rámce kapitoly: **část autorizačních pravidel je doménová a patří do modelu**. „Zrušit smí jen vlastník" není politika, je to invariant vztahu mezi `Order` a `CustomerId`. Rozhraní mezi vrstvami je pak jinde, než kapitola naznačuje: doména vlastní *vztahy* (vlastnictví, členství, hierarchii), aplikační vrstva vlastní *politiku nad nimi* (kdo z těch, kdo vztah mají, smí co za jakých okolností). Kapitola tuto hranici v sekci 11.06 řeší jen přes stav agregátu (status, časové okno) a vlastnictví nechává celé ve Voteru, přestože `Order` má `customerId()` a rozhodnutí umí udělat sám.

**Row-Level Security se posunula z exotiky do běžné výbavy.** PostgreSQL RLS [17] nabízí `ALTER TABLE … ENABLE ROW LEVEL SECURITY` (po zapnutí platí default-deny, pokud neexistuje politika), `CREATE POLICY … USING (…) WITH CHECK (…)`, permissive politiky kombinované přes OR a restrictive přes AND, `FORCE ROW LEVEL SECURITY` pro vynucení i vůči vlastníkovi tabulky a atribut role `BYPASSRLS`. To je přímá odpověď na varování v kapitole na řádcích 801–805: Doctrine SQLFilter neplatí na nativní SQL, RLS ano, protože sedí pod aplikací.

## 4. Symfony / PHP specifika

Aktuální stav ekosystému (symfony.com/releases, ověřeno 3. 9. 2026): stabilní je **Symfony 8.1** (květen 2026, PHP ≥ 8.4), LTS je **7.4** (listopad 2025, PHP ≥ 8.2), 6.4 je starší udržovaná LTS. Kniha cílí na Symfony 8 / PHP 8.4, což odpovídá.

**`#[IsGranted]` – v kapitole zcela chybí.** Namespace `Symfony\Component\Security\Http\Attribute\IsGranted` [1][6]. Funguje na metodě i na třídě, přijímá `subject` odkazující na argument controlleru, a dále `message`, `statusCode` a `methods`:

```php
#[IsGranted('ROLE_ADMIN')]                       // na třídě
#[IsGranted('order.cancel', 'order')]            // subject = argument $order
#[IsGranted('edit', 'post', 'Post not found', 404)]
```

Atribut `statusCode: 404` je přímý nástroj proti enumeraci zdrojů – téma, které kapitola v callout „403 vs. 409" (řádky 482–486) neotevírá.

**Výrazy a closures.** `#[IsGranted]` přijímá `Symfony\Component\ExpressionLanguage\Expression` jak pro atribut, tak pro subject [7]. Dostupné proměnné: `user`, `role_names`, `object`/`subject`, `token`, `trust_resolver`, `request`, `args`, a od Symfony 8.1 `this`. Funkce: `is_authenticated()`, `is_fully_authenticated()`, `is_remember_me()`, `is_granted()`. Od 7.3 lze hlasovat i na closure s `IsGrantedContext`. Dokumentace k výrazům přitom sama uzavírá, že pro komplexní pravidla je správným řešením **Voter, ne výrazy** [7] – což je přímý argument proti konstrukci `Rule(expression: '…')` ze sekce 11.08.

**`AccessDecisionManager` a strategie – v kapitole chybí.** Strategie: `affirmative` (výchozí – stačí jeden souhlasící volič), `consensus`, `unanimous`, `priority`; konfigurace přes `security.access_decision_manager.strategy`, `allow_if_all_abstain`, `strategy_service`, `service` [6]. Toto podkopává klíčovou větu kapitoly na řádku 110 („Vrstvy fungují jako filtry"): při výchozí `affirmative` strategii jeden souhlasící Voter přebije všechny nesouhlasící. Kdo si postaví `OrderVoter` a vedle něj `TenantVoter`, dostane při `affirmative` opak toho, co kapitola popisuje. Doporučení pro DDD projekt: `unanimous` a explicitní `allow_if_all_abstain: false`.

**Kontrola rolí uvnitř Voteru.** Dokumentace k Voterům [1] to říká explicitně: uvnitř Voteru se role kontrolují přes injektovaný `AccessDecisionManagerInterface::decide($token, ['ROLE_SUPER_ADMIN'])`, **ne** přes `Security::isGranted()`. Kapitola na řádcích 223 a 231 používá `$user->hasRole('ROLE_ADMIN')`, což obchází role hierarchy nakonfigurovanou v `security.yaml` a nutí doménový `AppUser` implementovat vlastní správu rolí.

**`CacheableVoterInterface`.** Abstraktní `Voter` jej implementuje a obě metody `supportsAttribute()` a `supportsType()` vracejí ve výchozím stavu `true` [8], takže bez override nepřinášejí žádnou optimalizaci. U seznamu s 200 položkami a pěti Votery to je 1000 zbytečných volání `supports()`.

**Novinky 7.3 / 7.4, které kapitolu mění (CHANGELOG Security Core a SecurityBundle) [9]:**

- 7.3 „Add ability for voters to explain their vote": `voteOnAttribute(…, ?Vote $vote = null)`, `Vote::addReason(string)`, čtení přes `Security::getAccessDecision(): AccessDecision`. 7.4 přidává `Vote::$extraData` a argument `$accessDecision` do `AccessDecisionStrategyInterface`.
- 7.3 „Add `UserAuthorizationCheckerInterface` to test user authorization without relying on the session": `isGrantedForUser(UserInterface $user, mixed $attribute, mixed $subject = null, ?AccessDecision $accessDecision = null): bool`.
- 7.3 hlasování na closures; 8.1 priorita voličů přes `#[AsTaggedItem(priority: …)]` v kombinaci se strategií `priority` [1].
- 6.2 deprecated a 7.0 odstraněná `Symfony\Component\Security\Core\Security`; nahrazuje ji `Symfony\Bundle\SecurityBundle\Security` s `isGranted()`, `getUser()`, `login()`, `logout()`, `getFirewallConfig()` [6]. Kapitola tuto třídu nepoužívá, takže chybu neobsahuje, ale ani čtenáře nevaruje.
- 6.0: `security.authorization_checker` a `security.token_storage` jsou privátní služby.

**`access_control` má víc než `path` a `roles`** [18]: `host`, `port`, `ips`, `methods`, `attributes`, `route`, `request_matcher`, plus `allow_if` a `requires_channel`. Uplatní se **první shodné pravidlo** a nespecifikovaný matcher odpovídá čemukoli. Past, kterou kapitola nezmiňuje: pokud jsou v jednom pravidle `roles` i `allow_if`, při výchozí `affirmative` strategii stačí splnit **jedno z nich** – vypadá to jako AND, chová se to jako OR.

**Doctrine.** `Doctrine\ORM\Query\Filter\SQLFilter::addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string`, parametry přes `setParameter()`/`getParameter()`, `setParameterList()`/`getParameterList()` pro IN [19]. Filtry se uplatní na DQL, lazy a extra-lazy kolekce a operace persisterů; **neuplatní se na entity už v identity map** – dokumentace uvádí, že obnovení vyžaduje vyčištění EntityManageru. `disable()` zahodí instanci filtru i s parametry; pro dočasné vypnutí existují `suspend()` a `restore()`, což kapitola nezmiňuje. YAML tvar v kapitole (řádky 736–744) odpovídá referenci DoctrineBundle [20], kde `enabled` je volitelné s výchozí hodnotou `false` a existuje i klíč `parameters` pro výchozí hodnoty.

**Firewall priorita.** Tvrzení kapitoly na řádku 791 je ověřeno: `Symfony\Bundle\SecurityBundle\EventListener\FirewallListener::getSubscribedEvents()` registruje `configureLogoutUrlGenerator` i `onKernelRequest` na `KernelEvents::REQUEST` s prioritou **8** [21]. Listener s prioritou 7 tedy skutečně běží po autentizaci.

**Messenger a bezpečnost.** Oficiální dokumentace Messengeru [22] neobsahuje žádnou zmínku o security tokenu, `TokenStorage` ani autorizaci uvnitř handlerů; jediné související doporučení je předávat místo entit jejich identifikátory. Řešení v sekci 11.05 tedy není v rozporu s dokumentací, ale ani se o ni nemůže opřít – od Symfony 7.3 je oficiální cestou `isGrantedForUser()`.

**ExpressionLanguage: tvrzení kapitoly na řádku 646 sedí.** Komponenta překládá `foo.bar` na přístup k veřejné property a `foo.bar()` na volání veřejné metody; na rozdíl od PropertyAccess nezkouší gettery. Poznámky kapitoly o nutnosti `subject.status.value` u backed enumu a `subject.placedAt.getTimestamp()` u data jsou tedy správné. Zůstává ale nepříjemný důsledek, který kapitola pojmenovává jen napůl: subjektem politiky musí být snapshot s veřejnými poli, tedy **další model navíc**, který se musí držet v synchronizaci s agregátem. To je skrytá cena sekce 11.08 a v tabulce výhod (řádky 684–689) chybí.

**Co v Symfony 8 pro autorizaci neexistuje.** Stojí za to říct i to: neexistuje nativní podpora pro autorizaci na read straně (filtrování seznamů), neexistuje cache autorizačních rozhodnutí napříč requesty, a neexistuje oficiální integrace s externím PDP. Voter je dobrý PEP a nic víc si nenárokuje. Kapitola, která staví celý rámec na Voterech, má tuto hranici pojmenovat.

## 5. Sporné a chybně podávané body

**S1 – „Každé rozhodnutí patří do právě jedné vrstvy" (řádek 122) vs. defense in depth.** Kapitola si sama protiřečí: na řádku 465 mluví o „dvou nezávislých bariérách" a na řádku 648 o „obraně do hloubky", zatímco anti-vzor 3 (řádky 1009–1013) duplicitu zakazuje. OWASP A01 [5] doporučuje „implement access control mechanisms once and re-use them", tedy jedno místo *definice*, ne jedno místo *vynucení*. Doporučení: přeformulovat pravidlo na „každé pravidlo má právě jedno místo definice; vynucení může být na více vrstvách, pokud se odvozuje z téhož zdroje".

**S2 – „Voter nesmí fetchovat z databáze" (řádky 319–323) jako absolutní zákaz.** Argument o duplicitním dotazu a race condition platí pro *subjekt*. Neplatí pro doplňková data, která na subjektu nejsou (členství v týmu, delegace, tenant hierarchie). Dokumentace Symfony sama ukazuje Votery s injektovanými službami [1]. Doporučení: zákaz zúžit na „Voter nenačítá subjekt, o kterém rozhoduje", a doplnit poznámku o cachování doplňkových dotazů v rámci requestu.

**S3 – Anti-vzor „autorizace na úrovni databázových řádků" (11.01) vs. doporučení Doctrine SQLFilter (11.09).** Kapitola nejprve označí filtrování v perzistentní vrstvě za chybu, o 600 řádků dál je z toho idiomatické řešení. Rozdíl je reálný – tenant izolace je jiná třída problému než use-case autorizace – ale text ho nikde nepojmenuje. Doporučení: v 11.09 explicitně navázat na 11.01 jednou větou (tenant je *kontext dotazu*, ne autorizační rozhodnutí o akci).

**S4 – Vlastní `Policy`/`Rule` nad ExpressionLanguage (11.08) jako doporučená cesta.** Tři nezávislé námitky. Dokumentace k výrazům [7] doporučuje pro komplexní pravidla Votery, ne výrazy. Symfony od 7.3 nabízí `Vote::addReason()` a `Security::getAccessDecision()` [9], takže hlavní deklarovaná výhoda vlastního evaluátoru („vrací, které pravidlo selhalo", řádek 686) už není důvodem psát vlastní vrstvu. A pravidla ve stringu nevidí statická analýza, což kapitola sama přiznává na řádku 646. Doporučení: sekci ponechat jako *ilustraci ABAC modelu*, ale výslovně uvést, že v Symfony 8 se totéž postaví z Voterů s `Vote` reasons, a externí engine (OPA, Cerbos) přichází na řadu až tehdy, když policy musí žít mimo aplikaci.

**S5 – Práh „zhruba stovka pravidel" (řádky 569, 1088).** Číslo se v kapitole opakuje třikrát a nemá zdroj. Doporučení: nahradit kvalitativním kritériem (policy musí být čitelná pro nevývojáře; policy se mění jindy než kód; policy sdílí víc aplikací) nebo číslo označit jako vlastní zkušenostní odhad.

**S6 – 403 vs. 409 bez třetí možnosti.** Callout na řádcích 482–486 staví jen tyto dvě volby. V praxi je běžné vracet **404** místo 403, aby se zabránilo enumeraci cizích identifikátorů; Symfony to podporuje přímo přes `#[IsGranted(…, statusCode: 404)]` [1]. Doporučení: doplnit třetí větev s trade-offem (bezpečnost vs. srozumitelnost chyby).

**S7 – `App\Identity\Domain\AppUser` jako entity user provider (řádek 136) vs. anti-vzor 4 (řádky 1023–1042) a FAQ.** Kapitola zakazuje závislost domény na Symfony Security, ale v `security.yaml` použije doménovou třídu jako uživatelského providera – což vyžaduje, aby implementovala `Symfony\Component\Security\Core\User\UserInterface`. Noback [23] argumentuje pro oddělený `SecurityUser` třemi důvody: doména patří za port; security user je read model, ne write model; obě třídy mají jiný důvod ke změně. Doporučení: v security.yaml použít `App\Identity\Infrastructure\Security\SecurityUser` a v aplikační vrstvě mapovat na `CustomerId`. Toto je vnitřní rozpor kapitoly, ne jen stylová preference.

**S8 – Voter jako jediná odpověď na read stranu.** Voter odpovídá na otázku „smí tento uživatel tento objekt?" (Zanzibar `Check`). Seznam 10 000 objednávek se takto autorizovat nedá – potřebný je opačný dotaz „které objekty smí?" (`ListObjects`). Kapitola tento rozdíl neotevírá vůbec, ačkoli 11.07 se read strany dotýká. To je nejzávažnější praktická mezera celého rámce.

**S9 – Vlastnictví ve Voteru, nebo v agregátu?** Kapitola dává jednoznačnou heuristiku (řádky 393–397): „uživatel + use case + entita" do Voteru, „stav agregátu + doménové pravidlo" do agregátu. Pravidlo „jen vlastník smí zrušit" spadne podle této heuristiky do Voteru – a přesně to dělá `OrderVoter::canCancel()` na řádcích 234–237. Jenže vlastnictví je vztah, který agregát zná a který nepřestává platit, když se command zpracuje asynchronně. Kapitola si to sama uvědomuje: async handler na řádcích 371–375 kontrolu vlastnictví duplikuje ručně, protože Voter tam nedosáhne. Tato duplicita je přitom podle anti-vzoru 3 zakázaná. Doporučení: buď heuristiku upřesnit (vztahové invarianty patří k agregátu, atributová politika do Voteru), nebo výslovně přiznat, že vlastnictví je hraniční případ a proč se v kapitole řeší na obou místech.

**S10 – `is_granted()` v Twigu nad seznamem.** Kapitola ukazuje `is_granted()` v šabloně detailu, ale nezmiňuje, co se stane v `{% for %}` cyklu: pro každý řádek proběhne rozhodnutí přes všechny registrované Votery. Bez override `supportsAttribute()`/`supportsType()` je to N × počet Voterů volání `supports()` [8]. Doporučení: doplnit varování k sekci 11.07 spolu s `CacheableVoterInterface` (viz G12).

**S11 – Dekorace `security.authorization_checker` (callout na řádcích 691–695).** Kapitola doporučuje `#[AsDecorator(decorates: 'security.authorization_checker')]` pro audit log. Tato služba je od Symfony 6.0 privátní [9]. Dekorace privátní služby v Symfony funguje, ale mění se tím vyžadované zacházení a v aplikaci se pak checker musí injektovat přes typehint, ne přes `$container->get()`. Doporučení: ověřit na živé aplikaci a v textu upřesnit; alternativou je dekorovat `Symfony\Bundle\SecurityBundle\Security` nebo použít `AccessDecisionStrategyInterface` s `$accessDecision`, který od 7.4 nese `Vote::$extraData` a je pro audit vhodnější, protože vidí i jednotlivé hlasy.

## 6. Gap analýza vůči kapitole

| # | Typ | Místo | Nález | Doporučení |
|---|-----|-------|-------|------------|
| G1 | chybí | sekce 11.04, celá | `#[IsGranted]` se v kapitole nevyskytuje ani jednou (ověřeno grepem). Chybí i `denyAccessUnlessGranted()` a `#[CurrentUser]` | Doplnit podsekci s `#[IsGranted]` na metodě i třídě, `subject`, `statusCode`, `methods`; vysvětlit vztah k volání v handleru |
| G2 | chybí | `authorization_in_ddd.md:110`, 122 | `AccessDecisionManager` a strategie (`affirmative` výchozí / `consensus` / `unanimous` / `priority`) nezmíněny. Metafora „vrstvy jako filtry" při výchozí strategii neplatí | Nová podsekce ~25 řádků; doporučit `unanimous` + `allow_if_all_abstain: false` pro DDD projekt |
| G3 | zastaralé | `authorization_in_ddd.md:686` | Tvrzení, že vlastní `PolicyEvaluator` je cesta k „které pravidlo selhalo". Symfony 7.3 to má nativně (`Vote::addReason`, `Security::getAccessDecision`) | Přepsat argument; ukázat Voter s `?Vote $vote` parametrem |
| G4 | zastaralé | `authorization_in_ddd.md:327–329` | „Ve workeru token neexistuje → porovnávejte `actorId` ručně". Symfony 7.3 nabízí `UserAuthorizationCheckerInterface::isGrantedForUser()` | Doplnit oficiální variantu vedle `actorId` vzoru a vysvětlit, kdy který |
| G5 | chybí | sekce 11.08 | ReBAC, Zanzibar, OpenFGA, SpiceDB nejsou v kapitole ani jednou. Kapitola končí u ABAC/OPA | Nová sekce ~45 řádků: ReBAC jako třetí model, Zanzibar jako primární zdroj, a poctivá poznámka o slabé podpoře v PHP |
| G6 | chybí | sekce 11.09 | PostgreSQL Row-Level Security nezmíněna, ačkoli řeší přesně tu díru, před kterou kapitola varuje na řádcích 801–805 (nativní SQL) | Doplnit ~25 řádků: `ENABLE`/`FORCE ROW LEVEL SECURITY`, `CREATE POLICY`, `current_setting()`, `BYPASSRLS`; srovnat s Doctrine filtrem |
| G7 | chybí | sekce 11.07 | Field-level řeší jen sloupce. Chybí filtrování řádků v seznamech („které objekty smí vidět") i autorizace zapisovaných polí | Rozšířit 11.07 nebo přidat podsekci ~30 řádků o `Check` vs. `ListObjects` problému |
| G8 | chybí | celá kapitola | Autorizace jako Bounded Context / Identity & Access kontext (Vernon, `iddd_identityaccess`). Kapitola o DDD nepoužije termín „Bounded Context" | Doplnit do 11.02 nebo jako novou úvodní podsekci ~20 řádků; navázat na kap. 03 Context Mapping |
| G9 | nepodložené | `authorization_in_ddd.md:223`, 231 | `$user->hasRole('ROLE_ADMIN')` uvnitř Voteru obchází role hierarchy; dokumentace Symfony doporučuje `AccessDecisionManagerInterface::decide()` | Opravit ukázku Voteru; přidat větu proč |
| G10 | sporné | `authorization_in_ddd.md:136` vs. 1023–1042 | Doménová třída `App\Identity\Domain\AppUser` použitá jako entity user provider musí implementovat Symfony `UserInterface` – rozpor s anti-vzorem 4 a FAQ | Zavést `SecurityUser` v Infrastructure; sjednotit napříč všemi ukázkami kapitoly |
| G11 | mělké | sekce 11.03 | Edge pokrývá jen `path` + `roles`. Chybí `methods`, `ips`, `host`, `route`, `request_matcher`, `allow_if`, `requires_channel` a past „roles + allow_if = OR" | Rozšířit o ~15 řádků, hlavně o past s `allow_if` |
| G12 | chybí | sekce 11.04 | `CacheableVoterInterface` (`supportsAttribute`, `supportsType`) – výchozí implementace vrací `true`, takže bez override žádná optimalizace | Doplnit do „tří implementačních detailů" (bude jich pět) |
| G13 | chybí | sekce 11.08 nebo 11.02 | Standardní slovník PEP / PDP / PIP / PAP z NIST SP 800-162, který kapitola cituje, ale nepoužívá; plus AuthZEN Authorization API 1.0 (finální, leden 2026) | Namapovat 4 vrstvy kapitoly na PEP/PDP; AuthZEN zmínit jednou větou u externího enginu |
| G14 | chybí | `page_title`, `meta_keywords`, sekce 11.13 | ACL je v SEO metadatech, ale v textu nikde. `symfony/acl-bundle` končí u Symfony 7.0, ACL odstraněna z jádra v 6.0 | Buď jeden odstavec „proč ne Symfony ACL", nebo ACL vyškrtnout z metadat |
| G15 | mělké | sekce 11.10 | Chybí `KernelBrowser::loginUser()` pro e2e testy a architektonický test zakazující import `Symfony\Component\Security` v `*\Domain\*` | Doplnit ~20 řádků; architektonický test navázat na kap. 17 |
| G16 | nadbytečné | `authorization_in_ddd.md:978–1000` | Anti-vzory 1 a 2 doslova opakují 11.01 a callout na řádcích 319–323 | Zkrátit na odkazy; ušetřené místo dát G5 nebo G7 |
| G17 | nepodložené | `authorization_in_ddd.md:569`, 1088 | Práh „zhruba stovka pravidel" bez zdroje, opakovaný třikrát | Nahradit kvalitativním kritériem nebo označit jako autorský odhad |
| G18 | mělké | `authorization_in_ddd.md:482–486` | 403 vs. 409 bez varianty 404 proti enumeraci zdrojů | Doplnit třetí větev + odkaz na `#[IsGranted(…, statusCode: 404)]` |
| G19 | chybí | sekce 11.09 | Doctrine `suspend()`/`restore()` pro dočasné vypnutí filtru (`disable()` zahodí i parametry) – praktická past při migracích a admin dotazech | Doplnit dvě věty do callout na řádcích 795–799 |
| G20 | mělké | `authorization_in_ddd.md:1092–1101` | „Další četba" odkazuje na Vernonovu knihu přes Amazon a chybí primární zdroje k ReBAC | Nahradit bibliografickým záznamem; doplnit Zanzibar paper, OpenFGA, NIST |
| G21 | sporné | `authorization_in_ddd.md:393–397` vs. 371–375 | Heuristika posílá vlastnictví do Voteru, ale async handler ho kontroluje znovu ručně – duplicita, kterou anti-vzor 3 zakazuje (S9) | Upřesnit heuristiku: vztahové invarianty k agregátu, atributová politika do Voteru |
| G22 | chybí | sekce 11.07, `authorization_in_ddd.md:498–516` | `is_granted()` v `{% for %}` cyklu spouští rozhodovací proces pro každý řádek; bez `CacheableVoterInterface` je to N × počet Voterů (S10) | Doplnit varování ~8 řádků, provázat s G12 |
| G23 | sporné | `authorization_in_ddd.md:691–695` | Dekorace `security.authorization_checker`, která je od Symfony 6.0 privátní služba; pro audit je vhodnější `AccessDecisionStrategyInterface` s přístupem k jednotlivým `Vote` (S11) | Ověřit na živé aplikaci; nabídnout alternativu přes strategii nebo `Vote::$extraData` |
| G24 | mělké | `authorization_in_ddd.md:684–689` | Tabulka výhod policy přístupu neuvádí jeho hlavní cenu: subjektem musí být snapshot s veřejnými poli, tedy další model k udržování | Doplnit řádek o nákladech do výčtu |
| G25 | chybí | sekce 11.01 nebo úvod | Chybí důvod, proč téma zaslouží kapitolu: OWASP posunul Broken Access Control z 5. místa (2017) na 1. (2021) | Jedna věta se zdrojem [27] do úvodu |

## 7. Doporučení k přepisu

**P1-1 – Doplnit `#[IsGranted]` a `AccessDecisionManager` do sekce 11.04 (G1, G2).**
Kniha o Symfony 8 nemůže mít kapitolu o autorizaci, kde chybí nejpoužívanější autorizační atribut frameworku. `AccessDecisionManager` je závažnější: výchozí strategie `affirmative` přímo popírá metaforu „vrstvy jako filtry" z řádku 110, jakmile má aplikace víc než jeden Voter. Bez toho je kapitola nejen neúplná, ale v jednom místě i zavádějící. *Odhad: nová podsekce ~40 řádků v 11.04 + oprava dvou vět v 11.02.*

**P1-2 – Přepsat sekci 11.08 kolem Symfony 7.3+ API místo vlastního evaluátoru (G3, S4).**
Hlavní argument pro vlastní `Policy`/`Rule` byl „víme, které pravidlo selhalo". `Vote::addReason()` a `Security::getAccessDecision()` to dnes dělají nativně a bez stringových výrazů, které nevidí PHPStan. Sekce se nemá smazat – ABAC model je správně vysvětlený – ale doporučení na konci se musí obrátit. *Odhad: přepis sekce 11.08, zkrácení o ~30 řádků, přidání ukázky Voteru s `Vote`.*

**P1-3 – Doplnit `isGrantedForUser()` do sekce 11.05 (G4).**
Sekce je logicky správná, ale od Symfony 7.3 existuje `UserAuthorizationCheckerInterface`, který umožní zavolat tentýž Voter ve workeru proti rekonstruovanému uživateli. Vzor „actorId v commandu" zůstává platný pro čistou doménovou identitu; obě varianty mají vedle sebe stát s jasným kritériem volby. *Odhad: přepis ~20 řádků v 11.05 + jedna ukázka.*

**P1-4 – Odstranit rozpor kolem `AppUser` (G10, S7).**
`App\Identity\Domain\AppUser` je v `security.yaml` jako entity provider a zároveň kapitola v anti-vzoru 4 a ve FAQ zakazuje závislost domény na Symfony Security. Jedno z toho musí padnout. Doporučená varianta: zavést `SecurityUser` v Infrastructure a mapovat na `CustomerId`, s odkazem na Nobackův článek. Zásah je průřezový – dotkne se ukázek v 11.03, 11.04, 11.07 a 11.10. *Odhad: oprava ~8 ukázek, ~25 řádků textu.*

**P1-5 – Nová sekce o ReBAC a Zanzibaru (G5).**
Kapitola tvrdí, že za ABAC už je jen OPA. Za posledních pět let se ale těžiště posunulo k relationship-based modelům. Sekce má být krátká a poctivá: co Zanzibar řeší, proč OpenFGA a SpiceDB existují, **a že PHP klient prakticky není** – `evansims/openfga-php` je opuštěný, oficiální SDK pro PHP neexistuje, Cerbos má SDK s minimální adopcí. To je pro čtenáře užitečnější než nadšený popis. *Odhad: nová sekce 11.09 ~45 řádků, přečíslování následujících.*

**P1-6 – Doplnit filtrování seznamů do 11.07 (G7, S8).**
Voter odpovídá na „smí tento objekt?". Endpoint se seznamem potřebuje „které objekty smí?". Tuto asymetrii kapitola neotevírá, přitom je to první věc, na kterou tým narazí den po zavedení Voterů. *Odhad: nová podsekce v 11.07 ~30 řádků.*

**P2-1 – Doplnit PostgreSQL RLS do 11.09 (G6).**
Kapitola sama varuje, že Doctrine filter neplatí na nativní SQL, Redis ani externí API, a nechá čtenáře bez odpovědi. RLS je ta odpověď pro databázovou část. Zároveň zapadá do argumentace o fail-closed: RLS má po zapnutí default-deny, na rozdíl od SQLFilteru. *Odhad: ~25 řádků + jedna SQL ukázka.*

**P2-2 – Zakotvit autorizaci do DDD strategie (G8).**
Kapitola je z 90 % Symfony a z 10 % DDD. Vernonův Identity & Access jako samostatný kontext s Open Host Service dá kapitole to, co jí jako kapitole knihy o DDD chybí, a napojí ji na kapitolu 03. *Odhad: ~20 řádků na začátek 11.02.*

**P2-3 – Opravit `hasRole()` ve Voteru a doplnit `CacheableVoterInterface` (G9, G12).**
Obojí jsou konkrétní, ověřená doporučení z dokumentace Symfony a obojí se vejde do stávající struktury „implementačních detailů". *Odhad: oprava dvou řádků ukázky + ~12 řádků textu.*

**P2-4 – Rozšířit edge sekci a doplnit past `roles` + `allow_if` (G11).**
První matchující pravidlo vyhrává a nespecifikovaný matcher odpovídá čemukoli; kombinace `roles` a `allow_if` se při výchozí strategii chová jako OR. Obojí jsou reálné zdroje děr v produkci. *Odhad: ~15 řádků v 11.03.*

**P2-5 – Doplnit testovací mezery (G15).**
`loginUser()` v `WebTestCase` a architektonický test zakazující `Symfony\Component\Security` v doménových namespacech. Druhý bod je přímé vynucení anti-vzoru 4 a napojí kapitolu na kap. 17. *Odhad: ~20 řádků v 11.10.*

**P2-6 – Vyřešit ACL v metadatech (G14).**
`page_title` slibuje „ACL na agregátu", text ACL nevysvětluje a Symfony ACL pro verzi 8 neexistuje. Buď jeden odstavec o tom, proč ACL v Symfony skončila, nebo úprava metadat. *Odhad: ~10 řádků, nebo oprava dvou řádků frontmatteru.*

**P3-1 – Zkrátit sekci 11.11 (G16).** Anti-vzory 1 a 2 duplikují text z 11.01 a 11.04; zkrácení na křížové odkazy uvolní ~35 řádků. *Odhad: přepis 11.11.*

**P3-2 – Doplnit 404 variantu k 403/409 (G18) a `suspend()`/`restore()` (G19).** Dvě krátké vsuvky do stávajících calloutů. *Odhad: ~8 řádků celkem.*

**P3-3 – Nahradit práh „stovka pravidel" (G17) a opravit „Další četbu" (G13, G20).** PEP/PDP slovník, AuthZEN, Zanzibar paper, bibliografický záznam Vernona místo odkazu na Amazon. *Odhad: ~12 řádků.*

## 8. Otevřené otázky pro autora

1. **Rozsah kapitoly.** Kapitola má 1101 řádků a doporučení P1 přidávají zhruba 200. Má se kapitola rozrůst, nebo se má ABAC sekce 11.08 seškrtat na polovinu a uvolnit místo pro ReBAC a filtrování seznamů?
2. **Kolik prostoru dát ReBAC.** V PHP ekosystému není použitelné SDK. Má to být plnohodnotná sekce s ukázkou HTTP klienta, nebo dvě stránky konceptu s explicitním „v PHP si to zatím napíšete sami"?
3. **`SecurityUser` napříč knihou.** Oprava G10 se dotkne i kapitoly 10 a praktických příkladů, pokud tam `AppUser` figuruje ve stejné roli. Sjednotit napříč knihou, nebo řešit jen lokálně v kapitole 11?
4. **Vlastní `Policy`/`Rule` vrstva.** Zůstane jako doporučený vzor, nebo se přeznačí na ilustraci principu s doporučením „v Symfony 8 stavte z Voterů"? Tato volba mění vyznění celé sekce 11.08.
5. **Verzování API.** Kniha cílí na Symfony 8, ale klíčové funkce (`Vote`, `isGrantedForUser`) přišly v 7.3. Uvádět „od Symfony 7.3", nebo psát bez verzí, protože 8.x je má samozřejmě?
6. **Autorizace jako Bounded Context.** Patří toto téma sem, nebo do kapitoly 03 (Context Mapping) s odkazem odtud? Riziko duplicity.
7. **Vztah k CQRS (kap. 12).** Úvod kapitoly slibuje, že integraci do Command Handleru ukáže kapitola 12. Ověřit, zda to tam skutečně je – v osnově kapitoly 12 se autorizace neobjevuje.

## 9. Bibliografie

### Ověřené zdroje

`[1] Symfony – How to Use Voters to Check User Permissions. symfony.com/doc/current/security/voters.html (přístup 2026-09-03)`
`[2] Hu V., Ferraiolo D., Kuhn R., Schnitzer A., Sandlin K., Miller R., Scarfone K. – NIST SP 800-162: Guide to Attribute Based Access Control (ABAC) Definition and Considerations, leden 2014, aktualizace 2019-08-02. csrc.nist.gov/pubs/sp/800/162/upd2/final (přístup 2026-09-03)`
`[3] OpenID Foundation – OpenID Connect Core 1.0. openid.net/specs/openid-connect-core-1_0.html (odkaz z kapitoly, formálně existující)`
`[4] Stripe – API keys. stripe.com/docs/keys (odkaz z kapitoly)`
`[5] OWASP – Top 10 2021, A01:2021 Broken Access Control. owasp.org/Top10/2021/A01_2021-Broken_Access_Control/ (přístup 2026-09-03)`
`[6] Symfony – Security. symfony.com/doc/current/security.html (přístup 2026-09-03)`
`[7] Symfony – Using Expressions in Security Access Controls. symfony.com/doc/current/security/expressions.html (přístup 2026-09-03)`
`[8] symfony/symfony – Component/Security/Core/Authorization/Voter/Voter.php, větev 7.4. github.com/symfony/symfony (přístup 2026-09-03)`
`[9] symfony/symfony – CHANGELOG.md komponenty Security/Core a SecurityBundle, větev 7.4 (přístup 2026-09-03)`
`[10] Pang R. et al. – Zanzibar: Google's Consistent, Global Authorization System. USENIX ATC '19, Renton WA, 2019.`
`[11] OpenFGA – Authorization Concepts. openfga.dev/docs/authorization-concepts (přístup 2026-09-03)`
`[12] Vernon V. – IDDD_Samples, referenční implementace k Implementing Domain-Driven Design (2013). github.com/VaughnVernon/IDDD_Samples (přístup 2026-09-03)`
`[13] Authzed – SpiceDB Schema Language. authzed.com/docs/spicedb/concepts/schema (přístup 2026-09-03)`
`[14] OpenFGA – Install SDK Client. openfga.dev/docs/getting-started/install-sdk (přístup 2026-09-03)`
`[15] OpenID Foundation – AuthZEN Working Group, Authorization API 1.0 (Final Specification, leden 2026). openid.net/wg/authzen/ (přístup 2026-09-03)`
`[16] Packagist – symfony/acl-bundle, v2.4.0, 2024-04-24, podpora Symfony 4.4–7.0. packagist.org/packages/symfony/acl-bundle (přístup 2026-09-03)`
`[17] PostgreSQL – Row Security Policies. postgresql.org/docs/current/ddl-rowsecurity.html (přístup 2026-09-03)`
`[18] Symfony – How Does the Security access_control Work? symfony.com/doc/current/security/access_control.html (přístup 2026-09-03)`
`[19] Doctrine – Filters. doctrine-project.org/projects/doctrine-orm/en/current/reference/filters.html (přístup 2026-09-03)`
`[20] DoctrineBundle – Configuration Reference. symfony.com/bundles/DoctrineBundle/current/configuration.html (přístup 2026-09-03)`
`[21] symfony/symfony – Bundle/SecurityBundle/EventListener/FirewallListener.php a Component/Security/Http/Firewall.php, větev 7.4 (přístup 2026-09-03)`
`[22] Symfony – Messenger: Sync & Queued Message Handling. symfony.com/doc/current/messenger.html (přístup 2026-09-03)`
`[23] Noback M. – Decoupling your security user from your user model, 2022-07. matthiasnoback.nl/2022/07/decoupling-your-security-user-from-your-user-model/ (přístup 2026-09-03)`
`[24] Symfony – Releases. symfony.com/releases (přístup 2026-09-03)`
`[25] Packagist API – dotazy na balíčky openfga, cerbos, authzed (packagist.org/search.json, přístup 2026-09-03)`
`[26] Open Policy Agent – Documentation. openpolicyagent.org/docs (přístup 2026-09-03)`
`[27] OWASP – Top 10 2021, Introduction. owasp.org/Top10/2021/A00_2021_Introduction/ (přístup 2026-09-03)`

### Neověřené / nedohledané

- **Vernon V., *IDDD* (2013), kap. 14 „Application“ – ČÁSTEČNĚ DOVĚŘENO 2026-09-04.** Název i téma kapitoly sedí: kap. 14 se jmenuje *Application* a obsahuje sekce *User Interface* (s. 512), *Rendering Domain Objects* (s. 512) a *Application Services* (s. 521). Autorizace tam je – Vernon Application Services popisuje jako místo, které koordinuje úlohy use case, řídí transakce a **prosazuje potřebná bezpečnostní oprávnění**. Plný text kapitoly zůstává za paywallem (oreilly.com vrací 403), takže doslovné znění neověřeno.

  **Zbývající výhrada je k formulaci, ne k existenci zdroje.** Kapitola 11 na ř. 124 slibuje „praktický pohled na **vrstvení** autorizace v doménové aplikaci“. Vernon ale v kap. 14 autorizaci umisťuje do **jedné** vrstvy (Application Services); vícevrstvý model, který je nosnou myšlenkou kapitoly 11, u něj nepochází odtud. Jeho vlastní strukturální odpověď je samostatný Identity & Access kontext (viz sekce 2 této studie).

  **Doporučení:** citaci ponechat, ale přeformulovat na to, co Vernon skutečně říká – že autorizační kontrola patří do Application Services. Tvrzení o „vrstvení“ nechat jako autorské, nebo je opřít o [1] a [2], které vícevrstvý přístup dokládají.
- **Chování Doctrine SQLFilteru u one-to-one asociací na neowning straně.** V komunitě se traduje, že se filtr neuplatní. V aktuální dokumentaci [19] ani v sekci limitations-and-known-issues se tato výjimka neuvádí. Chce ověřit experimentem proti Doctrine ORM 3 dřív, než se do kapitoly cokoli takového napíše.
- **Přesné znění funkční dekompozice PEP / PDP / PIP / PAP v NIST SP 800-162.** Landing page publikace [2] tyto komponenty neuvádí; jsou v plném PDF (`nvlpubs.nist.gov/nistpubs/specialpublications/NIST.SP.800-162.pdf`), které se v rámci studie nepodařilo načíst. Před použitím slovníku v kapitole ověřit v PDF.
- **Statistiky adopce OPA / Cerbos v PHP projektech.** Nedohledáno; k dispozici jsou jen počty stažení z Packagistu [25], které o produkčním nasazení nevypovídají.
- **Existence sekce o autorizaci v kapitole 12 (CQRS).** Úvod kapitoly 11 (řádek 21) slibuje, že integraci do Command Handleru ukáže kapitola o CQRS. V osnově `content/chapters/cqrs.md` se odpovídající sekce nenašla – ověřit ručně a případně slib upravit.
- **Dekorace privátní služby `security.authorization_checker` v Symfony 8.** Že je služba od 6.0 privátní, je ověřeno z CHANGELOGu SecurityBundle [9]. Že `#[AsDecorator]` na ni bez dalšího zásahu funguje, ověřeno nebylo – vyžaduje test na živé aplikaci se Symfony 8.1 (viz G23).
- **`Vote::getReasons()` a veřejné API třídy `Vote`.** Načtení zdroje vrátilo jen část třídy: potvrzeny jsou vlastnosti `$voter`, `$result`, `$reasons`, `$extraData` a metoda `addReason(string): void` [8][9]. Konstruktor, statické tovární metody a getter pro reasons se ověřit nepodařilo. Před psaním ukázky do kapitoly ověřit proti nainstalované verzi.
- **Chování `Symfony\Component\Security\Http\Attribute\IsGranted` s asynchronním busem.** Zda a jak se `#[IsGranted]` snese s controllerem, který jen odešle command na bus, není v dokumentaci řešeno. Pro doporučení P1-1 je to podstatná otázka – ověřit experimentem.
- **Tvrzení kapitoly o četnosti tří chyb (řádky 27–29) a o typických volbách multi-tenancy strategie podle velikosti firmy (řádek 705).** Obojí jsou zkušenostní tvrzení bez dohledatelných dat. Buď je označit jako autorský odhad, nebo najít oporu (např. průzkum mezi Symfony týmy) – v rámci studie se nic použitelného nenašlo.
