-- ============================================================================
-- 2026-09-23 — Álbum de fotos del evento en messbook (pestaña "Fotos" de inicio.php)
-- Base: mess_rrhh (la misma de ../incidencias/conn.php).
--
-- NO se corre solo: se aplica a mano, en este orden, y después se copia el
-- id_evento que devuelva el paso 2 a MB_ALBUM_EVENTO en acciones_album.php.
-- ============================================================================


-- 1) Las fotos. Cada álbum es una fila de enc_eventos (id_evento).
--
--    InnoDB a propósito (usuarios es MyISAM, enc_eventos sí es InnoDB): así la
--    llave foránea existe de verdad. ON DELETE RESTRICT: si alguien borra el
--    evento desde el módulo de eventos con fotos adentro, la BD lo impide —
--    con CASCADE se irían las filas y los .jpg se quedarían huérfanos en disco.
--
--    noEmpleado es VARCHAR(11) como usuarios.noEmpleado.
--    url: sólo 'fotos_album/<24 hex>.jpg' (37 caracteres); acciones_album.php
--    no enseña ni borra nada que no tenga esa forma.
CREATE TABLE IF NOT EXISTS messbook_fotos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_evento   INT          NOT NULL,
    noEmpleado  VARCHAR(11)  NOT NULL,
    url         VARCHAR(64)  NOT NULL,
    fecha       DATETIME     NOT NULL,
    PRIMARY KEY (id),
    -- El muro ordena y pagina por la tupla (fecha, origen, id) sobre un UNION
    -- con las fotos de invitados de oktoberMESS: de este lado filtra por álbum
    -- y recorre por fecha, id. (origen es una constante por tabla.)
    KEY idx_album_fecha (id_evento, fecha, id),
    -- El tope de fotos por persona cuenta por álbum y empleado.
    KEY idx_album_empleado (id_evento, noEmpleado),
    UNIQUE KEY uq_url (url),
    CONSTRAINT fk_messbook_fotos_evento
        FOREIGN KEY (id_evento) REFERENCES enc_eventos (id_evento)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;


-- 2) El álbum de OktoberMESS como evento.
--
--    enc_eventos.tipo sólo admite 'votacion', 'asistencia' y 'encuesta' y se
--    decidió no cambiar la tabla. Se usa 'asistencia' porque es el único tipo
--    que NO sale como pendiente a todos los empleados: acciones_eventos.php
--    (listar_eventos_pendientes_empleado / listar_mis_actividades_completas)
--    sólo muestra un evento 'asistencia' a quien esté en enc_eventos_asignados,
--    y a este no se le asigna nadie. Un 'votacion' o 'encuesta' activo le
--    aparecería a todo el portal como actividad por contestar.
--
--    estatus = 0 para que tampoco lo abra ver_evento.php. El álbum no depende
--    de estatus ni de las fechas: se prende y se apaga con MB_ALBUM_EVENTO.
--    Sí aparece en la lista del módulo de eventos (admins): no borrarlo.
--    Las fechas son de referencia (el día real vive en config_evento.php de
--    oktoberMESS, que no está en git): ajústalas si quieres que cuadren.
--
--    Se puede correr más de una vez: si la fila ya existe (mismo nombre) no se
--    inserta otra, y el SELECT final devuelve la que ya estaba.
INSERT INTO enc_eventos (nombre, descripcion, tipo, fecha_inicio, fecha_fin, estatus)
SELECT 'OktoberMESS 2026 - Album de fotos',
       'Album de la pestana Fotos de messbook. No borrar ni asignar empleados: ver acciones_album.php.',
       'asistencia', '2026-10-01 00:00:00', '2026-10-31 23:59:59', 0
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM enc_eventos WHERE nombre = 'OktoberMESS 2026 - Album de fotos');

-- Este número va en MB_ALBUM_EVENTO (acciones_album.php). Si sale más de una
-- fila, alguien la duplicó a mano: usar la que ya tenga fotos.
SELECT id_evento AS id_evento_album
  FROM enc_eventos
 WHERE nombre = 'OktoberMESS 2026 - Album de fotos';
