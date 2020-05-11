<?php
namespace Mawo\Barcode\Encoder;

include_once(__DIR__ . '/../../lib/barcode/barcode.php');
class QRCodeEncoder extends \barcode_generator
{
    public function getSvg($data, $theOptions) {
        $options = [];
        $symbology = 'qr';
        return $this->render_svg($symbology, $data, $options);
    }
}
