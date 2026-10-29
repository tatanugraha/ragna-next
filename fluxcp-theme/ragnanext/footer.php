<?php if (!defined('FLUX_ROOT')) exit; ?>
		</div>
	</main>

	<footer class="footer">
		<p>&copy; <?php echo date('Y') ?> Ragna Next. Ragnarok Online is a trademark of Gravity Co., Ltd. This site is not affiliated with Gravity.</p>
		<?php if (count(Flux::getAvailableLanguages()) > 1): ?>
		<p>Language:
			<select name="preferred_language" onchange="updatePreferredLanguage(this)">
			<?php foreach (Flux::getAvailableLanguages() as $lang_key => $lang): ?>
				<option value="<?php echo htmlspecialchars($lang_key) ?>"<?php if (!empty($_COOKIE['language']) && $_COOKIE['language'] == $lang_key) echo ' selected="selected"' ?>><?php echo htmlspecialchars($lang) ?></option>
			<?php endforeach ?>
			</select>
		</p>
		<?php endif ?>
	</footer>

	<form action="<?php echo $this->urlWithQs ?>" method="post" name="preferred_server_form" style="display: none">
		<input type="hidden" name="preferred_server" value="">
	</form>

	<script>
		// Functions called by FluxCP's built-in views
		function updatePreferredServer(sel) {
			document.preferred_server_form.preferred_server.value = sel.options[sel.selectedIndex].value;
			document.preferred_server_form.submit();
		}
		function updatePreferredLanguage(sel) {
			setCookie('language', sel.options[sel.selectedIndex].value);
			reload();
		}
		function refreshSecurityCode(imgSelector) {
			var clean = <?php echo Flux::config('UseCleanUrls') ? 'true' : 'false' ?>;
			$(imgSelector).attr('src', "<?php echo $this->url('captcha') ?>" + (clean ? '?nocache=' : '&nocache=') + Math.random());
		}
		function toggleSearchForm() { $('.search-form').slideToggle('fast'); }
		function setCookie(key, value) {
			var expires = new Date();
			expires.setTime(expires.getTime() + expires.getTime());
			document.cookie = key + '=' + value + ';path=/;expires=' + expires.toUTCString();
		}
		function reload() { window.location.href = '<?php echo $this->url ?>'; }

		$(function () {
			$('#burger').on('click', function () { $('#menu').toggleClass('open'); });
			$('.cp-drop > button').on('click', function () {
				var drop = $(this).parent();
				$('.cp-drop').not(drop).removeClass('open');
				drop.toggleClass('open');
			});
			$(document).on('click', function (e) { if (!$(e.target).closest('.cp-drop').length) $('.cp-drop').removeClass('open'); });
			$('.money-input').keyup(function () {
				var v = parseInt($(this).val() / <?php echo Flux::config('CreditExchangeRate') ?>, 10);
				$('.credit-input').val(isNaN(v) ? '?' : v);
			}).keyup();
			$('.credit-input').keyup(function () {
				var v = parseFloat($(this).val() * <?php echo Flux::config('CreditExchangeRate') ?>);
				$('.money-input').val(isNaN(v) ? '?' : v.toFixed(2));
			}).keyup();
		});
	</script>
</body>
</html>
