<?php
// Export用あまり使う必要は無いかもしれないとおもう
class Media_SWF_Tag_DefineBitsLossless extends Media_SWF_Tag
{
  public function parse($content)
  {
    $reader = new Media_SWF_Parser();
    $reader->input($content);
    $this->_fields = array(
      'CharacterId'  => $this->characterId, // ShapeId
      'BitmapFormat' => $reader->getUI8(),
      'BitmapWidth'  => $reader->getUI16LE(),
      'BitmapHeight' => $reader->getUI16LE(),
    );
    if ($this->_fields['BitmapFormat'] == 3) {
      $this->_fields['BitmapColorTableSize'] = $reader->getUI8();
    }
    $this->_fields['ZlibBitmapData'] = $reader->getDataAll();
  }

  public function build()
  {
    $writer = new Media_SWF_Parser();
    $writer->putUI8($this->_fields['BitmapFormat']);
    $writer->putUI16LE($this->_fields['BitmapWidth']);
    $writer->putUI16LE($this->_fields['BitmapHeight']);
    if ($this->_fields['BitmapFormat'] == 3) {
      $writer->putUI8($this->_fields['BitmapColorTableSize']);
    }
    $writer->putData($this->_fields['ZlibBitmapData']);
    return $writer->output();
  }

  public function dump($indent)
  {
    Media_SWF_Dumper::dumpDefineBitsLossless($this->_fields, $indent);
  }

}
