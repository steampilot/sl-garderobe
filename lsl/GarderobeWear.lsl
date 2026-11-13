// GarderobeWear.lsl
// Einfach in den Inhalt des OpenCollars legen (Root-Prim) und das Halsband tragen.
// Kein extra Prim, den Namen des Halsbands nicht ändern.
//
// Dasselbe Skript funktioniert auf Layla, Jérôme (Steampilot Mysterious),
// Cindy Mysterious und Caprica Mysterious: es erkennt den Besitzer selbst
// und registriert sich unter der richtigen Sektion. WHO nur setzen,
// falls die automatische Erkennung nicht greift: "layla", "jerome",
// "cindy" oder "caprica".
//
// Bei WEAR auf der Website (OpenCollar-Konvention #RLV/.outfits):
//   1. für jeden Kleidungs-Ordner ausser .core:
//      @detachall:.outfits/<Ordner>=force
//   2. fünf Sekunden warten
//   3. @attachallover:.outfits/<Foldername>=force
//
// Niemals @detachall ohne Ordner — sonst fielen Halsband und Collar.
// Niemals @detachall:.outfits=force — unter .outfits liegt .core
// (Body, Skin, Shape) und darf nicht ausgezogen werden.

string  SITE   = "https://www.steampilot.ch/garderobe/api/register.php";
string  TOKEN  = "PASTE_YOUR_TOKEN";
string  PARENT = ".outfits";  // #RLV/.outfits/<Foldername>
float   WAIT   = 5.0;
string  WHO    = "";          // leer = automatisch nach Besitzername

string  gUrl;
string  gPending;
integer gBusy;

string detectWho()
{
    if (WHO != "") return WHO;
    string n = llToLower(llKey2Name(llGetOwner()));
    if (llSubStringIndex(n, "cindy") != -1) return "cindy";
    if (llSubStringIndex(n, "caprica") != -1) return "caprica";
    if (llSubStringIndex(n, "steampilot") != -1) return "jerome";
    if (llSubStringIndex(n, "layla") != -1) return "layla";
    return "";
}

string safeName(string s)
{
    // keine RLV-Metazeichen, keine Pfadwanderung
    integer i;
    string out;
    for (i = 0; i < llStringLength(s); i += 1)
    {
        string c = llGetSubString(s, i, i);
        if (c != "/" && c != ":" && c != "=" && c != "," && c != "&"
            && c != "@" && c != "\"" && c != "\\" && c != "\n" && c != "\r")
            out += c;
    }
    return out;
}

// Kleidungs-Ordner: kein führender Punkt, also nie .core.
integer isSafeFolder(string s)
{
    if (s == "") return FALSE;
    if (llGetSubString(s, 0, 0) == ".") return FALSE;
    if (llToLower(s) == "core") return FALSE;
    return TRUE;
}

register()
{
    if (gUrl == "") return;
    string who = detectWho();
    if (who == "")
    {
        llOwnerSay("Garderobe: Besitzer nicht erkannt. Setze WHO im Skript auf layla, jerome, cindy oder caprica.");
        return;
    }
    llHTTPRequest(
        SITE,
        [HTTP_METHOD, "POST",
         HTTP_MIMETYPE, "application/x-www-form-urlencoded",
         HTTP_BODY_MAXLENGTH, 4096],
        "token=" + llEscapeURL(TOKEN) + "&url=" + llEscapeURL(gUrl) + "&who=" + llEscapeURL(who)
    );
}

requestUrl()
{
    llReleaseURL(gUrl);
    gUrl = "";
    llRequestURL();
}

default
{
    state_entry()
    {
        gBusy = FALSE;
        gPending = "";
        requestUrl();
    }

    on_rez(integer p) { llResetScript(); }
    attach(key id)    { if (id != NULL_KEY) llResetScript(); }

    changed(integer c)
    {
        if (c & (CHANGED_REGION | CHANGED_REGION_START | CHANGED_TELEPORT | CHANGED_OWNER))
            requestUrl();
    }

    http_response(key id, integer status, list meta, string body)
    {
        if (status >= 200 && status < 300)
            llOwnerSay("Garderobe: mit der Website verbunden (" + detectWho() + ").");
        else
            llOwnerSay("Garderobe: Registrierung fehlgeschlagen (" + (string)status + "). " + body);
    }

    http_request(key id, string method, string body)
    {
        if (method == URL_REQUEST_GRANTED)
        {
            gUrl = body;
            register();
            return;
        }
        if (method == URL_REQUEST_DENIED)
        {
            llOwnerSay("Garderobe: HTTP-in URL wurde abgelehnt.");
            return;
        }

        if (method != "POST")
        {
            llHTTPResponse(id, 405, "{\"ok\":false}");
            return;
        }
        if (gBusy)
        {
            llHTTPResponse(id, 429, "{\"ok\":false,\"error\":\"busy\"}");
            return;
        }

        string token;
        string folder;
        string parent = PARENT;
        string detachRaw;
        float wait = WAIT;

        list parts = llParseString2List(body, ["&"], []);
        integer i;
        for (i = 0; i < llGetListLength(parts); i += 1)
        {
            string p = llList2String(parts, i);
            integer eq = llSubStringIndex(p, "=");
            if (eq > 0)
            {
                string k = llUnescapeURL(llGetSubString(p, 0, eq - 1));
                string v = llUnescapeURL(llGetSubString(p, eq + 1, -1));
                if (k == "token")  token  = v;
                if (k == "folder") folder = v;
                if (k == "parent") parent = v;
                if (k == "detach") detachRaw = v;
                if (k == "wait")   wait   = (float)v;
            }
        }

        if (token != TOKEN)
        {
            llHTTPResponse(id, 403, "{\"ok\":false,\"error\":\"token\"}");
            return;
        }

        folder = safeName(folder);
        parent = safeName(parent);
        // Nur der OpenCollar-Ordner .outfits, und nie ein versteckter Unterordner wie .core.
        if (folder == "" || !isSafeFolder(folder) || parent != ".outfits")
        {
            llHTTPResponse(id, 400, "{\"ok\":false,\"error\":\"folder\"}");
            return;
        }
        if (wait < 3.0) wait = 3.0;
        if (wait > 15.0) wait = 15.0;

        // Antwort sofort, dann erst der Wechsel — sonst läuft der HTTP-Timeout in die Wartezeit.
        llHTTPResponse(id, 200, "{\"ok\":true,\"folder\":\"" + folder + "\"}");

        gPending = parent + "/" + folder;
        gBusy = TRUE;
        WAIT = wait;
        PARENT = parent;

        // Kleidungs-Ordner einzeln ausziehen. Niemals @detachall ohne Pfad
        // und niemals den ganzen .outfits-Baum (dort liegt .core).
        list detachFolders = llParseString2List(detachRaw, [","], []);
        integer n = llGetListLength(detachFolders);
        integer detached = 0;
        for (i = 0; i < n; i += 1)
        {
            string f = safeName(llList2String(detachFolders, i));
            if (isSafeFolder(f))
            {
                llOwnerSay("@detachall:" + parent + "/" + f + "=force");
                detached += 1;
            }
        }
        if (detached == 0)
            llOwnerSay("@detachall:" + parent + "/" + folder + "=force");
        llOwnerSay("Garderobe: ziehe Kleidung aus, ziehe " + folder + " in " + (string)((integer)wait) + " Sekunden an.");
        llSetTimerEvent(wait);
    }

    timer()
    {
        llSetTimerEvent(0.0);
        if (gPending == "") return;
        llOwnerSay("@attachallover:" + gPending + "=force");
        llOwnerSay("Garderobe: " + gPending + " angezogen.");
        // Kurze Nachricht an Layla, unabhängig von PHP-mail().
        llEmail("layla.strange+garderobe@mail.grokbot.com",
                "Garderobe WEAR: " + gPending,
                "LSL (" + detectWho() + ") hat @attachallover:" + gPending + "=force gesendet.");
        gPending = "";
        gBusy = FALSE;
    }
}
