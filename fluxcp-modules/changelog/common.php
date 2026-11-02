<?php
if (!defined('FLUX_ROOT')) exit;

define('RAGNA_CHANGELOG_FILE', '/var/lib/ragna-next/changelog.json');
define('RAGNA_NEWS_PAGE_URL', 'https://ragna-next.cc/#news');
define('RAGNA_CHANGELOG_PAGE_URL', 'https://ragna-next.cc/#changelog');
define('RAGNA_CHANGELOG_MAX_ITEMS', 50);
define('RAGNA_EVENT_MAX_ITEMS', 50);

function ragnaContentReadData()
{
	$raw = @file_get_contents(RAGNA_CHANGELOG_FILE);
	if ($raw === false || strlen($raw) > 262144) return array();
	$data = json_decode($raw, true);
	return is_array($data) ? $data : array();
}


function ragnaContentEntriesRead($entries, $url)
{
	if (!is_array($entries)) return array('items' => array());
	$items = array();
	foreach (array_slice($entries, 0, RAGNA_CHANGELOG_MAX_ITEMS) as $item) {
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
			'url' => $url
		);
	}
	return array('items' => $items);
}

function ragnaNewsRead()
{
	$data = ragnaContentReadData();
	$entries = isset($data['news']) && is_array($data['news']) ? $data['news'] : (!isset($data['changelog']) && isset($data['items']) ? $data['items'] : array());
	return ragnaContentEntriesRead($entries, RAGNA_NEWS_PAGE_URL);
}

function ragnaChangelogRead()
{
	$data = ragnaContentReadData();
	return ragnaContentEntriesRead($data['changelog'] ?? array(), RAGNA_CHANGELOG_PAGE_URL);
}

function ragnaEventsRead()
{
	$data = ragnaContentReadData();

	$items = array();
	foreach (array_slice($data['events'], 0, RAGNA_EVENT_MAX_ITEMS) as $item) {
		if (!is_array($item)) continue;
		$title = trim((string)($item['title'] ?? ''));
		$startDate = trim((string)($item['startDate'] ?? ''));
		$endDate = trim((string)($item['endDate'] ?? ''));
		if ($title === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) continue;
		if ($endDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) $endDate = '';
		$items[] = array(
			'id' => preg_match('/^[A-Za-z0-9_-]{1,64}$/', (string)($item['id'] ?? '')) ? (string)$item['id'] : bin2hex(random_bytes(8)),
			'title' => substr($title, 0, 140),
			'startDate' => $startDate,
			'endDate' => $endDate,
			'summary' => substr(trim((string)($item['summary'] ?? '')), 0, 320),
			'description' => substr(trim((string)($item['description'] ?? '')), 0, 1200),
			'image' => ragnaChangelogCleanHttpsUrl($item['image'] ?? '', 500) ?: '',
			'url' => ragnaChangelogCleanHttpsUrl($item['url'] ?? '', 500) ?: ''
		);
	}
	return array('items' => $items);
}

function ragnaContentWrite(array $news, array $changelog, array $events)
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
		$payload = json_encode(array('news' => $news, 'changelog' => $changelog, 'events' => $events), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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

function ragnaChangelogCleanHttpsUrl($value, $maxLength)
{
	$url = ragnaChangelogCleanText($value, $maxLength);
	if ($url === '') return '';
	if ($url === null || preg_match('/[\r\n]/', $url) || !filter_var($url, FILTER_VALIDATE_URL)) return null;
	$parts = parse_url($url);
	if (!is_array($parts) || strtolower((string)($parts['scheme'] ?? '')) !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) return null;
	return $url;
}

function ragnaContentNormalizePostedEntries($posted, &$error, $contentType)
{
	$error = '';
	if (!is_array($posted) || count($posted) > RAGNA_CHANGELOG_MAX_ITEMS) {
		$error = ucfirst($contentType) . ' must contain no more than 50 entries.';
		return null;
	}
	$items = array();
	foreach ($posted as $raw) {
		if (!is_array($raw)) {
			$error = 'Invalid ' . $contentType . ' entry.';
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
			$error = 'Every ' . $contentType . ' entry needs a title.';
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
			'url' => $contentType === 'news' ? RAGNA_NEWS_PAGE_URL : RAGNA_CHANGELOG_PAGE_URL
		);
	}
	return $items;
}

function ragnaChangelogNormalizePostedItems($posted, &$error)
{
	return ragnaContentNormalizePostedEntries($posted, $error, 'changelog');
}

function ragnaChangelogNormalizePostedEvents($posted, &$error)
{
	$error = '';
	if (!is_array($posted) || count($posted) > RAGNA_EVENT_MAX_ITEMS) {
		$error = 'Events must contain no more than 50 entries.';
		return null;
	}
	$items = array();
	foreach ($posted as $raw) {
		if (!is_array($raw)) {
			$error = 'Invalid event entry.';
			return null;
		}
		if ((string)($raw['deleted'] ?? '') === '1') continue;
		$title = ragnaChangelogCleanText($raw['title'] ?? '', 140);
		$summary = ragnaChangelogCleanText($raw['summary'] ?? '', 320);
		$description = ragnaChangelogCleanText($raw['description'] ?? '', 1200);
		$image = ragnaChangelogCleanHttpsUrl($raw['image'] ?? '', 500);
		$url = ragnaChangelogCleanHttpsUrl($raw['url'] ?? '', 500);
		$startDate = is_string($raw['startDate'] ?? null) ? trim($raw['startDate']) : '';
		$endDate = is_string($raw['endDate'] ?? null) ? trim($raw['endDate']) : '';
		if ($title === null || $summary === null || $description === null || $image === null || $url === null) {
			$error = 'An event field is too long or contains an invalid URL or text.';
			return null;
		}
		if ($title === '' && $startDate === '' && $endDate === '' && $summary === '' && $description === '' && $image === '' && $url === '') continue;
		$dateParts = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $startDate, $matches) ? $matches : null;
		if ($title === '' || !$dateParts || !checkdate((int)$dateParts[2], (int)$dateParts[3], (int)$dateParts[1])) {
			$error = 'Every event needs a title and valid start date.';
			return null;
		}
		if ($endDate !== '') {
			$endParts = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $endDate, $matches) ? $matches : null;
			if (!$endParts || !checkdate((int)$endParts[2], (int)$endParts[3], (int)$endParts[1]) || $endDate < $startDate) {
				$error = 'Use a valid end date on or after the start date.';
				return null;
			}
		}
		$id = is_string($raw['id'] ?? null) ? trim($raw['id']) : '';
		if ($id !== '' && !preg_match('/^[A-Za-z0-9_-]{1,64}$/', $id)) {
			$error = 'Invalid event identifier.';
			return null;
		}
		$items[] = array(
			'id' => $id !== '' ? $id : bin2hex(random_bytes(8)),
			'title' => $title,
			'startDate' => $startDate,
			'endDate' => $endDate,
			'summary' => $summary,
			'description' => $description,
			'image' => $image,
			'url' => $url
		);
	}
	return $items;
}
