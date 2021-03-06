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
                    try {
                        if (!copy($source . '/' . $file, $destination . '/' . $file)) {
                            throw new Exception("Failed to copy $file");
                        }
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }
                }
            }
        }
        closedir($dir);
    }
}

recurse_copy("vendor/twbs/bootstrap/dist/", "public/bootstrap/");
