<?php
function deldir($dir) {
        $dh = opendir($dir);
         while ($file = readdir($dh)) {
             if ($file != "." && $file != "..") {
                 $fullpath = $dir . "/" . $file;
                if (!is_dir($fullpath)) {
                     unlink($fullpath);
                } else {
					 deldir($fullpath);
				 }
             }
         }
    }

function format_price($num){
	$num = round($num, 0);
	$s_num = strval($num);
	$len = strlen($s_num)-1;
	$result = round($num, -$len);
	return $result;
}


function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true) {
    if($str){
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
		return $suffix ? $slice.'...' : $slice;
	}else{
		return '';
	}
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
		$return = array(0, '连接服务器出错', -1);
	} else {
		$return = json_decode($txt, true);
		if (!$return) {
			$return = array(0, '服务器返回数据异常', -1);
		}
	}

	return $return;
}


//$sysMessage=0 为系统消息
function sendMessage($id,$content){
	if(!$id) return false;
	if(!$content) return false;
	$m_message = M('message');
	$data['member_id'] = $id;
	$data['content'] = $content;
	$data['read_time'] = 0;
	$data['create_time'] = time();
	return $m_message->add($data);
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

function is_utf8($liehuo_net) 
{
	if (preg_match("/^([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}/",$liehuo_net) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}$/",$liehuo_net) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){2,}/",$liehuo_net) == true) 
	{
		return true; 
	}
	else 
	{ 
		return false; 
	}
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
	return strlen($email) > 8 && preg_match("/^[-_+.[:alnum:]]+@((([[:alnum:]]|[[:alnum:]][[:alnum:]-]*[[:alnum:]])\.)+([a-z]{2,4})|(([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])\.){3}([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5]))$/i", $email);
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

//获取制定时间$now_time  $change_month月份差 往前是负数，往后是正数 的月份时间戳范围
function GetMonth($change_month, $now_time){
	$now_time = $now_time ? $now_time : time();
	$now_year = date('Y', $now_time);
	$now_month = date('m', $now_time);
	
	if($now_month+$change_month <= 0){
		$target_year = $now_year-(intval(((-1*$change_month)-$now_month)/12)+1);
		$target_moon = 12+(($now_month-(-1*$change_month))%12);
	}elseif($now_month+$change_month > 12){
		$target_year = $now_year+intval(($change_month+$now_month)/12);
		$target_moon = $now_month+$change_month-12;
	}else{
		$target_year = $now_year;
		$target_moon = $change_month+$now_month;
	}
	$tmp_nextmonth=mktime(0,0,0,$tmp_mon+1,1,$tmp_year);  
	
	$target_next_moon = $target_moon+1 > 12 ? $target_moon+1-12 :$target_moon+1;
	$target_next_moon_year = $target_moon+1 > 12 ? $target_year+1 : $target_year;
	
	$target_moon_start_time = mktime(0,0,0,$target_moon,1,$target_year);  
	$target_moon_end_time = mktime(0,0,0,$target_next_moon,1,$target_next_moon_year); 
	
	return array('start_time' => $target_moon_start_time, 'end_time' => $target_moon_end_time);
}

