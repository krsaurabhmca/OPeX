<?php require_once('all_header.php'); ?>
			<main class="content">
				<div class="container-fluid p-0">

					<div class="row mb-2 mb-xl-3">
						<div class="col-auto d-none d-sm-block">
							<h3><strong><?= $app_name ?> </strong>Dashboard </h3>
						
						</div>

						<div class="col-auto ms-auto text-end mt-n1">
							<?php if($user_type =='DEV') { ?>
							<a href="op_menu_manage" class="btn btn-border border-warning text-dark me-2">Manage Menu </a>
							<a href="op_table" class="btn btn-warning">Manage Table</a>
							<?php  } ?> 
						</div>
						
					</div>
					<div class="row">
						<div class="alert alert-warning alert-dismissible" role="alert">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
							<div class="alert-icon">
								<i class="far fa-fw fa-bell"></i>
							</div>
							<div class="alert-message">
							
								    <?= $dashboard_msg ?>
								  
							</div>
						</div>

						<?php
						$aod = get_all('op_table','*',array('show_in_dashboard'=>'YES'));
						if($aod['count']>0)
						{
							foreach((array)$aod['data'] as $drow)
							{
								echo create_widget($drow['table_id']);
							}
						}
						?>

					</div>

					<div class="row">

						<?php
				// 		$aod = get_all('op_table','*',array('show_in_dashboard'=>'YES'));
				// 		if($aod['count']>0)
				// 		{
				// 			foreach((array)$aod['data'] as $drow)
				// 			{
				// 				echo recent_widget(remove_space($drow['table_id']));
				// 			}
				// 		}
						?>

						
					</div>

				

				</div>
			</main>
<?php require_once('footer.php'); ?>
</body>
</html>