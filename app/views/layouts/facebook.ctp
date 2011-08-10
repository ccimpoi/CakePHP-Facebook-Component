<!doctype html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
<head>
	<title></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
<p>Lorem ipsum</p>

<?php echo $facebook->init(null, 'de_DE'); ?>
<?php $session->flash(FacebookComponent::FB_JS_REDIRECT_SESSION); ?>

</body>
</html>