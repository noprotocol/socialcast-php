<?php

namespace Socialcast\Resource;

class Group extends Resource {

    function getMessages($parameters = array()) {
        return Message::all($this->client, 'groups/'.$this->id.'/messages', $parameters);
    }

}
