<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


$app->post('/addResultadoPila', function (Request $request, Response $response) {
    $parsedBody = $request->getParsedBody();
    $idLote = $parsedBody['idLote'];
    $tons = $parsedBody['tons'];
    $muestradoPor = $parsedBody['muestradoPor'];
    $fechaMuestreo = $parsedBody['fechaMuestreo'];
    $lugarMuestreo = $parsedBody['lugarMuestreo'];
    $humedadFlujo = $parsedBody['humedadFlujo'];
    $limiteHumedad = $parsedBody['limiteHumedad'];
    $humedad = $parsedBody['humedad'];
    $idUsuario = $parsedBody['idUsuario'];
    $estadoMuestra = ($humedad > 7 && $humedad < $limiteHumedad) ? 'Aprobado' : 'Rechazado';
    $adjunto = 'NO';

    $consulta = "INSERT INTO muestrapila (tons, humedadFlujo, limiteHumedad, resultadoHumedad, fechaMuestra, muestradoPor, lugarMuestreo, estadoMuestra, adjunto, idLote, idUsuario) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    try {
        $db = new db();
        $db = $db->connect();
        $insertStatement = $db->prepare($consulta);
        $insertStatement->bindParam(1, $tons);
        $insertStatement->bindParam(2, $humedadFlujo);
        $insertStatement->bindParam(3, $limiteHumedad);
        $insertStatement->bindParam(4, $humedad);
        $insertStatement->bindParam(5, $fechaMuestreo);
        $insertStatement->bindParam(6, $muestradoPor);
        $insertStatement->bindParam(7, $lugarMuestreo);
        $insertStatement->bindParam(8, $estadoMuestra);
        $insertStatement->bindParam(9, $adjunto);
        $insertStatement->bindParam(10, $idLote);
        $insertStatement->bindParam(11, $idUsuario);

        // Realizar la inserción
        $result = $insertStatement->execute();
        $db = null;

        $responseData = ["success" => $result];
        $response->getBody()->write(json_encode($responseData));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result ? 201 : 500);
    } catch (PDOException $e) {
        $error = ["success" => false, "message" => "PDOException: " . $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    } catch (Exception $e) {
        $error = ["success" => false, "message" => "Exception: " . $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    } catch (Error $e) {
        $error = ["success" => false, "message" => "Error: " . $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// OBTENER TODOS LOS DATOS DE RESULTADO DE PILA
$app->get('/getResultadoPila/{idLote}', function (Request $request, Response $response) {
    $idLote = $request->getAttribute('idLote');
    $consulta = "SELECT * FROM muestrapila WHERE idLote = ?";

    try {
        $db = new db();
        $db = $db->connect();
        $selectStatement = $db->prepare($consulta);
        $selectStatement->bindParam(1, $idLote);
        $selectStatement->execute();
        $results = $selectStatement->fetchAll(PDO::FETCH_ASSOC);
        $db = null;

        $responseData = [];

        foreach ($results as $result) {
            $data = [
                'idPila' => $result['idPila'],
                'tons' => $result['tons'],
                'humedadFlujo' => $result['humedadFlujo'],
                'limiteHumedad' => $result['limiteHumedad'],
                'resultadoHumedad' => $result['resultadoHumedad'],
                'fechaMuestra' => $result['fechaMuestra'],
                'muestradoPor' => $result['muestradoPor'],
                'lugarMuestreo' => $result['lugarMuestreo'],
                'estadoMuestra' => $result['estadoMuestra'],
                'adjunto' => $result['adjunto'],
                'idLote' => $result['idLote'],
                'idUsuario' => $result['idUsuario'],
            ];
            $responseData[] = $data;
        }

        return $response->withHeader('Content-Type', 'application/json')->write(json_encode($responseData));
    } catch (PDOException $e) {
        return $response->withStatus(500);
    } catch (Exception $e) {
        return $response->withStatus(500);
    }
});

//TRAE LOS RESULTADOS PILA SEGUN NOMINACIÓN PARA LOS INDICADORES
$app->get('/getResultadoPilaIndicadores/{idNominacion}', function (Request $request, Response $response, $args) {
    $idNominacion = $args['idNominacion'];
    
    $consulta = "SELECT l.*, m.* FROM lote AS l 
                 JOIN muestrapila AS m ON l.idLote = m.idLote  
                 WHERE l.idNominacion = ?
                 ORDER BY m.fechaMuestra DESC, CASE WHEN m.estadoMuestra = 'Aprobado' THEN 1 ELSE 2 END";

    try {
        // Instanciar y conectar a la base de datos
        $db = new db();
        $db = $db->connect();

        // Preparar y ejecutar la consulta
        $selectStatement = $db->prepare($consulta);
        $selectStatement->bindParam(1, $idNominacion, PDO::PARAM_INT);
        $selectStatement->execute();
        $results = $selectStatement->fetchAll(PDO::FETCH_ASSOC);

        // Devolver directamente los resultados
        return $response->withHeader('Content-Type', 'application/json')->write(json_encode($results));
    } catch (PDOException $e) {
        $error = array(
            "message" => $e->getMessage()
        );
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500)
            ->write(json_encode($error));
    }
});

// Obtener los resultados de pila según el id de la pila
$app->get('/getResultadoPilaByIdPila/{idPila}', function (Request $request, Response $response) {
    $idPila = $request->getAttribute('idPila');
    $consulta = "SELECT * FROM muestrapila WHERE idPila = ?";

    try {
        // Inicialización de la conexión a la base de datos
        $db = new db();
        $db = $db->connect();

        // Preparación y ejecución de la consulta
        $selectStatement = $db->prepare($consulta);
        $selectStatement->bindParam(1, $idPila);
        $selectStatement->execute();
        $result = $selectStatement->fetch(PDO::FETCH_ASSOC);
        // Preparación de la respuesta
        $responseData = [
            'idPila' => $result['idPila'],
            'tons' => $result['tons'],
            'humedadFlujo' => $result['humedadFlujo'],
            'limiteHumedad' => $result['limiteHumedad'],
            'resultadoHumedad' => $result['resultadoHumedad'],
            'fechaMuestra' => $result['fechaMuestra'],
            'muestradoPor' => $result['muestradoPor'],
            'lugarMuestreo' => $result['lugarMuestreo'],
            'estadoMuestra' => $result['estadoMuestra'],
            'adjunto' => $result['adjunto'],
            'idLote' => $result['idLote'],
            'idUsuario' => $result['idUsuario'],
        ];

        return $response->withHeader('Content-Type', 'application/json')->write(json_encode($responseData));

    } catch (PDOException $e) {
        // Manejo de excepciones de la base de datos
        $errorData = ['message' => 'Error de base de datos: ' . $e->getMessage()];
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->write(json_encode($errorData));
    } catch (Exception $e) {
        // Manejo de excepciones generales
        $errorData = ['message' => 'Error del servidor: ' . $e->getMessage()];
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->write(json_encode($errorData));
    }
});

$app->delete('/eliminarResultLote/{idPila}', function (Request $request, Response $response) {
    $idPila = $request->getAttribute('idPila');
    
    try {
        $db = new db();
        $db = $db->connect();
        
        // Paso 1: Recuperar el idLote antes de eliminar
        $consultaLote = "SELECT idLote FROM muestrapila WHERE idPila = :idPila";
        $stmt = $db->prepare($consultaLote);
        $stmt->bindParam(':idPila', $idPila);
        $stmt->execute();
        $resultadoLote = $stmt->fetch(PDO::FETCH_OBJ);
        $idLote = $resultadoLote->idLote; // Asumiendo que siempre hay un resultado. Deberías manejar el caso contrario.
        
        // Paso 2: Eliminar el registro
        $consulta = "DELETE FROM muestrapila WHERE idPila = :idPila";
        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':idPila', $idPila);
        $stmt->execute();
        
        // Paso 3: Obtener y entregar los datos después de eliminar
        $consulta2 = "SELECT * FROM muestrapila WHERE idLote = :idLote";
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

//Edita un resultado de pila
$app->post('/editResultadoLote', function (Request $request, Response $response) {
    $parsedBody = $request->getParsedBody();
    $idPila = $parsedBody['idPila'];
    $idLote = $parsedBody['idLote'];
    $tons = $parsedBody['tons'];
    $muestradoPor = $parsedBody['muestradoPor'];
    $fechaMuestra = $parsedBody['fechaMuestra'];
    $lugarMuestreo = $parsedBody['lugarMuestreo'];
    $humedadFlujo = $parsedBody['humedadFlujo'];
    $limiteHumedad = $parsedBody['limiteHumedad'];
    $resultadoHumedad = $parsedBody['humedad'];
    $idUsuario = $parsedBody['idUsuario'];
    
    $db = new db();
    $db = $db->connect();

    try {
        $db->beginTransaction();
        // Actualizar la base de datos
        if($limiteHumedad > $resultadoHumedad){
            $estadoMuestra = 'Aprobado';

            if($resultadoHumedad > 7){
                
                $estadoMuestra = 'Aprobado';

            }else {
                $estadoMuestra = 'Rechazado';
            }
            
        } else {
            $estadoMuestra = 'Rechazado';
        }
        
        $consulta = "UPDATE muestrapila SET tons = ?, humedadFlujo = ?, limiteHumedad = ?, resultadoHumedad = ?, fechaMuestra = ?, muestradoPor = ?, lugarMuestreo = ?, estadoMuestra = ? , idUsuario = ? WHERE idPila = ?";
        $ejecutar = $db->prepare($consulta);
        $ejecutar->execute([$tons, $humedadFlujo, $limiteHumedad, $resultadoHumedad, $fechaMuestra, $muestradoPor, $lugarMuestreo, $estadoMuestra, $idUsuario, $idPila]);

        $db->commit();

        // Consulta para obtener todos los resultados asociados al idLote
        $consulta2 = "SELECT * FROM muestrapila WHERE idPila = ?";
        $ejecutar2 = $db->prepare($consulta2);
        $ejecutar2->execute([$idPila]);
        $resultadosLote = $ejecutar2->fetchAll(PDO::FETCH_OBJ);

        $db = null;

        // Enviar respuesta con los resultados del lote
        $response->getBody()->write(json_encode($resultadosLote));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    } catch (PDOException $e) {
        $db->rollBack();
        $error = array(
            "message" => $e->getMessage()
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});

$app->post('/addAdjuntosPila', function (Request $request, Response $response) {
    // Establecer la conexión a la base de datos
    $db = new db();
    $db = $db->connect();

    $parsedBody = $request->getParsedBody();
    $idPila = $parsedBody['idPila'];
    $idLote = $parsedBody['idLote'];
    $idUsuario = $parsedBody['idUsuario'];
    $uploadedFiles = $request->getUploadedFiles();
    $infmuestra = $uploadedFiles['infmuestra'] ?? null;
    $infhumedad = $uploadedFiles['infhumedad'] ?? null;
    $informeHechos = $uploadedFiles['informeHechos'] ?? null; // Agregado

    // Directorio para subir archivos
    $uploadsDirectory = './src/File/uploads/';
    if (!is_dir($uploadsDirectory)) {
        mkdir($uploadsDirectory, 0755, true);
    }

    // Inicializar nombres de archivo
    $filename = $filenameDos = $filenameTres = null; // Modificado para incluir $filenameTres

    try {
        // Procesar archivo adjunto 1 (infmuestra)
        if ($infmuestra && $infmuestra->getError() === UPLOAD_ERR_OK) {
            $filename = getUniqueFileName($uploadsDirectory, $infmuestra->getClientFilename());
            $infmuestra->moveTo($uploadsDirectory . $filename);
            $insertStatement = $db->prepare("INSERT INTO adjunto (nombreArchivo, idPila, idUsuario) VALUES (?, ?, ?)");
            $insertStatement->execute([$filename, $idPila, $idUsuario]);
        }

        // Procesar archivo adjunto 2 (infhumedad)
        if ($infhumedad && $infhumedad->getError() === UPLOAD_ERR_OK) {
            $filenameDos = getUniqueFileName($uploadsDirectory, $infhumedad->getClientFilename());
            $infhumedad->moveTo($uploadsDirectory . $filenameDos);
            $insertStatement = $db->prepare("INSERT INTO adjunto (nombreArchivo, idPila, idUsuario) VALUES (?, ?, ?)");
            $insertStatement->execute([$filenameDos, $idPila, $idUsuario]);
        }

        // Procesar archivo adjunto 3 (informeHechos)
        if ($informeHechos && $informeHechos->getError() === UPLOAD_ERR_OK) {
            $filenameTres = getUniqueFileName($uploadsDirectory, $informeHechos->getClientFilename());
            $informeHechos->moveTo($uploadsDirectory . $filenameTres);
            $insertStatement = $db->prepare("INSERT INTO adjunto (nombreArchivo, idPila, idUsuario) VALUES (?, ?, ?)");
            $insertStatement->execute([$filenameTres, $idPila, $idUsuario]);
        }

        // Actualizar el campo adjuntos en la tabla muestrapila
        $updateStatement = $db->prepare("UPDATE muestrapila SET adjunto = 'SI' WHERE idPila = ?");
        $updateStatement->execute([$idPila]);

        // Realizar consulta JOIN
        $joinStatement = $db->prepare("SELECT * FROM muestrapila WHERE idLote = ?");
        $joinStatement->execute([$idLote]);
        $result = $joinStatement->fetchAll(PDO::FETCH_OBJ);

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {
        $error = ["success" => false, "message" => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    } finally {
        $db = null; // Cerrar la conexión a la base de datos
    }
});



// Función para generar un nombre de archivo único
function getUniqueFileName($directory, $filename) {
    $now = new DateTime();
    $uniqueId = uniqid();
    return $now->format('YmdHis') . '_' . $uniqueId . '_' . $filename;
}


$app->get('/getAdjuntosPila/{idPila}', function (Request $request, Response $response, array $args) {
    $db = new db();
    $db = $db->connect();

    $idPila = $args['idPila'];

    try {
        $stmt = $db->prepare("SELECT nombreArchivo FROM adjunto WHERE idPila = :idPila");
        $stmt->bindParam(':idPila', $idPila, PDO::PARAM_INT);
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
        $db = null; // Cerrar la conexión a la base de datos
    }
});


$app->get('/removeAdjuntosPila/{idPila}/{idLote}', function (Request $request, Response $response) {
    $idPila = $request->getAttribute('idPila');
    $idLote = $request->getAttribute('idLote');
    $directorio = 'C:\xampp\htdocs\api_grupo_mexico\/src\File\uploads/';

    try {
        // Instanciar y conectar a la base de datos
        $db = new db();
        $db = $db->connect();

        // Obtener los nombres de los archivos adjuntos
        $consultaArchivos = "SELECT nombreArchivo FROM adjunto WHERE idPila = :idPila";
        $stmt = $db->prepare($consultaArchivos);
        $stmt->bindParam(':idPila', $idPila);
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
        $consultaEliminar = "DELETE FROM adjunto WHERE idPila = :idPila";
        $stmt = $db->prepare($consultaEliminar);
        $stmt->bindParam(':idPila', $idPila);
        $stmt->execute();

        //Consulta si existen datos para el idLote
        $consultaVerificarVistaCliente = "SELECT COUNT(*) AS count FROM vistacliente WHERE idLote = :idLote AND proceso = 'Resultado toma de pila'";
        $stmt = $db->prepare($consultaVerificarVistaCliente);
        $stmt->bindParam(':idLote', $idLote);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Si hay registros en vistacliente para el idLote proporcionado, eliminarlos
        if ($count > 0) {
            $consultaEliminarSolicitud = "DELETE FROM vistacliente WHERE idLote = :idLote AND proceso = 'Resultado toma de pila'";
            $stmt = $db->prepare($consultaEliminarSolicitud);
            $stmt->bindParam(':idLote', $idLote);
            $stmt->execute();
        }

        // Actualizar la tabla muestrapila
        $consultaActualizar = "UPDATE muestrapila SET adjunto = 'NO' WHERE idPila = :idPila";
        $stmt = $db->prepare($consultaActualizar);
        $stmt->bindParam(':idPila', $idPila);
        $stmt->execute();

        // Obtener la tabla actualizada muestrapila
        $consultaMuestraPila = "SELECT * FROM muestrapila WHERE idLote = :idLote";
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



//TRAE LOS RESULTADOS PILA SEGUN IDLOTE
$app->get('/getResultadoPilaIdLote/{idLote}', function (Request $request, Response $response, $args) {
    $idLote = $request->getAttribute('idLote');
    
    $consulta = "SELECT DISTINCT l.*, m.*, v.* FROM lote AS l 
                 JOIN muestrapila AS m ON l.idLote = m.idLote 
                 INNER JOIN vistacliente AS v ON l.idLote = v.idLote 
                 WHERE v.idLote = $idLote AND v.estado = 'Aprobado' AND v.proceso = 'Resultado toma de pila'
                 ORDER BY m.fechaMuestra DESC";

    try {
        // Log de consulta
        error_log("Consulta SQL: $consulta");

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
            ->withStatus(500);
    }
});


$app->post('/addSolicitudVistaClientePila', function (Request $request, Response $response) {
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

$app->get('/getAutorizacionVistaClientePila/{idLote}', function (Request $request, Response $response) {
    $idLote = $request->getAttribute('idLote');
    try {
        // Instanciar y conectar a la base de datos
        $db = new db();
        $db = $db->connect();
        $consulta = "SELECT * FROM vistacliente WHERE proceso = 'Resultado toma de pila' AND idLote = '$idLote'";

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

