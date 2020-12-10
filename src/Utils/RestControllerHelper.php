<?php



class XsltHandler{
	private $_apiKey;
	private $_hostAddr;
	private $_transformator;
	private $_siteAddress;
	private $_userAgent;
	private $_method;
	private $_projectID;

	private $_postCallbacks;
	private $_getCallbacks;
	
	public function __construct(array $opts){
        $this->_postCallbacks = array();
        $this->_getCallbacks = array();
	
        if(isset($opts['api_key'])){
            $this->setApiKey($opts['api_key']);
        }
        
        if(isset($opts['host'])){
            $this->setHostAddress($opts['host']);
        }
        
        if(isset($opts['site'])){
            $this->setSiteAddress($opts['site']);
        }
        
        if(isset($opts['method'])){
            $this->setMethod($opts['method']);
        }
        
        if(isset($opts['project_id'])){
            $this->setProjectID($opts['project_id']);
        }
        
        $this->setUserAgent((isset($opts['user_agent'])) ? $opts['user_agent'] : "DEFAULT");
		$this->setXsltTransformator(new XSLTProcessor());
		
		//set POST command callbacks
		$this->setPostCallback('showCart',array($this,'showCart'));
		$this->setPostCallback('addCartItem',array($this,'addCartItem'));
		$this->setPostCallback('removeCartItem',array($this,'removeCartItem'));
		$this->setPostCallback('showProfile',array($this,'showProfile'));
		$this->setPostCallback('updateProfile',array($this,'updateProfile'));
		$this->setPostCallback('showTickets',array($this,'showTickets'));
		$this->setPostCallback('updateTicket',array($this,'updateTicket'));
		$this->setPostCallback('addTicket',array($this,'addTicket'));
		$this->setPostCallback('showProducts',array($this,'showProducts'));
		$this->setPostCallback('showProduct',array($this,'showProduct'));
        $this->setPostCallback('showOrders',array($this,'showOrders'));
		$this->setPostCallback('showOrder',array($this,'showOrder'));
		
		//set GET url callbacks
		$this->setGetCallback('//',array($this,'getImage'));
	}

	public function __destruct(){}

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

	public function handle($command, array $args = array()){
	
        if(!$this->host() || !$this->apiKey() || !$this->siteAddress() || !$this->projectID()){
            return array(500,'Incomplete settings','text/plain');
        }
        
        if(!$command){
            return array(500,'Invalid request','text/plain');
        }
        
        switch($this->method()){
            case "POST":
                return $this->postHandler($command,$args);
                
            case "GET":
                return $this->getHandler($command,$args);
        }
        
        return array(500,'Unprocessable request','text/plain');
	}	
	
	protected function showCart(){
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
	
	protected function addCartItem(){
        return array(200,'Not implemented yet','text/plain');
	}
	
	protected function removeCartItem(){
        return array(200,'Not implemented yet','text/plain');
	}
	
	protected function showProfile(){
        return array(200,'Not implemented yet','text/plain');
	}
	
	protected function updateProfile(){
        return array(200,'Not implemented yet','text/plain');
	}
	
	protected function showTickets(){
        return array(200,'Not implemented yet','text/plain');
	}
	
	protected function updateTicket(){
        return array(200,'Not implemented yet','text/plain');
	}
	
	protected function addTicket(){
        return array(200,'Not implemented yet','text/plain');
	}
	
	protected function showProducts(){
        $data = (isset($matches[0])) ? (isset($matches[0][0])) ? $matches[0][0] : null : null;
        $tpl = (isset($matches[2])) ? (isset($matches[2][0])) ? $matches[2][0] : null : null;
		
        //php be damned why you don't support xslt 2.0 still better then webkit
        $ret = $this->transform($this->baseAddress()."/".$data.".xml?key=".$this->apiKey(),
        $this->hostAddress()."/pages/".$tpl."xsl?key=".$this->apiKey());

        $ret = preg_replace_callback('/(<img[ ]+(alt="[^"]+")*[ ]+src="https*:\/\/)('.$opts['rest_address'].'\/)/',
            function ($matches) use($opts) {
                return $matches[1].$opts['site_address']."/robco_rest/";
            },
        $ret);
			
        return array();
	}
	
	protected function showProduct(){
        return array(200,'Not implemented yet','text/plain');
	}
	
	protected function showOrders(){
        return array(200,'Not implemented yet','text/plain');
	}
	
	protected function showOrder(){
        return array(200,'Not implemented yet','text/plain');
	}
	
	protected function order(){
        return array(200,'Not implemented yet','text/plain');
	}
	
	protected function postHandler($command,$args){
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

		try{
            //find right callback

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

	protected function getHandler($command,$args){
		
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

    public function userAgent(){
        return $this->_userAgent;
    }
    
    public function setUserAgent($agent){
        $this->_userAgent = $agent;
    }
    
    public function method(){
        return $this->_method;
    }
    
    public function setMethod($method){
        $this->_method = $method;
    }
    
    public function projectID(){
        return $this->_projectID;
    }
    
    public function setProjectID($id){
        $this->_projectID = $id;
    }
    
    private function setXsltTransformator($trans){
		$this->_transformator = $trans;
	}

	private function xsltTransformator(){
		return $this->_transformator;
	}
	
	public function postCallback($command){
        return (is_array($this->_callbacks)) ? (isset($this->_callbacks[$command])) ? : null : null;
	}
	
	public function setPostCallback($command, $callback){
        if($command == null || !is_string($command)){
            return false;
        }
        
        $this->_callbacks[$command] = $callback;
        return true;
	}
	
	public function getCallback($url){
        $matches = array();
        $call = array(0,null);
        
        if(!$this->_getCallbacks || !is_array($this->_getCallbacks)){
            return null;
        }
        
        foreach($this->_getCallbacks as $regex => $callback){
            if(!is_string($regex) || !preg_match($regex,$url,$matches)){
                continue;
            }
            
            if(count($matches[0]) > $call[0]){
                $call[0] = count($matches[0]);
                $call[1] = $callback;
            }
        }
        
        return $call[1];
	}
	
	public function setGetCallback($regex, $callback){
        $this->_getCallbacks[$regex] = $callback;
	}
}
