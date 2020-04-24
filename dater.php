<?php
class KovSpace_Dater
{
    public static function plural($n, $form1, $form2, $form3)
    {
        $n = abs($n) % 100;
        $n1 = $n % 10;

        if ($n > 10 && $n < 20) {
            return $form3;
        }

        if ($n1 > 1 && $n1 < 5) {
            return $form2;
        }

        if ($n1 == 1) {
            return $form1;
        }

        return $form3;
    }
}
