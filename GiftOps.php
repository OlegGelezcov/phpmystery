<?php

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Accept, X-Access-Token, X-Application-Name, X-Request-Sent-Time");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Origin: *");

/*
 *
 * {
 *  id : <id>
 * gifts: [
 *      {
 *          gift_id: <gift_id>
 *          sender: <sender_id>
 *          item_type: <inventory_type>
 *          item_id: <item_id>
 *      },
 *  *   {
 *          gift_id: <gift_id>
 *          sender: <sender_id>
 *          item_type: <inventory_type>
 *          item_id: <item_id>
 *      }
 *  ] 
 * } 
 */

require_once 'Gift.php';
require_once 'UserOps.php';


function dbobj_to_giftcollection(MongoCollection $ucollection, $gift_obj) {
    $gift_collection = new GiftCollection();
    $gift_collection->id = $gift_obj['id'];
    foreach ($gift_obj['gifts'] as $gobj) {
        $gift = new Gift();
        $gift->gift_id = $gobj['gift_id'];
        $gift->sender = read_user_from_db2($ucollection, $gobj['sender']);
        $gift->item_type = intval($gobj['item_type']);
        $gift->item_id = $gobj['item_id'];
        if(!is_null($gift->sender)) {
            $gift_collection->gifts[] = $gift;
        }
    }
    $doc = $gift_collection->to_document();
    return $doc;
}
function read_gifts(MongoCollection $gcollection, MongoCollection $ucollection, $id) {
    
    $query = array('id' => $id );
    $gift_obj = $gcollection->findOne($query);
    
    if(!$gift_obj) {
        $new_gift_obj = array('id' => $id, 'gifts' => array());
        $gcollection->insert($new_gift_obj);
        $json = json_encode($new_gift_obj);
        echo $json;
    } else {
        $doc = dbobj_to_giftcollection($ucollection, $gift_obj);
        $json = json_encode($doc);
        echo $json;
    }
}

//get or add gift obj at db for id
function read_gift_db_obj(MongoCollection $gcollection, $id ) {
    $query = array('id' => $id );
    $gift_obj = $gcollection->findOne($query);
    
    if(!$gift_obj) {
        $new_gift_obj = array('id' => $id, 'gifts' => array());
        $gcollection->insert($new_gift_obj);
        $result = $gcollection->findOne($query);
        return $result;
    }
    return $gift_obj;
}

function get_index_of_gift($gift_arr, $gift_id ) {
    for($i = 0; $i < count($gift_arr); $i++ ) {
        if($gift_arr[$i]['gift_id'] == $gift_id ) {
            return $i;
        }
    }
    return -1;
}

function take_gift(MongoCollection $gcollection, MongoCollection $ucollection,  $id, $gift_id) {
    
    $query = array('id' => $id );
    $gift_obj = $gcollection->findOne($query);
    $index = get_index_of_gift($gift_obj['gifts'], $gift_id);
    if($index < 0 ) {
        echo 'error';
        return;
    } else {
        $arr = $gift_obj['gifts'];
        array_splice($arr, $index, 1);
        $gift_obj['gifts'] = $arr;
        $gcollection->save($gift_obj);
        $doc = dbobj_to_giftcollection($ucollection, $gift_obj);
        $doc['success'] = 1;
        
        $json = json_encode($doc);
        echo $json;
    }
}

/*
 * Send gift (item_type, item_id) from sender_id to receiver_id
 */
function give_gift(MongoCollection $gcollection, MongoCollection $ucollection, $sender_id, $receiver_id, $gift_id, $item_type, $item_id ) {
    //check the sender and receiver exists in user collection
    $sender_user = read_user_from_db2($ucollection, $sender_id);
    if(is_null($sender_user)) {
        echo 'error';
        return;
    }
    
    $receiver_user = read_user_from_db2($ucollection, $receiver_id);
    if(is_null($receiver_user)) {
        echo 'error';
        return;
    }
    
    //get gift obj for receiver
    $receiver_gift_obj = read_gift_db_obj($gcollection, $receiver_id);
    if(!$receiver_gift_obj) {
        echo 'error';
        return;
    }
    
    $MAX_GIFTS = 100;
    
    //update gift array
    $gifts_arr = $receiver_gift_obj['gifts'];
    
    if(count($gifts_arr) >= $MAX_GIFTS ) {
        echo 'error';
        return;
    }
    
    $gifts_arr[] = array('gift_id'=>$gift_id, 'sender'=> $sender_id, 'item_type'=>$item_type, 'item_id'=>$item_id);
    $receiver_gift_obj['gifts'] = $gifts_arr;
    $gcollection->save($receiver_gift_obj);
    
    $result = array('item_id' => $item_id, 'item_type' => $item_type);
    $json = json_encode($result);
    echo $json;
}


function gift_process() {
    if(!isset($_POST['op'])){
        echo 'error';
        return;
    }
    
    $connection = new MongoClient();
    $db = $connection->mc;
    $gcollection = $db->gifts;
    $ucollection = $db->users;
    
    switch($_POST['op']) {
        case 'read_gifts':
            read_gifts($gcollection, $ucollection, $_POST['id']);
            break;
        case 'take_gift':
            take_gift($gcollection, $ucollection, $_POST['id'], $_POST['gift_id']);
            break;
        case 'give_gift':
            give_gift($gcollection, $ucollection, $_POST['sender_id'], $_POST['receiver_id'], $_POST['gift_id'], $_POST['item_type'], $_POST['item_id']);
            break;
    }
}

function test_give_gift() {
    $connection = new MongoClient();
    $db = $connection->mc;
    $gcollection = $db->gifts;
    $ucollection = $db->users;
    give_gift($gcollection, $ucollection, 'TEST_USER_3', 'TEST_USER_4', com_create_guid(), 0, 'CL00021');
}

function test_take_gift() {
    $connection = new MongoClient();
    $db = $connection->mc;
    $gcollection = $db->gifts;
    $ucollection = $db->users;
    take_gift($gcollection, $ucollection, 'TEST_USER_4', "ABCDT");
}

function test_read_gifts() {
    $connection = new MongoClient();
    $db = $connection->mc;
    $gcollection = $db->gifts;
    $ucollection = $db->users;
    read_gifts($gcollection, $ucollection, 'TEST_USER_444');
}

gift_process();


//test_read_gifts();


//test_take_gift();

/*
for($i = 0; $i < 5; $i++ ) {
    test_give_gift();
}*/


/*
class Data {
    public $val;
}

$d1 = new Data();
$d1->val = 2;
$d2 = new Data();
$d2->val = 6;
$d3 = new Data();
$d3->val = 8;
$testarr = array();
$testarr[] = $d1;
$testarr[] = $d2;
$testarr[] = $d3;

var_dump($testarr);
array_splice($testarr, 1, 1);
var_dump($testarr);
*/

?>