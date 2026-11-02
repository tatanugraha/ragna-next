<?php
if (!defined('FLUX_ROOT')) exit;

define('RAGNA_CHANGELOG_FILE', '/var/lib/ragna-next/changelog.json');
define('RAGNA_CHANGELOG_PAGE_URL', 'https://ragna-next.cc/#changelog');
define('RAGNA_CHANGELOG_MAX_ITEMS', 50);

function ragnaChangelogRead()
{
	$raw = @file_get_contents(RAGNA_CHANGELOG_FILE);
	if ($raw === false || strlen($raw) > 262144) return array('items' => array());
	$data = json_decode($raw, true);
	if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) return array('items' => array());

	$items = array();
	foreach (array_slice($data['items'], 0, RAGNA_CHANGELOG_MAX_ITEMS) as $item) {
		if (!is_array($item)) continue;
		$title = trim((string)($item['title'] ?? ''));
		$date = trim((string)($item['date'] ?? ''));
		$category = strtolower(trim((string)($item['category'] ?? 'info')));
		if ($title === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) continue;
		if (!in_array($category, array('event', 'info', 'update'), true)) $category = 'info';
		$items[] = array(
			'id' => preg_match('/^[A-Za-z0-9_-]{1,64}$/', (string)($item['id'] ?? '')) ? (string)$item['id'] : bin2hex(random_bytes(8)),
			'title' => substr($title, 0, 560),
			'date' => $date,
			'category' => $category,
			'summary' => substr(trim((string)($item['summary'] ?? '')), 0, 4800),
			'url' => RAGNA_CHANGELOG_PAGE_URL
		);
	}
	return array('items' => $items);
}

function ragnaChangelogWrite(array $items)
{
	$directory = dirname(RAGNA_CHANGELOG_FILE);
	$lockPath = RAGNA_CHANGELOG_FILE . '.lock';
	if (!is_dir($directory) || is_link($directory) || is_link(RAGNA_CHANGELOG_FILE) || is_link($lockPath)) {
		throw new RuntimeException('Changelog storage is not available.');
	}
	$lock = @fopen($lockPath, 'c');
	if (!$lock || !flock($lock, LOCK_EX)) {
		if ($lock) fclose($lock);
		throw new RuntimeException('Changelog storage is busy.');
	}

	$tempPath = null;
	try {
		$tempPath = tempnam($directory, '.changelog-');
		if ($tempPath === false) throw new RuntimeException('Changelog storage is not available.');
		$payload = json_encode(array('items' => $items), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		if (!is_string($payload) || file_put_contents($tempPath, $payload . "\n", LOCK_EX) === false) {
			throw new RuntimeException('Changelog could not be saved.');
		}
		@chmod($tempPath, 0640);
		if (!rename($tempPath, RAGNA_CHANGELOG_FILE)) throw new RuntimeException('Changelog could not be saved.');
		$tempPath = null;
	} finally {
		if ($tempPath && is_file($tempPath)) @unlink($tempPath);
		flock($lock, LOCK_UN);
		fclose($lock);
	}
}

function ragnaChangelogCleanText($value, $maxLength)
{
	if (!is_string($value) && !is_numeric($value)) return '';
	$text = trim(strip_tags((string)$value));
	$text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
	if (!is_string($text) || (function_exists('mb_check_encoding') && !mb_check_encoding($text, 'UTF-8'))) return null;
	$length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
	if ($length > $maxLength) return null;
	return $text;
}

function ragnaChangelogNormalizePostedItems($posted, &$error)
{
	$error = '';
	if (!is_array($posted) || count($posted) > RAGNA_CHANGELOG_MAX_ITEMS) {
		$error = 'Changelog must contain no more than 50 entries.';
		return null;
	}
	$items = array();
	foreach ($posted as $raw) {
		if (!is_array($raw)) {
			$error = 'Invalid changelog entry.';
			return null;
		}
		if ((string)($raw['deleted'] ?? '') === '1') continue;

		$title = ragnaChangelogCleanText($raw['title'] ?? '', 140);
		$date = is_string($raw['date'] ?? null) ? trim($raw['date']) : '';
		$category = is_string($raw['category'] ?? null) ? strtolower(trim($raw['category'])) : '';
		$summary = ragnaChangelogCleanText($raw['summary'] ?? '', 1200);
		if ($title === null || $summary === null) {
			$error = 'Title or summary is too long or contains invalid text.';
			return null;
		}
		if ($title === '' && $date === '' && $summary === '') continue;
		if ($title === '') {
			$error = 'Every changelog entry needs a title.';
			return null;
		}
		$dateParts = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches) ? $matches : null;
		if (!$dateParts || !checkdate((int)$dateParts[2], (int)$dateParts[3], (int)$dateParts[1])) {
			$error = 'Use a valid date in YYYY-MM-DD format.';
			return null;
		}
		if (!in_array($category, array('event', 'info', 'update'), true)) {
			$error = 'Choose a valid changelog category.';
			return null;
		}
		$id = is_string($raw['id'] ?? null) ? trim($raw['id']) : '';
		if ($id !== '' && !preg_match('/^[A-Za-z0-9_-]{1,64}$/', $id)) {
			$error = 'Invalid changelog entry identifier.';
			return null;
		}
		$items[] = array(
			'id' => $id !== '' ? $id : bin2hex(random_bytes(8)),
			'title' => $title,
			'date' => $date,
			'category' => $category,
			'summary' => $summary,
			'url' => RAGNA_CHANGELOG_PAGE_URL
		);
	}
	return $items;
}
