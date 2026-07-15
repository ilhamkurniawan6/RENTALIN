<?php

function rentalin_user_has_role($user, array $allowedRoles): bool
{
    if (!is_array($user)) {
        return false;
    }

    $role = isset($user['role']) ? strtolower((string) $user['role']) : '';
    if ($role === '') {
        return false;
    }

    if ($role === 'super_admin') {
        return in_array('super_admin', $allowedRoles, true) || in_array('admin', $allowedRoles, true);
    }

    if ($role === 'admin') {
        return in_array('admin', $allowedRoles, true) || in_array('super_admin', $allowedRoles, true);
    }

    return in_array($role, $allowedRoles, true);
}

function rentalin_is_admin_like($user): bool
{
    return rentalin_user_has_role($user, ['admin', 'super_admin']);
}

function rentalin_is_super_admin($user): bool
{
    return rentalin_user_has_role($user, ['super_admin']);
}
