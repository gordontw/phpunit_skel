<?php
  /*
   * Automatic Generate PHPUnit Skeleton
   * Usage:  ./gen_phpunit_skel.php -f file_path -c class_name [-b=bootstrap] [-d hallid=1,sid=2]
   * @Author: Gordon Wang <2016.5.18>
   */

  define(PATH_SEPARATOR, ":");

  $options    = getopt("f:c:b:d:");
  $filePath   = $options['f'];
  $className  = $options['c'];
  $bootstrap  = $options['b'];
  $defines    = $options['d'];
  $baseFile   = basename($filePath,".class.php");
  $filedir    = realpath(dirname($filePath));
  $include    = array($filedir);

  if (empty($filePath) || empty($className)) {
    echo chr(27)."[0;31mUsage: $argv[0] -f file_path -c class_name [-b bootstrap] [-d hallid=1,sid=2]\n";
    die();
  }

  $require = array();
  set_include_path(get_include_path() . PATH_SEPARATOR . $filedir);
  // default bootstrap - ~/dev/ipl_dev/commom/autoload.php
  $defaultinc = getenv("HOME")."/dev/ipl_dev/common/autoload.php";
  if(file_exists($defaultinc) && $defaultinc != realpath($bootstrap)) {
    $defaultdir = realpath(dirname($default_inc));
    $include[] = $defaultdir;
    set_include_path(get_include_path() . PATH_SEPARATOR . $defaultdir);
    $require[] = $defaultinc;
  }

  // add require for this unittest
  if (!empty($bootstrap) && file_exists($bootstrap)) {
    $bootdir = realpath(dirname($bootstrap));
    $include[] = $bootdir;
    set_include_path(get_include_path() . PATH_SEPARATOR . $bootdir);
    $require[] = $bootstrap;
  }
  $require[] = $filePath;

  foreach($require as $req) {
    require $req;
  }

  if (!class_exists($className)) {
    die("Error: cannot find class($className). \n");
  }

  $reflector = new ReflectionClass($className);

  $methods = $reflector->getMethods(ReflectionMethod::IS_PUBLIC);

  date_default_timezone_set('Asia/Taipei');
  $objName = lcfirst(str_replace('_', '', $className));

  $code = "<?php
  /*
   * ${baseFile}__" . str_replace('_', '', $className) . "_Test
   * $baseFile Class($className) PHPUnit
   */\n";

  $initWay = "new $className()";
  if (method_exists($className, '__construct')) {
    $constructMethod = new ReflectionMethod($className, '__construct');
    if (!$constructMethod->isPublic()) {
      if (is_callable(array($className, 'getInstance'))) {
        $initWay = "$className::getInstance()";
      } else if(is_callable(array($className, 'newInstance'))) {
        $initWay = "$className::newInstance()";
      } else {
        $initWay = 'NULL';
      }
    }
  }

  $code .= "  set_include_path(get_include_path().':".implode(":",$include)."');\n";
  foreach($require as $req) {
    $code .= "  require '$req';\n";
  }

  $code .= "
  class " . $baseFile . "__" . str_replace('_', '', $className) . "_Test extends PHPUnit_Framework_TestCase
  {
    protected \$$objName;
    protected function setUp()
    {
      parent::setUp();
      \$this->$objName = $initWay;
    }
    protected function tearDown()
    {
    }\n";

  foreach ($methods as $method) {
    if($method->class != $className) continue;

    $func = $method->name;
    $Func = ucfirst($func);

    if (strlen($Func) > 2 && substr($Func, 0, 2) == '__') continue;

    $rMethod = new ReflectionMethod($className, $method->name);
    $params = $rMethod->getParameters();
    $isStatic = $rMethod->isStatic();
    $isConstructor = $rMethod->isConstructor();

    if($isConstructor) continue;

    $initParamStr = '';
    $callParamStr = '';
    foreach ($params as $param) {
      $default = '';

      $rp = new ReflectionParameter(array($className, $func), $param->name);
      if ($rp->isOptional()) {
        $default = $rp->getDefaultValue();
      }
      if (is_string($default)) {
        $default = "'$default'";
      } else if (is_array($default)) {
        $default = var_export($default, true);
      } else if (is_bool($default)) {
        $default = $default ? 'true' : 'false';
      } else if ($default === null) {
        $default = 'null';
      } else {
        $default = "''";
      }

      $initParamStr .= "
      \$" . $param->name . " = $default;";
      $callParamStr .= '$' . $param->name . ', ';
    }
    $callParamStr = empty($callParamStr) ? $callParamStr : substr($callParamStr, 0, -2);

    /*
     * 如果function comment有指定 @return 的類型,自動產生簡單的判斷
     */
    $returnAssert = '';

    $docComment = $rMethod->getDocComment();
    $docCommentArr = explode("\n", $docComment);
    foreach ($docCommentArr as $comment) {
      if (strpos($comment, '@return') == false) {
        continue;
      }
      $returnCommentArr = explode(' ', strrchr($comment, '@return'));
      if (count($returnCommentArr) >= 2) {
        switch (strtolower($returnCommentArr[1])) {
          case 'bool':
          case 'boolean':
            $returnAssert = '$this->assertTrue(is_bool($rs));';
            break;
          case 'int':
            $returnAssert = '$this->assertTrue(is_int($rs));';
            break;
          case 'integer':
            $returnAssert = '$this->assertTrue(is_integer($rs));';
            break;
          case 'string':
            $returnAssert = '$this->assertTrue(is_string($rs));';
            break;
          case 'object':
            $returnAssert = '$this->assertTrue(is_object($rs));';
            break;
          case 'array':
            $returnAssert = '$this->assertTrue(is_array($rs));';
            break;
          case 'float':
            $returnAssert = '$this->assertTrue(is_float($rs));';
            break;
        }
        break;
      }
    }

    /*------------------- 產生基本的UnitTest ------------------*/
    $code .= "
    /*
     * @group publicMethod
     * $func UnitTest
     */
    public function test$Func() {"
    . (empty($initParamStr) ? '' : "$initParamStr\n") 
    . "\n      "
    . ($isStatic ? "\$res = $className::$func($callParamStr);" : "\$res = \$this->$objName->$func($callParamStr);") 
    . (empty($returnAssert) ? '' : "\n\n        " . $returnAssert . "\n") 
    . "\n      "
    . "\$this->markTestIncomplete("
    . "  'This test has not been implemented yet.'"
    . ");"
    . "
    }
  ";
  }
  $code .= "}\n";
  //echo $code;

  /*--------- write to tests/${filename}Test.php ----------*/
  if(!is_dir("$filedir/tests")) {
    mkdir("$filedir/tests");
  }
  $testfile = $baseFile."Test.php";
  if($fp = fopen("$filedir/tests/$testfile","w")) {
    fwrite($fp, $code);
    fclose($fp);

    echo chr(27)."[0;36mThe UnitTest Skeleton => $filedir/tests/$testfile\n";
  }

?>
