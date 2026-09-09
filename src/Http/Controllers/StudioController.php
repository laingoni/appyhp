<?php

namespace Alliswell\Appyhp\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StudioController
{
    public function __invoke(Request $request): View
    {
        return view('appyhp::studio', ['studioCsrfToken' => $request->session()->token()]);
    }
}
