<?php
use App\Security\AdminArea;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
return static function(RoutingConfigurator $routes): void {
    $routes->add('2fa_login',AdminArea::PATH.'/verify')->controller('scheb_two_factor.form_controller::form')->methods(['GET']);
    $routes->add('2fa_login_check',AdminArea::PATH.'/verify/check')->methods(['POST']);
};
