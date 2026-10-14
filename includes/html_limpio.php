<?php
/**
 * html_limpio.php — Deja un HTML "pegado de un correo" en formato básico y seguro.
 *
 * Lo usan los Avisos (acciones_avisos.php): el contenido se escribe en un editor
 * (Summernote) o se pega desde Outlook, y se guarda en la BD para pintarlo tal
 * cual en la pantalla de TODOS los empleados. Por eso se limpia en el servidor,
 * siempre, antes de guardar: lo que mande el navegador no cuenta.
 *
 * Lista blanca (lo que no está aquí no pasa):
 *   - Etiquetas de formato básico: párrafos, saltos, negrita, cursiva,
 *     subrayado, tachado, listas, enlaces, títulos chicos, citas y tablas.
 *   - Atributos: sólo href en <a> (http, https o mailto) y colspan/rowspan en
 *     celdas. Nada de style ni class: Outlook los llena de basura (mso-*).
 *     Antes de tirar el style de un <span>, la negrita / cursiva / subrayado
 *     que traía se convierten en <b> / <i> / <u>, que es como Outlook marca
 *     muchas veces el subrayado de una fecha.
 *   - Lo peligroso o inútil (script, style, iframe, imágenes, formularios…) se
 *     tira con todo su contenido; cualquier otra etiqueta desconocida (font,
 *     o:p, section…) se quita dejando su texto.
 */

// Se carga con require_once.

/** Etiquetas que se quedan (sus atributos los decide mbHtmlLimpiarAtributos). */
const MB_HTML_PERMITIDAS = [
    'p', 'br', 'div', 'span', 'b', 'strong', 'i', 'em', 'u', 's', 'strike',
    'ul', 'ol', 'li', 'a', 'h3', 'h4', 'h5', 'blockquote', 'hr',
    'table', 'thead', 'tbody', 'tr', 'td', 'th',
];

/** Se tiran CON su contenido. */
const MB_HTML_FUERA = [
    'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet',
    'head', 'title', 'meta', 'link', 'base', 'xml', 'svg', 'math', 'img', 'picture',
    'video', 'audio', 'source', 'canvas', 'form', 'input', 'button', 'select',
    'option', 'textarea', 'noscript', 'template',
];

/** Títulos grandes que se bajan a los permitidos (el aviso ya tiene su título). */
const MB_HTML_RENOMBRAR = ['h1' => 'h3', 'h2' => 'h3', 'h6' => 'h5'];

/**
 * El HTML limpio, listo para guardar y pintar. '' si no queda nada con
 * texto. Quien lo guarde revisa el largo del resultado.
 */
function mbHtmlLimpio(string $html): string {
    // Comentarios condicionales "revelados" de Office: <![if !supportLists]>
    // ... <![endif]>. No son comentarios para el parser: se quitan las marcas.
    $html = preg_replace('/<!\[if[^\]]*\]>|<!\[endif\]>/i', '', $html);
    if (mbHtmlSinTexto((string)$html)) return '';

    $doc = new DOMDocument('1.0', 'UTF-8');
    $antes = libxml_use_internal_errors(true);
    // El prefijo de codificación hace que el texto se lea como UTF-8 (sin él
    // DOMDocument asume ISO-8859-1 y rompe los acentos). Todo va dentro de un
    // <div> raíz propio, que no se devuelve.
    $doc->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>',
                   LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($antes);

    $raiz = null;
    foreach ($doc->childNodes as $n) {
        if ($n->nodeType === XML_ELEMENT_NODE) { $raiz = $n; break; }
    }
    if (!$raiz) return '';
    mbHtmlLimpiarHijos($raiz);

    $salida = '';
    foreach ($raiz->childNodes as $n) $salida .= $doc->saveHTML($n);
    $salida = trim($salida);
    return mbHtmlSinTexto($salida) ? '' : $salida;
}

/** ¿No trae nada que leer? Espacios y &nbsp; no cuentan como texto. */
function mbHtmlSinTexto(string $html): bool {
    $texto = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return preg_replace('/[\s\x{00A0}]+/u', '', $texto) === '';
}

/** Recorre los hijos de $padre y deja sólo lo de la lista blanca. */
function mbHtmlLimpiarHijos(DOMNode $padre): void {
    // Copia: la lista "viva" cambia mientras se quitan o desenvuelven nodos.
    $hijos = [];
    foreach ($padre->childNodes as $h) $hijos[] = $h;

    foreach ($hijos as $nodo) {
        if ($nodo->nodeType === XML_TEXT_NODE) continue;
        if ($nodo->nodeType !== XML_ELEMENT_NODE) {          // comentarios, PI, CDATA…
            $padre->removeChild($nodo);
            continue;
        }

        $tag = strtolower($nodo->nodeName);
        if (in_array($tag, MB_HTML_FUERA, true)) {
            $padre->removeChild($nodo);
            continue;
        }
        if (isset(MB_HTML_RENOMBRAR[$tag])) {
            $nodo = mbHtmlRenombrar($nodo, MB_HTML_RENOMBRAR[$tag]);
            $tag  = MB_HTML_RENOMBRAR[$tag];
        }

        // El formato que traía el style se rescata ANTES de limpiar atributos.
        $estilo = $nodo instanceof DOMElement ? strtolower($nodo->getAttribute('style')) : '';

        mbHtmlLimpiarHijos($nodo);

        if (!in_array($tag, MB_HTML_PERMITIDAS, true)) {
            mbHtmlDesenvolver($nodo);                        // font, o:p, section…: se queda el texto
            continue;
        }

        mbHtmlLimpiarAtributos($nodo, $tag);

        if ($tag === 'a' && !$nodo->hasAttribute('href')) {
            mbHtmlDesenvolver($nodo);                        // enlace sin destino válido: sólo el texto
            continue;
        }
        if ($tag === 'span') {
            foreach (mbHtmlFormatoDeEstilo($estilo) as $envoltura) {
                mbHtmlEnvolverHijos($nodo, $envoltura);
            }
            mbHtmlDesenvolver($nodo);                        // sin atributos, el span ya no sirve
        }
    }
}

/** Quita todos los atributos menos los que esa etiqueta puede llevar. */
function mbHtmlLimpiarAtributos(DOMElement $el, string $tag): void {
    $href = $tag === 'a' ? trim($el->getAttribute('href')) : '';
    $span = in_array($tag, ['td', 'th'], true)
        ? ['colspan' => $el->getAttribute('colspan'), 'rowspan' => $el->getAttribute('rowspan')]
        : [];

    while ($el->attributes->length) $el->removeAttribute($el->attributes->item(0)->nodeName);

    if ($tag === 'a' && preg_match('~^(https?://|mailto:)~i', $href)) {
        $el->setAttribute('href', $href);
        $el->setAttribute('target', '_blank');
        $el->setAttribute('rel', 'noopener noreferrer');
    }
    foreach ($span as $atr => $valor) {
        $n = (int)$valor;
        if ($n > 1 && $n <= 50) $el->setAttribute($atr, (string)$n);
    }
}

/** style="font-weight:bold; text-decoration:underline" → ['b', 'u']. */
function mbHtmlFormatoDeEstilo(string $estilo): array {
    if ($estilo === '') return [];
    $f = [];
    if (preg_match('/font-weight\s*:\s*(bold|bolder|[6-9]00)/', $estilo))  $f[] = 'b';
    if (preg_match('/font-style\s*:\s*italic/', $estilo))                   $f[] = 'i';
    if (preg_match('/text-decoration[a-z-]*\s*:[^;]*underline/', $estilo))  $f[] = 'u';
    return $f;
}

/** Pone los hijos de $nodo en su lugar y quita $nodo. */
function mbHtmlDesenvolver(DOMNode $nodo): void {
    $padre = $nodo->parentNode;
    if (!$padre) return;
    while ($nodo->firstChild) $padre->insertBefore($nodo->firstChild, $nodo);
    $padre->removeChild($nodo);
}

/** Mete todos los hijos de $nodo dentro de un <$tag> nuevo. */
function mbHtmlEnvolverHijos(DOMNode $nodo, string $tag): void {
    if (!$nodo->firstChild) return;
    $env = $nodo->ownerDocument->createElement($tag);
    while ($nodo->firstChild) $env->appendChild($nodo->firstChild);
    $nodo->appendChild($env);
}

/** <h1>…</h1> → <h3>…</h3>, con sus hijos. Devuelve el nodo nuevo. */
function mbHtmlRenombrar(DOMNode $nodo, string $tag): DOMNode {
    $nuevo = $nodo->ownerDocument->createElement($tag);
    while ($nodo->firstChild) $nuevo->appendChild($nodo->firstChild);
    $nodo->parentNode->replaceChild($nuevo, $nodo);
    return $nuevo;
}
