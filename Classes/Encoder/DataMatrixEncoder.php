<?php
namespace Mawo\Barcode\Encoder;

include(__DIR__ . '/../../lib/barcode/barcode.php');
class DataMatrixEncoder extends \barcode_generator
{
    /*
        theOptions:
            fnc1char (char)
            gs1 (boolean)
    */
    public function getSvg($data, $theOptions) {
        $options = [];
        if (isset ($theOptions['fnc1char'])) {
            $options['fnc1char'] = $theOptions['fnc1char'];
        }
        if (isset ($theOptions['gs1'])) {
            $options['gs1'] = $theOptions['gs1'];
        }
        return $this->render_svg('gs1-dmtx', $data, $options);
    }

    protected function dmtx_encode($data, $rect, $fnc1, $fnc1char='') {
        list($data, $ec) = $this->dmtx_encode_data($data, $rect, $fnc1, $fnc1char);
        $data = $this->dmtx_encode_ec($data, $ec);
        list($h, $w, $mtx) = $this->dmtx_create_matrix($ec, $data);
        return array(
            'graphic_type' => 'matrix',
            'quiet_zone' => array(1, 1, 1, 1),
            'size' => array($w, $h),
            'blocks' => $mtx
        );
    }

    protected function dispatch_encode($symbology, $data, $options) {
        if (is_array($options) && isset($options['fnc1char'])) {
            $fnc1char = $options['fnc1char'];
        } else {
            $fnc1char = '';
        }
        switch (strtolower(preg_replace('/[^A-Za-z0-9]/', '', $symbology))) {
            case 'dmtx'       : return $this->dmtx_encode($data,false,false);
            case 'dmtxs'      : return $this->dmtx_encode($data,false,false);
            case 'dmtxr'      : return $this->dmtx_encode($data, true,false);
            case 'gs1dmtx'    : return $this->dmtx_encode($data,false, true, $fnc1char);
            case 'gs1dmtxs'   : return $this->dmtx_encode($data,false, true, $fnc1char);
            case 'gs1dmtxr'   : return $this->dmtx_encode($data, true, true, $fnc1char);
        }
        return NULL;
    }
    // overriding the internal encoding function with custom logic
    protected function dmtx_encode_data($text, $rect, $fnc1, $fnc1char='') {
        $datawords = $this->encode($text, ['fnc1char' => $fnc1char]);
        $length = strlen($text);
        /* Add padding. */
        $numCodeWords = count($datawords);
        $ec_params = $this->dmtx_detect_version($numCodeWords, $rect);
        if ($numCodeWords > $ec_params[0]) {
            $numCodeWords = $ec_params[0];
            $datawords = array_slice($datawords, 0, $numCodeWords);
            if ($datawords[$numCodeWords - 1] == 235) {
                $datawords[$numCodeWords - 1] = 129;
            }
        } else if ($numCodeWords < $ec_params[0]) {
            $numCodeWords++;
            $datawords[] = 129;
            while ($numCodeWords < $ec_params[0]) {
                $numCodeWords++;
                $r = (($numCodeWords * 149) % 253) + 1;
                $datawords[] = ($r + 129) % 254;
            }
        }
        /* Return. */
        return array($datawords, $ec_params);
    }

    /*
        options:
            fnc1char (char)
            gs1 (boolean)
    */
    private function encode($text, $options)
    {
        $fnc1char = isset ($options['fnc1char']) ? ord($options['fnc1char']) : 0;
        $gs1compilance = isset ($options['gs1']) ? (bool)$options['gs1'] : true;
        $usefnc1 = $fnc1char !== 0 || $gs1compilance;

        // remove starting fnc1char (for unified processing later on)
        if ($usefnc1 && substr($text, 0, 1) == chr($fnc1char)) {
            $text = substr($text, 1);
        }
        // explode to data blocks using fnc1char as delimiter
        $datablocks = explode(chr($fnc1char), $text);
        $codewords = [];
        $curMode = 'ASCII';
        foreach ($datablocks as $part)
        {

            // start each block with fnc1 if required
            if ($usefnc1) {
                $codewords[] = 232;
            }
            // switch encoding between parts
            $nextMode = $this->detectBestEncoding($part);
            if ($nextMode !== $curMode) {
                $curMode = $nextMode;
                if ($curMode == 'C40')
                    $codewords[] = 230;
                if ($curMode == 'ASCII')
                    $codewords[] = 254;
            }
            // add encoded part depending on best encoding
            if ($curMode == 'C40')
            {
                array_push($codewords, ...$this->getCodewordsC40($part, $curMode));
            }
            else
                array_push($codewords, ...$this->getCodewordsASCII($part, $curMode));
        }
        if ($codewords[count($codewords)-1] == 254) array_pop($codewords);
        return $codewords;
    }

    private function detectBestEncoding($text)
    {
        // if only numbers keep it default
        if( preg_match('/^[0-9]+$/', $text) )
        {
            return 'ASCII';
        }
        // only uppercase and numbers are optimal in C40:
        if( preg_match('/^[A-Z0-9]+$/', $text) )
        {
            return 'C40';
        }
        return 'ASCII';
    }

    /**
     * @param string
     */
    public function getCodewordsASCII($text, &$mode)
    {
        $words = [];
        $length = strlen($text);
        $offset = 0;
        while ($offset < $length)
        {
            $currentChar = ord(substr($text, $offset, 1));
            $offset++;
            // between 0 and 9:
            if ($currentChar >= 0x30 && $currentChar <= 0x39)
            {
                $nextChar = ord(substr($text, $offset, 1));
                // next char also between 0 and 9 allows to encode pairs:
                if ($nextChar >= 0x30 && $nextChar <= 0x39)
                {
                    $offset++;
                    $words[] = (($currentChar - 0x30) * 10) + ($nextChar - 0x30) + 130;
                } else {
                    $words[] = $currentChar + 1;
                }
            }
            // less than 128 (8 bit ascii range):
            else if ($currentChar < 0x80)
            {
                $words[] = $currentChar + 1;
            }
            // leaving 8 bit range:
            else
            {
                $words[] = 235;
                $words[] = ($currentChar - 0x80) + 1;
            }
        }
        return $words;
    }

    private function convertAsciiToC40($char)
    {
        // between 0…9
        if ($char >= 48 && $char <=57)
        {
            return $char - 44;
        }
        // between A…Z
        if ($char >= 65 && $char <=90)
        {
            return $char - 51;
        }
        return 0;
    }

    // ATTENTION!
    // SUPPORTS ONLY UPPERCASE AND NUMBERS!
    private function getCodewordsC40($text, &$mode)
    {
        $words = [];
        $length = strlen($text);
        $offset = 0;
        while ($offset < $length)
        {
            //var_dump($offset);
            $c1 = $this->convertAsciiToC40(ord(substr($text, $offset  , 1)));
            $c2 = $this->convertAsciiToC40(ord(substr($text, $offset+1, 1)));
            $c3 = $this->convertAsciiToC40(ord(substr($text, $offset+2, 1)));
            // if c2 and c3 both are zero values (= not set) we can save one
            // code word by switching back to ASCII early
            if ($c2 == 0 and $c3 == 0) {
                $words[] = 254; // switch to ascii
                $words[] = ord(substr($text, $offset  , 1)) + 1;
                $mode = 'ASCII';
            }
            // in all other cases we can continue as usual
            else {
                // calculation
                $v = $c1 * 1600 + $c2 * 40 + $c3 + 1;
                $cw1 = (int)floor( $v / 256 );
                $cw2 = $v % 256;
                // add the two found codewords
                $words[] = $cw1;
                $words[] = $cw2;
            }
            $offset += 3;
        }
        return $words;
    }
}
