<?php
if (!defined('FLUX_ROOT')) exit;
if (!$session->isLoggedIn()) $this->loginRequired();
if (!isset($session->account->group_level) || $session->account->group_level < AccountLevel::LOWGM) $this->deny();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	header('Allow: POST');
	http_response_code(405);
	exit('Method not allowed.');
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 131072) {
	http_response_code(413);
	exit('Changelog submission is too large.');
}

require_once FLUX_ROOT . '/modules/changelog/common.php';
$sessionToken = (string)$session->changelogCsrfToken;
$postedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
if ($sessionToken === '' || !hash_equals($sessionToken, $postedToken)) $this->deny();
$session->setChangelogCsrfTokenData(bin2hex(random_bytes(32)));

$error = '';
$items = ragnaChangelogNormalizePostedItems($_POST['items'] ?? null, $error);
if ($items === null) {
	$session->setMessageData($error);
	header('Location: ' . $this->url('changelog', 'index'));
	exit;
}

try {
	ragnaChangelogWrite($items);
	$session->setMessageData('Changelog updated. The website and patcher now use the new entries.');
} catch (Throwable $exception) {
	$session->setMessageData('Changelog could not be saved. Check the server storage permissions.');
}
header('Location: ' . $this->url('changelog', 'index'));
exit;
