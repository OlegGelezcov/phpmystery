<?php

$challenge_ids = array('kill_monster', 'got_exp', 'complete_room',
    'collect_silver', 'collect_gold', 'charge_collections', 'use_energy', 'complete_quest',
    'use_tool', 'use_bonus', 'make_purchase', 'roll');
$tools = array('T0001', 'T0002', 'T0005', 'T0006', 'T0008', 'T0009');
$bonuses = array('B00001', 'B00002', 'B00003', 'B00004', 'B00005',
    'B00006', 'B00007', 'B00008', 'B00009');
$rooms = array('r1', 'r2', 'r3', 'r4', 'r5', 'r6', 'r7', 'r8', 'r9', 'r17', 'r18', 'r19', 'r20');




//Check the current challenge completed, if yes - move top users to 
//prev challenge collection, clear current challenfge collection
//and start new challenge
function check_for_start_new_challenge(
        $challenge_info_collection, 
        $current_challenge_collection, 
        $prev_challenge_collection) {
    
    $info_obj = $challenge_info_collection->findOne();
    
    if(!$info_obj) {
        $new_challenge_info = array('id');
    } else {
        
    }
}
?>