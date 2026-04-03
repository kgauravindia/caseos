			<footer class="footer bg-white mt-auto py-4">
				<div class="container-fluid">
					<div class="row text-muted align-items-center">
						<div class="col-6 text-start">
							<p class="mb-0 small fw-medium">
							&copy; <?= date('Y'); ?> <span class="text-primary"><?= $inst_name; ?></span>. All rights reserved.
							</p>
						</div>
						<div class="col-6 text-end">
							<p class="mb-0 small">
							    <span class="d-none d-md-inline-block">Built with passion by</span> 
							    <a href="<?= @$dev_url; ?>" target="_blank" class="text-dark fw-bold" style="text-decoration: none;">
							        <i data-feather="heart" class="text-danger feather-sm me-1" style="width:12px; height:12px; fill: currentColor;"></i> <?= $dev_by ?>
							    </a>
							</p>
						</div>
					</div>
				</div>
			</footer>
		</div>
	</div>
	<!-- =========== View Data IN modal ========= -->
<div class="modal fade bd-example-modal-lg shadow-premium" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id='view_data'>
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 rounded-4">
      <div class="modal-header border-bottom-0 p-4">
        <h3 class="modal-title fw-bold" id="exampleModalCenterTitle"></h3>
      	<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4 pb-4">
      </div>
    </div>
  </div>
</div>

<div class="offcanvas offcanvas-end border-0 shadow-lg" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel" style="width: 350px;">
	<div class="offcanvas-header p-4">
		<h4 id="offcanvasRightLabel" class="fw-bold mb-0">Table Columns</h4>
		<?php if($user_type=='DEV' or $user_type=='ADMIN'){ ?>
		<a href="<?= $base_url?>system/op_column_sorting?table_name=<?= $table_name?>" title="Sort Columns" class="btn btn-primary btn-sm rounded-circle p-2 mx-2"><i data-feather="move"></i></a>
		<?php } ?>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body p-4">
		<div class="table-responsive">
			<table class="table table-sm align-middle">
			<?php 
            $col_list =  get_all('op_master_table','*',array('table_name'=>$table_name,'status'=>'ACTIVE','is_edit'=>'YES'))['data'];
            foreach($col_list as $col)
            {
              echo "<tr><td class='fw-medium'>". add_space($col['column_name']) ." </td><td class='text-end'>". $switch_str= show_switch('op_master_table',  $col['id'], 'show_in_table', $col['show_in_table'] ) ."</td></tr>";
            }
            ?>
			</table>	
		</div>
	</div>
</div>

<script src="<?= $base_url ?>system/js/app.js"></script>
<script src="<?= $base_url ?>system/js/datatables.js"></script>

<!-- Export functionality -->
<script src="<?= $base_url ?>system/DataTables-1.10.15/extensions/Buttons/js/dataTables.buttons.min.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/extensions/Buttons/js/buttons.flash.min.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/ex-js/jszip.min.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/ex-js/pdfmake.min.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/ex-js/vfs_fonts.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/extensions/Buttons/js/buttons.html5.min.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/extensions/Buttons/js/buttons.print.min.js"></script>

<script src="<?= $base_url ?>system/js/validate.js"></script>
<script src="<?= $base_url ?>system/js/shortcut.js"></script>
<script src="<?= $base_url ?>system/js/bootbox.all.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/js-base64@2.5.2/base64.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="<?= $base_url ?>system/js/op.js"></script>

<script>
	document.addEventListener("DOMContentLoaded", function() {
		feather.replace();
		
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