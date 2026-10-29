<?php if (!defined('FLUX_ROOT')) exit; ?>
<!doctype html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php if (isset($metaRefresh)): ?>
	<meta http-equiv="refresh" content="<?php echo $metaRefresh['seconds'] ?>; URL=<?php echo $metaRefresh['location'] ?>">
	<?php endif ?>
	<title><?php if (isset($title)) echo htmlspecialchars($title) . ' — ' ?>Ragna Next</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cinzel:wght@500;700&family=Noto+Sans:wght@400;500;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo $this->themePath('css/ragnanext.css') ?>">
	<?php if (Flux::config('EnableReCaptcha')): ?>
	<script src="https://www.google.com/recaptcha/api.js"></script>
	<?php endif ?>
	<!-- View bawaan FluxCP memakai jQuery di script inline -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
</head>
<body>
	<header class="topbar">
		<a href="/" class="brand" aria-label="Ragna Next">
			<svg class="brand-mark" viewBox="0 0 40 40" aria-hidden="true"><path d="M20 2 L25 15 L38 20 L25 25 L20 38 L15 25 L2 20 L15 15 Z"/><circle cx="20" cy="20" r="4"/></svg>
			<span>RAGNA<b>NEXT</b></span>
		</a>
		<nav class="menu" id="menu">
			<a href="/">Beranda</a>
			<a href="/#berita">Berita</a>
			<a href="/#dunia">Dunia</a>
			<a href="/#karakter">Karakter</a>
			<a href="/#info">Info Server</a>
			<a href="<?php echo $this->url('main') ?>" class="on">Akun</a>
		</nav>
		<div class="topbar-actions">
			<?php if ($session->isLoggedIn()): ?>
			<a class="avatar" href="<?php echo $this->url('account', 'view') ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8.5" r="4"/><path d="M4 20.5c1.4-4 4.4-6 8-6s6.6 2 8 6"/></svg>
				<span><?php echo htmlspecialchars($session->account->userid) ?></span>
			</a>
			<a class="btn-sm" href="<?php echo $this->url('account', 'logout') ?>">Keluar</a>
			<?php else: ?>
			<a class="btn-sm gold" href="<?php echo $this->url('account', 'create') ?>">Daftar</a>
			<a class="avatar" href="<?php echo $this->url('account', 'login') ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8.5" r="4"/><path d="M4 20.5c1.4-4 4.4-6 8-6s6.6 2 8 6"/></svg>
				<span>Masuk</span>
			</a>
			<?php endif ?>
		</div>
		<button class="burger" id="burger" aria-label="Menu"><span></span><span></span><span></span></button>
	</header>

	<div class="cp-hero">
		<p class="cp-kicker"><span></span>Panel Akun<span></span></p>
		<h1><?php echo htmlspecialchars(isset($title) ? $title : 'Ragna Next') ?></h1>
	</div>

	<?php $menuItems = $this->getMenuItems(); $adminMenuItems = $this->getAdminMenuItems(); ?>
	<nav class="cp-nav" aria-label="Menu panel akun">
		<?php foreach ($menuItems as $menuCategory => $menus): ?>
			<?php if (!empty($menus)): ?>
			<div class="cp-drop">
				<button type="button"><?php echo htmlspecialchars(Flux::message($menuCategory)) ?></button>
				<div class="cp-drop-list">
					<?php foreach ($menus as $menuItem): ?>
					<a href="<?php echo $menuItem['url'] ?>"><?php echo htmlspecialchars(Flux::message($menuItem['name'])) ?></a>
					<?php endforeach ?>
				</div>
			</div>
			<?php endif ?>
		<?php endforeach ?>
		<?php if (!empty($adminMenuItems) && Flux::config('AdminMenuNewStyle')): ?>
			<div class="cp-drop">
				<button type="button">Admin</button>
				<div class="cp-drop-list">
					<?php foreach ($adminMenuItems as $menuItem): ?>
					<a href="<?php echo $this->url($menuItem['module'], $menuItem['action']) ?>"><?php echo htmlspecialchars(Flux::message($menuItem['name'])) ?></a>
					<?php endforeach ?>
				</div>
			</div>
		<?php endif ?>
	</nav>

	<main class="cp-main">
		<div class="cp-card">
			<?php if ($message = $session->getMessage()): ?>
			<p class="message"><?php echo htmlspecialchars($message) ?></p>
			<?php endif ?>

			<?php include $this->themePath('main/submenu.php', true) ?>
			<?php include $this->themePath('main/pagemenu.php', true) ?>
