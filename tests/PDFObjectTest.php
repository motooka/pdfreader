<?php
namespace PDFReaderTest;

use PDFReader\PDFObject;

// TODO load in a correct way
require_once '../src/PDFObject.php';

// TODO use PHPUnit
class PDFObjectTest {
	public function testConstructor() {
		$test1 = '<< /Length 5 0 R /Filter /FlateDecode >>
stream
x+TT(TÐH-JN-()MÌQ(Ê
* ¡¹NÎUÐ÷Ì5TpÉª Å
ÿ
endstream
';
		$obj = new PDFObject($test1);
		var_dump($obj);
	}
}

// TODO use PHPUnit
$test = new PDFObjectTest();
$test->testConstructor();
