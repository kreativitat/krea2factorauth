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
 * \file    krea2factorauth/public/google_app.php
 * \ingroup krea2factorauth
 * \brief   Public Google OAuth application information page.
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

$title = $langs->trans("Krea2FactorAuthPublicGoogleAppTitle");
$privacyurl = dol_buildpath('/krea2factorauth/public/privacy.php', 2);
$termsurl = dol_buildpath('/krea2factorauth/public/terms.php', 2);

llxHeader('', $title);

print load_fiche_titre($title, '', 'fa-key');
print '<div class="fichecenter">';
print '<p>'.$langs->trans("Krea2FactorAuthPublicGoogleAppIntro").'</p>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Krea2FactorAuthPublicGoogleAppSummary").'</td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("Krea2FactorAuthPublicGoogleAppPurpose").'</td><td>'.$langs->trans("Krea2FactorAuthPublicGoogleAppPurposeText").'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthPublicGoogleAppOperator").'</td><td>'.$langs->trans("Krea2FactorAuthPublicGoogleAppOperatorText").'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthPublicGoogleAppScopes").'</td><td><code>openid,email,profile</code></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthPublicGoogleAppDataUse").'</td><td>'.$langs->trans("Krea2FactorAuthPublicGoogleAppDataUseText").'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthPublicGoogleAppPrivacy").'</td><td><a href="'.dol_escape_htmltag($privacyurl).'">'.dol_escape_htmltag($privacyurl).'</a></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthPublicGoogleAppTerms").'</td><td><a href="'.dol_escape_htmltag($termsurl).'">'.dol_escape_htmltag($termsurl).'</a></td></tr>';
print '</table>';
print '</div>';

llxFooter();
if (isset($db) && is_object($db)) {
	$db->close();
}
