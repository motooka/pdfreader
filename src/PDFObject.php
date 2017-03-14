<?php
namespace PDFReader;

class PDFObject {
	public $rawHeaderDictionary = null;
	public $headerDictionary = null;
	public $rawStream = null;
	public $rawContent = null;
	
	public function __construct($objectText) {
		$this->rawContent = $objectText;
		$rest = $objectText;
		
		if(strpos($rest, '<<') === 0) {
			$streamPos = strpos($rest, "\nstream");
			if($streamPos !== false) {
				$this->rawHeaderDictionary = substr($rest, 0, $streamPos);
				$rest = substr($rest, $streamPos+1);
				echo "streamPosition = $streamPos";
			}
			else {
				$this->rawHeaderDictionary = $rest;
				$rest = '';
			}
			
			// TODO parse header dictionary
			$this->headerDictionary = [];
		}
		
		if(strpos($rest, 'stream') === 0) {
			$this->rawStream = preg_replace('/\\nendstream\\n/', '', substr($rest, strlen("stream\n")));
			
			// TODO parse stream
		}
	}
}
