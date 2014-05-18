# Socialcast PHP

[![Master Build Status](https://secure.travis-ci.org/NoProtocol/socialcast-php.png?branch=master)](http://travis-ci.org/NoProtocol/socialcast-php)


PHP client for [Socialcast’s REST API](http://developers.socialcast.com/api-documentation/)

## Usage

```php
$auth = new Socialcast\Auth\BasicAuth('demo', 'emily@socialcast.com', 'demo');
$socialcast = new Socialcast\Client($auth);

$user = $socialcast->getUserinfo();
$messages = $user->getMessages(['page' => 1, 'per_page' => 25]);
var_dump($messages);
```