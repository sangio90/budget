<?php

/*
|--------------------------------------------------------------------------
| Regole di classificazione per l'import degli estratti conto ING
|--------------------------------------------------------------------------
|
| Tre liste con lo stesso formato:
|   regole  → fambudget:import-estratto (estratto conto ING)
|   amazon  → fambudget:import-amazon   (report ordini Amazon Business)
|   paypal  → fambudget:import-paypal   (lista attività PayPal)
|
| Ogni regola ha:
|
|   match     regex (case-insensitive) applicata alla descrizione completa
|             del movimento (esercente, creditore SDD, causale bonifico,
|             titolo articolo Amazon, esercente e nota PayPal...)
|   categoria nome della categoria di budget (colonna `nome`), oppure
|   ignora    motivo per cui il movimento NON va importato
|   note      nota da salvare; se assente si usa il nome dell'esercente
|             (per Amazon il titolo abbreviato, per PayPal la nota dell'invio)
|   min/max   (opzionali) la regola vale solo per importi in quell'intervallo
|
| Vince la prima regola che corrisponde: metti prima le più specifiche.
| I movimenti che non corrispondono a nessuna regola vengono elencati come
| "DA CLASSIFICARE" e non inseriti.
|
*/

return [
    'regole' => [

        // ---- Movimenti da NON importare --------------------------------
        ['match' => '/\bGiroconto\b/i',                       'ignora' => 'giroconto tra conti propri'],
        ['match' => '/Canone mensile Carta di Credito/i',      'ignora' => 'canone carta di credito'],
        ['match' => '/carta di credito/i',                     'ignora' => 'addebito carta di credito (gestito a parte)'],
        ['match' => '/Bonifico.*Note: Fattura/i',              'ignora' => 'pagamento fattura (registrato nelle transazioni)'],
        // Amazon e PayPal: dall'estratto non si capisce cosa contengono, si inseriscono a mano se serve.
        ['match' => '/Amazon Prime/i',                         'ignora' => 'abbonamento Amazon Prime'],
        ['match' => '/AMAZON|AMZN Mktp/i',                     'ignora' => 'Amazon (da inserire a mano)'],
        ['match' => '/Creditor id\. \S+ PayPal Europe/i',      'ignora' => 'PayPal (da inserire a mano)'],
        // Costi di lavoro (hosting, Google), fuori dal budget familiare.
        ['match' => '/PAYPAL \*(GOOGLE|ARUBA)|Aruba Spa/i',    'ignora' => 'costo di lavoro, fuori budget'],
        ['match' => '/MONTANARO MASSIMO/i',                    'ignora' => 'escluso dal budget'],

        // ---- CASA ------------------------------------------------------
        ['match' => '/OCTOPUS ENERGY/i',        'categoria' => 'Bollette Luce Octopus + Plenitude', 'note' => 'Octopus'],
        // Eni Plenitude: due SDD lo stesso giorno, distinti dall'ID mandato.
        ['match' => '/Id Mandato 8002118190013341354/', 'categoria' => 'Bollette Luce Octopus + Plenitude', 'note' => 'Luce casa (Plenitude)'],
        ['match' => '/PADANIA ACQUE/i',         'categoria' => 'Bollette Acqua Padania Acque',      'note' => 'Acqua Padania'],
        ['match' => '/LEROY MERLIN/i',          'categoria' => 'ALTRO',                             'note' => 'Leroy Merlin'], // CASA / ALTRO

        // ---- UFFICIO ---------------------------------------------------
        ['match' => '/Note: Affitto ufficio/i', 'categoria' => 'Affitto',     'note' => 'Affitto'],
        ['match' => '/ENELENERGIA/i',           'categoria' => 'GAS (Enel)',  'note' => 'Gas ufficio (Enel)'],
        ['match' => '/Id Mandato 8002118190012163611/', 'categoria' => 'Luce (ENI Plenitude)', 'note' => 'Luce ufficio (Plenitude)'],

        // ---- AUTO ------------------------------------------------------
        ['match' => '/TAMOIL|PIT[- ]?STOP BAGNOLO|\bQ8\b|\bESSO\b|\bAGIP\b|ENI STATION/i',
                                                'categoria' => 'Benzina',     'note' => 'Pieno'],

        // ---- SPESA -----------------------------------------------------
        ['match' => '/BENNET/i',                'categoria' => 'Supermercato', 'note' => 'Bennet'],
        ['match' => '/FAMILA/i',                'categoria' => 'Supermercato', 'note' => 'Famila'],
        ['match' => '/\bCONAD\b/i',             'categoria' => 'Supermercato', 'note' => 'Conad'],
        ['match' => '/BANCO FRESCO/i',          'categoria' => 'Supermercato', 'note' => 'Banco Fresco'],
        ['match' => '/\bDM-? ?Fil/i',           'categoria' => 'Supermercato', 'note' => 'DM'],
        ['match' => '/IPERCOOP/i',              'categoria' => 'Supermercato', 'note' => 'Ipercoop'],
        ['match' => '/ESSELUNGA/i',             'categoria' => 'Supermercato', 'note' => 'Esselunga'],
        ['match' => '/\bLIDL\b/i',              'categoria' => 'Supermercato', 'note' => 'Lidl'],
        ['match' => '/MACELLERIA|SALUMERIA/i',  'categoria' => 'Supermercato', 'note' => 'Macelleria Salumeria'],

        // ---- SALUTE ----------------------------------------------------
        ['match' => '/PARAFARMACIA/i',          'categoria' => 'Visite/Esami/Farmaci', 'note' => 'Parafarmacia'],
        ['match' => '/FARMACIA/i',              'categoria' => 'Visite/Esami/Farmaci', 'note' => 'Farmacia'],
        ['match' => '/OSPEDALE/i',              'categoria' => 'Visite/Esami/Farmaci', 'note' => 'Ospedale'],

        // ---- LIFESTYLE: piccole spese (caffè, snack) sotto i 10 euro ----
        ['match' => '/AUTOGRILL/i',             'max' => 10, 'categoria' => 'Spese accessorie', 'note' => 'Caffe Autogrill'],
        ['match' => "/MCDONALD|BURGER KING/i",  'max' => 10, 'categoria' => 'Spese accessorie', 'note' => "McDonald's"],
        ['match' => '/FERRETTI BEACH HOTEL/i',  'categoria' => 'Spese accessorie', 'note' => 'Caffe'],
        ['match' => '/VERONA ARENA/i',          'categoria' => 'Spese accessorie', 'note' => 'Arena di Verona'],

        // ---- LIFESTYLE: pranzi e cene ----------------------------------
        ['match' => '/AUTOGRILL/i',             'categoria' => 'Pranzi/Cene', 'note' => 'Autogrill'],
        ['match' => "/MCDONALD|BURGER KING/i",  'categoria' => 'Pranzi/Cene', 'note' => "McDonald's"],
        ['match' => '/CALICANT(H)?US/i',        'categoria' => 'Pranzi/Cene', 'note' => 'Calicanthus'],
        ['match' => '/OSTERIA|TRATTORIA|RISTORANTE|PIZZERIA|BRASSERIE|\bPUB\b/i',
                                                'categoria' => 'Pranzi/Cene'],

        // ---- ABBIGLIAMENTO ---------------------------------------------
        ['match' => '/\bOVS\b/i',               'categoria' => 'Abbigliamento per tutti', 'note' => 'OVS'],
        ['match' => '/\bZARA\b|H&M/i',          'categoria' => 'Abbigliamento per tutti'],
        ['match' => '/DECATHLON/i',             'categoria' => 'Abbigliamento per tutti', 'note' => 'Decathlon'],
        ['match' => '/\bCHE 48\b/i',            'categoria' => 'Abbigliamento per tutti', 'note' => 'Che 48'],

        // Movimenti non riconosciuti restano "DA CLASSIFICARE": aggiungi qui
        // una regola, anche puntuale sul riferimento ordine, es.:
        // ['match' => '/407-5397504-8029935/', 'categoria' => 'Videogiochi', 'note' => 'Titolo gioco'],
    ],

    // ---- Report ordini Amazon Business (una riga per articolo) ----------
    // Le regole si applicano a "Amazon ordine <n> rif. <pagamento> ASIN <asin>: <titolo> (venditore: ...)",
    // quindi si può agganciare anche un singolo ordine o ASIN, es. ['match' => '/ASIN B0CKZ1SZ9M/', ...].
    'amazon' => [
        // Ordine del 26/06/2026 (batterie + omega 3) inserito a mano come "Amazon 29,99 + 20,05".
        ['match' => '/ordine 407-8921213-2074730/',                                          'ignora' => 'già inserito a mano'],
        ['match' => '/sakura|ohuhu|clairefontaine|fineliner|album da disegno|carta da disegno|pennarelli ad alcool|sketchbook/i',
                                                                                          'categoria' => 'Materiale disegno'],
        ['match' => '/naruto|batman|manga|fumett|\bvol\.?\s*\d+\)/i',                    'categoria' => 'Libri-Fumetti'],
        ['match' => '/nintendo switch|playstation|\bps5\b|xbox/i',                        'categoria' => 'Videogiochi'],
        ['match' => '/aspirapolvere|sacchetti|filtri hepa|detergente|scopa|ragnatore|insetticida|carioca|pennarelli lavabili/i',
                                                                                          'categoria' => 'Supermercato'],
        ['match' => '/switchbot|caricatore|wireless charger|batterie ricaricabili|\bcavo\b|adattatore|\bhub\b/i',
                                                                                          'categoria' => 'Tastiera/Mouse/Altro'],
        ['match' => '/giacca|stivali|scarpe|pantalon|maglia|felpa|costume|calze|playshoes/i',
                                                                                          'categoria' => 'Abbigliamento per tutti'],
        ['match' => '/omega ?3|integrator|vitamin/i',                                       'categoria' => 'Visite/Esami/Farmaci'],
    ],

    // ---- Lista attività PayPal --------------------------------------------
    // Le regole si applicano a "PayPal <tipo>: <esercente> \"<nota>\"".
    'paypal' => [
        ['match' => '/Google Cloud|Aruba|Cleverbridge/i', 'ignora' => 'costo di lavoro, fuori budget'],
        ['match' => '/\. Refunded/i',                     'ignora' => 'pagamento rimborsato'],
        ['match' => '/Regalo/i',                          'categoria' => 'Regali altri'],
        ['match' => '/Trenitalia|Italo/i',                'categoria' => 'Spese accessorie', 'note' => 'Treno'],
        ['match' => '/Strava/i',                          'categoria' => 'Iscrizioni Gare',  'note' => 'Abbonamento Strava'],
        ['match' => '/Topforsport|Top4Running/i',         'categoria' => 'Scarpe da corsa',  'note' => 'Scarpe Top4Running'],
        ['match' => '/Mister Tennis/i',                   'categoria' => 'Scarpe da corsa',  'note' => 'Scarpe Mister Tennis'],
        ['match' => '/VG GROUP/i',                        'categoria' => 'Supermercato',     'note' => 'Carbo gel corsa (VG Group)'],
        ['match' => '/Costa Edutainment/i',               'categoria' => 'Viaggi vacanze e soggiorni', 'note' => 'Acquario di Genova'],
    ],
];

