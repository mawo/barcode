<?php
namespace Mawo\Barcode\Encoder;
class QRCodeEncoder extends \barcode_generator
{
    public function getSvg($data, $theOptions = []) {
        $options = [];
        $symbology = 'qr';
        return $this->render_svg($symbology, $data, $options);
    }
}
