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
 * \file    krea2factorauth/admin/google.php
 * \ingroup krea2factorauth
 * \brief   Google login setup page for Krea2FactorAuth.
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../lib/krea2factorauth.lib.php';
dol_include_once('/krea2factorauth/class/krea2factorauthmanager.class.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array("admin", "users", "krea2factorauth@krea2factorauth"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$error = 0;

$form = new Form($db);
$manager = new Krea2FactorAuthManager($db);
$manager->ensureGlobalStorageMigrated();
$currententityid = !empty($conf->entity) ? (int) $conf->entity : 1;
$primaryconfigentityid = Krea2FactorAuthManager::GOOGLE_OAUTH_CREDENTIAL_ENTITY;
$canmanagegooglesetup = ($currententityid === $primaryconfigentityid);

/*
 * Actions
 */

if ($action == 'save_google_login') {
	if (!$manager->isGoogleLoginCoreAvailable()) {
		accessforbidden("Krea2FactorAuthGoogleLoginCoreUnavailable");
	}

	$enabled = GETPOSTINT('google_login_enabled') ? '1' : '0';
	$useextrafieldemail = $manager->isGoogleLoginEmailExtrafieldEnabled() ? '1' : '0';
	$alloweddomainsraw = GETPOST('google_allowed_domains', 'alphanohtml');
	$alloweddomains = $manager->normalizeGoogleAllowedDomainsInput($alloweddomainsraw);

	$invaliddomains = array();
	$parts = preg_split('/[,;\s]+/', strtolower((string) $alloweddomainsraw));
	if (is_array($parts)) {
		foreach ($parts as $part) {
			$domain = ltrim(trim($part), '@');
			if ($domain !== '' && !preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $domain)) {
				$invaliddomains[] = $domain;
			}
		}
	}

	if (count($invaliddomains)) {
		$error++;
		setEventMessages($langs->trans("Krea2FactorAuthGoogleLoginInvalidDomains", implode(', ', $invaliddomains)), null, 'errors');
	}

	if ($canmanagegooglesetup) {
		$useextrafieldemail = GETPOSTINT('google_login_use_extrafield_email') ? '1' : '0';
		if (!$error && $manager->ensureGoogleLoginEmailExtrafield() < 0) {
			$error++;
			setEventMessages($manager->error, $manager->errors, 'errors');
		}
	}

	$settings = array(
		Krea2FactorAuthManager::GOOGLE_LOGIN_ENABLED_CONST => array('value' => $enabled, 'type' => 'yesno'),
		Krea2FactorAuthManager::GOOGLE_LOGIN_ALLOWED_DOMAINS_CONST => array('value' => $alloweddomains, 'type' => 'chaine'),
	);
	if ($canmanagegooglesetup) {
		$settings[Krea2FactorAuthManager::GOOGLE_LOGIN_USE_EXTRAFIELD_EMAIL_CONST] = array('value' => $useextrafieldemail, 'type' => 'yesno');
	}

	if (!$error) {
		$db->begin();
		$result = 1;

		foreach ($settings as $constname => $setting) {
			if (dolibarr_set_const($db, $constname, $setting['value'], $setting['type'], 0, '', Krea2FactorAuthManager::GLOBAL_CONFIG_ENTITY) <= 0) {
				$result = -1;
				break;
			}
		}

		$oauthsettings = array();
		if ($result > 0 && $canmanagegooglesetup) {
			$googleclientid = GETPOSTISSET('google_oauth_client_id') ? trim(GETPOST('google_oauth_client_id', 'alphanohtml')) : $manager->getGoogleOAuthClientId();
			$googleclientsecret = trim(GETPOST('google_oauth_client_secret', 'password'));
			$cleargooglecredentials = GETPOSTINT('clear_google_oauth_credentials');
			$existinggoogleclientsecret = $manager->getGoogleOAuthClientSecret();
			$googleclientidtosave = $cleargooglecredentials ? '' : $googleclientid;
			$googleclientsecrettosave = '';
			if (!$cleargooglecredentials && $googleclientidtosave !== '') {
				$googleclientsecrettosave = ($googleclientsecret !== '') ? $googleclientsecret : $existinggoogleclientsecret;
			}
			$googleoauthscopes = $googleclientidtosave !== '' ? Krea2FactorAuthManager::GOOGLE_OAUTH_SCOPES : '';
			$oauthsettings = array(
				Krea2FactorAuthManager::GOOGLE_OAUTH_CONST_PREFIX.'_ID' => $googleclientidtosave,
				Krea2FactorAuthManager::GOOGLE_OAUTH_CONST_PREFIX.'_SECRET' => $googleclientsecrettosave,
				Krea2FactorAuthManager::GOOGLE_OAUTH_CONST_PREFIX.'_SCOPE' => $googleoauthscopes,
			);

			foreach ($oauthsettings as $constname => $value) {
				if (dolibarr_set_const($db, $constname, $value, 'chaine', 0, '', Krea2FactorAuthManager::GOOGLE_OAUTH_CREDENTIAL_ENTITY) <= 0) {
					$result = -1;
					break;
				}
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

		if ($result > 0 && $canmanagegooglesetup && $manager->syncGoogleLoginEmailExtrafieldMetadata() < 0) {
			$result = -1;
		}

		if ($result > 0) {
			$db->commit();
			foreach ($settings as $constname => $setting) {
				$conf->global->$constname = $setting['value'];
			}
			foreach ($oauthsettings as $constname => $value) {
				$conf->global->$constname = $value;
			}
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
			if ($enabled === '1' && $canmanagegooglesetup && !$manager->isGoogleOAuthConfigured()) {
				setEventMessages($langs->trans("Krea2FactorAuthGoogleLoginCredentialsMissing"), null, 'warnings');
			}
		} else {
			$db->rollback();
			$error++;
			setEventMessages($db->lasterror() ?: $langs->trans("Error"), null, 'errors');
		}
	}
}

/*
 * View
 */

$help_url = '';
$title = "Krea2FactorAuthGoogleLogin";
$morecss = array(dol_buildpath('/krea2factorauth/css/krea2factorauth.css', 1));

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', $morecss, '', 'mod-krea2factorauth page-admin page-admin-google');

$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

$head = krea2factorauthAdminPrepareHead();
print dol_get_fiche_head($head, 'google', $langs->trans($title), -1, "krea2factorauth@krea2factorauth");

if (!$manager->isGoogleLoginCoreAvailable()) {
	print '<div class="warning">'.$langs->trans("Krea2FactorAuthGoogleLoginCoreUnavailable").'</div>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
	exit;
}

$currentauthmode = isset($conf->file->main_authentication) ? (string) $conf->file->main_authentication : '';
$authparts = array_map('trim', explode(',', $currentauthmode));
$isfirstauthmode = (count($authparts) > 0 && $authparts[0] === 'krea2factorauth');
$hasauthmode = in_array('krea2factorauth', $authparts);
$recommendedauthmode = 'krea2factorauth,dolibarr';

if ($canmanagegooglesetup && (!$hasauthmode || !$isfirstauthmode)) {
	print '<div class="warning">';
	print $langs->trans("Krea2FactorAuthAuthModeWarning").'<br>';
	print '<code>$dolibarr_main_authentication = \''.dol_escape_htmltag($recommendedauthmode).'\';</code>';
	print '</div><br>';
}

print '<form method="post" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save_google_login">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Krea2FactorAuthGoogleLoginSettings").'</td></tr>';

print '<tr class="oddeven">';
print '<td class="titlefield">'.krea2factorauthAdminHelpLabel($form, $langs, "Krea2FactorAuthGoogleLoginEnable").'</td>';
print '<td><div class="krea2factorauth-admin-toggle" role="group" aria-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGoogleLoginEnable")).'">';
print '<input type="radio" id="google_login_enabled_on" name="google_login_enabled" value="1"'.($manager->isGoogleLoginEnabled() ? ' checked' : '').'>';
print '<label for="google_login_enabled_on">'.$langs->trans("Krea2FactorAuthToggleOn").'</label>';
print '<input type="radio" id="google_login_enabled_off" name="google_login_enabled" value="0"'.(!$manager->isGoogleLoginEnabled() ? ' checked' : '').'>';
print '<label for="google_login_enabled_off">'.$langs->trans("Krea2FactorAuthToggleOff").'</label>';
print '</div></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.krea2factorauthAdminHelpLabel($form, $langs, "Krea2FactorAuthGoogleLoginAllowedDomains").'</td>';
print '<td><input type="text" class="flat minwidth400" name="google_allowed_domains" value="'.dol_escape_htmltag($manager->getGoogleAllowedDomainsString()).'" placeholder="example.com, example.org"></td>';
print '</tr>';

if ($canmanagegooglesetup) {
	print '<tr class="oddeven">';
	print '<td>'.krea2factorauthAdminHelpLabel($form, $langs, "Krea2FactorAuthGoogleLoginUseExtrafieldEmail").'</td>';
	print '<td><div class="krea2factorauth-admin-toggle" role="group" aria-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGoogleLoginUseExtrafieldEmail")).'">';
	print '<input type="radio" id="google_login_use_extrafield_email_on" name="google_login_use_extrafield_email" value="1"'.($manager->isGoogleLoginEmailExtrafieldEnabled() ? ' checked' : '').'>';
	print '<label for="google_login_use_extrafield_email_on">'.$langs->trans("Krea2FactorAuthToggleOn").'</label>';
	print '<input type="radio" id="google_login_use_extrafield_email_off" name="google_login_use_extrafield_email" value="0"'.(!$manager->isGoogleLoginEmailExtrafieldEnabled() ? ' checked' : '').'>';
	print '<label for="google_login_use_extrafield_email_off">'.$langs->trans("Krea2FactorAuthToggleOff").'</label>';
	print '</div></td>';
	print '</tr>';
}

print '</table><br>';

if (!$canmanagegooglesetup) {
	print '<div class="tabsAction">';
	print '<input type="submit" class="button krea2factorauth-settings-button" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
	print '</div>';
	print '</form>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
	exit;
}

$googleoauthclientid = $manager->getGoogleOAuthClientId();
$googleoauthsecretconfigured = ($manager->getGoogleOAuthClientSecret() !== '');
$googleoauthconfigured = ($googleoauthclientid !== '' && $googleoauthsecretconfigured);
$googleoauthcorecallbackurl = $manager->getGoogleOAuthCallbackUrl();
$googleoauthmodulecallbackurl = $manager->getGoogleOAuthModuleCallbackUrl();
$googleoauthcallbackurl = $manager->getGoogleOAuthLoginCallbackUrl();
$googleoauthoriginurl = preg_replace('/\/$/', '', defined('DOL_MAIN_URL_ROOT') ? DOL_MAIN_URL_ROOT : DOL_URL_ROOT);
$googleapphomepageurl = dol_buildpath('/krea2factorauth/public/google_app.php', 2);
$googleprivacyurl = dol_buildpath('/krea2factorauth/public/privacy.php', 2);
$googletermsurl = dol_buildpath('/krea2factorauth/public/terms.php', 2);
$googlecloudprojecturl = 'https://console.cloud.google.com/projectcreate';
$googleauthoverviewurl = 'https://console.cloud.google.com/auth/overview';
$googleauthbrandingurl = 'https://console.cloud.google.com/auth/branding';
$googleauthclientsurl = 'https://console.cloud.google.com/auth/clients';
$googledocconsenturl = 'https://developers.google.com/workspace/guides/configure-oauth-consent';
$googledoccredentialsurl = 'https://developers.google.com/workspace/guides/create-credentials';
$googledocwebserverurl = 'https://developers.google.com/identity/protocols/oauth2/web-server';
$googledocopenidurl = 'https://developers.google.com/identity/openid-connect/openid-connect';
$callbackurlparts = parse_url($googleoauthcallbackurl);
$callbackscheme = is_array($callbackurlparts) && !empty($callbackurlparts['scheme']) ? strtolower($callbackurlparts['scheme']) : '';
$callbackhost = is_array($callbackurlparts) && !empty($callbackurlparts['host']) ? strtolower($callbackurlparts['host']) : '';
$islocalcallback = in_array($callbackhost, array('localhost', '127.0.0.1', '::1'), true);

if (!$manager->isGoogleOAuthConfigured()) {
	print '<div class="warning">';
	print $langs->trans("Krea2FactorAuthGoogleLoginCredentialsMissing");
	print '</div><br>';
}

if ($callbackscheme !== 'https' && !$islocalcallback) {
	print '<div class="warning">';
	print $langs->trans("Krea2FactorAuthGoogleSetupHttpsWarning");
	print '</div><br>';
}

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Krea2FactorAuthGoogleOAuthCredentialsSettings").'</td></tr>';

print '<tr class="oddeven">';
print '<td class="titlefield">'.$langs->trans("Krea2FactorAuthGoogleOAuthCurrentEntity").'</td>';
print '<td>'.((int) Krea2FactorAuthManager::GOOGLE_OAUTH_CREDENTIAL_ENTITY).'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Krea2FactorAuthGoogleOAuthProviderKey").'</td>';
print '<td><code>Login</code></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.krea2factorauthAdminHelpLabel($form, $langs, "Krea2FactorAuthGoogleOAuthClientId").'</td>';
print '<td><input type="text" class="flat minwidth500" name="google_oauth_client_id" value="'.dol_escape_htmltag($googleoauthclientid).'" autocomplete="off"></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.krea2factorauthAdminHelpLabel($form, $langs, "Krea2FactorAuthGoogleOAuthClientSecret").'</td>';
print '<td>';
print '<input type="password" class="flat minwidth500" name="google_oauth_client_secret" value="" autocomplete="new-password">';
print ' <span class="krea2factorauth-user-state-pill krea2factorauth-user-state-pill-'.($googleoauthsecretconfigured ? 'enabled' : 'disabled').'">'.dol_escape_htmltag($langs->trans($googleoauthsecretconfigured ? "Krea2FactorAuthGoogleOAuthClientSecretConfigured" : "Krea2FactorAuthGoogleOAuthClientSecretMissing")).'</span>';
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Krea2FactorAuthGoogleOAuthScopes").'</td>';
print '<td><code>'.dol_escape_htmltag(Krea2FactorAuthManager::GOOGLE_OAUTH_SCOPES).'</code></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Krea2FactorAuthGoogleLoginCallbackUrl").'</td>';
print '<td><code>'.dol_escape_htmltag($googleoauthcallbackurl).'</code><br><span class="opacitymedium">'.$langs->trans($manager->isGoogleLoginEmailExtrafieldEnabled() ? "Krea2FactorAuthGoogleLoginCallbackExtrafieldMode" : "Krea2FactorAuthGoogleLoginCallbackCoreMode").'</span></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Krea2FactorAuthGoogleOAuthClearCredentials").'</td>';
print '<td><input type="checkbox" name="clear_google_oauth_credentials" value="1"></td>';
print '</tr>';

print '</table><br>';

print '<div class="tabsAction">';
print '<input type="submit" class="button krea2factorauth-settings-button" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
print '</div>';
print '</form>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Krea2FactorAuthGoogleSetupGuide").'</td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("Krea2FactorAuthGoogleSetupPurpose").'</td><td>'.$langs->trans("Krea2FactorAuthGoogleSetupPurposeText").'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthGoogleSetupGoogleConsole").'</td><td>';
print '<a class="button" href="'.dol_escape_htmltag($googlecloudprojecturl).'" target="_blank" rel="noopener noreferrer">'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGoogleSetupCreateProject")).'</a> ';
print '<a class="button" href="'.dol_escape_htmltag($googleauthoverviewurl).'" target="_blank" rel="noopener noreferrer">'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGoogleSetupAuthPlatform")).'</a> ';
print '<a class="button" href="'.dol_escape_htmltag($googleauthbrandingurl).'" target="_blank" rel="noopener noreferrer">'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGoogleSetupBranding")).'</a> ';
print '<a class="button" href="'.dol_escape_htmltag($googleauthclientsurl).'" target="_blank" rel="noopener noreferrer">'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGoogleSetupClients")).'</a>';
print '</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthGoogleSetupOfficialDocs").'</td><td>';
print '<a href="'.dol_escape_htmltag($googledocconsenturl).'" target="_blank" rel="noopener noreferrer">'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGoogleSetupConsentDocs")).'</a> | ';
print '<a href="'.dol_escape_htmltag($googledoccredentialsurl).'" target="_blank" rel="noopener noreferrer">'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGoogleSetupCredentialsDocs")).'</a> | ';
print '<a href="'.dol_escape_htmltag($googledocwebserverurl).'" target="_blank" rel="noopener noreferrer">'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGoogleSetupWebServerDocs")).'</a> | ';
print '<a href="'.dol_escape_htmltag($googledocopenidurl).'" target="_blank" rel="noopener noreferrer">'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGoogleSetupOpenIdDocs")).'</a>';
print '</td></tr>';
print '</table><br>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Krea2FactorAuthGoogleSetupSteps").'</td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("Krea2FactorAuthGoogleSetupStepProject").'</td><td>'.$langs->trans("Krea2FactorAuthGoogleSetupStepProjectText").'</td><td><a href="'.dol_escape_htmltag($googlecloudprojecturl).'" target="_blank" rel="noopener noreferrer">'.$langs->trans("Krea2FactorAuthGoogleSetupCreateProject").'</a></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthGoogleSetupStepConsent").'</td><td>'.$langs->trans("Krea2FactorAuthGoogleSetupStepConsentText").'</td><td><a href="'.dol_escape_htmltag($googleauthbrandingurl).'" target="_blank" rel="noopener noreferrer">'.$langs->trans("Krea2FactorAuthGoogleSetupBranding").'</a></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthGoogleSetupStepClient").'</td><td>'.$langs->trans("Krea2FactorAuthGoogleSetupStepClientText").'</td><td><a href="'.dol_escape_htmltag($googleauthclientsurl).'" target="_blank" rel="noopener noreferrer">'.$langs->trans("Krea2FactorAuthGoogleSetupClients").'</a></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthGoogleSetupStepCredentials").'</td><td>'.$langs->trans("Krea2FactorAuthGoogleSetupStepCredentialsText").'</td><td>'.$langs->trans("Krea2FactorAuthGoogleSetupStepCredentialsAction").'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthGoogleSetupStepTest").'</td><td>'.$langs->trans("Krea2FactorAuthGoogleSetupStepTestText").'</td><td>'.$langs->trans("Krea2FactorAuthGoogleSetupStepTestAction").'</td></tr>';
print '</table><br>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Krea2FactorAuthGoogleSetupValuesForGoogle").'</td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("Krea2FactorAuthGoogleSetupApplicationHomepage").'</td><td><code>'.dol_escape_htmltag($googleapphomepageurl).'</code> <a href="'.dol_escape_htmltag($googleapphomepageurl).'" target="_blank" rel="noopener noreferrer">'.$langs->trans("Open").'</a></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthGoogleSetupPrivacyPolicyUrl").'</td><td><code>'.dol_escape_htmltag($googleprivacyurl).'</code> <a href="'.dol_escape_htmltag($googleprivacyurl).'" target="_blank" rel="noopener noreferrer">'.$langs->trans("Open").'</a></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthGoogleSetupTermsUrl").'</td><td><code>'.dol_escape_htmltag($googletermsurl).'</code> <a href="'.dol_escape_htmltag($googletermsurl).'" target="_blank" rel="noopener noreferrer">'.$langs->trans("Open").'</a></td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("Krea2FactorAuthGoogleSetupApplicationType").'</td><td><code>Web application</code></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthGoogleSetupAuthorizedRedirectUri").'</td><td><code>'.dol_escape_htmltag($googleoauthcallbackurl).'</code></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthGoogleSetupCoreRedirectUri").'</td><td><code>'.dol_escape_htmltag($googleoauthcorecallbackurl).'</code></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthGoogleSetupExtrafieldRedirectUri").'</td><td><code>'.dol_escape_htmltag($googleoauthmodulecallbackurl).'</code><br><span class="opacitymedium">'.$langs->trans("Krea2FactorAuthGoogleSetupExtrafieldRedirectUriHelp").'</span></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthGoogleSetupAuthorizedOrigin").'</td><td><code>'.dol_escape_htmltag($googleoauthoriginurl).'</code><br><span class="opacitymedium">'.$langs->trans("Krea2FactorAuthGoogleSetupAuthorizedOriginHelp").'</span></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthGoogleOAuthScopes").'</td><td><code>'.dol_escape_htmltag(Krea2FactorAuthManager::GOOGLE_OAUTH_SCOPES).'</code><br><span class="opacitymedium">'.$langs->trans("Krea2FactorAuthGoogleSetupScopesHelp").'</span></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthGoogleSetupEntityNote").'</td><td>'.$langs->trans("Krea2FactorAuthGoogleSetupEntityNoteText", (int) Krea2FactorAuthManager::GOOGLE_OAUTH_CREDENTIAL_ENTITY).'</td></tr>';
print '</table><br>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Krea2FactorAuthGoogleLoginStatus").'</td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("Krea2FactorAuthGoogleLoginCoreSupport").'</td><td><span class="krea2factorauth-user-state-pill krea2factorauth-user-state-pill-enabled">'.$langs->trans("Enabled").'</span></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthGoogleLoginCredentials").'</td><td><span class="krea2factorauth-user-state-pill krea2factorauth-user-state-pill-'.($googleoauthconfigured ? 'enabled' : 'disabled').'">'.dol_escape_htmltag($langs->trans($googleoauthconfigured ? "Krea2FactorAuthConfigured" : "NotConfigured")).'</span></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthGoogleLoginCallbackUrl").'</td><td><code>'.dol_escape_htmltag($googleoauthcallbackurl).'</code></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("Krea2FactorAuthGoogleLoginUseExtrafieldEmail").'</td><td><span class="krea2factorauth-user-state-pill krea2factorauth-user-state-pill-'.($manager->isGoogleLoginEmailExtrafieldEnabled() ? 'enabled' : 'disabled').'">'.$langs->trans($manager->isGoogleLoginEmailExtrafieldEnabled() ? "Enabled" : "Disabled").'</span></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("AuthenticationMode").'</td><td><code>'.dol_escape_htmltag($currentauthmode).'</code></td></tr>';
print '</table><br>';

print dol_get_fiche_end();

llxFooter();
$db->close();
