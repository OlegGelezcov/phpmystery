<?php

require_once 'Friend.php';
require_once 'UserOps.php';

function get_friends(MongoCollection $friend_collection, MongoCollection $user_collection, MongoCollection $wish_collection, $id) {

    $query = array('id' => $id);
    $friend_obj = $friend_collection->findOne($query);
    
    if(!$friend_obj) {
        $new_friend_user = new Friend();
        $new_friend_user->id = $id;
        $document = $new_friend_user->to_document();
        $friend_collection->insert($document);
        return $document;
    } else {
        $friend = new Friend();
        $friend->id = $friend_obj['id'];
        $friend->friends = $friend_obj['friends'];
        
        $friend_obj_arr = [];
        $wishes = [];
        
        
        $fids = $friend->friends;
        foreach ($fids as $fid) {
            $fobj = read_user_from_db2($user_collection, $fid);
            if(!is_null($fobj)) {
                $friend_obj_arr[] = $fobj->to_document();
            }
            
            $wishQuery = array('id' => $fid);
            $wishObj = $wish_collection->findOne($wishQuery);
            if($wishObj) {
                $wishes[] = array('item_id' => $wishObj['item_id'], 'item_type' => $wishObj['item_type'], 'id' => $fid);
            } else {
                $wishes[] = array('item_id' => '', 'item_type' => -1, 'id' => $fid);
            }
        }
        $friend->wishes = $wishes;
        
        $result = array('id' => $friend->id, 'friends' => $friend_obj_arr, 'wishes' => $wishes);
        return $result;
    }
}

function create_new_friend_object_in_db(MongoCollection $collection, $id) {
    $new_friend_user = new Friend();
    $new_friend_user->id = $id;
    $document = $new_friend_user->to_document();
    $collection->insert($document);
    
    $query = array('id'=>$id);
    $db_obj = $collection->findOne($query);
    
    return $db_obj;
}



function read_friend_object(MongoCollection $collection, $id) {
    $query = array('id' => $id);
    $friend_obj = $collection->findOne($query);
    if(!$friend_obj) {
        return create_new_friend_object_in_db($collection, $id);
    } 
    return $friend_obj;
}

function add_friend($first_id, $second_id ) {
    $connection = new MongoClient();
    $db = $connection->mc;
    $friend_collection = $db->friends;
    $user_collection = $db->users;
    $wish_collection = $db->wishes;
    
    $first_user = read_user_from_db2($user_collection, $first_id);
    if(is_null($first_user)) {
        echo 'error';
        return;
    }
    
    $second_user = read_user_from_db2($user_collection, $second_id);
    if(is_null($second_user)) {
        echo 'error';
        return;
    }
    
    $MAX_FRIENDS = 50;
    
    
    
    $first_friend_obj = read_friend_object($friend_collection, $first_id);
    
    $second_friend_obj = read_friend_object($friend_collection, $second_id);
    
    if(count($first_friend_obj['friends']) >= $MAX_FRIENDS ) {
        echo 'SOURCE_MAX_FRIENDS_REACHED';
        return;
    }
    
    if(count($second_friend_obj['friends']) >= $MAX_FRIENDS ) {
        echo 'TARGET_MAX_FRIENDS_REACHED';
        return;
    }
    
    if(!in_array($second_id, $first_friend_obj['friends'])) {
        $first_friend_obj['friends'][] = $second_id;
        $friend_collection->save($first_friend_obj);
    }
    
    if(!in_array($first_id, $second_friend_obj['friends'])) {
        $second_friend_obj['friends'][] = $first_id;
        $friend_collection->save($second_friend_obj);
    }
    
    $first_friends = get_friends($friend_collection, $user_collection, $wish_collection, $first_id);
    $json = json_encode($first_friends);
    echo $json;
}

function get_index_of_element($arr, $elem) {
    for($i = 0; $i < count($arr); $i++ ) {
        if($arr[$i] == $elem ) {
            return $i;
        }
    }
    return -1;
}


function remove_friend($first_id, $second_id) {
    $connection = new MongoClient();
    $db = $connection->mc;
    $friend_collection = $db->friends;
    $user_collection = $db->users;
    $wish_collection = $db->wishes;
    
    $first_friend_obj = read_friend_object($friend_collection, $first_id);
    
    $second_friend_obj = read_friend_object($friend_collection, $second_id);
    
    if($first_friend_obj) {
        $farr = $first_friend_obj['friends'];
        if(in_array($second_id, $farr)) {
            $index = get_index_of_element($farr, $second_id);
            if($index >= 0 ) {
                array_splice($farr, $index, 1);
            }
        }
        $first_friend_obj['friends'] = $farr;
        $friend_collection->save($first_friend_obj);
    }
    
    if($second_friend_obj) {
        $farr = $second_friend_obj['friends'];
        if(in_array($first_id, $farr)) {
            $index = get_index_of_element($farr, $first_id);
            if($index >= 0 ) {
                array_splice($farr, $index, 1);
            }
        }
        $second_friend_obj['friends'] = $farr;
        $friend_collection->save($second_friend_obj);
    }
    
    $first_friends = get_friends($friend_collection, $user_collection, $wish_collection, $first_id);
    $json = json_encode($first_friends);
    echo $json;
}

function read_friends_op($id) {
    $connection = new MongoClient();
    $db = $connection->mc;
    $friend_collection = $db->friends;
    $user_collection = $db->users;
    $wish_collection = $db->wishes;
    
    $friends = get_friends($friend_collection, $user_collection, $wish_collection, $id);
    $json = json_encode($friends);
    echo $json;
}


function friend_process() {
    if(!isset($_POST['op'])) {
        return;
    }
    
    switch ($_POST['op']) {
        case 'read_friends': 
            read_friends_op($_POST['id']);
            break;
        case 'add_friend':
            add_friend($_POST['first_id'], $_POST['second_id']);
            break;
        case 'remove_friend':
            remove_friend($_POST['first_id'], $_POST['second_id']);
            break;
    }
}

friend_process();

//read_friends_op('721cefa42f59901bd437dbeb707b32cd672f2ecb');

?>