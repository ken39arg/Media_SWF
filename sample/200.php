<?php
// MC と 画像の入れ替え
require_once 'include.php';

$editor = new Media_SWF_Builder();
$editor->parse(file_get_contents('swf/200_base.swf'));
$editor->loadCharacterMap();

// item1 (MCの入れ替え)
$item_editor1 = new Media_SWF_Builder();
$item_editor1->parse(file_get_contents('swf/200_item_1.swf'));
$item_editor1->loadCharacterMap();

$editor->changeSpriteBySpriteName($item_editor1, '/avatar/b', '/b');
$editor->changeSpriteBySpriteName($item_editor1, '/avatar/s', '/s');
$editor->changeSpriteBySpriteName($item_editor1, '/avatar/l', '/l');

// item2 (MCの入れ替え)
$item_editor2 = new Media_SWF_Builder();
$item_editor2->parse(file_get_contents('swf/200_item_2.swf'));
$item_editor2->loadCharacterMap();
$editor->changeSpriteBySpriteName($item_editor2, '/rack');

// item3 (MCの入れ替え)
$item_editor3 = new Media_SWF_Builder();
$item_editor3->parse(file_get_contents('swf/200_item_3.swf'));
$item_editor3->loadCharacterMap();
$editor->changeSpriteBySpriteName($item_editor3, '/wall');

// item4 (透過PNGの入れ替え)
$bitmap_id = $editor->getBitmapIdBySpriteName('p2'); // bitmap id の取り出し
$image = new Media_SWF_Bitmap_PNG('swf/200_item_4.png'); // PNGからswftagを生成
$image->build();
$editor->setTagByCharacterId($bitmap_id, $image->getTag($bitmap_id));

echo $editor->build();
