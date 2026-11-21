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
 * \file    krea2factorauth/user/2fa_setup.php
 * \ingroup krea2factorauth
 * \brief   Compatibility redirect to the dedicated 2FA enrollment page.
 */

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
 * @var User $user
 */

if (!isModEnabled('krea2factorauth')) {
	accessforbidden('Module not enabled');
}

$id = GETPOSTINT('id');
if ($id <= 0) {
	$id = (int) $user->id;
}

if ($id != (int) $user->id && !$user->admin) {
	accessforbidden();
}

$manager = new Krea2FactorAuthManager($db);
if (!$manager->isUserVisibleInCurrentEntity($id)) {
	accessforbidden('Invalid user scope');
}

if ($id == (int) $user->id) {
	header('Location: '.krea2factorauthGetEnrollmentPageUrl());
	exit;
}

header('Location: '.krea2factorauthGetUserCardPanelUrl($id));
exit;
