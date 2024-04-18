<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->post('/addResultadoNave', function (Request $request, Response $response) {
    // Obtener los datos del cuerpo de la solicitud
    $parsedBody = $request->getParsedBody();

    // Extraer los datos necesarios
    $fechaMuestreo = $parsedBody['fechaMuestreo'];
    $sello = $parsedBody['sello'];
    $selloreemplazo = $parsedBody['selloremplazoForm'];
    $wmt = $parsedBody['wmt'];
    $lote = $parsedBody['lote'];
    $moisture = $parsedBody['moisture']; 
    $mt = $parsedBody['mt'];
    $dmt = $parsedBody['dmt'];
    $idUsuario = $parsedBody['idUsuario'];

    // Consulta para contar los resultados de la tabla costadonave para el lote dado
    $consultaCount = "SELECT COUNT(*) AS count FROM costadonave WHERE idlote = '$lote'";

    try {
        $db = new db();
        $db = $db->connect();

        // Realizar la consulta para contar los resultados para el lote dado
        $countStatement = $db->query($consultaCount);
        $count = $countStatement->fetch(PDO::FETCH_ASSOC)['count'];

        // Calcular el estado
        $estado = ($moisture > 7) ? 'Aprobado' : 'Rechazado';

        // Verificar si el próximo resultado es el 21 o 22 para el lote dado
        if ($count === '20' || $count === '21') {
            // Verificar si el resultado sería marcado como rechazado
            if ($estado === 'Rechazado') {
                // Cambiar el estado a "Aprobado"
                $estado = 'Aprobado';
            }
        }

        // Realizar la inserción del nuevo resultado
        $insertStatement = $db->prepare("INSERT INTO costadonave (fechaMuestreo, sello, selloreemplazo, wmt, moisture, mt, dmt, estado, idLote, idUsuario) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insertStatement->execute([$fechaMuestreo, $sello, $selloreemplazo, $wmt, $moisture, $mt, $dmt, $estado, $lote, $idUsuario]);

        // Enviar respuesta de éxito
        $responseData = ["success" => true, "message" => "Resultado agregado correctamente"];
        $response->getBody()->write(json_encode($responseData));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    } catch (PDOException $e) {
        // Enviar respuesta de error en caso de excepción
        $error = ["success" => false, "message" => $e->getMessage()];
        $response->getBody()->write(json_encode($error));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});



$app->get('/getResultadoNave/{idLote}', function (Request $request, Response $response) {

    $idLote = $request->getAttribute('idLote');

    $consulta = "SELECT c.*, MAX(m.limiteHumedad) AS limiteHumedad
    FROM costadonave AS c
    JOIN muestrapila AS m ON c.idLote = m.idLote
    WHERE c.idLote = '$idLote'
    GROUP BY c.idCostadonave ORDER BY sello ASC";

    try {

        // Instanciar la base de datos
        $db = new db();

        // Conexión
        $db = $db->connect();
        $ejecutar = $db->query($consulta);
        $result = $ejecutar->fetchAll(PDO::FETCH_ASSOC);
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
            ->withStatus(500);;
    }
});

$app->get('/getResultadoNaveCliente/{idLote}', function (Request $request, Response $response) {

    $idLote = $request->getAttribute('idLote');

    $consulta = "SELECT c.*, MAX(m.limiteHumedad) AS limiteHumedad
    FROM costadonave AS c
    JOIN muestrapila AS m ON c.idLote = m.idLote
    JOIN vistacliente AS v ON c.idLote = v.idLote
    WHERE c.idLote = '$idLote' AND v.estado = 'Aprobado' AND v.proceso = 'Resultado costado de nave'
    GROUP BY c.idCostadonave ORDER BY sello ASC";

    try {

        // Instanciar la base de datos
        $db = new db();

        // Conexión
        $db = $db->connect();
        $ejecutar = $db->query($consulta);
        $result = $ejecutar->fetchAll(PDO::FETCH_ASSOC);
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
            ->withStatus(500);;
    }
});

$app->get('/getResultadoNaveById/{idCostadonave}', function (Request $request, Response $response) {
    $idCostadonave = $request->getAttribute('idCostadonave');
    $consulta = "SELECT * FROM costadonave WHERE idCostadonave = '$idCostadonave' ORDER BY sello ASC";

    try {
        // Instanciar la base de datos
        $db = new db();
        // Conexión
        $db = $db->connect();
        $ejecutar = $db->query($consulta);
        $result = $ejecutar->fetchAll(PDO::FETCH_ASSOC);
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
            ->withStatus(500);;
    }
});

$app->delete('/eliminarResultNave/{idCostadonave}', function (Request $request, Response $response) {
    $idCostadonave = $request->getAttribute('idCostadonave');
    
    try {
        $db = new db();
        $db = $db->connect();
        
        // Paso 1: Recuperar el idLote antes de eliminar
        $consultaLote = "SELECT idLote FROM costadonave WHERE idCostadonave = :idCostadonave";
        $stmt = $db->prepare($consultaLote);
        $stmt->bindParam(':idCostadonave', $idCostadonave);
        $stmt->execute();
        $resultadoLote = $stmt->fetch(PDO::FETCH_OBJ);
        $idLote = $resultadoLote->idLote; // Asumiendo que siempre hay un resultado. Deberías manejar el caso contrario.
        
        // Paso 2: Eliminar el registro
        $consulta = "DELETE FROM costadonave WHERE idCostadonave = :idCostadonave";
        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':idCostadonave', $idCostadonave);
        $stmt->execute();
        
        // Paso 3: Obtener y entregar los datos después de eliminar
        $consulta2 = "SELECT * FROM costadonave WHERE idLote = :idLote ORDER BY sello ASC";
        $stmt2 = $db->prepare($consulta2);
        $stmt2->bindParam(':idLote', $idLote);
        $stmt2->execute();
        $result2 = $stmt2->fetchAll(PDO::FETCH_OBJ);
        
        $db = null;
        $response->getBody()->write(json_encode($result2));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        
    } catch (PDOException $e) {
        $error = array(
            "message" => $e->getMessage()
        );
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});


//Editar Un Resultado de Costado Nave
$app->put('/editResCostadoNave', function (Request $request, Response $response) {
    // Obtener los parámetros de la solicitud
    $idResultado = $request->getParam('idCostadonave');
    $fechaMuestreo = $request->getParam('fechaMuestreo');
    $sello = $request->getParam('sello');
    $selloreemplazo = $request->getParam('selloreemplazo');
    $wmt = $request->getParam('wmt');
    $moisture = $request->getParam('moisture');
    $mt = $request->getParam('mt');
    $dmt = $request->getParam('dmt');

    try {
        // Instanciar la base de datos
        $db = new db();

        // Conexión
        $db = $db->connect();

        $consultaPosicion = "SELECT COUNT(*) AS posicion FROM costadonave WHERE idLote = (SELECT idLote FROM costadonave WHERE idCostadonave = '$idResultado') AND idCostadonave <= '$idResultado'";

        // Ejecutar la consulta para obtener la posición
        $posicionStatement = $db->query($consultaPosicion);
        $posicion = $posicionStatement->fetch(PDO::FETCH_ASSOC)['posicion'];

        // Verificar si la posición es 21 o 22 y cambiar el estado si es necesario
        if ($posicion === '21' || $posicion === '22') {
            $estado = 'Aprobado';
        } else {
            // Calcular el estado basado en la humedad
            $estado = ($moisture > 7) ? 'Aprobado' : 'Rechazado';
        }

        // Consulta para actualizar el resultado
        $consultaUpdate = "UPDATE costadonave SET fechaMuestreo = '$fechaMuestreo', sello = '$sello', selloreemplazo = '$selloreemplazo', wmt = '$wmt', moisture = '$moisture', mt = '$mt', dmt = '$dmt', estado = '$estado' WHERE idCostadonave = '$idResultado'";

        // Ejecutar la consulta para actualizar el resultado
        $updateStatement = $db->prepare($consultaUpdate);
        $updateStatement->execute();

        // Obtener los datos actualizados
        $consultaSelectAll = "SELECT * FROM costadonave WHERE idLote = (SELECT idLote FROM costadonave WHERE idCostadonave = '$idResultado') ORDER BY sello ASC";
        $updatedResults = $db->query($consultaSelectAll)->fetchAll(PDO::FETCH_ASSOC);

        // Cerrar la conexión
        $db = null;

        // Enviar respuesta exitosa con los datos actualizados
        $response->getBody()->write(json_encode($updatedResults));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    } catch (PDOException $e) {
        // Enviar respuesta de error en caso de excepción
        $error = ["message" => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});



$app->get('/getAdjuntosNave/{idLote}', function (Request $request, Response $response, array $args) {
    $db = new db();
    $db = $db->connect();

    $idLote = $args['idLote'];

    try {
        $stmt = $db->prepare("SELECT nombreArchivo FROM adjuntonave WHERE idLote = :idLote");
        $stmt->bindParam(':idLote', $idLote, PDO::PARAM_INT);
        $stmt->execute();

        $adjuntos = $stmt->fetchAll(PDO::FETCH_OBJ);

        $pdfData = [];
        foreach ($adjuntos as $adjunto) {
            $filePath = 'C:\xampp\htdocs\api_grupo_mexico\/src/File/uploads/' . $adjunto->nombreArchivo;
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $pdfData[] = base64_encode($content);
            } else {
                $pdfData[] = null;
            }
        }
        $response->getBody()->write(json_encode($pdfData));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {
        $error = ["success" => false, "message" => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    } finally {
        $db = null;
    }
});

$app->post('/addAdjuntosNave', function (Request $request, Response $response) {
    $db = new db();
    $db = $db->connect();

    $parsedBody = $request->getParsedBody();
    $idLote = $parsedBody['idLote'];
    $idUsuario = $parsedBody['idUsuario'];
    $uploadedFiles = $request->getUploadedFiles();
    $informeResultados = $uploadedFiles['informeResultados'] ?? null;
    $informeSellos = $uploadedFiles['informeSellos'] ?? null;

    // Directorio para subir archivos
    $uploadsDirectory = './src/File/uploads/';
    if (!is_dir($uploadsDirectory)) {
        mkdir($uploadsDirectory, 0755, true);
    }

    // Inicializar nombres de archivo
    $filename = $filenameDos = null;

    try {
        // Procesar archivo adjunto 1 (informeResultados)
        if ($informeResultados && $informeResultados->getError() === UPLOAD_ERR_OK) {
            $filename = getUniqueFileName1($uploadsDirectory, $informeResultados->getClientFilename());
            $informeResultados->moveTo($uploadsDirectory . $filename);
            $insertStatement = $db->prepare("INSERT INTO adjuntonave (nombreArchivo, idLote, idUsuario) VALUES (?, ?, ?)");
            $insertStatement->execute([$filename, $idLote, $idUsuario]);
        }

        // Procesar archivo adjunto 2 (informeSellos)
        if ($informeSellos && $informeSellos->getError() === UPLOAD_ERR_OK) {
            $filenameDos = getUniqueFileName1($uploadsDirectory, $informeSellos->getClientFilename());
            $informeSellos->moveTo($uploadsDirectory . $filenameDos);
            $insertStatement = $db->prepare("INSERT INTO adjuntonave (nombreArchivo, idLote, idUsuario) VALUES (?, ?, ?)");
            $insertStatement->execute([$filenameDos, $idLote, $idUsuario]);
        }

        // Consulta para obtener los archivos adjuntos
        $joinStatement = $db->prepare("SELECT * FROM adjuntonave WHERE idLote = ?");
        $joinStatement->execute([$idLote]);
        $result = $joinStatement->fetchAll(PDO::FETCH_OBJ);

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {
        $error = ["success" => false, "message" => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    } finally {
        $db = null;
    }
});


// Función para generar un nombre de archivo único
function getUniqueFileName1($directory, $filename) {
    $now = new DateTime();
    $uniqueId = uniqid();
    return $now->format('YmdHis') . '_' . $uniqueId . '_' . $filename;
}

$app->get('/removeAdjuntosNave/{idLote}', function (Request $request, Response $response) {
    $idLote = $request->getAttribute('idLote');
    $directorio = 'C:\xampp\htdocs\api_grupo_mexico\/src\File\uploads/';

    try {
        // Instanciar y conectar a la base de datos
        $db = new db();
        $db = $db->connect();

        // Obtener los nombres de los archivos adjuntos
        $consultaArchivos = "SELECT nombreArchivo FROM adjuntonave WHERE idLote = :idLote";
        $stmt = $db->prepare($consultaArchivos);
        $stmt->bindParam(':idLote', $idLote);
        $stmt->execute();
        $archivos = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Eliminar archivos del directorio
        foreach ($archivos as $archivo) {
            $rutaArchivo = $directorio . $archivo->nombreArchivo;
            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);
            }
        }

        // Eliminar registros de la base de datos
        $consultaEliminar = "DELETE FROM adjuntonave WHERE idLote = :idLote";
        $stmt = $db->prepare($consultaEliminar);
        $stmt->bindParam(':idLote', $idLote);
        $stmt->execute();
        
        //Consulta si existen datos para el idLote
        $consultaVerificarVistaCliente = "SELECT COUNT(*) AS count FROM vistacliente WHERE idLote = :idLote AND proceso = 'Resultado costado de nave'";
        $stmt = $db->prepare($consultaVerificarVistaCliente);
        $stmt->bindParam(':idLote', $idLote);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Si hay registros en vistacliente para el idLote proporcionado, eliminarlos
        if ($count > 0) {
            $consultaEliminarSolicitud = "DELETE FROM vistacliente WHERE idLote = :idLote AND proceso = 'Resultado costado de nave'";
            $stmt = $db->prepare($consultaEliminarSolicitud);
            $stmt->bindParam(':idLote', $idLote);
            $stmt->execute();
        }

        // Obtener la tabla actualizada muestrapila
        $consultaMuestraPila = "SELECT * FROM  adjuntonave WHERE idLote = :idLote";
        $stmt = $db->prepare($consultaMuestraPila);
        $stmt->bindParam(':idLote', $idLote);
        $stmt->execute();
        $resultMuestraPila = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Cerrar conexión a la base de datos
        $db = null;

        // Enviar respuesta con la tabla muestrapila actualizada
        $response->getBody()->write(json_encode($resultMuestraPila));
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


$app->post('/addSolicitudVistaClienteNave', function (Request $request, Response $response) {
    $data = json_decode($request->getBody(), true);

    $idNominacion = $data['idNominacion'];
    $idLote = $data['idLote'];
    $idUsuario = $data['idUsuario'];
    $estado = $data['estado'];
    $proceso = $data['proceso'];
   

    // Consulta SQL para insertar en la tabla notificacion
    $sql = "INSERT INTO vistacliente (estado, proceso, idNominacion, idLote, idUsuario) VALUES (:estado, :proceso, :idNominacion, :idLote, :idUsuario)";

    try {
        // Obtener la conexión a la base de datos
        $db = new db();
        $conn = $db->connect();

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':proceso', $proceso);
        $stmt->bindParam(':idNominacion', $idNominacion);
        $stmt->bindParam(':idLote', $idLote);
        $stmt->bindParam(':idUsuario', $idUsuario);

        $stmt->execute();

        $response->getBody()->write(json_encode(['message' => 'Autorización solicitada con éxito']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {
        $error = array("message" => $e->getMessage());
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->get('/getAutorizacionVistaClienteNave/{idLote}', function (Request $request, Response $response) {
    $idLote = $request->getAttribute('idLote');
    try {
        // Instanciar y conectar a la base de datos
        $db = new db();
        $db = $db->connect();
        $consulta = "SELECT * FROM vistacliente WHERE proceso = 'Resultado costado de nave' AND idLote = '$idLote'";

        // Ejecutar consulta
        $ejecutar = $db->query($consulta);
        $result = $ejecutar->fetchAll(PDO::FETCH_OBJ);

        // Cerrar conexión
        $db = null;

        // Preparar respuesta
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
            ->withStatus(500);;
    }
});




