<?php

namespace Socialcast\Auth;

use Exception;
use Sledgehammer\Curl;
use Sledgehammer\Json;

/**
 * OAuth authentication
 */
class OAuth extends AbstractAuth {

    protected $appId;
    protected $appSecret;
    protected $callbackUrl;
    protected $readCallback;
    protected $writeCallback;

    /**
     * @var object
     */
    protected $token;

    /**
     *
     * @param string $subdomain
     * @param string $appId
     * @param string $appSecret
     * @param string $callbackUrl
     * @param callback $readCallback Callback that reads the token from the session
     * @param callback $writeCallback Callback to write the token to the session
     */
    public function __construct($subdomain, $appId, $appSecret, $callbackUrl, $readCallback, $writeCallback) {
        parent::__construct($subdomain);
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->callbackUrl = $callbackUrl;
        if (is_callable($readCallback) === false) {
            throw new Exception('readCallback must be a valid ');
        }
        $this->readCallback = $readCallback;
        if (is_callable($writeCallback) === false) {
            throw new Exception('writeCallback must be a valid ');
        }
        $this->writeCallback = $writeCallback;
        $this->readToken();
    }

    /**
     * @param bool $new Force a new login (instead of a connect)
     * @return string
     */
    public function getLoginUrl($new = false) {
        $pathSuffix = $new ? '/new' : '';
        return 'https://' . $this->subdomain . '.socialcast.com/oauth2/authorization' . $pathSuffix . '?response_type=code&client_id=' . urlencode($this->appId) . '&redirect_uri=' . urlencode($this->callbackUrl);
    }

    /**
     * Retrieves the AccessToken using the code and stores it in the session
     * @param $code
     */
    public function authenticate($code = null) {
        if ($code === null) {
            $code = $_GET['code'];
        }
        $url = 'https://' . $this->subdomain . '.socialcast.com/oauth2/token?grant_type=authorization_code&code=' . urlencode($code) . '&redirect_uri=' . urlencode($this->callbackUrl);
        $request = Curl::post($url, array('client_id' => $this->appId, 'client_secret' => $this->appSecret));
        $this->token = Json::decode($request->getBody());
        $this->token->expires_at = (time() + $this->token->expires_in) - 30;
        $this->storeToken();
    }

    public function isAuthenticated() {
        return ($this->token !== null);
    }

    /**
     * @inheritdoc
     */
    public function sign($options) {
        if (!$this->token) {
            return $options;
        }
        if ($this->token->expires_at && $this->token->expires_at < time()) {
            $this->refreshToken();
        }
        if (isset($options[CURLOPT_HTTPHEADER]) === false) {
            $options[CURLOPT_HTTPHEADER] = array();
        }
        $options[CURLOPT_HTTPHEADER][] = 'Authorization: Bearer ' . $this->token->access_token;
        return $options;
    }

    protected function refreshToken() {
        $url = 'https://' . $this->subdomain . '.socialcast.com/oauth2/token?grant_type=refresh_token&refresh_token=' . urlencode($this->token->refresh_token);
        $request = Curl::post($url, array('client_id' => $this->appId, 'client_secret' => $this->appSecret));
        $this->token = Json::decode($request->getBody());
        $this->token->expires_at = time() + $this->token->expires_in - 30;
        $this->storeToken();
    }

    private function readToken() {
        $token = call_user_func($this->readCallback);
        if (is_string($token)) {
            $this->token = unserialize($token);
        }
    }

    private function storeToken() {
        call_user_func($this->writeCallback, serialize($this->token));
    }

}
