<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Seed the membership plans from Figma node 3154-7834.
     */
    public function run(): void
    {
        SubscriptionPlan::updateOrCreate(
            ['slug' => 'free'],
            [
                'name'          => 'Free Verified Membership',
                'tagline'       => 'Ideal for getting started on ValorHub',
                'badge'         => 'MOST POPULAR',
                'monthly_price' => 0.00,
                'annual_price'  => 0.00,
                'is_popular'    => true,
                'is_active'     => true,
                'sort_order'    => 1,
                'service_limit' => 3,
                'gallery_limit' => 0,
                'video_limit'   => 0,
                'features'      => [
                    'Maximum 3 services',
                    'Basic business information',
                    'Business logo upload',
                    'No premium features',
                ],
            ]
        );

        SubscriptionPlan::updateOrCreate(
            ['slug' => 'pro'],
            [
                'name'          => 'Pro Verified Membership',
                'tagline'       => 'For growing contractors wanting higher visibility',
                'badge'         => null,
                'monthly_price' => 29.00,
                'annual_price'  => 290.00,
                'is_popular'    => false,
                'is_active'     => true,
                'sort_order'    => 2,
                'service_limit' => 10,
                'gallery_limit' => 20,
                'video_limit'   => 0,
                'features'      => [
                    '10 service listings',
                    '20 photo uploads',
                    'Featured listing',
                    'Priority search ranking',
                    'Standard leads',
                    'Custom business URL',
                ],
            ]
        );

        SubscriptionPlan::updateOrCreate(
            ['slug' => 'elite'],
            [
                'name'          => 'Elite Verified Membership',
                'tagline'       => 'Maximum reach, unlimited features, and premium promotion',
                'badge'         => null,
                'monthly_price' => 49.00,
                'annual_price'  => 490.00,
                'is_popular'    => false,
                'is_active'     => true,
                'sort_order'    => 3,
                'service_limit' => null, // unlimited
                'gallery_limit' => 20,
                'video_limit'   => 5,
                'features'      => [
                    'Unlimited services',
                    '20 gallery images',
                    '5 video uploads',
                    'Website & social links',
                    'Brand information',
                    'Business hours & 24/7',
                    'Homepage banner ad',
                    'Featured badge',
                    'Priority marketplace promotion',
                    'Premium support',
                ],
            ]
        );
    }
}
