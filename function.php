<?php

/* *
 * 使用CURL模拟提交HTTP、HTTPS请求
 * @return array('code', 'header', 'content', 'encoding'); 返回状态码、响应头信息、内容
 */
function curlResponse($url, $method = false, $postdata = NULL, $cookie = '', $redirect = false, $referer = '', $useragent = '', $request_header = array(), $timeout = 30){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, max($timeout, 30));
    curl_setopt($ch, CURLOPT_POST, $method ?  true : false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $redirect ? true : false);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    if (isset($postdata) && !empty($postdata)) curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    if (isset($cookie) && strlen($cookie) > 0) curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    if (isset($referer) && strlen($referer) > 0) curl_setopt($ch, CURLOPT_REFERER, $referer);
    if (isset($useragent) && strlen($useragent) > 0) curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    if (isset($request_header) && !empty($request_header)) curl_setopt($ch, CURLOPT_HTTPHEADER, $request_header);
    $output_stream = curl_exec($ch);
    $info = curl_getinfo($ch);
    $http_code = $info['http_code'];
    $header_size = $info['header_size'];
    $header = substr($output_stream, 0, $header_size);
    $content = substr($output_stream, $header_size);
    $encoding = mb_detect_encoding($content,array('UTF-8','ASCII','EUC-CN','CP936','BIG-5','GB2312','GBK'));
    if ($encoding && strcasecmp($encoding, 'UTF-8') !== 0) $content = @mb_convert_encoding($content,'UTF-8',array('ASCII','EUC-CN','CP936','BIG-5','GB2312','GBK'));
    curl_close($ch);

    return array('code' => $http_code, 'header' => $header, 'content' => $content, 'encoding' => $encoding);
}

/* *
 * 获取Set-Cookie串
 * @return array | string 默认返回字符串
 */
function getCookies($response_header, $return_type = false){
    preg_match_all('/Set-Cookie: (.+)path=\//i', $response_header, $matches);
    if ($return_type){
        $cookies = array();
        foreach($matches[1] as $match){
            $name = strstr($match, '=', true);
            $value = ltrim(strstr($match, '='), '=');
            $cookies["$name"] = $value;
        }
        return $cookies;
    }
    return implode($matches[1]);
}

function getLocation($header){
    preg_match('/Location: (.+)/', $header, $match);
    return trim($match[1]);
}

/* *
 * 截取字符串
 */
function cutstr($haystack, $path, $format){
    switch($format){
        case 'xml' :
            $obj = simplexml_load_string($haystack);
            return eval('return $obj->'.$path.';');
        case 'json' :
            $obj = json_decode($haystack);
            return eval('return $obj->'.$path.';');
        default :
            if(strpos($haystack, $path) === FALSE) return '';
            return strstr(substr($haystack, strpos($haystack, $path) + strlen($path)), $format, true);
    }
}

/* *
 * 获取表单内容
 */
function getFormData($content, $return_type = false, $out_charset = 'utf-8', $exclude_names = '', $exclude_types = "submit"){
    // if (strcasecmp($out_charset, 'utf-8') !== 0) $content = mb_convert_encoding($content, $out_charset, 'UTF-8');
    if (!preg_match_all('/<input.+>/iU', $content, $matches) || count($matches) === 0) return false;
    $exclude_types = explode('|', $exclude_types);
    $exclude_names = explode('|', $exclude_names);
    $inputs = $matches[0];
    $params = array();
    foreach($inputs as $input){
        $input = str_replace('\"', '"', $input); // 广东移动特殊化
        if (!preg_match('/type *= *["\']{0,1}(?<m>[^"\' ]*)["\']{0,1}/i', $input, $matches)) continue;
        $type = $matches['m'];
        if (in_array($type, $exclude_types)) continue;
        if (!preg_match('/name *= *["\']{0,1}(?<m>[^"\' ]*)["\']{0,1}/i', $input, $matches)) continue;
        $name = $matches['m'];
        if (in_array($name, $exclude_names)) continue;
        $value = "";
        if (preg_match('/value *= *["\']{0,1}(?<m>[^"\']*)["\']{0,1}/i', $input, $matches)) {
            $value = html_entity_decode($matches['m']);
            if (strcasecmp($out_charset, 'utf-8') !== 0) $value = mb_convert_encoding($value, $out_charset, 'UTF-8');
        }
        $params["$name"] = $value;
    }
    if($return_type) return $params;
    return http_build_query($params);
}

/* *
 * 输出xml格式结果 (仅一维数组)
 */
function outputXML($datas){
    header('Content-type:application/xml;charset=utf-8');
    if(!is_array($datas)) return '<?xml version="1.0" encoding="utf-8"?><datas>'.htmlspecialchars($datas).'</datas>';
    $xml = simplexml_load_string('<?xml version="1.0" encoding="utf-8"?><datas></datas>');
    foreach($datas as $key => $value){
        $value = htmlspecialchars($value);
        $xml->addChild($key, $value);
    }
    return $xml->asXML();
}

/* *
 * 悠悠云验证码识别
 * 根据图片路径上传, 返回验证码在服务器的ID, 根据验证码ID获取识别结果
 * 常用1004, 1005, 1900,取值查看：http://www.uuwise.com/price.html
 */
function autoRecognition($imgage_url, $code_type = '1004', $cookie = '', $referer = '', $header = null){
    $image_result = curlResponse($imgage_url, false, null, $cookie, false, $referer, '', $header);
    $cookie.= getCookies($image_result['header']);
    $header = array(
        'SID: 90054', // $softID
        'HASH: 926ee2922dd3e92fae46eea0d2d5aef8', // $uhash = md5($softId.strtoupper($softKEY));
        'UUVersion: 1.1.1.3',
        'UID: 5737', // $_SESSION['uid']
        'User-Agent: 3e45ae3c17259ee850e5405d0cd4594d', // md5(strtoupper($softKEY.$userName)).$macAddress;
    );
    $userKey = "5737_JIEYITONG_C3-1D-53-B7-AF-D1-3C-93-A0-FE-B1-2A-37-B7-73-7D_6C-64-0F-C2-FF-97-AE-10-4D-15-F9-8D-14-91-72-18-57-68-DF-2F"; // $_SESSION['userKey']
    $postdata = array(
        "filename=\"C:/temp.png\"" => $image_result['content'], // 'img'=>'@'.realpath($saveImgPath),
        // 'img' => '@'.realpath("C:\\Users\\Yang\\Desktop\\test.jpg"),
        'key' => $userKey,
        'sid' => '90054', // $softID
        'skey' => 'a6f8caf5613ab9286e8e0c76097a2cff', // $softContentKEY=md5(strtolower($userKey.$softID.$softKEY));
        'Type' => $code_type,
        'GUID' => 'dd53c34ab76002d04abfe763482ac927',
        'Version' => 100,
    );

    $vcode = curlResponse('http://upload.uuwise.net:9000/Upload/Processing.aspx', 1, $postdata, null, 0, null, '', $header);
    $vcodeID = $vcode['content'];
    if (strpos($vcodeID, '|') !== false){
        $vcode_info = explode('|', $vcodeID);
        return array('vcode' => $vcode_info[1], 'cookie' => $cookie);
    }

    do{
        $result = curlResponse('http://upload.uuwise.net:9000/Upload/GetResult.aspx?KEY='.$userKey.'&ID='.$vcodeID.'&Random='.mktime(time()));
        usleep(100000);
    }while($result['content'] == '-3' || strlen($result['content']) === 0);

    return array('vcode' => $result['content'], 'cookie' => $cookie);
}

/* 获取命令行参数 */
function getArgument($argument){
    $temp = explode('&', $argument);
    foreach($temp as $val){
        $val = explode('=',$val);
        $name = $val[0];
        $value = $val[1];
        $_GET["$name"] = $value;
    }
}

/* 把一个汉字转为unicode */
function getUnicodeFromOneUTF8($word) {
    $array = str_split($word);
    $bin_str = '';
    foreach($array as $val) $bin_str.= decbin(ord($val));  // chr(int $ascii)  ord(string $char)
    $bin_str = preg_replace('/^.{4}(.{4}).{2}(.{6}).{2}(.{6})$/','$1$2$3', $bin_str); // 1110(0100)10(111101)10(100000) => 0100 111101 100000
    return bindec($bin_str);   			//返回类似20320， 汉字"你"
    return dechex(bindec($bin_str)); 	//返回十六进制4f60，
}

/* 将字符串转换成JS的escape编码 */
function escape($word){
    $result = '';
    for($i = 0, $len = strlen($word); $i < $len; $i++){
        if(ord($word[$i]) >= 127) {
            $temp = bin2hex(iconv('utf-8', 'ucs-2', substr($word, $i, 3)));
            $result.= '%u'.$temp;
            $i = $i + 2;
        } else {
            $result.= '%'.dechex(ord($word[$i]));
        }
    }
    return $result;
}

/* 将JS的escape编码反转, JS escape编码统一为utf-8 */
function unescape($str){
    $ret = '';
    for($i = 0, $len = strlen($str); $i < $len; $i++){
        if($str[$i] == '%' && $str[$i + 1] == 'u'){
            $val = hexdec(substr($str, $i + 2, 4));
            if($val < 0x7f) $ret .= chr($val);
            elseif($val < 0x800) $ret .= chr(0xc0 | ($val >> 6)) . chr(0x80 | ($val & 0x3f));
            else $ret .= chr(0xe0 | ($val >> 12)) . chr(0x80 | (($val >> 6) & 0x3f)) . chr(0x80 | ($val & 0x3f));
            $i += 5;
        } elseif($str[$i] == '%') {
            $ret .= urldecode(substr($str, $i, 3));
            $i += 2;
        } else {
            $ret .= $str[$i];
        }
    }
    return $ret;
}

/* 防SQL注入攻击 */
function inject_check($rearray){
    $preg = '/select|insert|update|delete|\'|\\*|\*|\.\.\/|\.\/|union|into|load_file|outfile|\$\{|\%24\{|\$\%7b|\%24\%7b/i';
    foreach($rearray as $key => $value){
        if(strcmp($key, "cookie") === 0) continue;

        if(is_array($value)){
            foreach($value as $key => $val){
                $check = preg_match($preg, $key); // 进行过滤
                if(!$check) $check = preg_match($preg, $val); // 进行过滤
            }
        } else {
            $check = preg_match($preg, $value); // 进行过滤
        }

        if($check){
            die();
        }
    }
}

function quickSort($param){

    //如果个数不大于一，直接返回
    if(count($param) <= 1) return $param;

    //取一个值，稍后用来比较；
    $key = $param[0];
    $left_arr = array();
    $right_arr = array();

    //比$key大的放在右边，小的放在左边；
    for($i = 1; $i < count($param); $i++){
        if($param[$i] <= $key)
            $left_arr[] = $param[$i];
        else
            $right_arr[] = $param[$i];
    }

    //进行递归；
    $left_arr = quicksort($left_arr);
    $right_arr = quicksort($right_arr);

    //将左中右的值合并成一个数组；
    return array_merge($left_arr, array($key), $right_arr);
}

/**
 * 远程下载
 * @param string $remote 远程图片地址
 * @param string $local 本地保存的地址
 * @param string $cookie cookie地址 可选参数由
 * 于某些网站是需要cookie才能下载网站上的图片的
 * 所以需要加上cookie
 * @return void
 * @author andy
 */
function reutersload($remote, $local, $cookie= '') {
    $cp = curl_init($remote);
    $fp = fopen($local,"w");
    curl_setopt($cp, CURLOPT_FILE, $fp);
    curl_setopt($cp, CURLOPT_HEADER, 0);
    if($cookie != '') {
        curl_setopt($cp, CURLOPT_COOKIEFILE, $cookie);
    }
    curl_exec($cp);
    curl_close($cp);
    fclose($fp);
}

/*
 * 计算四则运算表达式问号处的值
 * @param expression 四则运算表达式
 */
function calc_verify_expression($expression){
    if(!preg_match_all('/^(?<x>\?|\d+)(?<symbol>\+|\-|\*|\/)(?<y>\?|\d+)#(?<z>\?|\d+)$/', $expression, $matches)) return false;
    $x = $matches['x'][0];
    $y = $matches['y'][0];
    $z = $matches['z'][0];
    $symbol = $matches['symbol'][0];
    if (strcasecmp($z, '?') === 0) $result = eval("return $x$symbol$y;");
    else if (strcasecmp($x, '?') === 0) {
        if (strcasecmp($symbol, '-') === 0) $symbol = '+';
        else if (strcasecmp($symbol, '/') === 0) $symbol = "*";
        else if (strcasecmp($symbol, '+') === 0) $symbol = "-";
        else if (strcasecmp($symbol, '*') === 0) $symbol = "/";
        $result = eval("return $z$symbol$y;");
    }else {
        if (strcasecmp($symbol, '+') === 0) $result = eval("return $z - $x;");
        else if (strcasecmp($symbol, '*') === 0) $result = eval("return $z / $x;");
        else if (strcasecmp($symbol, '-') === 0) $result = eval("return $x - $z;");
        else if (strcasecmp($symbol, '/') === 0) $result = eval("return $x / $z;");
    }
    return $result;
}

/* -------------------------------------------------------
 * MySQL Function
 * -------------------------------------------------------
 */
function wk_mysql_connect() {
    $conn = mysql_connect(MYSQL_HOST . ':' . MYSQL_PORT, MYSQL_USER, MYSQL_PASS);
    if (!$conn) return false;
    mysql_select_db(MYSQL_DB, $conn);
    mysql_query("SET NAMES utf8", $conn);
    return $conn;
}

// MYSQL查询返回多条记录做成一个二维数组
function wk_mysql_list($sql, $conn = null, $node = "list") {
    if (!isset($conn)) $conn = wk_mysql_connect();
    if (!$conn) return false;
    $result = mysql_query($sql, $conn);
    if (!$result) return false;
    $rc = mysql_num_rows($result);
    $arr = array('count' => $rc);
    while ($row = mysql_fetch_assoc($result)) {
        $arr[$node][] = $row;
    }
    return $arr;
}

// MYSQL查询返回单个记录做成一个一维数组
function wk_mysql_single($sql, $conn = null, $paging = null) {
    if (!isset($conn)) $conn = wk_mysql_connect();
    if (!$conn) return false;
    $result = mysql_query($sql, $conn);
    if (!$result) return false;
    $verb = substr($sql, 0, 6);
    $is_select = strcasecmp($verb, "select") === 0;
    $rc = $is_select ? mysql_num_rows($result) : mysql_affected_rows($conn);
    $row = mysql_fetch_assoc($result);
    $arr = array('count' => $rc);
    if ($rc > 0 && is_array($row)) $arr = array_merge($arr, $row);
    return $arr;
}

//启动事务
function begin_tran($conn) {
    if (!mysql_query("SET AUTOCOMMIT = 0", $conn))
        return "启动事务前出错";
    if (!mysql_query("BEGIN"))
        return "启动事务出错";
    return true;
}

//提交事务
function commit_tran($conn) {
    if (!mysql_query("COMMIT"))
        return "提交事务出错";
    if (!mysql_query("SET AUTOCOMMIT = 1", $conn))
        return "提交事务后出错";
    return true;
}

/**
 * 检查事务
 *
 * @param string $sql
 *        	当前sql
 * @param mixed $conn
 *        	当前连接
 * @param array $result
 *        	记录集
 * @param int $count_expected
 *        	预期更新条数
 * @return bool 成功或失败
 */
function check_tran($sql, $conn, $result, $count_expected = 0) {
    $ret = array('success' => true);
    if (!$result) {
        $ret['success'] = false;
        $ret['msg'] = mysql_error($conn);
    } elseif ($count_expected > 0 && ($result['count'] != $count_expected)) {
        $ret['success'] = false;
        $ret['msg'] = "执行的记录数:$result[count]与遇期值{$count_expected}不符";
    }

    if (!$ret['success']) {
        mysql_query('ROLLBACK', $conn);
        return $ret;
    }

    return $ret['success'];
}

// -------------------------------------------------------
// Image Functions
// -------------------------------------------------------
/**
 *    打印水印图片
 * @param String $dst_path 要打水印的图片路径
 * @param String $water_path 水印图片路径
 * @param int $position 水印图片打印位置 1.右下角 2.左下角 默认为右下角
 * @param String $dir 保存文件夹    默认为当前文件目录子目录images
 * @param String $fix 打水印标志    默认为'w_'
 * @return String $file 保存文件所在路径
 * @author S10_YKW 2014/03/10
 */
function waterImage($dst_path, $water_path, $position = 1, $dir = './images/', $fix = 'w_'){

    $dst_image = getSrcImage($dst_path);
    $water_image = getSrcImage($water_path);

    $src_w = imagesX($water_image);
    $src_h = imagesY($water_image);

    $dst_w = $src_w;
    $dst_h = $src_h;

    $width = imagesX($dst_image);
    $height = imagesY($dst_image);

    switch ($position) {
        case 1:
            $dst_x = $width - $dst_w;
            $dst_y = $height - $dst_h;
            break;
        case 2:
            $dst_x = 0;
            $dst_y = $height - $dst_h;
            break;
        default:
            return false;
    }

    //拷贝水印
    imageCopyResampled($dst_image, $water_image, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);

    //header('Content-Type:image/jpg');
    $basename = basename($dst_path);
    $file = rtrim($dir, '/') . '/' . $fix . $basename;
    saveDstImage($dst_image, $file);
    //imageJPEG($dst_image,$file);

    imageDestroy($dst_image);
    imageDestroy($water_image);
    return true;
}

/**
 * 压缩图片函数----居中留旁白显示
 * @param String $path 源图文件路径
 * @param int $width 压缩后宽度
 * @param int $height 压缩后高度
 * @param string $dir 压缩后保存目录
 * @param string $fix 添加压缩标志
 * @return string $file    返回图片压缩后保存路径
 * @anthor S10_YKW 2014/03/10
 *
 */
function zoomImage($path, $width = 300, $height = 300, $dir = './images/', $fix = 're_'){

    $dst_image = imageCreateTrueColor($width, $height);
    if (!file_exists($path)) return false;
    $src_image = getSrcImage($path);
    $white = imageColorAllocate($dst_image, 255, 255, 255);
    imageFill($dst_image, 0, 0, $white);

    $src_w = imagesX($src_image);
    $src_h = imagesY($src_image);

    $pre_dst = $width / $height;
    $pre_src = $src_w / $src_h;

    if ($pre_dst < $pre_src) {
        $dst_w = $width;                        //明显高 确定宽
        $dst_h = $src_h * $dst_w / $src_w;

        $dst_x = 0;                             //居中显示
        $dst_y = ($height - $dst_h) / 2;
    } else {
        $dst_h = $height;                       //明显宽 确定高
        $dst_w = $src_w * $dst_h / $src_h;

        $dst_y = 0;
        $dst_x = ($width - $dst_w) / 2;         //居中显示
    }

    //缩放目标图片
    imageCopyResampled($dst_image, $src_image, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);

    $basename = basename($path);
    if (!file_exists($dir)) mkdir($dir, 0777, true);

    $file = rtrim($dir, '/') . '/' . $fix . $basename;
    saveDstImage($dst_image, $file);
    imageDestroy($dst_image);
    imageDestroy($src_image);

    return $file;
}

/**
 * 压缩图片函数----固定高宽显示
 * @param String $path 源图文件路径
 * @param int $width 压缩后宽度
 * @param int $height 压缩后高度
 * @param string $dir 压缩后保存目录
 * @param string $fix 添加压缩标志
 * @return string $file    返回图片压缩后保存路径
 * @anthor S10_YKW 2014/03/11
 *
 */
function zoomImageFix($path, $width, $height, $dir = './images/', $fix = 're_'){

    $dst_image = imageCreateTrueColor($width, $height);
    if (!file_exists($path)) return false;
    $src_image = getSrcImage($path);

    $white = imageColorAllocate($dst_image, 255, 255, 255);
    imageFill($dst_image, 0, 0, $white);
    $width = imagesX($dst_image);
    $height = imagesY($dst_image);

    $src_im_w = imagesX($src_image);
    $src_im_h = imagesY($src_image);

    $pre_dst = $width / $height;
    $pre_src = $src_im_w / $src_im_h;
    if ($pre_src > $pre_dst) {                        //源图像明显宽
        $src_h = $src_im_h;
        $src_w = $width * $src_h / $height;
        $src_x = ($src_im_w - $src_w) / 2;
        $src_y = 0;
    } else {                                        //源图明显高
        $src_w = $src_im_w;
        $src_h = $src_w * $height / $width;
        $src_x = 0;
        $src_y = ($src_im_h - $src_h) / 2;
    }

    imageCopyReSampled($dst_image, $src_image, 0, 0, $src_x, $src_y, $width, $height, $src_w, $src_h);

    $basename = basename($path);
    if (!file_exists($dir)) mkdir($dir, 0777, true);

    $file = rtrim($dir, '/') . '/' . $fix . $basename;
    saveDstImage($dst_image, $file);
    imageDestroy($dst_image);
    imageDestroy($src_image);
    return $file;
}

/*
 * Arithmetic Functions
 */
/**
 *数字金额转换成中文大写金额的函数
 *String Int $num 要转换的小写数字或小写字符串
 *return 大写字母
 *小数位为两位
 **/
function getFormatCH($num){
    $ch_num = "零壹贰叁肆伍陆柒捌玖";
    $ch_unit = "分角元拾佰仟万拾佰仟亿";
    $num = str_replace(",", "", $num);
    $num = round(bcmul("$num", "100"), 0);
    $ch_str = "";
    $i = 0;
    while ($num > 0) {
        $n = bcmod("$num", "10");
        $ch_str = mb_substr($ch_num, $n, 1, "utf-8"). mb_substr($ch_unit, $i, 1, "utf-8"). $ch_str;
        $num = (int)(bcdiv("$num", "10"));
        $i++;
    }
    $search = array("零万", "零仟", "零佰", "零拾", "零元", "零零", "零万", "亿万");
    $replace = array("万",  "零", "零", "", "元", "零", "万", "亿");
    $ch_str = str_replace($search, $replace, $c);
    if (strlen($ch_str) > 0) $ch_str.= "整";
    return $ch_str;
}