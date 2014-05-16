<?php

namespace Socialcast\Resource;
use Socialcast\Client;

class Resource {

    /**
     * @var stdClass|false
     */
    protected $response;

    /**
     * Path for retrieving remaining properties.
     * @var string|false
     */
    protected $path;

    /**
     * Additional parameters for retrieving remaining properties.
     * @var string|false
     */
    protected $pathParameters;

    /**
     *
     * @var Client
     */
    protected $client;

    /**
     * @param Client $client
     * @param Object $response  The fields
     * @param string $path  Path for retrieving remaining properties.
     */
    function __construct($client, $response = false, $path = false, $pathParameters = array()) {
        $this->client = $client;
        $this->response = $this->unpackResponse($response);
        $this->path = $path;
        $this->pathParameters= $pathParameters;
    }

    public function __get($property) {
        if (!$this->response) {
            if (!$this->path) {
                \Sledgehammer\warning('Empty ' . get_class($this) . ' object. Initialize with an response or path');
                return;
            }
            $this->response = $this->unpackResponse($this->client->get($this->path, $this->pathParameters));
            $this->path = false;
        }
        if (property_exists($this->response, $property)) {
            $value = $this->response->$property;
            if (is_array($value) || (is_object($value) && get_class($value) === 'stdClass')) { // An array or not-converted object?
                $this->convertProperty($property);
                return $this->response->$property;
            } else {
                return $value;
            }
        } elseif ($this->path) {
            $this->response = $this->unpackResponse($this->client->get($this->path, $this->pathParameters));
            $this->path = false;
            return $this->__get($property); // retry
        }
        $properties = \Sledgehammer\reflect_properties($this->response);
        warning('Property "' . $property . '" doesn\'t exist in a ' . get_class($this) . ' object', \Sledgehammer\build_properties_hint($properties));
    }

    /**
     * Override this method for unpacking the response.
     * @param stdClass
     */
    protected function unpackResponse($response) {
        if ($response === false) {
            return false;
        }
        if (count(get_object_vars($response)) === 1) { // Response is wrapped with a rootNode?
            $unwrapped = current($response);
            if (is_object($unwrapped)) {
                return $unwrapped;
            }
        }
        return $response;
    }

    /**
     * Override this method to auto-convert certain fields.
     * @param string $property
     */
    protected function convertProperty($property) {

    }

    /**
     *
     * @param string $path
     * @param string $class
     * @return \Socialcast\Resource[]
     * @throws Exception
     */
//    protected function fetchCollection($path, $class) {

//    }

    /**
     *
     * @param Client $client
     * @param string $path
     * @param array [$parameters]
     */
    static function all($client, $path, $parameters = array()) {
        $response = $client->get($path, $parameters);
        if (is_object($response) && count(get_object_vars($response)) === 1) { // Response is wrapped with a rootNode?
            $response = current($response); // unwrap
        }
        if (is_array($response) === false) {
            throw new Exception('Response is not an collection/array');
        }
        $collection = array();
        foreach ($response as $item) {
            $collection[] = new static($client, $item);
        }
        return $collection;
    }
}
