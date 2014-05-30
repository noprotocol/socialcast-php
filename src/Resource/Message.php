<?php

namespace Socialcast\Resource;

/**
 * Individual message in the stream.
 *
 * @link http://developers.socialcast.com/api-documentation/api/responses/message-response/
 *
 * @property-read string $title title of the message
 * @property-read string $body (optional) details for the message
 * @property-read string $url url for interacting with the message via the API.
 * @property-read string $permalink_url url for accessing the message’s permalink page.
 * @property-read string $action useful text to construct a sentence from the message title, (ex: asked a question)
 * @property-read string $external_url (optional) external url for shared links, bookmarks, YouTube videos, etc.
 * @property-read string $icon url for the icon image for this message type.
 * @property-read string $id unique ID for this message. Use this ID for posting comments or likes on this message.
 * @property-read string $likable boolean flag whether this user can “like” the message. (ex: users can not like their own content)
 * @property-read string $created-at date/time the message was created. formatted according to iso8601 specification.
 * @property-read string $last-interacted-at date/time that the message was last interacted interacted with. formatted as millis since epoch.
 * @property-read string $player_url The URL of a flash player which should be embedded in this message in supporting clients.
 * @property-read string $thumbnail_url Location of the thumbnail to display before embedding the flash player.
 * @property-read string $player_params FlashVars to pass to the embedded flash player.
 * @property-read User $user owning user of the message. See User Response for the user format.
 * @property-read object $poll{} poll associated to the message. See Poll Response for the poll format.
 * @property-read object[] $groups[] list of groups that this message belongs to. See Group Response for the group format.
 * @property-read object $source{}(optional) source of this message
 * @property-read object[] $attachments (optional) file attachments for this message
 * @property-read object[] $comments[](optional) array of comments posted for this message
 * @property-read string $comments_count integer total number of comments for this message
 * @property-read Like[] $likes (optional) array of likes for this message
 * @property-read string $likes_count integer total number of likes for this message
 * @property-read object[] $media_files[](optional) array of referenced media files for this message. Includes images, video, audio, etc. Includes info for URLs referenced in the message as well as attachments.
 * @property-read Users[] $recipients (optional) array of users that this message was sent to
 * @property-read string $tags[](optional) array of tags for this message
 * @property-read object $tag individual tag for the message
 * @property-read object $flag (optional) flag for the current user. See Flag Response for the format.
 * @property-read string $in_origin_stream (on create only) boolean flag to notify clients if the message belongs in the stream that it was generated from. See the origin_stream[id] parameter in the POST Message documentation for more information.
 */
class Message extends Resource {

    /**
     * Add a like for the authenticated user for a particular message.
     * @link http://developers.socialcast.com/api-documentation/api/likes/create-message-like/
     */
    public function like() {
        return $this->client->post('messages/'.$this->id.'/likes', array());
    }

    /**
     * Un-like a message for the authenticated user.
     * @link http://developers.socialcast.com/api-documentation/api/likes/destroy-message-like/
     */
    public function unlike() {
        foreach ($this->likes as $like) {
            if ($like->unlikable) {
                $this->client->delete('messages/'.$this->id.'/likes/'.$like->id);
                return true;
            }
        }
        return false;
    }

    /**
     * Create a new comment for an existing message.
     * @link http://developers.socialcast.com/api-documentation/api/comments/create/
     *
     * @param type $message
     */
    public function postComment($message) {
        if ($message == '') {
            throw new \Exception('Message is required');
        }
        $this->client->post('messages/'.$this->id.'/comments', array(
            'comment[text]' => $message
        ));
    }

    protected function convertProperty($property) {
        switch ($property) {

            case 'user':
                $this->response->user = new User($this->client, $this->response->user, 'users/'.$this->response->user->id);
                break;
        }
    }

}
