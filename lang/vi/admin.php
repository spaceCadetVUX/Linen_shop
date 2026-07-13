<?php

return [

    'nav' => [
        'catalog' => 'Sản phẩm',
        'commerce' => 'Kinh doanh',
        'blog' => 'Blog',
        'content' => 'Nội dung',
        'seo_geo' => 'SEO & GEO',
        'setting' => 'Cài đặt',
        'system' => 'Hệ thống',
    ],

    'user' => [
        'label' => 'Người dùng',
        'plural_label' => 'Người dùng',
        'fields' => [
            'name' => 'Tên',
            'email' => 'Email',
            'role' => 'Vai trò',
            'password' => 'Mật khẩu',
            'password_edit_hint' => 'Mật khẩu mới (để trống = giữ nguyên)',
            'tokens' => 'Tokens',
            'joined' => 'Ngày tham gia',
        ],
        'roles' => [
            'admin' => 'Admin',
            'manager' => 'Manager',
            'customer' => 'Khách hàng',
        ],
        'actions' => [
            'new' => 'Người dùng mới',
            'delete_revoke' => 'Xóa & Thu hồi token',
            'delete_selected' => 'Xóa đã chọn',
        ],
        'notifications' => [
            'cannot_delete_self' => 'Không thể xóa tài khoản của chính mình',
            'deleted' => 'Đã xóa user — toàn bộ token bị thu hồi',
        ],
    ],

];
