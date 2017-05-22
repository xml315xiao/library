<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('curl_request'))
{
    /**
     * CURL调用外部接口 支持http 和 https
     * @param string         $url
     * @param bool           $method GET=>FALSE | POST=>TRUE 默认GET请求
     * @param string|array   $data
     * @param array          $header  头信息配置
     * @param int            $timeout 最大响应时长
     * @return string
     */
    function curl_request($url, $method = FALSE, $data = NULL, $header = array(), $timeout = 30)
    {
        if (strlen(trim($url)) === 0) {
            return FALSE;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, $method ?  TRUE : FALSE);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        if (isset($data) && ! empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        if (isset($header) && count($header) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        $content = curl_exec($ch);
        $encoding = mb_detect_encoding($content, array('UTF-8','ASCII','EUC-CN','CP936','BIG-5','GB2312','GBK'));
        if ($encoding && strcasecmp($encoding, 'UTF-8') !== 0) {
            $content = @mb_convert_encoding($content,'UTF-8',array('ASCII','EUC-CN','CP936','BIG-5','GB2312','GBK'));
        }

        curl_close($ch);

        return $content;
    }

}

if ( ! function_exists('random_ip'))
{
    /**
     * 国内IP地址随机生成器
     *
     */
    function random_ip()
    {
        $ip = [
            ['607649792',  '608174079'],     // 36.56.0.0-36.63.255.255
            ['1038614528', '1039007743'],    // 61.232.0.0-61.237.255.255
            ['1783627776', '1784676351'],    // 106.80.0.0-106.95.255.255
            ['2035023872', '2035154943'],    // 121.76.0.0-121.77.255.255
            ['2078801920', '2079064063'],    // 123.232.0.0-123.235.255.255
            ['-1950089216', '-1948778497'],  // 139.196.0.0-139.215.255.255
            ['-1425539072', '-1425014785'],  // 171.8.0.0-171.15.255.255
            ['-1236271104', '-1235419137'],  // 182.80.0.0-182.92.255.255
            ['-770113536',  '-768606209'],   // 210.25.0.0-210.47.255.255
            ['-569376768',  '-564133889'],   // 222.16.0.0-222.95.255.255
        ];

        $index = mt_rand(0, 9);
        return long2ip(mt_rand($ip[$index][0], $ip[$index][1]));
    }
}

if ( ! function_exists('fetch_client_ip'))
{
    /**
     * 获取客户端IP地址
     */
    function fetch_client_ip()
    {
        $CI =& get_instance();
        if ( !empty($CI->input->server('HTTP_CLIENT_IP'))) {
            return $CI->input->server('HTTP_CLIENT_IP');
        } elseif( !empty($CI->input->server('HTTP_X_FORWARDED_FOR'))) {
            return $CI->input->server('HTTP_X_FORWARDED_FOR');
        } else {
            return $CI->input->server('REMOTE_ADDR');
        }
    }
}

if ( ! function_exists('fetch_ip_info'))
{
    /**
     * 调用新浪公共接口获取IP地址详情
     * @param  $ip
     * @return array
     */
    function fetch_ip_info($ip)
    {
        // ip 地址规则校验  剔除 127.0.0.1
        if (check_format(trim($ip), 'ip') === false || trim($ip) == '127.0.0.1') {
            return "IP 地址不正确";
        }

        $url = 'http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=';
        $random_ip = random_ip();
        $header = ['CLIENT-IP:'. $random_ip, 'X-FORWARDED-FOR:'. $random_ip];
        $result = curl_request($url. $ip, FALSE, NULL, $header);
        $result = json_decode($result);

        return $result;
    }
}

if ( ! function_exists('check_format'))
{
    /**
     * 校验数据格式
     * @param $value    string
     * @param $format   string
     * @param $regexp   string 自定义正则表达式
     * @return bool
     */
    function check_format($value, $format = 'regexp', $regexp = "")
    {
        $res = NULL;
        $format = strtolower($format);
        switch ($format) {
            case 'email' :
                $res = preg_match('/^\w+(\.\w+)*@\w+(\.\w+)+$/', $value);
                break;
            case 'url' :
                $res = preg_match('/^(http:\/\/)?(https:\/\/)?([\w\d-]+\.)+[\w-]+(\/[\d\w-.\/?%&=]*)?$/i', $value);
                break;
            case 'ip' :
                $res = preg_match('/^(25[0-5]|2[0-4][0-9]|[0-1]{0,1}[0-9]{1,2})\.(25[0-5]|2[0-4][0-9]|[0-1]{0,1}[0-9]{1,2})\.(25[0-5]|2[0-4][0-9]|[0-1]{0,1}[0-9]{1,2})\.(25[0-5]|2[0-4][0-9]|[0-1]{0,1}[0-9]{1,2})$/', $value);
                break;
            case 'int' :
                $res = preg_match('/^\d*$/', $value);
                break;
            case 'bool' :
            case 'boolean' :
                $res = preg_match('/^(0|1)$/', $value);
                break;
            // 以大小写字母开头 6 ~ 12 位
            case 'username' :
                $res = preg_match('/^[a-zA-Z]\w{5,11}$/iu', $value);
                break;
            // 非空字符 6 ~ 12 位，必须包含特殊字符、数字、大小写字母
            case 'password' :
                $res = preg_match('/^\S{6,12}$/', $value);
                if ($res === 1) {
                    $res = preg_match('/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*+-])/', $value);
                }
                break;
            case 'idcard' :
                preg_match('/^\d{6}((1[789])|(20))\d{2}(0\d|1[0-2])([0-2]\d|3[01])(\d{3}(\d|X))$/', $value);
                break;
            case 'postcode' :
                preg_match('/^[1-9]\d{5}$/', $value);
                break;
            // 移动：134（不含1349）、135、136、137、138、139、147、150、151、152、157、158、 159、182、183、184、187、188、178
            // 联通：130、131、132、145（上网卡）、155、156、185、186、176
            // 电信：133、1349（卫星通信）、153、180、181、189、177、173
            // 4G : 176(联通)、173/177(电信)、178(移动)
            case 'mobile' :
                $res = preg_match('/^(\(86\))?[0]?((1[358][0-9]{9})|(147[0-9]{8})|(17[3678][\d]{8}))$/', $value);
                break;
            // (010) 12345678 (0572)12435689 0571-12345678 0755 12345678 021--12345678
            case 'phone'  :
                $res = preg_match('/^(\(0\d{2,3}\)|(0\d{2,3}))([ ]?[-]{0,2}[ ]?)([1-9][0-9]{6,7})$/', $value);
                break;
            // 1970-01-01 23:59:59 1970/01/01 23:59 1970-1-31
            case 'datetime' :
                $res = preg_match('/^(19|20)[0-9]{2}(\-|\/)([0]{0,1}[0-9]|1[0-2])(\\2)([0-2]{0,1}[0-9]|3[0-1])(\s+(([01][0-9])|(2[0-3])):([0-5][0-9])(:[0-5][0-9])?)$/', $value);
                break;
            case 'money' :
                $res = preg_match('/^([1-9]{1}\d*|[0]{1})(\.([0-9]{1,2}))?/', $value);
                break;
            case 'token' :
                $res = preg_match('/^[0-9a-f]{32}$/i', $value);
                break;
            default :
                $res = preg_match($regexp, $value);
                break;
        }

        return boolval($res);
    }
}



