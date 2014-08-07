<?php
/**
 * 商家模块
 * @author 赵凡
 * @createtime 2014-6-3
*/
class MemberAction extends Action {

	public function _initialize(){
		$action = array(
			'permission'=>array('login','lostpw','register','resetpw','getverify','alertinfo','checkverify','uploadimg','islogin','feedback'),
			'allow'=>array('logout', 'index','home','updateinfo','resetpassword','uploadimg','saveavatar', 'openmessage','deletemessage','earn','tips', 'setqrlogo')
		);

		B('Authenticate', $action);
	}
	
	//登录
	public function login() {
		if (session('?member_id')) {
			$this->redirect('member/index',array(), 0, '');
		} elseif($this->isPost()) {
			$email = $this->_post('email','trim');
			$password = $this->_post('password','trim');
			if (!is_email($email)) {
				$this->error('您输入的邮箱不存在哦~');
			} elseif (!$password) {
				$this->error('请输入密码');
			} else {
				$m_member = M('Member');
				$member = $m_member->where(array('email' => trim($_POST['email'])))->find();
				if (!is_array($member)) {
					$this->error('用户名不存在~');
				} elseif ($member['status'] != 1) {
					$this->error('-_-! 您被封号了，亲,如有疑问，请联系官人解决');
				}
				if ($member['password'] == md5(md5(trim($_POST['password'])) . $member['salt'])) {
					$email = $member['short_name'] ? $member['short_name'] : $member['email'];
					session('email', $email);
					cookie('trueemail', $member['email']);
					session('member_id', $member['member_id']);
					$m_member->where(array('email' => trim($_POST['email'])))->setField('last_login_time', time());
					
					if(session('product_id')){
						//开始事务
						M('Product')->startTrans(); 
						$product_ids = explode(',', session('product_id'));
						foreach($product_ids as $k => $v){
							if(!M('Product')->where('product_id = %d', $v)->setField('member_id', $member['member_id'])){
								M('Product')->rollback();
							}elseif(M('ProductPage')->where('product_id = %d', $v)->find() && !M('ProductPage')->where('product_id = %d', $v)->setField('member_id', $member['member_id'])){
								M('Product')->rollback();
							}
						}
						M('Product')->commit();
					}
					
					$this->redirect('member/index', array(), 0, '页面跳转中...');
					//$this->success('登录成功!', U('member/index'), 1);
				} else {
					$this->error('输入密码不正确哦，请重新输入：-）');
				}
			}
		} else {
			$this->display();
		}
	}

	//注册
	public function register() {
		
		$d_member = D('Member');
		if ($this->isAjax()) {
			if (!is_email($this->_post('email','trim')) || $d_member->where('email = "%s"', $this->_post('email','trim'))->find()) {
				$this->ajaxReturn(1, "您申请的用户名已被使用，请换一个试试吧~", 1);
			} else {
				$this->ajaxReturn(0, "用户名可以使用:-)", 0);
			}
		} elseif ($this->isPost()) {
			if(session('?member_id')) $this->error('您已经登录了哦', U('member/index'));
			//填写密码
			if (!session('verify')){
				$this->error('验证码已失效，请重新输入:-)');
			} elseif ($this->_post('verify','md5') != session('verify')){
				$this->error('输入的验证码不正确，请重新输入:-)');
			} elseif (!is_email($this->_post('email','trim'))) {
				$this->error('请用您的常用邮箱注册，这样万一丢失密码可以通过邮箱找回:-)');
			} elseif ($d_member->where('email = "%s"', $this->_post('email','trim'))->find()) {
				$this->error('您申请的用户名已被使用，请换一个试试吧~');
			} elseif ($this->_post('email','trim') == ''){
				$this->error('邮箱可不能为空哦:-)');
			}  elseif ($this->_post('password','trim') == ''){
				$this->error('密码可不能为空哦:-)');
			} elseif (strlen($this->_post('password','trim')) < 6){
				$this->error('密码最少六位哦:-)');
			} elseif ($this->_post('repassword','trim') != $this->_post('password','trim')){
				$this->error('两次密码输入不一致哦，请重新输入一次~');
			}
			if ($d_member->create() && $member_id = $d_member->add()) {
				session('email', $this->_post('email','trim'));
				session('member_id', $member_id);
				
				if(session('product_id')){
					//开始事务
					M('Product')->startTrans(); 
					$product_ids = explode(',', session('product_id'));
					foreach($product_ids as $k => $v){
						if(!M('Product')->where('product_id = %d', $v)->setField('member_id', $member_id)){
							M('Product')->rollback();
						}else if(M('ProductPage')->where('product_id = %d', $v)->find() && !M('ProductPage')->where('product_id = %d', $v)->setField('member_id', $member_id)){
							M('Product')->rollback();
						}
					}
					M('Product')->commit();
				}
				
				$this->redirect('member/index', array(), 0, '页面跳转中...');
				//$this->success('注册成功，现在可以登录了！', U('member/login'));
			} else {
				$this->error('注册失败, 请联系管理员', U('member/login'));
			}
		} else {
			if(session('?member_id')) $this->error('您已经登录了哦', U('member/index'));
			$category = M('user_category');
			$this->categoryList = $category->select();
			$this->display();
		}
	}

	//商家根目录
	public function index(){
		if($this->_get('categoryid','trim') == 'default'){
			$where['category_id'] = 0;
		}elseif($this->_get('categoryid','intval')){
			$where['category_id'] = $this->_get('categoryid','intval');
		}
		
		$where['member_id'] = $member_id = session('member_id');
		$productlist = M('Product')->where($where)->order('product_id')->select();

		foreach($productlist as $k => $v){
			if($v['category_id'])	$productlist[$k]['category'] = M('ProductCategory')->where(array('member_id'=>$member_id,'category_id'=>$v['category_id']))->find();
		}
		$categorylist = M('ProductCategory')->where(array('member_id'=>$member_id))->order('category_id')->select();
		
		$this->productlist = $productlist;
		$this->categorylist = $categorylist;
		$this->display();
	}

	//邮箱找回密码重置
	public function resetpw(){
		if(session('?member_id')) $this->error('您已经登录了哦', U('member/index'));
		
		$verify_code = trim($_REQUEST['verify_code']);
		$member_id = intval($_REQUEST['member_id']);
		$m_member = M('Member');
		$member = $m_member->where('member_id = %d', $member_id)->find();
		session(null);
		cookie('member_id',null);
		cookie('email',null);
		
		if (!empty($member)) {
			if ((time()-$member['lostpw_time'])>86400){
				$this->error('链接已失效', U('member/lostpw'));
			}elseif (md5(md5($member['lostpw_time']) . $member['salt']) == $verify_code){
				if ($_REQUEST['password']) {
					$password = md5(md5(trim($_REQUEST["password"])) . $member['salt']);
					$m_member->where('member_id =' . $_REQUEST['member_id'])->save(array('password'=>$password, 'lostpw_time'=>0));
					$this->success('密码修改成功,请登录~', U('member/login'));
				} else {
					$this->display();
				}
			}else{
				$this->error('链接已失效:-)');
			}
		} else {
			$this->error('链接信息错误:-)');
		}
	}

	//个人中心密码重置
	public function resetpassword(){
		if($this->isPost()){
			$member_id = intval(session('member_id'));
			if($member = M('Member')->where('status = 1 and member_id = %d', $member_id)->find()){
				$password = $this->_post('password','trim');
				$new_password = $this->_post('new_password','trim');
				$re_password = $this->_post('repassword','trim');
				
				if(!$password || !$new_password || !$re_password){
					$this->error('三项必填,密码可不能为空哦~');
				} elseif (strlen($password) < 6 || strlen($new_password) < 6){
					$this->error('密码最少六位哦~');
				}elseif ($new_password != $re_password){
					$this->error('两次密码输入不一致哦，请重新输入一次~');
				} elseif (md5(md5(trim($password)) . $member['salt']) != $member['password']){
					$this->error('原密码错误，一定要填写正确的哦~');
				} elseif ($password == $new_password) {
					$this->error('密码没有变化哦~');
				}  else {
					if(M('Member')->where('member_id = %d', $member_id)->setField('password', md5(md5(trim($new_password)) . $member['salt']))){
						session(null);
						cookie('member_id',null);
						cookie('email',null);
						$this->success('试试新密码，重新登录下吧:-)', U('member/login'));
					}else{
						$this->error('密码修改失败:-)', U('member/login'));
					}
				}
			} else {
				$this->error('账号状态异常:-)');
			}
			
			
		}
		
	}
	
	//找回密码
	public function lostpw() {
		if($this->isAjax()){
			if(session('?member_id')) $this->ajaxReturn(0, "您已经登录了哦", 0); 
			if ($_POST['checkinfo']){
				import('@.ORG.Mail');
				$m_member = M('Member');
				$member = $m_member->where('email = "%s"', $this->_post('checkinfo', 'trim'))->find();
				if(!empty($member)){
					$lost_time = time();
					$m_member->where('member_id = %d', $member['member_id'])->setField('lostpw_time', $lost_time);
					$verify_code = md5(md5($lost_time) . $member['salt']);
					C(F('smtp'),'smtp');
					
					$url = U('member/resetpw', 'member_id='.$member['member_id'].'&verify_code='.$verify_code,'','',true);
					$content = '尊敬的用户，请点击以下链接，重新设置你的用户宝密码：' . $url;

					if (SendMail($member['email'], '您可通过此邮件找回用户宝密码', $content,'用户宝管理员')){
						$this->ajaxReturn(1, "发送成功", 1); 
					}else{
						$this->ajaxReturn(1, "发送失败", 2); 
					}
				}else{
					$this->ajaxReturn(0, "您的邮箱未注册，请检查是否拼写错误", 3); 
				}
			} else {
				$this->ajaxReturn(0, "您填写您的注册信息", 0); 
			}
		} else{
			if(session('?member_id')) $this->error('您已经登录了哦', U('member/index'));
			if (!F('smtp')) {
			echo 3; die();
				$this->error('该功能无法使用，邮箱未配置');
			}
			$this->display();
		}
	}

	//退出
	public function logout() {
		session('member_id', null);
		session('product_id', null);
		session('email', null);
		$this->success('成功退出!', U('member/login'));
	}

	//提示界面
	public function alertinfo(){
		if(session('?member_id')) $this->message = '非法操作:-)';
		else $this->message = '您当前处于未登录状态，请先登录后进行操作~';
		$this->jumpUrl = U('member/login');
		$this->waitSecond = 1;
		$this->display('Public:message');
	}
	
	//用户反馈
	public function feedback(){
		$m_feedback = M('Feedback');
		if ($this->isAjax()) {
			$content = $this->_post('content','trim');
			$email = $this->_post('email','trim');
			
			if ($content) {
				$data['content'] = $content;
				$data['email'] = $email;
				$data['member_id'] = intval(session('member_id'));
				$data['create_time'] = time();
				if ($result = $m_feedback->add($data)) { 
					$this->ajaxReturn($result, "谢谢您的意见，我们会做的更好的~", 1); 
				} else {
					$this->ajaxReturn(0, "添加失败~", 0); 
				}
			} else {
				if(!trim($_POST['content'])){
					$this->ajaxReturn(0, "您忘记写反馈内容了:-)", 0);
				}elseif(!trim($_POST['email'])){
					$this->ajaxReturn(0, "您忘记留下接收回复的邮箱了:-)", 0);
				}
				
			}
		} else {
			$this->error('操作错误！');
		}
	}

	//获取验证码
	public function getverify(){
		import('@.ORG.Image');
		Image::buildImageVerify();
	}
	
	//验证验证码
	public function checkverify(){
		if($this->isAjax()){
			if (!$this->_get('verify','trim')){
				$this->ajaxReturn(2, "请输入验证码哦",0);
			} elseif (!session('verify')) {
				$this->ajaxReturn(3, "验证码失效了，再刷新一次吧~",0);
			} elseif ($this->_get('verify','md5') == session('verify')){
				$this->ajaxReturn(1, "验证码正确:-)",1);
			} else {
				$this->ajaxReturn(4, "输入的验证码不正确，请重新输入:-)",0);
			}
		}
	} 	

	//更新个人资料
	public function updateinfo(){
		$field_name = $this->_post('name', 'trim');
		$value = filtStr($this->_post('value', 'trim'));
		$member_id = intval(session('member_id'));
		if($field_name && $member_id){
			switch($field_name){
				case 'name' : 
					$field_name = 'name'; break;
				case 'true_name' : 
					$field_name = 'true_name'; break;
				case 'qq' : 
					$field_name = 'qq'; break;
				case 'phone_number' : 
					$field_name = 'phone_number'; break;
				case 'company_name' : 
					$field_name = 'company_name'; break;
				case 'short_name' : 
					$field_name = 'short_name'; break;
				case 'company_address' : 
					$field_name = 'company_address'; break;
				case 'company_description' :
					$field_name = 'company_description'; break;
				case 'company_website' : 
					$field_name = 'company_website'; break;
				case 'company_salelink' : 
					$field_name = 'company_salelink'; break;
				default:
					$this->ajaxReturn(0, "非法操作", 0); break;
			}
			
			if(M('Member')->where('member_id = %d', $member_id)->setField($field_name, $value)){
				$member = M('Member')->where('member_id = %d', $member_id)->find();
				if($field_name = 'short_name') $member['short_name'] ? session('email', $member['short_name']) : session('email', $member['email']);
				$this->ajaxReturn($value, "资料修改成功", 1);
			}else{
				$this->ajaxReturn(0, "资料修改失败", 0);
			}
		}else{
			$this->ajaxReturn(0, "参数错误", 0);
		}
		
	}
	
	//个人中心
	public function home(){
	
		$act = trim($_GET['act']);
		$member_id = intval(session('member_id'));
		$where['member_id'] = $member_id;
		$where['status'] = 1;
		if($member = M('member')->where($where)->find()){
			$member['product_count'] = M('product')->where($where)->count();
			$this->member = $member;
			$this->message_alert_count = M('Message')->where('read_time = 0 and member_id = %d', $member_id)->count();
			echo M('message')->_lastsql;
			if($act == 'avatar'){
				$this->display('avatar');
			}elseif($act == 'message'){
				$this->message_list = M('Message')->where('member_id = %d', $member_id)->order('create_time desc')->select();
				$this->display('message');
			}elseif($act == 'resetpassword'){
				$this->display('resetpassword');
			}else{
				$this->display();
			}
		}else{
			$this->error('用户不存在');
		}
		
	}

	public function openMessage(){
		
		if ($this->isAjax()) {
			$m_message = M('Message');
			$where['member_id'] = intval(session('member_id'));
			$where['message_id'] = intval($_GET['message_id']);
			if ($m_message->where($where)->setField('read_time', time())) {
				$this->ajaxReturn(1, "打开成功",1);
			} else {
				$this->ajaxReturn(0, "打开失败",0);
			}
		}
	}
	
	public function deleteMessage(){
		if ($this->isAjax()) {
			$m_message = M('Message');
			$where['member_id'] = intval(session('member_id'));
			$where['message_id'] = intval($_GET['message_id']);
			if ($m_message->where($where)->delete()) {
				$this->ajaxReturn(1, "打开成功",1);
			} else {
				$this->ajaxReturn(0, "打开失败",0);
			}
		}
	}
	
	//上传头像
	public function uploadimg(){
		import('@.ORG.UploadFile');
		$upload = new UploadFile();
		$upload->maxSize = 1*1024*1024;	
		$upload->uploadReplace = true;
		$upload->allowExts = array('jpg','png');
		$dirname = './Uploads/avatar/temp/';
		if (!is_dir($dirname) && !mkdir($dirname, 0777, true)){
			$this->ajaxReturn(0,'上传目录不可写',0);
			die();
		}
		$upload->savePath = $dirname;
		
		if(!$upload->upload()) {						// 上传错误提示错误信息
			$this->ajaxReturn('',$upload->getErrorMsg(),0,'json');
		}else{											// 上传成功 获取上传文件信息
			$info =  $upload->getUploadFileInfo();
			$temp_size = getimagesize($info[0]['savepath'].$info[0]['savename']);
			if($temp_size[0] < 100 || $temp_size[1] < 100){//判断宽和高是否符合头像要求
				$this->ajaxReturn(0,'图片宽或高不得小于100px',0,'json');
			}
			$this->ajaxReturn($info[0]['savepath'].$info[0]['savename'],$info,1,'json');
		}
	}
	
	//裁剪并保存用户头像
	public function saveavatar(){
		$where['status'] = 1;
		$where['member_id'] = intval(session('member_id'));
		$member = M('Member')->where($where)->find();
		if($member){
			//图片裁剪数据
			$params = $this->_post();						//裁剪参数
			if(!isset($params) && empty($params)){
				return;
			}
		
			//头像目录地址
			$path = './Uploads/avatar/';
			//要保存的图片
			$real_path = $path.md5($member['email'].time()).'.png';
			//临时图片地址
			$pic_path = $params['src'];
			import('@.ORG.ThinkImage.ThinkImage');
			$Think_img = new ThinkImage(THINKIMAGE_GD); 
			//裁剪原图
			$Think_img->open($pic_path)->crop($params['w'],$params['h'],$params['x'],$params['y'])->save($real_path);
			//生成缩略图
			$Think_img->open($real_path)->thumb(100,100, 1)->save($real_path);
			
			unlink($params['src']);
			if(M('Member')->where($where)->setField('avatar',$real_path)){
				unlink($member['avatar']);
				$this->redirect('member/home', array(), 0, '页面跳转中...');
			}else{
				unlink($real_path);
				$this->success('上传头像失败');
			}
		}
		
	}

	public function islogin(){
		if($this->isAjax()){
			if (session('member_id')){
				$this->ajaxReturn(1, "登录状态", 0);
			} else {
				$this->ajaxReturn(0, "登录超时或退出", 0);
			}
		}
	}
	
	public function earn(){
		if($this->isAjax()){
			$product_id = intval($_GET['product_id']);
			$verify = trim($_GET['verify']);
			if($product_id && $verify){
				if($product = M('product')->where('product_id = %d and verify = "%s"', $product_id, $verify)->find()){
					if($product['member_id']){
						$this->ajaxReturn(0, "您已经认领过了", 2);
					}else{
						if(M('product')->where('product_id = %d and verify = "%s"', $product_id, $verify)->setField('member_id', session('member_id'))){
							M('Page')->where('product_id = %d', $product_id)->setField('member_id', session('member_id'));
							M('Video')->where('product_id = %d', $product_id)->setField('member_id', session('member_id'));
							$this->ajaxReturn(1, "恭喜您认领成功", 1);
						}else{
							$this->ajaxReturn(0, "认领失败", 0);
						}
					}
				}else{
					$this->ajaxReturn(0, "参数错误", 0);
				}
			}else{
				$this->ajaxReturn(0, "参数错误", 0);
			}
		}
	}
	
	public function tips(){
		if($this->isAjax()){
			$new_num = M('Message')->where('read_time = 0 and member_id = %d', intval(session('member_id')))->count();
			$this->ajaxReturn($new_num,"",1);
		}
	}
	
	public function setQrLogo(){
		if($this->isAjax()){
			$check_logo = trim($_GET['checked']) == 'true' ? 1 : 0;
			if(M('member')->where('member_id = %d', intval(session('member_id')))->setField('check_logo', $check_logo)){
				$this->ajaxReturn(1,"设置成功",1);
			}else{
				$this->ajaxReturn(0,"设置失败",0);
			}
		}
	}
}