<?php

class XsltHandler{
	private $_apiKey;
	private $_hostAddr;
	private $_transformator;
	private $_siteAddress;
	private $_userAgent;

	public function __construct($apiKey, $hostAddr, $siteAddr,$agent = null){
		$this->setApiKey($apiKey);
		$this->setHostAddress($hostAddr);
		$this->setSiteAddress($siteAddr);
		$this->setUserAgent($agent);
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

	protected function cart(){
        if(!($tempstore = \Drupal::service('tempstore.private')->get('redmine_commerce'))){
            return array(false,"Failed to get session storage");
        }
        
        if(($ret = $tempstore->get('cart_id')) != null){
            return array(true,$ret);
        }
        
        $id = Drupal\Component\Utility\Random::string(16,true);
        $id = "cart-$id";
        $xml = '<?xml version="1.0" encoding="UTF-8"?><deal><project_id>1</project_id><name>'.$id.'</name><contact_id>1</contact_id></deal>';
        
        $header = array(
            "Content-type: text/xml",
            "Content-length: " . strlen($xml),
            "Connection: close"
        );
        
        $options = array(
            CURLOPT_POST            => true,
            CURLOPT_URL             => $this->hostAddress()"/deals.xml?key=".$this->apiKey(),
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_USERAGENT       => $this->userAgent(),
            CURLOPT_HEADER          => false,
            CURLOPT_ENCODING        => "",
            CURLOPT_AUTOREFERER     => true,
            CURLOPT_CONNECTTIMEOUT  => 120,
            CURLOPT_TIMEOUT         => 120,
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_POSTFIELDS      => $xml,
            CURLOPT_HTTPHEADER      => $header
		);

        $ch = curl_init();
        curl_setopt_array( $ch, $options );
        
        if(($data = curl_exec($ch)) === FALSE) {
			$ret = array(
                'status'        => 500, 
                'content'       => curl_error($ch),
                'content_type'  => 'text/plain'
            );
        }else{
            $ret = array(
                'status'    => 200, 
                'content'   => $data
            );
            $header  = curl_getinfo( $ch );
            $ret['content_type'] = (isset($header['content_type'])) ? $header['content_type'] : null;
        }

        curl_close($ch);
        
        $tempstore->set('cart_id', $id);
	}
	
	protected function postHandler(){
        $siteInfo = array();
        $matches = array();
        $opts = array();
	
        if(!preg_match('/(https*:\/\/)(.+)/',$this->siteAddress(), $siteInfo) || count($siteInfo) != 3 ){
            return array(
                'status'        => 500,
                'content'       => "Cannot obtain valid site address",
                'content_type'  => "text/plain"
            );
        }

        $opts['site_protocol']  = $siteInfo[1];
        $opts['site_address']   = $siteInfo[2];
        
        if(!preg_match('/(https*:\/\/)(.+)/', $this->hostAddress(), $siteInfo) || count($siteInfo) != 3 ){
            return array(
                'status'        => 500,
                'content'       => "Cannot obtain valid REST endpoint address",
                'content_type'  => "text/plain"
            );
        }
        
        $opts['rest_protocol']   = $siteInfo[1];
        $opts['rest_address']    = $siteInfo[2];	

		if (!isset($_POST['item']) || !is_string($_POST['item'])) {
            return array(
                'status'        => 400,
                'content'       => "Failed to validate POST data",
                'content_type'  => 'text/plain'
            );
        }   

        if (!$this->validate($_POST['item']) || !$this->parse($_POST['item'],$matches)){
            return array(
                'status'        => 400,
                'content'       => "Failed to parse POST data",
                'content_type'  => 'text/html'
            );
        }   

        $data = (isset($matches[0])) ? (isset($matches[0][0])) ? $matches[0][0] : null : null;
        $tpl = (isset($matches[2])) ? (isset($matches[2][0])) ? $matches[2][0] : null : null;

		try{
			//php be damned why you don't support xslt 2.0 still better then webkit
            $ret = $this->transform($this->baseAddress()."/".$data.".xml?key=".$this->apiKey(),
            $this->hostAddress()."/pages/".$tpl."xsl?key=".$this->apiKey());

			$ret = preg_replace_callback('/(<img[ ]+(alt="[^"]+")*[ ]+src="https*:\/\/)('.$opts['rest_address'].'\/)/',
				function ($matches) use($opts) {
					return $matches[1].$opts['site_address']."/robco_rest/";
                },
			$ret);

			return array(
                'status'        => 200,
                'content'       => $ret,
                'content_type'  => 'text/html'
            );

        }catch(Exception $e){
            return array(
                'status'        => 500, 
                'content'       => $e->getMessage(),
                'content_type'  => 'text/plain'
            );
        }
	}

	protected function getHandler(){
		
		$ret = null;
        $data = null;
		$matches = array();
		
		if(!isset($_GET['q']) || !is_string($_GET['q'])){
			return array(
                'status'        => 400,
                'content'       => "Failed to validate GET data",
                'content_type'  => 'text/plain'
            );
		}
        
        if(!preg_match('/(attachments\/)(download\/)([0-9]+)(\/.+)/', $_GET['q'], $matches) || count($matches) != 5){
			return array(
                'status'        => 400,
                'content'       => "Invalid GET parameters",
                'content_type'  => 'text/plain'
            );
        }
		
		$options = array(
            CURLOPT_URL             => $this->hostAddress()"/".$matches[1].$matches[2].$matches[3]."?key=".$this->apiKey(),
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_USERAGENT       => $this->userAgent(),
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
			$ret = array(
                'status'        => 500, 
                'content'       => curl_error($ch),
                'content_type'  => 'text/plain'
            );
        }else{
            $ret = array(
                'status'    => 200, 
                'content'   => $data
            );
            $header  = curl_getinfo( $ch );
            $ret['content_type'] = (isset($header['content_type'])) ? $header['content_type'] : null;
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

    public function userAgent(){
        return $this->_userAgent;
    }
    
    public function setUserAgent($agent){
        $this->_userAgent = $agent;
    }
}
