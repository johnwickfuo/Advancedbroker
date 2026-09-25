<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Support\{Request,Response};
final class NotificationController extends Controller {
    public function index(Request $r):Response{
        app('notifications')->markAllRead((int)$_SESSION['user_id']);
        return Response::redirect(route('dashboard.index'));
    }
    public function read(Request $r):Response{
        app('notifications')->markRead((int)$_SESSION['user_id'],(int)$r->route('notification'));
        return Response::redirect(route('dashboard.index'));
    }
    public function readAll(Request $r):Response{
        app('notifications')->markAllRead((int)$_SESSION['user_id']);
        return Response::redirect(route('dashboard.index'));
    }
}
