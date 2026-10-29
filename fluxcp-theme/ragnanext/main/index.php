<?php if (!defined('FLUX_ROOT')) exit; ?>
<?php if ($session->isLoggedIn()): ?>
<h2>Welcome, <?php echo htmlspecialchars($session->account->userid) ?></h2>
<p>Manage your Ragna Next account from here.</p>
<div class="cp-tiles">
	<a href="<?php echo $this->url('account', 'view') ?>"><strong>My Account</strong><span>Account &amp; character info</span></a>
	<a href="<?php echo $this->url('account', 'changepass') ?>"><strong>Change Password</strong><span>Keep your account secure</span></a>
	<a href="<?php echo $this->url('account', 'changemail') ?>"><strong>Change Email</strong><span>Update your account email</span></a>
	<a href="/#info"><strong>Download Client</strong><span>Start playing</span></a>
</div>
<?php else: ?>
<div class="auth-card cp-welcome">
	<h2>Account Panel</h2>
	<p>Log in to manage your account and characters, or create a new account to begin your adventure in Midgard.</p>
	<div class="cp-actions">
		<a class="btn-auth" href="<?php echo $this->url('account', 'login') ?>">Log In</a>
		<a class="btn-line" href="<?php echo $this->url('account', 'create') ?>">Create Account</a>
	</div>
</div>
<?php endif ?>
