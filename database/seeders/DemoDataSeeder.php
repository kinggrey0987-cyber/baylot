<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $brokerOne = User::where('email', 'broker1@baylot.local')->first();
        $brokerTwo = User::where('email', 'broker2@baylot.local')->first();
        $agentOne = User::where('email', 'agent1@baylot.local')->first();
        $agentTwo = User::where('email', 'agent2@baylot.local')->first();
        $agentThree = User::where('email', 'agent3@baylot.local')->first();

        if (! $brokerOne || ! $brokerTwo || ! $agentOne || ! $agentTwo || ! $agentThree) {
            return;
        }

        $greenResidences = Project::firstOrCreate(
            ['name' => 'Bacolod Green Residences'],
            [
                'description' => 'Broker-owned shared subdivision inventory in Bacolod.',
                'broker_id' => $brokerOne->id,
                'created_by_admin_id' => null,
            ]
        );

        $sunriseHomes = Project::firstOrCreate(
            ['name' => 'Silay Sunrise Homes'],
            [
                'description' => 'Broker-managed residential inventory in Silay.',
                'broker_id' => $brokerTwo->id,
                'created_by_admin_id' => null,
            ]
        );

        $greenResidences->update([
            'broker_id' => $brokerOne->id,
            'created_by_admin_id' => null,
        ]);

        $sunriseHomes->update([
            'broker_id' => $brokerTwo->id,
            'created_by_admin_id' => null,
        ]);

        $greenResidences->agents()->syncWithoutDetaching([$agentOne->id, $agentTwo->id]);
        $sunriseHomes->agents()->syncWithoutDetaching([$agentThree->id]);

        $this->insertSharedListingIfMissing(
            project: $greenResidences,
            title: 'Lot 12 Block 3 - Bacolod Green',
            description: 'Broker-owned shared listing for queue testing.',
            propertyType: 'lot',
            price: 1200000,
            lat: 10.6800,
            lng: 123.0600,
        );

        $this->insertSharedListingIfMissing(
            project: $sunriseHomes,
            title: '2BR House and Lot - Silay Sunrise',
            description: 'Shared house and lot unit inventory under broker operations.',
            propertyType: 'house_and_lot',
            price: 2850000,
            lat: 10.7900,
            lng: 122.9600,
        );

        $this->insertExclusiveListingIfMissing(
            ownerAgent: $agentOne,
            title: 'Exclusive Condo Unit - Bacolod North',
            description: 'Exclusive listing posted directly by Agent One.',
            propertyType: 'condo',
            price: 3400000,
            lat: 10.6900,
            lng: 122.9500,
        );
    }

    private function insertSharedListingIfMissing(Project $project, string $title, string $description, string $propertyType, float $price, float $lat, float $lng): void
    {
        $existing = Listing::where('title', $title)->first();

        if ($existing) {
            return;
        }

        DB::statement(
            'INSERT INTO listings (id, listing_type, property_type, project_id, owner_agent_id, title, description, price, status, location, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326), NOW(), NOW())',
            [
                (string) Str::uuid(),
                'shared',
                $propertyType,
                $project->id,
                null,
                $title,
                $description,
                $price,
                'available',
                $lng,
                $lat,
            ]
        );
    }

    private function insertExclusiveListingIfMissing(User $ownerAgent, string $title, string $description, string $propertyType, float $price, float $lat, float $lng): void
    {
        $existing = Listing::where('title', $title)->first();

        if ($existing) {
            return;
        }

        DB::statement(
            'INSERT INTO listings (id, listing_type, property_type, project_id, owner_agent_id, title, description, price, status, location, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326), NOW(), NOW())',
            [
                (string) Str::uuid(),
                'exclusive',
                $propertyType,
                null,
                $ownerAgent->id,
                $title,
                $description,
                $price,
                'available',
                $lng,
                $lat,
            ]
        );
    }
}
