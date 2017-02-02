<?php

class FileDataGateway {

    protected $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function addFile(File $file) {
        if ($file->getJson() != '') {
            $sql = "INSERT INTO fileshare (`name`, `tmpname`, `size`, `type`, `json`)"
                    . " VALUES (:name, :tmpname, :size, :type, :json)";
        } else {
            $sql = "INSERT INTO fileshare (`name`, `tmpname`, `size`, `type`)"
                    . " VALUES (:name, :tmpname, :size, :type)";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":name", $file->getName());
        $stmt->bindValue(":tmpname", $file->getTmpName());
        $stmt->bindValue(":size", $file->getSize());
        $stmt->bindValue(":type", $file->getType());
        if ($file->getJson() != '') {
            $stmt->bindValue(":json", $file->getJson());
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

    public function createTmpName() {
        for ($i = 0; $i < 20; $i++) {
            do {
                $tmpName = Helper::generateTmpName();
            } while ($this->isTmpNameExisting($tmpName));
        }
        return $tmpName;
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

    public function searchWithSphinx($query) {
        $query = trim(strval($query));
        $pdo = new PDO("mysql:host=127.0.0.1;port=9306");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "SELECT * FROM idx_fileshare_name WHERE MATCH (:query)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":query", $query);
        $stmt->execute();
        $files = $stmt->fetchAll();
        return $files;
    }

}
