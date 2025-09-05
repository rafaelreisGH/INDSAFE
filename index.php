<?php
session_start();

// Gera um token CSRF único e o armazena na sessão
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Inicializa as mensagens de erro, se existirem na sessão
$mensagens = [];
if (isset($_SESSION['mensagens'])) {
    $mensagens = $_SESSION['mensagens'];
    unset($_SESSION['mensagens']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>INDSAFE</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.gstatic.com" />
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,wght@0,600;1,600&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,300;0,500;0,600;0,700;1,300;1,500;1,600;1,700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,400;1,400&amp;display=swap" rel="stylesheet" />
    <link href="css/styles_index.css" rel="stylesheet" />
</head>

<body id="page-top">
    <nav class="navbar navbar-expand-lg navbar-light fixed-top shadow-sm" id="mainNav">
        <div class="container px-5">
            <a class="navbar-brand fw-bold" href="#page-top">INDSAFE</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
                Menu
                <i class="bi-list"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto me-4 my-3 my-lg-0">
                    <li class="nav-item"><a class="nav-link me-lg-3" href="#features">Funcionalidades</a></li>
                    <li class="nav-item"><a class="nav-link me-lg-3" href="Views/inscrevase.php">Inscreva-se</a></li>
                </ul>
                <button class="btn btn-primary rounded-pill px-3 mb-2 mb-lg-0" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                    <span class="d-flex align-items-center">
                        <i class="bi-door-open me-2"></i>
                        <span class="small">Acessar sistema</span>
                    </span>
                </button>
            </div>
        </div>
    </nav>
    <header class="masthead">
        <div class="container px-5">
            <div class="row gx-5 align-items-center">
                <div class="col-lg-6">
                    <div class="mb-5 mb-lg-0 text-center text-lg-start">
                        <h1 class="display-1 lh-1 mb-3">Plano de contingência a emergências.</h1>
                        <p class="lead fw-normal text-muted mb-5">Nossa plataforma dispõe de informações auxiliares às operações no Distrito Industrial de Cuiabá.</p>
                        <!-- <div class="d-flex flex-column flex-lg-row align-items-center">
                            <a class="me-lg-3 mb-4 mb-lg-0" href="#!"><img class="app-badge" src="assets/img/google-play-badge.svg" alt="..." /></a>
                            <a href="#!"><img class="app-badge" src="assets/img/app-store-badge.svg" alt="..." /></a>
                        </div> -->
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="masthead-device-mockup">
                        <svg class="circle" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="circleGradient" gradientTransform="rotate(45)">
                                    <stop class="gradient-start-color" offset="0%"></stop>
                                    <stop class="gradient-end-color" offset="100%"></stop>
                                </linearGradient>
                            </defs>
                            <circle cx="50" cy="50" r="50"></circle>
                        </svg>
                        <svg class="shape-1 d-none d-sm-block" viewBox="0 0 240.83 240.83" xmlns="http://www.w3.org/2000/svg">
                            <rect x="-32.54" y="78.39" width="305.92" height="84.05" rx="42.03" transform="translate(120.42 -49.88) rotate(45)"></rect>
                            <rect x="-32.54" y="78.39" width="305.92" height="84.05" rx="42.03" transform="translate(-49.88 120.42) rotate(-45)"></rect>
                        </svg>
                        <svg class="shape-2 d-none d-sm-block" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="50" cy="50" r="50"></circle>
                        </svg>
                        <div class="device-wrapper">
                            <div class="device" data-device="iPhoneX" data-orientation="portrait" data-color="black">
                                <div class="screen bg-black">
                                    <!-- <video muted="muted" autoplay="" loop="" style="max-width: 100%; height: 100%">
                                        <source src="assets/img/demo-screen.mp4" type="video/mp4" />
                                    </video> -->
                                    <img src="assets/img/PrintApp.png" class="img-fluid" alt="..." />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <aside class="text-center bg-gradient-primary-to-secondary">
        <div class="container px-5">
            <div class="row gx-5 justify-content-center">
                <div class="col-xl-8">
                    <div class="h2 fs-1 text-white mb-4">"Uma solução intuitiva para um problema comum que enfrentamos durante um sinistro, disponível em um mesmo lugar!"</div>
                    <!-- <img src="assets/img/tnw-logo.svg" alt="..." style="height: 3rem" /> -->
                </div>
            </div>
        </div>
    </aside>
    <section id="features">
        <div class="container px-5">
            <div class="row gx-5 align-items-center">
                <div class="col-lg-8 order-lg-1 mb-5 mb-lg-0">
                    <div class="container-fluid px-5">
                        <div class="row gx-5">
                            <div class="col-md-6 mb-5">
                                <div class="text-center">
                                    <i class="bi-clipboard-check icon-feature text-gradient d-block mb-3"></i>
                                    <h3 class="font-alt">Informações genuínas</h3>
                                    <p class="text-muted mb-0">Informações fornecidas pelos próprios associados da AEDIC</p>
                                </div>
                            </div>
                            <div class="col-md-6 mb-5">
                                <div class="text-center">
                                    <i class="bi-arrow-clockwise icon-feature text-gradient d-block mb-3"></i>
                                    <h3 class="font-alt">Uso flexível</h3>
                                    <p class="text-muted mb-0">Através de qualquer dispositivo com acesso à internet.</p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-5 mb-md-0">
                                <div class="text-center">
                                    <i class="bi-lock icon-feature text-gradient d-block mb-3"></i>
                                    <h3 class="font-alt">Seguro</h3>
                                    <p class="text-muted mb-0">Informações protegidas em nossos banco de dados.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-center">
                                    <i class="bi-people-fill icon-feature text-gradient d-block mb-3"></i>
                                    <h3 class="font-alt">Colaborativo</h3>
                                    <p class="text-muted mb-0">AEDIC e Corpo de Bombeiros Militar de Mato Grosso trabalhando em parceria.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 order-lg-0">
                    <div class="features-device-mockup">
                        <svg class="circle" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="circleGradient" gradientTransform="rotate(45)">
                                    <stop class="gradient-start-color" offset="0%"></stop>
                                    <stop class="gradient-end-color" offset="100%"></stop>
                                </linearGradient>
                            </defs>
                            <circle cx="50" cy="50" r="50"></circle>
                        </svg><svg class="shape-1 d-none d-sm-block" viewBox="0 0 240.83 240.83" xmlns="http://www.w3.org/2000/svg">
                            <rect x="-32.54" y="78.39" width="305.92" height="84.05" rx="42.03" transform="translate(120.42 -49.88) rotate(45)"></rect>
                            <rect x="-32.54" y="78.39" width="305.92" height="84.05" rx="42.03" transform="translate(-49.88 120.42) rotate(-45)"></rect>
                        </svg><svg class="shape-2 d-none d-sm-block" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="50" cy="50" r="50"></circle>
                        </svg>
                        <div class="device-wrapper">
                            <div class="device" data-device="iPhoneX" data-orientation="portrait" data-color="black">
                                <div class="screen bg-black">
                                    <!-- <video muted="muted" autoplay="" loop="" style="max-width: 100%; height: 100%">
                                        <source src="assets/img/demo-screen.mp4" type="video/mp4" />
                                    </video> -->
                                    <img src="assets/img/PrintApp2.png" class="img-fluid" alt="..." />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="bg-light">
        <div class="container px-5">
            <div class="row gx-5 align-items-center justify-content-center justify-content-lg-between">
                <div class="col-12 col-lg-5">
                    <h2 class="display-4 lh-1 mb-4">Seja um parceiro da segurança</h2>
                    <p class="lead fw-normal text-muted mb-5 mb-lg-0">Solicite o acesso ao sistema. Atualize as informações do seu estabelecimento.</p>
                </div>
                <div class="col-sm-8 col-md-6">
                    <div class="px-5 px-sm-0">
                        <img class="img-fluid rounded-circle" style="width: 10vw;" src="/img/Vetor CBMMT.png" alt="..." />

                        <img class="img-fluid" style="width: 10vw;" src="/assets/img/aedic.png" alt="..." />
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="cta">
        <div class="cta-content">
            <div class="container px-5">
                <h2 class="text-white display-1 lh-1 mb-4">
                    Não espere.
                    <br />
                    Comece agora.
                </h2>
                <a class="btn btn-outline-light py-3 px-4 rounded-pill" href="/Views/inscrevase.php" target="_blank">Registre-se</a>
            </div>
        </div>
    </section>
    <!-- <section class="bg-gradient-primary-to-secondary" id="download">
        <div class="container px-5">
            <h2 class="text-center text-white font-alt mb-4">Get the app now!</h2>
            <div class="d-flex flex-column flex-lg-row align-items-center justify-content-center">
                <a class="me-lg-3 mb-4 mb-lg-0" href="#!"><img class="app-badge" src="assets/img/google-play-badge.svg" alt="..." /></a>
                <a href="#!"><img class="app-badge" src="assets/img/app-store-badge.svg" alt="..." /></a>
            </div>
        </div>
    </section> -->
    <footer class="bg-black text-center py-5">
        <div class="container px-5">
            <div class="text-white-50 small">
                <div class="mb-2">&copy; STIC/DAI/CBMMT 2025. Todos os direitos reservados.</div>
                <a href="#!">Privacidade</a>
                <span class="mx-1">&middot;</span>
                <a href="#!">Termos e condições</a>
                <span class="mx-1">&middot;</span>
                <a href="#!">FAQ</a>
            </div>
        </div>
    </footer>
    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary-to-secondary p-4">
                    <h5 class="modal-title font-alt text-white" id="feedbackModalLabel">Acesso Restrito</h5>
                    <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body border-0 p-4">
                    <form id="loginForm" action="Controllers/validar_acesso.php" method="POST">
                        <?php
                        // Exibe as mensagens de erro, se houver
                        if (!empty($mensagens)) {
                            echo '<div class="alert alert-danger" role="alert">';
                            foreach ($mensagens as $mensagem) {
                                echo "<p class='mb-0'>$mensagem</p>";
                            }
                            echo '</div>';
                        }
                        ?>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <div class="form-floating mb-3">
                            <input class="form-control" id="loginUsername" name="cpf" type="text" placeholder="Insira seu CPF (somente números)" required pattern="\d{11}" title="Informe o CPF com 11 dígitos, sem pontos ou traços." />
                            <label for="loginUsername">CPF</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input class="form-control" id="loginPassword" name="senha" type="password" placeholder="Insira sua senha" required />
                            <label for="loginPassword">Senha</label>
                        </div>
                        <div class="d-grid">
                            <button class="btn btn-primary rounded-pill btn-lg" id="submitLogin" type="submit">Login</button>
                        </div>
                        <!-- Link "Esqueci minha senha" -->
                        <div class="text-center mt-3">
                            <a href="/Views/esqueci_senha.php">Esqueci minha senha</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdn.startbootstrap.com/sb-forms-latest.js"></script>
</body>

</html>