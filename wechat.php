<?php
require_once './function.php';

$app_id = 'wx922219674c18f088';                                  // $app_id = 'wxf7a541209b418e63';
$app_secret = 'a423a094fd815951d7f52e13cf8f169d';                // $app_secret = '2713563dfe7f202b524f37545dc566b7';

$result = getAccessToken($app_id, $app_secret);
$token = $result['access_token'];
$result = getShortURL($token);


// $url = getWeixinOauthURL('http://www.kuaijiegou.cn/wxcz/action/redirect.php', 'snsapi_base', 'membercard.html');
// echo $url;

/* *
 * 创建用户分组（一个公众账号，最多支持创建100个分组）
 * https://api.weixin.qq.com/cgi-bin/groups/create?access_token=
 * @postdata : {"group":{"name":"test"}}
 *  {
		"group": {
			"id": 107,
			"name": "test"
		}
	}
 */
function createGroup($token, $name){
    $postdata = '{"group":{"name":"'. $name .'"}}';
    return curlWechat('https://api.weixin.qq.com/cgi-bin/groups/create?access_token='.$token, 1, $postdata);
}

/* *
 * 查询所有分组
 {
 	"groups" : [{
 			"id" : 0,
 			"name" : "未分组",
 			"count" : 72596
 		}, {
 			"id" : 1,
 			"name" : "黑名单",
 			"count" : 36
 		}, {
 			"id" : 2,
 			"name" : "星标组",
 			"count" : 8
 		}, {
 			"id" : 104,
 			"name" : "华东媒",
 			"count" : 4
 		}, {
 			"id" : 106,
 			"name" : "★不测试组★",
 			"count" : 1
 		}
 	]
 }
*/
function getGroups($token){
    return curlWechat('https://api.weixin.qq.com/cgi-bin/groups/get?access_token='.$token);
}

/* 查询用户所在分组
 * {"groupid" : 102}
 */
function getUserGroup($openid, $token){
    $postdata = '{"openid":"'. $openid .'"}';
    return curlWechat('https://api.weixin.qq.com/cgi-bin/groups/getid?access_token='.$token, 1, $postdata);
}

/* 修改分组名 {"errcode": 0, "errmsg": "ok"} */
function updateGroup($token, $groupid, $groupname) {
    $postdata = '{"group":{"id":'. $groupid .',"name":"'. $groupname .'"}}';
    return curlWechat('https://api.weixin.qq.com/cgi-bin/groups/update?access_token='.$token, 1, $postdata);
}

/* 移动用户分组 {"errcode": 0, "errmsg": "ok"} */
function moveUserGroup($token, $openid, $groupid){
    $postdata = '{"openid":"'. $openid .'","to_groupid":'. $groupid .'}';
    return curlWechat('https://api.weixin.qq.com/cgi-bin/groups/members/update?access_token='.$token, 1, $postdata);
}

/* 设置备注姓名 {"errcode": 0, "errmsg": "ok"} */
function setUserRemark($token, $openid, $remark){
    $postdata = '{"openid":"'. $openid .'","remark":"'. $remark .'"}';
    return curlWechat('https://api.weixin.qq.com/cgi-bin/user/info/updateremark?access_token='.$token, 1, $postdata);
}

/* *
 * 获取用户信息
{
	"subscribe" : 1,
	"openid" : "o6_bmjrPTlm6_2sgVt7hMZOPfL2M",
	"nickname" : "Band",
	"sex" : 1,
	"language" : "zh_CN",
	"city" : "广州",
	"province" : "广东",
	"country" : "中国",
	"headimgurl" : "http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/0",
	"subscribe_time" : 1382694957,
	"unionid" : " o6_bmasdasdsad6_2sgVt7hMZOPfL"	// 只有在用户将公众号绑定到微信开放平台帐号后，才会出现该字段
	"remark" : "",
	"groupid" : 0
}
 */
function getUserInfo($token, $openid){
    return curlWechat('https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token.'&openid='.$openid.'&lang=zh_CN');
}

/* *
 * 批量获取用户信息
 * @postdata
	{
		"user_list" : [{
				"openid" : "otvxTs4dckWG7imySrJd6jSi0CWE",
				"lang" : "zh-CN"
			}, {
				"openid" : "otvxTs_JZ6SEiP0imdhpi50fuSZg",
				"lang" : "zh-CN"
			}
		]
	}
 * @return
 * {"errcode":40013,"errmsg":"invalid appid"}
 */
function getUserInfoList($token, $openid_arr){
    $user_arr = array();
    foreach($openid_arr as $openid){
        $user_arr['user_list'][] = array('openid' => $openid, 'lang' => 'zh-CN');
    }
    $postdata = json_encode($user_arr);
    return curlWechat('https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token='.$token, 1, $postdata);
}

/* *
 * 获取用户列表
 * @next_openid	第一个拉取的OPENID，不填默认从头开始拉取
 {
 	"total" : 2,                                     // 关注该公众账号的总用户数
 	"count" : 2,                                     // 拉取的OPENID个数，最大值为10000
 	"data" : {                                       // 列表数据，OPENID的列表
 		"openid" : ["", "OPENID1", "OPENID2"]
 	},
 	"next_openid" : "NEXT_OPENID"                    // 拉取列表的最后一个用户的OPENID
 }
 */
function getUseridList($token, $next_openid = ''){
    return curlWechat('https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$token.'&next_openid='.$next_openid);
}

/* *
 * 第一步、用户同意授权，获取code
 * https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=SCOPE&state=STATE#wechat_redirect
 * @redirect_uri 授权后重定向的回调链接地址，请使用urlencode对链接进行处理
 * @response_type 返回类型，请填写固定值code
 * @scope 应用授权作用域, snsapi_base （不弹出授权页面，直接跳转，只能获取用户openid）, snsapi_userinfo （弹出授权页面，可通过openid拿到昵称、性别、所在地。并且，即使在未关注的情况下，只要用户授权，也能获取其信息）
 * @state 重定向后会带上state参数，开发者可以填写a-zA-Z0-9的参数值，最多128字节
 * @#wechat_redirect 无论直接打开还是做页面302重定向时候，必须带此参数
 */
function getWeixinOauthURL($redirect_uri, $scope = 'snsapi_base', $state = 'STATE', $app_id = 'wx922219674c18f088'){
    return 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$app_id.'&redirect_uri='.urlencode($redirect_uri).'&response_type=code&scope='.$scope.'&state='.urlencode($state).'#wechat_redirect';
}

/* *
 * 第二步：通过code换取网页授权access_token
 * https://api.weixin.qq.com/sns/oauth2/access_token?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code
 * eg :
 * {
 *    "access_token":"ACCESS_TOKEN",   				// 网页授权接口调用凭证,注意：此access_token与基础支持的access_token不同
 *    "expires_in":7200,                            // access_token接口调用凭证超时时间，单位（秒）
 *    "refresh_token":"REFRESH_TOKEN",              // 用户刷新access_token, 可以使用refresh_token进行刷新，refresh_token拥有较长的有效期（7天、30天、60天、90天），当refresh_token失效的后，需要用户重新授权。
 *    "openid":"OPENID",                            // 用户唯一标识，请注意，在未关注公众号时，用户访问公众号的网页，也会产生一个用户和公众号唯一的OpenID
 *    "scope":"SCOPE",                              // 用户授权的作用域，使用逗号（,）分隔
 *    "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"    // 只有在用户将公众号绑定到微信开放平台帐号后，才会出现该字段
 * }
 * 失败：{"errcode":40029,"errmsg":"invalid code"}
 */
function getUserAccessToken($code, $app_id = 'wx922219674c18f088', $app_secret = 'a423a094fd815951d7f52e13cf8f169d'){
    return curlWechat('https://api.weixin.qq.com/sns/oauth2/access_token?appid='. $app_id .'&secret='. $app_secret .'&code='. $code .'&grant_type=authorization_code');
}

/* *
 * 第三步 : 刷新用户授权access_token
 * https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=APPID&grant_type=refresh_token&refresh_token=REFRESH_TOKEN
 * @ refresh_token 通过用户授权access_token时获取到的refresh_token
 * 成功 :
 *  {
 *    "access_token":"ACCESS_TOKEN",
 *    "expires_in":7200,
 *    "refresh_token":"REFRESH_TOKEN",
 *    "openid":"OPENID",
 *    "scope":"SCOPE"
 *  }
 * 失败 : {"errcode":40029,"errmsg":"invalid code"}
 */
function refreshUserAccessToken($refresh_token, $app_id = 'wx922219674c18f088'){
    return curlWechat('https://api.weixin.qq.com/sns/oauth2/refresh_token?appid='. $app_id .'&grant_type=refresh_token&refresh_token='. $refresh_token);
}

/* *
 * 第四步 ：拉取用户信息(需scope为 snsapi_userinfo)
 * https://api.weixin.qq.com/sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN
 * @access_token 用户授权凭证
 * @openid 用户的唯一标识
 * @lang zh_CN 简体，zh_TW 繁体，en 英语
 *  {
 *   "openid":" OPENID",
 *   "nickname": NICKNAME,
 *   "sex":"1",              // 用户的性别，值为1时是男性，值为2时是女性，值为0时是未知
 *   "province":"PROVINCE"
 *   "city":"CITY",
 *   "country":"COUNTRY",
 *	 "headimgurl":    "http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46",
 *	 "privilege":[          // 用户特权信息，json 数组，如微信沃卡用户为（chinaunicom）
 *		"PRIVILEGE1"
 *		"PRIVILEGE2"
 *	 ],
 *	 "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
 *	}
 * 失败 : {"errcode":40003,"errmsg":" invalid openid "}
 */

/* *
 * 获取二维码ticket
 * {"ticket":"gQH77zoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL0trUW1DUi1sUHVOSHlMc0xSV29FAAIEfHykVQMEgDoJAA==","expire_seconds":604800,"url":"http:\/\/weixin.qq.com\/q\/KkQmCR-lPuNHyLsLRWoE"}
 * {"errcode":40013,"errmsg":"invalid appid"}
 */
function getQrcodeTicket($token, $scene_id = '', $expire = 1800){
    $postdata = json_encode(array('expire_seconds' => $expire, 'action_name' => 'QR_SCENE', 'action_info' => array('scene_id' => $scene_id)));
    return curlWechat('https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$token, 1, $postdata);
}

/* 生成二维码 */
function getQrcode($ticket){
    header('Content-type: image/jpg');
    $httpret = curlResponse('https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket);
    print($httpret['content']);
}

/* *
 * 生成二维码短链接
 * 成功：{"errcode":0,"errmsg":"ok","short_url":"http:\/\/w.url.cn\/s\/AvCo6Ih"}
 * 失败：{"errcode":40013,"errmsg":"invalid appid"}
 */
function getShortURL($token){
    $result = getQrcodeTicket($token);
    if ($result['status'] === false) return $result;
    $longurl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$result['ticket'];
    $postdata = json_encode(array('action' => 'long2short', 'long_url' => $longurl));
    return curlWechat('https://api.weixin.qq.com/cgi-bin/shorturl?access_token='.$token, 1, $postdata);
}

/* *
 * 获取微信access_token
 * 成功返回array('status' => 0, 'access_token' => access_token)
 * 失败返回array('status' => 1, 'errmsg' => errcode.':'.errmsg)
 * eg: {"access_token":"nU1TZjah1yKuyZjW6KpsdKdpB73CNVL_fyZwRgiTRR89In8-zRl3OrqD72GaUk7zuU4i8xt-iO2eGkbLgsEa0pGgx6pp42UrTggh9KOPXag","expires_in":7200}
 * eg: {"errcode":40125,"errmsg":"invalid appsecret, view more at http:\/\/t.cn\/RAEkdVq"}
 */
function getAccessToken($appid, $appsecret){
    return curlWechat('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$appsecret);
}

/* *
 * 微信curl
 */
function curlWechat($url,  $post = 0, $postdata = ''){
    $httpret = curlResponse($url, $post, $postdata);
    if ($httpret['code'] != '200') return array('status' => 1, 'errmsg' => '请求响应失败');
    $json_result = json_decode($httpret['content'], true);
    if (!$json_result) return array('status' => 1, '解析JSON出错');
    if (array_key_exists('errcode', $json_result) && $json_result['errcode'] > 0) return sendErrorMsg($json_result);
    return array_merge(array('status' => true), $json_result);
}

/* 返回错误信息 */
function sendErrorMsg($json_result){
    return array('status' => false, 'errcode' => $json_result['errcode'], 'errmsg' => $json_result['errmsg']);
}

/* *
 * 获取微信IP列表
 * 成功返回列表信息
 * 失败返回错误信息
 * eg：
 */
function getIPlists($access_token){
    $httpret = curlResponse('https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token='.$access_token);
    if ($httpret['code'] != '200') return array('status' => 1, 'errmsg' => '请求token失败');
    if (strpos($httpret['content'], 'errcode') !== false) return sendErrorMsg($httpret['content']);
    $ip_list = cutstr($httpret['content'], 'ip_list', 'json');
    return array('status' => 0, 'ip_list' => $ip_list);
}