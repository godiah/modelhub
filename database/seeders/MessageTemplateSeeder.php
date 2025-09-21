<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                // 'user_id' => null,
                'name' => 'Welcome Message',
                'subject' => 'Welcome to ModelHub!',
                'message' => 'Hello and welcome to ModelHub! We’re thrilled to have you join our platform. You can now explore job opportunities, connect with professionals, and manage your applications all in one place.'
            ],
            [
                // 'user_id' => null,
                'name' => 'Application Received',
                'subject' => 'We’ve Received Your Application',
                'message' => 'Dear Applicant, thank you for submitting your application. We have received it successfully and our team is currently reviewing your materials. We’ll be in touch with any updates soon.'
            ],
            [
                // 'user_id' => null,
                'name' => 'Application Under Review',
                'subject' => 'Your Application is Being Reviewed',
                'message' => 'Dear Applicant, your application is currently under review by our recruitment team. We appreciate your patience as we carefully evaluate your qualifications for the role.'
            ],
            [
                // 'user_id' => null,
                'name' => 'Interview Invite',
                'subject' => 'Interview Invitation from ModelHub',
                'message' => 'Dear Applicant, we are pleased to inform you that you’ve been shortlisted for an interview. Kindly reply to this message with your availability, and we’ll schedule the session accordingly.'
            ],
            [
                // 'user_id' => null,
                'name' => 'Application Accepted (Hired)',
                'subject' => 'Congratulations! You’re Hired',
                'message' => 'Dear Applicant, congratulations! After a successful review and interview, we are delighted to offer you the position. Our team will reach out shortly with the next steps to onboard you.'
            ],
            [
                // 'user_id' => null,
                'name' => 'Rejection Notice',
                'subject' => 'Update on Your Application Status',
                'message' => 'Dear Applicant, thank you for your interest in the position. After careful consideration, we regret to inform you that we will not be moving forward with your application. We wish you all the best in your career journey.'
            ],
            [
                // 'user_id' => 1,
                'name' => 'Custom Thank You Note',
                'subject' => 'Thank You for Connecting with Us',
                'message' => "Dear Applicant,\n\nThank you for taking the time to connect with us. We value your interest and look forward to possibly working together in the near future. Please feel free to reach out if you have any questions or need further assistance.\n\nWarm regards,  \nThe ModelHub Team"
            ],
            [
                // 'user_id' => 5,
                'name' => 'Shortlisted for Future Opportunities',
                'subject' => 'You’ve Been Shortlisted for Future Roles',
                'message' => "Dear Applicant,\n\nThank you for your recent application. While we are not proceeding with your profile for the current role, we were impressed with your background. We’ve added your profile to our shortlist for future opportunities and will reach out if a suitable position becomes available.\n\nBest regards,  \nThe ModelHub Recruitment Team"
            ]
        ];

        foreach ($templates as $template) {
            MessageTemplate::updateOrCreate(
                [
                    'name' => $template['name'],
                    // 'user_id' => $template['user_id']
                ],
                [
                    'subject' => $template['subject'],
                    'message' => $template['message']
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now()
                ],
            );
        }
    }
}
