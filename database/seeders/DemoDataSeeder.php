<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private const IMAGE_POOL = [
        '/images/hero.jpg',
        '/images/chatgpt-image.png',
        '/images/aboutus.png',
        '/images/logo.png',
    ];

    public function run(): void
    {
        $brokerEmails = [
            'broker1@baylot.local',
            'broker2@baylot.local',
            'broker3@baylot.local',
            'broker4@baylot.local',
        ];

        $agentEmails = [
            'agent1@baylot.local',
            'agent2@baylot.local',
            'agent3@baylot.local',
            'agent4@baylot.local',
            'agent5@baylot.local',
            'agent6@baylot.local',
            'agent7@baylot.local',
            'agent8@baylot.local',
            'agent9@baylot.local',
            'agent10@baylot.local',
            'agent11@baylot.local',
            'agent12@baylot.local',
        ];

        $brokers = User::query()
            ->where('role', 'broker')
            ->whereIn('email', $brokerEmails)
            ->get()
            ->keyBy('email');

        $agents = User::query()
            ->where('role', 'agent')
            ->whereIn('email', $agentEmails)
            ->with('broker')
            ->get()
            ->keyBy('email');

        if ($brokers->count() !== count($brokerEmails) || $agents->count() !== count($agentEmails)) {
            return;
        }

        $fallbackBrokerId = $brokers->first()?->id;

        if ($fallbackBrokerId) {
            Project::query()
                ->whereNull('broker_id')
                ->update([
                    'broker_id' => $fallbackBrokerId,
                    'created_by_admin_id' => null,
                    'updated_at' => now(),
                ]);
        }

        $projectSeeds = $this->seedProjects($brokers, $agents);

        if ($projectSeeds === []) {
            return;
        }

        $this->seedSharedListings($projectSeeds);
        $this->seedExclusiveListings($agents);
        $this->markFeaturedListings();
    }

    private function markFeaturedListings(): void
    {
        Listing::query()->update(['is_featured' => false]);

        $featuredIds = collect();

        $sharedBaseQuery = Listing::query()
            ->publiclyVisible()
            ->where('listing_type', 'shared')
            ->whereHas('project.broker', fn ($query) => $query->where('role', 'broker')->where('is_active', true));

        $exclusiveBaseQuery = Listing::query()
            ->publiclyVisible()
            ->where('listing_type', 'exclusive')
            ->whereHas('owner', fn ($query) => $query->where('role', 'agent')->where('is_active', true));

        foreach (['lot', 'house_and_lot', 'condo'] as $propertyType) {
            $sharedId = (clone $sharedBaseQuery)
                ->where('property_type', $propertyType)
                ->orderBy('created_at')
                ->value('id');

            $exclusiveId = (clone $exclusiveBaseQuery)
                ->where('property_type', $propertyType)
                ->orderBy('created_at')
                ->value('id');

            if ($sharedId) {
                $featuredIds->push($sharedId);
            }

            if ($exclusiveId) {
                $featuredIds->push($exclusiveId);
            }
        }

        $fallbackIds = Listing::query()
            ->publiclyVisible()
            ->orderByDesc('updated_at')
            ->pluck('id')
            ->reject(fn ($id) => $featuredIds->contains($id))
            ->take(4);

        $finalIds = $featuredIds
            ->merge($fallbackIds)
            ->unique()
            ->take(10)
            ->values();

        if ($finalIds->isNotEmpty()) {
            Listing::query()->whereIn('id', $finalIds->all())->update(['is_featured' => true]);
        }
    }

    private function seedProjects(Collection $brokers, Collection $agents): array
    {
        $projectDefinitions = [
            [
                'name' => 'Bacolod Green Residences',
                'broker_email' => 'broker1@baylot.local',
                'agent_emails' => ['agent1@baylot.local', 'agent2@baylot.local', 'agent3@baylot.local'],
                'city' => 'Bacolod City',
                'district' => 'Brgy. Tangub',
                'access' => 'Mansilingan-Tangub arterial road',
                'lat' => 10.6845,
                'lng' => 123.0421,
            ],
            [
                'name' => 'Talisay Garden Estates',
                'broker_email' => 'broker1@baylot.local',
                'agent_emails' => ['agent1@baylot.local', 'agent2@baylot.local'],
                'city' => 'Talisay City',
                'district' => 'Brgy. Dos Hermanas',
                'access' => 'Talisay-Silay highway corridor',
                'lat' => 10.7412,
                'lng' => 123.0128,
            ],
            [
                'name' => 'Silay Sunrise Homes',
                'broker_email' => 'broker2@baylot.local',
                'agent_emails' => ['agent4@baylot.local', 'agent5@baylot.local', 'agent6@baylot.local'],
                'city' => 'Silay City',
                'district' => 'Brgy. E. Lopez',
                'access' => 'airport access road and bypass links',
                'lat' => 10.7967,
                'lng' => 122.9749,
            ],
            [
                'name' => 'Victorias Heights Community',
                'broker_email' => 'broker2@baylot.local',
                'agent_emails' => ['agent4@baylot.local', 'agent5@baylot.local'],
                'city' => 'Victorias City',
                'district' => 'Brgy. XIII',
                'access' => 'National Highway (north corridor)',
                'lat' => 10.9015,
                'lng' => 123.0712,
            ],
            [
                'name' => 'Bago Riverbend Villas',
                'broker_email' => 'broker3@baylot.local',
                'agent_emails' => ['agent7@baylot.local', 'agent8@baylot.local', 'agent9@baylot.local'],
                'city' => 'Bago City',
                'district' => 'Brgy. Ma-ao',
                'access' => 'Bago-Ma-ao connector road',
                'lat' => 10.5362,
                'lng' => 122.8421,
            ],
            [
                'name' => 'San Carlos Seaview Residences',
                'broker_email' => 'broker3@baylot.local',
                'agent_emails' => ['agent7@baylot.local', 'agent9@baylot.local'],
                'city' => 'San Carlos City',
                'district' => 'Brgy. Rizal',
                'access' => 'San Carlos north coastal route',
                'lat' => 10.4958,
                'lng' => 123.4241,
            ],
            [
                'name' => 'Kabankalan Southgate Meadows',
                'broker_email' => 'broker4@baylot.local',
                'agent_emails' => ['agent10@baylot.local', 'agent11@baylot.local', 'agent12@baylot.local'],
                'city' => 'Kabankalan City',
                'district' => 'Brgy. Hilamonan',
                'access' => 'Kabankalan circumferential road',
                'lat' => 9.9948,
                'lng' => 122.8162,
            ],
            [
                'name' => 'Himamaylan Highlands Park',
                'broker_email' => 'broker4@baylot.local',
                'agent_emails' => ['agent10@baylot.local', 'agent11@baylot.local'],
                'city' => 'Himamaylan City',
                'district' => 'Brgy. To-oy',
                'access' => 'Himamaylan national road',
                'lat' => 10.1064,
                'lng' => 122.8727,
            ],
        ];

        $seededProjects = [];

        foreach ($projectDefinitions as $definition) {
            $broker = $brokers->get($definition['broker_email']);

            if (! $broker) {
                continue;
            }

            $project = Project::query()->updateOrCreate(
                ['name' => $definition['name']],
                [
                    'description' => "Broker-managed mixed inventory in {$definition['district']}, {$definition['city']}, Negros Occidental. Access is supported by {$definition['access']} and nearby schools, markets, and civic centers.",
                    'broker_id' => $broker->id,
                    'created_by_admin_id' => null,
                ]
            );

            $agentIds = collect($definition['agent_emails'])
                ->map(fn (string $email) => $agents->get($email)?->id)
                ->filter()
                ->values()
                ->all();

            if ($agentIds !== []) {
                $project->agents()->sync($agentIds);
            }

            $seededProjects[] = [
                'project' => $project,
                'city' => $definition['city'],
                'district' => $definition['district'],
                'access' => $definition['access'],
                'lat' => (float) $definition['lat'],
                'lng' => (float) $definition['lng'],
            ];
        }

        return $seededProjects;
    }

    private function seedSharedListings(array $projectSeeds): void
    {
        foreach ($projectSeeds as $index => $seed) {
            $project = $seed['project'];
            $city = $seed['city'];
            $district = $seed['district'];
            $access = $seed['access'];
            $baseLat = (float) $seed['lat'];
            $baseLng = (float) $seed['lng'];

            $statusTownhouse = $index % 3 === 0 ? 'reserved' : 'available';
            $statusCondo = $index % 2 === 0 ? 'available' : 'sold';
            $statusPremiumLot = $index % 2 === 0 ? 'unavailable' : 'available';

            $lotAreaA = 112 + ($index * 6);
            $lotAreaAMax = $lotAreaA + 52;
            $pricePerSqmA = 8200 + ($index * 250);

            $lotAreaD = 176 + ($index * 8);
            $lotAreaDMax = $lotAreaD + 74;
            $pricePerSqmD = 9800 + ($index * 300);

            $housePrice = 3200000 + ($index * 260000);
            $housePriceMax = $housePrice + 1450000;
            $houseLotArea = 86 + ($index * 3);
            $houseLotAreaMax = $houseLotArea + 28;
            $houseFloorArea = 68 + ($index * 2);

            $condoPrice = 2300000 + ($index * 190000);
            $condoPriceMax = $condoPrice + 1650000;
            $condoFloorArea = 32 + ($index * 1.5);

            $this->upsertListing([
                'title' => "{$project->name} Lot Series A".($index + 1),
                'listing_type' => 'shared',
                'property_type' => 'lot',
                'project_id' => $project->id,
                'owner_agent_id' => null,
                'description' => $this->buildSharedDescription($project->name, $city, $district, $access, 'lot'),
                'price' => round($lotAreaA * $pricePerSqmA, 2),
                'price_max' => round($lotAreaAMax * $pricePerSqmA, 2),
                'lot_area_sqm' => $lotAreaA,
                'lot_area_max_sqm' => $lotAreaAMax,
                'floor_area_sqm' => null,
                'price_per_sqm' => $pricePerSqmA,
                'bedrooms' => null,
                'bedrooms_max' => null,
                'bathrooms' => null,
                'bathrooms_max' => null,
                'floors' => null,
                'floors_max' => null,
                'available_units' => $this->unitsForStatus('available', 18 + ($index % 5)),
                'status' => 'available',
                'lat' => $baseLat + 0.0012,
                'lng' => $baseLng + 0.0010,
            ]);

            $this->upsertListing([
                'title' => "{$project->name} Townhouse Cluster B".($index + 1),
                'listing_type' => 'shared',
                'property_type' => 'house_and_lot',
                'project_id' => $project->id,
                'owner_agent_id' => null,
                'description' => $this->buildSharedDescription($project->name, $city, $district, $access, 'house_and_lot'),
                'price' => $housePrice,
                'price_max' => $housePriceMax,
                'lot_area_sqm' => $houseLotArea,
                'lot_area_max_sqm' => $houseLotAreaMax,
                'floor_area_sqm' => $houseFloorArea,
                'price_per_sqm' => null,
                'bedrooms' => 2,
                'bedrooms_max' => 4,
                'bathrooms' => 1,
                'bathrooms_max' => 3,
                'floors' => 2,
                'floors_max' => 2,
                'available_units' => $this->unitsForStatus($statusTownhouse, 10 + ($index % 4), 2),
                'status' => $statusTownhouse,
                'lat' => $baseLat - 0.0011,
                'lng' => $baseLng + 0.0016,
            ]);

            $this->upsertListing([
                'title' => "{$project->name} Condo Suites C".($index + 1),
                'listing_type' => 'shared',
                'property_type' => 'condo',
                'project_id' => $project->id,
                'owner_agent_id' => null,
                'description' => $this->buildSharedDescription($project->name, $city, $district, $access, 'condo'),
                'price' => $condoPrice,
                'price_max' => $condoPriceMax,
                'lot_area_sqm' => null,
                'lot_area_max_sqm' => null,
                'floor_area_sqm' => $condoFloorArea,
                'price_per_sqm' => null,
                'bedrooms' => 1,
                'bedrooms_max' => 3,
                'bathrooms' => 1,
                'bathrooms_max' => 2,
                'floors' => null,
                'floors_max' => null,
                'available_units' => $this->unitsForStatus($statusCondo, 22 + ($index % 6), 4),
                'status' => $statusCondo,
                'lat' => $baseLat + 0.0004,
                'lng' => $baseLng - 0.0019,
            ]);

            $this->upsertListing([
                'title' => "{$project->name} Premier Corner Lots D".($index + 1),
                'listing_type' => 'shared',
                'property_type' => 'lot',
                'project_id' => $project->id,
                'owner_agent_id' => null,
                'description' => $this->buildSharedDescription($project->name, $city, $district, $access, 'lot'),
                'price' => round($lotAreaD * $pricePerSqmD, 2),
                'price_max' => round($lotAreaDMax * $pricePerSqmD, 2),
                'lot_area_sqm' => $lotAreaD,
                'lot_area_max_sqm' => $lotAreaDMax,
                'floor_area_sqm' => null,
                'price_per_sqm' => $pricePerSqmD,
                'bedrooms' => null,
                'bedrooms_max' => null,
                'bathrooms' => null,
                'bathrooms_max' => null,
                'floors' => null,
                'floors_max' => null,
                'available_units' => $this->unitsForStatus($statusPremiumLot, 7 + ($index % 3), 1),
                'status' => $statusPremiumLot,
                'lat' => $baseLat - 0.0018,
                'lng' => $baseLng - 0.0009,
            ]);
        }
    }

    private function seedExclusiveListings(Collection $agents): void
    {
        $agentLocationProfiles = [
            'agent1@baylot.local' => ['city' => 'Bacolod City', 'district' => 'Brgy. Mandalagan', 'lat' => 10.7012, 'lng' => 122.9631],
            'agent2@baylot.local' => ['city' => 'Bacolod City', 'district' => 'Brgy. Alijis', 'lat' => 10.6572, 'lng' => 122.9542],
            'agent3@baylot.local' => ['city' => 'Bacolod City', 'district' => 'Brgy. Villamonte', 'lat' => 10.6765, 'lng' => 122.9529],
            'agent4@baylot.local' => ['city' => 'Silay City', 'district' => 'Brgy. Guimbala-on', 'lat' => 10.8121, 'lng' => 122.9692],
            'agent5@baylot.local' => ['city' => 'Silay City', 'district' => 'Brgy. Rizal', 'lat' => 10.7868, 'lng' => 122.9703],
            'agent6@baylot.local' => ['city' => 'Victorias City', 'district' => 'Brgy. XI', 'lat' => 10.8992, 'lng' => 123.0674],
            'agent7@baylot.local' => ['city' => 'Bago City', 'district' => 'Brgy. Napoles', 'lat' => 10.5425, 'lng' => 122.8404],
            'agent8@baylot.local' => ['city' => 'San Carlos City', 'district' => 'Brgy. Palampas', 'lat' => 10.4901, 'lng' => 123.4162],
            'agent9@baylot.local' => ['city' => 'San Carlos City', 'district' => 'Brgy. Quezon', 'lat' => 10.5058, 'lng' => 123.4287],
            'agent10@baylot.local' => ['city' => 'Kabankalan City', 'district' => 'Brgy. Daan Banwa', 'lat' => 9.9964, 'lng' => 122.8151],
            'agent11@baylot.local' => ['city' => 'Himamaylan City', 'district' => 'Brgy. Carabalan', 'lat' => 10.1128, 'lng' => 122.8784],
            'agent12@baylot.local' => ['city' => 'Himamaylan City', 'district' => 'Brgy. Aguisan', 'lat' => 10.0978, 'lng' => 122.8664],
        ];

        $mixedStatuses = ['reserved', 'sold', 'unavailable'];

        $seedIndex = 0;

        foreach ($agents as $agent) {
            if (! $agent->is_active) {
                continue;
            }

            $profile = $agentLocationProfiles[$agent->email] ?? null;

            if (! $profile) {
                continue;
            }

            $mixedStatus = $mixedStatuses[$seedIndex % count($mixedStatuses)];

            $lotArea = 96 + (($seedIndex % 5) * 11);
            $pricePerSqm = 9300 + ($seedIndex * 210);

            $housePrice = 2850000 + ($seedIndex * 240000);
            $houseLotArea = 82 + ($seedIndex * 2);
            $houseFloorArea = 66 + ($seedIndex * 2);

            $condoPrice = 2180000 + ($seedIndex * 175000);
            $condoFloorArea = 29 + ($seedIndex * 1.2);

            $this->upsertListing([
                'title' => "Exclusive Lot in {$profile['district']}, {$profile['city']} - {$agent->name}",
                'listing_type' => 'exclusive',
                'property_type' => 'lot',
                'project_id' => null,
                'owner_agent_id' => $agent->id,
                'description' => $this->buildExclusiveDescription($agent, $profile['city'], $profile['district'], 'lot'),
                'price' => round($lotArea * $pricePerSqm, 2),
                'price_max' => null,
                'lot_area_sqm' => $lotArea,
                'lot_area_max_sqm' => null,
                'floor_area_sqm' => null,
                'price_per_sqm' => $pricePerSqm,
                'bedrooms' => null,
                'bedrooms_max' => null,
                'bathrooms' => null,
                'bathrooms_max' => null,
                'floors' => null,
                'floors_max' => null,
                'available_units' => null,
                'status' => 'available',
                'lat' => (float) $profile['lat'] + 0.0008,
                'lng' => (float) $profile['lng'] + 0.0011,
            ]);

            $this->upsertListing([
                'title' => "Exclusive House and Lot in {$profile['city']} - {$agent->name}",
                'listing_type' => 'exclusive',
                'property_type' => 'house_and_lot',
                'project_id' => null,
                'owner_agent_id' => $agent->id,
                'description' => $this->buildExclusiveDescription($agent, $profile['city'], $profile['district'], 'house_and_lot'),
                'price' => $housePrice,
                'price_max' => null,
                'lot_area_sqm' => $houseLotArea,
                'lot_area_max_sqm' => null,
                'floor_area_sqm' => $houseFloorArea,
                'price_per_sqm' => null,
                'bedrooms' => 3,
                'bedrooms_max' => null,
                'bathrooms' => 2,
                'bathrooms_max' => null,
                'floors' => 2,
                'floors_max' => null,
                'available_units' => null,
                'status' => 'available',
                'lat' => (float) $profile['lat'] - 0.0010,
                'lng' => (float) $profile['lng'] + 0.0016,
            ]);

            $this->upsertListing([
                'title' => "Exclusive Condo in {$profile['city']} - {$agent->name}",
                'listing_type' => 'exclusive',
                'property_type' => 'condo',
                'project_id' => null,
                'owner_agent_id' => $agent->id,
                'description' => $this->buildExclusiveDescription($agent, $profile['city'], $profile['district'], 'condo'),
                'price' => $condoPrice,
                'price_max' => null,
                'lot_area_sqm' => null,
                'lot_area_max_sqm' => null,
                'floor_area_sqm' => $condoFloorArea,
                'price_per_sqm' => null,
                'bedrooms' => 1,
                'bedrooms_max' => null,
                'bathrooms' => 1,
                'bathrooms_max' => null,
                'floors' => null,
                'floors_max' => null,
                'available_units' => null,
                'status' => $mixedStatus,
                'lat' => (float) $profile['lat'] + 0.0015,
                'lng' => (float) $profile['lng'] - 0.0009,
            ]);

            $seedIndex++;
        }
    }

    private function upsertListing(array $data): Listing
    {
        $existing = Listing::query()->where('title', $data['title'])->first();

        $bindings = [
            $data['listing_type'],
            $data['property_type'],
            $data['project_id'],
            $data['owner_agent_id'],
            $data['title'],
            $data['description'],
            $data['price'],
            $data['price_max'],
            $data['lot_area_sqm'],
            $data['lot_area_max_sqm'],
            $data['floor_area_sqm'],
            $data['price_per_sqm'],
            $data['bedrooms'],
            $data['bedrooms_max'],
            $data['bathrooms'],
            $data['bathrooms_max'],
            $data['floors'],
            $data['floors_max'],
            $data['available_units'],
            $data['status'],
            $data['lng'],
            $data['lat'],
        ];

        if ($existing) {
            DB::update(
                'UPDATE listings
                 SET listing_type = ?,
                     property_type = ?,
                     project_id = ?,
                     owner_agent_id = ?,
                     title = ?,
                     description = ?,
                     price = ?,
                     price_max = ?,
                     lot_area_sqm = ?,
                     lot_area_max_sqm = ?,
                     floor_area_sqm = ?,
                     price_per_sqm = ?,
                     bedrooms = ?,
                     bedrooms_max = ?,
                     bathrooms = ?,
                     bathrooms_max = ?,
                     floors = ?,
                     floors_max = ?,
                     available_units = ?,
                     status = ?,
                     location = ST_SetSRID(ST_MakePoint(?, ?), 4326),
                     updated_at = NOW()
                 WHERE id = ?',
                [...$bindings, $existing->id]
            );

            $listing = Listing::query()->findOrFail($existing->id);
        } else {
            $listingId = (string) Str::uuid();

            DB::insert(
                'INSERT INTO listings (
                    id,
                    listing_type,
                    property_type,
                    project_id,
                    owner_agent_id,
                    title,
                    description,
                    price,
                    price_max,
                    lot_area_sqm,
                    lot_area_max_sqm,
                    floor_area_sqm,
                    price_per_sqm,
                    bedrooms,
                    bedrooms_max,
                    bathrooms,
                    bathrooms_max,
                    floors,
                    floors_max,
                    available_units,
                    status,
                    location,
                    created_at,
                    updated_at
                 ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ST_SetSRID(ST_MakePoint(?, ?), 4326), NOW(), NOW()
                 )',
                [$listingId, ...$bindings]
            );

            $listing = Listing::query()->findOrFail($listingId);
        }

        $this->syncListingImages($listing, $this->imageUrlsForSeed($listing->title));

        return $listing;
    }

    private function syncListingImages(Listing $listing, array $imageUrls): void
    {
        $listing->images()->delete();

        foreach ($imageUrls as $index => $imageUrl) {
            $listing->images()->create([
                'image_url' => $imageUrl,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function imageUrlsForSeed(string $seedKey): array
    {
        $pool = self::IMAGE_POOL;
        $poolCount = count($pool);
        $offset = abs(crc32($seedKey)) % $poolCount;

        return [
            $pool[$offset],
            $pool[($offset + 1) % $poolCount],
            $pool[($offset + 2) % $poolCount],
        ];
    }

    private function unitsForStatus(string $status, int $availableUnits, int $reservedUnits = 2): int
    {
        return match ($status) {
            'available' => $availableUnits,
            'reserved' => $reservedUnits,
            default => 0,
        };
    }

    private function buildSharedDescription(string $projectName, string $city, string $district, string $access, string $propertyType): string
    {
        $propertyLabel = ucwords(str_replace('_', ' ', $propertyType));

        $focus = match ($propertyType) {
            'lot' => 'This inventory suits end-users planning custom homes and long-term land banking in growth corridors.',
            'house_and_lot' => 'The layout is designed for practical family living with balanced indoor space and future expansion options.',
            'condo' => 'The unit mix is ideal for professionals, OFW families, and investors looking for rental-ready addresses.',
            default => 'This listing offers a balanced value proposition for home seekers and investors.',
        };

        return implode("\n\n", [
            "{$projectName} is a broker-managed community in {$district}, {$city}, Negros Occidental.",
            "This {$propertyLabel} listing offers a competitive price point within a professionally managed project portfolio. {$focus}",
            'Amenities include a clubhouse, swimming pool, fitness gym, landscaped gardens, children\'s playground, and 24/7 gated security with CCTV monitoring.',
            "Access is supported by {$access}, with nearby schools, wet markets, transport terminals, churches, and local business centers.",
        ]);
    }

    private function buildExclusiveDescription(User $agent, string $city, string $district, string $propertyType): string
    {
        $propertyLabel = ucwords(str_replace('_', ' ', $propertyType));
        $brokerage = $agent->broker?->name ?? 'an affiliated brokerage team';

        $focus = match ($propertyType) {
            'lot' => 'Ideal for buyers who want flexibility in design and staged construction timelines.',
            'house_and_lot' => 'Prepared for households needing immediate livability with practical lot and floor area proportions.',
            'condo' => 'Suitable for city-based professionals and buyers prioritizing low-maintenance urban residences.',
            default => 'Positioned for buyers seeking strong value in established neighborhoods.',
        };

        return implode("\n\n", [
            "This exclusive {$propertyLabel} listing is represented by {$agent->name} under {$brokerage}.",
            "Located in {$district}, {$city}, Negros Occidental, the property provides access to schools, hospitals, groceries, and commuter routes.",
            'Nearby amenities and facilities include gated street access, neighborhood security presence, parks, and convenience retail strips.',
            $focus,
        ]);
    }
}
