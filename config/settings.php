<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'free_shipping' => 70,

    'pagination' => [
        'front' => 30,
        'back' => 30
    ],

    'search_keyword' => 'pojam',
    'author_path' => 'autor',
    'publisher_path' => 'nakladnik',

    'images_domain' => 'https://www.antikvarijat-biblos.hr/',

    'eur_divide_amount' => 0.13272280,

    'sorting_list' => [
        0 => [
            'title' => 'Najnovije',
            'value' => 'novi'
        ],
        1 => [
            'title' => 'Najmanja cijena',
            'value' => 'price_up'
        ],
        2 => [
            'title' => 'Najveća cijena',
            'value' => 'price_down'
        ],
        3 => [
            'title' => 'A - Ž',
            'value' => 'naziv_up'
        ],
        4 => [
            'title' => 'Ž - A',
            'value' => 'naziv_down'
        ],
    ],

    'order' => [
        'made_text' => 'Narudžba napravljena.',
        'status' => [
            'new' => 1,
            'unfinished' => 8,
            'declined' => 7,
            'canceled' => 5,
            'paid' => 3,
            'send' => 4,
        ]
    ],

    'payment' => [
        'providers' => [
            //'wspay' => \App\Models\Front\Checkout\Payment\Wspay::class,
            'payway' => \App\Models\Front\Checkout\Payment\Payway::class,
            'cod' => \App\Models\Front\Checkout\Payment\Cod::class,
            'bank' => \App\Models\Front\Checkout\Payment\Bank::class,
            'pickup' => \App\Models\Front\Checkout\Payment\Pickup::class,
            'corvus' => \App\Models\Front\Checkout\Payment\Corvus::class,
            'keks' => \App\Models\Front\Checkout\Payment\Keks::class
        ]
    ],

    'sitemap' => [
        0 => 'pages',
        1 => 'categories',
        2 => 'products',
        3 => 'authors',
        4 => 'publishers'
    ],

    //
    'njuskalo' => [
        'user_id' => '3162806',
        'sync' => [
            'fantasy' => 15360, //*// Literatura / Znanstveno fantastični romani
            'djecje-knjige' => 15348,
            'beletristika' => 9751, //*
            'proza' => 9760, //*// Literatura / Ostala literatura
            'sf' => 15369, //*// Literatura / Popularno-znanstvena liter.
            'knjizevnost' => 15361, //// Literatura / Ostala književnost
            'outlet' => 9760,
            'duhovne-knjige' => 9754, //*
            'povijest' => 15371, // Literatura / Povijesna i politička liter.
            'autobiografije-i-biografije' => 15363,
            'kompleti' => 9760,
            'alternativne-knjige' => 9760,
            'savjetnici' => 9760, //*
            'psihologija' => 15389,
            'rijetke-knjige' => 9806,
            'erotske-knjige' => 15357, // Literatura / Ljubavni romani
            'nakladnici' => 9760,
            'knjige-na-stranom-jeziku' => 9760,
            'religija-i-mitologija' => 9754,
            'antikvarne-knjige' => 9750, // 9806 pronađeno blago/antikvarne knjige, 9750 Literatura/Antikvarne knjige
            'militarija' => 9760,
            'kuharice' => 13116,
            'publicistika' => 15372, // Literatura / Ostala publicistika
            'ostala-literatura' => 9760,
            'strucna-literatura' => 15394, // Literatura / Ostala stručna literatura
            'enciklopedije-i-leksikon' => 13096,
            'monografije' => 15368,
            'putopisi' => 15367,// Literatura / Literatura za putovanja
            'rjecnici' => 13095,
            'filozofija' => 15380,
            'poezija' => 15358,
            'stripovi' => 15395,// Literatura / Ostali stripovi
            'wattpad-i-domaci-pisci' => 9760,
            'nekategorizirane' => 9760,
            'casopisi' => 15342, // Literatura / Auto moto časopisi i magazini
            'bookmarkeri' => 9760,
            'fotografija' => 12799, // 12799 Pronađeno blago/ razglednice i fotografije
            'bojanke-za-odrasle' => 9760,
            'rokovnici' => 9760,
            'gift-program' => 9760,
            'karte' => 9760,
            'svezalice-pidzame-za-knjige' => 9760
        ]
    ]

];
