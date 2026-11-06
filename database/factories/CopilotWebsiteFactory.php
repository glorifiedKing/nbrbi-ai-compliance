<?php

namespace Database\Factories;

use App\Models\CopilotWebsite;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CopilotWebsite>
 */
class CopilotWebsiteFactory extends Factory
{
    protected $model = CopilotWebsite::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company().' Portal';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->randomNumber(3),
            'domain' => 'https://'.$this->faker->domainName(),
            'welcome_message' => 'Ask our NBRB Statutory AI Copilot about Uganda National Building Code compliance.',
            'is_active' => true,
            'requests_count' => 0,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}

