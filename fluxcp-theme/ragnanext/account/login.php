<?php if (!defined('FLUX_ROOT')) exit; ?>
<div class="auth-card">
	<h2><?php echo htmlspecialchars(Flux::message('LoginHeading')) ?></h2>
	<?php if (isset($errorMessage)): ?>
	<p class="red"><?php echo htmlspecialchars($errorMessage) ?></p>
	<?php endif ?>

	<form action="<?php echo $this->url('account', 'login', array('return_url' => $params->get('return_url'))) ?>" method="post" class="auth-form">
		<?php if (count($serverNames) === 1): ?>
		<input type="hidden" name="server" value="<?php echo htmlspecialchars($session->loginAthenaGroup->serverName) ?>">
		<?php else: ?>
		<label><?php echo htmlspecialchars(Flux::message('AccountServerLabel')) ?>
			<select name="server">
				<?php foreach ($serverNames as $serverName): ?>
				<option value="<?php echo htmlspecialchars($serverName) ?>"><?php echo htmlspecialchars($serverName) ?></option>
				<?php endforeach ?>
			</select>
		</label>
		<?php endif ?>
		<label><?php echo htmlspecialchars(Flux::message('AccountUsernameLabel')) ?>
			<input type="text" name="username" value="<?php echo htmlspecialchars($params->get('username') ?: '') ?>" required autocomplete="username" autofocus>
		</label>
		<label><?php echo htmlspecialchars(Flux::message('AccountPasswordLabel')) ?>
			<input type="password" name="password" required autocomplete="current-password">
		</label>
		<?php if (Flux::config('UseLoginCaptcha')): ?>
			<?php if (Flux::config('EnableReCaptcha')): ?>
			<div class="g-recaptcha" data-theme="dark" data-sitekey="<?php echo htmlspecialchars($recaptcha) ?>"></div>
			<?php else: ?>
			<div class="auth-captcha">
				<img class="security-code" src="<?php echo $this->url('captcha') ?>" alt="<?php echo htmlspecialchars(Flux::message('AccountSecurityLabel')) ?>">
				<button type="button" class="captcha-refresh" onclick="refreshSecurityCode('img.security-code')" aria-label="<?php echo htmlspecialchars(Flux::message('RefreshSecurityCode')) ?>">&#x21bb;</button>
				<label><?php echo htmlspecialchars(Flux::message('AccountSecurityLabel')) ?><input type="text" name="security_code" required autocomplete="off"></label>
			</div>
			<?php endif ?>
		<?php endif ?>
		<button type="submit" class="btn-auth"><?php echo htmlspecialchars(Flux::message('LoginButton')) ?></button>
		<p class="auth-alt">
			<a href="<?php echo $this->url('account', 'resetpass') ?>">Lupa password?</a>
			<?php if ($auth->actionAllowed('account', 'create')): ?>
			· <a href="<?php echo $this->url('account', 'create') ?>">Buat akun baru</a>
			<?php endif ?>
		</p>
	</form>
</div>
