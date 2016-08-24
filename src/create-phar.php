<?php
  $srcRoot = "../src";
  $buildRoot = "../build";

  $phar = new Phar($buildRoot . "/gen_phpunit_skel.phar", 
  FilesystemIterator::CURRENT_AS_FILEINFO |       FilesystemIterator::KEY_AS_FILENAME, "gen_phpunit_skel.phar");
  $phar->setStub("#!/usr/bin/php\n<?php Phar::mapPhar();include 'phar://gen_phpunit_skel.phar/gen_phpunit_skel.php';__HALT_COMPILER(); ?>");
  $phar["gen_phpunit_skel.php"] = file_get_contents($srcRoot . "/gen_phpunit_skel.php");

