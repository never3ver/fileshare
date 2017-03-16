<?php

class Sphinx {

    protected $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function searchBySphinx($query) {
        $query = trim(strval($query));
        $sql = "SELECT * FROM idx_fileshare_name WHERE MATCH (:query)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":query", $query);
        $stmt->execute();
        $files = $stmt->fetchAll();
        return $files;
    }

    public function addRtIndex($id, $name) {
        $sql = "INSERT INTO rt VALUES (:id, 'temp', :name)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":id", intval($id, 10), PDO::PARAM_INT);
        $stmt->bindValue(":name", $name);
        $stmt->execute();
    }

    public function searchIdInRtIndex($query) {
        $sql = "SELECT * FROM rt WHERE MATCH (:query)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":query", $query);
        $stmt->execute();
        $id = $stmt->fetchAll();
        return $id;
    }

    public function deleteRtIndex($id) {
        $sql = "DELETE FROM rt WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(":id", intval($id, 10), PDO::PARAM_INT);
        $stmt->execute();
    }

}
