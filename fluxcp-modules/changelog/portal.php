<?php
if (!defined('FLUX_ROOT')) exit;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

$loggedIn = $session->isLoggedIn();
$canEdit = $loggedIn && isset($session->account->group_level) && $session->account->group_level >= AccountLevel::LOWGM;
$response = array(
	'loggedIn' => (bool)$loggedIn,
	'canEdit' => (bool)$canEdit,
	'accountName' => $loggedIn ? (string)$session->account->userid : ''
);

if ($canEdit) {
	require_once FLUX_ROOT . '/modules/changelog/common.php';
	$csrfToken = is_string($session->changelogCsrfToken) ? $session->changelogCsrfToken : '';
	if (!preg_match('/^[a-f0-9]{64}$/', $csrfToken)) {
		$csrfToken = bin2hex(random_bytes(32));
		$session->setChangelogCsrfTokenData($csrfToken);
	}
	$response['csrfToken'] = $csrfToken;
	$response['items'] = ragnaChangelogRead()['items'];
	$response['events'] = ragnaEventsRead()['items'];
}

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
