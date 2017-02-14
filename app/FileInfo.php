<?php

class FileInfo {

    protected $file;
    protected $imageUrl;
    protected $filePath;
    protected $playtime;
    protected $bitrate;
    protected $json;

    public function __construct(File $file) {
        $this->file = $file;
        $this->filePath = Helper::getFilePath($this->file->getTmpName());
        if ($file->isImage()) {
            $this->imageUrl = Helper::getImageUrl($this->file->getTmpName());
        } elseif ($file->getJson() != '' && $this->file->isMedia()) {
            $json = json_decode($file->getJson(), true);
            $this->playtime = $json['playtime'];
            $this->bitrate = $json['bitrate'];
        } elseif ($file->isMedia() && $file->getJson() == '') {
            $this->createJson();
        }
    }

    public function createJson() {
        $getID3 = new getID3();
        $properties = $getID3->analyze($this->filePath);
        $this->playtime = $properties['playtime_string'];
        $this->bitrate = round($properties['bitrate'] / 1024, 2);
        $json = ['playtime' => $this->playtime, 'bitrate' => $this->bitrate];
        $json = json_encode($json);
        $this->json = $json;
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

    public function getJson() {
        return $this->json;
    }

    protected function makePreviewDir() {
        $datePath = date('Y-m-d') . '/';
        if (!is_dir(Helper::getImagePreviewPath('') . $datePath)) {
            $tmpName = $datePath . $this->file->getTmpName();
            mkdir(Helper::getImagePreviewPath('') . $datePath);
        }
    }

    public function getPreview() {
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
                imagegif($preview, Helper::getImagePreviewPath($this->file->getTmpName()));
                return Helper::getImagePreviewUrl($this->file->getTmpName());
            case 'image/jpeg':
                $source = imagecreatefromjpeg($this->filePath);
                $preview = imagecreatetruecolor($previewWidth, $previewHeight);
                imagealphablending($preview, false);
                imagesavealpha($preview, true);
                imagecopyresampled($preview, $source, 0, 0, 0, 0, $previewWidth, $previewHeight, $width, $height);
                $this->makePreviewDir();
                imagejpeg($preview, Helper::getImagePreviewPath($this->file->getTmpName()));
                return Helper::getImagePreviewUrl($this->file->getTmpName());
            case 'image/png':
                $source = imagecreatefrompng($this->filePath);
                $transparentPreview = imagecolorallocatealpha($preview, 0, 0, 0, 127);
                imagefill($preview, 0, 0, $transparentPreview);
                imagealphablending($preview, FALSE);
                imagesavealpha($preview, TRUE);
                imagecopyresampled($preview, $source, 0, 0, 0, 0, $previewWidth, $previewHeight, $width, $height);
                $this->makePreviewDir();
                imagepng($preview, Helper::getImagePreviewPath($this->file->getTmpName()));
                return Helper::getImagePreviewUrl($this->file->getTmpName());
        }
    }

}
