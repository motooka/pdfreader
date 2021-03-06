<?php
namespace motooka\PDFReaderTest;

use motooka\PDFReader\PDFObject;
use PHPUnit\Framework\TestCase;

class PDFObjectTest extends TestCase {
	public function testObject_DictionaryAndStream() {
		$test1 = '<< /Length 5 0 R /Filter /FlateDecode >>
stream
x+TT(TÐH-JN-()MÌQ(Ê
* ¡¹NÎUÐ÷Ì5TpÉª Å
ÿ
endstream
';
		$obj = new PDFObject($test1);
		$this->assertEquals($obj->rawHeaderDictionary, '<< /Length 5 0 R /Filter /FlateDecode >>
');
		$this->assertNotEmpty($obj->rawStream);
//		var_dump($obj);
	}
	
	public function testObject_DictionaryOnly() {
		$test1 = '<< /Length 5 0 R /Filter /FlateDecode >>
';
		$obj = new PDFObject($test1);
		$this->assertEquals($obj->rawHeaderDictionary, $test1);
		$this->assertEmpty($obj->rawStream);
//		var_dump($obj);
	}
	
	public function testObject_StreamOnly() {
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
	
	public function testHeader_DictionaryOnly() {
		$test1 = '<< /Length 5 0 R /Filter /FlateDecode >>
';
		$headerDictionary = PDFObject::parseHeaderDictionary($test1);
		$this->assertTrue(is_array($headerDictionary), 'An array is expected as a return value.');
		$this->assertEquals(count($headerDictionary), 2);
		$this->assertArrayHasKey('/Length', $headerDictionary);
		$this->assertArrayHasKey('/Filter', $headerDictionary);
//		var_dump($headerDictionary);
	}
	
	public function testHeader_Nest() {
		$test1 = '<< /ProcSet [ /PDF /ImageB /ImageC /ImageI ] /XObject << /Im1 7 0 R >> >>
';
		$headerDictionary = PDFObject::parseHeaderDictionary($test1);
		$this->assertTrue(is_array($headerDictionary), 'An array is expected as a return value.');
		$this->assertEquals(count($headerDictionary), 2);
		$this->assertArrayHasKey('/ProcSet', $headerDictionary);
		$this->assertArrayHasKey('/XObject', $headerDictionary);
	}
}

