<?php if (!defined('FLUX_ROOT')) exit; ?>
<!doctype html>
<html lang="en">
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
	<!-- FluxCP's built-in views use jQuery in inline scripts -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
</head>
<body>
	<header class="topbar">
		<a href="/" class="brand" aria-label="Ragna Next">
			<svg class="brand-mark" viewBox="0 0 40 40" aria-hidden="true"><circle cx="20" cy="20" r="18.5"/><path d="M7 17c2-7 8-11 13-11s11 4 13 11c-3 2-7 3-13 3s-10-1-13-3z"/><path d="M20 20v11M20 24l-5-4M20 24l5-4M20 31l-6 5M20 31l6 5M20 31v6"/></svg>
			<span>RAGNA<b>NEXT</b></span>
		</a>
		<nav class="menu" id="menu">
			<a href="/">Home</a>
			<a href="/#news">News</a>
			<a href="/#world">World</a>
			<a href="/#heroes">Classes</a>
			<a href="/#info">Server Info</a>
			<a href="<?php echo $this->url('main') ?>" class="on">Account</a>
		</nav>
		<div class="topbar-actions">
			<?php if ($session->isLoggedIn()): ?>
			<a class="avatar" href="<?php echo $this->url('account', 'view') ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8.5" r="4"/><path d="M4 20.5c1.4-4 4.4-6 8-6s6.6 2 8 6"/></svg>
				<span><?php echo htmlspecialchars($session->account->userid) ?></span>
			</a>
			<a class="btn-sm" href="<?php echo $this->url('account', 'logout') ?>">Log Out</a>
			<?php else: ?>
			<a class="btn-sm gold" href="<?php echo $this->url('account', 'create') ?>">Sign Up</a>
			<a class="avatar" href="<?php echo $this->url('account', 'login') ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8.5" r="4"/><path d="M4 20.5c1.4-4 4.4-6 8-6s6.6 2 8 6"/></svg>
				<span>Log In</span>
			</a>
			<?php endif ?>
		</div>
		<button class="burger" id="burger" aria-label="Menu"><span></span><span></span><span></span></button>
	</header>

	<div class="cp-hero">
		<p class="cp-kicker"><span></span>Account Panel<span></span></p>
		<h1><?php echo htmlspecialchars(isset($title) ? $title : 'Ragna Next') ?></h1>
	</div>

	<?php $menuItems = $this->getMenuItems(); $adminMenuItems = $this->getAdminMenuItems(); ?>
	<?php if ($session->isLoggedIn()): ?>
	<nav class="cp-nav" aria-label="Account panel navigation">
		<div class="cp-nav-primary">
			<a href="<?php echo $this->url('main') ?>">Account home</a>
			<a href="<?php echo $this->url('account', 'view') ?>">My account</a>
			<a href="<?php echo $this->url('account', 'changepass') ?>">Change password</a>
			<a href="<?php echo $this->url('account', 'changemail') ?>">Change email</a>
			<?php if ((int)$session->account->group_level >= AccountLevel::LOWGM): ?>
			<a class="cp-staff-link" href="<?php echo $this->url('changelog', 'index') ?>">Staff dashboard</a>
			<?php endif ?>
		</div>
		<details class="cp-more-tools">
			<summary>More account tools</summary>
			<div class="cp-tools-groups">
			<?php foreach ($menuItems as $menuCategory => $menus): ?>
				<?php if (!empty($menus)): ?>
				<section><h2><?php echo htmlspecialchars(Flux::message($menuCategory)) ?></h2>
					<?php foreach ($menus as $menuItem): ?>
					<a href="<?php echo htmlspecialchars($menuItem['url'], ENT_QUOTES, 'UTF-8') ?>"><?php echo htmlspecialchars(Flux::message($menuItem['name'])) ?></a>
					<?php endforeach ?>
				</section>
				<?php endif ?>
			<?php endforeach ?>
			<?php if (!empty($adminMenuItems)): ?>
				<section><h2>Administration</h2>
					<?php foreach ($adminMenuItems as $menuItem): ?>
					<a href="<?php echo htmlspecialchars($this->url($menuItem['module'], $menuItem['action']), ENT_QUOTES, 'UTF-8') ?>"><?php echo htmlspecialchars(Flux::message($menuItem['name'])) ?></a>
					<?php endforeach ?>
				</section>
			<?php endif ?>
			</div>
		</details>
	</nav>
	<?php endif ?>

	<main class="cp-main">
		<div class="cp-card">
			<?php if ($message = $session->getMessage()): ?>
			<p class="message"><?php echo htmlspecialchars($message) ?></p>
			<?php endif ?>

			<?php include $this->themePath('main/submenu.php', true) ?>
			<?php include $this->themePath('main/pagemenu.php', true) ?>
