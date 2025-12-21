<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [
            // Notifications
            [
                'key' => 'notify_new_order',
                'value' => 'true',
                'group' => 'notifications',
                'type' => 'boolean',
                'description' => 'Recevoir une notification pour chaque nouvelle commande',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'notify_low_stock',
                'value' => 'true',
                'group' => 'notifications',
                'type' => 'boolean',
                'description' => 'Être alerté lorsqu\'un article passe sous le seuil minimum',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'notify_daily_reports',
                'value' => 'false',
                'group' => 'notifications',
                'type' => 'boolean',
                'description' => 'Recevoir un CV des ventes chaque jour',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'notify_sounds',
                'value' => 'true',
                'group' => 'notifications',
                'type' => 'boolean',
                'description' => 'Activer les sons pour les alertes importantes',
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Security
            [
                'key' => 'auth_two_factor',
                'value' => 'false',
                'group' => 'security',
                'type' => 'boolean',
                'description' => 'Ajouter une couche de sécurité supplémentaire',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'session_auto_logout',
                'value' => 'true',
                'group' => 'security',
                'type' => 'boolean',
                'description' => 'Se déconnecter après 30 minutes d\'inactivité',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'activity_logging',
                'value' => 'true',
                'group' => 'security',
                'type' => 'boolean',
                'description' => 'Enregistrer toutes les actions des utilisateurs',
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Appearance
            [
                'key' => 'dark_mode',
                'value' => 'false',
                'group' => 'appearance',
                'type' => 'boolean',
                'description' => 'Basculer entre les thèmes clair et sombre',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'animations_enabled',
                'value' => 'true',
                'group' => 'appearance',
                'type' => 'boolean',
                'description' => 'Activer les animations de l\'interface',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'compact_mode',
                'value' => 'false',
                'group' => 'appearance',
                'type' => 'boolean',
                'description' => 'Réduire l\'espacement pour afficher plus d\'informations',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $keys = [
            'notify_new_order',
            'notify_low_stock',
            'notify_daily_reports',
            'notify_sounds',
            'auth_two_factor',
            'session_auto_logout',
            'activity_logging',
            'dark_mode',
            'animations_enabled',
            'compact_mode',
        ];

        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};
