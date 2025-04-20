<?php 
namespace Suxianjia\xianjiaocr;
use Suxianjia\xianjiaocr\myConfig;
use Exception;
use Suxianjia\xianjiaocr\OCRClient;
use Suxianjia\xianjiaocr\myDatabase;
use Suxianjia\xianjiaocr\myLogClient;


class Appocr {
    private static  $tableName = '';
    private static $contentName = '';
    private static $idName = '';
 

    private static $instance = null;
 

    private function __construct() {
        // Private constructor to prevent direct instantiation
    }
//     public static function getInstance(string $tableName, string $contentName, string $idName): Appocr {
    public static function getInstance(): Appocr { 

        // Load all configuration settings from myConfig
        $config = myConfig::getAllConfig();
        if (isset($config['tableName'])) {
            self::$tableName = $config['tableName'];
        }
        if (isset($config['contentName'])) {
            self::$contentName = $config['contentName'];
        }
        if (isset($config['idName'])) {
            self::$idName = $config['idName'];
        }

        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

 

    private function reSaveArticleToDatabase(string $table_ontent, int $table_id) : array   {
   

        $results = ['code' => 500, 'msg' => 'Failed', 'data' => []   ];
        $mysqli = myDatabase::getInstance()->getConnection();
        try {
            $stmt = $mysqli->prepare("UPDATE `".self::$tableName."` SET `".self::$contentName."` = ? WHERE `".self::$idName."` = ?");
            if (!$stmt) {
                throw new Exception("Error preparing statement");
            }
            $stmt->bind_param('si', $table_ontent, $table_id);
            $res = $stmt->execute();
            if (!$res) {
                throw new Exception("Error executing statement");
            }
            $results = ['code' => 200, 'msg' => 'Article resaved successfully.', 'data' => ['id' => $table_id]];
            $stmt->close();
        } catch (Exception $e) {
            $results = ['code' => 500, 'msg' => 'Database error: ' . $e->getMessage(), 'data' => []  ];
            myLogClient::getInstance()::writeErrorLog('Error message', var_export(  $results , true));
        }
        return $results;
    }

    public function processAllArticles(): array {
       
        $results = ['code' => 500, 'msg' => 'Failed', 'data' =>  []  ];
        try {
        

            $mysqli = myDatabase::getInstance()->getConnection();
            $pageSize = 1000;
            $pageCount = $mysqli->query("SELECT COUNT(*) FROM `".self::$tableName."` ")->fetch_row()[0];
            $maxPages = ceil($pageCount / $pageSize);

            for ($currentPage = 0; $currentPage < $maxPages; $currentPage++) {
                $offset = $currentPage * $pageSize;
                $stmt = $mysqli->prepare("SELECT `".self::$idName."` AS `id`,  `".self::$contentName."`   AS `content` FROM `".self::$tableName."`  ORDER BY `".self::$idName."`   DESC LIMIT ? OFFSET ?");
                if (!$stmt) {
                    throw new Exception('Failed to prepare the database statement.');
                }
                $stmt->bind_param('ii', $pageSize, $offset);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    $stmt->close();
                    continue;
                }

                while ($row = $result->fetch_assoc()) {
                    $table_id = (int) $row['id'];
                    $table_ontent = $row['content'];

                    preg_match_all('/<img(?![^>]*ocr-data)[^>]+src="([^"]+)"/i', $table_ontent, $matches);
                    $imagePaths = $matches[1] ?? [];

                    foreach ($imagePaths as $imagePath) {
                        $ocrDatatext = '';
                        $ocrResult = OCRClient::getInstance()->processImage($imagePath);

                        if ($ocrResult['code'] === 200 && isset($ocrResult['data'])) {
                            $ocrData = $ocrResult['data'];
                            $ocrDatatext = isset($ocrData['text']) ? $ocrData['text'] : '';
                            $ocrDatatext = "<font class='ocr-data' style='color:white; opacity:0;'>{$ocrDatatext}</font>";
                            $table_ontent = preg_replace(
                                '/(<img[^>]+src="' . preg_quote($imagePath, '/') . '"[^>]*>)/i',
                                '$1' . $ocrDatatext,
                                $table_ontent
                            );
                            $table_ontent = preg_replace(
                                '/(<img[^>]+src="' . preg_quote($imagePath, '/') . '"[^>]*)(>)/i',
                                '$1 ocr-data="isocred"$2',
                                $table_ontent
                            );
                        } else {
                            echo "|---> ocrResult error: {$ocrResult['code']} -- {$ocrResult['msg']} " . PHP_EOL;
                        }
                        echo "|---> table_id = $table_id -- imagePath = $imagePath -- ocrDatatext = $ocrDatatext " . PHP_EOL;

                

                        myLogClient::getInstance()::writeLog( $table_id, self::$idName, self::$contentName, self::$tableName, $imagePath, $ocrDatatext );
                    }

                    $saveResult = $this->reSaveArticleToDatabase($table_ontent, $table_id, $myDatabase);
                    if ($saveResult['code'] !== 200) {
                        throw new Exception("Failed to update article ID {$table_id}: " . $saveResult['msg']);
                    }
                    $results = ['code' => 200, 'msg' => "Successfully updated article ID {$table_id}.", 'data' => $table_id];
                }

                $stmt->close();
            }
        } catch (Exception $e) {
            $results = ['code' => 500, 'msg' => 'Error: 002 ' . $e->getMessage() .'---' . $e->getLine(), 'data' => []   ];
            myLogClient::getInstance()::writeErrorLog('Error message', var_export(  $results , true));
        } finally {
            $myDatabase->close();
        }
        return $results;
    }
}
