<?php
namespace Suxianjia\xianjiaocr\client;
use Exception;
use Suxianjia\xianjiaocr\myConfig;
class myLogClient {
    private static $instance = null;
    private static   $CREATE_SQL = "CREATE TABLE IF NOT EXISTS `image_ocr_log` (
        `id` int NOT NULL AUTO_INCREMENT COMMENT '唯一标识符',
        `current_id` int DEFAULT NULL COMMENT '当前id',
        `id_name` varchar(40) DEFAULT NULL COMMENT '表ID',
        `content_name` varchar(40) DEFAULT NULL COMMENT '表内容',
        `table_name` varchar(40) DEFAULT NULL COMMENT '表名',
        `image_path` varchar(255) DEFAULT NULL COMMENT '图片路径',
        `image_size` int DEFAULT NULL COMMENT '图片大小',
        `image_path_index` varchar(40) DEFAULT NULL COMMENT '图片路径的MD5值',
        `ocr_data_text` text COMMENT 'OCR数据文本',
        `create_time` timestamp NULL DEFAULT NULL COMMENT '时间戳',
        PRIMARY KEY (`id`),
        KEY `image` (`image_path_index`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ";
    private static $LOG_FILE_PATH;
    //  'runtime_path' => __DIR__.'/../runtime',
    private static $RUNTIME_LOG_FILE_PATH; // 运行时路径


    private static $LOG_MODEL;

    private static $MY_DATABASE;
    // public static function getInstance( $logFilePath ,$logmodel,$myDatabase ) {
    
    public static function getInstance(   ) {
         // Load all configuration settings from myConfig
        $config = myConfig::getAllConfig();
        if (isset($config['log'])) {
            $logConfig = $config['log'];
            $logFilePath = $logConfig['path'] ?? $logFilePath;
            //  运行时路径
            self::$RUNTIME_LOG_FILE_PATH = $logConfig['runtime_path'] ?? $logFilePath;

            $logmodel = $logConfig['type'] ?? $logmodel;
            // $myDatabase = $logConfig['db'] ?? $myDatabase;
        }
        if (self::$LOG_MODEL == 'mysql') {
            $myDatabase = myDatabase::getInstance();
        } else {
            $myDatabase = null;
        }
        if (self::$LOG_FILE_PATH == null) {
            self::$LOG_FILE_PATH = $logFilePath;
        }
        if (self::$LOG_MODEL == null) {
            self::$LOG_MODEL = $logmodel;
        }
        if (self::$MY_DATABASE == null) {
            self::$MY_DATABASE = $myDatabase;
        }
        if (self::$instance === null) {
            self::$instance = new self();
        }
        self::$LOG_FILE_PATH = $logFilePath;
        self::$LOG_MODEL=$logmodel;
        self::$MY_DATABASE = $myDatabase;
        return self::$instance;
    }
    public function __destruct() {
        // Close the database connection if it exists
        if (self::$MY_DATABASE) {
            self::$MY_DATABASE->close();
        }
    }
    public function getLogModel() {
        return self::$LOG_MODEL;
    }
    public function getLogFilePath() {
        return self::$LOG_FILE_PATH;
    }
    public function getMyDatabase() {
        return self::$MY_DATABASE;
    }
    public function getCreateSql() {
        return self::$CREATE_SQL;
    }
    // 写入错误日志  myLogClient::getInstance()::writeErrorLog('Error message');
    public static function writeErrorLog(string $errorMessage, string $str =""): void {
        $logFilePath = self::$LOG_FILE_PATH . '/error_log_' . date('Y-m-d--H', time()) . '.txt';
        file_put_contents($logFilePath, $errorMessage.':' $str . PHP_EOL, FILE_APPEND);
    }

    // 写入执行日志

    public  static function writeLog( int $current_id, string $idName, string $contentName, string $tableName,  string $imagePath, string $ocrDataText): array {
        $results = ['code' => 500, 'msg' => 'Failed', 'data' => []];
 
        $image_ocr_log = [
            'current_id' => $current_id, // 当前id
            'id_name' => $idName, // 表ID
            'content_name' => $contentName, // 表内容
            'table_name' => $tableName, // 表名
            'image_path' => $imagePath, // 图片路径
            'image_size' => (int) filesize($imagePath) ?? 0, // 图片大小
            'image_path_index' => md5($imagePath), // 图片路径的MD5值
            'ocr_data_text' => $ocrDataText, // OCR数据文本
            'create_time' => date('Y-m-d H:i:s') // 时间戳
        ];

        try {
            if ( self::getLogModel()  == 'file') {
                // $logFilePath = __DIR__ . '/tempImagePath/' . 'ocr_log_' . date('Y-m-d--H', time()) . '.txt';
                $logFilePath = self::$LOG_FILE_PATH. '/ocr_log_' . date('Y-m-d--H', time()) . '.txt';
                file_put_contents($logFilePath, json_encode($image_ocr_log, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
                $results['code'] = 200;
                $results['msg'] = 'Successfully ';
            } else if (  self::getLogModel()    == 'mysql') {
                // 保存到mysql数据库
                $mysqli = self::$MY_DATABASE->getConnection();
                // 判断表是否存在，不存在则创建
                $tableName = 'image_ocr_log';
                $stmt = $mysqli->prepare("SHOW TABLES LIKE '{$tableName}'");
                if (!$stmt) {
                    throw new Exception("failed 准备语句失败: " . $mysqli->error);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 0) {
                    $createStmt = $mysqli->prepare(self::$CREATE_SQL);
                    if (!$createStmt) {
                        throw new Exception("failed 准备创建表语句失败: " . $mysqli->error);
                    }
                    if (!$createStmt->execute()) {
                        throw new Exception("failed 创建表失败: " . $createStmt->error);
                    }
                    $createStmt->close();
                }
                $stmt->close();

             
                    // Check if a record with the same image_path_index exists
                    $SELECT_SQL = "SELECT `id` FROM `image_ocr_log` WHERE `image_path_index` = ?";
                    $stmt = $mysqli->prepare($SELECT_SQL);

                    if (!$stmt) {
                        throw new Exception("failed 准备查询语句失败: " . $mysqli->error);
                    }

                    $stmt->bind_param('s', $image_ocr_log['image_path_index']);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        // Record exists, update it
                        $UPDATE_SQL = "UPDATE `image_ocr_log` SET 
                            current_id = ?, 
                            id_name = ?, 
                            content_name = ?, 
                            table_name = ?, 
                            image_path = ?, 
                            image_size = ?, 
                            ocr_data_text = ?, 
                            create_time = ? 
                            WHERE image_path_index = ?";
                        $updateStmt = $mysqli->prepare($UPDATE_SQL);

                        if (!$updateStmt) {
                            throw new Exception("failed 准备更新语句失败: " . $mysqli->error);
                        }

                        $updateStmt->bind_param(
                            'issssisss',
                            $image_ocr_log['current_id'],
                            $image_ocr_log['id_name'],
                            $image_ocr_log['content_name'],
                            $image_ocr_log['table_name'],
                            $image_ocr_log['image_path'],
                            $image_ocr_log['image_size'],
                            $image_ocr_log['ocr_data_text'],
                            $image_ocr_log['create_time'],
                            $image_ocr_log['image_path_index']
                        );

                        if (!$updateStmt->execute()) {
                            throw new Exception("更新数据失败: " . $updateStmt->error);
                        }

                        $updateStmt->close();
                    } else {
                        // Record does not exist, insert it
                        $INSERT_SQL = "INSERT INTO image_ocr_log (current_id, id_name, content_name, table_name, image_path, image_size, image_path_index, ocr_data_text, create_time) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $insertStmt = $mysqli->prepare($INSERT_SQL);

                        if (!$insertStmt) {
                            throw new Exception("failed 准备插入语句失败: " . $mysqli->error);
                        }

                        $insertStmt->bind_param(
                            'issssisss',
                            $image_ocr_log['current_id'],
                            $image_ocr_log['id_name'],
                            $image_ocr_log['content_name'],
                            $image_ocr_log['table_name'],
                            $image_ocr_log['image_path'],
                            $image_ocr_log['image_size'],
                            $image_ocr_log['image_path_index'],
                            $image_ocr_log['ocr_data_text'],
                            $image_ocr_log['create_time']
                        );

                        if (!$insertStmt->execute()) {
                            throw new Exception("插入数据失败: " . $insertStmt->error);
                        }

                        $insertStmt->close();
                    }

                    $stmt->close();
             
                $results['code'] = 200;
                $results['msg'] = 'Successfully';
            } else {
                $results['code'] = 500;
                $results['msg'] = 'log model error';
            }
        } catch (Exception $e) {
            $results['code'] = 500;
            $results['msg'] = $e->getMessage() . "--" . $e->getLine();
        }

        $results['data']['logmodel'] = self::$LOG_MODEL ;
        return $results;
    }

    public function __clone() {}
public function __wakeup() {}

}