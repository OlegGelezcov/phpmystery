<?php

/*
collections


challenge_info
    id: <id of challenge>
    start_time: <time of starting challenge>
    end_time: <time of ending challenge>
    prev_challenge_id: <id of previous challenge>
    param: <addtional parameter for challenge>


current_challenge
    user_id: <id of user>
    user_name: <name of user>
    user_avatar: <avatar of user>
    points: <current points of user>
    rank: <current rank of user>
    rewarded: <status of receiving reward for challenge>



After ending challenge first 50 user copiing in prev_challenge collection
after taking reward setted flag for user rewarded

prev_challenge
 */


$challenge_ids = array('kill_monster', 'got_exp', 'complete_room',
    'collect_silver', 'collect_gold', 'charge_collections', 'use_energy', 'complete_quest',
    'use_tool', 'use_bonus', 'make_purchase', 'roll');
$monsters = array('NPC01', 'NPC02', 'NPC03', 'NPC04', 'NPC05',
        'NPC06', 'NPC07', 'NPC08', 'NPC09', 'NPC10');
$tools = array('T0001', 'T0002', 'T0005', 'T0006', 'T0008', 'T0009');
$bonuses = array('B00001', 'B00002', 'B00003', 'B00004', 'B00005',
    'B00006', 'B00007', 'B00008', 'B00009');
$rooms = array('r1', 'r2', 'r3', 'r4', 'r5', 'r6', 'r7', 'r8', 'r9', 'r17', 'r18', 'r19', 'r20');
$CHALLENGE_INERVAL = 100;
$MAX_TOP_USERS = 50;


function get_challenge_param($challenge, $param ) {
    global $monsters;
    global $tools;
    global $bonuses;
    global $rooms;
    
    $result = '';
    switch ($challenge){
        case 'kill_monster': 
            $filtered = select_from_array_except($monsters, $param);
            $result = $filtered[array_rand($filtered)];
            break;
        case 'complete_room':
            $filtered = select_from_array_except($rooms, $param);
            $result = $filtered[array_rand($filtered)];
            break;
        case 'use_tool':
            $filtered = select_from_array_except($tools, $param);
            $result = $filtered[array_rand($filtered)];
            break;
        case 'use_bonus':
            $filtered = select_from_array_except($bonuses, $param);
            $result = $filtered[array_rand($filtered)];
            break;
    }
    return $result;
}



//return source array except single element
function select_from_array_except($arr, $elem) {
    $result = array();
    foreach($arr as $single) {
        if($single != $elem ) {
            $result[] = $single;
        }
    }
    return $result;
}

//read challenge index from file if exists, else return default index 0
function read_challenge_index() {
    $filename = 'challenge_index.txt';
    if(file_exists($filename)) {
        $content = file_get_contents($filename);
        if(!$content) {
            return 0;
        } else {
            $val = intval($content);
            return $val;
        }
    } else {
        return 0;
    }
}

function write_challenge_index($val) {
    $filename = 'challenge_index.txt';
    file_put_contents($filename, strval($val));
}

//write_challenge_index(5);

//echo read_challenge_index() . "\n";




//var_dump(select_from_array_except($tools, 'T0001'));
//var_dump(select_from_array_except($rooms, 'r6'));

function create_new_challenge(
        $challenge_info_collection,  
        $prev_challenge_id, $prev_param) {
    global $challenge_ids;
    global $CHALLENGE_INERVAL;
    $challenge_index = read_challenge_index();
    if($challenge_index >= count($challenge_ids)) {
        $challenge_index = 0;
    }
    write_challenge_index($challenge_index + 1);
    $challenge_id = $challenge_ids[$challenge_index];
    
    $challenge = array(
        'id' => $challenge_id,
        'start_time' => time(),
        'end_time' => (time() + $CHALLENGE_INERVAL),
        'prev_challenge_id' => $prev_challenge_id,
        'param' => get_challenge_param($challenge_id, $prev_param)
        );
    $challenge_info_collection->insert($challenge);
    $curchlg = $challenge_info_collection->findOne();
    return $curchlg;
}

function replace_old_challenge($challenge_info_collection, $info_obj, $prev_challenge, $prev_param) {
    global $challenge_ids;
    global $CHALLENGE_INERVAL;
    $challenge_index = read_challenge_index();
    if($challenge_index >= count($challenge_ids)) {
        $challenge_index = 0;
    }
    write_challenge_index($challenge_index + 1);
    $challenge_id = $challenge_ids[$challenge_index];
    $info_obj['id'] = $challenge_id;
    $info_obj['start_time'] = time();
    $info_obj['end_time'] = time() + $CHALLENGE_INERVAL;
    $info_obj['prev_challenge_id'] = $prev_challenge;
    $info_obj['param'] = get_challenge_param($challenge_id, $prev_param);
    $challenge_info_collection->save($info_obj);
    return $info_obj;
}

/*
$cursor = $collection->find();
$cursor->sort(array('i' => -1));
$cursor->limit(20);

foreach($cursor as $id => $value ) {
    //echo "$id: ";
    //var_dump($value);
    //echo $value;
    $new_collection->save($value);
}

$cursor = $new_collection->find();
$i = 1;
foreach($cursor as $id => $value ) {
    $value['rank'] = $i;
    $i++;
    var_dump($value);
}*/
//when challenge completed make copying current top 50 users with their ranks to prev users collection
function copy_current_challenge_to_prev($current_challenge_collection, $prev_challenge_collection ) {
    global $MAX_TOP_USERS;
    $prev_challenge_collection->remove();
    $cursor = $current_challenge_collection->find();
    $cursor->sort(array('points' => -1));
    $cursor->limit($MAX_TOP_USERS);
    
    $i = 1;
    
    foreach ($cursor as $key => $value ) {
        $value['rank'] = $i;
        $i++;
        $prev_challenge_collection->insert($value);
    }
    
    $current_challenge_collection->remove();
}

//Check the current challenge completed, if yes - move top users to 
//prev challenge collection, clear current challenfge collection
//and start new challenge
function check_for_start_new_challenge(
        $challenge_info_collection, 
        $current_challenge_collection, 
        $prev_challenge_collection) {
    
    $info_obj = $challenge_info_collection->findOne();
    
    if(!$info_obj) {
        //echo 'No challenge create new...' . PHP_EOL;
        return create_new_challenge($challenge_info_collection, '', '');
    } else {
        $end_time = intval($info_obj['end_time']);
        if(time() > $end_time) {
            //echo 'Challenge expired replace old with new...' . PHP_EOL;
            copy_current_challenge_to_prev($current_challenge_collection, $prev_challenge_collection);
            $prev_challenge = $info_obj['id'];
            $prev_param = $info_obj['param'];
            return replace_old_challenge($challenge_info_collection, $info_obj, $prev_challenge, $prev_param);
            //create_new_challenge($challenge_info_collection, $current_challenge_collection, $prev_challenge_id, $prev_param);
        } else {
            //echo 'challenge not started, not expired...' . PHP_EOL;
            $info_obj = $challenge_info_collection->findOne();
            return $info_obj;
        }
    }
}

function get_current_challenge_info($challenge_info_collection) {
    $info_obj = $challenge_info_collection->findOne();
    if($info_obj) {
        return json_encode($info_obj);
    }
    return '';
}



//echo get_challenge_param('kill_monster', '') . '\r';
//print_r(get_challenge_param('kill_monster', '') . PHP_EOL);

/*
current_challenge
    id: <id of user>
    name: <name of user>
    avatar: <avatar of user>
    points: <current points of user>
    rank: <current rank of user>
    rewarded: <status of receiving reward for challenge>
 *  */

//write points for user id for current challenge
function write_user_points($current_challenge_collection, $id, $name, $avatar, $points) {
    $query = array('id' => $id );
    $user_obj = $current_challenge_collection->findOne($query);
    if($user_obj) {
        $user_obj['name'] = $name;
        $user_obj['avatar'] = intval($avatar);
        $user_obj['points'] = intval($points);
        $user_obj['rewarded'] = 0;
        $current_challenge_collection->save($user_obj);
    } else {
        $new_user_arr = array(
            'id' => $id,
            'name' => $name,
            'avatar' => intval($avatar),
            'points' => intval($points),
            'rewarded' => 0,
            'rank' => 0
        );
        $current_challenge_collection->insert($new_user_arr);
    }
}

//find user and return as json, also count user rank
function read_user($current_challenge_collection, $id, $name, $avatar) {
    $query = array('id' => $id);
    $user_obj = $current_challenge_collection->findOne($query);
    if($user_obj) {
        $count = $current_challenge_collection->count(
                array('points' => array('$gt' => intval($user_obj['points'])))
                );
        $user_obj['rank'] = ($count + 1);
        return $user_obj;
    } else {
        $usr = array('id' => $id, 'name' => $name, 'avatar' => intval($avatar),
            'points' => 0, 'rewarded' => 0, 'rank' => 0);
        $current_challenge_collection->insert($usr);
        $user_obj2 = $current_challenge_collection->findOne($query);
        if($user_obj2) {
            $count = $current_challenge_collection->count(
                array('points' => array('$gt' => intval($user_obj2['points'])))
                );
            $user_obj2['rank'] = ($count+1);
            return $user_obj2;
        }
    }
    return NULL;
}

function read_prev_top($prev_challenge_collection) {
    $cursor = $prev_challenge_collection->find();
    $result = array();
    foreach ($cursor as $key => $value ) {
        $result[] = $value;
    }
    return $result;
}

function read_current_top($current_challenge_collection) {
    global $MAX_TOP_USERS;
    $cursor = $current_challenge_collection->find();
    $cursor->sort(array('points' => -1));
    $cursor->limit($MAX_TOP_USERS);
    
    $result = array();
    $i = 1;
    foreach ($cursor as $key => $value) {
        $value['rank'] = $i;
        $i++;
        $result[] = $value;
    }
    return $result;
}

function take_reward($prev_challenge_collection, $id ) {
    $info_obj = $prev_challenge_collection->findOne(array('id' => $id));
    if($info_obj) {
        $rewarded = intval($info_obj['rewarded']);
        if($rewarded == 0 ) {
            $info_obj['rewarded'] = 1;
            $prev_challenge_collection->save($info_obj);
            return 1;
        }
    }
    return 0;
}






//echo read_prev_top($prev_challenge_collection);

//check_for_start_new_challenge($challenge_info_collection, $current_challenge_collection, $prev_challenge_collection);
//echo get_current_challenge_info($challenge_info_collection) . PHP_EOL;

/*
$usr_1 = array('id' => 'usr1', 'name' => 'Oleg', 'avatar' => 0, 'points' => 10);
$usr_2 = array('id' => 'usr2', 'name' => 'Vasya', 'avatar' => 1, 'points' => 50);
$usr_3 = array('id' => 'usr3', 'name' => 'John', 'avatar' => 2, 'points' => 30);
$usr_4 = array('id' => 'usr4', 'name' => 'Mark', 'avatar' => 3, 'points' => 40);


write_user_points($current_challenge_collection, $usr_1['id'], $usr_1['name'], $usr_1['avatar'], $usr_1['points']);
write_user_points($current_challenge_collection, $usr_2['id'], $usr_2['name'], $usr_2['avatar'], $usr_2['points']);
write_user_points($current_challenge_collection, $usr_3['id'], $usr_3['name'], $usr_3['avatar'], $usr_3['points']);
write_user_points($current_challenge_collection, $usr_4['id'], $usr_4['name'], $usr_4['avatar'], $usr_4['points']);


copy_current_challenge_to_prev($current_challenge_collection, $prev_challenge_collection);

echo 'CURRENT:' . PHP_EOL;
print_collection($current_challenge_collection);

echo 'PREV:' . PHP_EOL;
print_collection($prev_challenge_collection);
*/
//echo read_user($current_challenge_collection, 'usr3');


function test_remove_collection($collection) {
    $collection->remove();
}

function print_collection($collection ) {
    $cursor = $collection->find();
    foreach ($cursor as $key => $value ) {
        var_dump($value);
    }
}

//test_remove_collection($current_challenge_collection);
//print_collection($current_challenge_collection);

//Read all challenge state
function read_full(
        $challenge_info_collection, 
        $current_challenge_collection, 
        $prev_challenge_collection, $id, $name, $avatar) {
    
    //check for new challenge
    $challenge_obj = check_for_start_new_challenge($challenge_info_collection, $current_challenge_collection, $prev_challenge_collection);
    
    $my_user_obj = read_user($current_challenge_collection, $id, $name, $avatar);
    $current_top = read_current_top($current_challenge_collection);
    $prev_top = read_prev_top($prev_challenge_collection);
    
    
    $response = array();
    if($my_user_obj) {
        $response['myuser'] = $my_user_obj;
    }
    $response['current_top'] = $current_top;
    $response['prev_top'] = $prev_top;
    if($challenge_obj) {
        $response['challenge'] = $challenge_obj;
    }
    
    echo json_encode($response);
}

function handle_op($source) {
    $connection = new MongoClient();
    $db = $connection->mc;
    $challenge_info_collection = $db->challenge_info_collection;
    $current_challenge_collection = $db->current_challenge_collection;
    $prev_challenge_collection = $db->prev_challenge_collection;
    
    
    $id = '';
    $name = '';
    $avatar = 0;
    $points = 0;
    $op = '';
    if(isset($source['op'])) {
        $op = $source['op'];
    }
    if(isset($source['id'])) {
        $id = $source['id'];
    }
    if(isset($source['name'])) {
        $name = $source['name'];
    }
    if(isset($source['points'])) {
        $points = $source['points'];
    }
    if(isset($source['avatar'])) {
        $avatar = $source['avatar'];
    }
   
    //echo $id . ' ' . $name . ' ' . $avatar . ' ' . $points . PHP_EOL;
    
    switch ($op) {
        case 'read_full':
            read_full($challenge_info_collection, $current_challenge_collection, $prev_challenge_collection, $id, $name, intval($avatar));
            break;
        case 'write_points':
            write_user_points($current_challenge_collection, $id, $name, intval($avatar), intval($points));
            break;
        case 'take_reward':
            take_reward($prev_challenge_collection, $id);
            break;
    }
}

function challenge_op() {
    if(!$_POST['op']) {
        if(isset($_GET['op'])) {
            handle_op($_GET);
        }
    } else {
        handle_op($_POST);
    }
}



/*
$connection = new MongoClient();
$db = $connection->mc;
$challenge_info_collection = $db->challenge_info_collection;
$current_challenge_collection = $db->current_challenge_collection;
$prev_challenge_collection = $db->prev_challenge_collection;

$usr_1 = array('id' => 'usr1', 'name' => 'Oleg', 'avatar' => 0, 'points' => 10);
$usr_2 = array('id' => 'usr2', 'name' => 'Vasya', 'avatar' => 1, 'points' => 50);
$usr_3 = array('id' => 'usr3', 'name' => 'John', 'avatar' => 2, 'points' => 30);
$usr_4 = array('id' => 'usr4', 'name' => 'Mark', 'avatar' => 3, 'points' => 40);


write_user_points($current_challenge_collection, $usr_1['id'], $usr_1['name'], $usr_1['avatar'], $usr_1['points']);
write_user_points($current_challenge_collection, $usr_2['id'], $usr_2['name'], $usr_2['avatar'], $usr_2['points']);
write_user_points($current_challenge_collection, $usr_3['id'], $usr_3['name'], $usr_3['avatar'], $usr_3['points']);
write_user_points($current_challenge_collection, $usr_4['id'], $usr_4['name'], $usr_4['avatar'], $usr_4['points']);


//read_full($challenge_info_collection, $current_challenge_collection, $prev_challenge_collection, 'usr1', 'Vasya', 2);
*/

//$_POST['op'] = 'read_full';
//$_POST['id'] = 'usr1';
//$_POST['name'] = 'abc';
//$_POST['avatar'] = 0;
//$_POST['points'] = 34;
challenge_op();

?>