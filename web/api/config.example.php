<?php
// Garderobe-Config. Nicht per Web erreichbar (siehe .htaccess).
// Token muss mit dem im LSL-Skript übereinstimmen.
// Echte Datei: diese Vorlage nach config.php kopieren (nicht ins Git).
return [
    'token' => 'PASTE_YOUR_TOKEN',
    'notify_email' => 'layla.strange+garderobe@mail.grokbot.com',
    'mail_from' => 'garderobe@steampilot.ch',
    'parent_folder' => 'Outfits',
    'wait_seconds' => 5,
    // Optional. Health-Check und getragenes Outfit laufen über das Collar-Skript.
    // bot_url nur setzen, wenn der Webserver den Bot wirklich erreicht — dann
    // dient outfit-worn als Fallback, falls der Collar-Ping die Flags nicht liefert.
    // 'bot_url' => 'http://127.0.0.1:8765/',
    'stuff_parent' => 'Stuff',
    // Vorerst nur Layla. Weitere Avatare (jerome, cindy, caprica) später wieder
    // hier eintragen — deren Collar funktioniert anders und wird gerade nicht gebraucht.
    'people' => [
        ['id' => 'layla', 'name' => 'Layla', 'img' => 'Layla'],
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
            'id' => 'bikini',
            'who' => 'layla',
            'name' => 'Bikini',
            'folder' => 'bikini',
            'desc' => 'Rosa Rüschenbikini (Hut und Tasche liegen in Stuff/beach)',
            'wear' => true,
        ],
        [
            'id' => 'western',
            'who' => 'layla',
            'name' => 'Western',
            'folder' => 'western',
            'desc' => 'BLauer Cowboyhut, kariertes Hemd, Jeans-Shorts, blaue Stiefel',
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
        // Personenordner: Layla (weitere: Steampilot, Cindy, Caprica).
    ],
    // Zubehör unter #RLV/Stuff/<folder>. ADD hängt den Ordner zusätzlich an,
    // REMOVE zieht nur diesen Ordner aus — das Outfit bleibt.
    'stuff' => [
        [
            'id' => 'guitar',
            'name' => 'Gitarre',
            'folder' => 'guitar',
            'desc' => 'Gitarre',
        ],
        [
            'id' => 'beach',
            'name' => 'Strand',
            'folder' => 'beach',
            'desc' => 'Hut und Handtasche',
        ],
    ],
];
