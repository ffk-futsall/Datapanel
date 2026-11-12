# Listat M3U të klientëve — version prove

Ky modul importon kanale nga një M3U dhe krijon një listë të veçantë për klientin. Kjo është një **provë**, jo një sistem i audituar ose gati për përdorim në prodhim. Përdore vetëm me përmbajtje që ke të drejtë ta rishpërndash.

## Përdorimi lokal

1. Përditëso projektin nga GitHub dhe ruaj pa ndryshime skedarin privat ekzistues `config/db.php`. Mos e dërgo në GitHub. Baza duhet të jetë konfiguruar dhe të përmbajë së paku një klient. Përdor PHP 8.1+ me `curl`, `mbstring`, `pdo_mysql` dhe `openssl`; përdoruesi i bazës duhet të ketë leje të krijojë tre tabelat e modulit.
2. Ndiz MySQL dhe panelin, kyçu si administrator dhe kliko **Listat M3U**. Fut lidhjen e ofruesit në fushën e importit në panelin lokal ose përmes HTTPS; mos e posto në GitHub ose në chat. Importi shton/përditëson kanale, por nuk heq automatikisht kanalet e vjetra. Baza dhe backup-et përmbajnë adresat burimore dhe duhet të jenë private.
3. Zgjidh klientin, kanalet dhe datën e skadimit (opsionale), pastaj **Krijo lidhjen**. Kopjo lidhjen kur shfaqet: ajo shfaqet vetëm një herë. Çelësi në link është sekret. Krijimi i një lidhjeje tjetër për të njëjtin klient çaktivizon të mëparshmen; mund ta çaktivizosh edhe veçmas.
4. Një link `127.0.0.1` është vetëm për provë në të njëjtin kompjuter, jo për klientë të tjerë. Serveri i integruar i PHP është vetëm për zhvillim dhe mund të bllokohet gjatë transmetimit. Për klientë realë nevojitet server publik HTTPS i konfiguruar, kontroll sigurie dhe test me ofruesin.

## Kufizime të rëndësishme

- Ky modul provon vetëm MPEG-TS në HTTP/HTTPS. HLS/M3U8, formate të tjera, adresa IPv6-only dhe porte të tjera përveç 80, 443, 8080 dhe 8443 nuk mbështeten. Testimi me të dhëna provë nuk garanton që burimi yt real do të funksionojë.
- Transmetimi kalon në serverin tënd: çdo shikues përdor një proces/worker dhe konsumon trafik. Një plan i zakonshëm shared hosting ose serveri lokal PHP mund të mos jetë i përshtatshëm. Kufijtë e lidhjeve të ofruesit vazhdojnë të zbatohen.
- Nuk ka ende kufizim të lidhjeve të njëkohshme ose mbrojtje kundër ndarjes së linkut. Skadimi/çaktivizimi kontrollohet për kërkesat e reja dhe periodikisht gjatë transmetimit; një lidhje ekzistuese mund të mos ndërpritet menjëherë.
- Çelësat në URL mund të shfaqen në log-et e web serverit. Kufizo aksesin dhe ruajtjen e log-eve; mos i shpërnda. Vendos HTTPS dhe kontrollo konfigurimin e proxy-t të besuar para përdorimit publik. Mos përdor linke publike mbi HTTP.
- Mos i ruaj kredencialet ose kopjet e bazës në repository. Kredencialet e ofruesit që u dërguan në chat duhen zëvendësuar. Importi nga një URL `http://` është i paenkriptuar edhe pas aktivizimit të HTTPS për panelin.
