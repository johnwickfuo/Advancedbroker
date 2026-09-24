<?php
declare(strict_types=1);
use App\Support\Response; use App\Support\Router;
/** @var Router $router */
$router->get('/api/health', fn() => Response::json(['status' => 'ok', 'service' => 'upgradedbroker']), 'api.health');
