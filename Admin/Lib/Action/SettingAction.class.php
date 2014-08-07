<?php
	class SettingAction extends Action{
		public function _initialize(){
			$action = array(
				'permission'=>array(''),
				'allow'=>array('index')
			);
			B('Authenticate',$action);
		}

		public function index(){
			if ($this->isAjax()) {
				if($_POST['address']){
					if (ereg('^([a-zA-Z0-9]+[_|_|.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|_|.]?)*[a-zA-Z0-9]+.[a-zA-Z]{2,3}$',$_POST['address'])){
						if (ereg('^([a-zA-Z0-9]+[_|_|.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|_|.]?)*[a-zA-Z0-9]+.[a-zA-Z]{2,3}$',$_POST['test_email'])){
							$smtp = array('MAIL_ADDRESS'=>$_POST['address'],'MAIL_SMTP'=>$_POST['smtp'],'MAIL_PORT'=>$_POST['port'],'MAIL_LOGINNAME'=>$_POST['loginName'],'MAIL_PASSWORD'=>$_POST['password'],'MAIL_SECURE'=>$_POST['secure'], 'MAIL_CHARSET'=>'UTF-8','MAIL_AUTH'=>true,'MAIL_HTML'=>true);
							C($smtp,'smtp');
							import('@.ORG.Mail');
							$content ='这是一封悟空CRM系统自动生成测试邮件，如果你成功收到，证明悟空smtp设置成功！请勿回复';
							if(SendMail($_POST['test_email'],'悟空CRM邮箱测试',$content,'悟空CRM管理员')){
								$message = '发送成功！';
							} else {
								$message = '发送失败，信息错误！';
							}
						} else {
							$message = '测试收件箱格式错误!';
						}
					} else {
						$message = '邮箱格式错误！';
					}
					$this->ajaxReturn("", $message, 1);
				}else{
					if($_POST['uid'] && $_POST['passwd'] && $_POST['phone']){
						$result = sendtestSMS(trim($_POST['uid']), trim($_POST['passwd']), $_POST['phone']);
						if(strstr(trim($_POST['uid']), 'BST') === false){
							$message = '账号名格式错误!';
						}elseif($result == 0 || $result == 1){
							$message = '发送成功,请先保存设置，短信如有延迟，请稍候确认！';
						}elseif($result == -1 ){
							$message = '账号未注册，请联系悟空CRM客服!';
						}elseif($result == -3 ){
							$message = '密码错误!';
						}else{
							$message = '发送失败，请确认短信接口信息!';
						}
					}else{
						$message = '发送失败，请确认短信接口信息!';
					}
					$this->ajaxReturn("", $message, 1);
				}
			} elseif($this->isPost()) {
				$edit = false;
				$m_config = M('Config');
				if($_POST['address']){
					if(is_email($_POST['address'])){
						$value = array('MAIL_ADDRESS'=>$_POST['address'],'MAIL_SMTP'=>$_POST['smtp'],'MAIL_PORT'=>$_POST['port'],'MAIL_LOGINNAME'=>$_POST['loginName'],'MAIL_PASSWORD'=>$_POST['password'],'MAIL_SECURE'=>$_POST['secure'],'MAIL_CHARSET'=>'UTF-8','MAIL_AUTH'=>true,'MAIL_HTML'=>true);
						$smtp['name'] = 'smtp';
						$smtp['value'] =serialize($value);
						if($m_config->where('name = "smtp"')->find()){
							if($m_config->where('name = "smtp"')->save($smtp)){
								F('smtp',$smtp,__ROOT__.'/App/Runtime/Data/');
								$edit = true;
							}
						} else {
							if($m_config->add($smtp)){
								F('smtp',$smtp,__ROOT__.'/App/Runtime/Data/');
								$edit = true;
							}else{
								alert('error','添加失败，请联系管理员！',U('setting/index'));
							}
						}
					}else{
						alert('error','邮箱格式错误！',U('setting/index'));
					}
					
				}

				if($edit){
					alert('success','设置成功并保存！',U('setting/index'));
				}else{
					alert('error','数据无变化',U('setting/index'));
				}
			} else {
				$smtp = M('Config')->where('name = "smtp"')->getField('value');
				$this->limit_video_size = M('Config')->where('name = "limit_video_size"')->getField('value');
				$this->smtp = unserialize($smtp);
				$this->alert = parseAlert();
				$this->display();			
			}
		}
		
		public function info(){
			$data['value'] = $this->_post('limit_video_size', 'intval');
			$data['name'] = 'limit_video_size';
			$m_config = M('Config');
			if($m_config->where('name = "limit_video_size"')->find()){
				if($m_config->where('name = "limit_video_size"')->save($data)){
					$edit = true;
				}
			} else {
				if($m_config->add($data)){
					$edit = true;
				}else{
					alert('error','添加失败，请联系管理员！',U('setting/index'));
				}
			}
			if($edit){
				F('limit_video_size',$data['value'],'./App/Runtime/Data/');
				alert('success','设置成功并保存！',U('setting/index'));
			}else{
				alert('error','数据无变化',U('setting/index'));
			}
		}
	}