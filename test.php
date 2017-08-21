<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/*
$m = new MongoClient();
$db = $m->comedy;
$collection = $db->cartoons;
$document = array("title" => "Calvin and Hobbes", "author" => "Bill Watterson");
$collection->insert($document);

$document = array("title" => "XKCD", "online" => true);
$collection->insert($document);

$cursor = $collection->find();

foreach($cursor as $document) {
    echo $document["title"] . "\n";
}
*/

/*
$connection = new MongoClient();
$collection = $connection->database->collectionName;
$new_collection = $connection->database->newCollectionName;
*/

/*
for($i = 0; $i < 100; $i++ ) {
    $collection->insert(array('i' => $i, "field{$i}" => $i * 2));
}*/

//echo $collection->count();

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

//echo time();

$test_arr = array('a' => 1, 'b' => 2);
$test_arr['c'] = 3;
var_dump($test_arr);
$test_arr['a'] = 333;
var_dump($test_arr);

?>