<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//Agregar camiones
$app->post('/addRegistroCamion', function (Request $request, Response $response) {
    $parsedBody = $request->getParsedBody();
    $idLote = $parsedBody['idLote'];
    $fecha = $parsedBody['fecha'];
    $hora = $parsedBody['hora'];
    $placa = $parsedBody['placa'];
    $bruto = $parsedBody['bruto'];
    $tara = $parsedBody['tara']; 
    $neto = $parsedBody['neto']; 
    $tiket = $parsedBody['tiket']; 
    $idUsuario = $parsedBody['idUsuario']; 

    try {
        $db = new db();
        $db = $db->connect();
        $consulta = "INSERT INTO registrarcamion (fecha, hora, placa, bruto, tara, neto, tiket, idLote, idUsuario) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $insertStatement = $db->prepare($consulta);
        $insertStatement->bindParam(1, $fecha);
        $insertStatement->bindParam(2, $hora);
        $insertStatement->bindParam(3, $placa);
        $insertStatement->bindParam(4, $bruto);
        $insertStatement->bindParam(5, $tara);
        $insertStatement->bindParam(6, $neto);
        $insertStatement->bindParam(7, $tiket);
        $insertStatement->bindParam(8, $idLote);
        $insertStatement->bindParam(9, $idUsuario);

        // Realizar la inserción
        $result = $insertStatement->execute();
        $db = null;
        if ($result) {
            // Enviar respuesta de éxito con código de estado 201 (Created)
            $responseData = ["success" => true];
        } else {
            // Enviar respuesta de error con código de estado 500 (Internal Server Error)
            $responseData = ["success" => false, "message" => "Error al realizar la operación"];
        }
        $response->getBody()->write(json_encode($responseData));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($result ? 201 : 500);
    } catch (PDOException $e) {
        // Enviar respuesta de error con código de estado 500 y mensaje detallado
        $error = ["success" => false, "message" => $e->getMessage()];
        $response->getBody()->write(json_encode($error));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});


$app->get('/getListarCamionesByIdLote/{idLote}', function (Request $request, Response $response) {

    $idLote = $request->getAttribute('idLote');
    $consulta = "SELECT * FROM registrarcamion AS r JOIN lote AS l ON r.idLote = l.idLote WHERE r.idLote = '$idLote'";

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

$app->get('/getListarCamionesByIdLoteCliente/{idLote}', function (Request $request, Response $response) {

    $idLote = $request->getAttribute('idLote');
    $consulta = "SELECT DISTINCT * FROM registrarcamion AS r 
            JOIN lote AS l ON r.idLote = l.idLote 
            JOIN vistacliente AS v ON l.idLote = v.idLote
            WHERE r.idLote = '$idLote' AND v.estado = 'Aprobado' AND v.proceso = 'Registro de camión'
            GROUP BY r.idRegistro ";

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


//DEVUELVE LOS CAMIONES AGRUPADOS POR LOTE PARA LA VISTA CLIENTE
$app->get('/getListarCamionesByIdNominacion/{IdNominacion}', function (Request $request, Response $response, $args) {
    $idNominacion = $args['IdNominacion'];

    $consulta = "SELECT r.*, l.idLote FROM registrarcamion AS r 
                 JOIN lote AS l ON r.idLote = l.idLote 
                 WHERE l.idNominacion = :idNominacion
                 ORDER BY l.idLote";

    try {
        $db = new db();
        $db = $db->connect();
        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':idNominacion', $idNominacion, PDO::PARAM_INT);
        $stmt->execute();
        $camiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $camionesAgrupados = [];
        foreach ($camiones as $camion) {
            $camionesAgrupados[$camion['idLote']][] = $camion;
        }

        $response->getBody()->write(json_encode($camionesAgrupados));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {
        $error = array("message" => $e->getMessage());
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500)->write(json_encode($error));
    }
});


$app->get('/getCamionByIdRegistro/{idRegistro}', function (Request $request, Response $response) {

    $idRegistro = $request->getAttribute('idRegistro');

    $consulta = "SELECT * FROM registrarcamion WHERE idRegistro = '$idRegistro'";

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


$app->post('/editCamionByIdRegistro', function (Request $request, Response $response) {
    $parsedBody = $request->getParsedBody();
    $idRegistro = $parsedBody['idRegistro'];
    $fecha = $parsedBody['fecha'];
    $hora = $parsedBody['hora'];
    $placa = $parsedBody['placa'];
    $bruto = $parsedBody['bruto'];
    $tara = $parsedBody['tara']; 
    $neto = $parsedBody['neto']; 
    $tiket = $parsedBody['tiket']; 
    $idUsuario = $parsedBody['idUsuario']; 

    try {
        $db = new db();
        $db = $db->connect();

        $consulta = "UPDATE registrarcamion SET fecha = ?, hora = ?, placa = ?, bruto = ?, tara = ?, neto = ?, tiket = ?, idUsuario = ? 
                     WHERE idRegistro = ?";

        $updateStatement = $db->prepare($consulta);
        $updateStatement->bindParam(1, $fecha);
        $updateStatement->bindParam(2, $hora);
        $updateStatement->bindParam(3, $placa);
        $updateStatement->bindParam(4, $bruto);
        $updateStatement->bindParam(5, $tara);
        $updateStatement->bindParam(6, $neto);
        $updateStatement->bindParam(7, $tiket);
        $updateStatement->bindParam(8, $idUsuario);
        $updateStatement->bindParam(9, $idRegistro);

        // Realizar la actualización
        $result = $updateStatement->execute();

        // Obtener el idLote del camión actualizado
        $selectStatement = $db->prepare("SELECT idLote FROM registrarcamion WHERE idRegistro = ?");
        $selectStatement->bindParam(1, $idRegistro);
        $selectStatement->execute();
        $row = $selectStatement->fetch(PDO::FETCH_ASSOC);
        $idLote = $row['idLote'];
        $result = $updateStatement->execute();

        if ($result) {
            $selectStatement = $db->prepare("SELECT * FROM registrarcamion WHERE idRegistro = ?");
            $selectStatement->bindParam(1, $idRegistro);
            $selectStatement->execute();
            $updatedData = $selectStatement->fetch(PDO::FETCH_OBJ);

            $responseData = ["success" => true, "data" => [$updatedData]];
        } else {
            $responseData = ["success" => false, "message" => "Error al editar el registro del camión"];
        }
        $db = null;
        $response->getBody()->write(json_encode($responseData));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($result ? 200 : 500);
    } catch (PDOException $e) {
        $error = ["success" => false, "message" => $e->getMessage()];
        $response->getBody()->write(json_encode($error));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});


$app->delete('/eliminarCamion/{idRegistro}', function (Request $request, Response $response) {
    $idRegistro = $request->getAttribute('idRegistro');
    // Query para eliminar la fila con el idRegistro especificado
    $consulta = "DELETE FROM registrarcamion WHERE idRegistro = '$idRegistro'";

    try {
        $db = new db();
        $db = $db->connect();
        $db->exec($consulta);
        $db = null;
        $responseArray = array("message" => "Camión eliminado correctamente");
        $response->getBody()->write(json_encode($responseArray));
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


$app->post('/addSolicitudVistaClienteCamion', function (Request $request, Response $response) {
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

$app->get('/getAutorizacionVistaClienteCamion', function (Request $request, Response $response) {
    try {
        // Instanciar y conectar a la base de datos
        $db = new db();
        $db = $db->connect();
        $consulta = "SELECT * FROM vistacliente WHERE proceso = 'Registro de camión'";

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


