<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use GrahamCampbell\Markdown\Facades\Markdown;

class DocsController extends Controller
{
    public function index($doc = 'readme')
    {
        $files = [
            'readme' => [
                'label' => 'README',
                'path' => base_path('README.md'),
            ],
            'api' => [
                'label' => 'API Docs',
                'path' => base_path('dokumentasi-api.md'),
            ],
        ];

        $selected = $files[$doc] ?? $files['readme'];
        $content = File::exists($selected['path']) ? File::get($selected['path']) : '# File not found';
        $html = Markdown::convertToHtml($content);

        return view('docs.index', [
            'html' => $html,
            'files' => $files,
            'selected' => $doc,
        ]);
    }
}