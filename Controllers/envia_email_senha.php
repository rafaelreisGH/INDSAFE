<?php

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require '../vendor/autoload.php';

//Create an instance; passing `true` enables exceptions
$mail = new PHPMailer(true);

try {
	//Server settings
	//$mail->SMTPDebug = SMTP::DEBUG_SERVER; //Enable verbose debug output
	$mail->isSMTP(); //Send using SMTP
	$mail->Host       = 'smtp.gmail.com'; //Set the SMTP server to send through
	$mail->SMTPAuth   = true; //Enable SMTP authentication
	$mail->Username   = 'sapcbmmt@gmail.com'; //'bombeirosSAP'                   //SMTP username
	$mail->Password   = 'kjznjqeygdrrzbva'; //SMTP password
	$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; //Enable implicit TLS encryption
	$mail->Port       = 587;
	$mail->SMTPOptions = array(
		'ssl' => array(
			'verify_peer' => false,
			'verify_peer_name' => false,
			'allow_self_signed' => true
		)
	);

	//Recipients
	$mail->setFrom('sapcbmmt@gmail.com', 'Admin');
	$mail->addAddress($aux_email, $aux_nome);

	//Content
	$mail->isHTML(true);
	$mail->Subject = 'Chave de acesso - SAP';
	$mail->Body = '<h1>Chave para acesso inicial no SAP - CBMMT</h1>
    <br><p>Informe a senha provisória encaminhada nesta mensagem para realizar o acesso ao SAP - CBMMT.</p><p>Senha:&nbsp;' . $aux . '
    </p><br><br>';
	$mail->AltBody = 'Chave para acesso inicial no SAP - CBMMT: ' . $aux;

	$mail->send();
} catch (Exception $e) {
	echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
