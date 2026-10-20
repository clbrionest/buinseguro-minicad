<?php

namespace App\Mail;

use App\Models\OperatorShift;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShiftClosedReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public OperatorShift $shift;
    public array $stats;

    public function __construct(OperatorShift $shift)
    {
        $this->shift = $shift->load(['openedBy', 'closedBy', 'receivedBy']);
        $this->stats = is_array($shift->stats_snapshot) && !empty($shift->stats_snapshot)
            ? $shift->stats_snapshot
            : OperatorShift::compileShiftStatistics($shift->started_at, $shift->closed_at ?? now());
    }

    public function envelope(): Envelope
    {
        $dateStr = ($this->shift->closed_at ?? now())->format('d/m/Y H:i');
        return new Envelope(
            subject: "[BuinSeguro CAD] Acta y Estadísticas Cierre de Turno #{$this->shift->id} ({$this->shift->shift_type}) - {$dateStr}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shift-closed-report',
            with: [
                'shift' => $this->shift,
                'stats' => $this->stats,
            ],
        );
    }
}
