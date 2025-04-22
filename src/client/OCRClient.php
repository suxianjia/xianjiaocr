<?php 
namespace Suxianjia\xianjiaocr\client;
use Exception;
use CURLFile;
use Suxianjia\xianjiaocr\myConfig;
 
// Ensure the class exists and is autoloaded
use Suxianjia\xianjialogwriter\client\myLogClient;

// If the class is missing, you may need to install or define it
// Example: composer require suxianjia/xianjialogwriter
if (!defined('myAPP_VERSION')) {        exit('myAPP_VERSION is not defined'); }
if (!defined('myAPP_ENV')  ) {          exit ('myAPP_ENV is not defined'); }
if (!defined('myAPP_DEBUG')) {          exit('myAPP_DEBUG is not defined'); }
if (!defined('myAPP_PATH')) {           exit('myAPP_PATH is not defined'); }
if (!defined('myAPP_RUNRIMT_PATH')) {   exit('myAPP_RUNRIMT_PATH is not defined'); }
class OCRClient {
    private static  $url;
    private static $token;
    private static $model;
    private static $response_format = 'json';
 
    private  static $out_file_name ='_temp_image_.log';// = 'temp_image_'. date('Y-m-d--H', time()) . '_'; // temp_file_name
    private  static $instance = null;
    private static $images_max_size = 3 * 1024 * 1024; // 3MB
    private static $app_path; // Declare the missing static property
    private static $runtime_path; // Declare the runtime_path property

    private function __construct( ) {
     
    }

public function __clone() {}
public function __wakeup() {}

public static function getInstance() {
    if (self::$instance === null) {
        self::init();
        self::$instance = new self( );
    }
    return self::$instance;
}

private static function init() {

    $config = myConfig::getInstance()::getOcrConfig(); //getOcrConfig
 
            if(  isset( $config['url']  )) {                     self::$url              =    $config['url']  ; }
            if(  isset( $config['token']  )) {                   self::$token            =    $config['token']  ; }
            if(  isset( $config['model']  )) {                   self::$model            =    $config['model']  ; }
            if(  isset( $config['response_format']  )) {          self::$response_format   =    $config['response_format']  ; }
 
      
            if  ( isset( $config['images_max_size']  )) {        self::$images_max_size    =    $config['images_max_size']  ; }
   
   
 

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


// self::getResponseFormat(), // self::$response_format
public static function getResponseFormat()  {
    return self::$response_format;
}
 
// getTempImageName()
public static function getTempImageName()   {
    return  date('Y-m-d--H', time()) . '_'.self::$out_file_name;
}
// getImagesMaxSize()
public static function getImagesMaxSize()   {
    return self::$images_max_size;
}
// self::getModel(),    // $this->model,
public static function getModel()   {
    return self::$model;
}

//         curl_setopt($ch, CURLOPT_URL, self::getAppUrl() );

private static function getAppUrl() {
return self::$url;
}
// "Authorization: Bearer  ".self::getAppToken(),
private static function getAppToken() {
return self::$token;
}


public function processImage(string $remoteImagePath = '',string $table_id = ''): array {
        $results = ['code' => 500, 'msg' => 'Failed', 'data' => []   ];

   
        $tempImage = self::getRuntimePath().'/'. basename($remoteImagePath);
        // compressed_
        $compressed_tempImage = self::getRuntimePath().'/compressed_'. basename($remoteImagePath);
        try {
            if( $remoteImagePath ==  ''){
                $results['msg'] = 'Error: 00001 请传入远程图片路径';
            }

            $remotefilesize = 0;
            //  获取远程文件大小； 
            $headers = get_headers($remoteImagePath , 1);
            if (isset($headers['Content-Length'])) {
                    $remotefilesize = (int) $headers['Content-Length'];
            } else {
                    $remotefilesize = 0; // Default to 0 if size cannot be determined
                    throw new Exception('Image remotefilesize ： ' . $remotefilesize);
                    
            }
            echo "准备远程图片大小为：" . $remotefilesize . "  bytes" . PHP_EOL;
            echo "准备远程图片路径为：" . $remoteImagePath .PHP_EOL;  
            //             $imageData = file_get_contents($remoteImagePath);// Warning: file_get_contents(https://p3-sign.toutiaoimg.com/tos-cn-i-qvj2lq49k0/8807517b855b49edb528c4744ec1b101~noop.image?_iz=58558&amp;from=article.pc_detail&amp;x-expires=1691369919&amp;x-signature=1oTNlVCb1ix%2FgZ4BZnXabLzGxQU%3D): Failed to open stream: HTTP request failed! HTTP/1.1 403 Forbidden
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $remoteImagePath);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]);
            $imageData = curl_exec($ch);


            // 判断请求状态为 200，  是否是图片
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ( $status !== 200) {
                $results = ['code' =>    $status , 'msg' =>  'Failed to download image status: 准备远程图片路径为：' . $remoteImagePath . '-->'. time() .'--> status:'. $status , 'data' => []   ];

                myLogClient::getInstance()::writeErrorLog('Error message: download image , tablename='.myConfig::getInstance()::getAllConfig()['modelinfo']['table_name' ].', content_name='.myConfig::getInstance()::getAllConfig()['modelinfo']['content_name' ]. ', table_id ='. $table_id, var_export(  $results , true));
                return $results;
                // throw new Exception('Failed to download image status: ' . $remoteImagePath . '-->'. time() .'--> status:'. $status);
            }
            // var_dump(   $imageData ); exit;
            // var_dump(   $imageData ); exit;
            if (curl_errno($ch)) {
                throw new Exception('Failed to download image: curl_errno ' . curl_error($ch));
            }
            curl_close($ch);
            if ($imageData === false) {
                throw new Exception('Failed to download image: imageData ' . $remoteImagePath . '-->'. time() );
            }

        } catch (Exception $e) {
            $results['msg'] = 'Error: 001 ' . $e->getMessage().'--> '. $e->getLine(). '-->'.$e->getFile();
            myLogClient::getInstance()::writeErrorLog('Error message,  ' , var_export(  $results , true));
             return $results;
        }

        // var_dump ($imageData); 
        try {
  

            file_put_contents($tempImage, $imageData);
            $filesize = filesize($tempImage);

            echo "压缩前 图片大小为：" .   $filesize .'/'.self::getImagesMaxSize()  .' --> ' . (  $filesize / self::getImagesMaxSize()  )  .PHP_EOL;
            echo "压缩前 图片路径为：" . $tempImage .PHP_EOL;  
            if(filesize($tempImage) == 0 ){
                throw new Exception('Image file not found: ' . $tempImage);
            }
        // 检查图片大小并压缩
        if (filesize($tempImage) > self::getImagesMaxSize()  ) {

            $compressionResult = self::compressImage($tempImage);
            if ($compressionResult['code'] !== 200) {
                throw new Exception($compressionResult['msg']);
            }
            echo "检查图片大小并压缩后， 临时图片大小为：" .   $compressionResult['data']['file_size'].'/'.self::getImagesMaxSize()  .PHP_EOL;
            echo "检查图片大小并压缩后， 临时图片路径为：" .   $compressionResult['data']['compressed_image'] .PHP_EOL;  
            $tempImage = $compressionResult['data']['compressed_image'];
        }
//imagePath
        if (!file_exists($tempImage)) {
            throw new Exception('Image file not found: ' . $tempImage);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::getAppUrl() );
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer  ".self::getAppToken(),
            "Content-Type: multipart/form-data"
        ]);

        $postFields = [
            'model' =>  self::getModel(),    // $this->model,
            'image' => new CURLFile($tempImage, mime_content_type($tempImage), basename($tempImage)),
            'response_format' => self::getResponseFormat(), // self::$response_format
        ];
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('cURL Error: ' . $error);
        }

        curl_close($ch);

        $decodedData = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $results = ['code' => 200, 'msg' => 'Success', 'data' => $decodedData];
        } else {
            throw new Exception('Failed to decode JSON response.');
        }

        // 删除临时文件
        unlink($tempImage);
        unlink($compressed_tempImage );
    } catch (Exception $e) {
        $results['msg'] = 'Error: 003 ' . $e->getMessage().'--> '. $e->getLine().'-->'.$e->getFile() ;
        myLogClient::getInstance()::writeErrorLog('Error message', var_export(  $results , true));
    }

    return $results;
}

//--- 

// 图片文件大小超过 3MB，需要压缩图片处理，并重新生成新的图片文件
public static function compressImage($imagePath): array {
    $results = ['code' => 500, 'msg' => 'Failed', 'data' => []   ];
 
    $compressed_image = self::getRuntimePath() .'/'. 'compressed_' . basename($imagePath);
  

    try {
        $imageInfo = getimagesize($imagePath);
        $mime = $imageInfo['mime'];

        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                $quality = 75;
                do {
                    imagejpeg($image, $compressed_image, $quality);
                    $quality -= 5; // Reduce quality incrementally
                } while (filesize($compressed_image) > self::getImagesMaxSize() && $quality > 10);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                $compressionLevel = 6;
                do {
                    imagepng($image, $compressed_image, $compressionLevel);
                    $compressionLevel++;
                } while (filesize($compressed_image) > self::getImagesMaxSize() && $compressionLevel <= 9);
                break;
            default:
                throw new Exception('Unsupported image type: ' . $mime);
        }

        imagedestroy($image);

        if (!file_exists($compressed_image)) {
            throw new Exception('Failed to create compressed image.');
        }
        $filesize = filesize($compressed_image);
        if (   $filesize > self::getImagesMaxSize()) {
            throw new Exception('Compressed image still exceeds the maximum size of 3MB.');
        }
 

        $results = [
            'code' => 200, 
            'msg' => 'Image compressed successfully. *.*', 
            'data' => [
                'file_size'=>        $filesize  ,
                'compressed_image'=>  $compressed_image
                // echo "检查图片大小并压缩后， 临时图片大小为：" .   $compressionResult['data']['fileSize'].'/'.self::getImagesMaxSize()  .PHP_EOL;
                // echo "检查图片大小并压缩后， 临时图片路径为：" .   $compressionResult['data']['imagePath'] .PHP_EOL;  
            ] 
        ];
    } catch (Exception $e) {
        $results['msg'] = 'Image compression error: ' . $e->getMessage().'--> '. $e->getLine() .'-->'.$e->getFile() ; 
        myLogClient::getInstance()::writeErrorLog('Error message', var_export(  $results , true));
    }

    return $results;
}


//------ end
}

