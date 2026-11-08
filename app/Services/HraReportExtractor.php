<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Process;

class HraReportExtractor
{
    public function extract(string $path, string $extension, string $filename): array
    {
        $result = Process::timeout(45)->run([
            config('hra.python'), base_path('scripts/extract-hra-report.py'), $path, $extension,
        ]);
        if (! $result->successful()) {
            return $this->draft('', $filename) + ['warning' => 'Text could not be extracted. Enter the details manually. The report is still attached.'];
        }
        $data = json_decode($result->output(), true);

        return $this->draft($data['text'] ?? '', $filename) + ['warning' => mb_strlen(trim($data['text'] ?? '')) < 80 ? 'No readable text found. This report may need OCR. Enter the details manually.' : 'Check every suggested field against the report. Blank fields were not reliably identified.'];
    }

    public function draft(string $text, string $filename): array
    {
        $text = preg_replace('/[\t ]+/', ' ', str_replace("\r", '', $text));
        $front = mb_substr($text, 0, 3500);
        $type = match (true) {
            (bool) preg_match('/noise risk assessment/i', $front) => 'noise',
            (bool) preg_match('/ergonomic|ergonomics|\b[AI]ERA\b/i', $front) => 'ergo',
            (bool) preg_match('/chemical health|\bCHRA\b/i', $front) => 'chemical',
            (bool) preg_match('/indoor air quality/i', $front) => 'iaq',
            (bool) preg_match('/psychosocial|prisma/i', $front) => 'prisma',
            default => 'other',
        };
        $titles = ['noise' => 'Noise risk assessment', 'ergo' => preg_match('/advanced/i', $front) ? 'Advanced ergonomics risk assessment' : 'Initial ergonomics risk assessment', 'chemical' => 'Chemical health risk assessment', 'iaq' => 'Indoor air quality assessment', 'prisma' => 'Psychosocial risk assessment', 'other' => pathinfo($filename, PATHINFO_FILENAME)];
        $date = '';
        $dateSource = '';
        $day = '(\d{1,2})(?:st|nd|rd|th)?';
        $month = '(January|February|March|April|May|June|July|August|September|October|November|December)';
        if (preg_match('/(?:date of assessment|assessment date|assessment was conducted on)\s*:?\s*'.$day.'(?:\s*[–—-]\s*\d{1,2})?\s+'.$month.'\s+(\d{4})/iu', $text, $match)) {
            try {
                $date = Carbon::createSafe((int) $match[3], Carbon::parse($match[2])->month, (int) $match[1])->format('Y-m-d');
                $dateSource = $match[0];
            } catch (\Throwable) {
                $date = '';
            }
        }
        $owner = '';
        if (preg_match('/(?:name of trained person|assessor)\s*:?\s*([^\n]{3,150})/i', $text, $match)) {
            $owner = trim($match[1]);
        }
        $overview = '';
        if (preg_match('/(?:Executive Summary|1\.1 Overview)\s*\n(.{80,2500}?)(?=\n\s*(?:\d+\.|Working Area)|$)/is', $text, $match)) {
            $overview = trim($match[1]);
        }
        $items = [];
        // Only prose in an explicit recommendations section becomes a suggestion.
        // Scoring keys and methodology are deliberately excluded.
        if (preg_match('/(?:7\.0 Conclusion and Recommendations for Improvement|\n\s*(?:8|9|10)\.0?\s+Recommendations)\s*\n(.{40,5000}?)(?=\n\s*\d+\.0?\s+[A-Z]|$)/is', $text, $match)) {
            $items[] = ['title' => 'Review report recommendations', 'description' => trim($match[1]), 'priority' => 'Unassigned', 'due' => '', 'status' => 'Open', 'steps' => []];
        }

        return ['title' => $titles[$type], 'type' => $type, 'overview' => $overview, 'assessedOn' => $date, 'due' => '', 'owner' => $owner, 'priority' => 'Unassigned', 'items' => $items, 'dateSource' => $dateSource, 'sourceExcerpt' => mb_substr(trim($text), 0, 18000)];
    }
}
