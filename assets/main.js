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

  // Highlight menu & dots for the visible section
  const links = $$('.menu a, .dots a');
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (!e.isIntersecting) return;
      links.forEach(a => a.classList.toggle('on', a.getAttribute('href') === '#' + e.target.id));
    });
  }, { threshold: 0.5 });
  $$('.panel').forEach(p => io.observe(p));

  // Hero: Yggdrasil spores and leaves drifting upward
  const cv = $('#stars'), ctx = cv.getContext('2d');
  const still = matchMedia('(prefers-reduced-motion: reduce)').matches;
  let motes = [];
  function mote(anywhere) {
    const d = devicePixelRatio, leaf = Math.random() < 0.3;
    return {
      x: Math.random() * cv.width, y: anywhere ? Math.random() * cv.height : cv.height + 20 * d,
      r: (leaf ? Math.random() * 3 + 3 : Math.random() * 1.6 + 0.6) * d, leaf,
      vy: (Math.random() * 0.25 + 0.15) * d, sway: Math.random() * Math.PI * 2, rot: Math.random() * Math.PI,
      hue: Math.random() < 0.8 ? 145 : 50
    };
  }
  function resize() {
    cv.width = cv.offsetWidth * devicePixelRatio;
    cv.height = cv.offsetHeight * devicePixelRatio;
    motes = Array.from({ length: 70 }, () => mote(true));
  }
  function draw(t) {
    ctx.clearRect(0, 0, cv.width, cv.height);
    for (const m of motes) {
      if (!still) {
        m.y -= m.vy; m.x += Math.sin(t / 1800 + m.sway) * 0.3 * devicePixelRatio; m.rot += 0.004;
        if (m.y < -20) Object.assign(m, mote(false));
      }
      const a = 0.35 + 0.5 * Math.abs(Math.sin(t / 1600 + m.sway));
      ctx.save();
      ctx.translate(m.x, m.y);
      if (m.leaf) {
        ctx.rotate(m.rot);
        ctx.fillStyle = `hsla(${m.hue}, 70%, 65%, ${a * 0.8})`;
        ctx.beginPath(); ctx.ellipse(0, 0, m.r, m.r * 0.45, 0, 0, Math.PI * 2); ctx.fill();
      } else {
        ctx.shadowColor = `hsla(${m.hue}, 90%, 70%, 1)`; ctx.shadowBlur = 8 * devicePixelRatio;
        ctx.fillStyle = `hsla(${m.hue}, 90%, 80%, ${a})`;
        ctx.beginPath(); ctx.arc(0, 0, m.r, 0, Math.PI * 2); ctx.fill();
      }
      ctx.restore();
    }
    if (!still) requestAnimationFrame(draw);
  }
  addEventListener('resize', resize);
  resize(); requestAnimationFrame(draw);

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

  // Account: log in / sign up modal. Forms post straight to FluxCP (/akun); results and errors show there.
  const auth = $('#auth'), captcha = $('#pane-register .auth-captcha img');
  function refreshCaptcha() { captcha.src = '/akun/captcha?nocache=' + Math.random(); }
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
