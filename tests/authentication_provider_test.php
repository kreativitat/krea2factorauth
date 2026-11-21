<?php
/* Copyright (C) 2026 Kreativität Works <mail@kreativitat.com>
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Isolated authentication-provider regressions with synthetic Dolibarr fixtures.
 * Run: php tests/authentication_provider_test.php
 * No application bootstrap, real database, credentials or network are used.
 */

error_reporting(E_ALL);

$fixtureRoot = sys_get_temp_dir().'/krea2factorauth-test-'.bin2hex(random_bytes(8));
$fixtureFiles = array(
	'/core/lib/functions2.lib.php', '/core/lib/date.lib.php',
	'/core/lib/security.lib.php', '/core/lib/security2.lib.php',
	'/core/lib/admin.lib.php', '/user/class/user.class.php',
	'/core/login/functions_googleoauth.php',
	'/core/modules/oauth/google_oauthcallback.php', '/includes/OAuth/bootstrap.php',
);
foreach ($fixtureFiles as $file) {
	$directory = dirname($fixtureRoot.$file);
	if (!is_dir($directory)) {
		mkdir($directory, 0700, true);
	}
	file_put_contents($fixtureRoot.$file, "<?php\n");
}
register_shutdown_function(function () use ($fixtureRoot) {
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($fixtureRoot, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ($files as $file) {
		$file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
	}
	rmdir($fixtureRoot);
});
define('DOL_DOCUMENT_ROOT', $fixtureRoot);
define('MAIN_DB_PREFIX', 'test_');

/** Synthetic user; fetch never connects to an installation. */
class User
{
	public $id = 17;
	public $login = 'test-user';
	public $email = 'test-user@example.invalid';
	public $email_oauth2 = '';
	public $status = 1;
	public $statut = 1;
	public $entity = 2;
	public $admin = 0;

	public function __construct($database) {}

	public function fetch($id, $login = '', $unused = '', $unused2 = 0, $entity = -1)
	{
		if (!empty($GLOBALS['missingTestUser'])) {
			return -1;
		}
		return ($login === '' || $login === $this->login) && ($entity === -1 || $entity === $this->entity) ? 1 : -1;
	}
}

class AuthenticationTestResult
{
	public $rows;
	public $affected;

	public function __construct(array $rows = array(), $affected = 0)
	{
		$this->rows = $rows;
		$this->affected = $affected;
	}
}

/** Only the SQL needed by the exercised policies is accepted. */
class AuthenticationTestDatabase
{
	public $params = array();
	public $googleEmail = '';

	public function escape($value) { return addslashes($value); }
	public function sanitize($value) { return $value; }
	public function DDLInfoTable($table) { return array(array('krea2factorauth_email')); }
	public function begin() {}
	public function commit() {}
	public function rollback() {}
	public function free($result) {}
	public function affected_rows($result) { return $result->affected; }
	public function fetch_object($result) { return count($result->rows) ? (object) array_shift($result->rows) : false; }

	public function query($sql)
	{
		if (strpos($sql, 'SELECT p.value') === 0 && preg_match("/p.param = '([^']+)'/", $sql, $matches)) {
			$value = isset($this->params[$matches[1]]) ? $this->params[$matches[1]] : null;
			return new AuthenticationTestResult($value === null ? array() : array(array('value' => $value)));
		}
		if (strpos($sql, 'SELECT uef.krea2factorauth_email as google_login_emails') === 0) {
			return new AuthenticationTestResult(array(array('google_login_emails' => $this->googleEmail)));
		}
		if (preg_match("/^UPDATE test_user_param SET value = '([^']+)' .*param = '([^']+)'/", $sql, $matches)) {
			$current = isset($this->params[$matches[2]]) ? $this->params[$matches[2]] : null;
			$affected = $current !== null && strcmp($current, $matches[1]) < 0 ? 1 : 0;
			if ($affected) {
				$this->params[$matches[2]] = $matches[1];
			}
			return new AuthenticationTestResult(array(), $affected);
		}
		if (preg_match("/^SELECT value FROM test_user_param .*param = '([^']+)'/", $sql, $matches)) {
			$value = isset($this->params[$matches[1]]) ? $this->params[$matches[1]] : null;
			return new AuthenticationTestResult($value === null ? array() : array(array('value' => $value)));
		}
		if (strpos($sql, 'DELETE FROM test_user_param') === 0 && preg_match('/param IN \((.+)\)/', $sql, $matches)) {
			preg_match_all("/'([^']+)'/", $matches[1], $keys);
			foreach ($keys[1] as $key) {
				unset($this->params[$key]);
			}
			return new AuthenticationTestResult();
		}
		if (preg_match("/^INSERT INTO test_user_param.*VALUES \([0-9]+, 0, '([^']+)', '([^']+)'\)/", $sql, $matches)) {
			$this->params[$matches[1]] = $matches[2];
			return new AuthenticationTestResult(array(), 1);
		}
		throw new RuntimeException('Unexpected SQL in isolated authentication test.');
	}
}

class AuthenticationTestLanguages
{
	public function loadLangs($catalogs) {}
	public function transnoentitiesnoconv($key) { return $key; }
}

function GETPOST($name, $filter = '', $method = 0) { return isset($GLOBALS['request'][$name]) ? $GLOBALS['request'][$name] : ''; }
function GETPOSTISARRAY($name, $method = 0) { return is_array(GETPOST($name)); }
function dol_syslog($message, $level = 0) {}
function dol_now() { return time(); }
function getEntity($type) { return '0,2'; }
function getDolGlobalString($name, $default = '') { return $default; }
function dolDecrypt($value) { return $value; }
function dolibarr_get_const($database, $name, $entity) { return isset($GLOBALS['settings'][$name]) ? $GLOBALS['settings'][$name] : ''; }

/**
 * Synthetic native delegation contract: Google accepts and consumes only its
 * callback session proof; otherwise a valid local password can authenticate.
 * Dolibarr records the successful delegated provider in conf->authmode.
 */
function checkLoginPassEntity($login, $password, $entity, $modes, $context = '')
{
	global $conf;
	foreach ($modes as $mode) {
		if ($mode === 'googleoauth' && GETPOST('actionlogin') === 'login' && GETPOST('afteroauthloginreturn') !== '') {
			if (isset($_SESSION['googleoauth_receivedlogin']) && $_SESSION['googleoauth_receivedlogin'] === $login) {
				unset($_SESSION['googleoauth_receivedlogin']);
				$conf->authmode = 'googleoauth';
				return $login;
			}
		}
		if ($mode === 'dolibarr' && $password === 'synthetic-password') {
			$conf->authmode = 'dolibarr';
			return $login;
		}
	}
	return '';
}

require dirname(__DIR__).'/core/login/functions_krea2factorauth.php';
require dirname(__DIR__).'/vendor/autoload.php';

/** Generate an RFC 6238 code independently for the public synthetic fixture. */
function getSyntheticTotpCode()
{
	$counter = (int) floor(time() / 30);
	$secret = hex2bin('48656c6c6f21deadbeef');
	$hmac = hash_hmac('sha1', pack('N2', 0, $counter), $secret, true);
	$offset = ord($hmac[strlen($hmac) - 1]) & 15;
	$value = unpack('Nvalue', substr($hmac, $offset, 4));
	return str_pad((string) (($value['value'] & 0x7fffffff) % 1000000), 6, '0', STR_PAD_LEFT);
}

function resetAuthenticationFixture()
{
	$GLOBALS['db'] = new AuthenticationTestDatabase();
	$GLOBALS['langs'] = new AuthenticationTestLanguages();
	$GLOBALS['conf'] = (object) array('entity' => 2, 'authmode' => '');
	$GLOBALS['settings'] = array(
		'KREA2FACTORAUTH_GOOGLE_LOGIN_ENABLED' => '1',
		'OAUTH_GOOGLE-Login_ID' => 'synthetic-client-id',
		'OAUTH_GOOGLE-Login_SECRET' => 'synthetic-client-secret',
		'MAIN_MODULE_KREA2FACTORAUTH' => '1',
		'KREA2FACTORAUTH_ENROLLMENT_PROMPT_MODE' => 'optional',
	);
	$GLOBALS['request'] = array('actionlogin' => 'login');
	$_SESSION = array();
	$GLOBALS['missingTestUser'] = false;
	$GLOBALS['db']->params[Krea2FactorAuthManager::PARAM_SECRET] = 'JBSWY3DPEHPK3PXP';
}

function setGoogleReturnFixture($withNativeProof = true)
{
	$GLOBALS['request']['afteroauthloginreturn'] = 'google';
	if ($withNativeProof) {
		$_SESSION['googleoauth_receivedlogin'] = 'test-user';
	}
	$manager = new Krea2FactorAuthManager($GLOBALS['db']);
	$manager->recordGoogleLoginReturnEmail(new User($GLOBALS['db']), 'test-user@example.invalid', 2);
}

function checkAuthenticationCase($name, $expected, $configure, $password = 'synthetic-password')
{
	resetAuthenticationFixture();
	$configure();
	$actual = check_user_password_krea2factorauth('test-user', $password, 2);
	if ($actual !== $expected) {
		$GLOBALS['failures']++;
		fwrite(STDERR, 'FAIL '.$name.': expected '.($expected === 'test-user' ? 'accepted' : 'rejected').', got '.($actual === 'test-user' ? 'accepted' : 'rejected')."\n");
	} else {
		echo 'PASS '.$name."\n";
	}
}

$failures = 0;
$denied = '--bad-login-validity--';
checkAuthenticationCase('password login requires enrolled TOTP', $denied, function () {});
checkAuthenticationCase('forged Google return cannot bypass TOTP', $denied, function () {
	$GLOBALS['request']['afteroauthloginreturn'] = 'google';
});
checkAuthenticationCase('module callback marker cannot bless password fallback', $denied, function () {
	setGoogleReturnFixture(false);
});
checkAuthenticationCase('native Google proof requires module callback proof', $denied, function () {
	$GLOBALS['request']['afteroauthloginreturn'] = 'google';
	$_SESSION['googleoauth_receivedlogin'] = 'test-user';
}, '');
checkAuthenticationCase('valid Google callback skips local TOTP', 'test-user', function () {
	setGoogleReturnFixture();
}, '');
checkAuthenticationCase('expired module callback marker is refused', $denied, function () {
	setGoogleReturnFixture();
	$_SESSION[Krea2FactorAuthManager::GOOGLE_LOGIN_RETURN_EMAIL_SESSION]['created'] = time() - Krea2FactorAuthManager::GOOGLE_LOGIN_PENDING_TTL - 1;
}, '');
checkAuthenticationCase('wrong user callback marker is refused', $denied, function () {
	setGoogleReturnFixture();
	$_SESSION[Krea2FactorAuthManager::GOOGLE_LOGIN_RETURN_EMAIL_SESSION]['userid'] = 18;
}, '');
checkAuthenticationCase('wrong entity callback marker is refused', $denied, function () {
	setGoogleReturnFixture();
	$_SESSION[Krea2FactorAuthManager::GOOGLE_LOGIN_RETURN_EMAIL_SESSION]['entity'] = 3;
}, '');
checkAuthenticationCase('stale successful provider cannot bless password fallback', $denied, function () {
	$GLOBALS['conf']->authmode = 'googleoauth';
	$GLOBALS['request']['afteroauthloginreturn'] = 'google';
});
checkAuthenticationCase('Google-only policy rejects local password', $denied, function () {
	unset($GLOBALS['db']->params[Krea2FactorAuthManager::PARAM_SECRET]);
	$GLOBALS['db']->googleEmail = 'test-user@example.invalid';
});
checkAuthenticationCase('forged Google return cannot bypass Google-only policy', $denied, function () {
	unset($GLOBALS['db']->params[Krea2FactorAuthManager::PARAM_SECRET]);
	$GLOBALS['db']->googleEmail = 'test-user@example.invalid';
	$GLOBALS['request']['afteroauthloginreturn'] = 'google';
});
checkAuthenticationCase('valid local TOTP remains accepted', 'test-user', function () {
	$GLOBALS['request']['totp_code'] = getSyntheticTotpCode();
});
checkAuthenticationCase('invalid local TOTP remains rejected', $denied, function () {
	$GLOBALS['request']['totp_code'] = 'invalid';
});
checkAuthenticationCase('Google callback is single use even with a valid local password', $denied, function () {
	setGoogleReturnFixture();
	$first = check_user_password_krea2factorauth('test-user', '', 2);
	if ($first !== 'test-user' || isset($_SESSION['googleoauth_receivedlogin']) || isset($_SESSION[Krea2FactorAuthManager::GOOGLE_LOGIN_RETURN_EMAIL_SESSION])) {
		throw new RuntimeException('Valid Google callback did not consume its session proofs.');
	}
});
checkAuthenticationCase('primary success without a fetched user fails closed', $denied, function () {
	$GLOBALS['missingTestUser'] = true;
});
checkAuthenticationCase('locked user remains rejected after valid Google callback', $denied, function () {
	setGoogleReturnFixture();
	$GLOBALS['db']->params[Krea2FactorAuthManager::PARAM_LOGIN_LOCKED] = '1';
}, '');
checkAuthenticationCase('Google callback cannot authenticate when module Google is disabled', $denied, function () {
	setGoogleReturnFixture();
	$GLOBALS['settings']['KREA2FACTORAUTH_GOOGLE_LOGIN_ENABLED'] = '0';
}, '');

if ($failures > 0) {
	fwrite(STDERR, $failures." authentication regressions failed.\n");
	exit(1);
}
echo "All authentication-provider regressions passed.\n";
