<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\JobTicket;
use App\Models\Opportunity;
use App\Models\ServiceRequest;
use App\Models\Technician;
use App\Models\User;
use App\Services\InquiryWorkflowService;
use App\Services\ModuleManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(ModuleManager::class)->syncFromConfig();

        $users = [
            ['name' => 'Admin Demo', 'email' => 'admin@plumbingleadpilot.test', 'role' => 'admin', 'phone' => '555-0100'],
            ['name' => 'Owner Demo', 'email' => 'owner@plumbingleadpilot.test', 'role' => 'owner', 'phone' => '555-0101'],
            ['name' => 'Staff Demo', 'email' => 'staff@plumbingleadpilot.test', 'role' => 'staff', 'phone' => '555-0102'],
            ['name' => 'Tech Demo', 'email' => 'tech@plumbingleadpilot.test', 'role' => 'technician', 'phone' => '555-0103'],
            ['name' => 'Customer Demo', 'email' => 'customer@plumbingleadpilot.test', 'role' => 'customer', 'phone' => '555-0104'],
        ];

        $created = [];
        foreach ($users as $user) {
            $created[$user['role']] = User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'phone' => $user['phone'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        $techUser = $created['technician'];
        $technician = Technician::query()->updateOrCreate(
            ['user_id' => $techUser->id],
            [
                'name' => 'Jordan Pipewright',
                'license_number' => 'PLM-TX-44821',
                'specialties' => ['emergency_burst_pipe', 'leak_repair', 'water_heater'],
                'availability_status' => 'available',
                'service_area' => 'Dallas–Fort Worth',
                'phone' => '555-0103',
                'is_active' => true,
            ]
        );

        Technician::query()->updateOrCreate(
            ['license_number' => 'PLM-TX-55210'],
            [
                'name' => 'Ava Drainwright',
                'specialties' => ['clogged_drain', 'sewer_line', 'maintenance'],
                'availability_status' => 'on_job',
                'service_area' => 'Austin Metro',
                'phone' => '555-0118',
                'is_active' => true,
            ]
        );

        Customer::query()->updateOrCreate(
            ['email' => 'homeowner@example.com'],
            [
                'name' => 'Riley Homeowner',
                'phone' => '555-2001',
                'address' => '1200 Oak Ridge Dr',
                'city' => 'Plano',
                'state' => 'TX',
                'zip' => '75024',
                'notes' => 'Prefers morning appointments; 50-gal water heater',
            ]
        );

        if (Inquiry::query()->count() === 0) {
            $workflow = app(InquiryWorkflowService::class);
            $staff = $created['staff'];

            $samples = [
                [
                    'source' => 'website',
                    'contact_name' => 'Chris Martinez',
                    'contact_email' => 'chris.m@example.com',
                    'contact_phone' => '555-3001',
                    'subject' => 'Burst pipe flooding — urgent',
                    'service_interest' => 'Emergency plumbing',
                    'body' => 'Residential home in Plano — burst pipe under kitchen sink, water everywhere since this morning. Need emergency leak repair ASAP. Phone 555-3001.',
                    'consent_email' => true,
                    'consent_sms' => true,
                ],
                [
                    'source' => 'phone',
                    'contact_name' => 'Dana Lee',
                    'contact_email' => 'dana.lee@example.com',
                    'contact_phone' => '555-3002',
                    'subject' => 'No hot water — water heater',
                    'service_interest' => 'Water heater',
                    'body' => 'Urgent: no hot water and we smell gas near the water heater. Need a licensed plumbing tech this week.',
                    'consent_email' => true,
                    'consent_sms' => false,
                ],
                [
                    'source' => 'email',
                    'contact_name' => 'Sam Patel',
                    'contact_email' => 'sam.patel@example.com',
                    'contact_phone' => '555-3003',
                    'subject' => 'Sewer line camera inspection quote',
                    'service_interest' => 'Sewer line',
                    'body' => 'Need a quote for sewer line camera inspection and root intrusion repair for our commercial building. Prefer this week for estimate.',
                    'consent_email' => true,
                    'consent_sms' => false,
                ],
            ];

            foreach ($samples as $i => $sample) {
                $inquiry = $workflow->intake($sample, $staff);
                if ($i < 2) {
                    $workflow->approve($inquiry, $created['owner']);
                }
            }
        }

        $sr = ServiceRequest::query()->first();
        if ($sr) {
            JobTicket::query()->updateOrCreate(
                ['ticket_number' => 'JT-DEMO0001'],
                [
                    'service_request_id' => $sr->id,
                    'technician_id' => $technician->id,
                    'status' => 'dispatched',
                    'scheduled_start' => now()->addDay()->setTime(9, 0),
                    'scheduled_end' => now()->addDay()->setTime(12, 0),
                    'safety_checklist' => ['PPE on site', 'Water shutoff verified', 'Gas valve locked if applicable'],
                ]
            );
        }

        if (Opportunity::query()->count() === 0) {
            Opportunity::query()->create([
                'title' => 'Demo plumbing pipeline opportunity',
                'stage' => 'qualified',
                'value_estimate' => 1850,
                'probability' => 60,
                'status' => 'open',
                'owner_id' => $created['owner']->id,
            ]);
        }
    }
}
