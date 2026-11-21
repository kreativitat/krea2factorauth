<?php
/* Copyright (C) 2004-2018  Laurent Destailleur          <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI              <info@inovea-conseil.com>
 * Copyright (C) 2019-2024  Frédéric France              <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \defgroup   krea2factorauth Module Krea2FactorAuth
 * \brief      TOTP authentication module for Dolibarr.
 *
 * \file       htdocs/custom/krea2factorauth/core/modules/modKrea2FactorAuth.class.php
 * \ingroup    krea2factorauth
 * \brief      Description and activation file for module Krea2FactorAuth.
 */

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module Krea2FactorAuth.
 */
class modKrea2FactorAuth extends DolibarrModules
{
	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		$this->numero = 156566;
		$this->rights_class = 'krea2factorauth';
		$this->family = 'Kreativität Works';
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = 'ModuleKrea2FactorAuthDesc';
		$this->descriptionlong = 'ModuleKrea2FactorAuthDescLong';
		$this->editor_name = 'Kreativität Works';
		$this->editor_url = 'https://kreativitat.com';
		$this->editor_squarred_logo = '';
		$this->version = '1.29.20';
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->picto = 'fa-key';

		$this->module_parts = array(
			'triggers' => 1,
			'login' => 1,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 1,
			'barcode' => 0,
			'models' => 0,
			'printing' => 0,
			'theme' => 0,
			'css' => array(),
			'js' => array(),
			'hooks' => array('mainloginpage', 'login', 'main', 'usercard'),
			'moduleforexternal' => 0,
			'websitetemplates' => 0,
			'captcha' => 0,
		);

		$this->dirs = array('/krea2factorauth/temp');
		$this->config_page_url = array('setup.php@krea2factorauth');

		$this->hidden = getDolGlobalInt('MODULE_KREA2FACTORAUTH_DISABLED');
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array('krea2factorauth@krea2factorauth');
		$this->phpmin = array(7, 1);
		$this->need_dolibarr_version = array(19, -3);
		$this->need_javascript_ajax = 0;
		$this->warnings_activation = array();
		$this->warnings_activation_ext = array();

		$defaultissuer = (defined('DOL_APPLICATION_TITLE') ? DOL_APPLICATION_TITLE : 'Dolibarr');
		$this->const = array(
			array('KREA2FACTORAUTH_ISSUER', 'chaine', $defaultissuer, 'TOTP issuer name', 0, 'allentities', 0),
			array('KREA2FACTORAUTH_PRIMARY_AUTH_MODES', 'chaine', 'dolibarr', 'Primary authentication modes delegated by TOTP provider', 0, 'allentities', 0),
			array('KREA2FACTORAUTH_CODE_WINDOW', 'chaine', '1', 'TOTP accepted time-window discrepancy', 0, 'allentities', 0),
			array('KREA2FACTORAUTH_ENFORCE_NON_WEB', 'yesno', '0', 'Enforce TOTP for API, DAV and web service authentication contexts', 0, 'allentities', 0),
			array('KREA2FACTORAUTH_ENROLLMENT_PROMPT_MODE', 'chaine', 'optional', 'Post-login enrollment prompt mode: optional, mandatory or off', 0, 'allentities', 0),
			array('KREA2FACTORAUTH_SHOW_OPTIONAL_MODAL', 'yesno', '1', 'Legacy optional 2FA enrollment modal toggle', 0, 'allentities', 0),
			array('KREA2FACTORAUTH_ALLOW_SELF_RESET', 'yesno', '1', 'Allow users to reset their pending 2FA enrollment secret', 0, 'allentities', 0),
			array('KREA2FACTORAUTH_ACTIVATION_SCOPE', 'chaine', 'independent', 'Activation scope source of truth: global or independent', 0, 'allentities', 0),
			array('KREA2FACTORAUTH_LOGIN_BACKGROUND_COLOR', 'chaine', '#eef3f7', 'Default login page background color', 0, 'allentities', 0),
			array('KREA2FACTORAUTH_LOGIN_BACKGROUND_COLORS', 'chaine', '{}', 'Entity-specific login page background colors', 0, 'allentities', 0),
			array('KREA2FACTORAUTH_GOOGLE_LOGIN_ENABLED', 'yesno', '0', 'Enable Dolibarr-native Google OAuth as a primary login method gated by Krea2FactorAuth', 0, 'allentities', 0),
			array('KREA2FACTORAUTH_GOOGLE_LOGIN_ALLOWED_DOMAINS', 'chaine', '', 'Comma-separated Google email domains allowed to login through Krea2FactorAuth', 0, 'allentities', 0),
			array('KREA2FACTORAUTH_GOOGLE_LOGIN_USE_EXTRAFIELD_EMAIL', 'yesno', '0', 'Require Google OAuth login to match the Krea2FactorAuth user extrafield email', 0, 'allentities', 0),
			array('KREA2FACTORAUTH_PASSWORD_EXPIRATION_MAX_DAYS', 'chaine', '90', 'Maximum password age in days for users with password expiration enabled', 0, 'allentities', 0),
			array('KREA2FACTORAUTH_PASSWORD_HISTORY_LIMIT', 'chaine', '5', 'Number of recent password hashes retained to prevent reuse', 0, 'allentities', 0),
		);

		if (!isModEnabled('krea2factorauth')) {
			$conf->krea2factorauth = new stdClass();
			$conf->krea2factorauth->enabled = 0;
		}

		$this->tabs = array();
		$this->tabs[] = array(
			'data' => 'user:-krea2factorauth',
		);

		$this->dictionaries = array();
		$this->boxes = array();
		$this->cronjobs = array();

		$this->rights = array();

		$this->menu = array();
		$r = 0;
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=tools',
			'type' => 'left',
			'titre' => 'ModuleKrea2FactorAuthName',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth"'),
			'mainmenu' => 'tools',
			'leftmenu' => 'krea2factorauth',
			'url' => '/custom/krea2factorauth/user/2fa_setup.php?mainmenu=tools&leftmenu=krea2factorauth',
			'langs' => 'krea2factorauth@krea2factorauth',
			'position' => 550 + $r,
			'enabled' => 'isModEnabled("krea2factorauth")',
			'perms' => '1',
			'target' => '',
			'user' => 0,
		);
	}

	/**
	 * Function called when module is enabled.
	 *
	 * @param string $options Options when enabling module
	 * @return int<-1,1>      1 if OK, <=0 if KO
	 */
	public function init($options = '')
	{
		$this->delete_menus();
		$result = $this->_load_tables('/krea2factorauth/sql/');
		if ($result <= 0) {
			return -1;
		}

		require_once dirname(__DIR__, 2) . '/class/krea2factorauthmanager.class.php';

		$manager = new Krea2FactorAuthManager($this->db);
		$configmigration = $manager->migrateEntityScopedConfigToGlobal($manager->getModuleGlobalConfigTypes());
		if ($configmigration < 0) {
			$this->error = $manager->error;
			return -1;
		}
		if ($manager->deleteGlobalLoginLogoConstants() < 0) {
			$this->error = $manager->error;
			return -1;
		}

		$sql = array(
			"UPDATE " . MAIN_DB_PREFIX . "extrafields SET enabled = '0', list = '0' WHERE elementtype = 'user' AND name IN ('totp2fa_secret', 'totp2fa_Secret') AND entity IN (0, __ENTITY__)",
		);

		$result = $this->_init($sql, $options);
		if ($result > 0) {
			if ($manager->ensureGoogleLoginEmailExtrafield() < 0) {
				$this->error = $manager->error;
				return -1;
			}
			if ($manager->migrateEntityScopedUserParamsToGlobal() < 0) {
				$this->error = $manager->error;
				return -1;
			}
			if ($manager->syncGlobalLoginBootstrap() < 0) {
				$this->error = $manager->error;
				return -1;
			}
		}

		return $result;
	}

	/**
	 * Function called when module is disabled.
	 *
	 * @param string $options Options when disabling module
	 * @return int<-1,1>      1 if OK, <=0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}
}
