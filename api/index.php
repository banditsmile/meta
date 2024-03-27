<?php
require __DIR__ . '/Router.php';
require __DIR__ . '/meta.php';
define('TMP_PATH', getenv('VERCEL') ? '/tmp' : __DIR__ . '/cache');

//$_SERVER['REQUEST_URI']= str_replace('.','-',$_SERVER['REQUEST_URI']);

$router = new Router();
$router->setBasePath('/');

// dynamic named route
$router->all( '/icon/{url}', function($url) {
    $meta = new html_meta($url);
    $json = $meta->check_cache($url,'json');
    if(empty($json)){
        $json = $meta->meta();
        $meta->cache($json,'json');
    }
    $meta->output(['json'=>$json['icon'],'url'=>$url],'json');
});

// dynamic named route
$router->all('/meta/{url}', function($url) {
    $meta = new html_meta($url);
    $json = $meta->check_cache($url,'json');
    $meta->output($json,'json');
});


// map homepage
$router->all( '/(/.*)?', function($url) {
    $meta = new html_meta($url);
    $icon = $meta->check_cache('ico');
    if($icon){
        $meta->output($icon,'ico');
    }
    $json = $meta->check_cache('json');
    if(empty($json)){
        $json = $meta->meta();
        $meta->cache($json,'json');
    }
    if(!empty($json['icon'])){
        $content = $meta->get_url_content($json['icon'][0]['href']);
        $meta->cache($content,'ico');
    }else{
        $content = $meta->default_icon();
    }
    $meta->output($content,'ico');

});
$router->all('/info', function() {
    phpinfo();
});
//$router->set404('/(/.*)?', function() {
//    header('HTTP/1.1 404 Not Found');
//    header('Content-Type: application/json');
//
//    $jsonArray = array();
//    $jsonArray['status'] = "404";
//    $jsonArray['status_text'] = "route not defined";
//
//    echo json_encode($jsonArray);
//});
$router->run();

