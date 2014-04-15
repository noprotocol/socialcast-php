<?php
namespace Socialcast;

use Exception;
use Sledgehammer\Curl;
use Sledgehammer\Framework;
use Sledgehammer\Json;
use Sledgehammer\Object;
use Socialcast\Resource\User;

/**
 * @link http://developers.socialcast.com/api-documentation/
 */
abstract class Client extends Object {

    protected $subdomain;

    public function __construct($subdomain = 'api') {
        $this->subdomain = $subdomain;
    }

    /**
     * Current logged in user.
     * @return User
     */
    public function getUserinfo() {
        return new User($this, $this->get('/userinfo'));
    }

    /**
     * @return User
     */
    public function getUser($id) {
        return new User($this, $this->get('/users/'.$id));
    }

    /**
     * @return User[]
     */
    public function getUsers() {
        return $this->getList('users', 'Socialcast\Resource\User');
    }

    /**
     * @return Message
     */
    public function getMessage($id) {
        return new Message($this, $this->get('/messages/'.$id));
    }

    /**
     * @return Message[]
     */
    public function getMessages() {
        return $this->getList('messages', 'Socialcast\Resource\Message');
    }

    /**
     *
     * @param string $path
     * @param string $class
     * @return \Socialcast\class
     * @throws Exception
     */
    public function getList($path, $class) {
        $response = $this->get($path);
        if (is_object($response) && count(get_object_vars($response)) === 1) { // Response is wrapped with a rootNode?
            $response = current($response); // unwrap
        }
        if (is_array($response) === false) {
            throw new Exception('Response is not an array');
        }
        $list = array();
        foreach ($response as $item) {
            $list[] = new $class($this, $item);
        }
        return $list;
    }

    /**
     * Perform a GET api call.
     * @param string $path Example: 'userinfo', 'users/USER_ID/followers'
     */
    public function get($path, $class = 'Socialcast\Resource') {
        $request = Curl::get($this->buildUrl($path), $this->curlOptions());
        return $this->processRequest($request);
    }

    public function post($path, $data) {
        $request = Curl::post($this->buildUrl($path), $data, $this->curlOptions());
        return $this->processRequest($request);
    }

    /**
     * Override to authentication the requests.
     * @return array
     */
    protected function curlOptions() {
        return array();
    }

    private function buildUrl($path) {
        return 'https://' . $this->subdomain . '.socialcast.com/api/' . $path . '.json';
    }

    /**
     * @param Curl $request
     */
    private function processRequest($request) {
        if ($request->http_code < 400) {
            return Json::decode($request->getBody());
        }
        if ($request->http_code == 401) {
            throw new Exception('Invalid credentials');
        }
        $message = @Framework::$statusCodes[$request->http_code];
        throw new Exception('Socialcast error: ' . $request->http_code . ' ' . $message);
    }

}
