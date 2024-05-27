<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Aquí cargamos el entorno y creamos la instancia de la aplicación...
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();
$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Aquí se registran algunos bindings en el contenedor de servicios...
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Aquí se registran los archivos de configuración "app", "jwt" y "permission"...
|
*/

$app->configure('app');
$app->configure('swagger-lume');
$app->configure('jwt');
$app->configure('permission');

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Aquí se registran los middlewares para la aplicación...
|
*/

$app->middleware([
    App\Http\Middleware\CorsMiddleware::class, // Aquí se registra el middleware de CORS
]);

$app->routeMiddleware([
    'api' => App\Http\Middleware\ApiMiddleware::class,
    'auth' => App\Http\Middleware\Authenticate::class,
    'permission' => Spatie\Permission\Middlewares\PermissionMiddleware::class,
    'role' => Spatie\Permission\Middlewares\RoleMiddleware::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Aquí se registran todos los proveedores de servicios de la aplicación...
|
*/

$app->register(App\Providers\AuthServiceProvider::class);
$app->register(\SwaggerLume\ServiceProvider::class);
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);
$app->register(Spatie\Permission\PermissionServiceProvider::class);

$app->alias('cache', \Illuminate\Cache\CacheManager::class);  // if you don't have this already
/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Aquí incluimos el archivo de rutas...
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app;
