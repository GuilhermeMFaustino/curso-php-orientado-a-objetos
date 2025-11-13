<?php 

namespace Source\Models\CafeApp;

use Source\Core\Model;

class AppCategory extends model 
{
     public function __construct()
    {
        parent::__construct("app_categories", ["id"], ["name", "type"]);
    }
}