<?php

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Accept, X-Access-Token, X-Application-Name, X-Request-Sent-Time");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Origin: *");

    function read_wheel($collection) {
        $find_obj = $collection->findOne();
        if($find_obj) {
            return $find_obj['wheel'];
        }
        return NULL;
    }
    
    
    function process() {
        
        switch($_POST['op']) {
            
            case 'getwheel': 
                $connection = new MongoClient();
                $db = $connection->mc;
                $collection = $db->wheel;
                $result = read_wheel($collection);
                if($result != NULL ) {
                    $json = json_encode($result);
                    echo $json;
                } else {
                    echo '';
                }
                break;
        }
        
    }
    
    process();
    
?>