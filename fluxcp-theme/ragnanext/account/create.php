<?php if (!defined('FLUX_ROOT')) exit; ?>
<div class="auth-card">
	<h2><?php echo htmlspecialchars(Flux::message('AccountCreateHeading')) ?></h2>
	<?php if (Flux::config('RequireEmailConfirm')): ?>
	<p class="info-text">Gunakan email aktif: kamu perlu mengonfirmasi akun lewat email sebelum bisa login.</p>
	<?php endif ?>
	<?php if (isset($errorMessage)): ?>
	<p class="red"><?php echo htmlspecialchars($errorMessage) ?></p>
	<?php endif ?>

	<form action="<?php echo $this->url ?>" method="post" class="auth-form">
		<?php if (count($serverNames) === 1): ?>
		<input type="hidden" name="server" value="<?php echo htmlspecialchars($session->loginAthenaGroup->serverName) ?>">
		<?php else: ?>
		<label><?php echo htmlspecialchars(Flux::message('AccountServerLabel')) ?>
			<select name="server">
				<?php foreach ($serverNames as $serverName): ?>
				<option value="<?php echo htmlspecialchars($serverName) ?>"<?php if ($params->get('server') == $serverName) echo ' selected' ?>><?php echo htmlspecialchars($serverName) ?></option>
				<?php endforeach ?>
			</select>
		</label>
		<?php endif ?>

		<label><?php echo htmlspecialchars(Flux::message('AccountUsernameLabel')) ?>
			<input type="text" name="username" value="<?php echo htmlspecialchars($params->get('username') ?: '') ?>" required autocomplete="username">
		</label>
		<div class="auth-row">
			<label><?php echo htmlspecialchars(Flux::message('AccountPasswordLabel')) ?>
				<input type="password" name="password" required autocomplete="new-password">
			</label>
			<label><?php echo htmlspecialchars(Flux::message('AccountPassConfirmLabel')) ?>
				<input type="password" name="confirm_password" required autocomplete="new-password">
			</label>
		</div>
		<p class="auth-hint">
			<?php printf('%d–%d karakter', Flux::config('MinPasswordLength'), Flux::config('MaxPasswordLength')) ?><?php
			if (Flux::config('PasswordMinUpper') > 0) echo ', huruf besar';
			if (Flux::config('PasswordMinLower') > 0) echo ', huruf kecil';
			if (Flux::config('PasswordMinNumber') > 0) echo ', angka';
			if (Flux::config('PasswordMinSymbol') > 0) echo ', simbol';
			if (!Flux::config('AllowUserInPassword')) echo '; tidak boleh mengandung username' ?>.
		</p>
		<div class="auth-row">
			<label><?php echo htmlspecialchars(Flux::message('AccountEmailLabel')) ?>
				<input type="email" name="email_address" value="<?php echo htmlspecialchars($params->get('email_address') ?: '') ?>" required autocomplete="email">
			</label>
			<label><?php echo htmlspecialchars(Flux::message('AccountEmailLabel2')) ?>
				<input type="email" name="email_address2" value="<?php echo htmlspecialchars($params->get('email_address2') ?: '') ?>" required autocomplete="email">
			</label>
		</div>
		<fieldset class="auth-birth">
			<legend><?php echo htmlspecialchars(Flux::message('AccountBirthdateLabel')) ?></legend>
			<?php echo $this->dateField('birthdate', null, 0) ?>
		</fieldset>
		<fieldset class="auth-gender">
			<legend><?php echo htmlspecialchars(Flux::message('AccountGenderLabel')) ?></legend>
			<label><input type="radio" name="gender" value="M"<?php if ($params->get('gender') === 'M') echo ' checked' ?> required> <?php echo $this->genderText('M') ?></label>
			<label><input type="radio" name="gender" value="F"<?php if ($params->get('gender') === 'F') echo ' checked' ?>> <?php echo $this->genderText('F') ?></label>
		</fieldset>

		<?php if (Flux::config('UseCaptcha')): ?>
			<?php if (Flux::config('EnableReCaptcha')): ?>
			<div class="g-recaptcha" data-theme="dark" data-sitekey="<?php echo htmlspecialchars($recaptcha) ?>"></div>
			<?php else: ?>
			<?php // Tema default hanya menampilkan pesan "butuh kunci reCAPTCHA" di sini, padahal captcha bawaan (GD) dipakai. ?>
			<div class="auth-captcha">
				<img class="security-code" src="<?php echo $this->url('captcha') ?>" alt="<?php echo htmlspecialchars(Flux::message('AccountSecurityLabel')) ?>">
				<button type="button" class="captcha-refresh" onclick="refreshSecurityCode('img.security-code')" aria-label="<?php echo htmlspecialchars(Flux::message('RefreshSecurityCode')) ?>">&#x21bb;</button>
				<label><?php echo htmlspecialchars(Flux::message('AccountSecurityLabel')) ?><input type="text" name="security_code" required autocomplete="off"></label>
			</div>
			<?php endif ?>
		<?php endif ?>

		<p class="auth-hint"><?php printf(htmlspecialchars(Flux::message('AccountCreateInfo2')), '<a href="'.$this->url('service', 'tos').'">'.Flux::message('AccountCreateTerms').'</a>') ?></p>
		<button type="submit" class="btn-auth"><?php echo htmlspecialchars(Flux::message('AccountCreateButton')) ?></button>
		<p class="auth-alt">Sudah punya akun? <a href="<?php echo $this->url('account', 'login') ?>">Masuk</a></p>
	</form>
</div>
