<?php

class Helper {

    protected function generateTmpName($date) {
        $result = null;
        $source = str_split('abcdefghijklmnopqrstuvwxyz'
                . 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
                . '0123456789');
        for ($i = 0; $i < 45; $i++) {
            $result .= $source[array_rand($source)];
        }
        $result = $date . '/' . $result;
        return $result;
    }

    public function createTmpName(FileDataGateway $gateway) {
        $date = date('Y-m-d');
        $i = 0;
        $tmpName = $this->generateTmpName($date);
        while ($gateway->isTmpNameExisting($tmpName) && $i < 20) {
            $tmpName = $this->generateTmpName($date);
            $i++;
        }

        try {
            if ($gateway->isTmpNameExisting($tmpName)) {
                throw new Exception('Unable to create name of file');
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        $this->createDir($date);
        return $tmpName;
    }

    public function getFilePath($tmpName = '') {
        return dirname(__DIR__) . '/public/files/' . $tmpName;
    }

    public function getImagePreviewPath($tmpName) {
        return dirname(__DIR__) . '/public/files/preview/' . $tmpName;
    }

    public function getImagePreviewUrl($tmpName) {
        return '../files/preview/' . $tmpName;
    }

    public function getFilePageUrl($id) {
        return '/file/' . $id;
    }

    public function getFileUrl($id, $name) {
        return "/download/{$id}/{$name}";
    }

    protected function createDir($date) {
        if (!is_dir($this->getFilePath() . $date . '/')) {
            mkdir($this->getFilePath() . $date . '/');
        }
    }

}
