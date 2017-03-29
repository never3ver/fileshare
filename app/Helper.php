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

        if ($gateway->isTmpNameExisting($tmpName)) {
            throw new Exception('Unable to create name of file');
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

    public function downloadFile(\Slim\Http\Request $request, Slim\Http\Response $response, $path, File $file) {
        if (is_readable($path)) {
            if (in_array('mod_xsendfile', apache_get_modules())) {
                //download using xsendfile apache module:
                $response = $response->withHeader('X-SendFile', $path);
                $response = $response->withHeader('Content-Description', 'File Transfer');
                $response = $response->withHeader('Content-Disposition', 'attachment');
                return $response;
            } else {
                //universal way to download using php:
                $fh = fopen($path, 'rb');
                $stream = new \Slim\Http\Stream($fh); // create a new stream instance for the response body
                $response = $response->withHeader('Content-Type', 'application/octet-stream');
                $response = $response->withHeader('Content-Description', 'File Transfer');
                $response = $response->withHeader('Content-Disposition', 'attachment');
                $response = $response->withHeader('Content-Transfer-Encoding', 'binary');
                $response = $response->withHeader('Expires', '0');
                $response = $response->withHeader('Cache-Control', 'must-revalidate');
                $response = $response->withHeader('Pragma', 'public');
                $response = $response->withHeader('Content-Length', $file->getSize());
                $response = $response->withBody($stream);
                return $response;
            }
        } else {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }
    }

}
