<?php
// ActionScript変数の入れ替え
require_once 'include.php';

$map = '1,1,1,1,1,1,1,1,1,1,1;'
     . '5,5,5,1,1,1,1,1,1,1,1;'
     . '4,4,5,5,5,5,1,1,1,1,1;'
     . '2,4,4,1,4,4,6,1,1,1,1;'
     . '2,2,4,4,7,7,6,6,1,1,1;'
     . '4,4,4,7,7,7,4,6,1,1,1;'
     . '4,7,7,7,4,4,4,1,1,1,1;'
     . '4,6,7,7,4,4,1,1,1,1,1;'
     . '4,4,4,4,4,1,1,1,1,1,1;'
     . '4,4,4,4,4,4,1,1,1,1,1;'
     . '4,4,4,4,4,4,1,1,1,1,1;';

$objpos = '4:6-0;'
        . '2:9-1;'
        . '3:5-2;'
        . '9:7-4;'
        . '2:2-6;'
        . '3:9-9;'
        . '3:5-8;'
        . '5:1-9;'
        . '1:5-8;'
        . '2:1-9;';

$editor = new Media_SWF_Builder();
$editor->parse(file_get_contents('swf/100_base.swf'));
$firstAction = $editor->getFirstAction(); // rootで最初に出てくるActionを取り出す
$firstAction->replaceValue('map',    $map);
$firstAction->replaceValue('objpos', $objpos);
$firstAction->replaceValue('team',   1);

echo $editor->build();
