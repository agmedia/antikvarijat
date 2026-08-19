-- SEO content refresh for category landing pages.
-- Source reviewed against the 2026-08-19 production dump.
-- Run in phpMyAdmin or with the MySQL client on the production server.

SET NAMES utf8mb4;
USE `d17659_biblos2`;

-- One-time rollback copy of only the rows changed by this script.
CREATE TABLE IF NOT EXISTS `categories_seo_backup_20260819` LIKE `categories`;
INSERT IGNORE INTO `categories_seo_backup_20260819`
SELECT *
FROM `categories`
WHERE `id` IN (3,4,10,11,14,15,17,18,21,26,27,28,30,35,38,43,48,49,52,61,64,65,66,68,69,70,71,73);

START TRANSACTION;

UPDATE `categories`
SET
  `meta_title` = 'Hrvatske rijetke knjige 18. stoljeća | Biblos',
  `meta_title_en` = 'Rare Croatian books from the 18th century | Biblos',
  `meta_description` = 'Otkrijte rijetka hrvatska izdanja iz 18. stoljeća: povijesna, znanstvena, vjerska i književna djela u ponudi Antikvarijata Biblos.',
  `meta_description_en` = 'Explore rare Croatian 18th-century editions, including historical, scientific, religious and literary works selected by Antikvarijat Biblos.',
  `description` = '<h2>Rijetke hrvatske knjige iz 18. stoljeća</h2><p>Ova kategorija okuplja izdanja iz doba prosvjetiteljstva, razvoja prirodnih znanosti i snažnih kulturnih promjena. Zastupljena su djela povezana s hrvatskim prostorom, autorima i knjižnom baštinom, tiskana na latinskom, talijanskom, hrvatskom i drugim jezicima.</p><p>Pregledajte povijesne rasprave, vjerska djela, rane znanstvene tekstove i druge rijetke knjige iz 18. stoljeća. Svaki primjerak ima vlastiti opis izdanja i podatke o stanju kako biste ga mogli procijeniti prije kupnje.</p>',
  `description_en` = '<h2>Rare Croatian books from the 18th century</h2><p>This category brings together editions from the Enlightenment, the growth of the natural sciences and a period of major cultural change. It includes works connected with Croatian authors, regions and book heritage, printed in Latin, Italian, Croatian and other languages.</p><p>Browse historical studies, religious works, early scientific texts and other rare 18th-century books. Each listing provides edition details and information about the condition of the individual copy.</p>',
  `updated_at` = NOW()
WHERE `id` = 3 AND `title` = 'HRVATSKA RARA - 18 stoljeće';

UPDATE `categories`
SET
  `meta_title` = 'Hrvatske rijetke knjige 17. stoljeća | Biblos',
  `meta_title_en` = 'Rare Croatian books from the 17th century | Biblos',
  `meta_description` = 'Pregledajte rijetke hrvatske knjige iz 17. stoljeća: barokna, povijesna, književna i teološka izdanja iz ponude Antikvarijata Biblos.',
  `meta_description_en` = 'Browse rare Croatian 17th-century books, including Baroque, historical, literary and theological editions at Antikvarijat Biblos.',
  `description` = '<h2>Rijetke hrvatske knjige iz 17. stoljeća</h2><p>Knjige iz 17. stoljeća svjedoče o baroknoj kulturi, vjerskim raspravama i političkim promjenama ranoga novog vijeka. U kategoriji se nalaze djela važna za hrvatsku i jadransku kulturnu povijest, kao i knjige autora povezanih s Dalmacijom, Dubrovnikom i drugim hrvatskim krajevima.</p><p>Istražite izvorna izdanja na latinskom, talijanskom i drugim jezicima, od teoloških i filozofskih tekstova do povijesnih i književnih djela. Opis svakog primjerka navodi bibliografske podatke i njegovo sačuvano stanje.</p>',
  `description_en` = '<h2>Rare Croatian books from the 17th century</h2><p>Seventeenth-century books reflect Baroque culture, religious debate and the political changes of the early modern period. The category includes works important to Croatian and Adriatic cultural history, including authors associated with Dalmatia, Dubrovnik and other Croatian regions.</p><p>Explore original editions in Latin, Italian and other languages, from theological and philosophical texts to historical and literary works. Each listing includes bibliographic details and a description of the individual copy.</p>',
  `updated_at` = NOW()
WHERE `id` = 4 AND `title` = 'HRVATSKA RARA - 17 stoljeće';

UPDATE `categories`
SET
  `meta_title` = 'Hrvatske rijetke knjige 19. stoljeća | Biblos',
  `meta_title_en` = 'Rare Croatian books from the 19th century | Biblos',
  `meta_description` = 'Pronađite rijetke hrvatske knjige iz 19. stoljeća: preporodne, povijesne, jezikoslovne i književne naslove u Antikvarijatu Biblos.',
  `meta_description_en` = 'Find rare Croatian 19th-century books, including historical, linguistic, literary and National Revival editions at Antikvarijat Biblos.',
  `description` = '<h2>Rijetke hrvatske knjige iz 19. stoljeća</h2><p>Devetnaesto stoljeće donijelo je snažan razvoj hrvatskog nakladništva, jezikoslovlja, historiografije i književnosti. Ovdje su okupljena izdanja iz vremena narodnog preporoda, oblikovanja modernoga hrvatskog jezika i intenzivnog istraživanja nacionalne povijesti.</p><p>U ponudi se pojavljuju rječnici, povijesna djela, književna prva izdanja, putopisi i knjige o hrvatskim zemljama. Za svaki primjerak provjerite godinu i mjesto tiska, uvez, potpunost te napomene o očuvanosti.</p>',
  `description_en` = '<h2>Rare Croatian books from the 19th century</h2><p>The nineteenth century brought major growth in Croatian publishing, linguistics, historiography and literature. This category gathers editions from the National Revival, the formation of the modern Croatian language and a period of intensive research into national history.</p><p>Browse dictionaries, historical works, literary first editions, travel writing and books about Croatian lands. Each listing records the year and place of printing, binding, completeness and relevant condition notes.</p>',
  `updated_at` = NOW()
WHERE `id` = 10 AND `title` = 'HRVATSKA RARA - 19 stoljeće';

UPDATE `categories`
SET
  `meta_title` = 'NDH i politička emigracija – povijesne knjige | Biblos',
  `meta_title_en` = 'NDH and political emigration – history books | Biblos',
  `meta_description` = 'Knjige, dokumenti i memoari o NDH, Drugom svjetskom ratu i hrvatskoj političkoj emigraciji za istraživanje složenog povijesnog razdoblja.',
  `meta_description_en` = 'Books, documents and memoirs on the NDH, the Second World War and Croatian political emigration for research into a complex historical period.',
  `description` = '<h2>Knjige o NDH i hrvatskoj političkoj emigraciji</h2><p>Kategorija obuhvaća historiografska djela, dokumente, memoare i biografije o Nezavisnoj Državi Hrvatskoj, Drugom svjetskom ratu, poraću i hrvatskoj političkoj emigraciji. Zastupljene su različite istraživačke perspektive i izdanja nastala u zemlji i inozemstvu.</p><p>Građa je namijenjena čitateljima i istraživačima koji žele usporediti izvore i kritički proučiti političke, vojne i društvene okolnosti razdoblja. Opis svake knjige navodi autora, izdanje i dostupne podatke o primjerku.</p>',
  `description_en` = '<h2>Books on the NDH and Croatian political emigration</h2><p>This category includes historical studies, documents, memoirs and biographies concerning the Independent State of Croatia, the Second World War, the post-war period and Croatian political emigration. It represents different scholarly perspectives and publications produced both at home and abroad.</p><p>The material is intended for readers and researchers who want to compare sources and critically examine the political, military and social context of the period. Each book listing identifies the author, edition and available copy details.</p>',
  `updated_at` = NOW()
WHERE `id` = 11 AND `title` = 'NDH i emigracija';

UPDATE `categories`
SET
  `meta_title` = 'Rječnici hrvatskog i stranih jezika | Biblos',
  `meta_title_en` = 'Croatian and foreign-language dictionaries | Biblos',
  `meta_description` = 'Pregledajte hrvatske, dvojezične, stručne i etimološke rječnike te rječnike engleskog, njemačkog, talijanskog, latinskog i drugih jezika.',
  `meta_description_en` = 'Browse Croatian, bilingual, specialist and etymological dictionaries, including English, German, Italian, Latin and other languages.',
  `updated_at` = NOW()
WHERE `id` = 14 AND `title` = 'Rječnici';

UPDATE `categories`
SET
  `meta_title` = 'Antika i srednji vijek – povijesne knjige | Biblos',
  `meta_title_en` = 'Ancient and medieval history books | Biblos',
  `meta_description` = 'Knjige o starom Egiptu, Grčkoj, Rimu, Bizantu i srednjem vijeku: civilizacije, vladari, ratovi, religija, društvo i svakodnevni život.',
  `meta_description_en` = 'Books on ancient Egypt, Greece, Rome, Byzantium and the Middle Ages, covering civilizations, rulers, warfare, religion and daily life.',
  `description` = '<h2>Knjige o antici i srednjem vijeku</h2><p>Istražite povijest starih civilizacija i europskoga srednjovjekovlja kroz sinteze, monografije, biografije i izvore. Kategorija obuhvaća stari Egipat, Grčku i Rim, kasnu antiku, Bizant, seobe naroda, feudalno društvo, križarske ratove i razvoj srednjovjekovnih država.</p><p>Naslovi su korisni studentima, istraživačima i svima koje zanimaju politička povijest, arheologija, religija, umjetnost i svakodnevni život prošlih razdoblja. Dostupna su stručna i popularnoznanstvena izdanja različitih godina tiska.</p>',
  `description_en` = '<h2>Books on antiquity and the Middle Ages</h2><p>Explore ancient civilizations and medieval Europe through surveys, monographs, biographies and primary sources. The category covers ancient Egypt, Greece and Rome, late antiquity, Byzantium, migrations, feudal society, the Crusades and the development of medieval states.</p><p>The selection is useful for students, researchers and readers interested in political history, archaeology, religion, art and everyday life. It includes scholarly and accessible editions from different periods of publishing.</p>',
  `updated_at` = NOW()
WHERE `id` = 15 AND `title` = 'Antika i srednji vijek';

UPDATE `categories`
SET
  `meta_title` = 'Monografije gradova, umjetnosti i baštine | Biblos',
  `meta_title_en` = 'Monographs on cities, art and cultural heritage | Biblos',
  `meta_description` = 'Pronađite monografije o gradovima, regijama, umjetnicima, ustanovama i kulturnoj baštini, uključujući bogato ilustrirana i rijetka izdanja.',
  `meta_description_en` = 'Find monographs on cities, regions, artists, institutions and cultural heritage, including richly illustrated and rare editions.',
  `updated_at` = NOW()
WHERE `id` = 17 AND `title` = 'Monografija';

UPDATE `categories`
SET
  `meta_title` = 'Geografija, atlasi i putopisni vodiči | Biblos',
  `meta_title_en` = 'Geography books, atlases and travel guides | Biblos',
  `meta_description` = 'Knjige iz fizičke i društvene geografije, atlasi, kartografski priručnici i putopisni vodiči za učenje, istraživanje i putovanja.',
  `meta_description_en` = 'Browse physical and human geography books, atlases, cartography handbooks and travel guides for study, research and travel.',
  `updated_at` = NOW()
WHERE `id` = 18 AND `title` = 'Geografija';

UPDATE `categories`
SET
  `meta_title` = 'Pravne knjige i stručna literatura | Biblos',
  `meta_title_en` = 'Law books and legal literature | Biblos',
  `meta_description` = 'Pravne knjige, udžbenici i priručnici iz građanskog, kaznenog, ustavnog, međunarodnog i rimskog prava te pravne povijesti.',
  `meta_description_en` = 'Law books, textbooks and reference works on civil, criminal, constitutional, international and Roman law, plus legal history.',
  `description` = '<h2>Pravne knjige, udžbenici i priručnici</h2><p>Kategorija Pravo okuplja stručnu literaturu za studente, pravnike, istraživače i čitatelje koje zanimaju pravni sustavi i razvoj zakonodavstva. Obuhvaćena su građansko, kazneno, ustavno, upravno, trgovačko, međunarodno i rimsko pravo.</p><p>Pregledajte pravne udžbenike, komentare zakona, rječnike, zbornike sudske prakse i djela iz pravne povijesti i filozofije prava. Uz svaku knjigu navedeni su podaci o izdanju i stanju dostupnog primjerka.</p>',
  `description_en` = '<h2>Law books, textbooks and legal reference works</h2><p>The Law category offers specialist literature for students, legal professionals, researchers and readers interested in legal systems and the development of legislation. Subjects include civil, criminal, constitutional, administrative, commercial, international and Roman law.</p><p>Browse textbooks, legal commentaries, dictionaries, case-law collections and works on legal history and jurisprudence. Each listing includes edition details and information about the condition of the available copy.</p>',
  `updated_at` = NOW()
WHERE `id` = 21 AND `title` = 'Pravo';

UPDATE `categories`
SET
  `meta_title` = 'Knjige o glazbi i glazbenicima | Biblos',
  `meta_title_en` = 'Books on music and musicians | Biblos',
  `meta_description` = 'Biografije glazbenika, povijest glazbe, glazbena teorija i knjige o rocku, jazzu, klasici, operi, tradicijskoj i popularnoj glazbi.',
  `meta_description_en` = 'Biographies of musicians, music history and theory, plus books on rock, jazz, classical, opera, traditional and popular music.',
  `updated_at` = NOW()
WHERE `id` = 26 AND `title` = 'Glazba';

UPDATE `categories`
SET
  `meta_title` = 'Tehničke knjige i stručni priručnici | Biblos',
  `meta_title_en` = 'Technical books and engineering handbooks | Biblos',
  `meta_description` = 'Stručne knjige i priručnici iz strojarstva, građevinarstva, elektrotehnike, prometa, energetike, proizvodnje i povijesti izuma.',
  `meta_description_en` = 'Technical books and handbooks on mechanical, civil and electrical engineering, transport, energy, manufacturing and inventions.',
  `updated_at` = NOW()
WHERE `id` = 27 AND `title` = 'Tehnika';

UPDATE `categories`
SET
  `meta_title` = 'Povijest Istre i Rijeke – knjige | Biblos',
  `meta_title_en` = 'History of Istria and Rijeka – books | Biblos',
  `meta_description` = 'Knjige o povijesti Istre, Rijeke, Kvarnera i Trsta: lokalne monografije, dokumenti, zbornici i studije o kulturnoj i političkoj baštini.',
  `meta_description_en` = 'Books on the history of Istria, Rijeka, Kvarner and Trieste, including local monographs, documents and cultural and political studies.',
  `description` = '<h2>Knjige o povijesti Istre i Rijeke</h2><p>Otkrijte povijest Istre, Rijeke, Kvarnera i širega sjevernojadranskog prostora kroz lokalne monografije, dokumente, zbornike i povijesne studije. Teme obuhvaćaju gradove i naselja, pomorstvo, gospodarstvo, kulturnu baštinu te promjene granica i državnih uprava.</p><p>Kategorija uključuje izdanja na hrvatskom, talijanskom i drugim jezicima, od starijih regionalnih knjiga do suvremenih istraživanja. Bibliografski podaci i napomene o stanju navedeni su uz svaki primjerak.</p>',
  `description_en` = '<h2>Books on the history of Istria and Rijeka</h2><p>Discover the history of Istria, Rijeka, Kvarner and the wider northern Adriatic through local monographs, documents, collected papers and historical studies. Topics include towns and settlements, maritime history, the economy, cultural heritage and changes of borders and government.</p><p>The category includes Croatian, Italian and other-language editions, from older regional books to modern scholarship. Bibliographic details and condition notes are provided for each copy.</p>',
  `updated_at` = NOW()
WHERE `id` = 28 AND `title` = 'Istra i Rijeka';

UPDATE `categories`
SET
  `meta_title` = 'Hrvatske rijetke knjige 16. stoljeća | Biblos',
  `meta_title_en` = 'Rare Croatian books from the 16th century | Biblos',
  `meta_description` = 'Istražite rijetka izdanja iz 16. stoljeća povezana s hrvatskom knjižnom baštinom, renesansnim autorima, poviješću i ranim tiskarstvom.',
  `meta_description_en` = 'Explore rare 16th-century editions connected with Croatian book heritage, Renaissance authors, history and early European printing.',
  `description` = '<h2>Rijetke hrvatske knjige iz 16. stoljeća</h2><p>Šesnaesto stoljeće bilo je doba renesanse, reformacije i brzog razvoja europskog tiskarstva. Ova kategorija obuhvaća rijetka izdanja povezana s hrvatskim autorima, povijesnim zemljama i kulturnim krugovima, uključujući djela tiskana u važnim europskim tiskarskim središtima.</p><p>Pregledajte povijesne, vojne, teološke i književne tekstove na latinskom, talijanskom i drugim jezicima. Zbog starosti i posebnosti svakog primjerka, opis sadrži podatke o izdanju, uvezu, potpunosti i očuvanosti.</p>',
  `description_en` = '<h2>Rare Croatian books from the 16th century</h2><p>The sixteenth century was an age of Renaissance learning, Reformation and rapid growth in European printing. This category presents rare editions connected with Croatian authors, historical lands and cultural circles, including works printed in major European centres.</p><p>Browse historical, military, theological and literary texts in Latin, Italian and other languages. Because each copy is old and distinctive, listings include details of the edition, binding, completeness and condition.</p>',
  `updated_at` = NOW()
WHERE `id` = 30 AND `title` = 'HRVATSKA RARA - 16 stoljeće';

UPDATE `categories`
SET
  `meta_title` = 'Ezoterija, astrologija i alternativna učenja | Biblos',
  `meta_title_en` = 'Esotericism, astrology and alternative thought | Biblos',
  `meta_description` = 'Knjige o ezoteriji, astrologiji, teozofiji, gnosticizmu, simbolizmu i alternativnim duhovnim učenjima različitih tradicija.',
  `meta_description_en` = 'Books on esotericism, astrology, Theosophy, Gnosticism, symbolism and alternative spiritual teachings from different traditions.',
  `updated_at` = NOW()
WHERE `id` = 35 AND `title` = 'Alternativa';

UPDATE `categories`
SET
  `meta_title` = 'Povijest Dalmacije – knjige i monografije | Biblos',
  `meta_title_en` = 'History of Dalmatia – books and monographs | Biblos',
  `meta_description` = 'Knjige o povijesti Dalmacije, njezinih gradova i otoka: monografije, arhivska građa, kulturna povijest, umjetnost i pomorska baština.',
  `meta_description_en` = 'Books on the history of Dalmatia, its towns and islands, including monographs, archival sources, art and maritime heritage.',
  `description` = '<h2>Knjige o povijesti Dalmacije</h2><p>Istražite prošlost Dalmacije kroz knjige o njezinim gradovima, otocima, stanovništvu i kulturnoj baštini. Kategorija obuhvaća povijesne sinteze, lokalne monografije, arhivsku građu i studije o razdobljima od antike i srednjega vijeka do modernog doba.</p><p>Posebne teme uključuju pomorstvo, umjetnost, arhitekturu, crkvenu povijest i odnose dalmatinskih komuna s Venecijom, Habsburškom Monarhijom i drugim političkim središtima. Dostupna su starija izdanja i suvremena istraživanja.</p>',
  `description_en` = '<h2>Books on the history of Dalmatia</h2><p>Explore Dalmatia through books on its towns, islands, people and cultural heritage. The category includes historical surveys, local monographs, archival sources and studies ranging from antiquity and the Middle Ages to the modern period.</p><p>Subjects include maritime history, art, architecture, church history and the relationships of Dalmatian communes with Venice, the Habsburg Monarchy and other political centres. Both older editions and modern research are represented.</p>',
  `updated_at` = NOW()
WHERE `id` = 38 AND `title` = 'Dalmacija';

UPDATE `categories`
SET
  `meta_title` = 'Povijest Bosne i Hercegovine – knjige | Biblos',
  `meta_title_en` = 'History of Bosnia and Herzegovina – books | Biblos',
  `meta_description` = 'Knjige o povijesti Bosne i Hercegovine: srednjovjekovna Bosna, osmansko i austrougarsko razdoblje, kultura, religije i 20. stoljeće.',
  `meta_description_en` = 'Books on Bosnia and Herzegovina, from medieval Bosnia and the Ottoman and Austro-Hungarian periods to culture, religion and the 20th century.',
  `description` = '<h2>Knjige o povijesti Bosne i Hercegovine</h2><p>Kategorija obuhvaća povijest Bosne i Hercegovine od srednjovjekovne bosanske države preko osmanskog i austrougarskog razdoblja do Jugoslavije i suvremenoga doba. Uključuje povijesne preglede, monografije, dokumente, memoare i lokalnu povijest.</p><p>Čitatelji mogu istražiti političke i društvene procese, religijsku i kulturnu raznolikost, gradove i regije te prijelomne događaje 20. stoljeća. Različiti autori i vrste izvora omogućuju usporedbu pristupa složenoj prošlosti zemlje.</p>',
  `description_en` = '<h2>Books on the history of Bosnia and Herzegovina</h2><p>This category covers Bosnia and Herzegovina from the medieval Bosnian state through Ottoman and Austro-Hungarian rule to Yugoslavia and the contemporary period. It includes historical surveys, monographs, documents, memoirs and local history.</p><p>Readers can explore political and social change, religious and cultural diversity, towns and regions, and major events of the twentieth century. Different authors and source types make it possible to compare approaches to the country’s complex past.</p>',
  `updated_at` = NOW()
WHERE `id` = 43 AND `title` = 'Bosna i Hercegovina';

UPDATE `categories`
SET
  `meta_title` = 'Knjige o arhitekturi i graditeljstvu | Biblos',
  `meta_title_en` = 'Books on architecture and the built environment | Biblos',
  `meta_description` = 'Knjige o povijesti arhitekture, urbanizmu, teoriji prostora, graditeljstvu i hrvatskoj sakralnoj, stambenoj i modernoj arhitekturi.',
  `meta_description_en` = 'Books on architectural history, urbanism, spatial theory, construction and Croatian religious, residential and modern architecture.',
  `description` = '<h2>Knjige o arhitekturi, urbanizmu i graditeljstvu</h2><p>Pregledajte stručne i ilustrirane knjige o povijesti arhitekture, teoriji prostora, urbanizmu i graditeljstvu. Kategorija prati razvoj arhitektonskih stilova od antičkih i srednjovjekovnih građevina do moderne i suvremene arhitekture.</p><p>Zastupljene su monografije arhitekata i pojedinih građevina, knjige o hrvatskoj sakralnoj i stambenoj baštini te priručnici namijenjeni studentima i stručnjacima. Uz starija i rijetka izdanja dostupne su i novije studije.</p>',
  `description_en` = '<h2>Books on architecture, urbanism and construction</h2><p>Browse scholarly and illustrated books on architectural history, spatial theory, urbanism and construction. The category follows architectural styles from ancient and medieval buildings to modern and contemporary design.</p><p>It includes monographs on architects and individual buildings, books on Croatian religious and residential heritage, and reference works for students and professionals. Older and rare editions appear alongside recent studies.</p>',
  `updated_at` = NOW()
WHERE `id` = 48 AND `title` = 'Arhitektura';

UPDATE `categories`
SET
  `meta_title` = 'Povijest Dubrovnika – knjige i monografije | Biblos',
  `meta_title_en` = 'History of Dubrovnik – books and monographs | Biblos',
  `meta_description` = 'Knjige o Dubrovačkoj Republici, povijesti grada, diplomaciji, pomorstvu, književnosti, umjetnosti i kulturnoj baštini Dubrovnika.',
  `meta_description_en` = 'Books on the Republic of Ragusa and Dubrovnik, including diplomacy, maritime history, literature, art and cultural heritage.',
  `description` = '<h2>Knjige o povijesti Dubrovnika i Dubrovačke Republike</h2><p>Upoznajte Dubrovnik kroz povijesne sinteze, arhivsku građu, monografije i studije o Dubrovačkoj Republici. Teme obuhvaćaju diplomaciju, trgovinu i pomorstvo, političke ustanove, odnose sa susjedima te svakodnevni život grada i njegove okolice.</p><p>Kategorija uključuje i knjige o dubrovačkoj književnosti, umjetnosti, arhitekturi i znamenitim osobama. Starija izdanja i novija istraživanja zajedno pružaju širok pogled na jednu od najvažnijih jadranskih povijesnih sredina.</p>',
  `description_en` = '<h2>Books on Dubrovnik and the Republic of Ragusa</h2><p>Discover Dubrovnik through historical surveys, archival sources, monographs and studies of the Republic of Ragusa. Subjects include diplomacy, trade and maritime history, political institutions, relations with neighbouring powers and everyday life in the city and its surroundings.</p><p>The category also includes books on Dubrovnik literature, art, architecture and notable people. Older editions and recent scholarship offer a broad view of one of the Adriatic’s most important historical centres.</p>',
  `updated_at` = NOW()
WHERE `id` = 49 AND `title` = 'Dubrovnik';

UPDATE `categories`
SET
  `meta_title` = 'Putopisi i knjige o putovanjima | Biblos',
  `meta_title_en` = 'Travel writing and exploration books | Biblos',
  `meta_description` = 'Putopisi iz Hrvatske i svijeta, zapisi istraživača, književna putovanja i ilustrirane knjige o gradovima, krajevima i kulturama.',
  `meta_description_en` = 'Travel writing from Croatia and around the world, explorers’ accounts, literary journeys and illustrated books on places and cultures.',
  `description` = '<h2>Putopisi iz Hrvatske i svijeta</h2><p>Putopis spaja osobno iskustvo, književnost, geografiju i kulturnu povijest. U ovoj kategoriji pronaći ćete zapise s putovanja po Hrvatskoj, Europi i udaljenim krajevima, od klasičnih putopisaca i istraživača do suvremenih autora.</p><p>Naslovi donose susrete s gradovima, krajolicima i ljudima te bilježe kako su se mjesta i način putovanja mijenjali kroz vrijeme. Dostupni su književni putopisi, dnevnici ekspedicija, ilustrirana izdanja i povijesni vodiči.</p>',
  `description_en` = '<h2>Travel writing from Croatia and around the world</h2><p>Travel writing brings together personal experience, literature, geography and cultural history. This category ranges from journeys through Croatia and Europe to distant regions, written by classic travellers, explorers and contemporary authors.</p><p>The books record encounters with cities, landscapes and people, and show how places and travel have changed over time. Browse literary travelogues, expedition journals, illustrated editions and historical guides.</p>',
  `updated_at` = NOW()
WHERE `id` = 52 AND `title` = 'Putopis';

UPDATE `categories`
SET
  `meta_title` = 'Knjige iz politologije i političke povijesti | Biblos',
  `meta_title_en` = 'Political science and political history books | Biblos',
  `meta_description` = 'Knjige o političkoj teoriji, demokraciji, ideologijama, državi, međunarodnim odnosima, geopolitici i hrvatskoj političkoj povijesti.',
  `meta_description_en` = 'Books on political theory, democracy, ideologies, the state, international relations, geopolitics and Croatian political history.',
  `updated_at` = NOW()
WHERE `id` = 61 AND `title` = 'Politologija';

UPDATE `categories`
SET
  `meta_title` = 'Knjige o gljivama i mikologiji | Biblos',
  `meta_title_en` = 'Books on mushrooms and mycology | Biblos',
  `meta_description` = 'Ilustrirani vodiči i knjige o gljivama, mikologiji, prepoznavanju vrsta, uzgoju, sakupljanju i kulinarskoj uporabi gljiva.',
  `meta_description_en` = 'Illustrated guides and books on mushrooms, mycology, species identification, cultivation, foraging and culinary uses.',
  `description` = '<h2>Knjige o gljivama i mikologiji</h2><p>Pronađite ilustrirane vodiče za upoznavanje gljiva, stručne knjige iz mikologije te naslove o uzgoju i kulinarskoj uporabi pojedinih vrsta. Fotografije, crteži i ključevi za određivanje mogu pomoći pri učenju morfoloških obilježja i staništa.</p><p>Knjige su informativna literatura, ali nisu zamjena za provjeru kod kvalificiranog poznavatelja gljiva. Pri sakupljanju i konzumaciji uvijek se oslonite na pouzdanu identifikaciju vrste.</p>',
  `description_en` = '<h2>Books on mushrooms and mycology</h2><p>Find illustrated mushroom guides, specialist books on mycology and titles covering cultivation and culinary uses. Photographs, drawings and identification keys can support learning about morphology and habitat.</p><p>These books are reference material and are not a substitute for confirmation by a qualified mushroom expert. When foraging or preparing wild mushrooms, always rely on a verified species identification.</p>',
  `updated_at` = NOW()
WHERE `id` = 64 AND `title` = 'Gljive';

UPDATE `categories`
SET
  `meta_title` = 'NOB i Drugi svjetski rat – povijesne knjige | Biblos',
  `meta_title_en` = 'Yugoslav Partisans and World War II history | Biblos',
  `meta_description` = 'Knjige, dokumenti i memoari o NOB-u, partizanskom pokretu i Drugom svjetskom ratu u Hrvatskoj, Jugoslaviji i jugoistočnoj Europi.',
  `meta_description_en` = 'Books, documents and memoirs on the Yugoslav Partisans and the Second World War in Croatia, Yugoslavia and Southeast Europe.',
  `description` = '<h2>Knjige o NOB-u i Drugom svjetskom ratu</h2><p>Kategorija okuplja povijesne studije, dokumente, memoare i lokalne monografije o Narodnooslobodilačkoj borbi, partizanskom pokretu i Drugom svjetskom ratu na prostoru Hrvatske i Jugoslavije. Obuhvaća vojne operacije, političku organizaciju, civilno stanovništvo i ratnu svakodnevicu.</p><p>Zastupljena su starija izdanja i novija historiografska istraživanja različitih pristupa. Usporedba autora, izvora i vremena nastanka izdanja važna je za kritičko razumijevanje ove složene teme.</p>',
  `description_en` = '<h2>Books on the Yugoslav Partisans and the Second World War</h2><p>This category gathers historical studies, documents, memoirs and local monographs on the National Liberation Struggle, the Yugoslav Partisan movement and the Second World War in Croatia and Yugoslavia. It covers military operations, political organisation, civilians and wartime daily life.</p><p>Older publications appear alongside recent historiography and different scholarly approaches. Comparing authors, sources and publication contexts supports a critical understanding of this complex subject.</p>',
  `updated_at` = NOW()
WHERE `id` = 65 AND `title` = 'NOB';

UPDATE `categories`
SET
  `meta_title` = 'Ruska književnost – klasici i moderna proza | Biblos',
  `meta_title_en` = 'Russian literature – classics and modern fiction | Biblos',
  `meta_description` = 'Djela Dostojevskog, Tolstoja, Čehova, Gogolja i drugih ruskih autora: romani, pripovijetke, drama, poezija i književne studije.',
  `meta_description_en` = 'Works by Dostoevsky, Tolstoy, Chekhov, Gogol and other Russian authors, including novels, stories, drama, poetry and criticism.',
  `description` = '<h2>Ruska književnost u hrvatskim i stranim izdanjima</h2><p>Od velikih romana 19. stoljeća do avangarde i suvremene proze, ruska književnost snažno je obilježila svjetsku kulturu. U kategoriji se pojavljuju djela Dostojevskog, Tolstoja, Čehova, Gogolja, Turgenjeva, Bulgakova i drugih važnih autora.</p><p>Pregledajte romane, pripovijetke, drame, poeziju, sabrana djela i književne studije u hrvatskim i stranim prijevodima. Starija izdanja zanimljiva su i čitateljima koji prate povijest prevođenja i oblikovanja biblioteka.</p>',
  `description_en` = '<h2>Russian literature in Croatian and other editions</h2><p>From the great nineteenth-century novels to the avant-garde and modern prose, Russian literature has profoundly shaped world culture. The category includes works by Dostoevsky, Tolstoy, Chekhov, Gogol, Turgenev, Bulgakov and other major authors.</p><p>Browse novels, short stories, drama, poetry, collected works and literary studies in Croatian and other translations. Older editions also appeal to readers interested in the history of translation and publishing series.</p>',
  `updated_at` = NOW()
WHERE `id` = 66 AND `title` = 'Ruska književnost';

UPDATE `categories`
SET
  `meta_title` = 'Biblioteka Vjeverica – knjige za djecu | Biblos',
  `meta_title_en` = 'Biblioteka Vjeverica children’s books | Biblos',
  `meta_description` = 'Knjige iz kultne Biblioteke Vjeverica: dječji romani, pustolovine i školski klasici u izdanjima Mladosti za čitatelje i kolekcionare.',
  `meta_description_en` = 'Books from Croatia’s classic Biblioteka Vjeverica series: children’s novels, adventures and school favourites published by Mladost.',
  `description` = '<h2>Biblioteka Vjeverica – klasici dječje književnosti</h2><p>Biblioteka Vjeverica zagrebačke Mladosti obilježila je odrastanje brojnih generacija čitatelja. Prepoznatljiva serija donosila je domaće i svjetske dječje romane, pustolovne priče, humoristične naslove i knjige koje su postale dio školske lektire.</p><p>Ovdje možete pronaći pojedinačne naslove i različita izdanja Vjeverice za čitanje, nadopunu kućne biblioteke ili kolekcionarsku zbirku. Kod svakog primjerka provjerite godinu izdanja, ilustratora, uvez i stanje.</p>',
  `description_en` = '<h2>Biblioteka Vjeverica – classics of children’s literature</h2><p>Published by Mladost in Zagreb, Biblioteka Vjeverica shaped the reading of generations of Croatian children. The recognisable series introduced domestic and international children’s novels, adventures, humorous stories and many school favourites.</p><p>Browse individual titles and different editions for reading, completing a family library or building a collection. Each listing provides the publication year, illustrator and available binding and condition details.</p>',
  `updated_at` = NOW()
WHERE `id` = 68 AND `title` = 'Biblioteka Vjeverica';

UPDATE `categories`
SET
  `meta_title` = 'Francuska književnost – klasici i romani | Biblos',
  `meta_title_en` = 'French literature – classics, novels and poetry | Biblos',
  `meta_description` = 'Francuska književnost od Molièrea i Balzaca do Flauberta, Prousta i modernih autora: romani, drama, poezija i književne studije.',
  `meta_description_en` = 'French literature from Molière and Balzac to Flaubert, Proust and modern authors: novels, drama, poetry and literary studies.',
  `description` = '<h2>Francuska književnost od klasika do suvremenih autora</h2><p>Francuska književnost obuhvaća stoljeća romana, drame, poezije i eseja koji su oblikovali europsku kulturu. U kategoriji se pojavljuju djela Molièrea, Balzaca, Flauberta, Hugoa, Prousta, Camusa i drugih klasičnih i modernih autora.</p><p>Pregledajte hrvatske prijevode, izdanja na francuskom jeziku, sabrana djela i književne studije. Ponuda se mijenja, a podaci uz svaki naslov olakšavaju razlikovanje prijevoda, nakladnika i godine izdanja.</p>',
  `description_en` = '<h2>French literature from classics to contemporary authors</h2><p>French literature spans centuries of novels, drama, poetry and essays that have shaped European culture. The category includes works by Molière, Balzac, Flaubert, Hugo, Proust, Camus and other classic and modern authors.</p><p>Browse Croatian translations, French-language editions, collected works and literary studies. The selection changes over time, while each listing helps readers distinguish the translation, publisher and year of publication.</p>',
  `updated_at` = NOW()
WHERE `id` = 69 AND `title` = 'Francuska književnost';

UPDATE `categories`
SET
  `meta_title` = 'Rijetki, stručni i enciklopedijski rječnici | Biblos',
  `meta_title_en` = 'Rare, specialist and encyclopedic dictionaries | Biblos',
  `meta_description` = 'Rijetki i stručni rječnici, enciklopedijski leksikoni te povijesna višejezična izdanja za istraživače, jezikoslovce i kolekcionare.',
  `meta_description_en` = 'Rare and specialist dictionaries, encyclopedic reference works and historical multilingual editions for researchers and collectors.',
  `description` = '<h2>Rijetki i stručni rječnici</h2><p>Potkategorija okuplja rječnike i leksikografska izdanja koja se izdvajaju starošću, opsegom ili stručnom tematikom. Među njima se mogu pronaći enciklopedijski, mitološki, povijesni i višejezični rječnici te djela važna za povijest hrvatske i slavenske leksikografije.</p><p>Takva su izdanja korisna jezikoslovcima i istraživačima, ali i kolekcionarima koji traže određeni tisak, uvez ili cjelovit komplet. Detalje o svescima i stanju provjerite u opisu primjerka.</p>',
  `description_en` = '<h2>Rare and specialist dictionaries</h2><p>This subcategory gathers dictionaries and lexicographical works notable for their age, scope or specialist subject. It may include encyclopedic, mythological, historical and multilingual dictionaries, together with works important to Croatian and Slavic lexicography.</p><p>These editions are useful to linguists and researchers as well as collectors seeking a particular printing, binding or complete set. Volume and condition details are provided in each listing.</p>',
  `updated_at` = NOW()
WHERE `id` = 70 AND `title` = 'Rječnik';

UPDATE `categories`
SET
  `meta_title` = 'Knjige o ljekovitom bilju i travarstvu | Biblos',
  `meta_title_en` = 'Books on medicinal plants and herbal traditions | Biblos',
  `meta_description` = 'Ilustrirani vodiči i knjige o prepoznavanju, uzgoju i tradicijskoj uporabi ljekovitog bilja, samoniklih biljaka i biljnih čajeva.',
  `meta_description_en` = 'Illustrated guides and books on identifying, growing and traditionally using medicinal and wild plants and herbal teas.',
  `description` = '<h2>Knjige o ljekovitom bilju i travarstvu</h2><p>U kategoriji se nalaze ilustrirani vodiči za prepoznavanje samoniklih i ljekovitih biljaka, knjige o uzgoju, berbi i sušenju te pregledi njihove tradicijske uporabe. Starija izdanja zanimljiva su i kao izvor za povijest pučke medicine i travarstva.</p><p>Sadržaj knjiga služi informiranju i istraživanju, a ne zamjenjuje savjet liječnika ili ljekarnika. Pri uporabi biljnih pripravaka potrebno je provjeriti sigurnost, doziranje i moguće interakcije.</p>',
  `description_en` = '<h2>Books on medicinal plants and herbal traditions</h2><p>This category includes illustrated guides to identifying wild and medicinal plants, books on growing, harvesting and drying, and accounts of traditional uses. Older editions are also valuable sources for the history of folk medicine and herbal practice.</p><p>The books are intended for information and research and do not replace advice from a doctor or pharmacist. Before using herbal preparations, check safety, dosage and possible interactions.</p>',
  `updated_at` = NOW()
WHERE `id` = 71 AND `title` = 'Ljekovito bilje';

UPDATE `categories`
SET
  `meta_title` = 'Filatelija i numizmatika – knjige i katalozi | Biblos',
  `meta_title_en` = 'Philately and numismatics books and catalogues | Biblos',
  `meta_description` = 'Knjige, katalozi i priručnici o poštanskim markama, novcu, medaljama i povijesti filatelije i numizmatike za kolekcionare i istraživače.',
  `meta_description_en` = 'Books, catalogues and guides to stamps, coins, banknotes, medals and the history of philately and numismatics for collectors and researchers.',
  `description` = '<h2>Knjige i katalozi o filateliji i numizmatici</h2><p>Ova kategorija okuplja kataloge, priručnike, almanaha i stručne knjige o poštanskim markama, poštanskoj povijesti, kovanicama, papirnatom novcu i medaljama. Literatura pomaže pri određivanju izdanja, razdoblja, tiskarskih varijanti i povijesnog konteksta kolekcionarskih predmeta.</p><p>Naslovi su namijenjeni početnicima, iskusnim kolekcionarima i istraživačima. Prije narudžbe provjerite godinu izdanja kataloga, obuhvaćeno područje, jezik i stanje konkretnog primjerka.</p>',
  `description_en` = '<h2>Books and catalogues on philately and numismatics</h2><p>This category brings together catalogues, handbooks, almanacs and specialist books on postage stamps, postal history, coins, banknotes and medals. The literature supports identification of issues, periods, printing varieties and the historical context of collectable objects.</p><p>Titles serve beginners, experienced collectors and researchers. Before ordering, check the catalogue year, geographical coverage, language and condition of the individual copy.</p>',
  `updated_at` = NOW()
WHERE `id` = 73 AND `title` = 'Filatelija i numizmatika';

COMMIT;

-- Verification: all metadata should be present and stay within the CMS limits.
SELECT
  `id`,
  `title`,
  CHAR_LENGTH(`meta_title`) AS `meta_title_length`,
  CHAR_LENGTH(`meta_description`) AS `meta_description_length`,
  CHAR_LENGTH(`meta_title_en`) AS `meta_title_en_length`,
  CHAR_LENGTH(`meta_description_en`) AS `meta_description_en_length`,
  CHAR_LENGTH(`description`) AS `description_length`,
  CHAR_LENGTH(`description_en`) AS `description_en_length`
FROM `categories`
WHERE `id` IN (3,4,10,11,14,15,17,18,21,26,27,28,30,35,38,43,48,49,52,61,64,65,66,68,69,70,71,73)
ORDER BY `id`;

-- Rollback, if ever needed:
-- START TRANSACTION;
-- DELETE FROM `categories` WHERE `id` IN (3,4,10,11,14,15,17,18,21,26,27,28,30,35,38,43,48,49,52,61,64,65,66,68,69,70,71,73);
-- INSERT INTO `categories` SELECT * FROM `categories_seo_backup_20260819`;
-- COMMIT;
