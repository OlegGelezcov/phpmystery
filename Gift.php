<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once 'User.php';

class Gift {
    public $gift_id = '';
    public $sender = null;
    public $item_type = 0;
    public $item_id = '';
    
    function to_document() {
        return array('gift_id' => $this->gift_id, 'sender' => $this->sender->to_document(), 'item_type' => $this->item_type, 'item_id' => $this->item_id);
    }
}

class GiftCollection {
    public  $id = '';
    public $gifts = [];
    
    function to_document() {
        $gdocs = [];
        foreach($this->gifts as $g ) {
            $gdocs[] = $g->to_document();
        }
        return array('id' => $this->id, 'gifts' => $gdocs);
    }
}

?>