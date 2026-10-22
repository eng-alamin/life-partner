<?php

declare(strict_types=1);

namespace App\Enums;

/** Permission names stored by spatie/laravel-permission. */
enum StaffPermission: string
{
    case AccessAdmin = 'admin.access';
    case ReviewBiodata = 'biodata.review';
    case HandleReports = 'reports.handle';
    case ManagePayments = 'payments.manage';
    case ManagePlans = 'plans.manage';
    case ManagePages = 'pages.manage';
    case ManageUsers = 'users.manage';
    case ManageSettings = 'settings.manage';
    case ReviewIdentity = 'identity.review';
}
