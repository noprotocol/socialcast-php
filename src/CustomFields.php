<?php

namespace Socialcast;

/**
 * Simplied access to the custom_fields of a User.
 */
class CustomFields {

    /**
     * @var array
     */
    private $fields;

    /**
     * @param array $fields
     */
    function __construct($fields) {
        $this->fields = $fields;
    }

    public function __get($property) {
        // Match by id
        foreach ($this->fields as $field) {
            if ($field->id === $property) {
                return $field->value;
            }
        }
        // Match by label & build property array
        $properties = array(
            'public' => array(),
            'protected' => array(),
            'private' => array(),
        );
        foreach ($this->fields as $field) {
            if ($field->label === $property) {
                return $field->value;
            }
            $properties['public'][$field->id] = $field->value;
            $properties['public'][$field->label] = $field->value;
        }
        warning('Property "' . $property . '" doesn\'t exist in a ' . get_class($this) . ' object', \Sledgehammer\build_properties_hint($properties));
    }

    /**
     * Extract the value from a custom_field.
     * @param string $label
     * @return mixed
     */
    public function byLabel($label) {
        foreach ($this->fields as $field) {
            if ($field->label === $label) {
                return $field->value;
            }
        }
        return null;
    }

    /**
     * Extract the value from a custom_field.
     * @param string $id
     * @return mixed
     */
    public function byId($id) {
        foreach ($this->fields as $field) {
            if ($field->id === $id) {
                return $field->value;
            }
        }
        return null;
    }

}
