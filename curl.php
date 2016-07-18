<?php

class CURL
{
    protected $ch = NULL;
    protected $options = array();
    protected $headers = array();

    protected $response;
    protected $info;

    public function __construct($url = '')
    {
        function_exists('curl_init') || die('CURL Library Not Loaded');
        $url &&  $this->ch = curl_init($url);
        $this->options = array(
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HEADER => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_FAILONERROR => TRUE,
            CURLOPT_FOLLOWLOCATION => TRUE,
        );
    }

    public function get($url, $params = array(), $options = array())
    {
        $this->ch = curl_init($url.($params ? '?'.http_build_query($params, NULL, '&') : ''));
        $this->options($options);
        return $this->request();
    }

    public function post($url, $params = array(), $options = array())
    {
        $this->ch = curl_init($url);
        // If its an array (instead of a query string) then format it correctly
        if (is_array($params)) {
            $params = http_build_query($params, NULL, '&');
        }
        // Add in the specific options provided
        $this->options($options);
        $this->option(CURLOPT_POST, TRUE);
        $this->option(CURLOPT_POSTFIELDS, $params);
        return $this->request();
    }

    public function http_cookies($params = array())
    {
        if (is_array($params)) {
            $params = http_build_query($params, NULL, ';');
        }
        $this->option(CURLOPT_COOKIE, $params);
    }

    public function http_header($header, $content = NULL)
    {
        $this->headers[] = $content ? $header . ': ' . $content : $header;
    }

    /**
     * 获取Set-Cookies
     * @param bool $return_type 返回array 设置false
     * @param array $keys 返回指定Cookie值
     * @return array | string 默认返回字符串
     */
    public function get_cookies($return_type = true, $keys = array()){
        preg_match_all('/Set-Cookie: (?<m>.+)[;](.+)path=\//iU', $this->response['header'], $matches);
        if (!sizeof($keys) > 0 && $return_type === true) // 0 0
            return implode($matches[1]);
        $cookies = array();
        if (sizeof($keys) > 0) {
            foreach($matches['m'] as $match) {
                $name = strstr($match, '=', true);
                if (!in_array($name, $keys)) continue;
                $value = ltrim(strstr($match, '='), '=');
                $cookies["$name"] = $value;
            }
        } else {
            foreach($matches['m'] as $match) {
                $name = strstr($match, '=', true);
                $value = ltrim(strstr($match, '='), '=');
                $cookies["$name"] = $value;
            }
        }
        if ( ! $return_type) {
            return $cookies;
        } else {
            return http_build_query($cookies, NULL, ';');
        }
    }

    /**
     * HTTP REQUEST EXECUTE
     * @return mixed
     */
    public function request()
    {
        // Set two default options, and merge any extra ones in
        if ( ! empty($this->headers)) {
            $this->option(CURLOPT_HTTPHEADER, $this->headers);
        }
        $this->options();

        // Execute the request & and hide all output
        $output = curl_exec($this->ch);
        $this->info = curl_getinfo($this->ch);
        $this->response['code'] = $this->info['http_code'];
        $this->response['header'] = substr($output, 0, $this->info['header_size']);
        $this->response['body'] = @mb_convert_encoding(substr($output, $this->info['header_size']), 'UTF-8',
            array('ASCII','EUC-CN','CP936','BIG-5','GB2312','GBK'));
        curl_close($this->ch);

        return $this->response['body'];
    }

    public function options($options = array())
    {
        foreach ($options as $option_code => $option_value) {
            $this->option($option_code, $option_value);
        }

        curl_setopt_array($this->ch, $this->options);
        return $this;
    }
    public function option($code, $value)
    {
        if (is_string($code) && !is_numeric($code)) {
            $code = constant('CURLOPT_' . strtoupper($code));
        }
        $this->options[$code] = $value;
        return $this;
    }
}
