// Ragna Next — page interactions
(function () {
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];

  $('#year').textContent = new Date().getFullYear();

  // Solid top bar on scroll + mobile menu
  const topbar = $('#topbar');
  addEventListener('scroll', () => topbar.classList.toggle('solid', scrollY > 40), { passive: true });
  $('#burger').addEventListener('click', () => $('#menu').classList.toggle('open'));
  $$('#menu a').forEach(a => a.addEventListener('click', () => $('#menu').classList.remove('open')));

  // Highlight the matching header link as sections enter the viewport.
  const links = $$('.menu a');
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (!e.isIntersecting) return;
      links.forEach(a => a.classList.toggle('on', a.getAttribute('href') === '#' + e.target.id));
    });
  }, { rootMargin: '-20% 0px -65% 0px', threshold: 0 });
  $$('.panel').forEach(p => io.observe(p));

  // News carousel
  const slides = $$('.slide'), dots = $$('.carousel-nav button');
  let cur = 0, timer;
  function go(i) {
    cur = (i + slides.length) % slides.length;
    slides.forEach((s, k) => s.classList.toggle('active', k === cur));
    dots.forEach((d, k) => d.classList.toggle('on', k === cur));
  }
  function auto() { clearInterval(timer); timer = setInterval(() => go(cur + 1), 5000); }
  dots.forEach((d, k) => d.addEventListener('click', () => { go(k); auto(); }));
  auto();

  // News tabs
  $$('.tab').forEach(tab => tab.addEventListener('click', () => {
    $$('.tab').forEach(t => t.classList.toggle('on', t === tab));
    const cat = tab.dataset.cat;
    $$('#news-items li').forEach(li => li.classList.toggle('hide', cat !== 'all' && li.dataset.cat !== cat));
  }));

  // News and game changelog have separate published feeds.
  async function loadChangelog() {
    const newsList = $('#news-items');
    const changelogList = $('#changelog-items');
    try {
      const fetchJson = async url => {
        const response = await fetch(url, { cache: 'no-store' });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
      };
      const fallbackPromise = fetchJson('assets/changelog.json');
      const [fallback, newsResult, changelogResult] = await Promise.all([
        fallbackPromise,
        fetchJson('/account/?module=changelog&action=news-feed').then(data => ({ data })).catch(() => null),
        fetchJson('/account/?module=changelog&action=feed').then(data => ({ data })).catch(() => null)
      ]);
      const newsData = newsResult?.data || fallback;
      const changelogData = changelogResult?.data || fallback;
      const newsItems = Array.isArray(newsData.news) ? newsData.news : (Array.isArray(newsData.items) ? newsData.items : []);
      const changelogItems = Array.isArray(changelogData.changelog) ? changelogData.changelog : (Array.isArray(changelogData.items) && changelogResult ? changelogData.items : []);
      newsList.replaceChildren();
      changelogList.replaceChildren();

      const makeTime = (value) => {
        const time = document.createElement('time');
        const date = String(value || '');
        time.dateTime = date;
        time.textContent = /^\d{4}-\d{2}-\d{2}$/.test(date)
          ? new Date(`${date}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
          : (date || 'Update');
        return time;
      };

      for (const item of newsItems) {
        const titleText = String(item.title || 'Server update');
        const date = String(item.date || '');
        const category = String(item.category || 'info').toLowerCase();

        const row = document.createElement('li');
        row.dataset.cat = category === 'event' ? 'event' : 'info';
        const link = document.createElement('a');
        link.href = String(item.url || '#news');
        const title = document.createElement('span');
        title.textContent = titleText;
        link.append(title, makeTime(date));
        row.append(link);
        newsList.append(row);
      }

      for (const item of changelogItems) {
        const titleText = String(item.title || 'Game update');
        const summaryText = String(item.summary || '');
        const date = String(item.date || '');
        const category = String(item.category || 'update').toLowerCase();
        const categoryLabel = category === 'event' ? 'Event' : category === 'info' ? 'Info' : 'Update';
        const entry = document.createElement('article');
        entry.className = 'changelog-entry';
        const meta = document.createElement('div');
        meta.className = 'changelog-meta';
        const categoryText = document.createElement('span');
        categoryText.className = 'category';
        categoryText.textContent = categoryLabel;
        meta.append(categoryText, makeTime(date));
        const heading = document.createElement('h3');
        heading.textContent = titleText;
        const summary = document.createElement('p');
        summary.textContent = summaryText;
        entry.append(meta, heading, summary);
        changelogList.append(entry);
      }

      if (!newsItems.length) {
        const emptyRow = document.createElement('li'); emptyRow.className = 'news-empty';
        const empty = document.createElement('span'); empty.textContent = 'No news announcements have been published yet.';
        emptyRow.append(empty); newsList.append(emptyRow);
      }
      if (!changelogItems.length) {
        const empty = document.createElement('p'); empty.className = 'changelog-empty';
        empty.textContent = 'No game changes have been published yet.';
        changelogList.append(empty);
      }

      const selectedTab = $('.tab.on');
      if (selectedTab) {
        const category = selectedTab.dataset.cat;
        $$('#news-items li').forEach(row => row.classList.toggle('hide', category !== 'all' && row.dataset.cat !== category));
      }
    } catch (error) {
      newsList.replaceChildren();
      changelogList.replaceChildren();
      const message = document.createElement('p');
      message.className = 'changelog-empty';
      message.textContent = 'News and changelog are temporarily unavailable.';
      const errorRow = document.createElement('li');
      errorRow.className = 'news-empty';
      errorRow.append(message.cloneNode(true));
      newsList.append(errorRow);
      changelogList.append(message);
    }
  }
  loadChangelog();

  // Reflect the FluxCP login session on the public website and expose the editor to GM+.
  const portalUrl = '/account/?module=changelog&action=portal';
  const editorSection = $('#dashboard');
  const editorForm = $('#staff-editor-form');
  const editorNewsEntries = $('#staff-news-entries');
  const editorChangelogEntries = $('#staff-changelog-entries');
  const editorEventEntries = $('#staff-event-entries');
  const editorMessage = $('#staff-editor-message');
  const editorLoading = $('#staff-editor-loading');
  const editorCsrf = $('#staff-editor-csrf');
  const editorNextIndex = { news: 0, changelog: 0 };
  let editorNextEventIndex = 0;

  function setEditorMessage(message, kind = '') {
    editorMessage.textContent = message;
    editorMessage.className = `staff-editor-message${kind ? ` ${kind}` : ''}`;
  }

  function createEditorEntry(item = {}, collection = 'news') {
    const index = editorNextIndex[collection]++;
    const prefix = `${collection}[${index}]`;
    const fieldset = document.createElement('fieldset');
    fieldset.className = 'staff-editor-entry';
    const legend = document.createElement('legend');
    legend.textContent = `${collection === 'news' ? 'News' : 'Changelog'} ${index + 1}`;
    fieldset.append(legend);

    const id = document.createElement('input');
    id.type = 'hidden'; id.name = `${prefix}[id]`; id.value = String(item.id || '');
    fieldset.append(id);

    const grid = document.createElement('div');
    grid.className = 'staff-editor-grid';
    const addField = (labelText, control) => {
      const label = document.createElement('label');
      label.append(document.createTextNode(labelText));
      label.append(control);
      grid.append(label);
    };
    const title = document.createElement('input');
    title.name = `${prefix}[title]`; title.maxLength = 140; title.required = true; title.value = String(item.title || '');
    addField('Title', title);
    const date = document.createElement('input');
    date.type = 'date'; date.name = `${prefix}[date]`; date.required = true; date.value = String(item.date || new Date().toISOString().slice(0, 10));
    addField('Date', date);
    const category = document.createElement('select');
    category.name = `${prefix}[category]`;
    [['event', 'Event'], ['info', 'Info'], ['update', 'Update']].forEach(([value, text]) => {
      const option = document.createElement('option'); option.value = value; option.textContent = text;
      if ((item.category || (collection === 'news' ? 'info' : 'update')) === value) option.selected = true;
      category.append(option);
    });
    addField('Category', category);
    fieldset.append(grid);

    const summaryLabel = document.createElement('label');
    summaryLabel.append(document.createTextNode('Short description'));
    const summary = document.createElement('textarea');
    summary.name = `${prefix}[summary]`; summary.maxLength = 1200; summary.rows = 3; summary.value = String(item.summary || '');
    summaryLabel.append(summary);
    fieldset.append(summaryLabel);

    const remove = document.createElement('button');
    remove.type = 'button'; remove.className = 'btn-sm staff-editor-remove'; remove.textContent = 'Remove entry';
    remove.addEventListener('click', () => { fieldset.remove(); });
    fieldset.append(remove);
    return fieldset;
  }

  function renderEditorEntries(collection, items) {
    const container = collection === 'news' ? editorNewsEntries : editorChangelogEntries;
    container.replaceChildren();
    editorNextIndex[collection] = 0;
    items.slice(0, 50).forEach(item => container.append(createEditorEntry(item, collection)));
  }

  function createEditorEvent(item = {}) {
    const index = editorNextEventIndex++;
    const fieldset = document.createElement('fieldset');
    fieldset.className = 'staff-editor-entry';
    const legend = document.createElement('legend');
    legend.textContent = `Event ${index + 1}`;
    fieldset.append(legend);
    const id = document.createElement('input');
    id.type = 'hidden'; id.name = `events[${index}][id]`; id.value = String(item.id || '');
    fieldset.append(id);

    const grid = document.createElement('div');
    grid.className = 'staff-editor-grid';
    const addField = (labelText, control) => {
      const label = document.createElement('label');
      label.append(document.createTextNode(labelText), control);
      grid.append(label);
    };
    const title = document.createElement('input');
    title.name = `events[${index}][title]`; title.maxLength = 140; title.required = true; title.value = String(item.title || '');
    addField('Event title', title);
    const startDate = document.createElement('input');
    startDate.type = 'date'; startDate.name = `events[${index}][startDate]`; startDate.required = true; startDate.value = String(item.startDate || new Date().toISOString().slice(0, 10));
    addField('Start date', startDate);
    const endDate = document.createElement('input');
    endDate.type = 'date'; endDate.name = `events[${index}][endDate]`; endDate.value = String(item.endDate || '');
    addField('End date', endDate);
    fieldset.append(grid);

    const addTextarea = (labelText, name, value, maxLength) => {
      const label = document.createElement('label');
      label.append(document.createTextNode(labelText));
      const textarea = document.createElement('textarea');
      textarea.name = `events[${index}][${name}]`; textarea.rows = 3; textarea.maxLength = maxLength; textarea.value = String(value || '');
      label.append(textarea);
      fieldset.append(label);
    };
    addTextarea('Short summary', 'summary', item.summary, 320);
    addTextarea('Event description', 'description', item.description, 1200);

    const linkGrid = document.createElement('div');
    linkGrid.className = 'staff-editor-grid';
    const addUrl = (labelText, name, value) => {
      const label = document.createElement('label');
      label.append(document.createTextNode(labelText));
      const input = document.createElement('input');
      input.type = 'url'; input.name = `events[${index}][${name}]`; input.maxLength = 500; input.placeholder = 'https://'; input.value = String(value || '');
      label.append(input); linkGrid.append(label);
    };
    addUrl('Promo image URL (optional)', 'image', item.image);
    addUrl('Event details URL (optional)', 'url', item.url);
    fieldset.append(linkGrid);

    const remove = document.createElement('button');
    remove.type = 'button'; remove.className = 'btn-sm staff-editor-remove'; remove.textContent = 'Remove event';
    remove.addEventListener('click', () => fieldset.remove());
    fieldset.append(remove);
    return fieldset;
  }

  function renderEditorEvents(items) {
    editorEventEntries.replaceChildren();
    editorNextEventIndex = 0;
    items.slice(0, 50).forEach(item => editorEventEntries.append(createEditorEvent(item)));
  }

  const dashboardViews = [
    { id: 'overview', tab: $('#dashboard-overview-tab'), pane: $('#dashboard-overview') },
    { id: 'news', tab: $('#staff-news-tab'), pane: $('#staff-news-pane') },
    { id: 'changelog', tab: $('#staff-changelog-tab'), pane: $('#staff-changelog-pane') },
    { id: 'events', tab: $('#staff-events-tab'), pane: $('#staff-events-pane') }
  ];
  function syncDashboardRoute() {
    const active = location.hash === '#dashboard' || location.hash === '#staff-editor';
    document.body.classList.toggle('dashboard-active', active);
    if (!active) editorSection.hidden = true;
  }
  window.addEventListener('hashchange', syncDashboardRoute);
  syncDashboardRoute();
  function setDashboardView(id) {
    dashboardViews.forEach(({ id: viewId, tab, pane }) => {
      const selected = viewId === id;
      tab.classList.toggle('on', selected);
      tab.setAttribute('aria-selected', String(selected));
      pane.hidden = !selected;
    });
    $('#dashboard-save-actions').hidden = id === 'overview';
  }
  dashboardViews.forEach(({ id, tab }, index) => {
    tab.addEventListener('click', () => setDashboardView(id));
    tab.addEventListener('keydown', event => {
      if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;
      event.preventDefault();
      const offset = event.key === 'ArrowDown' ? 1 : -1;
      const next = dashboardViews[(index + offset + dashboardViews.length) % dashboardViews.length];
      next.tab.focus();
      setDashboardView(next.id);
    });
  });
  document.querySelectorAll('[data-dashboard-view]:not([role="tab"])').forEach(button => button.addEventListener('click', () => {
    setDashboardView(button.dataset.dashboardView);
    dashboardViews.find(view => view.id === button.dataset.dashboardView)?.tab.focus();
  }));

  function showStaffEditor(view = 'overview') {
    editorSection.hidden = false;
    setDashboardView(view);
    location.hash = 'dashboard';
    editorSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  async function loadAccountPortal() {
    try {
      const response = await fetch(portalUrl, { cache: 'no-store', credentials: 'same-origin', headers: { accept: 'application/json' } });
      if (!response.ok) {
        if (location.hash === '#dashboard' || location.hash === '#staff-editor') {
          editorSection.hidden = false;
          editorLoading.textContent = 'Staff access could not be checked. Reload the page or sign in again.';
        }
        return;
      }
      const portal = await response.json();
      if (portal.loggedIn) {
        $('#account-login').hidden = true;
        $('#account-register').hidden = true;
        $('#account-profile').hidden = false;
        $('#account-logout').hidden = false;
        $('#account-name').textContent = String(portal.accountName || 'Account');
      }
      if (!portal.canEdit) {
        if (location.hash === '#dashboard' || location.hash === '#staff-editor') {
          editorSection.hidden = false;
          editorLoading.textContent = portal.loggedIn ? 'The dashboard is available to Game Masters and Super Admins.' : 'Sign in with a Game Master or Super Admin account to open the dashboard.';
        }
        return;
      }

      ['#staff-editor-link', '#staff-editor-button'].forEach(selector => { $(selector).hidden = false; });
      editorCsrf.value = String(portal.csrfToken || '');
      const news = Array.isArray(portal.news) ? portal.news : [];
      const changelog = Array.isArray(portal.changelog) ? portal.changelog : [];
      const events = Array.isArray(portal.events) ? portal.events : [];
      renderEditorEntries('news', news);
      renderEditorEntries('changelog', changelog);
      renderEditorEvents(events);
      $('#dashboard-news-count').textContent = String(news.length);
      $('#dashboard-changelog-count').textContent = String(changelog.length);
      $('#dashboard-events-count').textContent = String(events.length);
      editorLoading.hidden = true;
      editorForm.hidden = false;
      document.querySelectorAll('.staff-editor-link').forEach(link => link.addEventListener('click', event => {
        event.preventDefault();
        showStaffEditor(link.id === 'staff-editor-button' ? 'changelog' : 'overview');
      }));
      setDashboardView('overview');
      if (location.hash === '#staff-editor') showStaffEditor('changelog');
      else if (location.hash === '#dashboard') showStaffEditor();
    } catch {
      // Public pages remain usable if the account service is temporarily unavailable.
      editorLoading.textContent = 'The dashboard could not be loaded. Please try again in a moment.';
      if (location.hash === '#dashboard' || location.hash === '#staff-editor') editorSection.hidden = false;
    }
  }

  function addEditorEntry(collection, container) {
    if (container.querySelectorAll('.staff-editor-entry:not([hidden])').length >= 50) {
      setEditorMessage('Each editor supports up to 50 entries.', 'error');
      return;
    }
    const entry = createEditorEntry({}, collection);
    container.append(entry);
    entry.querySelector('input:not([type="hidden"])').focus();
    setEditorMessage('');
  }
  $('#staff-news-add').addEventListener('click', () => addEditorEntry('news', editorNewsEntries));
  $('#staff-changelog-add').addEventListener('click', () => addEditorEntry('changelog', editorChangelogEntries));

  $('#staff-editor-add-event').addEventListener('click', () => {
    if (editorEventEntries.querySelectorAll('.staff-editor-entry').length >= 50) {
      setEditorMessage('The editor supports up to 50 events.', 'error');
      return;
    }
    const entry = createEditorEvent();
    editorEventEntries.append(entry);
    entry.querySelector('input:not([type="hidden"])').focus();
    setEditorMessage('');
  });

  editorForm.addEventListener('submit', async event => {
    event.preventDefault();
    setEditorMessage('Saving changes…');
    try {
      const response = await fetch('/account/?module=changelog&action=save', {
        method: 'POST', cache: 'no-store', credentials: 'same-origin',
        headers: { accept: 'application/json' }, body: new FormData(editorForm)
      });
      const contentType = response.headers.get('content-type') || '';
      if (!contentType.includes('application/json')) {
        setEditorMessage('Your account session has expired. Sign in again, then reopen the editor.', 'error');
        return;
      }
      const result = await response.json();
      if (result.csrfToken) editorCsrf.value = String(result.csrfToken);
      if (!response.ok || !result.ok) {
        setEditorMessage(String(result.message || 'Changes could not be saved.'), 'error');
        return;
      }
      renderEditorEntries('news', Array.isArray(result.news) ? result.news : []);
      renderEditorEntries('changelog', Array.isArray(result.changelog) ? result.changelog : []);
      renderEditorEvents(Array.isArray(result.events) ? result.events : []);
      setEditorMessage(String(result.message || 'Changes saved.'), 'success');
      loadChangelog();
    } catch {
      setEditorMessage('Could not reach the account service. Your changes are still in this form; try saving again.', 'error');
    }
  });

  loadAccountPortal();

  // World
  const regions = [
    { tag: 'Royal Capital', name: 'Prontera', bg: 'url(assets/img/city1.webp) center right / cover no-repeat, #23345e',
      desc: 'The heart of the Rune-Midgard Kingdom. Adventurers gather here to trade and form parties before setting out into the world.' },
    { tag: 'City of Magic', name: 'Geffen', bg: 'url(assets/img/city2.webp) center right / cover no-repeat, #1d1a45',
      desc: 'A city of mages crowned by a mysterious tower. Secrets of ancient magic lie buried beneath its streets.' },
    { tag: 'Mountain Village', name: 'Payon', bg: 'url(assets/img/city3.webp) center right / cover no-repeat, #183a2c',
      desc: 'A quiet village in the pine forest and home of the archers. Yet the nearby cave holds restless spirits.' },
    { tag: 'Desert City', name: 'Morroc', bg: 'url(assets/img/city4.webp) center right / cover no-repeat, #5c341c',
      desc: 'An oasis in the Sograt Desert, haven of thieves and merchants. The shadow of an ancient power still haunts it.' }
  ];
  function showRegion(i) {
    const r = regions[i];
    const bg = $('#world-bg');
    bg.classList.add('fade');
    setTimeout(() => { bg.style.background = r.bg; bg.classList.remove('fade'); }, bg.style.background ? 250 : 0);
    const box = $('#world-text');
    box.innerHTML = `<small>${r.tag}</small><h3>${r.name}</h3><p>${r.desc}</p>`;
    box.style.animation = 'none'; void box.offsetWidth; box.style.animation = '';
    $$('#world-tabs button').forEach((b, k) => b.classList.toggle('on', k === i));
  }
  regions.forEach(r => { new Image().src = r.bg.match(/url\(([^)]+)\)/)[1]; });
  $$('#world-tabs button').forEach((b, k) => b.addEventListener('click', () => showRegion(k)));
  showRegion(0);

  // Classes
  const jobs = [
    { icon: '⚔️', role: 'Swordman', name: 'Rune Knight', desc: 'Front-line knights who master ancient runes and ride dragons. Unbreakable defense, devastating offense.' },
    { icon: '🔮', role: 'Mage', name: 'Warlock', desc: 'Masters of the elements who call down meteors and arcane storms to sweep away their foes.' },
    { icon: '🏹', role: 'Archer', name: 'Ranger', desc: 'Sharpshooters fighting beside a loyal wolf, striking from afar and setting deadly traps.' },
    { icon: '✨', role: 'Acolyte', name: 'Arch Bishop', desc: 'Holy servants who keep the party alive with healing and divine blessings.' },
    { icon: '🗡️', role: 'Thief', name: 'Guillotine Cross', desc: 'Shadow assassins wielding poison and lightning-fast critical strikes.' },
    { icon: '⚙️', role: 'Merchant', name: 'Mechanic', desc: 'Genius engineers who fight from inside a Madogear armed with heavy weaponry.' }
  ];
  const list = $('#char-list');
  jobs.forEach((j, k) => {
    const b = document.createElement('button');
    b.setAttribute('aria-label', j.name);
    b.innerHTML = `<span>${j.icon}</span>`;
    b.addEventListener('click', () => showJob(k));
    list.appendChild(b);
  });
  function showJob(i) {
    const j = jobs[i], em = $('#char-emblem');
    em.textContent = j.icon;
    em.style.animation = 'none'; void em.offsetWidth; em.style.animation = '';
    $('#char-role').textContent = j.role;
    $('#char-name').textContent = j.name;
    $('#char-desc').textContent = j.desc;
    $$('button', list).forEach((b, k) => b.classList.toggle('on', k === i));
  }
  showJob(0);

  // Account: log in / sign up modal. Forms post straight to FluxCP (/account); results and errors show there.
  const auth = $('#auth'), captcha = $('#pane-register .auth-captcha img');
  function refreshCaptcha() { captcha.src = '/account/captcha?nocache=' + Math.random(); }
  function showPane(name) {
    $$('.auth-tabs button').forEach(b => b.classList.toggle('on', b.dataset.pane === name));
    $$('.auth-form').forEach(f => { f.hidden = f.id !== 'pane-' + name; });
    if (name === 'register' && !captcha.getAttribute('src')) refreshCaptcha();
    $('#pane-' + name + ' input:not([type=hidden])').focus();
  }
  function openAuth(name) {
    auth.hidden = false;
    document.body.classList.add('modal-open');
    $('#menu').classList.remove('open');
    showPane(name);
  }
  function closeAuth() {
    auth.hidden = true;
    document.body.classList.remove('modal-open');
  }
  $$('[data-auth]').forEach(a => a.addEventListener('click', e => { e.preventDefault(); openAuth(a.dataset.auth); }));
  $$('[data-pane]', auth).forEach(b => b.addEventListener('click', e => { e.preventDefault(); showPane(b.dataset.pane); }));
  $$('[data-close]', auth).forEach(b => b.addEventListener('click', closeAuth));
  addEventListener('keydown', e => { if (e.key === 'Escape' && !auth.hidden) closeAuth(); });
  $('.captcha-refresh').addEventListener('click', refreshCaptcha);
  if (location.hash === '#login') openAuth('login');
  if (location.hash === '#signup') openAuth('register');

  // Date of birth
  const reg = $('#pane-register');
  const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  const opt = (v, t) => `<option value="${v}">${t}</option>`;
  const pad = n => String(n).padStart(2, '0');
  reg.birthdate_day.innerHTML = opt('', 'Day') + Array.from({ length: 31 }, (_, i) => opt(pad(i + 1), i + 1)).join('');
  reg.birthdate_month.innerHTML = opt('', 'Month') + months.map((m, i) => opt(pad(i + 1), m)).join('');
  const thisYear = new Date().getFullYear();
  reg.birthdate_year.innerHTML = opt('', 'Year') + Array.from({ length: 80 }, (_, i) => opt(thisYear - 5 - i, thisYear - 5 - i)).join('');

  // Check FluxCP's rules in the browser first so players don't bounce between pages
  reg.addEventListener('submit', e => {
    const err = $('.auth-error', reg), p = reg.password.value;
    const d = new Date(+reg.birthdate_year.value, +reg.birthdate_month.value - 1, +reg.birthdate_day.value);
    let msg = '';
    if (!/[A-Z]/.test(p) || !/[a-z]/.test(p) || !/[0-9]/.test(p)) msg = 'Password must contain an uppercase letter, a lowercase letter, and a number.';
    else if (p.toLowerCase().includes(reg.username.value.toLowerCase())) msg = 'Password must not contain your username.';
    else if (p !== reg.confirm_password.value) msg = 'Passwords do not match.';
    else if (d.getDate() !== +reg.birthdate_day.value) msg = 'Please enter a valid date of birth.';
    err.textContent = msg;
    err.hidden = !msg;
    if (msg) { e.preventDefault(); return; }
    reg.email_address2.value = reg.email_address.value;
  });
})();
