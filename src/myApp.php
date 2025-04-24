<?php 
namespace Suxianjia\xianjiaocr;
use Suxianjia\xianjiaocr\myConfig;
use Exception;
use Suxianjia\xianjiaocr\client\OCRClient;
use Suxianjia\xianjiaorm\orm\myDatabase;
use Suxianjia\xianjialogwriter\client\myLogClient;
if (!defined('myAPP_VERSION')) {        exit('myAPP_VERSION is not defined'); }
if (!defined('myAPP_ENV')  ) {          exit ('myAPP_ENV is not defined'); }
if (!defined('myAPP_DEBUG')) {          exit('myAPP_DEBUG is not defined'); }
if (!defined('myAPP_PATH')) {           exit('myAPP_PATH is not defined'); }
if (!defined('myAPP_RUNRIMT_PATH')) {   exit('myAPP_RUNRIMT_PATH is not defined'); }

class myApp {
    private static  $tableName = '';
    private static $contentName = '';
    private static $idName = '';
    private static $app_path = '';
 

    private static $instance = null;
    private static $runtime_path = '';
 

    private function __construct() {
        // Private constructor to prevent direct instantiation
    }
//     public static function getInstance(string $tableName, string $contentName, string $idName): Appocr {
    public static function getInstance(): myApp { 
        if (self::$instance === null) {
            self::init();
            self::$instance = new self();
        }
        return self::$instance;
    }

    private static function init () {
        $config = myConfig::getInstance()::getModelInfoConfig();
        self::$tableName = $config['table_name'];
        self::$contentName = $config['content_name'];
        self::$idName = $config['id_name'];
    }

//    
public static function getTableName( ): string  {
    return self::$tableName;
}
// e
public static  function getContentName( ): string  {
    return self::$contentName;
}
// 
public static function getIdName( ): string  {
    return self::$idName;
}


public static function setAppPath()    {
    self::$app_path = myAPP_PATH;
}

public static function getAppPath(): string {
    self::$app_path = myAPP_PATH;
    return  self::$app_path;
   
}
//  
public static function setRuntimePath(string $path = '') {
    self::$runtime_path = myAPP_RUNRIMT_PATH;
}
public static function getRuntimePath(): string {
    self::$runtime_path = myAPP_RUNRIMT_PATH;
  return  self::$runtime_path;
}
 

    private function reSaveArticleToDatabase(string $table_ontent, int $table_id) : array   {
   

        $results = ['code' => 500, 'msg' => 'Failed', 'data' => []   ];
        $mysqli = myDatabase::getInstance()->getConnection();
        try {
            $stmt = $mysqli->prepare("UPDATE `".self::getTableName()."` SET `".self::getContentName()."` = ? WHERE `".self::getIdName() ."` = ?");
            if (!$stmt) {
                throw new Exception("Error preparing statement");
            }
            $stmt->bind_param('si', $table_ontent, $table_id);
            $res = $stmt->execute();
            if (!$res) {
                throw new Exception("Error executing statement");
            }
            $results = ['code' => 200, 'msg' => 'Data resaved successfully.', 'data' => ['id' => $table_id]];
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
            $sql = "SELECT COUNT(*) FROM `".self::getTableName()."` ";
            echo "." . PHP_EOL;
            echo $sql.PHP_EOL;

            $pageCount = $mysqli->query( $sql )->fetch_row()[0];
            $maxPages = ceil($pageCount / $pageSize);

            for ($currentPage = 0; $currentPage < $maxPages; $currentPage++) {


                $offset = $currentPage * $pageSize;
                $stmt = $mysqli->prepare("SELECT `".self::getIdName()."` AS `id`,  `".self::getContentName()."`   AS `content` FROM `".self::getTableName()."`  ORDER BY `".self::getIdName()."`   DESC LIMIT ? OFFSET ?");
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
                    echo ".Page  = $currentPage , ".self::getTableName().'.'.self::getContentName().'....'.self::getIdName()." =   $table_id " . PHP_EOL;
                    echo "." . PHP_EOL;
                    echo "." . PHP_EOL;



                    preg_match_all('/<img(?![^>]*ocr-data)[^>]+src="([^"]+)"/i', $table_ontent, $matches);
                    $imagePaths = $matches[1] ?? [];

                    foreach ($imagePaths as $imagePath) {
                        echo ".......| 遍历图片： " . PHP_EOL;
                        echo ".......|" . PHP_EOL;
                        echo ".......| $imagePath " . PHP_EOL;
                        echo ".......|" . PHP_EOL;
                        $ocrDatatext = '';
                        $ocrResult = OCRClient::getInstance()->processImage($imagePath,$table_id  );

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
                            echo ".......|" . PHP_EOL;
                            echo ".......|---> ocrResult error: {$ocrResult['code']} -- {$ocrResult['msg']} " . PHP_EOL;
                        }
                        echo ".......|" . PHP_EOL;
                        echo ".......|---> table_id = $table_id -- imagePath = $imagePath -- ocrDatatext = $ocrDatatext ".'  end' . PHP_EOL;

                

                        myLogClient::getInstance()::writeExecutionLog( $table_id, self::getIdName(), self::getContentName(), self::getTableName(), $imagePath, $ocrDatatext );
                    }

                    $saveResult = $this->reSaveArticleToDatabase($table_ontent, $table_id );
                    if ($saveResult['code'] !== 200) {
                        throw new Exception("Failed to update table ID {$table_id}: " . $saveResult['msg']);
                    }else {
                        $results = ['code' => 200, 'msg' => "Successfully to  updated  table ID {$table_id}.", 'data' =>  [] ];
                    }
                    
                }

                $results = ['code' => 200, 'msg' => "Successfully    .", 'data' =>  [] ];
                $stmt->close();
            }
        } catch (Exception $e) {
            $results = ['code' => 500, 'msg' => 'Error: 002 ' . $e->getMessage() .'---' . $e->getLine(), 'data' => []   ];
            myLogClient::getInstance()::writeErrorLog('Error message', var_export(  $results , true));
        } finally {
            myDatabase::getInstance()->close();
        }
        return $results;
    }
}
