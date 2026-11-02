<?php
if (!defined('FLUX_ROOT')) exit;
if (!$session->isLoggedIn()) $this->loginRequired();
if (!isset($session->account->group_level) || $session->account->group_level < AccountLevel::LOWGM) $this->deny();
header('Location: https://ragna-next.cc/#staff-editor', true, 302);
exit;
