<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Support\Request;
use App\Support\Response;

final class LanguageController extends Controller {
    public function update(Request $request): Response {
        $language = strtolower((string)$request->input('language'));
        if (!app('languages')->select(country(), $language)) { $this->flash('error', __('language.unsupported')); return Response::redirect((string)$request->input('return_to', route('home'))); }
        if (!empty($_SESSION['user_id'])) { $_SESSION['preferred_language_code'] = $language; app('user_preferences')->setLanguage((int)$_SESSION['user_id'], $language); }
        $this->flash('success', __('language.updated'));
        $returnTo = (string)$request->input('return_to', route('home'));
        $baseUrl = (string) config('app.url');
        return Response::redirect(str_starts_with($returnTo, '/') || ($baseUrl !== '' && str_starts_with($returnTo, $baseUrl)) ? $returnTo : route('home'));
    }
}
