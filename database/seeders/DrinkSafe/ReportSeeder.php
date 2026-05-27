<?php

declare(strict_types=1);

namespace Database\Seeders\DrinkSafe;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ReportSeeder extends Seeder
{
    public function run(): void
    {
        // Get all venue UUIDs
        $venueUuids = DB::table('venues')->pluck('uuid')->toArray();

        if (empty($venueUuids)) {
            $this->command->warn('No venues found. Please run VenueSeeder first.');

            return;
        }

        $timeOfDayOptions = ['Morning', 'Afternoon', 'Evening', 'Night', 'Unknown'];

        $reports = [];
        $numReports = 200;

        for ($i = 0; $i < $numReports; $i++) {
            // Random date within past 6 months
            $incidentDate = now()
                ->subDays(fake()->numberBetween(1, 180))
                ->toDateString();

            // Random time of day
            $timeOfDay = fake()->randomElement($timeOfDayOptions);

            // Generate realistic incident description
            $description = $this->generateRealisticDescription();

            // Random created_at (after incident date but before now)
            $createdAt = now()
                ->subDays(fake()->numberBetween(0, 180))
                ->subHours(fake()->numberBetween(0, 23));

            $reports[] = [
                'uuid' => Str::uuid()->toString(),
                'venue_uuid' => fake()->randomElement($venueUuids),
                'incident_date' => $incidentDate,
                'time_of_day' => $timeOfDay,
                'description' => $description,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
                'deleted_at' => null,
            ];
        }

        DB::table('reports')->insert($reports);

        $this->command->info(sprintf('Seeded %d reports', count($reports)));
    }

    private function generateRealisticDescription(): string
    {
        $templates = [
            'I was at this venue on %s and witnessed suspicious behaviour. Someone appeared to be tampering with drinks when patrons were not looking. The staff were notified but seemed unconcerned.',
            'Visited this establishment and observed what seemed like drink spiking activity. A person was seen adding something to an unattended beverage. Security was informed immediately.',
            'Had a concerning experience where my drink tasted unusual after leaving it unattended for a moment. Felt unwell shortly after. Please be careful at this location.',
            'Saw someone acting suspiciously near the bar area, hovering around drinks. When confronted, they quickly left. Staff should be more vigilant.',
            'Friend experienced symptoms consistent with drink tampering after visiting this venue. We reported it to management and the police. Please stay alert.',
            'The security at this venue is lacking. Witnessed multiple instances of unattended drinks being approached by strangers. Very concerning.',
            'I felt dizzy and disoriented after having just one drink here. My friends had to help me leave. Something was definitely wrong with my beverage.',
            'Someone tried to hand me a drink I did not order. When I refused, they became defensive. The situation felt very unsafe.',
            'Observed a group watching people and their drinks closely. When staff were alerted, they took no action. Not a safe environment.',
            'My drink was left unattended briefly and afterwards tasted bitter. Started feeling ill within minutes. Be extremely careful here.',
        ];

        $template = fake()->randomElement($templates);

        // Some descriptions mention the day of week
        if (str_contains($template, '%s')) {
            $dayOfWeek = fake()->dayOfWeek();

            return sprintf($template, $dayOfWeek);
        }

        return $template;
    }
}
