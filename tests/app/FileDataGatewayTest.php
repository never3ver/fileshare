<?php

require_once 'vendor/autoload.php';

class ConnectionTest extends PHPUnit_Extensions_Database_TestCase {

    protected static $pdo;
    protected $dataGateway;

    public static function setUpBeforeClass() {
        self::$pdo = new PDO('mysql:host=localhost;dbname=test_fileshare', 'root', '');
    }

    protected function setUp() {
        $this->dataGateway = new FileDataGateway(self::$pdo);
    }

    public function getConnection() {
        $database = 'test_fileshare';
        self::$pdo->exec('CREATE TABLE IF NOT EXISTS fileshare (id int(11),'
                . ' name varchar(45), tmpName varchar(45), size int(11),'
                . ' uploadTime text, type varchar(45))');
        return $this->createDefaultDBConnection(self::$pdo, $database);
    }

//    public function tearDown() {
//        self::$pdo->exec('DROP TABLE IF EXISTS fileshare');
//    }

    public function getDataSet() {
        return $this->createFlatXMLDataSet('myFlatXmlFixture.xml');
    }

    public function testGetRowCount() {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fileshare'));
    }

    public function testCountAllFiles() {
        $this->assertEquals($this->getConnection()->getRowCount('fileshare'), $this->dataGateway->countAllFiles());
    }

    public function testGetFile() {
        $id = 1;
        $this->assertInstanceOf(File::class, $this->dataGateway->getFile($id));
    }

}
