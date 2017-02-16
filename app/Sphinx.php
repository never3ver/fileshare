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

}
