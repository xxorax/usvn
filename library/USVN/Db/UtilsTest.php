<?php
/**
 * Provide usefull static methods to manipulate database.
 *
 * @author Team USVN <contact@usvn.info>
 * @link http://www.usvn.info
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt CeCILL V2
 * @copyright Copyright 2007, Team USVN
 * @since 0.5
 * @package db
 * @subpackage utils
 *
 * This software has been written at EPITECH <http://www.epitech.net>
 * EPITECH, European Institute of Technology, Paris - FRANCE -
 * This project has been realised as part of
 * end of studies project.
 *
 * $Id: UtilsTest.php 1458 2008-01-06 12:42:27Z duponc_j $
 */
// Call USVN_Db_UtilsTest::main() if this source file is executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
	define("PHPUnit_MAIN_METHOD", "USVN_Db_UtilsTest::main");
}

require_once "PHPUnit/Framework/TestCase.php";
require_once "PHPUnit/Framework/TestSuite.php";

require_once 'library/USVN/autoload.php';

/**
 * Test class for USVN_Db_Utils.
 * Generated by PHPUnit_Util_Skeleton on 2007-03-13 at 16:30:24.
 */
class USVN_Db_UtilsTest extends PHPUnit_Framework_TestCase {
	private $testfile = "tests/tmp/db.sql";

	/**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
	public static function main() {
		require_once "PHPUnit/TextUI/TestRunner.php";

		$suite  = new PHPUnit_Framework_TestSuite("USVN_Db_UtilsTest");
		$result = PHPUnit_TextUI_TestRunner::run($suite);
	}

	public function setUp() {
		$params = array ('host'     => 'localhost',
		'username' => 'usvn-test',
		'password' => 'usvn-test',
		'dbname'   => 'usvn-test');
		USVN_Translation::initTranslation('en_US', 'app/locale');

		if (getenv('DB') != "PDO_MYSQL") {
			$this->markTestSkipped("Test only with PDO_MYSQL");
		}
		$this->db = Zend_Db::factory('PDO_MYSQL', $params);
		Zend_Db_Table::setDefaultAdapter($this->db);
		USVN_Db_Utils::deleteAllTables($this->db);
		USVN_Db_Table::$prefix = "usvn_";
		file_put_contents($this->testfile,
		"
	/*==============================================================*/
	/* Table: users                                                 */
	/*==============================================================*/
	create table usvn_users
	(
	   users_id                       int                            not null,
	   users_login                    varchar(255)                   not null,
	   users_password                 varchar(44)                    not null,
	   users_nom                      varchar(100),
	   users_prenom                   varchar(100),
	   users_email                    varchar(150),
	   primary key (users_id)
	)
	type = innodb;

	/*==============================================================*/
	/* Table: groups                                                */
	/*==============================================================*/
	create table usvn_groups
	(
	   groups_id                      int                            not null,
	   groups_label                   varchar(100),
	   groups_nom                     varchar(150),
	   primary key (groups_id)
	)
	type = innodb;

	/*==============================================================*/
	/* Table: to_belong                                             */
	/*==============================================================*/
	create table usvn_to_belong
	(
	   users_id                       int                            not null,
	   groups_id                      int                            not null,
	   primary key (users_id, groups_id)
	)
	type = innodb;

	/*==============================================================*/
	/* Index: to_belong_fk                                          */
	/*==============================================================*/
	create index to_belong_fk on usvn_to_belong
	(
		users_id
	);

	/*==============================================================*/
	/* Index: to_belong2_fk                                         */
	/*==============================================================*/
	create index to_belong2_fk on usvn_to_belong
	(
		groups_id
	);

	alter table usvn_to_belong add constraint fk_usvn_to_belong foreign key (users_id)
	references usvn_users (users_id) on delete restrict on update restrict;

	alter table usvn_to_belong add constraint fk_usvn_to_belong2 foreign key (groups_id)
	references usvn_groups (groups_id) on delete restrict on update restrict;
	");
	}

	protected function tearDown() {
		USVN_Db_Utils::deleteAllTables($this->db);
		USVN_Db_Utils::deleteAllTables($this->db, 'fake_');
		unlink($this->testfile);
	}

	public function testLoadFile() {
		USVN_Db_Utils::loadFile($this->db, $this->testfile);
		$list_tables =  $this->db->listTables();
		$this->assertEquals(3, sizeof($list_tables));
		$this->assertTrue(in_array('usvn_users', $list_tables));
		$this->assertTrue(in_array('usvn_groups', $list_tables));
	}

	public function testLoadFileWithAnotherPrefix() {
		USVN_Db_Table::$prefix = "fake_";
		USVN_Db_Utils::loadFile($this->db, $this->testfile);
		$list_tables =  $this->db->listTables();
		$this->assertEquals(3, sizeof($list_tables));
		$this->assertTrue(in_array('fake_users', $list_tables));
		$this->assertTrue(in_array('fake_groups', $list_tables));
	}

	public function test_deleteAllTables()
	{
		USVN_Db_Utils::loadFile($this->db, $this->testfile);
		$this->assertEquals(3, sizeof($this->db->listTables()));
		USVN_Db_Utils::deleteAllTables($this->db);
		$this->assertEquals(0, sizeof($this->db->listTables()));
	}
}

// Call USVN_Db_UtilsTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "USVN_Db_UtilsTest::main") {
	USVN_Db_UtilsTest::main();
}
?>