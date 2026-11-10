<?php
if (!defined('FLUX_ROOT')) exit;
if (!$session->isLoggedIn()) $this->loginRequired();
if ((int)$session->account->group_level < AccountLevel::LOWGM) $this->deny();
$jsonMode = strpos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;
$finishJson = function ($status, array $payload) {
	http_response_code($status);
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store, max-age=0');
	header('X-Content-Type-Options: nosniff');
	echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	exit;
};
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	header('Allow: POST');
	http_response_code(405);
	if ($jsonMode) $finishJson(405, array('ok' => false, 'message' => 'Method not allowed.'));
	exit('Method not allowed.');
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 131072) {
	http_response_code(413);
	if ($jsonMode) $finishJson(413, array('ok' => false, 'message' => 'Changelog submission is too large.'));
	exit('Changelog submission is too large.');
}

require_once FLUX_ROOT . '/modules/changelog/common.php';
$sessionToken = (string)$session->changelogCsrfToken;
$postedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
if ($sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
	if ($jsonMode) $finishJson(403, array('ok' => false, 'message' => 'The editor session expired. Reload the editor and try again.'));
	$this->deny();
}
$session->setChangelogCsrfTokenData(bin2hex(random_bytes(32)));
$nextCsrfToken = (string)$session->changelogCsrfToken;

$integratedEditor = (string)($_POST['editor_mode'] ?? '') === 'website-cms' || array_key_exists('news', $_POST) || array_key_exists('changelog', $_POST);
$error = '';
if ($integratedEditor) {
	$news = ragnaContentNormalizePostedEntries($_POST['news'] ?? array(), $error, 'news');
	if ($news === null) {
		if ($jsonMode) $finishJson(422, array('ok' => false, 'message' => $error, 'csrfToken' => $nextCsrfToken));
		$session->setMessageData($error);
		header('Location: ' . $this->url('changelog', 'index'));
		exit;
	}
	$changelog = ragnaContentNormalizePostedEntries($_POST['changelog'] ?? array(), $error, 'changelog');
	if ($changelog === null) {
		if ($jsonMode) $finishJson(422, array('ok' => false, 'message' => $error, 'csrfToken' => $nextCsrfToken));
		$session->setMessageData($error);
		header('Location: ' . $this->url('changelog', 'index'));
		exit;
	}
	$events = ragnaChangelogNormalizePostedEvents($_POST['events'] ?? array(), $error);
} else {
	$news = ragnaNewsRead()['items'];
	$changelog = ragnaChangelogNormalizePostedItems($_POST['items'] ?? array(), $error);
	$events = ragnaEventsRead()['items'];
}
if ($events === null) {
	if ($jsonMode) $finishJson(422, array('ok' => false, 'message' => $error, 'csrfToken' => $nextCsrfToken));
	$session->setMessageData($error);
	header('Location: ' . $this->url('changelog', 'index'));
	exit;
}
if ($news === null || $changelog === null) {
	if ($jsonMode) $finishJson(422, array('ok' => false, 'message' => $error, 'csrfToken' => $nextCsrfToken));
	$session->setMessageData($error);
	header('Location: ' . $this->url('changelog', 'index'));
	exit;
}

try {
	ragnaContentWrite($news, $changelog, $events);
	if ($jsonMode) $finishJson(200, array('ok' => true, 'message' => 'News, changelog, and events updated.', 'csrfToken' => $nextCsrfToken, 'news' => $news, 'changelog' => $changelog, 'events' => $events));
	$session->setMessageData('News, changelog, and events updated.');
} catch (Throwable $exception) {
	if ($jsonMode) $finishJson(500, array('ok' => false, 'message' => 'Changelog could not be saved. Check the server storage permissions.', 'csrfToken' => $nextCsrfToken));
	$session->setMessageData('Changelog could not be saved. Check the server storage permissions.');
}
header('Location: ' . $this->url('changelog', 'index'));
exit;
