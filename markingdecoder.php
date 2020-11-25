<?php

class KovSpace_MarkingDecoder
{
    public $type = '17485'; // Универсальный код типа маркировки
    public $type16 = '44 4D'; // Универсальный код типа маркировки
    public $error;
    public $gtinPrefix;
    public $gtin;
    public $gtin16;
    public $serialPrefix;
    public $serial;
    public $serial16;
    public $productCode;
    public $iml;
    public $imlApi;


    public function __construct($source)
    {

        if (strlen($source) < 31) $this->error = 'Код маркировки короче 31 символа';

        $this->gtinPrefix = substr($source, 0, 2);
        $this->gtin = substr($source, 2, 14);

        if (!is_numeric($this->gtin)) $this->error = 'GTIN не является числом';

        $this->serialPrefix = substr($source, 16, 2);
        $this->serial = substr($source, 18, 13);

        $gtin_diff = strlen($this->gtin) - strlen((int)$this->gtin);
        $gtin_hex = dechex($this->gtin);

        for ($i=0; $i < $gtin_diff; $i++) {
            $gtin_hex = "0$gtin_hex";
        }

        $this->gtin16 = $this->hexFormat($gtin_hex);
        $this->serial16 = $this->hexFormat(bin2hex($this->serial));
        $this->productCode = "$this->type16 $this->gtin16 $this->serial16";

        $this->iml = "$this->type $this->gtin $this->serial";
        $this->imlApi = str_replace('"', '\"', $this->iml);
    }

    public function hexFormat($hex) {
        $hex = chunk_split($hex, 2, ' ');
        $hex = strtoupper($hex);
        $hex = trim($hex);
        return $hex;
    }

}
