<?php
/**
 * Created by PhpStorm.
 * User: Eric R DeCoff
 * Date: 10/30/13
 * Time: 10:33 AM
 */
global $hostname;
global $database;
global $db_table;

global $check;

global $username;
global $password;

global $link;

global $table_views;

ini_set('include_path', ini_get('include_path') . ';' . $_SERVER['DOCUMENT_ROOT'] . "\\Slim\\");
ini_set('include_path', ini_get('include_path') . ';' . $_SERVER['DOCUMENT_ROOT'] . "\\twilio\\");

include('Services/Twilio.php');

require_once 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

require_once 'govathon112013_godaddy.php';

function init_db($database){
    global $hostname,$username,$password,$link;

    $link = mysql_connect($hostname,$username,$password)
    OR Die('Could not connect to '.$hostname);

    mysql_select_db($database)
    OR Die('Could not connect to database: '.$database);
}

function init_views(){
    global $table_views;
    $table_views = array(
        "table"=>"classes",
        "view"=>"show",
        "columns"=>"id,"
    );
}


function statusHandler(){
    echo '<div align="center"><h1>Status: Working</h1></div>';
}

function extractNumbers($str){
    preg_match_all('!\d+!', $str, $matches);
    return implode("",$matches[0]);
}

function actionHandler($action,$key1=null,$value1=null,$key2=null,$value2=null,$key3=null,$value3=null,$key4=null,$value4=null,$key5=null,$value5=null){
    switch (strtoupper($action)){
        case "ADD":
            if (strtoupper($key2)=="ID"){
                $value2=extractNumbers($value2);
                if ( checkWhereExists($value1,"ID='".$value2."'") == 1){
                    errorKeyValue(__FUNCTION__,"TWILIO [ ID ] Already Exists",$key1,$value1,$key2,$value2);
                }else{
                    if ( checkLength($value1,10,$value2) == 1 ){
                        if (strtoupper($key3)=="GREETING"){
                            if (strtoupper($key4)=="ORGANIZER"){
                                if ( checkLength($value1,10,$value4) == 1 ){
                                    echo 'insert goes here';
                                }else{
                                    errorKeyValue(__FUNCTION__,"TWILIO [ ORGANIZER ] too [ SHORT ]",$key1,$value1,$key2,$value2,$key3,$value3,$key4,$value4);
                                }
                            }else{
                                errorKeyValue(__FUNCTION__,"TWILIO [ ORGANIZER ] expected",$key1,$value1,$key2,$value2,$key3,$value3,$key4,$value4);
                            }
                        }else{
                            errorKeyValue(__FUNCTION__,"TWILIO [ GREETING ] expected",$key1,$value1,$key2,$value2,$key3,$value3);
                        }
                    }else{
                        // ID too short to insert
                        errorKeyValue(__FUNCTION__,"TWILIO [ ID ] too [ SHORT ] to insert",$key1,$value1,$key2,$value2);

                    }
                }
            }else{
                errorKeyValue(__FUNCTION__,"[ ID ] expected",$key1,$value1,$key2,$value2);
            }

            print_r(extractNumbers($value1));
            break;
        case "CHECK":
            switch(strtoupper($key1)){
                case "TABLE":
                    switch(strtoupper($key2)){
                        case "COLUMN":
                            errorKeyValue(__FUNCTION__,null,$key2,$value2,'value', checkColumnExists($value1,$value2) ? 'exists' : 'does NOT exists');
                            break;
                        case "WHERE":
                            errorKeyValue(__FUNCTION__,null,$key2,$value2,'value', checkWhereExists($value1,$value2) ? 'exists' : 'does NOT exists');
                            break;
                        case "LENGTH":
                            checkLength($value1,$value2,$value3);
                            break;
                        default:
                            errorKeyValue(__FUNCTION__,"[ COLUMN || WHERE ] expected",$key1,$value1,$key2,$value2);
                            break;
                    }
                    break;
                default:
                    errorKeyValue(__FUNCTION__,"TABLE expected");
                    break;
            }
            break;
        default:
            $body= array();
            $body["action"]=$action;
            $body["key1"]=$key1;
            $body["value1"]=$value1;
            $body["key2"]=$key2;
            $body["value2"]=$value2;
            var_dump($body);
            break;
    };
}

function errorKeyValue($function,$error,$key1=null,$value1=null,$key2=null,$value2=null,$key3=null,$value3=null,$key4=null,$value4=null,$key5=null,$value5=null){
    $body = array();
    $body["function"]=$function;
    if (!is_null($error)){
        $body["error"]=$error;
    }
    if (!is_null($key1)){
        $body["key1"]=$key1;
        $body["value1"]=$value1;
    }
    if (!is_null($key2)){
        $body["key2"]=$key2;
        $body["value2"]=$value2;
    }
    if (!is_null($key3)){
        $body["key3"]=$key3;
        $body["value3"]=$value3;
    }
    if (!is_null($key4)){
        $body["key4"]=$key4;
        $body["value4"]=$value4;
    }
    if (!is_null($key5)){
        $body["key5"]=$key5;
        $body["value5"]=$value5;
    }
    slimJson(json_encode($body),0);
}

function checkTables($database='fbcvr'){
    global $link;

    init_db($database);
    $sql = "SHOW TABLE STATUS FROM ".$database;

    $result = mysql_query($sql,$link)
    OR Die('SQL Failed: '.$sql);

    while($array = mysql_fetch_array($result)) {
        $total = $array[Data_length]+$array[Index_length];
        echo '
        Table: '.$array[Name].'<br />
        Data Size: '.$array[Data_length].'<br />
        Index Size: '.$array[Index_length].'<br />
        Total Size: '.$total.'<br />
        Total Rows: '.$array[Rows].'<br />
        Average Size Per Row: '.$array[Avg_row_length].'<br /><br />
        ';
    }
}
include 'myTwilio_config.php';

function sms(){
    global $messages, $status, $level;

//    $number = $_POST['From'];
    $number = isset($_POST['From']) == true ? $_POST['From'] : $_GET['From'];
    $number = extractNumbers($number);
    capture_number($number);
    $status = strtoupper(getColumnValue('members',$number,'status'));
    $level = strtoupper(getColumnValue('members',$number,'status'));

//    $body = $_POST['Body'];
    $body = isset($_POST['Body']) == true ? $_POST['Body'] : $_GET['Body'];
    $body = preg_replace("/[^A-Za-z0-9]\s/","",$body);

    //echo "Body:".$body;

    $keyword = explode(" ",$body);
    //echo "KeyWord: ".$keyword[0];
    $switch = strtoupper($keyword[0]);
    switch ($switch){
        case "JOIN":
            if (count($keyword) < 3){
                $name = getColumnValue('members',$number,'name');
                $len = checkLength($name,3,'lt');
                if ($status <> 'JOINED' ){
                    sms_join();
                    break;
                }else if ($len == true){
                    sms_join();
                    break;
                }
                sms_joined();
                break;
            }else{
                $name = $keyword[1].' '.$keyword[2];
                updateColumn('members',$number,'name',$name);
                updateColumn('members',$number,'status','CONFIRM');
                sms_confirm($name);
                break;
            }
            break;
        case "CONFIRM":
            $name = getColumnValue('members',$number,'name');
            $len = checkLength($name,3,'lt');
            if ($len == false){
                updateColumn('members',$number,'status','DOW');
                sms_dow();
            }else{
                sms_join();
            }
            break;
        case "USE":
            $help = strtoupper($keyword[1]);
            switch($help){
                case "MISSED":
                case "NEXT":
                case "RECYCLE":
                case "YARD":
                    sms_help('USE-'.$help);
                    break;
                default:
                    sms_help('USE-USER');
                    break;
            }
            break;
        case "MON":
        case "TUE":
        case "WED":
        case "THU":
        case "FRI":
        case "SAT":
        case "SUN":
            if (strtoupper($status) == strtoupper("DOW")){
                updateColumn('members',$number,'DOW',$switch);
                updateColumn('members',$number,'status','JOINED');
                sms_joined();
                break;
            }else{
                sms_joined();
                break;
            }
            break;
        case "CONTACT":
            sms_message($messages['CONTACT']);
            break;
        case "MISSED":
            sms_message($messages['MISSED']);
            break;
        case "NEXT":
            $dow = strtoupper(getColumnValue('members',$number,'DOW'));
            $today = strtoupper(date('D'));
            $tomorrow  = strtoupper(Date('D',mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"))));
            $dm = Date('D Y-m-d',strtotime('next '.$dow));
            if ($dow == $today){
                $dm = 'Today: '.Date('D Y-m-d',strtotime('today'));
            }
            if ($dow == $tomorrow){
                $dm = 'Tomorrow: '.Date('D Y-m-d',strtotime('tomorrow'));
            }

            $next = $messages['NEXT'];
            $next = str_replace("PICKUP_DATE",$dm,$next);
            sms_message($next);
            break;
        case "RECYCLE":
            $dow = strtoupper(getColumnValue('members',$number,'DOW'));
            $dow = 'SAT';
            $today = strtoupper(date('D'));
            $tomorrow  = strtoupper(Date('D',mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"))));
            $dm = Date('D Y-m-d',strtotime('next '.$dow));
            if ($dow == $today){
                $dm = 'Today: '.Date('D Y-m-d',strtotime('today'));
            }
            if ($dow == $tomorrow){
                $dm = 'Tomorrow: '.Date('D Y-m-d',strtotime('tomorrow'));
            }

            $next = $messages['NEXT'];
            $next = str_replace("PICKUP_DATE",$dm,$next);
            sms_message($next);
            break;
        case "YARD":
            $dow = strtoupper(getColumnValue('members',$number,'DOW'));
            $dow = 'SUN';
            $today = strtoupper(date('D'));
            $tomorrow  = strtoupper(Date('D',mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"))));
            $dm = Date('D Y-m-d',strtotime('next '.$dow));
            if ($dow == $today){
                $dm = 'Today: '.Date('D Y-m-d',strtotime('today'));
            }
            if ($dow == $tomorrow){
                $dm = 'Tomorrow: '.Date('D Y-m-d',strtotime('tomorrow'));
            }

            $next = $messages['YARD'];
            $next = str_replace("PICKUP_DATE",$dm,$next);
            sms_message($next);
            break;
        default:
            switch (strtoupper($status)){
                case "NOT":
                    sms_join();
                    break;
                CASE "CONFIRM":
                    $name = getColumnValue('members',$number,'name');
                    sms_confirm($name);
                    break;
                CASE "DOW":
                    sms_dow();
                    break;
                CASE "JOINED":
                    sms_help();
                    break;
                CASE "BLOCKED":
                    break;
            }
    }
}

function sms_join(){
    global $messages;

    $lb="\n";
    $greet = $messages['GREETING'].$lb.$messages['JOIN'];
    $body = $greet;
    $response = new Services_Twilio_Twiml();
    $response->message($body);
    echo $response;
}

function sms_message($m){
    global $messages, $level;

    $response = new Services_Twilio_Twiml();
    $response->message($m);
    echo $response;

}

function sms_confirm($name){
    global $messages;

    $confirm = $messages['CONFIRM'];
    $confirm = str_replace("NAME_GOES_HERE",$name,$confirm);
    $response = new Services_Twilio_Twiml();
    $response->message($confirm);
    echo $response;
}

function sms_dow(){
    global $messages;

    $dow = $messages['DOW'];
    $response = new Services_Twilio_Twiml();
    $response->message($dow);
    echo $response;
}

function sms_help($help='USE-USER'){
    global $messages, $level;

    $lb = "\n\n";
    $help = $messages['GREETING'].$lb.$messages[$help];
    $response = new Services_Twilio_Twiml();
    $response->message($help);
    echo $response;
}

function sms_joined(){
    global $messages;

    $thank_you = $messages['THANK_YOU']."\n".$messages['USE-USER'];
    $response = new Services_Twilio_Twiml();
    $response->message($thank_you);
    echo $response;
}

function capture_number($number){
    global $member;

    $member = checkNumber($number,true,false,'members');

    $json = json_decode($member,true);

    if ($json["status"]=='failure'){
        addNumber('members',$number);
        return "success";
    }else{
        return $json["status"];
    }
}

function is_member($number){
    global $member;

    $member = checkNumber($number,true,false,'members');

    $json = json_decode($member,true);

    return $json["status"];
}

function addNumber($table,$number){
    global $app, $link,$database,$db_table;

    init_db($database);

    $db_table = $table;

    $sql = "INSERT INTO ".$database.'.'.$db_table." (id) values('".$number."')";

    $results = mysql_query($sql,$link)
    OR Die('SQL Failed: '.$sql);
//    checkNumber($number,true,true,'members');
}

function updateColumn($table,$number,$column,$value){
    global $app, $link,$database,$db_table;

    init_db($database);

    $db_table = $table;

    $sql = "UPDATE `".$db_table."` SET `".$column."` = '".$value."' WHERE ID = '".$number."'";

    $results = mysql_query($sql,$link)
    OR Die('SQL Failed: '.$sql);

}

/*
function deleteNumber($number){
    global $app, $link,$database,$db_table;

    init_db($database);

    $sql = "DELETE FROM ".$database.'.'.$db_table." WHERE id='".$number."'";

    $results = mysql_query($sql,$link)
    OR Die('SQL Failed: '.$sql);

    $json = json_decode( checkNumber($number,false,false) ,true);
    if ($json['max'] == '0'){
        $body = json_encode(array(
            "function"=>"deleteNumber",
            "status"=>"success",
            "max"=>$json['max'],
            "error"=>"Number Deleted"
        ));
        slimJson($body,$json['max']);
    }else{
        $body = json_encode(array(
            "function"=>"deleteNumber",
            "status"=>"failure",
            "max"=>$json['max'],
            "error"=>"Number Still Exists"
        ));
        slimJson($body,$json['max']);

    }

}
*/


function checkColumnExists($table,$column){
    global $app, $link,$database,$db_table;

    init_db($database);

    $db_table = $table;

    $sql = "
        SELECT *
        FROM information_schema.COLUMNS
        WHERE
        TABLE_SCHEMA = '".$database."'
        AND TABLE_NAME = '".$db_table."'
        AND COLUMN_NAME = '".$column."'";

    $results = mysql_query($sql,$link)
    OR Die('SQL Failed: '.$sql);

    $rows = mysql_num_rows($results);

    return $rows;
}

function checkWhereExists($table,$where){
    global $app, $link,$database,$db_table;

    init_db($database);

    $db_table = $table;

    $sql = "
        SELECT *
        FROM `".$database."`.`".$db_table."`
        WHERE ".$where;

    $results = mysql_query($sql,$link)
    OR Die('SQL Failed: '.$sql);

    $rows = mysql_num_rows($results);

    return $rows;
}

function checkLength($value, $length, $option='ge'){
    global $app, $link,$database,$db_table;

    switch (strtolower($option)){
        // Equal Too
        case 'eq':
        case '=':
            $option = '=';
            break;
        // Less Then or Equal too
        case 'le':
        case '<=':
            $option = '<=';
            break;
        // Less Then
        case 'lt':
        case '<':
            $option = '<';
            break;
        // Greater Then
        case 'gt':
        case '>':
            $option = '>';
            break;
        // Greater Then or Equal too
        default:
            $option = '>=';
            break;
    }


    init_db($database);

    $sql = "
      SELECT * FROM (SELECT '".$value."' AS ID) _values
      WHERE CHAR_LENGTH(_values.id) ".$option." ".$length;

    $results = mysql_query($sql,$link)
    OR Die('SQL Failed: '.$sql);

    $rows = mysql_num_rows($results);

    return $rows;
}

function getColumnValue($table,$number,$column){
    global $app, $link,$database,$db_table;

    init_db($database);

    $db_table = $table;

    $sql = "SELECT ".$column." FROM ".$database.'.'.$db_table." WHERE id = '".$number."'";

    $results = mysql_query($sql,$link)
    OR Die('SQL Failed: '.$sql);

    $row = mysql_fetch_row($results);

    return $row[0];
}

function checkNumber($number,$success,$encode,$table){
    global $app, $link,$database,$db_table;

    init_db($database);

    $db_table = $table;

    $sql = "SELECT * FROM ".$database.'.'.$db_table." WHERE id = '".$number."'";

    $results = mysql_query($sql,$link)
    OR Die('SQL Failed: '.$sql);

    $rows = mysql_num_rows($results);
    $status = $rows > 0 ? $success : !$success;
    $status = $status ? "success" : "failure";

    $body = json_encode(array(
        "function"=>__FUNCTION__,
        "status"=>$status,
        "max"=>$rows
    ));

    if ($encode == false) {
        return $body;
    }else{
        slimJson($body,count($results));
    }
}

function ml($message, $send = false){
    global $messages, $auth_sid, $auth_token;

    echo $message.'<BR><BR>';
    echo 'count = '.strlen($message).'<BR><BR>';

    echo 'sent to:<BR><BR>';

    $message = str_replace("<BR>","\n",$message);

    if ($send == true){
        $client = new Services_Twilio($auth_sid, $auth_token);

        $results = getList('members',false);

        foreach($results as $data){
            echo $data['NAME'].'<BR><BR>';
            /*
            if ($data['STATUS'] == 'JOINED'){
                $messageSend = $client->account->sms_messages->create(
                    '+'.'14046204555', // From a valid Twilio number
                    '+'.$data['ID'], // Text this number
                    $message
                );
            }
            */
        }
    }
}

function notification($message, $dow = 'ALL', $send = false){
    global $messages, $auth_sid, $auth_token;

    echo $message.'<BR><BR>';
    echo 'count = '.strlen($message).'<BR><BR>';

    echo 'sent to:<BR><BR>';

    $message = str_replace("<BR>","\n",$message);

    if ($send == true){
    $client = new Services_Twilio($auth_sid, $auth_token);

    $results = getList('members',false);

    foreach($results as $data){
        echo $data['NAME'].'<BR><BR>';
        /*
        if ($data['STATUS'] == 'JOINED'){
            $messageSend = $client->account->sms_messages->create(
                '+'.'14046205025', // From a valid Twilio number
                '+'.$data['ID'], // Text this number
                $message
            );
        }
        */
    }
    }
}

function getList($table='members',$encode=false){
    global $app, $link,$database,$db_table;

    init_db($database);

    $db_table = $table;

    $sql = "SELECT * FROM ".$database.'.'.$db_table." WHERE status = 'JOINED'";

    $results = mysql_query($sql,$link)
    OR Die('SQL Failed: '.$sql);

    $data = array();

    while($row=mysql_fetch_array($results)){
        $data[] = array(
            'ID'=>$row['id'],
            'NAME'=>$row['name'],
            'STATUS'=>$row['status'],
            'LEVEL'=>$row['level']
        );
    }

    if (empty($data)){
        $count = 0;
        $data[] = array(
            'ID'=>0,
            'NAME'=>'EMPTY',
            'STATUS'=>'BLOCKED',
            'LEVEL'=>'NOT'
        );
    }else{
        $count = count($results);
    }

    if ($encode == false) {
        $body = $data;
        return $body;
    }else{
        $body = json_encode($data);
        slimJson($body,$count);
    }
}

function getDOW($table='members',$dow,$encode=false){
    global $app, $link,$database,$db_table;

    init_db($database);

    $db_table = $table;

    $sql = "SELECT * FROM ".$database.'.'.$db_table." WHERE status = 'JOINED' and DOW='".$dow."'";

    $results = mysql_query($sql,$link)
    OR Die('SQL Failed: '.$sql);

    $data = array();

    while($row=mysql_fetch_array($results)){
        $data[] = array(
            'ID'=>$row['id'],
            'NAME'=>$row['name'],
            'STATUS'=>$row['status'],
            'LEVEL'=>$row['level']
        );
    }

    if (empty($data)){
        $count = 0;
        $data[] = array(
            'ID'=>0,
            'NAME'=>'EMPTY',
            'STATUS'=>'BLOCKED',
            'LEVEL'=>'NOT'
        );
    }else{
        $count = count($results);
    }

    if ($encode == false) {
        $body = $data;
        return $body;
    }else{
        $body = json_encode($data);
        slimJson($body,$count);
    }
}

function slimSMS($body=''){
    global $app;
    // get request
    $request = $app->request();
    // get headers
    $headers = $request->headers();

    // Create Response Header
    $response = $app->response();
    $response->status(200);
    $response['Content-Type'] = 'text/xml';
    $response['X-Powered-By'] = 'Slim';

//    $response['Content-Range'] = '/ ' . $max;
//    error_log("Content-Range [" . $response['Content-Ranger'] . "]" . chr(10));
/*
    $body = "<Response>
<Message>
Do I Know You
</Message>
</Response>";
*/
    $response->body('<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"');
    $response->body($body);

}

function slimJson($body,$max){
    global $app;
    // get request
    $request = $app->request();
    // get headers
    $headers = $request->headers();

    // Create Response Header
    $response = $app->response();
    $response->status(200);
    $response['Content-Type'] = 'application/json';
    $response['X-Powered-By'] = 'Slim';

    $response['Content-Range'] = '/ ' . $max;
    error_log("Content-Range [" . $response['Content-Ranger'] . "]" . chr(10));

    $response->body($body);

}

function addTwilioNumber ($number = 0, $greeting = 'default' ){
    $greeting = $greeting == 'default' ? "Twilio ".$number." default greeting" : $greeting;

    $json = json_decode( checkNumber($number,true,false,'twilio') ,true);


    if ($json['max'] != '0'){
        json_error(__FUNCTION__,'failure',0,'Number [ '.$number. ' ]  Exists');
    }else{
        _addTwilioNumber($number,$greeting);
    }

}


function checkColumnLength($table,$column,$length){

}

function _addTwilioNumber($number,$greeting){
    global $app, $link,$database,$db_table;

    init_db($database);

    $db_table = 'twilio';

    $sql = "INSERT INTO ".$database.'.'.$db_table." (id,greeting) values('".$number."','".$greeting."')";

    $results = mysql_query($sql,$link)
    OR Die('SQL Failed: '.$sql);

    checkNumber($number,true,true,'twilio');
}

function json_error($function,$status='failure',$max = 0, $error = ''){
    $body = json_encode(array(
        "function"=>$function,
        "status"=>$status,
        "max"=>$max,
        "error"=>$error
    ));
    slimJson($body,$max=99);

}

function testGetFunctionName(){
    echo "function: " . __FUNCTION__;
}


$app = new \Slim\Slim(array('debug' => true));
// Required for PUT using JSON *** USE $app->request->getBody() *** instead of $app->request->put()
$app->add(new \Slim\Middleware\ContentTypes());

$app->get('/status','statusHandler');


//$app->get('/checkColumnExists/:table/:column','checkColumnExists');

$app->get('/sms','SMS');
$app->post('/sms','SMS');
//$app->post('/sms','slimSMS');

$app->get('/getList(/:table(/:encode))','getList');

$app->get('/ml/(:message(/:send))','ml');

$app->get('/is_member/(:number)','is_member');

$app->get('/db/check/:name',function($name){
    init_db($name);
    echo "<div align='center'><h1>Database: ".$name." [ Working ]</h1></div>";
});

$app->get('/checkTables(/:name)','checkTables');

$app->get('/testGetFunctionName','testGetFunctionName');

// $app->get('/(:action(/:key1(/:value1(/:key2(/:value2(/:key3(/:value3(/:key4(/:value4(/:key5(/:value5)))))))))))','actionHandler');

$app->run();
?>