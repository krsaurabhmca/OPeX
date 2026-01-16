<?php
require_once('function.php');
$_POST = post_clean($_POST);
$_GET = post_clean($_GET);
if (isset($_GET['task'])) {
	$task = xss_clean($_GET['task']);
	$user_id = (isset($_SESSION['user_id']))?$_SESSION['user_id']:'';
	switch ($task) {
        
        
		case "master_update_data":
			extract($_POST);
			unset($_POST['isedit']);
			unset($_POST['table_name']);
			$res = update_data($table_name, $_POST, $id);
			if ($isedit == "yes") {
				$res['url'] = $table_name.'_manage';	
			}
			else{
				$res['url'] = $table_name.'_add';
			}
			echo json_encode($res);
			break;
        
         case "get_account_list": // Change Date form Complete System
			extract($_POST);
			$res = get_all('account_head', ['id','account_name'], ['account_type'=>trim($account_type)])['data'];
			echo json_encode($res);
			break;
			
		 case "plot_status": // Change Date form Complete System
			extract($_POST);
			$res = get_data('plot_details', $plot_id,'plot_status','plot_id');
			echo json_encode($res);
			break;
		
	

		default:
			echo "<script> alert('Invalid Action'); window.location ='" . $_SERVER['HTTP_REFERER'] . "' </script>";
	}
}
