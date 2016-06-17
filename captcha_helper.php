<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('create_captcha'))
{

    /**
     * Captcha constructor.
     *
     * @param array $attributes config for the CAPTCHA
     * eg: 'width'=>200,'height'=>30,'length'=>5,'font'=>...
     *
     * @return bool false or array if success.
     */
    function create_captcha($attributes = array())
    {
        // Set config for the CAPTCHA
        $default = [
            'width' => 150,
            'height' => 40,
            'length' => 4,
            'font_size' => 20,
            'style' => 1,
            'expiration' => 300,
            'img_path' => 'public/captcha/',
            'img_url' => 'public/captcha/',
            'font' => 'public/simhei.ttf',
        ];
        foreach ($default as $key => $value) {
            if (is_array($attributes) && isset($attributes[$key]))
                $$key = $attributes[$key];
            else
                $$key = $value;
        }

        // Check for config.
        if (! is_file($font) || $img_path === '' || $img_url === ''
            || !is_dir($img_path) || ! is_writable($img_path)
            || !extension_loaded('gd')
        )

            return false;

        // Remove old images.
        $now = microtime(true);
        $current_dir = @opendir($img_path);
        while ($filename = @readdir($current_dir)) {
            if (substr($filename, -5) === '.jpeg' && (str_replace('.jpeg', '', $filename) + $expiration) < $now)
                @unlink($img_path . $filename);
        }

        @closedir($current_dir);

        // Generate the pool.
        $str_int = '0123456789';
        $str_A = 'ABCDEFGHIJKLMNPQRSTUVWXYZ';
        $str_a = 'abcdefghijkmnpqrstuvwxyz';

        switch ($style) {
            case 1 :
                $pool = $str_int . $str_A . $str_a;
                break;
            case 2 :
                $pool = $str_int;
                break;
            case 3 :
                $pool = $str_A . $str_a;
                break;
            default :
                $pool = $str_int . $str_A . $str_a;
                break;
        }

        // Generate a random code.
        $code = '';
        $pool_lenth = strlen($pool);
        for ($i = 0; $i < $length; $i++) {
            $code .= $pool[mt_rand(0, $pool_lenth - 1)];
        }

        // Create image and fill the backgroud.
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, mt_rand(157, 255), mt_rand(157, 255), mt_rand(157, 255));
        ImageFilledRectangle($image, 0, 0, $width, $height, $color);

        // Write the text
        $_x = $width / $length;
        for ($i = 0; $i < $length; $i++) {
            $x = $_x * $i + mt_rand(1, 5);
            $y = $height / 1.4;
            $color = imagecolorallocate($image, mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
            imagettftext($image, $font_size, mt_rand(-30, 30), $x, $y, $color, $font, $code[$i]);
        }

        // Create the spiral pattern
        for ($i = 0; $i < 6; $i++) {
            $color = imagecolorallocate($image, mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
            imageline($image, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $color);
        }

        $random = mt_rand(20, 60);
        for ($i = 0; $i < $random; $i++) {
            $color = imagecolorallocate($image, mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
            imagestring($image, mt_rand(1, 5), mt_rand(0, $width), mt_rand(0, $height), '*', $color);
        }

        // Generate the image
        $filename = $now . '.jpeg';
        imagejpeg($image, $img_path . $filename);

        return array(
            'code' => $code,
            'image' => $img_url . $filename,
            'filename' => $filename,
        );

    }

}