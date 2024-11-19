<?php

$ROUTES = [];

class Route {
    protected $method = null;
    protected $path = [];
    public $func = null;

    protected function create_path($str, ...$validators) {
        if (gettype($str) != "string")
            throw new Exception("str has to be a string");
        $size = strlen($str);
        if ($size == 0)
            throw new Exception("str cannot be empty");

        $ret = [];
        $start = 0;
        $i = 0;
        $val_count = count($validators);
        $val = 0;

        for (; $i < $size; $i++) {
            if ($str[$i] === '@') {
                if ($i-$start > 0)
                    array_push($ret,substr($str,$start,$i-$start));

                if ($val >= $val_count)
                    throw new Exception("not enough validators given");

                array_push($ret,$validators[$val++]);

                $start = ++$i;
            }
        }
        if ($size-$start > 0)
            array_push($ret,substr($str,$start,$size-$start));

        return $ret;
    }

    function __construct($method, $path, $func, ...$validators) {
        $this->method = $method;
        $this->path = $this->create_path($path,...$validators);
        $this->func = $func;
    }

    protected function check_path($str) {
        $pathl = count($this->path);
        $strl = strlen($str);
        $values = [];

        $strc = 0;
        $pathc = 0;
        while (True) {
            if ($strc >= $strl) {
                if ($pathc >= $pathl)
                    break;
                return [False];
            }
            if ($pathc >= $pathl)
                return [False];

            if (gettype($this->path[$pathc]) === "string") {
                $len = strlen($this->path[$pathc]);
                if ($strc+$len > $strl || (strcasecmp($this->path[$pathc],substr($str,$strc,$len)) != 0))
                    return [False];
                $strc += $len;
            } else {
                $r = $this->path[$pathc](substr($str,$strc),'/');
                if ($r[0] == 0)
                    return [False];
                $strc += $r[0];
                array_push($values,$r[1]);
            }

            $pathc++;
        }

        return [True,$values];
    }

    function check($method, $str) {
        if ($method !== $this->method)
            return [False];
        return $this->check_path($str);
    }
}

function routes_run($method, $path) {
    global $ROUTES;
    $routesl = count($ROUTES);
    $i = 0;
    for (; $i < $routesl; $i++) {
        $r = $ROUTES[$i]->check($method,$path);
        if ($r[0]) {
            if ($ROUTES[$i]->func != null)
                return ($ROUTES[$i]->func)(...$r[1]);
            return null;
        }
    }
    return null;
}

function route_valid_string($str,$last) {
    $r = strtok($str,$last);
    return [strlen($r),$r];
}

function route_valid_uint($str,$last) {
    $val = 0;
    $i = 0;
    $strl = strlen($str);
    for (; $i < $strl; $i++) {
        if (!ctype_digit($str[$i]))
            break;
        $val = ($val*10) + (ord($str[$i])-ord('0'));
    }
    return [$i,$val];
}

function route_valid_int($str,$last) {
    $i = 0;
    $isminus = False;
    $strl = strlen($str);
    if ($i < $strl && $str[$i] == '-') {
        $isminus = True;
        $i++;
    }

    $r = route_valid_uint(substr($str,$i),$last);

    if ($r[0] == 0)
        return $r;

    $val = $r[1];
    if ($isminus)
        $val *= -1;
    return [$i+$r[0],$val];
}

function routes_add($method, $path, $func, ...$validators)
{
    $vall = count($validators);
    for ($i = 0; $i < $vall; $i++) {
        switch ($validators[$i]) {
            case "string": $validators[$i] = function($x,$y) { return route_valid_string($x,$y); }; break;
            case "uint": $validators[$i] = function($x,$y) { return route_valid_uint($x,$y); }; break;
            case "int": $validators[$i] = function($x,$y) { return route_valid_int($x,$y); }; break;
        }
    }


    global $ROUTES;
    array_push($ROUTES,new Route(strtoupper($method),$path,$func,...$validators));
}

function routes_get($path, $func, ...$validators) {
    return routes_add('get',$path,$func,...$validators);
}

function routes_post($path, $func, ...$validators) {
    return routes_add('post',$path,$func,...$validators);
}

function routes_put($path, $func, ...$validators) {
    return routes_add('put',$path,$func,...$validators);
}

function routes_delete($path, $func, ...$validators) {
    return routes_add('delete',$path,$func,...$validators);
}

function routes_patch($path, $func, ...$validators) {
    return routes_add('patch',$path,$func,...$validators);
}

?>
