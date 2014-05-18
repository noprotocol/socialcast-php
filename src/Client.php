<?php

namespace Socialcast;

use Sledgehammer\Curl;
use Sledgehammer\Framework;
use Sledgehammer\Html;
use Sledgehammer\InfoException;
use Sledgehammer\Json;
use Sledgehammer\Logger;
use Sledgehammer\Object;
use Sledgehammer\PropertyPath;
use Socialcast\Auth\AbstractAuth;
use Socialcast\Resource\Badge;
use Socialcast\Resource\Category;
use Socialcast\Resource\ContentFilter;
use Socialcast\Resource\Conversation;
use Socialcast\Resource\Group;
use Socialcast\Resource\GroupMembership;
use Socialcast\Resource\Message;
use Socialcast\Resource\Poll;
use Socialcast\Resource\Resource;
use Socialcast\Resource\Stream;
use Socialcast\Resource\User;
use stdClass;
use Exception;

/**
 * @link http://developers.socialcast.com/api-documentation/
 *
 */
class Client extends Object {

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var AbstractAuth
     */
    protected $auth;

    /**
     *
     * @param AbstractAuth $auth
     * @param Logger $logger
     */
    public function __construct($auth, $logger = null) {
        $this->auth = $auth;
        $this->logger = $logger;
        if ($logger === null) {
            $this->logger = new Logger(array(
                'identifier' => 'Socialcast',
                'singular' => 'request',
                'plural' => 'requests',
                'renderer' => array($this, 'renderEntry'),
                'columns' => array('Method', 'Path', 'Size', 'Duration')
            ));
        }
    }

    /**
     * Perform a GET api call.
     * @param string $path Example: 'userinfo' or 'users/123/followers'
     * @param array $parameters The GET parameters Example: ['q' => 'test'] add `?q=test` to the url.
     */
    public function get($path, $parameters = array()) {
        return $this->api('GET', $path, $parameters);
    }

    public function post($path, $data) {
        return $this->api('POST', $path, array(), $data);
    }

    public function put($path, $data) {
        return $this->api('PUT', $path, array(), $data);
    }

    public function delete($path) {
        return $this->api('DELETE', $path);
    }

    /**
     * Perform an API call.
     *
     * @param string $method HTTP method: GET,POST,PUT or DELETE
     * @param string $path
     * @param array $parameters GET parameters
     * @param mixed $data POST/PUT data
     * @return stdClass
     */
    public function api($method, $path, $parameters = array(), $data = null) {
        $start = microtime(true);
        $request = Curl::$defaults;
        $request[CURLOPT_URL] = $this->buildUrl($path, $parameters);
        $request[CURLOPT_FAILONERROR] = false;
        switch ($method) {
            case 'GET':
                break;
            case 'POST':
                $request[CURLOPT_POST] = true;
                break;
            case 'PUT':
                $request[CURLOPT_CUSTOMREQUEST] = 'PUT';
                break;
            case 'DELETE':
                $request[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                break;
        }
        if ($data !== null) {
            $request[CURLOPT_POSTFIELDS] = $this->postFields($data);
        }
        $request = $this->auth->sign($request);
        $response = new Curl($request);
        try {
            $responseBody = $response->getBody();
            if ($response->http_code < 400) {
                $this->logger->append($path, array(
                    'duration' => microtime(true) - $start,
                    'url' => $request[CURLOPT_URL],
                    'method' => $method,
                    'response' => array(
                        'length' => strlen($responseBody)
                    ),
                    'success' => true
                ));
                return Json::decode($responseBody);
            }
            if ($response->http_code == 401) {
                throw new Exception('[Socialcast] Invalid credentials, 401 '.Framework::$statusCodes[401]);
            }
            $message = @Framework::$statusCodes[$response->http_code];
            throw new Exception('[Socialcast] ' . $response->http_code . ' ' . $message);
        } catch (Exception $e) {
            $this->logger->append($path, array(
                'duration' => microtime(true) - $start,
                'url' => $request[CURLOPT_URL],
                'method' => $method,
                'success' => false
            ));
            throw $e;
        }
    }

    /**
     * Override to authentication the requests.
     * @return array
     */
    protected function curlOptions() {
        return array();
    }

    private function buildUrl($path, $parameters = array()) {
        if (count($parameters) === 0) {
            $suffix = '';
        } else {
            $suffix = '?' . http_build_query($parameters);
        }
        return 'https://' . $this->auth->subdomain . '.socialcast.com/api/' . $path . '.json' . $suffix;
    }

    /**
     * Process post data, allows for file entries in nested arrays.
     */
    private function postFields(&$data, $prefix = false) {
        if (is_array($data) === false) {
            return $data;
        }
        $postFields = array();
        foreach ($data as $key => $value) {
            if ($prefix) {
                $key = $prefix.'['.$key.']';
            }
            if (is_array($value) === false) {
                $postFields[$key] = $value;
            } else {
                $postFields = array_merge($postFields, $this->postFields($value, $key));
            }
        }
        return $postFields;
    }

    function renderEntry($entry, $meta) {
        echo '<td>', $meta['method'], '</td>';
        $link = array('href' => $meta['url'], 'target' => '_blank');
        if (!$meta['success']) {
            $link['class'] = 'logentry-alert';
        }
        echo '<td>', Html::element('a', $link, $entry), '</td>';
        echo '<td class="logentry-number">';
        if (isset($meta['response']['length'])) {
             echo number_format($meta['response']['length'] / 1024, 2), 'KiB';
        }
        echo '</td>';
        $duration = $meta['duration'];
        if ($duration > 3) {
            $color = 'logentry-alert';
        } elseif ($duration > 2) {
            $color = 'logentry-warning';
        } else {
            $color = 'logentry-debug';
        }
        echo '<td class="logentry-number ', $color, '"><b>', \Sledgehammer\format_parsetime($duration), '</b>&nbsp;sec</td>';
    }

    //*****************************************
    //
    //          GENERATED METHODS
    //
    // Update code using CodeGenerator::run();
    //*****************************************

    /**
     * Create an Attachment
     *
     * @param array $attachment
     * @return \Socialcast\Resource
     */
    public function postAttachment($attachment) {
        // ** GENERATED CODE **
        $response = $this->post('attachment', $attachment);
        return new \Socialcast\Resource\Attachment($this, $response);
    }

    /**
     * View Authenticated User Profile
     *
     * @param array [$parameter]  Request parameters
     * @return \Socialcast\Resource\User
     */
    public function getUserinfo($parameters = array()) {
        // ** GENERATED CODE **
        return new \Socialcast\Resource\User($this, false, 'userinfo', $parameters);
    }

    /**
     * View User Profile
     *
     * @param int $id
     * @return \Socialcast\Resource\User
     */
    public function getUser($id) {
        // ** GENERATED CODE **
        return new \Socialcast\Resource\User($this, (object) array('id' => $id), 'users/'.$id);
    }

    /**
     * Search Users in Your Company
     *
     * @param string $querystring  Search query string
     * @param array [$parameter]  Request parameters
     * @return \Socialcast\Resource\User[]
     */
    public function searchUsers($querystring, $parameters = array()) {
        // ** GENERATED CODE **
        $parameters['q'] = $querystring;
        return \Socialcast\Resource\User::all($this, 'users/search', $parameters);
    }

    /**
     * List Users in Your Company
     *
     * @param array [$parameter]  Request parameters
     * @return \Socialcast\Resource\User[]
     */
    public function getUsers($parameters = array()) {
        // ** GENERATED CODE **
        return \Socialcast\Resource\User::all($this, 'users', $parameters);
    }

    /**
     * Deactivate a User
     *
     * @param int $id
     */
    public function deleteUser($id) {
        // ** GENERATED CODE **
        $this->delete('users/'.$id);
    }

    /**
     * Listing Group Memberships
     *
     * @param array [$parameter]  Request parameters
     * @return \Socialcast\Resource\GroupMembership[]
     */
    public function getGroupMemberships($parameters = array()) {
        // ** GENERATED CODE **
        return \Socialcast\Resource\GroupMembership::all($this, 'group_memberships', $parameters);
    }

    /**
     * Listing All Groups
     *
     * @param array [$parameter]  Request parameters
     * @return \Socialcast\Resource\Group[]
     */
    public function getGroups($parameters = array()) {
        // ** GENERATED CODE **
        return \Socialcast\Resource\Group::all($this, 'groups', $parameters);
    }

    /**
     * Show a Single Group
     *
     * @param int $id
     * @return \Socialcast\Resource\Group
     */
    public function getGroup($id) {
        // ** GENERATED CODE **
        return new \Socialcast\Resource\Group($this, (object) array('id' => $id), 'groups/'.$id);
    }

    /**
     * Create a Group
     *
     * @param array $group
     * @return \Socialcast\Resource
     */
    public function postGroup($group) {
        // ** GENERATED CODE **
        $response = $this->post('groups', $group);
        return new \Socialcast\Resource\Group($this, $response);
    }

    /**
     * Destroy an Archived Message
     *
     * @param int $id
     */
    public function deleteGroup($id) {
        // ** GENERATED CODE **
        $this->delete('groups/'.$id);
    }

    /**
     * Reading Stream Messages
     *
     * @param array [$parameter]  Request parameters
     * @return \Socialcast\Resource\Message[]
     */
    public function getMessages($parameters = array()) {
        // ** GENERATED CODE **
        return \Socialcast\Resource\Message::all($this, 'messages', $parameters);
    }

    /**
     * Read a Single Stream Message
     *
     * @param int $id
     * @return \Socialcast\Resource\Message
     */
    public function getMessage($id) {
        // ** GENERATED CODE **
        return new \Socialcast\Resource\Message($this, (object) array('id' => $id), 'messages/'.$id);
    }

    /**
     * Searching Messages
     *
     * @param string $querystring  Search query string
     * @param array [$parameter]  Request parameters
     * @return \Socialcast\Resource\Message[]
     */
    public function searchMessages($querystring, $parameters = array()) {
        // ** GENERATED CODE **
        $parameters['q'] = $querystring;
        return \Socialcast\Resource\Message::all($this, 'messages/search', $parameters);
    }

    /**
     * Creating New Messages
     *
     * @param array $message
     * @return \Socialcast\Resource
     */
    public function postMessage($message) {
        // ** GENERATED CODE **
        $response = $this->post('messages', array('message' => $message));
        return new \Socialcast\Resource\Message($this, $response);
    }

    /**
     * Destroy an existing message
     *
     * @param int $id
     */
    public function deleteMessage($id) {
        // ** GENERATED CODE **
        $this->delete('messages/'.$id);
    }

    /**
     * Listing Tenant Content Filters
     *
     * @param array [$parameter]  Request parameters
     * @return \Socialcast\Resource\ContentFilter[]
     */
    public function getContentFilters($parameters = array()) {
        // ** GENERATED CODE **
        return \Socialcast\Resource\ContentFilter::all($this, 'content_filters', $parameters);
    }

    /**
     * Returns information of the referenced conversation
     *
     * @param int $id
     * @return \Socialcast\Resource\Conversation
     */
    public function getConversation($id) {
        // ** GENERATED CODE **
        return new \Socialcast\Resource\Conversation($this, (object) array('id' => $id), 'conversations/'.$id);
    }

    /**
     * List all conversations that a user has access to
     *
     * @param array [$parameter]  Request parameters
     * @return \Socialcast\Resource\Conversation[]
     */
    public function getConversations($parameters = array()) {
        // ** GENERATED CODE **
        return \Socialcast\Resource\Conversation::all($this, 'conversations', $parameters);
    }

    /**
     * Create a new conversation
     *
     * @param array $conversation
     * @return \Socialcast\Resource
     */
    public function postConversation($conversation) {
        // ** GENERATED CODE **
        $response = $this->post('conversations', $conversation);
        return new \Socialcast\Resource\Conversation($this, $response);
    }

    /**
     * Listing Tenant Categories
     *
     * @param array [$parameter]  Request parameters
     * @return \Socialcast\Resource\Category[]
     */
    public function getCategories($parameters = array()) {
        // ** GENERATED CODE **
        return \Socialcast\Resource\Category::all($this, 'categories', $parameters);
    }

    /**
     * Listing User’s Streams
     *
     * @param array [$parameter]  Request parameters
     * @return \Socialcast\Resource\Stream[]
     */
    public function getStreams($parameters = array()) {
        // ** GENERATED CODE **
        return \Socialcast\Resource\Stream::all($this, 'streams', $parameters);
    }

    /**
     * View Poll Data
     *
     * @param int $id
     * @return \Socialcast\Resource\Poll
     */
    public function getPoll($id) {
        // ** GENERATED CODE **
        return new \Socialcast\Resource\Poll($this, (object) array('id' => $id), 'polls/'.$id);
    }

    /**
     * Create a poll
     *
     * @param array $poll
     * @return \Socialcast\Resource
     */
    public function postPoll($poll) {
        // ** GENERATED CODE **
        $response = $this->post('polls', $poll);
        return new \Socialcast\Resource\Poll($this, $response);
    }

    /**
     * Create Thanks
     *
     * @param array $thank
     * @return \Socialcast\Resource
     */
    public function postThank($thank) {
        // ** GENERATED CODE **
        $response = $this->post('thanks', $thank);
        return new \Socialcast\Resource\Thank($this, $response);
    }

    /**
     * Get list of Thanks Badges
     *
     * @param array [$parameter]  Request parameters
     * @return \Socialcast\Resource\Badge[]
     */
    public function getBadges($parameters = array()) {
        // ** GENERATED CODE **
        return \Socialcast\Resource\Badge::all($this, 'badges', $parameters);
    }

    /**
     * Get a specific badge
     *
     * @param int $id
     * @return \Socialcast\Resource\Badge
     */
    public function getBadge($id) {
        // ** GENERATED CODE **
        return new \Socialcast\Resource\Badge($this, (object) array('id' => $id), 'badges/'.$id);
    }
}