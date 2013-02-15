<?php

namespace Walterra\J4p5Bundle\j4p5;

class output {
  private static $className = '';
  private static $key = '';
  private static $value = '';
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
      self::$key = $key;
      self::$value = $value;
  }

  public function get()
  {
      return array(
        "key" => self::$key,
        "value" => self::$value
      );
  }
}

?>
