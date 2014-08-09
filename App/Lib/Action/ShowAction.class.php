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
		$m_product = M('Product');
		$where = array();
		$where['status'] = 1;
		$order = 'hits';
		$list = $m_product->where($where)->order($order)->select();
		$this->list = $list;
		foreach($list as $k=>$v){

		}
		echo ($_SERVER['HTTP_HOST']."---");
		echo ($_SERVER['REQUEST_URI'])
		die();
		$this->display('showcases');
	}
}

?>