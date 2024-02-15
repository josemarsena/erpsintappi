<?php
class Splash extends SplashController
{
    /**
     * @since  2.3.3
     */

    public function __construct()
    {
        parent::__construct();

    }

    public function index()
    {
        redirect(admin_url());
    }
}
