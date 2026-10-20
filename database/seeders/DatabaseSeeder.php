<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,   // after roles + permissions
            DepartmentSeeder::class,
            UserSeeder::class,
            ItemTypeSeeder::class,
            IssueTypeSeeder::class,
        ]);
    }   
}

/*
 id |       name        |           email            |  email_verified_at  |                           password                           | role_id |     designation      |     created_at      |     updated_at      | department_id 
----+-------------------+----------------------------+---------------------+--------------------------------------------------------------+---------+----------------------+---------------------+---------------------+---------------
  2 | Mudassir Shahid   | mudassir.shahid@cef.org.pk | 2026-10-18 14:39:13 | $2y$12$F4J/pscD9u8Dc42M1UN01efoZDmoXeoh4tdaJFlS1UM0nc1P7Joee |       1 | Senior Administrator | 2026-10-18 14:39:13 | 2026-10-18 14:39:13 |             1
  3 | Muhammad Afaq     | afaq@cef.org.pk            | 2026-10-18 14:39:14 | $2y$12$CDmYpmqosczWcctolcU7f.iDLEFCwvqI/8JqKdflFj9fgdlImRw/O |       2 | Junior Administrator | 2026-10-18 14:39:14 | 2026-10-18 14:39:14 |             7
  4 | Haris Niaz Abbasi | haris.niaz@cef.org.pk      | 2026-10-18 14:39:14 | $2y$12$rExH88EfkR5VaTSDi6b6iOAnG8Lshx/JuAN6.PO18M39gI8qZP7bi |       3 | Junior Administrator | 2026-10-18 14:39:14 | 2026-10-18 14:39:14 |             7
  5 | Zulfiqar Ahmed    | zulfiqar.ahmed@cef.org.pk  | 2026-10-18 14:39:14 | $2y$12$AllfmT7wmugCgRGaYMRiJ.8WcQRfhck7OE5odQ0qZfO1zc6NF/n.O |       3 | Senior Developer     | 2026-10-18 14:39:14 | 2026-10-18 14:39:14 |             7
  6 | Obaid Ullah Zeb   | obaid.ullah@cef.org.pk     | 2026-10-18 14:39:14 | $2y$12$Q///ukkmazXNdb8sFYTTKeYKhZTID9P2fgKR47CNHfKDmoJpLFmVW |       3 | Assistant Developer  | 2026-10-18 14:39:14 | 2026-10-18 14:39:14 |             7
(5 rows)

*/