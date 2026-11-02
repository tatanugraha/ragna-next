<?php
if (!defined('FLUX_ROOT')) exit;
require_once FLUX_ROOT . '/modules/changelog/common.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', array('GET', 'HEAD'), true)) {
	header('Allow: GET, HEAD');
	http_response_code(405);
	echo '{"items":[]}';
	exit;
}
echo json_encode(ragnaChangelogRead(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
