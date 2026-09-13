<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Default guard for spatie permissions
     */
    protected string $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->roles->pluck('name')->first(),
        ];
    }

    /**
     * Business profile relationship (1:1 for business role)
     */
    public function businessProfile(): HasOne
    {
        return $this->hasOne(BusinessProfile::class, 'user_id');
    }

    /**
     * Customer profile relationship (1:1 for customer role)
     */
    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class, 'user_id');
    }

    /**
     * Contractor services
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'business_id');
    }

    /**
     * Contractor products
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'business_id');
    }

    /**
     * Quote requests received by contractor
     */
    public function quoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class, 'business_id');
    }

    /**
     * Quote requests submitted by customer
     */
    public function customerQuoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class, 'customer_id');
    }

    /**
     * Contractor projects
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'business_id');
    }

    /**
     * Customer projects
     */
    public function customerProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'customer_id');
    }

    /**
     * Contractor issued invoices
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'business_id');
    }

    /**
     * Customer received invoices
     */
    public function customerInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    /**
     * Conversations as business
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'business_id');
    }

    /**
     * Conversations as customer
     */
    public function customerConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'customer_id');
    }

    /**
     * Sent messages
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Reviews received as business
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'business_id');
    }

    /**
     * Reviews submitted as customer
     */
    public function customerReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    /**
     * Active subscription for contractor
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'business_id')->latestOfMany();
    }

    /**
     * All subscriptions history
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'business_id');
    }

    /**
     * In-app notifications
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    /**
     * Veteran nominations submitted by this user
     */
    public function veteranNominations(): HasMany
    {
        return $this->hasMany(VeteranNomination::class, 'user_id');
    }

    /**
     * Contact inquiries submitted by this user
     */
    public function contactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class, 'user_id');
    }

    /**
     * Customer payments
     */
    public function customerPayments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class, 'user_id');
    }

    /**
     * Saved / Bookmarked contractors
     */
    public function savedContractors(): HasMany
    {
        return $this->hasMany(SavedContractor::class, 'user_id');
    }
}

