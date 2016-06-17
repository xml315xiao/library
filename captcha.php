<?php

/**
 * 自定义验证码类
 *
 * @create-time 2016-06-01 16:46:31
 * @author  Mickle Yang <YuanLong>
 */
class Captcha
{
    private $image;
    private $width;
    private $height;
    private $length;
    private $code = '';
    private $font;
    private $font_size;
    private $style;
    private $pool;
    private $img_path;
    private $img_url;
    private $filename;
    private $expiration;

    /**
     * Captcha constructor.
     *
     * @param array $attributes config for the CAPTCHA
     * eg: 'width'=>200,'height'=>30,'length'=>5,'font'=>...
     */
    public function __construct($attributes = array())
    {

        $default = [
            'width' => 150,
            'height' => 40,
            'length' => 4,
            'font_size' => 20,
            'style' => 1,
            'expiration' => 300,
            'img_path' => 'public/captcha/',
            'img_url' =>  'public/captcha/',
            'font' => 'public/simhei.ttf',
        ];
        foreach ($default as $key => $value) {
            if (is_array($attributes) && isset($attributes[$key]))
                $this->$key = $attributes[$key];
            else
                $this->$key = $value;
        }

        $this->setPool();
        $this->image = imagecreatetruecolor($this->width, $this->height);
    }

    /**
     * 生成验证码
     *
     * @return array ['code','image','filename'] if success
     * ortherwise return false
     *
     */
    public function createCaptcha()
    {

        if ( ! is_file($this->font) || $this->img_path === '' || $this->img_url === ''
            || !is_dir($this->img_path) || ! is_writable($this->img_path)
        )
            return false;

        $this->_createCode();
        $this->_drawBackground();
        $this->_drawCode();
        $this->_drawLines();
        $this->_drawSnowflakes();
        $this->_generateImage();

        return array(
            'code' => $this->code,
            'image' => $this->img_url . $this->filename,
            'filename' => $this->filename,
        );
    }

    /**
     * 生成原始字符串
     */
    private function setPool()
    {
        $str_int = '0123456789';
        $str_A = 'ABCDEFGHIJKLMNPQRSTUVWXYZ';
        $str_a = 'abcdefghijkmnpqrstuvwxyz';

        switch ($this->style) {
            case 1 :
                $str = $str_int . $str_A . $str_a;
                break;
            case 2 :
                $str = $str_int;
                break;
            case 3 :
                $str = $str_A . $str_a;
                break;
            default :
                $str = $str_int . $str_A . $str_a;
                break;
        }

        $this->pool = $str;
    }

    /**
     * 获取生成固定长度的字符串
     *
     */
    private function _createCode()
    {
        $pool_lenth = strlen($this->pool);
        for ($i = 0; $i < $this->length; $i++) {
            $this->code .= $this->pool[mt_rand(0, $pool_lenth - 1)];
        }
    }

    /**
     * 生成画布并且初始化背景
     *
     */
    private function _drawBackground()
    {
        $color = imagecolorallocate($this->image, mt_rand(157, 255), mt_rand(157, 255), mt_rand(157, 255));
        ImageFilledRectangle($this->image, 0, 0, $this->width, $this->height, $color);
    }

    /**
     * 逐个描绘字符
     *
     */
    private function _drawCode()
    {
        $_x = $this->width / $this->length;
        for ($i = 0; $i < $this->length; $i++) {
            $x = $_x * $i + mt_rand(1, 5);
            $y = $this->height / 1.4;
            $color = imagecolorallocate($this->image, mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
            imagettftext($this->image, $this->font_size, mt_rand(-30, 30), $x, $y, $color, $this->font, $this->code[$i]);
        }
    }

    /**
     * 生成干扰线条
     *
     */
    private function _drawLines()
    {
        for ($i = 0; $i < 6; $i++) {
            $color = imagecolorallocate($this->image, mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
            imageline($this->image, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height), $color);
        }
    }

    /**
     * 生成干扰雪花
     */
    private function _drawSnowflakes()
    {
        $random = mt_rand(20, 60);
        for ($i = 0; $i < $random; $i++) {
            $color = imagecolorallocate($this->image, mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
            imagestring($this->image, mt_rand(1, 5), mt_rand(0, $this->width), mt_rand(0, $this->height), '*', $color);
        }
    }

    /**
     * 清理当前路径下的过期图片并且生成图片文件
     *
     */
    private function _generateImage()
    {
        $now = microtime(true);
        $current_dir = @opendir($this->img_path);
        while ($filename = @readdir($current_dir)) {
            if (substr($filename, -5) === '.jpeg' && (str_replace('.jpeg', '', $filename) + $this->expiration) < $now)
                @unlink($this->img_path . $filename);
        }

        @closedir($current_dir);

        $this->filename = $now . '.jpeg';
        imagejpeg($this->image, $this->img_path . $this->filename);
    }

    public function __destruct()
    {
        imagedestroy($this->image);
    }
}
