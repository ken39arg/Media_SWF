<?php

class Media_SWF_Tag
{
  protected
    $content,
    $code,
    $length,
    $characterId,
    $_fields;

  public function __construct($tag = array())
  {
    if (isset($tag['Code'])) {
      $this->code = $tag['Code'];
    }
    if (isset($tag['Length'])) {
      $this->length = $tag['Length'];
    }
    if (isset($tag['CharacterId'])) {
      $this->characterId = $tag['CharacterId'];
    }
    if (isset($tag['Content'])) {
      $this->parse($tag['Content']);
    }
  }

  public function getFields()
  {
    return $this->_fields;
  }

  public function hasField($field)
  {
    return empty($this->_fields[$field]) ? false : true;
  }

  public function getField($field)
  {
    return isset($this->_fields[$field]) ? $this->_fields[$field] : null;
  }

  public function setField($field, $value)
  {
    $this->_fields[$field] = $value;
  }

  public function parse($content)
  {
    $this->content = $content;
  }

  public function build()
  {
    return $this->content;
  }

  public function dump($indent)
  {
    return array();
  }
}
