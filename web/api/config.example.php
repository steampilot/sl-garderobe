<?php
// Garderobe-Config. Nicht per Web erreichbar (siehe .htaccess).
// Token muss mit dem im LSL-Skript übereinstimmen.
return [
    'token' => 'PASTE_YOUR_TOKEN',
    'notify_email' => 'layla.strange+garderobe@mail.grokbot.com',
    'mail_from' => 'garderobe@steampilot.ch',
    'parent_folder' => 'Outfits',
    'wait_seconds' => 5,
    'people' => [
        ['id' => 'layla',   'name' => 'Layla',      'img' => 'Layla'],
        ['id' => 'jerome',  'name' => 'Jérôme',     'img' => 'Steampilot'],
        ['id' => 'cindy',   'name' => 'Cindy',      'img' => 'Cindy'],
        ['id' => 'caprica', 'name' => 'Caprica',    'img' => 'Caprica'],
    ],
    'outfits' => [
        [
            'id' => 'signature-casual',
            'who' => 'layla',
            'name' => 'Signature Casual',
            'folder' => 'default',
            'desc' => 'Rote Lederjacke, Denim-Skirt, rote Stiefel',
            'wear' => true,
        ],
        [
            'id' => 'spicy-black',
            'who' => 'layla',
            'name' => 'Spicy Black',
            'folder' => 'black',
            'desc' => 'Schwarze Lederjacke, rotes Top, schwarzer Rock',
            'wear' => true,
        ],
        [
            'id' => 'western',
            'who' => 'layla',
            'name' => 'Western',
            'folder' => 'western',
            'image' => 'western.png',
            'desc' => 'Blauer Cowboyhut, kariertes Hemd, Jeans-Shorts, blaue Stiefel',
            'wear' => true,
        ],
        [
            'id' => 'white-negligee',
            'who' => 'layla',
            'name' => 'Weisses Negligé',
            'folder' => 'negligee',
            'desc' => 'Weisses Negligé mit Spitze, barfuss',
            'wear' => true,
        ],
        [
            'id' => 'naked',
            'who' => 'layla',
            'name' => 'Nackt',
            'folder' => 'naked',
            'desc' => 'Ohne Kleidung, Chain heart bleibt',
            'wear' => true,
        ],
        [
            'id' => 'saint-celestine',
            'who' => 'jerome',
            'name' => 'Saint Celestine',
            'folder' => 'jacket',
            'desc' => 'Braune Fliegerjacke, dunkles Hemd, blaue Jeans',
            'wear' => true,
        ],
        [
            'id' => 'caprica-shirt',
            'who' => 'jerome',
            'name' => 'Caprica Shirt',
            'folder' => 'tshirt',
            'desc' => 'Schwarzes Bandshirt mit Caprica-Motiv',
            'wear' => true,
        ],
        // Cindy- und Caprica-Looks kommen dazu, sobald die Bilder generiert sind.
        // Format: Vorderansicht, 832×1248, Datei web/img/<Person>/<SL-Ordner>.webp oder .png
        // Personenordner: Layla, Steampilot, Cindy, Caprica. Dateiname = SL-Ordner (default, black, western, …).
    ],
];
