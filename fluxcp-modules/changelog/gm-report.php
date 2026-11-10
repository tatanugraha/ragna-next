<?php
// GM reports for the website dashboard: read-only JSON built from the game and log databases.
// GET /account/?module=changelog&action=gm-report&tab=<report|online|items|zeny|trades|cash|commands>
if (!defined('FLUX_ROOT')) exit;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

$gmFinish = function ($status, array $payload) {
	http_response_code($status);
	echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
	exit;
};

if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', array('GET', 'HEAD'), true)) {
	header('Allow: GET, HEAD');
	$gmFinish(405, array('ok' => false, 'message' => 'Method not allowed.'));
}
if (!$session->isLoggedIn()) $gmFinish(401, array('ok' => false, 'message' => 'Sign in with a Game Master account.'));
if (!isset($session->account->group_level) || $session->account->group_level < AccountLevel::LOWGM) {
	$gmFinish(403, array('ok' => false, 'message' => 'GM reports are available to Game Masters only.'));
}

if (!defined('RAGNA_GM_ROW_LIMIT')) define('RAGNA_GM_ROW_LIMIT', 500);

$gmPickTypes = array(
	'T' => 'Trade', 'V' => 'Vending', 'P' => 'Player drop/pick', 'M' => 'Monster drop', 'L' => 'Loot',
	'S' => 'NPC shop', 'N' => 'NPC script', 'D' => 'Steal', 'C' => 'Consumed', 'O' => 'Produced',
	'U' => 'MVP reward', 'A' => 'GM command', 'R' => 'Storage', 'G' => 'Guild storage', 'E' => 'Mail',
	'I' => 'Auction', 'B' => 'Buying store', 'K' => 'Bank', 'X' => 'Other', '$' => 'Cash shop',
	'F' => 'Bound removal', 'Y' => 'Roulette', 'Z' => 'Merge', 'Q' => 'Quest', 'H' => 'Airship',
	'J' => 'Barter', 'W' => 'Laphine', '0' => 'Enchant grade', '1' => 'Reform', '2' => 'Enchant', '3' => 'Package'
);
$gmTypeGroups = array('p2p' => array('T', 'V', 'B'), 'npc' => array('S', 'N', 'J'));

$main = $server->connection;
$loginDb = $server->loginDatabase;
$charDb = $server->charMapDatabase;
$logsDb = $server->logsDatabase;

$gmRows = function ($statement) {
	$rows = array();
	// FluxCP fetches Flux_DataObject by default; plain arrays are simpler to read here.
	while ($row = $statement->fetch(PDO::FETCH_ASSOC)) $rows[] = $row;
	return $rows;
};
$gmQuery = function ($sql, array $params = array(), $logs = false) use ($main, $gmRows) {
	$statement = $logs ? $main->getStatementForLogs($sql) : $main->getStatement($sql);
	if (!$statement->execute($params)) throw new RuntimeException('Query failed.');
	return $gmRows($statement);
};
$gmValue = function ($sql, array $params = array(), $logs = false) use ($gmQuery) {
	$rows = $gmQuery($sql, $params, $logs);
	return $rows ? (int)reset($rows[0]) : 0;
};
$gmPeriodDays = function () {
	$days = (int)($_GET['period'] ?? 7);
	return in_array($days, array(1, 3, 7, 30, 90), true) ? $days : 7;
};
$gmText = function ($key, $max) {
	$value = is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : '';
	return substr($value, 0, $max);
};
// Resolve a character by exact name or char_id.
$gmFindChar = function ($input) use ($gmQuery, $charDb, $loginDb) {
	if ($input === '') return null;
	$sql = "SELECT c.char_id, c.account_id, c.name, l.userid FROM {$charDb}.`char` c LEFT JOIN {$loginDb}.login l ON l.account_id = c.account_id WHERE ";
	$rows = ctype_digit($input) ? $gmQuery($sql . 'c.char_id = ?', array((int)$input)) : $gmQuery($sql . 'c.name = ?', array($input));
	return $rows ? $rows[0] : null;
};
// char_id => name for the ids seen in a log result.
$gmCharNames = function (array $ids) use ($gmQuery, $charDb) {
	$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
	if (!$ids) return array();
	$names = array();
	foreach (array_chunk($ids, 400) as $chunk) {
		$marks = implode(',', array_fill(0, count($chunk), '?'));
		foreach ($gmQuery("SELECT char_id, name FROM {$charDb}.`char` WHERE char_id IN ($marks)", $chunk) as $row) {
			$names[(int)$row['char_id']] = (string)$row['name'];
		}
	}
	return $names;
};
$gmItemNames = function (array $ids) use ($gmQuery, $charDb) {
	$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
	if (!$ids) return array();
	foreach (array('item_db_re', 'item_db') as $table) {
		try {
			$names = array();
			foreach (array_chunk($ids, 400) as $chunk) {
				$marks = implode(',', array_fill(0, count($chunk), '?'));
				foreach ($gmQuery("SELECT id, name_english FROM {$charDb}.`$table` WHERE id IN ($marks)", $chunk) as $row) {
					$names[(int)$row['id']] = (string)$row['name_english'];
				}
			}
			return $names;
		} catch (Throwable $e) {
			// The item table is optional (YAML item databases are not in SQL).
		}
	}
	return array();
};

$tab = $gmText('tab', 16);
try {
	switch ($tab) {
	case 'report':
		$since = date('Y-m-d', strtotime('-13 days'));
		$days = array();
		for ($i = 0; $i < 14; $i++) {
			$day = date('Y-m-d', strtotime("-$i days"));
			$days[$day] = array('day' => $day, 'accounts' => 0, 'ips' => 0);
		}
		// loginlog is written to the log database (log_db_db), not the login database.
		try {
			foreach ($gmQuery("SELECT DATE(`time`) d, COUNT(DISTINCT `user`) a, COUNT(DISTINCT ip) i FROM {$logsDb}.loginlog WHERE rcode = 100 AND `time` >= ? GROUP BY DATE(`time`)", array($since), true) as $row) {
				if (isset($days[$row['d']])) { $days[$row['d']]['accounts'] = (int)$row['a']; $days[$row['d']]['ips'] = (int)$row['i']; }
			}
		} catch (Throwable $e) {
			// Without the log table the stats below still load; the daily log-ins stay at 0.
		}
		$gmFinish(200, array('ok' => true, 'tab' => 'report', 'stats' => array(
			'online' => $gmValue("SELECT COUNT(*) FROM {$charDb}.`char` WHERE online = 1"),
			'onlineAccounts' => $gmValue("SELECT COUNT(DISTINCT account_id) FROM {$charDb}.`char` WHERE online = 1"),
			'accounts' => $gmValue("SELECT COUNT(*) FROM {$loginDb}.login WHERE sex <> 'S'"),
			'vip' => $gmValue("SELECT COUNT(*) FROM {$loginDb}.login WHERE sex <> 'S' AND vip_time > UNIX_TIMESTAMP()"),
			'activeToday' => $gmValue("SELECT COUNT(*) FROM {$loginDb}.login WHERE sex <> 'S' AND lastlogin >= CURDATE()"),
			'active7' => $gmValue("SELECT COUNT(*) FROM {$loginDb}.login WHERE sex <> 'S' AND lastlogin >= CURDATE() - INTERVAL 6 DAY"),
			'characters' => $gmValue("SELECT COUNT(*) FROM {$charDb}.`char`"),
			'maxLevel' => $gmValue("SELECT COUNT(*) FROM {$charDb}.`char` WHERE base_level >= 99"),
			'guilds' => $gmValue("SELECT COUNT(*) FROM {$charDb}.guild"),
			'zeny' => $gmValue("SELECT COALESCE(SUM(zeny), 0) FROM {$charDb}.`char`")
		), 'days' => array_values($days)));

	case 'online':
		$rows = $gmQuery("SELECT c.char_id, c.account_id, c.name, c.class, c.base_level, c.job_level, c.last_map, c.last_x, c.last_y,
				l.userid, l.last_ip, g.name guild,
				v.autotrade vend_auto, v.title vend_title, b.autotrade buy_auto, b.title buy_title
			FROM {$charDb}.`char` c
			JOIN {$loginDb}.login l ON l.account_id = c.account_id
			LEFT JOIN {$charDb}.guild g ON g.guild_id = c.guild_id
			LEFT JOIN {$charDb}.vendings v ON v.char_id = c.char_id
			LEFT JOIN {$charDb}.buyingstores b ON b.char_id = c.char_id
			WHERE c.online = 1 ORDER BY c.last_map, c.name LIMIT " . RAGNA_GM_ROW_LIMIT);
		$ipCount = array();
		foreach ($rows as $row) $ipCount[$row['last_ip']] = ($ipCount[$row['last_ip']] ?? 0) + 1;
		$out = array();
		$summary = array('online' => 0, 'playing' => 0, 'vending' => 0, 'buying' => 0, 'autotrade' => 0, 'sameIp' => 0);
		$maps = array();
		foreach ($rows as $row) {
			$status = 'playing';
			if ($row['vend_title'] !== null) $status = (int)$row['vend_auto'] ? 'autotrade vending' : 'vending';
			elseif ($row['buy_title'] !== null) $status = (int)$row['buy_auto'] ? 'autotrade buying' : 'buying';
			$summary['online']++;
			if ($status === 'playing') $summary['playing']++;
			if (strpos($status, 'vending') !== false) $summary['vending']++;
			if (strpos($status, 'buying') !== false) $summary['buying']++;
			if (strpos($status, 'autotrade') === 0) $summary['autotrade']++;
			$shared = $ipCount[$row['last_ip']] > 1;
			if ($shared) $summary['sameIp']++;
			$maps[$row['last_map']] = ($maps[$row['last_map']] ?? 0) + 1;
			$out[] = array(
				'charId' => (int)$row['char_id'], 'name' => $row['name'], 'accountId' => (int)$row['account_id'], 'account' => $row['userid'],
				'job' => (int)$row['class'], 'baseLevel' => (int)$row['base_level'], 'jobLevel' => (int)$row['job_level'],
				'guild' => (string)$row['guild'], 'map' => $row['last_map'], 'x' => (int)$row['last_x'], 'y' => (int)$row['last_y'],
				'status' => $status, 'shop' => (string)($row['vend_title'] ?? $row['buy_title'] ?? ''), 'ip' => $row['last_ip'], 'sameIp' => $shared
			);
		}
		arsort($maps);
		$gmFinish(200, array('ok' => true, 'tab' => 'online', 'summary' => $summary, 'maps' => $maps, 'rows' => $out));

	case 'items':
	case 'zeny':
		$isItems = $tab === 'items';
		$where = array('`time` >= NOW() - INTERVAL ' . $gmPeriodDays() . ' DAY');
		$params = array();
		$charInput = $gmText('char', 30);
		$char = $gmFindChar($charInput);
		if ($charInput !== '' && !$char) $gmFinish(200, array('ok' => true, 'tab' => $tab, 'rows' => array(), 'message' => 'Character not found.'));
		if ($char) { $where[] = 'char_id = ?'; $params[] = (int)$char['char_id']; }
		$type = $gmText('type', 8);
		if (isset($gmTypeGroups[$type])) {
			$where[] = '`type` IN (' . implode(',', array_fill(0, count($gmTypeGroups[$type]), '?')) . ')';
			$params = array_merge($params, $gmTypeGroups[$type]);
		} elseif ($type !== '' && isset($gmPickTypes[$type])) {
			$where[] = '`type` = ?'; $params[] = $type;
		}
		if ($isItems) {
			$itemId = $gmText('item', 10);
			if (ctype_digit($itemId)) { $where[] = 'nameid = ?'; $params[] = (int)$itemId; }
			$uid = $gmText('uid', 20);
			if (ctype_digit($uid)) { $where[] = 'unique_id = ?'; $params[] = $uid; }
			$sql = "SELECT id, `time`, char_id, `type`, nameid, amount, refine, card0, card1, card2, card3, unique_id, map FROM {$logsDb}.picklog";
		} else {
			$sql = "SELECT id, `time`, char_id, src_id, `type`, amount, map FROM {$logsDb}.zenylog";
		}
		$rows = $gmQuery($sql . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT ' . RAGNA_GM_ROW_LIMIT, $params, true);

		// Other side of player-to-player moves: same second, same map, opposite sign.
		$partners = array();
		$p2p = array_filter($rows, function ($row) { return in_array($row['type'], array('T', 'V', 'B'), true); });
		if ($isItems && $p2p) {
			$keys = array();
			foreach ($p2p as $row) $keys[$row['time'] . '|' . $row['map']] = array($row['time'], $row['map']);
			foreach (array_chunk(array_values($keys), 100) as $chunk) {
				$cond = implode(' OR ', array_fill(0, count($chunk), '(`time` = ? AND map = ?)'));
				$flat = array();
				foreach ($chunk as $pair) { $flat[] = $pair[0]; $flat[] = $pair[1]; }
				foreach ($gmQuery("SELECT `time`, map, char_id, nameid, amount FROM {$logsDb}.picklog WHERE `type` IN ('T','V','B') AND ($cond)", $flat, true) as $other) {
					$partners[$other['time'] . '|' . $other['map']][] = $other;
				}
			}
		}
		$charIds = array_column($rows, 'char_id');
		if (!$isItems) $charIds = array_merge($charIds, array_column($rows, 'src_id'));
		foreach ($partners as $list) $charIds = array_merge($charIds, array_column($list, 'char_id'));
		$names = $gmCharNames($charIds);
		$itemNames = $isItems ? $gmItemNames(array_merge(array_column($rows, 'nameid'), array_column($rows, 'card0'), array_column($rows, 'card1'), array_column($rows, 'card2'), array_column($rows, 'card3'))) : array();

		$out = array();
		foreach ($rows as $row) {
			$entry = array(
				'id' => (int)$row['id'], 'time' => $row['time'], 'charId' => (int)$row['char_id'], 'char' => $names[(int)$row['char_id']] ?? '',
				'type' => $row['type'], 'typeLabel' => $gmPickTypes[$row['type']] ?? $row['type'], 'amount' => (int)$row['amount'], 'map' => $row['map']
			);
			if ($isItems) {
				$entry['itemId'] = (int)$row['nameid'];
				$entry['item'] = $itemNames[(int)$row['nameid']] ?? '';
				$entry['refine'] = (int)$row['refine'];
				$entry['cards'] = array_values(array_filter(array((int)$row['card0'], (int)$row['card1'], (int)$row['card2'], (int)$row['card3'])));
				$entry['uniqueId'] = (string)$row['unique_id'];
				$entry['partner'] = '';
				foreach ($partners[$row['time'] . '|' . $row['map']] ?? array() as $other) {
					if ((int)$other['char_id'] !== (int)$row['char_id'] && ((int)$other['amount'] < 0) !== ((int)$row['amount'] < 0)) {
						$entry['partner'] = $names[(int)$other['char_id']] ?? ('#' . $other['char_id']);
						break;
					}
				}
			} else {
				$entry['srcId'] = (int)$row['src_id'];
				$entry['partner'] = in_array($row['type'], array('T', 'V', 'B'), true) ? ($names[(int)$row['src_id']] ?? '') : '';
			}
			$out[] = $entry;
		}
		$gmFinish(200, array('ok' => true, 'tab' => $tab, 'char' => $char ? $char['name'] : '', 'limit' => RAGNA_GM_ROW_LIMIT, 'rows' => $out));

	case 'trades':
		$charInput = $gmText('char', 30);
		$char = $gmFindChar($charInput);
		if ($charInput !== '' && !$char) $gmFinish(200, array('ok' => true, 'tab' => 'trades', 'rows' => array(), 'message' => 'Character not found.'));
		$where = array("`type` = 'T'", '`time` >= NOW() - INTERVAL ' . $gmPeriodDays() . ' DAY');
		$params = array();
		if ($char) {
			$where[] = "(`time`, map) IN (SELECT `time`, map FROM {$logsDb}.picklog WHERE `type` = 'T' AND char_id = ?)";
			$params[] = (int)$char['char_id'];
		}
		$rows = $gmQuery("SELECT `time`, map, char_id, nameid, amount, refine FROM {$logsDb}.picklog WHERE " . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT 2000', $params, true);
		// Zeny that changed hands in the same trade (zeny log, when enabled).
		$zeny = array();
		if ($rows) {
			$zeny = $gmQuery("SELECT `time`, map, char_id, src_id, amount FROM {$logsDb}.zenylog WHERE `type` = 'T' AND `time` >= NOW() - INTERVAL " . $gmPeriodDays() . ' DAY', array(), true);
		}
		$trades = array();
		foreach ($rows as $row) {
			$key = $row['time'] . '|' . $row['map'];
			if (!isset($trades[$key])) $trades[$key] = array('time' => $row['time'], 'map' => $row['map'], 'sides' => array(), 'exact' => false);
			$cid = (int)$row['char_id'];
			if (!isset($trades[$key]['sides'][$cid])) $trades[$key]['sides'][$cid] = array('charId' => $cid, 'gave' => array(), 'zeny' => 0);
			// Negative amount = item left this character.
			if ((int)$row['amount'] < 0) $trades[$key]['sides'][$cid]['gave'][] = array('itemId' => (int)$row['nameid'], 'amount' => -(int)$row['amount'], 'refine' => (int)$row['refine']);
		}
		foreach ($zeny as $z) {
			$key = $z['time'] . '|' . $z['map'];
			if (!isset($trades[$key])) continue;
			$cid = (int)$z['char_id'];
			if (!isset($trades[$key]['sides'][$cid])) $trades[$key]['sides'][$cid] = array('charId' => $cid, 'gave' => array(), 'zeny' => 0);
			if ((int)$z['amount'] < 0) $trades[$key]['sides'][$cid]['zeny'] += -(int)$z['amount'];
			$trades[$key]['exact'] = true;
		}
		$ids = array(); $items = array();
		foreach ($trades as $t) foreach ($t['sides'] as $s) { $ids[] = $s['charId']; foreach ($s['gave'] as $g) $items[] = $g['itemId']; }
		$names = $gmCharNames($ids);
		$itemNames = $gmItemNames($items);
		$out = array();
		foreach (array_slice(array_values($trades), 0, RAGNA_GM_ROW_LIMIT) as $t) {
			$sides = array();
			foreach ($t['sides'] as $s) {
				$s['char'] = $names[$s['charId']] ?? ('#' . $s['charId']);
				foreach ($s['gave'] as &$g) $g['item'] = $itemNames[$g['itemId']] ?? '';
				unset($g);
				$sides[] = $s;
			}
			$out[] = array('time' => $t['time'], 'map' => $t['map'], 'exact' => $t['exact'] && count($sides) === 2, 'sides' => $sides);
		}
		$gmFinish(200, array('ok' => true, 'tab' => 'trades', 'char' => $char ? $char['name'] : '', 'rows' => $out));

	case 'cash':
		$charInput = $gmText('char', 30);
		if ($charInput === '') $gmFinish(200, array('ok' => true, 'tab' => 'cash', 'rows' => array(), 'message' => 'Enter a character name to see the account Cash Points.'));
		$char = $gmFindChar($charInput);
		if (!$char) $gmFinish(200, array('ok' => true, 'tab' => 'cash', 'rows' => array(), 'message' => 'Character not found.'));
		$accountChars = $gmQuery("SELECT char_id, name FROM {$charDb}.`char` WHERE account_id = ?", array((int)$char['account_id']));
		$ids = array_map('intval', array_column($accountChars, 'char_id'));
		$names = array_combine($ids, array_column($accountChars, 'name'));
		$rows = array();
		if ($ids) {
			$marks = implode(',', array_fill(0, count($ids), '?'));
			$rows = $gmQuery("SELECT id, `time`, char_id, `type`, cash_type, amount, map FROM {$logsDb}.cashlog
				WHERE char_id IN ($marks) AND `time` >= NOW() - INTERVAL " . $gmPeriodDays() . " DAY ORDER BY id DESC LIMIT " . RAGNA_GM_ROW_LIMIT, $ids, true);
		}
		$balance = array('cash' => 0, 'kafra' => 0);
		foreach ($gmQuery("SELECT `key`, `value` FROM {$charDb}.acc_reg_num WHERE account_id = ? AND `key` IN ('#CASHPOINTS', '#KAFRAPOINTS')", array((int)$char['account_id'])) as $reg) {
			$balance[$reg['key'] === '#CASHPOINTS' ? 'cash' : 'kafra'] = (int)$reg['value'];
		}
		$logged = array('cash' => 0, 'kafra' => 0);
		if ($ids) {
			foreach ($gmQuery("SELECT cash_type, COALESCE(SUM(amount), 0) total FROM {$logsDb}.cashlog WHERE char_id IN ($marks) GROUP BY cash_type", $ids, true) as $sum) {
				if ($sum['cash_type'] === 'C') $logged['cash'] = (int)$sum['total'];
				if ($sum['cash_type'] === 'K') $logged['kafra'] = (int)$sum['total'];
			}
		}
		$out = array();
		foreach ($rows as $row) {
			$out[] = array('id' => (int)$row['id'], 'time' => $row['time'], 'char' => $names[(int)$row['char_id']] ?? '', 'type' => $row['type'],
				'typeLabel' => $gmPickTypes[$row['type']] ?? $row['type'], 'cashType' => $row['cash_type'] === 'K' ? 'Kafra' : ($row['cash_type'] === 'C' ? 'Cash' : 'Other'),
				'amount' => (int)$row['amount'], 'map' => $row['map']);
		}
		$gmFinish(200, array('ok' => true, 'tab' => 'cash', 'account' => $char['userid'], 'accountId' => (int)$char['account_id'],
			'balance' => $balance, 'logged' => $logged, 'rows' => $out));

	case 'commands':
		$where = array('atcommand_date >= NOW() - INTERVAL ' . $gmPeriodDays() . ' DAY');
		$params = array();
		$charInput = $gmText('char', 30);
		if ($charInput !== '') { $where[] = 'char_name = ?'; $params[] = $charInput; }
		$command = $gmText('command', 30);
		if ($command !== '') { $where[] = 'command LIKE ?'; $params[] = '%' . addcslashes($command, '%_\\') . '%'; }
		$rows = $gmQuery("SELECT atcommand_id, atcommand_date, account_id, char_name, map, command FROM {$logsDb}.atcommandlog WHERE " . implode(' AND ', $where) . ' ORDER BY atcommand_id DESC LIMIT ' . RAGNA_GM_ROW_LIMIT, $params, true);
		$out = array();
		foreach ($rows as $row) {
			$out[] = array('id' => (int)$row['atcommand_id'], 'time' => $row['atcommand_date'], 'accountId' => (int)$row['account_id'],
				'char' => $row['char_name'], 'map' => $row['map'], 'command' => $row['command']);
		}
		$gmFinish(200, array('ok' => true, 'tab' => 'commands', 'rows' => $out));

	default:
		$gmFinish(400, array('ok' => false, 'message' => 'Unknown report.'));
	}
} catch (Throwable $exception) {
	$gmFinish(500, array('ok' => false, 'message' => 'The report could not be loaded. Check that the log tables exist and FluxCP can read them.'));
}
