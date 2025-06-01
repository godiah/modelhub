<?php

namespace App\Http\Controllers;

use App\Models\JobReview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashBoardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Fetch user data with all required relationships using eager loading
        $userData = User::with([
            'profile',
            'skills' => function ($query) {
                $query->orderBy('name');
            },
            'software' => function ($query) {
                $query->orderBy('name');
            },
            'socialLinks' => function ($query) {
                $query->public()->ordered()
                    ->with(['socialNetwork' => function ($networkQuery) {
                        $networkQuery->active()->ordered();
                    }]);
            }
        ])->find($user->id);

        // Fetch reviews received by the user with reviewer details
        $reviewsReceived = JobReview::with([
            'reviewer' => function ($query) {
                $query->select('id', 'name');
            },
            'reviewer.profile' => function ($query) {
                $query->select('user_id', 'avatar');
            },
            'engagement' => function ($query) {
                $query->select('id', 'application_id', 'created_at');
            },
            'engagement.job' => function ($query) {
                $query->select('model_jobs.id', 'model_jobs.title');
            }
        ])
            ->where('reviewee_id', $user->id)
            ->where('is_public', true)
            ->orderBy('created_at', 'desc')
            ->limit(10) // Limit to recent 10 reviews for performance
            ->get();

        // Calculate review statistics
        $reviewStats = $this->calculateReviewStats($user->id);

        // Get activity summary
        $activitySummary = $this->getActivitySummary();

        // Prepare data for the view
        $dashboardData = [
            'user' => $userData,
            'profile' => $userData->profile,
            'socialLinks' => $userData->socialLinks->filter(function ($link) {
                return $link->socialNetwork && $link->socialNetwork->is_active;
            }),
            'skills' => $userData->skills,
            'software' => $userData->software,
            'reviews' => $reviewsReceived,
            'reviewStats' => $reviewStats,
            'activitySummary' => $activitySummary,
        ];

        return view('dashboard.index', compact('dashboardData'));
    }

    /**
     * Calculate review statistics for the user
     */
    private function calculateReviewStats(int $userId): array
    {
        $stats = JobReview::where('reviewee_id', $userId)
            ->where('is_public', true)
            ->selectRaw('
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star_count,
                COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star_count,
                COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star_count,
                COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star_count,
                COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star_count
            ')
            ->first();

        return [
            'total_reviews' => $stats->total_reviews ?? 0,
            'average_rating' => $stats->average_rating ? round($stats->average_rating, 1) : 0,
            'rating_distribution' => [
                5 => $stats->five_star_count ?? 0,
                4 => $stats->four_star_count ?? 0,
                3 => $stats->three_star_count ?? 0,
                2 => $stats->two_star_count ?? 0,
                1 => $stats->one_star_count ?? 0,
            ],
        ];
    }

    /**
     * Get user's recent activity summary
     */
    public function getActivitySummary()
    {
        $user = Auth::user();

        $summary = [
            'applications_count' => DB::table('job_applications')
                ->where('applicant_id', $user->id)
                ->count(),
            'jobs_posted_count' => DB::table('model_jobs')
                ->where('user_id', $user->id)
                ->count(),
            'active_engagements_count' => DB::table('job_engagements')
                ->join('job_applications', 'job_engagements.application_id', '=', 'job_applications.id')
                ->where(function ($query) use ($user) {
                    $query->where('job_applications.applicant_id', $user->id)
                        ->orWhere('job_applications.poster_id', $user->id);
                })
                ->where('job_engagements.status', 'active')
                ->count(),
            'completed_engagements_count' => DB::table('job_engagements')
                ->join('job_applications', 'job_engagements.application_id', '=', 'job_applications.id')
                ->where(function ($query) use ($user) {
                    $query->where('job_applications.applicant_id', $user->id)
                        ->orWhere('job_applications.poster_id', $user->id);
                })
                ->where('job_engagements.status', 'completed')
                ->count(),
        ];

        return $summary;
    }
}
