<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//Obtiene los clientes
$app->get('/obtieneClientes', function(Request $request, Response $response){

    $consulta = "SELECT * 
    FROM usuario 
    WHERE idCargo = 4;
    ";

    try {
        
        // Instanciar la base de datos
        $db = new db();

        // Conectarse a la base de datos
        $db = $db->connect();
        $ejecutar = $db->query($consulta);
        $result = $ejecutar->fetchAll(PDO::FETCH_OBJ);
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

//Obtiene el JobIDColina y lo devuelve
$app->get('/obtieneJobId', function(Request $request, Response $response){

    $consulta = "SELECT item 
    FROM orden_trabajo 
    ORDER BY item DESC 
    LIMIT 1
    ";
    try {
        
        // Instanciar la base de datos
        $db = new db();

        // Conectarse a la base de datos
        $db = $db->connect();

        $ejecutar = $db->query($consulta);
        $result = $ejecutar->fetchAll(PDO::FETCH_OBJ);
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

// SEPARA LOS STRING DE LOS NUMEROS
function obtenerNumeros($cadena) {
    preg_match_all('!\d+!', $cadena, $coincidencias);
    return $coincidencias[0];
}


//Guardar un Orden de trabajo
$app->post('/guardarOT', function (Request $request, Response $response) {
    $fechaAviso = $request->getParam('fechaAviso');
    $jobIdColina = $request->getParam('jobIdColina');
    $itemArray = obtenerNumeros($jobIdColina);
    $itemString = implode('', $itemArray); // Convierte el array en una cadena sin separadores
    $itemNumero = intval($itemString); // Convierte la cadena en un entero

    try {
        // Instanciar la base de datos
        $db = new db();
        // Conexión
        $db = $db->connect();


        if ($jobIdColina !== '' && $fechaAviso  !== '' ) {
            $consulta = "INSERT INTO orden_trabajo (item, fechaAviso, jobIDColina) VALUES (:item, :fechaAviso, :jobIdColina)";
            $ejecutar = $db->prepare($consulta);
            $ejecutar->execute(['item' => $itemNumero ,'fechaAviso' => $fechaAviso, 'jobIdColina' => $jobIdColina]);
        }

        // Realizar la segunda consulta
        $consulta2 = "SELECT * FROM orden_trabajo ";
        $ejecutar2 = $db->query($consulta2);
        $ordenesTrabajos = $ejecutar2->fetchAll(PDO::FETCH_OBJ);

        $db = null;

        // Devolver la lista de usuarios
        $response->getBody()->write(json_encode($ordenesTrabajos));
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


// //Obtener las clases de usuario
// $app->get('/getCargos', function (Request $request, Response $response) {

//     $consulta = "SELECT * FROM cargo";
//     try {

//         // Instanciar la base de datos
//         $db = new db();

//         // Conexión
//         $db = $db->connect();
//         $ejecutar = $db->query($consulta);
//         $result = $ejecutar->fetchAll(PDO::FETCH_OBJ);
//         $db = null;
//         $response->getBody()->write(json_encode($result));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(200);
//     } catch (PDOException $e) {
//         $error = array(
//             "message" => $e->getMessage()
//         );
//         $response->getBody()->write(json_encode($error));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(500);;
//     }
// });

// //Obtener todos los usuarios
// $app->get('/getUsuarios', function (Request $request, Response $response) {
//     // Modifica la consulta para excluir la columna de contraseña
//     $consulta = "SELECT u.idUsuario, u.nombre, u.apellido, u.correo, u.telefono, u.idCargo, c.cargo, e.estado FROM usuario AS u INNER JOIN cargo AS c ON u.idCargo = c.idCargo JOIN estadousuario AS e ON u.idEstado = e.idEstado";

//     try {
//         $db = new db();
//         $db = $db->connect();
//         $ejecutar = $db->query($consulta);
//         $result = $ejecutar->fetchAll(PDO::FETCH_OBJ);
//         $db = null;
//         $response->getBody()->write(json_encode($result));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(200);
//     } catch (PDOException $e) {
//         $error = array("message" => $e->getMessage());
//         $response->getBody()->write(json_encode($error));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(500);
//     }
// });


// //Guardar un usuario
// $app->post('/saveUsuario', function (Request $request, Response $response) {
//     $nombre = $request->getParam('nombre');
//     $apellido = $request->getParam('apellido');
//     $correo = $request->getParam('correo');
//     $telefono = $request->getParam('telefono');
//     $password = $request->getParam('password'); // Contraseña en texto plano
//     $idCargo = $request->getParam('cargo');

//     try {
//         // Instanciar la base de datos
//         $db = new db();
//         // Conexión
//         $db = $db->connect();

//         // Verificar si ya existe el correo
//         $stmt = $db->prepare("SELECT COUNT(*) FROM usuario WHERE correo = :correo");
//         $stmt->execute(['correo' => $correo]);
//         $existe = $stmt->fetchColumn() > 0;

//         if (!$existe) {
//             // Si no existe, encripta la contraseña antes de insertar el nuevo usuario
//             $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
//             $consulta = "INSERT INTO usuario (nombre, apellido, correo, password, telefono, idCargo, idEstado) VALUES (:nombre, :apellido, :correo, :password, :telefono, :idCargo, '1')";
//             $ejecutar = $db->prepare($consulta);
//             $ejecutar->execute(['nombre' => $nombre, 'apellido' => $apellido, 'correo' => $correo, 'password' => $hashedPassword, 'telefono' => $telefono, 'idCargo' => $idCargo]);
//         }

//         // Realizar la segunda consulta
//         $consulta2 = "SELECT * FROM usuario AS u INNER JOIN cargo AS c ON u.idCargo = c.idCargo JOIN estadousuario AS e ON u.idEstado = e.idEstado";
//         $ejecutar2 = $db->query($consulta2);
//         $usuarios = $ejecutar2->fetchAll(PDO::FETCH_OBJ);

//         $db = null;

//         // Devolver la lista de usuarios
//         $response->getBody()->write(json_encode($usuarios));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(200);

//     } catch (PDOException $e) {
//         $error = array(
//             "message" => $e->getMessage()
//         );
//         $response->getBody()->write(json_encode($error));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(500);;
//     }
// });


// //Obtener un usuario
// $app->get('/getUsuario/{id}', function (Request $request, Response $response) {
//     $id = $request->getAttribute('id');
//     // Excluye la contraseña de la consulta
//     $consulta = "SELECT idUsuario, nombre, apellido, correo, telefono, idCargo FROM usuario WHERE idUsuario = :id";

//     try {
//         // Instanciar la base de datos
//         $db = new db();
//         $db = $db->connect();
//         $stmt = $db->prepare($consulta);
//         $stmt->execute(['id' => $id]);
//         $result = $stmt->fetch(PDO::FETCH_OBJ);
//         $db = null;
//         $response->getBody()->write(json_encode($result));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(200);
//     } catch (PDOException $e) {
//         $error = array("message" => $e->getMessage());
//         $response->getBody()->write(json_encode($error));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(500);
//     }
// });


// //Editar un usuario
// $app->put('/editUsuario', function (Request $request, Response $response) {
//     $nombre = $request->getParam('nombre');
//     $apellido = $request->getParam('apellido');
//     $correo = $request->getParam('correo');
//     $telefono = $request->getParam('telefono');
//     $password = $request->getParam('password');
//     $idCargo = $request->getParam('idCargo');
//     $id = $request->getParam('idUsuario');

//     try {
//         $db = new db();
//         $db = $db->connect();

//         if (!empty($password)) {
//             // Hashear la nueva contraseña y actualizar
//             $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
//             $consulta = "UPDATE usuario SET nombre = :nombre, apellido = :apellido, correo = :correo, password = :hashedPassword, telefono = :telefono, idCargo = :idCargo WHERE idUsuario = :id";
//             $parametros = ['nombre' => $nombre, 'apellido' => $apellido, 'correo' => $correo, 'hashedPassword' => $hashedPassword, 'telefono' => $telefono, 'idCargo' => $idCargo, 'id' => $id];
//         } else {
//             // No actualizar la contraseña
//             $consulta = "UPDATE usuario SET nombre = :nombre, apellido = :apellido, correo = :correo, telefono = :telefono, idCargo = :idCargo WHERE idUsuario = :id";
//             $parametros = ['nombre' => $nombre, 'apellido' => $apellido, 'correo' => $correo, 'telefono' => $telefono, 'idCargo' => $idCargo, 'id' => $id];
//         }

//         $ejecutar = $db->prepare($consulta);
//         $result =  $ejecutar->execute($parametros);

//         // Consulta para obtener todos los usuarios actualizados
//         $consulta2 = "SELECT * FROM usuario as u INNER JOIN cargo as c ON u.idCargo = c.idCargo JOIN estadousuario AS e ON u.idEstado = e.idEstado";
//         $ejecutar2 = $db->query($consulta2);
//         $result2 = $ejecutar2->fetchAll(PDO::FETCH_OBJ);
//         $db = null;
//         $response->getBody()->write(json_encode($result2));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(200);
//     } catch (PDOException $e) {
//         $error = array("message" => $e->getMessage());
//         $response->getBody()->write(json_encode($error));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(500);
//     }
// });

// //Desactiva un usuario, por configuración del front tiene el nombre contrario.
// $app->get('/activarUsuario/{id}', function (Request $request, Response $response) {

//     $id =  $request->getAttribute('id');

//     $consulta = "UPDATE usuario SET idEstado = '2' WHERE idUsuario = '$id' ";
   
//     try {
//         // Instanciar la base de datos
//         $db = new db();

//         // Conexión
//         $db = $db->connect();
//         $ejecutar = $db->prepare($consulta);
//         $result =  $ejecutar->execute();
//         $consulta2 = "SELECT * FROM usuario as u INNER JOIN cargo as cu ON u.idCargo = cu.idCargo JOIN estadousuario AS e ON u.idEstado = e.idEstado";
//         $ejecutar2 = $db->query($consulta2);
//         $result2 = $ejecutar2->fetchAll(PDO::FETCH_OBJ);
//         $db = null;
//         $response->getBody()->write(json_encode($result2));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(200);
//     } catch (PDOException $e) {
//         $error = array(
//             "message" => $e->getMessage()
//         );
//         $response->getBody()->write(json_encode($error));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(500);;
//     }
// });

// //Activa un usuario, por configuración del front tiene el nombre contrario.
// $app->get('/desactivarUsuario/{id}', function (Request $request, Response $response) {

//     $id =  $request->getAttribute('id');

//     $consulta = "UPDATE usuario SET idEstado = '1' WHERE idUsuario = '$id' ";
   
//     try {
//         // Instanciar la base de datos
//         $db = new db();

//         // Conexión
//         $db = $db->connect();
//         $ejecutar = $db->prepare($consulta);
//         $result =  $ejecutar->execute();
//         $consulta2 = "SELECT * FROM usuario as u INNER JOIN cargo as cu ON u.idCargo = cu.idCargo JOIN estadousuario AS e ON u.idEstado = e.idEstado";
//         $ejecutar2 = $db->query($consulta2);
//         $result2 = $ejecutar2->fetchAll(PDO::FETCH_OBJ);
//         $db = null;
//         $response->getBody()->write(json_encode($result2));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(200);
//     } catch (PDOException $e) {
//         $error = array(
//             "message" => $e->getMessage()
//         );
//         $response->getBody()->write(json_encode($error));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(500);;
//     }
// });


// $app->post('/pedirAutorizacion', function (Request $request, Response $response) {
//     $data = json_decode($request->getBody(), true);
//     // Datos a insertar
//     $tipoAccion = $data['tipoAccion'];
//     $idTablaUno = $data['idTablaUno'];
//     $idTablaDos = $data['idTablaDos'];
//     $idTablaTres = $data['idTablaTres'];
//     $tabla = $data['tabla'];
//     $estado = $data['estado'];
//     $tipoProceso = $data['tipoProceso'];
//     $idUsuario = $data['idUsuario'];

//     // Obtener la fecha y hora actual en la zona horaria de México
//     date_default_timezone_set('America/Mexico_City');
//     $fechaHora = date('Y-m-d H:i:s');

//     // Consulta SQL para insertar en la tabla notificacion
//     $sql = "INSERT INTO notificacion (tipoAccion, idTablaUno, idTablaDos, idTablaTres, tabla, estado, fechaSolicitud, tipoProceso, idUsuario) VALUES (:tipoAccion, :idTablaUno, :idTablaDos, :idTablaTres, :tabla, :estado, :fechaHora, :tipoProceso, :idUsuario)";

//     try {
//         // Obtener la conexión a la base de datos
//         $db = new db();
//         $conn = $db->connect();

//         $stmt = $conn->prepare($sql);
//         $stmt->bindParam(':tipoAccion', $tipoAccion);
//         $stmt->bindParam(':idTablaUno', $idTablaUno);
//         $stmt->bindParam(':idTablaDos', $idTablaDos);
//         $stmt->bindParam(':idTablaTres', $idTablaTres);
//         $stmt->bindParam(':tabla', $tabla);
//         $stmt->bindParam(':estado', $estado);
//         $stmt->bindParam(':fechaHora', $fechaHora);
//         $stmt->bindParam(':tipoProceso', $tipoProceso);
//         $stmt->bindParam(':idUsuario', $idUsuario);

//         $stmt->execute();

//         // Aquí puedes añadir cualquier lógica adicional o respuesta
//         $response->getBody()->write(json_encode(['message' => 'Autorización solicitada con éxito']));
//         return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
//     } catch (PDOException $e) {
//         $error = array("message" => $e->getMessage());
//         $response->getBody()->write(json_encode($error));
//         return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
//     }
// });

// $app->get('/getAutorizacionesAdmin', function (Request $request, Response $response) {

//     $consulta = "SELECT * FROM notificacion AS n JOIN usuario AS u ON n.idUsuario = u.idUsuario WHERE estado = 'Pendiente'";
//     try {

//         // Instanciar la base de datos
//         $db = new db();
//         // Conexión
//         $db = $db->connect();
//         $ejecutar = $db->query($consulta);
//         $result = $ejecutar->fetchAll(PDO::FETCH_OBJ);
//         $db = null;
//         $response->getBody()->write(json_encode($result));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(200);
//     } catch (PDOException $e) {
//         $error = array(
//             "message" => $e->getMessage()
//         );
//         $response->getBody()->write(json_encode($error));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(500);;
//     }
// });

// $app->get('/getAutorizaciones', function (Request $request, Response $response) {
//     try {
//         // Instanciar y conectar a la base de datos
//         $db = new db();
//         $db = $db->connect();
//         $consulta = "SELECT idTablaUno, idTablaDos, idTablaTres, estado, tipoProceso FROM notificacion";

//         // Ejecutar consulta
//         $ejecutar = $db->query($consulta);
//         $result = $ejecutar->fetchAll(PDO::FETCH_OBJ);

//         // Cerrar conexión
//         $db = null;

//         // Preparar respuesta
//         $response->getBody()->write(json_encode($result));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(200);
//     } catch (PDOException $e) {
//         $error = array(
//             "message" => $e->getMessage()
//         );
//         $response->getBody()->write(json_encode($error));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(500);;
//     }
// });


// $app->post('/autorizarSolicitud', function (Request $request, Response $response) {
//     $data = json_decode($request->getBody(), true);
//     $idNotificacion = $data['idNotificacion'];

//     // Establece la zona horaria a la de México
//     date_default_timezone_set('America/Mexico_City');
//     $fechaActual = date('Y-m-d H:i:s');

//     $sqlUpdate = "UPDATE notificacion SET estado = 'Aprobado', fechaSolicitud = :fechaActual WHERE idNotificacion = :idNotificacion";

//     // Consulta para obtener los datos actualizados
//     $sqlSelect = "SELECT * FROM notificacion WHERE estado = 'Pendiente'";

//     try {
//         $db = new db();
//         $conn = $db->connect();

//         // Ejecutar actualización
//         $stmtUpdate = $conn->prepare($sqlUpdate);
//         $stmtUpdate->bindParam(':idNotificacion', $idNotificacion, PDO::PARAM_INT);
//         $stmtUpdate->bindParam(':fechaActual', $fechaActual, PDO::PARAM_STR);
//         $stmtUpdate->execute();

//         // Obtener datos actualizados
//         $stmtSelect = $conn->query($sqlSelect);
//         $notificacionesPendientes = $stmtSelect->fetchAll(PDO::FETCH_OBJ);

//         // Devuelve una respuesta con las notificaciones pendientes
//         $response->getBody()->write(json_encode($notificacionesPendientes));
//         return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
//     } catch (PDOException $e) {
//         $error = array("message" => $e->getMessage());
//         $response->getBody()->write(json_encode($error));
//         return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
//     }
// });


// $app->post('/eliminarSolicitud', function (Request $request, Response $response) {
//     $data = json_decode($request->getBody(), true);
//     $idNotificacion = $data['idNotificacion'];

//     $sqlDelete = "DELETE FROM notificacion WHERE idNotificacion = :idNotificacion";

//     $sqlSelect = "SELECT * FROM notificacion WHERE estado = 'Pendiente'";

//     try {
//         $db = new db();
//         $conn = $db->connect();

//         // Ejecutar eliminación
//         $stmtDelete = $conn->prepare($sqlDelete);
//         $stmtDelete->bindParam(':idNotificacion', $idNotificacion);
//         $stmtDelete->execute();

//         // Obtener datos actualizados
//         $stmtSelect = $conn->query($sqlSelect);
//         $notificaciones = $stmtSelect->fetchAll(PDO::FETCH_OBJ);

//         $response->getBody()->write(json_encode($notificaciones));
//         return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
//     } catch (PDOException $e) {
//         $error = array("message" => $e->getMessage());
//         $response->getBody()->write(json_encode($error));
//         return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
//     }
// });

// $app->get('/gethistorialCambios', function (Request $request, Response $response) {
//     try {
//         // Instanciar y conectar a la base de datos
//         $db = new db();
//         $db = $db->connect();

//         // Modificar la consulta para incluir las uniones con la tabla usuario
//         $consulta = "SELECT h.*, u1.nombre AS nombreUsuarioEditor, u2.nombre AS nombreUsuarioRegistrado
//                      FROM historialcambio AS h
//                      JOIN usuario AS u1 ON h.idUsuario = u1.idUsuario
//                      JOIN usuario AS u2 ON h.ingresadopor = u2.idUsuario
//                      ORDER BY h.fechaHora DESC";

//         // Ejecutar consulta
//         $ejecutar = $db->query($consulta);
//         $result = $ejecutar->fetchAll(PDO::FETCH_OBJ);

//         // Cerrar conexión
//         $db = null;

//         // Preparar respuesta
//         $response->getBody()->write(json_encode($result));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(200);
//     } catch (PDOException $e) {
//         $error = array(
//             "message" => $e->getMessage()
//         );
//         $response->getBody()->write(json_encode($error));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(500);
//     }
// });



// $app->get('/getVistaCliente', function (Request $request, Response $response) {
//     try {
//         // Instanciar y conectar a la base de datos
//         $db = new db();
//         $db = $db->connect();
//         $consulta = "SELECT * FROM vistacliente AS v 
//                     JOIN nominacion AS n ON v.idNominacion = n.idNominacion 
//                     JOIN lote AS l ON v.idLote = l.idLote
//                     JOIN usuario AS u ON v.idUsuario = u.idUsuario WHERE estado = 'Pendiente'";

//         // Ejecutar consulta
//         $ejecutar = $db->query($consulta);
//         $result = $ejecutar->fetchAll(PDO::FETCH_OBJ);

//         // Cerrar conexión
//         $db = null;

//         // Preparar respuesta
//         $response->getBody()->write(json_encode($result));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(200);
//     } catch (PDOException $e) {
//         $error = array(
//             "message" => $e->getMessage()
//         );
//         $response->getBody()->write(json_encode($error));
//         return $response
//             ->withHeader('Content-Type', 'application/json')
//             ->withStatus(500);;
//     }
// });


// $app->post('/autorizarVistaCliente', function (Request $request, Response $response) {
//     $data = json_decode($request->getBody(), true);
//     $idVista = $data['idVista'];

    
//     $sqlUpdate = "UPDATE vistacliente SET estado = 'Aprobado' WHERE idVista = :idVista";

//     // Consulta para obtener los datos actualizados
//     $sqlSelect = "SELECT * FROM vistacliente AS v 
//                 JOIN nominacion AS n ON v.idNominacion = n.idNominacion 
//                 JOIN lote AS l ON v.idLote = l.idLote
//                 JOIN usuario AS u ON v.idUsuario = u.idUsuario WHERE estado = 'Pendiente'";

//     try {
//         $db = new db();
//         $conn = $db->connect();

//         // Ejecutar actualización
//         $stmtUpdate = $conn->prepare($sqlUpdate);
//         $stmtUpdate->bindParam(':idVista', $idVista);

//         $stmtUpdate->execute();

//         // Obtener datos actualizados
//         $stmtSelect = $conn->query($sqlSelect);
//         $notificacionesPendientes = $stmtSelect->fetchAll(PDO::FETCH_OBJ);

//         // Devuelve una respuesta con las notificaciones pendientes
//         $response->getBody()->write(json_encode($notificacionesPendientes));
//         return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
//     } catch (PDOException $e) {
//         $error = array("message" => $e->getMessage());
//         $response->getBody()->write(json_encode($error));
//         return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
//     }
// });



// $app->post('/rechazarVistaClieste', function (Request $request, Response $response) {
//     $data = json_decode($request->getBody(), true);
//     $idVista = $data['idVista'];

//     $sqlDelete = "DELETE FROM vistacliente WHERE idVista = :idVista";

//     $sqlSelect = "SELECT * FROM vistacliente AS v 
//                 JOIN nominacion AS n ON v.idNominacion = n.idNominacion 
//                 JOIN lote AS l ON v.idLote = l.idLote
//                 JOIN usuario AS u ON v.idUsuario = u.idUsuario WHERE estado = 'Pendiente'";

//     try {
//         $db = new db();
//         $conn = $db->connect();

//         // Ejecutar eliminación
//         $stmtDelete = $conn->prepare($sqlDelete);
//         $stmtDelete->bindParam(':idVista', $idVista);
//         $stmtDelete->execute();

//         // Obtener datos actualizados
//         $stmtSelect = $conn->query($sqlSelect);
//         $notificaciones = $stmtSelect->fetchAll(PDO::FETCH_OBJ);

//         $response->getBody()->write(json_encode($notificaciones));
//         return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
//     } catch (PDOException $e) {
//         $error = array("message" => $e->getMessage());
//         $response->getBody()->write(json_encode($error));
//         return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
//     }
// });



