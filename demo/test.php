<?php
 include_once __DIR__."/../vendor/autoload.php";

use Suxianjia\xianjiaocr\Appocr;
use Suxianjia\xianjiaocr\OCRClient;
use Suxianjia\xianjiaocr\myDatabase;
use Suxianjia\xianjiaocr\myLogClient;  
 

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
 

$ocrClient = OCRClient::getInstance(OCR_URL, OCR_TOKEN, OCR_MODEL,OCR_RESPONSE_FORMAT,__DIR__.'/temp' );
$myDatabase = myDatabase::getInstance(DB_HOST_MASTER    ,  DB_USERNAME_MASTER   , DB_PASSWORD_MASTER ,DB_DATABASE_MASTER , DB_PORT_MASTER);
$logClient = myLogClient::getInstance(__DIR__.'/temp','mysql', $myDatabase );
$App =   Appocr::getInstance('ypc_news_base' , 'article_content','article_id' );  
$result = $App->processAllArticles($ocrClient, $myDatabase,$logClient);

echo json_encode($result, JSON_UNESCAPED_UNICODE);



// 使用方法  cd /ocr/code/demo   &&  php82 test.php 

 
 