<?php 
/*
 * 产品类别模块
 * @author 赵凡
 * @createtime 2014-6-3
*/
class CategoryAction extends Action {
	
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('add', 'index', 'delete', 'edit')
		);
		
		B('Authenticate', $action); 
	}
	
	public function index(){
		$this->display();
	}

	public function add() {
		$m_product_category = M('ProductCategory');
		$data['name'] = filtStr($this->_post('name','trim'));
		$data['member_id'] = intval(session('member_id'));

		if ($data['name']) {
			if ($m_product_category->where($data)->find()) { 
				$this->ajaxReturn(0, "类别名不可重复", 0);
			} elseif ($result = $m_product_category->add($data)) { 
				$this->ajaxReturn($result, "添加类别成功", 1); 
			} else {
				$this->ajaxReturn(0, "添加失败", 0); 
			}
		} else {
			$this->ajaxReturn(0, "参数错误", 0);
		}
	}

	public function edit() {
		$m_product_category = M('ProductCategory');
			$category_id = $this->_post('id','intval');
			$category_name = $this->_post('name','trim');
			$member_id = intval(session('member_id'));
			
			if ($category_name && $category_id) {
				if($category = $m_product_category->where('member_id = %d and category_id = %d', $member_id, $category_id)->find()){
					if($category['name'] == $category_name || $m_product_category->where('category_id = %d', $category_id)->setField('name', $category_name)){
						$this->ajaxReturn($category_id, "修改类别成功", 1); 
					} else {
						$this->ajaxReturn(0, "修改失败", 0); 
					}
				} else {
					$this->ajaxReturn(0, "参数错误，修改失败", 0); 
				}
			} else {
				$this->ajaxReturn(0, "参数错误", 0);
			}
	}

	public function delete() {
		$m_product = M('Product');
		$m_product_category = M('ProductCategory');
		if ($this->isAjax()) {
			$where['member_id'] = intval(session('member_id'));
			$where['category_id'] = intval($_GET['id']);
			
			if (intval($_GET['id'])) {
				if ($m_product->where($where)->select()){
					$this->ajaxReturn(0, "该分类下还有说明书。为安全起见，请先手动删除分类内的说明书，再删除分类", 0); 
				}else{
					if ($result = $m_product_category->where($where)->delete()) { 
						$this->ajaxReturn($result, "删除类别成功", 1); 
					} else {
						$this->ajaxReturn(0, "删除失败", 0); 
					}
				}
			} else {
				$this->ajaxReturn(0, "参数错误", 0);
			}
		} else {
			$this->error('操作错误');
		}
	}
	
}