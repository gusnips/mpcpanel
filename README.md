mpcpanel
========

Yii extension to access cpanel functions - accepts json xml, cpanel 1 and 2 apis


##Requirements

+ [PHP curl extension](http://php.net/manual/en/book.curl.php "")   

##Install

put the download file under any imported directory   
and in you components config file add the component  

``` php
    return array(
       '...'
       'components'=>array(
            '...',//other components
            'cpanel'=>array(
                   'class'=>'MPCpanel',
                    'username'=>'yourUsername',//required
                    'url'=>'http://mydomain.com:2086/',// by default https://127.0.0.1:2087/
                    'auth_type'=>'basic',//accepted = basic or whm
                                         //basic by default
                    'api_type'=>'json',//accepted = json or xml
                                       //json by default
                    'password'=>'myPassword',//required for basic type of authentication
                    'access_key'=>'myBiggggggggggKey',//required for whm type of authentication
            ),
       ),
    );
```

##Usage

``` php
        $cpanel=Yii::app()->cpanel;
     
    //will use xml/json api
    $cpanel->listaccts();
     
    //will use xml/json api with parameters
    $cpanel->createacct(array('username'=>'myAccount','domain'=>'mydomain.com','password'=>'myPassword'));
    
    //will use cpanel api1 function webalizer, module Stats, no parameters
    $cpanel->webalizer('Stats');
     
    //will use cpanel api1 function adduserdb, module Mysql using parameters (*api1 parameters must be in order and as string) 
    $cpanel->adduserdb('Mysql','mydbname','mydbuser','all');
     
     //will use cpanel api2 function listwebalizer, module Stats
     //to call api2 make sure the second parameter is array, even if empty
    $cpanel->listwebalizer('Stats',array());
     
    //will use cpanel api2 function change_password, module Passwd using parameters (*api2 parameters must be an array using key/values pairs)
    $cpanel->username='client3';
    $cpanel->password='client3Password';
    $cpanel->change_password('Passwd',array('newpass'=>'m1n3wp4$$w0rd','oldpass'=>$cpanel->password));

```


##Resources

+ [forum support ](http://www.yiiframework.com/forum/index.php?/topic/18262-mpcpanel/ "forum support")
+ [Get an access_key
](http://docs.cpanel.net/twiki/bin/view/SoftwareDevelopmentKit/RemoteAccess "Get an access_key")
+ [json/xml api functions](http://docs.cpanel.net/twiki/bin/view/SoftwareDevelopmentKit/XmlApi "json/xml api functions")
+ [cpanel api1 functions](http://docs.cpanel.net/twiki/bin/view/ApiDocs/Api1/WebHome "cpanel api1 functions")
+ [cpanel api2 functions](http://docs.cpanel.net/twiki/bin/view/ApiDocs/Api2/WebHome "cpanel api2 functions")
