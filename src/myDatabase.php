<?php 
namespace Suxianjia\xianjiaocr;
use Exception;
use mysqli;
use Suxianjia\xianjiaocr\myConfig;

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


    // public static function getInstance($hostname,$username,$password,$database,$port ) {
    public static function getInstance( ) {

 // Load all configuration settings from myConfig
 $config = myConfig::getAllConfig();
        if (isset($config['db'])) {
            $dbConfig = $config['db'];
            $hostname = $dbConfig['host'] ?? $hostname;
            $username = $dbConfig['username'] ?? $username;
            $password = $dbConfig['password'] ?? $password;
            $database = $dbConfig['database'] ?? $database;
            $port = $dbConfig['port'] ?? $port;
        }

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
                myLogClient::getInstance()::writeErrorLog('Error message', "Connection failed: " .self::$mysqli->connect_error );
                // myLogClient::getInstance()::writeErrorLog('Error message', var_export(  $results , true));
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
