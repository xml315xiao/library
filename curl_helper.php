<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('curl_request'))
{
    /**
     * curl request http & https
     * @param string         $url
     * @param bool           $method TRUE 'POST' ELSE FALSE 'GET'
     * @param string|array   $data
     * @param string         $cookie
     * @param bool           $redirect
     * @param string         $referer
     * @param string         $useragent
     * @param array          $header other headers config
     * @param int            $timeout max time exec
     * @return array | bool
     */
    function curl_request($url, $method = FALSE, $data = NULL, $cookie = '', $redirect = FALSE,
                          $referer = '', $useragent = '', $header = array(), $timeout = 30)
    {
        if (strlen(trim($url)) === 0) {
            return FALSE;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT, max($timeout, 30));
        curl_setopt($ch, CURLOPT_POST, $method ?  TRUE : FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $redirect ? TRUE : FALSE);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

        if (isset($data) && ! empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        if (isset($cookie) && strlen($cookie) > 0) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        if (isset($referer) && strlen($referer) > 0) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        if (isset($useragent) && strlen($useragent) > 0) {
            curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        }
        if (isset($header) && count($header) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        $code = intval($info['http_code']);
        $header_size = $info['header_size'];

        $header = substr($output, 0, $header_size);
        $content = substr($output, $header_size);
        $encoding = mb_detect_encoding($content,array('UTF-8','ASCII','EUC-CN','CP936','BIG-5','GB2312','GBK'));
        if ($encoding && strcasecmp($encoding, 'UTF-8') !== 0) {
            $content = @mb_convert_encoding($content,'UTF-8',array('ASCII','EUC-CN','CP936','BIG-5','GB2312','GBK'));
        }
        curl_close($ch);

        return array(
            'code' => $code,
            'header' => $header,
            'content' => $content,
            'encoding'=> $encoding,
        );
    }
}
