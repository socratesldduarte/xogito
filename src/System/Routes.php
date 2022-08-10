<?php

namespace Src\System;

class Routes {

    public function getRoutes()
    {
        return
        [
            [
                'method' => 'POST',
                'route' => '/register',
                'middleware' => null,
                'action' => [
                    'controller' => 'UserController',
                    'function' => 'register',
                ],
            ],
            [
                'method' => 'POST',
                'route' => '/login',
                'middleware' => null,
                'action' => [
                    'controller' => 'UserController',
                    'function' => 'login',
                ],
            ],
            [
                'method' => 'POST',
                'route' => '/mfa',
                'middleware' => null,
                'action' => [
                    'controller' => 'UserController',
                    'function' => 'mfa',
                ],
            ],
            [
                'method' => 'POST',
                'route' => '/user/create',
                'middleware' => [
                    'authenticated', 'administrator'
                ],
                'action' => [
                    'controller' => 'UserController',
                    'function' => 'createUser',
                ],
            ],
            [
                'method' => 'PATCH',
                'route' => '/user/{id}/updatename',
                'middleware' => [
                    'authenticated'
                ],
                'action' => [
                    'controller' => 'UserController',
                    'function' => 'updateName',
                ],
            ],
            [
                'method' => 'PATCH',
                'route' => '/user/{id}/deactivate',
                'middleware' => [
                    'authenticated', 'administrator'
                ],
                'action' => [
                    'controller' => 'UserController',
                    'function' => 'deactivate',
                ],
            ],
            [
                'method' => 'GET',
                'route' => '/users',
                'middleware' => [
                    'authenticated', 'administrator'
                ],
                'action' => [
                    'controller' => 'UserController',
                    'function' => 'listAll',
                ],
            ],
            [
                'method' => 'GET',
                'route' => '/user/{id}',
                'middleware' => [
                    'authenticated', 'administrator'
                ],
                'action' => [
                    'controller' => 'UserController',
                    'function' => 'show',
                ],
            ],
        ];
    }

    public function checkRoute($uri, $requestMethod) {
        $routes = $this->getRoutes();
        $uriPattern = array_slice($uri, 1);
        $parameters = [];
        foreach($routes as $route) {
            $routePattern = $this->getRoutePattern($route);
            if (strtoupper($requestMethod) === strtoupper($route["method"]) && count($routePattern) === count($uriPattern)) {
                $blnSamePattern = true;
                for ($i = 0; $i < count($uriPattern); $i++) {
                    if ($blnSamePattern && ($routePattern[$i] !== $uriPattern[$i])) {
                        if (strpos($routePattern[$i], "{") === false || strpos($routePattern[$i], "}") === false) {
                            $blnSamePattern = false;
                            break;
                        } else {
                            $parameters[] = [
                                'parameter' => str_replace(['{', '}'], ['', ''], $routePattern[$i]),
                                'value' => $uriPattern[$i],
                            ];
                        }
                    }
                }
                if ($blnSamePattern) {
                    $route["parameters"] = $parameters;
                    return $route;
                }
            }
        }
        return false;
    }

    public function getRoutePattern($route) {
        return array_slice(explode( '/', $route["route"]), 1);
    }
}