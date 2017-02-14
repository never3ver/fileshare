<?php

class File {

    protected $id;
    protected $name;
    protected $tmpName;
    protected $size;
    protected $uploadTime;
    protected $type;
    protected $json;

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getTmpName() {
        return $this->tmpName;
    }

    public function getSize() {
        return $this->size;
    }

    public function getSizeInKb() {
        return round($this->size / 1024, 2);
    }

    public function getUploadTime() {
        return $this->uploadTime;
    }

    public function getType() {
        return $this->type;
    }

    public function getJson() {
        return $this->json;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setTmpName($tmpName) {
        $this->tmpName = $tmpName;
    }

    public function setSize($size) {
        $this->size = $size;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function setJson($json) {
        $this->json = $json;
    }

    public function isImage() {
        $imageMimeTypes = [
            "image/gif",
            "image/jpeg",
            "image/png",
        ];
        if (in_array($this->type, $imageMimeTypes)) {
            return TRUE;
        }
        return FALSE;
    }

    public function isMedia() {
        $mediaMimeTypes = [
            "audio/aac",
            "audio/mp3",
            "audio/mp4",
            "audio/mpeg",
            "audio/ogg",
            "audio/wav",
            "audio/webm",
            "video/mp4",
            "video/ogg",
            "video/webm",
            "video/avi"
        ];
        if (in_array($this->type, $mediaMimeTypes)) {
            return TRUE;
        }
        return FALSE;
    }

}
