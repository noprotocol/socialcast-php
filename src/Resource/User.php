<?php

namespace Socialcast\Resource;

use Socialcast\CustomFields;
use Socialcast\Resource;

/**
 * User profile information.
 *
 * @link http://developers.socialcast.com/api-documentation/api/responses/user-profile-response/
 *
 * @property-read string $id unique ID for this user.
 * @property-read string $name full name of the user (includes first and last name)
 * @property-read string $username username used for sending direct messages
 * @property-read User $manager user response for this user’s manager
 * @property-read CustomFields $custom_fields  (optional) list of custom profile fields for the user
 * @property-read object $contact_info  contact information available for the user
 * @property-read string $followable flag if current user can follow/unfollow this user
 * @property-read string $contact_id (optional) Unique ID if current user is following this user. See the Follow API for more information.
 * @property-read object $avatars  urls for various sizes of user avatar pictures.
 * @property-read string $out_of_office flag if the user is currently out of the office.
 * @property-read string $back_in_office_on if the user is out of the office, this date is the day they will return to the office.
 */
class User extends Resource {

    protected function convertProperty($property) {
        switch ($property) {
            case 'custom_fields':
                $this->response->custom_fields = new CustomFields($this->response->custom_fields);
                break;

            case 'manager':
                $this->response->manager = new User($this->client, $this->response->manager, 'users/'.$this->response->manager->id);
                break;
        }
    }

    public function getMessages() {
        return $this->fetchCollection('users/'.$this->id.'/messages', 'Socialcast\Resource\Message');
    }

}
