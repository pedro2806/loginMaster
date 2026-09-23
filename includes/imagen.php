<?php
/**
 * imagen.php — Guardar una imagen que subió alguien, como JPEG limpio.
 *
 * Copia de includes/imagen.php de oktoberMESS con el prefijo cambiado (okt → mb)
 * para que no choquen si algún día se cargan los dos. Lo usa el álbum de fotos
 * (acciones_album.php). Es el punto donde se decide qué queda escrito en el
 * disco del servidor: si cambia allá, cambiarlo aquí también.
 */

if (!function_exists('mbGuardarJpegLimpio')) {

    /**
     * Re-codifica la imagen con GD. No es por tamaño (el celular ya la reduce):
     * es que al volver a pintarla se tiran los metadatos —la ubicación GPS con
     * la que muchos celulares firman cada foto— y cualquier cosa escondida
     * dentro del archivo. Lo que se guarda es SIEMPRE un JPEG limpio.
     */
    function mbGuardarJpegLimpio(string $origen, string $destino, int $ladoMax = 1600): bool {
        $datos = file_get_contents($origen);
        $img   = $datos !== false ? @imagecreatefromstring($datos) : false;
        if (!$img) return false;

        // Orientación EXIF: si el celular no la redujo (navegador viejo), la
        // foto llega "acostada" con una marca de rotación que GD ignora.
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($origen);
            $giro = [3 => 180, 6 => -90, 8 => 90][(int)($exif['Orientation'] ?? 1)] ?? 0;
            if ($giro) { $rot = imagerotate($img, $giro, 0); if ($rot) { imagedestroy($img); $img = $rot; } }
        }

        $w = imagesx($img); $h = imagesy($img);
        $escala = min(1, $ladoMax / max($w, $h));
        if ($escala < 1) {
            $nw = (int)round($w * $escala); $nh = (int)round($h * $escala);
            $chica = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($chica, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($img);
            $img = $chica;
        }
        $ok = imagejpeg($img, $destino, 82);
        imagedestroy($img);
        return $ok;
    }

    /**
     * ¿Lo que subieron es de verdad una imagen? El tipo se decide por el
     * CONTENIDO, no por el nombre ni por lo que diga el navegador: las dos
     * cosas las controla quien sube. Devuelve '' si está bien, o el mensaje
     * que hay que mostrar.
     *
     * getimagesize lee la cabecera real y viene en PHP de base; finfo es una
     * extensión que no todo hosting trae, así que se usa sólo si existe.
     */
    function mbRevisarImagen(string $tmp): string {
        $dim  = @getimagesize($tmp);
        $mime = is_array($dim) ? (string)($dim['mime'] ?? '') : '';
        if (class_exists('finfo')) {
            $mime = (string)((new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: $mime);
        }
        if (!$dim || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return 'Ese archivo no es una imagen. Sube un JPG, PNG o WebP.';
        }
        if ($dim[0] * $dim[1] > 40000000) {
            return 'La imagen es demasiado grande. Prueba con otra.';
        }
        if (!function_exists('imagecreatefromstring')) {
            error_log('messbook imagen: el servidor no tiene la extensión GD.');
            return 'Las imágenes no están disponibles en este momento. Avísale a BI.';
        }
        return '';
    }
}
