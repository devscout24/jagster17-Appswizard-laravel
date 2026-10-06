<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = ['group', 'key', 'value'];

    private const CACHE_KEY = 'site_settings';

    /**
     * Editable settings shown in the admin panel, grouped by section.
     */
    public const FIELDS = [
        'general' => [
            'site_name' => ['label' => 'Site name', 'default' => 'ValorHub'],
            'tagline' => ['label' => 'Tagline', 'default' => "They Served Their Country. Now, They're Here To Serve You."],
            'footer_description' => ['label' => 'Footer description', 'default' => 'Connecting America with trusted veteran-owned professionals for home service needs.'],
        ],
        'contact' => [
            'support_email' => ['label' => 'Support email', 'default' => 'support@valorhub.com'],
            'support_phone' => ['label' => 'Support phone', 'default' => '(800) 555-0199'],
            'office_address' => ['label' => 'Office address', 'default' => 'Austin, TX'],
            'support_hours' => ['label' => 'Support hours', 'default' => 'Mon–Fri, 8am–6pm CT'],
        ],
        'social' => [
            'facebook_url' => ['label' => 'Facebook URL', 'default' => ''],
            'twitter_url' => ['label' => 'X / Twitter URL', 'default' => ''],
            'instagram_url' => ['label' => 'Instagram URL', 'default' => ''],
            'linkedin_url' => ['label' => 'LinkedIn URL', 'default' => ''],
        ],
        'platform' => [
            'platform_fee_percent' => ['label' => 'Platform fee (%)', 'default' => '5'],
            'annual_discount_percent' => ['label' => 'Annual plan discount (%)', 'default' => '20'],
        ],
    ];

    /**
     * All settings as key => value, with defaults for anything not saved yet.
     */
    public static function allValues(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $saved = self::pluck('value', 'key')->all();
            $values = [];
            foreach (self::FIELDS as $fields) {
                foreach ($fields as $key => $field) {
                    $values[$key] = $saved[$key] ?? $field['default'];
                }
            }

            return $values;
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::allValues()[$key] ?? $default;
    }

    public static function setMany(array $values): void
    {
        foreach (self::FIELDS as $group => $fields) {
            foreach ($fields as $key => $field) {
                if (array_key_exists($key, $values)) {
                    self::updateOrCreate(['key' => $key], ['group' => $group, 'value' => $values[$key]]);
                }
            }
        }

        Cache::forget(self::CACHE_KEY);
    }
}
