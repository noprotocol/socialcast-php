<?php

namespace Socialcast\Client;

use Socialcast\Client;

/**
 * OAuth authentication
 *
 */
class OAuth extends Client {

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
     * @param string $appId
     * @param string $appSecret
     * @param string $callbackUrl
     * @param callback $readCallback Callback that reads the token from the session
     * @param callback $writeCallback Callback to write the token to the session
     */
    public function __construct($appId, $appSecret, $callbackUrl, $readCallback, $writeCallback) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->callbackUrl = $callbackUrl;
        $this->readCallback = $readCallback;
        $this->writeCallback = $writeCallback;
        $this->readToken();
    }

    /**
     * @param bool $new Force a new login (instead of a connect)
     * @return string
     */
    public function getAuthUrl($new = false) {
        $pathSuffix = $new ? '/new' : '';
        return 'https://' . $this->subdomain . '.socialcast.com/oauth2/authorization' . $pathSuffix . '?response_type=code&client_id=' . urlencode($this->appId) . '&redirect_uri=' . urlencode($this->callbackUrl);
    }

    /**
     * Retrieves the AccessToken using the code and stores it in the session
     * @param $code
     */
    public function attempt() {
        if (empty($_GET['code'])) {
            return false;
        }
        $url = 'https://' . $this->subdomain . '.socialcast.com/oauth2/token?grant_type=authorization_code&code=' . urlencode($_GET['code']) . '&redirect_uri=' . urlencode($this->callbackUrl);
        $request = Curl::post($url, array('client_id' => $this->appId, 'client_secret' => $this->appSecret));
        $this->token = Json::decode($request->getBody());
        $this->token->expires_at = (time() + $this->token->expires_in) - 30;
        $this->storeToken();
    }

    public function curlOptions() {
        if (!$this->accessToken) {
            return array();
        }
        if ($this->token->expires_at && $this->token->expires_at < time()) {
            $this->refreshToken();
        }
        return array(
            CURLOPT_HTTPHEADER => 'Authorization: Bearer ' . $this->token->access_token
        );
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
