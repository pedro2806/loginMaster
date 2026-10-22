<?php
/**
 * temporada.php — Logo de temporada (Día de Muertos 2026).
 *
 * Lo usan el logo sobre la tarjeta del usuario (inicio.php) y el del login
 * (index.php). Se ve hasta el 6 de noviembre de 2026 inclusive; el 7 a las
 * 00:00 (hora de CDMX) vuelven solos los de siempre, sin desplegar nada.
 *
 * La imagen tiene la misma proporción que img/messbook_logo3.png y lo visible
 * ocupa el mismo ancho: donde va el logo de siempre, éste mide lo mismo. Trae
 * las letras en blanco: va sin el filtro que blanquea al de siempre y sólo
 * sobre fondo oscuro.
 */
const MB_LOGO_TEMPORADA       = 'img/temporada/messbook_dia_de_muertos2.png';
const MB_LOGO_TEMPORADA_HASTA = '2026-11-07 00:00';   // hora de la Ciudad de México

/** ¿Toca el logo de temporada en este momento? */
function mbHayLogoTemporada(): bool {
    $zona = new DateTimeZone('America/Mexico_City');
    return new DateTimeImmutable('now', $zona) < new DateTimeImmutable(MB_LOGO_TEMPORADA_HASTA, $zona);
}
