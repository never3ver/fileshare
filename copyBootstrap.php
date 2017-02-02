<?php

function recurse_copy($source, $destination) {
    if (is_dir($source)) {
        $dir = opendir($source);
        @mkdir($destination);
        while (false !== ( $file = readdir($dir))) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if (is_dir($source . '/' . $file)) {
                    recurse_copy($source . '/' . $file, $destination . '/' . $file);
                } else {
                    if (!copy($source . '/' . $file, $destination . '/' . $file)) {
                        echo "failed to copy $file";
                    }
                }
            }
        }
        closedir($dir);
    }
}

recurse_copy("vendor/twbs/bootstrap/dist/", "public/bootstrap/");
