<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Queries\ContractListQuery;
use App\Support\Csv;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractExportController extends Controller
{
    private const array HEADERS = [
        'Policy number', 'Client', 'Insurer', 'Type', 'Status', 'Starts on', 'Expires on', 'Notice deadline', 'Annual premium (EUR)', 'Payment frequency',
    ];

    /**
     * Export contracts as CSV.
     *
     * Accepts the same filters and sort as the contract list. Semicolons and a
     * byte order mark let Excel open the file correctly with Belgian settings.
     */
    public function __invoke(Request $request, ContractListQuery $query): StreamedResponse
    {
        $contracts = $query->for($request);

        return response()->streamDownload(function () use ($contracts) {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, self::HEADERS, ';');

            /** @var Contract $contract */
            foreach ($contracts->lazy(500) as $contract) {
                fputcsv($output, [
                    Csv::safeCell($contract->policy_number),
                    Csv::safeCell($contract->client->displayName()),
                    Csv::safeCell($contract->insurer->name),
                    $contract->type->value,
                    $contract->status->value,
                    $contract->starts_on->toDateString(),
                    $contract->expires_on->toDateString(),
                    $contract->noticeDeadline()->toDateString(),
                    number_format($contract->annual_premium_cents / 100, 2, ',', ''),
                    $contract->payment_frequency->value,
                ], ';');
            }

            fclose($output);
        }, 'contracts-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
