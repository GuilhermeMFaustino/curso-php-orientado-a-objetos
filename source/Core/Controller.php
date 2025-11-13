<?php

namespace Source\Core;

use Source\Support\Message;
use Source\Support\Seo;


class Controller
{    
    /**
     * view * @var mixed */
     protected $view;    
    /** seo * @var mixed */
    protected $seo;

    /** @var Message */

    protected $message;
    
    /**
     * Method __construct 
     * @param ?string $pathToViews [explicite description] 
     * @return void
     */
    public function __construct(?string $pathToViews = null)
    {
        $this->view = new View($pathToViews);
        $this->seo = new Seo();
        $this->message = new Message();
    }

    
}