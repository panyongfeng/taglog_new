<?php
/*
 * showcase页面
 *
 */


class ShowAction extends Action {

	public function show() {
		$m_product = M('Product');
		$where = array();
		$where['status'] = 1;
		$order = 'hits';
		$list = $m_product->where($where)->order($order)->select();
		foreach($list as $k=>$v){

		}
		$this->display('showcases');
	}
}

?>