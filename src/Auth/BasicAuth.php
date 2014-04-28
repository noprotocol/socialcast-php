<?php

namespace Socialcast\Auth;

/**
 * Socialcast Client using HTTP Basic Authentication.
 *
 * @link http://developers.socialcast.com/api-documentation/http-basic-authentication/
 */
class BasicAuth extends AbstractAuth {

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     *
     * @param string $subdomain
     * @param string $username
     * @param string $password
     */
    public function __construct($subdomain = 'api', $username = null, $password = null) {
        parent::__construct($subdomain);
        $this->username = $username;
        $this->password = $password;
    }

    public function sign($request) {
        $request[CURLOPT_USERPWD] = $this->username . ':' . $this->password;
        return $request;
    }

}
