<?php

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\IssueType;
use App\Models\ItemType;
use App\Models\TechnicalPersonnel;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed deterministic configuration and explicitly named initial accounts.
     * Existing legacy assets, inspections, and personnel are not removed.
     */
    public function run(): void
    {
        $adminEmail = mb_strtolower(trim((string) env('INITIAL_ADMIN_EMAIL', 'mudassir@logreport.test')));
        $resourceDefinitions = [
            ['name' => 'Afaq', 'email' => 'afaq@logreport.test', 'designation' => 'Senior Hardware Technician', 'specialization' => 'Computer Hardware', 'role' => UserRole::TechnicalResource],
            ['name' => 'Haris', 'email' => 'haris@logreport.test', 'designation' => 'Network Technician', 'specialization' => 'Networking', 'role' => UserRole::TechnicalResource],
            ['name' => 'Obaid', 'email' => 'obaid@logreport.test', 'designation' => 'IT Support Technician', 'specialization' => 'Software and Hardware Support', 'role' => UserRole::TechnicalResource],
            ['name' => 'Zulfiqar', 'email' => 'zulfiqar@logreport.test', 'designation' => 'Printers and Peripherals Technician', 'specialization' => 'Printers and Peripherals', 'role' => UserRole::TechnicalResource],
            ['name' => 'Mudassir', 'email' => $adminEmail, 'designation' => 'IT Department Head', 'specialization' => 'Field and Projector Support', 'role' => UserRole::Admin],
        ];

        $adminPassword = $this->passwordFromEnvironment('INITIAL_ADMIN_PASSWORD');
        $resourcePassword = $this->passwordFromEnvironment('INITIAL_RESOURCE_PASSWORD');
        $adminExists = User::query()->where('email', $adminEmail)->exists();
        $missingResources = collect($resourceDefinitions)
            ->reject(fn (array $definition): bool => $definition['email'] === $adminEmail || User::query()->where('email', $definition['email'])->exists())
            ->all();

        if (! $adminExists && $adminPassword === null) {
            throw new RuntimeException('Set INITIAL_ADMIN_PASSWORD before seeding the initial administrator. No default password is used.');
        }

        if ($missingResources !== [] && $resourcePassword === null) {
            throw new RuntimeException('Set INITIAL_RESOURCE_PASSWORD before seeding missing technical-resource users. No default password is used.');
        }

        $departments = $this->seedDepartments();
        $this->seedConfiguration();

        $this->seedUser(
            email: $adminEmail,
            attributes: [
                'name' => 'Mudassir',
                'department_id' => $departments['IT']->getKey(),
                'designation' => 'IT Department Head',
                'territory' => 'Head Office',
                'status' => RecordStatus::Active,
                'role' => UserRole::Admin,
            ],
            password: $adminPassword,
        );

        foreach ($resourceDefinitions as $definition) {
            $this->seedUser(
                email: $definition['email'],
                attributes: [
                    'name' => $definition['name'],
                    'department_id' => $departments['IT']->getKey(),
                    'designation' => $definition['designation'],
                    'territory' => 'Head Office',
                    'status' => RecordStatus::Active,
                    'role' => $definition['email'] === $adminEmail ? UserRole::Admin : $definition['role'],
                ],
                password: $definition['email'] === $adminEmail ? $adminPassword : $resourcePassword,
            );

            TechnicalPersonnel::query()->updateOrCreate(
                ['name' => $definition['name']],
                [
                    'department_id' => $departments['IT']->getKey(),
                    'email' => $definition['email'],
                    'designation' => $definition['designation'],
                    'specialization' => $definition['specialization'],
                    'status' => RecordStatus::Active,
                ],
            );
        }
    }

    /**
     * @return Collection<string, Department>
     */
    private function seedDepartments(): Collection
    {
        return collect([
            ['name' => 'Information Technology', 'code' => 'IT', 'description' => 'Technology infrastructure and support.'],
            ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'People and workplace operations.'],
            ['name' => 'Sales', 'code' => 'SALES', 'description' => 'Sales and customer operations.'],
            ['name' => 'SupplyChain', 'code' => 'SUPPLY', 'description' => 'Supply chain and logistics operations.'],
            ['name' => 'CEO of the Company', 'code' => 'CEO', 'description' => 'Executive office requests.'],
            ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Finance and accounts operations.'],
            ['name' => 'Operations', 'code' => 'OPS', 'description' => 'Business and field operations.'],
            ['name' => 'Other departments', 'code' => 'OTHER', 'description' => 'Requests from teams not otherwise listed.'],
        ])->mapWithKeys(function (array $attributes): array {
            $department = Department::query()->updateOrCreate(
                ['name' => $attributes['name']],
                [
                    'code' => $attributes['code'],
                    'description' => $attributes['description'],
                    'status' => RecordStatus::Active,
                ],
            );

            return [$attributes['code'] => $department];
        });
    }

    private function seedConfiguration(): void
    {
        foreach ([
            ['name' => 'Hardware', 'description' => 'Hardware faults, damage, or replacement requirements.'],
            ['name' => 'Software', 'description' => 'Application, operating system, or installation issues.'],
            ['name' => 'Network', 'description' => 'Network, internet, Wi-Fi, or connectivity issues.'],
            ['name' => 'Printer Support', 'description' => 'Printer and printing-device support.'],
            ['name' => 'Projector / Display', 'description' => 'Projector, display, and presentation-equipment support.'],
            ['name' => 'Access Request', 'description' => 'Account, access, and permission requests.'],
            ['name' => 'Other', 'description' => 'Other IT support requests.'],
        ] as $attributes) {
            IssueType::query()->updateOrCreate(
                ['name' => $attributes['name']],
                [
                    'description' => $attributes['description'],
                    'status' => RecordStatus::Active,
                ],
            );
        }

        foreach ([
            ['name' => 'Laptop', 'description' => 'Laptop computer.'],
            ['name' => 'Computer', 'description' => 'Desktop or workstation computer.'],
            ['name' => 'Printer', 'description' => 'Printer or multifunction device.'],
            ['name' => 'Projector', 'description' => 'Projector or display equipment.'],
            ['name' => 'Network Equipment', 'description' => 'Router, switch, access point, or similar equipment.'],
            ['name' => 'Software', 'description' => 'Software or application item.'],
            ['name' => 'Peripheral', 'description' => 'Keyboard, mouse, monitor, or other peripheral.'],
            ['name' => 'Other IT Equipment', 'description' => 'Other IT equipment not listed above.'],
        ] as $attributes) {
            ItemType::query()->updateOrCreate(
                ['name' => $attributes['name']],
                [
                    'description' => $attributes['description'],
                    'status' => RecordStatus::Active,
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function seedUser(string $email, array $attributes, ?string $password): void
    {
        $values = [
            ...$attributes,
            'email' => $email,
        ];

        if ($password !== null) {
            $values['password'] = Hash::make($password);
        }

        $user = User::query()->updateOrCreate(['email' => $email], $values);
        $user->forceFill(['email_verified_at' => now()])->save();
    }

    private function passwordFromEnvironment(string $variable): ?string
    {
        $password = env($variable);

        if (! is_string($password) || trim($password) === '') {
            return null;
        }

        if (strlen($password) < 12) {
            throw new RuntimeException("{$variable} must contain at least 12 characters.");
        }

        return $password;
    }
}
