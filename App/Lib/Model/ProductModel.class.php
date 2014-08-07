<?php 
    class ProductModel extends Model{
	
		protected $_validate = array(
			array('name','require','标题不能为空！'), //默认情况下用正则进行验证
		);
		protected $_auto = array(
			array('create_time', 'time', 1, 'function'),
			array('update_time', 'time', 3, 'function'),
			array('last_view_time', 'time', 1, 'function')
		);
		
    }