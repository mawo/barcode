# barcode
Generate GS1 compatible Data Matrix with multiple code blocks divided by fnc1.

The only supported function is "getSvg()"

If you don't need multiple data blocks and want more features (e.g. other output formats) have a look at
https://github.com/kreativekorp/barcode 

## Example:
```
$bc = new Mawo\Barcode\Encoder\DataMatrixEncoder;
$data = '|1234567890123456789012|ABC123DEFGHIJKLMNOP456789QRSTUVWXY|1234567890';
echo $bc->getSvg($data, $options);
```

