<?php
/**
 * All Datasources plugin tests
 *
 * @package       Cake.Test.Case.Controller.Component.Auth
 */
class AllDatasourcesTest extends CakeTestCase {

/**
 * Suite define the tests for this suite
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('All Datasources test');

		$path = CakePlugin::path('Datasources') . 'Test' . DS . 'Case' . DS;
		$suite->addTestDirectoryRecursive($path);

		return $suite;
	}
}
