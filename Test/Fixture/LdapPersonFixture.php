<?php
/**
 * LdapPerson Fixture
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP Datasources v 0.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * LdapPerson Fixture
 *
 */
class LdapPersonFixture extends CakeTestFixture {

	public $primaryKey = 'cn';

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('objectclass' => 'person', 'cn' => 'jane', 'sn' => 'doe'),
		array('objectclass' => 'person', 'cn' => 'john', 'sn' => 'doe'),
		array('objectclass' => 'person', 'cn' => 'nanashi ', 'sn' => 'nanashi'),
	);
}
