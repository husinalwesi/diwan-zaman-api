<?php

/**
 * @author Hussein Alwesi
 * @copyright 2017
 */

class melhem
{
  /**
  * class construct
  */
  public function __construct()
  {

  }
  /**
  * check header request parameters and return its secure value and strip
  * the tags
  */
  public function getHeaderParams($var,$default=0)
  {
    $data = 0;
    $flag_par = 0;
    $data =getallheaders();
    if(isset($data[$var])) $default = $data[$var];
    return $default;
  }
  /**
  * check post and get request parameters and return its secure value and strip
  * the tags
  */
  public function getSecureParams($var,$default=0)
  {
      $data = 0;
      $flag_par = 0;

      if(isset($_POST[$var]))
      {
        $data = strip_tags($_POST[$var]);
        $flag_par=1;
      }else if(isset($_GET[$var]))
      {
        $data = strip_tags($_GET[$var]);
        $flag_par=1;
      }
      if($flag_par)
      {
        $default = $data;
      }
      $default = str_replace("'","",$default);
      $default = str_replace('"',"",$default);
      return $default;
  }

  public function getJsonParam()
  {
    // Takes raw data from the request
    $json = file_get_contents('php://input');
    // 
    // Converts it into a PHP object
    $data = json_decode($json, true);
    return $data;
    // return json_encode($data);
  }

  public function getIP()
  {
    $key = "85c3aef9a2d34fc3b3117b78ad59e237";
    $url = "https://api.bigdatacloud.net/data/ip-geolocation?key=$key";
    $data = file_get_contents($url);
    if($data){
      $data = json_decode($data);
      return $data->ip;
    }
    return "0";
  }

  public function setIPLog($user_id)
  {
    $params = array(
      'id' => '',
      'user_id' => $user_id,
      'ip' => $this->getIP(),
      "created_date" => time()
    );
    // 
    $this->queryInsert('users_log',$params);
    //
  }

  
  /**
  * check post and get request parameters and return its secure value
  */
  public function getParams($var,$default=0)
  {
    if(isset($_POST[$var]))
    {
      $default = $_POST[$var];
    }else if(isset($_GET[$var]))
    {
      $default = $_POST[$var];
    }
    return $default;
  }
  public function getMutliParams($stringFlag)
  {
    $data = array();
    foreach($_POST as $key=>$value)
    {

      if(strpos($key,$stringFlag) !== false)
      {
        if(!$value) $value=0;
        $data[$key]=$value;
      }
    }
    return $data;
  }
  /**
  * check controller name and include it
  */
  public function getControllerHandler($controller_name="")
  {

    if(!$controller_name) $controller_name ="ctrl_".$this->getSecureParams("type");
    else $controller_name = "ctrl_".$controller_name;
    $dirname = dirname(dirname(__FILE__));
    $file_name = $dirname."/controllers/$controller_name".".php";
    if(!file_exists($file_name))
    {
      $controller_name = "controllers/ctrl_error.php";
    }else{
        $controller_name = "controllers/$controller_name".".php";
    }
    include($controller_name);
  }
  /**
  * create object from requested controller
  */
  public function newClassObject()
  {
     $newObject = null;
     $className = $this->getSecureParams("type");
     if(class_exists($className))
     {
      $newObject = new $className();
     }
     return $newObject;
  }

    public function handleIsDeleted(){
      $is_deleted = $this->getSecureParams("is_deleted");
      if(!$is_deleted) $is_deleted = "0";
      return "is_deleted='$is_deleted'";
  }

  public function handleQuery(){
      $query = $this->getSecureParams("query");
      if(!$query) $query = "*";
      return $query;
  }

  public function getTotal($table_name,$param){
      $total = "0";
      $handleIsDeleted = $this->handleIsDeleted();
      $total_obj = $this->queryResponse("select count($param) as '$param' from $table_name where $handleIsDeleted");
      if($total_obj) $total = $total_obj[0][$param];
      // 
      return array(
          "total" => $total,
          "pages" => $this->calculatePages($total)
      );
  }

  public function getTotalWhere($table_name,$param,$where){
    $total = "0";
    $total_obj = $this->queryResponse("select count($param) as '$param' from $table_name where $where");
    if($total_obj) $total = $total_obj[0][$param];
    // 
    return array(
        "total" => $total,
        "pages" => $this->calculatePages($total)
    );
}

  public function calculatePages($total){
      $limit = $this->getSecureParams("limit");
      if(!$limit) $limit = "10";
      // 
      $pages = ceil($total / $limit);
      if(!$pages) $pages = "0";
      return $pages;
  }

  public function handlePagination(){
      $limit = $this->getSecureParams("limit");
      $page = $this->getSecureParams("page");
      if(!$limit) $limit = "10";
      if(!$page) $page = "1";
      // 
      $offset = ($limit * $page) - $limit;
      // 
      return "limit $limit OFFSET $offset";
  }

  public function timeStampToDate($val,$type){
    $result = "";
    if($type == "dateTime"){
      $result = date('d.m.Y g:i a', $val);
    }
    // 
    return $result;
  }

  public function deleteDbRow($table,$colomn_id_name,$id){
    if(!$table || !$colomn_id_name || !$id) $this->getResponse(503,"parameter needed");
    $sql = "select $colomn_id_name from $table where $colomn_id_name='$id'";
    $data = $this->queryResponse($sql);
    if(!$data) $this->getResponse(503,"row not found");
    $this->queryResponse("delete from $table where $colomn_id_name='$id'");
    // 
    // $params_to_edit = array('is_deleted'=>'1');
    // if(!$this->queryUpdate($table,$params_to_edit,"where $colomn_id_name='$id'")) $this->getResponse(503);
    return true;
  }

  public function restoreDbRow($table,$colomn_id_name,$id){
    if(!$table || !$colomn_id_name || !$id) $this->getResponse(503,"parameter needed");
    $sql = "select $colomn_id_name from $table where $colomn_id_name='$id'";
    $data = $this->queryResponse($sql);
    if(!$data) $this->getResponse(503,"row not found");
    $params_to_edit = array('is_deleted'=>'0');
    if(!$this->queryUpdate($table,$params_to_edit,"where $colomn_id_name='$id'")) $this->getResponse(503);
    return true;
  }

  public function changeStatus($table,$colomn_id_name,$id,$status){
    if(!$table || !$colomn_id_name || !$id) $this->getResponse(503,"parameter needed");
    $sql = "select $colomn_id_name from $table where $colomn_id_name='$id'";
    $data = $this->queryResponse($sql);
    if(!$data) $this->getResponse(503,"row not found");
    $params_to_edit = array('status'=>$status);
    if(!$this->queryUpdate($table,$params_to_edit,"where $colomn_id_name='$id'")) $this->getResponse(503);
    return true;
  }

}
?>
