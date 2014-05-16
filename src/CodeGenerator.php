<?php

/**
 * Extract mappings and autocomplete helpers from the online manual.
 *
 * Generates phpdoc-comments for the dynamic methods of Socialcast\Client.
 * (Requires Laravel's Str class)
 */

namespace Socialcast;

use DOMDocument;
use Illuminate\Support\Str;
use Sledgehammer\Curl;

class CodeGenerator {

    static function run() {
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
        $codeMethods = array();
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
                    $method['parameters'][$matches[2]] = '$id';// . Str::camel(strtolower($matches[2]));
                    if ($method['type'] === 'GET') {
                        $method['returnType'] = $class;
                    }
                } else { // POST or PUT
                    $method['wrapped'] = false;
                    $method['parameters'][] = '$' . Str::singular($matches[1]);
                    if ($method['type'] === 'PUT') {
//                        $pathParams['[0].id'] = $matches[2];
                    }
                }
            } elseif (strpos($method['path'], '/') === false) { // Add
                if ($method['type'] === 'POST') {
                    $method['wrapped'] = false;
                    $method['name'] = strtolower($method['type']) . Str::studly(Str::singular($method['path']));
                    $method['parameters'][] = '$' . Str::singular($resource);
                } else { // fetch all
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
                    continue;
                }
            }

            switch ($method['name']) {
                case 'getUserinfo':
                    $class = '\Socialcast\Resource\User';
                    $method['returnType'] = $class;
                    break;
                case 'postMessage':
                    $method['wrapped'] = 'message';
                    break;
            }

            $code = "\n\t/**";
            $code .= "\n\t * ".$method['description'];
            $code .= "\n\t *";
            if ($method['type'] === 'SEARCH') {
                $code .= "\n\t * @param string \$querystring  Search query string";
            }
            $path = "'".$method['path']."'";
            foreach ($method['parameters'] as $key => $parameter) {
                $type = in_array($method['type'], array('POST', 'PUT')) ? 'array' : 'int';
                $code .= "\n\t * @param ".$type." ".$parameter;
                $path = str_replace($key, "'.".$parameter.".'", $path);
            }
            $path = str_replace(".''", '', $path);
            if ($method['has_filters']) {
                $code .= "\n\t * @param array [\$parameter]  Request parameters";
            }
            if ($method['type'] !== 'DELETE') {
                $code .= "\n\t * @return ".$method['returnType'];
            }
            $code .= "\n\t */";
            $parameters = $method['parameters'];
            if ($method['type'] === 'SEARCH') {
                $parameters[] = '$querystring';
            }
            if ($method['has_filters']) {
                $parameters[] = '$parameters = array()';
                $pathParameters = ", \$parameters";
            } else {
                $pathParameters = '';
            }
            $code .= "\n\tpublic function ".$method['name'].'('.implode(', ', $parameters);

            $code .= ") {";
            $code .= "\n\t\t// ** GENERATED CODE **";
            if ($method['type'] === 'GET') {
                if ($class === $method['returnType']) { // Single return
                    if (count($method['parameters']) === 1) {
                        $resource = "(object) array('id' => \$id)";
                    } else {
                        $resource = 'false';
                    }
                    $code .= "\n\t\treturn new ".$class."(\$this, ".$resource.", ".$path.$pathParameters.");";
                } else { // Collection
                    $code .= "\n\t\treturn ".$class."::all(\$this, ".$path.$pathParameters.");";
                }
            } elseif ($method['type'] === 'SEARCH') {
                $code .= "\n\t\t\$parameters['q'] = \$querystring;";
                $code .= "\n\t\treturn ".$class."::all(\$this, ".$path.$pathParameters.");";
            } elseif ($method['type'] === 'POST') {
                $data = $method['parameters'][0];
                if ($method['wrapped']) {
                    $data = "array('".$method['wrapped']."' => ".$data.')';
                }
                $code .= "\n\t\t\$response = \$this->post(".$path.", ".$data.");";
                $code .= "\n\t\treturn new ".$class."(\$this, \$response);";
            } elseif ($method['type'] === 'DELETE') {
                $code .= "\n\t\t\$this->delete(".$path.");";
            } else {
                // @todo implement PUT
                $code .= "\n\t\tthrow new \Exception('Not implemented');";
                continue;
            }
            $code .= "\n\t}\n";
            $codeMethods[$method['name']] = $code;
        }
        dump(str_replace("\t", '    ', implode($codeMethods)));
    }

}
