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
 * \file    krea2factorauth/core/triggers/interface_99_modKrea2FactorAuth_PasswordHistory.class.php
 * \ingroup krea2factorauth
 * \brief   Record password history after Dolibarr password changes.
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once dirname(__DIR__, 2).'/class/krea2factorauthmanager.class.php';

/**
 * Trigger handler for password history.
 */
class InterfaceKrea2FactorAuthPasswordHistory extends DolibarrTriggers
{
	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		parent::__construct($db);

		$this->family = 'krea2factorauth';
		$this->description = 'Records password history for Krea2FactorAuth password expiration policy.';
		$this->version = self::VERSIONS['prod'];
		$this->picto = 'technic';
	}

	/**
	 * Run trigger.
	 *
	 * @param string    $action Event action code
	 * @param object    $object Business object
	 * @param User      $user   Current user
	 * @param Translate $langs  Translation handler
	 * @param Conf      $conf   Configuration object
	 * @return int              <0 on error, 0 if not run, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if ($action !== 'USER_NEW_PASSWORD') {
			return 0;
		}
		if (!is_object($object) || empty($object->id)) {
			return 0;
		}

		$manager = new Krea2FactorAuthManager($this->db);
		$entity = !empty($conf->entity) ? (int) $conf->entity : (isset($object->entity) ? (int) $object->entity : 1);
		if (!$manager->isModuleActiveForEntity($entity > 0 ? $entity : 1)) {
			return 0;
		}
		if (!$manager->isPasswordExpirationEnabledForUser((int) $object->id)) {
			return 0;
		}

		$result = $manager->recordCurrentPasswordHashForUser($object, $user, 'trigger', true);
		if ($result < 0) {
			$this->error = $manager->error;
			$this->errors = $manager->errors;
			dol_syslog('InterfaceKrea2FactorAuthPasswordHistory::runTrigger '.$this->error, LOG_ERR);
			return -1;
		}

		return 1;
	}
}
