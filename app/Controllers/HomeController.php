<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Support\Request; use App\Support\Response;
final class HomeController extends Controller {
    public function index(Request $request): Response { $context = country(); $content = app('countries')->homepageContent($context->country, $context->languageCode); return $this->view('home.index', ['title' => $content['hero_headline'] ?? __('home.hero_headline'), 'description' => $content['hero_subheading'] ?? __('home.hero_subheading'), 'country_content' => $content, 'country' => $context,'featured_companies'=>app('companies')->featured($context)]); }
}
