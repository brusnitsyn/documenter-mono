<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentEditorController extends Controller
{
    public function editor(Request $request)
    {
        $templateId = $request->get('templateId', null);

        $template = null;
        if ($templateId !== null) {
            $template = DocumentTemplate::find($templateId);
        }

        return Inertia::render('TemplateEditor', [
            'template' => $template,
        ]);
    }
}
