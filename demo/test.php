<?php
 include_once __DIR__."/../vendor/autoload.php";
use Suxianjia\xianjiaocr\myConfig;
use Suxianjia\xianjiaocr\Appocr;
// use Suxianjia\xianjiaocr\OCRClient;
// use Suxianjia\xianjiaocr\myDatabase;
// use Suxianjia\xianjiaocr\myLogClient;  
 

define('DB_HOST_MASTER', '127.0.01');
define('DB_PORT_MASTER', 3306);
define('DB_DATABASE_MASTER', ' ');
define('DB_USERNAME_MASTER', ' ');
define('DB_PASSWORD_MASTER', ' ');

 
// Example usage
define('OCR_URL', 'https://ai.gitee.com/v1/images/ocr');
// define('OCR_TOKEN', ' ');//临时的 
define('OCR_TOKEN',' ');
define('OCR_MODEL', 'GOT-OCR2_0');
define('OCR_IMAGE_PATH', '1740650492348.jpg');
define('OCR_OUT_FILE_PATH', 'ocr.json');
define('OCR_RESPONSE_FORMAT', 'text');
 
myConfig::getInstance()->set('version', 'v1.0.1');
// Database configuration
myConfig::getInstance()->set('db', [
    'host' => DB_HOST_MASTER,
    'port' => DB_PORT_MASTER,
    'database' => DB_DATABASE_MASTER,
    'username' => DB_USERNAME_MASTER,
    'password' => DB_PASSWORD_MASTER,
]);
// ocr
myConfig::getInstance()->set('ocr', [
    'url' => OCR_URL,
    'token' => OCR_TOKEN,
    'model' => OCR_MODEL,
    'image_path' => OCR_IMAGE_PATH,
    'out_file_path' => OCR_OUT_FILE_PATH,
    'response_format' => OCR_RESPONSE_FORMAT,
]);
//log
myConfig::getInstance()->set('log', [
    'path' => __DIR__.'/temp',
    'type' => 'mysql',
 
]);
// modelinfo
myConfig::getInstance()->set('modelinfo', [
    'table_name' => 'ypc_news_base',
    'content_name' => 'article_content',
    'id_name' => 'article_id',
]);
// Set the table name, content name, and ID name
 
myConfig::getInstance()->save();
myConfig::getInstance()->reload();
 
$App =   Appocr::getInstance( );   
$result = $App->processAllArticles();

echo json_encode($result, JSON_UNESCAPED_UNICODE);




// 使用方法  cd /ocr/code/demo   &&  php82 test.php 

 
 