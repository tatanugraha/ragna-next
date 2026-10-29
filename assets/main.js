// Ragna Next — interaksi halaman
(function () {
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];

  $('#year').textContent = new Date().getFullYear();

  // Topbar solid saat scroll + menu mobile
  const topbar = $('#topbar');
  addEventListener('scroll', () => topbar.classList.toggle('solid', scrollY > 40), { passive: true });
  $('#burger').addEventListener('click', () => $('#menu').classList.toggle('open'));
  $$('#menu a').forEach(a => a.addEventListener('click', () => $('#menu').classList.remove('open')));

  // Highlight menu & dots sesuai section yang terlihat
  const links = $$('.menu a, .dots a');
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (!e.isIntersecting) return;
      links.forEach(a => a.classList.toggle('on', a.getAttribute('href') === '#' + e.target.id));
    });
  }, { threshold: 0.5 });
  $$('.panel').forEach(p => io.observe(p));

  // Bintang di hero
  const cv = $('#stars'), ctx = cv.getContext('2d');
  let stars = [];
  function resize() {
    cv.width = cv.offsetWidth * devicePixelRatio;
    cv.height = cv.offsetHeight * devicePixelRatio;
    stars = Array.from({ length: 140 }, () => ({
      x: Math.random() * cv.width, y: Math.random() * cv.height * 0.65,
      r: (Math.random() * 1.4 + 0.3) * devicePixelRatio, p: Math.random() * Math.PI * 2
    }));
  }
  function draw(t) {
    ctx.clearRect(0, 0, cv.width, cv.height);
    for (const s of stars) {
      ctx.globalAlpha = 0.35 + 0.65 * Math.abs(Math.sin(t / 1400 + s.p));
      ctx.fillStyle = '#fff6dc';
      ctx.beginPath(); ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2); ctx.fill();
    }
    requestAnimationFrame(draw);
  }
  addEventListener('resize', resize);
  resize(); requestAnimationFrame(draw);

  // Carousel berita
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

  // Tab berita
  $$('.tab').forEach(tab => tab.addEventListener('click', () => {
    $$('.tab').forEach(t => t.classList.toggle('on', t === tab));
    const cat = tab.dataset.cat;
    $$('#news-items li').forEach(li => li.classList.toggle('hide', cat !== 'all' && li.dataset.cat !== cat));
  }));

  // Dunia
  const regions = [
    { tag: 'Ibu Kota Kerajaan', name: 'Prontera', bg: 'radial-gradient(circle at 75% 35%, rgba(246,223,164,.55), transparent 40%), linear-gradient(160deg, #5a78b8, #23345e 60%, #121a30)',
      desc: 'Jantung Kerajaan Rune-Midgard. Di sinilah petualang berkumpul, berdagang, dan membentuk party sebelum menjelajah dunia.' },
    { tag: 'Kota Sihir', name: 'Geffen', bg: 'radial-gradient(circle at 70% 40%, rgba(170,140,255,.55), transparent 40%), linear-gradient(160deg, #3c2f7a, #1d1a45 60%, #0f0e24)',
      desc: 'Kota para penyihir dengan menara misterius di pusatnya. Rahasia sihir kuno tersimpan di bawah tanahnya.' },
    { tag: 'Desa Pegunungan', name: 'Payon', bg: 'radial-gradient(circle at 70% 40%, rgba(150,220,150,.45), transparent 40%), linear-gradient(160deg, #2f6a4c, #183a2c 60%, #0c1a14)',
      desc: 'Desa tenang di tengah hutan pinus, rumah para pemanah. Namun gua di dekatnya menyimpan arwah yang gelisah.' },
    { tag: 'Kota Gurun', name: 'Morroc', bg: 'radial-gradient(circle at 70% 40%, rgba(255,190,110,.6), transparent 40%), linear-gradient(160deg, #b0703a, #5c341c 60%, #22140c)',
      desc: 'Oasis di tengah gurun Sograt, tempat para pencuri dan pedagang. Bayang-bayang kekuatan kuno masih menghantuinya.' }
  ];
  function showRegion(i) {
    const r = regions[i];
    $('#world-bg').style.background = r.bg;
    const box = $('#world-text');
    box.innerHTML = `<small>${r.tag}</small><h3>${r.name}</h3><p>${r.desc}</p>`;
    box.style.animation = 'none'; void box.offsetWidth; box.style.animation = '';
    $$('#world-tabs button').forEach((b, k) => b.classList.toggle('on', k === i));
  }
  $$('#world-tabs button').forEach((b, k) => b.addEventListener('click', () => showRegion(k)));
  showRegion(0);

  // Karakter
  const jobs = [
    { icon: '⚔️', role: 'Swordman', name: 'Rune Knight', desc: 'Ksatria garis depan yang menguasai rune kuno dan menunggangi naga. Pertahanan kokoh, serangan menghancurkan.' },
    { icon: '🔮', role: 'Mage', name: 'Warlock', desc: 'Pengendali elemen yang mampu memanggil meteor dan badai sihir untuk menyapu musuh dalam sekejap.' },
    { icon: '🏹', role: 'Archer', name: 'Ranger', desc: 'Pemanah jitu bersama serigala setianya. Menyerang dari jauh dan memasang jebakan mematikan.' },
    { icon: '✨', role: 'Acolyte', name: 'Arch Bishop', desc: 'Pelayan suci yang menjaga party tetap hidup dengan penyembuhan dan berkah ilahi.' },
    { icon: '🗡️', role: 'Thief', name: 'Guillotine Cross', desc: 'Pembunuh bayangan dengan racun dan serangan kritikal secepat kilat.' },
    { icon: '⚙️', role: 'Merchant', name: 'Mechanic', desc: 'Insinyur jenius yang bertempur dari dalam Madogear dengan persenjataan berat.' }
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
})();
