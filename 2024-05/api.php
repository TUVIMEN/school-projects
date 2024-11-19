<?php

function options_headers() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
}

header("Content-Type: application/json; charset=UTF-8");
options_headers();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri );

$rmethod = $_SERVER["REQUEST_METHOD"];

error_reporting(E_ALL);
ini_set('display_errors',1);
$conn = mysqli_connect("localhost:3306","cubes","Calcium","xen_forum");
if (!$conn) {
    die('Could not connect to database');
}

function api_login($conn,$uri,$rmethod) {
    if ($rmethod !== "POST") {
        echo json_encode(['error' => 'Invalid Method']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $input = (array)json_decode(file_get_contents('php://input'), TRUE);

    if (!isset($input['email']) || !preg_match("/^[A-Za-z0-9.]+@[A-Za-z]([A-Za-z0-9]+\.)+[A-Za-z]+$/",$input['email']) || !isset($input['password']) || preg_match("/[\"']/",$input['password'])) {
        echo json_encode(['error' => 'Invalid input']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,'select phash,psalt from members where email = "' . $input['email'] . '";');
    if (mysqli_num_rows($re) == 0) {
        echo json_encode(['error' => 'Invalid email']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $row = mysqli_fetch_assoc($re);
    $hash = hash_pbkdf2('sha256',$input['password'],hex2bin($row['psalt']),1024,32);
    if ($hash !== $row['phash']) {
        echo json_encode(['error' => 'Incorrect password']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }
    echo '"' . $hash . '"';
    header('HTTP/1.1 200 OK');
}

function api_register($conn,$uri,$rmethod) {
    if ($rmethod !== "POST") {
        echo json_encode(['error' => 'Invalid Method']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $input = (array)json_decode(file_get_contents('php://input'), TRUE);

    if (!isset($input['email']) || !preg_match("/^[A-Za-z0-9.]+@[A-Za-z]([A-Za-z0-9]+\.)+[A-Za-z]+$/",$input['email']) || !isset($input['password']) || preg_match("/[\"']/",$input['password']) || !isset($input['name']) || preg_match("/[\"']/",$input['name'])) {
        echo json_encode(['error' => 'Invalid input']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select id from members where name='" . $input['name'] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck !== 0) {
        echo json_encode(['error' => 'User already exists']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select id from members where email='" . $input['email'] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck !== 0) {
        echo json_encode(['error' => 'There already is user with this email']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $salt = openssl_random_pseudo_bytes(16);
    $hash = hash_pbkdf2('sha256',$input['password'],$salt,1024,32);
    mysqli_query($conn,"insert into members values(null," .
        ((isset($input['location'])) ? "'" . $input['location'] . "'," : "null,") .
        ((isset($input['avatar'])) ? "'" . $input['avatar'] . "'," : "null,") .
        ((isset($input['joined'])) ? "'" . $input['joined'] . "'," : "null,") .
        ((isset($input['lastseen'])) ? "'" . $input['lastseen'] . "'," : "null,") .
        ((isset($input['title'])) ? "'" . $input['title'] . "'," : "null,") .
        "'" . $input['name'] . "'," .
        ((isset($input['messages'])) ? $input['messages'] . "," : "null,") .
        ((isset($input['reactionscore'])) ? $input['reactionscore'] . "," : "null,") .
        ((isset($input['points'])) ? $input['points'] . "," : "null,") .
        "'" . $input['email'] . "','" .
        $hash . "','" .
        bin2hex($salt) . "');");
    header('HTTP/1.1 201 Created');
    echo "[]";
}

function api_thread($conn,$uri,$rmethod) {
    if ($rmethod !== "GET") {
        echo json_encode(['error' => 'Invalid Method']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    if (!isset($uri[3]) || !preg_match("/^[0-9a-f]{32}$/",$uri[3]) || !isset($uri[4]) || !preg_match("/^[0-9]+$/",$uri[4])) {
        echo json_encode(['error' => 'Invalid thread_id']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select id from members where phash='" . $uri[3] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck == 0) {
        echo json_encode(['error' => 'Bad auth key']);
        //header('HTTP/1.1 403 Forbidden');
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select * from threads where id='" . $uri[4] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck == 0) {
        echo json_encode(['error' => 'There is no such thread']);
        //header('HTTP/1.1 404 Not Found');
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $trow = mysqli_fetch_assoc($re);

    $allposts =  $trow['posts'];
    $limitposts = 30;
    $lastpage = floor($allposts/$limitposts)+1;
    $trow['posts'] = array();
    $trow['lastpage'] = $lastpage;
    $trow['currentpage'] = 1;

    if ($allposts > 0) {
        $page = 1;
        if (isset($uri[5]))
            $page = intval($uri[5]);
        if ($lastpage < $page)
            $page = $lastpage;
        if ($page < 1)
            $page = 1;
        $trow['currentpage'] = $page;
        $re = mysqli_query($conn,"select * from posts where thread_id='" . $trow['id'] . "' limit " . ($limitposts*($page-1)) . ' , ' . $limitposts . ";");
        while ($prow = mysqli_fetch_assoc($re)) {
            $member = mysqli_query($conn,"select location,avatar,joined,lastseen,title,messages,reactionscore,points from members where id='" . $prow['user_id'] . " limit 1;'");
            if ($mrow = mysqli_fetch_assoc($member))
                foreach ($mrow as $k => $v)
                    $prow["user_" . $k] = $v;

            if ($prow['reactions'] == 0) {
                $prow['reactions'] = array();
            } else if ($prow['reactions'] > 0) {
                $prow['reactions'] = array();
                $rre = mysqli_query($conn,"select * from reactions where post_id='" . $prow['id'] . "';");
                while ($rrow = mysqli_fetch_assoc($rre)) {
                    $prow['reactions'][] = $rrow;
                }
            }
            $trow['posts'][] = $prow;
        }
    }

    header('HTTP/1.1 200 OK');
    echo json_encode($trow);
}

function api_tags($conn,$uri,$rmethod) {
    if ($rmethod !== "GET") {
        echo json_encode(['error' => 'Invalid Method']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    if (!isset($uri[3]) || !preg_match("/^[0-9a-f]{32}$/",$uri[3])) {
        echo json_encode(['error' => 'Invalid auth key']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select id from members where phash='" . $uri[3] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck == 0) {
        echo json_encode(['error' => 'Bad auth key']);
        //header('HTTP/1.1 403 Forbidden');
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select * from tags;");

    $out = array();

    while($row = mysqli_fetch_assoc($re))
        $out[] = $row;

    header('HTTP/1.1 200 OK');
    echo json_encode($out);
}

function api_paths($conn,$uri,$rmethod) {
    if ($rmethod !== "GET") {
        echo json_encode(['error' => 'Invalid Method']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    if (!isset($uri[3]) || !preg_match("/^[0-9a-f]{32}$/",$uri[3])) {
        echo json_encode(['error' => 'Invalid auth key']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select id from members where phash='" . $uri[3] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck == 0) {
        echo json_encode(['error' => 'Bad auth key']);
        //header('HTTP/1.1 403 Forbidden');
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select * from paths;");

    $out = array();

    while($row = mysqli_fetch_assoc($re))
        $out[] = $row;

    header('HTTP/1.1 200 OK');
    echo json_encode($out);
}

function api_view($conn,$uri,$rmethod) {
    if ($rmethod !== "GET") {
        echo json_encode(['error' => 'Invalid Method']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    if (!isset($uri[3]) || !preg_match("/^[0-9a-f]{32}$/",$uri[3]) || !isset($uri[4]) || !preg_match("/^[0-9]+$/",$uri[4])) {
        echo json_encode(['error' => 'Invalid path_id']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select id from members where phash='" . $uri[3] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck == 0) {
        echo json_encode(['error' => 'Bad auth key']);
        //header('HTTP/1.1 403 Forbidden');
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select * from paths where id='" . $uri[4] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck == 0) {
        echo json_encode(['error' => 'There is no such path']);
        //header('HTTP/1.1 404 Not Found');
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $trow = mysqli_fetch_assoc($re);

    $out = array();

    $allposts =  $trow['count'];
    $limitposts = 30;
    $lastpage = floor($allposts/$limitposts)+1;
    $out['lastpage'] = $lastpage;
    $out['currentpage'] = 1;
    $out['threads'] = array();

    if ($allposts > 0) {
        $page = 1;
        if (isset($uri[5]))
            $page = intval($uri[5]);
        if ($lastpage < $page)
            $page = $lastpage;
        if ($page < 1)
            $page = 1;
        $out['currentpage'] = $page;
        $re = mysqli_query($conn,"select * from threads where path='" . $trow['path'] . "' limit " . ($limitposts*($page-1)) . ' , ' . $limitposts . ";");
        while ($row = mysqli_fetch_assoc($re))
            $out['threads'][] = $row;
    }

    header('HTTP/1.1 200 OK');
    echo json_encode($out);
}

function api_vtag($conn,$uri,$rmethod) {
    if ($rmethod !== "GET") {
        echo json_encode(['error' => 'Invalid Method']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    if (!isset($uri[3]) || !preg_match("/^[0-9a-f]{32}$/",$uri[3]) || !isset($uri[4]) || !preg_match("/^[0-9]+$/",$uri[4])) {
        echo json_encode(['error' => 'Invalid tag_id']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select id from members where phash='" . $uri[3] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck == 0) {
        echo json_encode(['error' => 'Bad auth key']);
        //header('HTTP/1.1 403 Forbidden');
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select * from tags where id='" . $uri[4] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck == 0) {
        echo json_encode(['error' => 'There is no such tag']);
        //header('HTTP/1.1 404 Not Found');
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $trow = mysqli_fetch_assoc($re);

    $out = array();

    $allposts =  $trow['count'];
    $limitposts = 30;
    $lastpage = floor($allposts/$limitposts)+1;
    $out['lastpage'] = $lastpage;
    $out['currentpage'] = 1;
    $out['threads'] = array();

    if ($allposts > 0) {
        $page = 1;
        if (isset($uri[5]))
            $page = intval($uri[5]);
        if ($lastpage < $page)
            $page = $lastpage;
        if ($page < 1)
            $page = 1;
        $out['currentpage'] = $page;
        $re = mysqli_query($conn,"select * from threads where tags like '%" . $trow['tag'] . "%' limit " . ($limitposts*($page-1)) . ' , ' . $limitposts . ";");
        while ($row = mysqli_fetch_assoc($re))
            $out['threads'][] = $row;
    }

    header('HTTP/1.1 200 OK');
    echo json_encode($out);
}

function api_search($conn,$uri,$rmethod) {
    if ($rmethod !== "POST") {
        echo json_encode(['error' => 'Invalid Method']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $input = (array)json_decode(file_get_contents('php://input'), TRUE);

    if (!isset($input['auth_key']) || !preg_match("/^[0-9a-f]{32}$/",$input['auth_key']) || (isset($input['tag']) && preg_match("/['\"]/",$input['tag']))  || (isset($input['path']) && preg_match("/['\"]/",$input['path'])) || (isset($input['search']) && preg_match("/['\"]/",$input['search'])) || (isset($input['option']) && preg_match("/['\"]/",$input['option'])) || !(isset($input['tag']) || isset($input['search']) || isset($input['path']))) {
        echo json_encode(['error' => 'Invalid field']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select id from members where phash='" . $input['auth_key'] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck == 0) {
        echo json_encode(['error' => 'Bad auth key']);
        //header('HTTP/1.1 403 Forbidden');
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select * from threads where " .
        ((isset($input['tag'])) ? "tags like '%" . $input['tag'] . "%'" : "") .
        ((isset($input['tag']) && isset($input['path'])) ? " and " : "") .
        ((isset($input['path'])) ? "path = '" . $input['path'] . "'" : "") .
        (((isset($input['tag']) || isset($input['path'])) && isset($input['search'])) ? " and " : "") .
        ((isset($input['search'])) ? "title like '%" . $input['search'] . "%'" : "") .
        " limit 200;");

    $out = array();

    while ($row = mysqli_fetch_assoc($re))
        $out[] = $row;

    header('HTTP/1.1 200 OK');
    echo json_encode($out);
}

function api_nthread($conn,$uri,$rmethod) {
    if ($rmethod !== "POST") {
        echo json_encode(['error' => 'Invalid Method']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $input = (array)json_decode(file_get_contents('php://input'), TRUE);

    if (!isset($input['auth_key']) || !preg_match("/^[0-9a-f]{32}$/",$input['auth_key']) || !isset($input['title']) || preg_match("/^['\"]+$/",$input['title']) || !isset($input['path']) || preg_match("/[\"']/",$input['path']) || (isset($input['tags']) && preg_match("/[\"']/",$input['tags']))) {
        echo json_encode(['error' => 'Invalid input']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select * from members where phash='" . $input['auth_key'] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck == 0) {
        echo json_encode(['error' => 'Bad auth key']);
        //header('HTTP/1.1 403 Forbidden');
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $row = mysqli_fetch_assoc($re);

    $user = $row['name'];
    $user_id = $row['id'];

    mysqli_query($conn,"insert into threads values (null,\"" . $input['title'] . "\"," . $user_id . ",\"" . $user . "\",\"" . date('Y-m-d H:i:s') . "\",\"" . $input['path'] . "\"," . ((isset($input['tags'])) ? "\"" . $input['tags'] . "\"" : "null") . ",0);");
    $last_id = mysqli_insert_id($conn);

    if (isset($input['tags'])) {
        $tags = explode(" ",$input['tags']);
        for ($i = 0; $i < count($tags); $i++) {
            $re = mysqli_query($conn,"select id from tags where tag='" . $tags[$i] . "';");
            $resultcheck = mysqli_num_rows($re);
            if ($resultcheck == 0) {
                mysqli_query($conn,"insert into tags values (null,\"" . $tags[$i] . "\",1);");
            } else {
                $row = mysqli_fetch_assoc($re);
                mysqli_query($conn,"update tags set count = count+1 where id=" . $row['id'] . ";");
            }
        }
    }

    $re = mysqli_query($conn,"select id from paths where path='" . $input['path'] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck == 0) {
        mysqli_query($conn,"insert into paths values (null,\"" . $input['path'] . "\",1);");
    } else {
        $row = mysqli_fetch_assoc($re);
        mysqli_query($conn,"update paths set count = count+1 where id=" . $row['id'] . ";");
    }

    header('HTTP/1.1 201 Created');
    echo $last_id;
}

function api_npost($conn,$uri,$rmethod) {
    if ($rmethod !== "POST") {
        echo json_encode(['error' => 'Invalid Method']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $input = (array)json_decode(file_get_contents('php://input'), TRUE);

    if (!isset($input['auth_key']) || !preg_match("/^[0-9a-f]{32}$/",$input['auth_key']) || !isset($input['text']) || preg_match("/^['\"]+$/",$input['text']) || !isset($input['thread_id']) || !preg_match("/^[0-9]+$/",$input['thread_id'])) {
        echo json_encode(['error' => 'Invalid input']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select * from members where phash='" . $input['auth_key'] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck == 0) {
        echo json_encode(['error' => 'Bad auth key']);
        //header('HTTP/1.1 403 Forbidden');
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $row = mysqli_fetch_assoc($re);

    $user = $row['name'];
    $user_id = $row['id'];

    $re = mysqli_query($conn,"select id from threads where id='" . $input['thread_id'] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck == 0) {
        echo json_encode(['error' => 'There is no such thread']);
        //header('HTTP/1.1 404 Not Found');
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    mysqli_query($conn,"insert into posts values (null," . $user_id . ",\"" . $user . "\",\"" . date('Y-m-d H:i:s') . "\",\"" . $input['text'] . "\"," . $input['thread_id'] . ",0);");
    $last_id = mysqli_insert_id($conn);

    mysqli_query($conn,"update threads set posts = posts+1 where id=" . $input['thread_id'] . ";");

    header('HTTP/1.1 201 Created');
    echo $last_id;
}

function api_nreaction($conn,$uri,$rmethod) {
    if ($rmethod !== "POST") {
        echo json_encode(['error' => 'Invalid Method']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $input = (array)json_decode(file_get_contents('php://input'), TRUE);

    if (!isset($input['auth_key']) || !preg_match("/^[0-9a-f]{32}$/",$input['auth_key']) || !isset($input['reaction']) || preg_match("/^['\"]+$/",$input['reaction']) || !isset($input['post_id']) || !preg_match("/^[0-9]+$/",$input['post_id'])) {
        echo json_encode(['error' => 'Invalid input']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select * from members where phash='" . $input['auth_key'] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck == 0) {
        echo json_encode(['error' => 'Bad auth key']);
        //header('HTTP/1.1 403 Forbidden');
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $row = mysqli_fetch_assoc($re);

    $user = $row['name'];
    $user_id = $row['id'];

    $re = mysqli_query($conn,"select id from posts where id='" . $input['post_id'] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck == 0) {
        echo json_encode(['error' => 'There is no such thread']);
        //header('HTTP/1.1 404 Not Found');
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    mysqli_query($conn,"insert into reactions values (null," . $user_id . ",\"" . $user . "\",\"" . date('Y-m-d H:i:s') . "\",\"" . $input['reaction'] . "\"," . $input['post_id'] . ");");
    $last_id = mysqli_insert_id($conn);

    mysqli_query($conn,"update posts set reactions = reactions+1 where id=" . $input['post_id'] . ";");

    header('HTTP/1.1 201 Created');
    echo $last_id;
}

function api_member($conn,$uri,$rmethod) {
    if ($rmethod !== "GET") {
        echo json_encode(['error' => 'Invalid Method']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    if (!isset($uri[3]) || !preg_match("/^[0-9a-f]{32}$/",$uri[3])) {
        echo json_encode(['error' => 'Invalid auth key']);
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    $re = mysqli_query($conn,"select * from members where phash='" . $uri[3] . "';");
    $resultcheck = mysqli_num_rows($re);
    if ($resultcheck == 0) {
        echo json_encode(['error' => 'Bad auth key']);
        //header('HTTP/1.1 403 Forbidden');
        header('HTTP/1.1 422 Unprocessable Entity');
        return;
    }

    header('HTTP/1.1 200 OK');
    echo json_encode(mysqli_fetch_assoc($re));
}

if ($rmethod == 'OPTIONS') {
    options_headers();
    return;
}

switch($uri[2]) {
    case "login":
        api_login($conn,$uri,$rmethod);
        break;
    case "register":
        api_register($conn,$uri,$rmethod);
        break;
    case "thread":
        api_thread($conn,$uri,$rmethod);
        break;
    case "tags":
        api_tags($conn,$uri,$rmethod);
        break;
    case "paths":
        api_paths($conn,$uri,$rmethod);
        break;
    case "view":
        api_view($conn,$uri,$rmethod);
        break;
    case "vtag":
        api_vtag($conn,$uri,$rmethod);
        break;
    case "search":
        api_search($conn,$uri,$rmethod);
        break;
    case "nthread":
        api_nthread($conn,$uri,$rmethod);
        break;
    case "npost":
        api_npost($conn,$uri,$rmethod);
        break;
    case "nreaction":
        api_nreaction($conn,$uri,$rmethod);
        break;
    case "member":
        api_member($conn,$uri,$rmethod);
        break;
    default:
        break;
}

//curl -D - -X POST -d '{"name":"loop","email":"loop@loop.com","password":"Calcium"}' 'http://127.0.0.1/api/register'
//curl -D - -X POST -d '{"email":"loop@loop.com","password":"Calcium"}' 'http://127.0.0.1/api/login'
//curl 'http://127.0.0.1/api/thread/2c0f851c58208280830da40ec95c26b8/2/1'
//curl 'http://127.0.0.1/api/paths/2c0f851c58208280830da40ec95c26b8'
//curl 'http://127.0.0.1/api/tags/2c0f851c58208280830da40ec95c26b8'
//curl 'http://127.0.0.1/api/view/2c0f851c58208280830da40ec95c26b8/20/1'
//curl 'http://127.0.0.1/api/vtag/2c0f851c58208280830da40ec95c26b8/1/1'
//curl  -X POST -d '{"auth_key":"2c0f851c58208280830da40ec95c26b8","search":"dog","tag":"dog","path":"Lifestyle\/Products\/"}' 'http://127.0.0.1/api/search'
//curl  -X POST -d '{"auth_key":"2c0f851c58208280830da40ec95c26b8","title":"dog","tags":"uirhqiuwh nkau loias","path":"Lifestyle\/Products\/"}' 'http://127.0.0.1/api/nthread'
//curl  -X POST -d '{"auth_key":"2c0f851c58208280830da40ec95c26b8","text":"dog","thread_id":17952}' 'http://127.0.0.1/api/npost'
//curl  -X POST -d '{"auth_key":"2c0f851c58208280830da40ec95c26b8","reaction":"Like","post_id":490815}' 'http:/127.0.0.1/api/nreaction'
//curl 'http://127.0.0.1/api/member/2c0f851c58208280830da40ec95c26b8'
?>
