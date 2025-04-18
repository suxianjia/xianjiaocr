<?php 
namespace xianjiaocr;

//myDatabase


class myDatabase {
    private $database_type = 'mysqli';

    public function __destruct() {
        $this->close();
    }

    private static $instance = null;
    private $mysqli;

    private function __construct() {
        $this->mysqli = new mysqli(
            DB_HOST_MASTER,
            DB_USERNAME_MASTER,
            DB_PASSWORD_MASTER,
            DB_DATABASE_MASTER,
            DB_PORT_MASTER
        );

        if ($this->mysqli->connect_error) {
            die("Connection failed: " . $this->mysqli->connect_error);
        }
    }

    public function __clone(): void {}
    public function __wakeup(): void {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->mysqli;
    }

    public function close() {
        if ($this->mysqli) {
            $this->mysqli->close();
            $this->mysqli = null;
        }
        self::$instance = null; // Destroy the singleton instance
    }
}
