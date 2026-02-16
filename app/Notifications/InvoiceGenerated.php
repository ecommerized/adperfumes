<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class InvoiceGenerated extends Notification
{
    use Queueable;

    protected Invoice $invoice;

    /**
     * Create a new notification instance.
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Invoice for Order ' . $this->invoice->order->order_number . ' - AD Perfumes')
            ->greeting('Hello ' . $this->invoice->customer_name . ',')
            ->line('Thank you for your order!')
            ->line('Please find your invoice attached for order **' . $this->invoice->order->order_number . '**.')
            ->line('**Invoice Number:** ' . $this->invoice->invoice_number)
            ->line('**Order Total:** AED ' . number_format($this->invoice->total, 2))
            ->action('View Order', url('/orders/' . $this->invoice->order->order_number));

        // Attach PDF if it exists
        if ($this->invoice->pdf_path && Storage::exists($this->invoice->pdf_path)) {
            $message->attach(Storage::path($this->invoice->pdf_path), [
                'as' => $this->invoice->invoice_number . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $message->line('Thank you for shopping with AD Perfumes!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'order_number' => $this->invoice->order->order_number,
            'total' => $this->invoice->total,
        ];
    }
}
