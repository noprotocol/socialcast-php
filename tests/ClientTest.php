<?php

namespace Socialcast;

use PHPUnit_Framework_TestCase;
use Socialcast\Client\BasicAuth;
use Socialcast\Resource\User;

/**
 * Testing the Socialcast API using the demo community.
 */
class ClientTest extends PHPUnit_Framework_TestCase {

    /**
     * @return Client
     */
    function getClient() {
        return new BasicAuth('demo', 'emily@socialcast.com', 'demo');
    }

    function testUserinfo() {
        $user = $this->getClient()->getUserinfo();
        $this->assertEquals('Emily James', $user->name);
        $this->assertEquals('Marketing', $user->custom_fields->department);
    }

    function testTraversalAndLazyLoading() {
        $user = $this->getClient()->getUserinfo();
        $this->assertEquals('Stacey Lynch', $user->manager->name);
        $manager = $user->manager;
        $this->assertInstanceOf('Socialcast\Resource\User', $manager);
        $this->assertTrue($manager === $user->manager, 'Reuse the same instance');
        // Lazyload remaining properties
        $this->assertEquals(null, $user->manager->manager);
        $this->assertEquals('Headquarters', $user->manager->custom_fields->business_unit);
    }

    function testCollection() {
        $data = (object) array('id' => 25);
        $user = new User($this->getClient(), $data);
        $messages = $user->getMessages();
        $this->assertGreaterThan(10, count($messages));
        $this->assertInstanceOf('Socialcast\Resource\Message', $messages[0]);
    }

}
