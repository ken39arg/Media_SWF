<?php

require_once 'IO/Bit.php';

class Media_SWF_Tag_DoAction extends Media_SWF_Tag
{
  private 
    $_tags,
    $_values;

  public function hasField($field)
  {
    return $field === 'Actions' ? true : false;
  }

  public function getField($field)
  {
    return $field === 'Actions' ? $this->_tags : null;
  }


  public function parse($content)
  {
    $reader = new IO_Bit();
    $reader->input($content);
    $tags    = array();
    $values  = array();
    $valname = null;
    while (true) {
      $action_code = $reader->getUI8();
      $length = ($action_code & 0x80) ? $reader->getUI16LE() : 0;
      $contents = ($length > 0) ? $reader->getData($length) : null;

      switch ($action_code) 
      {
        case 0x96: //PushData
          if ($valname != null) {
            if (!isset($values[$valname])) {
              $values[$valname] = array('Index' => count($tags), 'Content' => mb_convert_encoding($contents, 'utf-8', 'sjis-win'));
            }
            $valname = null;
          } else {
            $valname = trim($contents);
          }
          break;
        //case 0x1D: // SetVariable
        //case 0x17: // Pop
        default:
          $valname = null;
          break;
      }
      $tags[] = array('ActionCode' => $action_code, 'Length' => $length, 'Content' => $contents);
    
      if ($action_code == 0) { // END Tag
        break;
      }
    }
    $reader = null;
    $this->_tags   = $tags;
    $this->_values = $values;
  }

  public function build()
  {
    $writer = new IO_Bit();
    foreach ($this->_tags as $index => $d)
    {
      $writer->putUI8($d['ActionCode']);
      if ($d['Length'] == 0) continue;

      $writer->putUI16LE($d['Length']);
      $writer->putData($d['Content']);
    }
    return $writer->output();
  }

  public function replaceValue($name, $value)
  {
    // replace variable
    if (isset($this->_values[$name]))
    {
      $this->_values[$name]['Content'] = $value;
      $index = $this->_values[$name]['Index'];

      $data = "\x00" . mb_convert_encoding($value, 'sjis-win', 'utf-8') . "\x00";
      $len = strlen($data);

      $this->_tags[$index]['Length'] = $len;
      $this->_tags[$index]['Content'] = $data;
      return true;
    }

    // push variable
    if (true) {
        $tags = $this->_tags;
        $end_tag = array_pop($tags);
        // push stack
        $push_stack_data = array($name, $value,);
        foreach ($push_stack_data as $push_stack_element) {
            $data = "\x00" . $push_stack_element . "\x00";
            $len = strlen($data);
            $push_tag = array();
            $push_tag['ActionCode'] = 0x96;
            $push_tag['Length'] = $len;
            $push_tag['Content'] = $data;
            $tags[] = $push_tag;
        }
        // set valiable
        $set_tag = array();
        $set_tag['ActionCode'] = 0x1D;
        $set_tag['Length'] = 0;
        $set_tag['Content'] = null;
        $tags[] = $set_tag;
        $tags[] = $end_tag;
        $this->_tags = $tags;
        return true;
    }
    return false;
  }

  public function dump($indent)
  {
    Media_SWF_Dumper::dumpDoAction($this->_tags, $indent);
  }

}
