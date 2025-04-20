<?php 
namespace Suxianjia\xianjiaocr;
use Exception;



class myConfig {
    private static $instance = null;
    private $config = [];

    private function __construct() {
        $configFile = __DIR__ . '/config.php';
        if (!file_exists($configFile)) {
            throw new Exception('Configuration file not found: ' . $configFile);
        }
        if (!is_readable($configFile)) {
            throw new Exception('Configuration file is not readable: ' . $configFile);
        }
        if (!is_writable($configFile)) {
            throw new Exception('Configuration file is not writable: ' . $configFile);
        }
        $this->config = require_once __DIR__ . '/config.php';

 

    }

    public static function getInstance(): myConfig {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get($key) {
        return $this->config[$key] ?? null;
    }
    public function set($key, $value) {
        $this->config[$key] = $value;
    }
    public function getAll() {
        return $this->config;
    }
    public function setAll($config) {
        $this->config = $config;
    }
    public function save() {
        try {
            // $this->writeConfigToFile();
            $configFile = __DIR__ . '/config.php';
            $configContent = "<?php\n\nreturn " . var_export($this->config, true) . ";\n";
            file_put_contents($configFile, $configContent);
        } catch (Exception $e) {
            throw new Exception('Failed to save configuration: ' . $e->getMessage());
        }

    }
    public function reload() {
        $this->config = require_once __DIR__ . '/config.php';
    }
    public function getDatabaseConfig() {
        return $this->config['db'] ?? [];
    }
    public function getOcrConfig() {
        return $this->config['ocr'] ?? [];
    }
    public function getLogConfig() {
        return $this->config['log'] ?? [];
    }
    public function getVersion() {
        return $this->config['version'] ?? '';
    }
    public function setVersion($version) {
        $this->config['version'] = $version;
    }
    public function getLogFilePath() {
        return $this->config['log_file_path'] ?? '';
    }
    public function setLogFilePath($logFilePath) {
        $this->config['log_file_path'] = $logFilePath;
    }
    public function getLogLevel() {
        return $this->config['log_level'] ?? '';
    }
    public function setLogLevel($logLevel) {
        $this->config['log_level'] = $logLevel;
    }
    public function getLogFormat() {
        return $this->config['log_format'] ?? '';
    }
    public function setLogFormat($logFormat) {
        $this->config['log_format'] = $logFormat;
    }
    public function getLogMaxFileSize() {
        return $this->config['log_max_file_size'] ?? '';
    }
    public function setLogMaxFileSize($logMaxFileSize) {
        $this->config['log_max_file_size'] = $logMaxFileSize;
    }
    public function getLogMaxFiles() {
        return $this->config['log_max_files'] ?? '';
    }
    public function setLogMaxFiles($logMaxFiles) {
        $this->config['log_max_files'] = $logMaxFiles;
    }
    public function getLogRotation() {
        return $this->config['log_rotation'] ?? '';
    }
    public function setLogRotation($logRotation) {
        $this->config['log_rotation'] = $logRotation;
    }
    public function getLogRetention() {
        return $this->config['log_retention'] ?? '';
    }
    public function setLogRetention($logRetention) {
        $this->config['log_retention'] = $logRetention;
    }
    public function getLogBackup() {
        return $this->config['log_backup'] ?? '';
    }
    public function setLogBackup($logBackup) {
        $this->config['log_backup'] = $logBackup;
    }
    public function getLogCompression() {
        return $this->config['log_compression'] ?? '';
    }
    public function setLogCompression($logCompression) {
        $this->config['log_compression'] = $logCompression;
    }
    public function getLogEncryption() {
        return $this->config['log_encryption'] ?? '';
    }
    public function setLogEncryption($logEncryption) {
        $this->config['log_encryption'] = $logEncryption;
    }
}