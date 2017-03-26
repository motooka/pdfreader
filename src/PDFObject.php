<?php
namespace motooka\PDFReader;

use motooka\PDFReader\Exception\PDFReaderSyntaxException;

class PDFObject {
	public $rawHeaderDictionary = null;
	public $headerDictionary = null;
	public $rawStream = null;
	public $rawContent = null;
	
	const HEADER_TOKEN_DICTIONARY_OPEN = '/^<</';
	const HEADER_TOKEN_DICTIONARY_CLOSE = '/^>>/';
	const HEADER_TOKEN_ARRAY_OPEN = '/^\\[/';
	const HEADER_TOKEN_ARRAY_CLOSE = '/^\\]/';
	const HEADER_TOKEN_NAME = '/^\\/[A-Za-z0-9]+/';
	const HEADER_TOKEN_REFERENCE = '/^[0-9]+[ ]+[0-9]+[ ]+R/';
	const HEADER_TOKEN_INTEGER = '/^\\/-?[0-9]+/';
	const HEADER_TOKEN_NUMERIC = '/^\\/-?([0-9]+)?\\.[0-9]+/';
	const HEADER_TOKEN_WHITESPACE = '/^[ \\t\\r\\n]+/';
	
	public function __construct($objectText) {
		$this->rawContent = $objectText;
		$rest = $objectText;
		
		if(strpos($rest, '<<') === 0) {
			$streamPos = strpos($rest, "\nstream");
			if($streamPos !== false) {
				$this->rawHeaderDictionary = substr($rest, 0, $streamPos+1);
				$rest = substr($rest, $streamPos+1);
				echo "streamPosition = $streamPos";
			}
			else {
				$this->rawHeaderDictionary = $rest;
				$rest = '';
			}
			
			// parse header dictionary
			$this->headerDictionary = self::parseHeaderDictionary($this->rawHeaderDictionary);
		}
		
		if(strpos($rest, 'stream') === 0) {
			$this->rawStream = preg_replace('/\\nendstream\\n/', '', substr($rest, strlen("stream\n")));
			
			// TODO parse stream
		}
	}
	
	public static function parseHeaderDictionary($rawHeaderDictionary) {
		// trim leading whitespaces
		$rawHeaderDictionary = preg_replace(self::HEADER_TOKEN_WHITESPACE, '', $rawHeaderDictionary);
		list($result, $rest) = self::_parseDictionary($rawHeaderDictionary);
		
		$rest = preg_replace(self::HEADER_TOKEN_WHITESPACE, '', $rest);
		if(strlen($rest) > 0) {
			throw new PDFReaderSyntaxException('Header dictionary has more contents.');
		} 
		
		return $result;
	}
	protected static function _parseDictionary($headerDictionaryRest) {
		if(!preg_match(self::HEADER_TOKEN_DICTIONARY_OPEN, $headerDictionaryRest)) {
			throw new PDFReaderSyntaxException();
		}
		$headerDictionaryRest = preg_replace(self::HEADER_TOKEN_DICTIONARY_OPEN, '', $headerDictionaryRest);
		$resultArray = array();
		
		$currentKey = null;
		$matches = array();
		while(true) {
			$headerDictionaryRest = preg_replace(self::HEADER_TOKEN_WHITESPACE, '', $headerDictionaryRest);
			if(is_null($currentKey)) {
				if(preg_match(self::HEADER_TOKEN_NAME, $headerDictionaryRest, $matches)) {
					// the dictionary has a next entry
					$currentKey = $matches[0];
					if(isset($resultArray[$currentKey])) {
						throw new PDFReaderSyntaxException('The dictionary has duplicated key : ' . $currentKey);
					}
					$headerDictionaryRest = preg_replace(self::HEADER_TOKEN_NAME, '', $headerDictionaryRest);
				}
				else if(preg_match(self::HEADER_TOKEN_DICTIONARY_CLOSE, $headerDictionaryRest, $matches)) {
					// end of the dictionary
					$headerDictionaryRest = preg_replace(self::HEADER_TOKEN_DICTIONARY_CLOSE, '', $headerDictionaryRest);
					break;
				}
				else {
//					var_dump($resultArray);
					throw new PDFReaderSyntaxException('A dictionary key should be a name but another token was found. First 20 chars : ' . substr($headerDictionaryRest, 0 ,20));
				}
			}
			else {
				$value = null;
				
				if(preg_match(self::HEADER_TOKEN_REFERENCE, $headerDictionaryRest, $matches)) {
					$value = $matches[0];
					$headerDictionaryRest = preg_replace(self::HEADER_TOKEN_REFERENCE, '', $headerDictionaryRest);
				}
				else if(preg_match(self::HEADER_TOKEN_NAME, $headerDictionaryRest, $matches)) {
					$value = $matches[0];
					$headerDictionaryRest = preg_replace(self::HEADER_TOKEN_NAME, '', $headerDictionaryRest);
				}
				else if(preg_match(self::HEADER_TOKEN_INTEGER, $headerDictionaryRest, $matches)) {
					$value = $matches[0];
					$headerDictionaryRest = preg_replace(self::HEADER_TOKEN_INTEGER, '', $headerDictionaryRest);
				}
				else if(preg_match(self::HEADER_TOKEN_NUMERIC, $headerDictionaryRest, $matches)) {
					$value = $matches[0];
					$headerDictionaryRest = preg_replace(self::HEADER_TOKEN_NUMERIC, '', $headerDictionaryRest);
				}
				else if(preg_match(self::HEADER_TOKEN_DICTIONARY_OPEN, $headerDictionaryRest, $matches)) {
					list($value, $headerDictionaryRest) = self::_parseDictionary($headerDictionaryRest);
				}
				else if(preg_match(self::HEADER_TOKEN_ARRAY_OPEN, $headerDictionaryRest, $matches)) {
					list($value, $headerDictionaryRest) = self::_parseArray($headerDictionaryRest);
				}
				else {
					throw new PDFReaderSyntaxException('Unknown token was found as a dictionary value. First 20 chars : ' . substr($headerDictionaryRest, 0 ,20));
				}
				
				$resultArray[$currentKey] = $value;
				$currentKey = null;
			}
		}
		
		return array($resultArray, $headerDictionaryRest);
	}
	protected static function _parseArray($headerDictionaryRest) {
		if(!preg_match(self::HEADER_TOKEN_ARRAY_OPEN, $headerDictionaryRest)) {
			throw new PDFReaderSyntaxException();
		}
		$headerDictionaryRest = preg_replace(self::HEADER_TOKEN_ARRAY_OPEN, '', $headerDictionaryRest);
		$resultArray = array();
		
		$matches = array();
		while(true) {
			$headerDictionaryRest = preg_replace(self::HEADER_TOKEN_WHITESPACE, '', $headerDictionaryRest);
			$value = null;
			
			if(preg_match(self::HEADER_TOKEN_REFERENCE, $headerDictionaryRest, $matches)) {
				$value = $matches[0];
				$headerDictionaryRest = preg_replace(self::HEADER_TOKEN_REFERENCE, '', $headerDictionaryRest);
			}
			else if(preg_match(self::HEADER_TOKEN_NAME, $headerDictionaryRest, $matches)) {
				$value = $matches[0];
				$headerDictionaryRest = preg_replace(self::HEADER_TOKEN_NAME, '', $headerDictionaryRest);
			}
			else if(preg_match(self::HEADER_TOKEN_INTEGER, $headerDictionaryRest, $matches)) {
				$value = $matches[0];
				$headerDictionaryRest = preg_replace(self::HEADER_TOKEN_INTEGER, '', $headerDictionaryRest);
			}
			else if(preg_match(self::HEADER_TOKEN_NUMERIC, $headerDictionaryRest, $matches)) {
				$value = $matches[0];
				$headerDictionaryRest = preg_replace(self::HEADER_TOKEN_NUMERIC, '', $headerDictionaryRest);
			}
			else if(preg_match(self::HEADER_TOKEN_DICTIONARY_OPEN, $headerDictionaryRest, $matches)) {
				list($value, $headerDictionaryRest) = self::_parseDictionary($headerDictionaryRest);
			}
			else if(preg_match(self::HEADER_TOKEN_ARRAY_OPEN, $headerDictionaryRest, $matches)) {
				list($value, $headerDictionaryRest) = self::_parseArray($headerDictionaryRest);
			}
			else if(preg_match(self::HEADER_TOKEN_ARRAY_CLOSE, $headerDictionaryRest, $matches)) {
				// end of the array
				$headerDictionaryRest = preg_replace(self::HEADER_TOKEN_ARRAY_CLOSE, '', $headerDictionaryRest);
				break;
			}
			else {
//				var_dump($resultArray);
				throw new PDFReaderSyntaxException('Unknown token was found as an array item. First 20 chars : ' . substr($headerDictionaryRest, 0 ,20));
			}
			
			$resultArray[] = $value;
			$currentKey = null;
		}
		
		return array($resultArray, $headerDictionaryRest);
	}
}
