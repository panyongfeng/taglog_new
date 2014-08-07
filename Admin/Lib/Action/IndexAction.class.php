<?php 
// 
class IndexAction extends Action {
    
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('index')
		);
		B('Authenticate', $action);
	}
	
	public function index(){
		if (!F('smtp')) {
			alert('info', '<font style="color:red;">SMTP信息未配置 (无法使用密码找回功能)</font>&nbsp;&nbsp;&nbsp;&nbsp;<a href="' . U('setting/smtp') .'">点此设置</a>');
		}
		$this->alert = parseAlert();
		$this->display();
	}
}