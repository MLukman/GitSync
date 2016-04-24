<?php

namespace GitSync\Controller;

use Symfony\Component\HttpFoundation\Request;

class Root extends \GitSync\Base\Controller
{

    public function index(Request $request)
    {
        return new \Symfony\Component\HttpFoundation\RedirectResponse($this->app->path('repo_index'));
    }
}