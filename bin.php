<?php

$docpath = "files/";
$indexpath = "index/";
$randomchars = "abcdefghijklmnopqrstuvwxyz0123456789";
$filenamelength = 16;
$fileextension = '.paste';
$indexextension = '.index';
$maxcontentlength = 1024 * 1024 * 25;

//xhtml utf8 header
//header('Content-type: application/xhtml+xml;charset=utf-8');

mkdir($docpath);
mkdir($indexpath);

if(count($_POST) > 0)
{
  //check for valid post
  if(!isset($_POST['action']))
  {
    return;
  }

  //handle
  $action = $_POST['action'];
  switch($action)
  {
    case 'upload':
      if ($error != UPLOAD_ERR_OK)
      {
        echo '{"type":"Error", "msg"="File upload failed!"}';
        return;
      }
      
      //check contents
      if($_FILES['file']['size'] > $maxcontentlength)
      {
        echo '{"type":"Error", "msg"="Maximum file size is '.$maxcontentlength.'! (was '.$_FILES['file']['size'].')"}';
        return;
      }
    
      //get new filename
      do
      {
        $pastename = getRandomName();
        $filename = $pastename.$fileextension;
      } while(file_exists($indexpath.$filename));
      
      //save
      move_uploaded_file($_FILES['file']['tmp_name'], $docpath.$filename);
      //file_put_contents($docpath.$filename, $content);
      file_put_contents($indexpath.$pastename.'.index', 'Filename: '.$_FILES['file']['name']."\n".
                             'Extension: '.end(explode('.', $_FILES['file']['name']))."\n".
                             'Type: '.$_FILES['file']['type']."\n".
                             'Time: '.time()."\n");

      //send new url
      echo '{"type":"Success","msg":"'.$pastename.'"}';
      
      break;
    case 'dl':
      $doc = $_POST['id'];
      $indexfile = $indexpath.$doc.$indexextension;
      $file = $docpath.$doc.$fileextension;
      if (file_exists($file))
      {
        $index = readIndex($indexfile);
        header('Content-Type: '.$index['Type']);
        header('Content-Disposition: attachment; filename="'.$index['Filename'].'"');
        readfile($file);
      }
      else
      {
        printHeader('Error');
        error('We don\'t know that file!');
        printFooter();
      }
      break;
    default:
      echo '{"type":"Error","msg":"Unsupported operation! IP logged."}';
      break;
  }
  
  return;
}

$client = strtolower($_SERVER['HTTP_USER_AGENT']);
$iscmdline = strpos($client, 'wget') !== false || strpos($client, 'curl') !== false;

//var_dump($_GET);
if(isset($_GET["f"]))
{
  $doc = $_GET["f"];
  if ($iscmdline)
  {
    http_response_code(301);
    header('Location: https://'.$_SERVER[HTTP_HOST].'/d'.$_SERVER[REQUEST_URI]);
    die();
  }
  
  //valid check
  if(!preg_match('#(a-z0-9)*#', $doc))
  {
    printHeader(false, 'Error');
    error('Unsupported operation! IP logged.');
    return;
  }
  
  $indexfile = $indexpath.$doc.$indexextension;
  $file = $docpath.$doc.$fileextension;
  if(file_exists($file))
  {  
    $index = readIndex($indexfile);
	
    //header
    printHeader($index['Filename']);
    
    printContent($file, $index, $doc);
  }
  else
  {
    printHeader('Error');
    error('The requested file does not exist!');
  }
}
elseif(isset($_GET["d"]))
{
  $doc = $_GET["d"];
  
  //valid check
  if(!preg_match('#(a-z0-9)*#', $doc))
  {
    printHeader(false, 'Error');
    error('Unsupported operation! IP logged.');
    return;
  }
  
  $indexfile = $indexpath.$doc.$indexextension;
  $file = $docpath.$doc.$fileextension;
  if(file_exists($file))
  {
    $index = readIndex($indexfile);
    header('Content-Type: '.$index['Type']);
    if ($iscmdline)
      header('Content-Disposition: attachment; filename="'.$index['Filename'].'"');
    readfile($file);
    return;
  }
  else
  {
    printHeader('Error');
    error('The requested file does not exist!');
  }
}
else
{
  printHeader('New file drop');
  
  echo '
    <input id="upload_input" type="file" style="display: none" />
    <div id="drop_zone" style="width: 100%; height: 100%">
      <div style="position: absolute; top: 50%; left: 50%; margin-right: -50%; transform: translate(-50%, -50%)">
        <img src="icon/upload.png" width="256px" height="256px"/>
        <p id="text" class="caption">Drop file to upload here</p>
        <div style="display: none" align="center" id="progress-wrp">
          <div class="progress-bar"></div>
          <div class="status">0%</div>
        </div>
      </div>
    </div>
	';
}
printFooter();


function printHeader($title = null)
{ // TODO noselect for rightupperbox
  echo '
<html style="min-height: 100%;">
  <head>
    <meta charset="utf-8">
    <title>'.($title != null ? $title.' - ' : '').'Filedrop</title>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script type="text/javascript" src="script.js"></script>
    <link href="style.css" type="text/css" rel="stylesheet" />
    <link href="favicon.png" rel="shortcut icon" type="image/x-icon" />
    <link href="favicon.png" rel="icon" type="image/x-icon" />
  </head>
  <body>
    <div class="rightupperedge noselect">
      <div class="boxcontainer"><div class="box">
        <ul>
          <li><img id="optionnew" class="boxoption" alt="new" title="New" src="new.png" /></li>
          <li><img id="optiondl" class="boxoption" alt="dl" title="Download" src="download.png" onClick="dl()"/></li>
        </ul>
      </div></div>
    </div>
  ';
}
function printFooter()
{
  echo "
  </body>
</html>";
}

function getRandomName()
{
  global $randomchars, $filenamelength;
  $ret = "";
  for($i = 0; $i < $filenamelength; $i++)
  {
    $ret .= substr($randomchars, rand(0, strlen($randomchars)), 1);
  }
  return $ret;
}

function error($msg)
{
  echo '<div style="position: absolute; top: 50%; left: 50%; margin-right: -50%; transform: translate(-50%, -50%)">';
  echo '<img src="icon/error.png" width="256px" height="256px"/>
        <p class="error" class="caption">'.$msg.'</p><br />';
  echo '</div>';
}

function printContent($file, $index, $pastename)
{
  echo '<div id="fullscreen" style="width: 100%; height: 100%">
        <div style="position: absolute; top: 50%; left: 50%; margin-right: -50%; margin-bottom: -50%; transform: translate(-50%, -50%)">
        <form id="dlform" action="/" target="_blank" method="POST">
        <input type="hidden" name="action" value="dl">
        <input type="hidden" name="id" value="'.$pastename.'">';
  //echo $index['Type'];
  switch($index['Type'])
  {
    case 'application/pdf':
      //echo '<img src="icon/pdf.png" width="256px" height="256px" onClick="dl()"/>
            //<p class="caption">'.$index['Filename'].'</p>
            //<p class="caption">'.formatSizeUnits(filesize($file)).'</p>';
      echo '<iframe id="pdf" src="/d/'.$pastename.'" type="application/pdf"></iframe>
            <div id="pdfcaption" class="caption">
              <p class="caption">'.$index['Filename'].'</p>
              <p class="caption">'.formatSizeUnits(filesize($file)).'</p>
            </div>';
      break;
    case 'application/zip':
      echo '<img src="icon/zip.png" width="256px" height="256px" onClick="dl()"/>
            <p class="caption">'.$index['Filename'].'</p>
            <p class="caption">'.formatSizeUnits(filesize($file)).'</p>';
      break;
    case 'application/vnd.rar':
    case 'application/x-rar-compressed':
      echo '<img src="icon/rar.png" width="256px" height="256px" onClick="dl()"/>
            <p class="caption">'.$index['Filename'].'</p>
            <p class="caption">'.formatSizeUnits(filesize($file)).'</p>';
      break;
    case 'image/png':
    case 'image/jpg':
    case 'image/jpeg':
    case 'image/gif':
      //echo '<img id="img" src="data:'.$index['Type'].';base64,'.base64_encode(file_get_contents($file)).'" onClick="dl()"/>
            //<p id="imgcaption" class="caption" style="position: relative;">'.$index['Filename'].'</p></div>';
      echo '<img id="img" src="/d/'.$pastename.'"/>
            <p id="imgcaption" class="caption" style="position: relative;">'.$index['Filename'].'</p></div>';
      break;
    case 'video/mp4':
    case 'video/ogg':
    case 'video/webm':
      //echo '<video id="img" autoplay controls>
            //<source src="data:'.$index['Type'].';base64,'.base64_encode(file_get_contents($file)).'"/>
            //Your &quot;browser&quot; does not support HTML5 video. HAHAHA!
            //</video>
            //<p id="imgcaption" class="caption" style="position: relative;" onClick="dl()">'.$index['Filename'].'</p></div>';
      echo '<video id="img" autoplay controls>
            <source src="/d/'.$pastename.'"/>
            Your &quot;browser&quot; does not support HTML5 video. HAHAHA!
            </video>
            <p id="imgcaption" class="caption" style="position: relative;">'.$index['Filename'].'</p></div>';
      break;
    default:
      echo '<img src="icon/other.png" width="256px" height="256px" onClick="dl()"/>
            <p class="caption">'.$index['Filename'].'</p>
            <p class="caption">'.formatSizeUnits(filesize($file)).'</p>';
      break;
  }
  echo '</form></div></div>';
}

function readIndex($file)
{
  $t = explode("\n", file_get_contents($file));
  $index = array();
  foreach($t as $e)
  {
	  $x = explode(':', $e, 2);
	  $index[trim($x[0])] = trim($x[1]);
  }
  return $index;
}
function formatSizeUnits($bytes)
{
  if ($bytes >= 1024 * 1024 * 1024)
    $bytes = rtrim(rtrim(number_format($bytes / (1024 * 1024 * 1024), 2), '0'), localeconv()['decimal_point']).' GiB';
  elseif ($bytes >= 10024 * 1024)
    $bytes = rtrim(rtrim(number_format($bytes / (1024 * 1024), 2), '0'), localeconv()['decimal_point']).' MiB';
  elseif ($bytes >= 1024)
    $bytes = rtrim(rtrim(number_format($bytes / 1024, 2), '0'), localeconv()['decimal_point']).' KiB';
  elseif ($bytes > 1)
    $bytes = $bytes.' bytes';
  elseif ($bytes == 1)
    $bytes = $bytes.' byte';
  else
    $bytes = '0 bytes';

  return $bytes;
}
?>
