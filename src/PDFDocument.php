<?php
namespace motooka\PDFReader;

use motooka\PDFReader\Exception\PDFReaderException;
use motooka\PDFReader\Exception\PDFReaderSyntaxException;

class PDFDocument {
	public $rawHeader = null;
	public $version = null;
	public $objects = null;
	public $rawXref = null;
	public $rawTrailerDictionary = null;
	public $trailerDictionary = null;
	public $startXref = null;
	
	const DOCUMENT_TOKEN_HEADER = '/^%PDF[^\\r\\n]+(\\r\\n|\\r|\\n)[^\\r\\n]+(\\r\\n|\\r|\\n)/';
	const DOCUMENT_TOKEN_PDF_VERSION = '/^%PDF-([^\\r\\n]+)(\\r\\n|\\r|\\n)/';
	const DOCUMENT_TOKEN_OBJECT_START = '/^[0-9]+[ ]+[0-9]+[ ]+obj(\\r\\n|\\r|\\n)/';
	const DOCUMENT_TOKEN_OBJECT_END = '/^endobj(\\r\\n|\\r|\\n)/';
	const DOCUMENT_TOKEN_XREF_START = '/^xref(\\r\\n|\\r|\\n)/';
	const DOCUMENT_TOKEN_TRAILER_START = '/^trailer(\\r\\n|\\r|\\n)/';
	const DOCUMENT_TOKEN_STARTXREF_START = '/^startxref(\\r\\n|\\r|\\n)';
	const DOCUMENT_TOKEN_EOF = '/^%%EOF/';
	const DOCUMENT_LINE = '/^[^\\r\\n](\\r\\n|\\r|\\n)/';
	
	public function __construct($filePath) {
		if(!file_exists($filePath)) {
			throw new PDFReaderException('File does not exist : ' . $filePath);
		}
		if(is_dir($filePath)) {
			throw new PDFReaderException('Specified file is a directory : ' . $filePath);
		}
		if(!is_readable($filePath)) {
			throw new PDFReaderException('File cannot be read : ' . $filePath);
		}
		$fileSize = filesize($filePath);
		$pdfStr = file_get_contents($filePath);
		if($pdfStr === false) {
			throw new PDFReaderException('Failed to load the file : ' . $filePath);
		}
		if(strlen($pdfStr) != $fileSize) {
			throw new PDFReaderException("File size is incorrect. expected=$fileSize, actual=" . strlen($pdfStr));
		}
		
		$matches = array();
		
		// header
		if(!preg_match(self::DOCUMENT_TOKEN_HEADER, $pdfStr, $matches)) {
			throw new PDFReaderSyntaxException('Document header not found');
		}
		$this->rawHeader = $matches[0];
		if(preg_match(self::DOCUMENT_TOKEN_PDF_VERSION, $this->rawHeader, $matches)) {
			$this->version = $matches[1];
		}
		//echo 'rawHeader = ' . $this->rawHeader . "\n";
		//echo 'pdfVersion = ' . $this->version . "\n";
		$pdfStr = preg_replace(self::DOCUMENT_TOKEN_HEADER, '', $pdfStr);
		
		//echo 'head of $pdfStr = ' . preg_replace('/\\r\\n?/', "\n", substr($pdfStr, 0, 30)) . "\n";
		$currentObjectKey = null;
		$currentContent = '';
		while(true) {
			//echo 'you are in loop' . "\n";
			if(is_null($currentObjectKey)) {
				if(preg_match(self::DOCUMENT_TOKEN_OBJECT_START, $pdfStr, $matches)) {
					$currentObjectKey = preg_replace('/(\\r\\n|\\r|\\n)/', '', $matches[0]);
					if(isset($this->objects[$currentObjectKey])) {
						throw new PDFReaderSyntaxException('duplicated object : ' . $currentObjectKey);
					}
					echo 'objKey = ' . $currentObjectKey;
					$pdfStr = preg_replace(self::DOCUMENT_TOKEN_OBJECT_START, '', $pdfStr);
				}
				else if(preg_match(self::DOCUMENT_TOKEN_XREF_START, $pdfStr, $matches)) {
					$pdfStr = preg_replace(self::DOCUMENT_TOKEN_XREF_START, '', $pdfStr);
					break;
				}
				else {
					throw new PDFReaderSyntaxException('obj or xref is expected, but found : ' . substr($pdfStr, 0, 20));
				}
				$currentContent = '';
			}
			else {
				if(preg_match(self::DOCUMENT_TOKEN_OBJECT_END, $pdfStr)) {
					$pdfStr = preg_replace(self::DOCUMENT_TOKEN_OBJECT_END, '', $pdfStr);
					$this->objects[$currentObjectKey] = $currentContent;
					$currentObjectKey = null;
					$currentContent = '';
					continue;
				}
				else {
					list($line, $pdfStr) = $this->_readLine($pdfStr);
					if(strlen($pdfStr) <= 0) {
						throw new PDFReaderSyntaxException('Premature end of PDF file. expected : object content or endobj');
					}
					echo 'line content = ' . $line;
					$currentContent .= $line;
					continue;
				}
			}
		}
		
		// xref
		$this->rawXref = '';
		while(true) {
			if(preg_match(self::DOCUMENT_TOKEN_TRAILER_START, $pdfStr)) {
				preg_replace(self::DOCUMENT_TOKEN_TRAILER_START, '', $pdfStr);
				break;
			}
			list($line, $pdfStr) = $this->_readLine($pdfStr);
			if(strlen($pdfStr) <= 0) {
				throw new PDFReaderSyntaxException('EOF detected while parsing xref');
			}
			$this->rawXref .= $line;
		}
		
		// trailer dictionary
		// TODO support CRLF|CR|LF
		$startXrefPos = strpos($pdfStr, "\nstartxref\n");
		if($startXrefPos === false) {
			throw new PDFReaderSyntaxException('startxref not found');
		}
		$this->rawTrailerDictionary = substr($pdfStr, 0, $startXrefPos+1);
		$pdfStr = substr($pdfStr, $startXrefPos+1);
		$pdfStr = preg_replace(self::DOCUMENT_TOKEN_STARTXREF_START, '', $pdfStr);
		$this->trailerDictionary = PDFObject::parseHeaderDictionary($this->rawTrailerDictionary);
		
		// start xref
		list($line, $pdfStr) = $this->_readLine($pdfStr);
		$this->startXref = $line;
		
		// EOF
		if(preg_match(self::DOCUMENT_TOKEN_EOF, $pdfStr)) {
			// OK
		}
		else {
			throw new PDFReaderSyntaxException('%%EOF expected but found : ' . substr($pdfStr, 0, 20));
		}
		
		// now, we have done to check whole the file.
		// next, parse the pieces.
		
		// TODO parse objects
		
		// TODO check the trailer dictionary and build pages array
	}
	
	protected function _readLine($pdfStr) {
		$matches = array();
		if(preg_match(self::DOCUMENT_LINE, $pdfStr, $matches)) {
			$pdfStr = preg_replace(self::DOCUMENT_LINE, '', $pdfStr);
			return array($matches[0], $pdfStr);
		}
		else {
			return array($pdfStr, '');
		}
	}
}
