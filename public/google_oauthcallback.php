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
 * \file    krea2factorauth/public/google_oauthcallback.php
 * \ingroup krea2factorauth
 * \brief   Google OAuth callback that maps login by the module user email extrafield.
 */

if (!defined('NOLOGIN')) {
	define('NOLOGIN', '1');
}
if (!defined('DOLENTITY')) {
	if (!empty($_GET['entity']) && ctype_digit((string) $_GET['entity'])) {
		define('DOLENTITY', (int) $_GET['entity']);
	} elseif (!empty($_GET['state']) && preg_match('/-entity([1-9][0-9]*)-[^-]+$/', (string) $_GET['state'], $matches)) {
		define('DOLENTITY', (int) $matches[1]);
	}
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

require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';
dol_include_once('/krea2factorauth/class/krea2factorauthmanager.class.php');
dol_include_once('/krea2factorauth/lib/krea2factorauth.lib.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 */

/**
 * Decode the JSON payload of a Google OpenID Connect id_token.
 *
 * @param string $idtoken Google id_token
 * @return array<string,mixed>
 */
function krea2factorauthDecodeGoogleIdTokenPayload($idtoken)
{
	$jwt = explode('.', (string) $idtoken);
	if (empty($jwt[1])) {
		return array();
	}

	$payload = strtr($jwt[1], '-_', '+/');
	$payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
	$decoded = base64_decode($payload, true);
	if ($decoded === false) {
		return array();
	}

	$userinfo = json_decode($decoded, true);
	return is_array($userinfo) ? $userinfo : array();
}

/**
 * Redirect to the saved login page with an optional OAuth return payload.
 *
 * @param string $username Login mapped from Google
 * @param int    $entity   Login entity
 * @return void
 */
function krea2factorauthRedirectAfterGoogleOAuth($username = '', $entity = 1)
{
	global $conf;

	$entity = (int) $entity;
	if ($entity > 0) {
		$_SESSION["dol_entity"] = $entity;
		if (is_object($conf)) {
			$conf->entity = $entity;
		}
	}

	$backtourl = empty($_SESSION["backtourlsavedbeforeoauthjump"]) ? '' : krea2factorauthNormalizePostLoginReturnUrl($_SESSION["backtourlsavedbeforeoauthjump"]);
	unset($_SESSION["backtourlsavedbeforeoauthjump"]);
	if ($backtourl === '') {
		$backtourl = DOL_URL_ROOT.'/';
	}

	if ($username !== '') {
		$separator = (strpos($backtourl, '?') === false) ? '?' : '&';
		$backtourl .= $separator.'actionlogin=login&afteroauthloginreturn=google&mainmenu=home&loginfunction=loginfunction&username='.urlencode($username).'&token='.newToken();
		if ($entity > 0) {
			$backtourl .= '&entity='.$entity;
		}
	}

	header('Location: '.$backtourl);
	exit;
}

/**
 * Return the selected login entity preserved through the OAuth round trip.
 *
 * @param string $state OAuth state
 * @return int          Login entity
 */
function krea2factorauthGetGoogleOAuthLoginEntity($state)
{
	global $conf;

	$entity = 0;
	if (!empty($_SESSION['datafromloginform']) && is_array($_SESSION['datafromloginform']) && !empty($_SESSION['datafromloginform']['entity'])) {
		$entity = (int) $_SESSION['datafromloginform']['entity'];
	}
	if ($entity <= 0 && preg_match('/-entity([1-9][0-9]*)-[^-]+$/', (string) $state, $matches)) {
		$entity = (int) $matches[1];
	}
	if ($entity <= 0) {
		$entity = GETPOSTINT('entity');
	}
	if ($entity <= 0) {
		$entity = !empty($conf->entity) ? (int) $conf->entity : 1;
	}

	return $entity > 0 ? $entity : 1;
}

$langs->loadLangs(array('main', 'oauth', 'errors', 'krea2factorauth@krea2factorauth'));

$manager = new Krea2FactorAuthManager($db);

$state = GETPOST('state');
$loginentity = krea2factorauthGetGoogleOAuthLoginEntity($state);

if (!$manager->isGoogleTwoFactorAvailable()) {
	$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthGoogleLoginUnavailable");
	dol_syslog("krea2factorauth/public/google_oauthcallback.php refused unavailable Google login", LOG_WARNING);
	krea2factorauthRedirectAfterGoogleOAuth('', $loginentity);
}

$action = GETPOST('action', 'aZ09');
$backtourl = krea2factorauthNormalizePostLoginReturnUrl(GETPOST('backtourl', 'restricthtml'));
$keyforprovider = 'Login';
$statewithscopeonly = '';
$statewithanticsrfonly = '';
$requestedpermissionsarray = array();

if ($state) {
	$statewithscopeonly = preg_replace('/\-.*$/', '', preg_replace('/^forlogin-/', '', $state));
	$requestedpermissionsarray = explode(',', $statewithscopeonly);
	$statewithanticsrfonly = preg_replace('/^.*\-/', '', $state);
}

if ($action != 'delete' && !GETPOST('afteroauthloginreturn') && (empty($statewithscopeonly) || empty($requestedpermissionsarray) || empty($statewithanticsrfonly) || !preg_match('/^forlogin-/', (string) $state))) {
	$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthGoogleLoginUnavailable");
	krea2factorauthRedirectAfterGoogleOAuth('', $loginentity);
}

$uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
$currentUri = $uriFactory->createFromAbsolute($manager->getGoogleOAuthModuleCallbackUrl());

$serviceFactory = new \OAuth\ServiceFactory();
$httpClient = new \OAuth\Common\Http\Client\CurlClient();
$serviceFactory->setHttpClient($httpClient);

$credentials = new \OAuth\Common\Consumer\Credentials(
	$manager->getGoogleOAuthClientId(),
	$manager->getGoogleOAuthClientSecret(),
	$currentUri->getAbsoluteUri()
);

$storage = new \OAuth\Common\Storage\DoliStorage($db, $conf, $keyforprovider);

try {
	$apiService = $serviceFactory->createService('Google', $credentials, $storage, $requestedpermissionsarray);
	'@phan-var-force  OAuth\OAuth2\Service\Google $apiService';
} catch (Exception $e) {
	$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthGoogleOAuthInitFailed", $e->getMessage());
	krea2factorauthRedirectAfterGoogleOAuth('', $loginentity);
}
$apiService->setAccessType('offline');

if ($manager->getGoogleOAuthClientId() === '' || $manager->getGoogleOAuthClientSecret() === '') {
	$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthGoogleLoginCredentialsMissing");
	krea2factorauthRedirectAfterGoogleOAuth('', $loginentity);
}

if (!GETPOST('code')) {
	$_SESSION["backtourlsavedbeforeoauthjump"] = $backtourl !== '' ? $backtourl : DOL_URL_ROOT.'/';
	$_SESSION["oauthkeyforproviderbeforeoauthjump"] = $keyforprovider;
	$_SESSION['oauthstateanticsrf'] = $state;

	$apiService->setApprouvalPrompt(getDolGlobalString('OAUTH_GOOGLE_FORCE_PROMPT_ON_LOGIN', 'auto'));
	$url = $apiService->getAuthorizationUri(array('state' => $state));
	$url .= '&nonce='.bin2hex(random_bytes(64 / 8));

	$username = trim(GETPOST('username', 'alphanohtml'));
	if ($username !== '') {
		$url .= '&login_hint='.urlencode($username);
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
	$currentrooturl = getRootURLFromURL(DOL_MAIN_URL_ROOT);
	$externalrooturl = getRootURLFromURL($manager->getGoogleOAuthModuleCallbackUrl());
	if ($currentrooturl != $externalrooturl) {
		$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorTheUrlOfYourDolInstanceDoesNotMatchURLIntoOAuthSetup", $currentrooturl, $externalrooturl);
		krea2factorauthRedirectAfterGoogleOAuth('', $loginentity);
	}

	header('Location: '.$url);
	exit;
}

if (!isset($_SESSION['oauthstateanticsrf']) || $state != $_SESSION['oauthstateanticsrf']) {
	unset($_SESSION['oauthstateanticsrf']);
	$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthGoogleOAuthStateValidationFailed");
	krea2factorauthRedirectAfterGoogleOAuth('', $loginentity);
}
unset($_SESSION['oauthstateanticsrf']);

$errorincheck = 0;
$username = '';
$useremail = '';
$entitytosearchuser = $loginentity;

try {
	$db->begin();

	$token = null;
	try {
		$token = $apiService->requestAccessToken(GETPOST('code'), $state);
	} catch (Exception $e) {
		dol_syslog("krea2factorauth/public/google_oauthcallback.php failed to get token: ".$e->getMessage(), LOG_ERR);
		$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthGoogleOAuthTokenRequestFailed");
		$errorincheck++;
	}

	$extraparams = array();
	if ($token) {
		$extraparams = $token->getExtraParams();
	}
	$userinfo = empty($extraparams['id_token']) ? array() : krea2factorauthDecodeGoogleIdTokenPayload($extraparams['id_token']);
	if (empty($userinfo)) {
		$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthGoogleOAuthIdentityTokenUnreadable");
		$errorincheck++;
	}

	if (!$errorincheck) {
		$useremail = $manager->normalizeGoogleLoginEmail(empty($userinfo['email']) ? '' : (string) $userinfo['email']);
		if ($useremail === '') {
			$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthGoogleOAuthEmailMissing");
			$errorincheck++;
		}
		if (array_key_exists('email_verified', $userinfo) && empty($userinfo['email_verified'])) {
			$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthGoogleOAuthEmailNotVerified");
			$errorincheck++;
		}
		if (empty($userinfo['iss']) || ($userinfo['iss'] != 'accounts.google.com' && $userinfo['iss'] != 'https://accounts.google.com')) {
			$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthGoogleOAuthIssuerInvalid");
			$errorincheck++;
		}
		if (empty($userinfo['aud']) || $userinfo['aud'] != $manager->getGoogleOAuthClientId()) {
			$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthGoogleOAuthAudienceInvalid");
			$errorincheck++;
		}
		if (empty($userinfo['exp']) || (int) $userinfo['exp'] <= dol_now()) {
			$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthGoogleOAuthIdentityTokenExpired");
			$errorincheck++;
		}
	}

	if (!$errorincheck) {
		$entitytosearchuser = $loginentity;
		if ($entitytosearchuser <= 0) {
			$entitytosearchuser = 1;
		}

		$storage->clearToken('Google');
		$tmpuser = $manager->isGoogleLoginEmailExtrafieldEnabled()
			? $manager->fetchUserByGoogleLoginEmail($useremail, $entitytosearchuser)
			: $manager->fetchUserByGoogleOAuthEmail($useremail, $entitytosearchuser);
		if ($tmpuser instanceof User && !empty($tmpuser->id)) {
			$username = $tmpuser->login;
			if ($manager->recordGoogleLoginReturnEmail($tmpuser, $useremail, $entitytosearchuser) < 0) {
				$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthGoogleOAuthReturnStateFailed");
				$errorincheck++;
			} else {
				$_SESSION['googleoauth_receivedlogin'] = dol_hash($conf->file->instance_unique_id.$username, '0');
				dol_syslog('krea2factorauth/public/google_oauthcallback.php mapped Google email to user id '.((int) $tmpuser->id), LOG_INFO);
			}
		} else {
			$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv($manager->isGoogleLoginEmailExtrafieldEnabled() ? "Krea2FactorAuthGoogleLoginEmailDenied" : "ErrorCantLoadUserFromDolibarrDatabase", $useremail);
			$errorincheck++;
			dol_syslog('krea2factorauth/public/google_oauthcallback.php could not map Google email '.$useremail.': '.$manager->error, LOG_WARNING);
		}
	}

	if (!$errorincheck) {
		$db->commit();
	} else {
		$db->rollback();
		$manager->clearGoogleLoginPendingChallenge();
		$username = '';
	}
} catch (Exception $e) {
	$db->rollback();
	$manager->clearGoogleLoginPendingChallenge();
	$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("Krea2FactorAuthGoogleOAuthGenericFailure", $e->getMessage());
	$username = '';
}

krea2factorauthRedirectAfterGoogleOAuth($username, $entitytosearchuser);
