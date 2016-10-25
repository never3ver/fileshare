<?php

class Helper {

    public static function generateTmpName() {
        $result = null;
        $source = str_split('abcdefghijklmnopqrstuvwxyz'
                . 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
                . '0123456789');
        for ($i = 0; $i < 45; $i++) {
//            $result .= $source[mt_rand(0, count($source) - 1)];
            $result .= $source[array_rand($source)];
        }
        return $result;
    }

    public static function getImagePath($tmpName) {
        return "../files/" . $tmpName;
    }

    public static function getFilePath($tmpName) {
        return "files/" . $tmpName;
    }
}
