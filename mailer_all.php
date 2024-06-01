<?php
header('Content-Type: text/html; charset=iso-8859-1');


// fieldname (string) => required (bool)
$fieldsets = array(
	'from' => array('email' => true),
	'to' => array('email' => true),
	'body' => array('subject' => true, 'message' => true)
);
$success = array();
$errors = array();

$line_separator = "\r\n";  // RFC-822 http://www.ietf.org/rfc/rfc0822.txt

$headers  = array();
$headers[]= 'MIME-Version: 1.0';
$headers[]= 'Content-type: text/plain; charset=iso-8859-1';
$headers[]= 'X-Mailer: PHP/'.phpversion();
//$headers[]= 'Content-Transfer-Encoding: base64';


if($_SERVER['REQUEST_METHOD'] === 'POST')
{
	/* check invalid data */
	foreach($fieldsets as $fieldset => $fields){
		foreach($fields as $field => $required){
			$post_field = $fieldset.'_'.$field;

			if(true===$required && empty($_POST[$post_field]))
				$errors []= "Missing content for required field '$post_field'";

			if($field === 'email' && filter_var($_POST[$post_field], FILTER_VALIDATE_EMAIL)===false)
				$errors []= "Invalid value '".$_POST[$post_field]."' for field '$post_field'";
		}
	}

	echo '<pre>RAW POST'."\n";
	var_dump($_POST);
	echo '</pre>';

	if(empty($errors))
	{
		array_walk($_POST, 'prepare_post_data');
		$_POST['body_message'] = wordwrap($_POST['body_message'], 70, $line_separator);

		echo '<pre>PREPARED POST'."\n";
		var_dump($_POST);
		echo '</pre>';

		$headers[]= 'From: '.$_POST['from_email'];
		// $success['imap'] = imap_mail($_POST['to_email'], $_POST['body_subject'], $_POST['body_message'], implode($line_separator, $headers),  'cc_mailer_all@liebrex.net', 'bcc_mailer_all@liebrex.net',  'rpath_mailer_all@liebrex.net');

		$headers[]= 'Reply-To: '.$_POST['from_email'];
		$headers[]= "Bcc: bcc_headers_mailer_all@liebrex.net";
		//
		$success['mail'] = mail($_POST['to_email'], $_POST['body_subject'], $_POST['body_message'], implode($line_separator, $headers));

		echo '<pre>HEADERS'."\n";
		var_dump($headers);
		echo '</pre>';

//		var_dump(mail('sammy.dieleman@gmail.com', 'body_subject', 'body_message', $headers));
	}
}

function prepare_post_data(&$data, $key)
{
	$data = trim(stripslashes($data));
}


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

<style type="text/css">
	body{background-color:#E0E0E0; color:#0E0E0E;}
	body *{font-family:Courier;}

	fieldset, input { border:0; margin-bottom:2em; width:32em; position:relative; left:50%; margin-left:-16em;}
	fieldset legend{padding:0; font-size:1.9em;}

	label{display:none;}
	label:after{content: ":";}

	textarea{background-color:transparent; border:0; width:100%; margin:0.5em; height:3em; padding:0.5em;}
	textarea.large{height:15em;}
	textarea.required{border:1px solid red; background-color:#FFF;}
</style>
</head>

<body>
<?
if(!empty($errors))
	print_r($errors);

print_r('success: ');
print_r($success);

?>
<form method="POST" action="mailer_all.php">
<?
$field_line = "\n".'<label for="%s">%s</label><textarea name="%s" id="%s" rows="1" class="%s">%s</textarea>';
$fieldset_line = '<fieldset id="%s"><legend>%s</legend>';
foreach($fieldsets as $fieldset => $fields)
{
	printf($fieldset_line, $fieldset, $fieldset);

	foreach($fields as $field => $required){
		$fieldname = $fieldset.'_'.$field;
		$field_value = empty($_POST[$fieldname])? $field : $_POST[$fieldname];
		$css_class = $required === true? 'required' : '';

		if($field === 'message')
			$css_class .= ' large';

		printf($field_line, $fieldname, $fieldname, $fieldname, $fieldname, $css_class, $field_value);
	}
	printf('</fieldset>');
}
?>
<input type="submit" name="submit" value="send" />
</form>

<pre>
email format examples (RFC2822):
	user@example.com
	user@example.com, anotheruser@example.com
	User <user@example.com>
	User <user@example.com>, Another User <anotheruser@example.com>

subject format (RFC2047):
</pre>

</body>
</html>
