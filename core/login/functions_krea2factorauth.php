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
 * \file    krea2factorauth/core/login/functions_krea2factorauth.php
 * \ingroup krea2factorauth
 * \brief   Authentication provider enforcing TOTP after primary password validation.
 */

require_once dirname(__DIR__, 2).'/class/krea2factorauthmanager.class.php';

/**
 * Check validity of user/password/entity and enforce TOTP when configured.
 *
 * @param string              $usertotest     Login
 * @param string              $passwordtotest Password
 * @param int                 $entitytotest   Entity
 * @param 'api'|'dav'|'ws'|'' $context        Authentication context
 * @return string                             Login if OK, empty string if KO, --bad-login-validity-- for hard failure
 */
function check_user_password_krea2factorauth($usertotest, $passwordtotest, $entitytotest = 1, $context = '')
{
	global $db, $langs, $conf;

	$manager = new Krea2FactorAuthManager($db);

	dol_syslog("functions_krea2factorauth::check_user_password_krea2factorauth usertotest=".$usertotest." entitytotest=".$entitytotest." context=".$context);

	$isgooglestart = $manager->isGoogleLoginStartRequest();
	$isgooglereturn = $manager->isGoogleLoginReturnRequest();
	if ($manager->getPendingGoogleLogin($usertotest, $entitytotest) !== '') {
		$manager->clearGoogleLoginPendingChallenge();
	}
	$isgoogleflow = ($isgooglestart || $isgooglereturn);

	if ($isgoogleflow && (!$manager->isGoogleTwoFactorAvailable() || !$manager->isModuleActiveForEntity($entitytotest))) {
		$langs->loadLangs(array('main', 'krea2factorauth@krea2factorauth'));
		$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthGoogleLoginUnavailable");
		dol_syslog("functions_krea2factorauth::check_user_password_krea2factorauth refused unavailable Google login for entity ".$entitytotest, LOG_WARNING);
		$manager->clearGoogleLoginPendingChallenge();
		sleep(1);

		return '--bad-login-validity--';
	}

	$login = $manager->checkPrimaryLogin($usertotest, $passwordtotest, $entitytotest, $context);
	if (!$login || $login === '--bad-login-validity--') {
		if ($isgooglereturn) {
			$manager->clearGoogleLoginPendingChallenge();
			unset($_SESSION['googleoauth_receivedlogin']);
			$langs->loadLangs(array('main', 'krea2factorauth@krea2factorauth'));
			$_SESSION['dol_loginmesg'] = $langs->transnoentitiesnoconv('Krea2FactorAuthGoogleOAuthReturnStateFailed');
			return '--bad-login-validity--';
		}
		return $login;
	}

	$authenticateduser = $manager->fetchAuthenticatedUser($login, $entitytotest);
	if (!is_object($authenticateduser) || empty($authenticateduser->id)) {
		$manager->clearGoogleLoginPendingChallenge();
		unset($_SESSION['googleoauth_receivedlogin']);
		dol_syslog('functions_krea2factorauth::check_user_password_krea2factorauth could not load authenticated user', LOG_ERR);
		return '--bad-login-validity--';
	}

	// Request parameters describe the flow; only provider and callback session proof authenticate it.
	if ($isgooglereturn && (empty($conf->authmode) || $conf->authmode !== 'googleoauth'
		|| $manager->getGoogleLoginReturnEmail($authenticateduser, $entitytotest) === '')) {
		$manager->clearGoogleLoginPendingChallenge();
		unset($_SESSION['googleoauth_receivedlogin']);
		$langs->loadLangs(array('main', 'krea2factorauth@krea2factorauth'));
		$_SESSION['dol_loginmesg'] = $langs->transnoentitiesnoconv('Krea2FactorAuthGoogleOAuthReturnStateFailed');
		dol_syslog('functions_krea2factorauth::check_user_password_krea2factorauth refused unverified Google return', LOG_WARNING);
		return '--bad-login-validity--';
	}

	$userstatus = isset($authenticateduser->status) ? (int) $authenticateduser->status : (int) $authenticateduser->statut;
	if ($userstatus < 1) {
		$langs->loadLangs(array('main', 'errors'));
		$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorLoginDisabled");
		dol_syslog("functions_krea2factorauth::check_user_password_krea2factorauth refused inactive user id ".((int) $authenticateduser->id), LOG_NOTICE);
		if ($isgoogleflow) {
			$manager->clearGoogleLoginPendingChallenge();
		}
		sleep(1);

		return '--bad-login-validity--';
	}

	if (!$manager->isModuleActiveForEntity($entitytotest)) {
		dol_syslog("functions_krea2factorauth::check_user_password_krea2factorauth selected entity has inactive module, accepted primary auth without TOTP", LOG_INFO);
		return $login;
	}

	if ($isgooglereturn && !$manager->isGoogleUserAllowed($authenticateduser, $entitytotest)) {
		$langs->loadLangs(array('main', 'krea2factorauth@krea2factorauth'));
		$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv($manager->isGoogleLoginEmailExtrafieldEnabled() ? "Krea2FactorAuthGoogleLoginEmailDenied" : "Krea2FactorAuthGoogleLoginDomainDenied");
		dol_syslog("functions_krea2factorauth::check_user_password_krea2factorauth refused Google login policy for user id ".((int) $authenticateduser->id), LOG_WARNING);
		$manager->clearGoogleLoginPendingChallenge();
		sleep(1);

		return '--bad-login-validity--';
	}

	if ($manager->isLoginLockedForUser((int) $authenticateduser->id, $entitytotest)) {
		$langs->loadLangs(array('main', 'krea2factorauth@krea2factorauth'));
		$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthLoginLockedMessage");
		dol_syslog("functions_krea2factorauth::check_user_password_krea2factorauth blocked locked user id ".((int) $authenticateduser->id), LOG_WARNING);
		if ($isgooglereturn) {
			$manager->clearGoogleLoginPendingChallenge();
		}
		sleep(1);

		return '--bad-login-validity--';
	}

	if (!$isgooglereturn && $manager->isGoogleTwoFactorMandatoryForUser((int) $authenticateduser->id, $entitytotest)) {
		$langs->loadLangs(array('main', 'krea2factorauth@krea2factorauth'));
		$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthGoogleLoginRequired");
		dol_syslog("functions_krea2factorauth::check_user_password_krea2factorauth refused password login because Google 2FA is mandatory for user id ".((int) $authenticateduser->id), LOG_WARNING);
		sleep(1);

		return '--bad-login-validity--';
	}

	if ($context !== '' && !$manager->isNonWebEnforcementEnabled()) {
		dol_syslog("functions_krea2factorauth::check_user_password_krea2factorauth non-web context accepted by primary auth without TOTP", LOG_INFO);
		return $login;
	}

	if ($isgooglereturn) {
		$manager->clearGoogleLoginPendingChallenge();
		dol_syslog("functions_krea2factorauth::check_user_password_krea2factorauth accepted Google login without local TOTP for user id ".((int) $authenticateduser->id), LOG_INFO);
		return $login;
	}

	if ($manager->isMandatoryExemptForUser((int) $authenticateduser->id, $entitytotest)) {
		dol_syslog("functions_krea2factorauth::check_user_password_krea2factorauth accepted explicit 2FA-exempt user id ".((int) $authenticateduser->id)." without local TOTP", LOG_WARNING);
		return $login;
	}

	if (!$manager->isEnabledForUser((int) $authenticateduser->id, $entitytotest)) {
		return $login;
	}

	$totpcode = $manager->getSubmittedTotpCode('totp_code', 'totp_digit', 3);
	if (!$manager->verifyCodeForUser((int) $authenticateduser->id, $entitytotest, $totpcode)) {
		$langs->loadLangs(array('main', 'krea2factorauth@krea2factorauth'));
		$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv($manager->isTotpAttemptLockedForUser((int) $authenticateduser->id, $entitytotest) ? "Krea2FactorAuthTooManyAttempts" : "Krea2FactorAuthInvalidCode");
		dol_syslog("functions_krea2factorauth::check_user_password_krea2factorauth TOTP check failed for user id ".((int) $authenticateduser->id), LOG_NOTICE);
		sleep(1);

		return '--bad-login-validity--';
	}

	dol_syslog("functions_krea2factorauth::check_user_password_krea2factorauth TOTP check accepted for user id ".((int) $authenticateduser->id), LOG_INFO);
	$manager->clearGoogleLoginPendingChallenge();

	return $login;
}
