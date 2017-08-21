<?php

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Accept, X-Access-Token, X-Application-Name, X-Request-Sent-Time");
header("Access-Control-Allow-Metfhods: GET, POST, OPTIONS");
header("Access-Control-Allow-Origin: *");


function write_news($collection, $title, $content, $enabled, $region) {
    $query = array('region' => $region);
    $news_obj = $collection->findOne($query);
    if($news_obj) {
        $news_obj['title'] = $title;
        $news_obj['content'] = $content;
        $news_obj['enabled'] = $enabled;
        $news_obj['creation_time'] = time();
        
        $news_obj['time'] = time();
        $news_obj['token'] = uniqid();
        $collection->save($news_obj);
        return $news_obj;
    } else {
        $news_obj = array('title' => $title, 
            'content' => $content, 
            'enabled' => $enabled, 
            'creation_time' => time(), 
            'region' => $region, 
            'token' => uniqid());
        $news_obj['time'] = time();
        $collection->insert($news_obj);
        
        return $news_obj;
    }
}

function read_news($collection, $region) {
    $query = array('region' => $region);
    $news_obj = $collection->findOne($query);
    if($news_obj) {
        $news_obj['time'] = time();
        return $news_obj;
    } else {
        $new_obj = array('title' => '', 'content' => '', 'enabled' => 0, 'creation_time' => time(), 'region' => $region, 'token' => uniqid());
        return $news_obj;
    }
}

function handle_op($source) {
    $op_name = $source['op'];
    $client = new MongoClient();
    $db = $client->mc;
    $collection = $db->news;
    
    switch($op_name){
        case 'write': {
            $result = write_news($collection, $source['title'], $source['content'], intval($source['enabled']), $source['region']);
            echo json_encode($result);
        }
        break;
        case 'read': {
            $result = read_news($collection, $source['region']);
            echo json_encode($result);
        }
        break;
        default :
            echo 'error';
        break;
    }
}


function fmain() {
    if(isset($_POST['op'])) {
        handle_op($_POST);
    } else {
        if(isset($_GET['op'])) {
            handle_op($_GET);
        } else {
            echo 'error';
        }
    }
}

fmain();

?>

