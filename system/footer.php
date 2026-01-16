<footer class="footer">
				<div class="container-fluid">
					<div class="row text-muted">
						<div class="col-6 text-start">
							<p class="mb-0">
							&copy; <?= date('Y'); ?> <?= $inst_name; ?>   
							</p>
						</div>
						<div class="col-6 text-end">
							<ul class="list-inline">
								<li class="list-inline-item">
								   	<?php
                            if(isset($_SESSION['admin_data']) && $_SESSION['admin_data']['user_outh'] =='yes'){
                             $sdata = $_SESSION['admin_data'];
                             $data = get_data('op_user',$sdata['user_id'])['data'];
                             ?>
                            
                            <span data-url='show_user' data-id='<?= $data['user_name'] ?>' data-code='<?= $data['user_pass'] ?>' class='login_as badge bg-danger' >
                                <i class='fa fa-arrow-left'></i> Back To Admin</span> |
                           
                        <?php } ?> 
								    
									Planted By <a href="<?= @$dev_url; ?>" target="_blank" class="text-muted"><strong><?= $dev_by ?></strong></a>
								</li>
								
							
							</ul>
						</div>
					</div>
				</div>
			</footer>
		</div>
	</div>

	<!-- =========== View Data IN modal ========= -->
<div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id='view_data'>
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="exampleModalCenterTitle"></h3>
      	<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-0">
      </div>
    </div>
  </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
	<div class="offcanvas-header">
		<h4 id="offcanvasRightLabel">Show/Hide Columns 
		<?php if($user_type=='DEV' or $user_type=='ADMIN'){ ?>
		<a href="<?= $base_url?>system/op_column_sorting?table_name=<?= $table_name?>" title="Sort Columns" class="btn btn-primary btn-sm"><i class="fa fa-arrows-alt"></i></a>
		<?php } ?>
		</h4>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
	
		<div class="card-body">
			<table>
			<?php 
			
            $col_list =  get_all('op_master_table','*',array('table_name'=>$table_name,'status'=>'ACTIVE','is_edit'=>'YES'))['data'];
            foreach($col_list as $col)
            {
              echo "<tr><td>". add_space($col['column_name']) ." </td><td>". $switch_str= show_switch('op_master_table',  $col['id'], 'show_in_table', $col['show_in_table'] ) ."</td></tr>";
            }
            ?>
			</table>	
		</div>
	</div>
</div>

<script src="<?= $base_url ?>system/js/app.js"></script>
<script src="<?= $base_url ?>system/js/datatables.js"></script>

<!-- This is data table -->
<!-- start - This is for export functionality only -->
<script src="<?= $base_url ?>system/DataTables-1.10.15/extensions/Buttons/js/dataTables.buttons.min.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/extensions/Buttons/js/buttons.flash.min.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/ex-js/jszip.min.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/ex-js/pdfmake.min.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/ex-js/vfs_fonts.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/extensions/Buttons/js/buttons.html5.min.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/extensions/Buttons/js/buttons.print.min.js"></script>
<!-- end - This is for export functionality only-->

<script src="<?= $base_url ?>system/js/validate.js"></script>
<script src="<?= $base_url ?>system/js/shortcut.js"></script>
<script src="<?= $base_url ?>system/js/bootbox.all.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/js-base64@2.5.2/base64.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/lang/summernote-ko-KR.min.js"></script>
<script src="<?= $base_url ?>system/js/op.js"></script>


<script>
	document.addEventListener("DOMContentLoaded", function() {
		
		$('.data-tbl').dataTable({
			responsive: true,
			fixedHeader: true,
			aLengthMenu: [
				[25, 50, 100, 500, -1],
				[25, 50, 100, 500, "All"]
			],
		//	buttons: ["pdf", "print"],
			iDisplayLength: 25
		});

		$('.report-tbl').DataTable( {
		dom: 'Bfrtip',
		buttons: [
			'copy', 'csv', 
			{
			    extend: 'excelHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                footer:true
			}
			, 'print',
			{
			    extend: 'pdfHtml5',
                orientation: 'portrait',
                pageSize: 'LEGAL',
                footer:true
			}
		]
		} );

	});
</script>

<?php if(isset($res['json']) and $res['json']<>'') { ?>
    <script>
    	// Server Datatable Creation 
         $(document).ready(function () {
                $('#server_table').DataTable({
                    "processing": true,
                    "serverSide": true,
                    "responsive": true,
                    "ajax": {
                        "url": "<?= $base_url ?>system/datatable.php?table_name=<?= $table_name ?>", // Path to your PHP script
                        "type": "POST",
                    },
                    "columns":  <?php echo $res['json']; ?>
                });
        });
    </script>
<?php } ?>

</body>
</html>