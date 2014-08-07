<?php 

class WelcomeWidget extends Widget 
{
	public function render($data)
	{
		$m_product = M('product');
		$m_member = M('member');
		$end_time = time();
		$this_time = $end_time - 86400*30;
		
		while(date('Y-m-d', $this_time) <= date('Y-m-d', $end_time)) {
			$day_count_array[] = "'".date('m/d', $this_time)."'";
			$time1 = strtotime(date('Y-m-d', $this_time));
			$time2 = $time1 + 86400;
			
			$where_day_create['create_time'] = array(array('lt',$time2),array('gt',$time1), 'and');
			$day_product_count[] = $m_product->where($where_day_create)->count();
	
			$where_day_create['reg_time'] = array(array('lt',$time2),array('gt',$time1), 'and');
			$day_member_count[] = $m_member->where($where_day_create)->count();
			
			$this_time += 86400;
		}

		

		$i = 5;
		while($i >= 0) {
			$time_array = GetMonth(-$i);
			$start_time = $time_array['start_time'];
			$end_time = $time_array['end_time'];
			
			$month_member_count[] = "'".date('y/m', $start_time)."'";
			$where_moon_member['reg_time'] = array('lt', $end_time);
			$month_member_create_count[] = $m_member->where($where_moon_member)->count();
			
			$where_moon_product['create_time'] = array('lt', $end_time);
			$month_product_create_count[] = $m_product->where($where_moon_product)->count();
			
			$i--;
		}
		
		
		$data['month_member_count'] = implode(',', $month_member_count);
		$data['month_member_create_count'] = implode(',', $month_member_create_count);
		$data['month_product_create_count'] = implode(',', $month_product_create_count);
		$data['day_count'] = implode(',', $day_count_array);
		$data['day_product_count'] = implode(',', $day_product_count);
		$data['day_member_count'] = implode(',', $day_member_count);

		return $this->renderFile ("index", $data);
	}
}