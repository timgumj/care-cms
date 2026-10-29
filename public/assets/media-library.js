(() => {
  'use strict';
  const root = document.querySelector('[data-media-library]');
  if (!root) return;
  const grid = root.querySelector('[data-media-grid]');
  const status = root.querySelector('[data-media-status]');
  const search = root.querySelector('[data-media-search]');
  const folder = root.querySelector('[data-media-folder]');
  let images = [];
  function render() {
    grid.replaceChildren();
    const filtered = images.filter(image => image.name.toLowerCase().includes(search.value.toLowerCase()) && (!folder.value || image.folder === folder.value));
    for (const image of filtered) {
      const card = document.createElement('article'); card.className = 'media-item';
      const img = document.createElement('img'); img.src = image.src; img.alt = ''; img.loading = 'lazy';
      const label = document.createElement('p'); label.textContent = `${image.name} · ${image.width} × ${image.height} · ${Math.ceil(image.bytes / 1024)} KB`;
      const input = document.createElement('input'); input.value = image.src; input.readOnly = true; input.setAttribute('aria-label', image.name);
      const copy = document.createElement('button'); copy.type = 'button'; copy.textContent = root.dataset.copy;
      copy.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(image.src); status.textContent = root.dataset.copied; }
        catch { input.focus(); input.select(); }
      });
      card.append(img, label, input, copy); grid.append(card);
    }
    if (!filtered.length) status.textContent = root.dataset.empty;
  }
  async function load() {
    const response = await fetch(root.dataset.listUrl, {credentials:'same-origin', headers:{Accept:'application/json'}});
    if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) throw new Error();
    images = (await response.json()).data; render();
  }
  search.addEventListener('input', () => { status.textContent = ''; render(); });
  folder.addEventListener('change', () => { status.textContent = ''; render(); });
  root.querySelector('form').addEventListener('submit', async event => {
    event.preventDefault(); const form = event.currentTarget; const button = form.querySelector('button');
    button.disabled = true; status.textContent = '';
    try {
      const response = await fetch(root.dataset.uploadUrl, {method:'POST', credentials:'same-origin', headers:{'X-CSRF-Token':root.dataset.token, Accept:'application/json'}, body:new FormData(form)});
      if (!response.headers.get('content-type')?.includes('application/json')) throw new Error();
      const data = await response.json();
      if (!response.ok) { status.textContent = data.error || root.dataset.failed; return; }
      form.reset(); search.value = ''; folder.value = ''; await load(); status.textContent = root.dataset.uploaded;
    } catch { status.textContent = root.dataset.failed; }
    finally { button.disabled = false; }
  });
  load().catch(() => { status.textContent = root.dataset.failed; });
})();
