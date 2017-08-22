<?php
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Accept, X-Access-Token, X-Application-Name, X-Request-Sent-Time");
header("Access-Control-Allow-Metfhods: GET, POST, OPTIONS");
header("Access-Control-Allow-Origin: *");

$DEFAULT_INTERVAL = 604800;
$DEFAULT_REWARD_COUNT = 50;
$DEFAULT_HP = 10000;

function write_event_info($event_collection, $interval, $reward_count, $dragon_hp){
    $info_obj = $event_collection->findOne();
    if($info_obj) {
        $info_obj['interval'] = $interval;
        $info_obj['reward_count'] = $reward_count;
        $info_obj['hp'] = $dragon_hp;
        if(!isset($info_obj['end_time'])) {
            $info_obj['end_time'] = 0;
        }
        if(!isset($info_obj['rewarded'])) {
            $info_obj['rewarded'] = 0;
        }
        $info_obj['current_hp'] = 0;
        if(!isset($info_obj['check_time'])) {
            $info_obj['check_time'] = 0;
        }
        $info_obj['time'] = time();
        $event_collection->save($info_obj);
    } else {
        $info_obj = array('interval' => $interval,
            'reward_count' => $reward_count,
            'hp' => $dragon_hp,
            'end_time' => 0,
            'current_hp' => 0,
            'rewarded' => 0,
            'check_time' => 0,
            'time' => time());
        $event_collection->insert($info_obj);
    }
    return $info_obj;
}

function start_event($event_collection, $users_collection , $interval = 604800, $reward_count = 50, $hp = 10000){
    $info_obj = $event_collection->findOne();
    if($info_obj) {
        $info_obj['interval'] = $interval;
        $info_obj['reward_count'] = $reward_count;
        $info_obj['hp'] = $hp;
        $info_obj['end_time'] = time() + $info_obj['interval'];
        $info_obj['current_hp'] = 0;
        $info_obj['rewarded'] = 0;
        $info_obj['start_time'] = time();
        $info_obj['check_time'] = 0;
        $info_obj['token'] = md5(uniqid(rand(), true));
        $info_obj['time'] = time();
        
        $event_collection->save($info_obj);
        $users_collection->remove();
        return $info_obj;
    } else {
        write_event_info($event_collection, $interval, $reward_count, $hp);
        $info_obj = $event_collection->findOne();
        if($info_obj) {
            $info_obj['end_time'] = time() + $info_obj['interval'];
            $info_obj['current_hp'] = 0;
            $info_obj['rewarded'] = 0;
            $info_obj['start_time'] = time();
            $info_obj['check_time'] = 0;
            $info_obj['token'] = md5(uniqid(rand(), true));
            $info_obj['time'] = time();
            $event_collection->save($info_obj);
            $users_collection->remove();
            return $info_obj;
        } else {
            $users_collection->remove();
            return 'error';
        }
    }
}

function get_new_user($user_id, $avatar, $name, $level, $stones) {
    $user_obj = array('id' => $user_id,
        'avatar' => $avatar,
        'name' => $name,
        'level' => $level,
        'stones' => $stones,
        'spended_stones' => 0,
        'rewarded' => 0,
        'rank' => 0);  
    return $user_obj;
}

function add_stones($event_info, $event_collection, $users_collection, $user_id, $avatar, $name, $level, $stones ) {
    $query = array('id' => $user_id);
    $user_obj = $users_collection->findOne($query);
    if($user_obj) {
        $user_obj['stones'] = $stones + $user_obj['stones'];
        $user_obj['avatar'] = $avatar;
        $user_obj['name'] = $name;
        $user_obj['level'] = $level;
        $users_collection->save($user_obj);
    } else {
        $user_obj = get_new_user($user_id, $avatar, $name, $level, $stones);
        $users_collection->insert($user_obj);
    }
    return $user_obj;
}

function update_and_get_user($event_info, $users_collection, $user_id, $avatar, $name, $level, $stones) {
    if(is_event_started($event_info) && is_event_not_rewarded($event_info) && is_dragon_alive($event_info)) {
        $query = array('id' => $user_id);
        $user_obj = $users_collection->findOne($query);
        if($user_obj) {
            $user_obj['stones'] = $stones + $user_obj['stones'];
            $user_obj['avatar'] = $avatar;
            $user_obj['name'] = $name;
            $user_obj['level'] = $level;
        } else {
            $user_obj = get_new_user($user_id, $avatar, $name, $level, $stones);
        }
        return $user_obj;
    }
    return NULL;
}

function make_hit($event_info, $event_collection, $users_collection, $user_id, $avatar, $name, $level, $stones) {
    if(is_event_started($event_info) && is_event_not_rewarded($event_info) && is_dragon_alive($event_info)) {
        $user_obj = update_and_get_user($event_info, $users_collection, $user_id, $avatar, $name, $level, $stones);
        $stones = intval($user_obj['stones']);
        if($stones > 0 ) {

            $event_info['current_hp'] = intval($event_info['current_hp']) + 1;
            $event_collection->save($event_info);
            $edump = $event_collection->findOne();
            //echo json_encode($edump);
            
            $user_obj['stones'] = $stones - 1;
            $user_obj['spended_stones'] = intval($user_obj['spended_stones']) + 1;
            $user_obj['hit_success'] = 1;
            $users_collection->save($user_obj);
            
            //echo $event_info['current_hp'] . ' saving...\n';
            
        } else {
            $user_obj['hit_success'] = 0;
            $users_collection->save($user_obj);
        }
        return array('user' => $user_obj, 'event' => $event_info);
    }
    return NULL;
}

function get_prev_user($prev_users_collection, $user_id ) {
    $query = array('id' => $user_id);
    $user_obj = $prev_users_collection->findOne($query);
    if($user_obj) {
        return $user_obj;
    }
    return NULL;
}

function take_reward($event_info, $prev_users_collection, $user_id ) {
    $user_obj = get_prev_user($prev_users_collection, $user_id);
    if($user_obj != NULL ) {
        $rewarded = $user_obj['rewarded'];
        if($rewarded == 0 ) {
            $user_obj['rewarded'] = 1;
            $prev_users_collection->save($user_obj);
            
            $spended_stones = intval($user_obj['spended_stones']);
            $min_stones = intval($event_info['reward_count']);
            if($spended_stones >= $min_stones) {
                $rank = intval($user_obj['rank']);
                
                if($rank <= 3 ) {
                    return 'big';
                } else {
                    return 'small';
                }
            }
        }
    }
    return 'no';
}

function get_prev_top_users($prev_users_collection) {
    $result = array();
    $cursor = $prev_users_collection->find()->limit(3);
    foreach($cursor as $key=>$value) {
        $result[] = $value;
    }
    return $result;
}

function get_event_state_for_user($event_info, $event_collection, $users_collection, $prev_users_collection,  $user_id, $avatar, $name, $level) {
    
    $result = array();
    $event_info['is_event_expired'] = is_event_expired($event_info);
    $event_info['is_event_started'] = is_event_started($event_info);
    $event_info['is_dragon_killed'] = is_dragon_killed($event_info);
    $event_info['is_dragon_alive'] = is_dragon_alive($event_info);
    
    $event_info['time'] = time();
    $result['event'] = $event_info;
    $user_obj = $users_collection->findOne(array('id' => $user_id));
    if($user_obj) {
        $result['user'] = $user_obj;
    } else {
        $result['user'] = add_stones($event_info, $event_collection, $users_collection, $user_id, $avatar, $name, $level, 0);
    }
    
    $prev_user = $prev_users_collection->findOne(array('id' => $user_id));
    if($prev_user) {
        $result['prev_user'] = $prev_user;
    }
    
    $result['prev_top_users'] = get_prev_top_users($prev_users_collection);
    return $result;
}

function is_event_started($info_obj) {
    return (time() < intval($info_obj['end_time']));
}

function is_event_expired($info_obj) {
    return (time() >= intval($info_obj['end_time']));
}

function is_dragon_killed($info_obj) {
    return intval($info_obj['current_hp']) >= intval($info_obj['hp']);
}

function is_dragon_alive($info_obj) {
    return intval($info_obj['current_hp']) < intval($info_obj['hp']);
}

function is_event_not_rewarded($info_obj) {
    return (intval($info_obj['rewarded']) == 0);
}

function get_current_top_users($users_collection) {
    $result = array();
    $cursor = $users_collection->find();
    $cursor->sort(array('spended_stones' => -1));
    $i = 1;
    foreach ($cursor as $key=>$value) {
        $value['rank'] = $i;
        $i++;
        $result[] = $value;
        if($i > 3 ) {
            break;
        }
    }
    return $result;
}

function move_users_to_prev_collection($event_info, $event_collection, $users_collection, $prev_users_collection) {
    $cursor = $users_collection->find();
    $cursor->sort(array('spended_stones' => -1));
    $i = 1;
    $reward_count = $event_info['reward_count'];
    foreach($cursor as $key=>$value) {
        $value['rank'] = $i;
        $i++;
        if($value['spended_stones'] >= $reward_count) {
            $prev_users_collection->insert($value);
        } else {
            break;
        }
    }
}

function make_event_rewarded($event_info, $event_collection, $users_collection) {
    
    $event_info['rewarded'] = 1;
    $event_info['end_time'] = time() - 1;
    $event_collection->save($event_info);
    //$users_collection->remove();
}

function check_event($event_info, $event_collection, $users_collection, $prev_users_collection) {
    $check_time = 0;
    $event_info['time'] = time();
    if(isset($event_info['check_time'])) {
        $check_time = intval($event_info['check_time']);
    }
    
    $check_interval = time() - $check_time;
    if($check_interval >= 10 ) {
        $event_info['check_time'] = time();
        //echo 'checking...\n';
        
        if (is_event_expired($event_info)) {
                //echo 'event expired...\n';
                
                if (is_event_not_rewarded($event_info)) {
                    //echo 'event not rewarded...\n';
                    $prev_users_collection->remove();

                    move_users_to_prev_collection($event_info, $event_collection, $users_collection, $prev_users_collection);
                    make_event_rewarded($event_info, $event_collection, $users_collection);
                } else {
                    $event_collection->save($event_info);
                }
        } else {
            //echo 'event not expired...\n';
            
            if (is_event_not_rewarded($event_info)) {
                //echo 'event not rewarded...\n';
                
                if (is_dragon_killed($event_info)) {
                    //echo 'is dragon killed...=> move to prev collection...\n';
                    $prev_users_collection->remove();
                    move_users_to_prev_collection($event_info, $event_collection, $users_collection, $prev_users_collection);
                    make_event_rewarded($event_info, $event_collection, $users_collection);
                } else {
                    $event_collection->save($event_info);
                }
            } else {
                $event_collection->save($event_info);
            }
        } 
    } else {
        //echo 'check interval not ready';
    }
}


function handle_op($source) {
    $op_name = $source['op'];
    $client = new MongoClient();
    $db = $client->mc;
    $event_collection = $db->unicorn;
    $users_collection = $db->unicorn_users;
    $prev_users_collection = $db->prev_unicorn_users;
    
    switch ($op_name){
        case 'write_event': {
                $event_info = write_event_info($event_collection, intval($source['interval']), intval($source['reward_count']), intval($source['hp']));
                check_event($event_info, $event_collection, $users_collection, $prev_users_collection);
                echo json_encode($event_info);        
                break;
        }
        case 'start_event': {
                $event_info = start_event($event_collection, $users_collection , intval($source['interval']), 
                        intval($source['reward_count']), intval($source['hp']));
                check_event($event_info, $event_collection, $users_collection, $prev_users_collection);
                echo json_encode($event_info);           
            break;
        }
        case 'add_stones': {
                $event_info = $event_collection->findOne();
                check_event($event_info, $event_collection, $users_collection, $prev_users_collection);
                if(is_event_started($event_info) ) {
                    if(is_dragon_alive($event_info)) {
                        $user_obj = add_stones(
                                $event_info, $event_collection, $users_collection, 
                                $source['id'], $source['avatar'], 
                                $source['name'], intval($source['level']), intval($source['stones']));
                        echo json_encode($user_obj);
                    } else {
                        echo 'error: dragon already killed';
                    }
                } else {
                    echo 'error: event not started';
                }              
            break;
        }
        case 'make_hit': {
            //function make_hit($event_info, $event_collection, $users_collection, $user_id, $avatar, $name, $level, $stones)
            $event_info = $event_collection->findOne();
            check_event($event_info, $event_collection, $users_collection, $prev_users_collection);
            
            if(is_event_started($event_info)) {
                if(is_dragon_alive($event_info)) {
                    $user_obj = make_hit($event_info, $event_collection, $users_collection, 
                            $source['id'], $source['avatar'], 
                            $source['name'], intval($source['level']), 
                            intval($source['stones']));
                    if($user_obj) {
                        echo json_encode($user_obj);
                    } else {
                        echo 'error: user is NULL';
                    }
                } else {
                    echo 'error: dragon already killed';
                }
            } else {
                echo 'error: event not started';
            }
            
                break;
        }
        case 'get_info': {
            //function get_event_state_for_user($event_info, $event_collection, $users_collection, $prev_users_collection,  $user_id)
            $event_info = $event_collection->findOne();
            check_event($event_info, $event_collection, $users_collection, $prev_users_collection);
            $result = get_event_state_for_user($event_info, $event_collection, $users_collection, $prev_users_collection, $source['id'],
                    $source['avatar'], $source['name'], intval($source['level']));
            echo json_encode($result);
            break;
        }
        case 'take_reward': {
            //function take_reward($event_info, $prev_users_collection, $user_id ) {
            $event_info = $event_collection->findOne();
            check_event($event_info, $event_collection, $users_collection, $prev_users_collection);
            echo take_reward($event_info, $prev_users_collection, $source['id']);
                break;
        }
        case 'get_current_top': {
            $result = get_current_top_users($users_collection);
            echo json_encode($result);
                break;
        }
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

