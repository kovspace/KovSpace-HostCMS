<?php

class KovSpace_MarkingDecoder
{
    public $type = '44 4D'; // Код типа маркировки
    public $error;
    public $gtin;
    public $gtin16;
    public $serial;
    public $serial16;
    public $productCode;


    public function __construct($source)
    {
        $this->gtin = substr($source, 2, 14);
        $this->serial = substr($source, 18, 13);
        $this->gtin16 = $this->hexFormat(0 . dechex($this->gtin));
        $this->serial16 = $this->hexFormat(bin2hex($this->serial));
        $this->productCode = "$this->type $this->gtin16 $this->serial16";

        if (strlen($source) < 31) {
            $this->error = 'Код маркировки короче 31 символа';
        }

        if (!is_numeric($this->gtin)) {
            $this->error = 'GTIN не является числом';
        }
    }

    public function hexFormat($hex) {
        $hex = chunk_split($hex, 2, ' ');
        $hex = strtoupper($hex);
        $hex = trim($hex);
        return $hex;
    }

}
