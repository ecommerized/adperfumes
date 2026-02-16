<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Order $order;
    protected ?float $merchantTotal;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, ?float $merchantTotal = null)
    {
        $this->order = $order;
        $this->merchantTotal = $merchantTotal;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $isMerchant = $notifiable instanceof \App\Models\Merchant;
        $total = $isMerchant && $this->merchantTotal
            ? number_format($this->merchantTotal, 2)
            : number_format($this->order->grand_total, 2);

        $subject = $isMerchant
            ? 'New Order Received - ' . $this->order->order_number
            : 'New Order Placed - ' . $this->order->order_number;

        $greeting = $isMerchant
            ? 'Hello ' . $notifiable->business_name . ','
            : 'Hello Admin,';

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line('A new order has been placed!')
            ->line('**Order Number:** ' . $this->order->order_number)
            ->line('**Customer:** ' . $this->order->full_name)
            ->line('**Total Amount:** AED ' . $total)
            ->line('**Payment Method:** ' . ucfirst(str_replace('_', ' ', $this->order->payment_method)))
            ->line('**Status:** ' . ucfirst($this->order->status));

        if ($isMerchant) {
            $message->action('View Order Details', url('/merchant/orders?order=' . $this->order->id));
        } else {
            $message->action('View Order', url('/admin/orders/' . $this->order->id));
        }

        return $message->line('Thank you for using AD Perfumes!');
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $isMerchant = $notifiable instanceof \App\Models\Merchant;
        $total = $isMerchant && $this->merchantTotal
            ? $this->merchantTotal
            : $this->order->grand_total;

        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_name' => $this->order->full_name,
            'total' => $total,
            'payment_method' => $this->order->payment_method,
            'status' => $this->order->status,
            'message' => 'New order ' . $this->order->order_number . ' - AED ' . number_format($total, 2),
        ];
    }

    /**
     * Get the array representation of the notification (for broadcasting).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
