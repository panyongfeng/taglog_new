<?php
class FeedbackAction extends Action{
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array()
		);
		B('Authenticate', $action);
	}

	public function index(){
		$m_feedback = M('Feedback');
		$by = isset($_GET['by']) ? trim($_GET['by']) : '';
		$order = isset($_GET['order']) ? trim($_GET['order']) : '';
		$where['status'] = 1;
		$params = array();
		
		switch ($order) {
			case 'ct_up' :
				$order = 'create_time';
				break;
			case 'ct_down' :
				$order = 'create_time desc';
				break;
			default:
				$order = 'create_time desc';
				break;
		}
		switch ($by) {
			case 'today' :
				$where['create_time'] = array('between',array(strtotime(date('Y-m-d')) -1 ,strtotime(date('Y-m-d')) + 86400));
				break;
			case 'week' : 
				$week = (date('w') == 0)?7:date('w');
				$where['create_time'] = array('between',array(strtotime(date('Y-m-d')) - ($week-1) * 86400 -1 ,strtotime(date('Y-m-d')) + (8-$week) * 86400));
				break;
			case 'month' : 
				$next_year = date('Y')+1;
				$next_month = date('m')+1;
				$month_time = date('m') ==12 ? strtotime($next_year.'-01-01') : strtotime(date('Y').'-'.$next_month.'-01');
				$where['create_time'] = array('between',array(strtotime(date('Y-m-01')) -1 ,$month_time));
				break;
		}

		if ($_REQUEST["field"]) {
			$field = $_REQUEST['field'];
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
			if	('create_time' == $field) {
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
		$list = $m_feedback->where($where)->order($order)->page($p.',15')->select();
		$count = $m_feedback->where($where)->count();

		foreach($list as $k=>$v){
			if($v['member_id']) $list[$k]['member'] = M('Member')->where('member_id = %d', $v['member_id'])->find();
		}
		
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

	public function delete(){
		$m_feedback = M('Feedback');
		
		$feedback_ids = is_array($_REQUEST['feedback_id']) ? implode(',', $_REQUEST['feedback_id']) : $_REQUEST['id'];
		if ('' == $feedback_ids) {
			alert('error', '您没有选择任何内容！', $_SERVER['HTTP_REFERER']);
		} else {
			if($m_feedback->where('feedback_id in (%s)', $feedback_ids)->delete()){	
				alert('success', '删除成功!',$_SERVER['HTTP_REFERER']);
			} else {
				alert('error', '删除失败，联系管理员！', $_SERVER['HTTP_REFERER']);
			}
		}
	}
	
}