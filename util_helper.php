<?php  defined('BASEPATH') OR exit('No direct script access allowed');
if ( ! function_exists('fetch_ip'))
{
    /**
     * 获取客户端IP地址
     */
    function fetch_ip()
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
