<?php 

class AppBehavior extends Behavior {
	protected $options = array();
	
	public function run(&$params) {
		if(MODULE_NAME != 'Install') {
			if (!F('smtp')) {
				$value = M('Config')->where('name = "smtp"')->getField('value');
				F('smtp',unserialize($value));			
			}
			C('smtp', F('smtp'));
			if (!F('limit_video_size')) {
				$value = M('Config')->where('name = "limit_video_size"')->getField('value');
				F('limit_video_size',$value);			
			}
			C('limit_video_size', F('limit_video_size'));
		}
	}
}