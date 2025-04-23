<?php




function fun(): string
{
    return "fun";
}

// fnmatch() filler for non-POSIX systems

if (!function_exists('fnmatch')) {
    function fnmatch($pattern, $string)
    {
        return preg_match("#^" . strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.')) . "$#i", $string);
    } // end
} // end if


function check_blacklist($string,$blacklist)
{
//    global $blacklist;
    if (is_array($blacklist)) {
        foreach ($blacklist as $illegal) {
            if (fnmatch($illegal, $string)) {
                return false;
            }
        }
    }
    return true;
}
// check_scan_url
function check_scan_url($url = '' ,$config = []) :bool|array|string
{
    $result = false;
    $curl_client = curl_init();

    //Set URL
    curl_setopt($curl_client, CURLOPT_URL, $url);
    //Follow redirects and get new url
    curl_setopt($curl_client, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_client, CURLOPT_FOLLOWLOCATION, 1); // 设置跟踪跳转
//    curl_setopt($curl_client, CURLOPT_FOLLOWLOCATION, 1);

    //Get headers
    curl_setopt($curl_client, CURLOPT_HEADER, 1);
    curl_setopt($curl_client, CURLOPT_NOBODY, true);
    //Optionally avoid validating SSL
    curl_setopt($curl_client, CURLOPT_SSL_VERIFYPEER,  $config['curl_validate_certificate']   );
    //Set user agent
    curl_setopt($curl_client, CURLOPT_USERAGENT, $config['crawler_user_agent']    );

    //Get data
    $data = curl_exec($curl_client);
    $content_type = curl_getinfo($curl_client, CURLINFO_CONTENT_TYPE);
    $http_code = curl_getinfo($curl_client, CURLINFO_HTTP_CODE);
    $redirect_url = curl_getinfo($curl_client, CURLINFO_REDIRECT_URL);
    $redirect_url_2 = curl_getinfo($curl_client,CURLINFO_EFFECTIVE_URL);


    $result =    [
        'code' =>  $http_code ,
        'type'=> $content_type,
        'data' =>  $data ,
        'url'=>  $redirect_url,
        'url2'=>  $redirect_url_2,
        'is_html' =>  stripos($content_type, "html" )
    ];
    //If content acceptable, return it. If not, `false`
    //    $result = ($http_code != 200 || (!stripos($content_type, "html"))) ? false : $data;
    curl_close($curl_client);
    return $result;
}


