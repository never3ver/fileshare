<?php

class FileInfo {

    protected $file;
    protected $imageUrl;
    protected $filePath;
    protected $playtime;
    protected $bitrate;
    protected $metadata;
    protected $mimeType;
    protected $c;

    public function __construct(File $file, \Slim\Container $c) {
        $this->file = $file;
        $this->c = $c;
        $this->filePath = $c->helper->getFilePath($this->file->getTmpName());
        if ($file->isImage()) {
            $this->imageUrl = $c->helper->getFilePath($this->file->getTmpName());
        } elseif ($file->getMetadata() != '' && $this->file->isMedia()) {
            $metadata = json_decode($file->getMetadata(), true);
            $this->playtime = $metadata['playtime'];
            $this->bitrate = $metadata['bitrate'];
        } elseif ($file->isMedia() && $file->getMetadata() == '') {
            $this->createMetadata();
        }
    }

    protected function createMetadata() {
        $getID3 = new getID3();
        $properties = $getID3->analyze($this->filePath);
        $this->playtime = $properties['playtime_string'];
        $this->bitrate = round($properties['bitrate'] / 1024, 2);
        $metadata = ['playtime' => $this->playtime, 'bitrate' => $this->bitrate];
        $metadata = json_encode($metadata);
        $this->metadata = $metadata;
    }

    public function getPlaytime() {
        return $this->playtime;
    }

    public function getBitrate() {
        return $this->bitrate;
    }

    public function getImageUrl() {
        return $this->imageUrl;
    }

    public function getFilePath() {
        return $this->filePath;
    }

    public function getMetadata() {
        return $this->metadata;
    }

    public function getMimeType() {
        return $this->mimeType;
    }

    protected function makePreviewDir() {
        $datePath = date('Y-m-d') . '/';
        if (!is_dir($this->c->helper->getImagePreviewPath('') . $datePath)) {
            $tmpName = $datePath . $this->file->getTmpName();
            mkdir($this->c->helper->getImagePreviewPath('') . $datePath);
        }
    }

    public function getPreview() {
        if (is_readable($this->c->helper->getImagePreviewPath($this->file->getTmpName()))) {
            return $this->c->helper->getImagePreviewUrl($this->file->getTmpName());
        }
        $size = getimagesize($this->filePath);
        $width = $size[0];
        $height = $size[1];
        $previewWidth = 200;

        if ($width <= $previewWidth) {
            $previewWidth = $width;
        }

        $ratio = $width / $previewWidth;
        $previewHeight = round($height / $ratio);
        $preview = imagecreatetruecolor($previewWidth, $previewHeight);

        switch ($this->file->getType()) {
            case 'image/gif':
                $source = imagecreatefromgif($this->filePath);
                $transparentPreview = imagecolorallocatealpha($preview, 0, 0, 0, 127);
                imagefill($preview, 0, 0, $transparentPreview);
                imagealphablending($preview, FALSE);
                imagesavealpha($preview, TRUE);
                imagecopyresampled($preview, $source, 0, 0, 0, 0, $previewWidth, $previewHeight, $width, $height);
                $this->makePreviewDir();
                imagegif($preview, $this->c->helper->getImagePreviewPath($this->file->getTmpName()));
                return $this->c->helper->getImagePreviewUrl($this->file->getTmpName());
            case 'image/jpeg':
                $source = imagecreatefromjpeg($this->filePath);
                $preview = imagecreatetruecolor($previewWidth, $previewHeight);
                imagealphablending($preview, false);
                imagesavealpha($preview, true);
                imagecopyresampled($preview, $source, 0, 0, 0, 0, $previewWidth, $previewHeight, $width, $height);
                $this->makePreviewDir();
                imagejpeg($preview, $this->c->helper->getImagePreviewPath($this->file->getTmpName()));
                return $this->c->helper->getImagePreviewUrl($this->file->getTmpName());
            case 'image/png':
                $source = imagecreatefrompng($this->filePath);
                $transparentPreview = imagecolorallocatealpha($preview, 0, 0, 0, 127);
                imagefill($preview, 0, 0, $transparentPreview);
                imagealphablending($preview, FALSE);
                imagesavealpha($preview, TRUE);
                imagecopyresampled($preview, $source, 0, 0, 0, 0, $previewWidth, $previewHeight, $width, $height);
                $this->makePreviewDir();
                imagejpeg($preview, $this->c->helper->getImagePreviewPath($this->file->getTmpName()));
                return $this->c->helper->getImagePreviewUrl($this->file->getTmpName());
        }
    }

}
