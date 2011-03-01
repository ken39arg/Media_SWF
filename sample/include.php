<?php
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__) . '/../lib');

require_once 'Media/SWF.class.php';
require_once 'Media/SWF/Builder.class.php';
require_once 'Media/SWF/Bitmap.class.php';
require_once 'Media/SWF/Builder.class.php';
require_once 'Media/SWF/Dumper.class.php';
require_once 'Media/SWF/Parser.class.php';
require_once 'Media/SWF/Tag.class.php';
require_once 'Media/SWF/Bitmap/GIF.class.php';
require_once 'Media/SWF/Bitmap/JPEG.class.php';
require_once 'Media/SWF/Bitmap/PNG.class.php';
require_once 'Media/SWF/Tag/DefineShape.class.php';
require_once 'Media/SWF/Tag/DefineSprite.class.php';
require_once 'Media/SWF/Tag/DoAction.class.php';
require_once 'Media/SWF/Tag/PlaceObject.class.php';
require_once 'Media/SWF/Tag/PlaceObject2.class.php';
require_once 'Media/SWF/Tag/RemoveObject.class.php';
