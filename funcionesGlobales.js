async function validaOpciones(sistema, opcion) {
    try {
        const info = await $.ajax({
            url: '../loginMaster/acciones_globales.php',
            type: 'POST',
            dataType: 'json',
            data: {
                noEmpleado: getCookie('noEmpleado'),
                sistema: sistema,
                opcion: opcion,
                accion: 'ValidarPermisos'
            }
        });

        return info; // Ahora sí espera a tener el valor
    } catch (error) {        
        return 0;
    }
}