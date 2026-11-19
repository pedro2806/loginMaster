<?php
/**
 * modalTemas.php — "Logos de messbook": versiones de evento de los logos.
 *
 * Lo abre el logo de la columna de inicio.php, sólo para BI ($puedeTema;
 * includes/tema.php, mbTemaPuedeAdministrar). Sólo la vista: la lógica está en
 * js/temas.js y todo se guarda vía acciones_temas.php, que vuelve a revisar el
 * permiso en cada acción.
 *
 * Las filas de cada lugar las arma js/temas.js con el catálogo de abajo
 * (#temaCatalogo), que sale de las mismas constantes que usa el servidor.
 */
if (empty($puedeTema)) return;

$temaCatalogo = [
    'fijos' => [],
    'sistemas' => [],
    'ayuda' => [
        'portal'  => 'Sobre la tarjeta del usuario, en fondo azul: usa la versión con letras blancas y fondo transparente.',
        'login'   => 'Sólo en computadora y tableta, en fondo azul. En celular el login es blanco y sale siempre el de siempre.',
        'pie'     => 'Al final de la columna del perfil, en fondo azul.',
        'favicon' => 'El ícono de la pestaña del navegador. De preferencia cuadrado.',
    ],
];
foreach (MB_TEMA_LUGARES as $lugar => $nombre) {
    $temaCatalogo['fijos'][] = ['lugar' => $lugar, 'nombre' => $nombre];
}
foreach (MB_TEMA_SISTEMAS as $id => [$nombre, $icono, $texto]) {
    $temaCatalogo['sistemas'][] = ['lugar' => 'sis:' . $id, 'nombre' => $nombre, 'icono' => $icono, 'texto' => $texto];
}
?>

<div class="modal fade" id="modalTemas" tabindex="-1" role="dialog" aria-labelledby="tem_tituloModal" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content border-left-primary shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="tem_tituloModal"><i class="fas fa-palette mr-2"></i>Logos de messbook</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Versiones: la predeterminada primero, luego de la más nueva a la más vieja -->
                    <div class="col-lg-4 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="m-0 font-weight-bold">Versiones</h6>
                            <button type="button" class="btn btn-sm btn-success" id="tem_nueva">
                                <i class="fas fa-plus-circle mr-1"></i>Nueva versión
                            </button>
                        </div>
                        <div class="list-group tema-lista" id="tem_lista">
                            <div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i></div>
                        </div>
                        <p class="small text-muted mt-2 mb-0">
                            Cada versión se activa sola en sus fechas. Fuera de ellas sale la
                            <strong>predeterminada</strong>, y lo que una versión no cambie sale como siempre.
                        </p>
                    </div>

                    <!-- La versión elegida -->
                    <div class="col-lg-8">
                        <h6 class="font-weight-bold mb-2" id="tem_tituloForm">Predeterminada</h6>
                        <input type="hidden" id="tem_id" value="">

                        <div class="form-row" id="tem_datos">
                            <div class="col-md-12 mb-2">
                                <label class="small mb-1" for="tem_nombre">Nombre</label>
                                <input type="text" class="form-control form-control-sm" id="tem_nombre" maxlength="80"
                                       placeholder="Ej. Navidad 2026" autocomplete="off">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="small mb-1" for="tem_desde">Empieza</label>
                                <input type="datetime-local" class="form-control form-control-sm" id="tem_desde">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="small mb-1" for="tem_hasta">Termina</label>
                                <input type="datetime-local" class="form-control form-control-sm" id="tem_hasta">
                            </div>
                        </div>
                        <p class="small text-muted" id="tem_notaPred" hidden>
                            Es la de todos los días: sale cuando no hay ninguna versión activa. No lleva fechas.
                        </p>

                        <h6 class="tema-seccion">Portal y login</h6>
                        <div id="tem_fijos"></div>

                        <h6 class="tema-seccion">
                            <a class="tema-plegar collapsed" data-toggle="collapse" href="#tem_sistemas" role="button" aria-expanded="false" aria-controls="tem_sistemas">
                                Logos de los sistemas <i class="fas fa-chevron-down ml-1"></i>
                            </a>
                        </h6>
                        <div class="collapse" id="tem_sistemas">
                            <p class="small text-muted mb-2">
                                Se ven a 48 px de alto en la pestaña Sistemas. El del tema oscuro es opcional:
                                sin él, el claro sale en los dos temas.
                            </p>
                            <div id="tem_sis"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger mr-auto" id="tem_borrar" hidden>
                    <i class="fas fa-trash-alt mr-1"></i>Borrar versión
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="tem_guardar">
                    <i class="fas fa-save mr-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>
<script type="application/json" id="temaCatalogo"><?php echo json_encode($temaCatalogo, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
