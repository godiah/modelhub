<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A payment is now either a sale of a model or a client funding a job's escrow. An escrow payment has no listing and no licence tier.
        Schema::table('payments', function (Blueprint $table) {
            $table->string('purpose', 12)->default('model_sale')->after('reference');
            $table->foreignId('engagement_id')->nullable()->after('product_id')->constrained('job_engagements')->restrictOnDelete();
            $table->string('tier', 20)->nullable()->change();
            $table->index(['purpose', 'status']);
        });

        // What the client put into escrow for this job and what has left it since, in minor units. The ledger is the record; these are what
        // the next release is worked out from, kept in step with it inside the same transaction. escrow_minor is 0 for an engagement that was never funded.
        Schema::table('job_engagements', function (Blueprint $table) {
            $table->unsignedBigInteger('escrow_minor')->default(0)->after('payment_released_at');
            $table->unsignedBigInteger('released_net_minor')->default(0)->after('escrow_minor');
            $table->unsignedBigInteger('released_fee_minor')->default(0)->after('released_net_minor');
            $table->unsignedBigInteger('refunded_minor')->default(0)->after('released_fee_minor');
        });
    }

    public function down(): void
    {
        Schema::table('job_engagements', function (Blueprint $table) {
            $table->dropColumn(['escrow_minor', 'released_net_minor', 'released_fee_minor', 'refunded_minor']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['purpose', 'status']);
            $table->dropConstrainedForeignId('engagement_id');
            $table->dropColumn('purpose');
        });
    }
};
