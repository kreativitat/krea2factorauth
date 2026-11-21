<?php
/* Copyright (C) 2026		Kreativität Works	<mail@kreativitat.com>
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
 * \file    krea2factorauth/lib/krea2factorauth.lib.php
 * \ingroup krea2factorauth
 * \brief   Library files with common functions for Krea2FactorAuth
 */

/**
 * Prepare admin pages header
 *
 * @param string $onlytab Return only this tab when non-empty
 * @return array<array{string,string,string}>
 */
function krea2factorauthAdminPrepareHead($onlytab = '')
{
	global $langs, $conf;

	// global $db;
	// $extrafields = new ExtraFields($db);
	// $extrafields->fetch_name_optionals_label('myobject');

	$langs->load("krea2factorauth@krea2factorauth");

	$h = 0;
	$head = array();

	if ($onlytab === '' || $onlytab === 'settings') {
		$head[$h][0] = dol_buildpath("/krea2factorauth/admin/setup.php", 1);
		$head[$h][1] = $langs->trans("Krea2FactorAuthTotp2FA");
		$head[$h][2] = 'settings';
		$h++;
	}

	if (($onlytab === '' || $onlytab === 'google') && krea2factorauthGoogleLoginCoreAvailable()) {
		$head[$h][0] = dol_buildpath("/krea2factorauth/admin/google.php", 1);
		$head[$h][1] = $langs->trans("Krea2FactorAuthGoogleLogin");
		$head[$h][2] = 'google';
		$h++;
	}

	if ($onlytab === '' || $onlytab === 'users') {
		$head[$h][0] = dol_buildpath("/krea2factorauth/admin/users.php", 1).($onlytab === 'users' ? '?mainmenu=home&leftmenu=users' : '');
		$head[$h][1] = $langs->trans("Krea2FactorAuthManagedUsers");
		$head[$h][2] = 'users';
		$h++;
	}

	/*
	$head[$h][0] = dol_buildpath("/krea2factorauth/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = (isset($extrafields->attributes['myobject']['label']) && is_countable($extrafields->attributes['myobject']['label'])) ? count($extrafields->attributes['myobject']['label']) : 0;
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbExtrafields . '</span>';
	}
	$head[$h][2] = 'myobject_extrafields';
	$h++;

	$head[$h][0] = dol_buildpath("/krea2factorauth/admin/myobjectline_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFieldsLines");
	$nbExtrafields = (isset($extrafields->attributes['myobjectline']['label']) && is_countable($extrafields->attributes['myobjectline']['label'])) ? count($extrafields->attributes['myobject']['label']) : 0;
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbExtrafields . '</span>';
	}
	$head[$h][2] = 'myobject_extrafieldsline';
	$h++;
	*/

	if ($onlytab === '' || $onlytab === 'about') {
		$head[$h][0] = dol_buildpath("/krea2factorauth/admin/about.php", 1);
		$head[$h][1] = $langs->trans("About");
		$head[$h][2] = 'about';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@krea2factorauth:/krea2factorauth/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@krea2factorauth:/krea2factorauth/mypage.php?id=__ID__'
	//); // to remove a tab
	if ($onlytab === '') {
		complete_head_from_modules($conf, $langs, null, $head, $h, 'krea2factorauth@krea2factorauth');

		complete_head_from_modules($conf, $langs, null, $head, $h, 'krea2factorauth@krea2factorauth', 'remove');
	}

	return $head;
}

/**
 * Return whether this Dolibarr installation exposes native Google OAuth login support.
 *
 * @return bool
 */
function krea2factorauthGoogleLoginCoreAvailable()
{
	return defined('DOL_DOCUMENT_ROOT')
		&& is_readable(DOL_DOCUMENT_ROOT.'/core/login/functions_googleoauth.php')
		&& is_readable(DOL_DOCUMENT_ROOT.'/core/modules/oauth/google_oauthcallback.php')
		&& is_readable(DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php');
}

/**
 * Validate and normalize a same-instance return URL used after login.
 *
 * Authentication and state-changing parameters are removed so a login cannot
 * replay the action that was interrupted when the previous session expired.
 *
 * @param string $url Candidate relative URL
 * @return string     Safe relative URL, or an empty string when rejected
 */
function krea2factorauthNormalizePostLoginReturnUrl($url)
{
	$url = trim(html_entity_decode((string) $url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
	if ($url === '' || strlen($url) > 4096 || substr($url, 0, 1) !== '/' || substr($url, 0, 2) === '//') {
		return '';
	}
	if (preg_match('/[\x00-\x1F\x7F]/', $url) || strpos($url, '\\') !== false) {
		return '';
	}

	$parts = parse_url($url);
	if ($parts === false || empty($parts['path']) || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
		return '';
	}

	$path = (string) $parts['path'];
	$safetypath = $path;
	for ($i = 0; $i < 3; $i++) {
		$decodedpath = rawurldecode($safetypath);
		if ($decodedpath === $safetypath) {
			break;
		}
		$safetypath = $decodedpath;
	}
	if (substr($safetypath, 0, 1) !== '/' || substr($safetypath, 0, 2) === '//' || strpos($safetypath, '\\') !== false || preg_match('/[\x00-\x1F\x7F]/', $safetypath) || preg_match('~(?:^|/)\.\.(?:/|$)~', $safetypath)) {
		return '';
	}

	$root = rtrim((string) DOL_URL_ROOT, '/');
	if ($root !== '' && $safetypath !== $root && strpos($safetypath, $root.'/') !== 0) {
		return '';
	}
	$relativepath = $root !== '' ? substr($safetypath, strlen($root)) : $safetypath;
	if ($relativepath === '' || $relativepath === '/' || $relativepath === '/index.php') {
		return '';
	}

	$blockedpaths = array(
		'/user/logout.php',
		'/custom/krea2factorauth/public/google_login.php',
		'/custom/krea2factorauth/public/google_oauthcallback.php',
	);
	if (in_array($relativepath, $blockedpaths, true)) {
		return '';
	}

	$query = array();
	if (!empty($parts['query'])) {
		parse_str(html_entity_decode((string) $parts['query'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), $query);
	}
	$transientkeys = array(
		'action',
		'actionlogin',
		'afteroauthloginreturn',
		'beforeoauthloginredirect',
		'confirm',
		'entity',
		'loginfunction',
		'massaction',
		'password',
		'token',
		'username',
		'urlfrom',
		'krea2factorauth_return_url',
	);
	foreach (array_keys($query) as $querykey) {
		if (in_array(strtolower((string) $querykey), $transientkeys, true)) {
			unset($query[$querykey]);
		}
	}

	$normalizedurl = $path;
	if (!empty($query)) {
		$normalizedurl .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
	}

	return $normalizedurl;
}

/**
 * Resolve the return URL carried by the login form or the current request.
 *
 * @param string $submittedurl Explicit URL submitted by the module login form
 * @return string              Safe relative URL, or an empty string
 */
function krea2factorauthGetPostLoginReturnUrl($submittedurl = '')
{
	$returnurl = krea2factorauthNormalizePostLoginReturnUrl($submittedurl);
	if ($returnurl !== '') {
		return $returnurl;
	}

	$returnurl = krea2factorauthNormalizePostLoginReturnUrl(GETPOST('urlfrom', 'restricthtml'));
	if ($returnurl !== '') {
		return $returnurl;
	}

	if (!empty($_SESSION['urlfrom'])) {
		$returnurl = krea2factorauthNormalizePostLoginReturnUrl((string) $_SESSION['urlfrom']);
		if ($returnurl !== '') {
			return $returnurl;
		}
	}

	return krea2factorauthNormalizePostLoginReturnUrl(empty($_SERVER['REQUEST_URI']) ? '' : (string) $_SERVER['REQUEST_URI']);
}

/**
 * Return an admin label with a Dolibarr-native clickable help pictogram.
 *
 * @param Form      $form        Form helper
 * @param Translate $langs       Language handler
 * @param string    $labelKey    Translation key for the visible label
 * @param string    $helpKey     Translation key for the help text
 * @param string    $triggerKey  Stable tooltip trigger key
 * @return string
 */
function krea2factorauthAdminHelpLabel($form, $langs, $labelKey, $helpKey = '', $triggerKey = '')
{
	$helpKey = $helpKey ?: $labelKey.'Help';
	$triggerKey = $triggerKey ?: $labelKey;
	$helpText = $langs->trans($helpKey);

	if ($helpText === $helpKey) {
		return $langs->trans($labelKey);
	}

	return $form->textwithpicto(
		$langs->trans($labelKey),
		dol_escape_htmltag($helpText, 1),
		1,
		'info',
		'valignmiddle',
		1,
		3,
		'tootips'.$triggerKey
	);
}

/**
 * Return the packaged default logo URL.
 *
 * @return string
 */
function krea2factorauthGetDefaultLogoUrl()
{
	return dol_buildpath('/krea2factorauth/img/default-logo.png', 1);
}

/**
 * Return the Dolibarr user card URL.
 *
 * The function name is kept for compatibility with older module call sites
 * that targeted the former embedded user-card panel.
 *
 * @param int $userid User id
 * @return string     URL
 */
function krea2factorauthGetUserCardPanelUrl($userid)
{
	return DOL_URL_ROOT.'/user/card.php?id='.((int) $userid);
}

/**
 * Return the dedicated 2FA enrollment page URL.
 *
 * @return string URL
 */
function krea2factorauthGetEnrollmentPageUrl()
{
	return dol_buildpath('/krea2factorauth/user/enrollment.php', 1);
}

/**
 * Return the dedicated mandatory password-change page URL.
 *
 * @return string URL
 */
function krea2factorauthGetPasswordChangePageUrl()
{
	return dol_buildpath('/krea2factorauth/user/password_change.php', 1);
}

/**
 * Render Access Management rows for Dolibarr's user-card credentials table.
 *
 * @param User $targetuser Target user
 * @return string          HTML table rows
 */
function krea2factorauthRenderUserCardCredentialRows(User $targetuser)
{
	global $db, $langs;

	$langs->loadLangs(array("users", "krea2factorauth@krea2factorauth"));

	$manager = new Krea2FactorAuthManager($db);
	if (!$manager->isUserVisibleInCurrentEntity((int) $targetuser->id)) {
		return '';
	}

	$userid = (int) $targetuser->id;
	$enabled = $manager->isEnabledForUser($userid);
	$mandatory = $manager->isMandatoryForUser($userid);
	$exempt = $manager->isMandatoryExemptForUser($userid);
	$locked = $manager->isLoginLockedForUser($userid);
	$showgoogle2fa = $manager->isGoogleTwoFactorAvailable();
	if ($locked) {
		$totplabel = $langs->trans("Krea2FactorAuthPolicyLocked");
	} elseif ($exempt) {
		$totplabel = $langs->trans("Krea2FactorAuthPolicyExempt");
	} elseif ($enabled) {
		$totplabel = $langs->trans("Krea2FactorAuthPolicyEnabled");
	} elseif ($mandatory) {
		$totplabel = $langs->trans("Krea2FactorAuthPolicyMandatory");
	} else {
		$totplabel = $langs->trans("Krea2FactorAuthPolicyOptional");
	}
	$totpvalue = dol_escape_htmltag($totplabel);

	if ($showgoogle2fa) {
		$googleemails = $manager->getGoogleLoginEmailsForUserId($userid);
		if (count($googleemails)) {
			$googlevalue = dol_escape_htmltag($langs->trans("Krea2FactorAuthGoogleConfigured").' - '.implode(', ', $googleemails));
		} else {
			$googlevalue = dol_escape_htmltag($langs->trans("Krea2FactorAuthGoogleNotConfigured"));
		}
	}

	if ($manager->isPasswordExpirationEnabledForUser($userid)) {
		$passwordexpiresat = $manager->getPasswordExpiresAtForUser($userid);
		if ($passwordexpiresat > 0 && $passwordexpiresat <= dol_now()) {
			$passwordexpirationvalue = dol_escape_htmltag($langs->trans("Krea2FactorAuthPasswordExpiredSince", dol_print_date($passwordexpiresat, 'dayhour')));
		} elseif ($passwordexpiresat > 0) {
			$passwordexpirationvalue = dol_escape_htmltag($langs->trans("Krea2FactorAuthPasswordExpiresAt", dol_print_date($passwordexpiresat, 'dayhour')));
		} else {
			$passwordexpirationvalue = dol_escape_htmltag($langs->trans("Krea2FactorAuthPasswordExpirationActive"));
		}
	} else {
		$passwordexpirationvalue = dol_escape_htmltag($langs->trans("Krea2FactorAuthPasswordExpirationOff"));
	}

	$rows = '';
	$rows .= '<tr class="nooddeven krea2factorauth-user-card-credential-row"><td>'.$langs->trans("Krea2FactorAuthTotp2FA").'</td><td>'.$totpvalue.'</td></tr>';
	if ($showgoogle2fa) {
		$rows .= '<tr class="nooddeven krea2factorauth-user-card-credential-row"><td>'.$langs->trans("Krea2FactorAuthGoogle2FA").'</td><td>'.$googlevalue.'</td></tr>';
	}
	$rows .= '<tr class="nooddeven krea2factorauth-user-card-credential-row"><td>'.$langs->trans("Krea2FactorAuthPasswordExpiration").'</td><td>'.$passwordexpirationvalue.'</td></tr>';

	return $rows;
}

/**
 * Return the session key for a pending enrollment secret.
 *
 * @param int $userid User id
 * @return string
 */
function krea2factorauthGetPendingSecretSessionKey($userid)
{
	return 'KREA2FACTORAUTH_PENDING_SECRET_'.Krea2FactorAuthManager::GLOBAL_USER_PARAM_ENTITY.'_'.((int) $userid);
}

/**
 * Build a Dolibarr warning message for a generated exception password.
 *
 * @param string $login             Target user login
 * @param string $generatedpassword Generated password
 * @param bool   $failed            Whether the policy action failed after password rotation
 * @return string                   HTML warning content
 */
function krea2factorauthBuildExceptionPasswordWarning($login, $generatedpassword, $failed = false)
{
	global $langs;

	if ($generatedpassword === '') {
		return '';
	}

	$textkey = $failed ? "Krea2FactorAuthPersistentPasswordFailureText" : "Krea2FactorAuthPersistentPasswordText";
	$title = $langs->trans("Krea2FactorAuthPersistentPasswordTitle");
	$text = $langs->trans($textkey, $login);

	$html = '<div class="krea2factorauth-password-message">';
	$html .= '<strong>'.dol_escape_htmltag($title).'</strong>';
	$html .= '<span>'.dol_escape_htmltag($text).'</span>';
	$html .= '<code class="krea2factorauth-password-value">'.dol_escape_htmltag($generatedpassword).'</code>';
	$html .= '<button type="button" class="krea2factorauth-password-copy" data-krea2factorauth-copy-password="'.dol_escape_htmltag($generatedpassword).'" data-copy-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthCopyPassword")).'" data-copied-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthPasswordCopied")).'" data-copy-error-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthPasswordCopyFailed")).'" aria-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthCopyPassword")).'"><span class="fas fa-copy" aria-hidden="true"></span><span data-krea2factorauth-copy-password-label>'.dol_escape_htmltag($langs->trans("Krea2FactorAuthCopyPassword")).'</span></button>';
	$html .= '</div>';

	return $html;
}

/**
 * Push a generated exception password into Dolibarr's standard warning messages.
 *
 * @param string $login             Target user login
 * @param string $generatedpassword Generated password
 * @param bool   $failed            Whether the policy action failed after password rotation
 * @return void
 */
function krea2factorauthSetExceptionPasswordWarning($login, $generatedpassword, $failed = false)
{
	$html = krea2factorauthBuildExceptionPasswordWarning($login, $generatedpassword, $failed);
	if ($html !== '') {
		setEventMessages($html, null, 'warnings');
	}
}

/**
 * Render the generated-password reset control for a 2FA-exempt user.
 *
 * @param User   $targetuser       Target user
 * @param string $actionurl        Form action URL
 * @param bool   $insideparentform True when rendered inside Dolibarr's native user edit form
 * @return string                  HTML
 */
function krea2factorauthRenderExceptionPasswordResetControl(User $targetuser, $actionurl, $insideparentform = false)
{
	global $db, $langs, $user;

	$manager = new Krea2FactorAuthManager($db);
	$issuperadmin = $manager->isSuperAdmin($user);
	$confirmtitle = $langs->trans("Krea2FactorAuthGenerateExceptionPasswordConfirmTitle");
	$confirmtext = $langs->trans("Krea2FactorAuthGenerateExceptionPasswordConfirmText");
	$confirmaction = $langs->trans("Krea2FactorAuthGenerateExceptionPasswordConfirmAction");
	$confirmcancel = $langs->trans("Cancel");
	$buttonlabel = $langs->trans("Krea2FactorAuthActionGenerateExceptionPassword");

	$html = '<div class="krea2factorauth-exempt-password-control" data-krea2factorauth-exempt-password-lock="1">';
	$html .= '<span class="opacitymedium">'.dol_escape_htmltag($langs->trans("Krea2FactorAuthExemptManualPasswordDisabled")).'</span>';
	if ($issuperadmin) {
		if ($insideparentform) {
			$html .= '<input type="hidden" name="krea2factorauth_return_edit" value="1">';
			$html .= '<button type="submit" class="button smallpaddingimp" name="action" value="krea2factorauth_generate_exception_password" formaction="'.dol_escape_htmltag($actionurl).'" formmethod="post" formnovalidate data-krea2factorauth-button-confirm="1" data-confirm-title="'.dol_escape_htmltag($confirmtitle).'" data-confirm-text="'.dol_escape_htmltag($confirmtext).'" data-confirm-action="'.dol_escape_htmltag($confirmaction).'" data-confirm-cancel="'.dol_escape_htmltag($confirmcancel).'" aria-label="'.dol_escape_htmltag($buttonlabel).'">'.dol_escape_htmltag($buttonlabel).'</button>';
		} else {
			$html .= '<form class="krea2factorauth-inline-action" method="post" action="'.dol_escape_htmltag($actionurl).'" data-krea2factorauth-reset-confirm="1" data-confirm-title="'.dol_escape_htmltag($confirmtitle).'" data-confirm-text="'.dol_escape_htmltag($confirmtext).'" data-confirm-action="'.dol_escape_htmltag($confirmaction).'" data-confirm-cancel="'.dol_escape_htmltag($confirmcancel).'">';
			$html .= '<input type="hidden" name="token" value="'.newToken().'">';
			$html .= '<input type="hidden" name="action" value="krea2factorauth_generate_exception_password">';
			$html .= '<button type="submit" class="button smallpaddingimp" aria-label="'.dol_escape_htmltag($buttonlabel).'">'.dol_escape_htmltag($buttonlabel).'</button>';
			$html .= '</form>';
		}
	}
	$html .= '</div>';

	return $html;
}

/**
 * Render a six-box TOTP code input while preserving the legacy full-code field.
 *
 * @param string              $fieldname      Hidden full-code field name
 * @param string              $digitfieldname Repeated digit field name
 * @param string              $htmlid         Base HTML id
 * @param string              $label          Visible label
 * @param array<string,mixed> $options        Rendering options
 * @return string                             HTML
 */
function krea2factorauthRenderTotpCodeInput($fieldname = 'totp_code', $digitfieldname = 'totp_digit', $htmlid = 'krea2factorauth-code', $label = '', $options = array())
{
	$classes = array('krea2factorauth-code-input');
	if (!empty($options['compact'])) {
		$classes[] = 'krea2factorauth-code-input-compact';
	}

	$tabindex = isset($options['tabindex']) ? (int) $options['tabindex'] : 0;
	$aria = $label !== '' ? $label : 'TOTP code';

	$html = '<div class="'.dol_escape_htmltag(implode(' ', $classes)).'" data-krea2factorauth-code>';
	if ($label !== '') {
		$html .= '<label class="krea2factorauth-code-label" for="'.dol_escape_htmltag($htmlid).'-1">'.dol_escape_htmltag($label).'</label>';
	}
	$html .= '<input type="hidden" id="'.dol_escape_htmltag($htmlid).'" name="'.dol_escape_htmltag($fieldname).'" class="krea2factorauth-code-value" value="">';
	$html .= '<div class="krea2factorauth-code-boxes" role="group" aria-label="'.dol_escape_htmltag($aria).'">';
	for ($i = 1; $i <= 6; $i++) {
		$html .= '<input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="flat krea2factorauth-code-digit" name="'.dol_escape_htmltag($digitfieldname).'[]" id="'.dol_escape_htmltag($htmlid).'-'.$i.'" value="" autocomplete="'.($i === 1 ? 'one-time-code' : 'off').'" aria-label="'.dol_escape_htmltag($aria.' '.$i).'"'.($tabindex > 0 ? ' tabindex="'.$tabindex.'"' : '').'>';
	}
	$html .= '</div>';
	$html .= '</div>';

	return $html;
}

/**
 * Require a local POST request with a valid Dolibarr anti-CSRF token.
 *
 * @param string $action Current action
 * @return void
 */
function krea2factorauthRequirePostToken($action)
{
	$method = empty($_SERVER['REQUEST_METHOD']) ? '' : strtoupper((string) $_SERVER['REQUEST_METHOD']);
	if ($method !== 'POST') {
		dol_syslog('Krea2FactorAuth refused non-POST state-changing action '.$action, LOG_WARNING);
		accessforbidden('Invalid request method');
	}

	$submittedtoken = GETPOST('token', 'alpha', 2);
	$currenttoken = currentToken();
	$tokenisvalid = ($submittedtoken !== '' && $currenttoken !== '');
	if ($tokenisvalid) {
		$tokenisvalid = function_exists('hash_equals') ? hash_equals($currenttoken, $submittedtoken) : ($currenttoken === $submittedtoken);
	}
	if (!$tokenisvalid) {
		dol_syslog('Krea2FactorAuth refused state-changing action '.$action.' with invalid token', LOG_WARNING);
		accessforbidden('Invalid security token');
	}
}

/**
 * Handle 2FA user-card actions before Dolibarr renders the page.
 *
 * @param User   $targetuser  Target user
 * @param string $action      Current action
 * @param string $redirecturl Redirect URL after handled action
 * @return int                0 if action not handled, exits after handled action
 */
function krea2factorauthHandleUserCardAction(User $targetuser, $action, $redirecturl = '')
{
	global $conf, $db, $langs, $user;

	if (!in_array($action, array('krea2factorauth_regenerate_secret', 'krea2factorauth_enable_2fa', 'krea2factorauth_disable_2fa', 'krea2factorauth_exempt_2fa', 'krea2factorauth_require_2fa', 'krea2factorauth_generate_exception_password'))) {
		return 0;
	}
	krea2factorauthRequirePostToken($action);

	$langs->loadLangs(array("users", "krea2factorauth@krea2factorauth"));

	$manager = new Krea2FactorAuthManager($db);
	if (!$manager->isUserVisibleInCurrentEntity((int) $targetuser->id)) {
		accessforbidden('Invalid user scope');
	}

	$pendingkey = krea2factorauthGetPendingSecretSessionKey((int) $targetuser->id);
	$isself = ((int) $targetuser->id === (int) $user->id);
	$issuperadmin = $manager->isSuperAdmin($user);
	$redirecturl = $redirecturl ?: krea2factorauthGetUserCardPanelUrl((int) $targetuser->id);
	if ($action == 'krea2factorauth_generate_exception_password' && GETPOSTINT('krea2factorauth_return_edit')) {
		$redirecturl = DOL_URL_ROOT.'/user/card.php?id='.((int) $targetuser->id).'&action=edit&token='.newToken();
	}

	if ($action == 'krea2factorauth_regenerate_secret') {
		if (!$isself) {
			accessforbidden();
		}
		if (!$manager->isSelfEnrollmentResetAllowed()) {
			setEventMessages($langs->trans("Krea2FactorAuthSelfResetDisabled"), null, 'errors');
			header('Location: '.$redirecturl);
			exit;
		}

		try {
			$_SESSION[$pendingkey] = $manager->createSecret();
			setEventMessages($langs->trans("Krea2FactorAuthSecretRegenerated"), null, 'mesgs');
		} catch (Exception $e) {
			setEventMessages($e->getMessage(), null, 'errors');
		}
	} elseif ($action == 'krea2factorauth_enable_2fa') {
		if (!$isself) {
			accessforbidden();
		}

		$pendingsecret = empty($_SESSION[$pendingkey]) ? '' : $_SESSION[$pendingkey];
		$totpcode = $manager->getSubmittedTotpCode();

		if ($pendingsecret === '') {
			setEventMessages($langs->trans("Krea2FactorAuthNoPendingSecret"), null, 'errors');
		} elseif (!$manager->verifySecret($pendingsecret, $totpcode)) {
			setEventMessages($langs->trans("Krea2FactorAuthInvalidCode"), null, 'errors');
		} elseif ($manager->enableForUser($targetuser, $pendingsecret) < 0) {
			setEventMessages($manager->error, $manager->errors, 'errors');
		} else {
			unset($_SESSION[$pendingkey]);
			setEventMessages($langs->trans("Krea2FactorAuthEnabled"), null, 'mesgs');
		}
	} elseif ($action == 'krea2factorauth_disable_2fa') {
		if (!$issuperadmin) {
			dol_syslog('Krea2FactorAuth user id '.((int) $user->id).' attempted to disable 2FA without super administrator rights for user id '.((int) $targetuser->id), LOG_WARNING);
			setEventMessages($langs->trans("Krea2FactorAuthOnlySuperAdminCanDisable"), null, 'errors');
		} elseif ($manager->disableForUser($targetuser, $user) < 0) {
			setEventMessages($manager->error, $manager->errors, 'errors');
		} else {
			unset($_SESSION[$pendingkey]);
			setEventMessages($langs->trans("Krea2FactorAuthDisabled"), null, 'mesgs');
		}
	} elseif ($action == 'krea2factorauth_exempt_2fa') {
		$generatedexceptionpassword = '';
		$passwordrotated = false;
		if (!$issuperadmin) {
			accessforbidden();
		} elseif ($manager->exemptUserWithGeneratedPassword($targetuser, $user, $generatedexceptionpassword, $passwordrotated) < 0) {
			setEventMessages($manager->error, $manager->errors, 'errors');
			if ($passwordrotated && $generatedexceptionpassword !== '') {
				krea2factorauthSetExceptionPasswordWarning($targetuser->login, $generatedexceptionpassword, true);
			}
		} else {
			unset($_SESSION[$pendingkey]);
			krea2factorauthSetExceptionPasswordWarning($targetuser->login, $generatedexceptionpassword);
		}
	} elseif ($action == 'krea2factorauth_generate_exception_password') {
		$generatedexceptionpassword = '';
		if (!$issuperadmin) {
			accessforbidden();
		} elseif (!$manager->isMandatoryExemptForUser((int) $targetuser->id)) {
			setEventMessages($langs->trans("Krea2FactorAuthExceptionPasswordRequiresExemptUser"), null, 'errors');
		} elseif ($manager->generateExceptionPasswordForUser($targetuser, $user, $generatedexceptionpassword) < 0) {
			setEventMessages($manager->error, $manager->errors, 'errors');
		} else {
			unset($_SESSION[$pendingkey]);
			krea2factorauthSetExceptionPasswordWarning($targetuser->login, $generatedexceptionpassword);
		}
	} elseif ($action == 'krea2factorauth_require_2fa') {
		if (!$issuperadmin) {
			accessforbidden();
		} elseif ($manager->setMandatoryExemptionForUser($targetuser, $user, false) < 0) {
			setEventMessages($manager->error, $manager->errors, 'errors');
		} else {
			unset($_SESSION[$pendingkey]);
			setEventMessages($langs->trans($manager->isEnabledForUser((int) $targetuser->id) ? "Krea2FactorAuthUserRequiredForLogin" : "Krea2FactorAuthUserRequiredForEnrollment", $targetuser->login), null, 'mesgs');
		}
	}

	header('Location: '.$redirecturl);
	exit;
}

/**
 * Render the 2FA panel embedded at the bottom of Dolibarr's user card.
 *
 * @param User                $targetuser Target user
 * @param array<string,mixed> $options    Rendering options
 * @return string                        HTML
 */
function krea2factorauthRenderUserCardPanel(User $targetuser, array $options = array())
{
	global $conf, $db, $langs, $user;

	$langs->loadLangs(array("users", "krea2factorauth@krea2factorauth"));

	$manager = new Krea2FactorAuthManager($db);
	if (!$manager->isUserVisibleInCurrentEntity((int) $targetuser->id)) {
		return '';
	}

	$pendingkey = krea2factorauthGetPendingSecretSessionKey((int) $targetuser->id);
	$enabled = $manager->isEnabledForUser((int) $targetuser->id);
	$mandatory = $manager->isMandatoryForUser((int) $targetuser->id);
	$exempt = $manager->isMandatoryExemptForUser((int) $targetuser->id);
	$isself = ((int) $targetuser->id === (int) $user->id);
	$issuperadmin = $manager->isSuperAdmin($user);
	$allowselfreset = $manager->isSelfEnrollmentResetAllowed();
	$logourl = krea2factorauthGetDefaultLogoUrl();
	$actionurl = empty($options['actionurl']) ? DOL_URL_ROOT.'/user/card.php?id='.((int) $targetuser->id) : $options['actionurl'];
	$standalone = !empty($options['standalone']);

	if (!$enabled && $isself && !$exempt && empty($_SESSION[$pendingkey])) {
		try {
			$_SESSION[$pendingkey] = $manager->createSecret();
		} catch (Exception $e) {
			setEventMessages($e->getMessage(), null, 'errors');
		}
	}

	$currentauthmode = isset($conf->file->main_authentication) ? $conf->file->main_authentication : '';
	$authparts = array_map('trim', explode(',', $currentauthmode));
	$isauthproviderfirst = (count($authparts) > 0 && $authparts[0] === 'krea2factorauth');
	$recommendedauthmode = 'krea2factorauth,dolibarr';
	$statuslabel = $exempt ? $langs->trans("Krea2FactorAuthPolicyExempt") : ($enabled ? $langs->trans("Enabled") : ($mandatory ? $langs->trans("Krea2FactorAuthPolicyMandatory") : $langs->trans("Disabled")));
	$statusclass = $exempt ? 'krea2factorauth-status-exempt' : ($enabled ? 'krea2factorauth-status-enabled' : ($mandatory ? 'krea2factorauth-status-mandatory' : 'krea2factorauth-status-disabled'));
	$statusicon = '<span class="krea2factorauth-status-dot" aria-hidden="true"></span>';

	$html = '<div id="krea2factorauth-user-panel" class="fichecenter krea2factorauth-user-card-bottom'.($standalone ? ' krea2factorauth-standalone-panel' : '').'">';
	if (!$isauthproviderfirst) {
		$html .= '<div class="warning">';
		$html .= $langs->trans("Krea2FactorAuthNotEnforcedWarning").'<br>';
		$html .= '<code>$dolibarr_main_authentication = \''.dol_escape_htmltag($recommendedauthmode).'\';</code>';
		$html .= '</div><br>';
	}
	if ($mandatory && $isself) {
		$html .= '<div class="warning">'.$langs->trans("Krea2FactorAuthMandatorySetupRequired").'</div><br>';
	}
	$html .= '<section class="krea2factorauth-console">';
	$html .= '<div class="krea2factorauth-console-header">';
	$html .= '<div class="krea2factorauth-brand-lockup">';
	$html .= '<img src="'.dol_escape_htmltag($logourl).'" alt="Krea2FactorAuth" class="krea2factorauth-brand-logo">';
	$html .= '<div>';
	$html .= '<div class="krea2factorauth-eyebrow">'.$langs->trans("Krea2FactorAuthSecurityArea").'</div>';
	$html .= '<div class="krea2factorauth-console-title">'.$langs->trans("Krea2FactorAuthTab").'</div>';
	$html .= '<div class="krea2factorauth-console-subtitle">'.$langs->trans("Krea2FactorAuthSummarySubtitle").'</div>';
	if ($enabled && !$issuperadmin) {
		$html .= '<div class="krea2factorauth-console-note">'.$langs->trans("Krea2FactorAuthSuperAdminDisableOnly").'</div>';
	} elseif (!$enabled && !$isself && $user->admin && !$exempt) {
		$html .= '<div class="krea2factorauth-console-note">'.$langs->trans("Krea2FactorAuthAdminCannotEnable").'</div>';
	}
	$html .= '</div>';
	$html .= '</div>';
	$html .= '<div class="krea2factorauth-status-pill '.$statusclass.'">'.$statusicon.'<span>'.$statuslabel.'</span></div>';
	$html .= '</div>';
	$html .= '</section>';

	if ($enabled && $issuperadmin) {
		$html .= '<br>';
		$html .= '<form id="krea2factorauth-disable" class="krea2factorauth-recovery-panel" method="post" action="'.dol_escape_htmltag($actionurl).'">';
		$html .= '<input type="hidden" name="token" value="'.newToken().'">';
		$html .= '<input type="hidden" name="action" value="krea2factorauth_disable_2fa">';
		$html .= '<div><strong>'.$langs->trans("Krea2FactorAuthDisable").'</strong><span>'.$langs->trans("Krea2FactorAuthSuperAdminRecoveryOnly").'</span></div>';
		$html .= '<button type="submit" class="krea2factorauth-danger-button"><span class="fa fa-unlock"></span>'.$langs->trans("Disable").'</button>';
		$html .= '</form>';
	} elseif (!$enabled && $isself && $exempt) {
		$html .= '<br><div class="krea2factorauth-empty-state">'.dol_escape_htmltag($langs->trans("Krea2FactorAuthUserExempted", $targetuser->login)).'</div>';
	} elseif (!$enabled && $isself) {
		$pendingsecret = empty($_SESSION[$pendingkey]) ? '' : $_SESSION[$pendingkey];
		$qrcode = '';
		if ($pendingsecret !== '') {
			try {
				$qrcode = $manager->getQrCodeDataUri($targetuser->login, $pendingsecret);
			} catch (Exception $e) {
				setEventMessages($e->getMessage(), null, 'errors');
			}
		}

		$html .= '<br>';
		$html .= '<form id="krea2factorauth-enable" class="krea2factorauth-enroll-shell" method="post" action="'.dol_escape_htmltag($actionurl).'">';
			$html .= '<input type="hidden" name="token" value="'.newToken().'">';
			$html .= '<input type="hidden" name="action" value="krea2factorauth_enable_2fa">';
			$html .= '<div class="krea2factorauth-enroll-left">';
			$html .= '<div class="krea2factorauth-step-badge">'.$langs->trans("Krea2FactorAuthSetupStep").'</div>';
			$html .= '<h2>'.$langs->trans("Krea2FactorAuthEnrollmentTitle").'</h2>';
			$html .= '<p>'.$langs->trans("Krea2FactorAuthEnrollmentSubtitle").'</p>';
			$html .= '<div class="krea2factorauth-enroll-instruction"><span class="fa fa-info-circle" aria-hidden="true"></span><span>'.$langs->trans("Krea2FactorAuthAuthenticatorScanInstruction").'</span></div>';
			$html .= '<div class="krea2factorauth-device-pill"><span class="fa fa-clock"></span> '.$langs->trans("Krea2FactorAuthDeviceTypeTotp").'</div>';
			$html .= '</div>';
			$html .= '<div class="krea2factorauth-enroll-right">';
		if ($qrcode !== '') {
			$html .= '<div class="krea2factorauth-qr-tile">';
			$html .= '<img src="'.dol_escape_htmltag($qrcode).'" alt="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthQrCode")).'">';
			$html .= '<span>'.$langs->trans("Krea2FactorAuthQrCode").'</span>';
			$html .= '</div>';
		}
		$html .= '<div class="krea2factorauth-secret-block">';
		$html .= '<span>'.$langs->trans("Krea2FactorAuthManualSecret").'</span>';
		$html .= '<code>'.dol_escape_htmltag($pendingsecret).'</code>';
		$html .= '</div>';
		$html .= krea2factorauthRenderTotpCodeInput('totp_code', 'totp_digit', 'krea2factorauth-setup-code', $langs->trans("Krea2FactorAuthVerificationCode"));
		$html .= '<button type="submit" class="krea2factorauth-primary-button"><span class="fa fa-check"></span>'.$langs->trans("Krea2FactorAuthActivateButton").'</button>';
		$html .= '</div>';
		$html .= '</form>';

		if ($allowselfreset) {
			$html .= '<form class="krea2factorauth-secondary-actions" method="post" action="'.dol_escape_htmltag($actionurl).'" data-krea2factorauth-reset-confirm="1" data-confirm-title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthResetConfirmTitle")).'" data-confirm-text="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthResetConfirmText")).'" data-confirm-action="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthResetConfirmAction")).'" data-confirm-cancel="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
			$html .= '<input type="hidden" name="token" value="'.newToken().'">';
			$html .= '<input type="hidden" name="action" value="krea2factorauth_regenerate_secret">';
			$html .= '<button type="submit" class="krea2factorauth-secondary-button"><span class="fa fa-sync"></span>'.$langs->trans("Krea2FactorAuthRegenerateSecret").'</button>';
			$html .= '</form>';
		} else {
			$html .= '<div class="krea2factorauth-empty-state">'.$langs->trans("Krea2FactorAuthSelfResetDisabled").'</div>';
		}
	} elseif (!$enabled && !$exempt) {
		$html .= '<br><div class="krea2factorauth-empty-state">'.$langs->trans($user->admin ? "Krea2FactorAuthAdminCannotEnable" : "Krea2FactorAuthOnlyUserCanEnroll").'</div>';
		if ($issuperadmin) {
			$html .= '<div class="krea2factorauth-secondary-actions">';
			$html .= '<form class="krea2factorauth-inline-action" method="post" action="'.dol_escape_htmltag($actionurl).'" data-krea2factorauth-reset-confirm="1" data-confirm-title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthExemptConfirmTitle")).'" data-confirm-text="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthExemptConfirmText")).'" data-confirm-action="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthExemptConfirmAction")).'" data-confirm-cancel="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
			$html .= '<input type="hidden" name="token" value="'.newToken().'">';
			$html .= '<input type="hidden" name="action" value="krea2factorauth_exempt_2fa">';
			$html .= '<button type="submit" class="krea2factorauth-secondary-button"><span class="fa fa-user-check"></span>'.$langs->trans("Krea2FactorAuthActionExempt").'</button>';
			$html .= '</form>';
			$html .= '</div>';
		} elseif ($user->admin) {
			$html .= '<div class="krea2factorauth-secondary-actions">';
			$html .= '<a class="krea2factorauth-secondary-button" href="'.dol_escape_htmltag(krea2factorauthGetEnrollmentPageUrl()).'"><span class="fa fa-user-shield"></span>'.$langs->trans("Krea2FactorAuthOpenMySetup").'</a>';
			$html .= '</div>';
		}
	}

	$html .= '</div>';

	return $html;
}
