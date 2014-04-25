<?php

namespace Socialcast;

use Sledgehammer\Curl;
use Sledgehammer\Framework;
use Sledgehammer\InfoException;
use Sledgehammer\Json;
use Sledgehammer\Object;
use Sledgehammer\PropertyPath;
use Socialcast\Resource\Badge;
use Socialcast\Resource\Category;
use Socialcast\Resource\ContentFilter;
use Socialcast\Resource\Conversation;
use Socialcast\Resource\Group;
use Socialcast\Resource\GroupMembership;
use Socialcast\Resource\Message;
use Socialcast\Resource\Poll;
use Socialcast\Resource\Stream;
use Socialcast\Resource\User;

/**
 * @link http://developers.socialcast.com/api-documentation/
 *
 * @method Resource postAttachment($attachment)  Create an Attachment
 * @method User getUserinfo($parameters = array())  View Authenticated User Profile
 * @method User getUser($userId)  View User Profile
 * @method User[] searchUsers($querystring, $parameters = array())  Search Users in Your Company
 * @method User[] getUsers($parameters = array())  List Users in Your Company
 * @method Resource putUser($user)  Update User Profile
 * @method Resource deleteUser($userId)  Deactivate a User
 * @method GroupMembership[] getGroupMemberships($parameters = array())  Listing Group Memberships
 * @method Group[] getGroups($parameters = array())  Listing All Groups
 * @method Group getGroup($groupId)  Show a Single Group
 * @method Resource postGroup($group)  Create a Group
 * @method Resource putGroup($group)  Updating Existing Group
 * @method Resource deleteGroup($groupId)  Destroy an Archived Message
 * @method Message[] getMessages($parameters = array())  Reading Stream Messages
 * @method Message getMessage($messageId)  Read a Single Stream Message
 * @method Message[] searchMessages($querystring, $parameters = array())  Searching Messages
 * @method Resource postMessage($message)  Creating New Messages
 * @method Resource putMessage($message)  Updating Existing Messages
 * @method Resource deleteMessage($messageId)  Destroy an existing message
 * @method ContentFilter[] getContentFilters($parameters = array())  Listing Tenant Content Filters
 * @method Conversation getConversation($conversationId)  Returns information of the referenced conversation
 * @method Conversation[] getConversations($parameters = array())  List all conversations that a user has access to
 * @method Resource postConversation($conversation)  Create a new conversation
 * @method Resource ()  Acknowledge that a user has read the latest remarks in all of their conversations. This will clear the unread flag on all of their conversations.
 * @method Category[] getCategories($parameters = array())  Listing Tenant Categories
 * @method Stream[] getStreams($parameters = array())  Listing User’s Streams
 * @method Poll getPoll($pollId)  View Poll Data
 * @method Resource postPoll($poll)  Create a poll
 * @method Resource postThank($thank)  Create Thanks
 * @method Badge[] getBadges($parameters = array())  Get list of Thanks Badges
 * @method Badge getBadge($badgeId)  Get a specific badge
 */
abstract class Client extends Object {

    /**
     * @var \Sledgehammer\Logger
     */
    public $logger;

    protected $subdomain;


    public function __construct($subdomain = 'api') {
        $this->subdomain = $subdomain;
        $this->logger = new \Sledgehammer\Logger(array(
            'identifier' => 'Socialcast',
            'singular' => 'request',
            'plural' => 'requests',
        ));
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
     *
     * @param string $method HTTP method: GET,POST,PUT or DELETE
     * @param string $path
     * @param array $parameters GET parameters
     * @param mixed $data POST/PUT data
     * @return stdClass
     */
    public function api($method, $path, $parameters = array(), $data = null) {
        $start = microtime(true);
        $options = $this->curlOptions() + Curl::$defaults;
        $options[CURLOPT_URL] = $this->buildUrl($path, $parameters);
        switch ($method) {
            case 'GET':
                break;
            case 'POST':
                $options[CURLOPT_POST] = true;
                break;
            case 'PUT':
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                break;
            case 'DELETE':
                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                break;
        }
        if ($data !== null) {
        	$options[CURLOPT_POSTFIELDS] = $data;
        }
        $response = $this->processRequest(new Curl($options));
        $this->logger->append($method.' '.$path, array('duration' => microtime(true) - $start));
        return $response;
    }

    public function __call($method, $arguments) {
        $mapping = array(
            'postAttachment' => array(
                'path' => 'attachment',
                'class' => '\Socialcast\Resource',
                'arguments' => array(),
            ),
            'getUserinfo' => array(
                'path' => 'userinfo',
                'class' => '\Socialcast\Resource\User',
                'arguments' => array(),
            ),
            'getUser' => array(
                'path' => 'users/USER_ID',
                'class' => '\Socialcast\Resource\User',
                'arguments' => array(
                    '[0]' => 'USER_ID',
                ),
            ),
            'searchUsers' => array(
                'path' => 'users/search',
                'class' => '\Socialcast\Resource\User',
                'arguments' => array(),
            ),
            'getUsers' => array(
                'path' => 'users',
                'class' => '\Socialcast\Resource\User',
                'arguments' => array(),
            ),
            'putUser' => array(
                'path' => 'users/USER_ID',
                'class' => '\Socialcast\Resource',
                'arguments' => array(
                    '[0].id' => 'USER_ID',
                ),
            ),
            'deleteUser' => array(
                'path' => 'users/USER_ID',
                'class' => '\Socialcast\Resource',
                'arguments' => array(
                    '[0]' => 'USER_ID',
                ),
            ),
            'getGroupMemberships' => array(
                'path' => 'group_memberships',
                'class' => '\Socialcast\Resource\GroupMembership',
                'arguments' => array(),
            ),
            'getGroups' => array(
                'path' => 'groups',
                'class' => '\Socialcast\Resource\Group',
                'arguments' => array(),
            ),
            'getGroup' => array(
                'path' => 'groups/GROUP_ID',
                'class' => '\Socialcast\Resource\Group',
                'arguments' => array(
                    '[0]' => 'GROUP_ID',
                ),
            ),
            'postGroup' => array(
                'path' => 'groups',
                'class' => '\Socialcast\Resource',
                'arguments' => array(),
            ),
            'putGroup' => array(
                'path' => 'groups/GROUP_ID',
                'class' => '\Socialcast\Resource',
                'arguments' => array(
                    '[0].id' => 'GROUP_ID',
                ),
            ),
            'deleteGroup' => array(
                'path' => 'groups/GROUP_ID',
                'class' => '\Socialcast\Resource',
                'arguments' => array(
                    '[0]' => 'GROUP_ID',
                ),
            ),
            'getMessages' => array(
                'path' => 'messages',
                'class' => '\Socialcast\Resource\Message',
                'arguments' => array(),
            ),
            'getMessage' => array(
                'path' => 'messages/MESSAGE_ID',
                'class' => '\Socialcast\Resource\Message',
                'arguments' => array(
                    '[0]' => 'MESSAGE_ID',
                ),
            ),
            'searchMessages' => array(
                'path' => 'messages/search',
                'class' => '\Socialcast\Resource\Message',
                'arguments' => array(),
            ),
            'postMessage' => array(
                'path' => 'messages',
                'class' => '\Socialcast\Resource',
                'arguments' => array(),
            ),
            'putMessage' => array(
                'path' => 'messages/MESSAGE_ID',
                'class' => '\Socialcast\Resource',
                'arguments' => array(
                    '[0].id' => 'MESSAGE_ID',
                ),
            ),
            'deleteMessage' => array(
                'path' => 'messages/MESSAGE_ID',
                'class' => '\Socialcast\Resource',
                'arguments' => array(
                    '[0]' => 'MESSAGE_ID',
                ),
            ),
            'getContentFilters' => array(
                'path' => 'content_filters',
                'class' => '\Socialcast\Resource\ContentFilter',
                'arguments' => array(),
            ),
            'getConversation' => array(
                'path' => 'conversations/CONVERSATION_ID',
                'class' => '\Socialcast\Resource\Conversation',
                'arguments' => array(
                    '[0]' => 'CONVERSATION_ID',
                ),
            ),
            'getConversations' => array(
                'path' => 'conversations',
                'class' => '\Socialcast\Resource\Conversation',
                'arguments' => array(),
            ),
            'postConversation' => array(
                'path' => 'conversations',
                'class' => '\Socialcast\Resource',
                'arguments' => array(),
            ),
            '' => array(
                'path' => 'conversations/acknowledge_all',
                'class' => '\Socialcast\Resource',
                'arguments' => array(),
            ),
            'getCategories' => array(
                'path' => 'categories',
                'class' => '\Socialcast\Resource\Category',
                'arguments' => array(),
            ),
            'getStreams' => array(
                'path' => 'streams',
                'class' => '\Socialcast\Resource\Stream',
                'arguments' => array(),
            ),
            'getPoll' => array(
                'path' => 'polls/POLL_ID',
                'class' => '\Socialcast\Resource\Poll',
                'arguments' => array(
                    '[0]' => 'POLL_ID',
                ),
            ),
            'postPoll' => array(
                'path' => 'polls',
                'class' => '\Socialcast\Resource',
                'arguments' => array(),
            ),
            'postThank' => array(
                'path' => 'thanks',
                'class' => '\Socialcast\Resource',
                'arguments' => array(),
            ),
            'getBadges' => array(
                'path' => 'badges',
                'class' => '\Socialcast\Resource\Badge',
                'arguments' => array(),
            ),
            'getBadge' => array(
                'path' => 'badges/BADGE_ID',
                'class' => '\Socialcast\Resource\Badge',
                'arguments' => array(
                    '[0]' => 'BADGE_ID',
                ),
            ),
        );
        if (array_key_exists($method, $mapping) === false) {
            throw new InfoException('Method: "' . $method . '" doesn\'t exist in a "' . get_class($this) . '" object.', array('Available methods' => array_merge(array_keys($mapping), \Sledgehammer\get_public_methods($this))));
        }
        $config = $mapping[$method];
        $url = $config['path'];
        $body = '';
        foreach ($config['arguments'] as $path => $target) {
            $value = PropertyPath::get($path, $arguments);
            if ($target === '__HTTP_REQUEST_BODY__') {
                $body = $value;
            } else {
                $url = str_replace($target, $value, $url); // Replace USER_ID in the url with the $userId param
            }
        }
        preg_match('/^(get|post|put|delete|search)/', $method, $match);
        $methodType = $match[1];
        if ($methodType === 'search') {
            $params = count($arguments) === 1 ? array() : $arguments[1];
            $params['q'] = $arguments[0];
            $response = $this->get($url, $params);
        } elseif ($methodType === 'get') {
            $params = array();
            if (count($arguments) > count($config['arguments'])) {
                $params = $arguments[count($config['arguments'])];
            }
            $response = $this->get($url, $params);
        } elseif ($methodType === 'delete') {
            $response = $this->delete($url);
        } else { // post or put
            $response = $this->$methodType($url, $body);
        }
        if (count(get_object_vars($response)) === 1) { // Response is wrapped with a rootNode?
            $unwrapped = current($response);
            if (is_scalar($unwrapped) === false) {
                $response = $unwrapped;
            }
        }
        $class = $config['class'];
        // Single resource
        if (is_object($response)) {
            return new $class($this, $response);
        }
        // A resource list
        $list = array();
        foreach ($response as $item) {
            $list[] = new $class($this, $item);
        }
        return $list;
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
        return 'https://' . $this->subdomain . '.socialcast.com/api/' . $path . '.json' . $suffix;
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
