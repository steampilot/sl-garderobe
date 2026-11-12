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
        ['id' => 'layla',   'name' => 'Layla'],
        ['id' => 'jerome',  'name' => 'Jérôme'],
        ['id' => 'cindy',   'name' => 'Cindy'],
        ['id' => 'caprica', 'name' => 'Caprica'],
    ],
    'outfits' => [
        [
            'id' => 'signature-casual',
            'who' => 'layla',
            'name' => 'Signature Casual',
            'folder' => 'Signature Casual',
            'desc' => 'Rote Lederjacke, Denim-Skirt, rote Stiefel',
            'wear' => true,
        ],
        [
            'id' => 'spicy-black',
            'who' => 'layla',
            'name' => 'Spicy Black',
            'folder' => 'Spicy Black',
            'desc' => 'Schwarze Lederjacke, rotes Top, schwarzer Rock',
            'wear' => true,
        ],
        [
            'id' => 'white-negligee',
            'who' => 'layla',
            'name' => 'Weisses Negligé',
            'folder' => 'White Negligee',
            'desc' => 'Weisses Negligé mit Spitze, barfuss',
            'wear' => true,
        ],
        [
            'id' => 'saint-celestine',
            'who' => 'jerome',
            'name' => 'Saint Celestine',
            'folder' => 'Saint Celestine',
            'desc' => 'Braune Fliegerjacke, dunkles Hemd, blaue Jeans',
            'wear' => true,
        ],
        [
            'id' => 'caprica-shirt',
            'who' => 'jerome',
            'name' => 'Caprica Shirt',
            'folder' => 'Caprica Shirt',
            'desc' => 'Schwarzes Bandshirt mit Caprica-Motiv',
            'wear' => true,
        ],
        // Cindy- und Caprica-Looks kommen dazu, sobald die Bilder generiert sind.
        // Format: Vorderansicht, 832×1248, Datei web/img/<id>.webp
    ],
];
