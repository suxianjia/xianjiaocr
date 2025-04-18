<?php 
namespace Suxianjia\xianjiaocr;
use Exception;
use mysqli;


class myDatabase {
    private $database_type = 'mysqli';

    public function __destruct() {
        $this->close();
    }

    private static $instance = null;
    private static $mysqli;

    private function __construct() {

    }

    public function __clone(): void {}
    public function __wakeup(): void {}

    public static function getInstance($hostname,$username,$password,$database,$port ) {
        if (self::$instance === null) {
            self::$mysqli = new mysqli(
                $hostname ,
                $username ,
                $password ,
                $database ,
                $port 
            );
    
            if (self::$mysqli->connect_error) {
                die("Connection failed: " .self::$mysqli->connect_error);
            }

            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return self::$mysqli;
    }

    public function close() {
        if (self::$mysqli) {
            self::$mysqli->close();
            self::$mysqli = null;
        }
        self::$instance = null; // Destroy the singleton instance
    }
}
