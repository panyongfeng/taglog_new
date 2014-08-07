<?php
class ProductAction extends Action{
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array()
		);
		B('Authenticate', $action);
	}

	public function index(){ 
		$m_product = M('Product');
		$params = array();
		$where['status'] = 1;
		$by = isset($_GET['by']) ? trim($_GET['by']) : '';
		$_GET['content'] == 'free' ? $where['member_id'] = 0 : $where['member_id'] = array('gt', 0);	
		$order = isset($_GET['order']) ? trim($_GET['order']) : '';
		
		switch ($order) {
			case 'hits_up' :
				$order = 'hits';
				break;
			case 'hits_down' :
				$order = 'hits desc';
				break;
			case 'ct_up' : 
				$order = 'create_time';
				break;
			case 'ct_down' : 
				$order = 'create_time desc';
				break;
			case 'ut_up' : 
				$order = 'update_time';
				break;
			case 'ut_down' : 
				$order = 'update_time desc';
				break;
			case 'lt_up' : 
				$order = 'last_view_time';
				break;
			case 'lt_down' : 
				$order = 'last_view_time desc';
				break;
			default:
				$order = 'update_time desc';
				break;
		}
		
		switch ($by) {
			case 'today' :
				$where['create_time'] = array('between',array(strtotime(date('Y-m-d')) -1 ,strtotime(date('Y-m-d')) + 86400));
				break;
			case 'disable' :
				$where['status'] = 2;
				break;
			case 'deleted' :
				$where['status'] = 3;
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
			if	('create_time' == $field || 'last_view_time' == $field || 'update_time' == $field) {
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
		$list = $m_product->where($where)->order($order)->page($p.',15')->select();
		$count = $m_product->where($where)->count();
		
		import("@.ORG.Page");
		$Page = new Page($count,15);
		$params[] = 'by=' . trim($_GET['by']);
		$params[] = 'content=' . trim($_GET['content']);
		$parameter = implode('&', $params);
		$this->parameter = $parameter;
		
		if($_GET['order']) $parameter .= '&order='.trim($_GET['order']);
		$Page->parameter = $parameter;
		$show = $Page->show();		
		$this->assign('page',$show);

		if($_GET['content'] != 'free'){
			foreach($list as $k=>$v){
				$list[$k]['member'] = M('member')->where('member_id = %d', $v['member_id'])->find();
			}
		}
		
		$this->assign('productlist',$list);
		$this->alert = parseAlert();
		$this->display();
	}  

	public function disableproduct(){
		$product_id = intval($_GET['product_id']);
		if ($this->isAjax() && $product_id) {
			$m_product = M('Product');
			$where['product_id'] = $product_id;
			$status = $m_product->where($where)->getField('status');
			if ($status == 2 && $m_product->where($where)->setField('status', 1)) {
				$this->ajaxReturn(1,'已解除屏蔽',1);
			} elseif ($status == 1 && $m_product->where($where)->setField('status', 2)) {
				$this->ajaxReturn(2,'已屏蔽',2);
			} elseif ($status == 3 && $m_product->where($where)->setField('status', 1)) {
				$this->ajaxReturn(2,'已恢复',3);
			} else {
				$this->ajaxReturn(0,'参数错误!',0);
			}
		} else {
			$this->alert('非法操作！');
		}
	}

	public function delete(){
		$m_product = M('Product');
		
		$product_ids = is_array($_REQUEST['product_id']) ? implode(',', $_REQUEST['product_id']) : $_REQUEST['id'];
		if ('' == $product_ids) {
			alert('error', '您没有选择任何内容！', $_SERVER['HTTP_REFERER']);
		} else {
			$data = array('status'=>3);
			if($m_product->where('product_id in (%s)', $product_ids)->setField($data)){	
				alert('success', '已删除至回收站!',$_SERVER['HTTP_REFERER']);
			} else {
				alert('error', '删除失败，联系管理员！', $_SERVER['HTTP_REFERER']);
			}
		}
	}
	
	public function completeDelete(){
		$m_page = M('Page');
		$m_product = M('Product');
		
		$product_ids = is_array($_REQUEST['product_id']) ? implode(',', $_REQUEST['product_id']) : $_REQUEST['id'];
		if ($product_ids) {
			$where['product_id'] = array('in', $product_ids);
			if($_REQUEST['product_id']){
				$count = count($_REQUEST['product_id']);
			}else{
				$count = 1;
			}
			//判断说明书是否全部存在
			if ($m_product->where($where)->count() == $count) {
				//开启事务
				$m_product->startTrans(); 
				//删除说明书数据
				if ($result = $m_product->where($where)->delete()) { 
					//删除说明书页面数据
					if (!$m_page->where($where)->find() || $m_page->where($where)->delete()) {
						$m_product->commit();
						alert('success', '删除成功!',$_SERVER['HTTP_REFERER']);
					} else {
						$m_product->rollback();
						alert('error', '删除失败，请刷新页面后重试!',$_SERVER['HTTP_REFERER']);
					}
				} else {
					$m_product->rollback();
					alert('error', '删除失败，请刷新页面后重试!',$_SERVER['HTTP_REFERER']);
				}
			} else {
				alert('error', '您选中的部分产品说明书不存在!',$_SERVER['HTTP_REFERER']);
			}
		} else {
			alert('error', '参数错误!',$_SERVER['HTTP_REFERER']);
		}
	}
}