<?php
include __dir__ . '/../Classes/Encoder/DataMatrixEncoder.php';
include __dir__ . '/../Classes/Encoder/QRCodeEncoder.php';
include __dir__ . '/../Classes/CodeCreator.php';

$cc = new Mawo\Barcode\CodeCreator('ab');


$options=[
    'gs1' => true,
    'fnc1char' => '~',
    'rect' => false,
];
$bc = new Mawo\Barcode\Encoder\DataMatrixEncoder;
$data = '~1234567890123456789012~ABC123DEFGHIJKLMNOP456789QRSTUVWXY~1234567890';
echo $bc->getSvg($data, $options);


$bc = new Mawo\Barcode\Encoder\QRCodeEncoder;
$data = '~1234567890123456789012~ABC123DEFGHIJKLMNOP456789QRSTUVWXY~1234567890';
echo $bc->getSvg($data, $options);
