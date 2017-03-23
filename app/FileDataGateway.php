<?php

class FileDataGateway {

    protected $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function addFile(File $file) {
        if ($file->getMetadata() != '') {
            $sql = "INSERT INTO fileshare (`name`, `tmpname`, `size`, `type`, `metadata`)"
                    . " VALUES (:name, :tmpname, :size, :type, :metadata)";
        } else {
            $sql = "INSERT INTO fileshare (`name`, `tmpname`, `size`, `type`)"
                    . " VALUES (:name, :tmpname, :size, :type)";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":name", $file->getName());
        $stmt->bindValue(":tmpname", $file->getTmpName());
        $stmt->bindValue(":size", $file->getSize());
        $stmt->bindValue(":type", $file->getType());
        if ($file->getMetadata() != '') {
            $stmt->bindValue(":json", $file->getMetadata());
        }
        $stmt->execute();
    }

    public function getFile($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM fileshare WHERE `id` = :id");
        $stmt->bindValue(":id", $id);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'File');
        $file = $stmt->fetch();
        return $file;
    }

    public function getAllFiles($limit, $offset) {
        $sql = "SELECT * FROM fileshare ORDER BY uploadTime DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":limit", intval($limit, 10), PDO::PARAM_INT);
        $stmt->bindValue(":offset", intval($offset, 10), PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'File');
        $files = $stmt->fetchAll();
        return $files;
    }

    public function countAllFiles() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM fileshare");
        $stmt->execute();
        $filesTotal = $stmt->fetchColumn();
        return $filesTotal;
    }

    public function isTmpNameExisting($tmpName) {
        $sql = "SELECT COUNT(*) FROM fileshare WHERE tmpName = :tmpName";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":tmpName", $tmpName);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        if ($result > 0) {
            return TRUE;
        }
        return FALSE;
    }

    public function deleteFile($id) {
        $sql = "DELETE FROM fileshare WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":id", intval($id, 10), PDO::PARAM_INT);
        $stmt->execute();
    }

    public function search($query) {
        $sql = "SELECT * FROM fileshare WHERE `name` LIKE :query";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":query", "%" . $query . "%");
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'File');
        $files = $stmt->fetchAll();
        return $files;
    }

}
