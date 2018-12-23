<?php

use App\Models\Contact\Reminder;
use App\Models\Instance\SpecialDate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReminderInitialDateToReminders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->date('initial_date')->after('contact_id');
        });

        // we need to migrate old data. Since we don't know what was the initial
        // date for the reminder, we need to make a guess by taking the last
        // triggered column information.
        Reminder::chunk(200, function ($reminders) {
            foreach ($reminders as $reminder) {
                if (! is_null($reminder->special_date_id)) {
                    $reminder->initial_date = SpecialDate::find($reminder->special_date_id)->date;
                } else {
                    $reminder->initial_date = $reminder->last_triggered;
                }
                $reminder->save();
            }
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn('special_date_id');
            $table->dropColumn('last_triggered');
        });

        Schema::create('reminder_outbox', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('reminder_id');
            $table->date('planned_date');
            $table->string('nature')->default('reminder');
            $table->integer('notification_number_days_before')->nullable();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('reminder_id')->references('id')->on('reminders')->onDelete('cascade');
        });

        Schema::create('reminder_sent', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('reminder_id');
            $table->date('planned_date');
            $table->datetime('sent_date');
            $table->string('nature')->default('reminder');
            $table->longText('html_content')->nullable();
            $table->longText('text_content')->nullable();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('reminder_id')->references('id')->on('reminders')->onDelete('cascade');
        });
    }
}
