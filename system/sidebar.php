<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
	<div class="wrapper">
		<nav id="sidebar" class="sidebar js-sidebar">
			<div class="sidebar-content js-simplebar">
				<a class="sidebar-brand" href="<?= $base_url?>index">
					<span class="sidebar-brand-text align-middle">
						<?= @$inst_name; ?>
						<sup><small class="badge bg-warning text-uppercase"><?= @$tag ?> </small></sup>
					</span>
					<svg class="sidebar-brand-icon align-middle" width="32px" height="32px" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="1.5"
						stroke-linecap="square" stroke-linejoin="miter" color="#FFFFFF" style="margin-left: -3px">
						<path d="M12 4L20 8.00004L12 12L4 8.00004L12 4Z"></path>
						<path d="M20 12L12 16L4 12"></path>
						<path d="M20 16L12 20L4 16"></path>
					</svg>
				</a>

				<div class="sidebar-user p-4 mb-3 mx-3 rounded-4 bg-dark-subtle" style="background: rgba(255,255,255,0.03);">
					<div class="d-flex align-items-center">
						<div class="flex-shrink-0">
						    <div class="avatar-wrapper p-1 rounded-circle border border-primary border-opacity-25">
    						    <img src='<?= $user_photo ?>' width='48' height='48' class="avatar img-fluid rounded-circle shadow-sm">
						    </div>
						</div>
						<div class="flex-grow-1 ms-3 overflow-hidden">
							<a class="sidebar-user-title dropdown-toggle d-block text-truncate fw-bold text-white mb-0" href="#" data-bs-toggle="dropdown" style="font-size: 0.9rem;">
								<?= @$_SESSION['user_name']; ?>
							</a>
							<div class="dropdown-menu dropdown-menu-start shadow-premium border-0">
								<a class="dropdown-item py-2" href="<?= $base_url?>system/op_change_password"><i class="align-middle me-2 feather-sm" data-feather="settings"></i> Change Password</a>
								<a class="dropdown-item py-2" href="#"><i class="align-middle me-2 feather-sm" data-feather="help-circle"></i> Help Center</a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item py-2 text-danger" href="#" onclick='logout()'><i class="align-middle me-2 feather-sm" data-feather="log-out"></i> Log out</a>
							</div>
							<div class="sidebar-user-subtitle d-flex align-items-center mt-1">
							    <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 rounded-pill" style="font-size: 0.65rem; border: 1px solid rgba(99, 102, 241, 0.2);"><?= @$_SESSION['user_type']; ?></span>
							</div>
						</div>
					</div>
				</div>
                <li class="sidebar-item">
				<a href="<?= $base_url?>" class="sidebar-link collapsed">
					<i class="align-middle" data-feather="sliders"></i> <span class="align-middle">Dashboard</span>
				</a>
				</li>
				<ul class="sidebar-nav">
					<li class="sidebar-header">
						Main Menu 
					</li>

					<?php $x = create_menu();
					
					print_r($x);
					?>

				
				<?php if(strtoupper($user_type) =='DEV') { ?>
            		<li class="sidebar-header">
						Developer Menu
					</li>
					<li class="sidebar-item">
						<a data-bs-target="#form-plugins" data-bs-toggle="collapse" class="sidebar-link collapsed">
							<i class="align-middle" data-feather="check-square"></i> <span class="align-middle">Developer Tools</span>
						</a>
						<ul id="form-plugins" class="sidebar-dropdown list-unstyled collapse " data-bs-parent="#sidebar">
							<li class="sidebar-item"><a class="sidebar-link" accesskey="c" href="<?= $base_url?>system/op_config_manage">Config Manager </a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey="m" href="<?= $base_url?>system/op_table">Table Manager </a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey="e" href="<?= $base_url?>system/op_table_manager">Table Editor</a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey="d" href="<?= $base_url?>system/op_data_manager">Data Manager</a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey ="n" href="<?= $base_url?>system/op_menu_manage">Menu Manager </a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey ="n" href="<?= $base_url?>system/op_role_manager">Role Manager </a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey="i" href="<?= $base_url?>system/op_import">Bulk Import</a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey="u" href="<?= $base_url?>system/op_user_manage"> Users Manger</a></li>
							<li class="sidebar-item"><a class="sidebar-link text-bold text-warning" accesskey="r" href="<?= $base_url?>system/op_recycle_bin">Recycle Bin</a></li>
							<li class="sidebar-item"><a class="sidebar-link " href="<?= $base_url?>system/op_backup" accesskey="b" >Create Backup </a></li>
							<li class="sidebar-item"><a class="sidebar-link " href="<?= $base_url?>system/op_icons" accesskey="b" >All Icons </a></li>
							<li class="sidebar-item"><a class="sidebar-link " href="<?= $base_url?>system/op_settings_manage" accesskey="b" >Settings Manager </a></li>
							<li class="sidebar-item"><a class="sidebar-link " href="<?= $base_url?>system/op_docs_manager" accesskey="b" >Document Manager </a></li>
						
						</ul>
					</li>
					
				<?php } ?>


				<?php if(strtoupper($user_type) =='ADMIN') { ?>
            		<li class="sidebar-header">
						Settings
					</li>
					<li class="sidebar-item">
						<a data-bs-target="#form-plugins" data-bs-toggle="collapse" class="sidebar-link collapsed">
							<i class="align-middle" data-feather="check-square"></i> <span class="align-middle">Admin Tools</span>
						</a>
						<ul id="form-plugins" class="sidebar-dropdown list-unstyled collapse " data-bs-parent="#sidebar">
							<!--<li class="sidebar-item"><a class="sidebar-link" accesskey="e" href="<?= $base_url?>system/op_table_manager">Table Editor</a></li>-->
							<!--<li class="sidebar-item"><a class="sidebar-link" accesskey ="n" href="<?= $base_url?>system/op_menu_manage">Menu Manager </a></li>-->
							<!--<li class="sidebar-item"><a class="sidebar-link" accesskey ="n" href="<?= $base_url?>system/op_role_manager">Role Manager </a></li>-->
							<li class="sidebar-item"><a class="sidebar-link" accesskey="u" href="<?= $base_url?>system/op_user_manage">Users</a></li>
							<!--<li class="sidebar-item"><a class="sidebar-link " href="<?= $base_url?>system/op_backup" accesskey="b" >Create Backup </a></li>-->
							<!--<li class="sidebar-item"><a class="sidebar-link" accesskey ="s" href="<?= $base_url?>system/op_settings">Settings </a></li>-->
						
						</ul>
					</li>
					
				<?php } ?>

				</ul>

				<div class="sidebar-cta">
					<div class="sidebar-cta-content">
						<strong class="d-inline-block mb-2"><?= $help_title ?></strong>
						<div class="mb-1 text-sm">
							<?= $help_msg ?>
						</div>

						<div class="d-grid">
							<a href="https://wa.me/+91<?=$dev_contact?>?msg=Message%20from%20<?= $inst_name?>" class="btn btn-outline-success" target="_blank"><i class='fab fa-whatsapp'></i> WhatsApp</a>
						</div>
					</div>
				</div>
			</div>
		</nav>
