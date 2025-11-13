<?php 


if(strpos(url(), "localhost")){
    /**
     * CSS
     */
    $minCSS = new \MatthiasMullie\Minify\CSS();
    $minCSS->add(__DIR__ . "/../../../shared/styles/boot.css");
    $minCSS->add(__DIR__ . "/../../../shared/styles/styles.css");

    $cssDir = scandir(__DIR__ . "/../../../themes/" . CONF_VIEW_APP . "/assets/css");
        foreach ($cssDir as $css) {
            $cssFile = __DIR__ . "/../../../themes/" . CONF_VIEW_APP . "/assets/css/{$css}";
            if(is_file($cssFile) && pathinfo($cssFile)['extension'] == "css"){            
                $minCSS->add($cssFile);
            }
        }

    $minCSS->minify(__DIR__ . "/../../../themes/" . CONF_VIEW_APP . "/assets/style.css");

    /**
     * JS
     */
        $minJS = new \MatthiasMullie\Minify\JS();
        $minJS->add(__DIR__."/../../../shared/scripts/jquery.min.js");
        $minJS->add(__DIR__."/../../../shared/scripts/jquery.form.js");
        $minJS->add(__DIR__."/../../../shared/scripts/jquery-ui.js");
        $minJS->add(__DIR__."/../../../shared/scripts/jquery.mask.js");
        $minJS->add(__DIR__."/../../../shared/scripts/highcharts.js");

    $jsDir = scandir(__DIR__ . "/../../../themes/" . CONF_VIEW_APP . "/assets/js");
        foreach ($jsDir as $js) {
            $jsFile = __DIR__ . "/../../../themes/" . CONF_VIEW_APP . "/assets/js/{$js}";
            if(is_file($jsFile) && pathinfo($jsFile)['extension'] == "js"){            
                $minJS->add($jsFile);
            }
        }    
         $minJS->minify(__DIR__ . "/../../../themes/" . CONF_VIEW_APP . "/assets/scripts.js");
}