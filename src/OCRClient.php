<?php 
namespace Suxianjia\xianjiaocr;
use Exception;
use CURLFile;

class OCRClient {
    private $url;
    private $token;
    private $model;
    private $responseFormat;
    private static  $TEMP_IMAGE_PATH ;

    private static $instance = null;
    private static $imagesMaxSize = 3 * 1024 * 1024; // 3MB

    private function __construct($url, $token, $model, $responseFormat = 'json') {
        $this->url = $url;
        $this->token = $token;
        $this->model = $model;
        $this->responseFormat = $responseFormat;
    }
// 图片文件大小超过 3MB，需要压缩图片处理，并重新生成新的图片文件
public static function compressImage($imagePath): array {
    $results = ['code' => 500, 'msg' => 'Failed', 'data' => []   ];
    $imagesMaxSize = self::$imagesMaxSize; // 最大图片大小 3MB
    $compressedImagePath = self::$TEMP_IMAGE_PATH . '/compressed_' . basename($imagePath);

    try {
        $imageInfo = getimagesize($imagePath);
        $mime = $imageInfo['mime'];

        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                $quality = 75;
                do {
                    imagejpeg($image, $compressedImagePath, $quality);
                    $quality -= 5; // Reduce quality incrementally
                } while (filesize($compressedImagePath) > $imagesMaxSize && $quality > 10);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                $compressionLevel = 6;
                do {
                    imagepng($image, $compressedImagePath, $compressionLevel);
                    $compressionLevel++;
                } while (filesize($compressedImagePath) > $imagesMaxSize && $compressionLevel <= 9);
                break;
            default:
                throw new Exception('Unsupported image type: ' . $mime);
        }

        imagedestroy($image);

        if (!file_exists($compressedImagePath)) {
            throw new Exception('Failed to create compressed image.');
        }

        if (filesize($compressedImagePath) > $imagesMaxSize) {
            throw new Exception('Compressed image still exceeds the maximum size of 3MB.');
        }

        $results = [
            'code' => 200, 
            'msg' => 'Image compressed successfully.', 
            'data' => [
                'fileSize'=> filesize($compressedImagePath),
                'imagePath'=>$compressedImagePath
            ] 
        ];
    } catch (Exception $e) {
        $results['msg'] = 'Image compression error: ' . $e->getMessage();
    }

    return $results;
}

public function __clone() {}
public function __wakeup() {}

public static function getInstance(string $url,string  $token, string  $model, $responseFormat = 'json', string $tempImagePathString) {
    self::$TEMP_IMAGE_PATH = $tempImagePathString;
    if (self::$instance === null) {
        self::$instance = new self($url, $token, $model, $responseFormat);
    }
    return self::$instance;
}

public function processImage($remoteImagePath): array {
    $results = ['code' => 500, 'msg' => 'Failed', 'data' => []   ];

        $tempImagePath = self::$TEMP_IMAGE_PATH .'/'. basename($remoteImagePath);
        try {
            echo "准备远程图片路径为：" . $remoteImagePath .PHP_EOL;  
            $imageData = file_get_contents($remoteImagePath);
            if ($imageData === false) {
                throw new Exception('Failed to download image: ' . $remoteImagePath . '-->'. time() );
            }

        } catch (Exception $e) {
            $results['msg'] = 'Error: ' . $e->getMessage();
             return $results;
        }

        // var_dump ($imageData); 
        try {
  

            file_put_contents($tempImagePath, $imageData);
            echo "压缩前 图片大小为：" .  filesize($tempImagePath) .'/'.self::$imagesMaxSize .PHP_EOL;
            echo "压缩前 图片路径为：" . $tempImagePath .PHP_EOL;  
            if(filesize($tempImagePath) == 0 ){
                throw new Exception('Image file not found: ' . $tempImagePath);
            }
        // 检查图片大小并压缩
        if (filesize($tempImagePath) > self::$imagesMaxSize) {

            $compressionResult = self::compressImage($tempImagePath);
            if ($compressionResult['code'] !== 200) {
                throw new Exception($compressionResult['msg']);
            }
            echo "检查图片大小并压缩后， 临时图片大小为：" .   $compressionResult['data']['fileSize'].'/'.self::$imagesMaxSize .PHP_EOL;
            echo "检查图片大小并压缩后， 临时图片路径为：" .   $compressionResult['data']['imagePath'] .PHP_EOL;  
            $tempImagePath = $compressionResult['data']['imagePath'];
        }

        if (!file_exists($tempImagePath)) {
            throw new Exception('Image file not found: ' . $tempImagePath);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$this->token}",
            "Content-Type: multipart/form-data"
        ]);

        $postFields = [
            'model' => $this->model,
            'image' => new CURLFile($tempImagePath, mime_content_type($tempImagePath), basename($tempImagePath)),
            'response_format' => $this->responseFormat
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
        unlink($tempImagePath);
    } catch (Exception $e) {
        $results['msg'] = 'Error: ' . $e->getMessage();
    }

    return $results;
}
}

