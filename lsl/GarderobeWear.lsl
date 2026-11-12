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
// Bei WEAR auf der Website:
//   1. @detachall:Outfits=force
//   2. fünf Sekunden warten
//   3. @attachallover:Outfits/<Foldername>=force
//
// Nur der Ordner "Outfits" unter #RLV wird angefasst.
// Niemals @detachall ohne Ordner — sonst fielen Halsband und Collar.

string  SITE   = "https://www.steampilot.ch/garderobe/api/register.php";
string  TOKEN  = "PASTE_YOUR_TOKEN";
string  PARENT = "Outfits";   // #RLV/Outfits/<Foldername>
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
        if (folder == "" || parent == "")
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

        // Nur den Outfits-Ordner ausziehen, niemals @detachall ohne Pfad.
        llOwnerSay("@detachall:" + parent + "=force");
        llOwnerSay("Garderobe: ziehe " + parent + " aus, ziehe " + folder + " in " + (string)((integer)wait) + " Sekunden an.");
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
