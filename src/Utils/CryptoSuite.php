<?php

	namespace Drupal\robco_rest\Utils;

	class CryptoSuite{
	
        private $_privKey;
        private $_pubKey;
        
		public function __construct($privkey, $pubkey){
            if($privkey != null && $pubkey != null && is_string($privkey) &&  is_string($pubkey)){
                if(!($tmp = openssl_pkey_get_private($privkey))){
                    throw new Exception("Failed to initialize private key");
                }
                
                $this->setPrivateKey($tmp);
                
                if(!($tmp = openssl_pkey_get_public($tmp))){
                    throw new Exception("Failed to initialize public key");
                }
                
                $this->setPublicKey($tmp);
            }
            
            if($privkey == null && $pubkey == null){
                if(!($tmp = openssl_pkey_new())){
                    throw new Exception("Failed to create new private key");
                }
                
                $this->setPrivateKey($tmp);
                
                if(!($tmp = openssl_pkey_get_public($tmp))){
                    throw new Exception("Failed to get public key");
                }
                
                $this->setPublicKey($tmp);
            }
		}

		protected function encrypt($data, $token){
            if(!$this->publicKey() || !$this->privateKey()){
                return false;
            }
            
            $encrypted = $data;
		
            if(!openssl_private_encrypt($data, $encrypted, $this->privateKey())){
                return false;
            }
            
            return $this->xor_otp($encrypted,$token);
		}
		
		protected function decrypt($data, $token){
            if(!$this->publicKey() || !$this->privateKey()){
                return false;
            }
            
            $decrypted = null;
            
            if(!openssl_public_decrypt($data, $decrypted, $this->publicKey())){
                return false;
            }
            
            return $this->xor_otp($decrypted,$token);
		}

        protected function xor_otp($input, $otp){
            $ret = "";
            $k = 0;
            $klen = strlen($otp);
    	
            for ( $i = 0; $i < strlen($input); $i++ ){
                $ret .= chr(ord($input[$i]) ^ $otp[$k]); 
                $k = (++$k < $klen ? $k : 0);
            }
    	
            return $ret;
        }
        
        private function setPrivateKey($key){
            $this->_privKey = $key;
        }
        
        private function setPublicKey($key){
            $this->_pubKey = $key;
        }
        
        protected function publicKey(){
            return $this->_pubKey;
        }
        
        protected function privateKey(){
            return $this->_privKey;
        }
	}
