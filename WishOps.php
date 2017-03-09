<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function get_wish($user_id) {
    $connection = new MongoClient();
    $db = $connection->mc;
    $wish_collection = $db->wishes;
    $query = array('id' => $user_id);
    $wish_obj = $wish_collection->findOne($query);
    if(!$wish_obj) {
        $response = array('item_id' => '', 'item_type' => -1 );
        $json = json_encode($response);
        echo $json;
    } else {
        $json = json_encode($wish_obj);
        echo $json;
    }
}


function set_wish($user_id, $item_id, $item_type) {
    $connection = new MongoClient();
    $db = $connection->mc;
    $wish_collection = $db->wishes;
    $query = array('id' => $user_id);
    $wish_obj = $wish_collection->findOne($query);
    if($wish_obj) {
        $wish_obj['item_id'] = $item_id;
        $wish_obj['item_type'] = $item_type;
        $wish_collection->save($wish_obj);
    } else {
        $arr = array('id' => $user_id, 'item_id' => $item_id, 'item_type' => $item_type);
        $wish_collection->insert($arr);
    }
}

function wish_process() {
    if(!isset($_POST['op'])) {
        return;
    }
    switch($_POST['op']) {
        case 'get_wish':
            get_wish($_POST['id']);
            break;
        case 'set_wish':
            set_wish($_POST['id'], $_POST['item_id'], $_POST['item_type']);
            break;
    }
}

wish_process();


//set_wish('TEST_USER_3', 'CL00017', 0);
