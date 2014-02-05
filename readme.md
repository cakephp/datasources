## CakePHP datasources plugin - 2.0 dev branch

This plugin contains various datasources contributed by the core CakePHP team and the community.
The datasources plugin for CakePHP 2.0 is still in development. Refer to the following lists which Datasources are already fixed for the 2.0 branch.

### Already compatible Datasources:

* AmazonAssociatesSource
* ArraySource
* CsvSource
* XmlrpcSource
* Database/MysqlLog

### Still Incompatible Datasources:

* CouchdbSource
* LdapSource
* SoapSource
* Database/Adodb
* Database/Db2
* Database/Firebird
* Database/Odbc
* Database/Sqlite3
* Database/Sqlsrv
* Database/Sybase

### Using the datasources plugin

First download the repository and place it in `app/Plugin/Datasources` or on one of your plugin paths. You can then import and use the datasources in your App classes.

### Model validation

Datasource plugin datasources can be used either through App::uses of by defining them in your database configuration

	class DATABASE_CONFIG {
		public $mySource = array(
			'datasource' => 'Datasources.XmlrpcSource',
			...
			);
		}
	}

or

	App::uses('XmlrpcSource', 'Datasources.Model/Datasource');

or, if using one of the pdo extended datasources,

	class DATABASE_CONFIG {
		public $mySource = array(
			'driver' => 'Datasources.Database/Firebird',
			...
			);
		}
	}

## Contributing to datasources

If you have a datasource, or an idea for a datasource that could benefit the CakePHP community, please fork the project on github. Once you have forked the project you can commit your datasource class (and any test cases). Once you have pushed your changes back to github you can send a pull request, and your changes will be reviewed and merged in or feedback will be given.

## Issues with datasources

If you have issues with the datasources plugin, you can report them via [Github issues](https://github.com/cakephp/datasources/issues)
