

(() => {
  const form = document.getElementById('captcha');
  if (!form) return;

  const grid = form.querySelector('#grid');
  const cid = form.querySelector('[name=cid]');
  const count = document.getElementById('count');
  const status = document.getElementById('status');
  const replace = document.getElementById('replace');
  const confirm = document.getElementById('confirm');

  const paint = () => {
    const n = form.querySelectorAll('input[name="pick[]"]:checked').length;
    count.textContent = n === 1 ? '1 selected' : `${n} selected`;

    confirm.disabled = n === 0;
  };

  const busy = (on) => {
    confirm.disabled = on || form.querySelector('input[name="pick[]"]:checked') === null;
    replace.disabled = on;
  };



  const build = (d) => {
    if (!d || !Array.isArray(d.tiles) || !d.cid) return false;
    grid.replaceChildren(...d.tiles.map((file, i) => {
      const label = document.createElement('label');
      label.className = 'tile';
      const box = document.createElement('input');
      box.type = 'checkbox';
      box.name = 'pick[]';
      box.value = file;
      box.autocomplete = 'off';
      const img = document.createElement('img');
      img.src = `/img/${encodeURIComponent(d.cid)}/${encodeURIComponent(file)}`;
      img.alt = `Tile ${i + 1} of ${d.tiles.length}`;
      img.draggable = false;
      img.decoding = 'async';
      label.append(box, img);
      return label;
    }));
    cid.value = d.cid;

    const title = document.querySelector('.rcap-head h1 strong');
    if (d.label && title) title.textContent = d.label;
    paint();
    return true;
  };

  const post = async (url, body) => {
    const res = await fetch(url, {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body,
    });
    if (!res.ok && res.status !== 429) throw new Error(res.status);
    return res.json();
  };

  grid.addEventListener('change', paint);

  replace.addEventListener('click', async () => {
    busy(true);
    try {
      const res = await fetch('/captcha/api', { headers: { Accept: 'application/json' } });
      const d = await res.json();
      if (!res.ok || !build(d)) { location.href = '/captcha'; return; }
      status.textContent = '';
    } catch {
      location.href = '/captcha';
    } finally {
      busy(false);
    }
  });

  form.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    if (form.querySelector('input[name="pick[]"]:checked') === null) {
      status.textContent = 'Pick at least one tile.';
      return;
    }
    busy(true);
    try {
      const res = await post('/captcha/check', new FormData(form));
      if (res.ok) { location.href = res.redirect; return; }
      if (!build(res)) { location.href = '/captcha'; return; }
      status.textContent = res.error || '';
    } catch {
      status.textContent = 'Could not reach the server. Try again.';
    } finally {
      busy(false);
    }
  });

  paint();
})();