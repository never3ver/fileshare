<?php

function recurse_copy($source, $destination) {
    $dir = opendir($source);
    @mkdir($destination);
    while (false !== ( $file = readdir($dir))) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if (is_dir($source . '/' . $file)) {
                recurse_copy($source . '/' . $file, $destination . '/' . $file);
            } else {
                copy($source . '/' . $file, $destination . '/' . $file);
            }
        }
    }
    closedir($dir);
}

recurse_copy("vendor/twbs/bootstrap/dist/", "public/bootstrap/");
