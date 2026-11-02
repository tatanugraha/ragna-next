<?php
if (!defined('FLUX_ROOT')) exit;
if (!$session->isLoggedIn()) $this->loginRequired();
if (!isset($session->account->group_level) || $session->account->group_level < AccountLevel::LOWGM) $this->deny();

require_once FLUX_ROOT . '/modules/changelog/common.php';
$title = 'Manage Changelog';
$items = ragnaChangelogRead()['items'];
$csrfToken = (string)$session->changelogCsrfToken;
if (!preg_match('/^[a-f0-9]{64}$/', $csrfToken)) {
	$csrfToken = bin2hex(random_bytes(32));
	$session->setChangelogCsrfTokenData($csrfToken);
}
