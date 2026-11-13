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
    // Vom Webserver erreichbare URL der slbot-Befehlsschnittstelle.
    // Standard ist localhost; liegt der Bot woanders, hier den Tunnel oder Proxy eintragen.
    'bot_url' => 'http://127.0.0.1:8765/',
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
            'id' => 'bikini',
            'who' => 'layla',
            'name' => 'Bikini',
            'folder' => 'bikini',
            'desc' => 'Rosa Rüschenbikini (Hut und Tasche liegen in Stuff/beach)',
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
        // Weitere Personen später so ergänzen:
        // ['id' => 'saint-celestine', 'who' => 'jerome', 'name' => 'Saint Celestine', 'folder' => 'jacket', 'desc' => '…', 'wear' => true],
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
