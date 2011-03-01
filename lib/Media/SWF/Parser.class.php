<?php
require_once 'IO/Bit.php';
/**
 * Media_SWF_Parser.
 *
 * SwfFormatパーサ
 * 
 * @uses IO_Bit
 * @package   bowling
 * @version   $Id$
 * @copyright Copyright (C) 2010 KAYAC Inc.
 * @author    Kensaku Araga <araga-kensaku@kayac.com> 
 * @see http://hkpr.info/flash/swf/index.php?Flash%20SWF%20Spec
 * @see http://www.adobe.com/devnet/swf/pdf/swf_file_format_spec_v10.pdf
 * @via http://openpear.org/package/IO_SWF (@yoya)
 */
class Media_SWF_Parser extends IO_Bit
{
  public function getByteOffset()
  {
    return $this->_byte_offset;
  }

  public function getDataAll() {
    $this->byteAlign();
    $data = substr($this->_data, $this->_byte_offset);
    $data_len = strlen($data);
    $this->_byte_offset += $data_len;
    return $data;
  }

  public function getFIBits($width) 
  {
    // TODO 正しい実装(必要ならば)
    return $this->getUIBits($width);
  }
    
  public function getString()
  {
    $string = "";
    while (($byte = $this->getUI8()) !== 0x00) {
      $string .= chr($byte); 
    } 
    return $string;
  }

  public function getRGB()
  {
    return array(
      'Red'   => $this->getUI8(),
      'Green' => $this->getUI8(),
      'Blue'  => $this->getUI8(),
    );
  }

  public function getRGBA()
  {
    return array(
      'Red'   => $this->getUI8(),
      'Green' => $this->getUI8(),
      'Blue'  => $this->getUI8(),
      'Alpha' => $this->getUI8(),
    );
  }

  public function getARGB()
  {
    return array(
      'Alpha' => $this->getUI8(),
      'Red'   => $this->getUI8(),
      'Green' => $this->getUI8(),
      'Blue'  => $this->getUI8(),
    );
  }

  public function getRect()
  {
    $nbits = $this->getUIBits(5);
    $rect = array(
      'Nbits' => $nbits,
      'Xmin'  => $this->getSIBits($nbits),
      'Xmax'  => $this->getSIBits($nbits),
      'Ymin'  => $this->getSIBits($nbits),
      'Ymax'  => $this->getSIBits($nbits),
    );
    $this->byteAlign();
    return $rect;
  }

  public function getMatrix()
  {
    $matrix = array();
    $matrix['HasScale'] = $this->getUIBit();
    if ($matrix['HasScale'] === 1) {
      $matrix['NScaleBits'] = $this->getUIBits(5);
      $matrix['ScaleX'] = $this->getFIBits($matrix['NScaleBits']);
      $matrix['ScaleY'] = $this->getFIBits($matrix['NScaleBits']);
    }
    $matrix['HasRotate'] = $this->getUIBit();
    if ($matrix['HasRotate'] === 1) {
      $matrix['NRotateBits'] = $this->getUIBits(5);
      $matrix['RotateSkew0'] = $this->getFIBits($matrix['NRotateBits']);
      $matrix['RotateSkew1'] = $this->getFIBits($matrix['NRotateBits']);
    }
    $matrix['NTranslateBits'] = $this->getUIBits(5);
    $matrix['TranslateX'] = $this->getSIBits($matrix['NTranslateBits']);
    $matrix['TranslateY'] = $this->getSIBits($matrix['NTranslateBits']);
    $this->byteAlign();
    return $matrix;
  }

  public function getColorTransform()
  {
    $cxform = array();
    $cxform['HasAddTerms']  = $this->getUIBit();
    $cxform['HasMultTerms'] = $this->getUIBit();

    $cxform['Nbits'] = $this->getUIBits(4);

    if ($cxform['HasMultTerms'] === 1) {
      $cxform['RedMultTerm']   = $this->getSIBits($cxform['Nbits']);
      $cxform['GreenMultTerm'] = $this->getSIBits($cxform['Nbits']);
      $cxform['BlueMultTerm']  = $this->getSIBits($cxform['Nbits']);
    }
    if ($cxform['HasAddTerms'] === 1) {
      $cxform['RedAddTerm']   = $this->getSIBits($cxform['Nbits']);
      $cxform['GreenAddTerm'] = $this->getSIBits($cxform['Nbits']);
      $cxform['BlueAddTerm']  = $this->getSIBits($cxform['Nbits']);
    }
    $this->byteAlign();

    return $cxform;
  }

  public function getColorTransformWithAlpha()
  {
    $cxform = array();
    $cxform['HasAddTerms']  = $this->getUIBit();
    $cxform['HasMultTerms'] = $this->getUIBit();

    $cxform['Nbits'] = $this->getUIBits(4);

    if ($cxform['HasMultTerms'] === 1) {
      $cxform['RedMultTerm']   = $this->getSIBits($cxform['Nbits']);
      $cxform['GreenMultTerm'] = $this->getSIBits($cxform['Nbits']);
      $cxform['BlueMultTerm']  = $this->getSIBits($cxform['Nbits']);
      $cxform['AlphaMultTerm'] = $this->getSIBits($cxform['Nbits']);
    }
    if ($cxform['HasAddTerms'] === 1) {
      $cxform['RedAddTerm']   = $this->getSIBits($cxform['Nbits']);
      $cxform['GreenAddTerm'] = $this->getSIBits($cxform['Nbits']);
      $cxform['BlueAddTerm']  = $this->getSIBits($cxform['Nbits']);
      $cxform['AlphaAddTerm'] = $this->getSIBits($cxform['Nbits']);
    }
    $this->byteAlign();

    return $cxform;
  }

  public function getSWFHeader()
  {
    return array(
      'Signature'  => $this->getData(3),
      'Version'    => $this->getUI8(),
      'FileLength' => $this->getUI32LE(),
      'FrameSize'  => $this->getRect(),
      'FrameRate'  => $this->getUI16LE(),
      'FrameCount' => $this->getUI16LE(),
    );
  }

  public function getTag()
  {
    $tag = array();
    $tagCodeAndLength = $this->getUI16LE();
    $code = $tagCodeAndLength >> 6;
    $length = $tagCodeAndLength & 0x3f;
    if ($length == 0x3f) { // long format
      $length = $this->getUI32LE();
      $tag['LongFormat'] = true;
    }
    $tag['Code']  = $code;
    $tag['Length'] = $length;
    switch ($code) {
      case 6:  // DefineBits
      case 21: // DefineBitsJPEG2
      case 35: // DefineBitsJPEG3
      case 20: // DefineBitsLossless
      case 36: // DefineBitsLossless2
      case 46: // DefineMorphShape
      case 2:  // DefineShape (ShapeId)
      case 22: // DefineShape2 (ShapeId)
      case 32: // DefineShape3 (ShapeId)
      case 11: // DefineText
      case 33: // DefineText
      case 37: // DefineTextEdit
      case 39: // DefineSprite (SpriteId)
        $tag['CharacterId'] = $this->getUI16LE();
        $length -= 2;
        break;
    }
    $tag['Content'] = $this->getData($length);
    return $tag;
  }


  public function putFIBits($value, $width)
  {
    // TODO 正しい実装(必要ならば)
    $this->putUIBits($value, $width);
  }

  public function putRGB($rgb)
  {
    $this->putUI8($rgb['Red']);
    $this->putUI8($rgb['Green']);
    $this->putUI8($rgb['Blue']);
  }

  public function putRGBA($rgb)
  {
    $this->putUI8($rgb['Red']);
    $this->putUI8($rgb['Green']);
    $this->putUI8($rgb['Blue']);
    $this->putUI8($rgb['Alpha']);
  }

  public function putARGB($rgb)
  {
    $this->putUI8($rgb['Alpha']);
    $this->putUI8($rgb['Red']);
    $this->putUI8($rgb['Green']);
    $this->putUI8($rgb['Blue']);
  }

  public function putString($string)
  {
    $this->putData($string);
    $this->putData("\x00");
  }

  public function putRect($rect)
  {
    $this->putUIBits($rect['Nbits'], 5);
    $this->putSIBits($rect['Xmin'], $rect['Nbits']);
    $this->putSIBits($rect['Xmax'], $rect['Nbits']);
    $this->putSIBits($rect['Ymin'], $rect['Nbits']);
    $this->putSIBits($rect['Ymax'], $rect['Nbits']);
    $this->byteAlign();
  }

  public function putMatrix($matrix)
  {
    $this->putUIBit($matrix['HasScale']);
    if ($matrix['HasScale'] === 1) {
      $this->putUIBits($matrix['NScaleBits'], 5);
      $this->putFIBits($matrix['ScaleX'], $matrix['NScaleBits']);
      $this->putFIBits($matrix['ScaleY'], $matrix['NScaleBits']);
    }
    $this->putUIBit($matrix['HasRotate']);
    if ($matrix['HasRotate'] === 1) {
      $this->putUIBits($matrix['NRotateBits'], 5);
      $this->putFIBits($matrix['RotateSkew0'], $matrix['NRotateBits']);
      $this->putFIBits($matrix['RotateSkew1'], $matrix['NRotateBits']);
    }
    $this->putUIBits($matrix['NTranslateBits'], 5);
    $this->putSIBits($matrix['TranslateX'], $matrix['NTranslateBits']);
    $this->putSIBits($matrix['TranslateY'], $matrix['NTranslateBits']);
    $this->byteAlign();
  }

  public function putColorTransform($cxform)
  {
    $this->putUIBit($cxform['HasAddTerms']);
    $this->putUIBit($cxform['HasMultTerms']);
    $this->putUIBits($cxform['Nbits'], 4);

    if ($cxform['HasMultTerms'] === 1) {
      $this->putSIBits($cxform['RedMultTerm'],   $cxform['Nbits']);
      $this->putSIBits($cxform['GreenMultTerm'], $cxform['Nbits']);
      $this->putSIBits($cxform['BlueMultTerm'],  $cxform['Nbits']);
    }
    if ($cxform['HasAddTerms'] === 1) {
      $this->putSIBits($cxform['RedAddTerm'],   $cxform['Nbits']);
      $this->putSIBits($cxform['GreenAddTerm'], $cxform['Nbits']);
      $this->putSIBits($cxform['BlueAddTerm'],  $cxform['Nbits']);
    }
    $this->byteAlign();
  }

  public function putColorTransformWithAlpha($cxform)
  {
    $this->putUIBit($cxform['HasAddTerms']);
    $this->putUIBit($cxform['HasMultTerms']);
    $this->putUIBits($cxform['Nbits'], 4);

    if ($cxform['HasMultTerms'] === 1) {
      $this->putSIBits($cxform['RedMultTerm'],   $cxform['Nbits']);
      $this->putSIBits($cxform['GreenMultTerm'], $cxform['Nbits']);
      $this->putSIBits($cxform['BlueMultTerm'],  $cxform['Nbits']);
      $this->putSIBits($cxform['AlphaMultTerm'], $cxform['Nbits']);
    }
    if ($cxform['HasAddTerms'] === 1) {
      $this->putSIBits($cxform['RedAddTerm'],   $cxform['Nbits']);
      $this->putSIBits($cxform['GreenAddTerm'], $cxform['Nbits']);
      $this->putSIBits($cxform['BlueAddTerm'],  $cxform['Nbits']);
      $this->putSIBits($cxform['AlphaAddTerm'], $cxform['Nbits']);
    }
    $this->byteAlign();
  }

  public function putSWFHeader($header)
  {
    $this->putData($header['Signature']);
    $this->putUI8($header['Version']);
    $this->putUI32LE($header['FileLength']);

    /* SWF Movie Header */
    $this->putRect($header['FrameSize']);
    $this->putUI16LE($header['FrameRate']);
    $this->putUI16LE($header['FrameCount']);
  }

  public function putTag($tag)
  {
    $code = $tag['Code'];
    $length = $tag['Length'];
    if (empty($tag['LongFormat']) && ($length < 0x3f)) {
        $tagCodeAndLength = ($code << 6) | $length;
        $this->putUI16LE($tagCodeAndLength);
    } else {
        $tagCodeAndLength = ($code << 6) | 0x3f;
        $this->putUI16LE($tagCodeAndLength);
        $this->putUI32LE($length);
    }
    if (isset($tag['CharacterId'])) {
      $this->putUI16LE($tag['CharacterId']);
    }
    $this->putData($tag['Content']);
  }


  public function setFileLength($fileLength)
  {
    $this->setUI32LE($fileLength, 4);
  }
}
