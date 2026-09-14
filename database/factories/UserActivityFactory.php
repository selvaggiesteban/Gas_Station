<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserActivityFactory extends Factory
{
    protected $model = UserActivity::class;

    public function definition(): array
    {
        $types = ['view', 'submit', 'download', 'heartbeat'];
        $type = $this->faker->randomElement($types);
        
        return [
            'user_id' => User::factory(),
            'session_id' => $this->faker->uuid,
            'url' => $this->faker->url,
            'route_name' => $this->faker->randomElement(['temas.ver', 'examenes.presentar', 'dashboard', 'cursos.ver']),
            'method' => $this->faker->randomElement(['GET', 'POST']),
            'action_type' => $type,
            'ip_address' => $this->faker->ipv4,
            'device_info' => [
                'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                'platform' => $this->faker->randomElement(['Windows', 'macOS', 'iOS', 'Android']),
                'device' => $this->faker->randomElement(['Desktop', 'Mobile', 'Tablet']),
                'is_mobile' => $this->faker->boolean,
            ],
            'time_spent' => $type === 'heartbeat' ? 30 : 0,
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
