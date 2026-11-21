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
 * \file    krea2factorauth/user/password_change.php
 * \ingroup krea2factorauth
 * \brief   Mandatory password-change page for expired passwords.
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../lib/krea2factorauth.lib.php';
dol_include_once('/krea2factorauth/class/krea2factorauthmanager.class.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array("main", "users", "errors", "krea2factorauth@krea2factorauth"));

if (empty($user->id)) {
	accessforbidden();
}

$manager = new Krea2FactorAuthManager($db);
$currententity = !empty($conf->entity) ? (int) $conf->entity : 1;
if (!$manager->isModuleActiveForEntity($currententity) || !$manager->isPasswordExpirationEnabledForUser((int) $user->id)) {
	header('Location: '.DOL_URL_ROOT.'/index.php');
	exit;
}

$targetuser = new User($db);
if ($targetuser->fetch((int) $user->id, '', '', 0) <= 0) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$error = 0;

/*
 * Actions
 */

if ($action == 'change_expired_password') {
	krea2factorauthRequirePostToken($action);

	$currentpassword = GETPOST('current_password', 'password');
	$newpassword = GETPOST('new_password', 'password');
	$confirmpassword = GETPOST('confirm_password', 'password');

	if ($currentpassword === '' || $newpassword === '' || $confirmpassword === '') {
		$error++;
		setEventMessages($langs->trans("Krea2FactorAuthPasswordChangeAllFieldsRequired"), null, 'errors');
	} elseif ($newpassword !== $confirmpassword) {
		$error++;
		setEventMessages($langs->trans("ErrorPasswordsMustMatch"), null, 'errors');
	} elseif ($manager->changeExpiredPasswordForUser($targetuser, $user, $currentpassword, $newpassword) < 0) {
		$error++;
		if ($manager->error === 'Current password is not valid.') {
			setEventMessages($langs->trans("ErrorBadLoginPassword"), null, 'errors');
		} elseif ($manager->error === 'This password was already used recently. Choose a different password.') {
			setEventMessages($langs->trans("Krea2FactorAuthPasswordReuseDenied"), null, 'errors');
		} else {
			setEventMessages($manager->error, $manager->errors, 'errors');
		}
	} else {
		setEventMessages($langs->trans("Krea2FactorAuthPasswordChanged"), null, 'mesgs');
		header('Location: '.DOL_URL_ROOT.'/index.php');
		exit;
	}
}

$passwordexpired = $manager->isPasswordExpiredForUser((int) $user->id);
if (!$passwordexpired && !$error) {
	header('Location: '.DOL_URL_ROOT.'/index.php');
	exit;
}

/*
 * View
 */

$help_url = '';
$title = "Krea2FactorAuthPasswordChangePageTitle";
$cssversion = (string) @filemtime(dirname(__DIR__).'/css/krea2factorauth.css');
$morecss = array(dol_buildpath('/krea2factorauth/css/krea2factorauth.css', 1).($cssversion !== '' ? '?v='.$cssversion : ''));

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, array(), $morecss, '', 'mod-krea2factorauth page-password-change');

print load_fiche_titre($langs->trans($title), '', 'title_setup');

$expiresat = $manager->getPasswordExpiresAtForUser((int) $user->id);
print '<div class="krea2factorauth-password-change-box">';
print '<div class="info">'.dol_escape_htmltag($langs->trans("Krea2FactorAuthPasswordExpiredRequired")).'</div>';
if ($expiresat > 0) {
	print '<div class="opacitymedium">'.dol_escape_htmltag($langs->trans("Krea2FactorAuthPasswordExpiredSince", dol_print_date($expiresat, 'dayhour'))).'</div>';
}
print '<form method="post" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="change_expired_password">';
print '<table class="noborder centpercent">';
print '<tr class="oddeven"><td class="titlefield fieldrequired">'.$langs->trans("Password").'</td><td><input type="password" class="flat minwidth300" name="current_password" autocomplete="current-password"></td></tr>';
print '<tr class="oddeven"><td class="fieldrequired">'.$langs->trans("Krea2FactorAuthNewPassword").'</td><td><input type="password" class="flat minwidth300" name="new_password" autocomplete="new-password"></td></tr>';
print '<tr class="oddeven"><td class="fieldrequired">'.$langs->trans("Krea2FactorAuthConfirmPassword").'</td><td><input type="password" class="flat minwidth300" name="confirm_password" autocomplete="new-password"></td></tr>';
print '</table>';
print '<div class="tabsAction">';
print '<input type="submit" class="button krea2factorauth-settings-button" value="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthChangePassword")).'">';
print '</div>';
print '</form>';
print '</div>';

llxFooter();
$db->close();
