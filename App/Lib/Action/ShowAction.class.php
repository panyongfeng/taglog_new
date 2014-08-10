<?php
/*
 * showcase页面
 *
 */


class ShowAction extends Action {


	public function _initialize(){
		$action = array(
			'permission'=>array('show'),
			'allow'=>array('logout', 'index', "show")
		);

		B('Authenticate', $action);


	}
	
	public function show() {


		$m_product = D('Product');
		$products_v = array();

		$where = array();
		$where['status'] = 1;
		$order = 'hits desc';
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		$products = $m_product->where($where)->order($order)->page($p.',15')->select();
		$this->products_v = $products;
		foreach($products as $k=>$v) {
			$coverimage = $this->genCoverImageForProduct($v['product_id']);
			if(!$coverimage) continue;
			else $this->products_v[$k]['coverimage'] = $coverimage;
		}
		
		$this->display('showcases');
	}


	/*
	 * By iterating every video or image to find a cover image
	 * use 
	 */
	private function genCoverImageForProduct($productid) {
		$is_succeed = false;

		$m_page = D('Page');
		
		// find pages for every product by id
		$pages = $m_page->where(array('product_id'=>$productid))->select();
		// for every page, find the content, 
		foreach($pages as $pagek=>$pagev) {
			echo ($pagev['content']);
			die();
			$page_content = unserialize($pagev['content']);
			foreach ($page_content as $ck=>$cv){
				// find first image as cover image
				// or find a image as cover image
				// else doesnot show this product 
				if(!empty($cv['file'])){
					foreach($cv['file'] as $key=>$vo){
						if($vo['type'] == 'video'){
							return $page_content[$pagek]['file'][$key]['first_image'];
						} else if($vo['type'] == 'image') {
							// if there is a image, just use it.
							return $page_content[$pagek]['file'][$key]['path'];
						}
					}
				}
			}
		}
		return $is_succeed;
	}
}

?>