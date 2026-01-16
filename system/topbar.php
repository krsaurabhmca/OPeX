<div class="main">
<nav class="navbar navbar-expand navbar-light navbar-bg">
    <a class="sidebar-toggle js-sidebar-toggle">
        <i class="hamburger align-self-center"></i>
    </a>

    <form class="d-none d-sm-inline-block" action='op_search' method='post'>
        <div class="input-group input-group-navbar">
            <input type="text" class="form-control" placeholder="Search Member" aria-label="Search" id='search_text' name='search_term' required>
            <button class="btn" type="button" onclick='submit()'>
                <i class="align-middle" data-feather="search"></i>
            </button>
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
        <ul class="navbar-nav navbar-align">
            <li class="nav-item dropdown">
                <a class="nav-icon dropdown-toggle" href="#" id="alertsDropdown" data-bs-toggle="dropdown">
                    <div class="position-relative">
                        <i class="align-middle" data-feather="bell"></i>
                        <span class="indicator">1</span>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0" aria-labelledby="alertsDropdown">
                    <div class="dropdown-menu-header">
                         New Notifications
                    </div>
                    
                    <div class="list-group">
                        <a href="#" class="list-group-item">
                            <div class="row g-0 align-items-center">
                                <div class="col-2">
                                    <i class="text-danger" data-feather="alert-circle"></i>
                                </div>
                                <div class="col-10">
                                    <div class="text-dark">Update completed</div>
                                    <div class="text-muted small mt-1">Restart server 12 to complete the update.</div>
                                    <div class="text-muted small mt-1">30m ago</div>
                                </div>
                            </div>
                        </a> 
                    </div>

                    <div class="dropdown-menu-footer">
                        <a href="#" class="text-muted">Show all notifications</a>
                    </div>
                </div>
            </li>
            
            <li class="nav-item">
                <a class="nav-icon d-none d-lg-block" href="<?= $base_url.'system/op_chat'?>">
                    <div class="position-relative">
                    <i  class="align-middle" data-feather="message-circle"></i>
                    <!-- <span data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight"><i  class="align-middle" data-feather="message-circle"></i></span> -->
                    </div>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-icon js-fullscreen d-none d-lg-block" href="#">
                    <div class="position-relative">
                        <i class="align-middle" data-feather="maximize"></i>
                    </div>
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-icon pe-md-0 dropdown-toggle" href="#" data-bs-toggle="dropdown">
                   <img src='<?= $user_photo ?>' width='40px' height='40px'>
                   
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="<?= $base_url?>system/op_user_add?link=<?= encode("id=".$_SESSION['user_id'])?>"><i class="align-middle me-1" data-feather="user"></i> Profile</a>
                   
                    <a class="dropdown-item" href="op_change_password"><i class="align-middle me-1" data-feather="settings"></i> Change Password</a>
                    <a class="dropdown-item" href="#"><i class="align-middle me-1" data-feather="help-circle"></i> Help Center</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" onclick="logout()">Log out</a>
                </div>
            </li>
        </ul>
    </div>
</nav>