<!-- Topbar -->
<nav class = "navbar navbar-expand navbar-light bg-white topbar mb-2 static-top shadow">



<!-- Sidebar Toggle (Topbar) -->
<button id = "sidebarToggleTop" class = "btn btn-link d-md-none rounded-circle mr-3">
    <i class = "fa fa-bars"></i>
</button>

<!-- Topbar Navbar -->
<ul class = "navbar-nav ml-auto">    
    <!-- Nav Item - Search Dropdown (Visible Only XS) -->
    <li class = "nav-item dropdown no-arrow d-sm-none">
        <a class = "nav-link dropdown-toggle" href = "#" id = "searchDropdown" role = "button"
            data-toggle = "dropdown" aria-haspopup = "true" aria-expanded = "false">
            <i class = "fas fa-search fa-fw"></i>
        </a>
        <!-- Dropdown - Messages -->
        <div class = "dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
            aria-labelledby = "searchDropdown">
            <form class = "form-inline mr-auto w-100 navbar-search">
                <div class = "input-group">
                    <input type = "text" class = "form-control bg-light border-0 small"
                        placeholder = "Search for..." aria-label = "Search"
                        aria-describedby = "basic-addon2">
                    <div class = "input-group-append">
                        <button class = "btn btn-primary" type = "button">
                            <i class = "fas fa-search fa-sm"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </li>    
    <div class = "topbar-divider d-none d-sm-block"></div>
    <!-- Nav Item - User Information -->    
    <li class = "nav-item dropdown no-arrow">
        <a class = "nav-link dropdown-toggle" href = "#" id = "userDropdown" role = "button"
            data-toggle = "dropdown" aria-haspopup = "true" aria-expanded = "false">
            <span class = "mr-4 text-gray-600">
                <?php echo $_COOKIE['nombredelusuario']?>
            </span>
            
            <i class="fas fa-user-circle fa-2x text-gray-300"></i>
            
            
        </a>
        <!-- Dropdown - User Information -->
        <div class = "dropdown-menu dropdown-menu-right shadow animated--grow-in"   aria-labelledby = "userDropdown">
            
            <div class = "dropdown-divider"></div>
            <a class = "dropdown-item" href = "#" data-toggle = "modal" data-target = "#logoutModalN">
                <i class = "fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                Salir
            </a>
        </div>
    </li>

</ul>

    <!-- Logout Modal-->
    <div class = "modal fade" id = "logoutModalN" tabindex = "-1" role = "dialog" aria-labelledby = "exampleModalLabel"aria-hidden = "true">
        <div class = "modal-dialog" role = "document">
            <div class = "modal-content border-left-danger">
                <div class = "modal-header">
                    <h4 class = "modal-title" id = "exampleModalLabel"> Cerrar sesión </h4>
                    <button class = "close" type = "button" data-dismiss = "modal" aria-label = "Close">
                        <span aria-hidden = "true">X</span>
                    </button>
                </div>
                <div class = "modal-body"><h5><b>¿Estas seguro?</b></h5></div>
                <div class = "modal-footer">
                    <button class = "btn btn-warning" type = "button" data-dismiss = "modal">Cancelar</button>
                    <a class = "btn btn-danger" href = "logout">Salir</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>

  
    </script>
</nav>
<!-- End of Topbar -->