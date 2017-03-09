<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/*
set status of offer in DB
 * offer_name - name of target offer
 * status - 1 or 0 enabled or disabled
 * end_time - time of offer end
 *  
 */
function set_offer($offer_name, $status, $end_time, $token) {
    $connection = new MongoClient();
    $db = $connection->mc;
    $offer_collection = $db->offers;
    
    $query = array('offer_name' => $offer_name);
    $offer_obj = $offer_collection->findOne($query);
    
    if($offer_obj) {
        $offer_obj['status'] = $status;
        $offer_obj['end_time'] = $end_time;
        $offer_obj['token'] = $token;
        $offer_collection->save($offer_obj);
        $json = json_encode($offer_obj);
        echo $json;
    } else {
        $new_offer = array('offer_name' => $offer_name, 'status' => $status, 'end_time' => $end_time, 'token' => $token);
        $offer_collection->save($new_offer);
        $json = json_encode($new_offer);
        echo $json;
    }
}

function set_video_offer($prob) {
    $connection = new MongoClient();
    $db = $connection->mc;
    $offer_collection = $db->offers;
    
    $query = array('offer_name' => 'video_offer');
    $offer_obj = $offer_collection->findOne($query);
    
    if($offer_obj) {
        $offer_obj['prob'] = $prob;
        $offer_collection->save($offer_obj);
        $json = json_encode($offer_obj);
        echo $json;
    } else {
        $new_offer = array('offer_name' => 'video_offer', 'prob' => $prob);
        $offer_collection->save($new_offer);
        $json = json_encode($new_offer);
        echo $json;
    }
}


function get_offer($offer_name) {
    $connection = new MongoClient();
    $db = $connection->mc;
    $offer_collection = $db->offers;
    
    $query = array('offer_name' => $offer_name);
    $offer_obj = $offer_collection->findOne($query);
    if($offer_obj ) {
        $json = json_encode($offer_obj);
        echo $json;
    } else {
        $new_offer = array('offer_name' => $offer_name, 'status' => 0, 'end_time' => 0, 'token' => '');
        $offer_collection->save($new_offer);
        $json = json_encode($new_offer);
        echo $json;
    }
}

function get_video_offer() {
    $connection = new MongoClient();
    $db = $connection->mc;
    $offer_collection = $db->offers;
    
    $query = array('offer_name' => 'video_offer');
    $offer_obj = $offer_collection->findOne($query);
    
    if($offer_obj) {
        $json = json_encode($offer_obj);
        echo $json;
    } else {
        $new_offer = array('offer_name' => 'video_offer', 'prob' => 0.3 );
        $offer_collection->save($new_offer);
        $json = json_encode($new_offer);
        echo $json;
    }
}


function handle_post() {
    if(isset($_POST['op'])) {
        
        switch($_POST['op']) {
            case 'get_offer':
                get_offer($_POST['offer_name']);
                break;
            case 'set_offer':
                set_offer($_POST['offer_name'], intval($_POST['status']), intval($_POST['end_time']), $_POST['token']);
                break;
            case 'set_video_offer':
                set_video_offer(floatval($_POST['prob']));
                break;
            case 'get_video_offer':
                get_video_offer();
                break;
        }
        return TRUE;
    }
    return FALSE;
}



function handle_get() {
    if(isset($_GET['op'])) {
        switch ($_GET['op']) {
            case 'get_offer':
                get_offer($_GET['offer_name']);
                break;
            case 'set_offer':
                set_offer($_GET['offer_name'], intval($_GET['status']), intval($_GET['end_time']), $_GET['token']);
                break;
            case 'set_video_offer':
                set_video_offer(floatval($_GET['prob']));
                break;
            case 'get_video_offer':
                get_video_offer();
                break;
            default:
                echo 'error';
        }
    }
}

function offer_process() {
    if(!handle_post()) {
        handle_get();
    }
}

offer_process();

?>