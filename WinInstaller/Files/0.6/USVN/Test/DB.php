<?php
/**
 * Base class for test database
 *
 * @author Team USVN <contact@usvn.info>
 * @link http://www.usvn.info
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt CeCILL V2
 * @copyright Copyright 2007, Team USVN
 * @since 0.5
 * @package test
 * @subpackage db
 *
 * This software has been written at EPITECH <http://www.epitech.net>
 * EPITECH, European Institute of Technology, Paris - FRANCE -
 * This project has been realised as part of
 * end of studies project.
 *
 * $Id: DB.php 818 2007-06-18 23:15:57Z duponc_j $
 */
require_once "PHPUnit/Framework/TestCase.php";
require_once "PHPUnit/Framework/TestSuite.php";

require_once 'www/USVN/autoload.php';
require_once 'www/install/Install.php';

class USVN_Test_DB extends USVN_Test_Test {
	protected $db;

    protected function setUp() {
		parent::setUp();
		$params = array ('host'     => 'localhost',
			 'username' => 'usvn-test',
			 'password' => 'usvn-test',
			 'dbname'   => 'usvn-test');

		if (getenv('DB') == "PDO_SQLITE" || getenv('DB') === false) {
			$this->clean();
			Install::installDb('tests/db.ini', dirname(__FILE__) . '/../../SQL/', 'localhost', 'usvn-test', 'usvn-test', 'tests/usvn.db', 'usvn_', 'PDO_SQLITE', false);
			$params['dbname'] = "tests/usvn.db";
			$this->db = Zend_Db::factory('PDO_SQLITE', $params);
		}
		else {
			$this->db = Zend_Db::factory(getenv('DB'), $params);
			$this->clean();
			Install::installDb('tests/db.ini', dirname(__FILE__) . '/../../SQL/', 'localhost', 'usvn-test', 'usvn-test', 'usvn-test', 'usvn_', getenv('DB'), false);
		}
		Zend_Db_Table::setDefaultAdapter($this->db);
		USVN_Db_Table::$prefix = "usvn_";
    }

	private function clean()
	{
		if (getenv('DB') == "PDO_SQLITE" || getenv('DB') === false) {
			if (file_exists("usvn-test")) {
				@unlink("usvn-test");
			}
		}
		else {
			USVN_Db_Utils::deleteAllTables($this->db);
		}
	}

    protected function tearDown() {
        $this->clean();
        $this->db->closeConnection();
        $this->db = null;
		parent::tearDown();
    }
    
    public function __destruct() {
        if ($this->db != null) {
            $this->db->closeConnection();
        }
    }
}

?>