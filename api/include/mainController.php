<?php

/**
 * @author Hussein Alwesi
 * @copyright 2017
 */

class mainController extends melhem
{
  /**
  * check authentication for API call "the value of the token id" and check the
  * validation of the method request
  */
   var $msg            = "";
   var $api_token      = "123456";
   var $dataArray      = false;
   var $request_status = "1";
   var $resturant_id   = 0;
   var $time_zone = 'Asia/Amman';
   //private time_zone;
  /**
  * check authentication for API call "the value of the token id"
  */
  public function checkAuth()
  {
    $token  = $this->getSecureParams("token_id");
    $action = $this->getSecureParams("action");
    if(!$token || $token!= $this->api_token){
      $this->getResponse(401);
    }
  }
  /**
  * check authentication for API call "the value of the token id"
  */
  public function callMethod($object)
  {
    $action = $this->getSecureParams("action");
    if($action) $action .= ucfirst(get_class($object));
    if(method_exists($object,"$action"))
    {
      $object->$action();
    }else{
      $this->getResponse(400);
    }
  }
  /**
  * print api response as json string
  */
  public function getResponse($status,$msg='')
  {
    ob_end_clean();
    header("Content-type: application/json; charset=utf-8");
    $dataMsg[200] = 'OK';
    $dataMsg[201] = 'Already Exists';
    $dataMsg[204] = 'No Content';
    $dataMsg[400] = 'Bad Request';
    $dataMsg[401] = 'Unauthorized';
    $dataMsg[406] = 'Not Acceptable';
    $dataMsg[503] = 'Service Unavailable';
    $responseArray['status']     = "$status";
    $responseArray['msg']        = $dataMsg[$status];
    if($msg) $responseArray['msg']        = $msg;
    $responseArray['dataObject'] = $this->dataArray;
    echo json_encode($responseArray);
    exit(0);
  }

  public function getResponseDirect($status,$msg='')
  {
    ob_end_clean();
    header("Content-type: application/json; charset=utf-8");
    $responseArray = $this->dataArray;
    echo json_encode($responseArray);
    exit(0);
  }

  public function queryResponse($sql,$flag=1)
  {
    $db = new db();
    $db->setQuery($sql);
    if($db->getQuery()!==false)
    {
      if(strPos($sql,"select")===0)
      {
        $rows = array();
        if($db->getRowCount()){
          $rows = $db->getRowDataArray();
        }
        return $rows;
      }else if(strPos($sql,"insert")===0){
        return $db->getLastInsertedId();
      }else if(strPos($sql,"BEGIN"))
      {
          $this->getResponse(200);
      }else{
        if($flag) $this->getResponse(200);
        return 1;
      }

    }
    $this->getResponse(503);
  }

  public function uploadImagesBase64($img)
  {
    $user_id = 1;
    $img = str_replace('data:image/png;base64,', '', $img);
    $data = base64_decode($img);
    $img_name = uniqid()."_".$user_id.".png";
    $target_file = '../uploads/'.$user_id."/".$img_name;
    $success = file_put_contents($target_file, $data);
    // if($success) return "uploaded";
    // else return "unable to upload";
    return $img_name;
  }

  public function makeThumbnails($updir, $img, $img_name)
  {
    $thumbnail_width =250;
    $thumbnail_height = 250;
    $thumb_beforeword = "thumb";
    $arr_image_details = getimagesize($img); // pass id to thumb name
    $original_width = $arr_image_details[0];
    $original_height = $arr_image_details[1];
    if ($original_width > $original_height) {
        $new_width = $thumbnail_width;
        $new_height = intval($original_height * $new_width / $original_width);
    } else {
        $new_height = $thumbnail_height;
        $new_width = intval($original_width * $new_height / $original_height);
    }
    $dest_x = intval(($thumbnail_width - $new_width) / 2);
    $dest_y = intval(($thumbnail_height - $new_height) / 2);
    if ($arr_image_details[2] == 1) {
        $imgt = "ImageGIF";
        $imgcreatefrom = "ImageCreateFromGIF";
    }
    if ($arr_image_details[2] == 2) {
        $imgt = "ImageJPEG";
        $imgcreatefrom = "ImageCreateFromJPEG";
    }
    if ($arr_image_details[2] == 3) {
        $imgt = "ImagePNG";
        $imgcreatefrom = "ImageCreateFromPNG";
    }
    if ($imgt) {
        $old_image = $imgcreatefrom($img);
        $new_image = imagecreatetruecolor($thumbnail_width, $thumbnail_height);
        imagecopyresized($new_image, $old_image, $dest_x, $dest_y, 0, 0, $new_width, $new_height, $original_width, $original_height);
        $imgt($new_image, $updir.$img_name);
    }
  }

  public function uploadMultipartImagesThumbnails($user_id=1)
  {
    $img_name = array();
    if(!file_exists("../uploads/$user_id")){
      mkdir("../uploads/$user_id");
      mkdir("../uploads/thumbnails/$user_id");
    }
    foreach ($_FILES as $imgname => $value) {
      if(is_array($_FILES[$imgname]['name']))
      {
        foreach ($_FILES[$imgname]['name'] as $key1 => $value1) {
          if(is_array($value1))
          {
            foreach ($value1 as $i=>$v) {
                $extension = explode(".",$_FILES[$imgname]['name'][$key1][$i]);
                $extension =  $extension[count($extension)-1];
                $mg = uniqid()."_".$user_id.".".$extension;
                $img_name[$imgname][$key1][$i] = $mg;
                $originalImage  = "../uploads/$user_id/".$mg;
                $action = move_uploaded_file($_FILES[$imgname]['tmp_name'][$key1][$i], $originalImage);
                $img_name[$imgname][$key1][$i] = ROOTIMAGEURL.$mg;
            }
          }else{
            if($_FILES[$imgname]['name'][$key1]){
              $extension = explode(".",$_FILES[$imgname]['name'][$key1]);
              $extension =  $extension[count($extension)-1];
              $mg = uniqid()."_".$user_id.".".$extension;
              $img_name[$imgname][$key1] = $mg;
              $originalImage  = "../uploads/$user_id/".$mg;
              $action = move_uploaded_file($_FILES[$imgname]['tmp_name'][$key1], $originalImage);
              $img_name[$imgname][$key1] = ROOTIMAGEURL.$mg;
              //$this->makeMutliPartThumbnails("../uploads/thumbnails/$user_id/",$originalImage,$mg);
            }
          }
        }
      }else{
          if($_FILES[$imgname]['name']){
            $extension = explode(".",$_FILES[$imgname]['name']);
            $extension =  $extension[count($extension)-1];
            $mg = uniqid()."_".$user_id.".".$extension;
            $img_name[$imgname] = $mg;
            $originalImage  = "../uploads/$user_id/".$mg;
            $action = move_uploaded_file($_FILES[$imgname]['tmp_name'], $originalImage);
            // $originalImage
            //$this->makeMutliPartThumbnails("../uploads/thumbnails/$user_id/",$originalImage,$mg);
            $img_name[$imgname] = ROOTIMAGEURL.''.$mg;
            // $this->getResponse(200,$imgname." | ".$img_name[$imgname]);
            return $img_name[$imgname];
          }
      }
    }
   return $img_name['img'];
  }

  public function uploadMultipartImagesThumbnailsMultiple($user_id=1)
  {
    $img_name = array();
    if(!file_exists("../uploads/$user_id")){
      mkdir("../uploads/$user_id");
      mkdir("../uploads/thumbnails/$user_id");
    }
    foreach ($_FILES as $imgname => $value) {
      if(is_array($_FILES[$imgname]['name']))
      {
        foreach ($_FILES[$imgname]['name'] as $key1 => $value1) {
          if(is_array($value1))
          {
            foreach ($value1 as $i=>$v) {
                $extension = explode(".",$_FILES[$imgname]['name'][$key1][$i]);
                $extension =  $extension[count($extension)-1];
                $mg = uniqid()."_".$user_id.".".$extension;
                $img_name[$imgname][$key1][$i] = $mg;
                $originalImage  = "../uploads/$user_id/".$mg;
                $action = move_uploaded_file($_FILES[$imgname]['tmp_name'][$key1][$i], $originalImage);
                $img_name[$imgname][$key1][$i] = ROOTIMAGEURL.$mg;
            }
          }else{
            if($_FILES[$imgname]['name'][$key1]){
              $extension = explode(".",$_FILES[$imgname]['name'][$key1]);
              $extension =  $extension[count($extension)-1];
              $mg = uniqid()."_".$user_id.".".$extension;
              $img_name[$imgname][$key1] = $mg;
              $originalImage  = "../uploads/$user_id/".$mg;
              $action = move_uploaded_file($_FILES[$imgname]['tmp_name'][$key1], $originalImage);
              $img_name[$imgname][$key1] = ROOTIMAGEURL.$mg;
              //$this->makeMutliPartThumbnails("../uploads/thumbnails/$user_id/",$originalImage,$mg);
            }
          }
        }
      }else{
          if($_FILES[$imgname]['name']){
            $extension = explode(".",$_FILES[$imgname]['name']);
            $extension =  $extension[count($extension)-1];
            $mg = uniqid()."_".$user_id.".".$extension;
            $img_name[$imgname] = $mg;
            $originalImage  = "../uploads/$user_id/".$mg;
            $action = move_uploaded_file($_FILES[$imgname]['tmp_name'], $originalImage);
            //$this->makeMutliPartThumbnails("../uploads/thumbnails/$user_id/",$originalImage,$mg);
            $img_name[$imgname] = ROOTIMAGEURL.$mg;
          }
      }
    }

   return $img_name;
  }

  public function makeMutliPartThumbnails($updir, $img, $img_name)
  {
    return;
    $thumbnail_width =250;
    $thumbnail_height = 250;
    $thumb_beforeword = "thumb";
    $arr_image_details = getimagesize($img); // pass id to thumb name
    $original_width = $arr_image_details[0];
    $original_height = $arr_image_details[1];
    if ($original_width > $original_height) {
        $new_width = $thumbnail_width;
        $new_height = intval($original_height * $new_width / $original_width);
    } else {
        $new_height = $thumbnail_height;
        $new_width = intval($original_width * $new_height / $original_height);
    }
    $dest_x = intval(($thumbnail_width - $new_width) / 2);
    $dest_y = intval(($thumbnail_height - $new_height) / 2);
    if ($arr_image_details[2] == 1) {
        $imgt = "ImageGIF";
        $imgcreatefrom = "ImageCreateFromGIF";
    }
    if ($arr_image_details[2] == 2) {
        $imgt = "ImageJPEG";
        $imgcreatefrom = "ImageCreateFromJPEG";
    }
    if ($arr_image_details[2] == 3) {
        $imgt = "ImagePNG";
        $imgcreatefrom = "ImageCreateFromPNG";
    }
    if ($imgt) {
        $old_image = $imgcreatefrom($img);
        $new_image = imagecreatetruecolor($thumbnail_width, $thumbnail_height);
        imagecopyresized($new_image, $old_image, $dest_x, $dest_y, 0, 0, $new_width, $new_height, $original_width, $original_height);
        $imgt($new_image, $updir.$img_name);
    }
  }
  public function queryInsert($tableName,$params,$flag=0)
  {
    $db = new db();
    $columns = array();
    $values = array();
    foreach ($params as $key => $value) {
      $columns[]=$key;
      $values[]=$value;
    }
    $columns = implode(",",$columns);
    $values = implode("','",$values);
    $sql = "INSERT INTO $tableName ($columns) VALUES ('$values')";
    // $this->getResponse(200,$sql);
    $db->setQuery($sql);
    if($db->getQuery()!==false)
    {
      return $db->getLastInsertedId();
    }
    return 0;
  }

  public function queryUpdate($tableName,$params,$where='')
  {
    $db = new db();
    $values = array();
    foreach ($params as $key => $value) {
      $values[]="$key = '$value'";
    }
    $values = implode(",",$values);
    $sql = "UPDATE $tableName SET $values $where";
    // $this->getResponse(200,$sql);
    $db->setQuery($sql);
    if($db->getQuery()!==false)
    {
      return 1;
    }
    return 0;
  }


  public function sendMail($email,$content)
  {
    require_once 'include/PHPMailer-master/PHPMailerAutoload.php';
    $mail = new PHPMailer;
    //$mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = Email_HOST;  // Specify main and backup SMTP servers
    $mail->SMTPAuth = false;                               // Enable SMTP authentication
    $mail->SMTPAutoTLS = false;
    $mail->Username = Email_SEND_FROM;                 // SMTP username
    $mail->Password = Email_PASSWORD;                           // SMTP password
    $mail->SMTPSecure = Email_SMTPSECURE;                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = Email_PORT;//587 //465                                   // TCP port to connect to
    //$mail->SMTPDebug = 2;
    $mail->setFrom(Email_SEND_FROM,Email_TITLE);
    $mail->addAddress($email,Email_TITLE);     // Add a recipient
    //$mail->addAddress('ellen@example.com');               // Name is optional
    //$mail->addReplyTo('info@example.com', 'Information');
    //$mail->addCC('cc@example.com');
    //$mail->addBCC('bcc@example.com');

    //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
    //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
    $mail->isHTML(true);                                  // Set email format to HTML
    //http://bit.ly/2sJwLsF  this is a short link taken from this   52.34.144.49/easy-arab-dev/web/index.php?type=auth&action=verify
    $mail->Subject = Email_TITLE;
    $mail->Body    = $content;

    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    if(!$mail->send()) {
      //$this->getResponse(200,"Error: ".$mail->ErrorInfo);
      return $mail->ErrorInfo;
      } else {
          $mail->ClearAllRecipients();
          // $this->getResponse(200,"Done");
          return 1 ;
    }
  }

 public function sendDesignedMail($email,$content){
   $mail_structure = "<body style='font-family: Arial,Helvetica,sans-serif; background: #ebebeb; padding: 0;'>
     <table style='padding: 30px 15px;margin: 0 auto; border-radius:8px' cellpadding='0' cellspacing='0' width='640' align='center'>
       <tbody>
          <tr>
             <td valign='top' colspan='3' style='background: #fbfbfb;margin-top:20px;border-radius:5px'>
                <table cellpadding='0' cellspacing='0' width='640' align='center'>
                   <tbody>
                      <tr>
                         <td>
                            <div style='padding: 20px 20px 15px 15px;'>
                               <table>
                                  <tbody>
                                     <tr>
                                        <td style=''><a style='font-size: 18px; text-decoration: none; color: #323232; text-transform: uppercase; letter-spacing: 2px;' href='{siteUrl}' target='_blank'>".Email_TITLE."</a></td>
                                     </tr>
                                  </tbody>
                               </table>
                            </div>
                         </td>
                      </tr>
                      <tr>
                         <td valign='top' colspan='3'>
                            <div style='
                               background: rgb(30, 101, 221); /* Old browsers */
                               width: 675px; min-height: 6px;'></div>
                         </td>
                      </tr>
                        <td style='background: #ffffff; padding: 20px 15px; width:100%'>
                         $content
                        </td>
                   </tbody>
                 </table>
                 <table width='675' height='50' align='center' style='border-radius:0px 0px 5px 5px' bgcolor='#2E2E2E'>
                    <tbody>
                       <tr>
                          <td>
                             <a style='width:675px;display: block;text-align: center;color:#FFFFFF;text-decoration:none;font-size:12px;text-transform: uppercase; letter-spacing: 2px' href='javascript:void(0)' target='_blank'>".Email_TITLE."</a>
                          </td>
                       </tr>
                    </tbody>
                 </table>
               </td>
             </tr>
             <tr>
             <td style='color:rgb(169, 169, 169);font-size:12px; width:100%'>
                <br>
                <b>Disclaimer:</b> This e-mail and any attachments are confidential and may be protected by legal privilege. If you are not the intended recipient, be aware that any disclosure, copying, distribution or use of this e-mail or any attachment is prohibited. If you have received this e-mail in error, please notify us immediately by returning it to the sender and delete this copy from your system. Thank you for your cooperation.
             </td>
           </tr>
       </tbody>
     </table>
   </body>";
   $this->sendMail($email,$mail_structure);
}

 function EXPORT_DATABASE($host,$user,$pass,$name,$tables=false, $backup_name=false)
 {
   set_time_limit(3000);
   $mysqli = new mysqli($host,$user,$pass,$name);
   $mysqli->select_db($name);
   $mysqli->query("SET NAMES 'utf8'");
   $queryTables = $mysqli->query('SHOW TABLES');
   while($row = $queryTables->fetch_row()) {
      $target_tables[] = $row[0];
    }
     if($tables !== false) {
        $target_tables = array_intersect( $target_tables, $tables);
      }
   $content = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\r\nSET time_zone = \"+00:00\";\r\n\r\n\r\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\r\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\r\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\r\n/*!40101 SET NAMES utf8 */;\r\n--\r\n-- Database: `".$name."`\r\n--\r\n\r\n\r\n";
   foreach($target_tables as $table){
     if (empty($table)){
        continue;
      }
     $result	= $mysqli->query('SELECT * FROM `'.$table.'`');
     $fields_amount=$result->field_count;
     $rows_num=$mysqli->affected_rows;
     $res = $mysqli->query('SHOW CREATE TABLE '.$table);
     $TableMLine=$res->fetch_row();
     $content .= "\n\n".$TableMLine[1].";\n\n";
     $TableMLine[1]=str_ireplace('CREATE TABLE `','CREATE TABLE IF NOT EXISTS `',$TableMLine[1]);
     for ($i = 0, $st_counter = 0; $i < $fields_amount;   $i++, $st_counter=0) {
       while($row = $result->fetch_row())	{ //when started (and every after 100 command cycle):
         if ($st_counter%100 == 0 || $st_counter == 0 )	{
           $content .= "\nINSERT INTO ".$table." VALUES";
         }
           $content .= "\n(";    for($j=0; $j<$fields_amount; $j++){
             $row[$j] = str_replace("\n","\\n", addslashes($row[$j]) );
              if (isset($row[$j])){$content .= '"'.$row[$j].'"' ;
              }else{
                $content .= '""';
              }
              if ($j<($fields_amount-1)){
                $content.= ',';}
                 }
                         $content .=")";
         if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num) {
           $content .= ";";
         } else {
           $content .= ",";
         }
           $st_counter=$st_counter+1;
       }
     } $content .="\n\n\n";
   }
   $content .= "\r\n\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";
   $backup_name = $backup_name ? $backup_name : $name.'___('.date('H-i-s').'_'.date('d-m-Y').').sql';
   ob_get_clean();
   header('Content-Type: application/octet-stream');
   header("Content-Transfer-Encoding: Binary");
   header('Content-Length: '. (function_exists('mb_strlen') ? mb_strlen($content, '8bit'): strlen($content)) );
   header("Content-disposition: attachment; filename=\"".$backup_name."\"");
   echo $content;
   exit;
 }

}
?>
