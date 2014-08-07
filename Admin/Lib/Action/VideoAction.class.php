<?php
class VideoAction extends Action{
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array()
		);
		B('Authenticate', $action);
	}

	public function index(){ 
		$m_video = D('Video');
		$params = array();
		$_GET['content'] == 'free' ? $where['product_id'] = 0 : '';	

		if($_GET['order'] == 'ut_up') {
			$order = 'update_time';
		} else {
			$order = 'update_time desc';
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
		$params[] = 'content=' . trim($_GET['content']);
		$parameter = implode('&', $params);
		$this->parameter = $parameter;
		if($_GET['order']) $parameter .= '&order='.trim($_GET['order']);
		
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		$list = $m_video->where($where)->order($order)->page($p.',15')->select();
		$count = $m_video->where($where)->count();
		import("@.ORG.Page");
		$Page = new Page($count,15);
		
		
		$Page->parameter = $parameter;
		$show = $Page->show();		
		$this->assign('page',$show);

		
		foreach($list as $k=>$v){
			$list[$k]['member'] = M('member')->where('member_id = %d', $v['member_id'])->find();
			if($_GET['content'] != 'free'){
				$list[$k]['product'] = M('product')->where('product_id = %d', $v['product_id'])->find();
			}
		}
		
		
		$this->assign('videolist',$list);
		$this->alert = parseAlert();
		$this->display();
	}  

	public function delete(){
		$m_video = M('video');
		$vid = $_REQUEST['vid'];
		if ('' == $vid) {
			alert('error', '您没有选择任何内容！', $_SERVER['HTTP_REFERER']);
		} else {
			$m_video->startTrans(); 

			$result = json_decode(file_get_contents('http://v.polyv.net/uc/services/rest?method=delVideoById&writetoken=ql0zuWS75mvbgAy-w1NvhywuXqnj1vTL&vid='.$vid));
			if($result->error == 0){
				if($m_video->where('vid = "%s"', $vid)->delete()){
					$m_video->commit();
					if($this->isAjax()){
						$this->ajaxReturn($vid,'删除成功',1);
					}else{
						alert('success', '删除成功，请到视频管理后台回收站查看!',$_SERVER['HTTP_REFERER']);
					}
				} else {
					$m_video->rollback();
					
					if($this->isAjax()){
						$this->ajaxReturn($vid,'删除失败',0);
					}else{
						alert('error', '删除失败！', $_SERVER['HTTP_REFERER']);
					}
				}
			}else{	
				$m_video->rollback();
				if($this->isAjax()){
					$this->ajaxReturn($vid,'删除失败',0);
				}else{
					alert('error', '删除失败！', $_SERVER['HTTP_REFERER']);
				}
			}
		
		}
	}
	
	public function update(){
		$m_video = M('Video');
		$page_num = intval($_GET['page_num']);
		$page_size = 10;
		if($page_num){
			$get_url = 'http://v.polyv.net/uc/services/rest?method=getNewList&readtoken=f55NjipZmT-XZ40j3WYv1-LbJJc6ScM0&pageNum='.$page_num.'&numPerPage='.$page_size;
			$result = json_decode(file_get_contents($get_url));
			
			$return_info = '第'.$page_num.'页（'.(($page_num-1)*10).'—'.(($page_num-1)*10+count($result->data)).'条）:视频';
			if($page_num == 1) $m_video->where(1)->setField('in_polyv', 0);
			$save_data['in_polyv'] = 1;
			foreach($result->data as $v){
				if($m_video->where('vid = "%s"', $v->vid)->find()){
					$m_video->where('vid = "%s"', $v->vid)->save($save_data);
				}else{
					$temp = array();
					$temp['vid'] = $v->vid;
					$temp['status'] = 1;
					$temp['is_updated'] = 1;
					$temp['in_polyv'] = 1;
					$m_video->add($temp);
					$return_info.=$v->vid.', ';
				}
			}
			
			if(count($result->data) < 10){
				$data['nexpage'] = 0;
				$this->ajaxReturn($data,$return_info,0);
			}else{
				$data['nexpage'] = $page_num+1;
				$this->ajaxReturn($data,$return_info,1);
			}
		}
		
	}
}