<?php if (!defined('FLUX_ROOT')) exit; ?>
<?php if ($session->isLoggedIn()): ?>
<h2>Selamat datang, <?php echo htmlspecialchars($session->account->userid) ?></h2>
<p>Kelola akun Ragna Next kamu dari sini.</p>
<div class="cp-tiles">
	<a href="<?php echo $this->url('account', 'view') ?>"><strong>Akun Saya</strong><span>Info akun &amp; karakter</span></a>
	<a href="<?php echo $this->url('account', 'changepass') ?>"><strong>Ganti Password</strong><span>Amankan akunmu</span></a>
	<a href="<?php echo $this->url('account', 'changemail') ?>"><strong>Ganti Email</strong><span>Perbarui email akun</span></a>
	<a href="/#info"><strong>Download Client</strong><span>Mulai bermain</span></a>
</div>
<?php else: ?>
<div class="auth-card cp-welcome">
	<h2>Panel Akun</h2>
	<p>Masuk untuk mengelola akun dan karakter, atau buat akun baru untuk mulai bertualang di Midgard.</p>
	<div class="cp-actions">
		<a class="btn-auth" href="<?php echo $this->url('account', 'login') ?>">Masuk</a>
		<a class="btn-line" href="<?php echo $this->url('account', 'create') ?>">Daftar Akun</a>
	</div>
</div>
<?php endif ?>
