<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\StaffPermission;
use App\Models\User;
use App\Services\Admin\WaitingCounts;
use Illuminate\Support\Facades\Route;

/**
 * The menus of the member panel and the admin panel, built in one place.
 *
 * Each menu is a list of sections (the icons of the side rail / bottom dock). A section may hold
 * pages (shown as the second menu). Only pages whose route exists and that the person may open are
 * listed, and a section with no page left is dropped. This is the same rule the old admin menu used;
 * every page still checks its own permission on the server.
 *
 * @phpstan-type Page array{label: string, href: string, active: bool, badge: int}
 * @phpstan-type Section array{key: string, label: string, title: string, icon: string, href: string, active: bool, badge: int, items: list<Page>}
 */
final class PanelMenu
{
    /**
     * Member panel: one page per section.
     *
     * @return list<Section>
     */
    public static function member(): array
    {
        $entries = [
            ['key' => 'home', 'route' => 'dashboard', 'label' => 'nav.home', 'icon' => 'home', 'match' => ['dashboard', 'plans.*', 'billing.*', 'account.*', 'blocks.*', 'notifications.*']],
            ['key' => 'search', 'route' => 'biodatas.index', 'label' => 'nav.search', 'icon' => 'search', 'match' => ['biodatas.*']],
            ['key' => 'interests', 'route' => 'interests.index', 'label' => 'nav.interests', 'icon' => 'mail', 'match' => ['interests.*']],
            ['key' => 'shortlist', 'route' => 'shortlist.index', 'label' => 'nav.shortlist', 'icon' => 'bookmark', 'match' => ['shortlist.*']],
            ['key' => 'my-biodata', 'route' => 'my-biodata', 'label' => 'nav.biodatas', 'icon' => 'id', 'match' => ['my-biodata', 'my-biodata.*']],
        ];

        $sections = [];

        foreach ($entries as $entry) {
            if (! Route::has($entry['route'])) {
                continue;
            }

            $sections[] = [
                'key' => $entry['key'],
                'label' => $entry['label'],
                'title' => $entry['label'],
                'icon' => $entry['icon'],
                'href' => route($entry['route']),
                'active' => request()->routeIs(...$entry['match']),
                'badge' => 0,
                'items' => [],
            ];
        }

        return $sections;
    }

    /**
     * Admin panel: sections with pages, filtered by the person's permissions.
     *
     * @return list<Section>
     */
    public static function admin(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $waiting = app(WaitingCounts::class)->for($user);

        // Several counts can point at one page (payments to review + payments needing attention): add them up.
        $badgeByRoute = [];
        foreach (WaitingCounts::ROUTES as $countKey => $countRoute) {
            $badgeByRoute[$countRoute] = ($badgeByRoute[$countRoute] ?? 0) + ($waiting[$countKey] ?? 0);
        }

        $definition = [
            ['key' => 'home', 'label' => 'nav.home', 'title' => 'nav.dashboard', 'icon' => 'home', 'pages' => [
                ['admin.dashboard', 'nav.dashboard', null],
            ]],
            ['key' => 'review', 'label' => 'panel.sections.review', 'title' => 'panel.titles.review', 'icon' => 'shield', 'pages' => [
                ['admin.approvals', 'nav.approvals', null],
                ['admin.hidden', 'nav.hidden', null],
                ['admin.photos', 'nav.photos', null],
                ['admin.verification', 'nav.verification', StaffPermission::ReviewIdentity],
                ['admin.reports', 'nav.reports', null],
            ]],
            ['key' => 'members', 'label' => 'panel.sections.members', 'title' => 'panel.titles.members', 'icon' => 'users', 'pages' => [
                ['admin.users', 'nav.users', StaffPermission::ManageUsers],
                ['admin.deletions', 'nav.deletions', StaffPermission::ManageUsers],
                ['admin.contact', 'nav.contact_inbox', StaffPermission::HandleContact],
            ]],
            ['key' => 'money', 'label' => 'panel.sections.money', 'title' => 'panel.titles.money', 'icon' => 'wallet', 'pages' => [
                ['admin.payments', 'nav.payments', StaffPermission::ManagePayments],
                ['admin.plans', 'nav.plans', StaffPermission::ManagePlans],
            ]],
            ['key' => 'system', 'label' => 'panel.sections.system', 'title' => 'panel.titles.system', 'icon' => 'sliders', 'pages' => [
                ['admin.content', 'nav.content', StaffPermission::ManagePages],
                ['admin.stories', 'nav.stories_admin', StaffPermission::ManageStories],
                ['admin.activity-log', 'nav.activity_log', StaffPermission::ManageUsers],
                ['admin.settings', 'nav.admin_settings', StaffPermission::ManageSettings],
            ]],
        ];

        $sections = [];

        foreach ($definition as $section) {
            $items = [];

            foreach ($section['pages'] as [$route, $label, $permission]) {
                if (! Route::has($route)) {
                    continue;
                }

                if ($permission !== null && ! $user->can($permission->value)) {
                    continue;
                }

                $items[] = [
                    'label' => $label,
                    'href' => route($route),
                    'active' => request()->routeIs($route, $route.'.*'),
                    'badge' => (int) ($badgeByRoute[$route] ?? 0),
                ];
            }

            if ($items === []) {
                continue;
            }

            $sections[] = [
                'key' => $section['key'],
                'label' => $section['label'],
                'title' => $section['title'],
                'icon' => $section['icon'],
                'href' => $items[0]['href'],
                'active' => in_array(true, array_column($items, 'active'), true),
                'badge' => array_sum(array_column($items, 'badge')),
                'items' => $items,
            ];
        }

        return $sections;
    }
}
