<?php

App::import('Component', 'Facebook');

class FacebookHelper extends AppHelper {

	var $helpers = array('Javascript', 'Session');
	
	function init ($options = array(), $lang='en_US') {
    	if(Configure::read( 'FB.AppId' )){
	    	$appId = Configure::read( 'FB.AppId' );
	    	$init = '<div id="fb-root"></div>';
	    	$init .=  $this->Javascript->codeBlock(
		      	"
		      		window.fbAsyncInit = function() {
				        FB.init({
							appId   : '{$appId}',
							status  : true, // check login status
							cookie  : true, // enable cookies to allow the server to access the session
							xfbml   : true
				        });
				        FB.Canvas.setAutoResize(100);
					};
					(function() {
						var e = document.createElement('script');
						e.src = document.location.protocol + '//connect.facebook.net/{$lang}/all.js';
						e.async = true;
						document.getElementById('fb-root').appendChild(e);
					}());
	      		",
				$options
			);
    	} else {
			return "<span class='error'>No Facebook configuration detected!</span>";
    	}
		return $init;
    }
    
	function login ($options = array()) {
		return $this->_fbTag('fb:login-button', '', $options);
	}
	
	private function _fbTag($tag, $label, $options){
	    $retval = "<$tag";
	    foreach($options as $name => $value){
			if($value === false) $value = 0;
			$retval .= " " . $name . "='" . $value . "'";
	    }
	    $retval .= ">$label</$tag>";
	    return $retval;
	}

}

?>