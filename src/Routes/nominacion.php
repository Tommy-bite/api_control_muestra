<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//Ingresa una nominación
$app->post('/addNominacion', function (Request $request, Response $response) {
    
    $numeroNominacion = $request->getParam('nmrNominacion');
    $fechaAsignacion = $request->getParam('fechaAsignacion');
    $fechaIngreso = $request->getParam('fechaIngreso');
    $idUsuario = $request->getParam('idUsuario');

    // Verificar si el número de nominación ya existe
    $consultaExistencia = "SELECT COUNT(*) as count FROM nominacion WHERE numeroNominacion = '$numeroNominacion'";
    
    try {
        $db = new db();
        $db = $db->connect();
        $stmtExistencia = $db->query($consultaExistencia);
        $count = $stmtExistencia->fetch(PDO::FETCH_ASSOC)['count'];

        if ($count > 0) {
            // El número de nominación ya existe, manejar según tus necesidades
            $response->getBody()->write(json_encode(["message" => "El número de nominación ya existe", "exists" => false]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);  // Código de estado 200 para indicar una respuesta exitosa
        }

        // Si el número de nominación no existe, proceder con la inserción
        $consultaInsercion = "INSERT INTO nominacion (numeroNominacion, fechaAsignacion, fechaIngreso, estadoNominacion, idUsuario) 
                              VALUES ('$numeroNominacion', '$fechaAsignacion', '$fechaIngreso', 'Pendiente', '$idUsuario')";
        
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

//OBTENER TODAS LAS NOMINACIONES
$app->get('/getNominaciones', function (Request $request, Response $response) {
    // Calcular la fecha de 4 meses atrás desde el día actual
    $cuatroMesesAtras = date('Y-m-d', strtotime('-4 months'));

    $consulta = "SELECT * FROM nominacion WHERE fechaAsignacion >= :cuatroMesesAtras ORDER BY fechaAsignacion DESC";
    
    try {
        // Instanciar la base de datos
        $db = new db();
        // Conexión
        $db = $db->connect();        

        // Preparar la consulta SQL
        $ejecutar = $db->prepare($consulta);
        
        // Vincular el parámetro
        $ejecutar->bindParam(':cuatroMesesAtras', $cuatroMesesAtras);

        // Ejecutar la consulta SQL
        $ejecutar->execute();

        // Obtener todas las nominaciones como un array
        $result = $ejecutar->fetchAll(PDO::FETCH_ASSOC);
        $db = null;

        // Si no hay resultados, devolver un array vacío
        if (empty($result)) {
            $result = [];
        }
        // Convertir el array a formato JSON y enviarlo como respuesta
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


//OBTENER NOMINACIÓN POR NUMERO DE NOMINA
$app->get('/getNominacionByNumero/{numeroNominacion}', function (Request $request, Response $response) {

    $numeroNominacion = $request->getAttribute('numeroNominacion');

    $consulta = "SELECT * FROM nominacion WHERE numeroNominacion = '$numeroNominacion'";

    try {
        // Instanciar la base de datos
        $db = new db();
        // Conexión
        $db = $db->connect();
        $ejecutar = $db->query($consulta);
        $result = $ejecutar->fetchObject();
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


//Asignar lote
$app->post('/addLote', function (Request $request, Response $response) {
    $cliente = $request->getParam('cliente');
    $orden = $request->getParam('orden');
    $barco = $request->getParam('barco');
    $armador = $request->getParam('armador');
    $producto = $request->getParam('producto');
    $tonelaje = $request->getParam('tonelaje');
    $destino = $request->getParam('destino');
    $laycan = $request->getParam('laycan');
    $idNominacion = $request->getParam('idNominacion');
    $idUsuario = $request->getParam('idUsuario');

    try {
        // Instanciar la base de datos
        $db = new db();
        // Conexión
        $db = $db->connect();

        // Verificar si el número de orden ya existe
        $consultaExistencia = "SELECT COUNT(*) FROM lote WHERE orden = ?";
        $stmt = $db->prepare($consultaExistencia);
        $stmt->execute([$orden]);
        $existe = $stmt->fetchColumn();

        if ($existe > 0) {
            // La orden ya existe, se devuelve un mensaje indicando esto
            $response->getBody()->write(json_encode(["success" => false, "message" => "El lote ya se encuentra asigando"]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200); // Estado OK, pero la orden ya existe
        }

        // Insertar el nuevo lote si la orden no existe
        $consulta = "INSERT INTO lote (cliente, orden, barco, armador, producto, tonelaje, destino, laycan, adjuntoNominacion, estadoLote, idNominacion, idUsuario) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'NO', 'Pendiente', ?, ?)";
        $ejecutar = $db->prepare($consulta);
        $result = $ejecutar->execute([$cliente, $orden, $barco, $armador, $producto, $tonelaje, $destino, $laycan, $idNominacion, $idUsuario]);

        // Devolver una respuesta exitosa
        $response->getBody()->write(json_encode(["success" => true, "message" => "Lote agregado con éxito"]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    } catch (PDOException $e) {
        // Manejar cualquier error en la consulta
        $error = array(
            "message" => $e->getMessage()
        );
        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500); // Error interno del servidor
    }
});


$app->get('/getLotesByIdNominacionUno/{idNominacion}', function (Request $request, Response $response) {
    $idNominacion = $request->getAttribute('idNominacion');

    try {
        // Instanciar la base de datos
        $db = new db();
        // Conexión
        $db = $db->connect();
        // Consulta para obtener los datos de la tabla lote
        $consultaLote = "SELECT * FROM lote WHERE idNominacion = :idNominacion";
        $stmtLote = $db->prepare($consultaLote);
        $stmtLote->bindParam(':idNominacion', $idNominacion);
        $stmtLote->execute();
        $lotes = $stmtLote->fetchAll(PDO::FETCH_ASSOC);

        // Consulta para obtener los datos de la tabla nominacion
        $consultaNominacion = "SELECT * FROM nominacion WHERE idNominacion = :idNominacion";
        $stmtNominacion = $db->prepare($consultaNominacion);
        $stmtNominacion->bindParam(':idNominacion', $idNominacion);
        $stmtNominacion->execute();
        $nominacion = $stmtNominacion->fetchAll(PDO::FETCH_ASSOC);

        // Combinar los resultados
        $result = [
            'lotes' => $lotes,
            'nominacion' => $nominacion
        ];

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

$app->get('/getLotesByIdNominacion/{idNominacion}', function (Request $request, Response $response) {

    $idNominacion = $request->getAttribute('idNominacion');

    $consulta = "SELECT * FROM lote AS l JOIN nominacion AS n ON l.idNominacion = n.idNominacion WHERE l.idNominacion = '$idNominacion'";

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

//OBTENER TODAS LAS NOMINACIONES
$app->get('/getLotes', function (Request $request, Response $response) {
    // Obtener el primer día de hace cuatro meses
    $primerDiaCuatroMesesAtras = date('Y-m-01', strtotime("-4 months"));
    // Obtener la fecha actual
    $fechaActual = date('Y-m-d');

    $consulta = "SELECT l.*, n.* FROM lote AS l 
                 JOIN nominacion AS n ON l.idNominacion = n.idNominacion 
                 WHERE (n.fechaAsignacion BETWEEN :primerDiaCuatroMesesAtras AND :fechaActual) 
                 ORDER BY n.fechaAsignacion DESC";
    
    try {
        // Instanciar la base de datos
        $db = new db();
        // Conexión
        $db = $db->connect();        
        $ejecutar = $db->prepare($consulta);

        // Vincular parámetros
        $ejecutar->bindParam(':primerDiaCuatroMesesAtras', $primerDiaCuatroMesesAtras);
        $ejecutar->bindParam(':fechaActual', $fechaActual);

        $ejecutar->execute();
        $result = $ejecutar->fetchAll(PDO::FETCH_ASSOC);
        $db = null;
        if (empty($result)) {
            $result = [];
        }
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


$app->put('/editLote', function (Request $request, Response $response) {

    $cliente = $request->getParam('cliente');
    $orden = $request->getParam('orden');
    $barco = $request->getParam('barco');
    $armador = $request->getParam('armador');
    $producto = $request->getParam('producto');
    $tonelaje = $request->getParam('tonelaje');
    $destino = $request->getParam('destino');
    $laycan = $request->getParam('laycan');
    $idLote = $request->getParam('idLote');
    $idUsuario = $request->getParam('idUsuario');
    $idNominacion = $request->getParam('idNominacion');
    
        $consulta = "UPDATE lote SET  cliente = '$cliente', orden = '$orden', barco = '$barco', armador ='$armador', producto ='$producto', tonelaje ='$tonelaje' , destino ='$destino', laycan ='$laycan', idLote ='$idLote', idUsuario ='$idUsuario' WHERE idLote = '$idLote'";
   
    try {
        // Instanciar la base de datos
        $db = new db();
        // Conexión
        $db = $db->connect();
        $ejecutar = $db->prepare($consulta);
        $result =  $ejecutar->execute();
        $consulta2 = "SELECT * FROM lote  WHERE idNominacion = '$idNominacion'";
        $ejecutar2 = $db->query($consulta2);
        $result2 = $ejecutar2->fetchAll(PDO::FETCH_OBJ);
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


$app->put('/editNominacion', function (Request $request, Response $response) {

    $idNominacion = $request->getParam('idNominacion');
    $nmrNominacion = $request->getParam('nmrNominacion');
    $fechaAsignacion = $request->getParam('fechaAsignacion');
    $idUsuario = $request->getParam('idUsuario');

        $consulta = "UPDATE nominacion SET  numeroNominacion = '$nmrNominacion', fechaAsignacion = '$fechaAsignacion', idUsuario ='$idUsuario' WHERE idNominacion = '$idNominacion'";
   
    try {
        // Instanciar la base de datos
        $db = new db();
        // Conexión
        $db = $db->connect();
        $ejecutar = $db->prepare($consulta);
        $result =  $ejecutar->execute();
        $consulta2 = "SELECT * FROM nominacion  WHERE idNominacion = '$idNominacion'";
        $ejecutar2 = $db->query($consulta2);
        $result2 = $ejecutar2->fetchAll(PDO::FETCH_OBJ);
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

$app->post('/addAdjuntosNominacion', function (Request $request, Response $response) {
    // Establecer la conexión a la base de datos
    $db = new db();
    $db = $db->connect();
    $parsedBody = $request->getParsedBody();   
    $idLote = $parsedBody['idLote'];
    $idUsuario = $parsedBody['idUsuario'];
    $uploadedFiles = $request->getUploadedFiles();
    $informefinal = $uploadedFiles['informefinal'] ?? null;
    // Directorio para subir archivos
    $uploadsDirectory = './src/File/uploads/';
    if (!is_dir($uploadsDirectory)) {
        mkdir($uploadsDirectory, 0755, true);
    }
    // Inicializar nombres de archivo
    $filename = null;

    try {

        // Procesar archivo adjunto 1
        if ($informefinal && $informefinal->getError() === UPLOAD_ERR_OK) {
            $filename = getUniqueFileNameDos($uploadsDirectory, $informefinal->getClientFilename());
            $informefinal->moveTo($uploadsDirectory . $filename);
            $insertStatement = $db->prepare("INSERT INTO adjuntonominacion (nombreArchivo, idLote, idUsuario) VALUES (?, ?, ?)");
            $insertStatement->execute([$filename, $idLote, $idUsuario]);
        }
        // Actualizar el campo adjuntos en la tabla muestrapila
        $updateStatement = $db->prepare("UPDATE lote SET estadoLote = 'Cerrado', adjuntoNominacion = 'SI', fechaTerminoLote = CURRENT_DATE(),  idUsuario = '$idUsuario' WHERE idLote = ? ");
        $updateStatement->execute([$idLote]);

        $checkLotesStatement = $db->prepare("SELECT COUNT(*) AS count FROM lote WHERE idNominacion = (SELECT idNominacion FROM lote WHERE idLote = ?) AND estadoLote != 'Cerrado'");
        $checkLotesStatement->execute([$idLote]);
        $count = $checkLotesStatement->fetch(PDO::FETCH_ASSOC)['count'];

        if ($count == 0) {
            // Todos los lotes están cerrados, actualiza el estado de la nominación
            $updateNomStatement = $db->prepare("UPDATE nominacion SET estadoNominacion = 'Cerrado', fechaTermino = CURDATE() WHERE idNominacion = (SELECT idNominacion FROM lote WHERE idLote = ?)");
            $updateNomStatement->execute([$idLote]);
        }
        // Realizar consulta
        $primerDiaCuatroMesesAtras = date('Y-m-01', strtotime("-4 months"));
        $fechaActual = date('Y-m-d');
        $consultaFinal = "SELECT l.*, n.* FROM lote AS l 
                        JOIN nominacion AS n ON l.idNominacion = n.idNominacion 
                        WHERE (n.fechaAsignacion BETWEEN :primerDiaCuatroMesesAtras AND :fechaActual) 
                        ORDER BY n.fechaAsignacion DESC";
                        // Vincular parámetros

        $joinStatement = $db->prepare($consultaFinal);
        $joinStatement->bindParam(':primerDiaCuatroMesesAtras', $primerDiaCuatroMesesAtras);
        $joinStatement->bindParam(':fechaActual', $fechaActual);
        $joinStatement->execute();
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
function getUniqueFileNameDos($directory, $filename) {
    $now = new DateTime();
    $uniqueId = uniqid();
    return $now->format('YmdHis') . '_' . $uniqueId . '_' . $filename;
}


$app->get('/getAdjuntosNominacion/{idLote}', function (Request $request, Response $response, array $args) {
    // Establecer la conexión a la base de datos
    $db = new db();
    $db = $db->connect();
    $idLote = $args['idLote']; 
    try {
        // Consulta para obtener el nombre del archivo adjunto.
        $stmt = $db->prepare("SELECT nombreArchivo FROM adjuntonominacion WHERE idLote = :idLote");
        $stmt->bindParam(':idLote', $idLote, PDO::PARAM_INT);
        $stmt->execute();

        $adjunto = $stmt->fetch(PDO::FETCH_OBJ);

        if ($adjunto && file_exists('C:\xampp\htdocs\api_grupo_mexico\/src/File/uploads/' . $adjunto->nombreArchivo)) {
            $filePath = 'C:\xampp\htdocs\api_grupo_mexico\/src/File/uploads/' . $adjunto->nombreArchivo;
            $content = file_get_contents($filePath);
            $pdfData = base64_encode($content);
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

$app->get('/removeAdjuntosNominacion/{idLote}', function (Request $request, Response $response) {
    $idLote = $request->getAttribute('idLote');
    $directorio = 'C:\xampp\htdocs\api_grupo_mexico\/src\File\uploads/';
    $fechaActual = date('Y-m-01');

    try {
        // Instanciar y conectar a la base de datos
        $db = new db();
        $db = $db->connect();
        // Obtener los nombres de los archivos adjuntos
        $consultaArchivos = "SELECT nombreArchivo FROM adjuntonominacion WHERE idLote = :idLote";
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
        $consultaEliminar = "DELETE FROM adjuntonominacion WHERE idLote = :idLote";
        $stmt = $db->prepare($consultaEliminar);
        $stmt->bindParam(':idLote', $idLote);
        $stmt->execute();

        // Actualizar la tabla muestrapila
        $consultaActualizar = "UPDATE lote SET adjuntoNominacion = 'NO', estadoLote = 'Pendiente' WHERE idLote = :idLote";
        $stmt = $db->prepare($consultaActualizar);
        $stmt->bindParam(':idLote', $idLote);
        $stmt->execute();

        $primerDiaCuatroMesesAtras = date('Y-m-01', strtotime("-4 months"));
         $fechaActual = date('Y-m-d');
        $consultaMuestraPila = "SELECT l.*, n.* FROM lote AS l 
                                JOIN nominacion AS n ON l.idNominacion = n.idNominacion 
                                WHERE (n.fechaAsignacion BETWEEN :primerDiaCuatroMesesAtras AND :fechaActual) 
                                ORDER BY n.fechaAsignacion DESC";
        $stmt = $db->prepare($consultaMuestraPila);
        $stmt->bindParam(':primerDiaCuatroMesesAtras', $primerDiaCuatroMesesAtras);
        $stmt->bindParam(':fechaActual', $fechaActual);
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


$app->get('/getNominacionesCerradas', function (Request $request, Response $response) {
    // Calcular la fecha de 4 meses atrás desde el día actual
    $cuatroMesesAtras = date('Y-m-d', strtotime('-4 months'));

    $consulta = "SELECT n.*, l.*, m.*
    FROM nominacion AS n
    JOIN lote AS l ON n.idNominacion = l.idNominacion
    JOIN (
        SELECT idLote, MAX(fechaMuestra) AS fechaMax
        FROM muestrapila
        WHERE estadoMuestra = 'Aprobado'
        GROUP BY idLote
    ) AS m2 ON l.idLote = m2.idLote
    JOIN muestrapila AS m ON m.idLote = m2.idLote AND m.fechaMuestra = m2.fechaMax AND m.estadoMuestra = 'Aprobado' AND adjunto = 'SI'
    WHERE n.estadoNominacion = 'Cerrado' AND n.fechaAsignacion >= :cuatroMesesAtras
    GROUP BY l.idLote
    ORDER BY n.fechaAsignacion DESC;";

    try {
        // Instanciar la base de datos
        $db = new db();
        // Conexión
        $db = $db->connect();
        $ejecutar = $db->prepare($consulta);
        
        // Vincular el parámetro
        $ejecutar->bindParam(':cuatroMesesAtras', $cuatroMesesAtras);

        $ejecutar->execute();
        // Obtener todas las nominaciones como un array
        $result = $ejecutar->fetchAll(PDO::FETCH_ASSOC);
        $db = null;

        // Si no hay resultados, devolver un array vacío
        if (empty($result)) {
            $result = [];
        }
        // Convertir el array a formato JSON y enviarlo como respuesta
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



//TRAE LOS LOTES DE LAS NOMINACIONES CERRADAS QUE ESTAN DENTRO DEL RANGO DE LA FECHA
$app->get('/getNominacionesByDate', function (Request $request, Response $response) {

    $inicio = $request->getQueryParams()['inicio'];
    $fin = $request->getQueryParams()['fin'];

    $consulta = "SELECT * FROM lote AS l JOIN nominacion AS n ON l.idNominacion = n.idNominacion WHERE n.fechaAsignacion BETWEEN :inicio AND :fin
    GROUP BY l.idLote
    ORDER BY n.fechaAsignacion DESC";

    try {
        // Instanciar la base de datos
        $db = new db();
        // Conexión
        $db = $db->connect();
        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':inicio', $inicio);
        $stmt->bindParam(':fin', $fin);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $db = null;
        if (empty($result)) {
            $result = [];
        }
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

$app->get('/getNominacionesCerradasByIdLote/{idLote}', function (Request $request, Response $response) {
    $idLote = $request->getAttribute('idLote');

    $consulta = "SELECT * FROM nominacion AS n 
                JOIN lote AS l ON n.idNominacion = l.idNominacion 
                JOIN vistacliente AS v ON l.idLote = v.idLote
                WHERE n.estadoNominacion = 'Cerrado' AND l.idLote = '$idLote' AND v.estado = 'Aprobado' AND v.proceso = 'Informe Final'
                ORDER BY fechaAsignacion DESC";   
    try {
        // Instanciar la base de datos
        $db = new db();
        // Conexión
        $db = $db->connect();        
        $ejecutar = $db->query($consulta);
        $result = $ejecutar->fetchAll(PDO::FETCH_ASSOC);
        $db = null;
        if (empty($result)) {
            $result = [];
        }
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


$app->get('/getNominacionesCerradasByDate', function (Request $request, Response $response) {
    $inicio = $request->getQueryParams()['inicio'];
    $fin = $request->getQueryParams()['fin'];

    $consulta = "SELECT n.*, l.*, m.*
    FROM nominacion AS n
    JOIN lote AS l ON n.idNominacion = l.idNominacion
    JOIN (
        SELECT idLote, MAX(fechaMuestra) AS fechaMax
        FROM muestrapila
        WHERE estadoMuestra = 'Aprobado'
        GROUP BY idLote
    ) AS m2 ON l.idLote = m2.idLote
    JOIN muestrapila AS m ON m.idLote = m2.idLote AND m.fechaMuestra = m2.fechaMax AND m.estadoMuestra = 'Aprobado'
    WHERE n.estadoNominacion = 'Cerrado' AND n.fechaAsignacion BETWEEN :inicio AND :fin
    GROUP BY l.idLote
    ORDER BY n.fechaAsignacion DESC;";

    try {
        // Instanciar la base de datos
        $db = new db();
        // Conexión
        $db = $db->connect();
        $ejecutar = $db->prepare($consulta);
        
        // Vincular los parámetros de fecha
        $ejecutar->bindParam(':inicio', $inicio);
        $ejecutar->bindParam(':fin', $fin);

        $ejecutar->execute();
        // Obtener todas las nominaciones como un array
        $result = $ejecutar->fetchAll(PDO::FETCH_ASSOC);
        $db = null;

        // Si no hay resultados, devolver un array vacío
        if (empty($result)) {
            $result = [];
        }
        // Convertir el array a formato JSON y enviarlo como respuesta
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


$app->get('/dejarAdjuntarInformeFinal/{idLote}', function (Request $request, Response $response) {
    $idLote = $request->getAttribute('idLote'); 

    try {
       
        $db = new db();
        $db = $db->connect();

        $consulta1 = "SELECT adjunto FROM muestrapila WHERE idLote = :idLote";
        $stmt = $db->prepare($consulta1);
        $stmt->bindParam(':idLote', $idLote);
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);

        $adjuntoEncontrado = false;
        foreach ($resultados as $resultado) {
            if ($resultado->adjunto == 'SI') {
                $adjuntoEncontrado = true;
                break;
            }
        }

        $consulta2 = "SELECT * FROM adjuntonave WHERE idLote = :idLote";
        $stmt = $db->prepare($consulta2);
        $stmt->bindParam(':idLote', $idLote);
        $stmt->execute();
        $result2 = $stmt->fetch(PDO::FETCH_OBJ);

        $db = null;

        if ($adjuntoEncontrado && $result2) {
            $response->getBody()->write(json_encode(true));
        } else {
            $response->getBody()->write(json_encode(false));
        }

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

$app->get('/detalleLoteById/{idLote}', function (Request $request, Response $response) {

    $idLote = $request->getAttribute('idLote');
    $consulta = "SELECT * FROM lote WHERE idLote = '$idLote'";

    try {
        // Instanciar la base de datos
        $db = new db();
        // Conexión
        $db = $db->connect();
        $ejecutar = $db->query($consulta);
        $result = $ejecutar->fetch(PDO::FETCH_OBJ);
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


$app->post('/addSolicitudVistaClienteNomi', function (Request $request, Response $response) {
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


$app->delete('/deleteSolicitudVistaClienteNomi/{idLote}', function (Request $request, Response $response) {
    $idLote = $request->getAttribute('idLote');
   

    // Consulta SQL para insertar en la tabla notificacion
    $sql = "DELETE FROM vistacliente WHERE idLote = :idLote AND proceso = 'Informe Final'";

    try {
        // Obtener la conexión a la base de datos
        $db = new db();
        $conn = $db->connect();

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':idLote', $idLote);

        $stmt->execute();

        $response->getBody()->write(json_encode(['message' => 'Autorización Eliminada con éxito']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {
        $error = array("message" => $e->getMessage());
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->get('/getNominacionesCliente', function (Request $request, Response $response) {
   // Calcular la fecha de 4 meses atrás desde el día actual
   $cuatroMesesAtras = date('Y-m-d', strtotime('-4 months'));

   $consulta = "SELECT * FROM nominacion ORDER BY fechaAsignacion DESC"; //WHERE fechaAsignacion >= :cuatroMesesAtras 
   
   try {
       // Instanciar la base de datos
       $db = new db();
       // Conexión
       $db = $db->connect();        

       // Preparar la consulta SQL
       $ejecutar = $db->prepare($consulta);
       
       // Vincular el parámetro
       //$ejecutar->bindParam(':cuatroMesesAtras', $cuatroMesesAtras);

       // Ejecutar la consulta SQL
       $ejecutar->execute();

       // Obtener todas las nominaciones como un array
       $result = $ejecutar->fetchAll(PDO::FETCH_ASSOC);
       $db = null;

       // Si no hay resultados, devolver un array vacío
       if (empty($result)) {
           $result = [];
       }
       // Convertir el array a formato JSON y enviarlo como respuesta
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




