<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
	<div class="wrapper">
		<nav id="sidebar" class="sidebar js-sidebar sidebar-modern">
			<div class="sidebar-content js-simplebar">
				<a class="sidebar-brand d-flex align-items-center" href="<?= $base_url?>index">
					<div class="sidebar-brand-icon-wrapper me-3">
						<svg width="32px" height="32px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
							<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
						</svg>
					</div>
					<span class="sidebar-brand-text align-middle fw-bold">
						<?= @$inst_name; ?>
						<span class="badge bg-primary-soft text-primary ms-1 border border-primary border-opacity-10" style="font-size: 0.6rem; vertical-align: super;"><?= @$tag ?></span>
					</span>
				</a>

				<div class="sidebar-user-modern mx-3 mb-4 mt-2">
					<div class="user-card glass p-3 rounded-4 border-0 shadow-sm overflow-hidden position-relative">
						<div class="user-glow"></div>
						<div class="d-flex align-items-center position-relative">
							<div class="flex-shrink-0">
								<div class="avatar-container">
									<img src="<?= $user_photo ?>" width="48" height="48" class="rounded-circle border border-2 border-white shadow-sm" alt="User">
									<span class="status-indicator online"></span>
								</div>
							</div>
							<div class="flex-grow-1 ms-3 overflow-hidden">
								<div class="dropdown">
									<a class="user-name-link text-white fw-bold d-block text-truncate mb-0 dropdown-toggle" href="#" data-bs-toggle="dropdown">
										<?= @$_SESSION['user_name']; ?>
									</a>
									<div class="dropdown-menu dropdown-menu-start shadow-premium border-0 animate-fade-in">
										<a class="dropdown-item py-2" href="<?= $base_url?>system/op_change_password">
											<i class="align-middle me-2 feather-sm" data-feather="settings"></i> Account Settings
										</a>
										<a class="dropdown-item py-2" href="#">
											<i class="align-middle me-2 feather-sm" data-feather="help-circle"></i> Support Center
										</a>
										<div class="dropdown-divider"></div>
										<a class="dropdown-item py-2 text-danger" href="#" onclick="logout()">
											<i class="align-middle me-2 feather-sm" data-feather="log-out"></i> Sign Out
										</a>
									</div>
								</div>
								<div class="user-role-badge mt-1">
									<span class="badge bg-white bg-opacity-10 text-white-50 border border-white border-opacity-10 px-2 py-1 rounded-pill" style="font-size: 0.65rem;">
										<?= @$_SESSION['user_type']; ?>
									</span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<ul class="sidebar-nav">
					<li class="sidebar-header px-4 text-white-50 opacity-50 text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 1px;">
						Navigation
					</li>

					<li class="sidebar-item">
						<a href="<?= $base_url?>" class="sidebar-link">
							<i class="align-middle" data-feather="grid"></i> <span class="align-middle">Dashboard</span>
						</a>
					</li>

					<li class="sidebar-header px-4 text-white-50 opacity-50 text-uppercase fw-bold mt-3" style="font-size: 10px; letter-spacing: 1px;">
						Main Menu
					</li>

					<?php echo create_menu(); ?>

					<?php if(strtoupper($user_type) =='DEV') { ?>
						<li class="sidebar-header px-4 text-white-50 opacity-50 text-uppercase fw-bold mt-3" style="font-size: 10px; letter-spacing: 1px;">
							Development
						</li>
						<li class="sidebar-item">
							<a data-bs-target="#dev-tools" data-bs-toggle="collapse" class="sidebar-link collapsed">
								<i class="align-middle" data-feather="terminal"></i> <span class="align-middle">Core Engine</span>
								<span class="badge bg-danger rounded-pill float-end" style="font-size: 0.6rem;">Master</span>
							</a>
							<ul id="dev-tools" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
								<li class="sidebar-item"><a class="sidebar-link" href="<?= $base_url?>system/op_config_manage">Config Manager</a></li>
								<li class="sidebar-item"><a class="sidebar-link" href="<?= $base_url?>system/op_table">Table Schema</a></li>
								<li class="sidebar-item"><a class="sidebar-link" href="<?= $base_url?>system/op_table_manager">Table Editor</a></li>
								<li class="sidebar-item"><a class="sidebar-link" href="<?= $base_url?>system/op_data_manager">Data Records</a></li>
								<li class="sidebar-item"><a class="sidebar-link" href="<?= $base_url?>system/op_menu_manage">Menu Control</a></li>
								<li class="sidebar-item"><a class="sidebar-link" href="<?= $base_url?>system/op_role_manager">RBAC System</a></li>
								<li class="sidebar-item"><a class="sidebar-link" href="<?= $base_url?>system/op_import">Bulk Operations</a></li>
								<li class="sidebar-item"><a class="sidebar-link" href="<?= $base_url?>system/op_user_manage">User Directory</a></li>
								<li class="sidebar-item"><a class="sidebar-link text-warning fw-bold" href="<?= $base_url?>system/op_recycle_bin">Recycle Bin</a></li>
								<li class="sidebar-item"><a class="sidebar-link" href="<?= $base_url?>system/op_backup">System Backup</a></li>
								<li class="sidebar-item"><a class="sidebar-link text-info fw-bold" href="<?= $base_url?>system/op_git"><i class="align-middle me-1" data-feather="git-branch"></i> Version Control (Git) <span class="badge bg-primary rounded-pill float-end" style="font-size: 0.6rem;">VCS</span></a></li>
								<li class="sidebar-item"><a class="sidebar-link" href="<?= $base_url?>system/op_icons">Icon Library</a></li>
								<li class="sidebar-item"><a class="sidebar-link" href="<?= $base_url?>system/op_settings_manage">System Settings</a></li>
								<li class="sidebar-item"><a class="sidebar-link" href="<?= $base_url?>system/op_docs_manager">Asset Manager</a></li>
							</ul>
						</li>
					<?php } ?>

					<?php if(strtoupper($user_type) =='ADMIN') { ?>
						<li class="sidebar-header px-4 text-white-50 opacity-50 text-uppercase fw-bold mt-3" style="font-size: 10px; letter-spacing: 1px;">
							Administration
						</li>
						<li class="sidebar-item">
							<a data-bs-target="#admin-tools" data-bs-toggle="collapse" class="sidebar-link collapsed">
								<i class="align-middle" data-feather="settings"></i> <span class="align-middle">Control Panel</span>
							</a>
							<ul id="admin-tools" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
								<li class="sidebar-item"><a class="sidebar-link" href="<?= $base_url?>system/op_user_manage">User Management</a></li>
							</ul>
						</li>
					<?php } ?>
				</ul>

				<div class="sidebar-cta-modern mx-3 mb-4 mt-auto">
					<div class="cta-card glass p-3 rounded-4 border-0">
						<div class="d-flex align-items-center mb-2">
							<div class="cta-icon bg-success bg-opacity-20 text-success p-2 rounded-3 me-2">
								<i class="align-middle" data-feather="help-circle"></i>
							</div>
							<h6 class="mb-0 text-white fw-bold" style="font-size: 0.8rem;"><?= $help_title ?></h6>
						</div>
						<p class="text-white-50 mb-3" style="font-size: 0.75rem; line-height: 1.4;"><?= $help_msg ?></p>
						<div class="d-grid">
							<a href="https://wa.me/+91<?=$dev_contact?>?msg=Message%20from%20<?= $inst_name?>" class="btn btn-premium-success btn-sm d-flex align-items-center justify-content-center" target="_blank">
								<i class="fab fa-whatsapp me-2"></i> Immediate Support
							</a>
						</div>
					</div>
				</div>
			</div>
		</nav>

