<?php
class Media_SWF_Tag_RemoveObject extends Media_SWF_Tag
{
  public function parse($content)
  {
    $content_reader = new IO_Bit();
    $content_reader->input($content);
    $this->_fields = array(
      'CharacterId' => $content_reader->getUI16LE(),
      'Depth'       => $content_reader->getUI16LE(),
    );
  }

  public function build()
  {
    $writer = new IO_Bit();
    $writer->putUI16LE($this->_fields['CharacterId']);
    $writer->putUI16LE($this->_fields['Depth']);
    return $writer->output();
  }

  public function dump($indent)
  {
    Media_SWF_Dumper::dumpCharacterId($this->_fields, $indent);
  }
}
