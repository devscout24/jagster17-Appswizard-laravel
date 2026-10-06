<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\BusinessDocument;
use App\Models\GalleryPhoto;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Demo content for the admin sections added for the website: CMS pages, gallery photos,
 * homepage banners and verification documents.
 */
class PlatformContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPages();

        $contractors = User::whereHas('businessProfile')->with('businessProfile')->orderBy('id')->get();
        if ($contractors->isEmpty()) {
            return;
        }

        $images = $this->copyAssets();
        $this->seedGallery($contractors, $images);
        $this->seedBanners($contractors, $images['banner.jpg']);
        $this->seedDocuments($contractors);
    }

    private function seedPages(): void
    {
        $pages = [
            'privacy-policy' => ['Privacy Policy', 'How ValorHub collects, uses and protects your personal information.', <<<'HTML'
<h2>Information we collect</h2>
<p>We collect the details you give us when you create an account, request a quote, or contact a contractor: your name, email, phone number, address and project details.</p>
<h2>How we use it</h2>
<ul>
  <li>To connect you with verified veteran-owned contractors.</li>
  <li>To process quotes, invoices and payments.</li>
  <li>To send you updates you have opted in to.</li>
</ul>
<h2>Sharing</h2>
<p>We share your project details only with the contractors you contact. We never sell your personal information.</p>
<h2>Contact</h2>
<p>Questions about privacy? Email <a href="mailto:privacy@valorhub.com">privacy@valorhub.com</a>.</p>
HTML],
            'terms-of-service' => ['Terms of Service', 'The terms that apply when you use ValorHub.', <<<'HTML'
<h2>Using ValorHub</h2>
<p>ValorHub is a marketplace that connects homeowners with independent, veteran-owned contractors. Contractors are responsible for the work they perform.</p>
<h2>Accounts</h2>
<p>You are responsible for keeping your login details secure and for all activity on your account.</p>
<h2>Payments</h2>
<p>Invoices are issued by contractors. Membership fees for contractors are billed monthly or annually and can be cancelled at any time.</p>
<h2>Reviews</h2>
<p>Reviews must reflect genuine experiences. We may remove reviews that are abusive or misleading.</p>
HTML],
            'about' => ['About ValorHub', 'ValorHub connects America with trusted veteran-owned professionals.', <<<'HTML'
<h2>They served their country. Now they're here to serve you.</h2>
<p>ValorHub helps homeowners find trusted, ID.me-verified veteran-owned businesses for every home service, from roofing to HVAC.</p>
<p>Every contractor on ValorHub is verified, and a portion of every membership supports our Giving Back program for veterans in need.</p>
HTML],
        ];

        foreach ($pages as $slug => [$title, $meta, $content]) {
            Page::updateOrCreate(['slug' => $slug], [
                'title' => $title,
                'meta_description' => $meta,
                'content' => $content,
                'is_published' => true,
            ]);
        }
    }

    /**
     * Copy bundled demo images into public storage and return their storage paths.
     */
    private function copyAssets(): array
    {
        $paths = [];
        foreach (File::files(database_path('seeders/assets')) as $file) {
            $path = 'seed/' . $file->getFilename();
            Storage::disk('public')->put($path, File::get($file->getPathname()));
            $paths[$file->getFilename()] = $path;
        }

        return $paths;
    }

    private function seedGallery($contractors, array $images): void
    {
        $photos = [
            ['plumbing.jpg', 'Site visit - kickoff', 'project_photos'],
            ['drilling.jpg', 'Kitchen demo day 1', 'before_after'],
            ['sink.jpg', 'Cabinet install', 'project_photos'],
            ['team.png', 'Team on site', 'team'],
        ];

        foreach ($contractors->take(2) as $contractor) {
            if (GalleryPhoto::where('business_id', $contractor->id)->exists()) {
                continue;
            }
            foreach ($photos as $index => [$file, $caption, $category]) {
                GalleryPhoto::create([
                    'business_id' => $contractor->id,
                    'image_path' => $images[$file],
                    'caption' => $caption,
                    'category' => $category,
                    'sort_order' => $index,
                ]);
            }
        }
    }

    private function seedBanners($contractors, string $image): void
    {
        if (Banner::exists()) {
            return;
        }

        $live = $contractors->first();
        Banner::create([
            'business_id' => $live->id,
            'image_path' => $image,
            'headline' => $live->businessProfile->business_name . ' — Trusted Veteran-Owned Roofing & Remodeling',
            'status' => 'approved',
            'approved_at' => now()->subDays(5),
        ]);

        if ($pending = $contractors->get(1)) {
            Banner::create([
                'business_id' => $pending->id,
                'image_path' => $image,
                'headline' => $pending->businessProfile->business_name . ' — Licensed & Insured Electricians',
                'status' => 'pending',
            ]);
        }
    }

    private function seedDocuments($contractors): void
    {
        if (BusinessDocument::exists()) {
            return;
        }

        // Minimal valid PDF so "View file" opens in the browser
        $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj "
            . "3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF";

        foreach ($contractors as $index => $contractor) {
            $path = "documents/{$contractor->id}/license-and-insurance.pdf";
            Storage::disk('public')->put($path, $pdf);

            BusinessDocument::create([
                'business_id' => $contractor->id,
                'type' => 'license_insurance',
                'file_path' => $path,
                'original_name' => 'License & Insurance.pdf',
                // First two are already verified; the rest wait in the admin queue
                'status' => $index < 2 ? 'verified' : 'pending',
                'reviewed_at' => $index < 2 ? now()->subWeek() : null,
            ]);
        }
    }
}
