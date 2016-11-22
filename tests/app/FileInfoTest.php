<?php

require_once 'vendor/autoload.php';

class FileInfoTest extends PHPUnit_Framework_TestCase {

    public function testGetDataForTemplate() {
        $fileStub = $this->createMock(File::class);
        $fileStub->method('getSize')
                ->willReturn('555');
        $fileStub->method('isImage')
                ->willReturn('TRUE');
        $fileStub->method('getTmpName')
                ->willReturn('x8JCBqApY3a8qr60qt2M82HwGVQqgk05xitYlgMdG2NYj');

        $fileinfo = new FileInfo($fileStub);
        $this->assertInstanceOf('File', $fileStub);
        $this->assertArrayHasKey('file', $fileinfo->getDataForTemplate());
        $this->assertArrayHasKey('size', $fileinfo->getDataForTemplate());
        $this->assertArrayHasKey('imagePath', $fileinfo->getDataForTemplate());
    }

}
