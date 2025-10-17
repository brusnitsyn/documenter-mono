<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use Illuminate\Http\Request;

class EditorController extends Controller
{
    public function save(Request $request)
    {
        $template = DocumentTemplate::find($request->get('id'));
        $data = [
            'content' => $request->get('content'),
            'variables_config' => $request->get('variables_config'),
        ];
        $template->update($data);

        return redirect()->back();
    }
}
