<?php
/*
 * I wish the milkman would deliver my milk in the morning

 RFC 822 http://www.ietf.org/rfc/rfc0822.txt
 It  is  recommended that,  if  present,  headers be sent in the order
 "Return-Path", "Received", "Date",  "From",  "Subject",  "Sender", "To", "cc", etc.

 */
class Milkman{

  static $line_separator = "\r\n";  // RFC-822 http://www.ietf.org/rfc/rfc0822.txt
  static $real_sender = 'email@liebrex.net'; // must be an existing email adresse (one.com constraint)

    //fieldname           => array('RFC_HEADER', 'UPPERCASE_KEY', 'VALUE')
    //fieldname           => array('RFC_HEADER', 'Form label', 'validation')
    //fieldname           => array(null, 'Form label', 'validation') // not a header

  /*
  // each array KEY matches a {KEY} in rfc0822_header_template.txt 
  // each array value describe the field, using two words separated by underscore
    // first word [required|optional] makes a field required or optional
    // scnd word [email|string|text|datetime] allows content/type based validation
  */
  static $form_fields = array(
    'sender' => 'required_email',
    'subject' => 'required_string',
    'destination' => 'required_email',
    'carbon_copy' => 'optional_email',
    'blind_carbon_copy' => 'optional_email',
    // 'date' => 'optional_string',
    'message' => 'optional_text',
  );


  public static function form($action, $values=null)
  {
    if(is_null($values))
      $values = $_POST;
      
    $submit = "\n".'<button type="submit" name="submit" class="btn btn-primary" value="" />send</button>';
    $form = '';

    foreach(self::$form_fields as $field_name => $form_validation)
    {
      if($field_name === 'date'){
        $dt = new DateTime();
        $form_value = $dt->format(DateTime::RFC822);
      }
      else{
        $form_value = array_key_exists($field_name, $values) ? $values[$field_name] : '';
      }

      $css_class = '';
      if(preg_match('/required/', $form_validation) === 1)
        $css_class .= ' required';
      if(preg_match('/text/', $form_validation) === 1)
        $css_class .= ' large pull-right';

      $form .= "\n".'
        <div class="row">
        <div class="form-group col-sm-6">
          <label for="'.$field_name.'">'.$field_name.'</label>
          <textarea name="'.$field_name.'" id="%'.$field_name.'" rows="1" class="form-control '.$css_class.'">'.$form_value.'</textarea>
        </div>
        </div>';
    }
    return '<form method="post" action="'.$action.'" class="container">'.$form.$submit.'</form>';
  }

  public static function validate()
  {
    $errors = array();
    foreach(self::$form_fields as $field_name => $form_validation)
    {
      $value = trim($_POST[$field_name]);
      if(preg_match('/required/', $form_validation) === 1 && empty($value))
      {
        $errors []= "field '$field_name' is required, but empty '$value'";
      }
      if(!empty($value))
      {
        if(preg_match('/email/', $form_validation) === 1 && filter_var($value, FILTER_VALIDATE_EMAIL)===false)
        {
          $errors []= "field '$field_name' is not a valid email '$value'";
        }
        if(preg_match('/datetime/', $form_validation) === 1)
        {
          // $dt = new DateTime($value);
          // var_dump($dt->format(DateTime::RFC822));
          // $errors []= "field '$field_name' is not a valid email '$value'";
        }
      }
    }
    return $errors;
  }

  public static function rfc_headers()
  {
    return file_get_contents(__DIR__.'/rfc0822_headers_template.txt');
  }
  

  public static function send($data_by_fieldname)
  {
    $rfc_headers = self::rfc_headers();
    
    if(empty($rfc_headers)){
      die('no rfc headers');
    }
    
    $headers = array();
    $message = '';
    foreach(self::$form_fields as $field_name => $form_validation)
    {
      $value = trim(stripslashes($data_by_fieldname[$field_name]));

      if($field_name === 'message')
        $message = wordwrap($value, 70, self::$line_separator);
      else
        $headers['{'.$field_name.'}'] = $value;
    }

    $dt = new DateTime();
    $headers['{date}'] = $dt->format(DateTime::RFC822);    // $dt = new DateTime("now", new DateTimeZone("Europe/Brussels"));
    $headers['{php_version}'] = phpversion();
    $headers['{real_sender}'] = self::$real_sender;

    $header = str_replace(array_keys($headers), array_values($headers), $rfc_headers);

    // $ret = mail($headers['{destination}'], $headers['{subject}'], $message, $header);

    if($ret === false){
      echo '<pre>MAIL ERROR';
      echo "\nheaders"; var_dump($headers);
      echo "\nheader:"; var_dump($header);
      echo '</pre>';
    }

    return $ret;
  }


}

 ?>
