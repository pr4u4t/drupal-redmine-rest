<?php

namespace Drupal\robco_rest\Utils;

use Drupal\Component\Utility\Random;
use Symfony\Component\HttpFoundation\Request;

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
		$this->setPostCallback('delCartItem',array($this,'removeCartItem'));
		$this->setPostCallback('showProfile',array($this,'showProfile'));
		$this->setPostCallback('updateProfile',array($this,'updateProfile'));
		$this->setPostCallback('showTickets',array($this,'showTickets'));
		$this->setPostCallback('updateTicket',array($this,'updateTicket'));
		$this->setPostCallback('addTicket',array($this,'addTicket'));
		//$this->setPostCallback('showProducts',array($this,'showProducts'));
		$this->setPostCallback('showProduct',array($this,'showProduct'));
        $this->setPostCallback('showOrders',array($this,'showOrders'));
		$this->setPostCallback('showOrder',array($this,'showOrder'));
		$this->setPostCallback('login',array($this,'login'));
		//set GET url callbacks
		$this->setGetCallback('getimage',array($this,'getImage'));
		$this->setGetCallback('showcart',array($this,'showCart'));
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
	
	protected function getAuthCookie(){
        $request = Request::createFromGlobals();
        
        $ret['deal_id'] = $request->cookies->get('robco_rest.deal_id');
        $ret['session'] = $request->cookies->get('robco_rest.session');
        $ret['auth']    = $request->cookies->get('robco_rest.auth');
        
        return $ret;
	}
	
	protected function getRequest($url, $cookiesIn = '',$user = null, $password = null){
        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => true,     //return headers in addition to content
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            CURLINFO_HEADER_OUT    => true,
            CURLOPT_SSL_VERIFYPEER => true,     // Validate SSL Certificates
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_COOKIE         => $cookiesIn
        );

        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        
        if($user !== null && $password !== null){
            curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $password);
        }
        
        $rough_content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close( $ch );

        $header_content = substr($rough_content, 0, $header['header_size']);
        $body_content = trim(str_replace($header_content, '', $rough_content));
        $pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m"; 
        preg_match_all($pattern, $header_content, $matches); 
        $cookiesOut = implode("; ", $matches['cookie']);
        
        
        $ret = [
            'errno'             => $err,
            'errmsg'            => $errmsg,
            'headers'           => $header_content,
            'content'           => $body_content,
            'cookies'           => $cookiesOut,
            'status'            => $httpcode,
            'content-type'      => $contentType,
            'content-length'    => $contentLength
        ];
        
        return $ret;
	}
	
	protected function postRequest($url, $data, $cookiesIn = '',$content_type = ''){
        $header = array(
            "Content-type: ".$content_type,
            "Content-length: " . strlen($data),
            "Connection: close"
        );
        
        $options = array(
            CURLOPT_POST            => true,
            CURLOPT_URL             => $uri,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_USERAGENT       => $this->userAgent(),
            CURLOPT_HEADER          => false,
            CURLOPT_ENCODING        => "",
            CURLOPT_AUTOREFERER     => true,
            CURLOPT_CONNECTTIMEOUT  => 120,
            CURLOPT_TIMEOUT         => 120,
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_POSTFIELDS      => $data,
            CURLOPT_HTTPHEADER      => $header
		);

        $ch = curl_init();
        curl_setopt_array( $ch, $options );
        
        if(($ret = curl_exec($ch)) === FALSE) {
			return null;
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if($httpcode < 200 || $httpcode >= 300){
            return null;
        }
        
        
	}
	
	protected function putRequest($url, $data, $cookiesIn = '', $content_type){
        $header = array(
            "Content-type: application/xml",
            "Content-length: " . strlen($data),
            "Connection: close"
        );
        
        $options = array(
            CURLOPT_CUSTOMREQUEST   => "PUT",
            CURLOPT_POST            => false,
            CURLOPT_HTTPGET         => false,
            CURLOPT_URL             => $url, //$this->hostAddress()."/deals/".$id.".xml?key=".$this->apiKey()
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_USERAGENT       => $this->userAgent(),
            CURLOPT_HEADER          => false,
            CURLOPT_ENCODING        => "",
            CURLOPT_AUTOREFERER     => true,
            CURLOPT_CONNECTTIMEOUT  => 120,
            CURLOPT_TIMEOUT         => 120,
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_POSTFIELDS      => $data,
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
	}
	
	protected function checkCart($id){
        $ret = $this->getRequest($this->hostAddress()."/deals/$id.xml?key=".$this->apiKey());
        return ($ret['status'] < 200 || $ret['status'] >= 300) ? false : true;
	}
	
	protected function initCart(){
        $sess = $this->getAuthCookie();
    
        if($this->checkCart($sess['deal_id'])){
            return $sess['deal_id'];
        }
        
        $rand = new Random();
        $id = $rand->name(16,true);
        $id = "cart-$id";
        $xml = '<deal><project_id>'.$this->projectID().'</project_id><name>'.$id.'</name><contact_id>'.$this->defaultCartOwner().'</contact_id></deal>';
        
        if(!($cart = $this->postRequest($this->hostAddress()."/deals.xml?key=".$this->apiKey(),$xml,/*COOKIES*/null,'application/xml'))){
            return array();
        }
        
        if(!($ctree = new \SimpleXMLElement($data)) || !property_exists($ctree,'id')){
            return array(
                'status'        => 500,
                'content'       => 'Failed to get cart details.',
                'content_type'  => 'text/plain'
            );
        }
        
        $response = new Response();
        $cookie = new Cookie('redmine_commerce.deal_id',($id = (string) $ctree->id), 0, '/' , NULL, FALSE);
        $response->headers->setCookie($cookie);
        $response->send();
        
        return $id;
	}
	
	protected function showCart(array $args = array(),$format = 'xml',$pretty = true){
        
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
                // set url
                $url = $this->hostAddress()."/deals/$id.xml?key=".$this->apiKey();
                
                if($pretty){
                    $url .= "&pretty=true";
                }
                
                if(!($cart = $this->getRequest($url)) || $cart['status'] < 200 || $cart['status'] >= 300){
                    return array(
                        'status'        => 500,
                        'content'       => 'Failed to get cart data.',
                        'content_type'  => 'text/plain'
                    );
                }
                
                return array(
                    'status'        => 200,
                    'content'       => $ret,
                    'content_type'  => 'application/xml'
                );
        }
        
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
        
        if(!is_array(($cart = $this->showCart(array($id),'xml',false))) || !isset($cart['status']) 
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
        
        $resp = $this->putRequest($this->hostAddress()."/deals/".$id.".xml?key=".$this->apiKey(),$xml);
        
        if($resp['status'] < 200 || $resp['status'] >= 300){
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
        
        if(!isset($args[0])){
            return array(
                'status'        => 500,
                'content_type'  => 'text/plain',
                'content'       => 'Product position missing.'
            );
        }
        
        if(!is_array(($cart = $this->showCart(array($id),'xml',false))) || !isset($cart['status']) 
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
        
        if(!((property_exists($ctree,'lines') && is_a(($lines = $ctree->lines),"SimpleXMLElement")))){
           return array(
                'status'        => 500,
                'content'       => 'Failed to get cart lines.',
                'content_type'  => 'text/plain'
            );
        }
        
        list($elem) = $ctree->xpath("//lines/line[position=".$args[0]."]");
    
        if(!is_a($elem,"SimpleXMLElement")){
                return array(
                        'status'        => 500,
                        'content'       => 'Failed to find line item to remove.',
                        'content_type'  => 'text/plain'
                );  
        }   

        $elem[0]->addChild("_destroy","1");
        
        if(!($xml = $this->serializeXML($ctree))){
            return array(
                'status'        => 500,
                'content_type'  => 'text/plain',
                'content'       => 'Failed to prepare cart data.'
            );
        }
        
        if(!($resp = $this->putRequest($this->hostAddress()."/deals/".$id.".xml?key=".$this->apiKey(),$xml)) 
            || $resp['status'] < 200 || $resp['status'] >= 300){
            return array(
                'status'        => 500,
                'content_type'  => 'text/plain',
                'content'       => 'Invalid response code when removing line item.'
            );
        }
        
        return array(
            'status'        => 200,
            'content'       => 'Line item removed.',
            'content_type'  => 'text/plain'
        );
	}
	
	protected function login(array $args = array()){
	
        $postReq = \Drupal::request()->request->all();
        $login = isset($postReq['login']) ? $postReq['login'] : FALSE;
        $password = isset($postReq['password']) ? $postReq['password'] : FALSE;
        
        if(!$login || !$password){
            return array(
                'status'        => 200,
                'content'       => 'Invalid data',
                'content_type'  => 'text/plain'
            );
        }
                
        if(!($account = $this->getRequest($this->hostAddress()."/my/account.xml",null,$login,$password)) 
            || $account['status'] < 200 || $account['status'] >= 300){
            return array(
                'status'        => 500,
                'content'       => 'Failed to get account data.',
                'content_type'  => 'text/plain'
            );
        }
        
        if(!($ctree = new \SimpleXMLElement($ret))){
            return array(
                'status'        => 500,
                'content'       => 'Failed to parse account content.',
                'content_type'  => 'text/plain'
            );
        }
        
        if(!property_exists($ctree,'api_key')){
            return array(
                'status'        => 500,
                'content'       => 'Failed to obtain api-key.',
                'content_type'  => 'text/plain'
            );
        }
        
        if(!property_exists($ctree,'login')){
            return array(
                'status'        => 500,
                'content'       => 'Failed to obtain login.',
                'content_type'  => 'text/plain'
            );
        }
        
        /*if(!($tempstore = \Drupal::service('tempstore.private')->get('redmine_commerce'))){
            return array(
                'status'        => 500,
                'content'       => 'Failed to obtain tempstore.',
                'content_type'  => 'text/plain'
            );
        }
        
        $tempstore->set('login', (string) $ctree->login);
        //$tempstore->set('api_key', ($id = (string) $ctree->api_key));
        */
        
        return array(
            'redirect'        => '<front>',
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
	
	protected function getImage(array $args = array()){
	
        if(!isset($args[0])){
            return array(
                'status'        => 404,
                'content_type'  => 'text/plain',
                'content'       => 'Missing argument'
            );
        }
        
        if(!($image = $this->getRequest($this->hostAddress()."/attachments/download/".$args[0])) 
            || $image['status'] < 200 || $image['status'] >= 300){
            return array(
                'status'        => 500,
                'content_type'  => 'text/plain',
                'content'       => 'Failed to get image from redmine..'
            );
        }
        
        return array(
            'status'            => 200,
            'content'           => $image['content'],
            'content_type'      => $image['content-type'],
            'content_length'    => $image['content-length']
        );
	}
	
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
                if(!($product = $this->getRequest($this->hostAddress()."/products/".$args[0].".xml?key=".$this->apiKey())) 
                    || $product['status'] < 200 || $product['status'] >= 300){
                    return array(
                        'status'        => 500,
                        'content_type'  => 'text/plain',
                        'content'       => 'Failed to get image from redmine..'
                    );
                }
                
                return array(
                    'status'        => 200,
                    'content'       => $product['content'],
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
                'status'        => 500,
                'content'       => "Failed to validate GET data",
                'content_type'  => 'text/plain'
            );
		}
        
        try{
            $ret = null;
            
            if(!($callable = $this->getCallback(strtolower($command)))){
                return array(
                    'status'        => 500,
                    'content'       => "Command not understood",
                    'content_type'  => 'text/plain'
                );
            }
            
            if(!($ret = call_user_func($callable,$args))){
                return array(
                    'status'        => 500,
                    'content'       => 'Failed to call handler method.',
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
	
	public function getCallback($command){
        return (is_array($this->_getCallbacks)) ? (isset($this->_getCallbacks[$command])) ? $this->_getCallbacks[$command] : null : null;
	}
	
	public function setGetCallback($command, $callback){
        $this->_getCallbacks[$command] = $callback;
	}
}
