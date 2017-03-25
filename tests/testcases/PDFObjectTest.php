<?php
namespace motooka\PDFReaderTest;

use motooka\PDFReader\PDFObject;
use PHPUnit\Framework\TestCase;

class PDFObjectTest extends TestCase {
	public function testDictionaryAndStream() {
		$test1 = '<< /Length 5 0 R /Filter /FlateDecode >>
stream
x+TT(TÐH-JN-()MÌQ(Ê
* ¡¹NÎUÐ÷Ì5TpÉª Å
ÿ
endstream
';
		$obj = new PDFObject($test1);
		$this->assertEquals($obj->rawHeaderDictionary, '<< /Length 5 0 R /Filter /FlateDecode >>');
		$this->assertNotEmpty($obj->rawStream);
//		var_dump($obj);
	}
	
	public function testDictionaryOnly() {
		$test1 = '<< /Length 5 0 R /Filter /FlateDecode >>
';
		$obj = new PDFObject($test1);
		$this->assertEquals($obj->rawHeaderDictionary, $test1);
		$this->assertEmpty($obj->rawStream);
//		var_dump($obj);
	}
	public function testStreamOnly() {
		$test1 = 'stream
x+TT(TÐH-JN-()MÌQ(Ê
* ¡¹NÎUÐ÷Ì5TpÉª Å
ÿ
endstream
';
		$obj = new PDFObject($test1);
		$this->assertEmpty($obj->rawHeaderDictionary);
		$this->assertNotEmpty($obj->rawStream);
//		var_dump($obj);
	}
	
}

