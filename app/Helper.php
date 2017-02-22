<?php

class Helper {

    public function generateTmpName() {
        $result = null;
        $source = str_split('abcdefghijklmnopqrstuvwxyz'
                . 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
                . '0123456789');
        for ($i = 0; $i < 45; $i++) {
            $result .= $source[array_rand($source)];
        }
        return $result;
    }

    public function getFilePath($tmpName) {
        return dirname(__DIR__) . '/public/files/' . $tmpName;
    }

    public function getImagePreviewPath($tmpName) {
        return dirname(__DIR__) . '/public/files/preview/' . $tmpName;
    }

    public function getImagePreviewUrl($tmpName) {
        return '../files/preview/' . $tmpName;
    }

    public function getFilePageUrl($id) {
        return 'file/' . $id;
    }

    public function getFileUrl($id, $name) {
        return "/download/{$id}/{$name}";
    }

}
