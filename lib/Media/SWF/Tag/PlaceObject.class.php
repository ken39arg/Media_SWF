<?php
class Media_SWF_Tag_PlaceObject extends Media_SWF_Tag
{
  public function parse($content)
  {
    // Matrix & CXformの解析を回避してます
    //$content_reader = new Media_SWF_Parser();
    $content_reader = new IO_Bit();
    $content_reader->input($content);
    $this->_fields = array(
      'CharacterId'    => $content_reader->getUI16LE(),
    //  'Depth'          => $content_reader->getUI16LE(),
    //  'Matrix'         => $content_reader->getMatrix(),
      'Data' => $content_reader->getData(strlen($content) - 2),
    );
    //if ($length > $content_reader->getByteOffset()) {
    //  $this->_fields['ColorTransform'] = $content_reader->getColorTransform(),
    //}
  }

  public function build()
  {
    //$writer = new Media_SWF_Parser();
    $writer = new IO_Bit();
    $writer->putUI16LE($this->_fields['CharacterId']);
    $writer->putData($this->_fields['Data']);
    //$writer->putUI16LE($this->_fields['Depth']);
    //$writer->putMatrix($this->_fields['Matrix']);
    //if (isset($this->_fields['ColorTransform'])) {
    //  $writer->putColorTransform($this->_fields['ColorTransform']);
    //}
    return $writer->output();
  }

  public function dump($indent)
  {
    //Media_SWF_Dumper::dumpPlaceObject($this->_fields, $indent);
    Media_SWF_Dumper::dumpCharacterId($this->_fields, $indent);
  }
}
