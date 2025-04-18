<?php
namespace xianjiaocr;


class myLog {
 

    public  static function writeLog(string $logModel ,int $current_id, string $idName, string $contentName, string $tableName,  string $imagePath, string $ocrDataText, myDatabase $myDatabase): array {
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
            if ($logModel == 'file') {
                $logFilePath = __DIR__ . '/tempImagePath/' . 'ocr_log_' . date('Y-m-d--H', time()) . '.txt';
                file_put_contents($logFilePath, json_encode($image_ocr_log, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
                $results['code'] = 200;
                $results['msg'] = '保存成功';
            } else if ($logModel == 'mysql') {
                // 保存到mysql数据库
                $mysqli = $myDatabase->getConnection();

                $CREATE_SQL = "CREATE TABLE IF NOT EXISTS `image_ocr_log` (
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

                // 判断表是否存在，不存在则创建
                $tableName = 'image_ocr_log';
                $stmt = $mysqli->prepare("SHOW TABLES LIKE '{$tableName}'");
                if (!$stmt) {
                    throw new Exception("准备语句失败: " . $mysqli->error);
                }

                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 0) {
                    $createStmt = $mysqli->prepare($CREATE_SQL);
                    if (!$createStmt) {
                        throw new Exception("准备创建表语句失败: " . $mysqli->error);
                    }

                    if (!$createStmt->execute()) {
                        throw new Exception("创建表失败: " . $createStmt->error);
                    }

                    $createStmt->close();
                }

                $stmt->close();

                // 插入数据 prepare $stmt->execute
                $INSERT_SQL = "INSERT INTO image_ocr_log (current_id, id_name, content_name, table_name, image_path, image_size, image_path_index, ocr_data_text, create_time) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $mysqli->prepare($INSERT_SQL);

                if (!$stmt) {
                    throw new Exception("准备语句失败: " . $mysqli->error);
                }

                $stmt->bind_param(
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

                if (!$stmt->execute()) {
                    throw new Exception("插入数据失败: " . $stmt->error);
                }

                $stmt->close();
                $results['code'] = 200;
                $results['msg'] = '保存成功';
            } else {
                $results['code'] = 500;
                $results['msg'] = 'log model error';
            }
        } catch (Exception $e) {
            $results['code'] = 500;
            $results['msg'] = $e->getMessage() . "--" . $e->getLine();
        }

        $results['data']['logmodel'] = $logModel;
        return $results;
    }
}