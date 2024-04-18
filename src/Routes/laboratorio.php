<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


//Ingresa un registro
$app->post('/addRegistroMuestra', function (Request $request, Response $response) {

    $idLote = $request->getParam('idLote');
    $orden = $request->getParam('orden');
    $idUsuario = $request->getParam('idUsuario');
    $fechaRecepcion = $request->getParam('fechaRecepcion');
    $pesoMuestra = $request->getParam('pesoMuestra');
    $numSello = $request->getParam('numSello');
    $inspector = $request->getParam('inspector');

    try {
        $db = new db();
        $db = $db->connect();

        // Si el número de orden no existe, proceder con la inserción
        $consultaInsercion = "INSERT INTO registrolaboratorio (fechaRecepcion, pesoMuestra, numSello, inspector, orden, idUsuario, idLote) 
                              VALUES ('$fechaRecepcion', '$pesoMuestra', '$numSello', '$inspector', '$orden', '$idUsuario', '$idLote')";
        
        $ejecutar = $db->prepare($consultaInsercion);
        $result = $ejecutar->execute();
        $db = null;

        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    } catch (PDOException $e) {
        $error = array(
            "message" => $e->getMessage()
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});


$app->get('/getRegistroByIdLote/{idLote}', function (Request $request, Response $response) {

    $idLote = $request->getAttribute('idLote');
    $consulta = "SELECT * FROM registrolaboratorio WHERE idLote = '$idLote'";

    try {
        // Instanciar la base de datos
        $db = new db();
        // Conexión
        $db = $db->connect();
        $ejecutar = $db->prepare($consulta);
        $result =  $ejecutar->execute();
        $consulta2 = "SELECT * FROM registrolaboratorio  WHERE idLote = '$idLote'";
        $ejecutar2 = $db->query($consulta2);
        $result2 = $ejecutar2->fetch(PDO::FETCH_OBJ);
        $db = null;
        $response->getBody()->write(json_encode($result2));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    } catch (PDOException $e) {
        $error = array(
            "message" => $e->getMessage()
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);;
    }
});

$app->put('/editRegistroByIdLote', function (Request $request, Response $response) {

    $data = json_decode($request->getBody());
    $idLote = $data->idLote;
    $idUsuario = $data->idUsuario;
    $fechaRecepcion = $data->fechaRecepcion;
    $pesoMuestra = $data->pesoMuestra;
    $numSello = $data->numSello;
    $inspector = $data->inspector;
    
    $consulta = "UPDATE registrolaboratorio SET fechaRecepcion = ?, pesoMuestra = ?, numSello = ?, inspector = ?, idUsuario = ? WHERE idLote = ?";

    try {
        // Instanciar la base de datos
        $db = new db();

        // Conexión
        $db = $db->connect();
        $ejecutar = $db->prepare($consulta);
        $result = $ejecutar->execute([$fechaRecepcion, $pesoMuestra, $numSello, $inspector, $idUsuario, $idLote]);
        $db = null;
        $affectedRows = $ejecutar->rowCount();
        $response->getBody()->write(json_encode(array("affectedRows" => $affectedRows)));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    } catch (PDOException $e) {
        $error = array(
            "message" => $e->getMessage()
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);;
    }
});


//Agrega resultados humedad
$app->post('/saveResultHumedad', function (Request $request, Response $response) {
    // Asumiendo que el cuerpo del request contiene los datos en JSON
    $datos = json_decode($request->getBody()->getContents(), true);

    try {
        $db = new db();
        $db = $db->connect();

        // Inserción para los datos de la charola A
        $consultaInsercionA = $db->prepare("INSERT INTO resultadohumedad (valorIncremento, numero, numeroCharola, numeroCharolaUsuario, pesoTara, pesoHumedo, pesoBrutoNumerico,
                                    pesoBruto, pesoSeco1, pesoSeco2, pesoSeco3, condicion, porcentajeHumedad, orden, idUsuario, idLote) 
                              VALUES (:valorIncremento, :numero, :numeroCharola, :numeroCharolaUsuario, :pesoTara, :pesoHumedo, :pesoBrutoNumerico, :pesoBruto,
                                    :pesoSeco1, :pesoSeco2, :pesoSeco3, :condicion, :porcentajeHumedad, :orden, :idUsuario, :idLote)");
        // Ejecuta la consulta para la charola A usando los datos proporcionados
        $consultaInsercionA->execute([
            'valorIncremento' => $datos['datosCharolaA']['valorIncremento'],
            'numero' => $datos['datosCharolaA']['numero'],
            'numeroCharola' => $datos['datosCharolaA']['numeroCharola'],
            'numeroCharolaUsuario' => $datos['datosCharolaA']['numeroCharolaUsuario'],
            'pesoTara' => $datos['datosCharolaA']['pesoTara'],
            'pesoHumedo' => $datos['datosCharolaA']['pesoHumedo'],
            'pesoBrutoNumerico' => $datos['datosCharolaA']['pesoBrutoNumerico'],
            'pesoBruto' => $datos['datosCharolaA']['pesoBruto'],
            'pesoSeco1' => $datos['datosCharolaA']['pesoSeco1'],
            'pesoSeco2' => $datos['datosCharolaA']['pesoSeco2'],
            'pesoSeco3' => $datos['datosCharolaA']['pesoSeco3'],
            'condicion' => $datos['datosCharolaA']['condicion'],
            'porcentajeHumedad' => $datos['datosCharolaA']['porcentajeHumedad'],
            'orden' => $datos['datosCharolaA']['orden'],
            'idUsuario' => $datos['datosCharolaA']['idUsuario'],
            'idLote' => $datos['datosCharolaA']['idLote']
        ]);

        // Inserción para los datos de la charola B (incluido el promedio de humedad)
        $consultaInsercionB = $db->prepare("INSERT INTO resultadohumedad (valorIncremento, numero, numeroCharola, numeroCharolaUsuario, pesoTara, pesoHumedo, pesoBrutoNumerico,
                                    pesoBruto, pesoSeco1, pesoSeco2, pesoSeco3, condicion, porcentajeHumedad, orden, idUsuario, idLote) 
                              VALUES (:valorIncremento, :numero, :numeroCharola, :numeroCharolaUsuario, :pesoTara, :pesoHumedo, :pesoBrutoNumerico, :pesoBruto,
                                    :pesoSeco1, :pesoSeco2, :pesoSeco3, :condicion, :porcentajeHumedad, :orden, :idUsuario, :idLote)");
        // Ejecuta la consulta para la charola B usando los datos proporcionados

        $consultaInsercionB->execute([
            'valorIncremento' => $datos['datosCharolaB']['valorIncremento'],
            'numero' => $datos['datosCharolaB']['numero'],
            'numeroCharola' => $datos['datosCharolaB']['numeroCharola'],
            'numeroCharolaUsuario' => $datos['datosCharolaB']['numeroCharolaUsuario'],
            'pesoTara' => $datos['datosCharolaB']['pesoTara'],
            'pesoHumedo' => $datos['datosCharolaB']['pesoHumedo'],
            'pesoBrutoNumerico' => $datos['datosCharolaB']['pesoBrutoNumerico'],
            'pesoBruto' => $datos['datosCharolaB']['pesoBruto'],
            'pesoSeco1' => $datos['datosCharolaB']['pesoSeco1'],
            'pesoSeco2' => $datos['datosCharolaB']['pesoSeco2'],
            'pesoSeco3' => $datos['datosCharolaB']['pesoSeco3'],
            'condicion' => $datos['datosCharolaB']['condicion'],
            'porcentajeHumedad' => $datos['datosCharolaB']['porcentajeHumedad'],
            'orden' => $datos['datosCharolaB']['orden'],
            'idUsuario' => $datos['datosCharolaB']['idUsuario'],
            'idLote' => $datos['datosCharolaB']['idLote']
        ]);
        // Ahora, insertamos el promedioHumedad en su tabla correspondiente
        $consultaInsercionPromedio = $db->prepare("INSERT INTO promediohumedad (promedioHumedad, numero, orden, idUsuario, idLote) 
                              VALUES (:promedioHumedad, :numero, :orden, :idUsuario, :idLote)");

        $consultaInsercionPromedio->execute([
            'promedioHumedad' => $datos['datosCharolaB']['promedioHumedad'],
            'numero' => $datos['datosCharolaB']['numero'],
            'orden' => $datos['datosCharolaB']['orden'],
            'idUsuario' => $datos['datosCharolaB']['idUsuario'],
            'idLote' => $datos['datosCharolaB']['idLote']
        ]);

        $db = null;

        $response->getBody()->write(json_encode(['success' => true]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    } catch (PDOException $e) {
        $error = array(
            "message" => $e->getMessage()
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});
