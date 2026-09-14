<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractorDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource['user'];
        $profile = $user->businessProfile;
        $subscription = $user->subscription;
        $plan = $subscription?->plan;

        return [
            'contractor' => [
                'id'                         => $user->id,
                'business_name'              => $profile?->business_name ?? $user->name,
                'owner_name'                 => $profile?->owner_name ?? $user->name,
                'email'                      => $user->email,
                'phone'                      => $profile?->phone_number ?? $user->phone,
                'avatar'                     => $user->avatar,
                'cover_photo'                => $profile?->cover_photo,
                'location'                   => trim(($profile?->city ?? '') . ', ' . ($profile?->state ?? '')),
                'city'                       => $profile?->city,
                'state'                      => $profile?->state,
                'is_available_today'         => (bool) ($profile?->is_available_today ?? false),
                'is_id_verified'             => (bool) ($profile?->is_id_verified ?? false),
                'is_veteran_owned'           => (bool) ($profile?->is_veteran_owned ?? false),
                'is_elite'                   => (bool) ($profile?->is_elite ?? false),
                'membership_tier'            => $plan?->name ?? 'Free Verified Membership',
                'membership_slug'            => $plan?->slug ?? 'free',
                'membership_badge'           => $plan?->badge ?? ($profile?->is_elite ? 'ELITE' : 'VERIFIED PRO'),
                'profile_completion_percent' => $this->calculateProfileCompletion($profile),
            ],
            'metrics' => [
                'total_earnings'          => (float) $this->resource['total_earnings'],
                'total_earnings_display'  => '$' . number_format((float) $this->resource['total_earnings'], 2),
                'monthly_earnings'        => (float) $this->resource['monthly_earnings'],
                'monthly_earnings_display'=> '$' . number_format((float) $this->resource['monthly_earnings'], 2),
                'earnings_growth_display' => '+14.8%',
                'active_leads_count'      => $this->resource['active_leads_count'],
                'pending_quotes_count'    => $this->resource['pending_quotes_count'],
                'active_projects_count'   => $this->resource['active_projects_count'],
                'completed_projects_count'=> $this->resource['completed_projects_count'],
                'avg_rating'              => (float) ($profile?->avg_rating ?? 5.0),
                'review_count'            => (int) ($profile?->review_count ?? 0),
                'lead_win_rate_percent'   => $this->resource['win_rate_percent'],
                'lead_win_rate_display'   => $this->resource['win_rate_percent'] . '%',
                'avg_response_time'       => '24 mins',
            ],
            'recent_leads' => $this->resource['recent_leads']->map(function ($lead) {
                return [
                    'id'               => $lead->id,
                    'reference_number' => $lead->reference_number,
                    'customer_name'    => $lead->customer?->name ?? 'Homeowner',
                    'customer_avatar'  => $lead->customer?->avatar,
                    'project_title'    => $lead->project_title,
                    'category_name'    => $lead->category?->name ?? 'General Service',
                    'location'         => trim(($lead->city ?? '') . ', ' . ($lead->state ?? '')),
                    'budget_display'   => $lead->budget_min && $lead->budget_max
                        ? '$' . number_format((float) $lead->budget_min, 0) . ' - $' . number_format((float) $lead->budget_max, 0)
                        : ($lead->quote_amount ? '$' . number_format((float) $lead->quote_amount, 2) : 'Flexible'),
                    'status'           => $lead->status,
                    'requested_at'     => $lead->requested_at?->toIso8601String() ?? $lead->created_at->toIso8601String(),
                    'requested_human'  => $lead->requested_at?->diffForHumans() ?? $lead->created_at->diffForHumans(),
                ];
            }),
            'ongoing_projects' => $this->resource['ongoing_projects']->map(function ($project) {
                return [
                    'id'               => $project->id,
                    'title'            => $project->title,
                    'customer_name'    => $project->customer?->name ?? 'Client',
                    'progress_percent' => $project->progress_percent ?? 0,
                    'status'           => $project->status,
                    'due_date'         => $project->due_date?->format('M d, Y'),
                    'due_date_human'   => $project->due_date?->diffForHumans(),
                ];
            }),
            'recent_invoices' => $this->resource['recent_invoices']->map(function ($inv) {
                return [
                    'id'             => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'customer_name'  => $inv->customer?->name ?? 'Client',
                    'amount'         => (float) $inv->amount,
                    'amount_display' => '$' . number_format((float) $inv->amount, 2),
                    'status'         => $inv->status,
                    'due_at'         => $inv->due_at?->format('M d, Y'),
                    'paid_at'        => $inv->paid_at?->format('M d, Y'),
                ];
            }),
            'quick_actions' => [
                ['key' => 'send_quote', 'label' => 'Send Quote', 'icon' => 'file-text'],
                ['key' => 'create_invoice', 'label' => 'Create Invoice', 'icon' => 'credit-card'],
                ['key' => 'manage_services', 'label' => 'Manage Services', 'icon' => 'tool'],
                ['key' => 'edit_profile', 'label' => 'Edit Profile', 'icon' => 'user-check'],
            ],
        ];
    }

    private function calculateProfileCompletion(?object $profile): int
    {
        if (! $profile) {
            return 20;
        }

        $fields = [
            $profile->business_name,
            $profile->city,
            $profile->bio,
            $profile->hourly_rate,
            $profile->years_experience,
            $profile->cover_photo,
            $profile->is_id_verified,
            $profile->service_radius,
            ! empty($profile->service_areas),
            ! empty($profile->languages),
        ];

        $completed = count(array_filter($fields));
        return (int) round(($completed / count($fields)) * 100);
    }
}
