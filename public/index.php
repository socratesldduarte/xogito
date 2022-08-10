<?php
require "../bootstrap.php";

use Src\Controllers\{UserController}; //ALL CONTROLLER USED IN ROUTES
use Src\System\Routes;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri );

$requestMethod = $_SERVER["REQUEST_METHOD"];

$route = (new Routes())->checkRoute($uri, $requestMethod);
if ($route === false) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

switch ($route["action"]["controller"]) {
    case "UserController":
        $controller = new UserController();
        break;
    default:
        header("HTTP/1.1 404 Not Found");
        exit();
}

$controller->processRequest($route);