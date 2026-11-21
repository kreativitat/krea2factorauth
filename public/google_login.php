<?php
/* Copyright (C) 2026       Kreativität Works            <mail@kreativitat.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    krea2factorauth/public/google_login.php
 * \ingroup krea2factorauth
 * \brief   Token-protected bridge that starts Dolibarr's native Google OAuth login flow.
 */

if (!defined('NOLOGIN')) {
	define('NOLOGIN', '1');
}
if (!defined('CSRFCHECK_WITH_TOKEN')) {
	define('CSRFCHECK_WITH_TOKEN', '1');
}
if (!defined('DOLENTITY') && !empty($_POST['entity']) && ctype_digit((string) $_POST['entity']) && (int) $_POST['entity'] > 0) {
	define('DOLENTITY', (int) $_POST['entity']);
}

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

dol_include_once('/krea2factorauth/class/krea2factorauthmanager.class.php');
dol_include_once('/krea2factorauth/lib/krea2factorauth.lib.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 */

$langs->loadLangs(array('main', 'krea2factorauth@krea2factorauth'));

$method = empty($_SERVER['REQUEST_METHOD']) ? '' : strtoupper((string) $_SERVER['REQUEST_METHOD']);
if ($method !== 'POST' || GETPOST('actionlogin', 'aZ09') !== 'login' || GETPOST('beforeoauthloginredirect', 'alpha') !== 'google') {
	header('Location: '.DOL_URL_ROOT.'/');
	exit;
}

$manager = new Krea2FactorAuthManager($db);
$returnurl = krea2factorauthGetPostLoginReturnUrl(GETPOST(Krea2FactorAuthManager::LOGIN_RETURN_FORM_FIELD, 'restricthtml'));
$entity = GETPOSTINT('entity');
if ($entity <= 0) {
	$entity = !empty($conf->entity) ? (int) $conf->entity : 1;
}

if (!$manager->isGoogleTwoFactorAvailable() || !$manager->isModuleActiveForEntity($entity)) {
	$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthGoogleLoginUnavailable");
	dol_syslog("krea2factorauth/public/google_login.php refused unavailable Google login for entity ".$entity, LOG_WARNING);
	header('Location: '.DOL_URL_ROOT.'/');
	exit;
}

$_SESSION['datafromloginform'] = array(
	'entity' => $entity,
	'backtopage' => GETPOST('backtopage'),
	'tz' => GETPOST('tz'),
	'tz_string' => GETPOST('tz_string'),
	'dst_observed' => GETPOST('dst_observed'),
	'dst_first' => GETPOST('dst_first'),
	'dst_second' => GETPOST('dst_second'),
	'dol_screenwidth' => GETPOST('screenwidth'),
	'dol_screenheight' => GETPOST('screenheight'),
	'dol_hide_topmenu' => GETPOST('dol_hide_topmenu'),
	'dol_hide_leftmenu' => GETPOST('dol_hide_leftmenu'),
	'dol_optimize_smallscreen' => GETPOST('dol_optimize_smallscreen'),
	'dol_no_mouse_hover' => GETPOST('dol_no_mouse_hover'),
	'dol_use_jmobile' => GETPOST('dol_use_jmobile'),
	Krea2FactorAuthManager::LOGIN_RETURN_FORM_FIELD => $returnurl,
);

$manager->clearGoogleLoginPendingChallenge();

$shortscope = Krea2FactorAuthManager::GOOGLE_OAUTH_SCOPES;
$oauthstateanticsrf = bin2hex(random_bytes(128 / 8));
$url = $manager->getGoogleOAuthLoginCallbackUrl();
$url .= '?shortscope='.urlencode($shortscope);
$url .= '&state='.urlencode('forlogin-'.$shortscope.'-entity'.$entity.'-'.$oauthstateanticsrf);
$url .= '&backtourl='.urlencode($returnurl !== '' ? $returnurl : DOL_URL_ROOT.'/');
$url .= '&entity='.$entity;

$username = trim(GETPOST('username', 'alphanohtml'));
if ($username !== '') {
	$url .= '&username='.urlencode($username);
}

header('Location: '.$url);
exit;
