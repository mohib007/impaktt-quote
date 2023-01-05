<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitf60334e05eaaa9bf2dbbf4972ef36187
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WeDevs\\ERP\\WooCommerce\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WeDevs\\ERP\\WooCommerce\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
    );

    public static $classMap = array (
        'WeDevs\\ERP\\WooCommerce\\Accounting' => __DIR__ . '/../..' . '/includes/accounting.php',
        'WeDevs\\ERP\\WooCommerce\\CLI\\Commands' => __DIR__ . '/../..' . '/includes/cli/commands.php',
        'WeDevs\\ERP\\WooCommerce\\Customer' => __DIR__ . '/../..' . '/includes/customer.php',
        'WeDevs\\ERP\\WooCommerce\\Model\\Order_Product' => __DIR__ . '/../..' . '/includes/model/order_product.php',
        'WeDevs\\ERP\\WooCommerce\\Model\\Product_Order' => __DIR__ . '/../..' . '/includes/model/product_order.php',
        'WeDevs\\ERP\\WooCommerce\\Order' => __DIR__ . '/../..' . '/includes/orders.php',
        'WeDevs\\ERP\\WooCommerce\\Segment' => __DIR__ . '/../..' . '/includes/segment.php',
        'WeDevs\\ERP\\WooCommerce\\WooCommerce_Settings' => __DIR__ . '/../..' . '/includes/settings.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitf60334e05eaaa9bf2dbbf4972ef36187::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitf60334e05eaaa9bf2dbbf4972ef36187::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitf60334e05eaaa9bf2dbbf4972ef36187::$classMap;

        }, null, ClassLoader::class);
    }
}