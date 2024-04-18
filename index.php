<?php
header('Access-Control-Allow-Origin:*'); 
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Request-With');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');


use Slim\Factory\AppFactory;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/Config/db.php';


$app = AppFactory::create();

$app->setBasePath("/api_control_muestra");   

$app->addRoutingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write('Hello world!');
    return $response;
});

//Routes
require __DIR__ . '/src/Routes/login.php';
require __DIR__ . '/src/Routes/nominacion.php';
require __DIR__ . '/src/Routes/resultadopila.php';
require __DIR__ . '/src/Routes/registrocamion.php';
require __DIR__ . '/src/Routes/costadonave.php';
require __DIR__ . '/src/Routes/administracion.php';
require __DIR__ . '/src/Routes/laboratorio.php';

// Run app
$app->run();