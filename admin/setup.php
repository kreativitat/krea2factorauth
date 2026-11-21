<?php
/* Copyright (C) 2004-2017  Laurent Destailleur          <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France              <frederic.france@free.fr>
 * Copyright (C) 2026       Kreativität Works            <mail@kreativitat.com>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    krea2factorauth/admin/setup.php
 * \ingroup krea2factorauth
 * \brief   Krea2FactorAuth setup page.
 */

$requestmethod = empty($_SERVER['REQUEST_METHOD']) ? '' : strtoupper((string) $_SERVER['REQUEST_METHOD']);
if ($requestmethod === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
	unset($_GET['action'], $_REQUEST['action']);
}
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../lib/krea2factorauth.lib.php';
dol_include_once('/krea2factorauth/class/krea2factorauthmanager.class.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array("admin", "users", "krea2factorauth@krea2factorauth"));

if (!$user->admin) {
	accessforbidden();
}

$hookmanager->initHooks(array('krea2factorauthsetup', 'globalsetup'));

$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$error = 0;

$form = new Form($db);
$manager = new Krea2FactorAuthManager($db);
$manager->ensureGlobalStorageMigrated();
if ($manager->deleteGlobalLoginLogoConstants() < 0) {
	setEventMessages($manager->error, $manager->errors, 'warnings');
}
if ($manager->syncGlobalLoginBootstrap() < 0) {
	setEventMessages($manager->error, $manager->errors, 'warnings');
}
$issuperadmin = $manager->isSuperAdmin($user);
$currententityid = !empty($conf->entity) ? (int) $conf->entity : 1;
$primaryconfigentityid = 1;
$canmanageglobalsettings = ($currententityid === $primaryconfigentityid);

/*
 * Actions
 */

if (in_array($action, array('save_settings', 'save_activation_scope', 'save_entity_activation'), true)) {
	krea2factorauthRequirePostToken($action);
}

if ($action == 'save_settings') {
	$loginbackgrounddefaultcolor = Krea2FactorAuthManager::DEFAULT_LOGIN_BACKGROUND_COLOR;
	$loginbackgroundcolors = $manager->getLoginBackgroundColorMap();
	$currententitybackgroundcolor = $manager->normalizeLoginBackgroundColor(GETPOST('login_background_entity_color', 'alphanohtml'), '');
	$usecurrententitydefaultcolor = GETPOSTINT('login_background_entity_use_default');

	if ($currententityid > 0) {
		if ($currententitybackgroundcolor !== '' && $currententitybackgroundcolor !== $loginbackgrounddefaultcolor) {
			$loginbackgroundcolors[$currententityid] = $currententitybackgroundcolor;
		} elseif ($usecurrententitydefaultcolor) {
			unset($loginbackgroundcolors[$currententityid]);
		} elseif ($currententitybackgroundcolor !== '') {
			$loginbackgroundcolors[$currententityid] = $currententitybackgroundcolor;
		}
	}

	$settings = array(
		Krea2FactorAuthManager::LOGIN_BACKGROUND_COLORS_CONST => array('value' => $manager->encodeLoginBackgroundColorMap($loginbackgroundcolors), 'type' => 'chaine'),
	);
	if ($canmanageglobalsettings) {
		$issuer = trim(GETPOST('issuer', 'alphanohtml'));
		$primaryauthmodes = trim(GETPOST('primary_auth_modes', 'aZ09comma'));
		$codewindow = GETPOSTINT('code_window');
		$enforcenonweb = GETPOSTINT('enforce_non_web');
		$enrollmentpromptmode = GETPOST('enrollment_prompt_mode', 'alpha');
		$allowselfreset = GETPOSTINT('allow_self_reset');
		$passwordexpirationmaxdays = GETPOSTINT('password_expiration_max_days');
		$passwordhistorylimit = GETPOSTINT('password_history_limit');

		if ($issuer === '') {
			$issuer = (defined('DOL_APPLICATION_TITLE') ? DOL_APPLICATION_TITLE : 'Dolibarr');
		}
		if ($primaryauthmodes === '') {
			$primaryauthmodes = Krea2FactorAuthManager::DEFAULT_PRIMARY_AUTH_MODES;
		}
		if ($codewindow < 0 || $codewindow > 3) {
			$codewindow = 1;
		}
		if (!in_array($enrollmentpromptmode, array(Krea2FactorAuthManager::ENROLLMENT_PROMPT_OPTIONAL, Krea2FactorAuthManager::ENROLLMENT_PROMPT_MANDATORY, Krea2FactorAuthManager::ENROLLMENT_PROMPT_OFF), true)) {
			$enrollmentpromptmode = $manager->getEnrollmentPromptMode();
		}
		if ($passwordexpirationmaxdays < Krea2FactorAuthManager::PASSWORD_EXPIRATION_MIN_DAYS) {
			$passwordexpirationmaxdays = Krea2FactorAuthManager::PASSWORD_EXPIRATION_DEFAULT_MAX_DAYS;
		}
		if ($passwordexpirationmaxdays > Krea2FactorAuthManager::PASSWORD_EXPIRATION_MAX_DAYS) {
			$passwordexpirationmaxdays = Krea2FactorAuthManager::PASSWORD_EXPIRATION_MAX_DAYS;
		}
		if ($passwordhistorylimit < Krea2FactorAuthManager::PASSWORD_HISTORY_MIN_LIMIT) {
			$passwordhistorylimit = Krea2FactorAuthManager::PASSWORD_HISTORY_DEFAULT_LIMIT;
		}
		if ($passwordhistorylimit > Krea2FactorAuthManager::PASSWORD_HISTORY_MAX_LIMIT) {
			$passwordhistorylimit = Krea2FactorAuthManager::PASSWORD_HISTORY_MAX_LIMIT;
		}

		$settings = array_merge(array(
			'KREA2FACTORAUTH_ISSUER' => array('value' => $issuer, 'type' => 'chaine'),
			'KREA2FACTORAUTH_PRIMARY_AUTH_MODES' => array('value' => $primaryauthmodes, 'type' => 'chaine'),
			'KREA2FACTORAUTH_CODE_WINDOW' => array('value' => (string) $codewindow, 'type' => 'chaine'),
			'KREA2FACTORAUTH_ENFORCE_NON_WEB' => array('value' => ($enforcenonweb ? '1' : '0'), 'type' => 'yesno'),
			'KREA2FACTORAUTH_ENROLLMENT_PROMPT_MODE' => array('value' => $enrollmentpromptmode, 'type' => 'chaine'),
			'KREA2FACTORAUTH_SHOW_OPTIONAL_MODAL' => array('value' => ($enrollmentpromptmode === Krea2FactorAuthManager::ENROLLMENT_PROMPT_OPTIONAL ? '1' : '0'), 'type' => 'yesno'),
			'KREA2FACTORAUTH_ALLOW_SELF_RESET' => array('value' => ($allowselfreset ? '1' : '0'), 'type' => 'yesno'),
			Krea2FactorAuthManager::PASSWORD_EXPIRATION_MAX_DAYS_CONST => array('value' => (string) $passwordexpirationmaxdays, 'type' => 'chaine'),
			Krea2FactorAuthManager::PASSWORD_HISTORY_LIMIT_CONST => array('value' => (string) $passwordhistorylimit, 'type' => 'chaine'),
			Krea2FactorAuthManager::LOGIN_BACKGROUND_COLOR_CONST => array('value' => $loginbackgrounddefaultcolor, 'type' => 'chaine'),
		), $settings);
	}

	$result = 1;
	foreach ($settings as $constname => $setting) {
		if (dolibarr_set_const($db, $constname, $setting['value'], $setting['type'], 0, '', Krea2FactorAuthManager::GLOBAL_CONFIG_ENTITY) <= 0) {
			$result = -1;
			break;
		}
	}

	if ($result > 0) {
		$constnames = array();
		foreach (array_keys($settings) as $constname) {
			$constnames[] = "'".$db->escape($constname)."'";
		}
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
		$sql .= " WHERE ".$db->decrypt('name')." IN (".implode(',', $constnames).")";
		$sql .= " AND entity <> ".((int) Krea2FactorAuthManager::GLOBAL_CONFIG_ENTITY);
		$result = $db->query($sql) ? 1 : -1;
	}

	if ($result > 0 && (GETPOSTINT('remove_login_logo') || GETPOSTINT('remove_login_company_icon'))) {
		$result = $manager->deleteLoginLogo($currententityid);
	}

	if ($result > 0 && !empty($_FILES['login_logo']['name'])) {
		$result = $manager->storeLoginLogoUpload($_FILES['login_logo'], $currententityid);
	} elseif ($result > 0 && !empty($_FILES['login_company_icon']['name'])) {
		$result = $manager->storeLoginLogoUpload($_FILES['login_company_icon'], $currententityid);
	}

	if ($result <= 0) {
		$error++;
		if (!empty($manager->error) || !empty($manager->errors)) {
			setEventMessages($manager->error, $manager->errors, 'errors');
		} else {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	} else {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
}

if ($action == 'save_activation_scope') {
	if (!$issuperadmin || !$canmanageglobalsettings) {
		accessforbidden("Krea2FactorAuthSuperAdminPolicyOnly");
	}

	$activationscope = GETPOST('activation_scope', 'alpha');
	if (!in_array($activationscope, array('global', 'independent'))) {
		$activationscope = 'independent';
	}

	if ($manager->setModuleActivationScope($activationscope) < 0) {
		$error++;
		setEventMessages($manager->error, $manager->errors, 'errors');
	} else {
		setEventMessages($langs->trans($activationscope === 'global' ? "Krea2FactorAuthActivationScopeGlobalSaved" : "Krea2FactorAuthActivationScopeIndependentSaved"), null, 'mesgs');
	}
}

if ($action == 'save_entity_activation') {
	if (!$issuperadmin) {
		accessforbidden("Krea2FactorAuthSuperAdminPolicyOnly");
	}

	$entityactivationforaction = $manager->fetchModuleActivationByEntity();
	$hasglobalactivationforaction = false;
	foreach ($entityactivationforaction as $entityactivationline) {
		if (!empty($entityactivationline['global'])) {
			$hasglobalactivationforaction = true;
			break;
		}
	}
	if ($hasglobalactivationforaction) {
		accessforbidden("Krea2FactorAuthEntityActivationIndependentOnly");
	}

	$entityactivationenabled = GETPOSTINT('entity_activation_enabled') ? true : false;
	if ($manager->setModuleActivationForEntity($currententityid, $entityactivationenabled) < 0) {
		$error++;
		setEventMessages($manager->error, $manager->errors, 'errors');
	} else {
		setEventMessages($langs->trans($entityactivationenabled ? "Krea2FactorAuthEntityActivationEnabledSaved" : "Krea2FactorAuthEntityActivationDisabledSaved", $currententityid), null, 'mesgs');
	}
}

/*
 * View
 */

$help_url = '';
$title = "Krea2FactorAuthSetup";
$currentauthmode = isset($conf->file->main_authentication) ? $conf->file->main_authentication : '';
$recommendedauthmode = 'krea2factorauth,dolibarr';
$authparts = array_map('trim', explode(',', $currentauthmode));
$isfirstauthmode = (count($authparts) > 0 && $authparts[0] === 'krea2factorauth');
$hasauthmode = in_array('krea2factorauth', $authparts);

$morejs = array(dol_buildpath('/krea2factorauth/js/krea2factorauth.js', 1));
$morecss = array(dol_buildpath('/krea2factorauth/css/krea2factorauth.css', 1));

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, $morejs, $morecss, '', 'mod-krea2factorauth page-admin');

$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

$head = krea2factorauthAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($title), -1, "krea2factorauth@krea2factorauth");

if (!$hasauthmode || !$isfirstauthmode) {
	print '<div class="warning">';
	print $langs->trans("Krea2FactorAuthAuthModeWarning").'<br>';
	print '<code>$dolibarr_main_authentication = \''.dol_escape_htmltag($recommendedauthmode).'\';</code>';
	print '</div><br>';
}

if (!$canmanageglobalsettings) {
	print '<div class="info">';
	print $langs->trans("Krea2FactorAuthPrimaryEntitySetupOnly", $primaryconfigentityid);
	print '</div><br>';
}

$entityactivation = $manager->fetchModuleActivationByEntity();
$hasglobalactivation = false;
$currententityactivationenabled = false;
$currententitylabel = '';
foreach ($entityactivation as $entityactivationline) {
	if (!empty($entityactivationline['global'])) {
		$hasglobalactivation = true;
	}
	if ((int) $entityactivationline['rowid'] === $currententityid) {
		$currententityactivationenabled = !empty($entityactivationline['enabled']);
		$currententitylabel = (string) $entityactivationline['label'];
	}
}

if (count($entityactivation) > 1 && ($canmanageglobalsettings || !$hasglobalactivation)) {
	$nextentityactivationstate = $currententityactivationenabled ? 0 : 1;

	print '<table class="noborder centpercent krea2factorauth-entity-scope">';
	print '<tr class="liste_titre"><td colspan="3">'.krea2factorauthAdminHelpLabel($form, $langs, "Krea2FactorAuthEntityActivationScope", "Krea2FactorAuthEntityActivationHelp").'</td></tr>';

	if ($canmanageglobalsettings) {
		print '<tr class="oddeven">';
		print '<td class="titlefield">'.$langs->trans("Mode").'</td>';
		print '<td colspan="2">';
		print '<div class="krea2factorauth-scope-toolbar">';
		print '<div class="krea2factorauth-scope-current"><span class="krea2factorauth-policy-pill krea2factorauth-policy-pill-'.($hasglobalactivation ? 'enabled' : 'optional').'">'.dol_escape_htmltag($langs->trans($hasglobalactivation ? "Krea2FactorAuthActivationAllEntities" : "Krea2FactorAuthActivationIndependent")).'</span></div>';
		print '<form class="krea2factorauth-scope-form" method="post" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="save_activation_scope">';
		print '<div class="krea2factorauth-admin-toggle" role="group" aria-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthEntityActivationScope")).'">';
		print '<input type="radio" id="activation_scope_global" name="activation_scope" value="global"'.($hasglobalactivation ? ' checked' : '').'>';
		print '<label for="activation_scope_global">'.$langs->trans("Krea2FactorAuthActivationAllEntitiesShort").'</label>';
		print '<input type="radio" id="activation_scope_independent" name="activation_scope" value="independent"'.(!$hasglobalactivation ? ' checked' : '').'>';
		print '<label for="activation_scope_independent">'.$langs->trans("Krea2FactorAuthActivationIndependentShort").'</label>';
		print '</div>';
		print '<button type="submit" class="button krea2factorauth-settings-button">'.$langs->trans("Apply").'</button>';
		print '</form>';
		print '</div>';
		print '</td>';
		print '</tr>';
	}

	if (!$hasglobalactivation) {
		$currentstateclass = $currententityactivationenabled ? 'enabled' : 'disabled';
		$currentstatelabel = $currententityactivationenabled ? $langs->trans("Enabled") : $langs->trans("Disabled");
		print '<tr class="oddeven">';
		print '<td class="titlefield">'.krea2factorauthAdminHelpLabel($form, $langs, "Krea2FactorAuthCurrentEntityActivation").'</td>';
		print '<td><span class="krea2factorauth-current-entity-badge">'.((int) $currententityid).($currententitylabel !== '' ? ' - '.dol_escape_htmltag($currententitylabel) : '').'</span> ';
		print '<span class="krea2factorauth-user-state-pill krea2factorauth-user-state-pill-'.$currentstateclass.'">'.dol_escape_htmltag($currentstatelabel).'</span></td>';
		print '<td class="right">';
		if ($issuperadmin) {
			print '<form class="krea2factorauth-scope-form" method="post" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="save_entity_activation">';
			print '<input type="hidden" name="entity_activation_enabled" value="'.$nextentityactivationstate.'">';
			print '<button type="submit" class="button krea2factorauth-settings-button">'.$langs->trans($currententityactivationenabled ? "Disable" : "Enable").'</button>';
			print '</form>';
		}
		print '</td>';
		print '</tr>';
	}

	if ($canmanageglobalsettings) {
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Entity").'</td>';
		print '<td>'.$langs->trans("Name").'</td>';
		print '<td class="center">'.$langs->trans("Status").'</td>';
		print '</tr>';
		foreach ($entityactivation as $entityactivationline) {
			$stateclass = !empty($entityactivationline['enabled']) ? 'enabled' : 'disabled';
			$statelabel = !empty($entityactivationline['enabled']) ? $langs->trans("Enabled") : $langs->trans("Disabled");
			print '<tr class="oddeven">';
			print '<td>'.((int) $entityactivationline['rowid']).'</td>';
			print '<td>'.dol_escape_htmltag($entityactivationline['label']).'</td>';
			print '<td class="center"><span class="krea2factorauth-user-state-pill krea2factorauth-user-state-pill-'.$stateclass.'">'.dol_escape_htmltag($statelabel).'</span></td>';
			print '</tr>';
		}
	}
	print '</table><br>';
}

print '<form method="post" enctype="multipart/form-data" id="krea2factorauth-settings-section" class="krea2factorauth-settings-section" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save_settings">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans($canmanageglobalsettings ? "Krea2FactorAuthRuntimeSettings" : "Krea2FactorAuthEntityBrandingSettings").'</td></tr>';

if ($canmanageglobalsettings) {
	print '<tr class="oddeven">';
	print '<td class="fieldrequired">'.$langs->trans("Krea2FactorAuthIssuer").'</td>';
	print '<td><input type="text" class="flat minwidth300" name="issuer" value="'.dol_escape_htmltag($manager->getIssuer()).'"></td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td class="fieldrequired">'.krea2factorauthAdminHelpLabel($form, $langs, "Krea2FactorAuthPrimaryAuthModes").'</td>';
	print '<td><input type="text" class="flat minwidth300" name="primary_auth_modes" value="'.dol_escape_htmltag(implode(',', $manager->getPrimaryAuthModes())).'"></td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("Krea2FactorAuthCodeWindow").'</td>';
	print '<td><select class="flat" name="code_window">';
	for ($i = 0; $i <= 3; $i++) {
		print '<option value="'.$i.'"'.($manager->getCodeWindow() == $i ? ' selected' : '').'>'.$i.'</option>';
	}
	print '</select></td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("Krea2FactorAuthEnforceNonWeb").'</td>';
	print '<td>'.$form->selectyesno('enforce_non_web', $manager->isNonWebEnforcementEnabled() ? 1 : 0, 1).'</td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td>'.krea2factorauthAdminHelpLabel($form, $langs, "Krea2FactorAuthPasswordExpirationMaxDays").'</td>';
	print '<td><input type="number" class="flat maxwidth75" name="password_expiration_max_days" min="'.((int) Krea2FactorAuthManager::PASSWORD_EXPIRATION_MIN_DAYS).'" max="'.((int) Krea2FactorAuthManager::PASSWORD_EXPIRATION_MAX_DAYS).'" step="1" value="'.((int) $manager->getPasswordExpirationMaxDays()).'"></td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td>'.krea2factorauthAdminHelpLabel($form, $langs, "Krea2FactorAuthPasswordHistoryLimit").'</td>';
	print '<td><input type="number" class="flat maxwidth75" name="password_history_limit" min="'.((int) Krea2FactorAuthManager::PASSWORD_HISTORY_MIN_LIMIT).'" max="'.((int) Krea2FactorAuthManager::PASSWORD_HISTORY_MAX_LIMIT).'" step="1" value="'.((int) $manager->getPasswordHistoryLimit()).'"></td>';
	print '</tr>';
}

print '<tr class="oddeven">';
$loginbackgrounddefaultcolor = $manager->getLoginBackgroundDefaultColor();
$loginbackgroundcolors = $manager->getLoginBackgroundColorMap();
$hascurrententitybackgroundoverride = !empty($loginbackgroundcolors[$currententityid]);
$currententitybackgroundcolor = $hascurrententitybackgroundoverride ? $loginbackgroundcolors[$currententityid] : $loginbackgrounddefaultcolor;
$currententitylabel = '';
foreach ($entityactivation as $entityactivationline) {
	if ((int) $entityactivationline['rowid'] === $currententityid) {
		$currententitylabel = (string) $entityactivationline['label'];
		break;
	}
}
print '<td>'.krea2factorauthAdminHelpLabel($form, $langs, "Krea2FactorAuthCurrentEntityBackgroundColor").'</td>';
print '<td><div class="krea2factorauth-color-setting">';
print '<span class="krea2factorauth-current-entity-badge">'.((int) $currententityid).($currententitylabel !== '' ? ' - '.dol_escape_htmltag($currententitylabel) : '').'</span>';
print '<input type="color" class="flat krea2factorauth-color-input" name="login_background_entity_color" value="'.dol_escape_htmltag($currententitybackgroundcolor).'">';
print '<code>'.dol_escape_htmltag($currententitybackgroundcolor).'</code>';
print '<label class="krea2factorauth-checkbox-inline"><input type="checkbox" name="login_background_entity_use_default" value="1"'.(!$hascurrententitybackgroundoverride ? ' checked' : '').'> '.$langs->trans("Krea2FactorAuthUseDefaultColor").'</label>';
print '</div></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.krea2factorauthAdminHelpLabel($form, $langs, "Krea2FactorAuthLoginLogo").'</td>';
print '<td>';
$loginlogourl = $manager->getLoginLogoUrl($currententityid);
$loginlogobasename = $manager->getLoginLogoBasename($currententityid);
print '<div class="krea2factorauth-login-logo-setting">';
print '<div class="krea2factorauth-login-logo-preview">';
if ($loginlogourl !== '') {
	print '<img src="'.dol_escape_htmltag($loginlogourl).'" alt="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthLoginLogo")).'" data-krea2factorauth-login-logo-preview>';
} else {
	print '<span class="fa fa-shield-alt" aria-hidden="true"></span>';
}
print '</div>';
print '<div class="krea2factorauth-login-logo-control">';
print '<input type="file" class="flat" name="login_logo" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp">';
if ($loginlogobasename !== '') {
	print '<label class="krea2factorauth-login-logo-remove"><input type="checkbox" name="remove_login_logo" value="1"> '.$langs->trans("Delete").'</label>';
}
print '<div class="opacitymedium">'.$langs->trans("Krea2FactorAuthLoginLogoRules").'</div>';
print '</div>';
print '</div>';
print '</td>';
print '</tr>';

if ($canmanageglobalsettings) {
	print '<tr class="oddeven">';
	print '<td>'.krea2factorauthAdminHelpLabel($form, $langs, "Krea2FactorAuthOptionalModalSetting").'</td>';
	print '<td><div class="krea2factorauth-admin-toggle" role="group" aria-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthOptionalModalSetting")).'">';
	$enrollmentpromptmode = $manager->getEnrollmentPromptMode();
	print '<input type="radio" id="enrollment_prompt_optional" name="enrollment_prompt_mode" value="'.Krea2FactorAuthManager::ENROLLMENT_PROMPT_OPTIONAL.'"'.($enrollmentpromptmode === Krea2FactorAuthManager::ENROLLMENT_PROMPT_OPTIONAL ? ' checked' : '').'>';
	print '<label for="enrollment_prompt_optional">'.$langs->trans("Krea2FactorAuthPromptModeOptional").'</label>';
	print '<input type="radio" id="enrollment_prompt_mandatory" name="enrollment_prompt_mode" value="'.Krea2FactorAuthManager::ENROLLMENT_PROMPT_MANDATORY.'"'.($enrollmentpromptmode === Krea2FactorAuthManager::ENROLLMENT_PROMPT_MANDATORY ? ' checked' : '').'>';
	print '<label for="enrollment_prompt_mandatory">'.$langs->trans("Krea2FactorAuthPromptModeMandatory").'</label>';
	print '<input type="radio" id="enrollment_prompt_off" name="enrollment_prompt_mode" value="'.Krea2FactorAuthManager::ENROLLMENT_PROMPT_OFF.'"'.($enrollmentpromptmode === Krea2FactorAuthManager::ENROLLMENT_PROMPT_OFF ? ' checked' : '').'>';
	print '<label for="enrollment_prompt_off">'.$langs->trans("Krea2FactorAuthPromptModeOff").'</label>';
	print '</div></td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td>'.krea2factorauthAdminHelpLabel($form, $langs, "Krea2FactorAuthSelfResetSetting").'</td>';
	print '<td><div class="krea2factorauth-admin-toggle" role="group" aria-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthSelfResetSetting")).'">';
	print '<input type="radio" id="allow_self_reset_on" name="allow_self_reset" value="1"'.($manager->isSelfEnrollmentResetAllowed() ? ' checked' : '').'>';
	print '<label for="allow_self_reset_on">'.$langs->trans("Krea2FactorAuthToggleOn").'</label>';
	print '<input type="radio" id="allow_self_reset_off" name="allow_self_reset" value="0"'.(!$manager->isSelfEnrollmentResetAllowed() ? ' checked' : '').'>';
	print '<label for="allow_self_reset_off">'.$langs->trans("Krea2FactorAuthToggleOff").'</label>';
	print '</div></td>';
	print '</tr>';
}

print '</table>';
print '<div class="tabsAction">';
print '<input type="submit" class="button krea2factorauth-settings-button" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
print '</div>';
print '</form>';

print dol_get_fiche_end();

llxFooter();
$db->close();
