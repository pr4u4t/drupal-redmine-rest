<?php

namespace Drupal\robco_rest\Utils;

use Drupal\Component\Utility\Random;

class XsltHandler{
	private $_apiKey;
	private $_hostAddr;
	private $_transformator;
	private $_siteAddress;
	private $_userAgent;
	private $_method;
	private $_projectID;
    private $_defaultCartOwner;
    
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
        
        if(isset($opts['default_cart_owner'])){
            $this->setDefaultCartOwner($opts['default_cart_owner']);
        }
        
        $this->setUserAgent((isset($opts['user_agent'])) ? $opts['user_agent'] : "DEFAULT");
		$this->setXsltTransformator(new \XSLTProcessor());
		
		//set POST command callbacks
		$this->setPostCallback('showCart',array($this,'showCart'));
		$this->setPostCallback('addCartItem',array($this,'addCartItem'));
		$this->setPostCallback('removeCartItem',array($this,'removeCartItem'));
		$this->setPostCallback('showProfile',array($this,'showProfile'));
		$this->setPostCallback('updateProfile',array($this,'updateProfile'));
		$this->setPostCallback('showTickets',array($this,'showTickets'));
		$this->setPostCallback('updateTicket',array($this,'updateTicket'));
		$this->setPostCallback('addTicket',array($this,'addTicket'));
		//$this->setPostCallback('showProducts',array($this,'showProducts'));
		$this->setPostCallback('showProduct',array($this,'showProduct'));
        $this->setPostCallback('showOrders',array($this,'showOrders'));
		$this->setPostCallback('showOrder',array($this,'showOrder'));
		
		//set GET url callbacks
		$this->setGetCallback('/getImage/',array($this,'getImage'));
		$this->setGetCallback('/showCart/',array($this,'showCart'));
	}

	public function __destruct(){}

	protected function transform($xml, $xsl) {
		if(!$this->xsltTransformator()){
			return null;
		}

   		$this->xsltTransformator()->importStylesheet(new  \SimpleXMLElement($xsl,0,true));
   		return $this->xsltTransformator()->transformToXml(new \SimpleXMLElement($xml,0,true));
	}

	public function handle($command, array $args = array()){
	
        if(!$this->hostAddress() || !$this->apiKey() || !$this->siteAddress() || !$this->projectID()){
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
	
	protected function initCart(){
        if(!($tempstore = \Drupal::service('tempstore.private')->get('redmine_commerce'))){
            return null;
        }
	
        if(($id = $tempstore->get('cart_id')) != null){
            return $id;
        }
	
        $rand = new Random();
        $id = $rand->name(16,true);
        $id = "cart-$id";
        $xml = '<deal><project_id>'.$this->projectID().'</project_id><name>'.$id.'</name><contact_id>'.$this->defaultCartOwner().'</contact_id></deal>';
        
        $header = array(
            "Content-type: application/xml",
            "Content-length: " . strlen($xml),
            "Connection: close"
        );
        
        $options = array(
            CURLOPT_POST            => true,
            CURLOPT_URL             => $this->hostAddress()."/deals.xml?key=".$this->apiKey(),
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
			return null;
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if($httpcode < 200 || $httpcode >= 300){
            return null;
        }
        
        if(!($ctree = new \SimpleXMLElement($data)) || !property_exists($ctree,'id')){
            return array(
                'status'        => 500,
                'content'       => 'Failed to get cart details.',
                'content_type'  => 'text/plain'
            );
        }
        
        $tempstore->set('cart_id', ($id = (string) $ctree->id));
        
        return $id;
	}
	
	protected function showCart(array $args = array(),$format = 'xml'){
        if(!($id = $this->initCart())){
            return array(
                'status'        => 500,
                'content_type'  => 'text/plain',
                'content'       => 'Failed to obtain cart'
            );
        }
        
        switch($format){
            case 'html':
                if(!($ret = $this->transform($this->hostAddress()."/deals/$id.xml?key=".$this->apiKey(),
                    $this->hostAddress()."/cms/pages/cartxsl?key=".$this->apiKey()))){
                
                    return array(
                        'status'        => 500,
                        'content'       => 'Failed to transform cart data.',
                        'content_type'  => 'text/plain'
                    );
                }
                
                return array(
                    'status'        => 200,
                    'content_type'  => 'text/html',
                    'content'       => $ret
                );
        
        
            case 'xml':
                // create curl resource
                $ch = curl_init();

                // set url
                curl_setopt($ch, CURLOPT_URL, $this->hostAddress()."/deals/$id.xml?key=".$this->apiKey());

                //return the transfer as a string
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                // $output contains the output string
                $ret = curl_exec($ch);

                // close curl resource to free up system resources
                curl_close($ch);
                
                return array(
                    'status'        => 200,
                    'content'       => $ret,
                    'content_type'  => 'application/xml'
                );
        }
        
        /*
        $ret = array(
                'status'        => 500, 
                'content'       => curl_error($ch),
                'content_type'  => 'text/plain'
        );
        
        $ret = array(
                'status'    => 200, 
                'content'   => $data
        );
            
        $header  = curl_getinfo( $ch );
        $ret['content_type'] = (isset($header['content_type'])) ? $header['content_type'] : null;
        */
        
        return array(
            'status'        => 422,
            'content'       => 'Unprocessable entity.',
            'content_type'  => 'text/plain'
        );
	}
	
	protected function addCartItem(array $args = array()){
        if(!($id = $this->initCart())){
            return array(
                'status'        => 500,
                'content_type'  => 'text/plain',
                'content'       => 'Failed to obtain cart'
            );
        }
        
        if(!isset($args[0])){
            return array(
                'status'        => 500,
                'content_type'  => 'text/plain',
                'content'       => 'Product ID missing.'
            );
        }
        
        if(!is_array(($cart = $this->showCart($args,'xml'))) || !isset($cart['status']) 
            || $cart['status'] < 200 || $cart['status'] >= 300){
            return array(
                'status'        => 500,
                'content'       => 'Failed to get cart content.',
                'content_type'  => 'text/plain'
            );
        }
	
        if(!($ctree = new \SimpleXMLElement($cart['content']))){
            return array(
                'status'        => 500,
                'content'       => 'Failed to parse cart content.',
                'content_type'  => 'text/plain'
            );
        }

        if(!((property_exists($ctree,'lines') && is_a(($lines = $ctree->lines),"SimpleXMLElement"))
                || is_a($lines = $ctree->addChild('lines'),"SimpleXMLElement"))){
           return array(
                'status'        => 500,
                'content'       => 'Failed to add cart lines.',
                'content_type'  => 'text/plain'
            );
        }

        if(!is_a($line = $lines->addChild('line'),"SimpleXMLElement")){
            return array(
                'status'        => 500,
                'content'       => 'Failed to add line item.',
                'content_type'  => 'text/plain'
            );
        }
    
        if(!$this->xmlAttribute($lines,"type")){
            $lines->addAttribute('type','array');
        }
        
        if(!($parray = $this->showProduct(array($args[0]),'xml'))){
            return array(
                'status'        => 500,
                'content'       => 'Failed to obtain product details.',
                'content_type'  => 'text/plain'
            );
        }
        
        if(!($ptree = new \SimpleXMLElement($parray['content']))){
            return array(
                'status'        => 500,
                'content'       => 'Failed to parse product content.',
                'content_type'  => 'text/plain'
            );
        }
        
        $line->addChild('position', $ctree->lines->count());
        $line->addChild('product_id', $args[0]);
        $line->addChild('price',$ptree->price);
        $line->addChild('quantity',1);
        
        if(!($xml = $this->serializeXML($ctree))){
            return array(
                'status'        => 500,
                'content_type'  => 'text/plain',
                'content'       => 'Failed to prepare cart data.'
            );
        }
        
        $header = array(
            "Content-type: application/xml",
            "Content-length: " . strlen($xml),
            "Connection: close"
        );
        
        $options = array(
            CURLOPT_CUSTOMREQUEST   => "PUT",
            CURLOPT_POST            => false,
            CURLOPT_HTTPGET         => false,
            CURLOPT_URL             => $this->hostAddress()."/deals/".$id.".xml?key=".$this->apiKey(),
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
			return array(
                'status'        => 500,
                'content_type'  => 'text/plain',
                'content'       => 'Failed to send data to redmine ('.curl_error($ch).')'
			);
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if($httpcode < 200 || $httpcode >= 300){
            return array(
                'status'        => 500,
                'content_type'  => 'text/plain',
                'content'       => 'Invalid response code when adding line item.'
            );
        }
        
        return array(
            'status'        => 200,
            'content'       => 'Product added.',
            'content_type'  => 'text/plain'
        );
	}
	
	protected function removeCartItem(array $args = array()){
        if(!($id = $this->initCart())){
            return array(
                'status'        => 500,
                'content_type'  => 'text/plain',
                'content'       => 'Failed to obtain cart'
            );
        }
        
        return array(
            'status'        => 200,
            'content'       => 'Not implemented yet',
            'content_type'  => 'text/plain'
        );
	}
	
	protected function showProfile(array $args = array()){
        return array(
            'status'        => 200,
            'content'       => 'Not implemented yet',
            'content_type'  => 'text/plain'
        );
	}
	
	protected function updateProfile(array $args = array()){
        return array(
            'status'        => 200,
            'content'       => 'Not implemented yet',
            'content_type'  => 'text/plain'
        );
	}
	
	protected function showTickets(array $args = array()){
        return array(
            'status'        => 200,
            'content'       => 'Not implemented yet',
            'content_type'  => 'text/plain'
        );
	}
	
	protected function updateTicket(array $args = array()){
        return array(
            'status'        => 200,
            'content'       => 'Not implemented yet',
            'content_type'  => 'text/plain'
        );
	}
	
	protected function addTicket(array $args = array()){
        return array(
            'status'        => 200,
            'content'       => 'Not implemented yet',
            'content_type'  => 'text/plain'
        );
	}
	
	/*
	protected function showProducts(array $args = array()){
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
			
        return array(
            'status'        => 200,
            'content'       => $ret,
            'content_type'  =>'text/html'
        );
	}
	*/
	
	protected function showProduct(array $args = array(),$format = 'html'){
        switch($format){
            case 'html':
                if(!($ret = $this->transform($this->hostAddress()."/products/$id.xml?key=".$this->apiKey(),
                    $this->hostAddress()."/cms/pages/productxsl?key=".$this->apiKey()))){
                        return array(
                            'status'        => 500,
                            'content_type'  => 'text/plain',
                            'content'       => "Failed to get product details."
                        );
                }
                
                return array(
                    'status'        => 200,
                    'content_type'  => 'text/html',
                    'content'       => $ret
                );
        
        
            case 'xml':
                // create curl resource
                $ch = curl_init();

                // set url
                curl_setopt($ch, CURLOPT_URL, $this->hostAddress()."/products/$args[0].xml?key=".$this->apiKey());

                //return the transfer as a string
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                // $output contains the output string
                $ret = curl_exec($ch);

                // close curl resource to free up system resources
                curl_close($ch);
                
                return array(
                    'status'        => 200,
                    'content'       => $ret,
                    'content_type'  => 'application/xml'
                );
        }
        
        return array(
            'status'        => 422,
            'content'       => "Unprocessable entity.",
            'content_type'  =>'text/plain'
        );
	}
	
	protected function showOrders(array $args = array()){
        $ret = "Not implemented yet";
        return array(
            'status'        => 200,
            'content'       => $ret,
            'content_type'  =>'text/html'
        );
	}
	
	protected function showOrder(array $args = array()){
        $ret = "Not implemented yet";
        return array(
            'status'        => 200,
            'content'       => $ret,
            'content_type'  =>'text/html'
        );
	}
	
	protected function order(array $args = array()){
        $ret = "Not implemented yet";
        return array(
            'status'        => 200,
            'content'       => $ret,
            'content_type'  =>'text/html'
        );
	}
	
	protected function xmlAttribute(\SimpleXMLElement $elem, $sattr){
        foreach($elem->attributes() as $attr => $value) {
                if($attr == $sattr)
                    return $value;
        }
        
        return null;
    }
	
	protected function serializeXML(\SimpleXMLElement $tree){
        return $tree->asXML();
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

		try{
            $ret = null;
            
            if(!($callable = $this->postCallback($command))){
                return array(
                    'status'        => 500,
                    'content'       => "Command not understood",
                    'content_type'  => 'text/plain'
                );
            }
            
            if(!($ret = call_user_func($callable,$args))){
                return array(
                    'status'        => 500,
                    'content'       => $ret,
                    'content_type'  => 'text/plain'
                );
            
            }

			return $ret;

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
		
		if(!is_string($command)){
			return array(
                'status'        => 400,
                'content'       => "Failed to validate GET data",
                'content_type'  => 'text/plain'
            );
		}
        
        /*if(!preg_match('/(attachments\/)(download\/)([0-9]+)(\/.+)/', $_GET['q'], $matches) || count($matches) != 5){
			return array(
                'status'        => 400,
                'content'       => "Invalid GET parameters",
                'content_type'  => 'text/plain'
            );
        }*/
		
		/*$options = array(
            CURLOPT_URL             => $this->hostAddress()."/".$matches[1].$matches[2].$matches[3]."?key=".$this->apiKey(),
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
        */
        
        try{
            $ret = null;
            
            if(!($callable = $this->getCallback($command))){
                return array(
                    'status'        => 500,
                    'content'       => "Command not understood",
                    'content_type'  => 'text/plain'
                );
            }
            
            if(!($ret = call_user_func($callable,$args))){
                return array(
                    'status'        => 500,
                    'content'       => $ret,
                    'content_type'  => 'text/plain'
                );
            
            }

			return $ret;

        }catch(Exception $e){
            return array(
                'status'        => 500, 
                'content'       => $e->getMessage(),
                'content_type'  => 'text/plain'
            );
        }
        
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
    
    public function defaultCartOwner(){
        return $this->_defaultCartOwner;
    }
    
    public function setDefaultCartOwner($id){
        $this->_defaultCartOwner = $id;
    }
    
    private function setXsltTransformator($trans){
		$this->_transformator = $trans;
	}

	private function xsltTransformator(){
		return $this->_transformator;
	}
	
	public function postCallback($command){
        return (is_array($this->_postCallbacks)) ? (isset($this->_postCallbacks[$command])) ? $this->_postCallbacks[$command] : null : null;
	}
	
	public function setPostCallback($command, $callback){
        if($command == null || !is_string($command)){
            return false;
        }
        
        $this->_postCallbacks[$command] = $callback;
        return true;
	}
	
	public function getCallback($url){
        return (is_array($this->_getCallbacks)) ? (isset($this->_getCallbacks[$command])) ? $this->_getCallbacks[$command] : null : null;
	}
	
	public function setGetCallback($regex, $callback){
        $this->_getCallbacks[$regex] = $callback;
	}
}
