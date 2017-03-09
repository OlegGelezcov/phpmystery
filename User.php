<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class User {
    public $id = '';
    public $name = '';
    public $avatar = '';
    public $level = 1;
    
    function to_document() {
        $document = array('id' => $this->id, 'name' => $this->name, 'avatar' => $this->avatar, 'level' => $this->level);
        return $document;
    }
    
    function parse_document($document) {
        if(isset($document['id'])) {
            $this->id = $document['id'];
        }
        if(isset($document['name'])) {
            $this->name = $document['name'];
        }
        if(isset($document['avatar'])) {
            $this->avatar = $document['avatar'];
        }
        if(isset($document['level'])) {
            $this->level = intval($document['level']);
        }
    }
}


?>