<?php 


namespace Source\Models\CafeApp;

use Source\Core\Model;

class AppOrder extends Model
{
    public function __construct()
    {
        parent::__construct("app_orders", ["id"], ["user_id", "card_id", "subscription_id", "transaction", "amount", "status"]);
    }

    public function creditCard()
    {
        return (new AppCreditCard())->findById($this->card_id);
    }
}