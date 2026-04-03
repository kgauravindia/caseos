<div class="main">
<nav class="navbar navbar-expand navbar-light navbar-bg">
    <a class="sidebar-toggle js-sidebar-toggle">
        <i class="hamburger align-self-center"></i>
    </a>

    <form class="d-none d-sm-inline-block" action='op_search' method='post' type='system'>
        <div class="input-group input-group-navbar shadow-sm rounded-pill overflow-hidden" style="max-width: 300px; border: 1px solid #e2e8f0;">
            <span class="input-group-text bg-white border-0 ps-3">
                <i class="text-muted feather-sm" data-feather="search" style="width:14px; height:14px;"></i>
            </span>
            <input type="text" class="form-control border-0 bg-white" placeholder="Search activities..." aria-label="Search" id='search_text' name='search_term' required style="font-size: 0.85rem;">
        </div>
    </form>

    <ul class="navbar-nav d-none d-lg-flex">
        <li class="nav-item px-2 dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="megaDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true"
                aria-expanded="false">
                Quick Lunch
            </a>
            <div class="dropdown-menu dropdown-menu-start dropdown-mega" aria-labelledby="megaDropdown">
                <div class="d-md-flex align-items-start justify-content-start">
                    <div class="dropdown-mega-list">

                    <!-- <div class="dropdown-header">Short </div> -->
                        <?php 
                        $qmenu = get_all('op_menu','*',array('quick_lunch'=>'YES'));
                        foreach((array)$qmenu['data'] as $menu)
                        {
                            $link = $base_url.$menu['link'];
                           echo "<a class='dropdown-item' href='$link'>". $menu['title'] ."</a>";

                        }
                       ?>
                    
                    <!-- </div> -->
                  
                  
                </div>
            </div>
        </li>
    </ul>

    <div class="navbar-collapse collapse">
        <ul class="navbar-nav navbar-align align-items-center">
            <li class="nav-item dropdown mx-1">
                <a class="nav-icon dropdown-toggle bg-light rounded-circle p-2" href="#" id="alertsDropdown" data-bs-toggle="dropdown" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                    <div class="position-relative">
                        <i class="align-middle feather-sm" data-feather="bell" style="width:18px; height:18px;"></i>
                        <span class="indicator bg-primary" style="top: -2px; right: -2px;"></span>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0 shadow-premium border-0" aria-labelledby="alertsDropdown">
                    <div class="dropdown-menu-header bg-white border-bottom py-3">
                         <span class="fw-bold">Notifications</span>
                    </div>
                    
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action py-3">
                            <div class="row g-0 align-items-center">
                                <div class="col-2 text-center">
                                    <div class="bg-primary-soft p-2 rounded-circle d-inline-block">
                                        <i class="text-primary feather-sm" data-feather="refresh-cw"></i>
                                    </div>
                                </div>
                                <div class="col-10 ps-2">
                                    <div class="text-dark small fw-semibold">System Update</div>
                                    <div class="text-muted extra-small mt-1">All modules are up to date with the latest security patch.</div>
                                    <div class="text-muted extra-small mt-1">Just now</div>
                                </div>
                            </div>
                        </a> 
                    </div>

                    <div class="dropdown-menu-footer py-2">
                        <a href="#" class="text-muted small">View all notifications</a>
                    </div>
                </div>
            </li>
            
            <li class="nav-item mx-1">
                <a class="nav-icon bg-light rounded-circle p-2" href="<?= $base_url.'system/op_chat'?>" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                    <i class="align-middle feather-sm" data-feather="message-circle" style="width:18px; height:18px;"></i>
                </a>
            </li>

            <li class="nav-item dropdown ms-2">
                <a class="nav-link dropdown-toggle d-flex align-items-center bg-light rounded-pill px-2 py-1" href="#" data-bs-toggle="dropdown">
                   <img src='<?= $user_photo ?>' width='32' height='32' class="rounded-circle shadow-sm border border-white">
                   <span class="text-dark fw-semibold ms-2 d-none d-md-inline-block me-1" style="font-size: 0.85rem;"><?= @$_SESSION['user_name']; ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end shadow-premium border-0 mt-2">
                    <a class="dropdown-item py-2" href="<?= $base_url?>system/op_user_add?link=<?= encode("id=".$_SESSION['user_id'])?>">
                        <i class="align-middle me-2 feather-sm" data-feather="user"></i> My Profile
                    </a>
                    <a class="dropdown-item py-2" href="op_change_password">
                        <i class="align-middle me-2 feather-sm" data-feather="settings"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item py-2 text-danger" href="#" onclick="logout()">
                        <i class="align-middle me-2 feather-sm" data-feather="log-out"></i> Sign out
                    </a>
                </div>
            </li>
        </ul>
    </div>
</nav>