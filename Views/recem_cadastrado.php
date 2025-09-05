<?php
include_once("../helpers/header_basico.php");
$erro = isset($_GET['erro']) ? $_GET['erro'] : 0;
?>

<body>

    <div class="container">

        <main class="container-fluid my-4 d-flex flex-column align-items-center">
            <div class="col-md-6 p-3 mx-auto">
                <!-- Main component for a primary marketing message or call to action -->
                <div class="jumbotron">
                    <h2>Cadastro realizado com sucesso!</h2>
                    <p>Aguarde a validação pelo Administrador.</p>
                </div>
            </div>

            <div id="alerts-container" class="mt-3 col-md-6"></div>

            <div id="resultados-container" class="mt-4 col-md-10">
            </div>

        </main>
    </div>

</body>
