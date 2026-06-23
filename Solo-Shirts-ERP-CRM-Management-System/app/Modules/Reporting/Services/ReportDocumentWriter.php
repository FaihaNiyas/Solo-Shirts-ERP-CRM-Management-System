<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Services;

use App\Modules\Printing\Models\Document;
use App\Modules\Reporting\Models\ReportJob;
use App\Modules\Reporting\Reports\ReportInterface;
use Illuminate\Support\Facades\Storage;

/**
 * Serialises a report's rows to a CSV file on the documents disk and records a
 * content-addressed Document (kind = report) for it. (Excel export — maatwebsite/
 * excel — is the production target; CSV keeps the pipeline dependency-free here.)
 */
final class ReportDocumentWriter
{
    public function write(ReportInterface $report, ReportJob $job): Document
    {
        $params = $job->params ?? [];
        $csv = $this->toCsv($report->headers(), $report->rows($params, $job->branch_id));
        $hash = hash('sha256', $csv);

        $disk = $this->disk();
        $path = sprintf('documents/report/%s.csv', $hash);
        Storage::disk($disk)->put($path, $csv);

        return Document::query()->create([
            'branch_id' => $job->branch_id,
            'kind' => Document::KIND_REPORT,
            'reference_type' => ReportJob::class,
            'reference_id' => $job->id,
            'disk' => $disk,
            'path' => $path,
            'content_hash' => $hash,
            'size_bytes' => strlen($csv),
            'generated_by' => $job->requested_by,
            'generated_at' => now(),
        ]);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int>>  $rows
     */
    private function toCsv(array $headers, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    private function disk(): string
    {
        $disk = config('documents.disk') ?? config('filesystems.default');

        return is_string($disk) ? $disk : 'local';
    }
}
