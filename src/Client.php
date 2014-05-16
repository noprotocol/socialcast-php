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
            $request[CURLOPT_POSTFIELDS] = $data;
            foreach ($data as $value) {
                if (is_array($value)) {
                    $request[CURLOPT_POSTFIELDS] = http_build_query($data);
                    break;
                }
            }
        }
        $request = $this->auth->sign($request);
        $response = new Curl($request);
        try {
            $responseBody = $response->getBody();
            if ($response->http_code < 400) {
                return Json::decode($responseBody);
            }
            if ($response->http_code == 401) {
                throw new Exception('[Socialcast] Invalid credentials, 401 '.Framework::$statusCodes[401]);
            }
            $message = @Framework::$statusCodes[$response->http_code];
            throw new Exception('[Socialcast] ' . $response->http_code . ' ' . $message);
        } catch (Exception $e) {
            throw $e;
        } finally {
            $this->logger->append($path, array(
                'duration' => microtime(true) - $start,
                'url' => $request[CURLOPT_URL],
                'method' => $method,
                'response' => array(
                    'length' => isset($responseBody) ? strlen($responseBody) : ''
            )));
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

    function renderEntry($entry, $meta) {
        echo '<td>', $meta['method'], '</td>';
        echo '<td>', Html::element('a', array('href' => $meta['url'], 'target' => '_blank'), $entry), '</td>';
        echo '<td class="logentry-number">', number_format($meta['response']['length'] / 1024, 2), 'KiB</td>';
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
     * @param int $userId
     * @return \Socialcast\Resource\User
     */
    public function getUser($userId) {
        // ** GENERATED CODE **
        return new \Socialcast\Resource\User($this, false, 'users/'.$userId);
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
     * @param int $userId
     */
    public function deleteUser($userId) {
        // ** GENERATED CODE **
        $this->delete('users/'.$userId);
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
     * @param int $groupId
     * @return \Socialcast\Resource\Group
     */
    public function getGroup($groupId) {
        // ** GENERATED CODE **
        return new \Socialcast\Resource\Group($this, false, 'groups/'.$groupId);
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
     * @param int $groupId
     */
    public function deleteGroup($groupId) {
        // ** GENERATED CODE **
        $this->delete('groups/'.$groupId);
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
     * @param int $messageId
     * @return \Socialcast\Resource\Message
     */
    public function getMessage($messageId) {
        // ** GENERATED CODE **
        return new \Socialcast\Resource\Message($this, false, 'messages/'.$messageId);
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
     * @param int $messageId
     */
    public function deleteMessage($messageId) {
        // ** GENERATED CODE **
        $this->delete('messages/'.$messageId);
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
     * @param int $conversationId
     * @return \Socialcast\Resource\Conversation
     */
    public function getConversation($conversationId) {
        // ** GENERATED CODE **
        return new \Socialcast\Resource\Conversation($this, false, 'conversations/'.$conversationId);
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
     * @param int $pollId
     * @return \Socialcast\Resource\Poll
     */
    public function getPoll($pollId) {
        // ** GENERATED CODE **
        return new \Socialcast\Resource\Poll($this, false, 'polls/'.$pollId);
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
     * @param int $badgeId
     * @return \Socialcast\Resource\Badge
     */
    public function getBadge($badgeId) {
        // ** GENERATED CODE **
        return new \Socialcast\Resource\Badge($this, false, 'badges/'.$badgeId);
    }

}