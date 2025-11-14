<?php 

namespace Source\Models\CafeApp;

use Source\Core\Model;

class AppPlan extends Model
{
    public function __construct()
        {
            parent::__construct("app_plans", ["id"], ["name", "period", "period_str", "price", "status"]);
        }
}