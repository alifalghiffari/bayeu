<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderNotification extends Notification
{
    use Queueable;

    protected $order;
    protected $action;

    /**
     * Create a new notification instance.
     */
    public function __construct($order, $action = 'created')
    {
        $this->order = $order;
        $this->action = $action;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        $user = $this->action === 'created'
            ? $this->order->creator
            : $this->order->updater;

        \Log::info($user);

        $menuNames = $this->order->orderItems->map(function ($item) {
            return $item->menu->name ?? 'Unknown';
        })->implode(', ');

        \Log::info($menuNames);

        \Log::info($this->order->table);

        return [
            'title' => 'New Order',
            'message' => 'A new order has been placed for table: ' . $this->order->table->id,
            'menu' => 'Menu: ' . $menuNames,
            'order_id' => 'Order ID: ' . $this->order->id,
            'created_by' => $user->name,
            'updated_by' => $user->name,
            'created_at' => now(),
            'updated_at' => $this->order->updated_at,
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //                 ->line('The introduction to the notification.')
    //                 ->action('Notification Action', url('/'))
    //                 ->line('Thank you for using our application!');
    // }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    // public function toArray(object $notifiable): array
    // {
    //     return [
    //         //
    //     ];
    // }
}
