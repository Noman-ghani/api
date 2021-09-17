<?php

namespace App\Jobs;

use App\Jobs\Job;
use App\Models\Appointments;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Queue;

class AppointmentReminderJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * String $appointment_id   unique id of appointment
     */
    protected $appointment_id;

    /**
     * Create a new job instance.
     *
     * @param  Int   $appointment_id
     * @return void
     */
    public function __construct(Int $appointment_id)
    {
        $this->appointment_id = $appointment_id;
    }

    /**
     * Execute the job.
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $appointment = Appointments::with(["client", "business", "items"])->whereId($this->appointment_id)->whereStatus("confirmed")->first();

        if ($appointment) {
            $appointmentDateTime = Carbon::parse($appointment->booking_date . ' ' . $appointment->items[0]->start_time);
            dispatch(new SendSmsJob($appointment->business_id, $appointment->client->phone_number, "Hi {$appointment->client->first_name},\nThis is to inform you that you have an appointment on " . $appointmentDateTime->format("d/m") . " at " . $appointmentDateTime->format("h:ia") . ".\n\nTo cancel, reply with SU654N\n\n{$appointment->business->name}"));
        }
    }
}
