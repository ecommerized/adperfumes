<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification // implements ShouldQueue
{
    use Queueable;

    protected Order $order;
    protected ?string $oldStatus;
    protected string $newStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, ?string $oldStatus, string $newStatus)
    {
        $this->order = $order;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
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
        $statusMessages = [
            'pending' => 'is pending confirmation',
            'confirmed' => 'has been confirmed',
            'processing' => 'is being processed',
            'shipped' => 'has been shipped',
            'delivered' => 'has been delivered',
            'cancelled' => 'has been cancelled',
        ];

        $statusMessage = $statusMessages[$this->newStatus] ?? 'status has been updated';

        $message = (new MailMessage)
            ->subject('Order Status Update - ' . $this->order->order_number)
            ->greeting('Hello,')
            ->line('Your order **' . $this->order->order_number . '** ' . $statusMessage . '.')
            ->line('**Order Total:** AED ' . number_format($this->order->grand_total, 2));

        // Add tracking info if shipped
        if ($this->newStatus === 'shipped' && $this->order->tracking_number) {
            $message->line('**Tracking Number:** ' . $this->order->tracking_number)
                ->action('Track Shipment', 'https://www.aramex.com/track/results?ShipmentNumber=' . $this->order->tracking_number);
        } else {
            $message->action('View Order', url('/orders/' . $this->order->order_number));
        }

        return $message->line('Thank you for shopping with AD Perfumes!');
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'tracking_number' => $this->order->tracking_number,
            'message' => 'Order ' . $this->order->order_number . ' status changed to ' . ucfirst($this->newStatus),
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
