<?php
class Friend {
    public $id = '';
    public $friends = [];
    public $wishes = [];
    
    function to_document() {
        $parent = array('id' => $this->id, 'friends' => $this->friends, 'wishes' => $this->wishes);
        return $parent;
    }
    

    
    function try_add_friend($friend_id) {
        if(!in_array($friend_id, $this->friends)) {
            $this->friends[] = $friend_id;
            return TRUE;
        }
        return FALSE;
    }
}

?>

