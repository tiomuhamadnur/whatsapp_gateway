<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ApiDocumentationController extends Controller
{
    public function __invoke(): View
    {
        return view('cms.docs.api', [
            'postmanUrl' => asset('postman/wa-gateway.postman_collection.json'),
        ]);
    }
}
