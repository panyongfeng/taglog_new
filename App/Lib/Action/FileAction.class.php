<?php 

class FileAction extends Action{

	public function uploadify(){
		$verifyToken = md5('instructions' . $_POST['timestamp']);
		if (!empty($_FILES) && $_POST['token'] == $verifyToken) {
			$m_config = M('config');
			if (isset($_FILES['Filedata']['size']) && $_FILES['Filedata']['size'] != null) {
				import('@.ORG.UploadFile');
				$upload = new UploadFile();
				$upload->maxSize = 20000000;
				$upload->allowExts = array('jpg','jpeg','png','gif');
				$dirname = './Uploads/' . date('Ym', time()).'/'.date('d', time()).'/';
				if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
					$this->ajaxReturn(0,'上传目录不可写',0);
					die();
				}
				$upload->savePath = $dirname;
				
				if(!$upload->upload()) {
					$this->ajaxReturn(0,$upload->getErrorMsg(),0);
					die();
				}else{
					$info =  $upload->getUploadFileInfo();
				}
			}
			if(is_array($info[0]) && !empty($info[0])){
				$a['type']=$info[0]['extension'];
				$a['path'] = $info[0]['savepath'] . $info[0]['savename'];
				
				$temp_size = getimagesize($info[0]['savepath'].$info[0]['savename']);
				if($info[0]['extension'] != 'gif' && $temp_size[0] > 500){
					$height = 500*$temp_size[1]/$temp_size[0];
					import('@.ORG.ThinkImage.ThinkImage');
					$Think_img = new ThinkImage(THINKIMAGE_GD); 
					$Think_img->open($info[0]['savepath'].$info[0]['savename'])->thumb(500,$height, 1)->save($info[0]['savepath'].$info[0]['savename']);
					$a['width']=500;
					$a['height'] = $height;
				}else{
					$a['width']=$temp_size[0];
					$a['height'] = $temp_size[1];
				}
				
				$this->ajaxReturn($a,'上传成功',1);
			}else{
				$this->ajaxReturn(0,'上传失败',0);
			};
		}else{
			$this->ajaxReturn(0,'非法操作',0);
		}
	}
	
	
}