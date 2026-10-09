<?php
// classes/Database.php
// PDO wrapper implementing the Singleton pattern.
class Database extends PDO {
    private static $instance = null;

    private function __construct($dsn, $user, $pass) {
        parent::__construct($dsn, $user, $pass);
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public static function getInstance($dsn, $user = null, $pass = null) {
        if (self::$instance === null) {
            self::$instance = new Database($dsn, $user, $pass);
        }
        return self::$instance;
    }

    public function insert($table, array $data) {
        $cols = implode(", ", array_keys($data));
        $phs  = ":" . implode(", :", array_keys($data));
        $sql  = "INSERT INTO $table ($cols) VALUES ($phs)";
        $stmt = $this->prepare($sql);
        $stmt->execute($data);
        return $this->lastInsertId();
    }

    public function update($table, array $data, $where, array $whereParams = []) {
        $set = [];
        foreach (array_keys($data) as $col) {
            $set[] = "$col = :$col";
        }
        $sql  = "UPDATE $table SET " . implode(", ", $set) . " WHERE $where";
        $stmt = $this->prepare($sql);
        return $stmt->execute(array_merge($data, $whereParams));
    }

    public function delete($table, $where, array $params = []) {
        $stmt = $this->prepare("DELETE FROM $table WHERE $where");
        return $stmt->execute($params);
    }

    public function getRows($sql, array $params = []) {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getRow($sql, array $params = []) {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}