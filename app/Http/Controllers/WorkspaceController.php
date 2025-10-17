<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkspaceController extends Controller
{
    public function showTemplates()
    {
        $activeTemplates = DocumentTemplate::all();

        return Inertia::render('Index', [
            'templates' => $activeTemplates
        ]);
    }
}
