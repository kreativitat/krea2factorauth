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
 * \file    krea2factorauth/class/krea2factorauthmanager.class.php
 * \ingroup krea2factorauth
 * \brief   Shared TOTP enrollment and verification service.
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

/**
 * Shared service for Krea2FactorAuth.
 */
class Krea2FactorAuthManager
{
	public const PARAM_SECRET = 'KREA2FACTORAUTH_TOTP_SECRET';
	public const PARAM_ENABLED_AT = 'KREA2FACTORAUTH_TOTP_ENABLED_AT';
	public const PARAM_MANDATORY_REQUIRED = 'KREA2FACTORAUTH_MANDATORY_REQUIRED';
	public const PARAM_MANDATORY_REQUIRED_AT = 'KREA2FACTORAUTH_MANDATORY_REQUIRED_AT';
	public const PARAM_MANDATORY_REQUIRED_BY = 'KREA2FACTORAUTH_MANDATORY_REQUIRED_BY';
	public const PARAM_MANDATORY_EXEMPT = 'KREA2FACTORAUTH_MANDATORY_EXEMPT';
	public const PARAM_MANDATORY_EXEMPT_AT = 'KREA2FACTORAUTH_MANDATORY_EXEMPT_AT';
	public const PARAM_MANDATORY_EXEMPT_BY = 'KREA2FACTORAUTH_MANDATORY_EXEMPT_BY';
	public const PARAM_LOGIN_LOCKED = 'KREA2FACTORAUTH_LOGIN_LOCKED';
	public const PARAM_LOGIN_LOCKED_AT = 'KREA2FACTORAUTH_LOGIN_LOCKED_AT';
	public const PARAM_LOGIN_LOCKED_BY = 'KREA2FACTORAUTH_LOGIN_LOCKED_BY';
	public const PARAM_POLICY_LISTED = 'KREA2FACTORAUTH_POLICY_LISTED';
	public const PARAM_TOTP_FAILURE_COUNT = 'KREA2FACTORAUTH_TOTP_FAILURE_COUNT';
	public const PARAM_TOTP_FAILURE_FIRST_AT = 'KREA2FACTORAUTH_TOTP_FAILURE_FIRST_AT';
	public const PARAM_TOTP_LOCKED_UNTIL = 'KREA2FACTORAUTH_TOTP_LOCKED_UNTIL';
	public const PARAM_TOTP_LAST_TIMESLICE = 'KREA2FACTORAUTH_TOTP_LAST_TIMESLICE';
	public const PARAM_PASSWORD_EXPIRATION_ENABLED = 'KREA2FACTORAUTH_PASSWORD_EXPIRATION_ENABLED';
	public const PARAM_PASSWORD_EXPIRATION_ENABLED_AT = 'KREA2FACTORAUTH_PASSWORD_EXPIRATION_ENABLED_AT';
	public const PARAM_PASSWORD_EXPIRATION_ENABLED_BY = 'KREA2FACTORAUTH_PASSWORD_EXPIRATION_ENABLED_BY';
	public const DEFAULT_PRIMARY_AUTH_MODES = 'dolibarr';
	public const ENROLLMENT_PROMPT_OPTIONAL = 'optional';
	public const ENROLLMENT_PROMPT_MANDATORY = 'mandatory';
	public const ENROLLMENT_PROMPT_OFF = 'off';
	public const PASSWORD_EXPIRATION_MAX_DAYS_CONST = 'KREA2FACTORAUTH_PASSWORD_EXPIRATION_MAX_DAYS';
	public const PASSWORD_HISTORY_LIMIT_CONST = 'KREA2FACTORAUTH_PASSWORD_HISTORY_LIMIT';
	public const PASSWORD_EXPIRATION_DEFAULT_MAX_DAYS = 90;
	public const PASSWORD_HISTORY_DEFAULT_LIMIT = 5;
	public const PASSWORD_EXPIRATION_MIN_DAYS = 1;
	public const PASSWORD_EXPIRATION_MAX_DAYS = 3650;
	public const PASSWORD_HISTORY_MIN_LIMIT = 1;
	public const PASSWORD_HISTORY_MAX_LIMIT = 24;
	public const TOTP_FAILURE_LIMIT = 5;
	public const TOTP_FAILURE_WINDOW_SECONDS = 900;
	public const TOTP_LOCK_SECONDS = 300;
	public const LOGIN_LOGO_CONST = 'KREA2FACTORAUTH_LOGIN_LOGO_FILE';
	public const LEGACY_LOGIN_COMPANY_ICON_CONST = 'KREA2FACTORAUTH_LOGIN_COMPANY_ICON_FILE';
	public const LOGIN_COMPANY_ICON_CONST = self::LEGACY_LOGIN_COMPANY_ICON_CONST;
	public const LOGIN_LOGO_MAX_BYTES = 524288;
	public const LOGIN_LOGO_MIN_WIDTH = 64;
	public const LOGIN_LOGO_MIN_HEIGHT = 24;
	public const LOGIN_LOGO_MAX_PIXELS = 3000;
	public const DEFAULT_LOGIN_BACKGROUND_COLOR = '#eef3f7';
	public const LOGIN_BACKGROUND_COLOR_CONST = 'KREA2FACTORAUTH_LOGIN_BACKGROUND_COLOR';
	public const LOGIN_BACKGROUND_COLORS_CONST = 'KREA2FACTORAUTH_LOGIN_BACKGROUND_COLORS';
	public const GLOBAL_USER_PARAM_ENTITY = 0;
	public const GLOBAL_CONFIG_ENTITY = 0;
	public const GLOBAL_MIGRATION_DONE = 'KREA2FACTORAUTH_GLOBAL_STATE_MIGRATED';
	public const ACTIVATION_SCOPE_CONST = 'KREA2FACTORAUTH_ACTIVATION_SCOPE';
	public const GOOGLE_LOGIN_ENABLED_CONST = 'KREA2FACTORAUTH_GOOGLE_LOGIN_ENABLED';
	public const GOOGLE_LOGIN_ALLOWED_DOMAINS_CONST = 'KREA2FACTORAUTH_GOOGLE_LOGIN_ALLOWED_DOMAINS';
	public const GOOGLE_LOGIN_PENDING_SESSION = 'KREA2FACTORAUTH_GOOGLE_LOGIN_PENDING';
	public const GOOGLE_LOGIN_SUBMITTED_TOTP_SESSION = 'KREA2FACTORAUTH_GOOGLE_LOGIN_SUBMITTED_TOTP';
	public const GOOGLE_LOGIN_PENDING_TTL = 300;
	public const GOOGLE_OAUTH_CONST_PREFIX = 'OAUTH_GOOGLE-Login';
	public const GOOGLE_OAUTH_CREDENTIAL_ENTITY = 1;
	public const GOOGLE_OAUTH_SCOPES = 'openid,email,profile';
	public const GOOGLE_LOGIN_USE_EXTRAFIELD_EMAIL_CONST = 'KREA2FACTORAUTH_GOOGLE_LOGIN_USE_EXTRAFIELD_EMAIL';
	public const GOOGLE_LOGIN_EMAIL_EXTRAFIELD = 'krea2factorauth_email';
	public const GOOGLE_LOGIN_RETURN_EMAIL_SESSION = 'KREA2FACTORAUTH_GOOGLE_LOGIN_RETURN_EMAIL';
	public const LOGIN_RETURN_FORM_FIELD = 'krea2factorauth_return_url';
	public const LOGIN_RETURN_SESSION = 'KREA2FACTORAUTH_POST_LOGIN_RETURN_URL';

	/**
	 * @var DoliDB
	 */
	public $db;

	/**
	 * @var string
	 */
	public $error = '';

	/**
	 * @var string[]
	 */
	public $errors = array();

	/**
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Return configured TOTP issuer.
	 *
	 * @return string
	 */
	public function getIssuer()
	{
		$defaultissuer = (defined('DOL_APPLICATION_TITLE') ? DOL_APPLICATION_TITLE : 'Dolibarr');
		return $this->getGlobalConstString('KREA2FACTORAUTH_ISSUER', $defaultissuer);
	}

	/**
	 * Return whether a Dolibarr user is a super administrator.
	 *
	 * Dolibarr represents super administrators as admin users attached to entity 0.
	 *
	 * @param User $currentuser Current user
	 * @return bool
	 */
	public function isSuperAdmin(User $currentuser)
	{
		return !empty($currentuser->admin) && empty($currentuser->entity);
	}

	/**
	 * Return password providers delegated by this 2FA provider.
	 *
	 * @return string[]
	 */
	public function getPrimaryAuthModes()
	{
		$configured = $this->getGlobalConstString('KREA2FACTORAUTH_PRIMARY_AUTH_MODES', self::DEFAULT_PRIMARY_AUTH_MODES);
		$modes = array();
		foreach (explode(',', $configured) as $mode) {
			$mode = trim($mode);
			if ($mode === '' || $mode === 'krea2factorauth' || $mode === 'googleoauth') {
				continue;
			}
			$modes[] = $mode;
		}

		if ($this->isGoogleLoginEnabled()) {
			array_unshift($modes, 'googleoauth');
		}

		$modes = array_values(array_unique($modes));
		return count($modes) ? $modes : array('dolibarr');
	}

	/**
	 * Return accepted TOTP time-window discrepancy.
	 *
	 * @return int
	 */
	public function getCodeWindow()
	{
		$window = $this->getGlobalConstInt('KREA2FACTORAUTH_CODE_WINDOW', 1);
		if ($window < 0) {
			return 0;
		}
		if ($window > 3) {
			return 3;
		}

		return $window;
	}

	/**
	 * Return the post-login enrollment prompt mode.
	 *
	 * @return string optional|mandatory|off
	 */
	public function getEnrollmentPromptMode()
	{
		$mode = strtolower(trim($this->getGlobalConstString('KREA2FACTORAUTH_ENROLLMENT_PROMPT_MODE', '')));
		if (in_array($mode, array(self::ENROLLMENT_PROMPT_OPTIONAL, self::ENROLLMENT_PROMPT_MANDATORY, self::ENROLLMENT_PROMPT_OFF), true)) {
			return $mode;
		}

		return $this->getGlobalConstInt('KREA2FACTORAUTH_SHOW_OPTIONAL_MODAL', 1) ? self::ENROLLMENT_PROMPT_OPTIONAL : self::ENROLLMENT_PROMPT_OFF;
	}

	/**
	 * Return whether the optional post-login enrollment modal is enabled.
	 *
	 * @return bool
	 */
	public function isOptionalEnrollmentModalEnabled()
	{
		return $this->getEnrollmentPromptMode() === self::ENROLLMENT_PROMPT_OPTIONAL;
	}

	/**
	 * Return whether unenrolled users must complete 2FA enrollment before continuing.
	 *
	 * @return bool
	 */
	public function isMandatoryEnrollmentPromptEnabled()
	{
		return $this->getEnrollmentPromptMode() === self::ENROLLMENT_PROMPT_MANDATORY;
	}

	/**
	 * Return whether local TOTP enrollment can be required.
	 *
	 * @return bool
	 */
	public function isTotpEnrollmentAvailable()
	{
		return $this->getEnrollmentPromptMode() !== self::ENROLLMENT_PROMPT_OFF;
	}

	/**
	 * Return whether users may regenerate their pending enrollment secret.
	 *
	 * @return bool
	 */
	public function isSelfEnrollmentResetAllowed()
	{
		return (bool) $this->getGlobalConstInt('KREA2FACTORAUTH_ALLOW_SELF_RESET', 1);
	}

	/**
	 * Return whether TOTP is enforced for non-web authentication contexts.
	 *
	 * @return bool
	 */
	public function isNonWebEnforcementEnabled()
	{
		return (bool) $this->getGlobalConstInt('KREA2FACTORAUTH_ENFORCE_NON_WEB', 0);
	}

	/**
	 * Return the configured maximum password age in days.
	 *
	 * @return int
	 */
	public function getPasswordExpirationMaxDays()
	{
		$days = $this->getGlobalConstInt(self::PASSWORD_EXPIRATION_MAX_DAYS_CONST, self::PASSWORD_EXPIRATION_DEFAULT_MAX_DAYS);
		if ($days < self::PASSWORD_EXPIRATION_MIN_DAYS) {
			return self::PASSWORD_EXPIRATION_DEFAULT_MAX_DAYS;
		}
		if ($days > self::PASSWORD_EXPIRATION_MAX_DAYS) {
			return self::PASSWORD_EXPIRATION_MAX_DAYS;
		}

		return $days;
	}

	/**
	 * Return the number of password hashes retained per managed user.
	 *
	 * @return int
	 */
	public function getPasswordHistoryLimit()
	{
		$limit = $this->getGlobalConstInt(self::PASSWORD_HISTORY_LIMIT_CONST, self::PASSWORD_HISTORY_DEFAULT_LIMIT);
		if ($limit < self::PASSWORD_HISTORY_MIN_LIMIT) {
			return self::PASSWORD_HISTORY_DEFAULT_LIMIT;
		}
		if ($limit > self::PASSWORD_HISTORY_MAX_LIMIT) {
			return self::PASSWORD_HISTORY_MAX_LIMIT;
		}

		return $limit;
	}

	/**
	 * Return whether password expiration is enabled for a user.
	 *
	 * Password expiration policy is global per Dolibarr user, matching the
	 * module's 2FA policy storage model.
	 *
	 * @param int        $userid User id
	 * @param int|string $entity Deprecated entity parameter
	 * @return bool
	 */
	public function isPasswordExpirationEnabledForUser($userid, $entity = 0)
	{
		return $this->getUserParamValue($userid, self::PARAM_PASSWORD_EXPIRATION_ENABLED, $entity) === '1';
	}

	/**
	 * Return the timestamp when password expiration was enabled for a user.
	 *
	 * @param int        $userid User id
	 * @param int|string $entity Deprecated entity parameter
	 * @return int
	 */
	public function getPasswordExpirationEnabledAtForUser($userid, $entity = 0)
	{
		return (int) $this->getUserParamValue($userid, self::PARAM_PASSWORD_EXPIRATION_ENABLED_AT, $entity);
	}

	/**
	 * Return the user's last password validation timestamp.
	 *
	 * Dolibarr updates datelastpassvalidation in User::setPassword(). When older
	 * installations have no value yet, the module activation timestamp is used as
	 * the baseline so enabling the policy does not create a null expiry date.
	 *
	 * @param int $userid User id
	 * @return int
	 */
	public function getPasswordLastChangedAtForUser($userid)
	{
		$userid = (int) $userid;
		if ($userid <= 0) {
			return 0;
		}

		$sql = "SELECT u.datelastpassvalidation";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE u.rowid = ".$userid;
		$sql .= " AND u.entity IN (".getEntity('user').")";
		$sql .= " LIMIT 1";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::getPasswordLastChangedAtForUser '.$this->error, LOG_ERR);
			return 0;
		}

		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);
		if (!$obj) {
			return 0;
		}

		$lastchangedat = empty($obj->datelastpassvalidation) ? 0 : $this->db->jdate($obj->datelastpassvalidation);
		if ($lastchangedat > 0) {
			return $lastchangedat;
		}

		return $this->getPasswordExpirationEnabledAtForUser($userid);
	}

	/**
	 * Return the password expiration timestamp for a user.
	 *
	 * @param int $userid User id
	 * @return int        Expiration timestamp, 0 when policy is inactive
	 */
	public function getPasswordExpiresAtForUser($userid)
	{
		if (!$this->isPasswordExpirationEnabledForUser($userid)) {
			return 0;
		}

		$lastchangedat = $this->getPasswordLastChangedAtForUser($userid);
		if ($lastchangedat <= 0) {
			return 0;
		}

		return dol_time_plus_duree($lastchangedat, $this->getPasswordExpirationMaxDays(), 'd');
	}

	/**
	 * Return whether a user's password is expired by module policy.
	 *
	 * @param int $userid User id
	 * @return bool
	 */
	public function isPasswordExpiredForUser($userid)
	{
		$expiresat = $this->getPasswordExpiresAtForUser($userid);

		return $expiresat > 0 && $expiresat <= dol_now();
	}

	/**
	 * Enable password expiration for a user and seed history with the current hash.
	 *
	 * @param User $targetuser  Target user
	 * @param User $currentuser Current user authorizing the policy change
	 * @return int              >0 if OK, <0 if KO
	 */
	public function enablePasswordExpirationForUser(User $targetuser, User $currentuser)
	{
		if (!$this->isSuperAdmin($currentuser)) {
			$this->error = 'Only a super administrator can enable password expiration.';
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::enablePasswordExpirationForUser denied for user id '.((int) $currentuser->id).' on target user id '.((int) $targetuser->id), LOG_WARNING);
			return -1;
		}

		$params = array(
			self::PARAM_PASSWORD_EXPIRATION_ENABLED => array('value' => '1', 'forcevalue' => 1),
			self::PARAM_PASSWORD_EXPIRATION_ENABLED_AT => array('value' => (string) dol_now(), 'forcevalue' => 1),
			self::PARAM_PASSWORD_EXPIRATION_ENABLED_BY => array('value' => (string) $currentuser->id, 'forcevalue' => 1),
			self::PARAM_POLICY_LISTED => array('value' => '1', 'forcevalue' => 1),
		);

		if ($this->setGlobalUserParams($targetuser, $params) < 0) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::enablePasswordExpirationForUser '.$this->error, LOG_ERR);
			return -1;
		}

		if ($this->recordCurrentPasswordHashForUser($targetuser, $currentuser, 'activation') < 0) {
			$historyerror = $this->error;
			$historyerrors = $this->errors;
			$rollbackparams = array(
				self::PARAM_PASSWORD_EXPIRATION_ENABLED => '',
				self::PARAM_PASSWORD_EXPIRATION_ENABLED_AT => '',
				self::PARAM_PASSWORD_EXPIRATION_ENABLED_BY => '',
			);
			$this->setGlobalUserParams($targetuser, $rollbackparams);
			$this->error = $historyerror;
			$this->errors = $historyerrors;
			return -1;
		}

		dol_syslog('Krea2FactorAuthManager::enablePasswordExpirationForUser enabled password expiration for user id '.((int) $targetuser->id), LOG_WARNING);
		return 1;
	}

	/**
	 * Disable password expiration for a user.
	 *
	 * @param User $targetuser  Target user
	 * @param User $currentuser Current user authorizing the policy change
	 * @return int              >0 if OK, <0 if KO
	 */
	public function disablePasswordExpirationForUser(User $targetuser, User $currentuser)
	{
		if (!$this->isSuperAdmin($currentuser)) {
			$this->error = 'Only a super administrator can disable password expiration.';
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::disablePasswordExpirationForUser denied for user id '.((int) $currentuser->id).' on target user id '.((int) $targetuser->id), LOG_WARNING);
			return -1;
		}
		if (!$this->canDisablePasswordExpirationForUser((int) $targetuser->id)) {
			$this->error = 'Password expiration cannot be disabled until the user has active local TOTP or configured Google 2FA and is not exempt.';
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::disablePasswordExpirationForUser refused insecure final state for user id '.((int) $targetuser->id), LOG_WARNING);
			return -1;
		}

		$params = array(
			self::PARAM_PASSWORD_EXPIRATION_ENABLED => '',
			self::PARAM_PASSWORD_EXPIRATION_ENABLED_AT => '',
			self::PARAM_PASSWORD_EXPIRATION_ENABLED_BY => '',
			self::PARAM_POLICY_LISTED => array('value' => '1', 'forcevalue' => 1),
		);

		if ($this->setGlobalUserParams($targetuser, $params) < 0) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::disablePasswordExpirationForUser '.$this->error, LOG_ERR);
			return -1;
		}

		dol_syslog('Krea2FactorAuthManager::disablePasswordExpirationForUser disabled password expiration for user id '.((int) $targetuser->id), LOG_WARNING);
		return 1;
	}

	/**
	 * Return whether password expiration can be disabled for a user.
	 *
	 * The final state must keep an effective second factor. Explicit 2FA
	 * exemption means local TOTP and Google 2FA do not count until the
	 * exemption is removed.
	 *
	 * @param int        $userid User id
	 * @param int|string $entity Deprecated entity parameter
	 * @return bool
	 */
	public function canDisablePasswordExpirationForUser($userid, $entity = 0)
	{
		$userid = (int) $userid;
		$totpavailable = $this->isTotpEnrollmentAvailable();
		return $userid > 0
			&& !$this->isMandatoryExemptForUser($userid, $entity)
			&& (($totpavailable && $this->isEnabledForUser($userid, $entity)) || $this->isGoogleTwoFactorConfiguredForUser($userid, $entity));
	}

	/**
	 * Return whether TOTP must be required before password expiration can be disabled.
	 *
	 * @param int        $userid User id
	 * @param int|string $entity Deprecated entity parameter
	 * @return bool
	 */
	public function needsTotpFallbackAfterPasswordExpirationDisabled($userid, $entity = 0)
	{
		$userid = (int) $userid;
		return $userid > 0
			&& $this->isTotpEnrollmentAvailable()
			&& !$this->isMandatoryExemptForUser($userid, $entity)
			&& !$this->canDisablePasswordExpirationForUser($userid, $entity);
	}

	/**
	 * Change an expired password after verifying the current local password.
	 *
	 * @param User   $targetuser      Target/current user
	 * @param User   $currentuser     Current user authorizing the password change
	 * @param string $currentpassword Current clear-text password
	 * @param string $newpassword     New clear-text password
	 * @return int                    >0 if OK, <0 if KO
	 */
	public function changeExpiredPasswordForUser(User $targetuser, User $currentuser, $currentpassword, $newpassword)
	{
		if ((int) $targetuser->id !== (int) $currentuser->id) {
			$this->error = 'Expired passwords can only be changed by the signed-in user.';
			$this->errors[] = $this->error;
			return -1;
		}
		if (!$this->isPasswordExpirationEnabledForUser((int) $targetuser->id)) {
			$this->error = 'Password expiration is not enabled for this user.';
			$this->errors[] = $this->error;
			return -1;
		}
		if (!$this->verifyCurrentPasswordForUser($targetuser, $currentpassword)) {
			$this->error = 'Current password is not valid.';
			$this->errors[] = $this->error;
			return -1;
		}
		if ($this->isPasswordReusedForUser((int) $targetuser->id, $newpassword)) {
			$this->error = 'This password was already used recently. Choose a different password.';
			$this->errors[] = $this->error;
			return -1;
		}

		$result = $targetuser->setPassword($currentuser, $newpassword, 0, 0, 0, 0, 1);
		if ((is_int($result) && $result < 0) || $result === 0) {
			$this->error = !empty($targetuser->error) ? $targetuser->error : 'Unable to change expired password.';
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::changeExpiredPasswordForUser '.$this->error, LOG_ERR);
			return -1;
		}

		if ($this->recordCurrentPasswordHashForUser($targetuser, $currentuser, 'forced_change') < 0) {
			return -1;
		}

		return 1;
	}

	/**
	 * Verify a clear-text password against a loaded Dolibarr user's local hash.
	 *
	 * @param User   $targetuser Target user
	 * @param string $password   Clear-text password
	 * @return bool
	 */
	public function verifyCurrentPasswordForUser(User $targetuser, $password)
	{
		$password = (string) $password;
		if ($password === '') {
			return false;
		}

		if (empty($targetuser->pass_indatabase_crypted)) {
			$loadeduser = new User($this->db);
			if ($loadeduser->fetch((int) $targetuser->id, '', '', 0) <= 0) {
				return false;
			}
			$targetuser = $loadeduser;
		}

		$passcrypted = (string) $targetuser->pass_indatabase_crypted;
		if ($passcrypted !== '' && dol_verifyHash($password, $passcrypted, '0')) {
			return true;
		}

		$passclear = isset($targetuser->pass_indatabase) ? (string) $targetuser->pass_indatabase : (isset($targetuser->pass) ? (string) $targetuser->pass : '');
		return $passcrypted === '' && $passclear !== '' && hash_equals($passclear, $password);
	}

	/**
	 * Return whether a clear-text password matches the current or retained hashes.
	 *
	 * @param int    $userid   User id
	 * @param string $password Clear-text password
	 * @return bool
	 */
	public function isPasswordReusedForUser($userid, $password)
	{
		$userid = (int) $userid;
		$password = (string) $password;
		if ($userid <= 0 || $password === '') {
			return false;
		}

		$hashes = array();
		$sql = "SELECT u.pass_crypted";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE u.rowid = ".$userid;
		$sql .= " AND u.entity IN (".getEntity('user').")";
		$sql .= " LIMIT 1";
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj && !empty($obj->pass_crypted)) {
				$hashes[] = (string) $obj->pass_crypted;
			}
			$this->db->free($resql);
		} else {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::isPasswordReusedForUser '.$this->error, LOG_ERR);
			return true;
		}

		$sql = "SELECT h.password_hash";
		$sql .= " FROM ".MAIN_DB_PREFIX."krea2factorauth_password_history as h";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = h.fk_user";
		$sql .= " WHERE h.fk_user = ".$userid;
		$sql .= " AND h.entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND u.entity IN (".getEntity('user').")";
		$sql .= " ORDER BY h.date_creation DESC, h.rowid DESC";
		$sql .= $this->db->plimit($this->getPasswordHistoryLimit(), 0);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::isPasswordReusedForUser '.$this->error, LOG_ERR);
			return true;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			if (!empty($obj->password_hash)) {
				$hashes[] = (string) $obj->password_hash;
			}
		}
		$this->db->free($resql);

		foreach (array_unique($hashes) as $hash) {
			if ($hash !== '' && dol_verifyHash($password, $hash, '0')) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Record a user's current Dolibarr password hash in module password history.
	 *
	 * @param User   $targetuser        Target user
	 * @param User   $currentuser       Current user authorizing the change
	 * @param string $source            Source label
	 * @param bool   $inopentransaction True when called from inside a Dolibarr transaction
	 * @return int                      >0 if OK, <0 if KO
	 */
	public function recordCurrentPasswordHashForUser(User $targetuser, User $currentuser, $source = '', $inopentransaction = false)
	{
		$userid = (int) $targetuser->id;
		if ($userid <= 0) {
			return -1;
		}

		$passwordhash = isset($targetuser->pass_indatabase_crypted) ? (string) $targetuser->pass_indatabase_crypted : '';
		if ($passwordhash === '') {
			$loadeduser = new User($this->db);
			if ($loadeduser->fetch($userid, '', '', 0) <= 0) {
				$this->error = 'Unable to fetch user password hash.';
				$this->errors[] = $this->error;
				return -1;
			}
			$passwordhash = (string) $loadeduser->pass_indatabase_crypted;
		}
		if ($passwordhash === '') {
			return 1;
		}

		return $this->recordPasswordHashForUser($userid, $passwordhash, (int) $currentuser->id, $source, $inopentransaction);
	}

	/**
	 * Insert a password hash into history and keep only the configured newest rows.
	 *
	 * @param int    $userid            User id
	 * @param string $passwordhash      Dolibarr password hash
	 * @param int    $authorid          Actor user id
	 * @param string $source            Source label
	 * @param bool   $inopentransaction True when called from inside a Dolibarr transaction
	 * @return int                      >0 if OK, <0 if KO
	 */
	private function recordPasswordHashForUser($userid, $passwordhash, $authorid, $source = '', $inopentransaction = false)
	{
		$userid = (int) $userid;
		$passwordhash = (string) $passwordhash;
		if ($userid <= 0 || $passwordhash === '') {
			return 1;
		}

		if (!$inopentransaction) {
			$this->db->begin();
		}

		$sql = "SELECT h.password_hash";
		$sql .= " FROM ".MAIN_DB_PREFIX."krea2factorauth_password_history as h";
		$sql .= " WHERE h.fk_user = ".$userid;
		$sql .= " AND h.entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " ORDER BY h.date_creation DESC, h.rowid DESC";
		$sql .= $this->db->plimit(1, 0);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			if (!$inopentransaction) {
				$this->db->rollback();
			}
			return -1;
		}

		$obj = $this->db->fetch_object($resql);
		$lasthash = $obj ? (string) $obj->password_hash : '';
		$this->db->free($resql);

		if ($lasthash !== $passwordhash) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."krea2factorauth_password_history (";
			$sql .= "entity, fk_user, password_hash, date_creation, fk_user_creat, source";
			$sql .= ") VALUES (";
			$sql .= ((int) self::GLOBAL_USER_PARAM_ENTITY).",";
			$sql .= " ".$userid.",";
			$sql .= " '".$this->db->escape($passwordhash)."',";
			$sql .= " '".$this->db->idate(dol_now())."',";
			$sql .= " ".((int) $authorid).",";
			$sql .= " '".$this->db->escape(dol_trunc((string) $source, 32))."'";
			$sql .= ")";
			if (!$this->db->query($sql)) {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				if (!$inopentransaction) {
					$this->db->rollback();
				}
				return -1;
			}
		}

		if ($this->prunePasswordHistoryForUserInOpenTransaction($userid) < 0) {
			if (!$inopentransaction) {
				$this->db->rollback();
			}
			return -1;
		}

		if (!$inopentransaction) {
			$this->db->commit();
		}

		return 1;
	}

	/**
	 * Prune retained password history rows inside an open transaction.
	 *
	 * @param int $userid User id
	 * @return int        >0 if OK, <0 if KO
	 */
	private function prunePasswordHistoryForUserInOpenTransaction($userid)
	{
		$userid = (int) $userid;
		$limit = $this->getPasswordHistoryLimit();
		if ($userid <= 0 || $limit <= 0) {
			return 1;
		}

		$sql = "SELECT h.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."krea2factorauth_password_history as h";
		$sql .= " WHERE h.fk_user = ".$userid;
		$sql .= " AND h.entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " ORDER BY h.date_creation DESC, h.rowid DESC";
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}

		$deleteids = array();
		$index = 0;
		while ($obj = $this->db->fetch_object($resql)) {
			$index++;
			if ($index > $limit) {
				$deleteids[] = (int) $obj->rowid;
			}
		}
		$this->db->free($resql);
		if (!count($deleteids)) {
			return 1;
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."krea2factorauth_password_history";
		$sql .= " WHERE fk_user = ".$userid;
		$sql .= " AND rowid IN (".implode(',', array_map('intval', $deleteids)).")";
		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}

		return 1;
	}

	/**
	 * Return whether this Dolibarr installation exposes the native Google OAuth login provider.
	 *
	 * @return bool
	 */
	public function isGoogleLoginCoreAvailable()
	{
		return defined('DOL_DOCUMENT_ROOT')
			&& is_readable(DOL_DOCUMENT_ROOT.'/core/login/functions_googleoauth.php')
			&& is_readable(DOL_DOCUMENT_ROOT.'/core/modules/oauth/google_oauthcallback.php')
			&& is_readable(DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php');
	}

	/**
	 * Return whether Google login is enabled by this module.
	 *
	 * @return bool
	 */
	public function isGoogleLoginEnabled()
	{
		return $this->isGoogleLoginCoreAvailable()
			&& (bool) $this->getGlobalConstInt(self::GOOGLE_LOGIN_ENABLED_CONST, 0);
	}

	/**
	 * Return whether Dolibarr has the native Google OAuth client credentials configured.
	 *
	 * @return bool
	 */
	public function isGoogleOAuthConfigured()
	{
		return $this->getGoogleOAuthClientId() !== ''
			&& $this->getGoogleOAuthClientSecret() !== '';
	}

	/**
	 * Return whether Google 2FA is usable by this module.
	 *
	 * @return bool
	 */
	public function isGoogleTwoFactorAvailable()
	{
		return $this->isGoogleLoginEnabled() && $this->isGoogleOAuthConfigured();
	}

	/**
	 * Return a shared Google OAuth credential constant from entity 1.
	 *
	 * Google OAuth credentials are intentionally shared by every login entity.
	 *
	 * @param string $suffix Credential constant suffix
	 * @return string
	 */
	private function getGoogleOAuthCredentialConst($suffix)
	{
		return (string) dolibarr_get_const($this->db, self::GOOGLE_OAUTH_CONST_PREFIX.'_'.$suffix, self::GOOGLE_OAUTH_CREDENTIAL_ENTITY);
	}

	/**
	 * Return Dolibarr's native Google OAuth client id for the Login provider key.
	 *
	 * @return string
	 */
	public function getGoogleOAuthClientId()
	{
		return $this->getGoogleOAuthCredentialConst('ID');
	}

	/**
	 * Return Dolibarr's native Google OAuth client secret for the Login provider key.
	 *
	 * @return string
	 */
	public function getGoogleOAuthClientSecret()
	{
		return $this->getGoogleOAuthCredentialConst('SECRET');
	}

	/**
	 * Return the Google OAuth callback URL expected by Dolibarr core.
	 *
	 * @return string
	 */
	public function getGoogleOAuthCallbackUrl()
	{
		$root = defined('DOL_MAIN_URL_ROOT') ? DOL_MAIN_URL_ROOT : DOL_URL_ROOT;
		return $root.'/core/modules/oauth/google_oauthcallback.php';
	}

	/**
	 * Return the module-owned Google OAuth callback URL for user-extrafield email matching.
	 *
	 * @return string
	 */
	public function getGoogleOAuthModuleCallbackUrl()
	{
		return dol_buildpath('/krea2factorauth/public/google_oauthcallback.php', 2);
	}

	/**
	 * Return the callback URL used by module-initiated Google login.
	 *
	 * @return string
	 */
	public function getGoogleOAuthLoginCallbackUrl()
	{
		return $this->getGoogleOAuthModuleCallbackUrl();
	}

	/**
	 * Return whether Google login must match the module user-card email extrafield.
	 *
	 * @return bool
	 */
	public function isGoogleLoginEmailExtrafieldEnabled()
	{
		return (bool) $this->getGlobalConstInt(self::GOOGLE_LOGIN_USE_EXTRAFIELD_EMAIL_CONST, 0);
	}

	/**
	 * Ensure the user-card Google login email extrafield exists.
	 *
	 * @return int >0 if OK, <0 if KO
	 */
	public function ensureGoogleLoginEmailExtrafield()
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

		$definitionexists = $this->googleLoginEmailExtrafieldDefinitionExists();
		if ($definitionexists < 0) {
			return -1;
		}

		if ($definitionexists > 0) {
			if (!$this->googleLoginEmailExtrafieldColumnExists() && $this->addGoogleLoginEmailExtrafieldColumn() < 0) {
				return -1;
			}

			return $this->syncGoogleLoginEmailExtrafieldMetadata();
		}

		$enabledexpr = $this->getGoogleLoginEmailExtrafieldEnabledExpression();
		$extrafields = new ExtraFields($this->db);
		$result = $extrafields->addExtraField(
			self::GOOGLE_LOGIN_EMAIL_EXTRAFIELD,
			'Krea2FactorAuthGoogleLoginEmail',
			'varchar',
			260,
			'255',
			'user',
			0,
			0,
			'',
			'',
			1,
			'',
			'3',
			'Krea2FactorAuthGoogleLoginEmailHelp',
			'',
			'0',
			'krea2factorauth@krea2factorauth',
			$enabledexpr
		);

		if ($result <= 0) {
			$this->error = $extrafields->error ?: $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::ensureGoogleLoginEmailExtrafield '.$this->error, LOG_ERR);
			return -1;
		}

		return $this->syncGoogleLoginEmailExtrafieldMetadata();
	}

	/**
	 * Return whether the module Google login email extrafield declaration exists.
	 *
	 * @return int 1 when found, 0 when missing, <0 on SQL error
	 */
	private function googleLoginEmailExtrafieldDefinitionExists()
	{
		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."extrafields";
		$sql .= " WHERE elementtype = 'user'";
		$sql .= " AND name = '".$this->db->escape(self::GOOGLE_LOGIN_EMAIL_EXTRAFIELD)."'";
		$sql .= " AND entity = ".((int) self::GLOBAL_CONFIG_ENTITY);
		$sql .= " LIMIT 1";
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::googleLoginEmailExtrafieldDefinitionExists '.$this->error, LOG_ERR);
			return -1;
		}

		$exists = (bool) $this->db->fetch_object($resql);
		$this->db->free($resql);
		return $exists ? 1 : 0;
	}

	/**
	 * Add the module Google login email column when metadata exists but the column is missing.
	 *
	 * @return int >0 if OK, <0 if KO
	 */
	private function addGoogleLoginEmailExtrafieldColumn()
	{
		$fielddesc = array(
			'type' => 'varchar',
			'value' => '255',
			'null' => 'NULL',
			'default' => ''
		);

		$result = $this->db->DDLAddField(MAIN_DB_PREFIX.'user_extrafields', self::GOOGLE_LOGIN_EMAIL_EXTRAFIELD, $fielddesc);
		if ($result <= 0 && $this->db->lasterrno() !== 'DB_ERROR_COLUMN_ALREADY_EXISTS') {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::addGoogleLoginEmailExtrafieldColumn '.$this->error, LOG_ERR);
			return -1;
		}

		return 1;
	}

	/**
	 * Synchronize the module-owned Google login email extrafield declaration.
	 *
	 * @return int >0 if OK, <0 if KO
	 */
	public function syncGoogleLoginEmailExtrafieldMetadata()
	{
		$enabledexpr = $this->getGoogleLoginEmailExtrafieldEnabledExpression();
		$sql = "UPDATE ".MAIN_DB_PREFIX."extrafields";
		$sql .= " SET enabled = '".$this->db->escape($enabledexpr)."',";
		$sql .= " label = 'Krea2FactorAuthGoogleLoginEmail',";
		$sql .= " type = 'varchar',";
		$sql .= " size = '255',";
		$sql .= " help = 'Krea2FactorAuthGoogleLoginEmailHelp',";
		$sql .= " langs = 'krea2factorauth@krea2factorauth',";
		$sql .= " list = '3'";
		$sql .= " WHERE elementtype = 'user'";
		$sql .= " AND name = '".$this->db->escape(self::GOOGLE_LOGIN_EMAIL_EXTRAFIELD)."'";
		$sql .= " AND entity = ".((int) self::GLOBAL_CONFIG_ENTITY);
		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::syncGoogleLoginEmailExtrafieldMetadata '.$this->error, LOG_ERR);
			return -1;
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."extrafields";
		$sql .= " WHERE elementtype = 'user'";
		$sql .= " AND name = '".$this->db->escape(self::GOOGLE_LOGIN_EMAIL_EXTRAFIELD)."'";
		$sql .= " AND entity <> ".((int) self::GLOBAL_CONFIG_ENTITY);
		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::syncGoogleLoginEmailExtrafieldMetadata '.$this->error, LOG_ERR);
			return -1;
		}

		return 1;
	}

	/**
	 * Return the literal extrafield enabled expression from the entity-0 setting.
	 *
	 * @return string '1' when enabled, '0' when disabled
	 */
	private function getGoogleLoginEmailExtrafieldEnabledExpression()
	{
		return ($this->isGoogleTwoFactorAvailable() && $this->isGoogleLoginEmailExtrafieldEnabled()) ? '1' : '0';
	}

	/**
	 * Normalize a comma, semicolon, or whitespace separated list of allowed Google email domains.
	 *
	 * @param string $domains Raw domain list
	 * @return string         Comma-separated normalized domain list
	 */
	public function normalizeGoogleAllowedDomainsInput($domains)
	{
		$normalized = array();
		$parts = preg_split('/[,;\s]+/', strtolower((string) $domains));
		if (!is_array($parts)) {
			return '';
		}

		foreach ($parts as $domain) {
			$domain = trim($domain);
			$domain = ltrim($domain, '@');
			if ($domain === '') {
				continue;
			}
			if (!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $domain)) {
				continue;
			}
			$normalized[$domain] = $domain;
		}

		ksort($normalized);
		return implode(',', array_values($normalized));
	}

	/**
	 * Return allowed Google login domains.
	 *
	 * An empty list means any Google account may authenticate when Dolibarr's
	 * standard OAuth email mapping finds an existing visible user.
	 *
	 * @return string[]
	 */
	public function getGoogleAllowedDomains()
	{
		$domains = $this->normalizeGoogleAllowedDomainsInput($this->getGlobalConstString(self::GOOGLE_LOGIN_ALLOWED_DOMAINS_CONST, ''));
		if ($domains === '') {
			return array();
		}

		return explode(',', $domains);
	}

	/**
	 * Return the normalized allowed Google login domains as display/save text.
	 *
	 * @return string
	 */
	public function getGoogleAllowedDomainsString()
	{
		return implode(', ', $this->getGoogleAllowedDomains());
	}

	/**
	 * Return the Dolibarr user email used for OAuth matching.
	 *
	 * @param User $authenticateduser Authenticated user
	 * @return string                 Email address
	 */
	public function getGoogleUserOAuthEmail(User $authenticateduser)
	{
		$emailoauth2 = isset($authenticateduser->email_oauth2) ? trim((string) $authenticateduser->email_oauth2) : '';
		if ($emailoauth2 !== '') {
			return $emailoauth2;
		}

		return isset($authenticateduser->email) ? trim((string) $authenticateduser->email) : '';
	}

	/**
	 * Normalize a Google login email address for comparison.
	 *
	 * @param string $email Raw email address
	 * @return string       Normalized email or empty string when invalid
	 */
	public function normalizeGoogleLoginEmail($email)
	{
		$email = strtolower(preg_replace('/\s+/', '', trim((string) $email)));
		return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
	}

	/**
	 * Normalize a comma-separated Google login email list for comparison.
	 *
	 * @param string $emails Raw email list
	 * @return string[]      Unique normalized valid emails
	 */
	public function normalizeGoogleLoginEmailList($emails)
	{
		$normalized = array();
		$parts = preg_split('/,/', (string) $emails);
		if (!is_array($parts)) {
			return $normalized;
		}

		foreach ($parts as $part) {
			$email = $this->normalizeGoogleLoginEmail($part);
			if ($email !== '' && !in_array($email, $normalized, true)) {
				$normalized[] = $email;
			}
		}

		return $normalized;
	}

	/**
	 * Normalize submitted Google login emails and report invalid entries.
	 *
	 * @param string   $emails        Raw submitted email list
	 * @param string[] $invalidemails Invalid submitted values, returned by reference
	 * @return string                 Comma-separated normalized email list
	 */
	public function normalizeSubmittedGoogleLoginEmailList($emails, &$invalidemails = array())
	{
		$invalidemails = array();
		$normalized = array();
		$parts = preg_split('/[,;\s]+/', (string) $emails);
		if (!is_array($parts)) {
			return '';
		}

		foreach ($parts as $part) {
			$rawemail = trim((string) $part);
			if ($rawemail === '') {
				continue;
			}
			$email = $this->normalizeGoogleLoginEmail($rawemail);
			if ($email === '') {
				$invalidemails[] = $rawemail;
				continue;
			}
			if (!in_array($email, $normalized, true)) {
				$normalized[] = $email;
			}
		}

		return implode(', ', $normalized);
	}

	/**
	 * Save the module-owned Google login email list for a user.
	 *
	 * @param User   $targetuser  Target user
	 * @param User   $currentuser Current user authorizing the change
	 * @param string $emails      Raw submitted email list
	 * @return int                >0 if OK, <0 if KO
	 */
	public function setGoogleLoginEmailsForUser(User $targetuser, User $currentuser, $emails)
	{
		if (!$this->isSuperAdmin($currentuser)) {
			$this->error = 'Only a super administrator can manage Google login emails.';
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::setGoogleLoginEmailsForUser denied for user id '.((int) $currentuser->id).' on target user id '.((int) $targetuser->id), LOG_WARNING);
			return -1;
		}

		$invalidemails = array();
		$normalizedemails = $this->normalizeSubmittedGoogleLoginEmailList($emails, $invalidemails);
		if (count($invalidemails)) {
			$this->error = 'Invalid Google login emails: '.implode(', ', $invalidemails);
			$this->errors[] = $this->error;
			return -1;
		}
		if ($normalizedemails === '') {
			$this->error = 'Enter at least one Google login email.';
			$this->errors[] = $this->error;
			return -1;
		}
		if (dol_strlen($normalizedemails) > 255) {
			$this->error = 'Google login email list is too long.';
			$this->errors[] = $this->error;
			return -1;
		}

		return $this->updateGoogleLoginEmailExtrafieldForUser($targetuser, $currentuser, $normalizedemails);
	}

	/**
	 * Clear the module-owned Google login email list for a user.
	 *
	 * @param User $targetuser  Target user
	 * @param User $currentuser Current user authorizing the change
	 * @return int              >0 if OK, <0 if KO
	 */
	public function clearGoogleLoginEmailsForUser(User $targetuser, User $currentuser)
	{
		if (!$this->isSuperAdmin($currentuser)) {
			$this->error = 'Only a super administrator can manage Google login emails.';
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::clearGoogleLoginEmailsForUser denied for user id '.((int) $currentuser->id).' on target user id '.((int) $targetuser->id), LOG_WARNING);
			return -1;
		}

		return $this->updateGoogleLoginEmailExtrafieldForUser($targetuser, $currentuser, '');
	}

	/**
	 * Update the module-owned Google login email extrafield for a user.
	 *
	 * @param User   $targetuser       Target user
	 * @param User   $currentuser      Current user authorizing the change
	 * @param string $normalizedemails Normalized comma-separated email list
	 * @return int                     >0 if OK, <0 if KO
	 */
	private function updateGoogleLoginEmailExtrafieldForUser(User $targetuser, User $currentuser, $normalizedemails)
	{
		if ($this->ensureGoogleLoginEmailExtrafield() < 0) {
			return -1;
		}
		if (!$this->googleLoginEmailExtrafieldColumnExists()) {
			$this->error = 'Google login email extrafield column is missing.';
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::updateGoogleLoginEmailExtrafieldForUser '.$this->error, LOG_ERR);
			return -1;
		}

		$fieldsql = $this->db->sanitize(self::GOOGLE_LOGIN_EMAIL_EXTRAFIELD);
		$this->db->begin();

		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."user_extrafields";
		$sql .= " WHERE fk_object = ".((int) $targetuser->id);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			$this->db->rollback();
			dol_syslog('Krea2FactorAuthManager::updateGoogleLoginEmailExtrafieldForUser '.$this->error, LOG_ERR);
			return -1;
		}

		$existingrow = $this->db->fetch_object($resql);
		$this->db->free($resql);
		if ($existingrow) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."user_extrafields";
			$sql .= " SET ".$fieldsql." = ".($normalizedemails === '' ? "NULL" : "'".$this->db->escape($normalizedemails)."'");
			$sql .= " WHERE fk_object = ".((int) $targetuser->id);
		} else {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_extrafields (fk_object, ".$fieldsql.") VALUES (";
			$sql .= ((int) $targetuser->id).", ";
			$sql .= ($normalizedemails === '' ? "NULL" : "'".$this->db->escape($normalizedemails)."'");
			$sql .= ")";
		}

		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			$this->db->rollback();
			dol_syslog('Krea2FactorAuthManager::updateGoogleLoginEmailExtrafieldForUser '.$this->error, LOG_ERR);
			return -1;
		}

		if (!isset($targetuser->array_options) || !is_array($targetuser->array_options)) {
			$targetuser->array_options = array();
		}
		$targetuser->array_options['options_'.self::GOOGLE_LOGIN_EMAIL_EXTRAFIELD] = (string) $normalizedemails;
		$this->db->commit();

		dol_syslog('Krea2FactorAuthManager::updateGoogleLoginEmailExtrafieldForUser '.($normalizedemails === '' ? 'cleared' : 'saved').' Google login emails for user id '.((int) $targetuser->id), LOG_WARNING);
		return 1;
	}

	/**
	 * Return the configured Google login email extrafield values for a user.
	 *
	 * @param User $authenticateduser Authenticated user
	 * @return string[]               Normalized email list
	 */
	public function getGoogleLoginEmailsForUser(User $authenticateduser)
	{
		$fieldkey = 'options_'.self::GOOGLE_LOGIN_EMAIL_EXTRAFIELD;
		if (!isset($authenticateduser->array_options) || !is_array($authenticateduser->array_options) || !array_key_exists($fieldkey, $authenticateduser->array_options)) {
			$authenticateduser->fetch_optionals();
		}

		$email = '';
		if (isset($authenticateduser->array_options) && is_array($authenticateduser->array_options) && array_key_exists($fieldkey, $authenticateduser->array_options)) {
			$email = (string) $authenticateduser->array_options[$fieldkey];
		}

		return $this->normalizeGoogleLoginEmailList($email);
	}

	/**
	 * Return the configured Google login email extrafield values for a visible user id.
	 *
	 * @param int        $userid User id
	 * @param int|string $entity Deprecated entity parameter
	 * @return string[]          Normalized email list
	 */
	public function getGoogleLoginEmailsForUserId($userid, $entity = 0)
	{
		$userid = (int) $userid;
		if ($userid <= 0 || !$this->googleLoginEmailExtrafieldColumnExists()) {
			return array();
		}

		$field = $this->db->sanitize(self::GOOGLE_LOGIN_EMAIL_EXTRAFIELD);
		$sql = "SELECT uef.".$field." as google_login_emails";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_extrafields as uef ON uef.fk_object = u.rowid";
		$sql .= " WHERE u.rowid = ".$userid;
		$sql .= " AND u.entity IN (".getEntity('user').")";
		$sql .= " LIMIT 1";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::getGoogleLoginEmailsForUserId '.$this->error, LOG_ERR);
			return array();
		}

		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);
		if (!$obj) {
			return array();
		}

		return $this->normalizeGoogleLoginEmailList(empty($obj->google_login_emails) ? '' : (string) $obj->google_login_emails);
	}

	/**
	 * Return whether Google login satisfies the module 2FA policy for a user.
	 *
	 * @param int        $userid User id
	 * @param int|string $entity Deprecated entity parameter
	 * @return bool
	 */
	public function isGoogleTwoFactorConfiguredForUser($userid, $entity = 0)
	{
		if (!$this->isGoogleTwoFactorAvailable()) {
			return false;
		}

		return count($this->getGoogleLoginEmailsForUserId((int) $userid, $entity)) > 0;
	}

	/**
	 * Return whether Google OAuth is the mandatory second-factor path.
	 *
	 * This applies when local TOTP is inactive, the user is not explicitly
	 * exempt, password expiration is off, and module Google 2FA is configured.
	 *
	 * @param int        $userid User id
	 * @param int|string $entity Deprecated entity parameter
	 * @return bool
	 */
	public function isGoogleTwoFactorMandatoryForUser($userid, $entity = 0)
	{
		$userid = (int) $userid;
		$localtotpactive = ($this->isTotpEnrollmentAvailable() && $this->isEnabledForUser($userid, $entity));
		return $userid > 0
			&& !$localtotpactive
			&& !$this->isMandatoryExemptForUser($userid, $entity)
			&& !$this->isPasswordExpirationEnabledForUser($userid, $entity)
			&& $this->isGoogleTwoFactorConfiguredForUser($userid, $entity);
	}

	/**
	 * Return the first configured Google login email extrafield value for a user.
	 *
	 * @param User $authenticateduser Authenticated user
	 * @return string                 Normalized email or empty string
	 */
	public function getGoogleLoginEmailForUser(User $authenticateduser)
	{
		$emails = $this->getGoogleLoginEmailsForUser($authenticateduser);
		return count($emails) ? $emails[0] : '';
	}

	/**
	 * Store the Google email returned by the module-owned OAuth callback.
	 *
	 * @param User       $authenticateduser Authenticated user
	 * @param string     $email             Google email address
	 * @param int|string $entity            Login entity
	 * @return int                          >0 if OK, <0 if KO
	 */
	public function recordGoogleLoginReturnEmail(User $authenticateduser, $email, $entity)
	{
		$email = $this->normalizeGoogleLoginEmail($email);
		if ($email === '') {
			$this->error = 'Invalid Google login email.';
			$this->errors[] = $this->error;
			return -1;
		}

		$_SESSION[self::GOOGLE_LOGIN_RETURN_EMAIL_SESSION] = array(
			'userid' => (int) $authenticateduser->id,
			'login' => (string) $authenticateduser->login,
			'entity' => $this->normalizeEntity($entity),
			'email' => $email,
			'created' => dol_now(),
		);

		return 1;
	}

	/**
	 * Return the Google email stored for the current OAuth return.
	 *
	 * @param User       $authenticateduser Authenticated user
	 * @param int|string $entity            Login entity
	 * @return string                       Normalized email or empty string
	 */
	public function getGoogleLoginReturnEmail(User $authenticateduser, $entity)
	{
		if (empty($_SESSION[self::GOOGLE_LOGIN_RETURN_EMAIL_SESSION]) || !is_array($_SESSION[self::GOOGLE_LOGIN_RETURN_EMAIL_SESSION])) {
			return '';
		}

		$stored = $_SESSION[self::GOOGLE_LOGIN_RETURN_EMAIL_SESSION];
		if (empty($stored['created']) || ((int) $stored['created'] + self::GOOGLE_LOGIN_PENDING_TTL) < dol_now()) {
			unset($_SESSION[self::GOOGLE_LOGIN_RETURN_EMAIL_SESSION]);
			return '';
		}
		if ((int) $stored['userid'] !== (int) $authenticateduser->id || (string) $stored['login'] !== (string) $authenticateduser->login) {
			return '';
		}
		if ((int) $stored['entity'] !== $this->normalizeEntity($entity)) {
			return '';
		}

		return $this->normalizeGoogleLoginEmail(empty($stored['email']) ? '' : (string) $stored['email']);
	}

	/**
	 * Clear the module-owned Google return email marker.
	 *
	 * @return void
	 */
	public function clearGoogleLoginReturnEmail()
	{
		unset($_SESSION[self::GOOGLE_LOGIN_RETURN_EMAIL_SESSION]);
	}

	/**
	 * Fetch the Dolibarr user whose module Google login email matches a Google identity.
	 *
	 * @param string     $email  Google email address
	 * @param int|string $entity Login entity
	 * @return User|null         Matching user or null
	 */
	public function fetchUserByGoogleLoginEmail($email, $entity)
	{
		global $conf;

		$email = $this->normalizeGoogleLoginEmail($email);
		if ($email === '') {
			$this->error = 'Invalid Google login email.';
			$this->errors[] = $this->error;
			return null;
		}

		$entity = $this->normalizeEntity($entity);
		$field = $this->db->sanitize(self::GOOGLE_LOGIN_EMAIL_EXTRAFIELD);
		$sql = "SELECT u.rowid, uef.".$field." as google_login_emails";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user_extrafields as uef ON uef.fk_object = u.rowid";
		if (isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
			$sql .= " WHERE u.entity IS NOT NULL";
		} elseif ($entity == 0) {
			$sql .= " WHERE u.entity = 0";
		} else {
			$sql .= " WHERE u.entity IN (0, ".((int) ($entity > 0 ? $entity : $conf->entity)).")";
		}
		$sql .= " AND u.statut = 1";
		$sql .= " AND uef.".$field." IS NOT NULL";
		$sql .= " AND TRIM(uef.".$field.") <> ''";
		$sql .= " ORDER BY u.entity ASC";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::fetchUserByGoogleLoginEmail '.$this->error, LOG_ERR);
			return null;
		}

		$matches = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$configuredemails = $this->normalizeGoogleLoginEmailList(empty($obj->google_login_emails) ? '' : (string) $obj->google_login_emails);
			if (in_array($email, $configuredemails, true)) {
				$matches[] = (int) $obj->rowid;
			}
		}
		$this->db->free($resql);

		if (count($matches) > 1) {
			$this->error = 'Google login email matches more than one Dolibarr user.';
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::fetchUserByGoogleLoginEmail duplicate email '.$email, LOG_WARNING);
			return null;
		}
		if (!count($matches)) {
			$this->error = 'Google login email does not match a Dolibarr user.';
			$this->errors[] = $this->error;
			return null;
		}

		$usertmp = new User($this->db);
		if ($usertmp->fetch($matches[0], '', '', 0) > 0) {
			return $usertmp;
		}

		$this->error = $usertmp->error ?: 'Google login user could not be loaded.';
		$this->errors[] = $this->error;
		return null;
	}

	/**
	 * Fetch the Dolibarr user whose standard Google OAuth email matches a Google identity.
	 *
	 * @param string     $email  Google email address
	 * @param int|string $entity Login entity
	 * @return User|null         Matching user or null
	 */
	public function fetchUserByGoogleOAuthEmail($email, $entity)
	{
		$email = $this->normalizeGoogleLoginEmail($email);
		if ($email === '') {
			$this->error = 'Invalid Google OAuth email.';
			$this->errors[] = $this->error;
			return null;
		}

		$usertmp = new User($this->db);
		$result = $usertmp->fetch(0, '', '', 0, $this->normalizeEntity($entity), $email, 0, 1);
		if ($result > 0) {
			return $usertmp;
		}

		$this->error = $usertmp->error ?: 'Google OAuth email does not match a Dolibarr user.';
		$this->errors[] = $this->error;
		return null;
	}

	/**
	 * Return the Google email to use for post-return module policy checks.
	 *
	 * @param User       $authenticateduser Authenticated user
	 * @param int|string $entity            Login entity
	 * @return string                       Normalized email or empty string
	 */
	public function getGoogleAuthenticatedEmail(User $authenticateduser, $entity = 0)
	{
		$returnemail = $this->getGoogleLoginReturnEmail($authenticateduser, $entity);
		if ($returnemail !== '') {
			return $returnemail;
		}

		return $this->normalizeGoogleLoginEmail($this->getGoogleUserOAuthEmail($authenticateduser));
	}

	/**
	 * Return whether a Google-authenticated Dolibarr user passes module policy.
	 *
	 * @param User $authenticateduser Authenticated user
	 * @param int|string $entity      Login entity
	 * @return bool
	 */
	public function isGoogleUserAllowed(User $authenticateduser, $entity = 0)
	{
		$email = $this->getGoogleAuthenticatedEmail($authenticateduser, $entity);
		if ($this->isGoogleLoginEmailExtrafieldEnabled()) {
			$configuredemails = $this->getGoogleLoginEmailsForUser($authenticateduser);
			if (!count($configuredemails)) {
				$this->error = 'Google login requires a user-card Krea2FactorAuth login email for this user.';
				$this->errors[] = $this->error;
				return false;
			}
			if ($email === '' || !in_array($email, $configuredemails, true)) {
				$this->error = 'Google login email does not match the user-card Krea2FactorAuth login email.';
				$this->errors[] = $this->error;
				return false;
			}
		}

		$alloweddomains = $this->getGoogleAllowedDomains();
		if (!count($alloweddomains)) {
			return true;
		}

		$pos = strrpos($email, '@');
		if ($pos === false) {
			$this->error = 'Google login is restricted to configured domains, but this Dolibarr user has no OAuth email address.';
			$this->errors[] = $this->error;
			return false;
		}

		$domain = substr($email, $pos + 1);
		if (!in_array($domain, $alloweddomains, true)) {
			$this->error = 'Google login is not allowed for this email domain.';
			$this->errors[] = $this->error;
			return false;
		}

		return true;
	}

	/**
	 * Normalize a login background color.
	 *
	 * @param string $color   Raw color
	 * @param string $default Fallback color
	 * @return string         Normalized #rrggbb value or fallback
	 */
	public function normalizeLoginBackgroundColor($color, $default = '')
	{
		$color = strtolower(trim((string) $color));
		if (preg_match('/^#[0-9a-f]{6}$/', $color)) {
			return $color;
		}
		if (preg_match('/^#[0-9a-f]{3}$/', $color)) {
			return '#'.$color[1].$color[1].$color[2].$color[2].$color[3].$color[3];
		}

		return $default;
	}

	/**
	 * Return the default login page background color.
	 *
	 * @return string #rrggbb color
	 */
	public function getLoginBackgroundDefaultColor()
	{
		return self::DEFAULT_LOGIN_BACKGROUND_COLOR;
	}

	/**
	 * Return entity-specific login background color overrides.
	 *
	 * @return array<int,string>
	 */
	public function getLoginBackgroundColorMap()
	{
		$raw = $this->getGlobalConstString(self::LOGIN_BACKGROUND_COLORS_CONST, '');
		$decoded = json_decode($raw, true);
		if (!is_array($decoded)) {
			return array();
		}

		$colors = array();
		foreach ($decoded as $entity => $color) {
			$entity = (int) $entity;
			$color = $this->normalizeLoginBackgroundColor((string) $color, '');
			if ($entity > 0 && $color !== '') {
				$colors[$entity] = $color;
			}
		}

		ksort($colors);
		return $colors;
	}

	/**
	 * Return normalized entity-specific background colors as JSON.
	 *
	 * @param array<int|string,string> $colors Raw colors keyed by entity id
	 * @return string                          JSON object
	 */
	public function encodeLoginBackgroundColorMap(array $colors)
	{
		$normalized = array();
		foreach ($colors as $entity => $color) {
			$entity = (int) $entity;
			$color = $this->normalizeLoginBackgroundColor((string) $color, '');
			if ($entity > 0 && $color !== '') {
				$normalized[$entity] = $color;
			}
		}

		ksort($normalized);
		return json_encode($normalized);
	}

	/**
	 * Return the login page background color for a selected entity.
	 *
	 * @param int|string $entity Selected entity
	 * @return string            #rrggbb color
	 */
	public function getLoginBackgroundColorForEntity($entity)
	{
		$entity = $this->normalizeEntity($entity);
		$colors = $this->getLoginBackgroundColorMap();
		if (!empty($colors[$entity])) {
			return $colors[$entity];
		}

		return $this->getLoginBackgroundDefaultColor();
	}

	/**
	 * Return uploaded login logo rendering rules.
	 *
	 * @return array<string,mixed>
	 */
	public function getLoginLogoRules()
	{
		return array(
			'extensions' => array('png', 'jpg', 'jpeg', 'webp'),
			'maxbytes' => self::LOGIN_LOGO_MAX_BYTES,
			'minwidth' => self::LOGIN_LOGO_MIN_WIDTH,
			'minheight' => self::LOGIN_LOGO_MIN_HEIGHT,
			'maxpixels' => self::LOGIN_LOGO_MAX_PIXELS,
			'minratio' => 0.20,
			'maxratio' => 8.00,
			'renderedmaxwidth' => 180,
			'renderedmaxheight' => 132,
		);
	}

	/**
	 * Return uploaded login company icon rendering rules.
	 *
	 * @return array<string,mixed>
	 */
	public function getLoginCompanyIconRules()
	{
		return $this->getLoginLogoRules();
	}

	/**
	 * Return the login logo storage directory.
	 *
	 * @return string Absolute directory path
	 */
	public function getLoginLogoDirectory()
	{
		// Login logo files are selected by entity-scoped constants, but the
		// files themselves must stay in the default module document directory.
		return DOL_DATA_ROOT.'/krea2factorauth/login';
	}

	/**
	 * Return the login company icon storage directory.
	 *
	 * @return string Absolute directory path
	 */
	public function getLoginCompanyIconDirectory()
	{
		return $this->getLoginLogoDirectory();
	}

	/**
	 * Return the legacy current-entity login logo directory when it differs from the shared directory.
	 *
	 * @return string Absolute legacy directory path or empty string
	 */
	private function getCurrentEntityLoginLogoDirectory()
	{
		global $conf;

		$base = (!empty($conf->krea2factorauth->dir_output) ? $conf->krea2factorauth->dir_output : DOL_DATA_ROOT.'/krea2factorauth');
		$dir = $base.'/login';

		return $dir !== $this->getLoginLogoDirectory() ? $dir : '';
	}

	/**
	 * Return the stored login logo basename for an entity.
	 *
	 * @param int|string $entity Entity id
	 * @return string            Safe basename or empty string
	 */
	public function getLoginLogoBasename($entity = 0)
	{
		$entity = $this->normalizeEntity($entity);
		$entitypngfilename = 'login-logo-entity-'.$entity.'.png';
		$entitypngpath = $this->getLoginLogoDirectory().'/'.$entitypngfilename;
		if (is_file($entitypngpath) && is_readable($entitypngpath)) {
			return $entitypngfilename;
		}

		$filename = trim((string) dolibarr_get_const($this->db, self::LOGIN_LOGO_CONST, $entity));
		if ($filename === '') {
			$filename = trim((string) dolibarr_get_const($this->db, self::LEGACY_LOGIN_COMPANY_ICON_CONST, $entity));
		}
		if ($filename === '' || basename($filename) !== $filename) {
			return '';
		}
		if (!preg_match('/^login-(logo|company-icon)(-entity-[1-9][0-9]*)?\.(png|jpg|jpeg|webp)$/', $filename)) {
			return '';
		}

		return $filename;
	}

	/**
	 * Return the stored login company icon basename for an entity.
	 *
	 * @param int|string $entity Entity id
	 * @return string            Safe basename or empty string
	 */
	public function getLoginCompanyIconBasename($entity = 0)
	{
		return $this->getLoginLogoBasename($entity);
	}

	/**
	 * Return the stored login logo absolute path for an entity.
	 *
	 * @param int|string $entity Entity id
	 * @return string            Absolute path or empty string
	 */
	public function getLoginLogoPath($entity = 0)
	{
		$filename = $this->getLoginLogoBasename($entity);
		if ($filename === '') {
			return '';
		}

		$path = $this->getLoginLogoDirectory().'/'.$filename;
		if (!is_file($path) || !is_readable($path)) {
			$currententitydir = $this->getCurrentEntityLoginLogoDirectory();
			if ($currententitydir === '') {
				return '';
			}

			$legacypath = $currententitydir.'/'.$filename;
			if (!is_file($legacypath) || !is_readable($legacypath)) {
				return '';
			}
			if ($this->migrateLoginLogoFileToSharedDirectory($legacypath, $path) <= 0) {
				return '';
			}
		}

		return $path;
	}

	/**
	 * Return the stored login company icon absolute path for an entity.
	 *
	 * @param int|string $entity Entity id
	 * @return string            Absolute path or empty string
	 */
	public function getLoginCompanyIconPath($entity = 0)
	{
		return $this->getLoginLogoPath($entity);
	}

	/**
	 * Return the public URL for the stored login logo for an entity.
	 *
	 * @param int|string $entity Entity id
	 * @return string            URL or empty string
	 */
	public function getLoginLogoUrl($entity = 0)
	{
		$entity = $this->normalizeEntity($entity);
		$path = $this->getLoginLogoPath($entity);
		if ($path === '') {
			return '';
		}

		return dol_buildpath('/krea2factorauth/public/login_logo.php', 1).'?entity='.$entity.'&v='.(int) filemtime($path);
	}

	/**
	 * Return the public URL for the stored login company icon for an entity.
	 *
	 * @param int|string $entity Entity id
	 * @return string            URL or empty string
	 */
	public function getLoginCompanyIconUrl($entity = 0)
	{
		return $this->getLoginLogoUrl($entity);
	}

	/**
	 * Return MIME type for the stored login logo for an entity.
	 *
	 * @param int|string $entity Entity id
	 * @return string            MIME type
	 */
	public function getLoginLogoMimeType($entity = 0)
	{
		$filename = $this->getLoginLogoBasename($entity);
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		$map = array(
			'png' => 'image/png',
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'webp' => 'image/webp',
		);

		return isset($map[$ext]) ? $map[$ext] : 'application/octet-stream';
	}

	/**
	 * Return MIME type for the stored login company icon for an entity.
	 *
	 * @param int|string $entity Entity id
	 * @return string            MIME type
	 */
	public function getLoginCompanyIconMimeType($entity = 0)
	{
		return $this->getLoginLogoMimeType($entity);
	}

	/**
	 * Store an uploaded login logo after strict validation.
	 *
	 * @param array<string,mixed> $file Uploaded file array
	 * @param int|string          $entity Entity id
	 * @return int                     >0 if OK, <0 if KO
	 */
	public function storeLoginLogoUpload(array $file, $entity = 0)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$entity = $this->normalizeEntity($entity);

		if (empty($file['name']) || (isset($file['error']) && (int) $file['error'] === UPLOAD_ERR_NO_FILE)) {
			return 1;
		}

		$uploaderror = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_OK;
		if ($uploaderror !== UPLOAD_ERR_OK) {
			$this->error = 'Login logo upload failed with PHP upload error '.$uploaderror;
			$this->errors[] = $this->error;
			return -1;
		}

		$tmpname = empty($file['tmp_name']) ? '' : (string) $file['tmp_name'];
		if ($tmpname === '' || !is_uploaded_file($tmpname)) {
			$this->error = 'Invalid login logo upload source.';
			$this->errors[] = $this->error;
			return -1;
		}

		$size = isset($file['size']) ? (int) $file['size'] : 0;
		if ($size <= 0 || $size > self::LOGIN_LOGO_MAX_BYTES) {
			$this->error = 'Login logo must be smaller than 512 KB.';
			$this->errors[] = $this->error;
			return -1;
		}

		$originalname = dol_sanitizeFileName((string) $file['name']);
		$ext = strtolower(pathinfo($originalname, PATHINFO_EXTENSION));
		if ($ext === 'jpeg') {
			$ext = 'jpg';
		}

		$allowed = array(
			'png' => 'image/png',
			'jpg' => 'image/jpeg',
			'webp' => 'image/webp',
		);
		if (!isset($allowed[$ext])) {
			$this->error = 'Login logo must be a PNG, JPG, or WebP image.';
			$this->errors[] = $this->error;
			return -1;
		}

		$imageinfo = @getimagesize($tmpname);
		if (empty($imageinfo) || empty($imageinfo[0]) || empty($imageinfo[1]) || empty($imageinfo['mime'])) {
			$this->error = 'Login logo is not a readable image.';
			$this->errors[] = $this->error;
			return -1;
		}
		if ((string) $imageinfo['mime'] !== $allowed[$ext]) {
			$this->error = 'Login logo extension does not match the uploaded image type.';
			$this->errors[] = $this->error;
			return -1;
		}

		$width = (int) $imageinfo[0];
		$height = (int) $imageinfo[1];
		if ($width < self::LOGIN_LOGO_MIN_WIDTH || $height < self::LOGIN_LOGO_MIN_HEIGHT || $width > self::LOGIN_LOGO_MAX_PIXELS || $height > self::LOGIN_LOGO_MAX_PIXELS) {
			$this->error = 'Login logo dimensions must be between 64x24 and 3000x3000 pixels.';
			$this->errors[] = $this->error;
			return -1;
		}

		$ratio = $height > 0 ? ($width / $height) : 0;
		if ($ratio < 0.20 || $ratio > 8.00) {
			$this->error = 'Login logo form factor is outside the supported range.';
			$this->errors[] = $this->error;
			return -1;
		}

		$dir = $this->getLoginLogoDirectory();
		if (dol_mkdir($dir) < 0) {
			$this->error = 'Unable to create login logo directory.';
			$this->errors[] = $this->error;
			return -1;
		}

		$tempfile = $dir.'/login-logo-upload-entity-'.$entity.'.'.$ext;
		$finalbasename = 'login-logo-entity-'.$entity.'.'.$ext;
		$finalfile = $dir.'/'.$finalbasename;
		if (is_file($tempfile) && !dol_delete_file($tempfile, 1, 1, 1, null, false, 0)) {
			$this->error = 'Unable to clear previous temporary login logo.';
			$this->errors[] = $this->error;
			return -1;
		}

		if (!dol_move_uploaded_file($tmpname, $tempfile, 1, 0, $uploaderror, 1, 'login_logo', $dir)) {
			$this->error = 'Unable to store uploaded login logo.';
			$this->errors[] = $this->error;
			return -1;
		}

		if (is_file($finalfile) && !dol_delete_file($finalfile, 1, 1, 1, null, false, 0)) {
			@unlink($tempfile);
			$this->error = 'Unable to replace the existing login logo.';
			$this->errors[] = $this->error;
			return -1;
		}

		if (!@rename($tempfile, $finalfile)) {
			@unlink($tempfile);
			$this->error = 'Unable to finalize the uploaded login logo.';
			$this->errors[] = $this->error;
			return -1;
		}

		$cleanupdirs = array($dir);
		$currententitydir = $this->getCurrentEntityLoginLogoDirectory();
		if ($currententitydir !== '') {
			$cleanupdirs[] = $currententitydir;
		}
		foreach ($cleanupdirs as $cleanupdir) {
			foreach (array_merge((array) glob($cleanupdir.'/login-logo-entity-'.$entity.'.*'), (array) glob($cleanupdir.'/login-company-icon-entity-'.$entity.'.*')) as $path) {
				if ($path !== $finalfile) {
					dol_delete_file($path, 1, 1, 1, null, false, 0);
				}
			}
		}

		if (dolibarr_set_const($this->db, self::LOGIN_LOGO_CONST, $finalbasename, 'chaine', 0, '', $entity) <= 0
			|| dolibarr_set_const($this->db, self::LEGACY_LOGIN_COMPANY_ICON_CONST, '', 'chaine', 0, '', $entity) <= 0
			|| $this->deleteGlobalLoginLogoConstants() < 0) {
			if ($this->error === '') {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
			}
			return -1;
		}

		return 1;
	}

	/**
	 * Store an uploaded login company icon after strict validation.
	 *
	 * @param array<string,mixed> $file Uploaded file array
	 * @param int|string          $entity Entity id
	 * @return int                     >0 if OK, <0 if KO
	 */
	public function storeLoginCompanyIconUpload(array $file, $entity = 0)
	{
		return $this->storeLoginLogoUpload($file, $entity);
	}

	/**
	 * Delete the configured login logo for an entity.
	 *
	 * @param int|string $entity Entity id
	 * @return int               >0 if OK, <0 if KO
	 */
	public function deleteLoginLogo($entity = 0)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$entity = $this->normalizeEntity($entity);

		$dir = $this->getLoginLogoDirectory();
		$paths = array_merge((array) glob($dir.'/login-logo-entity-'.$entity.'.*'), (array) glob($dir.'/login-company-icon-entity-'.$entity.'.*'));
		$currententitydir = $this->getCurrentEntityLoginLogoDirectory();
		if ($currententitydir !== '') {
			$paths = array_merge($paths, (array) glob($currententitydir.'/login-logo-entity-'.$entity.'.*'), (array) glob($currententitydir.'/login-company-icon-entity-'.$entity.'.*'));
		}
		$currentpath = $this->getLoginLogoPath($entity);
		if ($currentpath !== '' && dirname($currentpath) === $dir) {
			$paths[] = $currentpath;
		}
		foreach (array_unique($paths) as $path) {
			if (!dol_delete_file($path, 1, 1, 1, null, false, 0)) {
				$this->error = 'Unable to delete login logo.';
				$this->errors[] = $this->error;
				return -1;
			}
		}

		if (dolibarr_set_const($this->db, self::LOGIN_LOGO_CONST, '', 'chaine', 0, '', $entity) <= 0
			|| dolibarr_set_const($this->db, self::LEGACY_LOGIN_COMPANY_ICON_CONST, '', 'chaine', 0, '', $entity) <= 0
			|| $this->deleteGlobalLoginLogoConstants() < 0) {
			if ($this->error === '') {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
			}
			return -1;
		}

		return 1;
	}

	/**
	 * Delete the configured login company icon for an entity.
	 *
	 * @param int|string $entity Entity id
	 * @return int               >0 if OK, <0 if KO
	 */
	public function deleteLoginCompanyIcon($entity = 0)
	{
		return $this->deleteLoginLogo($entity);
	}

	/**
	 * Copy a legacy entity-specific logo file into the shared login logo directory.
	 *
	 * @param string $sourcepath      Existing entity-specific file path
	 * @param string $destinationpath Shared destination path
	 * @return int                    >0 if OK, <0 if KO
	 */
	private function migrateLoginLogoFileToSharedDirectory($sourcepath, $destinationpath)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		if (dol_mkdir(dirname($destinationpath)) < 0) {
			dol_syslog('Krea2FactorAuthManager::migrateLoginLogoFileToSharedDirectory unable to create login logo directory', LOG_WARNING);
			return -1;
		}

		$result = dol_copy($sourcepath, $destinationpath, '0', 0, 0, 0);
		if ($result < 0 || !is_file($destinationpath) || !is_readable($destinationpath)) {
			dol_syslog('Krea2FactorAuthManager::migrateLoginLogoFileToSharedDirectory unable to copy '.$sourcepath.' to '.$destinationpath, LOG_WARNING);
			return -1;
		}

		dol_delete_file($sourcepath, 1, 1, 1, null, false, 0);
		return 1;
	}

	/**
	 * Delegate password validation to the configured primary providers.
	 *
	 * @param string              $usertotest     Login
	 * @param string              $passwordtotest Password
	 * @param int|string          $entitytotest   Entity to test
	 * @param 'api'|'dav'|'ws'|'' $context        Authentication context
	 * @return string                             Login if OK, empty string if KO
	 */
	public function checkPrimaryLogin($usertotest, $passwordtotest, $entitytotest = 1, $context = '')
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

		// An OAuth return must be authenticated by its provider, never by a password fallback.
		$modes = $this->isGoogleLoginReturnRequest() ? array('googleoauth') : $this->getPrimaryAuthModes();
		return checkLoginPassEntity($usertotest, $passwordtotest, $entitytotest, $modes, $context);
	}

	/**
	 * Fetch a user matching the login and entity authenticated by Dolibarr.
	 *
	 * @param string     $login        Login
	 * @param int|string $entitytotest Entity to test
	 * @return User|null
	 */
	public function fetchAuthenticatedUser($login, $entitytotest)
	{
		$entity = $this->normalizeEntity($entitytotest);
		$usertmp = new User($this->db);
		$result = $usertmp->fetch(0, $login, '', 0, ($entity > 0 ? $entity : -1));
		if ($result > 0) {
			return $usertmp;
		}

		return null;
	}

	/**
	 * Return whether the current request starts a Google login redirect.
	 *
	 * @return bool
	 */
	public function isGoogleLoginStartRequest()
	{
		return GETPOST('actionlogin', 'aZ09') === 'login'
			&& GETPOST('beforeoauthloginredirect', 'alpha') === 'google';
	}

	/**
	 * Return whether the current request is returning from Google OAuth.
	 *
	 * @return bool
	 */
	public function isGoogleLoginReturnRequest()
	{
		return GETPOST('afteroauthloginreturn', 'alpha') === 'google';
	}

	/**
	 * Legacy helper that stores a submitted TOTP code before the browser leaves Dolibarr for Google.
	 *
	 * Current Google login policy skips the local TOTP challenge after a
	 * successful Google OAuth return. This method is retained for compatibility
	 * with older module call sites only.
	 *
	 * @param int|string $entity Login entity
	 * @return void
	 */
	public function preserveGoogleLoginTotpCode($entity)
	{
		if (!$this->isGoogleLoginEnabled() || !$this->isGoogleLoginStartRequest()) {
			return;
		}

		$code = $this->getSubmittedTotpCode('totp_code', 'totp_digit', 3);
		if ($code === '') {
			unset($_SESSION[self::GOOGLE_LOGIN_SUBMITTED_TOTP_SESSION]);
			return;
		}

		$_SESSION[self::GOOGLE_LOGIN_SUBMITTED_TOTP_SESSION] = array(
			'code' => $code,
			'entity' => $this->normalizeEntity($entity),
			'created' => dol_now(),
		);
	}

	/**
	 * Legacy helper that restores a preserved TOTP code after the Google OAuth callback returns.
	 *
	 * Current Google login policy skips the local TOTP challenge after a
	 * successful Google OAuth return. This method is retained for compatibility
	 * with older module call sites only.
	 *
	 * @param int|string $entity Login entity
	 * @return void
	 */
	public function restoreGoogleLoginTotpCode($entity)
	{
		if (!$this->isGoogleLoginEnabled() || !$this->isGoogleLoginReturnRequest()) {
			return;
		}

		if (empty($_SESSION[self::GOOGLE_LOGIN_SUBMITTED_TOTP_SESSION]) || !is_array($_SESSION[self::GOOGLE_LOGIN_SUBMITTED_TOTP_SESSION])) {
			return;
		}

		$stored = $_SESSION[self::GOOGLE_LOGIN_SUBMITTED_TOTP_SESSION];
		unset($_SESSION[self::GOOGLE_LOGIN_SUBMITTED_TOTP_SESSION]);

		if (empty($stored['code']) || empty($stored['created'])) {
			return;
		}
		if (!empty($stored['entity']) && (int) $stored['entity'] !== $this->normalizeEntity($entity)) {
			return;
		}
		if (((int) $stored['created'] + self::GOOGLE_LOGIN_PENDING_TTL) < dol_now()) {
			return;
		}

		$_POST['totp_code'] = $this->normalizeCode((string) $stored['code']);
	}

	/**
	 * Start a short-lived legacy server-side challenge after Google primary authentication.
	 *
	 * Current Google login policy no longer starts this challenge. The method is
	 * retained so stale sessions can be cleared without removing the public API.
	 *
	 * @param User       $authenticateduser Google-authenticated user
	 * @param int|string $entity            Login entity
	 * @return void
	 */
	public function startGoogleLoginPendingChallenge(User $authenticateduser, $entity)
	{
		$_SESSION[self::GOOGLE_LOGIN_PENDING_SESSION] = array(
			'login' => (string) $authenticateduser->login,
			'userid' => (int) $authenticateduser->id,
			'entity' => $this->normalizeEntity($entity),
			'created' => dol_now(),
		);
	}

	/**
	 * Return a pending Google-authenticated login from the legacy TOTP completion step.
	 *
	 * @param string     $usertotest   Posted login
	 * @param int|string $entitytotest Login entity
	 * @return string                  Login if a pending Google challenge is valid, empty string otherwise
	 */
	public function getPendingGoogleLogin($usertotest, $entitytotest)
	{
		if (!$this->isGoogleLoginEnabled() || $this->isGoogleLoginReturnRequest() || $this->isGoogleLoginStartRequest()) {
			return '';
		}
		if (empty($_SESSION[self::GOOGLE_LOGIN_PENDING_SESSION]) || !is_array($_SESSION[self::GOOGLE_LOGIN_PENDING_SESSION])) {
			return '';
		}

		$pending = $_SESSION[self::GOOGLE_LOGIN_PENDING_SESSION];
		if (empty($pending['login']) || empty($pending['created'])) {
			unset($_SESSION[self::GOOGLE_LOGIN_PENDING_SESSION]);
			return '';
		}
		if (((int) $pending['created'] + self::GOOGLE_LOGIN_PENDING_TTL) < dol_now()) {
			unset($_SESSION[self::GOOGLE_LOGIN_PENDING_SESSION]);
			return '';
		}
		if ((int) $pending['entity'] !== $this->normalizeEntity($entitytotest)) {
			return '';
		}
		if ((string) $pending['login'] !== (string) $usertotest) {
			return '';
		}

		return (string) $pending['login'];
	}

	/**
	 * Clear any pending Google login challenge.
	 *
	 * @return void
	 */
	public function clearGoogleLoginPendingChallenge()
	{
		unset($_SESSION[self::GOOGLE_LOGIN_PENDING_SESSION]);
		unset($_SESSION[self::GOOGLE_LOGIN_SUBMITTED_TOTP_SESSION]);
		$this->clearGoogleLoginReturnEmail();
	}

	/**
	 * Return whether a user has an active 2FA secret.
	 *
	 * The optional entity parameter is kept for backward compatibility with
	 * earlier module code. Krea2FactorAuth stores enrollment state globally
	 * so the same authenticator applies to every entity visible to the user.
	 *
	 * @param int        $userid User id
	 * @param int|string $entity Deprecated entity parameter
	 * @return bool
	 */
	public function isEnabledForUser($userid, $entity = 0)
	{
		return $this->getEncryptedSecret($userid, $entity) !== '';
	}

	/**
	 * Return whether a user must complete 2FA setup before continuing.
	 *
	 * Users with an active secret are considered validated even if a stale
	 * mandatory flag remains from a previous recovery workflow.
	 *
	 * @param int        $userid User id
	 * @param int|string $entity Deprecated entity parameter
	 * @return bool
	 */
	public function isMandatoryForUser($userid, $entity = 0)
	{
		return $this->isTotpEnrollmentAvailable()
			&& $this->getUserParamValue($userid, self::PARAM_MANDATORY_REQUIRED, $entity) === '1'
			&& !$this->isEnabledForUser($userid, $entity)
			&& !$this->isMandatoryExemptForUser($userid, $entity)
			&& !$this->isGoogleTwoFactorConfiguredForUser($userid, $entity);
	}

	/**
	 * Return whether the user has a raw mandatory enrollment policy flag.
	 *
	 * @param int        $userid User id
	 * @param int|string $entity Deprecated entity parameter
	 * @return bool
	 */
	public function hasMandatoryFlagForUser($userid, $entity = 0)
	{
		return $this->getUserParamValue($userid, self::PARAM_MANDATORY_REQUIRED, $entity) === '1';
	}

	/**
	 * Return whether a user is exempt from module-owned 2FA enforcement.
	 *
	 * This super-administrator policy exception allows the account to continue
	 * with primary credentials even when a TOTP secret exists.
	 *
	 * @param int        $userid User id
	 * @param int|string $entity Deprecated entity parameter
	 * @return bool
	 */
	public function isMandatoryExemptForUser($userid, $entity = 0)
	{
		return $this->getUserParamValue($userid, self::PARAM_MANDATORY_EXEMPT, $entity) === '1';
	}

	/**
	 * Return whether module policy locks a user out of Dolibarr.
	 *
	 * @param int        $userid User id
	 * @param int|string $entity Deprecated entity parameter
	 * @return bool
	 */
	public function isLoginLockedForUser($userid, $entity = 0)
	{
		return $this->getUserParamValue($userid, self::PARAM_LOGIN_LOCKED, $entity) === '1';
	}

	/**
	 * Get decrypted TOTP secret for a user.
	 *
	 * @param int        $userid User id
	 * @param int|string $entity Deprecated entity parameter
	 * @return string
	 */
	public function getSecretForUser($userid, $entity = 0)
	{
		$encryptedsecret = $this->getEncryptedSecret($userid, $entity);
		if ($encryptedsecret === '') {
			return '';
		}

		$secret = dolDecrypt($encryptedsecret);
		if ($secret === false) {
			$this->error = 'Unable to decrypt TOTP secret';
			$this->errors[] = $this->error;
			return '';
		}

		return (string) $secret;
	}

	/**
	 * Generate a new Base32 TOTP secret.
	 *
	 * @return string
	 */
	public function createSecret()
	{
		$olderrorreporting = $this->suppressVendorDeprecatedErrors();
		try {
			return $this->getTwoFactorAuth()->createSecret(160);
		} finally {
			error_reporting($olderrorreporting);
		}
	}

	/**
	 * Build the QR image data URI for a pending secret.
	 *
	 * @param string $label  Account label
	 * @param string $secret Base32 secret
	 * @return string
	 */
	public function getQrCodeDataUri($label, $secret)
	{
		$olderrorreporting = $this->suppressVendorDeprecatedErrors();
		try {
			return $this->getTwoFactorAuth()->getQRCodeImageAsDataUri($label, $secret, 220);
		} finally {
			error_reporting($olderrorreporting);
		}
	}

	/**
	 * Build the OTPAUTH URI for manual setup/debug.
	 *
	 * @param string $label  Account label
	 * @param string $secret Base32 secret
	 * @return string
	 */
	public function getQrText($label, $secret)
	{
		$olderrorreporting = $this->suppressVendorDeprecatedErrors();
		try {
			return $this->getTwoFactorAuth()->getQRText($label, $secret);
		} finally {
			error_reporting($olderrorreporting);
		}
	}

	/**
	 * Verify a submitted TOTP code against a raw secret.
	 *
	 * @param string $secret    Secret
	 * @param string $code      User code
	 * @param int    $timeslice Matching TOTP time slice, set by reference
	 * @return bool
	 */
	public function verifySecret($secret, $code, &$timeslice = 0)
	{
		$timeslice = 0;
		$code = $this->normalizeCode($code);
		if (!preg_match('/^[0-9]{6}$/', $code)) {
			return false;
		}

		$olderrorreporting = $this->suppressVendorDeprecatedErrors();
		try {
			return $this->getTwoFactorAuth()->verifyCode($secret, $code, $this->getCodeWindow(), null, $timeslice);
		} catch (Exception $e) {
			$this->error = $e->getMessage();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::verifySecret '.$this->error, LOG_ERR);
			return false;
		} finally {
			error_reporting($olderrorreporting);
		}
	}

	/**
	 * Verify a submitted TOTP code for a user.
	 *
	 * @param int        $userid User id
	 * @param int|string $entity Deprecated entity parameter
	 * @param string     $code   User code
	 * @return bool
	 */
	public function verifyCodeForUser($userid, $entity, $code)
	{
		$userid = (int) $userid;
		if ($this->isTotpAttemptLockedForUser($userid, $entity)) {
			dol_syslog('Krea2FactorAuthManager::verifyCodeForUser refused temporarily locked TOTP verification for user id '.$userid, LOG_WARNING);
			return false;
		}

		$secret = $this->getSecretForUser($userid, $entity);
		if ($secret === '') {
			return false;
		}

		$timeslice = 0;
		if (!$this->verifySecret($secret, $code, $timeslice)) {
			$this->registerTotpFailure($userid, $entity);
			return false;
		}

		$accepted = $this->acceptTotpTimesliceAndClearFailures($userid, $timeslice);
		if ($accepted <= 0) {
			if ($accepted == 0) {
				$this->error = 'TOTP code was already used.';
				$this->errors[] = $this->error;
				dol_syslog('Krea2FactorAuthManager::verifyCodeForUser refused replayed TOTP time slice for user id '.$userid, LOG_WARNING);
			}
			$this->registerTotpFailure($userid, $entity);
			return false;
		}

		return true;
	}

	/**
	 * Return whether TOTP verification is temporarily locked for a user.
	 *
	 * @param int        $userid User id
	 * @param int|string $entity Deprecated entity parameter
	 * @return bool
	 */
	public function isTotpAttemptLockedForUser($userid, $entity = 0)
	{
		$lockeduntil = (int) $this->getUserParamValue((int) $userid, self::PARAM_TOTP_LOCKED_UNTIL, $entity);

		return $lockeduntil > dol_now();
	}

	/**
	 * Register a failed TOTP verification attempt and apply a temporary lock when required.
	 *
	 * @param int        $userid User id
	 * @param int|string $entity Deprecated entity parameter
	 * @return int               >0 if OK, <0 if KO
	 */
	private function registerTotpFailure($userid, $entity = 0)
	{
		$userid = (int) $userid;
		$now = dol_now();
		$firstfailureat = (int) $this->getUserParamValue($userid, self::PARAM_TOTP_FAILURE_FIRST_AT, $entity);
		$failurecount = (int) $this->getUserParamValue($userid, self::PARAM_TOTP_FAILURE_COUNT, $entity);

		if ($firstfailureat <= 0 || $firstfailureat < ($now - self::TOTP_FAILURE_WINDOW_SECONDS)) {
			$firstfailureat = $now;
			$failurecount = 0;
		}

		$failurecount++;
		$params = array(
			self::PARAM_TOTP_FAILURE_COUNT => array('value' => (string) $failurecount, 'forcevalue' => 1),
			self::PARAM_TOTP_FAILURE_FIRST_AT => array('value' => (string) $firstfailureat, 'forcevalue' => 1),
			self::PARAM_TOTP_LOCKED_UNTIL => '',
		);

		if ($failurecount >= self::TOTP_FAILURE_LIMIT) {
			$params[self::PARAM_TOTP_LOCKED_UNTIL] = array('value' => (string) ($now + self::TOTP_LOCK_SECONDS), 'forcevalue' => 1);
			dol_syslog('Krea2FactorAuthManager::registerTotpFailure temporarily locked TOTP verification for user id '.$userid, LOG_WARNING);
		}

		return $this->setGlobalUserParamValues($userid, $params);
	}

	/**
	 * Persist the accepted TOTP time slice and clear temporary failure state.
	 *
	 * @param int $userid    User id
	 * @param int $timeslice Matching TOTP time slice
	 * @return int           >0 if OK, 0 if replayed, <0 if KO
	 */
	private function acceptTotpTimesliceAndClearFailures($userid, $timeslice)
	{
		$userid = (int) $userid;
		$timeslice = (int) $timeslice;
		if ($userid <= 0 || $timeslice <= 0) {
			return -1;
		}

		$formattedtimeslice = $this->formatTotpTimeslice($timeslice);
		$param = self::PARAM_TOTP_LAST_TIMESLICE;

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."user_param";
		$sql .= " SET value = '".$this->db->escape($formattedtimeslice)."'";
		$sql .= " WHERE fk_user = ".$userid;
		$sql .= " AND entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND param = '".$this->db->escape($param)."'";
		$sql .= " AND value < '".$this->db->escape($formattedtimeslice)."'";
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			$this->db->rollback();
			return -1;
		}

		$claimed = ($this->db->affected_rows($resql) > 0);
		if (!$claimed) {
			$sql = "SELECT value";
			$sql .= " FROM ".MAIN_DB_PREFIX."user_param";
			$sql .= " WHERE fk_user = ".$userid;
			$sql .= " AND entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
			$sql .= " AND param = '".$this->db->escape($param)."'";

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				$this->db->rollback();
				return -1;
			}

			$obj = $this->db->fetch_object($resql);
			$currentvalue = $obj ? (string) $obj->value : '';
			$this->db->free($resql);

			if ($currentvalue !== '') {
				$this->db->rollback();
				return 0;
			}

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_param(fk_user, entity, param, value)";
			$sql .= " VALUES (".$userid.", ".((int) self::GLOBAL_USER_PARAM_ENTITY).",";
			$sql .= " '".$this->db->escape($param)."', '".$this->db->escape($formattedtimeslice)."')";

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				$this->db->rollback();
				return -1;
			}
		}

		if ($this->deleteGlobalUserParamsInOpenTransaction($userid, array(
			self::PARAM_TOTP_FAILURE_COUNT,
			self::PARAM_TOTP_FAILURE_FIRST_AT,
			self::PARAM_TOTP_LOCKED_UNTIL,
		)) < 0) {
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		return 1;
	}

	/**
	 * Read a submitted TOTP code from the legacy single input or from six digit inputs.
	 *
	 * @param string   $fieldname       Hidden/legacy full-code field name
	 * @param string   $digitfieldname  Repeated digit field name
	 * @param int<0,3> $method          Type of method for GETPOST
	 * @return string                   Normalized numeric code
	 */
	public function getSubmittedTotpCode($fieldname = 'totp_code', $digitfieldname = 'totp_digit', $method = 3)
	{
		$code = GETPOST($fieldname, 'alphanohtml', $method);
		if ($code === '' && GETPOSTISARRAY($digitfieldname, $method)) {
			$digits = GETPOST($digitfieldname, 'array:aZ09', $method);
			if (is_array($digits)) {
				$code = '';
				foreach ($digits as $digit) {
					$code .= (string) $digit;
				}
			}
		}

		return $this->normalizeCode($code);
	}

	/**
	 * Save a verified TOTP secret for a user.
	 *
	 * @param User   $targetuser User object
	 * @param string $secret     Base32 secret
	 * @return int               >0 if OK, <0 if KO
	 */
	public function enableForUser(User $targetuser, $secret)
	{
		if (!preg_match('/^[A-Z2-7]+=*$/', $secret)) {
			$this->error = 'Invalid TOTP secret format';
			$this->errors[] = $this->error;
			return -1;
		}

		$encryptedsecret = dolEncrypt($secret);
		$params = array(
			self::PARAM_SECRET => array('value' => $encryptedsecret, 'forcevalue' => 1),
			self::PARAM_ENABLED_AT => array('value' => (string) dol_now(), 'forcevalue' => 1),
			self::PARAM_MANDATORY_REQUIRED => '',
			self::PARAM_MANDATORY_REQUIRED_AT => '',
			self::PARAM_MANDATORY_REQUIRED_BY => '',
			self::PARAM_MANDATORY_EXEMPT => '',
			self::PARAM_MANDATORY_EXEMPT_AT => '',
			self::PARAM_MANDATORY_EXEMPT_BY => '',
			self::PARAM_POLICY_LISTED => array('value' => '1', 'forcevalue' => 1),
			self::PARAM_TOTP_FAILURE_COUNT => '',
			self::PARAM_TOTP_FAILURE_FIRST_AT => '',
			self::PARAM_TOTP_LOCKED_UNTIL => '',
			self::PARAM_TOTP_LAST_TIMESLICE => '',
		);

		$result = $this->setGlobalUserParams($targetuser, $params);
		if ($result < 0) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::enableForUser '.$this->error, LOG_ERR);
			return -1;
		}

		dol_syslog('Krea2FactorAuthManager::enableForUser enabled 2FA for user id '.((int) $targetuser->id), LOG_INFO);
		return 1;
	}

	/**
	 * Mark a user as required to complete 2FA enrollment.
	 *
	 * This is a module policy flag, not Dolibarr's core user disabled state.
	 * The user must remain able to authenticate so the module can redirect
	 * them to the enrollment page.
	 *
	 * @param User $targetuser  Target user
	 * @param User $currentuser Current user authorizing the policy change
	 * @return int             >0 if OK, <0 if KO
	 */
	public function requireEnrollmentForUser(User $targetuser, User $currentuser)
	{
		if (!$this->isSuperAdmin($currentuser)) {
			$this->error = 'Only a super administrator can require mandatory two-factor enrollment.';
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::requireEnrollmentForUser denied for user id '.((int) $currentuser->id).' on target user id '.((int) $targetuser->id), LOG_WARNING);
			return -1;
		}

		$params = array(
			self::PARAM_MANDATORY_REQUIRED => array('value' => '1', 'forcevalue' => 1),
			self::PARAM_MANDATORY_REQUIRED_AT => array('value' => (string) dol_now(), 'forcevalue' => 1),
			self::PARAM_MANDATORY_REQUIRED_BY => array('value' => (string) $currentuser->id, 'forcevalue' => 1),
			self::PARAM_MANDATORY_EXEMPT => '',
			self::PARAM_MANDATORY_EXEMPT_AT => '',
			self::PARAM_MANDATORY_EXEMPT_BY => '',
			self::PARAM_POLICY_LISTED => array('value' => '1', 'forcevalue' => 1),
		);

		$result = $this->setGlobalUserParams($targetuser, $params);
		if ($result < 0) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::requireEnrollmentForUser '.$this->error, LOG_ERR);
			return -1;
		}

		dol_syslog('Krea2FactorAuthManager::requireEnrollmentForUser required 2FA enrollment for user id '.((int) $targetuser->id), LOG_WARNING);
		return 1;
	}

	/**
	 * Clear mandatory enrollment flags for a user.
	 *
	 * @param User $targetuser Target user
	 * @return int            >0 if OK, <0 if KO
	 */
	public function clearMandatoryEnrollmentForUser(User $targetuser)
	{
		$params = array(
			self::PARAM_MANDATORY_REQUIRED => '',
			self::PARAM_MANDATORY_REQUIRED_AT => '',
			self::PARAM_MANDATORY_REQUIRED_BY => '',
			self::PARAM_POLICY_LISTED => array('value' => '1', 'forcevalue' => 1),
		);

		$result = $this->setGlobalUserParams($targetuser, $params);
		if ($result < 0) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::clearMandatoryEnrollmentForUser '.$this->error, LOG_ERR);
			return -1;
		}

		dol_syslog('Krea2FactorAuthManager::clearMandatoryEnrollmentForUser cleared mandatory enrollment for user id '.((int) $targetuser->id), LOG_INFO);
		return 1;
	}

	/**
	 * Mark or remove a super-administrator approved mandatory enrollment exception.
	 *
	 * @param User $targetuser  Target user
	 * @param User $currentuser Current user authorizing the policy change
	 * @param bool $exempt      True to exempt, false to require enrollment
	 * @return int              >0 if OK, <0 if KO
	 */
	public function setMandatoryExemptionForUser(User $targetuser, User $currentuser, $exempt)
	{
		if (!$this->isSuperAdmin($currentuser)) {
			$this->error = 'Only a super administrator can manage mandatory two-factor exceptions.';
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::setMandatoryExemptionForUser denied for user id '.((int) $currentuser->id).' on target user id '.((int) $targetuser->id), LOG_WARNING);
			return -1;
		}

		$exempt = (bool) $exempt;
		$enabled = $this->isEnabledForUser((int) $targetuser->id);

		if ($exempt) {
			$params = array(
				self::PARAM_MANDATORY_REQUIRED => '',
				self::PARAM_MANDATORY_REQUIRED_AT => '',
				self::PARAM_MANDATORY_REQUIRED_BY => '',
				self::PARAM_MANDATORY_EXEMPT => array('value' => '1', 'forcevalue' => 1),
				self::PARAM_MANDATORY_EXEMPT_AT => array('value' => (string) dol_now(), 'forcevalue' => 1),
				self::PARAM_MANDATORY_EXEMPT_BY => array('value' => (string) $currentuser->id, 'forcevalue' => 1),
				self::PARAM_POLICY_LISTED => array('value' => '1', 'forcevalue' => 1),
			);
		} else {
			$params = array(
				self::PARAM_MANDATORY_REQUIRED => ($enabled ? '' : array('value' => '1', 'forcevalue' => 1)),
				self::PARAM_MANDATORY_REQUIRED_AT => ($enabled ? '' : array('value' => (string) dol_now(), 'forcevalue' => 1)),
				self::PARAM_MANDATORY_REQUIRED_BY => ($enabled ? '' : array('value' => (string) $currentuser->id, 'forcevalue' => 1)),
				self::PARAM_MANDATORY_EXEMPT => '',
				self::PARAM_MANDATORY_EXEMPT_AT => '',
				self::PARAM_MANDATORY_EXEMPT_BY => '',
				self::PARAM_POLICY_LISTED => array('value' => '1', 'forcevalue' => 1),
			);
		}

		$result = $this->setGlobalUserParams($targetuser, $params);
		if ($result < 0) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::setMandatoryExemptionForUser '.$this->error, LOG_ERR);
			return -1;
		}

		dol_syslog('Krea2FactorAuthManager::setMandatoryExemptionForUser '.($exempt ? 'exempted' : 'required').' user id '.((int) $targetuser->id), LOG_WARNING);
		return 1;
	}

	/**
	 * Exempt a user and rotate the Dolibarr password to a Dolibarr-generated value.
	 *
	 * @param User   $targetuser        Target user
	 * @param User   $currentuser       Current user authorizing the policy change
	 * @param string $generatedpassword Generated password, returned by reference for one-time display
	 * @param bool   $passwordrotated   Whether the password was changed before a later failure
	 * @return int                      >0 if OK, <0 if KO
	 */
	public function exemptUserWithGeneratedPassword(User $targetuser, User $currentuser, &$generatedpassword, &$passwordrotated)
	{
		$generatedpassword = '';
		$passwordrotated = false;

		$result = $this->generateExceptionPasswordForUser($targetuser, $currentuser, $generatedpassword);
		if ($result < 0) {
			return -1;
		}
		$passwordrotated = true;

		$result = $this->setMandatoryExemptionForUser($targetuser, $currentuser, true);
		if ($result < 0) {
			return -1;
		}

		dol_syslog('Krea2FactorAuthManager::exemptUserWithGeneratedPassword rotated password and exempted user id '.((int) $targetuser->id), LOG_WARNING);
		return 1;
	}

	/**
	 * Rotate the Dolibarr password for a 2FA-exempt operational account.
	 *
	 * @param User   $targetuser        Target user
	 * @param User   $currentuser       Current user authorizing the password rotation
	 * @param string $generatedpassword Generated password, returned by reference for one-time display
	 * @return int                      >0 if OK, <0 if KO
	 */
	public function generateExceptionPasswordForUser(User $targetuser, User $currentuser, &$generatedpassword)
	{
		$generatedpassword = '';

		if (!$this->isSuperAdmin($currentuser)) {
			$this->error = 'Only a super administrator can rotate exception passwords.';
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::generateExceptionPasswordForUser denied for user id '.((int) $currentuser->id).' on target user id '.((int) $targetuser->id), LOG_WARNING);
			return -1;
		}

		require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

		$generatedpassword = getRandomPassword(false);
		if ($generatedpassword === '') {
			$this->error = 'Dolibarr password generation is not configured. Configure a password generator before rotating an exception password.';
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::generateExceptionPasswordForUser '.$this->error, LOG_ERR);
			return -1;
		}

		$result = $targetuser->setPassword($currentuser, $generatedpassword, 0, 0, 0, 0, 1);
		if ((is_int($result) && $result < 0) || $result === 0) {
			$this->error = !empty($targetuser->error) ? $targetuser->error : 'Unable to set generated exception password for user '.$targetuser->login.'.';
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::generateExceptionPasswordForUser '.$this->error, LOG_ERR);
			$generatedpassword = '';
			return -1;
		}

		dol_syslog('Krea2FactorAuthManager::generateExceptionPasswordForUser rotated password for user id '.((int) $targetuser->id), LOG_WARNING);
		return 1;
	}

	/**
	 * Reset the current authenticator and force a fresh enrollment.
	 *
	 * @param User $targetuser  Target user
	 * @param User $currentuser Current user authorizing the reset
	 * @return int             >0 if OK, <0 if KO
	 */
	public function resetEnrollmentForUser(User $targetuser, User $currentuser)
	{
		if (!$this->isSuperAdmin($currentuser)) {
			$this->error = 'Only a super administrator can reset two-factor enrollment.';
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::resetEnrollmentForUser denied for user id '.((int) $currentuser->id).' on target user id '.((int) $targetuser->id), LOG_WARNING);
			return -1;
		}

		$params = array(
			self::PARAM_SECRET => '',
			self::PARAM_ENABLED_AT => '',
			self::PARAM_MANDATORY_REQUIRED => array('value' => '1', 'forcevalue' => 1),
			self::PARAM_MANDATORY_REQUIRED_AT => array('value' => (string) dol_now(), 'forcevalue' => 1),
			self::PARAM_MANDATORY_REQUIRED_BY => array('value' => (string) $currentuser->id, 'forcevalue' => 1),
			self::PARAM_MANDATORY_EXEMPT => '',
			self::PARAM_MANDATORY_EXEMPT_AT => '',
			self::PARAM_MANDATORY_EXEMPT_BY => '',
			self::PARAM_POLICY_LISTED => array('value' => '1', 'forcevalue' => 1),
			self::PARAM_TOTP_FAILURE_COUNT => '',
			self::PARAM_TOTP_FAILURE_FIRST_AT => '',
			self::PARAM_TOTP_LOCKED_UNTIL => '',
			self::PARAM_TOTP_LAST_TIMESLICE => '',
		);

		$result = $this->setGlobalUserParams($targetuser, $params, true);
		if ($result < 0) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::resetEnrollmentForUser '.$this->error, LOG_ERR);
			return -1;
		}

		dol_syslog('Krea2FactorAuthManager::resetEnrollmentForUser reset 2FA enrollment for user id '.((int) $targetuser->id), LOG_WARNING);
		return 1;
	}

	/**
	 * Lock or unlock a user's Dolibarr login through module policy.
	 *
	 * @param User $targetuser  Target user
	 * @param User $currentuser Current user authorizing the change
	 * @param bool $locked      True to lock, false to unlock
	 * @return int             >0 if OK, <0 if KO
	 */
	public function setLoginLockForUser(User $targetuser, User $currentuser, $locked)
	{
		if (!$this->isSuperAdmin($currentuser)) {
			$this->error = 'Only a super administrator can lock or unlock users with two-factor policy.';
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::setLoginLockForUser denied for user id '.((int) $currentuser->id).' on target user id '.((int) $targetuser->id), LOG_WARNING);
			return -1;
		}

		if ($locked && (int) $targetuser->id === (int) $currentuser->id) {
			$this->error = 'A super administrator cannot lock their own active account.';
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::setLoginLockForUser refused self-lock for user id '.((int) $currentuser->id), LOG_WARNING);
			return -1;
		}

		if ($locked) {
			$params = array(
				self::PARAM_LOGIN_LOCKED => array('value' => '1', 'forcevalue' => 1),
				self::PARAM_LOGIN_LOCKED_AT => array('value' => (string) dol_now(), 'forcevalue' => 1),
				self::PARAM_LOGIN_LOCKED_BY => array('value' => (string) $currentuser->id, 'forcevalue' => 1),
				self::PARAM_POLICY_LISTED => array('value' => '1', 'forcevalue' => 1),
			);
		} else {
			$params = array(
				self::PARAM_LOGIN_LOCKED => '',
				self::PARAM_LOGIN_LOCKED_AT => '',
				self::PARAM_LOGIN_LOCKED_BY => '',
				self::PARAM_POLICY_LISTED => array('value' => '1', 'forcevalue' => 1),
			);
		}

		$result = $this->setGlobalUserParams($targetuser, $params);
		if ($result < 0) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::setLoginLockForUser '.$this->error, LOG_ERR);
			return -1;
		}

		dol_syslog('Krea2FactorAuthManager::setLoginLockForUser '.($locked ? 'locked' : 'unlocked').' user id '.((int) $targetuser->id), LOG_WARNING);
		return 1;
	}

	/**
	 * Remove TOTP state for a user in the current entity.
	 *
	 * @param User      $targetuser  User object
	 * @param User|null $currentuser Current user authorizing the recovery action
	 * @return int                   >0 if OK, <0 if KO
	 */
	public function disableForUser(User $targetuser, ?User $currentuser = null)
	{
		global $user;

		if ($currentuser === null && isset($user) && $user instanceof User) {
			$currentuser = $user;
		}

		if (!$currentuser instanceof User || !$this->isSuperAdmin($currentuser)) {
			$this->error = 'Only a super administrator can disable two-factor authentication after enrollment.';
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::disableForUser denied for user id '.($currentuser instanceof User ? (int) $currentuser->id : 0).' on target user id '.((int) $targetuser->id), LOG_WARNING);
			return -1;
		}

		$params = array(
			self::PARAM_SECRET => '',
			self::PARAM_ENABLED_AT => '',
			self::PARAM_MANDATORY_EXEMPT => '',
			self::PARAM_MANDATORY_EXEMPT_AT => '',
			self::PARAM_MANDATORY_EXEMPT_BY => '',
			self::PARAM_POLICY_LISTED => array('value' => '1', 'forcevalue' => 1),
			self::PARAM_TOTP_FAILURE_COUNT => '',
			self::PARAM_TOTP_FAILURE_FIRST_AT => '',
			self::PARAM_TOTP_LOCKED_UNTIL => '',
			self::PARAM_TOTP_LAST_TIMESLICE => '',
		);

		$result = $this->setGlobalUserParams($targetuser, $params, true);
		if ($result < 0) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::disableForUser '.$this->error, LOG_ERR);
			return -1;
		}

		dol_syslog('Krea2FactorAuthManager::disableForUser disabled 2FA for user id '.((int) $targetuser->id), LOG_WARNING);
		return 1;
	}

	/**
	 * Fetch users with enabled 2FA visible from the current entity.
	 *
	 * @return array<int,array{rowid:int,login:string,name:string,entity:int}>
	 */
	public function fetchEnabledUsersInCurrentEntity()
	{
		$users = array();
		$sql = "SELECT u.rowid, u.login, u.lastname, u.firstname, u.entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."user_param as p";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = p.fk_user";
		$sql .= " WHERE p.param = '".$this->db->escape(self::PARAM_SECRET)."'";
		$sql .= " AND p.entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND u.entity IN (".getEntity('user').")";
		$sql .= " ORDER BY u.login ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$name = trim($obj->firstname.' '.$obj->lastname);
				$users[] = array(
					'rowid' => (int) $obj->rowid,
					'login' => (string) $obj->login,
					'name' => $name,
					'entity' => (int) $obj->entity,
				);
			}
			$this->db->free($resql);
		} else {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::fetchEnabledUsersInCurrentEntity '.$this->error, LOG_ERR);
		}

		return $users;
	}

	/**
	 * Count users managed by 2FA policy in the current entity visibility.
	 *
	 * @return int Number of managed users
	 */
	public function countPolicyUsersInCurrentEntity(array $filters = array())
	{
		$globalmandatory = $this->isMandatoryEnrollmentPromptEnabled();
		$googlefieldsql = $this->getGoogleLoginEmailSqlExpression();

		$sql = "SELECT COUNT(DISTINCT u.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_extrafields as uef ON uef.fk_object = u.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_param as s ON s.fk_user = u.rowid";
		$sql .= " AND s.entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND s.param = '".$this->db->escape(self::PARAM_SECRET)."'";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_param as m ON m.fk_user = u.rowid";
		$sql .= " AND m.entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND m.param = '".$this->db->escape(self::PARAM_MANDATORY_REQUIRED)."'";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_param as ex ON ex.fk_user = u.rowid";
		$sql .= " AND ex.entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND ex.param = '".$this->db->escape(self::PARAM_MANDATORY_EXEMPT)."'";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_param as l ON l.fk_user = u.rowid";
		$sql .= " AND l.entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND l.param = '".$this->db->escape(self::PARAM_LOGIN_LOCKED)."'";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_param as pl ON pl.fk_user = u.rowid";
		$sql .= " AND pl.entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND pl.param = '".$this->db->escape(self::PARAM_POLICY_LISTED)."'";
		$sql .= " WHERE u.entity IN (".getEntity('user').")";
		$sql .= $this->buildPolicyUsersFilterSql($filters, $globalmandatory, $googlefieldsql);

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::countPolicyUsersInCurrentEntity '.$this->error, LOG_ERR);
			return 0;
		}

		$obj = $this->db->fetch_object($resql);
		$count = $obj ? (int) $obj->nb : 0;
		$this->db->free($resql);

		return $count;
	}

	/**
	 * Fetch users managed by 2FA policy in the current entity visibility.
	 *
	 * @param int                  $limit     Maximum number of rows to fetch
	 * @param int                  $offset    Offset for paginated reads
	 * @param array<string,string> $filters   Normalized filters
	 * @param string               $sortfield Dolibarr list sort field
	 * @param string               $sortorder Dolibarr list sort order
	 * @return array<int,array{rowid:int,login:string,name:string,google:bool,google_email:string,entity:int,statut:int,enabled:bool,mandatory:bool,exempt:bool,locked:bool,listed:bool,password_expiration_enabled:bool,password_expired:bool,password_last_changed_at:int,password_expires_at:int}>
	 */
	public function fetchPolicyUsersInCurrentEntity($limit = 0, $offset = 0, array $filters = array(), $sortfield = 'u.login', $sortorder = 'ASC')
	{
		$users = array();
		$limit = (int) $limit;
		$offset = (int) $offset;
		$globalmandatory = $this->isMandatoryEnrollmentPromptEnabled();
		$googlefieldsql = $this->getGoogleLoginEmailSqlExpression();
		$googlepolicyavailable = $this->isGoogleTwoFactorAvailable();
		if ($offset < 0) {
			$offset = 0;
		}

		$sql = "SELECT u.rowid, u.login, u.lastname, u.firstname, u.email, u.entity, u.statut, u.datelastpassvalidation,";
		$sql .= " ".$googlefieldsql." as google_login_emails,";
		$sql .= " s.value as secret, m.value as mandatory_required, ex.value as mandatory_exempt, l.value as login_locked, pl.value as policy_listed,";
		$sql .= " pe.value as password_expiration_enabled, pea.value as password_expiration_enabled_at,";
		$sql .= " (CASE WHEN COALESCE(pe.value, '') = '1' THEN 1 ELSE 0 END) as password_expiration_enabled_sort,";
		$sql .= " (CASE WHEN u.datelastpassvalidation IS NULL THEN 0 ELSE 1 END) as password_expiration_has_date_sort,";
		$sql .= " u.datelastpassvalidation as password_expiration_date_sort,";
		$sql .= " COALESCE(pea.value, '') as password_expiration_enabled_at_sort,";
		$sql .= " ".$this->getPolicyUsersTotpSortSql($globalmandatory, $googlefieldsql)." as totp2fa_sort,";
		$sql .= " ".$this->getPolicyUsersGoogleSortSql($googlefieldsql)." as google2fa_sort";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_extrafields as uef ON uef.fk_object = u.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_param as s ON s.fk_user = u.rowid";
		$sql .= " AND s.entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND s.param = '".$this->db->escape(self::PARAM_SECRET)."'";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_param as m ON m.fk_user = u.rowid";
		$sql .= " AND m.entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND m.param = '".$this->db->escape(self::PARAM_MANDATORY_REQUIRED)."'";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_param as ex ON ex.fk_user = u.rowid";
		$sql .= " AND ex.entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND ex.param = '".$this->db->escape(self::PARAM_MANDATORY_EXEMPT)."'";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_param as l ON l.fk_user = u.rowid";
		$sql .= " AND l.entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND l.param = '".$this->db->escape(self::PARAM_LOGIN_LOCKED)."'";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_param as pl ON pl.fk_user = u.rowid";
		$sql .= " AND pl.entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND pl.param = '".$this->db->escape(self::PARAM_POLICY_LISTED)."'";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_param as pe ON pe.fk_user = u.rowid";
		$sql .= " AND pe.entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND pe.param = '".$this->db->escape(self::PARAM_PASSWORD_EXPIRATION_ENABLED)."'";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_param as pea ON pea.fk_user = u.rowid";
		$sql .= " AND pea.entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND pea.param = '".$this->db->escape(self::PARAM_PASSWORD_EXPIRATION_ENABLED_AT)."'";
		$sql .= " WHERE u.entity IN (".getEntity('user').")";
		$sql .= $this->buildPolicyUsersFilterSql($filters, $globalmandatory, $googlefieldsql);
		$sql .= $this->getPolicyUsersOrderSql($sortfield, $sortorder);
		if ($limit > 0) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$name = trim($obj->firstname.' '.$obj->lastname);
				$enabled = ((string) $obj->secret !== '');
				$exempt = ((string) $obj->mandatory_exempt === '1');
				$googleemails = trim((string) $obj->google_login_emails);
				$googleconfigured = ($googleemails !== '');
				$googlepolicysatisfied = ($googlepolicyavailable && $googleconfigured);
				$mandatory = (((string) $obj->mandatory_required === '1' || $globalmandatory) && !$enabled && !$exempt && !$googlepolicysatisfied);
				$passwordexpirationenabled = ((string) $obj->password_expiration_enabled === '1');
				$passwordlastchangedat = empty($obj->datelastpassvalidation) ? (int) $obj->password_expiration_enabled_at : $this->db->jdate($obj->datelastpassvalidation);
				$passwordexpiresat = ($passwordexpirationenabled && $passwordlastchangedat > 0) ? dol_time_plus_duree($passwordlastchangedat, $this->getPasswordExpirationMaxDays(), 'd') : 0;
				$users[] = array(
					'rowid' => (int) $obj->rowid,
					'login' => (string) $obj->login,
					'name' => $name,
					'google' => $googleconfigured,
					'google_email' => $googleemails,
					'entity' => (int) $obj->entity,
					'statut' => (int) $obj->statut,
					'enabled' => $enabled,
					'mandatory' => $mandatory,
					'exempt' => $exempt,
					'locked' => ((string) $obj->login_locked === '1'),
					'listed' => ((string) $obj->policy_listed === '1'),
					'password_expiration_enabled' => $passwordexpirationenabled,
					'password_expired' => ($passwordexpiresat > 0 && $passwordexpiresat <= dol_now()),
					'password_last_changed_at' => (int) $passwordlastchangedat,
					'password_expires_at' => (int) $passwordexpiresat,
				);
			}
			$this->db->free($resql);
		} else {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::fetchPolicyUsersInCurrentEntity '.$this->error, LOG_ERR);
		}

		return $users;
	}

	/**
	 * Build SQL ordering for the visible users policy table from Dolibarr list parameters.
	 *
	 * @param string $sortfield Dolibarr list sort field
	 * @param string $sortorder Dolibarr list sort order
	 * @return string           SQL order fragment
	 */
	private function getPolicyUsersOrderSql($sortfield, $sortorder)
	{
		$sortfield = trim((string) $sortfield);
		$normalizedorders = array();
		foreach (explode(',', (string) $sortorder) as $order) {
			$order = strtoupper(trim($order));
			$normalizedorders[] = in_array($order, array('ASC', 'DESC'), true) ? $order : 'ASC';
		}
		if (empty($normalizedorders)) {
			$normalizedorders[] = 'ASC';
		}

		if ($sortfield === 'password_expiration') {
			$passwordexpirationorder = $normalizedorders[0];
			$sql = " ORDER BY password_expiration_enabled_sort DESC";
			$sql .= ", password_expiration_has_date_sort DESC";
			$sql .= ", password_expiration_date_sort ".$passwordexpirationorder;
			$sql .= ", password_expiration_enabled_at_sort ".$passwordexpirationorder;
			$sql .= ", u.login ASC";
			return $sql;
		}

		$allowedfields = array(
			'u.login' => 'u.login',
			'u.lastname' => 'u.lastname,u.firstname,u.login',
			'u.lastname,u.firstname,u.login' => 'u.lastname,u.firstname,u.login',
			'u.statut' => 'u.statut,u.login',
			'totp2fa' => 'totp2fa_sort,u.login',
			'google2fa' => 'google2fa_sort,u.login',
		);

		if (!isset($allowedfields[$sortfield])) {
			$sortfield = 'u.login';
		}

		return $this->db->order($allowedfields[$sortfield], implode(',', $normalizedorders));
	}

	/**
	 * Return SQL expression used to sort visible users by TOTP policy state.
	 *
	 * @param bool   $globalmandatory Whether global mandatory enrollment is enabled
	 * @param string $googlefieldsql  SQL expression for module Google login emails
	 * @return string                 SQL expression
	 */
	private function getPolicyUsersTotpSortSql($globalmandatory, $googlefieldsql)
	{
		$mandatorysql = "COALESCE(s.value, '') = '' AND COALESCE(ex.value, '') <> '1'";
		$mandatorysql .= " AND NOT (".$this->buildGooglePolicyConfiguredSql($googlefieldsql).")";
		if (!$globalmandatory) {
			$mandatorysql .= " AND COALESCE(m.value, '') = '1'";
		}

		$sql = "(CASE";
		$sql .= " WHEN COALESCE(l.value, '') = '1' THEN 5";
		$sql .= " WHEN COALESCE(ex.value, '') = '1' THEN 4";
		$sql .= " WHEN COALESCE(s.value, '') <> '' THEN 3";
		$sql .= " WHEN ".$mandatorysql." THEN 2";
		$sql .= " ELSE 1 END)";

		return $sql;
	}

	/**
	 * Return SQL expression used to sort visible users by Google 2FA association.
	 *
	 * @param string $googlefieldsql SQL expression for module Google login emails
	 * @return string                SQL expression
	 */
	private function getPolicyUsersGoogleSortSql($googlefieldsql)
	{
		return "(CASE WHEN ".$this->buildGoogleConfiguredSql($googlefieldsql)." THEN 1 ELSE 0 END)";
	}

	/**
	 * Build SQL filters for the visible users policy table.
	 *
	 * @param array<string,string> $filters          Normalized filters
	 * @param bool                 $globalmandatory  Whether global mandatory enrollment is enabled
	 * @param string               $googlefieldsql   SQL expression for module Google login emails
	 * @return string                                SQL where fragment
	 */
	private function buildPolicyUsersFilterSql(array $filters, $globalmandatory, $googlefieldsql)
	{
		$sql = '';
		$login = empty($filters['login']) ? '' : trim((string) $filters['login']);
		$name = empty($filters['name']) ? '' : trim((string) $filters['name']);
		$status = empty($filters['status']) ? '' : trim((string) $filters['status']);
		$policy = empty($filters['policy']) ? '' : trim((string) $filters['policy']);
		$google = empty($filters['google']) ? '' : trim((string) $filters['google']);
		$googleconfiguredsql = $this->buildGoogleConfiguredSql($googlefieldsql);
		$googlepolicyconfiguredsql = $this->buildGooglePolicyConfiguredSql($googlefieldsql);

		if ($login !== '') {
			$sql .= " AND u.login LIKE '%".$this->db->escape($this->db->escapeforlike($login))."%'";
		}
		if ($name !== '') {
			$escapedname = $this->db->escape($this->db->escapeforlike($name));
			$sql .= " AND (u.lastname LIKE '%".$escapedname."%' OR u.firstname LIKE '%".$escapedname."%')";
		}
		if ($status === 'enabled') {
			$sql .= " AND u.statut = 1";
		} elseif ($status === 'disabled') {
			$sql .= " AND u.statut = 0";
		}

		if ($policy === 'active') {
			$sql .= " AND COALESCE(s.value, '') <> ''";
		} elseif ($policy === 'mandatory') {
			$sql .= " AND COALESCE(s.value, '') = ''";
			$sql .= " AND COALESCE(ex.value, '') <> '1'";
			$sql .= " AND NOT (".$googlepolicyconfiguredsql.")";
			if (!$globalmandatory) {
				$sql .= " AND m.value = '1'";
			}
		} elseif ($policy === 'exempt') {
			$sql .= " AND ex.value = '1'";
		} elseif ($policy === 'optional') {
			$sql .= " AND COALESCE(s.value, '') = ''";
			$sql .= " AND COALESCE(ex.value, '') <> '1'";
			if ($globalmandatory) {
				$sql .= " AND ".$googlepolicyconfiguredsql;
			} else {
				$sql .= " AND (COALESCE(m.value, '') <> '1' OR ".$googlepolicyconfiguredsql.")";
			}
		} elseif ($policy === 'locked') {
			$sql .= " AND l.value = '1'";
		} elseif ($policy === 'unenrolled') {
			$sql .= " AND COALESCE(s.value, '') = ''";
		}

		if ($google === 'configured') {
			$sql .= " AND ".$googleconfiguredsql;
		} elseif ($google === 'not_configured') {
			$sql .= " AND NOT (".$googleconfiguredsql.")";
		}

		return $sql;
	}

	/**
	 * Return SQL expression for the module-owned Google login email extrafield.
	 *
	 * @return string SQL expression
	 */
	private function getGoogleLoginEmailSqlExpression()
	{
		if (!$this->googleLoginEmailExtrafieldColumnExists()) {
			return "''";
		}

		return "uef.".$this->db->sanitize(self::GOOGLE_LOGIN_EMAIL_EXTRAFIELD);
	}

	/**
	 * Return whether the user extrafields table contains the module Google login email column.
	 *
	 * @return bool
	 */
	private function googleLoginEmailExtrafieldColumnExists()
	{
		$fields = $this->db->DDLInfoTable(MAIN_DB_PREFIX.'user_extrafields');
		foreach ($fields as $field) {
			if (is_array($field) && isset($field[0]) && $field[0] === self::GOOGLE_LOGIN_EMAIL_EXTRAFIELD) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build the SQL condition used to identify a user with configured Google login.
	 *
	 * @param string $googlefieldsql SQL expression for module Google login emails
	 * @return string                SQL condition
	 */
	private function buildGoogleConfiguredSql($googlefieldsql)
	{
		return "TRIM(COALESCE(".$googlefieldsql.", '')) <> ''";
	}

	/**
	 * Build the SQL condition used when Google login satisfies TOTP policy.
	 *
	 * @param string $googlefieldsql SQL expression for module Google login emails
	 * @return string                SQL condition
	 */
	private function buildGooglePolicyConfiguredSql($googlefieldsql)
	{
		if (!$this->isGoogleTwoFactorAvailable()) {
			return "0 = 1";
		}

		return $this->buildGoogleConfiguredSql($googlefieldsql);
	}

	/**
	 * Check that a user id belongs to the current entity visibility.
	 *
	 * @param int $userid User id
	 * @return bool
	 */
	public function isUserVisibleInCurrentEntity($userid)
	{
		$sql = "SELECT u.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE u.rowid = ".((int) $userid);
		$sql .= " AND u.entity IN (".getEntity('user').")";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::isUserVisibleInCurrentEntity '.$this->error, LOG_ERR);
			return false;
		}

		$found = $this->db->num_rows($resql) > 0;
		$this->db->free($resql);

		return $found;
	}

	/**
	 * Move older entity-scoped module user parameters into the global scope.
	 *
	 * Existing global values are preserved. Older per-entity rows are left in
	 * place for rollback safety but no longer used by this module.
	 *
	 * @return int >0 if OK, <0 if KO
	 */
	public function migrateEntityScopedUserParamsToGlobal()
	{
		$params = $this->getModuleUserParamNames();
		if (!count($params)) {
			return 1;
		}

		$in = array();
		foreach ($params as $param) {
			$in[] = "'".$this->db->escape($param)."'";
		}

		$sql = "SELECT p.fk_user, p.param, p.value, p.entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."user_param as p";
		$sql .= " WHERE p.entity <> ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND p.param IN (".implode(',', $in).")";
		$sql .= " ORDER BY p.fk_user ASC, p.param ASC, p.entity ASC";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::migrateEntityScopedUserParamsToGlobal '.$this->error, LOG_ERR);
			return -1;
		}

		$rows = array();
		$seen = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$key = ((int) $obj->fk_user).'|'.((string) $obj->param);
			if (isset($seen[$key])) {
				continue;
			}
			$seen[$key] = true;
			$rows[] = array(
				'fk_user' => (int) $obj->fk_user,
				'param' => (string) $obj->param,
				'value' => (string) $obj->value,
			);
		}
		$this->db->free($resql);

		if (!count($rows)) {
			return 1;
		}

		$this->db->begin();
		foreach ($rows as $row) {
			$sql = "SELECT p.fk_user";
			$sql .= " FROM ".MAIN_DB_PREFIX."user_param as p";
			$sql .= " WHERE p.fk_user = ".((int) $row['fk_user']);
			$sql .= " AND p.entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
			$sql .= " AND p.param = '".$this->db->escape($row['param'])."'";

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				$this->db->rollback();
				dol_syslog('Krea2FactorAuthManager::migrateEntityScopedUserParamsToGlobal '.$this->error, LOG_ERR);
				return -1;
			}

			$exists = ($this->db->num_rows($resql) > 0);
			$this->db->free($resql);
			if ($exists) {
				continue;
			}

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_param(fk_user, entity, param, value)";
			$sql .= " VALUES (".((int) $row['fk_user']).", ".((int) self::GLOBAL_USER_PARAM_ENTITY).",";
			$sql .= " '".$this->db->escape($row['param'])."', '".$this->db->escape($row['value'])."')";

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				$this->db->rollback();
				dol_syslog('Krea2FactorAuthManager::migrateEntityScopedUserParamsToGlobal '.$this->error, LOG_ERR);
				return -1;
			}
		}

		$this->db->commit();
		return 1;
	}

	/**
	 * Move older entity-scoped module constants into the global scope.
	 *
	 * Existing global constants are preserved. The first active entity-specific
	 * value found by entity id is used when no global value exists yet.
	 *
	 * @param array<string,string> $settings Constant name => type
	 * @return int                          >0 if OK, <0 if KO
	 */
	public function migrateEntityScopedConfigToGlobal(array $settings)
	{
		if (!count($settings)) {
			return 1;
		}

		$names = array();
		foreach (array_keys($settings) as $name) {
			$names[] = "'".$this->db->escape($name)."'";
		}

		$sql = "SELECT DISTINCT entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."const";
		$sql .= " WHERE ".$this->db->decrypt('name')." IN (".implode(',', $names).")";
		$sql .= " AND entity <> ".((int) self::GLOBAL_CONFIG_ENTITY);
		$sql .= " ORDER BY entity ASC";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::migrateEntityScopedConfigToGlobal '.$this->error, LOG_ERR);
			return -1;
		}

		$entities = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$entities[] = (int) $obj->entity;
		}
		$this->db->free($resql);

		foreach ($settings as $name => $type) {
			if (dolibarr_get_const($this->db, $name, self::GLOBAL_CONFIG_ENTITY) !== '') {
				continue;
			}

			foreach ($entities as $entity) {
				$value = dolibarr_get_const($this->db, $name, $entity);
				if ($value === '') {
					continue;
				}

				if (dolibarr_set_const($this->db, $name, $value, $type, 0, '', self::GLOBAL_CONFIG_ENTITY) <= 0) {
					$this->error = $this->db->lasterror();
					$this->errors[] = $this->error;
					dol_syslog('Krea2FactorAuthManager::migrateEntityScopedConfigToGlobal '.$this->error, LOG_ERR);
					return -1;
				}
				break;
			}
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
		$sql .= " WHERE ".$this->db->decrypt('name')." IN (".implode(',', $names).")";
		$sql .= " AND entity <> ".((int) self::GLOBAL_CONFIG_ENTITY);
		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::migrateEntityScopedConfigToGlobal '.$this->error, LOG_ERR);
			return -1;
		}

		return 1;
	}

	/**
	 * Return module global configuration constant types.
	 *
	 * @return array<string,string>
	 */
	public function getModuleGlobalConfigTypes()
	{
		return array(
			'KREA2FACTORAUTH_ISSUER' => 'chaine',
			'KREA2FACTORAUTH_PRIMARY_AUTH_MODES' => 'chaine',
			'KREA2FACTORAUTH_CODE_WINDOW' => 'chaine',
			'KREA2FACTORAUTH_ENFORCE_NON_WEB' => 'yesno',
			'KREA2FACTORAUTH_ENROLLMENT_PROMPT_MODE' => 'chaine',
			'KREA2FACTORAUTH_SHOW_OPTIONAL_MODAL' => 'yesno',
			'KREA2FACTORAUTH_ALLOW_SELF_RESET' => 'yesno',
			self::ACTIVATION_SCOPE_CONST => 'chaine',
			self::LOGIN_BACKGROUND_COLOR_CONST => 'chaine',
			self::LOGIN_BACKGROUND_COLORS_CONST => 'chaine',
			self::GOOGLE_LOGIN_ENABLED_CONST => 'yesno',
			self::GOOGLE_LOGIN_ALLOWED_DOMAINS_CONST => 'chaine',
			self::GOOGLE_LOGIN_USE_EXTRAFIELD_EMAIL_CONST => 'yesno',
			self::PASSWORD_EXPIRATION_MAX_DAYS_CONST => 'chaine',
			self::PASSWORD_HISTORY_LIMIT_CONST => 'chaine',
		);
	}

	/**
	 * Delete legacy all-entity login logo constants.
	 *
	 * Login logo selection is entity-specific. Keeping these constants in entity
	 * 0 would allow a global logo to leak into entities without an explicit logo.
	 *
	 * @return int >0 if OK, <0 if KO
	 */
	public function deleteGlobalLoginLogoConstants()
	{
		$names = array(
			"'".$this->db->escape(self::LOGIN_LOGO_CONST)."'",
			"'".$this->db->escape(self::LEGACY_LOGIN_COMPANY_ICON_CONST)."'",
		);

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
		$sql .= " WHERE ".$this->db->decrypt('name')." IN (".implode(',', $names).")";
		$sql .= " AND entity = ".((int) self::GLOBAL_CONFIG_ENTITY);

		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::deleteGlobalLoginLogoConstants '.$this->error, LOG_ERR);
			return -1;
		}

		return 1;
	}

	/**
	 * Fetch active entity activation status for this module.
	 *
	 * @return array<int,array{rowid:int,label:string,enabled:bool,global:bool}>
	 */
	public function fetchModuleActivationByEntity()
	{
		$globalenabled = $this->isGlobalActivationScope();
		$entities = array();

		$sql = "SELECT e.rowid, e.label";
		$sql .= " FROM ".MAIN_DB_PREFIX."entity as e";
		$sql .= " WHERE e.active = 1";
		$sql .= " ORDER BY e.rowid ASC";

		$resql = $this->db->query($sql);
		if (!$resql) {
			return $entities;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$entityid = (int) $obj->rowid;
			$entities[] = array(
				'rowid' => $entityid,
				'label' => (string) $obj->label,
				'enabled' => $globalenabled || dolibarr_get_const($this->db, 'MAIN_MODULE_KREA2FACTORAUTH', $entityid) !== '',
				'global' => $globalenabled,
			);
		}
		$this->db->free($resql);

		return $entities;
	}

	/**
	 * Fetch module activation as an entity-id keyed map for login rendering.
	 *
	 * @return array<int,bool>
	 */
	public function fetchModuleActivationMapByEntity()
	{
		$map = array();
		foreach ($this->fetchModuleActivationByEntity() as $entity) {
			$map[(int) $entity['rowid']] = !empty($entity['enabled']);
		}

		return $map;
	}

	/**
	 * Return whether this module is enabled in at least one active entity.
	 *
	 * @return bool
	 */
	public function isModuleActiveInAnyEntity()
	{
		foreach ($this->fetchModuleActivationByEntity() as $entity) {
			if (!empty($entity['enabled'])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return whether this module is active for a selected login entity.
	 *
	 * The login provider itself may be globally bootstrapped so Dolibarr can
	 * load it from every entity. Enforcement remains scoped to the selected
	 * login entity unless the module is globally enabled.
	 *
	 * @param int|string $entity Entity selected on the login page
	 * @return bool
	 */
	public function isModuleActiveForEntity($entity)
	{
		if ($this->isGlobalActivationScope()) {
			return true;
		}

		return dolibarr_get_const($this->db, 'MAIN_MODULE_KREA2FACTORAUTH', $this->normalizeEntity($entity)) !== '';
	}

	/**
	 * Ensure Dolibarr can load this login provider from every entity.
	 *
	 * @return int >0 if OK, <0 if KO
	 */
	public function syncGlobalLoginBootstrap()
	{
		if (!$this->isModuleActiveInAnyEntity()) {
			return $this->deleteLoginBootstrapConstantsForEntity(self::GLOBAL_CONFIG_ENTITY);
		}

		if (dolibarr_get_const($this->db, self::ACTIVATION_SCOPE_CONST, self::GLOBAL_CONFIG_ENTITY) === '') {
			if (dolibarr_set_const($this->db, self::ACTIVATION_SCOPE_CONST, 'independent', 'chaine', 0, '', self::GLOBAL_CONFIG_ENTITY) <= 0) {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				return -1;
			}
		}

		return $this->writeLoginBootstrapConstantsForEntity(self::GLOBAL_CONFIG_ENTITY);
	}

	/**
	 * Set Dolibarr module activation scope for this module.
	 *
	 * @param string $scope global|independent
	 * @return int          >0 if OK, <0 if KO
	 */
	public function setModuleActivationScope($scope)
	{
		global $conf;

		$scope = ($scope === 'global') ? 'global' : 'independent';
		$targetentity = ($scope === 'global') ? 0 : (int) $conf->entity;

		$this->db->begin();

		if ($scope === 'independent') {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
			$sql .= " WHERE ".$this->db->decrypt('name')." LIKE 'MAIN_MODULE_KREA2FACTORAUTH%'";
			$sql .= " AND entity = 0";
			if (!$this->db->query($sql)) {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				$this->db->rollback();
				return -1;
			}
			if ($this->deleteModuleMenuForEntity(0) < 0) {
				$this->db->rollback();
				return -1;
			}
		}

		if ($this->writeActivationConstantsForEntity($targetentity) < 0) {
			$this->db->rollback();
			return -1;
		}

		if (dolibarr_set_const($this->db, self::ACTIVATION_SCOPE_CONST, $scope, 'chaine', 0, '', self::GLOBAL_CONFIG_ENTITY) <= 0) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			$this->db->rollback();
			return -1;
		}

		if ($this->copyModuleMenuToEntity($targetentity) < 0) {
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		return $this->syncGlobalLoginBootstrap();
	}

	/**
	 * Enable or disable this module for a single entity while activation scope is independent.
	 *
	 * @param int|string $entity  Entity id
	 * @param bool       $enabled True to activate the module in the entity
	 * @return int                >0 if OK, <0 if KO
	 */
	public function setModuleActivationForEntity($entity, $enabled)
	{
		$entity = $this->normalizeEntity($entity);
		if ($this->isGlobalActivationScope()) {
			$this->error = 'Per-entity activation cannot be changed while module activation is global.';
			$this->errors[] = $this->error;
			return -1;
		}

		$this->db->begin();

		if (dolibarr_set_const($this->db, self::ACTIVATION_SCOPE_CONST, 'independent', 'chaine', 0, '', self::GLOBAL_CONFIG_ENTITY) <= 0) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			$this->db->rollback();
			return -1;
		}

		if ($enabled) {
			if ($this->writeActivationConstantsForEntity($entity) < 0 || $this->copyModuleMenuToEntity($entity) < 0) {
				$this->db->rollback();
				return -1;
			}
		} else {
			if ($this->deleteModuleActivationConstantsForEntity($entity) < 0 || $this->deleteModuleMenuForEntity($entity) < 0) {
				$this->db->rollback();
				return -1;
			}
		}

		$this->db->commit();
		return $this->syncGlobalLoginBootstrap();
	}

	/**
	 * Normalize TOTP code input.
	 *
	 * @param string $code Raw code
	 * @return string
	 */
	public function normalizeCode($code)
	{
		return preg_replace('/[^0-9]/', '', (string) $code);
	}

	/**
	 * Normalize an entity value for module lookups.
	 *
	 * @param int|string $entity Entity value from Dolibarr login flow
	 * @return int               Normalized entity id
	 */
	private function normalizeEntity($entity)
	{
		$entity = (int) $entity;
		return $entity > 0 ? $entity : 1;
	}

	/**
	 * Return encrypted secret value.
	 *
	 * @param int        $userid User id
	 * @param int|string $entity Deprecated entity parameter
	 * @return string
	 */
	private function getEncryptedSecret($userid, $entity = 0)
	{
		return $this->getUserParamValue($userid, self::PARAM_SECRET, $entity);
	}

	/**
	 * Return a user parameter value scoped to entity visibility.
	 *
	 * @param int        $userid User id
	 * @param string     $param  Parameter name
	 * @param int|string $entity Deprecated entity parameter
	 * @return string
	 */
	private function getUserParamValue($userid, $param, $entity = 0)
	{
		global $conf;

		$fallbackentity = (int) $entity;
		if ($fallbackentity <= 0) {
			$fallbackentity = !empty($conf->entity) ? (int) $conf->entity : 1;
		}

		$entities = array(self::GLOBAL_USER_PARAM_ENTITY);
		if ($fallbackentity !== self::GLOBAL_USER_PARAM_ENTITY) {
			$entities[] = $fallbackentity;
		}

		$sql = "SELECT p.value";
		$sql .= " FROM ".MAIN_DB_PREFIX."user_param as p";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = p.fk_user";
		$sql .= " WHERE p.fk_user = ".((int) $userid);
		$sql .= " AND p.entity IN (".implode(',', array_map('intval', $entities)).")";
		$sql .= " AND p.param = '".$this->db->escape($param)."'";
		$sql .= " AND u.entity IN (".getEntity('user').")";
		$sql .= " ORDER BY p.entity ASC";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog('Krea2FactorAuthManager::getUserParamValue '.$this->error, LOG_ERR);
			return '';
		}

		$value = '';
		$obj = $this->db->fetch_object($resql);
		if ($obj) {
			$value = (string) $obj->value;
		}
		$this->db->free($resql);

		return $value;
	}

	/**
	 * Write module user parameters to global entity scope.
	 *
	 * @param User                                                 $targetuser        Target user
	 * @param array<string,string|array{value:string,forcevalue:int}> $params         Parameters
	 * @param bool                                                 $invalidatesessions True to invalidate existing sessions for the target user
	 * @return int                                                                     >0 if OK, <0 if KO
	 */
	private function setGlobalUserParams(User $targetuser, array $params, $invalidatesessions = false)
	{
		$result = $this->setGlobalUserParamValues((int) $targetuser->id, $params, $invalidatesessions);
		if ($result < 0) {
			return -1;
		}

		foreach ($params as $key => $value) {
			$forcevalue = 0;
			if (is_array($value)) {
				$forcevalue = (!empty($value['forcevalue']) ? 1 : 0);
				$value = $value['value'];
			}

			if ($forcevalue == 1 || $value) {
				$targetuser->conf->$key = $value;
			} else {
				unset($targetuser->conf->$key);
			}
		}

		return 1;
	}

	/**
	 * Write module user parameter values to global entity scope.
	 *
	 * @param int                                                   $userid            Target user id
	 * @param array<string,string|array{value:string,forcevalue:int}> $params          Parameters
	 * @param bool                                                  $invalidatesessions True to invalidate existing sessions for the target user
	 * @return int                                                                      >0 if OK, <0 if KO
	 */
	private function setGlobalUserParamValues($userid, array $params, $invalidatesessions = false)
	{
		if (!count($params)) {
			return -1;
		}
		$userid = (int) $userid;
		if ($userid <= 0) {
			return -1;
		}

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_param";
		$sql .= " WHERE fk_user = ".$userid;
		$sql .= " AND entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND param IN (";
		$i = 0;
		foreach ($params as $key => $value) {
			if ($i > 0) {
				$sql .= ',';
			}
			$sql .= "'".$this->db->escape($key)."'";
			$i++;
		}
		$sql .= ")";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			$this->db->rollback();
			return -1;
		}

		foreach ($params as $key => $value) {
			$forcevalue = 0;
			if (is_array($value)) {
				$forcevalue = (!empty($value['forcevalue']) ? 1 : 0);
				$value = $value['value'];
			}

			if ($forcevalue == 1 || $value) {
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_param(fk_user, entity, param, value)";
				$sql .= " VALUES (".$userid.", ".((int) self::GLOBAL_USER_PARAM_ENTITY).",";
				$sql .= " '".$this->db->escape((string) $key)."', '".$this->db->escape((string) $value)."')";

				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->error = $this->db->lasterror();
					$this->errors[] = $this->error;
					$this->db->rollback();
					return -1;
				}
			}
		}

		if ($invalidatesessions && $this->invalidateUserSessionsInOpenTransaction($userid) < 0) {
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		return 1;
	}

	/**
	 * Delete module user parameters inside an already-open transaction.
	 *
	 * @param int      $userid Target user id
	 * @param string[] $params Parameter names
	 * @return int             >0 if OK, <0 if KO
	 */
	private function deleteGlobalUserParamsInOpenTransaction($userid, array $params)
	{
		$userid = (int) $userid;
		if ($userid <= 0 || !count($params)) {
			return 1;
		}

		$paramnames = array();
		foreach ($params as $param) {
			$paramnames[] = "'".$this->db->escape($param)."'";
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_param";
		$sql .= " WHERE fk_user = ".$userid;
		$sql .= " AND entity = ".((int) self::GLOBAL_USER_PARAM_ENTITY);
		$sql .= " AND param IN (".implode(',', $paramnames).")";

		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}

		return 1;
	}

	/**
	 * Set Dolibarr's native session invalidation threshold inside an open transaction.
	 *
	 * @param int $userid Target user id
	 * @return int        >0 if OK, <0 if KO
	 */
	private function invalidateUserSessionsInOpenTransaction($userid)
	{
		$userid = (int) $userid;
		if ($userid <= 0) {
			return -1;
		}

		$invalidatebefore = dol_now() + 1;
		$sql = "UPDATE ".MAIN_DB_PREFIX."user";
		$sql .= " SET flagdelsessionsbefore = '".$this->db->idate($invalidatebefore, 'gmt')."'";
		$sql .= " WHERE rowid = ".$userid;

		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}

		dol_syslog('Krea2FactorAuthManager::invalidateUserSessionsInOpenTransaction invalidated active sessions for user id '.$userid, LOG_WARNING);
		return 1;
	}

	/**
	 * Format a TOTP time slice for stable lexical comparison in user_param.
	 *
	 * @param int $timeslice TOTP time slice
	 * @return string        Fixed-width decimal value
	 */
	private function formatTotpTimeslice($timeslice)
	{
		return sprintf('%012d', (int) $timeslice);
	}

	/**
	 * Return module user parameter names.
	 *
	 * @return string[]
	 */
	private function getModuleUserParamNames()
	{
		return array(
			self::PARAM_SECRET,
			self::PARAM_ENABLED_AT,
			self::PARAM_MANDATORY_REQUIRED,
			self::PARAM_MANDATORY_REQUIRED_AT,
			self::PARAM_MANDATORY_REQUIRED_BY,
			self::PARAM_MANDATORY_EXEMPT,
			self::PARAM_MANDATORY_EXEMPT_AT,
			self::PARAM_MANDATORY_EXEMPT_BY,
			self::PARAM_LOGIN_LOCKED,
			self::PARAM_LOGIN_LOCKED_AT,
			self::PARAM_LOGIN_LOCKED_BY,
			self::PARAM_POLICY_LISTED,
			self::PARAM_TOTP_FAILURE_COUNT,
			self::PARAM_TOTP_FAILURE_FIRST_AT,
			self::PARAM_TOTP_LOCKED_UNTIL,
			self::PARAM_TOTP_LAST_TIMESLICE,
			self::PARAM_PASSWORD_EXPIRATION_ENABLED,
			self::PARAM_PASSWORD_EXPIRATION_ENABLED_AT,
			self::PARAM_PASSWORD_EXPIRATION_ENABLED_BY,
		);
	}

	/**
	 * Read a module setting from global entity scope, falling back to loaded config.
	 *
	 * @param string $name    Constant name
	 * @param string $default Default value
	 * @return string
	 */
	private function getGlobalConstString($name, $default = '')
	{
		$value = dolibarr_get_const($this->db, $name, self::GLOBAL_CONFIG_ENTITY);
		if ($value !== '') {
			return (string) $value;
		}

		return getDolGlobalString($name, $default);
	}

	/**
	 * Read an integer module setting from global entity scope.
	 *
	 * @param string $name    Constant name
	 * @param int    $default Default value
	 * @return int
	 */
	private function getGlobalConstInt($name, $default = 0)
	{
		return (int) $this->getGlobalConstString($name, (string) $default);
	}

	/**
	 * Return whether module policy activation is configured for all entities.
	 *
	 * The global MAIN_MODULE_KREA2FACTORAUTH constant is also used as a
	 * runtime bootstrap so Dolibarr loads login hooks in inactive entities.
	 * This explicit scope flag is therefore the source of truth for real
	 * all-entity policy activation.
	 *
	 * @return bool
	 */
	private function isGlobalActivationScope()
	{
		return $this->getGlobalConstString(self::ACTIVATION_SCOPE_CONST, 'independent') === 'global';
	}

	/**
	 * Ensure older entity-scoped state is available in global scope.
	 *
	 * @return void
	 */
	public function ensureGlobalStorageMigrated()
	{
		static $checked = false;
		if ($checked) {
			return;
		}
		$checked = true;

		if (dolibarr_get_const($this->db, self::GLOBAL_MIGRATION_DONE, self::GLOBAL_CONFIG_ENTITY) === '1') {
			return;
		}

		$resultconfig = $this->migrateEntityScopedConfigToGlobal($this->getModuleGlobalConfigTypes());
		$resultusers = $this->migrateEntityScopedUserParamsToGlobal();
		if ($resultconfig > 0 && $resultusers > 0) {
			dolibarr_set_const($this->db, self::GLOBAL_MIGRATION_DONE, '1', 'yesno', 0, '', self::GLOBAL_CONFIG_ENTITY);
		}
	}

	/**
	 * Write module activation constants into an entity.
	 *
	 * @param int $entity Entity id, 0 for all entities
	 * @return int        >0 if OK, <0 if KO
	 */
	private function writeActivationConstantsForEntity($entity)
	{
		$constants = array_merge(array(
			'MAIN_MODULE_KREA2FACTORAUTH' => '1',
		), $this->getLoginBootstrapConstants());

		foreach ($constants as $name => $value) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
			$sql .= " WHERE ".$this->db->decrypt('name')." = '".$this->db->escape($name)."'";
			$sql .= " AND entity = ".((int) $entity);
			if (!$this->db->query($sql)) {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				return -1;
			}

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."const(name, type, value, note, visible, entity)";
			$sql .= " VALUES (";
			$sql .= $this->db->encrypt($name, 1);
			$sql .= ", 'chaine'";
			$sql .= ", ".$this->db->encrypt($value, 1);
			$sql .= ", null";
			$sql .= ", 0";
			$sql .= ", ".((int) $entity);
			$sql .= ")";

			if (!$this->db->query($sql)) {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				return -1;
			}
		}

		return 1;
	}

	/**
	 * Return constants required to load the login provider and login hooks.
	 *
	 * @return array<string,string>
	 */
	private function getLoginBootstrapConstants()
	{
		return array(
			'MAIN_MODULE_KREA2FACTORAUTH' => '1',
			'MAIN_MODULE_KREA2FACTORAUTH_LOGIN' => '1',
			'MAIN_MODULE_KREA2FACTORAUTH_TPL' => '1',
			'MAIN_MODULE_KREA2FACTORAUTH_TRIGGERS' => '1',
			'MAIN_MODULE_KREA2FACTORAUTH_HOOKS' => json_encode(array('mainloginpage', 'login', 'main', 'usercard')),
		);
	}

	/**
	 * Write only login bootstrap constants into an entity.
	 *
	 * @param int $entity Entity id, 0 for all entities
	 * @return int        >0 if OK, <0 if KO
	 */
	private function writeLoginBootstrapConstantsForEntity($entity)
	{
		foreach ($this->getLoginBootstrapConstants() as $name => $value) {
			if (dolibarr_set_const($this->db, $name, $value, 'chaine', 0, '', (int) $entity) <= 0) {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				return -1;
			}
		}

		return 1;
	}

	/**
	 * Delete only the login bootstrap constants for an entity.
	 *
	 * @param int $entity Entity id, 0 for all entities
	 * @return int        >0 if OK, <0 if KO
	 */
	private function deleteLoginBootstrapConstantsForEntity($entity)
	{
		$constnames = array();
		foreach (array_keys($this->getLoginBootstrapConstants()) as $name) {
			$constnames[] = "'".$this->db->escape($name)."'";
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
		$sql .= " WHERE ".$this->db->decrypt('name')." IN (".implode(',', $constnames).")";
		$sql .= " AND entity = ".((int) $entity);

		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}

		return 1;
	}

	/**
	 * Delete this module's activation constants for an entity.
	 *
	 * @param int $entity Entity id
	 * @return int        >0 if OK, <0 if KO
	 */
	private function deleteModuleActivationConstantsForEntity($entity)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
		$sql .= " WHERE ".$this->db->decrypt('name')." LIKE 'MAIN_MODULE_KREA2FACTORAUTH%'";
		$sql .= " AND entity = ".((int) $entity);

		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}

		return 1;
	}

	/**
	 * Delete this module's generated menu entry for an entity.
	 *
	 * @param int $entity Entity id
	 * @return int        >0 if OK, <0 if KO
	 */
	private function deleteModuleMenuForEntity($entity)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu";
		$sql .= " WHERE entity = ".((int) $entity);
		$sql .= " AND module = 'krea2factorauth'";
		$sql .= " AND leftmenu = 'krea2factorauth'";

		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}

		return 1;
	}

	/**
	 * Copy or create this module's Tools menu entry for an entity.
	 *
	 * @param int $entity Entity id, 0 for all entities
	 * @return int        >0 if OK, <0 if KO
	 */
	private function copyModuleMenuToEntity($entity)
	{
		global $conf;

		if ($this->deleteModuleMenuForEntity($entity) < 0) {
			return -1;
		}

		$sourceentity = (int) $conf->entity;
		$sql = "SELECT menu_handler, module, type, mainmenu, leftmenu, fk_menu, fk_mainmenu, fk_leftmenu, position, url, showtopmenuinframe, target, titre, prefix, langs, level, perms, enabled, usertype";
		$sql .= " FROM ".MAIN_DB_PREFIX."menu";
		$sql .= " WHERE module = 'krea2factorauth'";
		$sql .= " AND leftmenu = 'krea2factorauth'";
		$sql .= " AND entity IN (".$sourceentity.",0)";
		$sql .= " ORDER BY entity DESC";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}

		$obj = $this->db->fetch_object($resql);
		if ($obj) {
			$menu = array(
				'menu_handler' => (string) $obj->menu_handler,
				'module' => (string) $obj->module,
				'type' => (string) $obj->type,
				'mainmenu' => (string) $obj->mainmenu,
				'leftmenu' => (string) $obj->leftmenu,
				'fk_menu' => (int) $obj->fk_menu,
				'fk_mainmenu' => (string) $obj->fk_mainmenu,
				'fk_leftmenu' => (string) $obj->fk_leftmenu,
				'position' => (int) $obj->position,
				'url' => '/custom/krea2factorauth/user/2fa_setup.php?mainmenu=tools&leftmenu=krea2factorauth',
				'showtopmenuinframe' => (int) $obj->showtopmenuinframe,
				'target' => (string) $obj->target,
				'titre' => (string) $obj->titre,
				'prefix' => (string) $obj->prefix,
				'langs' => (string) $obj->langs,
				'level' => is_null($obj->level) ? 'null' : (string) ((int) $obj->level),
				'perms' => (string) $obj->perms,
				'enabled' => (string) $obj->enabled,
				'usertype' => (int) $obj->usertype,
			);
		} else {
			$menu = array(
				'menu_handler' => 'all',
				'module' => 'krea2factorauth',
				'type' => 'left',
				'mainmenu' => 'tools',
				'leftmenu' => 'krea2factorauth',
				'fk_menu' => -1,
				'fk_mainmenu' => 'tools',
				'fk_leftmenu' => '',
				'position' => 551,
				'url' => '/custom/krea2factorauth/user/2fa_setup.php?mainmenu=tools&leftmenu=krea2factorauth',
				'showtopmenuinframe' => 0,
				'target' => '',
				'titre' => 'ModuleKrea2FactorAuthName',
				'prefix' => '',
				'langs' => 'krea2factorauth@krea2factorauth',
				'level' => 'null',
				'perms' => '1',
				'enabled' => 'isModEnabled("krea2factorauth")',
				'usertype' => 0,
			);
		}
		$this->db->free($resql);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."menu(menu_handler, module, type, mainmenu, leftmenu, fk_menu, fk_mainmenu, fk_leftmenu, position, url, showtopmenuinframe, target, titre, prefix, langs, level, perms, enabled, usertype, entity)";
		$sql .= " VALUES (";
		$sql .= "'".$this->db->escape($menu['menu_handler'])."',";
		$sql .= "'".$this->db->escape($menu['module'])."',";
		$sql .= "'".$this->db->escape($menu['type'])."',";
		$sql .= "'".$this->db->escape($menu['mainmenu'])."',";
		$sql .= "'".$this->db->escape($menu['leftmenu'])."',";
		$sql .= ((int) $menu['fk_menu']).",";
		$sql .= "'".$this->db->escape($menu['fk_mainmenu'])."',";
		$sql .= "'".$this->db->escape($menu['fk_leftmenu'])."',";
		$sql .= ((int) $menu['position']).",";
		$sql .= "'".$this->db->escape($menu['url'])."',";
		$sql .= ((int) $menu['showtopmenuinframe']).",";
		$sql .= "'".$this->db->escape($menu['target'])."',";
		$sql .= "'".$this->db->escape($menu['titre'])."',";
		$sql .= "'".$this->db->escape($menu['prefix'])."',";
		$sql .= "'".$this->db->escape($menu['langs'])."',";
		$sql .= ($menu['level'] === 'null' ? 'null' : ((int) $menu['level'])).",";
		$sql .= "'".$this->db->escape($menu['perms'])."',";
		$sql .= "'".$this->db->escape($menu['enabled'])."',";
		$sql .= ((int) $menu['usertype']).",";
		$sql .= ((int) $entity);
		$sql .= ")";

		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}

		return 1;
	}

	/**
	 * Instantiate RobThree TOTP library with local SVG QR generation.
	 *
	 * @return RobThree\Auth\TwoFactorAuth
	 */
	private function getTwoFactorAuth()
	{
		$this->loadVendorAutoload();

		$qrcodeprovider = new RobThree\Auth\Providers\Qr\BaconQrCodeProvider(2, '#ffffff', '#000000', 'svg');

		return new RobThree\Auth\TwoFactorAuth($this->getIssuer(), 6, 30, 'sha1', $qrcodeprovider);
	}

	/**
	 * Load Composer autoloader.
	 *
	 * @return void
	 */
	private function loadVendorAutoload()
	{
		$autoload = dirname(__DIR__).'/vendor/autoload.php';
		if (!is_readable($autoload)) {
			throw new RuntimeException('Krea2FactorAuth vendor autoload file is missing. Run composer install in the module directory.');
		}

		require_once $autoload;
	}

	/**
	 * Suppress deprecation notices emitted by PHP 7.1-compatible vendor libraries on newer PHP runtimes.
	 *
	 * @return int Previous error reporting level
	 */
	private function suppressVendorDeprecatedErrors()
	{
		$olderrorreporting = error_reporting();
		error_reporting($olderrorreporting & ~E_DEPRECATED & ~E_USER_DEPRECATED);

		return $olderrorreporting;
	}
}
