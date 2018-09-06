<?php
include("simple_html_dom.php");
$crawled_urls=array();

function rel2abs($rel, $base){
 if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;
 if ($rel[0]=='#' || $rel[0]=='?') return $base.$rel;
 extract(parse_url($base));
 $path = preg_replace('#/[^/]*$#', '', $path);
 if ($rel[0] == '/') $path = '';
 $abs = "$host$path/$rel";
 $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
 $abs=str_replace("../","",$abs);
 return $scheme.'://'.$abs;
}
function perfect_url($u,$b){
 $bp=parse_url($b);
 if(($bp['path']!="/" && $bp['path']!="") || $bp['path']==''){
  if($bp['scheme']==""){$scheme="http";}else{$scheme=$bp['scheme'];}
  $b=$scheme."://".$bp['host']."/";
 }
 if(substr($u,0,2)=="//"){
  $u="http:".$u;
 }
 if(substr($u,0,4)!="http"){
  $u=rel2abs($u,$b);
 }
 return $u;
}
function crawl_site($u){
 ini_set('max_execution_time', 300);
 ini_set('memory_limit', '-1');
 $found_urls=array();
 global $crawled_urls;
 $uen=urlencode($u);
 if((array_key_exists($uen,$crawled_urls)==0 || $crawled_urls[$uen] < date("YmdHis",strtotime('-25 seconds', time())))){
  $html = file_get_html($u);
  foreach($html->find("a") as $li){
   $url=perfect_url($li->href,$u);
   $enurl=urlencode($url);
   if($url!='' && substr($url,0,4)!="mail" && substr($url,0,4)!="java" && array_key_exists($enurl,$found_urls)==0){
    $found_urls[$enurl]=1;
    $page = $url;
    //echo "<li><a target='_blank' href='".$page."'>".$page."</a></li>";
    for ($x = 1; $x <= 3; $x++) {
     $sub_page =  crawl_site($page);
    }




   }
  }
 }
}

// $dbhost = "127.0.0.1:3306";
// $dbuser = ‘root’;
// $dbpass = "";
$dbname = 'crawler';
$dbtable = 'pages';
$dbtable2 = 'subpages';

$conn = mysqli_connect("127.0.0.1:3306","root" ,"");


if (!$conn) {
    die("Connection failed:" . mysqli_connect_error());
}
$table1 = "CREATE TABLE `$dbname`.`$dbtable`(
   'idpage'   INT (11)   NOT NULL,
   'page_name' VARCHAR (2083)     NOT NULL,
   PRIMARY KEY ('idpage'));";

$table2 = "CREATE TABLE `$dbname`.`$dbtable2` (
    'idsubpage' int NOT NULL,
    'subpage_name' VARCHAR (2083)  NOT NULL,
    'idpage' INT(11),
    PRIMARY KEY ('idsubpage'),
    FOREIGN KEY ('idpage') REFERENCES $dbname.$dbtable('idpage'));";


$sql ="INSERT INTO `$dbname`.`$dbtable` ('idpage', 'page_name')
    VALUES (NULL, '$page');";


$sql2 ="INSERT INTO `$dbname`.`$dbtable2` ('idpage', 'idsubpage', 'subpage_name')
        VALUES (NULL, NULL, '$sub_page');";




if (mysqli_query($conn, $sql)) {
    echo "New record created successfully";}
 else {
   echo "Error: " . $sql . "<br>" . mysqli_error($conn);
}



mysqli_close($conn);
crawl_site("$https://www.homegate.ch/mieten/immobilien/kanton-zuerich/trefferliste");

?>
