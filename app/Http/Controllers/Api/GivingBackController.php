<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GivingBack\NominateVeteranRequest;
use App\Http\Resources\VeteranNominationResource;
use App\Models\BusinessProfile;
use App\Models\VeteranNomination;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GivingBackController extends Controller
{
    use ApiResponseTrait;

    /**
     * Provide all data required to render the Giving Back screen.
     */
    public function index(Request $request): JsonResponse
    {
        $payload = [
            'hero' => [
                'badge' => 'OUR MISSION',
                'headline' => 'Giving back to those who served',
                'highlight' => 'Giving back',
                'subtitle' => 'For every job completed on ValorHub, a portion of our platform fee goes toward supporting veteran and first-responder causes across America.',
            ],
            'impact_metrics' => $this->getImpactMetrics(),
            'initiatives' => $this->getInitiatives(),
            'testimonial' => [
                'quote' => 'ValorHub connected me with a contractor who rebuilt my wheelchair ramp at no cost. This platform genuinely gives back to veterans.',
                'author' => 'James Whitfield',
                'author_title' => 'U.S. Army Veteran',
                'location' => 'Phoenix, AZ',
                'program' => 'Home Repair Grant Recipient',
                'avatar' => 'https://images.test/testimonials/james.jpg',
            ],
            'nomination_cta' => [
                'headline' => 'Want to nominate a veteran in need?',
                'subtitle' => 'Tell us about a veteran or first responder who could benefit from our home repair grant program.',
                'action_label' => 'Nominate a Veteran',
                'submission_endpoint' => '/api/giving-back/nominate',
            ],
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Giving Back', 'url' => null],
            ],
        ];

        return $this->success($payload, 'Giving Back screen data retrieved successfully');
    }

    /**
     * Submit a nomination for a veteran or first responder in need.
     */
    public function nominate(NominateVeteranRequest $request): JsonResponse
    {
        $userId = auth('api')->id();

        $nomination = VeteranNomination::create([
            'user_id' => $userId,
            'nominator_name' => $request->validated('nominator_name'),
            'nominator_email' => $request->validated('nominator_email'),
            'nominator_phone' => $request->validated('nominator_phone'),
            'nominee_name' => $request->validated('nominee_name'),
            'nominee_branch' => $request->validated('nominee_branch'),
            'nominee_city' => $request->validated('nominee_city'),
            'nominee_state' => $request->validated('nominee_state'),
            'project_needed' => $request->validated('project_needed'),
            'story_details' => $request->validated('story_details'),
            'status' => 'pending',
        ]);

        return $this->success(
            new VeteranNominationResource($nomination),
            'Thank you! Your nomination has been submitted successfully for review.',
            201
        );
    }

    /**
     * Private helper to fetch impact metrics.
     */
    private function getImpactMetrics(): array
    {
        $veteranPros = BusinessProfile::where('is_veteran_owned', true)->count();
        $statesCount = BusinessProfile::whereNotNull('state')->distinct('state')->count('state');

        return [
            [
                'key' => 'donated_amount',
                'value' => '$1.2M+',
                'label' => 'Donated since 2023',
            ],
            [
                'key' => 'veterans_supported',
                'value' => $veteranPros > 0 ? number_format(8500 + $veteranPros).'+' : '8,500+',
                'label' => 'Veterans supported',
            ],
            [
                'key' => 'partner_orgs',
                'value' => '120+',
                'label' => 'Partner organizations',
            ],
            [
                'key' => 'states_reached',
                'value' => (string) ($statesCount > 0 ? max(32, $statesCount) : '32'),
                'label' => 'States reached',
            ],
        ];
    }

    /**
     * Private helper to provide the 3 core initiatives.
     */
    private function getInitiatives(): array
    {
        return [
            [
                'id' => 1,
                'icon' => 'home-heart',
                'icon_symbol' => '🏠',
                'title' => 'Home Repair Grants',
                'description' => 'Free home repair services for disabled veterans through our contractor network.',
            ],
            [
                'id' => 2,
                'icon' => 'shield-heart',
                'icon_symbol' => '💖',
                'title' => 'Emergency Housing Fund',
                'description' => 'Rapid financial support for veterans and first responders facing housing emergencies.',
            ],
            [
                'id' => 3,
                'icon' => 'briefcase-award',
                'icon_symbol' => '💼',
                'title' => 'Job Placement Program',
                'description' => 'Connecting transitioning service members with contractor apprenticeship opportunities.',
            ],
        ];
    }
}
