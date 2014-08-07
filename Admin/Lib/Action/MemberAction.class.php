<?php
class MemberAction extends Action{
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array()
		);
		B('Authenticate', $action);
	}

	public function index(){
		$m_member = M('Member');
		$by = isset($_GET['by']) ? trim($_GET['by']) : '';
		$order = isset($_GET['order']) ? trim($_GET['order']) : '';
		$where['status'] = 1;
		$params = array();
		
		switch ($order) {
			case 'rt_up' :
				$order = 'reg_time';
				break;
			case 'rt_down' :
				$order = 'reg_time desc';
				break;
			case 'lt_up' : 
				$order = 'last_login_time';
				break;
			case 'lt_down' : 
				$order = 'last_login_time desc';
				break;
			default:
				$order = 'reg_time desc';
				break;
		}
		switch ($by) {
			case 'disable' :
				$where['status'] = 2;
				break;
			case 'today' :
				$where['reg_time'] = array('between',array(strtotime(date('Y-m-d')) -1 ,strtotime(date('Y-m-d')) + 86400));
				break;
			case 'week' : 
				$week = (date('w') == 0)?7:date('w');
				$where['reg_time'] = array('between',array(strtotime(date('Y-m-d')) - ($week-1) * 86400 -1 ,strtotime(date('Y-m-d')) + (8-$week) * 86400));
				break;
			case 'month' : 
				$next_year = date('Y')+1;
				$next_month = date('m')+1;
				$month_time = date('m') ==12 ? strtotime($next_year.'-01-01') : strtotime(date('Y').'-'.$next_month.'-01');
				$where['reg_time'] = array('between',array(strtotime(date('Y-m-01')) -1 ,$month_time));
				break;
		}

		if ($_REQUEST["field"]) {
			$field = $_REQUEST['field'];
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
			if	('reg_time' == $field || 'last_login_time' == $field) {
				$search = is_numeric($search)?$search:strtotime($search);
			}
			switch ($_REQUEST['condition']) {
				case "is" : $where[$field] = array('eq',$search);break;
				case "isnot" :  $where[$field] = array('neq',$search);break;
				case "contains" :  $where[$field] = array('like','%'.$search.'%');break;
				case "not_contain" :  $where[$field] = array('notlike','%'.$search.'%');break;
				case "start_with" :  $where[$field] = array('like',$search.'%');break;
				case "end_with" :  $where[$field] = array('like','%'.$search);break;
				case "is_empty" :  $where[$field] = array('eq','');break;
				case "is_not_empty" :  $where[$field] = array('neq','');break;
				case "gt" :  $where[$field] = array('gt',$search);break;
				case "egt" :  $where[$field] = array('egt',$search);break;
				case "lt" :  $where[$field] = array('lt',$search);break;
				case "elt" :  $where[$field] = array('elt',$search);break;
				case "eq" : $where[$field] = array('eq',$search);break;
				case "neq" : $where[$field] = array('neq',$search);break;
				case "between" : $where[$field] = array('between',array($search-1,$search+86400));break;
				case "nbetween" : $where[$field] = array('not between',array($search,$search+86399));break;
				case "tgt" :  $where[$field] = array('gt',$search+86400);break;
				default : $where[$field] = array('eq',$search);
			}
			$params = array('field='.$field, 'condition='.$condition, 'search='.trim($_REQUEST["search"]));
		}
		
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		$list = $m_member->where($where)->order($order)->page($p.',15')->select();
		$count = $m_member->where($where)->count();
		
		import("@.ORG.Page");
		$Page = new Page($count,15);
		$params[] = 'by =' . trim($_GET['by']);
		$parameter = implode('&', $params);
		$this->assign('parameter',$parameter);
		
		if($_GET['order']) $parameter .= '&order='.trim($_GET['order']);
		$Page->parameter = $parameter;
		$show = $Page->show();		
		
		$this->assign('page',$show);
		$this->assign('memberlist',$list);
		$this->alert = parseAlert();
		$this->display();
	}  

	public function disablemember(){
		$member_id = intval($_GET['member_id']);
		if ($this->isAjax() && $member_id) {
			$m_member = M('Member');
			$where['member_id'] = $member_id;
			$status = $m_member->where($where)->getField('status');
			if ($status == 2 && $m_member->where($where)->setField('status', 1)) {
				$this->ajaxReturn(1,'启用成功',1);
			} elseif ($status == 1 && $m_member->where($where)->setField('status', 2)) {
				$this->ajaxReturn(2,'停用成功',2);
			} else {
				$this->ajaxReturn(0,'参数错误!',0);
			}
		} else {
			$this->alert('非法操作！');
		}
	}
	
	public function edit(){
		$member_id = intval($_REQUEST['id']);
		if ($this->isPost()){
			if($member = M('Member')->where('member_id = %d', $member_id)->find()){
			
				if($_POST['email'])	$data['email'] = trim($_POST['email']);
				if($_POST['password'])	$data['password'] = md5(md5(trim($_REQUEST["password"])) . $member['salt']);
				if(M('Member')->where('member_id = %d', $member_id)->save($data)){
					alert('success', '修改成功！',trim($_POST['HTTP_REFERER']));
				}else{ echo $_POST['HTTP_REFERER'];
					alert('error', '数据无变化！',trim($_POST['HTTP_REFERER']));
				}
			}else{
				alert('error', '参数错误！',$_SERVER['HTTP_REFERER']);
			}
		}
	}
	
	public function dialogInfo(){
		$member_id = intval($_REQUEST['id']);
		$act = trim($_REQUEST['act']);
		if($member = M('Member')->where('member_id = %d', $member_id)->find()){
			$this->member = $member;
			$this->act = $act;
			$this->HTTP_REFERER = $_SERVER['HTTP_REFERER'];
			$this->display();
		}else{
			$this->error('参数错误！');
		}
		
	}
	
	public function sendmessage(){
		if ($this->isAjax()){
			if(sendMessage(intval($_POST['to_member_id']),$_POST['content'])){
				$this->ajaxReturn("","发送成功！",1);
			}else{
				$this->ajaxReturn("","发送失败！",0);
			}
		}else{
			alert('error', '非法访问！', $_SERVER['HTTP_REFERER']);
		}
	}

	public function helplogin(){
		$member_id = intval($_GET['member_id']);
		
		if($member = M('Member')->where('member_id = %d', $member_id)->find()){
			$email = $member['name'] ? $member['name'] : $member['email'];
			session('email', $email);
			session('member_id', $member['member_id']);
			
			if($_GET['act'] == 'editproduct' && $_GET['product_id']){
				$jump_url = 'index.php?m=product&a=edit&product_id='.intval($_GET['product_id']);
			}else if($_GET['act'] == 'showproduct' && $_GET['product_id']){
				$jump_url = 'index.php?m=product&a=view&product_id='.intval($_GET['product_id']);
			}else if($_GET['act'] == 'productlist'){
				$jump_url = 'index.php?m=member&a=index';
			}else{
				$jump_url = 'index.php?m=member&a=home';
			}
			$this->success('模拟登录成功，正在跳转！', $jump_url);
		}
		
	}
}