<?php

namespace Socialcast\Auth;

use Sledgehammer\Object;


abstract class AbstractAuth extends Object {

    /**
     * @var string  The subdomain for the community.
     */
    public $subdomain;


    /**
     * @param string $subdomain The community subdomain
     */
    public function __construct($subdomain = 'api') {
        $this->subdomain = $subdomain;
    }

    /**
     * Sign the request.
     *
     * @param array $request The CURL_OPT options array for the request.
     * @return array Modified options array, including the authentication token.
     */
    abstract public function sign($request);


}
