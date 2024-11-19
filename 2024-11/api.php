<?php

session_name("auth");
session_start();

$now = time();
if (isset($_SESSION['lifetime']) && $now > $_SESSION['lifetime']) {
    session_unset();
    session_destroy();
    session_name("auth");
    session_start();
}

require_once('config.php');
require_once('router.php');
require_once('clear.php');

function options_headers() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,PATCH,DELETE");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
}

header("Content-Type: application/json; charset=UTF-8");
options_headers();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$rmethod = $_SERVER["REQUEST_METHOD"];

error_reporting(E_ALL);
ini_set('display_errors',1);
$cn = new mysqli();
if (!$cn->connect(DB_HOST,DB_USER,DB_PASSWD,DB_NAME))
    die('Could not connect to database');

if ($rmethod == 'OPTIONS') {
    options_headers();
    return;
}

const AUTH_TIME = 3600*24*7;
const PAGE_OFFERS = 30;
const MAX_IMG_SIZE = 4*1024*1024;

function isuint($str) {
    if (!preg_match('/[0-9]{1,15}/',$str))
        return False;
    return True;
}

function isufloat($str) {
    if (!preg_match('/[0-9]{1,15}(\.[0-9]{1,15})?/',$str))
        return False;
    return True;
}

function auth_create($userid) {
    //$auth = implode("-",str_split(substr(strtolower(md5(microtime().rand(1000, 9999))), 0, 30), 6));
    //$_SESSION['auth'][$auth] = [$userid,time()+AUTH_TIME];
    //return $auth;
    $_SESSION['userid'] = $userid;
    $_SESSION['lifetime'] = time() + AUTH_TIME;
}

function auth_check() {
    if (!isset($_SESSION['userid']))
        return 0;

    return $_SESSION['userid'];
}

function api_auth_check($ownerquery=null, $isowner=False) {
    $userid = auth_check();
    if ($userid === 0)
        api_error('You need to be logged in',401);

    if (empty($ownerquery))
        return $userid;

    $r = fetchnone('select perm from members where id=' . $userid . ';');
    if ($r[0] === 0)
        api_error('You need to be logged in',401);
    $userperm = $r[1]->fetch_assoc()['perm'];

    $r = fetchnone($ownerquery)[1]->fetch_assoc();
    $ownerperm = $r['perm'];
    $owner = $r['id'];

    if ($userid !== $owner && $isowner && $userperm <= $ownerperm)
        api_error('no access',403);

    return $userid;
}

function api_date($sec) {
    return date("Y-m-d H:i:s", $sec);
}

function translate_code($code) {
    switch ($code) {
        case 422: return "HTTP/1.1 422 Unprocessable Entity"; break;
        case 404: return "HTTP/1.1 404 Not Found"; break;
        case 403: return "HTTP/1.1 403 Forbidden"; break;
        case 401: return "HTTP/1.1 401 Unauthorized"; break;
        case 200: return "HTTP/1.1 200 OK"; break;
        case 201: return "HTTP/1.1 201 Created"; break;
        case 202: return "HTTP/1.1 201 Accepted"; break;
    }

    return null;
}

class apiException extends Exception {
    public $code;
    public $message;

    function __construct($message,$code) {
        $this->message = $message;
        $this->code = $code;
    }
}

function api_error($message="Invalid input", $code=422) {
    throw new apiException($message,$code);
}

function api_check_error($a) {
    if (count($a) >= 3)
        $m = $a[2];
    if (isset($m)) {
        api_error($m,422);
    } else
        api_error();
}

function api_check($arr, ...$args) {
    $argsl = count($args);
    for ($i = 0; $i < $argsl; $i++) {
        $a = $args[$i];
        $al = count($args[$i]);

        if (!isset($arr[$a[0]]))
           api_check_error($a);
        $val = $arr[$a[0]];

        if ($al < 2 || $a[1] === null)
            continue;
        $pattern = $a[1];

        if (gettype($pattern) !== "string") {
            if (($pattern)($val))
                continue;
            api_check_error($a);
        }

        if ($pattern === 'uint') {
            if (isuint($val))
                continue;
        } else if ($pattern === 'ufloat') {
            if (isufloat($val))
                continue;
        } else if ($pattern[0] == '/' && $pattern[-1] == '/') {
            if (preg_match($pattern,$val))
                continue;
        } else if (($pattern)($val))
                continue;

        api_check_error($a);
    }
}

function fetchall($query) {
    global $cn;
    $ret = [];

    $q = $cn->query($query);
    while ($r = $q->fetch_assoc())
        array_push($ret,$r);

    return $ret;
}

function fetchnone($query) {
    global $cn;
    $q = $cn->query($query);
    if ($q === False)
        return [0,null];

    return [$q->num_rows,$q];
}

function insertinto($table,...$args) {
    global $cn;
    $q = "insert into " . $table . " values (null";
    foreach ($args as $a) {
        if ($a === null) {
            $q = $q . ',null';
        } else {
            $q = $q . ',"' . $a . '"';
        }
    }
    $q = $q . ")";
    $cn->query($q);

    return $cn->query("select last_insert_id()")->fetch_array()[0];
}

function updatetable($table,$id,$values,...$args) {
    global $cn;
    $size = count($args);
    if ($size == 0)
        return null;

    $q = "update " . $table . ' set ';
    for ($i = 0; $i < $size; $i++) {
        if (!isset($values[$args[$i]]))
            continue;

        $q = $q . " `" . $args[$i] . '`="' . $values[$args[$i]] . '",';
    }
    if ($q[-1] == ',') {
        $q = substr($q,0,strlen($q)-1);
    } else
        return null;

    $q = $q . " where id=" . $id . ';';
    return $cn->query($q);
}

function get_logo($sellerid) {
    return fetchall('select * from logos where sellerid='. $sellerid . ';');
}

function get_badges($sellerid) {
    return fetchall('select n.id,n.code,n.name from badges b join badge_names n on b.nameid = n.id where b.sellerid = ' . $sellerid . ';');
}

function get_location($locationid) {
    $r = fetchall('select * from locations where id='. $locationid . ';');
    if (count($r) > 0)
        return $r[0];
    return null;
}

function get_services($sellerid) {
    return fetchall('select n.id,n.name,n.src from services s join service_names n on s.serviceid = n.id where s.sellerid=' . $sellerid . ';');
}

function get_workinghours($sellerid) {
    return fetchall('select * from workinghours where sellerid = ' . $sellerid . ';');
}

function get_seller($sellerid) {
    global $cn;
    $q = $cn->query("select * from sellers where id=".$sellerid.";");
    if (!($ret = $q->fetch_assoc()))
        return null;

    $userperm = null;
    if ($ret['userid'] !== null) {
        $q = $cn->query("select perm from members where id=" . $ret['userid'] . ";");
        if ($q->num_rows !== 0)
            $userperm = $q->fetch_assoc()['perm'];
    }

    $ret['userperm'] = $userperm;
    $ret['logo'] = get_logo($sellerid);
    $ret['badges'] = get_badges($sellerid);
    $ret['location'] = get_location($ret['locationid']);
    $ret['services'] = get_services($sellerid);
    $ret['workinghours'] = get_workinghours($sellerid);

    return $ret;
}

function get_details($offerid) {
    return fetchall('select n.id,n.key,n.name,d.value from details d join detail_names n on d.detailid = n.id where d.offerid=' . $offerid . ';');
}

function get_parameters($offerid) {
    return fetchall('select n.id,n.name,p.value from parameters p join parameter_names n on p.nameid = n.id where p.offerid=' . $offerid . ';');
}

function get_equipments($offerid) {
    return fetchall('select n.id,n.key,n.name,c.key as "category_key",c.name as "category_name" from equipments e join equipment_names n on n.id = e.nameid join equipment_categories c on c.id = n.categoryid where e.offerid=' . $offerid . ';');
}

function get_photos($offerid) {
    return fetchall('select src from offer_photos where offerid=' . $offerid . ';');
}

function get_offer($offerid) {
    global $cn;
    $q = $cn->query('select o.*,c.name as "currecy_name",c.value as "currency_value",ca.name as "category_name",ca.code as "category_code" from offers o join currencies c on c.id = o.currencyid join categories ca on ca.id = o.categoryid where o.id=' . $offerid . ';');
    if (!($ret = $q->fetch_assoc()))
        return null;

    $ret['seller'] = get_seller($ret['sellerid']);
    $ret['details'] = get_details($offerid);
    $ret['parameters'] = get_parameters($offerid);
    $ret['equipments'] = get_equipments($offerid);
    $ret['photos'] = get_photos($offerid);

    return $ret;
}

function api_offer($offerid) {
    $r = get_offer($offerid);
    if ($r == null)
        api_error("Not found",404);

    return [200,$r];
}

function api_seller() {

    $in = (array)json_decode(file_get_contents('php://input'), TRUE);
    api_check($in,
        ['sellerid','uint'],
        ['page','uint'],
    );

    $sellerid = intval($in['sellerid']);

    $seller = get_seller($sellerid);
    if ($seller == null)
        api_error("Not found",404);

    $page = intval($in['page']);
    if ($page == 0)
        $page = 1;

    $query = null;
    if (isset($in['query']))
        $query = $in['query'];
    $category_code = null;
    if (isset($in['category_code']))
        $category_code = $in['category_code'];
    $make = null;
    if (isset($in['make']))
        $make = $in['make'];

    $where = '';
    if ($sellerid !== null)
        $where = " o.sellerid=" . $sellerid . " ";
    $joins = '';
    $r = get_category($joins,$where,$category_code,$page,$query,$make);
    $r['seller'] = $seller;

    return [200,$r];
}

function valid_email($val) {
    return preg_match("/^[A-Za-z0-9.]+@[A-Za-z]([A-Za-z0-9]+\.)+[A-Za-z]+$/",$val);
}

function api_register() {
    $in = (array)json_decode(file_get_contents('php://input'), TRUE);

    api_check($in,
        ['email',function($x) { return valid_email($x); }],
        ['name'],
        ['password']
    );

    $email = clear_email($in['email']);
    $passwd = clear_string($in['password']);
    $name = clear_text($in['name']);

    if (fetchnone("select id from members where name='" . $name . "';")[0] !== 0)
        api_error('User already exits',422);

    if (fetchnone("select id from members where email='" . $email . "';")[0] !== 0)
        api_error('There already is user with this email',422);

    $salt = openssl_random_pseudo_bytes(16);
    $hash = hash_pbkdf2('sha256',$in['password'],$salt,1024,32);
    $psalt = bin2hex($salt);
    $created = api_date(time());

    insertinto("members",null,$created,null,$email,$hash,$psalt,1,$name);

    return [201,[]];
}

function api_login() {
    $in = (array)json_decode(file_get_contents('php://input'), TRUE);

    api_check($in,
        ['email',function($x) { return valid_email($x); }],
        ['password']
    );

    $email = clear_email($in['email']);

    $r = fetchnone('select id,phash,psalt from members where email = "' . $email . '";');
    if ($r[0] === 0)
        api_error('Invalid email',422);

    $row = mysqli_fetch_assoc($r[1]);
    $passwd = clear_string($in['password']);
    $hash = hash_pbkdf2('sha256',$passwd,hex2bin($row['psalt']),1024,32);
    if ($hash !== $row['phash'])
        api_error("Incorrect password",422);

    $lastseen = api_date(time());

    auth_create($row['id']);

    fetchnone("update members set lastseen = \"" . api_date(time()) . "\" where id=" . $row['id'] . ";");

    return [200,[]];
}

function get_search($joins, $where, $page, $query, $make) {
    $ret = [];

    $values = 'count(*) as "count"';
    if (!empty($query)) {
        $query = addslashes($query);
        $d = ' ';
        if (strlen($where) > 0)
            $d = ' and ';
        $where = $where . $d .  "(o.title like \"%" . $query . "%\" or o.description like \"%" . $query . "%\") ";
    }

    if (!empty($make)) {
        $detailid = fetchnone('select id from detail_names where `key`="make";')[1]->fetch_assoc()['id'];
        $joins = $joins . ' join details d on d.offerid=o.id and d.detailid=' . $detailid . ' ';
        $d = ' ';
        if (strlen($where) > 0)
            $d = ' and ';
        $where = $where . $d . 'd.value="' . $make . '"';
    }

    $nw = "";
    if (strlen($where))
        $nw = " where " . $where;

    $count = fetchall('select ' . $values .  ' from offers o ' . $joins . $nw . ";")[0]['count'];
    $ret['results'] = $count;
    $ret['pages'] = floor($count/PAGE_OFFERS)+1;
    $ret['pagesize'] = PAGE_OFFERS;
    $ret['page'] = $page;
    $count = intval($count);

    if ($count !== 0 && floor(($count/PAGE_OFFERS))+1 >= $page) {
        $t_joins = ' join currencies c on c.id = o.currencyid join sellers s on s.id = o.sellerid ' . $joins;

        $values = 'o.*,c.name as "currency_name",c.value as "currency_value",s.name,s.locationid as "locationid",(select src from offer_photos of where of.offerid = o.id limit 1) as "photo"';

        $limits = " limit " . (PAGE_OFFERS * ($page-1)) . ' , ' . PAGE_OFFERS;

        $nw = "";
        if (strlen($where))
            $nw = " where " . $where;

        $ret['list'] = fetchall('select ' . $values .  ' from offers o ' . $t_joins . $nw . $limits . ";");
    } else
        $ret['list'] = [];
    #$ret['makes'] = null;

    return $ret;
}

function get_category($joins, $where, $category_code, $page, $query, $make) {
    if ($category_code !== null) {
        $r = fetchnone('select id from categories where code="' . clear_string($category_code) . '";');
        if ($r[0] === 0)
            api_error("No such category",404);

        $categoryid = $r[1]->fetch_assoc()['id'];

        $d = ' ';
        if (strlen($where) > 0)
            $d = ' and ';

        $where = " o.categoryid=" . $categoryid . $d . $where;
    }

    return get_search($joins,$where,$page,$query,$make);
}

function api_search_category() {
    $in = (array)json_decode(file_get_contents('php://input'), TRUE);
    api_check($in,
        ['page','uint'],
    );

    $page = intval($in['page']);
    if ($page == 0)
        $page = 1;

    $query = null;
    if (isset($in['query']))
        $query = $in['query'];
    $category_code = null;
    if (isset($in['category_code']))
        $category_code = $in['category_code'];
    $make = null;
    if (isset($in['make']))
        $make = $in['make'];

    $r = get_category('','',$category_code,$page,$query,$make);
    return [200,$r];
}

function api_comment() {
    $in = (array)json_decode(file_get_contents('php://input'), TRUE);
    api_check($in,
        ['sellerid','uint'],
        ['value']
    );

    $userid = api_auth_check();

    $sellerid = intval($in['sellerid']);
    $created = api_date(time());
    $value = clear_html($in['value']);

    insertinto("comments",$sellerid,$userid,$created,$value);

    return [201,[]];
}

function api_comments($sellerid) {
    $r = fetchall('select c.created,c.value,c.id,m.id as "userid",m.name,m.avatar,m.perm from comments c join members m on m.id = c.userid where c.sellerid = ' .
        $sellerid . ' order by c.created asc;');
    return [200,$r];
}

function api_message() {
    $in = (array)json_decode(file_get_contents('php://input'), TRUE);
    api_check($in,
        ['recipientid','uint'],
        ['value']
    );

    api_auth_check();

    $recipientid = intval($in['recipientid']);
    $created = api_date(time());
    $value = clear_html($in['value']);

    insertinto("messages",$userid,$recipientid,$created,$value);

    return [201,[]];
}

function api_conversations() {
    $in = (array)json_decode(file_get_contents('php://input'), TRUE);

    api_auth_check();

    $t = fetchall('select count(*) as "count",recipientid,senderid,value,created from messages where senderid = ' . $userid .' or recipientid = ' . $userid . ' group by recipientid, senderid order by created asc;');
    $ret = [];
    foreach($t as $i) {
        $id = $t['recipientid'];
        if ($d == $userid)
            $id = $t['senderid'];
        if (isset($ret[(string)$id]))
            continue;

        $r = fetchnone('select name,avatar,lastseen,perm from members where id = ' . $$id . ';')[1]->fetch_assoc();
        $t['name'] = $r['name'];
        $t['avatar'] = $r['avatar'];
        $t['lastseen'] = $r['lastseen'];
        $t['perm'] = $r['perm'];

        $ret[(string)$id] = $t;
    }

    return [200,$ret];
}

function api_messages() {
    $in = (array)json_decode(file_get_contents('php://input'), TRUE);
    api_check($in,
        ['memberid','uint'],
    );

    api_auth_check();

    $memberid = intval($in['memberid']);

    $r = fetchall(
        'select senderid,recipientid,created,value from messages where (senderid=' . $userid .
        ' and recipientid=' . $memberid . ') or (senderid=' . $memberid .
        ' or recipientid=' . $userid . ') order by created asc;'
    );
    return [200,$r];
}

function update_logo($sellerid,$arr,$change) {
    api_check($arr,
        ['src'],
        ['alt'],
        ['type'],
    );

    if (empty($arr['src']))
        return;

    if ($change) {
        delete_image('logos','.logos','src',
            fetchnone('select src from logos where sellerid=' . $sellerid . ';')[1]->fetch_assoc()['src']);
        fetchnone("delete from logos where sellerid=" . $sellerid . ";");
    }

    $img = make_image($arr['src'],'.logos');
    $alt = clear_html($arr['alt']);
    $type = clear_html($arr['type']);

    insertinto('logos',$img,$alt,$type,$sellerid);
}

function update_workinghours($sellerid,$arr,$change) {
    if ($change)
        fetchnone("delete from workinghours where sellerid=" . $sellerid . ";");

    $q = "";

    foreach ($arr as $i) {
        api_check($i,
            ['day','uint'],
            ['openhour','uint'],
            ['openminute','uint'],
            ['closehour','uint'],
            ['closeminute','uint']
        );

        $q = $q . "(null," . $sellerid .
            "," . $i['day'] .
            "," . $i['openhour'] .
            "," . $i['openminute'] .
            "," . $i['closehour'] .
            "," . $i['closeminute'] . "),";
    }
    if ($q[-1] == ',') {
        $q = substr($q,0,strlen($q)-1);
    } else
        return null;

    fetchnone('insert into workinghours values ' . $q . ";");
}

function update_services($sellerid,$arr,$change) {
    if ($change)
        fetchnone("delete from services where sellerid=" . $sellerid . ";");

    $q = "";

    foreach ($arr as $i) {
        if (!isuint($i))
            api_error('No such name',404);

        $id = intval($i);
        if (fetchnone('select id from service_names where id=' . $id . ';')[0] === 0)
            api_error('No such name',404);

        $q = $q . "(null," . $id . "," . $sellerid . "),";
    }
    if ($q[-1] == ',') {
        $q = substr($q,0,strlen($q)-1);
    } else
        return null;

    fetchnone('insert into services values ' . $q . ";");
}

function update_location($sellerid,$arr,$change) {
    if ($change) {
        $locationid = fetchnone('select locationid from sellers where id=' . $sellerid . ';')[1]->fetch_assoc()['locationid'];
        fetchnone("delete from locations where id=" . $locationid . ";");
    }

    api_check($arr,
        ['address'],
        ['city'],
        ['region'],
        ['country'],
        ['postalcode'],
        ['shortaddress'],
        ['c_city'],
        ['c_region'],
        ['c_subregion'],
        ['latitude','ufloat'],
        ['longitude','ufloat'],
        ['zoom','uint'],
        ['radius','uint']
    );

    $locationid = insertinto('locations',
        $arr['address'],
        $arr['city'],
        $arr['region'],
        $arr['country'],
        $arr['postalcode'],
        $arr['shortaddress'],
        $arr['c_city'],
        $arr['c_region'],
        $arr['c_subregion'],
        $arr['latitude'],
        $arr['longitude'],
        $arr['zoom'],
        $arr['radius']
    );
    fetchnone('update sellers set locationid=' . $locationid . ' where id=' . $sellerid . ';');
}

function api_nseller() {
    $in = (array)json_decode(file_get_contents('php://input'), TRUE);
    api_check($in,
        ['phonenumber'],
        ['name'],
        ['website'],
        ['isprivate',"/[10]/"]
    );

    $userid = api_auth_check();

    $created = api_date(time());

    $sellerid = insertinto('sellers',1,$in['website'],$in['name'],$in['phonenumber'],$in['isprivate'],$userid,$created);

    if (isset($in['logo']))
        update_logo($sellerid,$in['logo'],False);
    if (isset($in['workinghours']))
        update_workinghours($sellerid,$in['workinghours'],False);
    if (isset($in['services']))
        update_services($sellerid,$in['services'],False);
    if (isset($in['location']))
        update_location($sellerid,$in['location'],False);

    return [201,[]];
}

function api_useller() {
    $in = (array)json_decode(file_get_contents('php://input'), TRUE);
    api_check($in,
        ['id','uint']
    );

    $sellerid = intval($in['id']);
    api_auth_check('select m.perm,m.id from sellers s join members m on m.id = s.userid where s.id=' . $sellerid . ';');

    updatetable('sellers',$sellerid,$in,'website','name','phonenumber','isprivate');

    if (isset($in['logo']))
        update_logo($sellerid,$in['logo'],True);
    if (isset($in['workinghours']))
        update_workinghours($sellerid,$in['workinghours'],True);
    if (isset($in['services']))
        update_services($sellerid,$in['services'],True);
    if (isset($in['location']))
        update_location($sellerid,$in['location'],True);

    return [201,[]];
}

function api_dseller($sellerid) {
    api_auth_check('select m.perm,m.id from sellers s join members m on m.id = s.userid where s.id=' . $sellerid . ';');

    delete_seller($sellerid);

    return [200,[]];
}

function delete_offer($offerid) {
    update_details($offerid,null,True);
    update_parameters($offerid,null,True);
    update_equipments($offerid,null,True);
    update_photos($offerid,null,True);
    fetchnone('delete from messages where offerid=' . $offerid . ';');

    fetchnone('delete from offers where id=' . $offerid . ';');
}

function api_doffer($offerid) {
    api_auth_check('select m.perm,m.id from offers o join sellers s on s.id = o.sellerid join members m on m.id = s.userid where o.id=' . $offerid . ' limit 1;');

    delete_offer($offerid);

    return [200,[]];
}

function delete_seller($sellerid) {
    fetchnone('delete from comments where sellerid=' . $sellerid . ';');
    $r = fetchall('select id from offers where sellerid=' . $sellerid . ';');
    foreach($r as $i)
        delete_offer($i['id']);
    fetchnone('delete from offers where sellerid=' . $sellerid . ';');

    #delete logo

    fetchnone('delete from sellers where id=' . $sellerid . ';');
}

function delete_member($memberid) {
    fetchnone('delete from messages where senderid='. $memberid . ' or recipientid=' . $memberid . ';');
    fetchnone('delete from comments where userid='. $memberid . ';');

    delete_image('members','.avatars','avatar',
        fetchnone('select avatar from members where id=' . $memberid . ';')[1]->fetch_assoc()['avatar']);

    $r = fetchall('select id from sellers where userid=' . $memberid . ';');
    foreach ($r as $i)
        delete_seller($i['id']);
    fetchnone('delete from sellers where userid=' . $memberid . ';');

    fetchnone('delete from members where id=' . $memberid . ';');
}

function api_dmember($memberid) {
    api_auth_check('select perm,id from members where id=' . $memberid . ' limit 1;');

    delete_member($memberid);

    return [200,[]];
}

function api_dcomment($commentid) {
    api_auth_check('select m.perm,m.id from comments c join members m on m.id = c.userid where c.id=' . $commentid . ' limit 1;');

    fetchnone('delete from comments where id=' . $commentid . ';');

    return [200,[]];
}

function update_parameters($offerid,$arr,$change) {
    if ($change)
        fetchnone("delete from parameters where offerid=" . $offerid . ";");

    $q = "";

    foreach ($arr as $i) {
        api_check($i,
            ['id','uint'],
            ['value']
        );
        $id = intval($i['id']);
        if (fetchnone('select id from parameter_names where id=' . $id . ';')[0] === 0)
            api_error('No such name',404);

        $q = $q . "(null," . $id . "," . $offerid . ',"' . clear_html($i['value']) . '"),';
    }
    if ($q[-1] == ',') {
        $q = substr($q,0,strlen($q)-1);
    } else
        return null;

    fetchnone('insert into parameters values ' . $q . ";");
}

function update_details($offerid,$arr,$change) {
    if ($change)
        fetchnone("delete from details where offerid=" . $offerid . ";");

    $q = "";

    foreach ($arr as $i) {
        api_check($i,
            ['id','uint'],
            ['value']
        );
        $id = intval($i['id']);
        if (fetchnone('select id from detail_names where id=' . $id . ';')[0] === 0)
            api_error('No such name',404);

        $q = $q . "(null," . $id . "," . $offerid . ',"' . clear_html($i['value']) . '"),';
    }
    if ($q[-1] == ',') {
        $q = substr($q,0,strlen($q)-1);
    } else
        return null;

    fetchnone('insert into details values ' . $q . ";");
}

function update_equipments($offerid,$arr,$change) {
    if ($change)
        fetchnone("delete from equipments where offerid=" . $offerid . ";");

    $q = "";

    foreach ($arr as $i) {
        $id = $i;
        if (!isuint($id))
            api_error('No such name',404);
        $id = intval($id);
        if (fetchnone('select id from equipment_names where id=' . $id . ';')[0] === 0)
            api_error('No such name',404);

        $q = $q . "(null," . $id . "," . $offerid . "),";
    }
    if ($q[-1] == ',') {
        $q = substr($q,0,strlen($q)-1);
    } else
        return null;

    fetchnone('insert into equipments values ' . $q . ";");
}

function delete_image($table,$dir,$field,$img) {
    if ($img === null || strlen($img) != 64 || !ctype_xdigit($img))
        return;
    $r = fetchnone('select id from ' . $table . ' where ' . $field . '="' . $img . '";');
    if ($r[0] == 1)
        unlink($dir . '/' . $img);
}

function delete_images($table,$idname,$id,$dir,$field) {
    $r = fetchall('select ' . $field . ' from ' . $table . ' where ' . $idname . '= ' . $id . ' and left(' . $field . ',5) != "https" and length(' . $field . ') = 64;');
    foreach($r as $i)
        delete_image($table,$dir,$field,$i[$field]);
}

function make_image($url,$dir) {
    if (substr($url,0,8) === "https://" || strlen($url) < 256)
        return $url;

    //js is retarded and outputs data:image/jpeg;base64, before base64
    $f = substr($url,strpos($url,',')+1);

    $f = base64_decode($f);
    if (strlen($f) > MAX_IMG_SIZE)
        api_error("Image is too large",422);


    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $type = $finfo->buffer($f);


    if (!isset($type) || !in_array($type, array("image/png", "image/jpeg", "image/gif")))
        api_error("Unidentified file format",422);

    $name = hash("sha256",$f);

    $path = $dir . '/' . $name;
    if (!file_exists($path)) {
        $h = fopen($path,"wb");
        fwrite($h,$f);
        fclose($h);
    }

    return $name;
}

function update_image($table,$id,$dir,$field,$image,$change) {
    if ($change)
        delete_images($table,'id',$id,$dir,$field);
    $img = make_image($image,$dir);
    fetchnone('update ' . $table . ' set ' . $field . '="' . $img . '" where id=' . $id . ';');
}

function update_photos($offerid,$arr,$change) {
    if ($change) {
        delete_images('offer_photos','offerid',$offerid,'.offer_photos','src');
        fetchnone('delete from offer_photos where offerid=' . $offerid . ';');
    }

    $q = '';
    foreach ($arr as $i) {
        $img = make_image($i,'.offer_photos');

        $q = $q . '(null,"' . $img . '",' . $offerid . "),";
    }
    if ($q[-1] == ',') {
        $q = substr($q,0,strlen($q)-1);
    } else
        return null;

    fetchnone('insert into offer_photos values ' . $q . ";");
}

function api_noffer() {
    $in = (array)json_decode(file_get_contents('php://input'), TRUE);
    api_check($in,
        ['title'],
        ['price','ufloat'],
        ['currencyid','uint'],
        ['sellerid','uint'],
        ['description'],
        ['categoryid','uint']
    );

    $sellerid = intval($in['sellerid']);

    api_auth_check('select m.id,m.perm from sellers s join members m on m.id = s.userid where s.id=' . $sellerid . ';',True);

    $isactive = 1;
    $created = api_date(time());
    $title = clear_html($in['title']);
    $description = clear_html($in['description']);

    $offerid = insertinto('offers',$title,$in['price'],$in['currencyid'],$in['sellerid'],$created,$description,$in['categoryid'],$isactive);

    if (isset($in['details']))
        update_details($offerid,$in['details'],False);
    if (isset($in['parameters']))
        update_parameters($offerid,$in['parameters'],False);
    if (isset($in['equipments']))
        update_equipments($offerid,$in['equipments'],False);
    if (isset($in['photos']))
        update_photos($offerid,$in['photos'],False);

    return [200,[]];
}

function api_uoffer() {
    $in = (array)json_decode(file_get_contents('php://input'), TRUE);
    api_check($in,
        ['id','uint']
    );

    $offerid = intval($in['id']);
    api_auth_check('select m.perm,m.id from offers o join sellers s on s.id = o.sellerid join members m on m.id = s.userid where o.id=' . $offerid . ' limit 1;');

    updatetable('offers',$offerid,$in,'title','price','currencyid','description','isactive','categoryid');

    if (isset($in['details']))
        update_details($offerid,$in['details'],True);
    if (isset($in['parameters']))
        update_parameters($offerid,$in['parameters'],True);
    if (isset($in['equipments']))
        update_equipments($offerid,$in['equipments'],True);
    if (isset($in['photos']))
        update_photos($offerid,$in['photos'],True);

    return [200,[]];
}

function api_categories() {
    $r = fetchall('select * from categories;');
    return [200,$r];
}

function api_makes() {
    $r = fetchall('select distinct value from details where detailid=(select id from detail_names where `key`="make");');
    return [200,$r];
}

function api_details() {
    $r = fetchall('select id,name from detail_names;');
    return [200,$r];
}

function api_parameters() {
    $r = fetchall('select id,name from parameter_names;');
    return [200,$r];
}

function api_equipments() {
    $r = fetchall('select id,name from equipment_names;');
    return [200,$r];
}

function api_currencies() {
    $r = fetchall('select * from currencies;');
    return [200,$r];
}

function api_services() {
    $r = fetchall('select * from service_names;');
    return [200,$r];
}

function api_sellers() {
    $in = (array)json_decode(file_get_contents('php://input'), TRUE);

    $userid = api_auth_check();

    $r = fetchall('select * from sellers where userid = ' . $userid . ';');
    return [200,$r];
}

function api_userinfo($userid=0) {
    if ($userid === 0)
        $userid = api_auth_check();

    $r = fetchnone("select id,avatar,created,lastseen,email,perm,name from members where id=" . $userid . ";")[1]->fetch_assoc();

    return [200,$r];
}

function api_usersellers($userid) {
    if ($userid === 0)
        $userid = api_auth_check();

    $r = fetchall("select id,created,name from sellers where userid=" . $userid . ";");

    return [200,$r];
}

function api_umember() {
    $in = (array)json_decode(file_get_contents('php://input'), TRUE);
    api_check($in,
        ['memberid','uint']
    );

    $memberid = intval($in['memberid']);
    $userid = api_auth_check('select id,perm from members where id=' . $memberid . ' limit 1;');

    if (isset($in['perm'])) {
        if ($userid === $memberid) # || $userperm <= intval($in['perm']))
            api_error('no access',403);
    }

    if (!empty($in['avatar']))
        update_image('members',$memberid,'.avatars','avatar',$in['avatar'],True);
    updatetable('members',$memberid,$in,'email','perm','name');

    return [200,[]];
}

routes_get("/offer/@",'api_offer',"int");
routes_get("/categories",'api_categories');
routes_get("/makes",'api_makes');
routes_get("/currencies",'api_currencies');
routes_get("/details",'api_details');
routes_get("/parameters",'api_parameters');
routes_get("/equipments",'api_equipments');
routes_get("/services",'api_services');
routes_get("/comments/@",'api_comments',"int");
routes_get("/userinfo/@",'api_userinfo',"int");
routes_get("/userinfo",'api_userinfo');
routes_get("/usersellers/@",'api_usersellers',"int");

routes_post("/search_category",'api_search_category');
routes_post("/seller",'api_seller');

routes_post("/login",'api_login');
routes_post("/register",'api_register');
routes_post("/comment",'api_comment');
routes_post("/messages",'api_messages');
routes_post("/message",'api_message');
routes_post("/nseller",'api_nseller');
routes_post("/noffer",'api_noffer');
routes_post("/conversations",'api_conversations');
routes_post("/sellers",'api_sellers');

routes_delete("/seller/@",'api_dseller',"int");
routes_delete("/offer/@",'api_doffer',"int");
routes_delete("/member/@",'api_dmember',"int");
routes_delete("/comment/@",'api_dcomment',"int");

routes_patch("/member",'api_umember');
routes_patch("/offer",'api_uoffer');
routes_patch("/seller",'api_useller');

#routes_run("GET","/offer/3");
#routes_run("GET","/c/osobowe/2/list");

$uri = substr($uri,4);

try {
    $r = routes_run($rmethod,$uri);
    if ($r === null)
        api_error("No such route",404);
    header(translate_code($r[0]));
    echo json_encode($r[1]);
} catch (apiException $e) {
    $r = ["error" => $e->message];
    header(translate_code($e->code));
    echo json_encode($r);
}

$cn->close();

?>
