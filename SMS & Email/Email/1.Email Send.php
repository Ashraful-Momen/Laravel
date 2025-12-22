.env: 
---------

#****: Use this email config =>
# # Mail Configuration - FIXED: Changed from log to smtp for actual email delivery
MAIL_MAILER=smtp
MAIL_HOST=smtppro.zoho.com
MAIL_PORT=465
MAIL_USERNAME=stbl.erp@smartbd.com
MAIL_PASSWORD=vdm8*bfH
MAIL_FROM_ADDRESS=stbl.erp@smartbd.com
MAIL_FROM_NAME="${APP_NAME}"
MAIL_ENCRYPTION=ssl
MAIL_DEFAULT_SENDER=stbl.erp@smartbd.com


#****: Use this sms config =>
# # SMS Configuration
SMS_URL='https://bulksmsbd.net/api/smsapi?api_key=KsNp0AcYqTNzTxCpoVA6&type=text&number={phone}&senderid=8809617611744&message={message}'
===================================================================

#route: web.php: 
------------------

<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


// Test email and SMS integration
Route::get('/test-email', function () {
    $recipient = 'ashrafulunisoft@gmail.com'; // Using specified email address for testing
    try {
        Mail::to($recipient)->send(new \App\Mail\TestEmail());
        return "Test email sent successfully to {$recipient}!";
    } catch (\Exception $e) {
        return "Error sending email: " . $e->getMessage();
    }
});



===================================================================
service: /home/ashraful/UniSoft Ltd/Backend/saffron-backend-3/app/Mail/TestEmail.php
----------
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Test Email',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.test',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    public function build()
    {
        return $this->subject('Test Email from Bagisto')
                    ->view('emails.test'); // This is the view for the email content.
    }

}
