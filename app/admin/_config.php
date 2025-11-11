<?php

$operationStatusColors = [
    0 => "#fbffda",
    1 => "#af415f4d",
    2 => "#57af414d",
];


$adminMenuHeader = [
    'Работа с продуктами' => [
        'Список категорий' => 'admin_products/index',
        'Атрибуты' => 'admin_products/attributes',
        'Массовые переводы товаров' => 'admin_products/grouped_product_translation',
    ],
    'Финансы' => [
        'Список оплаченных заказов' => 'admin/orders',
        'Заказы' => 'admin/orders_all',
        'Список пользователей' => 'admin/user_list',
        'Инвойсы и банковские операции' => 'admin/invoices_and_bank_operations',
    ],
    'Дополнительные функции' => [
        'Статистика' => 'admin/statistics',        
        'Редактировать переводы' => 'admin/translations',
        'Массовая загрузка картинок' => 'admin/image_import',
        'Информеры' => 'admin/informers',
        'Баннеры' => 'admin/banners',
    ],
];