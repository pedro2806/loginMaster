<style>
    .fb-logo-img {
        max-width: 320px;
        width: 100%;
        margin: 0 0 16px 0;
        filter: brightness(0) invert(1) drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        display: block;
    }
    
    /* Botón toggle sidebar - SIN FONDO */
    #toggleSidebarBtn {
        background: transparent !important;
        border: none !important;
        color: #fff;
        width: 40px;
        height: 40px;
        border-radius: 0.375rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        margin-right: 1.5rem;
        padding: 0;
    }
    
    #toggleSidebarBtn:hover {
        background: rgba(255,255,255,0.1) !important;
    }
    
    #toggleSidebarBtn i {
        font-size: 1.25rem;
    }
    
    
</style>

<!-- Topbar -->
<nav class="navbar navbar-expand navbar-light bg-white mb-2 static-top shadow" style="background-color: #0e2788 !important;">

    <!-- BOTÓN TOGGLE SIDEBAR -->
    <button id="toggleSidebarBtn" class="btn" type="button" title="Ocultar/Mostrar menú">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Sidebar Toggle (Topbar) Mobile -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-1">
        <i class="fa fa-bars"></i>
    </button>

   

    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto" style="height:60px; align-items:center;">
        <!-- Nav Item - User Information -->
        <li class="nav-item dropdown no-arrow" style="height:20px;">
            <a class="nav-link dropdown-toggle py-1" id="userDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="height:20px; display:flex; align-items:center;">
                <span class="mr-0 text-gray-600" style="font-size:15px; line-height:1;  color: #fff !important;">
                  <br> 
                <img src="../loginMaster/img/messbook_logo3.png" alt="Logo" class="fb-logo-img" style="height: 80px; margin-right: 10px;">
                </span>
            </a>
        </li>
    </ul>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModalN" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content border-left-danger">
                <div class="modal-header">
                    <h4 class="modal-title" id="exampleModalLabel">Cerrar sesión</h4>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">X</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h5><b>¿Estas seguro?</b></h5>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-warning" type="button" data-dismiss="modal">Cancelar</button>
                    <a class="btn btn-danger" href="logout">Salir</a>
                </div>
            </div>
        </div>
    </div>
</nav>
<!-- End of Topbar -->

<div id="notificationStack" class="position-fixed" style="top: 70px; right: 20px; z-index: 1080; width: min(420px, calc(100vw - 2rem));"></div>