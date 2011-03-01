<?php
class Media_SWF_Dumper
{
  public static function dumpAll($headers, $tags)
  {
    $tagTypes    = include(dirname(__FILE__).'/Tag/TagTypes.php');

    /* SWF Header */
    echo 'Signature: '.$headers['Signature'].PHP_EOL;
    echo 'Version: '.$headers['Version'].PHP_EOL;
    echo 'FileLength: '.$headers['FileLength'].PHP_EOL;
    echo 'FrameSize: '.PHP_EOL;
    echo "\tXmin: ".($headers['FrameSize']['Xmin'] / 20).PHP_EOL;
    echo "\tXmax: ".($headers['FrameSize']['Xmax'] / 20).PHP_EOL;
    echo "\tYmin: ".($headers['FrameSize']['Ymin'] / 20).PHP_EOL;
    echo "\tYmax: ".($headers['FrameSize']['Ymax'] / 20).PHP_EOL;
    echo 'FrameRate: '.($headers['FrameRate'] / 0x100).PHP_EOL;
    echo 'FrameCount: '.$headers['FrameCount'].PHP_EOL;

    /* SWF Tags */
    echo 'Tags:'.PHP_EOL;
    foreach ($tags as $tag) {
      $code = $tag['Code'];
      $length = $tag['Length'];
      echo "\t{$tagTypes[$code]}($code)  Length: $length".PHP_EOL;
      if (isset($tag['Object']))
      {
        $tag['Object']->dump("\t\t");
      }
      elseif (isset($tag['CharacterId']))
      {
        echo "\t\tCharacterId: ".$tag['CharacterId']. PHP_EOL;
      }
    }
  }

  public static function dumpCharacterId($fields, $indent = "\t\t")
  {
    if (isset($fields['CharacterId'])) {
      echo $indent.'CharacterId: '.$fields['CharacterId'].PHP_EOL;
    }
  }
  public static function dumpPlaceObject($fields, $indent = "\t\t")
  {
    if (!is_array($fields)) {
      return;
    }
    foreach ($fields as $field => $value)
    {
      echo $indent . $field . ": ";
      echo (is_array($value)) ? json_encode($value) : $value;
      echo PHP_EOL;
    }
  }

  public static function dumpDoAction($tags, $indent = "\t\t")
  {
    $actionCodes = include(dirname(__FILE__).'/Tag/ActionCodes.php');
    foreach ($tags as $tag)
    {
      $code = $tag['ActionCode'];
      echo $indent . "{$actionCodes[$code]}($code) Length: $length";
      if (isset($tag['Content'])) {
        $length = $tag['Length'];
        $content= $tag['Content'];
        echo " Length: $length Content: $content";
      }
      echo PHP_EOL;
    }
  }

  public static function dumpDefineSprite($fields, $indent = "\t\t")
  {
    $tagTypes    = include(dirname(__FILE__).'/Tag/TagTypes.php');
    echo $indent . "CharacterId: ".$fields['CharacterId'].PHP_EOL;
    echo $indent . "FrameCount: ".$fields['FrameCount'].PHP_EOL;
    foreach ($fields['ControlTags'] as $tag)
    {
      $code = $tag['Code'];
      $length = $tag['Length'];
      echo $indent . "\t{$tagTypes[$code]}($code)  Length: $length".PHP_EOL;
      if (isset($tag['Object']))
      {
        $tag['Object']->dump($indent . "\t\t");
      }
    }
  }

  public static function dumpDefineShape($fields, $indent = "\t\t")
  {
    echo $indent . "CharacterId: ".$fields['CharacterId'].PHP_EOL;
    echo $indent . "ShapeBounds: ".json_encode($fields['ShapeBounds']).PHP_EOL;
    echo $indent . "Shapes: ".PHP_EOL;
    foreach ($fields['Shapes'] as $name => $value)
    {
      echo $indent . "\t{$name}: ";
      if (in_array($name, array('NumFillBits', 'NumLineBits'))) {
        echo $value. PHP_EOL;
      } elseif ($name == 'ShapeRecords') {
        echo "Length: ".strlen($value) .PHP_EOL;
      } else {
        echo PHP_EOL;
        foreach ($value as $n => $v) {
          echo $indent."\t\t{$n}: ";
          if (is_array($v)) {
            echo PHP_EOL;
            foreach ($v as $l) {
              echo $indent . "\t\t\t". json_encode($l).PHP_EOL; 
            }
          } else {
            echo $v . PHP_EOL;
          }
        }
      }
    }
  }
}
