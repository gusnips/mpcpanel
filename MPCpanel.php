<?php
/**
 * @author Gustavo Salomé Silva
 * @license New BSD License
 * Install
    put this file file under any imported directory and in you components config file add the component
    return array(
       '...'
       'components'=>array(
            '...',//other components
            'cpanel'=>array(
                   'class'=>'MPCpanel',
                    'username'=>'yourUsername',//required
                    'url'=>'http://mydomain.com:2086/',//required 
                                                      // by default https://127.0.0.1:2087/
                    'auth_type'=>'basic',//accepted = basic or whm
                                         //basic by default
                    'api_type'=>'json',//accepted = json or xml
                                       //json by default
                    'password'=>'myPassword',//required for basic type of authentication
                    'access_key'=>'myBiggggggggggKey',//required for whm type of authentication
            ),
       ),
    );
    
    Usage
    
    $cpanel=Yii::app()->cpanel;
     
    //will use xml/json api
    $cpanel->listaccts();
     
    //will use xml/json api with parameters
    $cpanel->createacct(array('username'=>'myAccount','domain'=>'mydomain.com','password'=>'myPassword'));
    
    //will use cpanel api1 function webalizer, module Stats, no parameters
    $cpanel->webalizer('Stats');
     
    //will use cpanel api1 function webalizer, module Stats
    $cpanel->webalizer('Stats','mydomain');
     
    //will use cpanel api1 function adduserdb, module Mysql using parameters (*api1 parameters must be in order and as string) 
    $cpanel->adduserdb('Mysql','mydbname','mydbuser','all');
     
     //will use cpanel api2 function listwebalizer, module Stats
     //to call api2 make sure the second parameter is array, even if empty
    $cpanel->listwebalizer('Stats',array());
     
    //will use cpanel api2 function change_password, module Passwd using parameters (*api2 parameters must be an array using key/values pairs)
    $cpanel->username='client3';
    $cpanel->password='client3Password';
    $cpanel->change_password('Passwd',array('newpass'=>'m1n3wp4$$w0rd','oldpass'=>$cpanel->password));
 *
 *
 * 
 * */
class MPCpanel extends CComponent{
    public  $username='root',$url='https://127.0.0.1:2087/',
            $api_type='json',$auth_type='basic',$password,$access_key;
    function init(){
        if(empty($this->username))
            throw new CException('Username must be defined');
        
        //check if url is correct and normalize it
        if(empty($this->url))
            throw new CException('url must be defined');
        else
            $this->url=trim($this->url,"/")."/";
        //check if api type is correct and normalize it
        if(!in_array(($this->api_type=strtolower($this->api_type)),array('json','xml')))
            throw new CException('Invalid api type. Accepted values are json and xml');
        //check if auth type is correct and normalize it
        if(!in_array(($this->auth_type=strtolower($this->auth_type)),array('basic','whm')))
            throw new CException('Invalid auth type. Accepted values are basic and whm');
        //check if api all values are set for the auth type 
        if($this->auth_type==='basic' && empty($this->password))
            throw new CException('Password must be defined for basic authentication');
        if($this->auth_type==='whm' && empty($this->access_key))    
            throw new CException('Access_key must be defined  for whm authentication');
    }
    function __call($function,$parameters){
        //json/xml api call 
        if(!isset($parameters[0]) || is_array($parameters[0])){
            $module=null;
            $api_version=null;
            if(!isset($parameters[0]))
                $args=array();
            else
                $args=$parameters[0];
        //it is a module call for an api
        }elseif(is_string($parameters[0])){
            //by default, cpanel modules are ucfirst
            $module=ucfirst($parameters[0]);
            //call api 2
            if(isset($parameters[1]) && is_array($parameters[1])){
                $api_version=2;
                if(!isset($parameters[1]))
                    $args=array();
                else
                    $args=$parameters[1];
            //call api1
            }else{
                $api_version=1;
                $args=array_slice($parameters,1);
            }
        }

        return $this->createRequest($function,$args,$api_version,$module);
    }
    /**
     * MPCpanel::createRequest()
     * 
     * @param string $function name of the function to call
     * @param array $parameters array of parameters
     * @param int $api_version accepted null meaning xml/json api will be used, 1 and 2 that will use cpanel's apis 1 or 2
     * @param string $module required if api is 1 or 2
     * @return array
     */
    function createRequest($function,$parameters,$api_version=null,$module=null){
        //creates the request
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);	
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0); 	
        curl_setopt($curl, CURLOPT_HEADER,0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array($this->getAuthorizationHeader()));  
        curl_setopt($curl, CURLOPT_URL, ($url=$this->createUrl($function,$parameters,$api_version,$module)));
        $result = curl_exec($curl);
        if ($result === false) 
        	throw new CException("curl_exec threw error \"" . curl_error($curl). "url: ".$url );
        curl_close($curl);
        //decodes the response
        switch($this->api_type){
            case 'json': return json_decode($result);
            case 'xml': return simplexml_load_string($result);
            default: return $result; 
        }
    }
    private function getAuthorizationHeader(){
        switch(strtolower($this->auth_type)){
            case "basic": 
                return "Authorization: Basic ".base64_encode($this->username.":".$this->password)."\r\n";
            case "whm": 
                return "Authorization: WHM {$this->username}:" . $this->getAuthKey();
            default: throw new CException('Authorization type invalid');
        }
    }
    private function createUrl($function,$parameters,$api_version=null,$module=null){
        switch($api_version){
            case null:
                return "{$this->url}{$this->api_type}-api/{$function}".( count($parameters) ? "?".http_build_query($parameters) : null );
            case 1:
                return "{$this->url}{$this->api_type}-api/cpanel?user={$this->username}&cpanel_{$this->api_type}api_module={$module}&cpanel_{$this->api_type}api_apiversion=1&cpanel_{$this->api_type}api_func={$function}".( count($parameters) ? "&".http_build_query($this->prefix_keys($parameters)) : null );
            case 2:   
                return "{$this->url}{$this->api_type}-api/cpanel?user={$this->username}&cpanel_{$this->api_type}api_module={$module}&cpanel_{$this->api_type}api_apiversion=2&cpanel_{$this->api_type}api_func={$function}".( count($parameters) ? "&".http_build_query($parameters) : null );
        }
    }
    private function getAuthKey(){
        return preg_replace("'(\r|\n|\s)'","",$this->access_key);
    }
    private function prefix_keys($array){
        $n_array=array();
        foreach($array as $key=>$value){
            $n_array['arg-'.$key]=$value;
        }
        return $n_array;
    }
}