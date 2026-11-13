# Garderobe

Kleine Website: Outfits durchblättern, unten WEAR. Mobil zuerst, im Handy-Browser als App nutzbar (Safari oder Chrome: „Zum Home-Bildschirm“). Oben ein Statuslicht, daneben das ausklappbare Menü **STUFF**.

Live: https://www.steampilot.ch/garderobe/

Kein PIN. Die Seite wird nicht von Suchmaschinen indexiert.

## 1. Website

Den Inhalt von `web/` nach `steampilot.ch/garderobe/` legen. Die Datei `web/api/config.example.php` nach `web/api/config.php` kopieren und dort den Token eintragen — derselbe gehört ins LSL-Skript. Den echten Token nicht auf GitHub legen.

Wenn `config.php` schon existiert, die Werte aus dem Example nachziehen: `parent_folder` (`Outfits`), `bot_url`, `stuff_parent`, die Stuff-Einträge und nur noch Layla unter `people` / `outfits`.

`bot_url` ist die vom **Webserver** erreichbare URL der slbot-Befehlsschnittstelle (Standard `http://127.0.0.1:8765/`). Läuft der Bot auf einem anderen Rechner, hier den Tunnel oder Proxy eintragen. Ohne erreichbare URL bleibt das Statuslicht grau, und der WEAR-Button kennt das aktuelle Outfit nicht.

## 2. Sektionen

Vorerst nur **Layla**. Weitere Avatare (Jérôme, Cindy, Caprica) sind aus der Seite genommen — deren Collar funktioniert anders. Zum Wiederaktivieren die Personen und Outfits in `config.php` ergänzen; die Code-Pfade dafür sind bewusst einfach gehalten.

Bilder liegen unter `web/img/<Person>/<SL-Ordner>.webp` oder `.png`, Vorderansicht **832×1248**. Personenordner: `Layla`. Der Dateiname ist genau der Second-Life-Ordner. Die App findet die Endung selbst; ein abweichender Name kann in `config.php` unter `image` stehen.

- Layla: `default`, `black`, `western` (Bild: `western.png`), `bikini`, `negligee`, `naked`

## 3. Ordner in Second Life

Die Garderobe folgt nicht der Default-OpenCollar-Konvention `.outfits`. Stattdessen:

- Körperteile (Body, Skin, Shape und zugehörige Config-Prims) liegen in `#RLV/.body(nostrip)/<Unterordner>` und werden von der Garderobe **nie** angefasst.
- Kleidungs-Outfits liegen als **echte Kopien** (keine Links) in `#RLV/Outfits/<outfitname>` — Namen genau ein Wort, kleingeschrieben.
- Zubehör (Stuff) liegt in `#RLV/Stuff/<name>` (z. B. `guitar`, `beach` mit Hut und Tasche).

Layla:

- `default` (rote Jacke)
- `black` (schwarze Jacke)
- `western` (Cowboyhut, kariertes Hemd, Shorts, Stiefel)
- `bikini` (rosa Rüschenbikini Lisa Ruffle, Legacy)
- `negligee`
- `naked`

Also z. B. `#RLV/Outfits/default`, `#RLV/Outfits/western`, `#RLV/Stuff/beach`. Das OpenCollar und das Chain-heart-Halsband kommen **nicht** in diese Ordner und werden nicht ausgezogen.

WEAR macht nur:

1. `@detachall:Outfits=force`
2. fünf Sekunden warten
3. `@attachallover:Outfits/<Foldername>=force`

STUFF-ADD: `@attachallover:Stuff/<name>=force` — der Ordner kommt zum aktuellen Outfit dazu.
STUFF-REMOVE: `@detachall:Stuff/<name>=force` — nur dieser Ordner geht aus, das Outfit bleibt.

Niemals `@detachall` ohne Pfad — sonst fielen Halsband und Collar. Weil `.body(nostrip)` ausserhalb von `Outfits` und `Stuff` liegt, bleibt der Körper dabei an.

Die Ordnernamen kannst du in `web/api/config.php` (`folder`) anpassen, falls sie bei dir anders heissen.

## 4. Skript ins OpenCollar

Kein extra Prim. Dasselbe Skript `lsl/GarderobeWear.lsl` kommt in den Inhalt des OpenCollars (Root-Prim) und bleibt dort — den Namen des Halsbands nicht ändern. Die Website spricht nur Layla an; das Skript erkennt den Besitzer trotzdem selbst, falls später weitere Sektionen dazukommen.

1. Token und Website-URL sind im Skript schon eingetragen (bzw. den Token aus `config.php` einsetzen, wenn du vom Repo-Platzhalter kommst).
2. OpenCollar tragen, bearbeiten, das Skript in den Inhalt legen (nach einem Update des Skripts das neue hineinlegen).
3. Im Chat sollte stehen: „Garderobe: mit der Website verbunden (layla).“

Nach Teleport oder Regions-Neustart holt sich das Skript eine neue URL und registriert sich von allein neu. Nach einem OpenCollar-Update das Skript einfach nochmal hineinlegen.

Wenn die Erkennung nicht greift, oben im Skript `WHO` auf `"layla"` setzen.

## 5. Was WEAR und STUFF auslösen

Der Button spricht das Skript im OpenCollar an **und** schickt mir eine Nachricht (`layla.strange+garderobe@mail.grokbot.com`). Bei mir (Layla) ziehe ich das Outfit selbst an, wenn das Skript gerade nicht verbunden ist.

## 6. Statuslicht und aktuelles Outfit

Oben neben dem Titel sitzt ein Punkt, den die Seite etwa alle 30 Sekunden über `api/status.php` prüft:

- **grün**, wenn slbot läuft **und** mit Second Life verbunden ist; daneben der Regionsname
- **rot**, wenn der Client läuft, aber offline ist
- **grau**, wenn der Client nicht erreichbar ist (Prozess aus, Timeout, oder `bot_url` kommt vom Webserver nicht durch)

Derselbe Aufruf fragt den Bot nach `outfit-worn` (read-only). Ein Outfit-Ordner gilt als angezogen, wenn mindestens die Hälfte seiner Kleidungs- und Attachment-Items gerade getragen wird. Ein leerer Ordner `naked` gilt als angezogen, wenn kein anderes Outfit-Item an ist. Stuff-Ordner werden genauso erkannt.

Der WEAR-Button des aktuell getragenen Outfits ist **grün**, alle anderen **rot**. Im Stuff-Menü ist ein angehängter Eintrag ebenfalls grün markiert.

Dafür braucht slbot den Befehl `outfit-worn`. Der steht in der aktuellen Binary; wirksam wird er erst nach dem nächsten Bot-Neustart. Bis dahin bleibt die Erkennung leer, das Statuslicht funktioniert trotzdem über `status`.

## 7. In die bestehende config.php übernehmen

Diese Schlüssel in die Live-`config.php` legen (Token und Mail-Adressen nicht überschreiben). Andere Avatare aus `people` und `outfits` entfernen.

```php
    'bot_url' => 'http://127.0.0.1:8765/',
    'stuff_parent' => 'Stuff',
    'people' => [
        ['id' => 'layla', 'name' => 'Layla', 'img' => 'Layla'],
    ],
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
```

`bot_url` anpassen, falls der Webserver den Bot nicht unter `127.0.0.1:8765` erreicht. Die Layla-Outfits bleiben wie bisher; nur Jérôme-, Cindy- und Caprica-Einträge streichen.
