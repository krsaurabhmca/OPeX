<?php
require_once('system/op_lib.php');


function btn_pay($table_name, $id, $title = '')
{
    global $base_url;
	global $user_type;
	global $user_name;
	$view_link = $base_url.'public/pay_now.php?link=' . encode('table=' . $table_name . '&id=' . $id);
    if($_SESSION['user_type'] =='DEV' or $_SESSION['user_type'] =='ADMIN')
		{
			$str = "<a data-href='$view_link' class='view_data btn btn-success btn-sm text-light' data-title='$title'><i class='fa fa-eye'></i></a> ";
		}
	else{
	   
	    $table_id  = get_data('op_table', $table_name,'id','table_id')['data'];
	    $res =  get_multi_data('op_role', array('table_id'=>$table_id, 'role_name'=>$user_type));
    	$str = '';
    	if($res['count']>0)
    	{
    		$permission =$res['data'][0]; // Check Permission 
    	
    		if($permission['can_view'] =='YES'){
    
    			$str = "<a data-href='$view_link' class='view_data btn btn-success btn-sm text-light' data-title='$title'><i class='fa fa-eye'></i></a> ";
    		} else {
    			$str ='';
    		}
    	}
	}
	return $str;
}