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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    krea2factorauth/admin/users.php
 * \ingroup krea2factorauth
 * \brief   Krea2FactorAuth managed users page.
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

$hookmanager->initHooks(array('krea2factorauthusers', 'globalsetup'));

$manager = new Krea2FactorAuthManager($db);
$showgoogle2fa = $manager->isGoogleTwoFactorAvailable();
$showtotp2fa = $manager->isTotpEnrollmentAvailable();

$managedusersfilterkey = 'KREA2FACTORAUTH_MANAGED_USERS_FILTERS';
$removefilterrequested = (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha'));
$searchrequested = (GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha'));
$filterinputsubmitted = false;
$filterinputnames = array('search_login', 'search_name', 'search_status');
if ($showtotp2fa) {
	$filterinputnames[] = 'search_policy';
}
if ($showgoogle2fa) {
	$filterinputnames[] = 'search_google';
}
foreach ($filterinputnames as $filterinputname) {
	if (GETPOSTISSET($filterinputname)) {
		$filterinputsubmitted = true;
		break;
	}
}

$action = GETPOST('action', 'aZ09');
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
$limit = GETPOSTINT('limit');
$searchlogin = trim(GETPOST('search_login', 'alphanohtml'));
$searchname = trim(GETPOST('search_name', 'alphanohtml'));
$searchstatus = GETPOST('search_status', 'alpha');
$searchpolicy = GETPOST('search_policy', 'alpha');
$searchgoogle = $showgoogle2fa ? GETPOST('search_google', 'alpha') : '';
if (!$removefilterrequested && !$filterinputsubmitted && !empty($_SESSION[$managedusersfilterkey]) && is_array($_SESSION[$managedusersfilterkey])) {
	$savedfilters = $_SESSION[$managedusersfilterkey];
	$searchlogin = isset($savedfilters['login']) ? (string) $savedfilters['login'] : '';
	$searchname = isset($savedfilters['name']) ? (string) $savedfilters['name'] : '';
	$searchstatus = isset($savedfilters['status']) ? (string) $savedfilters['status'] : '';
	$searchpolicy = isset($savedfilters['policy']) ? (string) $savedfilters['policy'] : '';
	$searchgoogle = ($showgoogle2fa && isset($savedfilters['google'])) ? (string) $savedfilters['google'] : '';
}
if (empty($sortfield)) {
	$sortfield = 'u.login';
}
if (empty($sortorder)) {
	$sortorder = 'ASC';
}
$allowedlistsortfields = array('u.login', 'u.lastname', 'u.lastname,u.firstname,u.login', 'u.statut', 'password_expiration');
if ($showtotp2fa) {
	$allowedlistsortfields[] = 'totp2fa';
}
if ($showgoogle2fa) {
	$allowedlistsortfields[] = 'google2fa';
}
if (!in_array($sortfield, $allowedlistsortfields, true)) {
	$sortfield = 'u.login';
}
$normalizedsortorders = array();
foreach (explode(',', $sortorder) as $sortorderpart) {
	$sortorderpart = strtoupper(trim($sortorderpart));
	$normalizedsortorders[] = in_array($sortorderpart, array('ASC', 'DESC'), true) ? $sortorderpart : 'ASC';
}
$sortorder = implode(',', $normalizedsortorders);
if (empty($page) || $page < 0 || $searchrequested || $removefilterrequested) {
	$page = 0;
}
if ($limit <= 0) {
	$limit = (int) $conf->liste_limit;
}
if ($limit <= 0) {
	$limit = 20;
}
if ($removefilterrequested) {
	$searchlogin = '';
	$searchname = '';
	$searchstatus = '';
	$searchpolicy = '';
	$searchgoogle = '';
	unset($_SESSION[$managedusersfilterkey]);
}
if (!$showgoogle2fa) {
	$searchgoogle = '';
}
if (!$showtotp2fa) {
	$searchpolicy = '';
}
$allowedstatusfilters = array('', 'enabled', 'disabled');
if (!in_array($searchstatus, $allowedstatusfilters, true)) {
	$searchstatus = '';
}
$allowedpolicyfilters = array('', 'active', 'mandatory', 'exempt', 'optional', 'locked', 'unenrolled');
if (!in_array($searchpolicy, $allowedpolicyfilters, true)) {
	$searchpolicy = '';
}
if ($showgoogle2fa) {
	$allowedgooglefilters = array('', 'configured', 'not_configured');
	if (!in_array($searchgoogle, $allowedgooglefilters, true)) {
		$searchgoogle = '';
	}
}
$policyfilters = array(
	'login' => $searchlogin,
	'name' => $searchname,
	'status' => $searchstatus,
	'policy' => $searchpolicy,
	'google' => $searchgoogle,
);
if (!$removefilterrequested) {
	$_SESSION[$managedusersfilterkey] = $policyfilters;
}
$error = 0;

$manager->ensureGlobalStorageMigrated();
if ($manager->deleteGlobalLoginLogoConstants() < 0) {
	setEventMessages($manager->error, $manager->errors, 'warnings');
}
if ($manager->syncGlobalLoginBootstrap() < 0) {
	setEventMessages($manager->error, $manager->errors, 'warnings');
}
$issuperadmin = $manager->isSuperAdmin($user);
$pageurl = dol_buildpath('/krea2factorauth/admin/users.php', 1);
$pageurlwithmenu = $pageurl.'?mainmenu=home&leftmenu=users';

/*
 * Actions
 */

$manageduseractions = array('lock_user_2fa', 'unlock_user_2fa', 'enable_user_password_expiration', 'disable_user_password_expiration');
if ($showtotp2fa) {
	$manageduseractions[] = 'reset_user_2fa';
	$manageduseractions[] = 'exempt_user_2fa';
	$manageduseractions[] = 'require_user_2fa';
	$manageduseractions[] = 'generate_exception_password';
}
if ($showgoogle2fa) {
	$manageduseractions[] = 'save_user_google_emails';
	$manageduseractions[] = 'clear_user_google_emails';
}
if (in_array($action, $manageduseractions, true)) {
	krea2factorauthRequirePostToken($action);

	if (!$issuperadmin) {
		accessforbidden("Krea2FactorAuthSuperAdminPolicyOnly");
	}

	$targetuserid = GETPOSTINT('target_user_id');
	if ($targetuserid <= 0) {
		$error++;
		setEventMessages($langs->trans("Krea2FactorAuthSelectUserForPolicy"), null, 'errors');
	} elseif (!$manager->isUserVisibleInCurrentEntity($targetuserid)) {
		accessforbidden('Invalid user scope');
	} else {
		$targetuser = new User($db);
		$result = $targetuser->fetch($targetuserid, '', '', 0);
		if ($result <= 0) {
			$error++;
			setEventMessages($langs->trans("Krea2FactorAuthUserNotFound"), null, 'errors');
		} else {
			$result = 0;
			$generatedexceptionpassword = '';
			$passwordrotated = false;
			if ($action == 'reset_user_2fa') {
				$result = $manager->resetEnrollmentForUser($targetuser, $user);
				$messagekey = "Krea2FactorAuthUserResetForEnrollment";
			} elseif ($action == 'lock_user_2fa') {
				$result = $manager->setLoginLockForUser($targetuser, $user, true);
				$messagekey = "Krea2FactorAuthUserLocked";
			} elseif ($action == 'unlock_user_2fa') {
				$result = $manager->setLoginLockForUser($targetuser, $user, false);
				$messagekey = "Krea2FactorAuthUserUnlocked";
			} elseif ($action == 'exempt_user_2fa') {
				$result = $manager->exemptUserWithGeneratedPassword($targetuser, $user, $generatedexceptionpassword, $passwordrotated);
				$messagekey = "Krea2FactorAuthUserExemptedWithPersistentPassword";
			} elseif ($action == 'require_user_2fa') {
				$wasenabled = $manager->isEnabledForUser((int) $targetuser->id);
				$result = $manager->setMandatoryExemptionForUser($targetuser, $user, false);
				$messagekey = ($wasenabled ? "Krea2FactorAuthUserRequiredForLogin" : "Krea2FactorAuthUserRequiredForEnrollment");
			} elseif ($action == 'generate_exception_password') {
				if (!$manager->isMandatoryExemptForUser((int) $targetuser->id)) {
					$result = -1;
					$manager->error = $langs->trans("Krea2FactorAuthExceptionPasswordRequiresExemptUser");
					$manager->errors[] = $manager->error;
				} else {
					$result = $manager->generateExceptionPasswordForUser($targetuser, $user, $generatedexceptionpassword);
				}
				$messagekey = "Krea2FactorAuthExceptionPasswordGeneratedPersistent";
			} elseif ($action == 'save_user_google_emails') {
				$googleemailsraw = GETPOST('google_login_emails', 'restricthtml');
				$invalidgoogleemails = array();
				$normalizedgoogleemails = $manager->normalizeSubmittedGoogleLoginEmailList($googleemailsraw, $invalidgoogleemails);
				if (count($invalidgoogleemails)) {
					$result = -1;
					$manager->error = $langs->trans("Krea2FactorAuthGoogleEmailsInvalid", implode(', ', $invalidgoogleemails));
					$manager->errors[] = $manager->error;
				} elseif ($normalizedgoogleemails === '') {
					$result = -1;
					$manager->error = $langs->trans("Krea2FactorAuthGoogleEmailsRequired");
					$manager->errors[] = $manager->error;
				} elseif (dol_strlen($normalizedgoogleemails) > 255) {
					$result = -1;
					$manager->error = $langs->trans("Krea2FactorAuthGoogleEmailsTooLong");
					$manager->errors[] = $manager->error;
				} else {
					$result = $manager->setGoogleLoginEmailsForUser($targetuser, $user, $normalizedgoogleemails);
				}
				$messagekey = "Krea2FactorAuthGoogleEmailsSaved";
			} elseif ($action == 'clear_user_google_emails') {
				$result = $manager->clearGoogleLoginEmailsForUser($targetuser, $user);
				$messagekey = "Krea2FactorAuthGoogleEmailsCleared";
			} elseif ($action == 'enable_user_password_expiration') {
				$result = $manager->enablePasswordExpirationForUser($targetuser, $user);
				$messagekey = "Krea2FactorAuthPasswordExpirationEnabled";
			} elseif ($action == 'disable_user_password_expiration') {
				if ($manager->isMandatoryExemptForUser((int) $targetuser->id)) {
					$result = -1;
					$manager->error = $langs->trans("Krea2FactorAuthPasswordExpirationDisableBlockedExempt", $targetuser->login);
					$manager->errors[] = $manager->error;
					$messagekey = "Krea2FactorAuthPasswordExpirationDisabled";
				} elseif ($manager->needsTotpFallbackAfterPasswordExpirationDisabled((int) $targetuser->id)) {
					$result = $manager->requireEnrollmentForUser($targetuser, $user);
					$messagekey = "Krea2FactorAuthPasswordExpirationKeptTotpRequired";
				} elseif (!$manager->canDisablePasswordExpirationForUser((int) $targetuser->id)) {
					$result = -1;
					$messagekey = "Krea2FactorAuthPasswordExpirationDisabled";
					$blockedmessagekey = (!$manager->isTotpEnrollmentAvailable() && $manager->isGoogleTwoFactorAvailable()) ? "Krea2FactorAuthPasswordExpirationDisableBlockedGoogleRequired" : "Krea2FactorAuthPasswordExpirationDisableBlockedSecondFactorRequired";
					$manager->error = $langs->trans($blockedmessagekey, $targetuser->login);
					$manager->errors[] = $manager->error;
				} else {
					$usesgooglefallback = ((!$manager->isTotpEnrollmentAvailable() || !$manager->isEnabledForUser((int) $targetuser->id)) && $manager->isGoogleTwoFactorConfiguredForUser((int) $targetuser->id));
					$result = $manager->disablePasswordExpirationForUser($targetuser, $user);
					$messagekey = ($usesgooglefallback ? "Krea2FactorAuthPasswordExpirationDisabledGoogleRequired" : "Krea2FactorAuthPasswordExpirationDisabled");
				}
			} else {
				$result = $manager->exemptUserWithGeneratedPassword($targetuser, $user, $generatedexceptionpassword, $passwordrotated);
				$messagekey = "Krea2FactorAuthUserExemptedWithPersistentPassword";
			}

			if ($result < 0) {
				$error++;
				setEventMessages($manager->error, $manager->errors, 'errors');
				if ($passwordrotated && $generatedexceptionpassword !== '') {
					krea2factorauthSetExceptionPasswordWarning($targetuser->login, $generatedexceptionpassword, true);
				}
			} else {
				if ($generatedexceptionpassword !== '') {
					krea2factorauthSetExceptionPasswordWarning($targetuser->login, $generatedexceptionpassword);
				} else {
					setEventMessages($langs->trans($messagekey, $targetuser->login), null, 'mesgs');
				}
			}
		}
	}
}

/*
 * View
 */

$help_url = '';
$title = "Krea2FactorAuthManagedUsers";
$morejs = array(dol_buildpath('/krea2factorauth/js/krea2factorauth.js', 1));
$cssversion = (string) @filemtime(dirname(__DIR__).'/css/krea2factorauth.css');
$morecss = array(dol_buildpath('/krea2factorauth/css/krea2factorauth.css', 1).($cssversion !== '' ? '?v='.$cssversion : ''));

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, $morejs, $morecss, '', 'mod-krea2factorauth page-admin');

$linkback = '<a href="'.DOL_URL_ROOT.'/user/home.php?mainmenu=home&leftmenu=users">'.$langs->trans("MenuUsersAndGroups").'</a>';
print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

$showonlyaccessmanagementtab = (GETPOST('mainmenu', 'aZ09') === 'home' && GETPOST('leftmenu', 'aZ09') === 'users');
$head = krea2factorauthAdminPrepareHead($showonlyaccessmanagementtab ? 'users' : '');
print dol_get_fiche_head($head, 'users', $langs->trans($title), -1, "krea2factorauth@krea2factorauth");

if ($issuperadmin) {
	$listparam = '&mainmenu=home&leftmenu=users';
	$listparamvalues = array(
		'search_login' => $searchlogin,
		'search_name' => $searchname,
		'search_status' => $searchstatus,
	);
	if ($showtotp2fa) {
		$listparamvalues['search_policy'] = $searchpolicy;
	}
	if ($showgoogle2fa) {
		$listparamvalues['search_google'] = $searchgoogle;
	}
	$listparamvalues['limit'] = (string) $limit;
	foreach ($listparamvalues as $key => $value) {
		if ($value !== '') {
			$listparam .= '&'.$key.'='.urlencode($value);
		}
	}
	$pageurlwithliststate = $pageurl.'?'.ltrim($listparam, '&');

	$statusfilterlabels = array(
		'' => $langs->trans("All"),
		'enabled' => $langs->trans("Enabled"),
		'disabled' => $langs->trans("Disabled"),
	);
	$policyfilterlabels = array(
		'' => $langs->trans("All"),
		'active' => $langs->trans("Krea2FactorAuthPolicyEnabled"),
		'mandatory' => $langs->trans("Krea2FactorAuthPolicyMandatory"),
		'exempt' => $langs->trans("Krea2FactorAuthPolicyExempt"),
		'optional' => $langs->trans("Krea2FactorAuthPolicyOptional"),
		'locked' => $langs->trans("Krea2FactorAuthPolicyLocked"),
		'unenrolled' => $langs->trans("Krea2FactorAuthPolicyUnenrolled"),
	);
	if ($showgoogle2fa) {
		$googlefilterlabels = array(
			'' => $langs->trans("All"),
			'configured' => $langs->trans("Krea2FactorAuthGoogleConfigured"),
			'not_configured' => $langs->trans("Krea2FactorAuthGoogleEmailMissing"),
		);
	}

	$nbtotalofrecords = $manager->countPolicyUsersInCurrentEntity($policyfilters);
	$offset = $limit * $page;
	if ($offset >= $nbtotalofrecords && $nbtotalofrecords > 0) {
		$page = 0;
		$offset = 0;
	}
	$policyusers = $manager->fetchPolicyUsersInCurrentEntity($limit + 1, $offset, $policyfilters, $sortfield, $sortorder);
	$num = count($policyusers);
	if ($num > $limit) {
		$policyusers = array_slice($policyusers, 0, $limit);
	}

	$rowactionliststateinputs = '';
	$rowactionstatevalues = array(
		'sortfield' => $sortfield,
		'sortorder' => $sortorder,
		'page' => (string) $page,
		'limit' => (string) $limit,
		'search_login' => $searchlogin,
		'search_name' => $searchname,
		'search_status' => $searchstatus,
	);
	if ($showtotp2fa) {
		$rowactionstatevalues['search_policy'] = $searchpolicy;
	}
	if ($showgoogle2fa) {
		$rowactionstatevalues['search_google'] = $searchgoogle;
	}
	foreach ($rowactionstatevalues as $statekey => $statevalue) {
		$rowactionliststateinputs .= '<input type="hidden" name="'.dol_escape_htmltag($statekey).'" value="'.dol_escape_htmltag($statevalue).'">';
	}
	$rowactionliststateinputs .= '<input type="hidden" name="page_y" value="">';

	$navigationformid = 'krea2factorauthManagedUsersNavigationForm';
	print '<form method="GET" id="'.$navigationformid.'" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'">';
	print '<input type="hidden" name="mainmenu" value="home">';
	print '<input type="hidden" name="leftmenu" value="users">';
	print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
	print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';
	print '<input type="hidden" name="search_login" value="'.dol_escape_htmltag($searchlogin).'">';
	print '<input type="hidden" name="search_name" value="'.dol_escape_htmltag($searchname).'">';
	print '<input type="hidden" name="search_status" value="'.dol_escape_htmltag($searchstatus).'">';
	if ($showtotp2fa) {
		print '<input type="hidden" name="search_policy" value="'.dol_escape_htmltag($searchpolicy).'">';
	}
	if ($showgoogle2fa) {
		print '<input type="hidden" name="search_google" value="'.dol_escape_htmltag($searchgoogle).'">';
	}
	print '<input type="hidden" name="page_y" value="">';
	print_barre_liste($langs->trans("Krea2FactorAuthManagedUsers"), $page, $_SERVER["PHP_SELF"], $listparam, $sortfield, $sortorder, '', $num, $nbtotalofrecords, '', 0, '', '', $limit, 0, 0, 1);
	print '</form>';

	$filterformid = 'krea2factorauthManagedUsersFilterForm';
	print '<form method="POST" id="'.$filterformid.'" action="'.dol_escape_htmltag($pageurlwithmenu).'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
	print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';
	print '<input type="hidden" name="page" value="'.((int) $page).'">';
	print '<input type="hidden" name="limit" value="'.((int) $limit).'">';
	print '<input type="hidden" name="page_y" value="">';
	print '</form>';
	print '<div class="div-table-responsive">';
	print '<table class="tagtable nobottomiftotal liste krea2factorauth-managed-users-table">'."\n";
	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre center maxwidthsearch">';
	print '<div class="nowraponall">';
	print '<button type="submit" form="'.$filterformid.'" class="liste_titre button_search reposition" name="button_search_x" value="x" title="'.dol_escape_htmltag($langs->trans("Search")).'"><span class="fas fa-search"></span></button>';
	print '<button type="submit" form="'.$filterformid.'" class="liste_titre button_removefilter reposition" name="button_removefilter_x" value="x" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'"><span class="fas fa-times"></span></button>';
	print '</div>';
	print '</td>';
	print '<td class="liste_titre left"><input class="flat maxwidth100" type="text" form="'.$filterformid.'" name="search_login" value="'.dol_escape_htmltag($searchlogin).'"></td>';
	print '<td class="liste_titre left"><input class="flat maxwidth150" type="text" form="'.$filterformid.'" name="search_name" value="'.dol_escape_htmltag($searchname).'"></td>';
	print '<td class="liste_titre center">';
	print '<select class="flat maxwidth100" form="'.$filterformid.'" name="search_status">';
	foreach ($statusfilterlabels as $filtervalue => $filterlabel) {
		print '<option value="'.dol_escape_htmltag($filtervalue).'"'.($searchstatus === $filtervalue ? ' selected' : '').'>'.dol_escape_htmltag($filterlabel).'</option>';
	}
	print '</select>';
	print '</td>';
	if ($showtotp2fa) {
		print '<td class="liste_titre center">';
		print '<select class="flat maxwidth125" form="'.$filterformid.'" name="search_policy">';
		foreach ($policyfilterlabels as $filtervalue => $filterlabel) {
			print '<option value="'.dol_escape_htmltag($filtervalue).'"'.($searchpolicy === $filtervalue ? ' selected' : '').'>'.dol_escape_htmltag($filterlabel).'</option>';
		}
		print '</select>';
		print '</td>';
	}
	if ($showgoogle2fa) {
		print '<td class="liste_titre center">';
		print '<select class="flat maxwidth125" form="'.$filterformid.'" name="search_google">';
		foreach ($googlefilterlabels as $filtervalue => $filterlabel) {
			print '<option value="'.dol_escape_htmltag($filtervalue).'"'.($searchgoogle === $filtervalue ? ' selected' : '').'>'.dol_escape_htmltag($filterlabel).'</option>';
		}
		print '</select>';
		print '</td>';
	}
	print '<td class="liste_titre center">&nbsp;</td>';
	print '<td class="liste_titre right">&nbsp;</td>';
	print '</tr>';
	print '<tr class="liste_titre">';
	print '<th class="liste_titre center maxwidthsearch">&nbsp;</th>';
	print_liste_field_titre("Login", $_SERVER["PHP_SELF"], "u.login", "", $listparam, "", $sortfield, $sortorder);
	print_liste_field_titre("Name", $_SERVER["PHP_SELF"], "u.lastname,u.firstname,u.login", "", $listparam, "", $sortfield, $sortorder);
	print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "u.statut", "", $listparam, "", $sortfield, $sortorder, 'center ');
	if ($showtotp2fa) {
		print_liste_field_titre("Krea2FactorAuthTotp2FA", $_SERVER["PHP_SELF"], "totp2fa", "", $listparam, "", $sortfield, $sortorder, 'center ');
	}
	if ($showgoogle2fa) {
		print_liste_field_titre("Krea2FactorAuthGoogle2FA", $_SERVER["PHP_SELF"], "google2fa", "", $listparam, "", $sortfield, $sortorder, 'center ');
	}
	print_liste_field_titre("Krea2FactorAuthPasswordExpiration", $_SERVER["PHP_SELF"], "password_expiration", "", $listparam, "", $sortfield, $sortorder, 'center ');
	print_liste_field_titre("Actions", $_SERVER["PHP_SELF"], "", "", $listparam, "", $sortfield, $sortorder, 'right ');
	print '</tr>';
	if (count($policyusers)) {
		foreach ($policyusers as $policyuser) {
			if (!empty($policyuser['enabled'])) {
				$totpclass = 'enabled';
				$totplabel = $langs->trans("Krea2FactorAuthPolicyEnabled");
			} elseif (!empty($policyuser['exempt'])) {
				$totpclass = 'exempt';
				$totplabel = $langs->trans("Krea2FactorAuthPolicyExempt");
			} elseif (!empty($policyuser['mandatory'])) {
				$totpclass = 'mandatory';
				$totplabel = $langs->trans("Krea2FactorAuthPolicyMandatory");
			} else {
				$totpclass = 'optional';
				$totplabel = $langs->trans("Krea2FactorAuthPolicyOptional");
			}
			$userstatusclass = !empty($policyuser['statut']) ? 'enabled' : 'disabled';
			$userstatuslabel = !empty($policyuser['statut']) ? $langs->trans("Enabled") : $langs->trans("Disabled");
			$haslocaltotpfactor = ($showtotp2fa && !empty($policyuser['enabled']));

			$setupurl = krea2factorauthGetUserCardPanelUrl((int) $policyuser['rowid']);
			print '<tr class="oddeven">';
			print '<td class="center">&nbsp;</td>';
			print '<td><a href="'.dol_escape_htmltag($setupurl).'" target="_blank" rel="noopener noreferrer">'.dol_escape_htmltag($policyuser['login']).'</a></td>';
			print '<td>'.($policyuser['name'] !== '' ? dol_escape_htmltag($policyuser['name']) : '&nbsp;').'</td>';
			print '<td class="center"><span class="krea2factorauth-user-state-pill krea2factorauth-user-state-pill-'.$userstatusclass.'">'.dol_escape_htmltag($userstatuslabel).'</span></td>';
			if ($showtotp2fa) {
				print '<td class="center"><span class="krea2factorauth-policy-pill krea2factorauth-policy-pill-'.$totpclass.'">'.dol_escape_htmltag($totplabel).'</span></td>';
			}
			if ($showgoogle2fa) {
				$googleismandatory = (!empty($policyuser['google']) && !$haslocaltotpfactor && empty($policyuser['exempt']) && empty($policyuser['password_expiration_enabled']));
				$googlelabelkey = "Krea2FactorAuthGoogleEmailMissing";
				if (!empty($policyuser['google'])) {
					$googlelabelkey = $googleismandatory ? "Krea2FactorAuthGoogleMandatory" : "Krea2FactorAuthGoogleConfigured";
				}
				$googlelabel = $langs->trans($googlelabelkey);
				$googleclass = !empty($policyuser['google']) ? ($googleismandatory ? 'mandatory' : 'enabled') : 'optional';
				$googletitle = !empty($policyuser['google_email']) ? $policyuser['google_email'] : '';
				print '<td class="center"><span class="krea2factorauth-policy-pill krea2factorauth-policy-pill-'.$googleclass.'"'.($googletitle !== '' ? ' title="'.dol_escape_htmltag($googletitle).'"' : '').'>'.dol_escape_htmltag($googlelabel).'</span></td>';
			}
			$passwordexpirationtitle = '';
			$passwordexpirationdetail = '';
			if (!empty($policyuser['password_expiration_enabled'])) {
				if (!empty($policyuser['password_expired'])) {
					$passwordexpirationlabel = $langs->trans("Krea2FactorAuthPasswordExpired");
					$passwordexpirationclass = 'locked';
					$passwordexpirationdetail = !empty($policyuser['password_expires_at']) ? $langs->trans("Krea2FactorAuthPasswordExpiredSince", dol_print_date((int) $policyuser['password_expires_at'], 'dayhour')) : $passwordexpirationlabel;
				} elseif (!empty($policyuser['password_expires_at'])) {
					$passwordexpirationlabel = $langs->trans("Krea2FactorAuthPasswordExpiresShort", dol_print_date((int) $policyuser['password_expires_at'], 'day'));
					$passwordexpirationclass = 'enabled';
					$passwordexpirationdetail = $langs->trans("Krea2FactorAuthPasswordExpiresAt", dol_print_date((int) $policyuser['password_expires_at'], 'dayhour'));
				} else {
					$passwordexpirationlabel = $langs->trans("Krea2FactorAuthPasswordExpirationActive");
					$passwordexpirationclass = 'enabled';
					$passwordexpirationdetail = $passwordexpirationlabel;
				}
			} else {
				$passwordexpirationlabel = $langs->trans("Krea2FactorAuthPasswordExpirationOff");
				$passwordexpirationclass = 'optional';
				$passwordexpirationdetail = $passwordexpirationlabel;
			}
			if (!empty($policyuser['locked'])) {
				$passwordexpirationlabel = $langs->trans("Krea2FactorAuthPolicyLocked");
				$passwordexpirationclass = 'locked';
				$passwordexpirationdetail = '';
			}
			$passwordexpirationtitle = $passwordexpirationdetail;
			print '<td class="center"><span class="krea2factorauth-policy-pill krea2factorauth-policy-pill-'.$passwordexpirationclass.'"'.($passwordexpirationtitle !== '' ? ' title="'.dol_escape_htmltag($passwordexpirationtitle).'"' : '').'>'.dol_escape_htmltag($passwordexpirationlabel).'</span></td>';
			print '<td class="right">';
			$actiongridclass = 'krea2factorauth-actions-grid';
			if (!$showgoogle2fa) {
				$actiongridclass .= ' krea2factorauth-actions-grid-without-google';
			}
			if (!$showtotp2fa) {
				$actiongridclass .= ' krea2factorauth-actions-grid-without-totp';
			}
			print '<div class="'.dol_escape_htmltag($actiongridclass).'">';
			$inactiveactionbutton = '<button type="button" class="krea2factorauth-icon-action krea2factorauth-icon-action-inactive" disabled aria-hidden="true" tabindex="-1"></button>';
			if ($showgoogle2fa) {
				$googleactionformid = 'krea2factorauth-google-emails-'.((int) $policyuser['rowid']);
				print '<span class="krea2factorauth-action-slot">';
				if (!empty($policyuser['google_email'])) {
					print '<form id="'.$googleactionformid.'" class="krea2factorauth-inline-action" method="post" action="'.dol_escape_htmltag($pageurlwithliststate).'" data-krea2factorauth-reset-confirm="1" data-confirm-title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthClearGoogleEmailsConfirmTitle")).'" data-confirm-text="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthClearGoogleEmailsConfirmText")).'" data-confirm-action="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthClearGoogleEmailsConfirmAction")).'" data-confirm-cancel="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="action" value="clear_user_google_emails">';
					print '<input type="hidden" name="target_user_id" value="'.((int) $policyuser['rowid']).'">';
					print $rowactionliststateinputs;
					print '<button type="submit" class="krea2factorauth-icon-action krea2factorauth-icon-action-danger reposition" title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionClearGoogleEmails")).'" aria-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionClearGoogleEmails")).'"><span class="fab fa-google"></span></button>';
					print '</form>';
				} else {
					print '<form id="'.$googleactionformid.'" class="krea2factorauth-inline-action" method="post" action="'.dol_escape_htmltag($pageurlwithliststate).'" data-krea2factorauth-google-email-form="1" data-modal-title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGoogleEmailsModalTitle")).'" data-modal-text="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGoogleEmailsModalText")).'" data-modal-placeholder="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGoogleEmailsModalPlaceholder")).'" data-modal-action="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGoogleEmailsModalAction")).'" data-modal-cancel="'.dol_escape_htmltag($langs->trans("Cancel")).'" data-modal-invalid="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGoogleEmailsModalInvalid")).'">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="action" value="save_user_google_emails">';
					print '<input type="hidden" name="target_user_id" value="'.((int) $policyuser['rowid']).'">';
					print $rowactionliststateinputs;
					print '<input type="hidden" name="google_login_emails" value="">';
					print '<button type="button" class="krea2factorauth-icon-action reposition" title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionSetGoogleEmails")).'" aria-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionSetGoogleEmails")).'"><span class="fab fa-google"></span></button>';
					print '</form>';
				}
				print '</span>';
			}
			print '<span class="krea2factorauth-action-slot">';
			if (!empty($policyuser['password_expiration_enabled'])) {
				$disableexpirationconfirmtext = $langs->trans("Krea2FactorAuthDisablePasswordExpirationConfirmText");
				$disableexpirationconfirmaction = $langs->trans("Krea2FactorAuthDisablePasswordExpirationConfirmAction");
				$disableexpirationconfirmsubmit = '1';
				if (!empty($policyuser['exempt'])) {
					$disableexpirationconfirmtext = $langs->trans("Krea2FactorAuthDisablePasswordExpirationExemptConfirmText");
					$disableexpirationconfirmaction = $langs->trans("Krea2FactorAuthDisablePasswordExpirationKeepAction");
				} elseif (!$haslocaltotpfactor) {
					if (!empty($policyuser['google'])) {
						$disableexpirationconfirmtext = $langs->trans("Krea2FactorAuthDisablePasswordExpirationGoogleConfirmText");
					} elseif (!$showtotp2fa) {
						$disableexpirationconfirmtext = $langs->trans($showgoogle2fa ? "Krea2FactorAuthDisablePasswordExpirationGoogleRequiredConfirmText" : "Krea2FactorAuthDisablePasswordExpirationSecondFactorRequiredConfirmText");
						$disableexpirationconfirmaction = $langs->trans("Krea2FactorAuthDisablePasswordExpirationKeepAction");
						$disableexpirationconfirmsubmit = '0';
					} else {
						$disableexpirationconfirmtext = $langs->trans("Krea2FactorAuthDisablePasswordExpirationRequireTotpConfirmText");
						$disableexpirationconfirmaction = $langs->trans("Krea2FactorAuthDisablePasswordExpirationRequireTotpConfirmAction");
					}
				}
				print '<form class="krea2factorauth-inline-action" method="post" action="'.dol_escape_htmltag($pageurlwithliststate).'" data-krea2factorauth-reset-confirm="1" data-confirm-title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthDisablePasswordExpirationConfirmTitle")).'" data-confirm-text="'.dol_escape_htmltag($disableexpirationconfirmtext).'" data-confirm-action="'.dol_escape_htmltag($disableexpirationconfirmaction).'" data-confirm-cancel="'.dol_escape_htmltag($langs->trans("Cancel")).'" data-confirm-submit="'.dol_escape_htmltag($disableexpirationconfirmsubmit).'">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="disable_user_password_expiration">';
				print '<input type="hidden" name="target_user_id" value="'.((int) $policyuser['rowid']).'">';
				print $rowactionliststateinputs;
				print '<button type="submit" class="krea2factorauth-icon-action krea2factorauth-icon-action-danger reposition" title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionDisablePasswordExpiration")).'" aria-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionDisablePasswordExpiration")).'"><span class="fas fa-hourglass-end"></span></button>';
				print '</form>';
			} else {
				print '<form class="krea2factorauth-inline-action" method="post" action="'.dol_escape_htmltag($pageurlwithliststate).'" data-krea2factorauth-reset-confirm="1" data-confirm-title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthEnablePasswordExpirationConfirmTitle")).'" data-confirm-text="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthEnablePasswordExpirationConfirmText", $manager->getPasswordExpirationMaxDays(), $manager->getPasswordHistoryLimit())).'" data-confirm-action="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthEnablePasswordExpirationConfirmAction")).'" data-confirm-cancel="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="enable_user_password_expiration">';
				print '<input type="hidden" name="target_user_id" value="'.((int) $policyuser['rowid']).'">';
				print $rowactionliststateinputs;
				print '<button type="submit" class="krea2factorauth-icon-action reposition" title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionEnablePasswordExpiration")).'" aria-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionEnablePasswordExpiration")).'"><span class="fas fa-hourglass-start"></span></button>';
				print '</form>';
			}
			print '</span>';
			if ($showtotp2fa) {
				print '<span class="krea2factorauth-action-slot">';
				print '<form class="krea2factorauth-inline-action" method="post" action="'.dol_escape_htmltag($pageurlwithliststate).'" data-krea2factorauth-reset-confirm="1" data-confirm-title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthResetConfirmTitle")).'" data-confirm-text="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthAdminResetConfirmText")).'" data-confirm-action="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthResetConfirmAction")).'" data-confirm-cancel="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="reset_user_2fa">';
				print '<input type="hidden" name="target_user_id" value="'.((int) $policyuser['rowid']).'">';
				print $rowactionliststateinputs;
				print '<button type="submit" class="krea2factorauth-icon-action reposition" title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionReset")).'" aria-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionReset")).'"><span class="fas fa-sync-alt"></span></button>';
				print '</form>';
				print '</span>';
			}
			print '<span class="krea2factorauth-action-slot">';
			if (!empty($policyuser['locked'])) {
				print '<form class="krea2factorauth-inline-action" method="post" action="'.dol_escape_htmltag($pageurlwithliststate).'">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="unlock_user_2fa">';
				print '<input type="hidden" name="target_user_id" value="'.((int) $policyuser['rowid']).'">';
				print $rowactionliststateinputs;
				print '<button type="submit" class="krea2factorauth-icon-action krea2factorauth-icon-action-danger reposition" title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionUnlock")).'" aria-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionUnlock")).'"><span class="fas fa-lock"></span></button>';
				print '</form>';
			} elseif ((int) $policyuser['rowid'] !== (int) $user->id) {
				print '<form class="krea2factorauth-inline-action" method="post" action="'.dol_escape_htmltag($pageurlwithliststate).'">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="lock_user_2fa">';
				print '<input type="hidden" name="target_user_id" value="'.((int) $policyuser['rowid']).'">';
				print $rowactionliststateinputs;
				print '<button type="submit" class="krea2factorauth-icon-action reposition" title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionLock")).'" aria-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionLock")).'"><span class="fas fa-unlock"></span></button>';
				print '</form>';
			} else {
				print $inactiveactionbutton;
			}
			print '</span>';
			if ($showtotp2fa) {
				print '<span class="krea2factorauth-action-slot">';
				if ((!$policyuser['enabled'] && empty($policyuser['exempt']) && empty($policyuser['mandatory'])) || !empty($policyuser['exempt'])) {
					print '<form class="krea2factorauth-inline-action" method="post" action="'.dol_escape_htmltag($pageurlwithliststate).'">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="action" value="require_user_2fa">';
					print '<input type="hidden" name="target_user_id" value="'.((int) $policyuser['rowid']).'">';
					print $rowactionliststateinputs;
					print '<button type="submit" class="krea2factorauth-icon-action reposition" title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionRequire")).'" aria-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionRequire")).'"><span class="fas fa-shield-alt"></span></button>';
					print '</form>';
				} else {
					print $inactiveactionbutton;
				}
				print '</span>';
			}
			if ($showtotp2fa) {
				print '<span class="krea2factorauth-action-slot">';
				if (empty($policyuser['exempt'])) {
					print '<form class="krea2factorauth-inline-action" method="post" action="'.dol_escape_htmltag($pageurlwithliststate).'" data-krea2factorauth-reset-confirm="1" data-confirm-title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthExemptConfirmTitle")).'" data-confirm-text="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthExemptConfirmText")).'" data-confirm-action="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthExemptConfirmAction")).'" data-confirm-cancel="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="action" value="exempt_user_2fa">';
					print '<input type="hidden" name="target_user_id" value="'.((int) $policyuser['rowid']).'">';
					print $rowactionliststateinputs;
					print '<button type="submit" class="krea2factorauth-icon-action reposition" title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionExempt")).'" aria-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionExempt")).'"><span class="fas fa-user-check"></span></button>';
					print '</form>';
				} else {
					print '<form class="krea2factorauth-inline-action" method="post" action="'.dol_escape_htmltag($pageurlwithliststate).'" data-krea2factorauth-reset-confirm="1" data-confirm-title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGenerateExceptionPasswordConfirmTitle")).'" data-confirm-text="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGenerateExceptionPasswordConfirmText")).'" data-confirm-action="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthGenerateExceptionPasswordConfirmAction")).'" data-confirm-cancel="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="action" value="generate_exception_password">';
					print '<input type="hidden" name="target_user_id" value="'.((int) $policyuser['rowid']).'">';
					print $rowactionliststateinputs;
					print '<button type="submit" class="krea2factorauth-icon-action reposition" title="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionGenerateExceptionPassword")).'" aria-label="'.dol_escape_htmltag($langs->trans("Krea2FactorAuthActionGenerateExceptionPassword")).'"><span class="fas fa-key"></span></button>';
					print '</form>';
				}
				print '</span>';
			}
			print '</div>';
			print '</td>';
			print '</tr>';
		}
	} else {
		$emptylistcolspan = 6 + ($showtotp2fa ? 1 : 0) + ($showgoogle2fa ? 1 : 0);
		print '<tr class="oddeven"><td colspan="'.((int) $emptylistcolspan).'" class="opacitymedium">'.$langs->trans("Krea2FactorAuthNoManagedUsers").'</td></tr>';
	}
	print '</table>';
	print '</div>';
} else {
	print '<div class="opacitymedium">'.$langs->trans("Krea2FactorAuthSuperAdminPolicyOnly").'</div>';
}

print dol_get_fiche_end();

llxFooter();
$db->close();
