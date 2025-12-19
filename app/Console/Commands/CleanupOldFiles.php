<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOldFiles extends Command
{
    protected $signature = 'chat:cleanup-files';
    protected $description = 'Delete chat files older than 15 days';

    public function handle()
    {
        $this->info('Starting file cleanup...');

        $cutoffDate = now()->subDays(15);

        $oldMessages = Message::where('message_type', 'file')
            ->whereNotNull('file_path')
            ->where('created_at', '<', $cutoffDate)
            ->get();

        $deletedCount = 0;
        $errorCount = 0;

        foreach ($oldMessages as $message) {
            try {
                if (Storage::exists($message->file_path)) {
                    Storage::delete($message->file_path);
                }

                $message->update([
                    'file_path' => null,
                    'file_name' => 'File expired',
                ]);

                $deletedCount++;
            } catch (\Exception $e) {
                $this->error("Failed to delete file for message {$message->id}: " . $e->getMessage());
                $errorCount++;
            }
        }

        $this->info("Cleanup complete: {$deletedCount} files deleted, {$errorCount} errors");

        return Command::SUCCESS;
    }
}
