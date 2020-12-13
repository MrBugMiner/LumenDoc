<?php

namespace MrBugMiner\LumenDoc\Classes;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Psr\Http\Message\ResponseInterface;

class Generate
{

    // Scan Data
    /** @var array $scan */
    private $scan = [];

    // Doc Data
    /** @var array $doc */
    private $doc = [];

    /**
     * @param Command $console
     */
    public function handle(Command $console)
    {
        // Load From File
        /** @var bool $error */
        /** @var string $loadFilePath */
        list($error, $loadFilePath) = $this->loadFromFile();
        // Show Load Error / Message According To Load From File Error
        if ($error === true) {
            $console->error('Scan File Is Not Found In "' . $loadFilePath . '".');
            return;
        } else {
            $console->info('Load Scan Data From "' . $loadFilePath . '".');
        }
        // Process Scan Data And Get It's Error
        /** @var string $processScanFileError */
        $processScanFileError = $this->processScanData();
        // If Process Scan Data Return Error , Show This Error
        if ($processScanFileError !== '') {
            $console->error($processScanFileError);
            return;
        }
        // Generate Doc
        $console->info('Generate Doc.');
        $this->generateDoc();
        // Save To File
        /** @var string $saveFilePath */
        $saveFilePath = $this->saveToFile();
        $console->info('Save Doc Data To "' . $saveFilePath . '" File.');
    }

    /**
     * @return array
     */
    private function loadFromFile()
    {
        // File Name
        /** @var string $fileName */
        $fileName = 'scan.json';
        // Folder Path
        /** @var string $folderPath */
        $folderPath = 'public/lumen-doc/';
        // File Full Path In Public Folder
        /** @var string $folderFullPath */
        $fileFullPath = base_path($folderPath) . $fileName;
        // If File Is Not Found , Return Error
        if (file_exists($fileFullPath) === false) {
            return [
                true, // Error
                $folderPath . $fileName,
            ];
        }
        // Get File Content And Get Scan Data From File
        $this->scan = json_decode(file_get_contents($fileFullPath), true, 512, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        // If Scan Data Is Null , Set Empty Array To Scan Data
        if ($this->scan === null) {
            $this->scan = [];
        }
        // Return File Path
        return [
            false,
            $folderPath . $fileName,
        ];
    }

    /**
     * @return string
     */
    private function processScanData()
    {
        // If Scan Data Is Empty , Return Error
        if (empty($this->scan) === true) {
            return 'Scan Data Is Empty.';
        }
        // If Scan Data Has Not Groups , Return Error
        if (isset($this->scan['groups']) === false) {
            return 'Scan Data Has Not Groups.';
        } else {
            // Get Scan Data Groups
            /** @var array $groups */
            $groups = $this->scan['groups'];
            // If Scan Data Groups Is Not Array , Return Error
            if (is_array($groups) === false) {
                return 'Scan Data Groups Is Not Array.';
            } else {
                // Get Scan Data Groups Error
                /** @var string $scanDataGroupsError */
                $scanDataGroupsError = $this->getScanDataGroupsError($groups);
                // If Scan Data Groups Return Error , Return This Error
                if ($scanDataGroupsError !== '') {
                    return $scanDataGroupsError;
                }
            }
        }
        // If Scan Data Has Not Routes , Return Error
        if (isset($this->scan['routes']) === false) {
            return 'Scan Data Has Not Routes.';
        } else {
            // Get Scan Data Routes
            /** @var array $routes */
            $routes = $this->scan['routes'];
            // If Scan Data Routes Is Not Array , Return Error
            if (is_array($routes) === false) {
                return 'Scan Data Routes Is Not Array.';
            } else {
                // Get Scan Data Routes Error
                /** @var string $scanDataRoutesError */
                $scanDataRoutesError = $this->getScanDataRoutesError($routes);
                // If Scan Data Routes Return Error , Return This Error
                if ($scanDataRoutesError !== '') {
                    return $scanDataRoutesError;
                }
            }
        }
        // Set Doc Data From Scan Data
        $this->doc = $this->scan;
        // Return Empty Error
        return '';
    }

    /**
     * @param array $groups
     * @return string
     */
    private function getScanDataGroupsError(array $groups)
    {
        // Process Groups
        foreach ($groups as $groupName => $groupItems) {
            // If Group Name Is Not String Or Group Name Is Empty String , Return Error
            if (is_string($groupName) === false || trim($groupName) === '') {
                return 'Scan Data Groups Has Group With Empty Name.';
            }
            // If Group Has Not Title , Return Error
            if (array_key_exists('title', $groupItems) === false) {
                return 'Scan Data Group "' . $groupName . '" Has Not Title.';
            } else if (is_string($groupItems['title']) === false) { // If Group Title Is Not String , Return Error
                return 'Scan Data Group "' . $groupName . '" Title Is Not String.';
            }
            // If Group Has Not Description , Return Error
            if (array_key_exists('description', $groupItems) === false) {
                return 'Scan Data Group "' . $groupName . '" Has Not Description.';
            } else if (is_string($groupItems['description']) === false) { // If Group Description Is Not String , Return Error
                return 'Scan Data Group "' . $groupName . '" Description Is Not String.';
            }
            // If Group Has Not Groups , Return Error
            if (array_key_exists('groups', $groupItems) === false) {
                return 'Scan Data Group "' . $groupName . '" Has Not Groups.';
            } else if (is_array($groupItems['groups']) === false) { // If Group Groups Is Not Array , Return Error
                return 'Scan Data Group "' . $groupName . '" Description Is Not Array.';
            }
            // If Group Groups Is Not Empty , Check This Groups Error
            if (empty($groupItems['groups']) === false) {
                return $this->getScanDataGroupsError($groupItems['groups']);
            }
        }
        // Return Empty Error
        return '';
    }

    /**
     * @param array $routes
     * @return string
     */
    private function getScanDataRoutesError(array $routes)
    {
        // Process Routes
        /** @var int $routeIndex */
        /** @var array $route */
        foreach ($routes as $routeIndex => $route) {
            // If Route Has Not Group , Return Error
            if (array_key_exists('group', $route) === false) {
                return 'Scan Data Route #' . $routeIndex . ' Has Not Group.';
            } else if (is_array($route['group']) === false) { // If Route Group Is Not Array , Return Error
                return 'Scan Data Route #' . $routeIndex . ' Group Is Not Array.';
            }
            // If Route Has Not Description , Return Error
            if (array_key_exists('description', $route) === false) {
                return 'Scan Data Route #' . $routeIndex . ' Has Not Description.';
            } else if (is_string($route['description']) === false) { // If Route Description Is Not String , Return Error
                return 'Scan Data Route #' . $routeIndex . ' Description Is Not String.';
            }
            // If Route Has Not Method , Return Error
            if (array_key_exists('method', $route) === false) {
                return 'Scan Data Route #' . $routeIndex . ' Has Not Method.';
            } else if (is_string($route['method']) === false) { // If Route Method Is Not String , Return Error
                return 'Scan Data Route #' . $routeIndex . ' Method Is Not String.';
            }
            // If Route Has Not Url , Return Error
            if (array_key_exists('url', $route) === false) {
                return 'Scan Data Route #' . $routeIndex . ' Has Not Url.';
            } else if (is_string($route['url']) === false) { // If Route Url Is Not String , Return Error
                return 'Scan Data Route #' . $routeIndex . ' Url Is Not String.';
            }
            // Get Route Url Params Error
            /** @var string $error */
            $error = $this->getScanDataRoutesParamsError($route, $routeIndex, 'url_params', 'Url Param');
            // If Route Url Params Return Error , Return This Error
            if ($error !== '') {
                return $error;
            }
            // Get Route Query Params Error
            $error = $this->getScanDataRoutesParamsError($route, $routeIndex, 'query_params', 'Query Param');
            // If Route Query Params Return Error , Return This Error
            if ($error !== '') {
                return $error;
            }
            // Get Route Body Params Error
            $error = $this->getScanDataRoutesParamsError($route, $routeIndex, 'body_params', 'Body Param');
            // If Route Body Params Return Error , Return This Error
            if ($error !== '') {
                return $error;
            }
            // Get Route Headers Error
            $error = $this->getScanDataRoutesHeadersError($route, $routeIndex);
            // If Route Headers Return Error , Return This Error
            if ($error !== '') {
                return $error;
            }
            // Get Route Requests Error
            $error = $this->getScanDataRoutesRequestsError($route, $routeIndex);
            // If Route Requests Return Error , Return This Error
            if ($error !== '') {
                return $error;
            }
        }
        // Return Empty Error
        return '';
    }

    /**
     * @param array $route
     * @param int $routeIndex
     * @param string $itemName
     * @param string $name
     * @return string
     */
    private function getScanDataRoutesParamsError(array $route, int $routeIndex, string $itemName, string $name)
    {
        // If Route Has Not Params , Return Error
        if (array_key_exists($itemName, $route) === false) {
            return "Scan Data Route #{$routeIndex} Has Not {$name}s.";
        } else if (is_array($route[$itemName]) === false) { // If Route Params Is Not Array , Return Error
            return "Scan Data Route #{$routeIndex} {$name}s Is Not Array.";
        } else if (empty($route[$itemName]) === false) {
            /** @var int $paramIndex */
            /** @var array $param */
            foreach ($route[$itemName] as $paramIndex => $param) {
                // If Param Has Not Name , Return Error
                if (array_key_exists('name', $param) === false) {
                    return "Scan Data Route #{$routeIndex} {$name} #{$paramIndex} Has Not Name.";
                } else if (is_string($param['name']) === false) { // If Param Name Is Not String , Return Error
                    return "Scan Data Route #{$routeIndex} {$name} #{$paramIndex} Name Is Not String.";
                }
                // If Param Has Not Type , Return Error
                if (array_key_exists('type', $param) === false) {
                    return "Scan Data Route #{$routeIndex} {$name} #{$paramIndex} Has Not Type.";
                } else if (is_string($param['type']) === false) { // If Param Type Is Not String , Return Error
                    return "Scan Data Route #{$routeIndex} {$name} #{$paramIndex} Type Is Not String.";
                }
                // If Param Has Not Value , Return Error
                if (array_key_exists('value', $param) === false) {
                    return "Scan Data Route #{$routeIndex} {$name} #{$paramIndex} Has Not Value.";
                }
                // If Param Has Not Description , Return Error
                if (array_key_exists('description', $param) === false) {
                    return "Scan Data Route #{$routeIndex} {$name} #{$paramIndex} Has Not Description.";
                } else if (is_string($param['description']) === false) { // If Param Description Is Not String , Return Error
                    return "Scan Data Route #{$routeIndex} {$name} #{$paramIndex} Description Is Not String.";
                }
            }
        }
        // Return Empty Error
        return '';
    }

    /**
     * @param array $route
     * @param int $routeIndex
     * @return string
     */
    private function getScanDataRoutesHeadersError(array $route, int $routeIndex)
    {
        // If Route Has Not Headers , Return Error
        if (array_key_exists('headers', $route) === false) {
            return "Scan Data Route #{$routeIndex} Has Not Headers.";
        } else if (is_array($route['headers']) === false) { // If Route Headers Is Not Array , Return Error
            return "Scan Data Route #{$routeIndex} Headers Is Not Array.";
        } else if (empty($route['headers']) === false) {
            /** @var int $headerIndex */
            /** @var array $header */
            foreach ($route['headers'] as $headerIndex => $header) {
                // If Header Has Not Name , Return Error
                if (array_key_exists('name', $header) === false) {
                    return "Scan Data Route #{$routeIndex} Header #{$headerIndex} Has Not Name.";
                } else if (is_string($header['name']) === false) { // If Header Name Is Not String , Return Error
                    return "Scan Data Route #{$routeIndex} Header #{$headerIndex} Name Is Not String.";
                }
                // If Header Has Not Value , Return Error
                if (array_key_exists('value', $header) === false) {
                    return "Scan Data Route #{$routeIndex} Header #{$headerIndex} Has Not Value.";
                }
                // If Header Has Not Description , Return Error
                if (array_key_exists('description', $header) === false) {
                    return "Scan Data Route #{$routeIndex} Header #{$headerIndex} Has Not Description.";
                } else if (is_string($header['description']) === false) { // If Header Description Is Not String , Return Error
                    return "Scan Data Route #{$routeIndex} Header #{$headerIndex} Description Is Not String.";
                }
            }
        }
        // Return Empty Error
        return '';
    }

    /**
     * @param array $route
     * @param int $routeIndex
     * @return string
     */
    private function getScanDataRoutesRequestsError(array $route, int $routeIndex)
    {
        // If Route Has Not Requests , Return Error
        if (array_key_exists('requests', $route) === false) {
            return "Scan Data Route #{$routeIndex} Has Not Requests.";
        } else if (is_array($route['requests']) === false) { // If Route Requests Is Not Array , Return Error
            return "Scan Data Route #{$routeIndex} Requests Is Not Array.";
        } else if (empty($route['requests']) === false) {
            /** @var int $requestIndex */
            /** @var array $request */
            foreach ($route['requests'] as $requestIndex => $request) {
                // If Request Has Not Params , Return Error
                if (array_key_exists('params', $request) === false) {
                    return "Scan Data Route #{$routeIndex} Request #{$requestIndex} Has Not Params.";
                } else if (is_array($request['params']) === false) { // If Request Params Is Not Array , Return Error
                    return "Scan Data Route #{$routeIndex} Request #{$requestIndex} Params Is Not Array.";
                } else {
                    /** @var int $paramIndex */
                    $paramIndex = 0;
                    foreach ($request['params'] as $paramName => $paramValue) {
                        if (is_string($paramName) === false) {
                            return "Scan Data Route #{$routeIndex} Request #{$requestIndex} Param #{$paramIndex} Is Not String.";
                        } else if ($paramName[0] !== '$') {
                            return "Scan Data Route #{$routeIndex} Request #{$requestIndex} Param #{$paramIndex} Has Not $ In First.";
                        }
                        ++$paramIndex;
                    }
                }
                // If Request Has Not Description , Return Error
                if (array_key_exists('description', $request) === false) {
                    return "Scan Data Route #{$routeIndex} Request #{$requestIndex} Has Not Description.";
                } else if (is_string($request['description']) === false) { // If Request Description Is Not String , Return Error
                    return "Scan Data Route #{$routeIndex} Request #{$requestIndex} Description Is Not String.";
                }
                // If Request Has Not Token , Return Error
                if (array_key_exists('token', $request) === false) {
                    return "Scan Data Route #{$routeIndex} Request #{$requestIndex} Has Not Token.";
                } else if (is_bool($request['token']) === false) { // If Request Token Is Not Boolean , Return Error
                    return "Scan Data Route #{$routeIndex} Request #{$requestIndex} Token Is Not Boolean.";
                }
            }
        }
        // Return Empty Error
        return '';
    }

    private function generateDoc()
    {

        // Base Url
        /** @var string $baseUrl */
        $baseUrl = rtrim(trim(Config::get('lumen-doc.base_url', '')), '/');
        // Token For "tokenRequest"
        /** @var string $token */
        $token = trim(Config::get('lumen-doc.token', ''));
        // Call Routes Requests And Get Response
        /** @var int $routeIndex */
        /** @var array $route */
        foreach ($this->doc['routes'] as $routeIndex => $route) {
            // Get Route Requests
            /** @var array $requests */
            $requests = $route['requests'];
            // If Route Requests Is Empty , Ignore This Route
            if (empty($requests) === true) {
                continue;
            }
            // Route Url
            /** @var string $routeUrl */
            $routeUrl = $baseUrl . '/' . ltrim(trim($route['url']), '/');
            // Route Method
            /** @var string $routeMethod */
            $routeMethod = $route['method'];
            // Route Headers
            /** @var array $routeHeaders */
            $routeHeaders = [];
            /** @var array $routeHeader */
            foreach ($route['headers'] as $routeHeader) {
                /** @var string $name */
                $name = strtolower(trim($routeHeader['name']));
                $routeHeaders[$name] = "{$name}: {$routeHeader['value']}";
            }
            // Route Url Params
            /** @var array $routeUrlParams */
            $routeUrlParams = [];
            /** @var array $routeUrlParam */
            foreach ($route['url_params'] as $routeUrlParam) {
                $routeUrlParams[$this->removeSigil($routeUrlParam['name'])] = $this->normalizeValueByType($routeUrlParam['value'], $routeUrlParam['type']);
            }
            // Route Query Params
            /** @var array $routeQueryParams */
            $routeQueryParams = [];
            /** @var array $routeQueryParam */
            foreach ($route['query_params'] as $routeQueryParam) {
                $routeQueryParams[$this->removeSigil($routeQueryParam['name'])] = $this->normalizeValueByType($routeQueryParam['value'], $routeQueryParam['type']);
            }
            // Route Body Params
            /** @var array $routeBodyParams */
            $routeBodyParams = [];
            /** @var array $routeBodyParam */
            foreach ($route['body_params'] as $routeBodyParam) {
                $routeBodyParams[$this->removeSigil($routeBodyParam['name'])] = $this->normalizeValueByType($routeBodyParam['value'], $routeBodyParam['type']);
            }
            // Requests
            /** @var int $requestIndex */
            /** @var array $request */
            foreach ($route['requests'] as $requestIndex => $request) {
                // Request Headers
                /** @var array $requestHeaders */
                $requestHeaders = $routeHeaders;
                // If Request Need Token , Add Token To Request Headers
                if ($request['token'] === true) {
                    $requestHeaders['authorization'] = "authorization: {$token}";
                }
                $requestHeaders = array_values($requestHeaders);
                // Request Url Params
                /** @var array $requestUrlParams */
                $requestUrlParams = $routeUrlParams;
                // Request Query Params
                /** @var array $requestQueryParams */
                $requestQueryParams = $routeQueryParams;
                // Request Body Params
                /** @var array $requestBodyParams */
                $requestBodyParams = $routeBodyParams;
                // Params
                /** @var string $paramName */
                foreach ($request['params'] as $paramName => $paramValue) {
                    $paramName = $this->removeSigil($paramName);
                    $paramValue = $this->normalizeValue($paramValue);
                    // If Param Is In Request Url Params , Add Param Value To Request Url Params
                    if (array_key_exists($paramName, $requestUrlParams) === true) {
                        $requestUrlParams[$paramName] = $paramValue;
                    }
                    // If Param Is In Request Query Params , Add Param Value To Request Query Params
                    if (array_key_exists($paramName, $requestQueryParams) === true) {
                        $requestQueryParams[$paramName] = $paramValue;
                    }
                    // If Param Is In Request Body Params , Add Param Value To Request Body Params
                    if (array_key_exists($paramName, $requestBodyParams) === true) {
                        $requestBodyParams[$paramName] = $paramValue;
                    }
                }
                // Request Url
                /** @var string $requestUrl */
                $requestUrl = $routeUrl;
                // Add Request Url Params To Request Url
                foreach ($requestUrlParams as $requestUrlParamName => $requestUrlParamValue) {
                    $requestUrl = str_replace(
                        '{' . $requestUrlParamName . '}',
                        $requestUrlParamValue,
                        $requestUrl
                    );
                }
                // Call Request
                /** @var array $response */
                $response = $this->callRoute(
                    $requestUrl,
                    $routeMethod,
                    $requestHeaders,
                    $requestQueryParams,
                    $requestBodyParams
                );
                // Update Route Request
                $this->doc['routes'][$routeIndex]['requests'][$requestIndex] = [
                    'description' => $request['description'],
                    'token' => $request['token'],
                    'method' => $routeMethod,
                    'url' => $requestUrl,
                    'headers' => $requestHeaders,
                    'query_params' => $requestQueryParams,
                    'body_params' => $requestBodyParams,
                    'response' => $response,
                ];
            }
        }
    }

    /**
     * @param string $name
     * @return string
     */
    private function removeSigil(string $name)
    {
        $name = trim($name);
        if ($name !== '' && $name[0] === '$') {
            return substr($name, 1);
        }
        return $name;
    }

    /**
     * @param float|int|array|string|null $value
     * @param string $valueType
     * @return float|int|array|null|string
     */
    private function normalizeValueByType($value, string $valueType)
    {
        switch (trim($valueType)) {
            case 'float':
                return floatval($value);
            case 'int':
                return intval($value);
            case 'array':
                return (array)$value;
            case 'null':
                return null;
            case 'string':
            case '':
            default:
                return trim($value);
        }
    }

    /**
     * @param float|int|array|string|null $value
     * @return float|int|array|null|string
     */
    private function normalizeValue($value)
    {
        // Trim Value
        $value = trim($value);
        // Return Normalize Value
        if (is_numeric($value) === true) {
            return strpos($value, '.') === false ? intval($value) : floatval($value);
        } else {
            /** @var array|null $array */
            $array = json_decode($value, true, 512, JSON_UNESCAPED_UNICODE | JSON_OBJECT_AS_ARRAY | JSON_UNESCAPED_SLASHES);
            if (is_array($array) === true) {
                return $array;
            } else if (is_null($value) === true || strtolower($value) === 'null') {
                return null;
            } else {
                return $value;
            }
        }
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $headers
     * @param array $queryString
     * @param array $json
     * @param array $form_data
     * @param array $x_www_form_urlencoded
     * @param string $raw
     * @return array
     */
    private function callRoute(string $url, string $method, array $headers = [], array $queryString = [], array $json = [], array $form_data = [], array $x_www_form_urlencoded = [], string $raw = '')
    {
        try {
            /** @var array $options */
            $options = [];
            // Headers
            if (empty($headers) === false) {
                $options['headers'] = $headers;
            }
            // Query String
            if (empty($queryString) === false) {
                $options['query'] = $queryString;
            }
            // JSON
            if (empty($json) === false) {
                $options['json'] = $json;
            }
            // Form Data
            if (empty($form_data) === false) {
                $options['multipart'] = $form_data;
            }
            // X WWW Form UrlEncoded
            if (empty($x_www_form_urlencoded) === false) {
                $options['form_params'] = $x_www_form_urlencoded;
            }
            // Raw
            if (trim($raw) !== '') {
                $options['body'] = trim($raw);
            }
            /** @var ResponseInterface $response */
            $response = (new Client())->request($method, $url, $options);
            /** @var string $responseContentType */
            $responseContentType = strtolower(trim($response->getHeader('content-type')[0]));
            if ($responseContentType === 'application/json') {
                /** @var array|null $serviceResponse */
                $serviceResponse = json_decode($response->getBody()->getContents(), true, 512, JSON_OBJECT_AS_ARRAY | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                if (is_array($serviceResponse) === true) {
                    return [
                        'status' => $this->getStatusText($response->getStatusCode()),
                        'content' => $serviceResponse,
                    ];
                }
            }
            return [];
        } catch (RequestException $requestException) {
            return [];
        } catch (GuzzleException $guzzleException) {
            return [];
        } catch (Exception $exception) {
            return [];
        }
    }

    /**
     * @param int $status
     * @return string
     */
    private function getStatusText(int $status)
    {
        // Statuses Text
        /** @var array $statusesText */
        $statusesText = [
            // 1xx
            100 => '100 Continue',
            101 => '101 Switching Protocols',
            102 => '102 Processing',
            103 => '103 Early Hints',
            // 2xx
            200 => '200 OK',
            201 => '201 Created',
            202 => '202 Accepted',
            203 => '203 Non-Authoritative Information',
            204 => '204 No Content',
            205 => '205 Reset Content',
            206 => '206 Partial Content',
            207 => '207 Multi-Status',
            208 => '208 Already Reported',
            226 => '226 IM Used',
            // 3xx
            300 => '300 Multiple Choices',
            301 => '301 Moved Permanently',
            302 => '302 Found (Moved Temporarily)',
            303 => '303 See Other',
            304 => '304 Not Modified',
            305 => '305 Use Proxy',
            306 => '306 Switch Proxy',
            307 => '307 Temporary Redirect',
            308 => '308 Permanent Redirect',
        ];
        // Return Status Text
        return array_key_exists($status, $statusesText) === true ? $statusesText[$status] : '';
    }

    /**
     * @return string
     */
    private function saveToFile()
    {
        // File Name
        /** @var string $fileName */
        $fileName = 'doc.json';
        // Folder Path
        /** @var string $folderPath */
        $folderPath = 'public/lumen-doc/';
        // Folder Full Path In Public Folder
        /** @var string $folderFullPath */
        $folderFullPath = base_path($folderPath);
        // If Folder Is Not Exists , Create It
        if (file_exists($folderFullPath) === false) {
            mkdir($folderFullPath);
        }
        // Save Doc Data To File
        file_put_contents(
            $folderFullPath . $fileName,
            json_encode($this->doc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );
        // Return File Path
        return $folderPath . $fileName;
    }

}
