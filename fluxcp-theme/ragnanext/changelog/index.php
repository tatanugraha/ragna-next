<?php if (!defined('FLUX_ROOT')) exit; ?>
<?php
$escapeDashboard = function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$dashboardCollections = array(
	'news' => array('title' => 'News', 'description' => 'Announcements and community updates shown on the website and patcher.', 'items' => $news),
	'changelog' => array('title' => 'Game changelog', 'description' => 'Game updates shown on the website and patcher home page.', 'items' => $changelog),
	'events' => array('title' => 'Events', 'description' => 'Event dates, promotion details, and optional links.', 'items' => $events)
);
?>
<section class="staff-dashboard" aria-labelledby="staff-dashboard-title">
	<header class="staff-dashboard-header">
		<div><p class="cp-kicker staff-dashboard-kicker">STAFF WORKSPACE</p><h2 id="staff-dashboard-title">Content dashboard</h2><p>Manage the content players see on the website and in the patcher.</p></div>
		<a class="btn-sm" href="/">View website</a>
	</header>
	<form method="post" action="<?php echo $this->url('changelog', 'save') ?>" id="staff-dashboard-form">
		<input type="hidden" name="csrf_token" value="<?php echo $escapeDashboard($csrfToken) ?>">
		<input type="hidden" name="editor_mode" value="website-cms">
		<div class="staff-dashboard-layout">
			<nav class="staff-dashboard-nav" aria-label="Dashboard sections" role="tablist" aria-orientation="vertical">
				<p>CONTENT</p>
				<button type="button" class="is-active" id="dashboard-tab-overview" data-dashboard-tab="overview" role="tab" aria-selected="true" aria-controls="dashboard-panel-overview">Overview</button>
				<?php foreach ($dashboardCollections as $key => $collection): ?>
				<button type="button" id="dashboard-tab-<?php echo $key ?>" data-dashboard-tab="<?php echo $key ?>" role="tab" aria-selected="false" aria-controls="dashboard-panel-<?php echo $key ?>"><?php echo $collection['title'] ?><span><?php echo count($collection['items']) ?></span></button>
				<?php endforeach ?>
			</nav>
			<div class="staff-dashboard-main">
				<section class="staff-dashboard-panel" id="dashboard-panel-overview" role="tabpanel" aria-labelledby="dashboard-tab-overview">
					<div class="staff-dashboard-panel-heading"><p class="cp-kicker">CONTENT MANAGEMENT</p><h3>Choose what to manage</h3><p>Each section is published to its matching website and patcher feed.</p></div>
					<div class="staff-dashboard-cards">
						<?php foreach ($dashboardCollections as $key => $collection): ?>
						<article class="staff-dashboard-card"><span><?php echo strtoupper($collection['title']) ?></span><strong><?php echo count($collection['items']) ?></strong><p><?php echo $collection['description'] ?></p><button type="button" class="btn-sm" data-open-dashboard-tab="<?php echo $key ?>">Manage <?php echo $collection['title'] ?></button></article>
						<?php endforeach ?>
					</div>
				</section>

				<?php foreach ($dashboardCollections as $key => $collection): ?>
				<section class="staff-dashboard-panel" id="dashboard-panel-<?php echo $key ?>" role="tabpanel" aria-labelledby="dashboard-tab-<?php echo $key ?>" hidden>
					<div class="staff-dashboard-panel-heading"><p class="cp-kicker"><?php echo $key === 'events' ? 'SCHEDULED CONTENT' : 'PUBLIC CONTENT' ?></p><h3><?php echo $collection['title'] ?></h3><p><?php echo $collection['description'] ?></p></div>
					<div class="staff-dashboard-entries" id="dashboard-entries-<?php echo $key ?>" data-collection="<?php echo $key ?>" data-next-index="<?php echo count($collection['items']) ?>">
						<?php foreach ($collection['items'] as $index => $item): ?>
						<fieldset class="staff-dashboard-entry">
							<legend><?php echo $collection['title'] ?> <?php echo (int)$index + 1 ?></legend>
							<input type="hidden" name="<?php echo $key ?>[<?php echo (int)$index ?>][id]" value="<?php echo $escapeDashboard($item['id'] ?? '') ?>">
							<input type="hidden" name="<?php echo $key ?>[<?php echo (int)$index ?>][deleted]" value="0" data-deleted>
							<?php if ($key === 'events'): ?>
							<div class="staff-dashboard-grid"><label>Event title<input name="events[<?php echo (int)$index ?>][title]" maxlength="140" value="<?php echo $escapeDashboard($item['title'] ?? '') ?>" required></label><label>Start date<input type="date" name="events[<?php echo (int)$index ?>][startDate]" value="<?php echo $escapeDashboard($item['startDate'] ?? '') ?>" required></label><label>End date<input type="date" name="events[<?php echo (int)$index ?>][endDate]" value="<?php echo $escapeDashboard($item['endDate'] ?? '') ?>"></label></div>
							<label>Short summary<textarea name="events[<?php echo (int)$index ?>][summary]" maxlength="320" rows="2"><?php echo $escapeDashboard($item['summary'] ?? '') ?></textarea></label>
							<label>Event details<textarea name="events[<?php echo (int)$index ?>][description]" maxlength="1200" rows="3"><?php echo $escapeDashboard($item['description'] ?? '') ?></textarea></label>
							<div class="staff-dashboard-grid"><label>Promotion image URL (optional)<input type="url" name="events[<?php echo (int)$index ?>][image]" maxlength="500" placeholder="https://" value="<?php echo $escapeDashboard($item['image'] ?? '') ?>"></label><label>Event page URL (optional)<input type="url" name="events[<?php echo (int)$index ?>][url]" maxlength="500" placeholder="https://" value="<?php echo $escapeDashboard($item['url'] ?? '') ?>"></label></div>
							<?php else: ?>
							<div class="staff-dashboard-grid"><label>Title<input name="<?php echo $key ?>[<?php echo (int)$index ?>][title]" maxlength="140" value="<?php echo $escapeDashboard($item['title'] ?? '') ?>" required></label><label>Publication date<input type="date" name="<?php echo $key ?>[<?php echo (int)$index ?>][date]" value="<?php echo $escapeDashboard($item['date'] ?? '') ?>" required></label><label>Category<select name="<?php echo $key ?>[<?php echo (int)$index ?>][category]"><?php foreach (array('event' => 'Event', 'info' => 'Info', 'update' => 'Update') as $value => $label): ?><option value="<?php echo $value ?>"<?php if (($item['category'] ?? '') === $value) echo ' selected' ?>><?php echo $label ?></option><?php endforeach ?></select></label></div>
							<label>Short description<textarea name="<?php echo $key ?>[<?php echo (int)$index ?>][summary]" maxlength="1200" rows="3"><?php echo $escapeDashboard($item['summary'] ?? '') ?></textarea></label>
							<?php endif ?>
							<button class="staff-dashboard-remove" type="button" data-remove-dashboard-entry>Remove <?php echo $key === 'events' ? 'event' : 'entry' ?></button>
						</fieldset>
						<?php endforeach ?>
					</div>
					<div class="staff-dashboard-section-actions"><button class="btn-sm" type="button" data-add-dashboard-entry="<?php echo $key ?>">Add <?php echo $key === 'events' ? 'event' : 'entry' ?></button><span>Up to 50 items</span></div>
				</section>
				<?php endforeach ?>
			</div>
		</div>
		<p class="staff-dashboard-notice" id="staff-dashboard-notice" role="status" aria-live="polite"></p>
		<div class="staff-dashboard-save"><button class="btn-auth" type="submit">Save all changes</button></div>
	</form>
</section>

<template id="dashboard-entry-template">
	<fieldset class="staff-dashboard-entry"><legend data-entry-legend>New entry</legend><input type="hidden" data-field="id" value=""><input type="hidden" data-field="deleted" value="0" data-deleted>
		<div data-fields="news, changelog"><div class="staff-dashboard-grid"><label>Title<input data-field="title" maxlength="140" required></label><label>Publication date<input type="date" data-field="date" required></label><label>Category<select data-field="category"><option value="event">Event</option><option value="info">Info</option><option value="update" selected>Update</option></select></label></div><label>Short description<textarea data-field="summary" maxlength="1200" rows="3"></textarea></label></div>
		<div data-fields="events"><div class="staff-dashboard-grid"><label>Event title<input data-field="title" maxlength="140" required></label><label>Start date<input type="date" data-field="startDate" required></label><label>End date<input type="date" data-field="endDate"></label></div><label>Short summary<textarea data-field="summary" maxlength="320" rows="2"></textarea></label><label>Event details<textarea data-field="description" maxlength="1200" rows="3"></textarea></label><div class="staff-dashboard-grid"><label>Promotion image URL (optional)<input type="url" data-field="image" maxlength="500" placeholder="https://"></label><label>Event page URL (optional)<input type="url" data-field="url" maxlength="500" placeholder="https://"></label></div></div>
		<button class="staff-dashboard-remove" type="button" data-remove-dashboard-entry>Remove entry</button>
	</fieldset>
</template>
<script>
(function () {
	var form = document.getElementById('staff-dashboard-form');
	var template = document.getElementById('dashboard-entry-template');
	var notice = document.getElementById('staff-dashboard-notice');
	var sections = ['overview', 'news', 'changelog', 'events'];
	function selectSection(id) {
		sections.forEach(function (key) {
			var tab = document.getElementById('dashboard-tab-' + key);
			var panel = document.getElementById('dashboard-panel-' + key);
			var active = key === id;
			tab.classList.toggle('is-active', active);
			tab.setAttribute('aria-selected', String(active));
			panel.hidden = !active;
		});
	}
	document.querySelectorAll('[data-dashboard-tab]').forEach(function (tab, index, tabs) {
		tab.addEventListener('click', function () { selectSection(tab.dataset.dashboardTab); });
		tab.addEventListener('keydown', function (event) {
			if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;
			event.preventDefault();
			var offset = event.key === 'ArrowDown' ? 1 : -1;
			var next = tabs[(index + offset + tabs.length) % tabs.length];
			next.focus();
			selectSection(next.dataset.dashboardTab);
		});
	});
	document.querySelectorAll('[data-open-dashboard-tab]').forEach(function (button) { button.addEventListener('click', function () { selectSection(button.dataset.openDashboardTab); document.getElementById('dashboard-tab-' + button.dataset.openDashboardTab).focus(); }); });
	document.querySelectorAll('[data-add-dashboard-entry]').forEach(function (button) {
		button.addEventListener('click', function () {
			var collection = button.dataset.addDashboardEntry;
			var list = document.getElementById('dashboard-entries-' + collection);
			if (list.querySelectorAll('.staff-dashboard-entry:not([hidden])').length >= 50) { notice.textContent = 'Each section can contain up to 50 items.'; return; }
			var index = Number(list.dataset.nextIndex || 0);
			var entry = template.content.firstElementChild.cloneNode(true);
			entry.querySelector('[data-entry-legend]').textContent = 'New ' + (collection === 'events' ? 'event' : 'entry');
			entry.querySelectorAll('[data-fields]').forEach(function (group) {
				var enabled = group.dataset.fields.split(', ').includes(collection);
				group.hidden = !enabled;
				group.querySelectorAll('input, select, textarea').forEach(function (field) { field.disabled = !enabled; });
			});
			entry.querySelectorAll('[data-field]').forEach(function (field) { field.name = collection + '[' + index + '][' + field.dataset.field + ']'; });
			list.appendChild(entry);
			list.dataset.nextIndex = String(index + 1);
			entry.querySelector('input:not([type="hidden"])').focus();
			notice.textContent = '';
		});
	});
	form.addEventListener('click', function (event) {
		var button = event.target.closest('[data-remove-dashboard-entry]');
		if (!button) return;
		var entry = button.closest('.staff-dashboard-entry');
		entry.querySelector('[data-deleted]').value = '1';
		entry.querySelectorAll('input:not([data-deleted]), select, textarea').forEach(function (field) { field.disabled = true; });
		entry.hidden = true;
	});
	form.addEventListener('submit', function (event) {
		var emptySections = ['news', 'changelog', 'events'].filter(function (key) { return document.querySelectorAll('#dashboard-entries-' + key + ' .staff-dashboard-entry:not([hidden])').length === 0; });
		if (emptySections.length && !window.confirm('This will remove all items from ' + emptySections.join(', ') + '. Continue?')) event.preventDefault();
	});
})();
</script>
