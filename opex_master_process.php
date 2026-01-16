<?php
require_once('function.php');
require 'system/vendor/autoload.php';

$_POST = post_clean($_POST);
$_GET = post_clean($_GET);
if (isset($_GET['task'])) {
	$task = xss_clean($_GET['task']);
	$user_id = (isset($_SESSION['user_id']))?$_SESSION['user_id']:'';
	if($task !='')
	{
		$data['task_name'] 	=	$task;
		$data['status']		=	'ACTIVE';
		if(isset($_REQUEST['table_name']))
		{
			$data['table_name']=$_REQUEST['table_name'];
			$data['user_id']=$user_id;
		}
		$req = insert_data('op_log',$data);
	}
	switch ($task) {

		case "master_update_data":
			extract($_POST);
			unset($_POST['isedit']);
			unset($_POST['table_name']);
			$_POST = array_to_string($_POST);
			$res = update_data($table_name, $_POST, $id);
			if ($isedit == "yes") {
				$res['url'] = $base_url."public/".$table_name.'_manage';	
			}
			else{
				$res['url'] = $base_url."public/".$table_name.'_add';
			}
			echo json_encode($res);
			break;
			

		case "send_whatsapp":
			extract($_POST);
			unset($_POST['isedit']);
			unset($_POST['table_name']);
			$document_link  =$base_url.'upload/'.$images;
		    $link_id = new LinkID($document_link);
		    
		    if($msg_type=='DOCS')
		    {
            $whatsapp_cloud_api->sendDocument('91'.$mobile, $link_id, $images, $text);
		    }
		    else if($msg_type=='IMAGE')
		    {
            $whatsapp_cloud_api->sendImage('91'.$mobile, $link_id);
		    }
		    else if($msg_type=='AUDIO')
		    {
            $whatsapp_cloud_api->sendAudio('91'.$mobile, $link_id);
		    }
		    else if($msg_type=='VIDEO')
		    {
            $whatsapp_cloud_api->sendVideo('91'.$mobile, $link_id, $text);
		    }
		    else
		    {
			$whatsapp_cloud_api->sendTextMessage('91'.$mobile, $text);
		    }
			
			
			$res = update_data($table_name, $_POST, $id);
			if ($isedit == "yes") {
				$res['url'] = $table_name.'_manage';	
			}
			else{
				$res['url'] = $table_name.'_add';
			}
			echo json_encode($res);
			break;

        case "send_docs":
            $document_id = '341476474779872';
            $document_name = 'whatsapp-cloud-api-from-id.pdf';
            $document_caption = 'WhastApp API Cloud Guide';
            
            // With the Media Object ID of some document upload on the WhatsApp Cloud servers
            $media_id = new MediaObjectID($document_id);
            $whatsapp_cloud_api->sendDocument('34676104574', $media_id, $document_name, $document_caption);
            
            // Or
            $document_link = 'https://netflie.es/wp-content/uploads/2022/05/image.png';
            $link_id = new LinkID($document_link);
            $whatsapp_cloud_api->sendDocument('34676104574', $link_id, $document_name, $document_caption);
			extract($_POST);
			unset($_POST['isedit']);
			unset($_POST['table_name']);
			$whatsapp_cloud_api->sendTextMessage('91'.$mobile, $text);
			$res = update_data($table_name, $_POST, $id);
			if ($isedit == "yes") {
				$res['url'] = $table_name.'_manage';	
			}
			else{
				$res['url'] = $table_name.'_add';
			}
			echo json_encode($res);
			break;

	

		
	
		default:
			echo "<script> alert('Invalid Action'); window.location ='" . $_SERVER['HTTP_REFERER'] . "' </script>";
	}
}
