<?php

class FileInfo {

    protected $file;
    protected $size;
    protected $imageUrl;
    protected $playtime;
    protected $bitrate;
    protected $json;

    public function __construct(File $file) {
        $this->file = $file;
        $this->size = Helper::convertBytesToKilobytes($file->getSize());
        if ($file->isImage()) {
            $this->imageUrl = Helper::getImageUrl($file->getTmpName());
        } elseif ($file->getJson() != '' && $file->isMedia()) {
            $json = json_decode($file->getJson(), true);
            $this->playtime = $json['playtime'];
            $this->bitrate = $json['bitrate'];
        } elseif ($file->isMedia() && $file->getJson() == '') {
            $this->createJson();
        }
    }

    public function createJson() {
        $getID3 = new getID3();
        $properties = $getID3->analyze(Helper::getFilePath($this->file->getTmpName()));
        $this->playtime = $properties['playtime_string'];
        $this->bitrate = round($properties['bitrate']/1024, 2);
        $json = ['playtime' => $this->playtime, 'bitrate' => $this->bitrate];
        $json = json_encode($json);
        $this->json = $json;
    }

    public function getSize() {
        return $this->size;
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

    public function getJson() {
        return $this->json;
    }

}
