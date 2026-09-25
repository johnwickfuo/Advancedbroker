<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $context = country();
        $experience = app('homepage_experience')->forCountry($context->country, $context->languageCode);
        $featured = app('companies')->featured($context, 6);
        $sectors = [];
        foreach ($featured as $company) {
            $sector = trim((string)($company['industry'] ?: $company['sector'] ?? ''));
            if ($sector !== '') $sectors[$sector] = true;
        }

        return $this->view('home.index', [
            'title' => $experience['hero'],
            'description' => $experience['sub'],
            'country' => $context,
            'experience' => $experience,
            'featured_companies' => $featured,
            'featured_sectors' => array_keys($sectors),
        ]);
    }
}
