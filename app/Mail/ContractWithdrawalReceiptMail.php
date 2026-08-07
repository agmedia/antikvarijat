<?php

namespace App\Mail;

use App\Models\ContractWithdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractWithdrawalReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public $withdrawal;
    public $withdrawalSettings;
    public $returnCostText;

    public function __construct(
        ContractWithdrawal $withdrawal,
        array $withdrawalSettings,
        string $returnCostText
    ) {
        $this->withdrawal = $withdrawal;
        $this->withdrawalSettings = $withdrawalSettings;
        $this->returnCostText = $returnCostText;
    }

    public function build()
    {
        $locale = $this->withdrawal->locale === 'en' ? 'en' : 'hr';

        return $this
            ->subject(trans('contract_withdrawal.email.subject', [
                'reference' => $this->withdrawal->reference,
            ], $locale))
            ->view('emails.contract-withdrawals.receipt');
    }
}
