<?php
/**
 * Class to install the application for the first time use.
 * 
 * @package core
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */
class Install {

	/**
	 * Run the installation process. It will check the database connection, install the whole database
	 * tables, store the default options, creates the configuration file with database access data, and
	 * create the admin user with the email and password given.
	 * 
	 * @param array $post Array of data (email, password, password_rep, host, name, user, pass)
	 * @return bool True if installation was completed successfully
	 * @throws Exception If error occurred while installation
	 */
	public static function run($post) {
		// Check valid password and email
		if (!($email = filter_var($post['usr']['email'], FILTER_VALIDATE_EMAIL)) ||
			!preg_match('/^(?=.*[0-9]+)(?=.*[A-Z]+)(?=.*[a-z]+).{6,16}$/', $post['usr']['password']) ||
			$post['usr']['password'] != $post['usr']['password_rep'])
			throw new Exception(Text::error('install_userpass'));

		// Try to connect to database
		$conn_str = 'mysql:host='.($post['db']['host']??'').';dbname='.($post['db']['name']??'');
		try {
			$conn = new PDO($conn_str, $post['db']['user']??'', $post['db']['pass']??'');
		} catch (PDOException $e) {
			throw new Exception(Text::error('database_connect'));
		}

		// Get install SQL script from file
		if (!($sql = file_get_contents(CONFIG_DIR.'install.sql')))
			throw new Exception(Text::error('install_getfile'));

		// Install tables and store default options
		try {
			$conn->exec($sql);
		} catch (PDOException $e) {
			throw new Exception(Text::error('install_tables'));			
		} finally {
			$conn = null;
		}

		// Try to save the config file config/database.json
		if (!file_put_contents(CONFIG_DIR.'database.json', json_encode($post['db'])))
			throw new Exception(Text::error('install_putfile'));

		// Try to save the default admin user
		User::create($email, 'admin', $post['usr']['password'], 0, 1, 1);

		return true;
	}

}