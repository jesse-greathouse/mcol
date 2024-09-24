<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\FileExtension;

class FileExtensionSeeder extends Seeder
{
    protected array $extensions = [
        'tar',
        'mkv',
        'avi',
        'mp4',
        'nsp',
        'iso',
        'pdf',
        'pkg',
        'epub',
        'm4a',
        'm4v',
        'exe',
        'dll',
        'rar',
        'mobi',
        'm4b',
        'txt',
        'mp3',
        'wav',
        'flac',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->getExtensions() as $name) {
            FileExtension::factory()->create([
                'name' => $name
            ]);
        }
    }

    /**
     * Get the value of extensions
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }
}
