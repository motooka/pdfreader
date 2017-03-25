<?php
namespace motooka\PDFReaderTest;

use motooka\PDFReader\PDFObject;
use PHPUnit\Framework\TestCase;

// TODO load in a correct way
require_once '../src/autoloader.php';

// TODO use PHPUnit
class PDFObjectTest extends TestCase {
	public function testConstructor() {
		$test1 = '<< /Length 5 0 R /Filter /FlateDecode >>
stream
x+TT(TÐH-JN-()MÌQ(Ê
* ¡¹NÎUÐ÷Ì5TpÉª Å
ÿ
endstream
';
		$obj = new PDFObject($test1);
		$this->assertEquals($obj->rawHeaderDictionary, '<< /Length 5 0 R /Filter /FlateDecode >>');
//		var_dump($obj);
	}
}

// TODO use PHPUnit
//$test = new PDFObjectTest();
//$test->testConstructor();
