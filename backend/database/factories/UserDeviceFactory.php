<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserDevice>
 */
class UserDeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $deviceTypes = ['desktop', 'mobile', 'tablet'];
        $browsers = ['Chrome', 'Firefox', 'Safari', 'Edge'];
        $operatingSystems = ['Windows', 'macOS', 'Linux', 'iOS', 'Android'];

        return [
            'user_id' => User::factory(),
            'device_fingerprint' => hash('sha256', fake()->uuid),
            'device_name' => null,
            'device_type' => fake()->randomElement($deviceTypes),
            'browser_name' => fake()->randomElement($browsers),
            'browser_version' => fake()->randomFloat(1, 90, 120),
            'os_name' => fake()->randomElement($operatingSystems),
            'os_version' => fake()->randomFloat(1, 10, 15),
            'is_trusted' => fake()->boolean(30), // 30% chance of being trusted
            'trusted_at' => function (array $attributes) {
                return $attributes['is_trusted'] ? fake()->dateTimeBetween('-30 days', 'now') : null;
            },
            'last_seen_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'last_ip_address' => fake()->ipv4,
            'last_location' => fake()->optional()->city,
            'login_count' => fake()->numberBetween(1, 50),
            'fingerprint_data' => [
                'user_agent' => fake()->userAgent,
                'headers' => [
                    'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'accept_language' => 'en-US,en;q=0.9',
                    'accept_encoding' => 'gzip, deflate, br',
                ],
                'device_name' => fake()->optional()->randomElement(['iPhone', 'Samsung Galaxy', 'MacBook Pro']),
                'languages' => ['en-US', 'en'],
            ],
            'metadata' => [
                'created_via' => 'login',
                'initial_location' => fake()->city,
            ],
        ];
    }

    /**
     * Indicate that the device is trusted.
     */
    public function trusted(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'is_trusted' => true,
                'trusted_at' => fake()->dateTimeBetween('-30 days', 'now'),
            ],
        );
    }

    /**
     * Indicate that the device is not trusted.
     */
    public function untrusted(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'is_trusted' => false,
                'trusted_at' => null,
            ],
        );
    }

    /**
     * Indicate that the device is a mobile device.
     */
    public function mobile(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'device_type' => 'mobile',
                'browser_name' => fake()->randomElement([
                    'Chrome Mobile',
                    'Safari Mobile',
                    'Firefox Mobile',
                ]),
                'os_name' => fake()->randomElement(['iOS', 'Android']),
            ],
        );
    }

    /**
     * Indicate that the device is a desktop device.
     */
    public function desktop(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'device_type' => 'desktop',
                'browser_name' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                'os_name' => fake()->randomElement(['Windows', 'macOS', 'Linux']),
            ],
        );
    }
}
