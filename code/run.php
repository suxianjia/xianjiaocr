<?php
namespace xianjiaocr;

use xianjiaocr\App;
use xianjiaocr\OCRClient;
use xianjiaocr\myDatabase;

// define('DB_HOST_MASTER', 'rm-7xvqz50859y717cv0.mysql.rds.aliyuncs.com');
// define('DB_PORT_MASTER', 3308);
// define('DB_DATABASE_MASTER', 'www_u_petrol_com');
// define('DB_USERNAME_MASTER', 'w_api_user_master8');
// define('DB_PASSWORD_MASTER', 'Wdbfd_tdh@8Wirte0Db');

define('DB_HOST_MASTER', '127.0.01');
define('DB_PORT_MASTER', 3306);
define('DB_DATABASE_MASTER', 'www_u_petrol_com');
define('DB_USERNAME_MASTER', 'root');
define('DB_PASSWORD_MASTER', '654321mm');










// class myLog {}


// Example usage
define('OCR_URL', 'https://ai.gitee.com/v1/images/ocr');
// define('OCR_TOKEN', 'AVQX8TQWGAOMLQQSSXB2F5Q0VPTFJV1W3HTQIH1E');//临时的 
define('OCR_TOKEN','WUNJCZ38UH3TIQD5KU7D85PTELNJLBOQ03EQMG1H');
define('OCR_MODEL', 'GOT-OCR2_0');
define('OCR_IMAGE_PATH', '1740650492348.jpg');
define('OCR_OUT_FILE_PATH', 'ocr.json');
define('OCR_RESPONSE_FORMAT', 'text');
 

$ocrClient = OCRClient::getInstance(OCR_URL, OCR_TOKEN, OCR_MODEL,OCR_RESPONSE_FORMAT);
$myDatabase = myDatabase::getInstance();

$App = new App('ypc_news_base' , 'article_content','article_id', 'mysql' );  
$result = $App->processAllArticles($ocrClient, $myDatabase);

echo json_encode($result, JSON_UNESCAPED_UNICODE);



// 使用方法 yx-dev@Mac php % php82 ocr.php 

 
 