<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Support\Response;
class Controller {
    protected function view(string $view, array $data = [], string $layout = 'layouts.public'): Response { return new Response(app('view')->render($view, $data, $layout)); }
    protected function redirect(string $route, array $parameters = []): Response { return Response::redirect(route($route, $parameters)); }
    protected function json(array $data, int $status = 200): Response { return Response::json($data, $status); }
    protected function flash(string $type, string $message): void { $_SESSION['_flash'][] = compact('type', 'message'); }
}
