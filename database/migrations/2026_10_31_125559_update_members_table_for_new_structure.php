
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {

            // Remove English name (stored in users.name)
            $table->dropColumn('full_name_en');

            // Rename existing columns
            $table->renameColumn('mobile', 'mobile_number');
            $table->renameColumn('profile_photo', 'profile_photo_path');
            $table->renameColumn('membership_status', 'status');

            // Admin Review Information
            $table->text('rejection_reason')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
        });

        // Remove old address columns
        Schema::table('members', function (Blueprint $table) {

            $table->dropColumn([
                'permanent_village',
                'permanent_post_office',
                'permanent_upazila',
                'permanent_district',
                'present_village',
                'present_post_office',
                'present_upazila',
                'present_district',
            ]);
        });

        // Add indexes
        Schema::table('members', function (Blueprint $table) {
            $table->index('mobile_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {

            // Remove indexes
            $table->dropIndex(['mobile_number']);
            $table->dropIndex(['status']);

            // Remove admin review information
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'rejection_reason',
                'reviewed_at',
            ]);

            // Restore old columns
            $table->renameColumn('mobile_number', 'mobile');
            $table->renameColumn('profile_photo_path', 'profile_photo');
            $table->renameColumn('status', 'membership_status');

            $table->string('full_name_en');

            // Restore old address columns
            $table->string('permanent_village');
            $table->string('permanent_post_office');
            $table->string('permanent_upazila');
            $table->string('permanent_district');

            $table->string('present_village');
            $table->string('present_post_office');
            $table->string('present_upazila');
            $table->string('present_district');
        });
    }
};
