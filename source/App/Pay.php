<?php

namespace Source\App;

use Source\Core\Controller;

class Pay extends Controller
{
    public function __construct()
    {
        parent::__construct(__DIR__ . "/../../shared/pagarme/");
    }

    public function create()
    {
        
    }

    public function update(array $data)
    {
        
    }
}
