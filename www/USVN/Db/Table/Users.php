<?php
/**
 * Model for users table
 *
 * @author Team USVN <contact@usvn.info>
 * @link http://www.usvn.info
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt CeCILL V2
 * @copyright Copyright 2007, Team USVN
 * @since 0.5
 * @package USVN_Db
 * @subpackage Table
 *
 * This software has been written at EPITECH <http://www.epitech.net>
 * EPITECH, European Institute of Technology, Paris - FRANCE -
 * This project has been realised as part of
 * end of studies project.
 *
 * $Id$
 */

/**
 * Model for users table
 *
 * Extends USVN_Db_Table for magic configuration and methods
 *
 */
class USVN_Db_Table_Users extends USVN_Db_Table {
	/**
	 * The primary key column (underscore format).
	 *
	 * Without our prefixe...
	 *
	 * @var string
	 */
	protected $_primary = "users_id";

	/**
	 * The field's prefix for this table.
	 *
	 * @var string
	 */
	protected $_fieldPrefix = "users_";

	/**
	 * The table name derived from the class name (underscore format).
	 *
	 * @var array
	 */
	protected $_name = "users";

	/**
	 * Name of the Row object to instantiate when needed.
	 *
	 * @var string
	 */
	protected $_rowClass = "USVN_Db_Table_Row_User";

	/**
	 * Simple array of class names of tables that are "children" of the current
	 * table, in other words tables that contain a foreign key to this one.
	 * Array elements are not table names; they are class names of classes that
	 * extend Zend_Db_Table_Abstract.
	 *
	 * @var array
	 */
	protected $_dependentTables = array("USVN_Db_Table_UsersToGroups");

	/**
	 * Expected entries like anonymous user
	 *
	 * @var array
	 */
	public $exceptedEntries = array('users_login' => 'anonymous');

	/**
	 * Check if the login is valid or not
	 *
	 * @throws USVN_Exception
	 * @param string
	 * @todo check on the default's login ?
	 * @todo regexp on the login ?
	 */
	public function checkLogin($login)
	{
		if (empty($login) || preg_match('/^\s+$/', $login)) {
			throw new USVN_Exception(T_('Login empty.'));
		}
		if (!preg_match('/\w+/', $login)) {
			throw new USVN_Exception(T_('Login invalid.'));
		}
	}

	/**
	 * Check if the password is valid or not
	 *
	 * @throws USVN_Exception
	 * @param string
	 */
	public function checkPassword($password)
	{
		if (empty($password) || preg_match('/^\s+$/', $password)) {
			throw new USVN_Exception(T_('Password empty.'));
		}
		if (strlen($password) < 8) {
			throw new USVN_Exception(T_('Invalid password (at least 8 Characters).'));
		}
	}

	/**
	 * Check if the Email address is valid or not
	 *
	 * @throws USVN_Exception
	 * @param string
	 */
	public function checkEmailAddress($email)
	{
		if (strlen($email)) {
			$validator = new Zend_Validate_EmailAddress();
			if (!$validator->isValid($email)) {
				throw new USVN_Exception(T_('Invalid email address.'));
			}
		}
	}

	/**
	 * Do all checks
	 *
	 * @param array $data informations about the user to save
	 */
	private function check($data)
	{
		$this->checkLogin($data['users_login']);
		$this->checkPassword($data['users_password']);
		if (isset($data['users_email'])) {
			$this->checkEmailAddress($data['users_email']);
		}
	}

	/**
	 * Inserts a new row
	 *
	 * @param array Column-value pairs.
	 * @return integer The last insert ID.
	 */
	public function insert(array $data)
	{
		$this->check($data);
		$res = parent::insert($data);
		$this->updateHtpasswd();
		return $res;
	}

	/**
	 * Delete existing rows.
	 *
	 * @param string An SQL WHERE clause.
	 * @return the number of rows deleted.
	 */
	public function delete($where)
	{
		$res = parent::delete($where);
		$this->updateHtpasswd();
		return $res;
	}

	/**
	 * Updates existing rows.
	 *
	 * @param array Column-value pairs.
	 * @param string An SQL WHERE clause.
	 * @return int The number of rows updated.
	 */
	public function update(array $data, $where)
	{
		if (isset($data['users_login'])) {
			$this->checkLogin($data['users_login']);
		}
		if (isset($data['users_password'])) {
			$this->checkPassword($data['users_password']);
		}
		if (isset($data['users_email'])) {
			$this->checkEmailAddress($data['users_email']);
		}
		$res = parent::update($data, $where);
		$this->updateHtpasswd();
		return $res;
	}

	/**
	 * Update Htpasswd file after an insert, an delete or an update
	 */
	public function updateHtpasswd()
	{
		$text = null;
		foreach ($this->fetchAll(null, "users_login") as $user) {
			$text .= "{$user->login}:{$user->password}\n";
		}
		$config = Zend_Registry::get('config');
		if (@file_put_contents($config->subversion->path . "htpasswd", $text) === false) {
			throw new USVN_Exception(T_('Can\'t create or write on htpasswd file.'));
		}
	}

	/**
	 * To know if the user already exists or not
	 *
	 * @param string
	 * @return boolean
	 */
	public function isAUser($login)
	{
		$user = $this->fetchRow(array('users_login = ?' => $login));
		if ($user === NULL) {
			return false;
		}
		return true;
	}
}
