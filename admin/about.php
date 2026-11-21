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
 * \file    krea2factorauth/admin/about.php
 * \ingroup krea2factorauth
 * \brief   About page of module Krea2FactorAuth.
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once '../lib/krea2factorauth.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array("errors", "admin", "krea2factorauth@krea2factorauth"));

if (!$user->admin) {
	accessforbidden();
}

$backtopage = GETPOST('backtopage', 'alpha');

/*
 * View
 */

$help_url = '';
$page_name = "Krea2FactorAuthAbout";

llxHeader('', $langs->trans($page_name), $help_url, '', 0, 0, '', '', '', 'mod-krea2factorauth page-admin_about');

$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

$head = krea2factorauthAdminPrepareHead();
print dol_get_fiche_head($head, 'about', $langs->trans($page_name), 0, 'krea2factorauth@krea2factorauth');

dol_include_once('/krea2factorauth/core/modules/modKrea2FactorAuth.class.php');
$tmpmodule = new modKrea2FactorAuth($db);
print $tmpmodule->getDescLong();

$logoUrl = dol_buildpath('/custom/krea2factorauth/img/logo.png', 1);
$moduleName = $tmpmodule->getName();
$moduleVersion = $tmpmodule->getVersion();
$editorName = $tmpmodule->editor_name;
$editorUrl = $tmpmodule->editor_url;
$supportEmail = 'mail@kreativitat.com';

print '<div class="fichecenter" style="margin-top: 18px; display:flex; gap:24px; align-items:flex-start; flex-wrap:wrap;">';

print '<div style="flex:1 1 260px; max-width:320px; text-align:center;">';
print '<img src="'.dol_escape_htmltag($logoUrl).'" alt="'.dol_escape_htmltag($editorName).'" style="max-width: 260px; height: auto;">';
print '</div>';

print '<div style="flex:2 1 340px; min-width:320px;">';
print '<table class="border centpercent tableforfield">';
print '<tr><td class="titlefield">'.$langs->trans("Krea2FactorAuthAboutModuleInfo").'</td><td>'.dol_escape_htmltag($moduleName).'</td></tr>';
print '<tr><td class="titlefield">'.$langs->trans("Krea2FactorAuthAboutVersion").'</td><td>'.dol_escape_htmltag($moduleVersion).'</td></tr>';
print '<tr><td class="titlefield">'.$langs->trans("Krea2FactorAuthAboutPublisher").'</td><td><a href="'.dol_escape_htmltag($editorUrl).'" target="_blank" rel="noopener noreferrer">'.dol_escape_htmltag($editorName).'</a></td></tr>';
print '<tr><td class="titlefield">'.$langs->trans("Krea2FactorAuthAboutLicense").'</td><td>GPL-3.0-or-later</td></tr>';
print '<tr><td class="titlefield">'.$langs->trans("Krea2FactorAuthAboutSupport").'</td><td><a href="mailto:'.dol_escape_htmltag($supportEmail).'">'.dol_escape_htmltag($supportEmail).'</a></td></tr>';
print '<tr><td class="titlefield">'.$langs->trans("Krea2FactorAuthAboutWebsite").'</td><td><a href="'.dol_escape_htmltag($editorUrl).'" target="_blank" rel="noopener noreferrer">'.dol_escape_htmltag($editorUrl).'</a></td></tr>';
print '</table>';
print '</div>';
print '</div>';

print dol_get_fiche_end();
llxFooter();
$db->close();
