<?php
namespace motooka\PDFReaderTest;

use motooka\PDFReader\PDFDocument;
use PHPUnit\Framework\TestCase;

class PDFDocumentTest extends TestCase {
	public function testQuickBrownFox() {
		$filepath = 'tests' . DIRECTORY_SEPARATOR . 'testdata' . DIRECTORY_SEPARATOR . '01_quickbrownfox.pdf';
		$doc = new PDFDocument($filepath);
		$this->assertTrue(!empty($doc));
	}
}

