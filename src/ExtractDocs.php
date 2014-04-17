<?php

/**
 * Extract mappings and autocomplete helpers from the online manual.
 *
 * Generates phpdoc-comments for the dynamic methods of Socialcast\Client.
 * (Requires Laravel's Pluralizer)
 */

namespace Socialcast;

use DOMDocument;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use Sledgehammer\Curl;
use Sledgehammer\Object;

class ExtractDocs extends Object {

    function clientDocs() {
        $dom = new DOMDocument();
        @$dom->loadHTML(Curl::get('http://developers.socialcast.com/api-documentation/api/', array(CURLOPT_FAILONERROR => false))->getContent());
        $xml = simplexml_import_dom($dom);
        $api = $xml->xpath('//div[@class="api"]');

        $count = count($api[0]->h3);
        $methods = array();
        for ($i = 0; $i < $count; $i++) {
            $resource = preg_replace('/ API$/', '', $api[0]->h3[$i]->a);
            if ($resource === 'Responses') {
                // Resource types
            } else {
                // API calls
                foreach ($api[0]->table[$i]->tbody->tr as $tr) {
                    if ($tr['class'] == 'apiheader') {
                        continue;
                    }
                    if ($tr->td[0]->a == 'Examples') {
                        continue;
                    }
                    preg_match('/\[(.*)\] \/api\/(.+)$/', $tr->td[0]->a, $url);
                    $methods[] = array(
                        'name' => null,
                        'description' => (string) $tr->td[1],
                        'parameters' => array(),
                        'has_filters' => false,
                        'returnType' => '\\Socialcast\\Resource',
                        'type' => $url[1],
                        'path' => $url[2],

                    );
                }
            }
        }
        $mapping = array();
        $docComment = '';
        foreach ($methods as $method) {
            $pathParams = array();
            if (preg_match('/_ID\/.+/', $method['path'])) { // Skip related resources
                continue;
            }
            $resource = preg_replace('/\/.*$/', '', $method['path']);
            $class = '\\Socialcast\\Resource\\' .Str::studly(Str::singular($resource));

            if (preg_match('/^(.+)\/([^\/]+_ID)$/', $method['path'], $matches)) { // fetch one
                $method['name'] = strtolower($method['type']) . ucfirst(Str::singular($matches[1]));
                if (in_array($method['type'], array('GET', 'DELETE'))) {
                    $pathParams['[0]'] = $matches[2];
                    $method['parameters'][] = '$' . Str::camel(strtolower($matches[2]));
                    if ($method['type'] === 'GET') {
                        $method['returnType'] = $class;
                    }
                } else { // POST or PUT
                    $method['parameters'][] = '$' . Str::singular($matches[1]);
                    if ($method['type'] === 'PUT') {
                        $pathParams['[0].id'] = $matches[2];
                    }
                }
            } elseif (strpos($method['path'], '/') === false) { // fetch all
                if ($method['type'] === 'POST') {
                    $method['name'] = strtolower($method['type']) . Str::studly(Str::singular($method['path']));
                    $method['parameters'][] = '$' . Str::singular($resource);
                } else {
                    $method['name'] = strtolower($method['type']) . Str::studly($method['path']);
                    if ($method['type'] === 'GET') {
                        $method['returnType'] = $class . '[]';
                        $method['has_filters'] = true;
                    }
                }
            } else {
                if (Str::endsWith($method['path'], 'search') && $method['type'] === 'GET') {
                    $method['returnType'] = $class.'[]';
                    $method['name'] = 'search'.Str::studly($resource);
                    $method['type'] = 'SEARCH';
                    $method['has_filters'] = true;
                } else {
//                    warning('Unexpected method', $method);
                }
            }

            switch ($method['name']) {
                case 'getUserinfo':
                    $method['returnType'] = '\Socialcast\Resource\User';
                    break;
            }
            // build doccomment
            if ($method['type'] === 'SEARCH') {
                $docParams = array('$querystring');
            } else {
                $docParams = $method['parameters'];
            }
            if ($method['has_filters']) {
                $docParams[] = '$parameters = array()';
            }
            $docComment .= "\n * @method " . $method['returnType'] . ' ' . $method['name'] . '('.implode(', ', $docParams). ')  ' . $method['description'];

            // build mapping
            $mapping[$method['name']] = array(
                'path' => $method['path'],
                'class' => str_replace('[]', '', $method['returnType']),
                'arguments' => $pathParams,
            );
        }
        dump($docComment);
        dump($mapping);
    }

}
