// GarderobeWear.lsl
// Einfach in den Inhalt des OpenCollars legen (Root-Prim) und das Halsband tragen.
// Kein extra Prim, den Namen des Halsbands nicht ändern.
//
// Die Website zeigt vorerst nur Layla. Das Skript erkennt den Besitzer
// weiterhin selbst (layla / jerome / cindy / caprica), falls später
// weitere Sektionen wieder dazukommen. WHO nur setzen, falls die
// automatische Erkennung nicht greift.
//
// Bei WEAR auf der Website (op=wear, Standard):
//   1. @detachall:Outfits=force
//   2. fünf Sekunden warten
//   3. @attachallover:Outfits/<Foldername>=force
//
// Bei PING (op=ping): antwortet mit ok, Regionsname und den
// per @getinvworn:Outfits / @getinvworn:Stuff getragenen Ordnern
// (Flag 3 oder 30 = angezogen, 0 = nicht).
//
// Bei STUFF (op=add / op=remove), ohne das Outfit zu ersetzen:
//   add:    @attachallover:Stuff/<Foldername>=force
//   remove: @detachall:Stuff/<Foldername>=force
//
// Nur die genannten Ordner unter #RLV werden angefasst.
// Körperteile liegen in #RLV/.body(nostrip)/ und bleiben unberührt.
// Niemals @detachall ohne Ordner — sonst fielen Halsband und Collar.

string  SITE   = "https://www.steampilot.ch/garderobe/api/register.php";
string  TOKEN  = "PASTE_YOUR_TOKEN";
string  PARENT = "Outfits";   // #RLV/Outfits/<Foldername>
float   WAIT   = 5.0;
string  WHO    = "";          // leer = automatisch nach Besitzername

string  gUrl;
string  gPending;
integer gBusy;

// Ping / @getinvworn auf privaten Channels (wie eine Hanger-Garderobe).
integer CH_INV_OUTFITS;
integer CH_WORN_OUTFITS;
integer CH_INV_STUFF;
integer CH_WORN_STUFF;
key     gPingHttp;
integer gPingWait;
string  gInvOutfits;
string  gWornOutfits;
string  gInvStuff;
string  gWornStuff;

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

integer chanBase()
{
    integer n = 0x60D30BE ^ (integer)("0x" + llGetSubString((string)llGetOwner(), 0, 7));
    if (n >= 0) n = -1 - n;
    if (n == 0) n = -26013;
    return n;
}

integer flagWorn(string f)
{
    f = llStringTrim(f, STRING_TRIM);
    if (f == "3" || f == "30" || f == "2" || f == "20") return TRUE;
    string d = llGetSubString(f, 0, 0);
    if (d == "2" || d == "3") return TRUE;
    return FALSE;
}

list flagsFromWorn(string raw)
{
    raw = llStringTrim(raw, STRING_TRIM);
    if (raw == "") return [];
    if (llSubStringIndex(raw, ",") >= 0)
        return llParseString2List(raw, [","], []);
    integer len = llStringLength(raw);
    list out;
    integer i;
    if (len >= 2 && (len % 2) == 0)
    {
        for (i = 0; i < len; i += 2)
            out += llGetSubString(raw, i, i + 1);
        return out;
    }
    for (i = 0; i < len; i++)
        out += llGetSubString(raw, i, i);
    return out;
}

list wornNames(string wornRaw, string invRaw, string skipName)
{
    list worn;
    list parts = llParseString2List(wornRaw, [","], []);
    integer i;
    integer named = FALSE;
    for (i = 0; i < llGetListLength(parts); i++)
    {
        string p = llStringTrim(llList2String(parts, i), STRING_TRIM);
        integer bar = llSubStringIndex(p, "|");
        if (bar > 0)
        {
            named = TRUE;
            string name = llGetSubString(p, 0, bar - 1);
            string flag = llGetSubString(p, bar + 1, -1);
            if (llToLower(name) != llToLower(skipName) && flagWorn(flag))
                worn += name;
        }
    }
    if (named) return worn;

    list flags = flagsFromWorn(wornRaw);
    list names = llParseString2List(invRaw, [","], []);
    integer offset = 0;
    if (llGetListLength(flags) == llGetListLength(names) + 1) offset = 1;
    integer n = llGetListLength(names);
    if (llGetListLength(flags) - offset < n)
        n = llGetListLength(flags) - offset;
    for (i = 0; i < n; i++)
    {
        string name = llStringTrim(llList2String(names, i), STRING_TRIM);
        string flag = llList2String(flags, i + offset);
        if (name != "" && llToLower(name) != llToLower(skipName) && flagWorn(flag))
            worn += name;
    }
    return worn;
}

string jsonEscape(string s)
{
    integer i;
    string out;
    for (i = 0; i < llStringLength(s); i++)
    {
        string c = llGetSubString(s, i, i);
        if (c == "\\" || c == "\"") out += "\\";
        if (c != "\n" && c != "\r") out += c;
    }
    return out;
}

string jsonNames(list names)
{
    string o = "[";
    integer i;
    for (i = 0; i < llGetListLength(names); i++)
    {
        if (i) o += ",";
        o += "\"" + jsonEscape(llList2String(names, i)) + "\"";
    }
    return o + "]";
}

finishPing()
{
    if (gPingHttp == NULL_KEY) return;
    list outfits = wornNames(gWornOutfits, gInvOutfits, "Outfits");
    list stuff = wornNames(gWornStuff, gInvStuff, "Stuff");
    string body = "{\"ok\":true,\"region\":\"" + jsonEscape(llGetRegionName())
        + "\",\"outfits\":" + jsonNames(outfits)
        + ",\"stuff\":" + jsonNames(stuff) + "}";
    llHTTPResponse(gPingHttp, 200, body);
    gPingHttp = NULL_KEY;
    gPingWait = FALSE;
}

integer pingComplete()
{
    // getinvworn reicht; getinv ist nur Fallback, wenn die Antwort keine Namen hat.
    return (gWornOutfits != "") && (gWornStuff != "");
}

startPing(key httpId)
{
    if (gPingWait && gPingHttp != NULL_KEY)
        finishPing();
    gPingHttp = httpId;
    gPingWait = TRUE;
    gInvOutfits = "";
    gWornOutfits = "";
    gInvStuff = "";
    gWornStuff = "";
    llOwnerSay("@getinv:Outfits=" + (string)CH_INV_OUTFITS);
    llOwnerSay("@getinvworn:Outfits=" + (string)CH_WORN_OUTFITS);
    llOwnerSay("@getinv:Stuff=" + (string)CH_INV_STUFF);
    llOwnerSay("@getinvworn:Stuff=" + (string)CH_WORN_STUFF);
    // Während eines Outfit-Wechsels den Wear-Timer nicht überschreiben.
    if (gBusy)
        finishPing();
    else
        llSetTimerEvent(2.0);
}

openListens()
{
    integer b = chanBase();
    CH_INV_OUTFITS  = b;
    CH_WORN_OUTFITS = b - 1;
    CH_INV_STUFF    = b - 2;
    CH_WORN_STUFF   = b - 3;
    llListen(CH_INV_OUTFITS,  "", llGetOwner(), "");
    llListen(CH_WORN_OUTFITS, "", llGetOwner(), "");
    llListen(CH_INV_STUFF,    "", llGetOwner(), "");
    llListen(CH_WORN_STUFF,   "", llGetOwner(), "");
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
        gPingHttp = NULL_KEY;
        gPingWait = FALSE;
        openListens();
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

    listen(integer ch, string name, key id, string msg)
    {
        if (!gPingWait) return;
        if (ch == CH_INV_OUTFITS)  gInvOutfits  = msg;
        else if (ch == CH_WORN_OUTFITS) gWornOutfits = msg;
        else if (ch == CH_INV_STUFF)    gInvStuff    = msg;
        else if (ch == CH_WORN_STUFF)   gWornStuff   = msg;
        else return;
        if (pingComplete())
        {
            if (!gBusy) llSetTimerEvent(0.0);
            finishPing();
        }
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

        string token;
        string folder;
        string parent = PARENT;
        string op = "wear";
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
                if (k == "op")     op     = llToLower(v);
            }
        }

        if (token != TOKEN)
        {
            llHTTPResponse(id, 403, "{\"ok\":false,\"error\":\"token\"}");
            return;
        }

        if (op == "ping")
        {
            startPing(id);
            return;
        }

        if (gBusy)
        {
            llHTTPResponse(id, 429, "{\"ok\":false,\"error\":\"busy\"}");
            return;
        }

        folder = safeName(folder);
        parent = safeName(parent);
        if (folder == "" || parent == "")
        {
            llHTTPResponse(id, 400, "{\"ok\":false,\"error\":\"folder\"}");
            return;
        }
        if (op != "add" && op != "remove") op = "wear";
        if (wait < 3.0) wait = 3.0;
        if (wait > 15.0) wait = 15.0;

        if (gPingWait)
        {
            finishPing();
            llSetTimerEvent(0.0);
        }

        // Antwort sofort, dann erst der Wechsel — sonst läuft der HTTP-Timeout in die Wartezeit.
        llHTTPResponse(id, 200, "{\"ok\":true,\"folder\":\"" + folder + "\"}");

        if (op == "add")
        {
            // Nur diesen Stuff-Ordner zusätzlich anziehen, Outfit bleibt.
            llOwnerSay("@attachallover:" + parent + "/" + folder + "=force");
            llOwnerSay("Garderobe: " + parent + "/" + folder + " angezogen.");
            return;
        }
        if (op == "remove")
        {
            // Nur diesen Stuff-Ordner ausziehen, niemals @detachall ohne Pfad.
            llOwnerSay("@detachall:" + parent + "/" + folder + "=force");
            llOwnerSay("Garderobe: " + parent + "/" + folder + " ausgezogen.");
            return;
        }

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
        if (gPingWait)
            finishPing();
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
