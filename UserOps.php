<?php

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Accept, X-Access-Token, X-Application-Name, X-Request-Sent-Time");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Origin: *");

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once 'User.php';

function get_user_from_post() {
    $user = new User();
    
    if(isset($_POST['id'])) {
        $user->id = $_POST['id'];
    } else {
        $user->id = 'NO_ID';
    }
    
    if(isset($_POST['name'])) {
        $user->name = $_POST['name'];
    } else {
        $user->name = 'NO_NAME';
    }
    
    if(isset($_POST['avatar'])) {
        $user->avatar = $_POST['avatar'];
    } else {
        $user->avatar = 'NO_AVATAR';
    }
    if(isset($_POST['level'])) {
        $user->level = intval($_POST['level']);
    } else {
        $user->level = 0;
    }
    return $user;
}

function write_user_to_db(User $user) {
    $connection = new MongoClient();
    $db = $connection->mc;
    $collection = $db->users;
    
    $query = array('id' => $user->id);
    $user_in_db = $collection->findOne($query);
    if($user_in_db) {
       $user_in_db['name'] = $user->name;
       $user_in_db['avatar'] = $user->avatar;
       $user_in_db['level'] = $user->level;
       $user_in_db['time'] = time();
       $collection->save($user_in_db);
       //echo 'save: ';
       //var_dump($user_in_db);
    } else {
        $doc = $user->to_document();
        $doc['time'] = time();
        $collection->insert($doc);
        //echo 'insert: ';
        //var_dump($user->to_document());
    }
}

function read_user_from_db($id) {
    $connection = new MongoClient();
    $db = $connection->mc;
    $collection = $db->users;
    
    $query = array('id' => $id);
    $user_in_db = $collection->findOne($query);
    
    if($user_in_db) {
        $user = new User();
        $user->parse_document($user_in_db);
        return $user;
    }
    return null;
}

function read_user_from_db2(MongoCollection $collection, $id ) {
    $query = array('id' => $id);
    $user_in_db = $collection->findOne($query);
    
    if($user_in_db) {
        $user = new User();
        $user->parse_document($user_in_db);
        return $user;
    }
    return null;
}

function process() {
    if(isset($_POST['op'])) {
        $operation = $_POST['op'];
        switch($operation){
            case 'write_user': 
                $write_user = get_user_from_post();
                write_user_to_db($write_user);
                break;
            case 'read_user':
                if(isset($_POST['id'])) {
                    $read_user = read_user_from_db($_POST['id']);
                    if(is_null($read_user)) {
                        echo 'error';
                    } else {
                        $json = json_encode($read_user->to_document());
                        echo $json;
                    }
                } else {
                    echo 'error';
                }
                break;
        } 
    }
}

function write_test_users() {
    for($i = 0; $i < 10; $i++ ) {
        $user = new User();
        $user->id = 'TEST_USER_' . $i;
        $user->name = 'TestName';
        $user->avatar = 'AVA01';
        $user->level = rand(1, 20);
        write_user_to_db($user);
    }
}

//write_test_users();

process();

/*
$testUser = new User();
$testUser->id = 'TEST_ID_USER';
$testUser->name = 'God Name';
$testUser->avatar = 'AVA01';
$testUser->level = 15;
write_user_to_db($testUser);
*/


?>
