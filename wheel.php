<?php
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Accept, X-Access-Token, X-Application-Name, X-Request-Sent-Time");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Origin: *");

    $connection = new MongoClient();
    $db = $connection->mc;
    $collection = $db->wheel;
    
    function write_wheel($collection, $rewards) {
        $find_obj = $collection->findOne();
        if($find_obj) {
            $find_obj['wheel'] = $rewards;
            $collection->save($find_obj);
        } else {
            $newobj = array('wheel' => $rewards);
            $collection->save($newobj);
        }
    }
    
    function read_wheel($collection) {
        $find_obj = $collection->findOne();
        if($find_obj) {
            return $find_obj['wheel'];
        }
        return NULL;
    }
    
    function test_write($collection) {
        $c1 = array('id' => 'C00001', 'type' => 'Consumable', 'count' => 1);
        $c2 = array('id' => 'C00002', 'type' => 'Consumable', 'count' => 2);
        $c3 = array('id' => 'C00003', 'type' => 'Consumable', 'count' => 3);
        $c4 = array('id' => 'C00004', 'type' => 'Consumable', 'count' => 4);
        $c5 = array('id' => 'C00005', 'type' => 'Consumable', 'count' => 5);
        $c6 = array('id' => 'C00006', 'type' => 'Consumable', 'count' => 6);
        $c7 = array('id' => 'C00007', 'type' => 'Consumable', 'count' => 7);
        $c8 = array('id' => 'W0001', 'type' => 'Consumable', 'count' => 8);
        
        $total = array();
        $total[] = $c1;
        $total[] = $c2;
        $total[] = $c3;
        $total[] = $c4;
        $total[] = $c5;
        $total[] = $c6;
        $total[] = $c7;
        $total[] = $c8;
        
        write_wheel($collection, $total);
        var_dump($total);
        
    }
    
    function test_read($collection) {
        $findobj = read_wheel($collection);
        if($findobj) {
            var_dump($findobj);
        }else {
            echo 'empty';
        }
    }
    
    
    function start_html() {
        echo '<html>
    <head>
        <title>TODO supply a title</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>';
    }
    
    function end_html() {
        echo '    </body>
</html>';
        
    }
    
    function display_current_wheel($collection) {
        echo '<table><thead><tr>';
        echo '<th>Index</th>';
        echo '<th>ID</th>';
        echo '<th>Item Type</th>';
        echo '<th>Count</th>';
        echo '</tr></thead><tbody>';
        $findobj = read_wheel($collection);
        
        if($findobj) {
            for($i = 0; $i < 8; $i++ ) {
                $rowarr = $findobj[$i];
                if($rowarr) {
                    echo '<tr>';
                    echo '<td>';
                    echo $i;
                    echo '</td>';
                    echo '<td>';
                    echo $rowarr['id'];
                    echo '</td>';
                    echo '<td>';
                    echo $rowarr['type'];
                    echo '</td>';
                    echo '<td>';
                    echo $rowarr['count'];
                    echo '</td>';
                    echo '</tr>';
                }
            }
        }
        echo '</tbody></table>';     
    }
    
    function make_select_option($name) {
        echo "<select name='" . $name . "'>";
        echo '<option value="Charger">Charger</option>';
        echo '<option value="Consumable">Consumable</option>';
        echo '<option value="Tools">Tools</option>';
        echo '<option value="Bonuses">Bonuses</option>';
        echo '<option value="Weapon">Weapon</option>';
        echo '<option value="Stuff">Stuff</option>';
        echo '<option value="Chest">Chest</option>';
        echo '</select>';
    }
    function input_form($collection) {
        echo '<form action="wheel.php" method="post">';
        
        echo 'ID0: <input type="text" name="id0" />';
        make_select_option('type0');
        echo 'Count: <input type="text" name="count0" /><br/>';
        
        echo 'ID1: <input type="text" name="id1" />';
        make_select_option('type1');
        echo 'Count: <input type="text" name="count1" /><br/>';
        
        echo 'ID2: <input type="text" name="id2" />';
        make_select_option('type2');
        echo 'Count: <input type="text" name="count2" /><br/>';
        
        echo 'ID3: <input type="text" name="id3" />';
        make_select_option('type3');
        echo 'Count: <input type="text" name="count3" /><br/>';
        
        echo 'ID4: <input type="text" name="id4" />';
        make_select_option('type4');
        echo 'Count: <input type="text" name="count4" /><br/>';
        
        echo 'ID5: <input type="text" name="id5" />';
        make_select_option('type5');
        echo 'Count: <input type="text" name="count5" /><br/>';
        
        echo 'ID6: <input type="text" name="id6" />';
        make_select_option('type6');
        echo 'Count: <input type="text" name="count6" /><br/>';
        
        echo 'ID7: <input type="text" name="id7" />';
        make_select_option('type7');
        echo 'Count: <input type="text" name="count7" /><br/>';
        
        echo '<input type="submit" name="submit" value="Write Rewards!" /> </form>';
        
    }
    //test_write($collection);
    //test_read($collection);
    
    function get_data_for_index($index) {
        $id_key = 'id' . $index;
        $type_key = 'type' . $index;
        $count_key = 'count' . $index;
        
        if(isset($_POST[$id_key]) && isset($_POST[$type_key]) && isset($_POST[$count_key])) {
            return array('id' => $_POST[$id_key], 'type' => $_POST[$type_key], 'count' => intval($_POST[$count_key]));
        }
        return array();
    }
    
    
    if(isset($_POST['id0']) && !empty($_POST['id0'])) {
        $valid = TRUE;
        $c0 = get_data_for_index(0);
        
        if(count($c0) != 3 ) {
            echo 'invalid data at row 0<br/>';
            $valid = FALSE;
        }
        
        $c1 = get_data_for_index(1);
        if(count($c1) != 3 ) {
            echo 'invalid data at row 1<br/>';
            $valid = FALSE;
        }
        
        $c2 = get_data_for_index(2);
        if(count($c2) != 3 ) {
            echo 'invalid data at row 2<br/>';
            $valid = FALSE;
        }
        
        $c3 = get_data_for_index(3);
        if(count($c3) != 3 ) {
             echo 'invalid data at row 3<br/>';
            $valid = FALSE;           
        }
        
        $c4 = get_data_for_index(4);
        if(count($c4) != 3 ) {
              echo 'invalid data at row 4<br/>';
            $valid = FALSE;             
        }
        
        $c5 = get_data_for_index(5);
        if(count($c5) != 3 ) {
             echo 'invalid data at row 5<br/>';
            $valid = FALSE;            
        }
        
        $c6 = get_data_for_index(6);
        if(count($c6) != 3 ) {
            echo 'invalid data at row 6<br/>';
            $valid = FALSE;            
        }
        
        $c7 = get_data_for_index(7);
        if(count($c7) != 3 ) {
            echo 'invalid data at row 7<br/>';
            $valid = FALSE;              
        }
        
        if($valid) {
            $result = array();
            $result[] = $c0;
            $result[] = $c1;
            $result[] = $c2;
            $result[] = $c3;
            $result[] = $c4;
            $result[] = $c5;
            $result[] = $c6;
            $result[] = $c7;
            write_wheel($collection, $result);
            echo 'Data is written!<br/>';
        } else {
            echo 'error of data writing<br/>';
        }
    }
    
    
    start_html();
    display_current_wheel($collection);
    input_form($collection);
    end_html();
?>
