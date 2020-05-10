<?php
include __dir__ . '/../Classes/Encoder/DataMatrixEncoder.php';

$options=[
    'fnc1char' => '|',
];
$bc = new Mawo\Barcode\Encoder\DataMatrixEncoder;
$data = '|1234567890123456789012|ABC123DEFGHIJKLMNOP456789QRSTUVWXY|1234567890';
echo $bc->getSvg($data, $options);
