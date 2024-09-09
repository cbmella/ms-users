<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Aquí es donde puedes registrar todas las rutas para una aplicación.
| Es muy fácil. Simplemente indica a Lumen las URIs a las que debe responder
| y dale el Closure a llamar cuando se solicite esa URI.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'auth'], function () use ($router) {
    $router->post('/login', 'AuthController@login');
    $router->post('/register', 'AuthController@register');
    $router->post('/logout', 'AuthController@logout');
    $router->post('/refresh', 'AuthController@refresh');
    $router->post('/me', 'AuthController@me');
    $router->get('/validate-token', 'AuthController@validateToken'); // Nuevo endpoint para validar el token
    $router->get('/test-token-ttl', 'AuthController@testTokenTTL');
    $router->get('/token-life', 'AuthController@tokenLife');
});
