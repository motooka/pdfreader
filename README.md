# PDFReader

Reads PDF files and converts into PHP objects.

**still under development**

## Requirements

- PHP 5.6+

## License

LGPL v3

## How to Use

- require in your source `require_once 'path/to/src/autoloader.php'`
- parse the file `$doc = new PDFDocument($filepath)`

## How to Run Tests

- prepare PHPUnit
- cd path/to/pdfreader
- run the command `phpunit` : it reads phpunit.xml in the directory
