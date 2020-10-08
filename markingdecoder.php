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

        if (strlen($source) < 31) $this->error = 'Код маркировки короче 31 символа';

        $this->gtin = substr($source, 2, 14);

        if (!is_numeric($this->gtin)) $this->error = 'GTIN не является числом';

        $this->serial = substr($source, 18, 13);

        $gtin_diff = strlen($this->gtin) - strlen((int)$this->gtin);
        $gtin_hex = dechex($this->gtin);

        for ($i=0; $i < $gtin_diff; $i++) {
            $gtin_hex = "0$gtin_hex";
        }

        $this->gtin16 = $this->hexFormat($gtin_hex);
        $this->serial16 = $this->hexFormat(bin2hex($this->serial));
        $this->productCode = "$this->type $this->gtin16 $this->serial16";
    }

    public function hexFormat($hex) {
        $hex = chunk_split($hex, 2, ' ');
        $hex = strtoupper($hex);
        $hex = trim($hex);
        return $hex;
    }

}
