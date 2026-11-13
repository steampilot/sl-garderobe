# Garderobe

Kleine Website: Outfits durchblättern, unten WEAR. Mobil zuerst, im Handy-Browser als App nutzbar (Safari oder Chrome: „Zum Home-Bildschirm“).

Live: https://www.steampilot.ch/garderobe/

Kein PIN. Die Seite wird nicht von Suchmaschinen indexiert.

## 1. Website

Den Inhalt von `web/` nach `steampilot.ch/garderobe/` legen. Die Datei `web/api/config.example.php` nach `web/api/config.php` kopieren und dort den Token eintragen — derselbe gehört ins LSL-Skript. Den echten Token nicht auf GitHub legen.

Wenn `config.php` schon existiert, die Werte aus dem Example nachziehen: `parent_folder`, die Ordnernamen (`naked`, `negligee`) und den neuen Western-Eintrag. Die Website erst auf den Server legen, **nachdem** das neue Skript im OpenCollar liegt — sonst würde das alte Skript `@detachall:.outfits=force` senden und `.core` (Body, Skin, Shape) mit ausziehen.

## 2. Sektionen

Vier Reiter: **Layla**, **Jérôme**, **Cindy**, **Caprica**.

Bilder liegen unter `web/img/<Person>/<SL-Ordner>.webp` oder `.png`, Vorderansicht **832×1248**. Personenordner: `Layla`, `Steampilot`, `Cindy`, `Caprica`. Der Dateiname ist genau der Second-Life-Ordner. Die App findet die Endung selbst; ein abweichender Name kann in `config.php` unter `image` stehen.

- Layla: `default`, `black`, `western` (Bild: `western.png`), `negligee`, `naked`
- Steampilot (Jérôme): `jacket`, `tshirt`

Cindy- und Caprica-Bilder kommen nach, sobald sie generiert sind — gleicher Pfad, Eintrag in `web/api/config.php` (`folder` = SL-Ordnername, `who` = layla / jerome / cindy / caprica).

## 3. Ordner in Second Life

OpenCollar-Konvention: Jeder Avatar hat unter `#RLV` den Ordner `.outfits`. Darin liegen die Kleidungs-Outfits als **echte Kopien** (keine Links) — Namen genau ein Wort, kleingeschrieben. Body, Skin und Shape liegen in `#RLV/.outfits/.core` und werden von der Garderobe **nie** angefasst.

Layla:

- `default` (rote Jacke)
- `black` (schwarze Jacke)
- `western` (Cowboyhut, kariertes Hemd, Shorts, Stiefel)
- `negligee`
- `naked`

Jérôme (Steampilot Mysterious):

- `jacket` (Saint Celestine)
- `tshirt` (Caprica Shirt)

Also z. B. `#RLV/.outfits/default`, `#RLV/.outfits/western`. Das OpenCollar und das Chain-heart-Halsband kommen **nicht** in diese Ordner und werden nicht ausgezogen. Das Skript macht nur:

1. Für jeden konfigurierten Kleidungs-Ordner **ausser** `.core`: `@detachall:.outfits/<Ordner>=force`
2. fünf Sekunden warten
3. `@attachallover:.outfits/<Foldername>=force`

Niemals `@detachall` ohne Pfad, niemals `@detachall:.outfits=force` — das würde `.core` mit ausziehen.

Die Ordnernamen kannst du in `web/api/config.php` (`folder`) anpassen, falls sie bei dir anders heissen. OpenCollar steuert dieselben Ordner ingame.

## 4. Skript ins OpenCollar

Kein extra Prim. Dasselbe Skript `lsl/GarderobeWear.lsl` kommt in den Inhalt des OpenCollars (Root-Prim) und bleibt dort — den Namen des Halsbands nicht ändern. Es erkennt den Besitzer selbst (Steampilot / Cindy / Caprica / Layla) und hängt sich an die richtige Sektion.

1. Token und Website-URL sind im Skript schon eingetragen (bzw. den Token aus `config.php` einsetzen, wenn du vom Repo-Platzhalter kommst).
2. OpenCollar tragen, bearbeiten, das Skript in den Inhalt legen.
3. Im Chat sollte stehen: „Garderobe: mit der Website verbunden (jerome).“ — bzw. layla, cindy, caprica.

Nach Teleport oder Regions-Neustart holt sich das Skript eine neue URL und registriert sich von allein neu. Nach einem OpenCollar-Update das Skript einfach nochmal hineinlegen.

Wenn die Erkennung nicht greift, oben im Skript `WHO` setzen: `"layla"`, `"jerome"`, `"cindy"` oder `"caprica"`.

## 5. Was WEAR auslöst

Der Button spricht das Skript im OpenCollar **dieses** Avatars an **und** schickt mir eine Nachricht (`layla.strange+garderobe@mail.grokbot.com`). Bei mir (Layla) ziehe ich das Outfit selbst an, wenn das Skript gerade nicht verbunden ist.
