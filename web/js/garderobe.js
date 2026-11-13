(() => {
  const $ = (s, r = document) => r.querySelector(s);

  const imgA = $('#img-a');
  const imgB = $('#img-b');
  const nameEl = $('#look-name');
  const descEl = $('#look-desc');
  const wearBtn = $('#wear');
  const statusEl = $('#status');
  const dotsEl = $('#dots');
  const whoEl = $('#who');
  const frame = $('#frame');
  const stage = $('#stage');
  const emptyEl = $('#empty');
  const emptyWho = $('#empty-who');
  const metaEl = $('#meta');
  const healthEl = $('#health');
  const healthText = $('#health-text');
  const stuffToggle = $('#stuff-toggle');
  const stuffPanel = $('#stuff-panel');
  const stuffBackdrop = $('#stuff-backdrop');
  const stuffList = $('#stuff-list');
  const stuffClose = $('#stuff-close');

  let all = [];
  let stuff = [];
  let people = [{ id: 'layla', name: 'Layla', img: 'Layla' }];
  let who = 'layla';
  let index = 0;
  let frontIsA = true;
  let busy = false;
  let wornOutfits = []; // folder names under #RLV/Outfits
  let wornStuff = [];   // folder names under #RLV/Stuff

  const list = () => all.filter((o) => o.who === who);
  const current = () => list()[index] || null;
  const imgDir = (whoId) => {
    const p = people.find((x) => x.id === whoId);
    if (p && p.img) return p.img;
    return ({ layla: 'Layla' })[whoId] || whoId;
  };
  const imgUrl = (o) => {
    if (o.image && o.image.indexOf('/') !== -1) return o.image;
    return 'img/' + encodeURIComponent(imgDir(o.who)) + '/' + encodeURIComponent(o.image || (o.folder + '.webp'));
  };
  const personName = (id) => (people.find((p) => p.id === id) || { name: id }).name;
  const folderWorn = (folder, kind) => {
    const names = kind === 'stuff' ? wornStuff : wornOutfits;
    const needle = String(folder || '').toLowerCase();
    return names.some((n) => String(n).toLowerCase() === needle);
  };

  function setStatus(text, kind) {
    statusEl.textContent = text || '';
    statusEl.className = 'status' + (kind ? ' ' + kind : '');
  }

  function paintWearButton() {
    const o = current();
    const on = !!(o && folderWorn(o.folder, 'outfit'));
    wearBtn.classList.toggle('worn', on);
    wearBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
  }

  function renderDots() {
    const items = list();
    dotsEl.innerHTML = '';
    items.forEach((o, i) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.setAttribute('aria-label', o.name);
      if (i === index) b.setAttribute('aria-current', 'true');
      if (folderWorn(o.folder, 'outfit')) b.classList.add('worn');
      b.addEventListener('click', () => go(i));
      dotsEl.appendChild(b);
    });
  }

  function showLook(animate) {
    const items = list();
    const isEmpty = items.length === 0;
    stage.hidden = isEmpty;
    emptyEl.hidden = !isEmpty;
    dotsEl.hidden = isEmpty;
    metaEl.hidden = isEmpty;
    if (isEmpty) {
      emptyWho.textContent = personName(who);
      wearBtn.classList.add('hidden');
      wearBtn.classList.remove('worn');
      setStatus('');
      return;
    }
    if (index < 0) index = items.length - 1;
    if (index >= items.length) index = 0;
    const o = items[index];
    const incoming = frontIsA ? imgB : imgA;
    const outgoing = frontIsA ? imgA : imgB;
    incoming.src = imgUrl(o);
    incoming.alt = o.name;
    const reveal = () => {
      incoming.classList.add('on');
      outgoing.classList.remove('on');
      frontIsA = !frontIsA;
    };
    if (animate && incoming.complete) reveal();
    else if (animate) incoming.onload = () => { incoming.onload = null; reveal(); };
    else {
      incoming.classList.add('on');
      outgoing.classList.remove('on');
      outgoing.removeAttribute('src');
      frontIsA = !frontIsA;
    }
    nameEl.textContent = o.name;
    descEl.textContent = o.desc;
    wearBtn.classList.toggle('hidden', !o.wear);
    wearBtn.disabled = !o.wear || busy;
    paintWearButton();
    renderDots();
    setStatus('');
  }

  function go(i) {
    const n = list().length;
    if (!n) return;
    index = ((i % n) + n) % n;
    showLook(true);
  }

  function switchWho(next) {
    if (next === who) return;
    who = next;
    index = 0;
    whoEl.querySelectorAll('button').forEach((b) => {
      b.setAttribute('aria-selected', b.dataset.who === who ? 'true' : 'false');
    });
    showLook(true);
  }

  function renderStuff() {
    stuffList.innerHTML = '';
    if (!stuff.length) {
      const li = document.createElement('li');
      li.className = 'stuff-empty';
      li.textContent = 'Kein Stuff eingetragen.';
      stuffList.appendChild(li);
      return;
    }
    stuff.forEach((s) => {
      const on = folderWorn(s.folder, 'stuff');
      const li = document.createElement('li');
      li.className = 'stuff-item' + (on ? ' added' : '');
      li.dataset.id = s.id;

      const info = document.createElement('div');
      info.className = 'stuff-info';
      const title = document.createElement('h3');
      title.textContent = s.name;
      const desc = document.createElement('p');
      desc.textContent = s.desc || '';
      info.appendChild(title);
      if (s.desc) info.appendChild(desc);

      const actions = document.createElement('div');
      actions.className = 'stuff-actions';
      const addBtn = document.createElement('button');
      addBtn.type = 'button';
      addBtn.className = 'stuff-add' + (on ? ' on' : '');
      addBtn.textContent = 'ADD';
      addBtn.disabled = busy;
      addBtn.addEventListener('click', () => stuffAction(s, 'add'));
      const remBtn = document.createElement('button');
      remBtn.type = 'button';
      remBtn.className = 'stuff-remove';
      remBtn.textContent = 'REMOVE';
      remBtn.disabled = busy;
      remBtn.addEventListener('click', () => stuffAction(s, 'remove'));
      actions.appendChild(addBtn);
      actions.appendChild(remBtn);

      li.appendChild(info);
      li.appendChild(actions);
      stuffList.appendChild(li);
    });
  }

  function setStuffOpen(open) {
    stuffPanel.hidden = !open;
    stuffBackdrop.hidden = !open;
    stuffToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.classList.toggle('stuff-open', open);
  }

  function openApp(data) {
    all = data.outfits || data;
    stuff = data.stuff || [];
    if (data.people && data.people.length) people = data.people;
    who = (people[0] && people[0].id) || 'layla';
    index = 0;
    whoEl.querySelectorAll('button').forEach((b) => {
      b.setAttribute('aria-selected', b.dataset.who === who ? 'true' : 'false');
    });
    showLook(false);
    renderStuff();
    pollHealth();
  }

  function setHealth(state, text) {
    healthEl.dataset.state = state;
    healthText.textContent = text || '';
  }

  async function pollHealth() {
    const ctrl = new AbortController();
    const t = setTimeout(() => ctrl.abort(), 12000);
    try {
      const r = await fetch('api/status.php', {
        credentials: 'same-origin',
        cache: 'no-store',
        signal: ctrl.signal,
      });
      const j = await r.json();
      if (j.green) setHealth('ok', j.text || j.region || 'verbunden');
      else if (j.running && !j.online) setHealth('offline', j.text || 'nicht verbunden');
      else setHealth('down', j.text || 'Client nicht erreichbar');

      if (j.worn_available) {
        wornOutfits = Array.isArray(j.worn_outfits) ? j.worn_outfits : [];
        wornStuff = Array.isArray(j.worn_stuff) ? j.worn_stuff : [];
        paintWearButton();
        renderDots();
        renderStuff();
      }
    } catch {
      setHealth('down', 'Client nicht erreichbar');
    } finally {
      clearTimeout(t);
    }
  }

  $('#prev').addEventListener('click', () => go(index - 1));
  $('#next').addEventListener('click', () => go(index + 1));

  whoEl.addEventListener('click', (e) => {
    const b = e.target.closest('button[data-who]');
    if (b) switchWho(b.dataset.who);
  });

  stuffToggle.addEventListener('click', () => {
    setStuffOpen(stuffPanel.hidden);
  });
  stuffClose.addEventListener('click', () => setStuffOpen(false));
  stuffBackdrop.addEventListener('click', () => setStuffOpen(false));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !stuffPanel.hidden) setStuffOpen(false);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') go(index - 1);
    if (e.key === 'ArrowRight') go(index + 1);
    if (e.key === 'Enter' && !wearBtn.classList.contains('hidden') && !wearBtn.disabled && !metaEl.hidden) {
      wearBtn.click();
    }
  });

  let startX = 0;
  frame.addEventListener('touchstart', (e) => {
    startX = e.changedTouches[0].clientX;
  }, { passive: true });
  frame.addEventListener('touchend', (e) => {
    const dx = e.changedTouches[0].clientX - startX;
    if (dx > 50) go(index - 1);
    if (dx < -50) go(index + 1);
  }, { passive: true });

  wearBtn.addEventListener('click', async () => {
    const o = current();
    if (!o || !o.wear || busy) return;
    busy = true;
    wearBtn.disabled = true;
    setStatus('wird gewechselt …');
    try {
      const r = await fetch('api/wear.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ id: o.id }),
      });
      const j = await r.json();
      if (!j.ok) {
        setStatus(j.error || 'Hat nicht geklappt', 'err');
      } else if (j.lsl) {
        setStatus('Befehl ist raus. In fünf Sekunden sollte ich umgezogen sein.', 'ok');
        wornOutfits = [o.folder];
        paintWearButton();
        renderDots();
        const waitMs = ((j.wait || 5) + 2) * 1000;
        setTimeout(pollHealth, waitMs);
      } else if (o.who === 'layla') {
        setStatus('Das Skript ist gerade nicht verbunden — ich hab Bescheid bekommen und zieh’s selbst an.', 'ok');
      } else {
        setStatus('Das Skript im OpenCollar ist gerade nicht verbunden. Nach einem Collar-Update das Skript einfach nochmal hineinlegen.', 'ok');
      }
    } catch {
      setStatus('Verbindung hat nicht geklappt', 'err');
    }
    busy = false;
    wearBtn.disabled = false;
  });

  async function stuffAction(s, action) {
    if (!s || busy) return;
    busy = true;
    renderStuff();
    setStatus(action === 'add' ? (s.name + ' wird angezogen …') : (s.name + ' wird ausgezogen …'));
    try {
      const r = await fetch('api/stuff.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ id: s.id, action }),
      });
      const j = await r.json();
      if (!j.ok) {
        setStatus(j.error || 'Hat nicht geklappt', 'err');
      } else if (j.lsl) {
        setStatus(action === 'add' ? (s.name + ' ist angezogen.') : (s.name + ' ist ausgezogen.'), 'ok');
        const folder = s.folder;
        if (action === 'add') {
          if (!folderWorn(folder, 'stuff')) wornStuff = wornStuff.concat([folder]);
        } else {
          wornStuff = wornStuff.filter((n) => String(n).toLowerCase() !== String(folder).toLowerCase());
        }
        setTimeout(pollHealth, 2500);
      } else {
        setStatus('Das Skript ist gerade nicht verbunden.', 'err');
      }
    } catch {
      setStatus('Verbindung hat nicht geklappt', 'err');
    }
    busy = false;
    renderStuff();
  }

  if (window.GARDEROBE) openApp(window.GARDEROBE);
  setInterval(pollHealth, 30000);
})();
