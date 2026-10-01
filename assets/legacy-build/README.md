# Assety předchozích buildů

Cloudflare drží HTML stránek v cache i několik dní a stará HTML odkazují na
CSS/JS s hashem předchozího buildu. FTP deploy ale soubory, které v novém buildu
nejsou, ze serveru smaže – cachované stránky pak zůstanou bez stylů.

CI (`.github/workflows/deploy.yml`, krok „Keep previous build assets“) proto
po `vite build` nakopíruje soubory z tohoto adresáře do `public/build/assets/`.
Po změně CSS/JS sem přidejte assety z buildu, který byl nasazený předtím
(stačí posledních pár verzí; staré lze mazat, jakmile cache Cloudflare vyprší
nebo se promaže).
