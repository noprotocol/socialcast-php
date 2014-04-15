<?php

namespace Socialcast\Client;

use Socialcast\Client;

/**
 * Socialcast Client using HTTP Basic Authentication.
 *
 * @link http://developers.socialcast.com/api-documentation/http-basic-authentication/
 */
class BasicAuth extends Client {

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
        $this->username = $username;
        $this->password = $password;
        parent::__construct($subdomain);
    }

    protected function curlOptions() {
        return array(
            CURLOPT_USERPWD => $this->username . ':' . $this->password
        );
    }

}
