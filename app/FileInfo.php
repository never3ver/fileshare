<?php

class FileInfo {

    protected $file;

    public function __construct($file) {
        $this->file = $file;
    }

    public function getDataForTemplate() {
        $data = [];
        $data['file'] = $this->file;
        $data['size'] = Helper::convertBytesToKilobytes($this->file->getSize());
        if ($this->file->isImage()) {
            $data['imagePath'] = Helper::getImagePath($this->file->getTmpName());
        } elseif ($this->file->isMedia()) {
            $data['filePath'] = Helper::getFilePath($this->file->getTmpName());
            $getID3 = new getID3();
            $properties = $getID3->analyze(Helper::getFilePath($this->file->getTmpName()));
            $data['length'] = $properties['playtime_string'];
            $data['bitrate'] = $properties['bitrate'];
        } else {
            $data['filePath'] = Helper::getFilePath($this->file->getTmpName());
        }
        return $data;
    }

}
