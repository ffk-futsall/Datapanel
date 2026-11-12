# DataPanel — instalimi dhe administratori

**Para publikimit:** kjo kopje nuk përfshin të dhënat e klientëve. Aplikacioni ka nevojë për testim dhe një kontroll të plotë sigurie para përdorimit publik. Mos e ngarko `config/db.php`, fjalëkalimet ose kopjet e të dhënave në GitHub. Sigurohu që repository të ketë `.gitignore` dhe kontrollo ndryshimet përpara çdo commit-i.

## 1. Serveri dhe baza e të dhënave

- Përdor një server me PHP (PDO MySQL, JSON, cURL, sessions), MySQL/MariaDB dhe HTTPS. Vendos si dosje të aplikacionit dosjen `DataPanel` të repository-t.
- Krijo një bazë **të re, bosh** dhe një përdorues të saj. Në phpMyAdmin, zgjidh bazën dhe importo `schema.sql`. Mos e importo mbi një bazë ekzistuese pa kopje rezervë dhe pa kontrolluar përputhshmërinë.
- Në server, kopjo `config/db.example.php` si `config/db.php` dhe ndrysho `$host`, `$dbname`, `$username` dhe `$password` me të dhënat e bazës. Mbaje `db.php` vetëm në server, jo në GitHub.
- Mos e vendos skedarin `schema.sql` ose ndonjë eksport të bazës në një vend ku mund të shkarkohet nga interneti; hiqe nga dosja publike pas importit. Çaktivizo aksesin publik në `tools/` në konfigurimin e serverit. Instaluesi i vjetër në `setup.php` është çaktivizuar.

## 2. Krijo administratorin

Hyr në terminalin SSH të hostit, shko në dosjen e aplikacionit dhe ekzekuto:

```bash
php tools/create_admin.php
```

Komanda krijon llogarinë e parë vetëm nëse tabela `users` është bosh. Ajo shfaq një herë emrin `admin` dhe një fjalëkalim të përkohshëm të gjeneruar rastësisht. Ruaje privatisht; mos e dërgo në chat. Komanda nuk ndryshon llogaritë ekzistuese. Nëse terminali nuk është i disponueshëm, kërko nga hosti SSH/PHP CLI; mos përdor një instalues publik në vend të tij.

## 3. Kyçu

Hap `https://domaini-yt/login.php` (ose shto nën-dosjen e instalimit përpara `/login.php`). Përdor emrin **admin** dhe fjalëkalimin e përkohshëm. Te **Account**, ndryshoje menjëherë në një fjalëkalim të fortë me të paktën 12 karaktere. Hyrja me fjalëkalim duhet testuar pasi hosti të jetë konfiguruar; mos supozo që funksionet e tjera janë testuar.
