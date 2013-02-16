<?php

namespace Walterra\J4p5Bundle\j4p5;

class output {
  private static $className = '';
  private static $map = array();

  public static function getInstance() 
  {
      static $instance;
      if ($instance === null)
          $instance = new Output();
      return $instance;
  }
  private function __construct() { }

  public function setClassName($name)
  {
      self::$className = $name;
  }
  
  public function getClassName()
  {
      return self::$className;
  }
  
  public function set($key, $value)
  {
      array_push(self::$map, array(
        "key" => $key,
        "value" => $value
      ));
  }

  public function get()
  {
      return array_pop(self::$map);
  }
  
  public function getAll()
  {
      return self::$map;
  }
  
  public function resetMap()
  {
      self::$map = array();
  }
}

?>
