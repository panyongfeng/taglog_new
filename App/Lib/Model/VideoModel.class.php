<?php 
    class VideoModel extends Model{
		
		public function checkStatus($content=array()){
			foreach ($content as $key=>$vo){
				if(!empty($vo['file'])){
					foreach($vo['file'] as $key2=>$vo2){
						$status = 0;
						if($vo2['type'] == 'video'){
							$status = $this->where('vid = "%s"', trim($vo2['vid']))->getField('status');
							$content[$key]['file'][$key2]['status'] = intval($status);
						}
					}
				}
			}
			return $content;
		}
		
		public function updateVideo($content=array(),$product_id){
			$is_failed = false;
			
			if(empty($content)) return $content;
			foreach ($content as $k=>$v){
				if(!empty($v['file'])){
					foreach($v['file'] as $key=>$vo){
						if($vo['type'] == 'video'){
							$data = array();
							$data['update_time'] = time();
							$data['product_id'] = $product_id;
							if(session('?member_id')) $data['member_id'] = session('member_id');						
							if(strpos($vo['first_image'],'processing.png') !== false){
								if($video_info = $this->where('vid = "%s"', $vo['vid'])->getField('video_info')){
									$video_info = json_decode($video_info);
									$first_image = $video_info->data[0]->first_image;
									$content[$k]['file'][$key]['first_image'] = str_replace('.jpg','_b.jpg',$first_image);
								}
							}
							
							if($this->where('vid = "%s"', $vo['vid'])->find()){
								if(!$this->where('vid = "%s"', $vo['vid'])->save($data)){
									return $is_failed;
								}
							}else{
								$data['vid'] = $vo['vid'];
								if(!$this->add($data)){
									return $is_failed;
								}
							}
						}
					}
				}
			}
			return $content;
		}
	}