<?php
if (!defined('FLUX_ROOT')) exit;
if (!$session->isLoggedIn()) $this->loginRequired();
if (!isset($session->account->group_level) || $session->account->group_level < AccountLevel::LOWGM) $this->deny();

require_once FLUX_ROOT . '/modules/changelog/common.php';

$csrfToken = is_string($session->changelogCsrfToken) ? $session->changelogCsrfToken : '';
if (!preg_match('/^[a-f0-9]{64}$/', $csrfToken)) {
	$csrfToken = bin2hex(random_bytes(32));
	$session->setChangelogCsrfTokenData($csrfToken);
}

$news = ragnaNewsRead()['items'];
$changelog = ragnaChangelogRead()['items'];
$events = ragnaEventsRead()['items'];
