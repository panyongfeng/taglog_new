<?php 
/**
 *
 * 用户相关模块
 *
 **/ 

class UserAction extends Action {

	public function _initialize(){
		$action = array(
			'permission'=>array('login','lostpw','resetpw', 'test'),
			'allow'=>array('logout')
		);
		B('Authenticate', $action);
	}

	//登录
	public function login() {
		if (session('?admin')){
			$this->redirect('index/index',array(), 0, '');
		} elseif($_POST['submit']) {
			if((!isset($_POST['name']) || $_POST['name'] =='')||(!isset($_POST['password']) || $_POST['password'] =='')){
				alert('error', '请正确输入用户名和密码！'); 
			}elseif (isset($_POST['name']) && $_POST['name'] != ''){
				$m_user = M('user');
				$user = $m_user->where(array('name' => trim($_POST['name'])))->find();
				
				if ($user['password'] == md5(md5(trim($_POST['password'])) . $user['salt'])) {				
					if ($_POST['autologin'] == 'on') {
						session(array('expire'=>259200));
						cookie('user_id',$user['user_id'],259200);
						cookie('admin_name',$user['name'],259200);
						cookie('salt_code',md5(md5($user['user_id'] . $user['name']).$user['salt']),259200);
					}else{
						session(array('expire'=>3600));
					}
					session('admin', 1);
					session('admin_name', $user['name']);
					session('user_id', $user['user_id']);
					alert('success', '登录成功', U('Index/index'));		
				} else {
					alert('error', '用户名或密码错误！'); 				
				}
			}			
			$this->alert = parseAlert();
			$this->display();
		}else{
			$this->alert = parseAlert();
			$this->display();
		}
	}
	//找回密码
	public function lostpw() {
		if($_POST['submit']){
			if ($_POST['name'] || $_POST['email']){
				$user = M('User');
				if ($_POST['name']){
					$info = $user->where('name = "%s"',trim($_POST['name']))->find();
					if(!isset($info) || $info == null){
						$this->error('用户名不存在');
					}
				} elseif ($_POST['email']){
					$info = $user->where('email = "%s"',trim($_POST['email']))->find();
					if (ereg('^([a-zA-Z0-9]+[_|_|.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|_|.]?)*[a-zA-Z0-9]+.[a-zA-Z]{2,3}$',$_POST['email'])){
						if (!isset($info) || $info == null){
							$this->error('没有用户使用该邮箱');
						}
					}else{
						$this->error('邮箱格式不正确！');
					}					
				}				
				$time = time();
				$user->where('user_id = ' . $info['user_id'])->save(array('lostpw_time' => $time));
				$verify_code = md5(md5($time) . $info['salt']);
				C(F('smtp'),'smtp');
				import('@.ORG.Mail');
				$url = U('user/resetpw', array('user_id'=>$info['user_id'], 'verify_code'=>$verify_code),'','',true);
				$content ='尊敬的' . $_POST['name'] . '：<br/><br/>请点击下面的链接完成找回密码：<br/><br/>' . $url .'<br/><br/>如果以上链接无法点击，请将上面的地址复制到你的浏览器(如IE)的地址栏进入网站。<br/><br/>--悟空CRM(这是一封自动产生的email，请勿回复。)';
				if (SendMail($info['email'],'找回密码链接',$content,'悟空CRM管理员')){
					$this->success('邮件发送成功，请24小时之内到邮箱查看，请留意垃圾邮件！');
				}
			} else {
				$this->error('请输入用户名或邮箱！');
			}
		} else{
			if (!F('smtp')) {
				$this->error('SMTP未设置无法使用此功能，请联系管理员');
			}
			$this->alert = parseAlert();
			$this->display();			
		}
	}
	//密码重置
	public function resetpw(){
		$verify_code = trim($_REQUEST['verify_code']);
		$user_id = intval($_REQUEST['user_id']);
		$m_user = M('User');
		$user = $m_user->where('user_id = %d', $user_id)->find();
		if (is_array($user) && !empty($user)) {
			if ((time()-$user['lostpw_time'])>86400){
				alert('error', '链接失效，请重新找回密码',U('user/lostpw'));
			}elseif (md5(md5($user['lostpw_time']) . $user['salt']) == $verify_code) {
				if ($_REQUEST['password']) {
					$password = md5(md5(trim($_REQUEST["password"])) . $user['salt']);
					$m_user->where('user_id =' . $_REQUEST['user_id'])->save(array('password'=>$password, 'lostpw_time'=>0));
					alert('success', '密码修改成功，请登录', U('user/login'));
				} else {
					$this->alert = parseAlert();
					$this->display();
				}
			} else{
				$this->error('找回密码链接无效或链接已失效！');
			}		
		} else {
			$this->error('找回密码链接无效或链接已失效！');
		}
	}
	
	//退出
	public function logout() {
		session(null);
		cookie('user_id',null);
		cookie('name',null);
		cookie('salt_code',null);
		$this->success('已经退出！', U('User/login'));
	}

	
	//修改自己的信息
	public function edit(){
		$m_user = M('User');
		if ($this->isPost()) {
			$user=$m_user->where('user_id = %d', session('user_id'))->find();

			if ($m_user->create()) {
				if(isset($_POST['password']) && $_POST['password']!=''){
					$m_user->password = md5(md5(trim($_POST["password"])) . $user['salt']);
				} else {
					unset($m_user->password);
				}
				if($m_user->save()){
					alert('success','信息修改成功！',U('user/index'));
				}else{
					alert('error','信息无变化！',$_SERVER['HTTP_REFERER']);
				}
			} else {
				alert('error','信息修改失败！',$_SERVER['HTTP_REFERER']);
			}
		}else{
			$this->user = $m_user->where('user_id = %d', session('user_id'))->find();
			$this->alert = parseAlert();
			$this->display();
		}
	}


	public function add(){
		$m_role = M('Role');
		$m_user = D('User');
		if ($this->isPost()){
			$m_user->create(); 
			// echo $m_user->name; 
			if($_POST['radio_type'] == 'email'){
				//邮箱激活
				if (!isset($_POST['name']) || $_POST['name'] == '') {
					alert('error', '请输入用户名', $_SERVER['HTTP_REFERER']);				
				} elseif (!isset($_POST['email']) || $_POST['email'] == ''){
					alert('error', '请输入邮箱', $_SERVER['HTTP_REFERER']);	
				} elseif (!ereg('^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,4}$', $_POST['email'])){
					alert('error', '邮箱格式不正确', $_SERVER['HTTP_REFERER']);
				} elseif ($m_user->where('email = "%s"', $_POST['email'])->find()) {
					alert('error', '此邮箱已绑定用户!', $_SERVER['HTTP_REFERER']);
				} elseif (!isset($_POST['category_id']) || $_POST['category_id'] == ''){
					alert('error', '请选择用户类别！', $_SERVER['HTTP_REFERER']);
				} elseif (!session('?admin') && intval($_POST['category_id'])==1) {
					alert('error', '你没有添加管理员用户的权利！', $_SERVER['HTTP_REFERER']);
				} elseif (!isset($_POST['position_id']) || $_POST['position_id'] == ''){
					alert('error', '请选择要添加用户的岗位!', $_SERVER['HTTP_REFERER']);
				} elseif ($m_user->where('name = "%s"', $_POST['name'])->find()){
					alert('error', '该用户已存在!', $_SERVER['HTTP_REFERER']);
				}
				$m_user->status = 0;
				//为用户设置默认导航（根据系统菜单设置中的位置）
				$m_navigation = M('navigation');
				$navigation_list = $m_navigation->order('listorder asc')->select();
				$menu = array();
				foreach($navigation_list as $val){
					if($val['postion'] == 'top'){
						$menu['top'][] = $val['id'];
					}elseif($val['postion'] == 'user'){
						$menu['user'][] = $val['id'];
					}else{
						$menu['more'][] = $val['id'];
					}
				}
				$navigation = serialize($menu);
				$m_user->navigation = $navigation;
				
				if($re_id = $m_user->add()){
					// echo $m_user->getLastSql();
					// die();  
					$time = time();
					$info = $m_user->where('user_id = %d', $re_id)->find();
					$m_user->where('user_id = %d' . $info['user_id'])->setField('reg_time', $time);
					$verify_code = md5(md5($time) . $info['salt']);
					C(F('smtp'),'smtp');
					import('@.ORG.Mail');
					$url = U('user/active', array('user_id'=>$info['user_id'], 'verify_code'=>$verify_code),'','',true);
					$content ='尊敬的' . $_POST['name'] . '：<br/><br/>您好！您的CRM管理员已经给您发送了邀请，请查收！
			请点击下面的链接完成注册：<br/><br/>' . $url .'<br/><br/>如果以上链接无法点击，请将上面的地址复制到你的浏览器(如IE)的地址栏进入网站。<br/><br/>--悟空CRM管理员(这是一封自动产生的email，请勿回复。)';
					//echo $info['email'].$content;
					//die();
					if (SendMail($info['email'], '从悟空CRM添加用户邀请', $content,'悟空CRM管理员')){
						$data['position_id'] = $_POST['position_id'];
						$data['user_id'] = $re_id;
						if($role_id = $m_role->add($data)){
							$m_user->where('user_id = %d', $re_id)->setField('role_id', $role_id);
							actionLog($re_id);
							alert('success', '添加成功，等待被邀请用户激活!', U('user/index'));
						}
					} else {
						alert('error', '无法发送邀请，请检查smtp设置信息!', $_SERVER['HTTP_REFERER']);
					}
				} else {
					alert('error', '添加失败，请联系管理员!', $_SERVER['HTTP_REFERER']);
				}
			}else{
				//填写密码
				if (!isset($_POST['name']) || $_POST['name'] == '') {
					alert('error', '请输入用户名', $_SERVER['HTTP_REFERER']);				
				} elseif (!isset($_POST['password']) || $_POST['password'] == ''){
					alert('error', '请输入密码', $_SERVER['HTTP_REFERER']);	
				} elseif (!isset($_POST['category_id']) || $_POST['category_id'] == ''){
					alert('error', '请选择用户类别！', $_SERVER['HTTP_REFERER']);
				} elseif (!session('?admin') && intval($_POST['category_id'])==1) {
					alert('error', '你没有添加管理员用户的权利！', $_SERVER['HTTP_REFERER']);
				} elseif (!isset($_POST['position_id']) || $_POST['position_id'] == ''){
					alert('error', '请选择要添加用户的岗位!', $_SERVER['HTTP_REFERER']);
				} elseif ($m_user->where('name = "%s"', $_POST['name'])->find()){
					alert('error', '该用户已存在!', $_SERVER['HTTP_REFERER']);
				} elseif (!session('?admin') && intval($_POST['category_id'])==1) {
					alert('error', '你没有添加管理员用户的权利！', $_SERVER['HTTP_REFERER']);
				}
				
				$m_user->status = 1;
				//为用户设置默认导航（根据系统菜单设置中的位置）
				$m_navigation = M('navigation');
				$navigation_list = $m_navigation->order('listorder asc')->select();
				$menu = array();
				foreach($navigation_list as $val){
					if($val['postion'] == 'top'){
						$menu['top'][] = $val['id'];
					}elseif($val['postion'] == 'user'){
						$menu['user'][] = $val['id'];
					}else{
						$menu['more'][] = $val['id'];
					}
				}
				$navigation = serialize($menu);
				$m_user->navigation = $navigation;
				if($re_id = $m_user->add()){
					$data['position_id'] = $_POST['position_id'];
					$data['user_id'] = $re_id;
					if($role_id = $m_role->add($data)){
						$m_user->where('user_id = %d', $re_id)->setField('role_id', $role_id);
						actionLog($re_id);
						if($_POST['submit'] == '添加'){
							alert('success', '添加成功，该用户已可以登录系统!', U('user/index'));
						}else{
							alert('success', '添加成功，该用户已可以登录系统!', U('user/add'));
						}
					}
				}else{
					alert('error','添加失败，请联系管理员！',$_SERVER['HTTP_REFERER']);
				}
			}
		} else {
			$m_config = M('Config');
			if($m_config->where('name = "smtp"')->find()){
				$category = M('user_category');
				$m_position = M('position');
				if(!session('?admin')){
					$department_list = getSubDepartment2(session('department_id'), M('role_department')->select(), 1);
				}else{
					$department_list =  M('role_department')->select();
				}
				
				$where['department_id'] = session('department_id');
				$position_list = getSubPosition(session('position_id'), $m_position->where($where)->select());

				$position_id_array = array();
				foreach($position_list as $k => $v){
					$position_id_array[] = $v['position_id'];
				}
				$where['position_id'] = array('in', implode(',', $position_id_array));
				$role_list = $m_position->where($where)->select();
				
				if(empty($role_list) && !session('?admin')){
					alert('error', '您没有添加用户的权限!', U('setting/smtp'));
				}else{
					$this->categoryList = $category->select();
					$this->assign('department_list', $department_list);
					$this->alert = parseAlert();
					$this->display();
				}
			} else {
				alert('error','请先设置smtp用于邀请用户',U('setting/smtp'));
			}
		}
	}

	
	
	public function active() {
		$verify_code = trim($_REQUEST['verify_code']);
		$user_id = intval($_REQUEST['user_id']);
		$m_user = M('User');
		$user = $m_user->where('user_id = %d', $user_id)->find();
		if (is_array($user) && !empty($user)) {
			if (md5(md5($user['reg_time']) . $user['salt']) == $verify_code) {
				if ($_REQUEST['password']) {
					$password = md5(md5(trim($_REQUEST["password"])) . $user['salt']);
					$m_user->where('user_id =' . $_REQUEST['user_id'])->save(array('password'=>$password,'status'=>1, 'reg_time'=>time(), 'reg_ip'=>get_client_ip()));
					alert('success', '设置密码成功，请登录', U('user/login'));
				} else {
					$this->alert = parseAlert();
					$this->display();
				}
			} else {
				$this->error('找回密码链接无效或链接已失效！');
			}
		} else {
			$this->error('找回密码链接无效或链接已失效！');
		}
	}

	public function test(){
		echo 'begin-------';echo '<br />';
		for ($i = 0; $i < 111; $i++){
			$array = $this->GetMonth(-$i);
			echo ($array['start_time']); echo ' 转为时间：'; echo (date('Y-m-d H:i;s', $array['start_time']));
			echo ($array['end_time']); echo ' 转为时间：'; echo (date('Y-m-d H:i;s', $array['end_time'])); echo '<br />';
		}
		echo 'end-------'; die();
	}
	
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
}