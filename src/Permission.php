<?php
namespace Virtual_Optimizer;

class Permission {
    public static function is_allowed() {
        return current_user_can('manage_options');
    }
}
