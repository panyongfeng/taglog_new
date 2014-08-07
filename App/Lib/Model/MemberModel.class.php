<?php 
    class MemberModel extends Model{
	
		private  $_salt = "";
		
        protected $_validate;
        public function _initialize(){
            $validate[0] = 'email';
            $validate[1] = '';
            $validate[2] = '您申请的邮箱已被使用，您可以通过邮箱找回密码或者换一个试试吧！';
            $validate[3] = 0;
            $validate[4] = 'unique';
            $validate[5] = 1;
            $this->_validate[] = $validate;
        }
		protected $_auto = array(
			array('reg_time', 'time', 1, 'function'),
			array('last_login_time', 'time', 1, 'function'),
			array('reg_ip', 'get_client_ip', 1, 'function'),
			array('salt','getSalt',1,'callback'),
			array('password','getPassword',1,'callback'),
		);
		
		
		protected function getSalt(){
			return $this->_salt = substr(md5(time()),0,4);			
		}
		protected function getPassword(){
			return md5(md5(trim($_POST["password"])) . $this->_salt);
		}
		
    }