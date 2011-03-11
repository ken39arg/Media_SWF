<?php 

/**
 * Media_SWF_Builder.  
 * 
 * IO_SWF_Editorから派生
 * 
 * @package   Media_SWF 
 * @version   $Id$
 * @copyright Copyright (C) 2010 KAYAC Inc.
 * @author    Kensaku Araga <araga-kensaku@kayac.com> 
 * @see IO_Bit http://openpear.org/package/IO_Bit
 * @see IO_SWF http://openpear.org/package/IO_SWF
 * @todo Mingも評価
 */
class Media_SWF_Builder extends Media_SWF
{
  protected
    $_placedCharacters = array(),
    $_spriteNames = array(),
    $_maxCharacterId = 0;

  public function build() 
  {
    foreach ($this->_tags as &$tag) {
      if (isset($tag['Object'])) {
        $content = $tag['Object']->build();
        $tag['Length']  = strlen($content);
        if (isset($tag['CharacterId'])) {
          $tag['Length'] += 2; // 16bit
        }
        $tag['Content'] = $content;
      }
    }
    return parent::build();
  }

  public function free()
  {
    $this->_tags = array();
    $this->_headers = array();
    $this->_placedCharacters = array();
    $this->_spriteNames = array();
  }

  public function getUniqueCharacterId()
  {
    if ($this->_maxCharacterId === 0) {
      foreach ($this->_tags as $tag) {
        if (isset($tag['CharacterId']) && $this->_maxCharacterId < $tag['CharacterId']) {
          $this->_maxCharacterId = $tag['CharacterId'];
        }
      }
    }
    return ++$this->_maxCharacterId;
  }

  public function getSpriteNames()
  {
    return $this->_spriteNames;
  }

  public function getPlacedCharacters()
  {
    return $this->_placedCharacters;
  }

  public function getPlacedCharactersRecursiveByCharacterId($characterId)
  {
    $placedCharacterIds = array();
    if (isset($this->_placedCharacters[$characterId])) {
      foreach ($this->_placedCharacters[$characterId] as $p_cid) {
        if (in_array($p_cid, $placedCharacterIds)) {
          continue;
        }
        $placedCharacterIds[] = $p_cid;
        $placedCharacterIds = array_merge($placedCharacterIds, $this->getPlacedCharactersRecursiveByCharacterId($p_cid));
      }
    }
    return $placedCharacterIds;
  }

  public function getParentCharacterIdsByCharacterId($characterId)
  {
    $result = array();
    foreach ($this->getPlacedCharacters() as $parentId => $placedCharacters)
    {
      if (in_array($characterId, $placedCharacters)) {
        $result[] = $parentId;
      }
    }
    return $result;
  }

  public function getCharacterIdBySpriteName($spriteName)
  {
    $spriteNames = $this->getSpriteNames();
    $characterId = 0; // rootのCharacterId
    foreach (explode('/', trim($spriteName, '/')) as $_spriteName) {
      foreach ($spriteNames[$characterId] as $v) {
        if ($v['Name'] === $_spriteName) {
          $characterId = $v['CharacterId'];
          continue 2;
        }
      }
      return null;
    }
    return $characterId;
  }

  public function getBitmapIdBySpriteName($spriteName)
  {
    $spriteId = $this->getCharacterIdBySpriteName($spriteName);
    foreach ($this->getPlacedCharactersRecursiveByCharacterId($spriteId) as $characterId) {
      $tag = $this->getTagByCharacterId($characterId);
      if (in_array($tag['Code'], array(6, 21, 35, 20, 36, 90))) {
        return $tag['CharacterId'];
      }
    }
    return null;
  }

  public function changeSpriteBySpriteName($swfObj, $spriteName, $spriteName2 = null)
  {
    if ($spriteName2 == null) $spriteName2 = $spriteName;
    $oldSpriteId = $this->getCharacterIdBySpriteName($spriteName);
    $newSpriteId = $swfObj->getCharacterIdBySpriteName($spriteName2);
   
    // 不要タグの削除
    $removeCharacterIds = $this->getPlacedCharactersRecursiveByCharacterId($oldSpriteId);
    foreach ($removeCharacterIds as $characterId) {
      $parentId = $this->getParentCharacterIdsByCharacterId($characterId);
      if (count($parentId) > 1) {
        continue;
      } elseif (count($parentId) == 1 && !in_array($parentId[0], array_merge(array($oldSpriteId), $removeCharacterIds))) {
        continue;
      }
      $this->removeCharacterByCharacterId($characterId);
    }

    // 新しいオブジェクト
    $placedCharacterIds = $swfObj->getPlacedCharactersRecursiveByCharacterId($newSpriteId);
    sort($placedCharacterIds);

    $newCharacterIds = array(); 
    $count = count($placedCharacterIds);
    for ($i = 0; $i < $count; $i++) {
      $newCharacterIds[] = $this->getUniqueCharacterId();
    }
    
    $characterIdsMap = array_combine($placedCharacterIds, $newCharacterIds);

    $addTags = array();
    foreach ($characterIdsMap as $characterId => $newCharacterId)
    {
      $tag = $swfObj->getTagByCharacterId($characterId);

      switch ($tag['Code']) {
      case 39:
        if (!isset($tag['Object'])) {
          $tag['Object'] = new Media_SWF_Tag_DefineSprite($tag);
        }
        $tag['Object']->replacePlacedCharacterIds($characterIdsMap);
        break;
      case 2:  // DefineShape (ShapeId)
      case 22: // DefineShape2 (ShapeId)
      case 32: // DefineShape3 (ShapeId)
        if (!isset($tag['Object'])) {
          $tag['Object'] = new Media_SWF_Tag_DefineShape($tag);
        }
        $tag['Object']->replacePlacedCharacterIds($characterIdsMap);
        break;
      }

      $tag['CharacterId'] = $newCharacterId;
      $addTags[] = $tag;
    }

    $this->_tags = array_merge($this->_tags);
    foreach ($this->_tags as $i => $tag) {
      if (isset($tag['CharacterId']) && $tag['CharacterId'] === $oldSpriteId) {
        array_splice($this->_tags, $i, 0, $addTags);
        break;
      }
    }
    $newTag = $swfObj->getTagByCharacterId($newSpriteId);
    if (!isset($newTag['Object'])) {
      $newTag['Object'] = new Media_SWF_Tag_DefineSprite($newTag);
    }
    $newTag['Object']->replacePlacedCharacterIds($characterIdsMap);

    $this->_tags = array_merge($this->_tags);
    foreach ($this->_tags as &$tag) {
      if (isset($tag['CharacterId']) && $tag['CharacterId'] === $oldSpriteId) {
        $tag['Code']    = $newTag['Code'];
        $tag['Content'] = $newTag['Content'];
        $tag['Object']  = $newTag['Object'];
        break;
      }
    }
  }

  public function removeCharacterByCharacterId($characterId)
  {
    foreach ($this->_tags as $i => $tag) {
      if (isset($tag['CharacterId']) && $tag['CharacterId'] === $characterId) {
        unset($this->_tags[$i]);
        return $tag;
      }
    }
    return false;
  }

  /**
   * Characterデータを解析する手始めを行う.  
   * 
   * それなりに負荷のかかる処理なので不要の場合はお勧めしません. 
   * また、CharacterIdのみが必要の場合はsetCharacterId()を利用してください
   * 
   * @access public
   * @return void
   */
  public function loadCharacterMap() 
  {
    $placedCharacters = array(0 => array());
    $spriteNames = array(0 => array());
    foreach ($this->_tags as &$tag) {
      switch ($tag['Code']) {
        case 2:  // DefineShape (ShapeId)
        case 22: // DefineShape2 (ShapeId)
        case 32: // DefineShape3 (ShapeId)
          $tagObject = new Media_SWF_Tag_DefineShape($tag);
          //$tag['CharacterId'] = $tagObject->getField('CharacterId');
          $placedCharacters[$tag['CharacterId']] = $tagObject->getPlacedCharacters();
          break;
        case 39: // DefineSprite (SpriteId)
          $tagObject = new Media_SWF_Tag_DefineSprite($tag);
          $placedCharacters[$tag['CharacterId']] = $tagObject->getPlacedCharacters();
          $spriteNames[$tag['CharacterId']] = $tagObject->getSpriteNames();
          break;
        case 4:  // PlaceObject
          $tagObject = new Media_SWF_Tag_PlaceObject($tag);
          if ($tagObject->hasField('CharacterId')) {
            $placedCharacters[0][] = $tagObject->getField('CharacterId');
          }
          break;
        case 5:  // RemoveObject
          $tagObject = new Media_SWF_Tag_RemoveObject($tag);
          break;
        case 26: // PlaceObject2 (PlaceFlagHasCharacter)
          $tagObject = new Media_SWF_Tag_PlaceObject2($tag);
          if ($tagObject->hasField('CharacterId')) {
            $placedCharacters[0][] = $tagObject->getField('CharacterId');
          }
          if ($tagObject->hasField('Name')) {
            $spriteNames[0][] = array(
              'CharacterId' => $tagObject->getField('CharacterId'),
              'Name'        => $tagObject->getField('Name'),
            );
          }
          break;
      }
    }
    $this->_placedCharacters = $placedCharacters;
    $this->_spriteNames      = $spriteNames;
  }

  public function dump()
  {
    Media_SWF_Dumper::dumpAll($this->_headers, $this->_tags);
  }

}
