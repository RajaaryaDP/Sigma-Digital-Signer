<?php

require_once __DIR__ . '/Csrf.php';

class Loader
{
    public function view($path, $data = [])
    {
        extract($data);
        require __DIR__ . '/../' . $path;
    }
}
