<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Faq\FaqIndexRequest;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class FaqController extends Controller
{
    use ApiResponseTrait;

    /**
     * Provide all FAQs and filter categories for the FAQ screen.
     */
    public function index(FaqIndexRequest $request): JsonResponse
    {
        $category = $request->validated('category', 'all');
        $search = $request->validated('search');

        $query = Faq::query()
            ->where('is_published', true)
            ->when($category && $category !== 'all', function ($q) use ($category) {
                $q->where('category', $category);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('question', 'like', "%{$search}%")
                       ->orWhere('answer', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id');

        $faqs = $query->get();

        // Seed default platform FAQs if empty
        if ($faqs->isEmpty() && empty($search)) {
            $faqs = $this->getDefaultFaqs($category);
        }

        $payload = [
            'hero' => [
                'headline' => 'Frequently asked questions',
                'subtitle' => 'Answers to common questions about using ValorHub.',
            ],
            'filter_tabs' => [
                ['key' => 'all', 'label' => 'All'],
                ['key' => 'customers', 'label' => 'For Customers'],
                ['key' => 'contractors', 'label' => 'For Contractors'],
                ['key' => 'payments', 'label' => 'Payments & Billing'],
            ],
            'active_tab' => $category,
            'items' => FaqResource::collection($faqs),
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'FAQ', 'url' => null],
            ],
        ];

        return $this->success($payload, 'FAQs retrieved successfully');
    }

    /**
     * Private helper to provide initial default FAQ items.
     */
    private function getDefaultFaqs(string $category)
    {
        $defaults = [
            [
                'category' => 'customers',
                'question' => 'How do I request a quote from a contractor?',
                'answer' => 'Browse our contractor list or service directory, select the professional you would like to hire, and click "Request Quote". Fill out the brief project details form, specify your budget and timeline, and the contractor will respond within 24 hours.',
                'sort_order' => 1,
            ],
            [
                'category' => 'customers',
                'question' => 'Is ValorHub free to use as a customer?',
                'answer' => 'Yes! Searching for contractors, requesting quotes, and messaging verified pros on ValorHub is 100% free for homeowners and customers.',
                'sort_order' => 2,
            ],
            [
                'category' => 'customers',
                'question' => 'What does "Elite Member" mean for a contractor?',
                'answer' => 'Elite Members are top-rated contractors on ValorHub who maintain high customer satisfaction scores, have verified business credentials, and participate in our platform quality guarantee program.',
                'sort_order' => 3,
            ],
            [
                'category' => 'contractors',
                'question' => 'How do I become a verified contractor on ValorHub?',
                'answer' => 'Register with a Business Account, complete your profile, submit your ID.me verification and state business license credentials. Once approved by our team, you will receive your verified badges.',
                'sort_order' => 4,
            ],
            [
                'category' => 'contractors',
                'question' => 'How does the platform fee work for contractors?',
                'answer' => 'ValorHub operates on a transparent SaaS subscription tier (Free, Pro, and Elite) along with a minimal payment processing fee on completed invoices. A portion of every fee goes directly to our Giving Back veteran foundation.',
                'sort_order' => 5,
            ],
            [
                'category' => 'payments',
                'question' => 'How are payments and invoices processed?',
                'answer' => 'Contractors issue digital invoices directly through ValorHub upon project milestones. Customers can securely review and pay invoices using credit card, debit, or bank transfer.',
                'sort_order' => 6,
            ],
        ];

        foreach ($defaults as $item) {
            Faq::create(array_merge($item, ['is_published' => true]));
        }

        return Faq::query()
            ->where('is_published', true)
            ->when($category && $category !== 'all', fn ($q) => $q->where('category', $category))
            ->orderBy('sort_order')
            ->get();
    }
}
