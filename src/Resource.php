<?php

namespace Socialcast;

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
     *
     * @var Client
     */
    protected $client;

    /**
     * @param Client $client
     * @param Object $response  The fields
     * @param string $path  Path for retrieving remaining properties.
     */
    function __construct($client, $response = false, $path = false) {
        $this->client = $client;
        $this->response = $this->unpackResponse($response);
        $this->path = $path;
    }

    public function __get($property) {
        if (!$this->response) {
            if (!$this->path) {
                \Sledgehammer\warning('Empty ' . get_class($this) . ' object. Initialize with an response or path');
                return;
            }
            $this->response = $this->unpackResponse($this->client->get($this->path));
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
            $this->response = $this->unpackResponse($this->client->get($this->path));
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

}
