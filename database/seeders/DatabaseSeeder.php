<?php

namespace Database\Seeders;

use App\Enums\AssetType;
use App\Enums\InspectionCategory;
use App\Enums\InspectionStatus;
use App\Enums\InspectionSubCategory;
use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Department;
use App\Models\Inspection;
use App\Models\TechnicalPersonnel;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed deterministic application data.
     */
    public function run(): void
    {
        $departments = collect([
            ['name' => 'Information Technology', 'code' => 'IT', 'description' => 'Technology infrastructure and support.'],
            ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Finance and accounts operations.'],
            ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'People and workplace operations.'],
            ['name' => 'Operations', 'code' => 'OPS', 'description' => 'Business and field operations.'],
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

        $users = collect([
            ['name' => 'System Administrator', 'email' => 'admin@logreport.test', 'department' => 'IT', 'designation' => 'System Administrator', 'territory' => 'Head Office', 'role' => UserRole::Admin],
            ['name' => 'Technical User', 'email' => 'technician@logreport.test', 'department' => 'IT', 'designation' => 'IT Technician', 'territory' => 'Lahore', 'role' => UserRole::Technician],
            ['name' => 'Sample User', 'email' => 'user@logreport.test', 'department' => 'FIN', 'designation' => 'Accounts Officer', 'territory' => 'Karachi', 'role' => UserRole::User],
            ['name' => 'Operations User', 'email' => 'operations@logreport.test', 'department' => 'OPS', 'designation' => 'Operations Manager', 'territory' => 'Islamabad', 'role' => UserRole::User],
        ])->mapWithKeys(function (array $attributes) use ($departments): array {
            $user = User::query()->updateOrCreate(
                ['email' => $attributes['email']],
                [
                    'name' => $attributes['name'],
                    'password' => 'password',
                    'department_id' => $departments[$attributes['department']]->getKey(),
                    'designation' => $attributes['designation'],
                    'territory' => $attributes['territory'],
                    'status' => RecordStatus::Active,
                    'role' => $attributes['role'],
                ],
            );

            return [$attributes['email'] => $user];
        });

        $personnel = collect([
            ['name' => 'Afaq', 'email' => 'afaq@logreport.test', 'designation' => 'Senior Hardware Technician', 'specialization' => 'Computer Hardware'],
            ['name' => 'Waseem', 'email' => 'waseem@logreport.test', 'designation' => 'Network Technician', 'specialization' => 'Networking'],
            ['name' => 'Obaid', 'email' => 'obaid@logreport.test', 'designation' => 'IT Support Technician', 'specialization' => 'Software and Hardware Support'],
            ['name' => 'Zulfiqar', 'email' => 'zulfiqar@logreport.test', 'designation' => 'Printers and peripherals Technician', 'specialization' => 'Printers and Peripherals'],
            ['name' => 'Mudassar', 'email' => 'mudassar@logreport.test', 'designation' => 'Field Support Technician', 'specialization' => 'Field and Projector Support'],
        ])->mapWithKeys(function (array $attributes) use ($departments): array {
            $person = TechnicalPersonnel::query()->updateOrCreate(
                ['name' => $attributes['name']],
                [
                    'department_id' => $departments['IT']->getKey(),
                    'email' => $attributes['email'],
                    'phone' => '+92-300-0000000',
                    'designation' => $attributes['designation'],
                    'specialization' => $attributes['specialization'],
                    'status' => RecordStatus::Active,
                ],
            );

            return [$attributes['name'] => $person];
        });

        $assets = collect([
            ['tag' => 'AST-1001', 'user' => 'user@logreport.test', 'type' => AssetType::Laptop, 'brand' => 'Dell', 'model' => 'Latitude 5440', 'serial' => 'DL5440-1001', 'ram' => '16 GB', 'ram_gb' => 16, 'storage' => '512 GB SSD', 'acquired_at' => '2026-01-12'],
            ['tag' => 'AST-1002', 'user' => 'user@logreport.test', 'type' => AssetType::Computer, 'brand' => 'HP', 'model' => 'ProDesk 400', 'serial' => 'HP400-1002', 'ram' => '8 GB', 'ram_gb' => 8, 'storage' => '1 TB HDD', 'acquired_at' => '2025-11-03'],
            ['tag' => 'AST-1003', 'user' => 'operations@logreport.test', 'type' => AssetType::Laptop, 'brand' => 'Lenovo', 'model' => 'ThinkPad E14', 'serial' => 'LNE14-1003', 'ram' => '16 GB', 'ram_gb' => 16, 'storage' => '512 GB SSD', 'acquired_at' => '2026-03-19'],
            ['tag' => 'AST-1004', 'user' => 'operations@logreport.test', 'type' => AssetType::Printer, 'brand' => 'Canon', 'model' => 'LBP2900', 'serial' => 'CN2900-1004', 'ram' => null, 'ram_gb' => null, 'storage' => null, 'acquired_at' => '2024-08-22'],
            ['tag' => 'AST-1005', 'user' => 'technician@logreport.test', 'type' => AssetType::Laptop, 'brand' => 'Acer', 'model' => 'TravelMate P2', 'serial' => 'ACERP2-1005', 'ram' => '8 GB', 'ram_gb' => 8, 'storage' => '256 GB SSD', 'acquired_at' => '2026-06-05'],
            ['tag' => 'AST-1006', 'user' => 'user@logreport.test', 'type' => AssetType::Projector, 'brand' => 'Epson', 'model' => 'EB-FH52', 'serial' => 'EPFH52-1006', 'ram' => null, 'ram_gb' => null, 'storage' => null, 'acquired_at' => '2025-02-14'],
            ['tag' => 'AST-1007', 'user' => 'operations@logreport.test', 'type' => AssetType::ItSupportEquipment, 'brand' => 'Logitech', 'model' => 'MK270', 'serial' => 'LTMK270-1007', 'ram' => null, 'ram_gb' => null, 'storage' => null, 'acquired_at' => '2025-09-30'],
            ['tag' => 'AST-1008', 'user' => 'technician@logreport.test', 'type' => AssetType::Computer, 'brand' => 'Dell', 'model' => 'OptiPlex 3000', 'serial' => 'DL3000-1008', 'ram' => '32 GB', 'ram_gb' => 32, 'storage' => '1 TB SSD', 'acquired_at' => '2026-07-11'],
        ])->mapWithKeys(function (array $attributes) use ($users): array {
            $asset = Asset::query()->updateOrCreate(
                ['asset_tag' => $attributes['tag']],
                [
                    'user_id' => $users[$attributes['user']]->getKey(),
                    'type' => $attributes['type'],
                    'brand' => $attributes['brand'],
                    'model' => $attributes['model'],
                    'serial_number' => $attributes['serial'],
                    'ram' => $attributes['ram'],
                    'ram_gb' => $attributes['ram_gb'],
                    'storage' => $attributes['storage'],
                    'acquired_at' => $attributes['acquired_at'],
                ],
            );

            return [$attributes['tag'] => $asset];
        });

        $inspections = [
            ['problem' => 'PRB-2026-001', 'asset' => 'AST-1001', 'status' => InspectionStatus::Sold, 'category' => InspectionCategory::NewPurchase, 'sub_category' => null, 'personnel' => 'Afaq', 'date' => '2026-09-23', 'remarks' => 'New purchase registered and assigned.'],
            ['problem' => 'PRB-2026-002', 'asset' => 'AST-1002', 'status' => InspectionStatus::IndoorRepair, 'category' => InspectionCategory::Repair, 'sub_category' => InspectionSubCategory::InHouse, 'personnel' => 'Obaid', 'date' => '2026-09-23', 'remarks' => 'Hard drive replacement required.'],
            ['problem' => 'PRB-2026-003', 'asset' => 'AST-1003', 'status' => InspectionStatus::InProgress, 'category' => InspectionCategory::Repair, 'sub_category' => InspectionSubCategory::InHouse, 'personnel' => 'Waseem', 'date' => '2026-09-22', 'remarks' => 'Network connectivity investigation.'],
            ['problem' => 'PRB-2026-004', 'asset' => 'AST-1004', 'status' => InspectionStatus::OutdoorRepair, 'category' => InspectionCategory::Repair, 'sub_category' => InspectionSubCategory::OutHouse, 'personnel' => 'Zulfiqar', 'date' => '2026-09-20', 'remarks' => 'Printer sent for authorized service.'],
            ['problem' => 'PRB-2026-005', 'asset' => 'AST-1005', 'status' => InspectionStatus::Sold, 'category' => InspectionCategory::NewPurchase, 'sub_category' => null, 'personnel' => 'Mudassar', 'date' => '2026-09-18', 'remarks' => 'Replacement laptop supplied.'],
            ['problem' => 'PRB-2026-006', 'asset' => 'AST-1006', 'status' => InspectionStatus::InProgress, 'category' => InspectionCategory::Repair, 'sub_category' => InspectionSubCategory::InHouse, 'personnel' => 'Mudassar', 'date' => '2026-09-16', 'remarks' => 'Projector lamp condition under review.'],
        ];

        foreach ($inspections as $attributes) {
            Inspection::query()->updateOrCreate(
                ['problem_id' => $attributes['problem']],
                [
                    'asset_id' => $assets[$attributes['asset']]->getKey(),
                    'remarks' => $attributes['remarks'],
                    'status' => $attributes['status'],
                    'category' => $attributes['category'],
                    'sub_category' => $attributes['sub_category'],
                    'technical_personnel_id' => $personnel[$attributes['personnel']]->getKey(),
                    'created_by' => $users['admin@logreport.test']->getKey(),
                    'inspection_date' => $attributes['date'],
                ],
            );
        }
    }
}
