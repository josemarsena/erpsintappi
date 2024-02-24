<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('authentication/includes/head.php'); ?>

<body>

<div>
    <?php $this->load->view('authentication/includes/alerts'); ?>

    <?php echo form_open($this->uri->uri_string()); ?>

    <?php echo validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>

    <?php hooks()->do_action('after_admin_login_form_start'); ?>

        <div class="login-form-container">

            <div class="form-head">Login</div>
            <?php
            if (isset($_SESSION["errorMessage"])) {
                ?>
                <div class="error-message"><?php  echo $_SESSION["errorMessage"]; ?></div>
                <?php
                unset($_SESSION["errorMessage"]);
            }
            ?>

            <div class="field-column">
                <div>
                    <label for="email">Nome do Usuário</label><span id="user_info"
                                                                       class="error-info"></span>
                </div>
                <div>
                    <input name="email" id="email" type="email" autofocus="1"
                           class="demo-input-box" placeholder="Digite seu email">
                </div>
            </div>
            <div class="field-column">
                <div>
                    <label for="password">Senha</label><span id="password_info"
                                                             class="error-info"></span>
                </div>
                <div>
                    <input name="password" id="password" type="password"
                           class="demo-input-box" placeholder="Digite sua senha">
                </div>
            </div>
            <div class=field-column>
                <div>
                    <input type="submit" name="login" value="Login" class="btnLogin"></span>
                </div>
            </div>
            <div class="form-nav-row">
                <a href="#" class="form-link">Esqueceu a senha?</a>
            </div>
        </div>
    <?php echo form_close(); ?>
</div>

</body>

</html>