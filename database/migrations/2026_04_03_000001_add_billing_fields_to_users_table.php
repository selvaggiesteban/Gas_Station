<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add columns one by one to avoid MySQL issues with multiple AFTER clauses
        $columns = [
            'avatar_equipment' => ['type' => 'json', 'after' => 'avatar', 'nullable' => true],
            'read_announcements' => ['type' => 'json', 'after' => 'avatar_equipment', 'nullable' => true],
            'notification_preferences' => ['type' => 'json', 'after' => 'read_announcements', 'nullable' => true],
            'holded_id' => ['type' => 'string', 'after' => 'notification_preferences', 'nullable' => true],
            'nif' => ['type' => 'string', 'after' => 'holded_id', 'nullable' => true],
            'phone' => ['type' => 'string', 'after' => 'nif', 'nullable' => true],
            'address' => ['type' => 'string', 'after' => 'phone', 'nullable' => true],
            'city' => ['type' => 'string', 'after' => 'address', 'nullable' => true],
            'province' => ['type' => 'string', 'after' => 'city', 'nullable' => true],
            'postal_code' => ['type' => 'string', 'after' => 'province', 'nullable' => true],
            'company_name' => ['type' => 'string', 'after' => 'postal_code', 'nullable' => true],
            'stripe_id' => ['type' => 'string', 'after' => 'company_name', 'nullable' => true, 'index' => true],
            'pm_type' => ['type' => 'string', 'after' => 'stripe_id', 'nullable' => true],
            'pm_last_four' => ['type' => 'string:4', 'after' => 'pm_type', 'nullable' => true],
            'trial_ends_at' => ['type' => 'timestamp', 'after' => 'pm_last_four', 'nullable' => true],
        ];

        foreach ($columns as $name => $config) {
            if (!Schema::hasColumn('users', $name)) {
                Schema::table('users', function (Blueprint $table) use ($name, $config) {
                    $column = null;

                    if ($config['type'] === 'json') {
                        $column = $table->json($name);
                    } elseif ($config['type'] === 'string') {
                        $column = $table->string($name);
                    } elseif ($config['type'] === 'string:4') {
                        $column = $table->string($name, 4);
                    } elseif ($config['type'] === 'timestamp') {
                        $column = $table->timestamp($name);
                    }

                    if ($column && isset($config['nullable']) && $config['nullable']) {
                        $column->nullable();
                    }

                    if ($column && isset($config['after'])) {
                        $column->after($config['after']);
                    }

                    if (isset($config['index']) && $config['index']) {
                        $column->index();
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = [
                'avatar_equipment', 'read_announcements', 'notification_preferences',
                'holded_id', 'nif', 'phone', 'address', 'city', 'province', 
                'postal_code', 'company_name', 'stripe_id', 
                'pm_type', 'pm_last_four', 'trial_ends_at'
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
