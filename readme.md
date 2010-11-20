## CakePHP datasources plugin

This plugin contains various datasources contributed by the core CakePHP team and the community.
The datasources plugin is compatible with CakePHP 1.3+.

### Using the datasources plugin

First download the repository and place it in `app/plugins/datasources` or on one of your plugin paths. You can then import and use the datasources in your App classes.

### Model validation

Datasource plugin datasources can be used either through App::import of by defining them in your database configuration

	class DATABASE_CONFIG {
		var $mySource = array(
			'datasource' => 'Datasources.XmlrpcSource',
			...
			);
		}
	}

or

	App::import('Datasource', 'Datasources.XmlrpcSource');

or, if using one of the pdo extended datasources,

	class DATABASE_CONFIG {
		var $mySource = array(
			'driver' => 'Datasources.DboSqlite3',
			...
			);
		}
	}

## Contributing to datasources

If you have a datasource, or an idea for a datasource that could benefit the CakePHP community, please for the project on github. Once you have forked the project you can commit your datasource class (and any test cases). Once you have pushed your changes back to github you can send a pull request, and your changes will be reviewed and merged in or feedback will be given.

## Issues with datasources

If you have issues with the datasources plugin, you can report them at http://cakephp.lighthouseapp.com/projects/42657-datasources/overview
