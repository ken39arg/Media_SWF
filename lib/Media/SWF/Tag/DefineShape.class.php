<?php
class Media_SWF_Tag_DefineShape extends Media_SWF_Tag
{
  protected
    $_placedCharacters = array();

  public function getPlacedCharacters()
  {
    return $this->_placedCharacters;
  }

  public function replacePlacedCharacterIds($characterIdsMap)
  {
    foreach ($this->_fields['Shapes']['FillStyles']['FillStyles'] as &$tag)
    {
      if (isset($tag['BitmapId'])) {
        foreach ($characterIdsMap as $oldCharacterId => $newCharacterId) {
          if ($tag['BitmapId'] == $oldCharacterId) {
            $tag['BitmapId'] = $newCharacterId;
            break;
          }
        }
      }
    }
  }

  public function parse($content)
  {
    $reader = new Media_SWF_Parser();
    $reader->input($content);
    $this->_fields = array(
      'CharacterId' => $this->characterId, // ShapeId
      'ShapeBounds' => $reader->getRect(),
      'Shapes'      => $this->getShapeWithStyle($reader),
    );
  }

  public function build()
  {
    $writer = new Media_SWF_Parser();
    //$writer->putUI16LE($this->_fields['CharacterId']);
    $writer->putRect($this->_fields['ShapeBounds']);
    $this->putShapeWithStyle($this->_fields['Shapes'], $writer);
    return $writer->output();
  }

  protected function getShapeWithStyle($reader)
  {
    return array(
      'FillStyles'   => $this->getFillStyleArray($reader),
      'LineStyles'   => $this->getLineStyleArray($reader),
      'NumFillBits'  => $reader->getUIBits(4),
      'NumLineBits'  => $reader->getUIBits(4),
      'ShapeRecords' => $reader->getDataAll(), // パースはしない
    );
  }

  protected function getFillStyleArray($reader)
  {
    $fillStyleArray = array();
    $fillStyleArray['FillStyleCount'] = $fillStyleCount = $reader->getUI8();
    if ($fillStyleCount === 0xFF) {
      $fillStyleArray['FillStyleCountExtended'] = $fillStyleCount = $reader->getUI16LE();
    }
    $fillStyleArray['FillStyles'] = array();
    for ($i = 0; $i < $fillStyleCount; ++$i)
    {
      $fillStyleArray['FillStyles'][] = $this->getFillStyle($reader);
    }
    return $fillStyleArray;
  }

  protected function getFillStyle($reader)
  {
    $fillStyle = array();
    $fillStyle['FillStyleType'] = $reader->getUI8();
    switch ($fillStyle['FillStyleType']) {
      case 0x00: // solid
        $fillStyle['Color'] = ($this->code === 32) ? $reader->getRGBA() : $reader->getRGB();
        break;
      case 0x10: // linear gradient
      case 0x12: // radial gradient fill
      //case 0x13: // focal radial gradient  //swf 8
        $fillStyle['GradientMatrix'] = $reader->getMatrix();
        $fillStyle['Gradient'] = $this->getGradient($reader);
        break;
      case 0x40: // repeating bitmap
      case 0x41: // clipped bitmap 
      case 0x42: // non-smoothed repeating bitmap
      case 0x43: // non-smoothed clipped bitmap
        $fillStyle['BitmapId'] = $reader->getUI16LE();
        $fillStyle['BitmapMatrix'] = $reader->getMatrix();
        $this->_placedCharacters[] = $fillStyle['BitmapId'];
        break;
    }
    return $fillStyle;
  }

  protected function getLineStyleArray($reader)
  {
    $lineStyleArray = array();
    $lineStyleArray['LineStyleCount'] = $lineStyleCount = $reader->getUI8();
    if ($lineStyleCount === 0xFF) {
      $lineStyleArray['LineStyleCountExtended'] = $lineStyleCount = $reader->getUI16LE();
    }
    $lineStyleArray['LineStyles'] = array();
    for ($i = 0; $i < $lineStyleCount; ++$i)
    {
      // LineStyle2は対応しない
      $lineStyleArray['LineStyles'][] = array(
        'Width' => $reader->getUI16LE(),
        'Color' => ($this->code === 32 ? $reader->getRGBA() : $reader->getRGB()),
      );
    }
    return $lineStyleArray;
  }

  protected function getGradient($reader)
  {
    $gradient = array();
    $gradient['SpreadMode'] = $reader->getUIBits(2);
    $gradient['InterpolationMode'] = $reader->getUIBits(2);
    $gradient['NumGradients'] = $reader->getUIBits(4);
    $gradient['GradientRecords'] = array();
    for ($i = 0; $i < $gradient['NumGradients']; ++$i) {
      $gradient['GradientRecords'][] = array(
        'Ratio' => $reader->getUI8(),
        'Color' => ($this->code === 32 ? $reader->getRGBA() : $reader->getRGB()),
      );
    }
    return $gradient;
  }

  protected function putShapeWithStyle($shapeWithStyle, $writer)
  {
    $this->putFillStyleArray($shapeWithStyle['FillStyles'], $writer);
    $this->putLineStyleArray($shapeWithStyle['LineStyles'], $writer);
    $writer->putUIBits($shapeWithStyle['NumFillBits'], 4);
    $writer->putUIBits($shapeWithStyle['NumLineBits'], 4);
    $writer->putData($shapeWithStyle['ShapeRecords']);
  }

  protected function putFillStyleArray($fillStyleArray, $writer)
  {
    $writer->putUI8($fillStyleArray['FillStyleCount']);
    if (isset($fillStyleArray['FillStyleCountExtended'])) {
      $writer->putUI16LE($fillStyleArray['FillStyleCountExtended']);
    }
    foreach ($fillStyleArray['FillStyles'] as $fillStyle)
    {
      $this->putFillStyle($fillStyle, $writer);
    }
  }

  protected function putFillStyle($fillStyle, $writer)
  {
    $writer->putUI8($fillStyle['FillStyleType']);
    switch ($fillStyle['FillStyleType']) {
      case 0x00: // solid
        if ($this->code === 32) {
          $writer->putRGBA($fillStyle['Color']);
        } else {
          $writer->putRGB($fillStyle['Color']);
        }
        break;
      case 0x10: // linear gradient
      case 0x12: // radial gradient fill
      //case 0x13: // focal radial gradient  //swf 8
        $writer->putMatrix($fillStyle['GradientMatrix']);
        $this->putGradient($fillStyle['Gradient'], $writer);
        break;
      case 0x40: // repeating bitmap
      case 0x41: // clipped bitmap 
      case 0x42: // non-smoothed repeating bitmap
      case 0x43: // non-smoothed clipped bitmap
        $writer->putUI16LE($fillStyle['BitmapId']);
        $writer->putMatrix($fillStyle['BitmapMatrix']);
        break;
    }
  }

  protected function putLineStyleArray($lineStyleArray, $writer)
  {
    $writer->putUI8($lineStyleArray['LineStyleCount']);
    if (isset($lineStyleArray['LineStyleCountExtended'])) {
      $writer->putUI16LE($lineStyleArray['LineStyleCountExtended']);
    }
    foreach ($lineStyleArray['LineStyles'] as $lineStyle)
    {
      $writer->putUI16LE($lineStyle['Width']);
      if ($this->code === 32) {
        $writer->putRGBA($lineStyle['Color']);
      } else {
        $writer->putRGB($lineStyle['Color']);
      }
    }
  }

  protected function putGradient($gradient, $writer)
  {
    $writer->putUIBits($gradient['SpreadMode'], 2);
    $writer->putUIBits($gradient['InterpolationMode'], 2);
    $writer->putUIBits($gradient['NumGradients'], 4);
    foreach ($gradient['GradientRecords'] as $gradientRecord) {
      $writer->putUI8($gradientRecord['Ratio']);
      if ($this->code === 32) {
        $writer->putRGBA($gradientRecord['Color']);
      } else {
        $writer->putRGB($gradientRecord['Color']);
      }
    }
  }

  public function dump($indent)
  {
    Media_SWF_Dumper::dumpDefineShape($this->_fields, $indent);
  }

}
