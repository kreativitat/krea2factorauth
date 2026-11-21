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
 * \file    krea2factorauth/public/privacy.php
 * \ingroup krea2factorauth
 * \brief   Public privacy policy for the Google OAuth login integration.
 */

if (!defined('NOLOGIN')) {
	define('NOLOGIN', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
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
	http_response_code(500);
	exit;
}

/**
 * @var Translate $langs
 */

$langs->loadLangs(array("krea2factorauth@krea2factorauth"));

$title = $langs->trans("Krea2FactorAuthPublicPrivacyTitle");
$appinfourl = dol_buildpath('/krea2factorauth/public/google_app.php', 2);
$termsurl = dol_buildpath('/krea2factorauth/public/terms.php', 2);

llxHeader('', $title);

print load_fiche_titre($title, '', 'fa-lock');
print '<div class="fichecenter">';
print '<p class="opacitymedium">'.$langs->trans("Krea2FactorAuthPublicLastUpdated", '2026-07-09').'</p>';
print '<p>'.$langs->trans("Krea2FactorAuthPublicPrivacyIntro").'</p>';
print '<h2>'.$langs->trans("Krea2FactorAuthPublicPrivacyDataTitle").'</h2>';
print '<ul>';
print '<li>'.$langs->trans("Krea2FactorAuthPublicPrivacyDataIdentity").'</li>';
print '<li>'.$langs->trans("Krea2FactorAuthPublicPrivacyDataAccount").'</li>';
print '<li>'.$langs->trans("Krea2FactorAuthPublicPrivacyDataSession").'</li>';
print '</ul>';
print '<h2>'.$langs->trans("Krea2FactorAuthPublicPrivacyUseTitle").'</h2>';
print '<p>'.$langs->trans("Krea2FactorAuthPublicPrivacyUseText").'</p>';
print '<h2>'.$langs->trans("Krea2FactorAuthPublicPrivacySharingTitle").'</h2>';
print '<p>'.$langs->trans("Krea2FactorAuthPublicPrivacySharingText").'</p>';
print '<h2>'.$langs->trans("Krea2FactorAuthPublicPrivacyStorageTitle").'</h2>';
print '<p>'.$langs->trans("Krea2FactorAuthPublicPrivacyStorageText").'</p>';
print '<h2>'.$langs->trans("Krea2FactorAuthPublicPrivacySecurityTitle").'</h2>';
print '<p>'.$langs->trans("Krea2FactorAuthPublicPrivacySecurityText").'</p>';
print '<h2>'.$langs->trans("Krea2FactorAuthPublicPrivacyContactTitle").'</h2>';
print '<p>'.$langs->trans("Krea2FactorAuthPublicPrivacyContactText").'</p>';
print '<p><a href="'.dol_escape_htmltag($appinfourl).'">'.$langs->trans("Krea2FactorAuthPublicGoogleAppTitle").'</a> | <a href="'.dol_escape_htmltag($termsurl).'">'.$langs->trans("Krea2FactorAuthPublicTermsTitle").'</a></p>';
print '</div>';

llxFooter();
if (isset($db) && is_object($db)) {
	$db->close();
}
