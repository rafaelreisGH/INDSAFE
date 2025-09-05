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
	$mail->Subject = 'Link de acesso - INDSAFE';
	$mail->Body = '<h1>Link de acesso inicial no INDSAFE</h1>
    <br><p>Nesta mensagem se encontra o link para realizar o acesso ao sistema.</p><p>Senha:&nbsp;' . $aux . '
    </p><br><br>';
	$mail->AltBody = 'Link de acesso: ' . $aux;

	$mail->send();

	// Log de sucesso: registra o envio bem-sucedido e os detalhes do destinatário
	$logger->info('E-mail enviado com sucesso.', [
		'destinatario_nome' => $destinatario_nome,
		'destinatario_email' => $destinatario_email
	]);
} catch (Exception $e) {
	// Log de erro: registra a falha no envio com a mensagem de erro detalhada
	$logger->error('Falha ao enviar e-mail.', [
		'destinatario_nome' => $destinatario_nome,
		'destinatario_email' => $destinatario_email,
		'erro' => $mail->ErrorInfo,
		'mensagem_excecao' => $e->getMessage()
	]);
	echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
