<?php

namespace Alliswell\Appyhp\Http\Middleware;

use Alliswell\Appyhp\Support\RuntimeStorage;
use Closure;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Session\FileSessionHandler;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\Store;

class StudioSession extends StartSession
{
    public function handle($request, Closure $next)
    {
        return $this->handleStatefulRequest($request, $this->getSession($request), $next);
    }

    public function getSession(Request $request)
    {
        $directory = app(RuntimeStorage::class)->ensure('sessions', 0700);

        // The builder must work before the host application's database is set up.
        $session = new Store('appyhp_session', new FileSessionHandler(new Filesystem, $directory, 120));
        $session->setId($request->cookies->get($session->getName()));

        return $session;
    }

    protected function saveSession($request)
    {
        $request->session()->save();
    }

    protected function sessionIsPersistent(?array $config = null)
    {
        return true;
    }
}
