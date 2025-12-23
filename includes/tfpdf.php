<?php
/*******************************************************************************
* tFPDF (based on FPDF 1.86)                                                   *
*                                                                              *
* Version:  1.32                                                               *
* Date:     2021-09-01                                                         *
* Author:   Ian Back <ian@bpm1.com>                                            *
* License:  LGPL                                                               *
*                                                                              *
* Adds UTF-8 support to FPDF                                                   *
*******************************************************************************/

require_once('fpdf/fpdf.php');

class tFPDF extends FPDF
{
    protected $unifontSubset;
    protected $isUnicode;

    function __construct($orientation='P', $unit='mm', $size='A4')
    {
        parent::__construct($orientation, $unit, $size);
        $this->unifontSubset = false;
        $this->isUnicode = false;
    }

    function AddFont($family, $style='', $file='', $uni=false)
    {
        if($uni)
        {
            $this->isUnicode = true;
        }
        parent::AddFont($family, $style, $file);
    }

    function _escape($s)
    {
        if($this->isUnicode)
        {
            return $this->_UTF8toUTF16($s);
        }
        return parent::_escape($s);
    }

    function _UTF8toUTF16($s)
    {
        $res = '';
        $nb = strlen($s);
        $i = 0;
        while($i<$nb)
        {
            $c1 = ord($s[$i++]);
            if($c1>=224)
            {
                $c2 = ord($s[$i++]);
                $c3 = ord($s[$i++]);
                $res .= chr((($c1 & 0x0F)<<4) + (($c2 & 0x3C)>>2));
                $res .= chr((($c2 & 0x03)<<6) + ($c3 & 0x3F));
            }
            elseif($c1>=192)
            {
                $c2 = ord($s[$i++]);
                $res .= chr(($c1 & 0x1C)>>2);
                $res .= chr((($c1 & 0x03)<<6) + ($c2 & 0x3F));
            }
            else
            {
                $res .= "\0".chr($c1);
            }
        }
        return $res;
    }

    function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
    {
        if($this->isUnicode && is_string($txt))
            $txt = $this->_UTF8toUTF16($txt);
        parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    }

    function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false)
    {
        if($this->isUnicode && is_string($txt))
            $txt = $this->_UTF8toUTF16($txt);
        parent::MultiCell($w, $h, $txt, $border, $align, $fill);
    }

    function Write($h, $txt, $link='')
    {
        if($this->isUnicode && is_string($txt))
            $txt = $this->_UTF8toUTF16($txt);
        parent::Write($h, $txt, $link);
    }

    function Text($x, $y, $txt)
    {
        if($this->isUnicode && is_string($txt))
            $txt = $this->_UTF8toUTF16($txt);
        parent::Text($x, $y, $txt);
    }
}
