<?php
function deldir($dir) {
    $dh = opendir($dir);
    while ($file = readdir($dh)) {
        if ($file != "." && $file != "..") {
            $fullpath = $dir . "/" . $file;
            if (!is_dir($fullpath)) {
                @unlink($fullpath);
            } else {
                @deldir($fullpath);
            }
        }
    }
    closedir($dh);
}

function format_price($num){
	$num = round($num, 0);
	$s_num = strval($num);
	$len = strlen($s_num)-1;
	$result = round($num, -$len);
	return $result;
}

function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true) {
    if(function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif(function_exists('iconv_substr')) {
        $slice = iconv_substr($str,$start,$length,$charset);
        if(false === $slice) {
            $slice = '';
        }
    }else{
        $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("",array_slice($match[0], $start, $length));
    }
    return ($suffix && $slice != $str) ? $slice.'...' : $slice;
}

/**
 * Warning提示信息
 * @param string $type 提示类型 默认支持success, error, info
 * @param string $msg 提示信息
 * @param string $url 跳转的URL地址
 * @return void
 */
function alert($type='info', $msg='', $url='') {
    //多行URL地址支持
    $url        = str_replace(array("\n", "\r"), '', $url);
	$alert = unserialize(stripslashes(cookie('alert')));
    if (!empty($msg)) {
        $alert[$type][] = $msg;
		cookie('alert', serialize($alert));
	}
    if (!empty($url)) {
		if (!headers_sent()) {
			// redirect
			header('Location: ' . $url);
			exit();
		} else {
			$str    = "<meta http-equiv='Refresh' content='0;URL={$url}'>";
			exit($str);
		}
	}

	return $alert;
}

function parseAlert() {
	$alert = unserialize(stripslashes(cookie('alert')));
	cookie('alert', null);

	return $alert;
}


function sendRequest($url, $params = array() , $headers = array()) {
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
	if (!empty($params)) {
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	}
	if (!empty($headers)) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$txt = curl_exec($ch);
	if (curl_errno($ch)) {
        $array[0] = 0;
        $array[1] = L("CONNECT TO A SERVER ERROR");
        $array[2] = -1;
		$return = $array;
	} else {
		$return = json_decode($txt, true);
		if (!$return) {
            $array[0] = 0;
            $array[1] = L("THE SERVER RETURNS DATA ANOMALIES");
            $array[2] = -1;
			$return = $array;
		}
	}

	return $return;
}

/*
	功能:发送邮件
	参数说明：  $to_role_id 收件人role_id
				$title 邮件主题
				$content 邮件内容
				$author 作者
*/
function sysSendEmail($to_role_id,$title,$content,$author){
	C(F('smtp'),'smtp');
	if(!$content) return false;
	if(!$to_role_id) return false;
	if(!$author) $author=C('defaultinfo.name').L('ADMIN');
	import('@.ORG.Mail');
	$to_user = D('RoleView')->where('role.role_id = %d', $to_role_id)->find();
	if(!is_email($to_user['email'])) return false;
	return SendMail($to_user['email'],$title,$content,$author);
}
function userSendEmail($address,$title,$content,$author=false){
	C(F('smtp'),'smtp');
	if(!$address) return false;
	if(!$content) return false;
	$content = preg_replace('/\\\\/','', $content);
	$userid = session('user_id');
    $user = M('user')->where(array('user_id'=>$userid))->find();
	if($author===true) $author=C('defaultinfo.name').'-'.$user['name'];
	else $author=C('defaultinfo.name');
	import('@.ORG.Mail');
	if(!is_email($address)) return false;
	return SendMail($address,$title,$content,$author);
}
function bsendemail($address,$title,$content,$file=array(),$author=false){
	if(!$address) return false;
	if(!$content) return false;
	$content = eregi_replace("[\]",'',$content);
	$userid = session('user_id');
	$user = M('user')->where(array('user_id'=>$userid))->find();
	if($author===true) $author=C('defaultinfo.name').'-'.$user['name'];
	else $author=C('defaultinfo.name')."-wukong";
	C(F('smtp'),'smtp');
	import('@.ORG.Mail');
	$mail= new PHPMailer(true);
	try {
		$mail->IsSMTP();
		$mail->CharSet=C('MAIL_CHARSET');
		$mail->AddAddress($address);
		$mail->Body=$content;
		$mail->From= C('MAIL_ADDRESS');
		$mail->FromName=$author;
		$mail->Subject=$title;
		$mail->Host=C('MAIL_SMTP');
		$mail->SMTPAuth=C('MAIL_AUTH');
		$mail->Username=C('MAIL_LOGINNAME');
		$mail->Password=C('MAIL_PASSWORD');  
		$mail->IsHTML(C('MAIL_HTML'));
		$mail->MsgHTML($content);
		 ////对邮件正文进行重新编码，保证中文内容不乱码 如果正文引用该图片 就不会以附件形式存在 而是在正文中
		if(!empty($file)){
			foreach($file as $k=>$v){
				$mail->AddAttachment(ltrim($v,'/'));
			}
		}

		//$mail->AddAttachment($content); //上传附件内容
		return($mail->Send());
	} catch (phpmailerException $e) {
	 // echo $e->errorMessage(); //Pretty error messages from PHPMailer
	} catch (Exception $e) {
	  //echo $e->getMessage(); //Boring error messages from anything else!
	}
}

function sysSendSms($to_role_id,$content){

	if(!$content) return false;
	if(!$to_role_id) return false;
	if(!$title) $title="系统通知";
	if(!$author) $author=C('defaultinfo.name').L('ADMIN');

	$to_user = D('RoleView')->where('role.role_id = %d', $to_role_id)->find();
	if(!is_email($to_user['email'])) return 100;
	return sendSMS($to_user['telephone'],$content,'sign_sysname');
}

function isMobile(){

    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $mobile_agents = Array("240x320","acer","acoon","acs-","abacho","ahong","airness","alcatel","amoi","android","anywhereyougo.com","applewebkit/525","applewebkit/532","asus","audio","au-mic","avantogo","becker","benq","bilbo","bird","blackberry","blazer","bleu","cdm-","compal","coolpad","danger","dbtel","dopod","elaine","eric","etouch","fly ","fly_","fly-","go.web","goodaccess","gradiente","grundig","haier","hedy","hitachi","htc","huawei","hutchison","inno","ipad","ipaq","ipod","jbrowser","kddi","kgt","kwc","lenovo","lg ","lg2","lg3","lg4","lg5","lg7","lg8","lg9","lg-","lge-","lge9","longcos","maemo","mercator","meridian","micromax","midp","mini","mitsu","mmm","mmp","mobi","mot-","moto","nec-","netfront","newgen","nexian","nf-browser","nintendo","nitro","nokia","nook","novarra","obigo","palm","panasonic","pantech","philips","phone","pg-","playstation","pocket","pt-","qc-","qtek","rover","sagem","sama","samu","sanyo","samsung","sch-","scooter","sec-","sendo","sgh-","sharp","siemens","sie-","softbank","sony","spice","sprint","spv","symbian","tablet","talkabout","tcl-","teleca","telit","tianyu","tim-","toshiba","tsm","up.browser","utec","utstar","verykool","virgin","vk-","voda","voxtel","vx","wap","wellco","wig browser","wii","windows ce","wireless","xda","xde","zte");

    $is_mobile = false;

    foreach ($mobile_agents as $device) {
        if (stristr($user_agent, $device)) {
            $is_mobile = true;
            break;
        }
    }

    return $is_mobile;
}

function is_utf8($liehuo_net){
	if (preg_match("/^([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}/",$liehuo_net) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}$/",$liehuo_net) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){2,}/",$liehuo_net) == true) 
	{
		return true; 
	}
	else 
	{ 
		return false; 
	}
}

//验重二维数组排序  $arr 数组 $keys比较的键值
function array_sort($arr,$keys,$type='asc'){ 
	$keysvalue = $new_array = array();
	foreach ($arr as $k=>$v){
		$keysvalue[$k] = $v[$keys];
	}
	if($type == 'asc'){
		asort($keysvalue);
	}else{
		arsort($keysvalue);
	}
	reset($keysvalue);
	$i = 0;
	foreach ($keysvalue as $k=>$v){
		if($i < 8 && $arr[$k][search] > 0){
			$new_array[] = $arr[$k]['value'];
			$i++;
		}
		
	}
	return $new_array; 
}


/*
	返回码说明 短信函数返回1发送成功  0进入审核阶段 -4手机号码不正确
*/
//单条短信
//发送到目标手机号码 $telphone手机号码 $message短信内容
function sendSMS($telphone, $message, $sign_name="sign_name",$sendtime=''){
	$flag = 0; 
	$sms = F('sms');
	$argv = array( 
		'sn'=>$sms['uid'],
		'pwd'=>strtoupper(md5($sms['uid'].$sms['passwd'])),
		'mobile'=>$telphone,
		'content'=>urlencode($message.'【'.$sms[$sign_name].'】'),
		'ext'=>'',
		'rrid'=>'',
		'stime'=>$sendtime
	); 
	foreach ($argv as $key=>$value) { 
		if ($flag!=0) { 
			$params .= "&"; 
			$flag = 1; 
		} 
		$params.= $key."="; $params.= urlencode($value); 
		$flag = 1; 
    } 
	$length = strlen($params); 
	$fp = fsockopen("sdk2.entinfo.cn",8060,$errno,$errstr,10) or exit($errstr."--->".$errno); 
	$header = "POST /webservice.asmx/mdSmsSend_u HTTP/1.1\r\n"; 
	$header .= "Host:sdk2.entinfo.cn\r\n"; 
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n"; 
	$header .= "Content-Length: ".$length."\r\n"; 
	$header .= "Connection: Close\r\n\r\n"; 
	$header .= $params."\r\n"; 
	fputs($fp,$header); 
	$inheader = 1; 
	while (!feof($fp)) { 
		$line = fgets($fp,1024);
		if ($inheader && ($line == "\n" || $line == "\r\n")) { 
			$inheader = 0; 
		} 
		if ($inheader == 0) { 
		} 
	} 


	preg_match('/<string xmlns=\"http:\/\/tempuri.org\/\">(.*)<\/string>/',$line,$str);
	$result=explode("-",$str[1]);


	   
	if(count($result)>1){
		//echo '发送失败返回值为:'.$line."请查看webservice返回值";
		return $line;
	}else{
		//echo '发送成功 返回值为:'.$line;  
		return 1;
	}
}
function sendtestSMS($uid, $uname, $telphone){
	$flag = 0; 
	$sms = F('sms');
	$argv = array( 
		'sn'=>$uid,
		'pwd'=>strtoupper(md5($uid.$uname)),
		'mobile'=>$telphone,
		'content'=>urlencode('TEST SMS 【5KCRM】'),
		'ext'=>'',
		'rrid'=>'',
		'stime'=>$sendtime
	); 
	foreach ($argv as $key=>$value) { 
		if ($flag!=0) { 
			$params .= "&"; 
			$flag = 1; 
		} 
		$params.= $key."="; $params.= urlencode($value); 
		$flag = 1; 
    } 
	$length = strlen($params); 
	$fp = fsockopen("sdk2.entinfo.cn",8060,$errno,$errstr,10) or exit($errstr."--->".$errno); 
	$header = "POST /webservice.asmx/mdSmsSend_u HTTP/1.1\r\n"; 
	$header .= "Host:sdk2.entinfo.cn\r\n"; 
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n"; 
	$header .= "Content-Length: ".$length."\r\n"; 
	$header .= "Connection: Close\r\n\r\n"; 
	$header .= $params."\r\n"; 
	fputs($fp,$header); 
	$inheader = 1; 
	while (!feof($fp)) { 
		$line = fgets($fp,1024);
		if ($inheader && ($line == "\n" || $line == "\r\n")) { 
			$inheader = 0; 
		} 
		if ($inheader == 0) { 
		} 
	} 
	preg_match('/<string xmlns=\"http:\/\/tempuri.org\/\">(.*)<\/string>/',$line,$str);
	$result=explode("-",$str[1]);
	if(count($result)>1){
		//echo '发送失败返回值为:'.$line."请查看webservice返回值";
		return $line;
	}else{
		//echo '发送成功 返回值为:'.$line;  
		return 1;
	}
}

//多条短信 最多600条
//发送到目标手机号码字符串 用","隔开 $telphone手机号码 $message短信内容 
function sendGroupSMS($telphone, $message, $sign_name="sign_name",$sendtime=''){
	$flag = 0; 
	$sms = F('sms');
    //要post的数据 
	$argv = array( 
		'sn'=>$sms['uid'], ////替换成您自己的序列号
		'pwd'=>strtoupper(md5($sms['uid'].$sms['passwd'])), //此处密码需要加密 加密方式为 md5(sn+password) 32位大写
		'mobile'=>$telphone,//手机号 多个用英文的逗号隔开 post理论没有长度限制.推荐群发一次小于等于10000个手机号
		'content'=>urlencode($message.'【'.$sms[$sign_name].'】'),//短信内容
		'ext'=>'',
		'rrid'=>'',//默认空 如果空返回系统生成的标识串 如果传值保证值唯一 成功则返回传入的值
		'stime'=>$sendtime//定时时间 格式为2011-6-29 11:09:21
	); 
	//构造要post的字符串 
	foreach ($argv as $key=>$value) { 
		if ($flag!=0) { 
			$params .= "&"; 
			$flag = 1; 
		} 
		$params.= $key."="; $params.= urlencode($value); 
		$flag = 1; 
    } 
	$length = strlen($params); 
		 //创建socket连接 
	$fp = fsockopen("sdk2.entinfo.cn",8060,$errno,$errstr,10) or exit($errstr."--->".$errno); 
	//构造post请求的头 
	$header = "POST /webservice.asmx/mdSmsSend_u HTTP/1.1\r\n"; 
	$header .= "Host:sdk2.entinfo.cn\r\n"; 
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n"; 
	$header .= "Content-Length: ".$length."\r\n"; 
	$header .= "Connection: Close\r\n\r\n"; 
	//添加post的字符串 
	$header .= $params."\r\n"; 
	//发送post的数据 
	fputs($fp,$header); 
	$inheader = 1; 
	while (!feof($fp)) { 
		$line = fgets($fp,1024); //去除请求包的头只显示页面的返回数据 
		if ($inheader && ($line == "\n" || $line == "\r\n")) { 
			$inheader = 0; 
		} 
		if ($inheader == 0) { 
			// echo $line; 
		} 
	} 


	preg_match('/<string xmlns=\"http:\/\/tempuri.org\/\">(.*)<\/string>/',$line,$str);
	$result=explode("-",$str[1]);


	   
	if(count($result)>1){
		//echo '发送失败返回值为:'.$line."请查看webservice返回值";
		return $line;
	}else{
		//echo '发送成功 返回值为:'.$line;  
		return 1;
	}
}
 function getSmsNum(){
	$sms = F('sms');
	
	$flag = 0; 
        //要post的数据 
	$argv = array( 
		'sn'=>$sms['uid'], //替换成您自己的序列号
		'pwd'=>$sms['passwd'],//替换成您自己的密码	
	); 
	//构造要post的字符串 
	foreach ($argv as $key=>$value) { 
		if ($flag!=0) { 
				 $params .= "&"; 
				 $flag = 1; 
		} 
		$params.= $key."="; $params.= urlencode($value); 
		$flag = 1; 
	} 
		$length = strlen($params); 
		 //创建socket连接 
		$fp = fsockopen("sdk2.entinfo.cn",8060,$errno,$errstr,10) or exit($errstr."--->".$errno); 
		//构造post请求的头 
		$header = "POST /webservice.asmx/GetBalance HTTP/1.1\r\n"; 
		$header .= "Host:sdk2.entinfo.cn\r\n"; 
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n"; 
		$header .= "Content-Length: ".$length."\r\n"; 
		$header .= "Connection: Close\r\n\r\n"; 
		//添加post的字符串 
		$header .= $params."\r\n"; 
		//发送post的数据 
		fputs($fp,$header); 
		$inheader = 1; 
		while (!feof($fp)) { 
			$line = fgets($fp,1024); //去除请求包的头只显示页面的返回数据 
				if ($inheader && ($line == "\n" || $line == "\r\n")) { 
				$inheader = 0; 
			} 
			if ($inheader == 0) { 
				// echo $line; 
			} 
		} 
		//<string xmlns="http://tempuri.org/">-5</string>
		$line=str_replace("<string xmlns=\"http://tempuri.org/\">","",$line);
		$line=str_replace("</string>","",$line);
		$result=explode("-",$line);
		if(count($result)>1)
			return $line;
		else
			return $line;
}
//判断目录是否可写
function check_dir_iswritable($dir_path){
    $dir_path=str_replace('\\','/',$dir_path);
    $is_writale=1;
    if(!is_dir($dir_path)){
        $is_writale=0;
        return $is_writale;
    }else{
        $file_hd=@fopen($dir_path.'/test.txt','w');
        if(!$file_hd){
            @fclose($file_hd);
            @unlink($dir_path.'/test.txt');
            $is_writale=0;
            return $is_writale;
        }
        @unlink($dir_path.'/test.txt');
        $dir_hd=opendir($dir_path);
        while(false!==($file=readdir($dir_hd))){
            if ($file != "." && $file != "..") {
                if(is_file($dir_path.'/'.$file)){
                    //文件不可写，直接返回
                    if(!is_writable($dir_path.'/'.$file)){
                        return 0;
                    } 
                }else{
                    $file_hd2=@fopen($dir_path.'/'.$file.'/test.txt','w');
                    if(!$file_hd2){
                        @fclose($file_hd2);
                        @unlink($dir_path.'/'.$file.'/test.txt');
                        $is_writale=0;
                        return $is_writale;
                    }
                    @unlink($dir_path.'/test.txt');
                    //递归
                    $is_writale=check_dir_iswritable($dir_path.'/'.$file);
                }
            }
        }
    }
return $is_writale;
}

function is_email($email)
{
	return strlen($email) > 8 && preg_match("/^([a-zA-Z0-9]+[-|_|_|.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[-|_|_|.]?)*[a-zA-Z0-9]+.[a-zA-Z]{2,3}$/i", $email);
}
function is_phone($phone)
{
	return strlen(trim($phone)) == 11 && preg_match("/^1[3|5|8][0-9]{9}$/i", trim($phone));
}
function pregtime($timestamp){
	if($timestamp){
		return date('Y-m-d',$timestamp);
	}else{
		return '';
	}
}


function getContactsRQ($contacts_id,$width=200,$height=200){
	$contacts = M('Contacts')->where('contacts_id = %d', $contacts_id)->find();
	$customer_id = M('RContactsCustomer')->where('contacts_id = %d',$contacts_id)->getField('customer_id');
	$contacts['customer'] = M('Customer')->where('customer_id = %d', $customer_id)->getField('name');
	$qrOpt = array();
	$qrOpt['chl'] = "BEGIN:VCARD\nVERSION:3.0\n";
	$qrOpt['chl'] .= $contacts['name'] ? ("FN:".$contacts['name']."\n") : "";
	$qrOpt['chl'] .= $contacts['telephone'] ? ("TEL:".$contacts['telephone']."\n") : "";
	$qrOpt['chl'] .= $contacts['email'] ? ("EMAIL:".$contacts['email']."\n") : "";
	$qrOpt['chl'] .= $contacts['customer'] ? ("ORG:".$contacts['customer']."\n") : "";	
	$qrOpt['chl'] .= $contacts['post'] ? ("TITLE:".$contacts['post']."\n") : "";
	$qrOpt['chl'] .= $contacts['address'] ? ("ADR:".$contacts['address']."\n") : "";
	$qrOpt['chl'] .= "END:VCARD";
	
	$qrOpt['chs'] = $width."x".$height;
	$qrOpt['cht'] = "qr";
	$qrOpt['chld'] = "|1";
	$qrOpt['choe'] = "UTF-8";
	$link = 'http://chart.googleapis.com/chart?'.http_build_query($qrOpt);
	return $link;
}
function userLog($uid,$text=''){
    $user = M('user')->where(array('user_id'=>$uid))->find();
    $category = $user['category_id'] == 1 ? L('ADMIN') : L('USER');
    $data['user_id'] = $uid;
	$data['module_name'] = strtolower(MODULE_NAME);
    $data['action_name'] = strtolower(ACTION_NAME);
    $data['create_time'] = time();
 //   $data['action_id'] = $id;
    $data['content'] = sprintf('%s%s在%s%s。%s',$category,$user['name'],date('Y-m-d H:i:s'),L(ACTION_NAME),$text);
    $userLog = M('userLog');
    $userLog->create($data);
    if($userLog->add()){return true;}else{return false;}
    
}
function vali_permission($m, $a){
	$allow = $params['allow'];
	
	if (session('?admin')) {
		return true;
	}
	if (in_array($a, $allow)) {
		return true;
	} else {
		switch ($a) {
			case "listdialog" : $a = 'index'; break;
			case "adddialog" : $a = 'add'; break;
			case "excelimport" : $a = 'add'; break;
			case "excelexport" : $a = 'view'; break;
			case "cares" :  $a = 'index'; break;
			case "caresview" :  $a = 'view'; break;
			case "caresedit" :  $a = 'edit'; break;
			case "caresdelete" :   $a = 'delete'; break;
			case "caresadd" :  $a = 'add'; break;
			case "receive" : $a = 'add'; break;
			case "role_add" : $a = 'add';break;
			case "sendsms" : $a = 'marketing';break;
			case "sendemail" : $a = 'marketing';break;
		}
		$url = strtolower($m).'/'.strtolower($a);
		$ask_per = M('permission')->where('url = "%s" and position_id = %d', $url, session('position_id'))->find();
		if (is_array($ask_per) && !empty($ask_per)) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * @ atuhor		: zf
 * @ function 	: 下载方法
 **/
 function download($file,$name=''){
    $fileName = $name ? $name : pathinfo($file,PATHINFO_FILENAME);
    $filePath = realpath($file);
    
    $fp = fopen($filePath,'rb');
    
    if(!$filePath || !$fp){
        header('HTTP/1.1 404 Not Found');
        echo "Error: 404 Not Found.(server file path error)<!-- Padding --><!-- Padding --><!-- Padding --><!-- Padding --><!-- Padding --><!-- Padding --><!-- Padding --><!-- Padding --><!-- Padding --><!-- Padding --><!-- Padding --><!-- Padding --><!-- Padding --><!-- Padding -->";
        exit;
    }
    
    $fileName = $fileName .'.'. pathinfo($filePath,PATHINFO_EXTENSION);
    $encoded_filename = urlencode($fileName);
    $encoded_filename = str_replace("+", "%20", $encoded_filename);
    
    header('HTTP/1.1 200 OK');
    header( "Pragma: public" );
    header( "Expires: 0" );
    header("Content-type: application/octet-stream");
    header("Content-Length: ".filesize($filePath));
    header("Accept-Ranges: bytes");
    header("Accept-Length: ".filesize($filePath));
    
    $ua = $_SERVER["HTTP_USER_AGENT"];
    if (preg_match("/MSIE/", $ua)) {
        header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
    } else if (preg_match("/Firefox/", $ua)) {
        header('Content-Disposition: attachment; filename*="utf8\'\'' . $fileName . '"');
    } else {
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
    }
    
    // ob_end_clean(); <--有些情况可能需要调用此函数
    // 输出文件内容
    fpassthru($fp);
    exit;
}

function verify(){
    import('@.ORG.Image');
	Image::buildImageVerify();
}

function filtStr($str){
	$str= htmlspecialchars_decode($str); 
	$str= preg_replace("/<(.*?)>/","",$str); 
	return $str;
}


