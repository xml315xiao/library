<?php   defined('BASEPATH') OR exit('No direct script access allowed');

if (! function_exists('check_format'))
{
    /**
     * Check the format of the value
     * @param $value    string
     * @param $format   string
     * @return bool
     */
    function check_format($value, $format)
    {
        $res = NULL;
        $format = strtolower($format);
        switch ($format) {

            // filter_var($value, FILTER_VALIDATE_EMAIL);
            case 'email' :
                $res = preg_match('/^\w+(\.\w+)*@\w+(\.\w+)+$/', $value);
                break;

            // filter_var($value, FILTER_VALIDATE_URL);
            case 'url' :
                $res = preg_match('/^(http:\/\/)?(https:\/\/)?([\w\d-]+\.)+[\w-]+(\/[\d\w-.\/?%&=]*)?$/i', $value);
                break;

            // filter_var($value, FILTER_VALIDATE_IP)
            case 'ip' :
                $res = preg_match('/^(25[0-5]|2[0-4][0-9]|[0-1]{0,1}[0-9]{1,2})\.(25[0-5]|2[0-4][0-9]|[0-1]{0,1}[0-9]{1,2})\.(25[0-5]|2[0-4][0-9]|[0-1]{0,1}[0-9]{1,2})\.(25[0-5]|2[0-4][0-9]|[0-1]{0,1}[0-9]{1,2})$/', $value);
                break;

            // filter_var($value, FILTER_VALIDATE_INT)
            case 'int' :
                $res = preg_match('/^\d*$/', $value);
                break;

            // filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
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
                preg_match('/^([1-9]{1}\d*|[0]{1})(\.([0-9]{1,2}))?/', $value);
                break;

            case 'token' :
                preg_match('/^[0-9a-f]{32}$/i', $value);
                break;

            default :
                $res = FALSE;
        }

        return boolval($res);
    }
}
