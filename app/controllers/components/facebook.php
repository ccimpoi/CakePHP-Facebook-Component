<?php

/*
 * Note: for this to work you need to set the Security.level to low because otherwise there is a referer_check
 */

App::import('Vendor', 'facebook/src/base_facebook');

class FacebookComponent extends BaseFacebook {
	
//	name of *** flash layout *** and session key. Flashing in the view will delete the sess key
	const FB_JS_REDIRECT_SESSION = 'fb_redirect';
	const FB_RESULT_SUCCESS = 'FB_RESULT_SUCCESS';
	const FB_RESULT_DENY_ERROR = 'FB_RESULT_DENY_ERROR';
	const FB_RESULT_ERROR = 'FB_RESULT_ERROR';
		
	protected static $kSupportedKeys = array('state', 'code', 'access_token', 'user_id');
	
	var $components = array('Session');
	
 	public function __construct() {
		parent::__construct(array(
			'appId'  => Configure::read('FB.AppId'),
			'secret' => Configure::read('FB.AppSecret'),
			'cookie' => true
		));
	}
	
	function initialize (&$controller, $settings = array()) {
		$this->controller =& $controller;
	}
	
	function startup () {
		$this->getUser();
		
		if (Configure::read('FB.Debug')) {
			$this->controller->log('>>> FB Debug', LOG_DEBUG);
			$this->controller->log($this->controller->here, LOG_DEBUG);
			$this->controller->log($this->getUser(), LOG_DEBUG);
			$this->controller->log($this->getAccessToken(), LOG_DEBUG);
			$this->controller->log($this->getUserAccessToken(), LOG_DEBUG);
			$this->controller->log(json_encode($this->controller->params), LOG_DEBUG);
			$this->controller->log(json_encode($_SESSION), LOG_DEBUG);
		}
		
	//	if admin then skip any redirect. Below is only for page tabs
		if (Set::check($this->controller->params, 'admin')) return;
		
	//	check if the user did not allow our app
		if (Set::check($this->controller->params['url'], 'error')) {
			if ($this->controller->params['url']['error'] == 'access_denied') {
				$appData = array(
					'result' => self::FB_RESULT_DENY_ERROR,
					'redirect' => $this->controller->here
				);
			} else {
				$appData = array(
					'result' => self::FB_RESULT_ERROR,
					'redirect' => $this->controller->here
				);
			}
			$this->controller->redirect(
				$this->getPageAppTabUrl(). '&app_data='. urlencode(json_encode($appData))
			);
			return;
		}
		
	//	check if we received a FB code = initial FB callback
		if (Set::check($this->controller->params['url'], 'code')) {
			$appData = array(
				'result' => self::FB_RESULT_SUCCESS,
				'redirect' => $this->controller->here
			);
			$this->controller->redirect(
				$this->getPageAppTabUrl(). '&app_data='. urlencode(json_encode($appData))
			);
			return;
		}
		
	//	check if we received a signed_request = we are back on the Page Tab
		if (Set::check($this->controller->params['form'], 'signed_request')) {
			$ad = $this->getAppData();
			if (Configure::read('FB.Debug')) {
				$this->controller->log(json_encode($ad), LOG_DEBUG);
			}
			if ($ad && Set::check($ad, 'result')) {
				$res = $ad['result'];
				switch ($res) {
					case self::FB_RESULT_SUCCESS:
						$this->Session->setFlash(__('You are now logged in with your Facebook account', true), 'default', array('class' => 'session-message'));
						break;
					case self::FB_RESULT_DENY_ERROR:
						$this->Session->setFlash(__('You need to log in with your Facebook account and allow our application to access your basic data', true), 'default', array('class' => 'session-error-message'));
						break;
					case self::FB_RESULT_ERROR:
						$this->Session->setFlash(__('There was an error. Please try again later', true), 'default', array('class' => 'session-error-message'));
						break;
				}
			}
			
			if ($ad && Set::check($ad, 'redirect')) {
				$this->controller->redirect($ad['redirect']);
				return;
			}
		}
	}
	
	function getPageAppTabUrl () {
		return "http://www.facebook.com/pages/". Configure::read('FB.PageName'). "/". Configure::read('FB.PageId'). "?sk=app_". Configure::read('FB.AppId');
	}
	
//	when called from the controller it sets a redirect key that is flashed in the view with an top.location.href
	function checkUser () {
		if (!$this->getUser()) {
		//	set the redirect after FB login
			$this->Session->setFlash(
				$this->getLoginUrl(array(
					'redirect_uri' => Router::url($this->controller->referer(), true)
				)),
				'flash/'. self::FB_JS_REDIRECT_SESSION, array(), self::FB_JS_REDIRECT_SESSION
			);
			return false;
		}
		
		return true;
	}
	
	function updatePageAccessToken () {
		$this->controller->redirect($this->getLoginUrl(array(
			'scope' => 'user_status, publish_stream, user_photos, offline_access, manage_pages'
		)));
		return;
	}
	
	function getPageAccessToken () {
		if (Set::check($this->controller->params['url'], 'code')) {
			$code = $this->controller->params['url']['code'];
			if (Configure::read('FB.Debug')) $this->controller->log('getPageAccessToken', LOG_DEBUG);
			if (Configure::read('FB.Debug')) $this->controller->log($code, LOG_DEBUG);
			$at = $this->getAccessTokenFromCode($code);
			if (Configure::read('FB.Debug')) $this->controller->log($at, LOG_DEBUG);
			$feed = $this->api( '/'. Configure::read('FB.PageId'), 'GET', array('access_token' => $at, 'fields' => array('access_token')));
			if (Configure::read('FB.Debug')) $this->controller->log($feed, LOG_DEBUG);
			if (Set::check($feed, 'access_token')) {
				return $feed['access_token'];
			}
		}
		return false;
	}
	
	function getAppData () {
		$sr = $this->getSignedRequest();
		if (Set::check($sr, 'app_data')) return json_decode(urldecode($sr['app_data']), true);
		return false;
	}
	
	protected function setPersistentData($key, $value) {
		if (!session_id()) return;
		
    	if (!in_array($key, self::$kSupportedKeys)) {
			self::errorLog('Unsupported key passed to setPersistentData.');
			return;
    	}
    	
    	$session_var_name = $this->constructSessionVariableName($key);
    	$this->Session->write($session_var_name, $value);
	}

	protected function getPersistentData($key, $default = false) {
		if (!session_id()) return $default;
		
		if (!in_array($key, self::$kSupportedKeys)) {
			self::errorLog('Unsupported key passed to getPersistentData.');
			return $default;
    	}
    	
		$session_var_name = $this->constructSessionVariableName($key);
		return $this->Session->check($session_var_name) ? $this->Session->read($session_var_name) : $default;
	}

	protected function clearPersistentData($key) {
		if (!session_id()) return;
		
		if (!in_array($key, self::$kSupportedKeys)) {
			self::errorLog('Unsupported key passed to clearPersistentData.');
			return;
		}

		$session_var_name = $this->constructSessionVariableName($key);
		$this->Session->delete($session_var_name);
	}

	protected function clearAllPersistentData() {
		foreach (self::$kSupportedKeys as $key) {
			$this->clearPersistentData($key);
		}
	}

	protected function constructSessionVariableName($key) {
		return implode('_', array('fb',
                              $this->getAppId(),
                              $key));
	}
}
?>