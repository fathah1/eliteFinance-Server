<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class LogViewerController extends Controller
{
    public function index()
    {
        return view('logs');
    }

    public function tail(Request $request)
    {
        $lines = (int) $request->query('lines', 200);
        if ($lines < 10) {
            $lines = 10;
        }
        if ($lines > 1000) {
            $lines = 1000;
        }

        $laravelPath = storage_path('logs/laravel.log');
        $apiPath = storage_path('logs/api-requests.log');

        return Response::json([
            'lines' => $lines,
            'laravel' => [
                'path' => $laravelPath,
                'updated_at' => $this->fileMTime($laravelPath),
                'entries' => $this->tailFile($laravelPath, $lines),
            ],
            'api_requests' => [
                'path' => $apiPath,
                'updated_at' => $this->fileMTime($apiPath),
                'entries' => $this->tailFile($apiPath, $lines),
            ],
        ]);
    }

    public function clear()
    {
        $laravelPath = storage_path('logs/laravel.log');
        $apiPath = storage_path('logs/api-requests.log');

        $this->truncateFile($laravelPath);
        $this->truncateFile($apiPath);

        return Response::json(['cleared' => true]);
    }

    private function fileMTime(string $path): ?string
    {
        if (!File::exists($path)) {
            return null;
        }

        return date('c', File::lastModified($path));
    }

    /**
     * Read the last N lines of a file without loading the whole file into memory.
     *
     * @return array<int, string>
     */
    private function tailFile(string $path, int $lines): array
    {
        if (!File::exists($path)) {
            return [];
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }

        $buffer = '';
        $chunkSize = 4096;
        fseek($handle, 0, SEEK_END);
        $position = ftell($handle);

        while ($position > 0 && substr_count($buffer, "\n") <= $lines) {
            $readSize = ($position - $chunkSize) >= 0 ? $chunkSize : $position;
            $position -= $readSize;
            fseek($handle, $position);
            $buffer = fread($handle, $readSize) . $buffer;
        }

        fclose($handle);

        $buffer = trim($buffer);
        if ($buffer === '') {
            return [];
        }

        $allLines = explode("\n", $buffer);
        return array_slice($allLines, -$lines);
    }

    private function truncateFile(string $path): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            return;
        }
        fclose($handle);
    }
}
