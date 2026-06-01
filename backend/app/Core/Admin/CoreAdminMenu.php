<?php

namespace App\Core\Admin;

class CoreAdminMenu
{
    /**
     * Get the default admin sidebar items.
     *
     * Only includes items that have complete backend implementations.
     * See ADMIN_MENU_AUDIT.md for tracking missing features.
     */
    public static function items(): array
    {
        return [
            [
                'id' => 'user-management',
                'label' => 'User Management',
                'icon' => 'UsersIcon',
                'order' => 1,
                'children' => [
                    [ 'route' => '/users',      'label' => 'Users',               'icon' => 'UsersIcon' ],
                    [ 'route' => '/user-tools', 'label' => 'User Tools',          'icon' => 'WrenchScrewdriverIcon' ],
                    [ 'route' => '/roles',      'label' => 'Roles & Permissions', 'icon' => 'ShieldCheckIcon' ],
                    [ 'route' => '/ip-bans',    'label' => 'IP Bans',             'icon' => 'NoSymbolIcon' ],
                ],
            ],
            [
                'id' => 'configuration',
                'label' => 'Configuration',
                'icon' => 'Cog6ToothIcon',
                'order' => 2,
                'children' => [
                    [ 'route' => '/settings',       'label' => 'Settings',    'icon' => 'Cog6ToothIcon' ],
                    [ 'route' => '/email-settings', 'label' => 'Email',       'icon' => 'EnvelopeIcon' ],
                    [ 'route' => '/plugin-settings','label' => 'Plugins',     'icon' => 'PuzzlePieceIcon' ],
                    [ 'route' => '/license',        'label' => 'License',     'icon' => 'KeyIcon' ],
                ],
            ],
            [
                'id' => 'boxingdb',
                'label' => 'BoxingDB',
                'icon' => 'TrophyIcon',
                'order' => 10,
                'children' => [
                    [ 'route' => '/boxingdb/scraper',          'label' => 'Scraper',          'icon' => 'CloudArrowDownIcon' ],
                    [ 'route' => '/boxingdb/fighters',         'label' => 'Fighters',         'icon' => 'UsersIcon' ],
                    [ 'route' => '/boxingdb/events',           'label' => 'Events',           'icon' => 'CalendarIcon' ],
                    [ 'route' => '/boxingdb/fights',           'label' => 'Fights',           'icon' => 'ClipboardDocumentListIcon' ],
                    [ 'route' => '/boxingdb/promoters',        'label' => 'Promotions',       'icon' => 'MegaphoneIcon' ],
                    [ 'route' => '/boxingdb/venues',           'label' => 'Venues',           'icon' => 'MapPinIcon' ],
                    [ 'route' => '/boxingdb/weight-classes',   'label' => 'Weight Classes',   'icon' => 'ScaleIcon' ],
                    [ 'route' => '/boxingdb/organisations',    'label' => 'Organisations',    'icon' => 'ShieldCheckIcon' ],
                    [ 'route' => '/boxingdb/belts',            'label' => 'Belts',            'icon' => 'TrophyIcon' ],
                    [ 'route' => '/boxingdb/belt-history',     'label' => 'Belt History',     'icon' => 'ClockIcon' ],
                    [ 'route' => '/boxingdb/rankings',         'label' => 'Rankings',         'icon' => 'ChartBarIcon' ],
                    [ 'route' => '/boxingdb/broadcasters',     'label' => 'Broadcasters',     'icon' => 'RadioIcon' ],
                    [ 'route' => '/boxingdb/event-broadcasts', 'label' => 'Event Broadcasts', 'icon' => 'SignalIcon' ],
                    [ 'route' => '/boxingdb/media',            'label' => 'Media & Posters',  'icon' => 'PhotoIcon' ],
                ],
            ],
            // Communication section removed - no backend implementations
            // See ADMIN_MENU_AUDIT.md for tracking
            // Support section removed - no backend implementations
            // See ADMIN_MENU_AUDIT.md for tracking
            [
                'id' => 'system',
                'label' => 'System',
                'icon' => 'ServerIcon',
                'order' => 90,
                'children' => [
                    [ 'route' => '/system-health',  'label' => 'System Health',   'icon' => 'ServerIcon' ],
                    [ 'route' => '/error-logs',     'label' => 'Error Logs',      'icon' => 'ExclamationTriangleIcon' ],
                    [ 'route' => '/activity-logs',  'label' => 'Activity Logs',   'icon' => 'ClipboardDocumentListIcon' ],
                    [ 'route' => '/security',       'label' => 'Security',        'icon' => 'ShieldCheckIcon' ],
                    [ 'route' => '/backups',        'label' => 'Backups',         'icon' => 'CircleStackIcon' ],
                    [ 'route' => '/webhooks',       'label' => 'Webhooks',        'icon' => 'ArrowTopRightOnSquareIcon' ],
                    [ 'route' => '/api-keys',       'label' => 'API Keys',        'icon' => 'KeyIcon' ],
                    [ 'route' => '/notifications',  'label' => 'Notifications',   'icon' => 'BellIcon' ],
                    // Calendar and Tasks removed - no backend implementations
                    // See ADMIN_MENU_AUDIT.md for tracking
                ],
            ],
        ];
    }
}
