<?php
include '../ControlVehicular/conn.php';

$email = $_POST['InputEmail'];
//validar variable email
$emailValido = str_replace('', '', $email); 
$password = $_POST['InputPassword'];
$accion = $_POST['btningresar'];
$varKPIS = '';
$accionT = $_POST['accion'] ?? '';
$accionV = $_POST['accionV'] ?? '';

if($emailValido == 'silvia') { $varKPIS = 'RENI_2024'; }
if($emailValido == 'martin.becerra') { $varKPIS = 'bajio23_AL8A'; }
if($emailValido == 'jorge') { $varKPIS = 'nte24_PMP'; }
if($emailValido == 'omaral') { $varKPIS = 'occ23_OALG'; }
if($emailValido == 'vrico') { $varKPIS = 'VRClabs_24'; }
if($emailValido == 'marypaz.cruz') { $varKPIS = 'calidad2024*'; }
if($emailValido == 'omar.corro') { $varKPIS = 'slp23_PROG'; }
if($emailValido == 'cinthia.garcia') { $varKPIS = 'slp24_CG'; }
if($emailValido == 'alberto') { $varKPIS = 'ServiciosMess0924'; }
if($emailValido == 'adrian.castruita') { $varKPIS = 'Ocupacion1124'; }
if($emailValido == 'arnoldo') { $varKPIS = 'Ocupacion_ALARA24'; }
if($emailValido == 'sergio') { $varKPIS = 'Ocupacion_SCOTA24'; }
if($emailValido == 'hugo.soria') { $varKPIS = 'Mess@servicios25'; }
if($emailValido == 'alberto.mg') { $varKPIS = 'IINSCAN2023_ALB'; }
if($emailValido == 'orana.salcedo') { $varKPIS = 'IINSCAN2023_OR'; }
if($emailValido == 'juan.guzman') { $varKPIS = 'fxt_2023'; }
if($emailValido == 'nayeli.trejo') { $varKPIS = 'drptmc_2023'; }
if($emailValido == 'ariadna') { $varKPIS = 'votyh_2023'; }
if($emailValido == 'jlhernandez') { $varKPIS = 'MESS_mkt2024'; }
if($emailValido == 'oscar') { $varKPIS = 'mess_dir2023'; }
if($emailValido == 'sandra.galindo') { $varKPIS = 'nes23_KPIS'; }
if($emailValido == 'fernanda.hernandez') { $varKPIS = 'rrhhpbi23'; }
if($emailValido == 'carmen.ls') { $varKPIS = 'reciclados_CL23'; }
if($emailValido == 'omar') { $varKPIS = 'sfg23PBI'; }
if($emailValido == 'karen.hernandez') { $varKPIS = 'AK47_MIC'; }
if($emailValido == 'jose.reynoso') { $varKPIS = 'MESS_C0602J'; }
if($emailValido == 'christian.resendiz') { $varKPIS = 'M3SS_COT62CR'; }
if($emailValido == 'ramon.jauregui') { $varKPIS = 'M3SS_CT06RJ'; }
if($emailValido == 'ramiro.garcia') { $varKPIS = 'MESS_C0525RG'; }
if($emailValido == 'alfonso.camacho') { $varKPIS = 'M3SS_COTJC05'; }
if($emailValido == 'itzel.uribe') { $varKPIS = 'MESS_C0527KU'; }
if($emailValido == 'yessica.hernandez') { $varKPIS = 'MESS_C0526YH'; }
if($emailValido == 'fernanda.rodriguez') { $varKPIS = 'MESS_C0524SF'; }
if($emailValido == 'francisco.martinez') { $varKPIS = 'MESS_0306FM'; }
if($emailValido == 'julian.martinez') { $varKPIS = 'M3SS_0306JM'; }
if($emailValido == 'osiel.pardo') { $varKPIS = 'ME55_0306OP'; }

if($emailValido == 'cuentasporcobrar') { $varKPIS = 'PorCobrar_1124'; }
if($emailValido == 'leticia.vazquez') { $varKPIS = 'M3SS_0506LV'; }
if($emailValido == 'victor.acosta') { $varKPIS = 'M3SS_0610VA'; }
if($emailValido == 'jose.lara') { $varKPIS = 'M3SS_0613JTL'; }
if($emailValido == 'misael.gutierrez') { $varKPIS = 'Cal1108MG'; }
if($emailValido == 'sebastian.angulo') { $varKPIS = 'Cal1108SA'; }
if($emailValido == 'joseluis.tejeda') { $varKPIS = 'Cal1108JT'; }
if($emailValido == 'alberto.olguin') { $varKPIS = 'Cal1108LO'; }
if($emailValido == 'guadalupe.suarez') { $varKPIS = 'Cal1108MS'; }
if($emailValido == 'manuel.mendoza') { $varKPIS = 'Dim1108JM'; }
if($emailValido == 'maria.ayala') { $varKPIS = 'Dim1108AA'; }
if($emailValido == 'erik') { $varKPIS = 'Dim1108EG'; }
if($emailValido == 'gerardo') { $varKPIS = 'Dim1108GM'; }
if($emailValido == 'angeles.pacheco') { $varKPIS = 'EPT1108MP'; }
if($emailValido == 'maria.sanchez') { $varKPIS = 'EPT1108MS'; }
if($emailValido == 'emmanuel.vizcaya') { $varKPIS = 'EPT1108EV'; }
if($emailValido == 'hector.ortiz') { $varKPIS = 'EPT1108HO'; }
if($emailValido == 'jose.porras') { $varKPIS = 'EPT1108JP'; }
if($emailValido == 'pedro.velazquez') { $varKPIS = 'EPT1108PV'; }
if($emailValido == 'angel.bonilla') { $varKPIS = 'FPT1108AB'; }
if($emailValido == 'adal.beltran') { $varKPIS = 'FPT1108CB'; }
if($emailValido == 'lab.par') { $varKPIS = 'FPT1108JG'; }
if($emailValido == 'juanita.estrella') { $varKPIS = 'FPT1108MC'; }
if($emailValido == 'ricardo.basilio') { $varKPIS = 'FPT1108RB'; }
if($emailValido == 'tomas') { $varKPIS = 'FPT1108TG'; }
if($emailValido == 'alexis.fundora') { $varKPIS = 'FPT1108AF'; }
if($emailValido == 'guillermo.cruz') { $varKPIS = 'FPT1108GC'; }
if($emailValido == 'jhony.hernandez') { $varKPIS = 'FPT1108RH'; }
if($emailValido == 'juan.harrell') { $varKPIS = 'FPT1108JH'; }
if($emailValido == 'alfredo.robles') { $varKPIS = 'FPT1108LR'; }
if($emailValido == 'patricio.espino') { $varKPIS = 'FPT1108PE'; }
if($emailValido == 'hugo.soria') { $varKPIS = 'Mess@servicios25'; }

if($emailValido == 'jefes y gerente laboratorios') { $varKPIS = 'M3SS_Labs0525'; }

if ($accion == 'Ingresar') {
    $datosUsr = [];        

    $sql = "SELECT  * 
            FROM usuarios 
            WHERE (usuario = '$email' 
                OR usuario LIKE '$emailValido@%') 
            AND password = '$password'";                
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $vehiculos = [];
        while ($row = $result->fetch_assoc()) {
            
            $datosUsr[] = [                
                'id' => $row['id'],
                'usuario' => $row['usuario'],
                'nombre' => $row['nombre'],
                'noEmpleado' => $row['noEmpleado'],
                'rol' => $row['rol'],
                'email' => $row['email'],
                'kpis' => $varKPIS
            ];
        }
        echo json_encode($datosUsr);
    } else {
        echo json_encode($datosUsr);
    }
    exit;
    
}

//FUNCIONALIDAD PARA REGISTRAR TALLAS
if ($accionT == 'registraTallas') {
    include '../incidencias/conn.php';

    $noEmpleado = isset($_POST['noEmpleado']) ? $_POST['noEmpleado'] : '';
    $talla = isset($_POST['talla']) ? $_POST['talla'] : '';

    // Verificar si ya existe una talla para ese empleado
    $sqlExiste = "SELECT COUNT(*) FROM tallas WHERE noEmpleado = ?";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->bind_param("i", $noEmpleado);
    $stmtExiste->execute();
    $stmtExiste->bind_result($existe);
    $stmtExiste->fetch();
    $stmtExiste->close();

    if ($existe > 0) {
        // Ya existe: hacer UPDATE
        $sqlUpdate = "UPDATE tallas SET talla = ?, fecha_captura = NOW() WHERE noEmpleado = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("si", $talla, $noEmpleado);
        $success = $stmtUpdate->execute();
        $stmtUpdate->close();
    } else {
        // No existe: hacer INSERT
        $sqlInsert = "INSERT INTO tallas (talla, prenda, fecha_captura, noEmpleado) VALUES (?, 'Playera', NOW(), ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("si", $talla, $noEmpleado);
        $success = $stmtInsert->execute();
        $stmtInsert->close();
    }

    echo json_encode(['success' => $success]);
    $conn->close();
    exit;
}

//VALIDAR TALLA 
if ($accionT == 'validaTalla') {
    include '../incidencias/conn.php';
    $noEmpleado = isset($_POST['noEmpleado']) ? $_POST['noEmpleado'] : '';

    $sqlValidaTalla = "SELECT talla FROM tallas WHERE noEmpleado = ?";
    $stmt = $conn->prepare($sqlValidaTalla);
    $stmt->bind_param("i", $noEmpleado); // "i" porque es entero
    $stmt->execute();
    $stmt->bind_result($talla);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => true, 'exists' => true, 'talla' => $talla]);
    } else {
        echo json_encode(['success' => false]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

//BUZON DE SUGERENCIAS
if ($accionT == 'buzon') {
    include '../incidencias/conn.php';

    $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
    $comentario = isset($_POST['comentario']) ? $_POST['comentario'] : '';
    $noEmpleado = isset($_POST['noEmpleado']) ? $_POST['noEmpleado'] : '';

    $sqlInsert = "INSERT INTO buzon (noEmpleado, tipo, comentario, fecha_registro) VALUES (?, ?, ?, NOW())";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("iss", $noEmpleado, $tipo, $comentario);
    $success = $stmtInsert->execute();
    $stmtInsert->close();

    echo json_encode(['success' => $success]);
    $conn->close();
    exit;
}

//REGISTRAR VOTACION hallowen 2025
if ($accionV == 'votacion') {
    include '../incidencias/conn.php';

    $noEmpleado = $_POST['noEmpleado'] ?? '';
    $id_foto = $_POST['id_foto'] ?? '';

    //VALIDAR SI YA VOTO
    $sqlCheck = "SELECT COUNT(*) FROM votos_fotos WHERE id_usuario = ? AND encuesta = 'Hallowen2025'";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("i", $noEmpleado);
    $stmtCheck->execute();
    $stmtCheck->bind_result($yaVoto);
    $stmtCheck->fetch();
    $stmtCheck->close();

    if ($yaVoto > 0) {
        echo json_encode(['success' => false]);
        exit;
    }

    //REGISTRAR VOTO
    $sqlInsert = "INSERT INTO votos_fotos (id_usuario, id_foto, encuesta, fecha) VALUES (?, ?, 'Hallowen2025', NOW())";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("ii", $noEmpleado, $id_foto);
    $success = $stmtInsert->execute();
    $stmtInsert->close();

    echo json_encode(['success' => $success]);
    $conn->close();
    exit;
}
?>
