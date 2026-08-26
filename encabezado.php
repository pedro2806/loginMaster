<style>
    /* Se quitó la barra superior para que el bloque de pestañas gane altura y
       quede mejor encuadrado; el logo se movió arriba de la tarjeta del usuario
       (ver inicio.php). De la barra solo sobrevive el botón que colapsa el menú,
       que ahora flota sobre el contenido en vez de vivir dentro del navbar.
       Este archivo lo comparten inicio.php, inicio2.php, inicio_res.php e
       inicioOld.php, por eso el modal de salir y la pila de avisos siguen aquí. */
    #toggleSidebarBtn {
        position: fixed;
        /* Alineado con el logo, no pegado al borde: el logo arranca tras el pt-3
           (16px) y mide 83px de alto, así que su centro cae en y≈57px; restando
           media altura del botón (19px) da 38px. Tiene que seguir siendo fixed
           porque la columna del perfil colapsa a ancho 0 y se lleva su contenido. */
        top: 38px;
        left: 10px;
        z-index: 1040;
        background: rgba(7, 68, 128, 0.85) !important;
        border: none !important;
        color: #fff;
        width: 38px;
        height: 38px;
        border-radius: 0.375rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        padding: 0;
    }

    #toggleSidebarBtn:hover {
        background: rgba(7, 68, 128, 1) !important;
    }

    #toggleSidebarBtn i {
        font-size: 1.1rem;
    }
</style>

<button id="toggleSidebarBtn" class="btn" type="button" title="Ocultar/Mostrar menú">
    <i class="fa fa-bars"></i>
</button>

<!-- Logo de respaldo para cuando la tarjeta del usuario está colapsada: el logo
     grande vive dentro de esa columna, que al colapsar queda en width:0 con
     overflow:hidden y se lo lleva consigo. Este solo se muestra en ese estado. -->
<img id="logoColapsado" src="../loginMaster/img/messbook_logo3.png" alt="Messbook">

<div>
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
</div>

<!-- Sube a 12px porque ya no hay barra superior de 56px que esquivar. -->
<div id="notificationStack" class="position-fixed" style="top: 12px; right: 20px; z-index: 1080; width: min(420px, calc(100vw - 2rem));"></div>