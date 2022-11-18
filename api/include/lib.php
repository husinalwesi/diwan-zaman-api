<?php

/**
 * @author Hussein Alwesi
 * @copyright 2017
 * this class to process the database connections and sql statments using pdo
 */

class db extends MConfig
{
  var $conn;
  var $query;
  var $sql_result;

  /***
  * create datbase connection and slect the database
  */
  public function __construct()
  {
    try {
      $this->conn = new PDO("mysql:host=$this->db_host;dbname=$this->db_name;charset=utf8", $this->db_user, $this->db_pass);
    }
    catch(PDOException $e){
      echo "Database Lose Connection";
      exit(0);
    //echo $e->getMessage();
    }
  }

  /**
  * prepare the sql statments
  */
  public function setQuery($query)
  {
    $this->sql_result = $this->conn->prepare(trim($query));
    $this->sql = $this->sql_result->queryString;
  }
  public function error_json()
  {

  }
  /**
  * run the sql statments and check the if its process fine or not
  */
  public function getQuery()
  {
    $sql_result = false;

    if(strPos($this->sql,"select")===0)
    {
      $sql_result = $this->conn->query($this->sql);
      if($sql_result) $this->sql_result->execute();

    }else{
      $sql_result = $this->conn->exec($this->sql);
    }

    return $sql_result;
  }
  /**
  * return the count of the selected rows from table
  */
  public function getRowCount()
  {
    return $this->sql_result->rowCount();
  }
  /**
  * fetch the selected rows
  */
  public function getRowDataArray()
  {
    $rows = array();
    while($dataArray = $this->sql_result->fetch(PDO::FETCH_ASSOC))
    {
      $rows[]=$dataArray;
    }
    return $rows;
  }
  /**
  * return the last insert id after insert statment process
  */
  public function getLastInsertedId()
  {
     return $this->conn->lastInsertId();
  }
}
?>
