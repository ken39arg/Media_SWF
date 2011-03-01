<?php
class Media_SWF_Tag_DefineSprite extends Media_SWF_Tag
{
  protected
    $_placedCharacters = array(),
    $_spriteNames = array();

  public function getSpriteNames()
  {
    return $this->_spriteNames;
  }

  public function getPlacedCharacters()
  {
    return $this->_placedCharacters;
  }

  public function replacePlacedCharacterIds($characterIdsMap)
  {
    foreach ($this->_fields['ControlTags'] as &$tag)
    {
      if (isset($tag['Object']) && $tag['Object']->hasField('CharacterId')) {
        foreach ($characterIdsMap as $oldCharacterId => $newCharacterId) {
          if ($tag['Object']->getField('CharacterId') === $oldCharacterId) {
            $tag['Object']->setField('CharacterId', $newCharacterId);
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
    //$spriteId = $reader->getUI16LE();
    $frameCount = $reader->getUI16LE();
    $controlTags = array();
    $placedCharacters = array();
    $spriteNames = array();
    while (true) {
      $tag = $reader->getTag();
      switch ($tag['Code']) {
        case 4:  // PlaceObject
          $tag['Object'] = new Media_SWF_Tag_PlaceObject($tag);
          break;
        case 5:  // RemoveObject
          $tag['Object'] = new Media_SWF_Tag_RemoveObject($tag);
          break;
        case 26: // PlaceObject2 (PlaceFlagHasCharacter)
          $tag['Object'] = new Media_SWF_Tag_PlaceObject2($tag);
          break;
        case 0:
          $controlTags[] = $tag;
          break 2;
        default:
          continue;
      }
      if (isset($tag['Object'])) {
        if ($tag['Object']->hasField('CharacterId')) {
          $placedCharacters[] = $tag['Object']->getField('CharacterId');
        }
        if ($tag['Object']->hasField('Name')) {
          $spriteNames[] = array(
            'CharacterId' => $tag['Object']->getField('CharacterId'),
            'Name'        => $tag['Object']->getField('Name'),
          );
        }
      }
      $controlTags[] = $tag;
    }
    $this->_fields = array(
      'CharacterId' => $this->characterId,
      'FrameCount'  => $frameCount,
      'ControlTags' => $controlTags,
    );
    $this->_placedCharacters = array_unique($placedCharacters);
    $this->_spriteNames      = $spriteNames;
  }

  public function build()
  {
    $writer = new Media_SWF_Parser();
    //$writer->putUI16LE($this->_fields['CharacterId']);
    $writer->putUI16LE($this->_fields['FrameCount']);
    foreach ($this->_fields['ControlTags'] as $tag)
    {
      if (isset($tag['Object'])) {
        $content = $tag['Object']->build();
        $tag['Length']  = strlen($content);
        $tag['Content'] = $content;
      }
      $writer->putTag($tag);
    }
    return $writer->output();
  }

  public function dump($indent)
  {
    Media_SWF_Dumper::dumpDefineSprite($this->_fields, $indent);
  }

}
