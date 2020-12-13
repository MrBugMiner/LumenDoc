<?php

namespace MrBugMiner\LumenDoc\Classes;

use Illuminate\Console\Command;
use Laravel\Lumen\Application;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class Scan
{

    // Scan Data
    /** @var array $scan */
    private $scan = [
        'groups' => [],
        'routes' => [],
    ];

    /**
     * @param Command $console
     */
    public function handle(Command $console)
    {
        // Get Routes
        $console->info('Get Routes.');
        /** @var array $routes */
        $routes = $this->getRoutes();
        // Process @ Lines
        $console->info('Process @ Lines.');
        $routes = $this->processAtSignLines($routes);
        // Add Routes To Scan Data
        $this->scan['routes'] = $routes;
        // Save To File
        /** @var string $filePath */
        $filePath = $this->saveToFile();
        $console->info('Save Scan Data To "' . $filePath . '" File.');
    }

    /**
     * @return array
     */
    private function getControllerValidAtSigns()
    {
        return [
        ];
    }

    /**
     * @return array
     */
    private function getControllerMethodValidAtSigns()
    {
        return [
            '@group',
            '@description',
            '@urlParam',
            '@queryParam',
            '@bodyParam',
            '@header',
            '@request',
            '@tokenRequest',
        ];
    }

    /**
     * @return array
     */
    private function getRoutes()
    {
        // Get Application
        /** @var Application $app */
        $app = app();
        // Get All Routes
        /** @var array $allRoutes */
        $allRoutes = property_exists($app, 'router') === true ?
            $app->router->getRoutes()
            :
            $app->getRoutes();
        // Routes
        /** @var array $routes */
        $routes = [];
        // Process All Routes
        foreach ($allRoutes as $route) {
            // Uses
            /** @var string $uses */
            $uses = empty($route['action']['uses']) === false ? trim($route['action']['uses']) : '';
            // Controller
            /** @var string $controller */
            $controller = '';
            if ($uses !== '') {
                $controller = current(explode("@", $uses));
            }
            // Controller Method
            /** @var string $controllerMethod */
            $controllerMethod = '';
            if ($uses !== '') {
                /** @var int $atSignPos */
                if (($atSignPos = strpos($uses, "@")) !== false) {
                    $controllerMethod = substr($uses, $atSignPos + 1);
                }
            }
            // Add Route Info To Routes
            $routes[] = [
                'group' => [],
                'description' => '',
                'method' => trim($route['method']),
                'url' => trim($route['uri']),
                'headers' => [],
                'url_params' => [],
                'query_params' => [],
                'body_params' => [],
                'requests' => [],
                'controller' => $controller,
                'controller_method' => $controllerMethod,
            ];
        }
        // Return Routes
        return $routes;
    }

    /**
     * @param array $routes
     * @return array
     */
    private function processAtSignLines(array $routes)
    {
        // Controller Valid At Signs
        /** @var array $controllerValidAtSigns */
        $controllerValidAtSigns = $this->getControllerValidAtSigns();
        // Controller @ Lines Pattern
        /** @var string $controllerAtSignLinesPattern */
        $controllerAtSignLinesPattern = "/(" . implode('|', $controllerValidAtSigns) . ")\ (.+)/";
        // Controller Method Valid At Signs
        /** @var array $controllerMethodValidAtSigns */
        $controllerMethodValidAtSigns = $this->getControllerMethodValidAtSigns();
        // Controller Method @ Lines Pattern
        /** @var string $controllerMethodAtSignLinesPattern */
        $controllerMethodAtSignLinesPattern = "/(" . implode('|', $controllerMethodValidAtSigns) . ")\ (.+)/";
        // Process All Routes
        /** @var int $routeIndex */
        /** @var array $route */
        foreach ($routes as $routeIndex => $route) {
            try {
                // Get Controller
                /** @var string $controller */
                $controller = $route['controller'];
                // Get Controller Method
                /** @var string $controllerMethod */
                $controllerMethod = $route['controller_method'];
                // Delete Controller And Controller Method From Route
                unset($route['controller'], $route['controller_method']);
                // If Controller Is Exists , Get And Process Controller @ Lines
                if (class_exists($controller) === true) {
                    // Get Controller Info
                    /** @var ReflectionClass $controllerInfo */
                    $controllerInfo = new ReflectionClass($controller);
                    // Get Controller Comment
                    /** @var string|bool $controllerComment */
                    $controllerComment = $controllerInfo->getDocComment();
                    // If Controller Has Comment , Get Controller @ Lines
                    if ($controllerComment !== false) {
                        // Controller @ Lines
                        /** @var array $controllerAtSignLines */
                        $controllerAtSignLines = [];
                        // Get Controller @ Lines
                        preg_match_all($controllerAtSignLinesPattern, $controllerComment, $controllerAtSignLines, PREG_PATTERN_ORDER);
                        // Process Controller At Sign Lines
                        /** @var int $atSignWordIndex */
                        /** @var string $atSignWord */
                        /** @var string $function */
                        foreach ($controllerAtSignLines[1] as $atSignWordIndex => $atSignWord) {
                            $route = $this->processAtSignLine(
                                $route,
                                $atSignWord,
                                $controllerAtSignLines[2][$atSignWordIndex] // At Sign Value
                            );
                        }
                    }
                }
                // If Controller Method Is Exists , Get And Process Controller Method @ Lines
                if (method_exists($controller, $controllerMethod) === true) {
                    // Get Controller Method Info
                    /** @var ReflectionMethod $controllerMethodInfo */
                    $controllerMethodInfo = new ReflectionMethod($controller, $controllerMethod);
                    // Get Controller Method Comment
                    /** @var string|bool $controllerMethodComment */
                    $controllerMethodComment = $controllerMethodInfo->getDocComment();
                    // If Controller Method Has Comment , Get Controller Method @ Lines
                    if ($controllerMethodComment !== false) {
                        // Controller Method @ Lines
                        /** @var array $controllerMethodAtSignLines */
                        $controllerMethodAtSignLines = [];
                        // Get Controller Method @ Lines
                        preg_match_all($controllerMethodAtSignLinesPattern, $controllerMethodComment, $controllerMethodAtSignLines, PREG_PATTERN_ORDER);
                        // Process Controller Method @ Lines
                        /** @var int $atSignWordIndex */
                        /** @var string $atSignWord */
                        /** @var string $function */
                        foreach ($controllerMethodAtSignLines[1] as $atSignWordIndex => $atSignWord) {
                            $route = $this->processAtSignLine(
                                $route,
                                $atSignWord,
                                $controllerMethodAtSignLines[2][$atSignWordIndex] // At Sign Value
                            );
                        }
                    }
                }
                // Remove Route Params Keys
                if (empty($route['url_params']) === false) {
                    $route['url_params'] = array_values($route['url_params']);
                }
                if (empty($route['query_params']) === false) {
                    $route['query_params'] = array_values($route['query_params']);
                }
                if (empty($route['body_params']) === false) {
                    $route['body_params'] = array_values($route['body_params']);
                }
                // Update Route In Routes
                $routes[$routeIndex] = $route;
            } catch (ReflectionException $reflectionException) {
            }
        }
        // Return Routes With @ Lines
        return $routes;
    }

    /**
     * @param array $route
     * @param string $atSignWord
     * @param string $atSignValue
     * @return array
     */
    private function processAtSignLine(array $route, string $atSignWord, string $atSignValue)
    {
        switch ($atSignWord) {
            case '@group':
                return $this->processAtSignGroup($route, $atSignValue);
            case '@description':
                return $this->processAtSignDescription($route, $atSignValue);
            case '@urlParam':
                return $this->processAtSignParam($route, $atSignValue, 'url');
            case '@queryParam':
                return $this->processAtSignParam($route, $atSignValue, 'query');
            case '@bodyParam':
                return $this->processAtSignParam($route, $atSignValue, 'body');
            case '@header':
                return $this->processAtSignHeader($route, $atSignValue);
            case '@request':
                return $this->processAtSignRequest($route, $atSignValue, false);
            case '@tokenRequest':
                return $this->processAtSignRequest($route, $atSignValue, true);
            default:
                return $route;
        }
    }

    /**
     * @param array $route
     * @param string $atSignValue
     * @return array
     */
    private function processAtSignGroup(array $route, string $atSignValue)
    {
        // Trim @ Value
        $atSignValue = trim($atSignValue);
        // If @ Value Is Empty , Return
        if ($atSignValue === '') {
            return $route;
        }
        // @ Value Must Start With "/"
        if ($atSignValue[0] !== '/') {
            $atSignValue = '/' . $atSignValue;
        }
        // Get Groups From @ Value
        /** @var array $groups */
        $groups = explode('/', $atSignValue);
        // Ignore First Element Of Groups [FOR NOW]
        array_shift($groups);
        // Route Groups
        /** @var array $routeGroups */
        $routeGroups = [];
        // Groups Count
        /** @var int $groupsCount */
        $groupsCount = count($groups);
        // Add Groups To Scan Data Groups
        $parentGroup = &$this->scan['groups'];
        /** @var int $i */
        /** @var string $currentGroup */
        for ($i = 0; $i < $groupsCount; $i++) {
            // Get Current Group
            $currentGroup = trim($groups[$i]);
            // If Current Group Is Empty , Ignore Current Group
            if ($currentGroup === '') {
                continue;
            }
            // Add Current Group To Route Groups
            $routeGroups[] = $currentGroup;
            // If Current Group Is Not In Parent Group
            if (isset($parentGroup[$currentGroup]) === false) {
                // Add Current Group To Parent Group
                $parentGroup[$currentGroup] = [
                    'title' => '',
                    'description' => '',
                    'groups' => [],
                ];
                // Key Sort Parent Group Items
                ksort($parentGroup);
            }
            // Use Current Group As Parent Group
            $parentGroup = &$parentGroup[$currentGroup]['groups'];
        }
        // Key Sort Scan Data Groups
        ksort($this->scan['groups']);
        // Add Route Groups To Route
        $route['group'] = $routeGroups;
        // Return Route
        return $route;
    }

    /**
     * @param array $route
     * @param string $atSignValue
     * @return array
     */
    private function processAtSignDescription(array $route, string $atSignValue)
    {
        // Add Description To Route
        $route['description'] = trim($atSignValue);
        // Return Route
        return $route;
    }

    /**
     * @param array $route
     * @param string $atSignValue
     * @param string $atSignParamType
     * @return array
     */
    private function processAtSignParam(array $route, string $atSignValue, string $atSignParamType)
    {
        // Trim @ Value
        $atSignValue = trim($atSignValue);
        // Get Items From @ Value
        /** @var array $items */
        $items = explode('||', $atSignValue, 3);
        // Type , Name , Value , Description
        /** @var string $type */
        $type = '';
        /** @var string $name */
        /** @var float|int|array|null|string $value */
        /** @var string $description */
        $description = '';
        // Get Name , Type , Value , Description From Items
        switch (count($items)) {
            case 1: // Name = Value
                // Get Name And Value
                list($name, $value) = $this->getNameValue($items[0], true, null, '');
                break;
            case 2: // Type || Name = Value
                // Get Type
                $type = $this->getType($items[0]);
                // Get Name And Value
                list($name, $value) = $this->getNameValue($items[1], true, null, $type);
                break;
            case 3: // Type || Name = Value || Description
                // Get Type
                $type = $this->getType($items[0]);
                // Get Name And Value
                list($name, $value) = $this->getNameValue($items[1], true, null, $type);
                // Get Description
                $description = $this->getDescription($items[2]);
                break;
            default: // If Param Items Count Is Invalid , Ignore This Param
                return $route;
        }
        // Add Param To Route
        $route["{$atSignParamType}_params"][$name] = [
            'name' => $name,
            'type' => $type,
            'default' => $value,
            'description' => $description,
        ];
        // Return Route
        return $route;
    }

    /**
     * @param array $route
     * @param string $atSignValue
     * @return array
     */
    private function processAtSignHeader(array $route, string $atSignValue)
    {
        // Trim @ Value
        $atSignValue = trim($atSignValue);
        // Get Items From @ Value
        /** @var array $items */
        $items = explode('||', $atSignValue, 2);
        // Name , Value , Description
        /** @var string $name */
        /** @var float|int|array|null|string $value */
        /** @var string $description */
        $description = '';
        // Get Name , Value , Description From Items
        switch (count($items)) {
            case 1: // Name = Value
                // Get Name And Value
                list($name, $value) = $this->getNameValue($items[0], false, null, '');
                break;
            case 2: // Name = Value || Description
                // Get Name And Value
                list($name, $value) = $this->getNameValue($items[0], false, null, '');
                // Get Description
                $description = $this->getDescription($items[1]);
                break;
            default: // If Header Items Count Is Invalid , Ignore This Header
                return $route;
        }
        // Add Header To Route
        $route["headers"][] = [
            'name' => $name,
            'value' => $value,
            'description' => $description,
        ];
        // Return Route
        return $route;
    }

    /**
     * @param array $route
     * @param string $atSignValue
     * @param bool $token
     * @return array
     */
    private function processAtSignRequest(array $route, string $atSignValue, bool $token)
    {
        // Trim @ Value
        $atSignValue = trim($atSignValue);
        // Get Items From @ Value
        /** @var array $items */
        $items = explode('||', $atSignValue, 2);
        // Params , Description
        /** @var array $params */
        /** @var string $description */
        $description = '';
        // Get Params , Description From Items
        switch (count($items)) {
            case 1: // Param1 = Value1 ; Param2
                // Get Params
                $params = $this->getParams($items[0], $route);
                break;
            case 2: // Param1 = Value1 ; Param2 || Description
                // Get Params
                $params = $this->getParams($items[0], $route);
                // Get Description
                $description = $this->getDescription($items[1]);
                break;
            default: // If Request Items Count Is Invalid , Ignore This Request
                return $route;
        }
        // Add Request To Route
        $route["requests"][] = [
            'token' => $token,
            'params' => $params,
            'description' => $description,
            'response' => [],
        ];
        // Return Route
        return $route;
    }

    /**
     * @param string $params
     * @param array $route
     * @return array
     */
    private function getParams(string $params, array $route)
    {
        // Params List
        /** @var array $paramsList */
        $paramsList = [];
        // Get And Process Params
        foreach (explode(';', $params) as $param) {
            // Trim Param
            $param = trim($param);
            // If Param Is Empty , Ignore This Param
            if ($param === '') {
                continue;
            }
            // Get Name And Value
            /** @var string $name */
            /** @var float|int|array|null|string $value */
            list($name, $value) = $this->getNameValue($param, true, $route, '');
            // Add Param To Params List
            $paramsList[$name] = $value;
        }
        // Return Params List
        return $paramsList;
    }

    /**
     * @param string $name
     * @param bool $addSigil
     * @param array $route
     * @param string $type
     * @return array
     */
    private function getNameValue(string $name, bool $addSigil, array $route, string $type)
    {
        // Trim Name
        $name = trim($name);
        // If Add Sigil Is True , Name Must Start With "$"
        if ($addSigil === true && $name[0] !== '$') {
            $name = '$' . $name;
        }
        // Get Name And Value
        /** @var array $nameValue */
        $nameValue = explode('=', $name, 2);
        // Get Name From Name Value
        /** @var string $name */
        $name = trim($nameValue[0]);
        // Get Value From Name Value
        /** @var string|float|int|array|null $value */
        if (isset($nameValue[1]) === true) {
            // Trim Value
            $value = trim($nameValue[1]);
            // If Value Is Not Empty
            if ($value !== '') {
                // Normalize Value
                $value = eval("return $value;");
                // Return Name And Value
                return [$name, $value];
            }
        }
        // If Route Is Not Null , Get Default Value From Route Params
        if ($route !== null) {
            /** @var bool $getDefaultValue */
            $getDefaultValue = false;
            // Route Url Params
            if (array_key_exists($name, $route['url_params']) === true) {
                $getDefaultValue = true;
                $value = $route['url_params'][$name]['default'];
            }
            // Route Query Params
            if (array_key_exists($name, $route['query_params']) === true) {
                $getDefaultValue = true;
                $value = $route['query_params'][$name]['default'];
            }
            // Route Body Params
            if (array_key_exists($name, $route['body_params']) === true) {
                $getDefaultValue = true;
                $value = $route['body_params'][$name]['default'];
            }
            // If Default Value Get From Route Params , Return Name And Value
            if ($getDefaultValue === true) {
                return [$name, $value];
            }
        }
        // Set Default Value
        switch ($type) {
            case 'int':
            case 'integer':
                $value = 0;
                break;
            case 'float':
            case 'double':
                $value = 0.0;
                break;
            case 'array':
                $value = [];
                break;
            case '':
            case 'string':
            default:
                $value = '';
                break;
        }
        // Return Name And Value
        return [$name, $value];
    }

    /**
     * @param string $type
     * @return string
     */
    private function getType(string $type)
    {
        /** @var array $validTypes */
        $validTypes = ['string', 'int', 'integer', 'float', 'double', 'array'];
        $type = strtolower(trim($type));
        return in_array($type, $validTypes) === true ? $type : '';
    }

    /**
     * @param string $description
     * @return string
     */
    private function getDescription(string $description)
    {
        return trim($description);
    }

    /**
     * @return string
     */
    private function saveToFile()
    {
        // File Name
        /** @var string $fileName */
        $fileName = 'scan.json';
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
        // Save Scan Data To File
        file_put_contents(
            $folderFullPath . $fileName,
            json_encode($this->scan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );
        // Return File Path
        return $folderPath . $fileName;
    }

}
