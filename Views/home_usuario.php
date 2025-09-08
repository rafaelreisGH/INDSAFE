<?php
///Views/busca_por_nome.php
include_once '../helpers/header.php';
include_once '../helpers/header_gestor.php';
?>
<div class="container">
    <h1 class="mt-4">Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome_usuario']); ?>!</h1>
    <p>Esta é a sua página inicial.</p>
    <a href="../sair.php" class="btn btn-outline-danger">Sair do sistema</a>
</div>
<?php
include_once '../helpers/footer.php';
?>