<?php
/**
 * modalAvisos.php — Los modales del módulo de Avisos. Sólo la vista: lo que
 * se ve en ellos lo pinta js/avisos.js con lo que responde acciones_avisos.php.
 *
 *   #modalAvisoNuevo — pop-up "NUEVO AVISO" al entrar a messbook. Sólo se
 *                      cierra con "Aceptar" (queda registrado quién y cuándo).
 *   #modalAvisoVer   — "ver más" de la pestaña Avisos.
 *   #modalAvisoForm  — "Crear Aviso" / editar; sólo si $puedeCrearAviso.
 *
 * inicio.php lo incluye después de cargar includes/avisos.php y de definir
 * $puedeCrearAviso.
 */
if (!isset($puedeCrearAviso) || !function_exists('avLogosHtml')) return;
?>

<script>
    // Configuración para js/avisos.js
    window.MB_AVISOS = {
        puedeCrear: <?php echo $puedeCrearAviso ? 'true' : 'false'; ?>
    };
</script>

<!-- Pop-up "NUEVO AVISO": sin tache ni clic afuera; sólo "Aceptar". -->
<div class="modal fade aviso-modal" id="modalAvisoNuevo" tabindex="-1" role="dialog" aria-labelledby="avNuevoEtiqueta"
     aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="aviso-modal__cabecera">
                <span id="avNuevoEtiqueta">NUEVO AVISO</span>
                <small id="avNuevoContador"></small>
            </div>
            <div class="modal-body">
                <?php echo avLogosHtml('aviso-modal__logo'); ?>
                <div id="avNuevoCuerpo"></div>
            </div>
            <div class="aviso-modal__pie">
                <button type="button" class="btn btn-primary aviso-aceptar" id="avNuevoAceptar">Aceptar</button>
            </div>
        </div>
    </div>
</div>

<!-- "ver más": el aviso completo. Para el autor o un admin, además quién lo
     leyó, editar y ocultar. -->
<div class="modal fade aviso-modal" id="modalAvisoVer" tabindex="-1" role="dialog" aria-labelledby="avVerEtiqueta" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" role="document">
        <div class="modal-content">
            <div class="aviso-modal__cabecera">
                <span id="avVerEtiqueta">AVISO</span>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="avVerCuerpo"></div>
                <p class="aviso-meta" id="avVerMeta"></p>

                <!-- Sólo autor o admin (js/avisos.js lo muestra) -->
                <div class="aviso-gestion" id="avVerGestion" hidden>
                    <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap:.5rem;">
                        <span class="small" id="avVerLeido"></span>
                        <div class="d-flex flex-wrap" style="gap:.5rem;">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="avVerQuienes">
                                <i class="fas fa-user-check mr-1"></i>Quién lo leyó
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="avVerEditar">
                                <i class="fas fa-pen mr-1"></i>Editar
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="avVerOcultar">
                                <i class="fas fa-eye-slash mr-1"></i>Ocultar
                            </button>
                        </div>
                    </div>
                    <div class="aviso-quienes" id="avVerQuienesLista" hidden></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($puedeCrearAviso): ?>
<!-- Crear / editar aviso -->
<div class="modal fade aviso-modal" id="modalAvisoForm" tabindex="-1" role="dialog" aria-labelledby="avFormEtiqueta" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" role="document">
        <div class="modal-content">
            <div class="aviso-modal__cabecera">
                <span id="avFormEtiqueta"><i class="fas fa-bullhorn mr-2"></i>Nuevo Aviso</span>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body aviso-form">
                <input type="hidden" id="avId" value="0">

                <div class="form-group" id="avAreaCaja">
                    <label for="avArea">Área que publica</label>
                    <select class="form-control" id="avArea"></select>
                    <small class="text-muted">Define el color del aviso. Todos los empleados lo ven.</small>
                </div>
                <div class="form-group">
                    <label for="avTitulo">Título</label>
                    <input type="text" class="form-control" id="avTitulo" maxlength="120" autocomplete="off" placeholder="Ej. THOMSON INDUSTRIES">
                </div>
                <div class="form-group">
                    <label for="avSubtitulo">Subtítulo</label>
                    <input type="text" class="form-control" id="avSubtitulo" maxlength="160" autocomplete="off" placeholder="Ej. Cierre Contable año 2026">
                </div>
                <div class="form-group">
                    <label for="avContenido">Contenido</label>
                    <div id="avContenido"></div>
                    <small class="text-muted">Puedes pegar el texto del correo: se conservan negritas, subrayado, listas y enlaces.</small>
                </div>
                <div class="form-group">
                    <label for="avVigencia">Vigencia del aviso</label>
                    <input type="datetime-local" class="form-control" id="avVigencia">
                    <small class="text-muted">Hasta esta fecha sale como pop-up al entrar a messbook y se ve en Avisos. Después deja de verse; tú lo sigues viendo como vencido para extender la fecha.</small>
                </div>
                <div class="form-group">
                    <label for="avPdf">Adjuntar PDF</label>
                    <label class="aviso-drop" id="avDrop" for="avPdf">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span id="avDropTexto">Arrastra o selecciona un PDF</span>
                    </label>
                    <input type="file" id="avPdf" accept="application/pdf" hidden>
                    <div class="aviso-pdf-actual" id="avPdfActual" hidden>
                        <i class="fas fa-file-pdf"></i>
                        <a href="#" target="_blank" rel="noopener" id="avPdfActualLink">PDF adjunto</a>
                        <button type="button" class="btn btn-link btn-sm text-danger p-0 ml-2" id="avPdfQuitar">Quitar</button>
                    </div>
                </div>

                <div class="text-center">
                    <button type="button" class="btn btn-primary aviso-aceptar" id="avGuardar">
                        <i class="fas fa-paper-plane mr-2"></i><span id="avGuardarTexto">Lanzar aviso</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
