<?php

class XsltHandler{
	private $_apiKey;
	private $_hostAddr;
	private $_transformator;
	private $_siteAddress;

	public function __construct($apiKey, $hostAddr, $siteAddr){
		$this->setApiKey($apiKey);
		$this->setHostAddress($hostAddr);
		$this->setSiteAddress($siteAddr);
		$this->setXsltTransformator(new XSLTProcessor());
	}

	public function __destruct(){}

	private function setXsltTransformator($trans){
		$this->_transformator = $trans;
	}

	private function xsltTransformator(){
		return $this->_transformator;
	}

	protected function transform($xml, $xsl) {
		if(!$this->xsltTransformator()){
			return null;
		}

   		$this->xsltTransformator()->importStylesheet(new  SimpleXMLElement($xsl,0,true));
   		return $this->xsltTransformator()->transformToXml(new SimpleXMLElement($xml,0,true));
	}

	protected function validate($input){
        	return preg_match('/(cart|products)|(product\/([1-9][0-9]*))/', $input);
	}

	protected function parse($input,&$matches){
        	return preg_match_all('/(([a-z]+)(\/([1-9][0-9]+)){0,1})/', $input,$matches);
	}

	protected function postHandler(){
		if (!isset($_POST['item']) || !is_string($_POST['item'])) {
            return array(
                'status'    => 400,
                'content'   => "Failed to validate POST data"
            );
        }   

        $matches = array();

        if (!$this->validate($_POST['item']) || !$this->parse($_POST['item'],$matches)){
            return array(400,"Failed to parse POST data");
        }   

        $data = (isset($matches[0])) ? (isset($matches[0][0])) ? $matches[0][0] : null : null;
        $tpl = (isset($matches[2])) ? (isset($matches[2][0])) ? $matches[2][0] : null : null;


		try{
			//php be damned why you don't support xslt 2.0 still better then webkit
            $ret = $this->transform($this->baseAddress()."/".$data.".xml?key=".$this->apiKey(),
            $this->hostAddress()."/pages/".$tpl."xsl?key=".$this->apiKey());

			$ret = preg_replace_callback('/(<img[ ]+(alt="[^"]+")*[ ]+src="https*:\/\/)(office.robco.pl\/)/',
				function ($matches) {
					return $matches[1]."bigdrip.pl/xslt.php?q=";
        			},
			$ret);

			
			return array(200,$ret);

        }catch(Exception $e){
            return array(500, $e->getMessage());
        }
	}

	protected function getHandler(){
		
		$ret = null;
        $data = null;
        $user_agent='PHP/7.0 (LINUX; arch) Drupal/8.0';
		$matches = array();
		
		if(!isset($_GET['q']) || !is_string($_GET['q'])){
			return array(400,"Failed to validate GET data");
		}
        
        if(!preg_match('/(attachments\/)(download\/)([0-9]+)(\/.+)/', $_GET['q'], $matches) || count($matches) != 5){
			return array(400,"Invalid GET parameters");
        }
		
		$options = array(
            CURLOPT_URL             => $this->hostAddress()"/".$matches[1].$matches[2].$matches[3]."?key=".$this->apiKey(),
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_USERAGENT       => $user_agent,
            CURLOPT_HEADER          => false,
            CURLOPT_ENCODING        => "",
            CURLOPT_AUTOREFERER     => true,
            CURLOPT_CONNECTTIMEOUT  => 120,
            CURLOPT_TIMEOUT         => 120,
            CURLOPT_MAXREDIRS       => 10
		);

        $ch = curl_init();
        curl_setopt_array( $ch, $options );
        
        if(($data = curl_exec($ch)) === FALSE) {
			$ret = array(500, curl_error( $ch ));
        }else{
            $ret = array(200, $data);
            $header  = curl_getinfo( $ch );
            if(isset($header["content_type"])){
                $ret
            }
        }

        curl_close($ch);
        
		return $ret;
	}

	public function setApiKey($key){
		$this->_apiKey = $key;
	}

	public function apiKey(){
		return $this->_apiKey;
	}

	public function setHostAddress($addr){
		$this->_hostAddr = $addr;
	}

	public function hostAddress(){
		return $this->_hostAddr;
	}

	public function siteAddress(){
        return $this->_siteAddress;
	}
	
	public function setSiteAddress($site){
        $this->_siteAddress = $site;
	}
	
	public function handle($method){
        if($method == "POST")
			return $this->postHandler();	

		if($method == "GET")
			return $this->getHandler();
	}

}
