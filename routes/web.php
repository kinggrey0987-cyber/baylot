<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Agent\AppointmentController as AgentAppointmentController;
use App\Http\Controllers\Agent\DashboardController as AgentDashboardController;
use App\Http\Controllers\Agent\LeadQueueController;
use App\Http\Controllers\Agent\ListingController as AgentListingController;
use App\Http\Controllers\Agent\LotController;
use App\Http\Controllers\Agent\NotificationController as AgentNotificationController;
use App\Http\Controllers\Broker\AgentController as BrokerAgentController;
use App\Http\Controllers\Broker\DashboardController as BrokerDashboardController;
use App\Http\Controllers\Broker\ProjectController as BrokerProjectController;
use App\Http\Controllers\Broker\SharedListingController as BrokerSharedListingController;
use App\Http\Controllers\Broker\NotificationController as BrokerNotificationController;
use App\Http\Controllers\Buyer\AppointmentController as BuyerAppointmentController;
use App\Http\Controllers\Buyer\DashboardController as BuyerDashboardController;
use App\Http\Controllers\Buyer\LeadController as BuyerLeadController;
use App\Http\Controllers\Buyer\WishlistController as BuyerWishlistController;
use App\Http\Controllers\LeadConversationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicListingController;
use App\Http\Controllers\PublicProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicListingController::class, 'index'])->name('home');
Route::get('/properties', [PublicListingController::class, 'properties'])->name('properties.index');
Route::get('/properties/map', [PublicListingController::class, 'mapView'])->name('properties.map');
Route::get('/properties/map-data', [PublicListingController::class, 'mapMarkers'])->name('properties.map-data');
Route::get('/about', [PublicListingController::class, 'about'])->name('about');
Route::get('/listings/{listing}', [PublicListingController::class, 'show'])->name('listings.show');
Route::get('/brokers/{broker}', [PublicProfileController::class, 'broker'])->name('brokers.show');
Route::get('/agents/{agent}', [PublicProfileController::class, 'agent'])->name('agents.show');
Route::get('/listings/{listing}/request-visit', [PublicListingController::class, 'startSiteVisitRequest'])
    ->middleware(['auth', '2fa.verified', 'role:buyer'])
    ->name('listings.request-visit.start');
Route::post('/listings/{listing}/request-visit', [BuyerLeadController::class, 'store'])
    ->middleware(['auth', '2fa.verified', 'role:buyer'])
    ->name('listings.request-visit');

Route::get('/dashboard', function () {
    $user = auth()->user();

    return match ($user->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'broker' => redirect()->route('broker.dashboard'),
        'agent' => redirect()->route('agent.dashboard'),
        default => redirect()->route('buyer.dashboard'),
    };
})->middleware(['auth', '2fa.verified', 'verified'])->name('dashboard');

Route::middleware(['auth', '2fa.verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:buyer,agent')->group(function () {
        Route::get('/leads/{lead}/conversation', [LeadConversationController::class, 'show'])->name('leads.conversation.show');
        Route::post('/leads/{lead}/conversation/messages', [LeadConversationController::class, 'storeMessage'])->name('leads.conversation.messages.store');
    });

    Route::middleware('role:buyer')->group(function () {
        Route::post('/leads/{lead}/conversation/site-visit', [LeadConversationController::class, 'scheduleVisit'])->name('leads.conversation.site-visit.store');
    });
});

Route::middleware(['auth', '2fa.verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
    Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
    Route::patch('/agents/{user}/toggle', [AgentController::class, 'toggle'])->name('agents.toggle');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});

Route::middleware(['auth', '2fa.verified', 'role:broker'])->prefix('broker')->name('broker.')->group(function () {
    Route::get('/dashboard', [BrokerDashboardController::class, 'index'])->name('dashboard');

    Route::get('/agents', [BrokerAgentController::class, 'index'])->name('agents.index');
    Route::post('/agents', [BrokerAgentController::class, 'store'])->name('agents.store');
    Route::patch('/agents/{user}/toggle', [BrokerAgentController::class, 'toggle'])->name('agents.toggle');

    Route::get('/projects', [BrokerProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [BrokerProjectController::class, 'store'])->name('projects.store');
    Route::put('/projects/{project}', [BrokerProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [BrokerProjectController::class, 'destroy'])->name('projects.destroy');

    Route::get('/shared-listings', [BrokerSharedListingController::class, 'index'])->name('shared-listings.index');
    Route::post('/shared-listings', [BrokerSharedListingController::class, 'store'])->name('shared-listings.store');
    Route::get('/shared-listings/{listing}/edit', [BrokerSharedListingController::class, 'edit'])->name('shared-listings.edit');
    Route::put('/shared-listings/{listing}', [BrokerSharedListingController::class, 'update'])->name('shared-listings.update');
    Route::delete('/shared-listings/{listing}', [BrokerSharedListingController::class, 'destroy'])->name('shared-listings.destroy');

    Route::get('/notifications', [BrokerNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [BrokerNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [BrokerNotificationController::class, 'markAsRead'])->name('notifications.read');
});

Route::middleware(['auth', '2fa.verified', 'role:agent'])->prefix('agent')->name('agent.')->group(function () {
    Route::get('/dashboard', [AgentDashboardController::class, 'index'])->name('dashboard');

    Route::get('/listings', [AgentListingController::class, 'index'])->name('listings.index');
    Route::get('/listings/create', [AgentListingController::class, 'create'])->name('listings.create');
    Route::post('/listings', [AgentListingController::class, 'store'])->name('listings.store');
    Route::get('/listings/{listing}/edit', [AgentListingController::class, 'edit'])->name('listings.edit');
    Route::put('/listings/{listing}', [AgentListingController::class, 'update'])->name('listings.update');
    Route::delete('/listings/{listing}', [AgentListingController::class, 'destroy'])->name('listings.destroy');
    Route::post('/listings/{listing}/lots', [LotController::class, 'store'])->name('listings.lots.store');

    Route::get('/leads/queue', [LeadQueueController::class, 'index'])->name('leads.queue');
    Route::post('/leads/{lead}/claim', [LeadQueueController::class, 'claim'])->name('leads.claim');

    Route::get('/notifications', [AgentNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [AgentNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [AgentNotificationController::class, 'markAsRead'])->name('notifications.read');

    Route::get('/appointments', [AgentAppointmentController::class, 'index'])->name('appointments.index');
    Route::put('/appointments/{appointment}', [AgentAppointmentController::class, 'update'])->name('appointments.update');
});

Route::middleware(['auth', '2fa.verified', 'role:buyer'])->prefix('buyer')->name('buyer.')->group(function () {
    Route::get('/dashboard', [BuyerDashboardController::class, 'index'])->name('dashboard');
    Route::patch('/appointments/{appointment}/cancel', [BuyerAppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::get('/wishlist', [BuyerWishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{listing}', [BuyerWishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist/{listing}', [BuyerWishlistController::class, 'destroy'])->name('wishlist.destroy');
});

require __DIR__.'/auth.php';
