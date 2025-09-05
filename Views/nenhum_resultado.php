<?php
session_start();
include_once("../helpers/header_basico.php");
?>

<div class="container">
    <div class="col-md-12">
        <h4>Atenção!</h4>
        <hr>
        <p>
            <?php
            echo 'Sr(Srª) ';
            echo $_SESSION['posto_grad_usuario'] . ' ' . $_SESSION['nome'] . '.</br>';
            echo '<strong>A última busca não retornou resultado(s).</strong></br></br>';
            ?>
        </p>
        <A class="btn btn-primary" HREF="javascript:javascript:history.go(-1)">Voltar</A>
    </div>
</div>


<?php
include_once '../helpers/footer.php';
?>