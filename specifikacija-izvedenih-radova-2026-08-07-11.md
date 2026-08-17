# Specifikacija izvedenih radova

- **Projekt:** Antikvarijat Biblos web-trgovina
- **Razdoblje pregleda:** 7. kolovoza 2026. – 11. kolovoza 2026.
- **Grana:** `newtemplate`
- **Git raspon:** `dcd78b1d` – `9137cee8`
- **Osnova za usporedbu:** paketna ponuda poslana 7. kolovoza 2026., ukupne vrijednosti 4.000 €

## 1. Sažetak opsega

U promatranom razdoblju evidentirano je 59 commitova i izmjene u 407 datoteka. Rad nije bio ograničen na stavke iz ponude: uz nove prodajne i marketinške funkcije izveden je opsežan redizajn administracije i korisničkog sučelja, dvojezični pravni sadržaj, novi sustav e-mail predložaka, nadogradnja checkouta te dodatna sigurnosna i SEO poboljšanja.

Glavne izvedene cjeline su:

- recenzije proizvoda, moderacija, provjerene kupnje i automatizirani pozivi na recenziju;
- wishlist obavijesti, ručno i grupno slanje te mjerenje klikova i konverzija;
- oporavak nedovršenih narudžbi s dva automatizirana podsjetnika;
- GLS praćenje pošiljke, sinkronizacija statusa i obavijesti kupcu;
- automatska dopuna hrvatskoga grada i poštanskog broja;
- personalizirane preporuke u korisničkom računu;
- Google prijava za postojeće korisničke račune;
- Cookie Consent i Google Consent Mode;
- novi `add_to_cart` GA4/GTM događaj povezan s novom košaricom;
- tehnička SEO i AI/crawler optimizacija, strukturirani podaci i novi sitemap sustav;
- administratorski dashboard i zaseban modul napredne prodajne statistike;
- redizajn administracije, kataloga, checkouta, korisničkog računa, e-mailova i mobilne navigacije;
- dvojezični postupak jednostranog raskida ugovora i uređivanje sadržaja stranice za otkup knjiga;
- poboljšan pregled narudžbe, povijest GLS statusa i povijest brzih izmjena proizvoda.

## 2. Usklađenost s paketnom ponudom od 4.000 €

Statusi u tablici opisuju isključivo ono što je vidljivo u Git izmjenama od 7. do 11. kolovoza. Oznaka „djelomično” nije financijska procjena niti automatski znači pola vrijednosti stavke.

| # | Stavka ponude | Cijena | Status u ovom razdoblju | Izvedeno | Preostali dio stavke |
|---:|---|---:|---|---|---|
| 1 | Recenzije proizvoda, automatizirani e-mailovi i nagrađivanje dojmova | 370 € | **Djelomično – veći dio** | Recenzije, 1–5 ocjena, moderacija, provjerena kupnja, javni prikaz, istaknute recenzije, e-mail pozivi, backfill starih narudžbi i zaštita od dvostrukog slanja. | Nije pronađen sustav nagrađivanja dojma kuponom, bodovima ili drugim benefitom. |
| 2 | Poklon bonovi i vodiči za poklone | 300 € | **Nije uočeno** | Nema novih funkcija u ovom Git rasponu. | Poklon bonovi, stanje/iskorištenje bona i vodiči za poklone. |
| 3 | Praćenje GLS/BoxNow pošiljaka i obavijesti kupcima | 380 € | **Djelomično** | GLS naljepnica i identifikatori, dohvat statusa, automatsko osvježavanje, korisnički i administratorski prikaz, povijest te jednokratni e-mail s trackingom. | BoxNow integracija i praćenje nisu implementirani. |
| 4 | Wolt Drive i proširena pravila dostave | 290 € | **Nije uočeno** | Nema nove Wolt Drive integracije. | Wolt API i proširena pravila odabira/cijene dostave. |
| 5 | Automatska dopuna grada i poštanskog broja | 90 € | **Izvedeno** | Lokalni direktorij hrvatskih mjesta, dopuna prema poštanskom broju ili gradu i integracija u checkout. | Produkcijska provjera rubnih slučajeva i održavanje popisa mjesta. |
| 6 | Oporavak nedovršenih narudžbi, promo kodovi i statistika | 290 € | **Djelomično – veći dio** | Dva podsjetnika, potpisani link za povrat košarice, obnova dostupnih artikala i checkout podataka, ručno slanje iz administracije te napredna prodajna statistika. | U ovom razdoblju nije uveden novi sustav promo kodova. |
| 7 | Napredne marketinške akcije, BOGO i gift wrap | 370 € | **Nije uočeno** | Vizualno je osvježen postojeći modul marketinških akcija. | Nova pravila promocija, BOGO i gift wrap logika nisu implementirani. |
| 8 | Obavijesti u korisničkom računu i detaljni pregled narudžbe | 180 € | **Djelomično** | Redizajn korisničkog računa, detaljniji popis narudžbi, tracking, dojmovi, preporuke i bogatiji administratorski pregled narudžbe. | Nije uveden zaseban korisnički centar obavijesti s pročitanim/nepročitanim porukama. |
| 9 | Prošireni loyalty i affiliate program | 210 € | **Nije uočeno** | Nema novih loyalty ili affiliate funkcija u ovom Git rasponu. | Bodovi, razine, nagrade, affiliate atribucija i obračun. |
| 10 | Personalizirane preporuke i uređene kolekcije proizvoda | 290 € | **Djelomično** | Preporuke na temelju prethodnih kupnji, zajedničkih narudžbi i kategorija, uz fallback na popularne proizvode. | Nije pronađen zaseban administratorski modul za ručno uređene kolekcije. |
| 11 | Wishlist analitika i kontaktiranje zainteresiranih kupaca | 140 € | **Izvedeno** | Filtriranje spremnih obavijesti, pojedinačno i grupno slanje, evidencija slanja, potpisani tracking link, klikovi, atribucijski prozor i statistika konverzija. | Automatski raspored `check:wishlist` trenutno nije registriran u scheduleru i treba odlučiti želi li se ponovno uključiti. |
| 12 | Cookie consent i Google Consent Mode | 80 € | **Izvedeno** | Dvojezični consent dijalog, nužni/analitički/marketinški izbor, promjena postavki, Consent Mode default/update te povezivanje s Google i Meta privolama. | Produkcijska provjera tagova u GTM Preview/Consent debug prikazu. |
| 13 | GA4 i Google Tag Manager e-commerce praćenje | 130 € | **Dorađeno / djelomično novo** | Dodan je `add_to_cart` događaj iz nove košarice i postojeći GTM/GA4 sloj povezan je s novim consent mehanizmom. | `view_item`, checkout i `purchase` događaji postojali su prije 7. kolovoza; treba napraviti produkcijski end-to-end audit vrijednosti i deduplikacije svih događaja. |
| 14 | SEO i tehnička optimizacija kataloga | 200 € | **Izvedeno** | HR/EN URL-ovi, canonical/hreflang, robots pravila, pravi HTTP 404, noindex privatnih i filtriranih stranica, podijeljeni sitemapovi, image sitemap, sigurne slike i breadcrumb/listing schema. | Završna validacija u Google Search Consoleu i Rich Results Testu. |
| 15 | Admin dashboard s prodajnim KPI pokazateljima | 180 € | **Izvedeno** | Dnevni, mjesečni i godišnji promet/narudžbe/prosjek artikala, grafikoni po danu/mjesecu/godini/rasponu te zasebne napredne statistike. | Produkcijska provjera statusa koji ulaze u prodajne rezultate. |
| 16 | Administratorska prijava u korisnički račun radi podrške | 90 € | **Nije uočeno** | Uvedeno je pravilno preusmjeravanje administratora u backend. | Impersonation odnosno „prijavi se kao kupac” nije implementiran. |
| 17 | Apple Pay i Google Pay prečaci u checkoutu | 120 € | **Nije uočeno** | Postojeće ikone metoda plaćanja ostaju u footeru. | Nisu uvedeni wallet prečaci ili payment-request tok u checkoutu. |
| 18 | AI optimizacija sadržaja i kataloga | 210 € | **Izvedeno** | `llms.txt`, semantički entiteti, strukturirani podaci za proizvode, autore, nakladnike, blog, FAQ, CMS, kontakt, otkup knjiga, dostavu i povrate. | Preporučena je vanjska validacija generiranog JSON-LD-a na reprezentativnom uzorku stranica. |
| 19 | Prijava i registracija putem Google računa | 80 € | **Djelomično** | Sigurna Google OIDC prijava postojećih aktivnih računa, PKCE/state/nonce, provjera potpisa i e-maila, 2FA podrška, siguran povrat u checkout i administratorske postavke. | Google prijava ne kreira novi račun, pa registracija putem Googlea nije izvedena. |

Sažetak vrijednosti prema statusu, bez preračunavanja djelomičnih stavki:

- potpuno izvedene stavke iz ponude: 900 € nominalne vrijednosti;
- djelomično izvedene ili dorađene stavke: 1.720 € nominalne vrijednosti;
- stavke bez uočenih novih funkcija u ovom razdoblju: 1.380 € nominalne vrijednosti.

## 3. Dodatni radovi izvan izričitih stavki ponude

### 3.1. Dvojezični jednostrani raskid ugovora

- uveden je puni HR/EN obrazac s pregledom prije potvrde;
- dodani su validacija, ReCaptcha, povezivanje s narudžbom i zapis dokaznih podataka;
- kupac prima potvrdu na jeziku obrasca, a administrator zasebnu obavijest;
- administrator može obraditi zahtjev, mijenjati adresu povrata i pravilo troška povrata te ponovno poslati obavijesti;
- dodane su engleske rute `/en/returns-and-complaints` i odgovarajući sadržaj u footeru.

### 3.2. Uređivanje sadržaja usluge otkupa knjiga

- sadržaj HR i EN verzije stranice može se uređivati iz administracije;
- dodani su naslov, uvodni blokovi i pomoćni tekstovi po jeziku;
- poboljšani su administratorski popis, pretraga, detalj prijave i navigacija između prijava.

### 3.3. Redizajn administratorskog sučelja

- uveden je jedinstveni vizualni sustav za dashboard, katalog, narudžbe, korisnike, marketing, postavke i widgete;
- obnovljeni su sidebar, topbar, session poruke, tablice, filteri, hero blokovi, obrasci i responsive prikaz;
- poboljšano je upravljanje fotografijama i sigurni prikaz administracijskih thumbnaila;
- uređene su stranice proizvoda, kategorija, autora, nakladnika, bloga, wishlista, narudžbi, korisnika i postavki;
- dodani su brojači spremnih wishlist obavijesti i recenzija koje čekaju moderaciju;
- editorima su ograničeni osjetljivi prodajni grafikoni i statistike.

### 3.4. Redizajn javnog kataloga i navigacije

- obnovljene su stranice kategorija, proizvoda, bloga, autora i nakladnika;
- uvedeni su responsive filteri, novi listing proizvoda, fallback prikaz bez JavaScripta i vlastita paginacija;
- dodana je nova mobilna navigacija i prilagođen header/footer;
- poboljšani su widgeti proizvoda i stranica te obrada nedostupnih slika;
- izrađene su nove 404 stranice i jasniji povrat u katalog/pretragu.

### 3.5. Redizajn košarice i checkouta

- dodan je novi modal nakon dodavanja proizvoda u košaricu;
- modal nudi nastavak kupnje ili odlazak na dovršetak narudžbe te prikazuje proizvod i stanje košarice;
- redizajnirani su mini-cart, puna košarica, koraci checkouta, pregled narudžbe, načini plaćanja i statusne stranice;
- uvedena je prijava u modalu bez gubitka checkout konteksta;
- nakon prijave ili 2FA korisnik se sigurno vraća samo na dopuštenu checkout rutu;
- gost može tijekom checkouta izraditi korisnički račun;
- spremljeni podaci i adrese bolje se prenose kroz korake checkouta.

### 3.6. Redizajn korisničkog računa

- nova početna stranica računa s pregledom kupca;
- zasebne cjeline za narudžbe, dojmove i personalizirane preporuke;
- prikaz GLS statusa i mogućnost ručnog osvježavanja vlastite pošiljke;
- pregled odobrenih, neobrađenih i odbijenih dojmova te proizvoda koji još čekaju dojam;
- zaštita korisničkih ruta od administratorskih računa.

### 3.7. Jedinstveni sustav transakcijskih e-mailova

Redizajniran je zajednički predložak i sljedeće poruke:

- zaprimljena i poslana narudžba;
- plaćena i otkazana narudžba;
- zaboravljena lozinka;
- kontaktni obrazac;
- otkup knjiga;
- potvrda i administratorska poruka za raskid ugovora;
- wishlist obavijest;
- poziv na recenziju;
- napuštena košarica;
- dostupnost GLS praćenja.

Predlošci su dvojezični gdje je primjenjivo i dijele zajednički layout, podatke narudžbe i tablicu cijena.

### 3.8. Dodatne kataloške i operativne funkcije

- proizvodima je dodano ISBN polje i indeks;
- podržani su ISBN/GTIN podaci u Product/Book strukturiranim podacima;
- uvedena je povijest brzih izmjena proizvoda kroz administratorski API;
- API rute za promjenu statusa i brzu izmjenu proizvoda dodatno su zaštićene autentikacijom i ulogama;
- poboljšani su popisi narudžbi, korisnika, proizvoda, autora i nakladnika s filtrima i optimiziranim upitima;
- dodani su indeksi za performanse narudžbi i vidljivost autora/nakladnika;
- dodan je administratorski editor izvornog SEO/kategorijskog sadržaja;
- u footeru se mogu prikazati konfigurabilna Google ocjena i broj recenzija te broj jedinstvenih kupaca.

## 4. Funkcionalna specifikacija ključnih modula

### 4.1. Recenzije proizvoda

**Javni tok**

1. Kupac može poslati ocjenu od 1 do 5, naslov i tekst dojma.
2. Slobodno poslana recenzija prolazi ReCaptcha provjeru i dobiva status `pending`.
3. Recenzija poslana iz valjanog poziva vezuje se uz narudžbu i stavku te se označava kao provjerena kupnja.
4. Za istu stavku narudžbe nije moguće spremiti dva dojma.
5. Javno se prikazuju samo odobrene recenzije.
6. Prikazuju se prosječna ocjena, broj recenzija i oznaka provjerene kupnje.

**Administracija**

- filtriranje i pretraga recenzija;
- statusi `pending`, `approved` i `rejected`;
- bilježenje vremena i administratora koji je odobrio recenziju;
- označavanje odobrene recenzije kao istaknute;
- istaknute recenzije mogu se prikazati na naslovnici/widgetu.

**Automatizirano slanje**

- poziv se šalje 30 dana nakon kvalificirajućeg trenutka narudžbe;
- najviše tri pokušaja slanja;
- jedan uspješan poziv po normaliziranoj e-mail adresi;
- potpisani link vrijedi 180 dana;
- povijesne narudžbe mogu se obrađivati kroz kontrolirani backfill, uz limit, interval, napredak, greške i otkazivanje.

### 4.2. Wishlist

1. Za isti e-mail i proizvod čuva se jedan aktivni zapis.
2. Kada proizvod postane dostupan, administrator može poslati jednu ili više odabranih obavijesti.
3. Slanje se može kontrolirati varijablom `WISHLIST_EMAILS_ENABLED` i veličinom grupe.
4. Vrijeme slanja sprema se u `sent_at`.
5. E-mail vodi preko privremeno potpisanog tracking linka na proizvod.
6. Bilježe se prvi klik i ukupan broj klikova.
7. Konverzija se pripisuje zadnjoj valjanoj obavijesti unutar 30 dana, bez dvostrukog pripisivanja iste kupljene stavke.

### 4.3. Oporavak nedovršene narudžbe

1. Obuhvaćene su samo nedovršene narudžbe nastale od 9. kolovoza 2026.
2. Prvi automatski podsjetnik šalje se nakon 60 minuta.
3. Drugi se šalje nakon 24 sata i samo ako je prvi uspješno poslan.
4. Podsjetnici se mogu poslati i ručno iz administracije, ali uvijek pravilnim redoslijedom.
5. Svako slanje ima jedinstveni zapis i zaštitu od paralelnog/dvostrukog slanja.
6. Potpisani link za oporavak vrijedi sedam dana.
7. U novu košaricu vraćaju se samo aktivni proizvodi sa zalihom, u količini koja je trenutačno dostupna.
8. Vraćaju se adresa, dostava, plaćanje, komentar i poveznica s izvornom nedovršenom narudžbom.

### 4.4. GLS praćenje pošiljke

1. Kod izrade GLS pošiljke spremaju se Parcel ID i, kada je dostupan, Parcel Number.
2. Ako Parcel Number još nije dostupan, naknadno se pronalazi kroz GLS popis pošiljaka.
3. Status se osvježava svakih 15 minuta, do 50 aktivnih pošiljaka po pokretanju.
4. Završni statusi više se ne osvježavaju.
5. Promjena statusa ulazi u povijest narudžbe.
6. Kada se prvi put pojavi broj za praćenje, kupcu se šalje samo jedan e-mail.
7. Administrator može ručno osvježiti status i ručno pokrenuti e-mail ako već nije poslan.
8. Kupac u svom računu vidi status i može osvježiti isključivo vlastitu pošiljku.

### 4.5. Google prijava

1. Funkcija se uključuje u administraciji ili preko environment postavki.
2. Client Secret se pri spremanju u bazu šifrira.
3. OAuth/OIDC tok koristi state, nonce, PKCE i vremensko ograničenje transakcije od 10 minuta.
4. Provjeravaju se Google potpis, issuer, audience, authorized party, nonce i potvrđeni e-mail.
5. Prijava je dopuštena samo postojećem aktivnom korisniku koji ima korisničke detalje.
6. Podržana je postojeća dvofaktorska autentikacija.
7. Administrator nakon prijave ide u administraciju, a kupac u korisnički račun ili natrag u dopušteni checkout korak.
8. Vanjski i nepouzdani redirect URL-ovi odbacuju se.

### 4.6. SEO, strukturirani podaci i AI crawlability

- canonical URL i HR/EN alternate/hreflang veze;
- lokalizirani URL-ovi proizvoda, kategorija, autora, nakladnika, bloga i sadržajnih stranica;
- `BookStore`, `WebSite`, `WebPage`, `Product`, `Book`, `Offer`, `AggregateRating`, `Review`, `ItemList`, `BreadcrumbList`, `BlogPosting`, `FAQPage`, `AboutPage`, `Person`, `Organization`, `Service`, `MerchantReturnPolicy` i `OfferShippingDetails` JSON-LD strukture;
- robots politika za administraciju, račun, checkout, login, tracking i recovery URL-ove;
- stvarni HTTP 404 odgovor i `noindex` za privatne/filtrirane stranice;
- sitemap indeks s odvojenim stranicama, kategorijama, proizvodima, autorima i nakladnicima;
- shardani image sitemap i uklanjanje session/cookie overhead-a sa sitemap ruta;
- sigurna normalizacija slika i thumbnaila;
- `llms.txt` vodič za AI crawlere na hrvatskom i engleskom sadržajnom modelu.

### 4.7. Administratorski KPI i statistike

**Dashboard** prikazuje dnevne, mjesečne i godišnje narudžbe, promet i prosječan broj artikala, zadnje narudžbe/prodane artikle te grafikone po danu, mjesecu, godini i proizvoljnom rasponu.

**Napredne statistike** uključuju:

- ukupan promet, broj narudžbi, broj artikala, prosječnu vrijednost narudžbe i kupce;
- usporedbu s prethodnim jednakim razdobljem;
- trend po danu ili mjesecu te heatmap po danu u tjednu i satu;
- zemlje i gradove kupaca, uključujući kartu Hrvatske;
- najbolje proizvode, kategorije, autore i nakladnike;
- wishlist interes i iznose popusta;
- nove, povratne, ponovljene, registrirane i gostujuće kupce;
- načine plaćanja, načine dostave i raspodjelu statusa narudžbi.

## 5. Podatkovne i operativne promjene

Dodane su Laravel migracije i idempotentne SQL skripte za ručni produkcijski deploy:

1. `030_fix_product_english_urls.sql`
2. `031_add_product_reviews_and_isbn.sql`
3. `032_add_wishlist_sent_at.sql`
4. `033_add_wishlist_tracking.sql`
5. `034_add_abandoned_cart_reminders.sql`
6. `035_create_product_review_backfills.sql`
7. `036_add_product_review_email_index.sql`
8. `037_add_shipping_tracking_fields_to_orders_table.sql`
9. `038_add_shipping_tracking_email_sent_at_to_orders_table.sql`
10. `039_add_is_featured_to_product_reviews.sql`

Važne nove tablice i polja:

- `products.isbn`;
- `product_reviews` i `product_review_invitations`;
- `product_review_backfills` i `product_review_backfill_items`;
- `wishlist.sent_at`, `clicked_at` i `click_count`;
- `orders.locale`, `unfinished_at` i GLS tracking polja;
- `abandoned_cart_reminders`;
- `product_reviews.is_featured`.

### Scheduler

Za produkcijski rad mora biti aktivan Laravel `schedule:run`. Trenutačno su registrirani:

- pozivi na recenziju u 10:15, 14:15 i 18:15;
- obrada review backfilla svake minute;
- obrada napuštenih košarica svake minute;
- GLS sinkronizacija svakih 15 minuta.

Svi novi poslovi imaju zaštitu od preklapanja. `check:wishlist` naredba postoji, ali nakon izmjena više nije registrirana u scheduleru.

### Environment postavke

Prije produkcije treba postaviti i provjeriti najmanje:

- `WISHLIST_EMAILS_ENABLED` i wishlist limite;
- `REVIEW_REQUEST_EMAILS_ENABLED`, odgodu, broj pokušaja i backfill ograničenja;
- `ABANDONED_CART_EMAILS_ENABLED`;
- `GOOGLE_LOGIN_ENABLED`, Client ID i Client Secret;
- `GOOGLE_REVIEWS_RATING` i `GOOGLE_REVIEWS_COUNT`;
- GLS pristupne podatke i tracking URL;
- `APP_IMAGE_DOMAIN`.

## 6. Kriteriji prihvata prije produkcije

- sve SQL skripte/migracije prolaze redoslijedom bez gubitka postojećih podataka;
- scheduler je aktivan samo na jednom produkcijskom čvoru ili koristi odgovarajuće lockove;
- testni e-mailovi za HR i EN izgledaju ispravno na mobilnom i desktop klijentu;
- recenzija se ne može poslati dvaput za istu stavku narudžbe;
- abandoned-cart i wishlist linkovi odbijaju nevažeći ili izmijenjeni potpis;
- recovery ne vraća neaktivne proizvode niti količinu veću od trenutačne zalihe;
- GLS e-mail se šalje samo jednom i tek kad postoji broj pošiljke vidljiv kupcu;
- korisnik ne može osvježiti tracking tuđe narudžbe;
- Google login ne dopušta vanjski redirect niti neprovjeren e-mail;
- 404 stranica vraća status 404, a privatne i filtrirane stranice imaju odgovarajući `noindex`;
- sitemap i image sitemap ne otvaraju session te vraćaju samo kanonske, aktivne URL-ove;
- Consent Mode je `denied` prije izbora i pravilno se ažurira nakon promjene privole;
- GTM Preview prikazuje očekivane e-commerce događaje bez duplikata;
- KPI rezultati podudaraju se s ručnim uzorkom narudžbi i dogovorenim statusima prodaje.

## 7. Poznate otvorene točke i rizici

1. **GLS tajne:** GLS korisničko ime i lozinka imaju stvarne zadane vrijednosti u `config/services.php`. Prije produkcije ih treba premjestiti isključivo u sigurne environment varijable, ukloniti zadane tajne iz repozitorija i rotirati vjerodajnice.
2. **Wishlist automatika:** naredba postoji, ali nije raspoređena u `app/Console/Kernel.php`; potrebno je potvrditi treba li ručno/grupno ili ponovno automatsko slanje.
3. **Feature toggles:** wishlist, review e-mailovi, abandoned-cart i Google login zadano su isključeni ili ovise o okruženju; uključiti ih tek nakon migracija i mail testova.
4. **BoxNow, Wolt, loyalty, affiliate, gift bonovi, BOGO, gift wrap, impersonation i wallet prečaci:** nisu dio vidljivih izmjena u ovom razdoblju.
5. **Google registracija:** trenutačni tok prijavljuje samo postojeće račune; ne stvara novog korisnika.
6. **GA4 atribucija:** osnovni događaji postojali su prije ovog razdoblja. Novi rad pokriva `add_to_cart` i consent integraciju, ali produkcijski audit cijelog funnel-a ostaje obavezan.
7. **Kompatibilnost runtimea:** projekt je na Laravelu 8; pri lokalnom pokretanju na PHP-u 8.4 pojavljuju se deprecation upozorenja iz framework/dependency sloja.

## 8. Provjera testovima

Na lokalnom PHP-u 8.4 pokrenut je cijeli PHPUnit paket:

- ukupno 152 testa i 683 provjere;
- 145 testova prolazi kada se utišaju PHP 8.4 deprecation upozorenja;
- 6 testova je preskočeno;
- 1 postojeći Browser Sessions test je označen kao rizičan jer nema assertion;
- standardni `artisan test` na PHP-u 8.4 proizvodi velik broj deprecation upozorenja iz Laravel 8/dependency sloja i u tom režimu pet HTTP testova završava statusom 500.

Zaključak provjere: funkcionalni PHPUnit paket prolazi bez stvarnih assertion kvarova kada se ukloni šum nepodržanog PHP 8.4/Laravel 8 spoja, ali za mjerodavan produkcijski rezultat testove treba ponoviti na verziji PHP-a koju koristi produkcija ili nadograditi framework/dependency sloj.

## 9. Kronologija izvedbe

### Petak, 7. kolovoza

- popravci engleskih URL-ova i lokaliziranog routinga;
- dvojezični jednostrani raskid ugovora;
- dvojezično uređivanje sadržaja otkupa knjiga;
- nadogradnja footera, sitemapova i testova pravnog toka.

### Nedjelja, 9. kolovoza

- veliki redizajn administracije i novi statistički modul;
- recenzije proizvoda, ISBN, moderacija i pozivi na recenziju;
- redizajn transakcijskih e-mailova;
- wishlist ručno/grupno slanje, klikovi i atribucija;
- opsežan SEO/JSON-LD sloj i `llms.txt`;
- napuštene košarice i recovery e-mailovi;
- Cookie Consent/Consent Mode;
- kontrolirani backfill poziva na recenzije.

### Ponedjeljak, 10. kolovoza

- redizajn kataloga, proizvoda, filtera i paginacije;
- Google OIDC prijava i administratorske postavke;
- redizajn korisničkog računa, checkouta i autentikacije;
- automatska dopuna grada/poštanskog broja;
- personalizirane preporuke;
- novi cart modal i siguran povrat u checkout nakon prijave;
- dodatni responsive i mobilni popravci kataloga.

### Utorak, 11. kolovoza

- GLS praćenje, sinkronizacija i tracking e-mailovi;
- poboljšan blog i paginacija;
- istaknute recenzije i javna stranica dojmova;
- prošireni detalji narudžbe;
- sigurna brza izmjena proizvoda s poviješću;
- editor SEO sadržaja kategorije;
- Google ocjena i broj jedinstvenih kupaca u footeru;
- završne dorade cart modala i kataloga.

## 10. Zaključak

Od 19 komercijalnih stavki paketne ponude šest je potpuno vidljivo izvedeno, sedam je izvedeno djelomično ili bitno dorađeno, a šest nema novu implementaciju u promatranom razdoblju. Istodobno je izveden znatan dodatni opseg koji nije jasno iskazan kao zasebna stavka ponude: pravni HR/EN tok, CMS sadržaja otkupa knjiga, opći redizajn administracije i javnog weba, redizajn svih ključnih e-mailova, nadogradnja checkouta i autentikacije, poboljšani kataloški alati te dodatne sigurnosne i operativne optimizacije.
