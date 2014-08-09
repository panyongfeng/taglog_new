<?php 
/*
 * 产品展示页面
 *
 */
class ProductAction extends Action {
	
	public function _initialize(){
		$action = array(
			'permission'=>array('add', 'edit','view','mobileview', 'qrdownload', 'getvideostatus', 'sendvideostatus'),
			'allow'=>array('index', 'delete', 'changecategory')
		);

		B('Authenticate', $action);
	}
	
	public function index(){
		$this->display();
	}
	
	
	//添加说明书
	public function add() {
		if ($this->isAjax() || $this->isPost()) {
			$d_product = D('Product');
			$m_page = D('Page');
			$d_video = D('Video');
			
			$create_time = time();
			$member_id = intval(session('member_id'));
			$product['name'] = filtStr($this->_post('name', 'trim'));
			$product['description'] = filtStr($this->_post('description', 'trim'));
			$product['image'] = $this->_post('image', 'trim');
			$product['member_id'] = $member_id;
			
			$m_page->startTrans(); 
			if ($d_product->create($product)) {
				$d_product -> create_time = $create_time;
				//如果处于未登录状态

				if(!$member_id) $verify = $d_product->verify = substr(md5(substr(md5($create_time), 0, 4).substr(md5($product['name']), 0, 4)),0,8);
				if ($product_id = $d_product->add()) {
					$pages = $_POST['pages'];
					foreach($pages as $v){
						$page = array();
						$page['product_id'] = $product_id;
						$page['member_id'] = $member_id;
						$page['subject'] = trim($v['subject']) ? filtStr(trim($v['subject'])) : '默认标题';
						$content = $d_video->updateVideo($v['content'], $product_id);
						$page['content'] = serialize($content);
						$page['sort_id'] = intval($v['sort_id']);
						if (!$m_page -> add($page) || $content === false) {
							$is_failed = true;
							$m_page->rollback();
							break;
						}
					}
					if(!$is_failed){
						$m_page->commit();
					}else{
						if($this->isAjax())	$this->ajaxReturn(0, "保存失败，部分页面数据添加失败", 0) ;
						else	$this->error('保存失败，部分页面数据添加失败');
					}
					
					$jump_url = $member_id ? U('product/view', 'product_id='.$product_id) : U('product/view', 'product_id='.$product_id.'&verify='.$verify);
					if(!session('?member_id')) session('product_id',session('product_id').','.$product_id);
					if($this->isAjax()){
						$data['product_id'] = $product_id;
						$data['verify'] = $verify;
						$url = 'http://www.yhb360.com/recall/'.$data['product_id'].'/'.$data['verify'];
						if(session('?member_id'))	$this->ajaxReturn($data, "保存成功", 1) ;
						else	$this->ajaxReturn($data, "保存成功！<br/> 由于您尚未登录，直接离开将会丢失已编辑的内容。强烈建议您保存此链接:<a target='_blank' href='".$url."'>".$url."</a>，然后再离开。", 2) ;
					}else{
						$this->success('保存成功', $jump_url);
					}
					
				} else {
					if($this->isAjax())	$this->ajaxReturn(0, "保存失败".$d_product->getError(), 0) ;
					else	$this->error('保存失败,'.$d_product->getError());
				}
			} else {
				if($this->isAjax())	$this->ajaxReturn(0, "保存失败".$d_product->getError(), 0) ;
				else	$this->error('保存失败,'.$d_product->getError());
			}
		} else {
			$islogin = session('?email') ? 1 : 0;
			$this->islogin = $islogin;
			$this->display();
			
		}
	}

	public function view() {
		$member_id = intval(session('member_id'));
		$product_id = intval($_GET['product_id']);
		if ($product_id) {
			$m_product = M('Product');
			$m_page = M('Page');
			if ($product = $m_product->where('product_id = %d', $product_id)->find()) {
				if($product['status'] != 1) $this->error('该说明书已被停用');
				if (empty($product) || ($product['member_id'] ? ($product['member_id'] != $member_id) : (!$_GET['verify'] || trim($_GET['verify']) !=$product['verify']))) {
					if ($_GET['verify'] && $_GET['verify'] == $product['verify']){
						$message = '说明书已被认领，链接失效';
					} elseif ($_GET['verify']) {
						$message = '请检查您的链接是否正确';
					} elseif (empty($product)) {
						$message = '该产品说明书不存在';
					} else {
						$message = '您没有权限查看该说明书';
					}
					$this->error($message, U('index/index'));
				}
				if(!$product['member_id'] && $product['verify']) {
					$this->copylink = 'http://www.yhb360.com/recall/'.$product['product_id'].'/'.$product['verify'];
				}
			
				$pages = $m_page->where('product_id = %d', intval($product['product_id']))->select();
				foreach($pages as $k=>$v){
					$pages[$k]['content'] = D('Video')->checkStatus(unserialize($v['content']));
				}
				$this->link = 'http://www.yhb360.com/prd/'.$product['product_id'];
				$this->pages = $pages;
				$this->product = $product;
				$islogin = session('?email') ? 1 : 0;
				$this->islogin = $islogin;
				$this->display();
			} else {
				$this->error('该说明书不存在');
			}
		} else {
			$this->error('参数错误');
		}
	}
	
	public function mobileview(){
		$product_id = $this->_get('product_id','intval');
		if ($product_id) {
			$m_product = M('Product');
			$m_page = M('Page');
			if ($product = $m_product->where('product_id = %d', $product_id)->find()) {
				$m_product->where('product_id = %d', $product_id)->setInc('hits');
				if($product['status'] != 1) $this->error('该说明书已被停用', U('index/index'));
				$pages = $m_page->where('product_id = %d', $product['product_id'])->select();
				foreach($pages as $k=>$v){
					$pages[$k]['content'] = D('Video')->checkStatus(unserialize($v['content']));
				}
				$this->pages = $pages;
				$this->product = $product;
				if(isMobile()){
				//if(true){
					$this->display();
				}else{
					$this->display('cview');
				}
			} else {
				$this->error('该说明书不存在');
			}
		} else {
			$this->error('参数错误');
		}
	}

	//说明书编辑
	public function edit() {
		if($this->isPost() || $this->isAjax()){
			$d_product = D('Product');
			$d_video = D('Video');
			$m_page = M('Page');
			$member_id = intval(session('member_id'));
			$product['product_id'] = $this->_post('product_id', 'intval');
			$product['name'] = filtStr($this->_post('name', 'trim'));
			$product['description'] = filtStr($this->_post('description', 'trim'));
			$product['image'] = $this->_post('image', 'trim');
			$product['update_time'] = time();
			if($member_id != 0){
				$product['member_id'] = $member_id;
			}
			
			$old_product = $d_product->where('product_id = %d', $product['product_id'])->find();
 			if (empty($old_product) || ($old_product['member_id'] ? ($old_product['member_id'] != $member_id) : (!$_POST['verify'] || trim($_POST['verify']) !=$old_product['verify']))) {
				if($this->isAjax())	$this->ajaxReturn(100, "非法操作！", 0) ;
				else	$this->error('保存失败');
			} elseif ($old_product['status'] != 1){
				if($this->isAjax())	$this->ajaxReturn(0, "该说明书已被停用！", 0) ;
				else	$this->error('该说明书已被停用');
			}
			
			//开始事务
			$is_failed = false;
			$m_page->startTrans(); 
			if ($d_product->create($product)) {
				if ($d_product->save()) {
					//删除对应的相关页
					$m_page->where(array('product_id'=>$product['product_id']))->delete();
					$pages = $_POST['pages'];
					$d_video->where('product_id = %d', $product['product_id'])->setField('product_id', 0);

					foreach($pages as $v){
						$page = array();
						$page['product_id'] = $product['product_id'];
						if($member_id != 0) $page['member_id'] = intval(session('member_id'));
						$page['subject'] = trim($v['subject']) ? filtStr(trim($v['subject'])) : '默认标题';
						$content = $d_video->updateVideo($v['content'], $product['product_id']);
						$page['content'] = serialize($content);
						$page['sort_id'] = intval($v['sort_id']);
						//页面数据添加失败，回滚事务
						if (!$m_page -> add($page) || $content === false) {
							$is_failed = true;
							$m_page->rollback(); break;
						}
					}
					//提交事务
					if(!$is_failed) {
						$m_page->commit();
					} else {
						if($this->isAjax())	$this->ajaxReturn(0, "保存失败，部分页面数据异常", 0) ;
						else	$this->error('保存失败，部分页面数据异常', U('product/edit', 'product_id='.$product['product_id'].'&verify='.$product['verify']));
					}
					
					$jump_url = $member_id ? U('product/view', 'product_id='.$product['product_id']) : U('product/view', 'product_id='.$product['product_id'].'&verify='.$old_product['verify']);
					if(!session('?member_id')) session('product_id',session('product_id').','.$product['product_id']);
					if($this->isAjax())	{
						//$url = 'http://www.yhb360.com/recall/'.$product['product_id'].'/'.M('product')->where('product_id=%d',$product['product_id'])->getField('verify');
						if(session('?member_id'))	$this->ajaxReturn(1, "保存成功", 1) ;
						//else	$this->ajaxReturn(1, "保存成功！<br/> 由于您尚未登录，直接离开将会丢失已编辑的内容。强烈建议您保存此链接:<a target='_blank' href='".$url."'>".$url."</a>，然后再离开。", 2) ;
						else	$this->ajaxReturn(1, "保存成功！", 2) ;
					} else	{
						$this->success('保存成功', $jump_url);
					}
				} else {
					if($this->isAjax())	$this->ajaxReturn(1, "保存失败,".$d_product->getError(), 1) ;
					else	$this->success('保存失败，'.$d_product->getError());
				}
			} else {
				if($this->isAjax())	$this->ajaxReturn(1, "保存失败,".$d_product->getError(), 1) ;
				else	$this->error('保存失败,'.$d_product->getError());
				
			}
		}else{
			$member_id = intval(session('member_id'));
			$product_id = intval($_GET['product_id']);	

			$m_product = M('Product');
			$m_page = M('Page');
			
			$product = $m_product->where('product_id = %d', $product_id)->find();
			if (empty($product) || ($product['member_id'] ? ($product['member_id'] != $member_id) : (!$_GET['verify'] || trim($_GET['verify']) !=$product['verify']))) {

				if ($_GET['verify'] && $_GET['verify'] == $product['verify']){
					$message = '说明书已被认领，链接失效';
				} elseif ($_GET['verify']){
					$message = '请确认您的编辑链接是否正确';
				} elseif (empty($product)) {
					$message = '该产品说明书不存在';
				} else {
					$message = '您没有权限编辑该说明书';
				}
				$this->error($message);
			} else {
				$pages = $m_page->where('product_id = %d', intval($product['product_id']))->order('sort_id asc')->select();
				foreach($pages as $k=>$v){
					$pages[$k]['content'] = D('Video')->checkStatus(unserialize($v['content']));
				}
				if(!$product['member_id'] && $product['verify']) {
					$this->copylink = 'http://www.yhb360.com/recall/'.$product['product_id'].'/'.$product['verify'];
				}
				$islogin = session('?email') ? 1 : 0;
				$this->islogin = $islogin;
				$this->pages = $pages;
				$this->product = $product;
				$this->display();
				
			}
		}
	}
	//删除说明书
	public function delete() {
		$m_product = M('Product');
		$m_page = M('Page');
		if ($this->isAjax()) {
			$where['member_id'] = intval(session('member_id'));
			if ($_GET['product_id']) {
				$where['product_id'] = intval($_GET['product_id']);
				$count = 1;
			} elseif($_GET['product_ids']) {
				$product_id_array = explode(',', trim($_GET['product_ids']));
				$where['product_id'] = array('in', $product_id_array);
				$count = count($product_id_array)-1;
			} else {
				$this->ajaxReturn(0, "参数错误", 0);
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
						D('Video')->updateVideo(array(), $where['product_id']);
						$this->ajaxReturn($result, "删除说明书成功", 1); 
					} else {
						$m_product->rollback();
						$this->ajaxReturn(0, "删除失败", 0); 
					}
				} else {
					$m_product->rollback();
					$this->ajaxReturn(0, "删除失败", 0); 
				}
			} else {
				$this->ajaxReturn(0, "您选中的产品说明书不存在", 0); 
			}
		} elseif($this->isPost()){
			
		} else {
			$this->error('操作错误');
		}
	}
	
	public function changecategory(){
		$m_product = M('Product');
		$m_product_category = M('ProductCategory');
		if ($this->isAjax()) {
			$category_id = intval($_GET['category_id']);
			$member_id = intval(session('member_id'));
			$product_id = intval($_GET['product_id']);
			
			if ($category_id && $product_id) {
				if (!$m_product_category->where('member_id = %d and category_id = %d', $member_id, $category_id)->find()) { 
					$this->ajaxReturn(0, "修改失败，无此类别", 2);
				} elseif ($result = $m_product->where('member_id = %d and product_id = %d', $member_id, $product_id)->setField('category_id', $category_id)) {
					$this->ajaxReturn($result, "类别修改成功", 1); 
				}  elseif ($category_id == $m_product->where('member_id = %d and product_id = %d', $member_id, $product_id)->getField('category_id')) {
					$this->ajaxReturn($result, "数据无变化", 1); 
				} else {
					$this->ajaxReturn(0, "类别修改失败", 3); 
				}
			} else {
				$this->ajaxReturn(0, "参数错误", 4);
			}
		} else {
			$this->error('操作错误');
		}
	}
	
	public function qrdownload(){
		$product_id = intval($_GET['product_id']);
		$product = M('Product')->where('product_id = %d', $product_id)->find();
		$logo_path = M('Member')->where('member_id = %d and check_logo = 1', $product['member_id'])->getField('avatar');
		
		$qrsize = intval($_GET['qrsize']);
		if(!in_array($qrsize, array(6,9,11,24))) $qrsize = 9;
		
		if(!empty($product)){
			//注释为完整链接
			//$data = U('product/mobileview', 'product_id='.$product['product_id'], '','', true);
			$data = 'http://www.yhb360.com/prd/'.$product['product_id'];
			
			$png_temp_dir = './qrpng/';
			$filename = $png_temp_dir.md5($product['product_id'].$product['create_time']).'_'.$qrsize.'.png';
			if (!is_dir($png_temp_dir) && !mkdir($png_temp_dir, 0777, true)) { $this->error('二维码保存目录不可写'); }
			
			import("@.ORG.QRCode.qrlib");
			QRcode::png($data, $filename, 'M', $qrsize, 2); 
			
			$logo = imagecreatefromstring(file_get_contents($logo_path));
			if($logo){
				$qrpng = imagecreatefrompng($filename);
				
				$bimage = imagecreatefromstring(file_get_contents('./images/bimage.png'));
				//注释代码为内部的圆角边框效果
				$bimage2 = imagecreatefromstring(file_get_contents('./images/bimage2.png'));
				
				$QR_width = imagesx($qrpng);
				$QR_height = imagesy($qrpng);

				$logo_width = imagesx($logo);
				$logo_height = imagesy($logo);
				
				$bimage_width = imagesx($bimage);
				$bimage_height = imagesy($bimage);
				
				$logo_re = imagecreatetruecolor($bimage_width*0.9, $bimage_height*0.9);
				imagecopyresized ($logo_re, $logo, 0, 0, 0, 0, $bimage_width*0.9, $bimage_height*0.9, $logo_width, $logo_height);
				
				imagecopymerge ($bimage,$logo_re,$bimage_width*0.05,$bimage_height*0.05,0,0,$bimage_width*0.9,$bimage_height*0.9,100);
				//注释代码为内部的圆角边框效果
				imagecopyresized ($bimage, $bimage2, 0, 0, 0, 0, $bimage_width, $bimage_height, $bimage_width, $bimage_height);
				
				$bimage_re = imagecreatetruecolor($QR_width*0.2, $QR_height*0.2);
				imagecopyresized ($bimage_re, $bimage, 0, 0, 0, 0, $QR_width*0.2, $QR_height*0.2, $bimage_width, $bimage_height);
				
				imagecopymerge($qrpng, $bimage_re, $QR_width*0.4, $QR_height*0.4, 0, 0, $QR_width*0.2, $QR_height*0.2, 100);
				
				if($_GET['act'] == 'download') {
					header('Content-type: image/png');
					if(stristr($_SERVER["HTTP_USER_AGENT"],'MSIE')){
						header("Content-Disposition: attachment; filename=".urlencode($product['name']).'_'.$qrsize.".png");
					}else{
						header("Content-Disposition: attachment; filename=".$product['name'].'_'.$qrsize.".png");
					}
				}

				imagepng($qrpng);
			}else{
				if($_GET['act'] == 'download') {
					header('Content-type: image/png');
					if(stristr($_SERVER["HTTP_USER_AGENT"],'MSIE')){
						header("Content-Disposition: attachment; filename=".urlencode($product['name']).'_'.$qrsize.".png");
					}else{
						header("Content-Disposition: attachment; filename=".$product['name'].'_'.$qrsize.".png");
					}
				}
				
				echo file_get_contents($filename);
			}
			
			
			unlink($filename);
		}else{
			$this->error('请求错误');
		}
		
	}

	public function getvideostatus(){
		$m_video = M('Video');
		$vid = trim($_REQUEST['vid']);
		$type = trim($_REQUEST['type']);
		if($vid && $type){
			
			$data['vid'] = $vid;
			$data['call_type_time'] = time();
			$data['video_info'] = file_get_contents('http://v.polyv.net/uc/services/rest?method=getById&readtoken=f55NjipZmT-XZ40j3WYv1-LbJJc6ScM0&vid='.$vid);
			
			switch($type){
				case 'pass' : $data['status'] = 1; break;
				case 'nopass' : $data['status'] = 2; break;
				case 'del' : $data['status'] = 3; break;
				default : $data['status'] = 0; break;
			}
			if($m_video->where('vid = "%s"', $vid)->find()){
				$m_video->where('vid = "%s"', $vid)->save($data);
			}else{
				$m_video->add($data);
			}

			$where['content'] = array(array('like', '%'.$vid.'%'), array('like', '%processing.png%'), 'and');
			if($page = M('Page')->where($where)->find()){
				$video_info = json_decode($data['video_info']);
				$first_image = $video_info->data[0]->first_image;
				$content = unserialize($page['content']);
				foreach ($content as $k=>$v){
					if(!empty($v['file'])){
						foreach($v['file'] as $key => $vo){
							if($vo['vid'] == $vid){
								$content[$k]['file'][$key]['first_image'] = str_replace('.jpg','_b.jpg',$first_image);
							}
						}
					}
				}
				M('Page')->where('page_id = %d', $page['page_id'])->setField('content', serialize($content));
			}
		}
	}
	
}