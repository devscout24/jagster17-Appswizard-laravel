<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\ContactMessageRequest;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    use ApiResponseTrait;

    /**
     * Provide contact & support metadata and prefill details for the Contact Screen.
     */
    public function info(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $payload = [
            'hero' => [
                'headline' => 'Get in touch',
                'subtitle' => 'Questions about ValorHub? Our support team is here to help.',
            ],
            'support_details' => [
                'email' => 'support@valorhub.com',
                'phone' => '(800) 555-0199',
                'phone_display' => 'Mon–Sat 7am–7pm CST',
                'address' => '1200 Liberty Way, Dallas, TX 75201',
                'office_hours' => 'Mon–Sat 7am–7pm CST',
                'map_location' => [
                    'city' => 'Dallas',
                    'state' => 'TX',
                    'pin_label' => 'ValorHub HQ - Dallas, TX',
                ],
            ],
            'user_prefill' => [
                'full_name' => $user?->name,
                'email' => $user?->email,
            ],
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Contact Us', 'url' => null],
            ],
        ];

        return $this->success($payload, 'Contact information retrieved successfully');
    }

    /**
     * Handle contact & support message submission.
     */
    public function send(ContactMessageRequest $request): JsonResponse
    {
        $userId = auth('api')->id();

        $message = ContactMessage::create([
            'user_id' => $userId,
            'full_name' => $request->validated('full_name'),
            'email' => $request->validated('email'),
            'subject' => $request->validated('subject'),
            'message' => $request->validated('message'),
            'status' => 'pending',
        ]);

        return $this->success(
            new ContactMessageResource($message),
            'Your message has been sent successfully. Our support team will get back to you shortly.',
            201
        );
    }
}
