<?php

require_once 'vendor/autoload.php';

class HelperTest extends PHPUnit_Framework_TestCase {

    protected static $pdo;
    protected $dataGateway;

    public static function setUpBeforeClass() {
        self::$pdo = new PDO('mysql:host=localhost;dbname=test_fileshare', 'root', '');
    }

    protected function setUp() {
        $this->dataGateway = new FileDataGateway(self::$pdo);
    }

    public function testCreateTmpName() {
        $this->assertRegExp('/^[a-zA-Z0-9]{45}$/i', Helper::createTmpName($this->dataGateway));
    }

}
