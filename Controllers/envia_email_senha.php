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
	// Server settings obtidos dinamicamente de variáveis de ambiente
	$smtpHost = getenv('MAIL_HOST') ?: ($_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');
	$smtpPort = getenv('MAIL_PORT') ?: ($_ENV['MAIL_PORT'] ?? 587);
	$smtpUser = getenv('MAIL_USERNAME') ?: ($_ENV['MAIL_USERNAME'] ?? '');
	$smtpPass = getenv('MAIL_PASSWORD') ?: ($_ENV['MAIL_PASSWORD'] ?? '');
	$fromEmail = getenv('MAIL_FROM_ADDRESS') ?: ($_ENV['MAIL_FROM_ADDRESS'] ?? $smtpUser);
	$fromName = getenv('MAIL_FROM_NAME') ?: ($_ENV['MAIL_FROM_NAME'] ?? 'INDSAFE - Admin');

	$mail->isSMTP();
	$mail->Host       = $smtpHost;
	$mail->SMTPAuth   = !empty($smtpUser);
	$mail->Username   = $smtpUser;
	$mail->Password   = $smtpPass;
	$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
	$mail->Port       = (int) $smtpPort;
	$mail->SMTPOptions = array(
		'ssl' => array(
			'verify_peer' => false,
			'verify_peer_name' => false,
			'allow_self_signed' => true
		)
	);

	// Recipients
	$mail->setFrom($fromEmail, $fromName);
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
