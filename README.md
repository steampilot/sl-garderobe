# Garderobe

Kleine Website: Outfits durchblättern, unten WEAR. Mobil zuerst, im Handy-Browser als App nutzbar (Safari oder Chrome: „Zum Home-Bildschirm“).

Live: https://www.steampilot.ch/garderobe/

Kein PIN. Die Seite wird nicht von Suchmaschinen indexiert.

## 1. Website

Den Inhalt von `web/` nach `steampilot.ch/garderobe/` legen. Die Datei `web/api/config.example.php` nach `web/api/config.php` kopieren und dort den Token eintragen — derselbe gehört ins LSL-Skript. Den echten Token nicht auf GitHub legen.

## 2. Sektionen

Vier Reiter: **Layla**, **Jérôme**, **Cindy**, **Caprica**.

Bereits mit Bildern (alle 832×1248, nur Vorderansicht):

- Layla: `Signature Casual`, `Spicy Black`, `White Negligee`
- Jérôme: `Saint Celestine`, `Caprica Shirt`

Cindy- und Caprica-Bilder kommen nach, sobald sie generiert sind. Format: Vorderansicht, **832×1248**, Datei `web/img/<id>.webp`, Eintrag in `web/api/config.php`.

## 3. Ordner in Second Life

Jeder Avatar hat unter `#RLV` einen eigenen Ordner `Outfits`, darin je einen Ordner — Namen genau so:

Layla:

- `Signature Casual`
- `Spicy Black`
- `White Negligee`

Jérôme (Steampilot Mysterious):

- `Saint Celestine`
- `Caprica Shirt`

Hinein: Kopien (oder Links) der Kleidung. Das OpenCollar und das Chain-heart-Halsband kommen **nicht** in diese Ordner und werden nicht ausgezogen. Das Skript macht nur:

1. `@detachall:Outfits=force`
2. fünf Sekunden warten
3. `@attachallover:Outfits/<Foldername>=force`

Die Ordnernamen kannst du in `web/api/config.php` (`folder`) anpassen, falls sie bei dir anders heissen.

## 4. Skript ins OpenCollar

Kein extra Prim. Dasselbe Skript `lsl/GarderobeWear.lsl` kommt in den Inhalt des OpenCollars (Root-Prim) und bleibt dort — den Namen des Halsbands nicht ändern. Es erkennt den Besitzer selbst (Steampilot / Cindy / Caprica / Layla) und hängt sich an die richtige Sektion.

1. Token und Website-URL sind im Skript schon eingetragen (bzw. den Token aus `config.php` einsetzen, wenn du vom Repo-Platzhalter kommst).
2. OpenCollar tragen, bearbeiten, das Skript in den Inhalt legen.
3. Im Chat sollte stehen: „Garderobe: mit der Website verbunden (jerome).“ — bzw. layla, cindy, caprica.

Nach Teleport oder Regions-Neustart holt sich das Skript eine neue URL und registriert sich von allein neu. Nach einem OpenCollar-Update das Skript einfach nochmal hineinlegen.

Wenn die Erkennung nicht greift, oben im Skript `WHO` setzen: `"layla"`, `"jerome"`, `"cindy"` oder `"caprica"`.

## 5. Was WEAR auslöst

Der Button spricht das Skript im OpenCollar **dieses** Avatars an **und** schickt mir eine Nachricht (`layla.strange+garderobe@mail.grokbot.com`). Bei mir (Layla) ziehe ich das Outfit selbst an, wenn das Skript gerade nicht verbunden ist.
