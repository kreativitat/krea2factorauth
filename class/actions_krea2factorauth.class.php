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
 * \file    krea2factorauth/class/actions_krea2factorauth.class.php
 * \ingroup krea2factorauth
 * \brief   Hook actions for Krea2FactorAuth.
 */

dol_include_once('/krea2factorauth/lib/krea2factorauth.lib.php');
dol_include_once('/krea2factorauth/class/krea2factorauthmanager.class.php');

/**
 * Hook actions for Krea2FactorAuth.
 */
class ActionsKrea2FactorAuth
{
	/**
	 * @var string[]
	 */
	public $errors = array();

	/**
	 * @var array<string,mixed>
	 */
	public $results = array();

	/**
	 * @var string
	 */
	public $resprints = '';

	/**
	 * Build the current request URL without selected query parameters.
	 *
	 * @param string[] $excludedkeys Query keys to remove
	 * @return string                Relative URL
	 */
	private function getCurrentUrlWithout(array $excludedkeys)
	{
		$query = $_GET;
		foreach ($excludedkeys as $key) {
			unset($query[$key]);
		}

		$url = empty($_SERVER['PHP_SELF']) ? DOL_URL_ROOT . '/index.php' : $_SERVER['PHP_SELF'];
		if (!empty($query)) {
			$url .= '?' . http_build_query($query, '', '&');
		}

		return $url;
	}

	/**
	 * Add query parameters to a relative URL.
	 *
	 * @param string               $url    Relative URL
	 * @param array<string,string> $params Query parameters to add
	 * @return string                      Relative URL with query parameters
	 */
	private function addQueryParams($url, array $params)
	{
		if (empty($params)) {
			return $url;
		}

		return $url.(strpos($url, '?') === false ? '?' : '&').http_build_query($params, '', '&');
	}

	/**
	 * Return a menu URL normalized for path checks.
	 *
	 * @param array<string,mixed> $menuitem Menu entry
	 * @return string                      Decoded menu URL
	 */
	private function getMenuItemUrl(array $menuitem)
	{
		return html_entity_decode((string) (isset($menuitem['url']) ? $menuitem['url'] : ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}

	/**
	 * Return whether a menu entry targets the given path.
	 *
	 * @param array<string,mixed> $menuitem Menu entry
	 * @param string              $path     Path to match inside the URL
	 * @return bool                         True when the menu URL targets the path
	 */
	private function menuItemTargetsPath(array $menuitem, $path)
	{
		return strpos($this->getMenuItemUrl($menuitem), $path) !== false;
	}

	/**
	 * Build the managed-users left-menu entry.
	 *
	 * @return array<string,mixed> Menu entry
	 */
	private function buildManagedUsersMenuItem()
	{
		global $langs;

		$langs->load('krea2factorauth@krea2factorauth');

		return array(
			'url' => '/custom/krea2factorauth/admin/users.php?mainmenu=home&leftmenu=users',
			'titre' => $langs->trans('Krea2FactorAuthManagedUsers'),
			'level' => 2,
			'enabled' => 1,
			'target' => '',
			'mainmenu' => '',
			'leftmenu' => '',
			'position' => 0,
			'id' => '',
			'idsel' => '',
			'classname' => '',
			'prefix' => '',
		);
	}

	/**
	 * Insert the managed-users entry under the generated Home > Users and Groups > Users submenu.
	 *
	 * @param array<int,array<string,mixed>> $menuitems Current left-menu items
	 * @return array<int,array<string,mixed>>            Updated left-menu items
	 */
	private function insertManagedUsersMenuItemUnderUsers(array $menuitems)
	{
		$filteredmenuitems = array();
		foreach ($menuitems as $menuitem) {
			if (!$this->menuItemTargetsPath($menuitem, '/custom/krea2factorauth/admin/users.php')) {
				$filteredmenuitems[] = $menuitem;
			}
		}

		$usersparentindex = null;
		$userssectionindex = null;
		$insertindex = null;
		$count = count($filteredmenuitems);
		for ($i = 0; $i < $count; $i++) {
			$level = (int) (isset($filteredmenuitems[$i]['level']) ? $filteredmenuitems[$i]['level'] : 0);
			if ($usersparentindex === null) {
				$isusersparent = $level === 0
					&& $this->menuItemTargetsPath($filteredmenuitems[$i], '/user/home.php')
					&& (string) (isset($filteredmenuitems[$i]['leftmenu']) ? $filteredmenuitems[$i]['leftmenu'] : '') === 'users';
				if ($isusersparent) {
					$usersparentindex = $i;
				}
				continue;
			}

			if ($i > $usersparentindex && $level <= 0) {
				break;
			}

			if ($userssectionindex === null) {
				$isuserssectionheader = $level === 1
					&& (string) (isset($filteredmenuitems[$i]['url']) ? $filteredmenuitems[$i]['url'] : '') === '';
				if ($isuserssectionheader) {
					$userssectionindex = $i;
					$insertindex = $i + 1;
				}
				continue;
			}

			if ($level === 1) {
				break;
			}

			if ($level >= 2) {
				$insertindex = $i + 1;
			}
		}

		if ($insertindex !== null) {
			array_splice($filteredmenuitems, $insertindex, 0, array($this->buildManagedUsersMenuItem()));
		}

		return $filteredmenuitems;
	}

	/**
	 * Return query parameters that must not survive post-login modal actions.
	 *
	 * @return string[] Query parameter names
	 */
	private function getPostLoginTransientQueryKeys()
	{
		return array('action', 'token', 'actionlogin', 'beforeoauthloginredirect', 'afteroauthloginreturn', 'loginfunction', 'username', 'password');
	}

	/**
	 * Return whether the current request is the implicit current-user card.
	 *
	 * Dolibarr resolves /user/card.php without id to the active user after
	 * main.inc.php, but keeping the id implicit makes post-login 2FA routing
	 * ambiguous. The module redirects it to the explicit user-card panel URL.
	 *
	 * @param string $current         Current PHP_SELF path
	 * @param int    $requestedid     Requested user id
	 * @param string $requestedaction Requested action
	 * @return bool
	 */
	private function isImplicitCurrentUserCard($current, $requestedid, $requestedaction)
	{
		return strpos($current, '/user/card.php') !== false && $requestedid <= 0 && $requestedaction === '';
	}

	/**
	 * Return whether the current request is the dedicated 2FA enrollment page.
	 *
	 * @param string $current Current PHP_SELF path
	 * @return bool
	 */
	private function isEnrollmentPageRequest($current)
	{
		return strpos($current, '/krea2factorauth/user/enrollment.php') !== false;
	}

	/**
	 * Return whether the current request is the dedicated password-change page.
	 *
	 * @param string $current Current PHP_SELF path
	 * @return bool
	 */
	private function isPasswordChangePageRequest($current)
	{
		return strpos($current, '/krea2factorauth/user/password_change.php') !== false;
	}

	/**
	 * Return whether the current Dolibarr user-card action changes passwords through core.
	 *
	 * @param string $requestedaction Requested action
	 * @return bool
	 */
	private function isNativeUserPasswordAction($requestedaction)
	{
		return in_array($requestedaction, array('password', 'passwordsend', 'confirm_password', 'confirm_passwordsend'), true);
	}

	/**
	 * Return whether the current request submits a manual password on the user card.
	 *
	 * @param string $requestedaction Requested action
	 * @return bool
	 */
	private function hasManualUserPasswordSubmission($requestedaction)
	{
		return $requestedaction === 'update' && GETPOST('password', 'password') !== '';
	}

	/**
	 * Stop native password edits for 2FA-exempt users.
	 *
	 * @param User                 $targetuser Target user card object
	 * @param string               $action     Current action, changed back to a display action when blocked
	 * @param Krea2FactorAuthManager $manager  Module manager
	 * @return int                             1 when blocked, 0 otherwise
	 */
	private function blockNativePasswordChangeForExemptUser(User $targetuser, &$action, Krea2FactorAuthManager $manager)
	{
		global $langs;

		if (!$manager->isMandatoryExemptForUser((int) $targetuser->id)) {
			return 0;
		}
		if ($manager->isPasswordExpirationEnabledForUser((int) $targetuser->id)) {
			return 0;
		}
		if (!$this->isNativeUserPasswordAction($action) && !$this->hasManualUserPasswordSubmission($action)) {
			return 0;
		}

		$langs->loadLangs(array("users", "krea2factorauth@krea2factorauth"));
		setEventMessages($langs->trans("Krea2FactorAuthExemptManualPasswordBlocked"), null, 'errors');
		dol_syslog('ActionsKrea2FactorAuth::blockNativePasswordChangeForExemptUser blocked native password change for exempt user id ' . ((int) $targetuser->id), LOG_WARNING);

		$action = $this->hasManualUserPasswordSubmission($action) ? 'edit' : '';
		return 1;
	}

	/**
	 * Stop manual password reuse for users governed by password expiration.
	 *
	 * @param User                   $targetuser Target user card object
	 * @param string                 $action     Current action, changed back to edit when blocked
	 * @param Krea2FactorAuthManager $manager    Module manager
	 * @return int                              1 when blocked, 0 otherwise
	 */
	private function blockPasswordReuseForManagedUser(User $targetuser, &$action, Krea2FactorAuthManager $manager)
	{
		global $langs;

		if (!$manager->isPasswordExpirationEnabledForUser((int) $targetuser->id)) {
			return 0;
		}
		if (!$this->hasManualUserPasswordSubmission($action)) {
			return 0;
		}

		$newpassword = GETPOST('password', 'password');
		if (!$manager->isPasswordReusedForUser((int) $targetuser->id, $newpassword)) {
			return 0;
		}

		$langs->loadLangs(array("users", "krea2factorauth@krea2factorauth"));
		setEventMessages($langs->trans("Krea2FactorAuthPasswordReuseDenied"), null, 'errors');
		dol_syslog('ActionsKrea2FactorAuth::blockPasswordReuseForManagedUser blocked reused password for user id ' . ((int) $targetuser->id), LOG_WARNING);

		$action = 'edit';
		return 1;
	}

	/**
	 * Return whether Krea2FactorAuth is active in the current Dolibarr entity.
	 *
	 * @param Krea2FactorAuthManager $manager Module manager
	 * @return bool
	 */
	private function isCurrentEntityActive(Krea2FactorAuthManager $manager)
	{
		global $conf;

		$entity = !empty($conf->entity) ? (int) $conf->entity : GETPOSTINT('entity');
		return $manager->isModuleActiveForEntity($entity > 0 ? $entity : 1);
	}

	/**
	 * Return whether an implicit current-user card must land on the 2FA panel.
	 *
	 * @param string                    $current         Current PHP_SELF path
	 * @param int                       $requestedid     Requested user id
	 * @param string                    $requestedaction Requested action
	 * @param Krea2FactorAuthManager    $manager         Module manager
	 * @param int                       $userid          Current user id
	 * @return bool
	 */
	private function shouldRedirectImplicitCurrentUserCard($current, $requestedid, $requestedaction, Krea2FactorAuthManager $manager, $userid)
	{
		if (!$this->isImplicitCurrentUserCard($current, $requestedid, $requestedaction)) {
			return false;
		}

		$requestedleftmenu = GETPOST('leftmenu', 'aZ09');
		return $requestedleftmenu === 'krea2factorauth';
	}

	/**
	 * Return whether the current user must complete enrollment before continuing.
	 *
	 * @param Krea2FactorAuthManager $manager Module manager
	 * @param int                    $userid  Current user id
	 * @return bool
	 */
	private function hasPendingMandatoryEnrollment(Krea2FactorAuthManager $manager, $userid)
	{
		if ($manager->isEnabledForUser((int) $userid)) {
			return false;
		}
		if ($manager->isMandatoryExemptForUser((int) $userid)) {
			return false;
		}
		if ($manager->isGoogleTwoFactorConfiguredForUser((int) $userid)) {
			return false;
		}

		return $manager->isMandatoryForUser((int) $userid)
			|| $manager->isMandatoryEnrollmentPromptEnabled();
	}

	/**
	 * Return whether the current request is allowed while mandatory enrollment is pending.
	 *
	 * @param string $current         Current PHP_SELF path
	 * @param string $requestedaction Requested action
	 * @return bool
	 */
	private function isAllowedMandatorySetupRequest($current, $requestedaction)
	{
		$isallowedenrollmentaction = in_array($requestedaction, array('', 'krea2factorauth_regenerate_secret', 'krea2factorauth_enable_2fa'));

		return $this->isEnrollmentPageRequest($current) && $isallowedenrollmentaction;
	}

	/**
	 * Return whether the current request is allowed while a password is expired.
	 *
	 * @param string $current         Current PHP_SELF path
	 * @param string $requestedaction Requested action
	 * @return bool
	 */
	private function isAllowedExpiredPasswordRequest($current, $requestedaction)
	{
		return $this->isPasswordChangePageRequest($current) && in_array($requestedaction, array('', 'change_expired_password'), true);
	}

	/**
	 * Redirect to the current user's dedicated 2FA enrollment page.
	 *
	 * @param int $userid Current user id
	 * @return void
	 */
	private function redirectToCurrentUserPanel($userid)
	{
		header('Location: ' . krea2factorauthGetEnrollmentPageUrl());
		exit;
	}

	/**
	 * Redirect to the dedicated enrollment page.
	 *
	 * @return void
	 */
	private function redirectToEnrollmentPage()
	{
		header('Location: ' . krea2factorauthGetEnrollmentPageUrl());
		exit;
	}

	/**
	 * Redirect to the mandatory password-change page.
	 *
	 * @return void
	 */
	private function redirectToPasswordChangePage()
	{
		header('Location: ' . krea2factorauthGetPasswordChangePageUrl());
		exit;
	}

	/**
	 * Redirect to the page that was interrupted by authentication.
	 *
	 * @return void
	 */
	private function redirectToPostLoginReturnUrl()
	{
		if (empty($_SESSION[Krea2FactorAuthManager::LOGIN_RETURN_SESSION])) {
			return;
		}

		$returnurl = krea2factorauthNormalizePostLoginReturnUrl((string) $_SESSION[Krea2FactorAuthManager::LOGIN_RETURN_SESSION]);
		if ($returnurl === '') {
			unset($_SESSION[Krea2FactorAuthManager::LOGIN_RETURN_SESSION]);
			return;
		}

		$currenturl = krea2factorauthNormalizePostLoginReturnUrl(empty($_SERVER['REQUEST_URI']) ? '' : (string) $_SERVER['REQUEST_URI']);
		unset($_SESSION[Krea2FactorAuthManager::LOGIN_RETURN_SESSION]);
		if ($currenturl === $returnurl) {
			return;
		}

		dol_syslog('ActionsKrea2FactorAuth::redirectToPostLoginReturnUrl restoring interrupted page '.$returnurl, LOG_INFO);
		header('Location: '.$returnurl);
		exit;
	}

	/**
	 * Handle embedded 2FA actions on Dolibarr's user card.
	 *
	 * @param array<string,mixed> $parameters  Hook parameters
	 * @param User                $object      Current user card object
	 * @param string              $action      Current action
	 * @param HookManager         $hookmanager Hook manager
	 * @return int                             0 on success
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $db;

		if (!is_object($object) || empty($object->id) || !in_array('usercard', explode(':', empty($parameters['context']) ? '' : $parameters['context']))) {
			return 0;
		}

		$manager = new Krea2FactorAuthManager($db);
		if (!$this->isCurrentEntityActive($manager)) {
			return 0;
		}

		$result = krea2factorauthHandleUserCardAction($object, $action);
		if ($result !== 0) {
			return $result;
		}

		$result = $this->blockNativePasswordChangeForExemptUser($object, $action, $manager);
		if ($result !== 0) {
			return $result;
		}

		return $this->blockPasswordReuseForManagedUser($object, $action, $manager);
	}

	/**
	 * Move the managed-users left-menu entry under Home > Users and Groups > Users.
	 *
	 * Dolibarr's generated "Users" submenu header is not a database menu row, so
	 * descriptor-based fk_leftmenu targeting can only attach to "Users and Groups".
	 *
	 * @param array<string,mixed>            $parameters  Hook parameters
	 * @param array<int,array<string,mixed>> $object      Current left-menu items
	 * @param string                         $action      Current action
	 * @param HookManager                    $hookmanager Hook manager
	 * @return int                                        1 to replace the left-menu item array
	 */
	public function menuLeftMenuItems($parameters, &$object, &$action, $hookmanager)
	{
		global $user;

		$this->results = array();

		$mainmenu = (string) (isset($parameters['mainmenu']) ? $parameters['mainmenu'] : '');
		if ($mainmenu !== 'home' || empty($user->admin) || !isModEnabled('krea2factorauth') || !is_array($object)) {
			return 0;
		}

		$this->results = $this->insertManagedUsersMenuItemUnderUsers($object);

		return 1;
	}

	/**
	 * Replace the native user-card password field for 2FA-exempt users.
	 *
	 * @param array<string,mixed> $parameters  Hook parameters
	 * @param User                $object      Current user card object
	 * @param string              $action      Current action
	 * @param HookManager         $hookmanager Hook manager
	 * @return int                             1 to replace the native field, 0 to keep it
	 */
	public function printUserPasswordField($parameters, &$object, &$action, $hookmanager)
	{
		global $db, $langs;

		$this->resprints = '';

		if (!is_object($object) || empty($object->id) || !in_array('usercard', explode(':', empty($parameters['context']) ? '' : $parameters['context']))) {
			return 0;
		}

		$manager = new Krea2FactorAuthManager($db);
		if (!$this->isCurrentEntityActive($manager) || !$manager->isUserVisibleInCurrentEntity((int) $object->id) || !$manager->isMandatoryExemptForUser((int) $object->id) || $manager->isPasswordExpirationEnabledForUser((int) $object->id)) {
			return 0;
		}

		$langs->loadLangs(array("users", "krea2factorauth@krea2factorauth"));

		$actionurl = DOL_URL_ROOT.'/user/card.php?id='.((int) $object->id);
		$insideparentform = ($action === 'edit');
		$this->resprints = krea2factorauthRenderExceptionPasswordResetControl($object, $actionurl, $insideparentform);

		return 1;
	}

	/**
	 * Add Access Management summary rows to Dolibarr's native user credentials table.
	 *
	 * Dolibarr prints the hook immediately after the credentials table, so the
	 * module appends rows to the already-rendered table without patching core.
	 *
	 * @param array<string,mixed> $parameters  Hook parameters
	 * @param User                $object      Current user card object
	 * @param string              $action      Current action
	 * @param HookManager         $hookmanager Hook manager
	 * @return int                             1 when rows are emitted, 0 otherwise
	 */
	public function addMoreObjectBlock($parameters, &$object, &$action, $hookmanager)
	{
		global $db;

		$this->resprints = '';

		if (!is_object($object) || empty($object->id) || !in_array('usercard', explode(':', empty($parameters['context']) ? '' : $parameters['context']))) {
			return 0;
		}
		if (in_array($action, array('add', 'create', 'edit', 'presend'), true)) {
			return 0;
		}

		$manager = new Krea2FactorAuthManager($db);
		if (!$this->isCurrentEntityActive($manager) || !$manager->isUserVisibleInCurrentEntity((int) $object->id)) {
			return 0;
		}

		$rows = krea2factorauthRenderUserCardCredentialRows($object);
		if ($rows === '') {
			return 0;
		}

		$encodedrows = json_encode($rows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
		if ($encodedrows === false) {
			return 0;
		}

		$this->resprints = '<script>';
		$this->resprints .= '(function(){';
		$this->resprints .= 'var script=document.currentScript;';
		$this->resprints .= 'var container=script?script.previousElementSibling:null;';
		$this->resprints .= 'while(container&&!(container.classList&&container.classList.contains("div-table-responsive-no-min"))){container=container.previousElementSibling;}';
		$this->resprints .= 'var table=container?container.querySelector("table.tableforfield"):null;';
		$this->resprints .= 'if(table&&table.tBodies&&table.tBodies[0]){table.tBodies[0].insertAdjacentHTML("beforeend", '.$encodedrows.');}';
		$this->resprints .= '})();';
		$this->resprints .= '</script>';

		return 1;
	}

	/**
	 * Mark unenrolled users for a post-login warning.
	 *
	 * @param array<string,mixed> $parameters  Hook parameters
	 * @param User                $object      Logged-in user
	 * @param string              $action      Current action
	 * @param HookManager         $hookmanager Hook manager
	 * @return int                             0 on success
	 */
	public function afterLogin($parameters, &$object, &$action, $hookmanager)
	{
		global $db;

		if (!is_object($object) || empty($object->id)) {
			return 0;
		}

		$manager = new Krea2FactorAuthManager($db);
		if (!$this->isCurrentEntityActive($manager)) {
			unset($_SESSION['KREA2FACTORAUTH_SHOW_OPTIONAL_WARNING']);
			unset($_SESSION['KREA2FACTORAUTH_OPTIONAL_ENROLLMENT_SKIPPED']);
			unset($_SESSION[Krea2FactorAuthManager::LOGIN_RETURN_SESSION]);
			return 0;
		}

		$submittedreturnurl = GETPOST(Krea2FactorAuthManager::LOGIN_RETURN_FORM_FIELD, 'restricthtml');
		if ($submittedreturnurl === '' && $manager->isGoogleLoginReturnRequest() && !empty($_SESSION['datafromloginform'][Krea2FactorAuthManager::LOGIN_RETURN_FORM_FIELD])) {
			$submittedreturnurl = (string) $_SESSION['datafromloginform'][Krea2FactorAuthManager::LOGIN_RETURN_FORM_FIELD];
		}
		$returnurl = krea2factorauthGetPostLoginReturnUrl($submittedreturnurl);
		if ($returnurl !== '') {
			$_SESSION[Krea2FactorAuthManager::LOGIN_RETURN_SESSION] = $returnurl;
		} else {
			unset($_SESSION[Krea2FactorAuthManager::LOGIN_RETURN_SESSION]);
		}
		if (isset($_SESSION['datafromloginform'][Krea2FactorAuthManager::LOGIN_RETURN_FORM_FIELD])) {
			unset($_SESSION['datafromloginform'][Krea2FactorAuthManager::LOGIN_RETURN_FORM_FIELD]);
		}

		if (!$manager->isOptionalEnrollmentModalEnabled()) {
			unset($_SESSION['KREA2FACTORAUTH_SHOW_OPTIONAL_WARNING']);
			unset($_SESSION['KREA2FACTORAUTH_OPTIONAL_ENROLLMENT_SKIPPED']);
			return 0;
		}

		if (!$manager->isEnabledForUser((int) $object->id) && !$manager->isMandatoryForUser((int) $object->id) && !$manager->isMandatoryExemptForUser((int) $object->id) && !$manager->isGoogleTwoFactorConfiguredForUser((int) $object->id)) {
			$_SESSION['KREA2FACTORAUTH_SHOW_OPTIONAL_WARNING'] = '1';
		} else {
			unset($_SESSION['KREA2FACTORAUTH_SHOW_OPTIONAL_WARNING']);
		}

		return 0;
	}

	/**
	 * Enforce first-page redirects before Dolibarr prints page output.
	 *
	 * updateSession is not executed on the request that creates a new login
	 * session. This hook closes that first-login gap without interrupting
	 * Dolibarr's login transaction.
	 *
	 * @param array<string,mixed> $parameters  Hook parameters
	 * @param object              $object      Hook object
	 * @param string              $action      Current action
	 * @param HookManager         $hookmanager Hook manager
	 * @return int                             0 on success
	 */
	public function llxHeader($parameters, &$object, &$action, $hookmanager)
	{
		global $db, $langs, $user;

		if (empty($user->id)) {
			return 0;
		}

		$current = empty($_SERVER['PHP_SELF']) ? '' : $_SERVER['PHP_SELF'];
		if (strpos($current, '/user/logout.php') !== false) {
			return 0;
		}

		$manager = new Krea2FactorAuthManager($db);
		if (!$this->isCurrentEntityActive($manager)) {
			return 0;
		}

		$requestedaction = GETPOST('action', 'aZ09');
		$requestedid = GETPOSTINT('id');
		if ($this->shouldRedirectImplicitCurrentUserCard($current, $requestedid, $requestedaction, $manager, (int) $user->id)) {
			$this->redirectToCurrentUserPanel((int) $user->id);
		}

		if ($manager->isPasswordExpiredForUser((int) $user->id)) {
			if ($this->isAllowedExpiredPasswordRequest($current, $requestedaction)) {
				return 0;
			}

			$langs->loadLangs(array('krea2factorauth@krea2factorauth'));
			setEventMessages($langs->trans("Krea2FactorAuthPasswordExpiredRequired"), null, 'warnings');
			unset($_SESSION['KREA2FACTORAUTH_SHOW_OPTIONAL_WARNING']);
			unset($_SESSION['KREA2FACTORAUTH_OPTIONAL_ENROLLMENT_SKIPPED']);

			$this->redirectToPasswordChangePage();
		}

		if (!$this->hasPendingMandatoryEnrollment($manager, (int) $user->id)) {
			$this->redirectToPostLoginReturnUrl();
			return 0;
		}

		if ($this->isAllowedMandatorySetupRequest($current, $requestedaction)) {
			return 0;
		}

		$langs->loadLangs(array('krea2factorauth@krea2factorauth'));
		setEventMessages($langs->trans("Krea2FactorAuthMandatorySetupRequired"), null, 'warnings');
		unset($_SESSION['KREA2FACTORAUTH_SHOW_OPTIONAL_WARNING']);
		unset($_SESSION['KREA2FACTORAUTH_OPTIONAL_ENROLLMENT_SKIPPED']);

		$this->redirectToEnrollmentPage();
	}

	/**
	 * Force users with pending mandatory enrollment to the dedicated setup page.
	 *
	 * @param array<string,mixed> $parameters  Hook parameters
	 * @param User                $object      Current user
	 * @param string              $action      Current action
	 * @param HookManager         $hookmanager Hook manager
	 * @return int                             0 on success
	 */
	public function updateSession($parameters, &$object, &$action, $hookmanager)
	{
		global $db, $langs, $user;

		if (empty($user->id)) {
			return 0;
		}

		$current = empty($_SERVER['PHP_SELF']) ? '' : $_SERVER['PHP_SELF'];
		if (strpos($current, '/user/logout.php') !== false) {
			return 0;
		}

		$manager = new Krea2FactorAuthManager($db);
		if (!$this->isCurrentEntityActive($manager)) {
			return 0;
		}

		$requestedaction = GETPOST('action', 'aZ09');
		$requestedid = GETPOSTINT('id');
		if ($this->shouldRedirectImplicitCurrentUserCard($current, $requestedid, $requestedaction, $manager, (int) $user->id)) {
			$this->redirectToCurrentUserPanel((int) $user->id);
		}

		if ($manager->isLoginLockedForUser((int) $user->id)) {
			$langs->loadLangs(array('krea2factorauth@krea2factorauth'));
			unset($_SESSION['KREA2FACTORAUTH_SHOW_OPTIONAL_WARNING']);
			unset($_SESSION['KREA2FACTORAUTH_OPTIONAL_ENROLLMENT_SKIPPED']);
			dol_syslog('ActionsKrea2FactorAuth::updateSession blocked locked active session for user id ' . ((int) $user->id), LOG_WARNING);
			accessforbidden("Krea2FactorAuthLoginLockedMessage");
		}

		if ($manager->isPasswordExpiredForUser((int) $user->id)) {
			if ($this->isAllowedExpiredPasswordRequest($current, $requestedaction)) {
				return 0;
			}

			$langs->loadLangs(array('krea2factorauth@krea2factorauth'));
			setEventMessages($langs->trans("Krea2FactorAuthPasswordExpiredRequired"), null, 'warnings');
			unset($_SESSION['KREA2FACTORAUTH_SHOW_OPTIONAL_WARNING']);
			unset($_SESSION['KREA2FACTORAUTH_OPTIONAL_ENROLLMENT_SKIPPED']);

			$this->redirectToPasswordChangePage();
		}

		if ($requestedaction == 'krea2factorauth_skip_optional_enrollment') {
			$method = empty($_SERVER['REQUEST_METHOD']) ? '' : strtoupper((string) $_SERVER['REQUEST_METHOD']);
			$validskiprequest = ($method === 'POST' && GETPOST('token', 'alpha') !== '');
			$canoptionallyskip = (
				$manager->isOptionalEnrollmentModalEnabled()
				&& !$manager->isEnabledForUser((int) $user->id)
				&& !$manager->isMandatoryForUser((int) $user->id)
				&& !$manager->isMandatoryExemptForUser((int) $user->id)
				&& !$manager->isGoogleTwoFactorConfiguredForUser((int) $user->id)
			);
			if ($validskiprequest && $canoptionallyskip) {
				$_SESSION['KREA2FACTORAUTH_OPTIONAL_ENROLLMENT_SKIPPED'] = '1';
				unset($_SESSION['KREA2FACTORAUTH_SHOW_OPTIONAL_WARNING']);
			} else {
				dol_syslog('ActionsKrea2FactorAuth::updateSession ignored optional enrollment skip with invalid request method or missing token for user id ' . ((int) $user->id), LOG_WARNING);
			}

			header('Location: ' . $this->getCurrentUrlWithout($this->getPostLoginTransientQueryKeys()));
			exit;
		}

		if (!$this->hasPendingMandatoryEnrollment($manager, (int) $user->id)) {
			return 0;
		}

		if ($this->isAllowedMandatorySetupRequest($current, $requestedaction)) {
			return 0;
		}

		$langs->loadLangs(array('krea2factorauth@krea2factorauth'));
		setEventMessages($langs->trans("Krea2FactorAuthMandatorySetupRequired"), null, 'warnings');
		unset($_SESSION['KREA2FACTORAUTH_SHOW_OPTIONAL_WARNING']);
		unset($_SESSION['KREA2FACTORAUTH_OPTIONAL_ENROLLMENT_SKIPPED']);

		$this->redirectToEnrollmentPage();
	}

	/**
	 * Load module CSS on authenticated pages that may print policy notices.
	 *
	 * @param array<string,mixed> $parameters  Hook parameters
	 * @param object              $object      Hook object
	 * @param string              $action      Current action
	 * @param HookManager         $hookmanager Hook manager
	 * @return int                             0 on success
	 */
	public function addHtmlHeader($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $db, $user;

		if (empty($user->id)) {
			$current = empty($_SERVER['PHP_SELF']) ? '' : $_SERVER['PHP_SELF'];
			if (basename($current) !== 'index.php') {
				return 0;
			}

			$manager = new Krea2FactorAuthManager($db);
			if (!$manager->isModuleActiveInAnyEntity()) {
				return 0;
			}

			$loginentity = GETPOSTINT('entity');
			if ($loginentity <= 0) {
				$loginentity = !empty($conf->entity) ? (int) $conf->entity : 1;
			}
			$loginbackgroundcolor = $manager->getLoginBackgroundColorForEntity($loginentity);

			$cssurl = dol_buildpath('/krea2factorauth/css/krea2factorauth.css', 1);
			$csspath = dirname(__DIR__) . '/css/krea2factorauth.css';
			if (is_readable($csspath)) {
				$cssurl .= '?v=' . (string) filemtime($csspath);
			}

			$this->resprints = '<script>document.documentElement.classList.add("krea2factorauth-login-page");document.documentElement.style.setProperty("--krea2fa-login-background","' . dol_escape_js($loginbackgroundcolor) . '");</script>' . "\n";
			$this->resprints .= '<style>html.krea2factorauth-login-page body.bodylogin{background-color:' . dol_escape_htmltag($loginbackgroundcolor) . '!important;background-image:none!important;opacity:1!important;}html.krea2factorauth-login-page body.bodylogin form#login #login_left{display:none!important;}</style>' . "\n";
			$this->resprints .= '<link rel="stylesheet" type="text/css" href="' . dol_escape_htmltag($cssurl) . '">' . "\n";
			return 0;
		}

		$manager = new Krea2FactorAuthManager($db);
		if (!$this->isCurrentEntityActive($manager)) {
			return 0;
		}

		$cssurl = dol_buildpath('/krea2factorauth/css/krea2factorauth.css', 1);
		$csspath = dirname(__DIR__) . '/css/krea2factorauth.css';
		if (is_readable($csspath)) {
			$cssurl .= '?v=' . (string) filemtime($csspath);
		}
		$jsurl = dol_buildpath('/krea2factorauth/js/krea2factorauth.js', 1);
		$jspath = dirname(__DIR__) . '/js/krea2factorauth.js';
		if (is_readable($jspath)) {
			$jsurl .= '?v=' . (string) filemtime($jspath);
		}

		$this->resprints = '<link rel="stylesheet" type="text/css" href="' . dol_escape_htmltag($cssurl) . '">' . "\n";
		$this->resprints .= '<script src="' . dol_escape_htmltag($jsurl) . '"></script>' . "\n";

		return 0;
	}

	/**
	 * Print the non-mandatory post-login enrollment warning.
	 *
	 * @param array<string,mixed> $parameters  Hook parameters
	 * @param object              $object      Hook object
	 * @param string              $action      Current action
	 * @param HookManager         $hookmanager Hook manager
	 * @return int                             0 on success
	 */
	public function llxFooter($parameters, &$object, &$action, $hookmanager)
	{
		global $db, $langs, $user;

		$this->resprints = '';

		$manager = new Krea2FactorAuthManager($db);
		if (!$this->isCurrentEntityActive($manager)) {
			unset($_SESSION['KREA2FACTORAUTH_SHOW_OPTIONAL_WARNING']);
			unset($_SESSION['KREA2FACTORAUTH_OPTIONAL_ENROLLMENT_SKIPPED']);
			return 0;
		}

		if (!empty($_SESSION['KREA2FACTORAUTH_OPTIONAL_ENROLLMENT_SKIPPED']) || empty($user->id)) {
			return 0;
		}

		$current = empty($_SERVER['PHP_SELF']) ? '' : $_SERVER['PHP_SELF'];
		if (strpos($current, '/user/card.php') !== false) {
			$requestedid = GETPOSTINT('id');
			if ($requestedid === (int) $user->id) {
				unset($_SESSION['KREA2FACTORAUTH_SHOW_OPTIONAL_WARNING']);
				return 0;
			}
		}
		if ($this->isEnrollmentPageRequest($current)) {
			unset($_SESSION['KREA2FACTORAUTH_SHOW_OPTIONAL_WARNING']);
			return 0;
		}

		if (strpos($current, '/user/logout.php') !== false) {
			return 0;
		}

		if (!$manager->isOptionalEnrollmentModalEnabled()) {
			unset($_SESSION['KREA2FACTORAUTH_SHOW_OPTIONAL_WARNING']);
			unset($_SESSION['KREA2FACTORAUTH_OPTIONAL_ENROLLMENT_SKIPPED']);
			return 0;
		}
		if ($manager->isEnabledForUser((int) $user->id) || $manager->isMandatoryForUser((int) $user->id) || $manager->isMandatoryExemptForUser((int) $user->id) || $manager->isGoogleTwoFactorConfiguredForUser((int) $user->id)) {
			unset($_SESSION['KREA2FACTORAUTH_SHOW_OPTIONAL_WARNING']);
			return 0;
		}

		$langs->loadLangs(array('krea2factorauth@krea2factorauth'));
		$setupurl = krea2factorauthGetEnrollmentPageUrl();
		$skiptoken = newToken();
		$skipurl = $this->addQueryParams(
			$this->getCurrentUrlWithout($this->getPostLoginTransientQueryKeys()),
			array('action' => 'krea2factorauth_skip_optional_enrollment', 'token' => $skiptoken)
		);
		$skipformid = 'krea2factorauth-optional-skip-form';
		$escapedskipurl = dol_escape_htmltag($skipurl);
		$this->resprints .= '<div class="krea2factorauth-enrollment-modal-backdrop" role="presentation">';
		$this->resprints .= '<section class="krea2factorauth-enrollment-modal" role="dialog" aria-modal="true" aria-labelledby="krea2factorauth-enrollment-modal-title">';
		$this->resprints .= '<div class="krea2factorauth-enrollment-modal-icon"><span class="fa fa-shield-alt"></span></div>';
		$this->resprints .= '<h2 id="krea2factorauth-enrollment-modal-title">' . $langs->trans("Krea2FactorAuthOptionalSetupModalTitle") . '</h2>';
		$this->resprints .= '<p>' . $langs->trans("Krea2FactorAuthOptionalSetupModalText") . '</p>';
		$this->resprints .= '<div class="krea2factorauth-enrollment-modal-actions">';
		$this->resprints .= '<a class="krea2factorauth-primary-button" href="' . dol_escape_htmltag($setupurl) . '"><span class="fa fa-check"></span>' . $langs->trans("Krea2FactorAuthOptionalSetupLink") . '</a>';
		$this->resprints .= '<form id="'.$skipformid.'" method="post" action="' . $escapedskipurl . '">';
		$this->resprints .= '<input type="hidden" name="token" value="' . dol_escape_htmltag($skiptoken) . '">';
		$this->resprints .= '<input type="hidden" name="action" value="krea2factorauth_skip_optional_enrollment">';
		$this->resprints .= '<button type="submit" form="'.$skipformid.'" formaction="' . $escapedskipurl . '" formmethod="post" class="krea2factorauth-secondary-button">';
		$this->resprints .= $langs->trans("Krea2FactorAuthOptionalSetupSkip");
		$this->resprints .= '</button>';
		$this->resprints .= '</form>';
		$this->resprints .= '</div>';
		$this->resprints .= '</section>';
		$this->resprints .= '</div>';

		return 0;
	}

	/**
	 * Add the TOTP code field to the login form.
	 *
	 * @param array<string,mixed> $parameters  Hook parameters
	 * @param object              $object      Hook object
	 * @param string              $action      Current action
	 * @param HookManager         $hookmanager Hook manager
	 * @return int                             0 on success
	 */
	public function getLoginPageOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $db, $langs;

		$langs->load("krea2factorauth@krea2factorauth");

		$manager = new Krea2FactorAuthManager($db);
		if (!$manager->isModuleActiveInAnyEntity()) {
			$this->resprints = '';
			return 0;
		}

		$cssurl = dol_buildpath('/krea2factorauth/css/krea2factorauth.css', 1);
		$csspath = dirname(__DIR__) . '/css/krea2factorauth.css';
		if (is_readable($csspath)) {
			$cssurl .= '?v=' . (string) filemtime($csspath);
		}
		$loginentity = GETPOSTINT('entity');
		if ($loginentity <= 0) {
			$loginentity = !empty($conf->entity) ? (int) $conf->entity : 1;
		}
		$loginbackgroundcolor = $manager->getLoginBackgroundColorForEntity($loginentity);
		$loginbackgrounddefaultcolor = $manager->getLoginBackgroundDefaultColor();
		$loginbackgroundcolors = $manager->getLoginBackgroundColorMap();
		$jsurl = dol_buildpath('/krea2factorauth/js/krea2factorauth.js', 1);
		$jspath = dirname(__DIR__) . '/js/krea2factorauth.js';
		if (is_readable($jspath)) {
			$jsurl .= '?v=' . (string) filemtime($jspath);
		}
		$loginlogourltemplate = dol_buildpath('/krea2factorauth/public/login_logo.php', 1).'?entity=__ENTITY__';
		$loginlogofallbackurl = dol_buildpath('/krea2factorauth/img/shield.png', 1);
		$loginlogofallbackpath = dirname(__DIR__) . '/img/shield.png';
		$loginlogofallbackavailable = is_readable($loginlogofallbackpath);
		if ($loginlogofallbackavailable) {
			$loginlogofallbackurl .= '?v=' . (string) filemtime($loginlogofallbackpath);
		}
		$loginlogoentities = array((int) $loginentity);
		foreach ($manager->fetchModuleActivationByEntity() as $activationentity) {
			$activationentityid = (int) $activationentity['rowid'];
			if ($activationentityid > 0) {
				$loginlogoentities[] = $activationentityid;
			}
		}
		$loginlogoentities = array_values(array_unique($loginlogoentities));
		$loginlogourls = array();
		$missingloginlogoentities = array();
		foreach ($loginlogoentities as $loginlogoentity) {
			$loginlogopath = $manager->getLoginLogoPath($loginlogoentity);
			if ($loginlogopath !== '') {
				$loginlogourls[$loginlogoentity] = str_replace('__ENTITY__', (string) $loginlogoentity, $loginlogourltemplate);
			} elseif ($loginlogofallbackavailable) {
				$loginlogourls[$loginlogoentity] = $loginlogofallbackurl;
				$missingloginlogoentities[$loginlogoentity] = true;
			} else {
				$missingloginlogoentities[$loginlogoentity] = true;
			}
		}
		$challengeimageurl = !empty($loginlogourls[(int) $loginentity]) ? $loginlogourls[(int) $loginentity] : '';
		$challengeimagemissingclass = ($challengeimageurl === '') ? ' krea2factorauth-login-challenge-image-missing' : '';
		$postloginreturnurl = krea2factorauthGetPostLoginReturnUrl();

		$html = "\n" . '<link rel="stylesheet" type="text/css" href="' . dol_escape_htmltag($cssurl) . '">' . "\n";
		$html .= '<style>:root{--krea2fa-login-background: ' . dol_escape_htmltag($loginbackgroundcolor) . '}</style>' . "\n";
		$html .= '<script>document.documentElement.classList.add("krea2factorauth-login-page");window.Krea2FactorAuthLoginEntity=' . ((int) $loginentity) . ';window.Krea2FactorAuthLoginBackgroundColor=' . json_encode($loginbackgroundcolor, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';window.Krea2FactorAuthLoginBackgroundDefaultColor=' . json_encode($loginbackgrounddefaultcolor, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';window.Krea2FactorAuthEntityBackgroundColors=' . json_encode($loginbackgroundcolors, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';window.Krea2FactorAuthLoginLogoUrlTemplate=' . json_encode($loginlogourltemplate, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';window.Krea2FactorAuthLoginLogoUrls=' . json_encode((object) $loginlogourls, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';window.Krea2FactorAuthLoginFallbackLogoUrl=' . json_encode($loginlogofallbackurl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';window.Krea2FactorAuthMissingLoginLogoEntities=' . json_encode((object) $missingloginlogoentities, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';window.Krea2FactorAuthLoginButtonLabel=' . json_encode($langs->transnoentitiesnoconv("Krea2FactorAuthLoginButton"), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';</script>' . "\n";
		$html .= '<script src="' . dol_escape_htmltag($jsurl) . '"></script>' . "\n";
		if ($postloginreturnurl !== '') {
			$html .= '<input type="hidden" name="'.Krea2FactorAuthManager::LOGIN_RETURN_FORM_FIELD.'" value="'.dol_escape_htmltag($postloginreturnurl).'">' . "\n";
		}
		$html .= '<div class="trinputlogin krea2factorauth-login-challenge-image-row' . $challengeimagemissingclass . '">' . "\n";
		$html .= '<div class="krea2factorauth-login-challenge-image"><img src="' . dol_escape_htmltag($challengeimageurl) . '" width="118" height="118" alt="" aria-hidden="true" data-krea2factorauth-login-logo="1" data-krea2factorauth-login-logo-entity="' . ((int) $loginentity) . '"></div>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<div class="trinputlogin krea2factorauth-trinputlogin" data-krea2factorauth-login-challenge>' . "\n";
		$html .= '<div class="tagtd nowraponall center valignmiddle tdinputlogin krea2factorauth-login-challenge">' . "\n";
		$html .= '<div class="krea2factorauth-login-heading"><span class="fa fa-shield-alt krea2factorauth-login-icon"></span><span>' . $langs->trans("Krea2FactorAuthLoginCode") . '</span></div>';
		$html .= krea2factorauthRenderTotpCodeInput('totp_code', 'totp_digit', 'krea2factorauth-login-code', $langs->trans("Krea2FactorAuthLoginCode"), array('compact' => 1, 'tabindex' => 3));
		$html .= '<div class="krea2factorauth-login-hint">' . $langs->trans("Krea2FactorAuthLoginHint") . '</div>';
		$html .= "\n" . '</div></div>' . "\n";
		$mainauthentication = isset($conf->file->main_authentication) ? (string) $conf->file->main_authentication : '';
		$coregooglebuttonvisible = preg_match('/google/', $mainauthentication) && strpos($conf->browser->ua, 'DoliDroid') === false;
		$mustusemodulegooglebutton = $manager->isGoogleLoginEmailExtrafieldEnabled();
		if ($manager->isGoogleTwoFactorAvailable() && $manager->isModuleActiveForEntity($loginentity) && (!$coregooglebuttonvisible || $mustusemodulegooglebutton) && strpos($conf->browser->ua, 'DoliDroid') === false) {
			$googleloginbridgeurl = dol_buildpath('/krea2factorauth/public/google_login.php', 1);
			$orlabel = dol_escape_htmltag($langs->trans("Krea2FactorAuthLoginOr"));
			$html .= '<!-- BEGIN KREA2FACTORAUTH GOOGLE LOGIN -->' . "\n";
			$html .= '<div class="trinputlogin krea2factorauth-google-login-row" data-krea2factorauth-google-login-row="1">';
			$html .= '<div class="krea2factorauth-login-or-separator" role="separator" aria-label="'.$orlabel.'"><span>'.$orlabel.'</span></div>';
			$html .= '<div class="tagtd nowraponall center valignmiddle krea2factorauth-google-login-cell">';
			$html .= '<input type="hidden" name="beforeoauthloginredirect" value="" data-krea2factorauth-google-login-flag="1">';
			$html .= '<button type="submit" class="krea2factorauth-google-login-button" name="beforeoauthloginredirect" value="google" formaction="'.dol_escape_htmltag($googleloginbridgeurl).'" formmethod="post" formnovalidate data-krea2factorauth-google-login="1">';
			$html .= img_picto('', 'google', 'class="krea2factorauth-google-login-icon"');
			$html .= dol_escape_htmltag($langs->trans("Krea2FactorAuthLoginWithGoogle"));
			$html .= '</button>';
			$html .= '</div></div>' . "\n";
			$html .= '<!-- END KREA2FACTORAUTH GOOGLE LOGIN -->' . "\n";
		}

		$this->resprints = $html;

		return 0;
	}
}
