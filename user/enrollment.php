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
 * \file    krea2factorauth/user/enrollment.php
 * \ingroup krea2factorauth
 * \brief   Dedicated current-user 2FA enrollment page.
 */

if (!defined('CSRFCHECK_WITH_TOKEN')) {
	define('CSRFCHECK_WITH_TOKEN', '1');
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

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
dol_include_once('/krea2factorauth/class/krea2factorauthmanager.class.php');
dol_include_once('/krea2factorauth/lib/krea2factorauth.lib.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array("users", "krea2factorauth@krea2factorauth"));

if (!isModEnabled('krea2factorauth')) {
	accessforbidden('Module not enabled');
}

if (empty($user->id)) {
	accessforbidden();
}

$manager = new Krea2FactorAuthManager($db);
if (!$manager->isUserVisibleInCurrentEntity((int) $user->id)) {
	accessforbidden('Invalid user scope');
}

$targetuser = new User($db);
if ($targetuser->fetch((int) $user->id, '', '', 0) <= 0) {
	accessforbidden('Invalid user');
}

$action = GETPOST('action', 'aZ09');
$enrollmenturl = krea2factorauthGetEnrollmentPageUrl();
if ($manager->isMandatoryExemptForUser((int) $user->id)) {
	unset($_SESSION[krea2factorauthGetPendingSecretSessionKey((int) $user->id)]);
	setEventMessages($langs->trans("Krea2FactorAuthUserExempted", $user->login), null, 'mesgs');
	header('Location: '.DOL_URL_ROOT.'/index.php');
	exit;
}

krea2factorauthHandleUserCardAction($targetuser, $action, $enrollmenturl);

/*
 * View
 */

$title = "Krea2FactorAuthEnrollmentPageTitle";
$morejs = array(dol_buildpath('/krea2factorauth/js/krea2factorauth.js', 1));
$cssversion = (string) @filemtime(dirname(__DIR__).'/css/krea2factorauth.css');
$morecss = array(dol_buildpath('/krea2factorauth/css/krea2factorauth.css', 1).($cssversion !== '' ? '?v='.$cssversion : ''));

llxHeader('', $langs->trans($title), '', '', 0, 0, $morejs, $morecss, '', 'mod-krea2factorauth page-user-2fa page-krea2factorauth-enrollment');

print '<div class="krea2factorauth-standalone-page">';
print load_fiche_titre($langs->trans($title), '', 'fa-key');

$isenrollmentmandatory = !$manager->isMandatoryExemptForUser((int) $user->id) && ($manager->isMandatoryForUser((int) $user->id) || $manager->isMandatoryEnrollmentPromptEnabled());
if (!$manager->isEnabledForUser((int) $user->id) && $isenrollmentmandatory) {
	print '<div class="warning">'.$langs->trans("Krea2FactorAuthMandatorySetupRequired").'</div><br>';
}

print krea2factorauthRenderUserCardPanel($targetuser, array(
	'actionurl' => $enrollmenturl,
	'standalone' => 1,
));

if ($manager->isEnabledForUser((int) $user->id)) {
	print '<div class="tabsAction">';
	print '<a class="button" href="'.dol_escape_htmltag(DOL_URL_ROOT.'/index.php').'">'.$langs->trans("Krea2FactorAuthContinue").'</a>';
	print '</div>';
}

print '</div>';

llxFooter();
$db->close();
