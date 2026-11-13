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

  let all = [];
  let people = [
    { id: 'layla', name: 'Layla' },
    { id: 'jerome', name: 'Jérôme' },
    { id: 'cindy', name: 'Cindy' },
    { id: 'caprica', name: 'Caprica' },
  ];
  let who = 'layla';
  let index = 0;
  let frontIsA = true;
  let busy = false;

  const list = () => all.filter((o) => o.who === who);
  const current = () => list()[index] || null;
  const imgDir = (whoId) => {
    const p = people.find((x) => x.id === whoId);
    if (p && p.img) return p.img;
    return ({ layla: 'Layla', jerome: 'Steampilot', cindy: 'Cindy', caprica: 'Caprica' })[whoId] || whoId;
  };
  const imgUrl = (o) => {
    if (o.image && o.image.indexOf('/') !== -1) return o.image;
    return 'img/' + encodeURIComponent(imgDir(o.who)) + '/' + encodeURIComponent(o.image || (o.folder + '.webp'));
  };
  const personName = (id) => (people.find((p) => p.id === id) || { name: id }).name;

  function setStatus(text, kind) {
    statusEl.textContent = text || '';
    statusEl.className = 'status' + (kind ? ' ' + kind : '');
  }

  function renderDots() {
    const items = list();
    dotsEl.innerHTML = '';
    items.forEach((o, i) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.setAttribute('aria-label', o.name);
      if (i === index) b.setAttribute('aria-current', 'true');
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

  function openApp(data) {
    all = data.outfits || data;
    if (data.people && data.people.length) people = data.people;
    who = 'layla';
    index = 0;
    whoEl.querySelectorAll('button').forEach((b) => {
      b.setAttribute('aria-selected', b.dataset.who === who ? 'true' : 'false');
    });
    showLook(false);
  }

  $('#prev').addEventListener('click', () => go(index - 1));
  $('#next').addEventListener('click', () => go(index + 1));

  whoEl.addEventListener('click', (e) => {
    const b = e.target.closest('button[data-who]');
    if (b) switchWho(b.dataset.who);
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
        if (o.who === 'layla') {
          setStatus('Befehl ist raus. In fünf Sekunden sollte ich umgezogen sein.', 'ok');
        } else {
          setStatus('Befehl ist raus. In fünf Sekunden solltest du umgezogen sein.', 'ok');
        }
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

  if (window.GARDEROBE) openApp(window.GARDEROBE);
})();
