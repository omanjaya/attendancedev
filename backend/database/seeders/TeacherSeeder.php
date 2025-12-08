<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TeacherSeeder extends Seeder
{
    public function run()
    {
        // Ensure guru role exists
        $guruRole = Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);

        $teachers = [
            ['code' => '1', 'name' => 'Pande Putu Sekar Ariwidiantari, S. Si', 'subject' => 'Kepala Sekolah'],
            ['code' => '2', 'name' => 'Drs. I Nyoman Sumerta', 'subject' => 'IPS'],
            ['code' => '3', 'name' => 'I.B. Gede Adnyana, S.Pd', 'subject' => 'Matematika'],
            ['code' => '4', 'name' => 'Made Dwijayanti, SS', 'subject' => 'Bahasa Inggris'],
            ['code' => '5', 'name' => 'Ni Gst. Ayu Putu Sekarini, S.Pd', 'subject' => 'Pend. Pancasila'],
            ['code' => '6', 'name' => 'Putu Sri Rahayu Kasumawati, ST', 'subject' => 'IPA'],
            ['code' => '7', 'name' => 'I Made Mahayoga Sapanca, S.Sn', 'subject' => 'SENI RUPA'],
            ['code' => '8', 'name' => 'Ni Wayan Sriyani, S.Ag', 'subject' => 'Agama Hindu'],
            ['code' => '9', 'name' => 'I Ketut Aria Batur, S.Pd', 'subject' => 'PJOK'],
            ['code' => '10', 'name' => 'I.A. Ketut Dewi Adnya S., S.Sos', 'subject' => 'IPS'],
            ['code' => '11', 'name' => 'Ni Wayan Sudarningsih, M.Pd', 'subject' => 'Matematika'],
            ['code' => '12', 'name' => 'Ni Luh Putu Nanik Suryati, S.Pd', 'subject' => 'Bahasa Inggris'],
            ['code' => '13', 'name' => 'Dra. Ni Nyoman Suryantini', 'subject' => 'Matematika'],
            ['code' => '14', 'name' => 'I Made Sumitra, BA', 'subject' => 'Bahasa Jepang'],
            ['code' => '15', 'name' => 'Drs. I Wayan Nengah', 'subject' => 'Bahasa Bali'],
            ['code' => '16', 'name' => 'Abdul Roup,S.Pdi', 'subject' => 'Agama Islam'],
            ['code' => '17', 'name' => 'Serie Kurnia Tarigan, S.Th', 'subject' => 'Agama Kristen'],
            ['code' => '18', 'name' => 'Dra. I G.A. Laksmi Dewanti, SE', 'subject' => 'Pend. Pancasila'],
            ['code' => '19', 'name' => 'Ni Komang Gayatri Werdiantari', 'subject' => 'Bahasa Bali'],
            ['code' => '20', 'name' => 'Ni Nyoman Niti Pratiwi, SS', 'subject' => 'Bahasa Jepang'],
            ['code' => '21', 'name' => 'I Wayan Gunawan, S.Pd', 'subject' => 'IPA'],
            ['code' => '22', 'name' => 'Ni Nyoman Ayu Savitri, S.Pd', 'subject' => 'Bahasa Indonesia'],
            ['code' => '23', 'name' => 'Ni Ketut Nardi, S.Pd.H', 'subject' => 'Agama Hindu'],
            ['code' => '24', 'name' => 'I Gusti Ngurah Putra Wirawan, S.Pd', 'subject' => 'PJOK'],
            ['code' => '25', 'name' => 'Maria Imaculada Daconceicao Sarmento, SS', 'subject' => 'Bahasa Indonesia'],
            ['code' => '26', 'name' => 'I G.A.A. Bintang Lestari, M.Pd', 'subject' => 'IPA'],
            ['code' => '27', 'name' => 'Ni Ketut Sukerti, S.ST', 'subject' => 'SENI RUPA'],
            ['code' => '28', 'name' => 'Ni Putu Dessy Wedayanthi, S.Pd', 'subject' => 'Bahasa Indonesia'],
            ['code' => '29', 'name' => 'I Made Widnata Putra, S.Ak', 'subject' => 'Informatika'],
            ['code' => '31', 'name' => 'Made Dwi Marina Gitariani Partama Putri, S. Psi', 'subject' => 'BK'],
            ['code' => '32', 'name' => 'Luh Putu Diah Utami Chandrasari, S. Pd', 'subject' => 'Informatika'],
        ];

        $created = 0;
        $updated = 0;

        foreach ($teachers as $t) {
            DB::beginTransaction();
            try {
                $searchName = explode(',', $t['name'])[0];
                
                // Find existing employee
                $emp = Employee::where('full_name', 'like', '%' . $searchName . '%')->first();
                
                if ($emp) {
                    // Update metadata
                    $meta = $emp->metadata ?? [];
                    $meta['teacher_code'] = $t['code'];
                    $meta['subject'] = $t['subject'];
                    $meta['position'] = 'Guru ' . $t['subject'];
                    
                    $emp->update([
                        'metadata' => $meta
                    ]);
                    
                    if ($emp->user) {
                        $emp->user->syncRoles(['guru']);
                    }
                    $updated++;
                } else {
                    // Create user
                    $email = strtolower(Str::slug($searchName, '.')) . '@sekolah.sch.id';
                    
                    // Check if user exists by email, if so try append code to be safe
                    $user = User::where('email', $email)->first();
                    if ($user && $user->employee) {
                        // Email taken by another employee
                        $email = strtolower(Str::slug($searchName, '.')) . $t['code'] . '@sekolah.sch.id';
                        $user = User::where('email', $email)->first();
                    }
                    
                    if (!$user) {
                        $user = User::create([
                            'name' => $t['name'],
                            'email' => $email,
                            'password' => Hash::make('password123'),
                            'is_active' => true,
                        ]);
                    }
                    
                    $user->syncRoles(['guru']);
                    
                    // Check employee again by user_id
                    $emp = Employee::where('user_id', $user->id)->first();
                    
                    if (!$emp) {
                        Employee::create([
                            'user_id' => $user->id,
                            'employee_id' => 'GR' . str_pad($t['code'], 3, '0', STR_PAD_LEFT),
                            'full_name' => $t['name'],
                            'employee_type' => 'permanent', 
                            'salary_type' => 'monthly',
                            'is_active' => true,
                            'hire_date' => now(),
                            'metadata' => [
                                'teacher_code' => $t['code'], 
                                'subject' => $t['subject'], 
                                'position' => 'Guru ' . $t['subject']
                            ],
                        ]);
                        $created++;
                    } else {
                        $meta = $emp->metadata ?? [];
                        $meta['teacher_code'] = $t['code'];
                        $meta['subject'] = $t['subject'];
                        $emp->update(['metadata' => $meta]);
                        $updated++;
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->command->error('Error processing ' . $t['name'] . ': ' . $e->getMessage());
            }
        }

        $this->command->info("Teacher Seeder Completed. Created: $created, Updated: $updated");
    }
}
