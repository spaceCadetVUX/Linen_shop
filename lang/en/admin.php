<?php

return [

    'nav' => [
        'catalog' => 'Catalog',
        'commerce' => 'Commerce',
        'blog' => 'Blog',
        'content' => 'Content',
        'seo_geo' => 'SEO & GEO',
        'setting' => 'Setting',
        'system' => 'System',
    ],

    'user' => [
        'label' => 'User',
        'plural_label' => 'Users',
        'fields' => [
            'name' => 'Name',
            'email' => 'Email',
            'role' => 'Role',
            'password' => 'Password',
            'password_edit_hint' => 'New password (leave blank to keep current)',
            'tokens' => 'Tokens',
            'joined' => 'Joined',
        ],
        'roles' => [
            'admin' => 'Admin',
            'manager' => 'Manager',
            'customer' => 'Customer',
        ],
        'actions' => [
            'new' => 'New user',
            'delete_revoke' => 'Delete & Revoke tokens',
            'delete_selected' => 'Delete selected',
        ],
        'notifications' => [
            'cannot_delete_self' => 'You cannot delete your own account',
            'deleted' => 'User deleted — all tokens revoked',
        ],
    ],

];
