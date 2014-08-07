<?php 

class AuthenticateBehavior extends Behavior {
	protected $options = array();
	
	public function run(&$params) {
		$m = MODULE_NAME;
		$a = ACTION_NAME;
		$allow = $params['allow'];
		$permission = $params['permission'];

		if(!session('?user_id') && intval(cookie('user_id')) != 0 && trim(cookie('admin_name')) != '' && trim(cookie('salt_code')) != ''){
			$user = M('user')->where(array('user_id' => intval(cookie('user_id'))))->find();
			if (md5(md5($user['user_id'] . $user['name']).$user['salt']) == trim(cookie('salt_code'))) {
				session('admin', 1);
				session('admin_name', $user['admin_name']);
				session('user_id', $user['user_id']);
			}
		}
	
		if (session('?admin')) {
			return true;
		} elseif(in_array($a, $permission)) {
			return true;
		} else {
			alert('error',  '请先登录...', U('user/login'));
		}
	}
}