<?php if (!defined('FLUX_ROOT')) exit; ?>
<?php $escapeChangelog = function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }; ?>
<section class="changelog-editor">
	<div class="changelog-editor-head">
		<div><p class="cp-kicker">STAFF CONTENT</p><h2>Changelog editor</h2></div>
		<span><?php echo count($items) ?> / 50 entries</span>
	</div>
	<p class="changelog-editor-help">Changes publish to the website and patcher feed after you save.</p>
	<form method="post" action="<?php echo $this->url('changelog', 'save') ?>" id="changelog-editor-form">
		<input type="hidden" name="csrf_token" value="<?php echo $escapeChangelog($csrfToken) ?>">
		<div class="changelog-editor-list" id="changelog-editor-list" data-next-index="<?php echo count($items) ?>">
			<?php foreach ($items as $index => $item): ?>
			<fieldset class="changelog-edit-entry">
				<legend>Entry <?php echo (int)$index + 1 ?></legend>
				<input type="hidden" name="items[<?php echo (int)$index ?>][id]" value="<?php echo $escapeChangelog($item['id']) ?>">
				<input type="hidden" name="items[<?php echo (int)$index ?>][deleted]" value="0" data-deleted>
				<div class="changelog-edit-grid">
					<label>Title<input name="items[<?php echo (int)$index ?>][title]" maxlength="140" value="<?php echo $escapeChangelog($item['title']) ?>" required></label>
					<label>Date<input name="items[<?php echo (int)$index ?>][date]" type="date" value="<?php echo $escapeChangelog($item['date']) ?>" required></label>
					<label>Category<select name="items[<?php echo (int)$index ?>][category]"><option value="event"<?php if ($item['category'] === 'event') echo ' selected' ?>>Event</option><option value="info"<?php if ($item['category'] === 'info') echo ' selected' ?>>Info</option><option value="update"<?php if ($item['category'] === 'update') echo ' selected' ?>>Update</option></select></label>
				</div>
				<label>Short description<textarea name="items[<?php echo (int)$index ?>][summary]" maxlength="1200" rows="3"><?php echo $escapeChangelog($item['summary']) ?></textarea></label>
				<button class="changelog-remove" type="button" data-remove-entry>Remove entry</button>
			</fieldset>
			<?php endforeach ?>
		</div>
		<template id="changelog-entry-template">
			<fieldset class="changelog-edit-entry">
				<legend>New entry</legend>
				<input type="hidden" name="items[__INDEX__][id]" value="">
				<input type="hidden" name="items[__INDEX__][deleted]" value="0" data-deleted>
				<div class="changelog-edit-grid">
					<label>Title<input name="items[__INDEX__][title]" maxlength="140" required></label>
					<label>Date<input name="items[__INDEX__][date]" type="date" required></label>
					<label>Category<select name="items[__INDEX__][category]"><option value="event">Event</option><option value="info" selected>Info</option><option value="update">Update</option></select></label>
				</div>
				<label>Short description<textarea name="items[__INDEX__][summary]" maxlength="1200" rows="3"></textarea></label>
				<button class="changelog-remove" type="button" data-remove-entry>Remove entry</button>
			</fieldset>
		</template>
		<div class="changelog-editor-actions">
			<button class="btn-line" id="changelog-add-entry" type="button">Add entry</button>
			<button class="btn-auth" type="submit">Save changelog</button>
		</div>
	</form>
</section>
<script>
(function () {
	var list = document.getElementById('changelog-editor-list');
	var template = document.getElementById('changelog-entry-template');
	var addButton = document.getElementById('changelog-add-entry');
	addButton.addEventListener('click', function () {
		var index = Number(list.dataset.nextIndex || 0);
		if (list.querySelectorAll('.changelog-edit-entry:not([hidden])').length >= 50) return;
		var entry = template.content.firstElementChild.cloneNode(true);
		entry.querySelectorAll('[name]').forEach(function (field) { field.name = field.name.replaceAll('__INDEX__', String(index)); });
		list.appendChild(entry);
		list.dataset.nextIndex = String(index + 1);
		entry.querySelector('input:not([type="hidden"])').focus();
	});
	list.addEventListener('click', function (event) {
		var button = event.target.closest('[data-remove-entry]');
		if (!button) return;
		var entry = button.closest('.changelog-edit-entry');
		entry.querySelector('[data-deleted]').value = '1';
		entry.hidden = true;
	});
	document.getElementById('changelog-editor-form').addEventListener('submit', function (event) {
		if (list.querySelectorAll('.changelog-edit-entry:not([hidden])').length === 0 && !window.confirm('Remove every changelog entry?')) event.preventDefault();
	});
})();
</script>
