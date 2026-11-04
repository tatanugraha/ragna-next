// Ragna Next — GM reports in the staff dashboard (read only).
(function () {
  const $ = (s, el = document) => el.querySelector(s);
  const pane = $('#gm-pane');
  if (!pane) return;
  const body = $('#gm-body');
  const endpoint = '/account/?module=changelog&action=gm-report';

  const tabs = {
    report: { title: 'Report', intro: 'Server overview and logins over the last 14 days.', fields: [] },
    online: { title: 'Online', intro: 'Everyone online right now. A walking player’s map is from the last autosave; shops are exact.', fields: ['filter'] },
    items: { title: 'Item Log', intro: 'Every item that moved (picklog). Trade, vending and buying-store rows show the other side when it can be matched (same second, same map, opposite sign).', fields: ['char', 'item', 'uid', 'type', 'period'] },
    zeny: { title: 'Zeny Log', intro: 'Where a character’s zeny came from and went to (zenylog). Needs log_zeny enabled in log_athena.conf.', fields: ['char', 'type', 'period'] },
    trades: { title: 'Trades', intro: 'Player-to-player trades, one row per trade. Rows marked exact also matched the zeny log.', fields: ['char', 'period'] },
    cash: { title: 'Cash Points', intro: 'Cash Points of the character’s whole account (CP is account-wide), with a check that the logged total matches the balance.', fields: ['char', 'period'] },
    commands: { title: 'GM Commands', intro: 'Commands used in game (atcommandlog).', fields: ['char', 'command', 'period'] }
  };
  const types = [
    ['', 'All types'], ['p2p', 'Player ↔ player (trade, vend, buy)'], ['npc', 'NPC ↔ player (shop, script, barter)'],
    ['T', 'Trade'], ['V', 'Vending'], ['B', 'Buying store'], ['P', 'Player drop/pick'], ['M', 'Monster drop'], ['L', 'Loot'],
    ['S', 'NPC shop'], ['N', 'NPC script'], ['J', 'Barter'], ['D', 'Steal'], ['C', 'Consumed'], ['O', 'Produced'], ['U', 'MVP reward'],
    ['A', 'GM command'], ['R', 'Storage'], ['G', 'Guild storage'], ['E', 'Mail'], ['I', 'Auction'], ['K', 'Bank'], ['$', 'Cash shop'],
    ['F', 'Bound removal'], ['Y', 'Roulette'], ['Z', 'Merge'], ['Q', 'Quest'], ['H', 'Airship'], ['X', 'Other']
  ];
  $('#gm-f-type').append(...types.map(([value, text]) => new Option(text, value)));

  const jobs = { 0: 'Novice', 1: 'Swordman', 2: 'Mage', 3: 'Archer', 4: 'Acolyte', 5: 'Merchant', 6: 'Thief', 7: 'Knight', 8: 'Priest', 9: 'Wizard', 10: 'Blacksmith', 11: 'Hunter', 12: 'Assassin', 14: 'Crusader', 15: 'Monk', 16: 'Sage', 17: 'Rogue', 18: 'Alchemist', 19: 'Bard', 20: 'Dancer', 23: 'Super Novice', 24: 'Gunslinger', 25: 'Ninja',
    4001: 'High Novice', 4008: 'Lord Knight', 4009: 'High Priest', 4010: 'High Wizard', 4011: 'Whitesmith', 4012: 'Sniper', 4013: 'Assassin Cross', 4015: 'Paladin', 4016: 'Champion', 4017: 'Professor', 4018: 'Stalker', 4019: 'Creator', 4020: 'Clown', 4021: 'Gypsy', 4023: 'Baby Novice', 4046: 'Taekwon', 4047: 'Star Gladiator', 4049: 'Soul Linker',
    4054: 'Rune Knight', 4055: 'Warlock', 4056: 'Ranger', 4057: 'Arch Bishop', 4058: 'Mechanic', 4059: 'Guillotine Cross', 4060: 'Rune Knight T', 4061: 'Warlock T', 4062: 'Ranger T', 4063: 'Arch Bishop T', 4064: 'Mechanic T', 4065: 'Guillotine Cross T',
    4066: 'Royal Guard', 4067: 'Sorcerer', 4068: 'Minstrel', 4069: 'Wanderer', 4070: 'Sura', 4071: 'Genetic', 4072: 'Shadow Chaser', 4073: 'Royal Guard T', 4074: 'Sorcerer T', 4075: 'Minstrel T', 4076: 'Wanderer T', 4077: 'Sura T', 4078: 'Genetic T', 4079: 'Shadow Chaser T',
    4190: 'Expanded Super Novice', 4211: 'Kagerou', 4212: 'Oboro', 4215: 'Rebellion', 4218: 'Summoner', 4239: 'Star Emperor', 4240: 'Soul Reaper',
    4252: 'Dragon Knight', 4253: 'Meister', 4254: 'Shadow Cross', 4255: 'Arch Mage', 4256: 'Cardinal', 4257: 'Windhawk', 4258: 'Imperial Guard', 4259: 'Biolo', 4260: 'Abyss Chaser', 4261: 'Elemental Master', 4262: 'Inquisitor', 4263: 'Troubadour', 4264: 'Trouvere',
    4302: 'Sky Emperor', 4303: 'Soul Ascetic', 4304: 'Shinkiro', 4305: 'Shiranui', 4306: 'Night Watch', 4307: 'Hyper Novice', 4308: 'Spirit Handler' };
  const jobName = id => jobs[id] || `Job #${id}`;

  let current = 'report';
  let lastOnline = [];
  let request = 0;

  const el = (tag, attrs = {}, ...children) => {
    const node = document.createElement(tag);
    Object.entries(attrs).forEach(([k, v]) => { if (k === 'class') node.className = v; else node.setAttribute(k, v); });
    children.flat().forEach(child => node.append(child instanceof Node ? child : document.createTextNode(String(child ?? ''))));
    return node;
  };
  const num = n => Number(n || 0).toLocaleString('en-US');
  const stat = (label, value, note) => el('div', { class: 'gm-stat' }, el('small', {}, label), el('strong', {}, value), note ? el('span', {}, note) : '');
  const table = (head, rows, empty = 'Nothing found for this search.') => {
    if (!rows.length) return el('p', { class: 'gm-empty' }, empty);
    return el('div', { class: 'gm-table-wrap' }, el('table', { class: 'gm-table' },
      el('thead', {}, el('tr', {}, head.map(h => el('th', {}, h)))),
      el('tbody', {}, rows.map(cells => el('tr', {}, cells.map(c => el('td', {}, c)))))));
  };
  const muted = text => el('span', { class: 'gm-muted' }, text);
  const amount = n => el('span', { class: n < 0 ? 'gm-neg' : 'gm-pos' }, (n > 0 ? '+' : '') + num(n));
  const message = (text, kind = '') => body.replaceChildren(el('p', { class: `gm-empty ${kind}` }, text));

  function showTab(name) {
    current = name;
    const cfg = tabs[name];
    $('#gm-title').textContent = cfg.title;
    $('#gm-intro').textContent = cfg.intro;
    pane.querySelectorAll('[data-gm-field]').forEach(label => { label.hidden = !cfg.fields.includes(label.dataset.gmField); });
    $('#gm-filters').hidden = !cfg.fields.length;
    load();
  }

  async function load() {
    if (current === 'cash' && !$('#gm-f-char').value.trim()) { message('Enter a character name to see the account Cash Points.'); return; }
    const params = new URLSearchParams({ tab: current });
    const cfg = tabs[current];
    [['char', '#gm-f-char'], ['item', '#gm-f-item'], ['uid', '#gm-f-uid'], ['command', '#gm-f-command'], ['type', '#gm-f-type'], ['period', '#gm-f-period']]
      .forEach(([key, sel]) => { const v = $(sel).value.trim(); if (cfg.fields.includes(key) && v) params.set(key, v); });
    const id = ++request;
    message('Loading…');
    try {
      const response = await fetch(`${endpoint}&${params}`, { cache: 'no-store', credentials: 'same-origin', headers: { accept: 'application/json' } });
      const data = await response.json().catch(() => ({ ok: false, message: 'The report service did not answer with data. Sign in again and retry.' }));
      if (id !== request) return;
      if (!response.ok || !data.ok) { message(data.message || 'The report could not be loaded.', 'error'); return; }
      try { render[current](data); } catch { message('The report data could not be shown.', 'error'); }
    } catch {
      if (id === request) message('Could not reach the account service. Try again in a moment.', 'error');
    }
  }

  const render = {
    report(d) {
      const s = d.stats;
      body.replaceChildren(
        el('div', { class: 'gm-stats' },
          stat('Online now', num(s.online), `${num(s.onlineAccounts)} accounts`),
          stat('Accounts', num(s.accounts), `${num(s.vip)} VIP`),
          stat('Active today', num(s.activeToday), `${num(s.active7)} in 7 days`),
          stat('Characters', num(s.characters), `${num(s.maxLevel)} at level 99+`),
          stat('Guilds', num(s.guilds)),
          stat('Zeny in circulation', num(s.zeny))),
        el('h4', { class: 'gm-sub' }, 'Last 14 days'),
        table(['Day', 'Accounts logged in', 'IPs'], d.days.map((r, i) => [i === 0 ? el('span', {}, r.day, ' ', muted('today')) : r.day, num(r.accounts), num(r.ips)])));
    },
    online(d) {
      lastOnline = d.rows;
      const s = d.summary;
      body.replaceChildren(
        el('div', { class: 'gm-stats' },
          stat('Online', num(s.online)), stat('Playing', num(s.playing)), stat('Vending', num(s.vending)),
          stat('Buying stores', num(s.buying)), stat('Autotrade', num(s.autotrade)), stat('Same IP (2+ clients)', num(s.sameIp))),
        el('div', { class: 'gm-chips' }, Object.entries(d.maps).map(([map, n]) => el('button', { type: 'button', class: 'gm-chip', 'data-map': map }, map, ' ', el('b', {}, n)))),
        el('div', { id: 'gm-online-table' }),
        el('div', { class: 'gm-actions' }, el('button', { type: 'button', class: 'btn btn-outline', id: 'gm-refresh' }, 'Refresh')));
      body.querySelectorAll('[data-map]').forEach(b => b.addEventListener('click', () => { $('#gm-f-filter').value = b.dataset.map; filterOnline(); }));
      $('#gm-refresh').addEventListener('click', load);
      filterOnline();
    },
    items(d) {
      body.replaceChildren(note(d), table(['When', 'Character', 'Type', 'Item', 'Amount', 'Other side', 'Map', 'Unique ID'], d.rows.map(r => [
        r.time, r.char || `#${r.charId}`, r.typeLabel,
        el('span', {}, r.refine ? `+${r.refine} ` : '', r.item || `Item #${r.itemId}`, ' ', muted(`#${r.itemId}`), r.cards.length ? muted(` [${r.cards.join(', ')}]`) : ''),
        amount(r.amount), r.partner || '', r.map, r.uniqueId !== '0' ? r.uniqueId : ''])));
    },
    zeny(d) {
      body.replaceChildren(note(d), table(['When', 'Character', 'Type', 'Zeny', 'Other side', 'Map'], d.rows.map(r => [
        r.time, r.char || `#${r.charId}`, r.typeLabel, amount(r.amount), r.partner || '', r.map]),
      'No zeny movements found. If this stays empty, log_zeny is still 0 in conf/log_athena.conf.'));
    },
    trades(d) {
      const gave = side => el('div', { class: 'gm-side' }, el('b', {}, side.char),
        side.gave.length || side.zeny ? el('ul', {}, side.gave.map(g => el('li', {}, `${g.amount} × ${g.refine ? `+${g.refine} ` : ''}${g.item || `Item #${g.itemId}`}`)), side.zeny ? el('li', {}, `${num(side.zeny)} zeny`) : '') : muted(' gave nothing logged'));
      body.replaceChildren(note(d), table(['When', 'Map', 'Side A', 'Side B', 'Match'], d.rows.map(r => [
        r.time, r.map, r.sides[0] ? gave(r.sides[0]) : '', r.sides[1] ? gave(r.sides[1]) : '', r.exact ? el('span', { class: 'gm-ok' }, 'exact') : muted('time + map')])));
    },
    cash(d) {
      if (d.message) { message(d.message); return; }
      const match = d.balance.cash === d.logged.cash;
      body.replaceChildren(
        el('div', { class: 'gm-stats' },
          stat('Account', d.account, `#${d.accountId}`),
          stat('Cash Points balance', num(d.balance.cash)),
          stat('Logged total (all time)', num(d.logged.cash), match ? 'Matches the balance' : `Differs by ${num(d.balance.cash - d.logged.cash)}`),
          stat('Kafra Points', num(d.balance.kafra))),
        table(['When', 'Character', 'Source', 'Points', 'Amount', 'Map'], d.rows.map(r => [r.time, r.char, r.typeLabel, r.cashType, amount(r.amount), r.map])));
    },
    commands(d) {
      body.replaceChildren(note(d), table(['When', 'Character', 'Account', 'Map', 'Command'], d.rows.map(r => [r.time, r.char, `#${r.accountId}`, r.map, el('code', {}, r.command)])));
    }
  };
  const note = d => d.message ? el('p', { class: 'gm-empty' }, d.message) : el('p', { class: 'gm-note' }, d.rows.length >= (d.limit || 500) ? `Showing the latest ${d.limit || 500} rows. Narrow the search to see older ones.` : `${num(d.rows.length)} rows${d.char ? ` for ${d.char}` : ''}.`);

  function filterOnline() {
    const target = $('#gm-online-table');
    if (!target) return;
    const q = $('#gm-f-filter').value.trim().toLowerCase();
    const rows = lastOnline.filter(r => !q || [r.name, r.account, r.map, r.guild, r.ip].some(v => String(v).toLowerCase().includes(q)));
    target.replaceChildren(table(['Character', 'Account', 'Job / Lv', 'Guild', 'Map', 'Status', 'Same IP'], rows.map(r => [
      r.name, el('span', {}, r.account, ' ', muted(`#${r.accountId}`)), el('span', {}, jobName(r.job), ' ', muted(`${r.baseLevel}/${r.jobLevel}`)),
      r.guild, el('span', {}, r.map, ' ', muted(`${r.x},${r.y}`)), r.shop ? el('span', {}, r.status, ' ', muted(r.shop)) : r.status,
      el('span', { class: r.sameIp ? 'gm-warn' : 'gm-muted' }, r.ip)]), 'Nobody online matches this filter.'));
  }

  $('#gm-search').addEventListener('click', () => (current === 'online' ? filterOnline() : load()));
  $('#gm-f-filter').addEventListener('input', filterOnline);
  pane.addEventListener('keydown', event => {
    if (event.key !== 'Enter' || event.target.tagName === 'BUTTON') return;
    event.preventDefault(); // keep Enter from submitting the content editor form
    $('#gm-search').click();
  });
  document.addEventListener('ragna:dashboard-view', event => {
    const id = String(event.detail || '');
    if (id.startsWith('gm-') && tabs[id.slice(3)]) showTab(id.slice(3));
  });
})();
