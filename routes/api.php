<?php

use App\Http\Controllers\Api\Admin\AdminContributionController;
use App\Http\Controllers\Api\Admin\MemberController;
use App\Http\Controllers\Api\Admin\ContactMessageController;
use App\Http\Controllers\Api\Admin\GalleryPhotoController;
use App\Http\Controllers\Api\Admin\MemberContributionController;
use App\Http\Controllers\Api\Admin\PendingMemberController;
use App\Http\Controllers\Api\Admin\ProfileController;
use App\Http\Controllers\Api\Admin\ProjectCategoryController;
use App\Http\Controllers\Api\Admin\ProjectController;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\AdvisorController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\BlogCategoryController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\ContactSettingController;
use App\Http\Controllers\Api\Expense\ExpenseCategoryController;
use App\Http\Controllers\Api\Expense\ExpenseController;
use App\Http\Controllers\Api\FounderController;
use App\Http\Controllers\Api\Payment\DonationController;
use App\Http\Controllers\Api\Payment\PaymentController;
use App\Http\Controllers\Api\Public\ContactMessageController as PublicContactMessageController;
use App\Http\Controllers\Api\PublicDonationController;
use App\Http\Controllers\Api\PublicFinancialLedgerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Member\MemberContributionPaymentController;
use App\Http\Controllers\Api\Admin\AdminContributionPaymentController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Member\MemberContributionOnlyController;
use App\Http\Controllers\Api\Member\MemberDashboardController;
use App\Http\Controllers\Api\Member\MemberSettingsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {

    Route::post('/payment-methods',[PaymentController::class, 'store']);
    Route::get('/payment-methods', [PaymentController::class, 'index']);
    Route::patch('/payment-methods/{paymentMethod}',[PaymentController::class, 'update']);
    Route::put('/payment-methods/{paymentMethod}',[PaymentController::class, 'update']);
    Route::get('/donations', [DonationController::class, 'get']);
    Route::patch('/donations/{id}/verify', [DonationController::class,'verify']);
    Route::patch('/donation/{id}/reject', [DonationController::class, 'reject']);
    Route::delete('/donation/{id}', [DonationController::class, 'destroy']);
    Route::patch('/donation/{id}', [DonationController::class, 'update']);
    Route::post('/donation', [DonationController::class, 'store_admin']);
    // Member Api enpoint
    Route::get('/members/pending', [PendingMemberController::class, 'index']);
    Route::patch('/members/{member}/approve',[PendingMemberController::class, 'approve']);
    Route::delete('/delete/member-requests/{id}', [PendingMemberController::class,'destroy']);
    Route::patch('/update/members/{id}', [MemberController::class, 'update']);
    // Category
    Route::get('/project-categories', [ProjectCategoryController::class, 'index']);
    Route::post('/project-categories', [ProjectCategoryController::class, 'store']);
    Route::put('/project-categories/{projectCategory}', [ProjectCategoryController::class, 'update']);
    Route::delete('/project-categories/{projectCategory}', [ProjectCategoryController::class, 'destroy']);

    // project api route
    Route::post("/project", [ProjectController::class, 'store']);
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::patch('/project/{id}/update', [ProjectController::class, 'update']);
    Route::delete('/project/{id}/delete', [ProjectController::class, 'destroy']);

    // Expense Category
    Route::apiResource('/expense-categories',ExpenseCategoryController::class);
    Route::apiResource('/expenses', ExpenseController::class);

    // Gallery photos
    Route::get('/gallery', [GalleryPhotoController::class,'index']);
    Route::post('/gallery', [GalleryPhotoController::class,'store']);
    Route::put('/gallery/{photo}/update', [GalleryPhotoController::class,'update']);
    Route::patch('/gallery/{photo}/status', [GalleryPhotoController::class,'updateStatus']);
    Route::delete('/gallery/{photo}', [GalleryPhotoController::class,'destroy']);

    // Founder API
    Route::get('/founders', [FounderController::class, 'index']);
    Route::post('/founders', [FounderController::class, 'store']);
    Route::get('/founders/{id}', [FounderController::class, 'show']);
    Route::post('/founders/{id}/update', [FounderController::class, 'update']);
    Route::delete('/founders/{id}', [FounderController::class, 'destroy']);

    // Blog News API
    Route::apiResource('/blogs', BlogController::class);
    Route::apiResource('/blog-categories', BlogCategoryController::class);

    // Advisor
    Route::get('/advisors', [AdvisorController::class, 'index']);
    Route::get('/advisors/{id}', [AdvisorController::class, 'show'])->whereNumber('id');
    Route::post('/advisors', [AdvisorController::class, 'store']);
    // POST is accepted for updates because PHP does not parse multipart/form-data on real PUT/PATCH requests.
    // The frontend sends POST with _method=PUT; true PUT/PATCH still work for JSON clients.
    Route::match(['put', 'patch', 'post'], '/advisors/{id}', [AdvisorController::class, 'update'])->whereNumber('id');
    Route::delete('/advisors/{id}', [AdvisorController::class, 'destroy'])->whereNumber('id');

    // Contact Message
    Route::get('/contact-messages', [ContactMessageController::class, 'index']);
    Route::get('/contact-messages/{id}', [ContactMessageController::class, 'show'])->whereNumber('id');
    Route::patch('/contact-messages/{id}/status', [ContactMessageController::class, 'updateStatus'])->whereNumber('id');
    Route::delete('/contact-messages/{id}', [ContactMessageController::class, 'destroy'])->whereNumber('id');

    // Contact Settings
    Route::get('/contact-settings', [ContactSettingController::class, 'show']);
    Route::match(['put', 'patch'], '/contact-settings', [ContactSettingController::class, 'update']);


});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {

    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // Contributions Route
    Route::post('/contributions/generate', [MemberContributionController::class, 'generate']);

    Route::get('/contribution-payments',[AdminContributionPaymentController::class, 'index']);
    Route::get('/contribution-payments/{id}',[AdminContributionPaymentController::class, 'show']);
    Route::post('/contribution-payments/{id}/approve',[AdminContributionPaymentController::class, 'approve']);
    Route::post('/contribution-payments/{id}/reject',[AdminContributionPaymentController::class, 'reject']);
    Route::post('/contribution-payments/cash',[AdminContributionPaymentController::class, 'storeCashPayment']);

    // Contribution list
    Route::get('/contributions', [AdminContributionController::class,'index']);
    // Contribution summary
    Route::get('/contributions/summary', [AdminContributionController::class,'summary']);
    // Specific member contribution history
    Route::get('/contributions/members/{memberId}', [AdminContributionController::class,'memberHistory']);
    // Members with outstanding contributions
    Route::get('/contributions/due-members', [AdminContributionController::class,'dueMembers']);


});


Route::middleware(['auth:sanctum','role:member'])->prefix('member')->group(function () {

    Route::get('/account', [MemberController::class, 'account']);
    Route::get('/profile', [MemberController::class, 'profile']);
    Route::patch('/profile', [MemberController::class, 'updateProfile']);
    Route::post('/profile/photo', [MemberController::class,'updateProfilePhoto',]);

    Route::get('/dashboard',[MemberDashboardController::class, 'index']);

    Route::get('/payment-methods',[MemberContributionPaymentController::class, 'paymentMethods']);
    Route::post('/contributions/{contributionId}/payments',[MemberContributionPaymentController::class, 'store']);
    Route::get('/payments',[MemberContributionPaymentController::class, 'history']);

    Route::get('/contributions',[MemberContributionOnlyController::class, 'index']);

    Route::patch('/change-email', [MemberSettingsController::class, 'changeEmail']);
    Route::patch('/change-password', [MemberSettingsController::class, 'changePassword']);
    Route::post('/logout', [MemberSettingsController::class, 'logout']);

});

Route::prefix('public')->group(function () {
    Route::get('/active-payment-methods', [PaymentController::class, 'activeMethods']);
    Route::post('/submit-donation', [DonationController::class, 'store']);
    // Route::get('/donatons', [DonationController::class, 'get']);
    // Join Member
    Route::get('/gallery', [GalleryPhotoController::class, 'index']);

    Route::get('/projects', [ProjectController::class, 'index_public']);
    Route::get('/projects/{id}', [ProjectController::class, 'show_public']);

    // Advisor
    Route::get('/advisors', [AdvisorController::class, 'index']);
    Route::get('/advisors/{id}', [AdvisorController::class, 'show'])->whereNumber('id');
    Route::get('/member-list', [PendingMemberController::class, 'list_member']);
    Route::get('/contact-settings', [ContactSettingController::class, 'show']);

    // Donation
    Route::get('/donation-list', [DonationController::class, 'getDonations']);
    Route::get('/donations/summary', [PublicDonationController::class, 'summary']);
    Route::get('/financial-ledger', [PublicFinancialLedgerController::class, 'index']);
    
});


Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
// Public Founder APIs
Route::get('/founders', [FounderController::class, 'publicIndex']);
Route::get('/founders/{id}', [FounderController::class, 'publicShow']);


Route::middleware('auth:sanctum')->group(function () {

    // Admin profile
    Route::get('/admin/profile', [ProfileController::class, 'show']);
    Route::patch('/admin/profile', [ProfileController::class, 'update']);

    // Member Profile
    Route::get('/member/profile', [MemberController::class, 'profile']);

    // Admin settings
    Route::patch('/admin/settings/email', [SettingsController::class, 'updateEmail']);
    Route::patch('/admin/settings/password', [SettingsController::class, 'updatePassword']);

    Route::post('/logout', [LoginController::class, 'logout']);
});
Route::post('/contact', [PublicContactMessageController::class, 'store'])->middleware('throttle:5,10');
