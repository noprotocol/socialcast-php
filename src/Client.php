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
 * @method User getUserinfo()  View Authenticated User Profile
 * @method User getUser($userId)  View User Profile
 * @method User[] getUsers()  List Users in Your Company
 * @method Resource putUser($user)  Update User Profile
 * @method Resource deleteUser($userId)  Deactivate a User
 * @method GroupMembership[] getGroupMemberships()  Listing Group Memberships
 * @method Group[] getGroups()  Listing All Groups
 * @method Group getGroup($groupId)  Show a Single Group
 * @method Resource postGroup($group)  Create a Group
 * @method Resource putGroup($group)  Updating Existing Group
 * @method Resource deleteGroup($groupId)  Destroy an Archived Message
 * @method Message[] getMessages()  Reading Stream Messages
 * @method Message getMessage($messageId)  Read a Single Stream Message
 * @method Resource postMessage($message)  Creating New Messages
 * @method Resource putMessage($message)  Updating Existing Messages
 * @method Resource deleteMessage($messageId)  Destroy an existing message
 * @method ContentFilter[] getContentFilters()  Listing Tenant Content Filters
 * @method Conversation getConversation($conversationId)  Returns information of the referenced conversation
 * @method Conversation[] getConversations()  List all conversations that a user has access to
 * @method Resource postConversation($conversation)  Create a new conversation
 * @method Resource ()  Acknowledge that a user has read the latest remarks in all of their conversations. This will clear the unread flag on all of their conversations.
 * @method Category[] getCategories()  Listing Tenant Categories
 * @method Stream[] getStreams()  Listing User’s Streams
 * @method Poll getPoll($pollId)  View Poll Data
 * @method Resource postPoll($poll)  Create a poll
 * @method Resource postThank($thank)  Create Thanks
 * @method Badge[] getBadges()  Get list of Thanks Badges
 * @method Badge getBadge($badgeId)  Get a specific badge
 */
abstract class Client extends Object {

    protected $subdomain;

    public function __construct($subdomain = 'api') {
        $this->subdomain = $subdomain;
    }

    /**
     * Perform a GET api call.
     * @param string $path Example: 'userinfo' or 'users/123/followers'
     */
    public function get($path, $class = 'Socialcast\Resource') {
        $request = Curl::get($this->buildUrl($path), $this->curlOptions());
        return $this->processRequest($request);
    }

    public function post($path, $data) {
        $request = Curl::post($this->buildUrl($path), $data, $this->curlOptions());
        return $this->processRequest($request);
    }

    public function put($path, $data) {
        $request = Curl::put($this->buildUrl($path), $data, $this->curlOptions());
        return $this->processRequest($request);
    }

    public function delete($path) {
        $request = Curl::delete($this->buildUrl($path), $this->curlOptions());
        return $this->processRequest($request);
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
        preg_match('/^(get|post|put|delete)/', $method, $match);
        $httpMethod = $match[1];
        if (in_array($httpMethod, array('get', 'delete'))) {
            $response = $this->$httpMethod($url);
        } else { // post or put
            $response = $this->$httpMethod($url, $body);
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
