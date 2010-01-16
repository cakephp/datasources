<?php

class UserFixture extends CakeTestFixture {

	var $name = 'User';

	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'born_id' => array('type' => 'integer', 'null' => false),
		'name' => array('type' => 'string', 'null' => false)
	);

/**
 * records property
 *
 * @var array
 * @access public
 */
	var $records = array(
		array('born_id' => 1, 'name' => 'User 1'),
		array('born_id' => 2, 'name' => 'User 2'),
		array('born_id' => 1, 'name' => 'User 3'),
		array('born_id' => 3, 'name' => 'User 4')
	);
}
?>