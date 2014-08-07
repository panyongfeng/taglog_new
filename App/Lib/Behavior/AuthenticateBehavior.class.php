<?php 

class AuthenticateBehavior extends Behavior {
	protected $options = array();
	
	public function run(&$params) {
		$a = ACTION_NAME;
		$allow = $params['allow'];
		$permission = $params['permission'];
		if (in_array($a, $permission)) {
			return true;
		} elseif (session('?member_id')) {
			if (in_array($a, $allow)) {
				return true;
			}else{
				header('Location: ' . U('member/alertinfo'));
				die();
			}
		} else {
			header('Location: ' . U('member/alertinfo'));
			die();
		}
	}
}