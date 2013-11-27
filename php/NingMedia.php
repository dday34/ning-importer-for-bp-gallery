<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of BPMediaNingMedia
 *
 * @author damien
 */
class NingMedia{

    public $title;
    public $url;
    public $owner_id;
    public $description;
    public $type;

    function __construct($title, $url, $owner_id, $description, $type) {
        $this->title = $title;
        $this->url = $url;
        $this->owner_id = $owner_id;
        $this->description = $description;
        $this->type = $type;
    }

}
?>
