<?php

namespace Modules\Inventory\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Inventory\Models\InventoryMovement;
use Modules\MailerManager\Traits\UsesEmailTemplate;

/**
 * Notification email for GL posting failures
 *
 * Sent to accounting team when an inventory movement
 * cannot be posted to the general ledger after max retries.
 */
class GLPostingFailedMail extends Mailable
{
    use Queueable, SerializesModels, UsesEmailTemplate;

    public InventoryMovement $movement;
    public string $errorMessage;
    public int $retryAttempts;

    protected function getRegistryKey(): string
    {
        return 'inventory.gl_posting_failed';
    }

    /**
     * Create a new message instance.
     */
    public function __construct(InventoryMovement $movement, string $errorMessage, int $retryAttempts = 0)
    {
        $this->movement = $movement;
        $this->errorMessage = $errorMessage;
        $this->retryAttempts = $retryAttempts;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[URGENTE] Fallo en GL Posting - Movimiento #{$this->movement->id}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'inventory::mail.gl-posting-failed',
            with: [
                'movement' => $this->movement,
                'errorMessage' => $this->errorMessage,
                'retryAttempts' => $this->retryAttempts,
            ],
        );
    }

    protected function getTemplateVariables(): array
    {
        return [
            'movement_id' => (string) $this->movement->id,
            'movement_type' => $this->movement->type ?? '',
            'error_message' => $this->errorMessage,
            'retry_attempts' => (string) $this->retryAttempts,
            'company_name' => config('app.name', 'Labor Wasser de Mexico'),
        ];
    }
}
