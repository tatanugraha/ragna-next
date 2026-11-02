<?php if (!defined('FLUX_ROOT')) exit; ?>
		</div>
	</main>

	<footer class="footer">
		<p>&copy; <?php echo date('Y') ?> Ragna Next. Ragnarok Online is a trademark of Gravity Co., Ltd. This site is not affiliated with Gravity.</p>
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
		function refreshSecurityCode(imgSelector) {
			var clean = <?php echo Flux::config('UseCleanUrls') ? 'true' : 'false' ?>;
			$(imgSelector).attr('src', "<?php echo $this->url('captcha') ?>" + (clean ? '?nocache=' : '&nocache=') + Math.random());
		}
		function toggleSearchForm() { $('.search-form').slideToggle('fast'); }

		$(function () {
			$('#burger').on('click', function () { $('#menu').toggleClass('open'); });
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
