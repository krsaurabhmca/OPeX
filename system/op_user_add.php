<?php require_once('all_header.php'); 

$table_name = 'op_user';

if (isset($_GET['link']) and $_GET['link'] != '') {
	$complain = decode($_GET['link']);
	$id = $complain['id'];
    $isedit ='yes';
} else {

	$complain = insert_row($table_name);
	$id = $complain['id'];
    $isedit ='no';
}

if ($id != '') {
	$res = get_data($table_name, $id);
	if ($res['count'] > 0 and $res['status'] == 'success') {
		extract($res['data']);
	}
}
?>

	<main class="content">
		<div class="container-fluid p-0">

			<h1 class="h3 mb-3">Profile</h1>

			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0">User Profile
							<button class='btn btn-primary btn-sm float-end' id='update_btn'> SAVE </button>
							</h5>
						</div>
						<div class="card-body">
                            <?php $form  = create_form($table_name, $id, $isedit, 'update_profile', 'system'); 
                            
                                foreach($form as $el)
                                {
                                echo $el;
                                }
                            ?>
						</div>
					</div>
				</div>
			</div>

		</div>
	</main>

<?php 
require_once('footer.php'); ?>		

<script>
	$(document).ready(function(){
		var isedit = $("input[name=isedit]").val();
		if(isedit=='yes')
		{
		$("#user_pass").val('');
		$("#user_pass").removeAttr('required');
		$("#user_type").parent(".form-group").css('display','none');
		$("#user_type").removeAttr("name");
		}
	});
</script>