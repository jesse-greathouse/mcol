<?php

namespace App\Media;

use App\Models\Download;

/**
 * Class DownloadCard
 *
 * Generates an SVG representation of a download's progress, including:
 * - The file icon
 * - Bot nameplate
 * - Progress bar (with color-coded fill)
 * - Queue position overlay (if applicable)
 * - Truncated file name
 */
class DownloadCard
{
    /**
     * The Download model instance for the requested file.
     */
    protected Download $download;

    /**
     * The cached progress percentage of the download.
     * This is computed once and reused to avoid redundant calculations.
     *
     * @var int|null Null until computed; then stores the progress percentage (0-100).
     */
    protected ?int $progress = null;

    /**
     * Any custom overload of the progress/queue label.
     *
     * @var string|null Null until computed; then stores the progress color hex value.
     */
    protected ?string $label = null;

    /**
     * The cached value produced by getProgressColor().
     * getProgressColor is an expensive call and this property holds the result.
     *
     * @var string|null Null until computed; then stores the progress color hex value.
     */
    protected ?string $progressColor = null;

    /**
     * Attributes that define various SVG element positions, dimensions, and defaults.
     * Some attributes may be overridden in the constructor.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [
        /**
         * The maximum length of the file name before truncation.
         *
         * @var int
         */
        'max_length' => 60,

        /**
         * X-coordinate of the file icon within the SVG.
         *
         * @var int
         */
        'icon_x' => 60,

        /**
         * Y-coordinate of the file icon within the SVG.
         *
         * @var int
         */
        'icon_y' => 80,

        /**
         * X-coordinate of the progress bar within the SVG.
         *
         * @var int
         */
        'progress_x' => 200,

        /**
         * Y-coordinate of the progress bar within the SVG.
         *
         * @var int
         */
        'progress_y' => 90,

        /**
         * The total width of the progress bar.
         *
         * @var int
         */
        'progress_width' => 500,

        /**
         * The total height of the progress bar.
         *
         * @var int
         */
        'progress_height' => 50,

        // Default colors for progress bar based on status/progress percentage
        'color_queued' => '#ffcc00',  // Queued State
        'color_completed' => '#008000',  // Completed state
        'color_range_0and10' => '#0099e6',  // 0-10%
        'color_range_11and25' => '#3399ff',  // 11-25%
        'color_range_26and50' => '#0099ff',  // 26-50%
        'color_range_51and75' => '#0099cc',  // 51-75%
        'color_range_76and90' => '#009973',  // 76-99%
        'color_range_90and100' => '#00994d',  // 100%
    ];

    /**
     * The default media type used when no specific type is assigned.
     * This ensures a fallback icon is always available.
     *
     * @var string
     */
    const DEFAULT_MEDIA = 'default';

    /**
     * A mapping of media types to their respective icon retrieval methods.
     * This allows dynamic selection of the correct SVG icon for the given media type.
     *
     * @var array<string, string>
     */
    const ICON_MAP = [
        MediaType::PORN => 'getPornIcon',
        MediaType::MOVIE => 'getMovieIcon',
        MediaType::TV_EPISODE => 'getTvEpisodeIcon',
        MediaType::TV_SEASON => 'getTvSeasonIcon',
        MediaType::BOOK => 'getBookIcon',
        MediaType::MUSIC => 'getMusicIcon',
        MediaType::GAME => 'getGameIcon',
        MediaType::APPLICATION => 'getApplicationIcon',
        self::DEFAULT_MEDIA => 'getDefaultIcon',
    ];

    /**
     * Constructs a DownloadCard instance, initializing attributes based on the provided
     * Download object and optional overrides. Attributes follow a three-step precedence:
     *
     * 1. Defaults are initialized within the class (not explicitly shown here).
     * 2. User-provided `$attributes` override default values.
     * 3. Dynamically assigned attributes (such as bot name, icon, etc.) always take final precedence.
     *
     * @param  Download  $download  The Download object.
     * @param  ?string  $label  Optional overloading label.
     * @param  ?array  $attributes  Optional key-value pairs to override default attributes.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the download record is not found.
     */
    public function __construct(Download $download, ?string $label = null, ?array $attributes = [])
    {
        // Overload label if provided.
        if ($label) {
            $this->label = $label;
        }

        // Retrieve the download record or fail if it does not exist
        $this->download = $download;

        // Determine the media type and the corresponding icon function
        $iconMethod = self::ICON_MAP[$this->download->media_type ?? self::DEFAULT_MEDIA];

        // Step 1: Override class defaults with user-provided attributes
        foreach ($attributes as $key => $val) {
            $this->attributes[$key] = $val;
        }

        // Step 2: Assign dynamic attributes, ensuring they always take precedence
        foreach ([
            'bot' => fn () => $this->download->packet->bot->nick,  // Retrieve bot's nickname
            'icon' => fn () => call_user_func([DownloadCard::class, $iconMethod], 60, 60), // Icon
            'color' => fn () => $this->getProgressColor(),  // Determine progress bar color
            'progress_fill' => fn () => $this->getProgressWidth(),  // Calculate progress bar width
            'file_name_trunc' => fn () => $this->truncateFileName(),  // Truncate file name if needed
        ] as $attribute => $handler) {
            $this->attributes[$attribute] = ($handler)(); // Final override
        }
    }

    /**
     * Generates an SVG representation of the download progress Meter.
     *
     * This method constructs an SVG image representing the download progress,
     * including:
     * - **File icon** at a defined position.
     * - **Bot nameplate**
     * - **Progress bar** with dynamically colored fill based on progress.
     * - **File name** above the progress bar.
     *
     * **Returns:**
     * - A **string** containing the full SVG markup.
     *
     * @return string SVG markup representing the download progress UI.
     */
    public function toSvg(): string
    {
        /**
         * TODO: Add dynamic image form values.
         *
         * The h,w,aspect-ratio should be able to be defined as attributes.
         */

        // Extract attributes for cleaner references
        [
            'bot' => $bot,
            'icon' => $icon,
            'color' => $color,
            'icon_x' => $iconX,
            'icon_y' => $iconY,
            'progress_x' => $progressX,
            'progress_y' => $progressY,
            'progress_width' => $progressWidth,
            'progress_height' => $progressHeight,
            'progress_fill' => $progressFillWidth,
            'file_name_trunc' => $fileName,
        ] = $this->attributes;

        // Vert align FileName Label above Progress Bar.
        $fileNameAlignY = $progressY - 10;

        // Adjust nameplate position (5% height offset)
        $nameplateOffset = $this->getNameplateOffset(0.05, 200);
        $nameplateY = 20 - $nameplateOffset;
        $textY = 45 - $nameplateOffset;

        // Start SVG output
        $svg = "<?xml version='1.0' encoding='UTF-8'?>\n";
        $svg .= "<svg xmlns='http://www.w3.org/2000/svg' width='800' height='200' viewBox='0 0 800 200' preserveAspectRatio='xMidYMid meet'>\n";

        // Contains the style information for the card design
        $svg .= $this->addStyleElement();

        // Background with padding and solid border, matching the progress bar's corner curve
        $svg .= "<rect class='greyPinBorder' x='10%' y='10%' width='80%' height='80%' rx='10' ry='10' />\n";

        // ** File Icon **
        $svg .= "<g class='icon' transform='translate($iconX, $iconY)'>\n$icon\n</g>\n";

        // ** Bot Nameplate **
        $svg .= "<rect class='botNameplate' x='25%' y='$nameplateY' width='400' height='40' rx='10' ry='10' />\n";
        $svg .= "<text class='lbl botNameplateLabel' x='27%' y='$textY'>$bot</text>\n";

        // ** Progress Bar **
        $svg .= "<rect class='progressBarFrame' x='$progressX' y='$progressY' width='$progressWidth' height='$progressHeight' rx='10' ry='10' stroke='$color' />\n";
        $svg .= "<rect class='progressBar' x='$progressX' y='$progressY' width='$progressFillWidth' height='$progressHeight' rx='10' ry='10' fill='$color' />\n";

        // ** Queue Position Label Overlay (only if queued) **
        // n / n or ''
        $svg .= $this->addQueuedLabelSvgElement();

        // ** Progress Position Label Overlay (only if incomplete) **
        // nn% or ''
        $svg .= $this->addProgressPercentLabelSvgElement();

        // ** Completed Overlay Label (only if complete) **
        // "Completed" or ''
        $svg .= $this->addCompletionLabelSvgElement();

        // ** File Name Label (Above Progress Bar) **
        $svg .= "<text class='lbl fileNameLabel' x='$progressX' y='$fileNameAlignY'>$fileName</text>\n";

        // Close SVG
        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Returns the dictionary of attributes described in creating this object.
     *
     * @return array<string, mixed>
     */
    protected function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Calculates the vertical offset for the bot nameplate.
     *
     * The nameplate should be **moved upward by 5% of the total height**.
     * This ensures proper alignment within the SVG layout.
     *
     * @return int The computed offset value in pixels.
     */
    protected function getNameplateOffset(float $percent, int $height): int
    {
        return round($percent * $height); // 5% of 200px height = 10px
    }

    /**
     * Truncates the file name if it exceeds the specified maximum length.
     *
     * If the file name length is greater than `$maxLength`, it is truncated in
     * a way that preserves both the beginning and end of the file name, replacing
     * the middle portion with an ellipsis (`...`).
     *
     * Example:
     * ```
     * truncateFileName(10, "very_long_filename.txt")
     * // Output: "ver...txt"
     * ```
     *
     * @param  int|null  $maxLength  The maximum allowed length for the file name.
     *                               If null, the default max length is taken from `$this->attributes['max_length']`.
     * @return string The truncated file name if it exceeds `$maxLength`, otherwise the original file name.
     */
    protected function truncateFileName(?int $maxLength = null): string
    {
        // Retrieve the original file name
        $fileName = $this->download->file_name;

        // Use the provided maxLength or fallback to the class attribute
        $maxLength ??= $this->attributes['max_length'];

        // Truncate only if the file name exceeds the max length
        if (strlen($fileName) > $maxLength) {
            // Calculate the portion of the filename to preserve at both ends
            $halfLength = intdiv($maxLength - 3, 2); // Account for the "..." in the middle
            $fileName = substr($fileName, 0, $halfLength).'...'.substr($fileName, -$halfLength);
        }

        return $fileName;
    }

    /**
     * Calculates the width of the progress bar based on the download's progress percentage.
     *
     * This method determines the progress width by taking the current progress percentage
     * (retrieved from `getProgress()`) and scaling it relative to the predefined maximum width
     * of the progress bar (`$this->attributes['progress_width']`).
     *
     * Formula:
     * ```
     * progress_width = (progress_percentage / 100) * max_progress_width
     * ```
     *
     * Example:
     * ```
     * // Assuming progress is 50% and max width is 500 pixels
     * getProgressWidth()
     * // Output: 250 (since 50% of 500 is 250)
     * ```
     *
     * @return int The calculated width of the progress bar, rounded to the nearest integer.
     */
    protected function getProgressWidth(): int
    {
        // Compute the scaled progress width based on the current progress percentage.
        // The width is determined as a proportion of the total progress bar width.
        return round(($this->getProgress() / 100) * $this->attributes['progress_width'], 2);
    }

    /**
     * Determines the color for the progress bar based on the download's progress status.
     *
     * This method retrieves color values from `$this->attributes`, allowing customization
     * of progress bar colors without modifying the method itself. If no custom attributes
     * are provided, predefined defaults are used.
     *
     * Colors are assigned based on the following conditions:
     * - **Queued downloads (`STATUS_QUEUED`)** → Uses `color_queued`
     * - **Completed downloads (`STATUS_COMPLETED`)** → Uses `color_completed`
     * - **Progress percentage ranges** → Uses corresponding `color_range_XandY` values
     *
     * Expected Attributes:
     * ```
     * 'color_queued'           => '#hexcolor',  // Color for queued downloads
     * 'color_completed'        => '#hexcolor',  // Color for completed downloads
     * 'color_range_0and10'     => '#hexcolor',  // 0-10%
     * 'color_range_11and25'    => '#hexcolor',  // 11-25%
     * 'color_range_26and50'    => '#hexcolor',  // 26-50%
     * 'color_range_51and75'    => '#hexcolor',  // 51-75%
     * 'color_range_76and90'    => '#hexcolor',  // 76-99%
     * 'color_range_90and100'   => '#hexcolor',  // 100%
     * ```
     *
     * Example Usage:
     * ```php
     * getProgressColor(); // Returns the appropriate hex color for progress percentage
     * ```
     *
     * @return string The corresponding hex color code from `$this->attributes`.
     */
    protected function getProgressColor(): string
    {
        // $this->progressColor holds the value so this calculation is only made once.
        if ($this->progressColor === null) {
            $progress = $this->getProgress(); // Get the current progress percentage (nn)%

            [
                'color_queued' => $colorQueued,
                'color_completed' => $colorCompleted,
                'color_range_0and10' => $colorVeryLow,
                'color_range_11and25' => $colorLow,
                'color_range_26and50' => $colorModerate,
                'color_range_51and75' => $colorHigh,
                'color_range_76and90' => $colorVeryHigh,
                'color_range_90and100' => $colorNearComplete,
            ] = $this->attributes;

            // Determine color based on progress percentage
            $this->progressColor = match (true) {
                $this->download->status === Download::STATUS_QUEUED => $colorQueued,
                $this->download->status === Download::STATUS_COMPLETED => $colorCompleted,
                $progress <= 10 => $colorVeryLow,
                $progress <= 25 => $colorLow,
                $progress <= 50 => $colorModerate,
                $progress <= 75 => $colorHigh,
                $progress <= 90 => $colorVeryHigh,
                default => $colorNearComplete,
            };
        }

        return $this->progressColor;
    }

    /**
     * Calculates and returns the download progress percentage.
     *
     * This method determines the progress of a download based on its status:
     * - **Completed (`STATUS_COMPLETED`)** → Always **100%**.
     * - **Incomplete (`STATUS_INCOMPLETE`)** → Calculated as a percentage of `progress_bytes` over `file_size_bytes`.
     * - **Queued (`STATUS_QUEUED`)** → Considered **100%** for UI purposes, as it's in line for processing.
     * - **Default (Unknown status)** → **0%** progress.
     *
     * The progress is cached in the `$this->progress` property to avoid redundant calculations.
     * If the progress has already been computed, the cached value is returned immediately.
     *
     * Example Usage:
     * ```
     * $progress = $this->getProgress(); // Returns an integer from 0 to 100
     * ```
     *
     * @return int The progress percentage, ranging from 0 to 100.
     */
    protected function getProgress(): int
    {
        // If progress is already calculated, return the cached value
        if ($this->progress === null) {
            // Determine progress based on the current download status
            switch ($this->download->status) {
                case Download::STATUS_COMPLETED:
                    $this->progress = 100; // Fully downloaded
                    break;

                case Download::STATUS_INCOMPLETE:
                    // Calculate progress as a percentage of bytes downloaded
                    $position = (is_numeric($this->download->progress_bytes) && $this->download->progress_bytes > 0)
                        ? $this->download->progress_bytes
                        : 1; // Avoid division by zero

                    $this->progress = min(100, max(0, ($position / $this->download->file_size_bytes) * 100));
                    break;

                case Download::STATUS_QUEUED:
                    $this->progress = 100; // Display as fully queued (UI behavior)
                    break;

                default:
                    $this->progress = 0; // Unknown status, assume no progress
            }
        }

        return $this->progress;
    }

    /**
     * Generates a label representing the queue position of the download.
     *
     * This method constructs a queue status string in the format:
     * ```
     * "X / Y"
     * ```
     * Where:
     * - **X (`queued_status`)** is the download's current position in the queue.
     * - **Y (`queued_total`)** is the total number of items in the queue.
     *
     * If either value is **null or missing**, it defaults to `'?'`, indicating that
     * the exact position or total queue size is unknown.
     *
     * Example Scenarios:
     * ```
     * getQueuedLabel(); // Returns "4 / 5" (4th in line out of 5)
     * getQueuedLabel(); // Returns "? / 8" (Unknown position, total queue size is 8)
     * getQueuedLabel(); // Returns "2 / ?" (2nd in line, total queue size unknown)
     * getQueuedLabel(); // Returns "? / ?" (No queue data available)
     * ```
     *
     * @return string A formatted string representing the queue position, e.g., "4 / 5".
     */
    protected function getQueuedLabel(): string
    {
        $queuedPosition = $this->download->queued_status ?? '?';
        $queuedTotal = $this->download->queued_total ?? '?';

        // Format as "X / Y" to represent queue position
        return "$queuedPosition / $queuedTotal";
    }

    /**
     * Appends an SVG `<text>` element for overlaying labels on the progress bar.
     *
     * The label text is centered on the progress bar and uses a **bold, white font**
     * with a **black outline** for visibility. This method is used to generate labels
     * for queued downloads, progress percentages, and completed downloads.
     *
     * @param  string  $text  The label text to display (e.g., "4 / 5", "45%", "Complete").
     * @return string SVG `<text>` element or an empty string if not applicable.
     */
    protected function addProgressLabelSvgElement(string $text): string
    {
        // Extract progress bar dimensions for centering
        [
            'progress_x' => $progressX,
            'progress_y' => $progressY,
            'progress_width' => $progressWidth,
            'progress_height' => $progressHeight,
            'color' => $color,
        ] = $this->attributes;

        // Construct centered SVG text overlay
        return "<text class='lbl progressLabel' stroke='$color' "
            ."x='".($progressX + $progressWidth / 2)."' "
            ."y='".($progressY + $progressHeight / 2 + 12)
            ."' >$text</text>\n";
    }

    /**
     * Appends an SVG `<text>` element to overlay the queue position on the progress bar.
     *
     * @return string SVG `<text>` element or an empty string if not queued.
     */
    protected function addQueuedLabelSvgElement(): string
    {
        // Overload this->label if not null
        $label = $this->label ?? $this->getQueuedLabel();

        return $this->download->status === Download::STATUS_QUEUED
            ? $this->addProgressLabelSvgElement($label)
            : '';
    }

    /**
     * Appends an SVG `<text>` element to overlay the download percentage on the progress bar.
     *
     * @return string SVG `<text>` element or an empty string if the download is not in progress.
     */
    protected function addProgressPercentLabelSvgElement(): string
    {
        // Overload this->label if not null
        $label = $this->label ?? "{$this->getProgress()}%";

        return $this->download->status === Download::STATUS_INCOMPLETE
            ? $this->addProgressLabelSvgElement($label)
            : '';
    }

    /**
     * Appends an SVG `<text>` element to overlay "Complete" on the progress bar.
     *
     * @return string SVG `<text>` element or an empty string if the download is not completed.
     */
    protected function addCompletionLabelSvgElement(): string
    {
        // Overload this->label if not null
        $label = $this->label ?? 'Complete';

        return $this->download->status === Download::STATUS_COMPLETED
            ? $this->addProgressLabelSvgElement($label)
            : '';
    }

    /**
     * Appends a style element to the svg document.
     *
     * @return string SVG `<style>` element.
     */
    public static function addStyleElement(): string
    {
        return <<<EOD
        <style type="text/css">
            .lbl{font-family: Arial, Helvetica, sans-serif;font-style:normal;}
            .greyPinBorder{fill:none;stroke:#D3D3D3;stroke-width:2;}
            .botNameplate{fill:#e6e6e6;stroke:#b3b3b3;stroke-width:3;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:1.5;}
            .botNameplateLabel{fill:#2c2d2e;font-weight:800;filter:drop-shadow(1px 1px 1px rgba(0, 0, 0, 0.1));text-anchor:start;}
            .progressBarFrame{fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:1.5;}
            .progressLabel{fill:#fafafa;font-size:2.4em;opacity:0.9;stroke-linejoin:round;stroke-width:1.5;font-weight:900;text-anchor:middle;filter:drop-shadow(2px 2px 3px rgba(0, 0, 0, 0.4));}
            .fileNameLabel{fill:#444;font-weight:600;text-anchor:start;filter:drop-shadow(1px 1px 1px rgba(136, 134, 134, 0.1));}
        </style>\n
    EOD;
    }

    public static function getPornIcon(): string
    {
        return <<<'EOD'
        <g transform="scale(3)">
        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
        <g id="SVGRepo_iconCarrier">
        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22ZM4.08397 8.37596C4.42862 8.1462 4.89427 8.23933 5.12404 8.58397L6.5 10.6479L7.87596 8.58397C8.10573 8.23933 8.57138 8.1462 8.91603 8.37596C9.12957 8.51832 9.24655 8.75125 9.25 8.98984C9.25345 8.75125 9.37043 8.51832 9.58397 8.37596C9.92862 8.1462 10.3943 8.23933 10.624 8.58397L12 10.6479L13.376 8.58397C13.6057 8.23933 14.0714 8.1462 14.416 8.37596C14.6296 8.51833 14.7466 8.75127 14.75 8.98987C14.7534 8.75127 14.8704 8.51833 15.084 8.37596C15.4286 8.1462 15.8943 8.23933 16.124 8.58397L17.5 10.6479L18.876 8.58397C19.1057 8.23933 19.5714 8.1462 19.916 8.37596C20.2607 8.60573 20.3538 9.07138 20.124 9.41602L18.4014 12L20.124 14.584C20.3538 14.9286 20.2607 15.3943 19.916 15.624C19.5714 15.8538 19.1057 15.7607 18.876 15.416L17.5 13.3521L16.124 15.416C15.8943 15.7607 15.4286 15.8538 15.084 15.624C14.8704 15.4817 14.7534 15.2487 14.75 15.0101C14.7466 15.2487 14.6296 15.4817 14.416 15.624C14.0714 15.8538 13.6057 15.7607 13.376 15.416L12 13.3521L10.624 15.416C10.3943 15.7607 9.92862 15.8538 9.58397 15.624C9.37043 15.4817 9.25345 15.2488 9.25 15.0102C9.24655 15.2488 9.12957 15.4817 8.91603 15.624C8.57138 15.8538 8.10573 15.7607 7.87596 15.416L6.5 13.3521L5.12404 15.416C4.89427 15.7607 4.42862 15.8538 4.08397 15.624C3.73933 15.3943 3.6462 14.9286 3.87596 14.584L5.59861 12L3.87596 9.41602C3.6462 9.07138 3.73933 8.60573 4.08397 8.37596Z" fill="#1C274C"></path>
        <path d="M9.12404 9.41602L7.40139 12L9.12404 14.584C9.20713 14.7086 9.24799 14.8491 9.25 14.9885C9.25202 14.8491 9.29287 14.7086 9.37596 14.584L11.0986 12L9.37596 9.41602C9.29287 9.29139 9.25202 9.15094 9.25 9.01154C9.24799 9.15094 9.20713 9.29139 9.12404 9.41602Z" fill="#1C274C"></path>
        <path d="M14.624 9.41602L12.9014 12L14.624 14.584C14.7071 14.7086 14.748 14.8491 14.75 14.9885C14.752 14.8491 14.7929 14.7086 14.876 14.584L16.5986 12L14.876 9.41602C14.7929 9.29138 14.752 9.15091 14.75 9.0115C14.748 9.15091 14.7071 9.29138 14.624 9.41602Z" fill="#1C274C"></path>
        </g></g>
    EOD;
    }

    public static function getMovieIcon(): string
    {
        return <<<'EOD'
        <g transform="scale(0.15)">
        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
        <g id="SVGRepo_iconCarrier"> <g> <circle cx="256" cy="256" fill="none" r="170.292" stroke="#4b5463" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></circle> <circle cx="256" cy="147.619" fill="none" r="31" stroke="#4b5463" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></circle> <circle cx="256" cy="366.715" fill="none" r="31" stroke="#4b5463" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></circle> <circle cx="149.717" cy="260.431" fill="none" r="31" stroke="#4b5463" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></circle> <circle cx="362.379" cy="260.582" fill="none" r="31" stroke="#4b5463" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></circle> <line fill="none" stroke="#4b5463" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="256" x2="385" y1="426.292" y2="426.292"></line> <circle cx="256" cy="256" fill="#6691d6" r="170.292" stroke="#4b5463" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></circle> <circle cx="256" cy="147.619" fill="#D9DCE1" r="31" stroke="#4b5463" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></circle> <circle cx="256" cy="366.715" fill="#D9DCE1" r="31" stroke="#4b5463" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></circle> <circle cx="149.717" cy="260.431" fill="#D9DCE1" r="31" stroke="#4b5463" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></circle> <circle cx="362.379" cy="260.582" fill="#D9DCE1" r="31" stroke="#4b5463" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></circle> <line fill="none" stroke="#4b5463" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="256" x2="385" y1="426.292" y2="426.292"></line> </g> </g></g>
    EOD;
    }

    public static function getTvSeasonIcon(): string
    {
        return <<<'EOD'
        <g transform="scale(0.15)">
        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
        <g id="SVGRepo_iconCarrier"> <g> <rect fill="#66d668" height="246" stroke="#49654d" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" width="387" x="62.5" y="109.5"></rect> <polygon fill="none" points=" 239.147,189.009 314.477,232.499 239.147,275.991 " stroke="#49654d" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polygon> <rect fill="none" height="246" stroke="#49654d" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" width="387" x="62.5" y="109.5"></rect> <line fill="none" stroke="#49654d" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="127.5" x2="80.5" y1="355.5" y2="402.5"></line> <line fill="none" stroke="#49654d" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="388.5" x2="426.5" y1="355.5" y2="402.5"></line> <polygon fill="#D9DCE1" points=" 239.147,189.009 314.477,232.499 239.147,275.991 " stroke="#49654d" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polygon> <line fill="none" stroke="#49654d" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="127.5" x2="80.5" y1="355.5" y2="402.5"></line> <line fill="none" stroke="#49654d" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="388.5" x2="426.5" y1="355.5" y2="402.5"></line> </g> </g></g>
    EOD;
    }

    public static function getTvEpisodeIcon(): string
    {
        return <<<'EOD'
        <g transform="scale(0.15)">
        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
        <g id="SVGRepo_iconCarrier"> <g> <rect fill="none" height="265.377" stroke="#456e69" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" width="377.98" x="67.01" y="82.061"></rect> <polygon fill="none" points=" 190.956,139.643 321.044,214.75 190.956,289.857 " stroke="#456e69" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polygon> <line fill="none" stroke="#456e69" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="444.99" x2="143" y1="405.939" y2="405.939"></line> <line fill="none" stroke="#456e69" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="100" x2="67.01" y1="405.939" y2="405.939"></line> <path d=" M143,393.939v24c0,6.62-5.37,12-12,12h-19c-6.63,0-12-5.38-12-12v-24c0-6.63,5.37-12,12-12h19 C137.63,381.939,143,387.309,143,393.939z" fill="none" stroke="#456e69" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> <rect fill="#D9DCE1" height="265.377" stroke="#456e69" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" width="377.98" x="67.01" y="82.061"></rect> <polygon fill="#66d4d6" points=" 190.956,139.643 321.044,214.75 190.956,289.857 " stroke="#456e69" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polygon> <line fill="none" stroke="#456e69" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="444.99" x2="143" y1="405.939" y2="405.939"></line> <line fill="none" stroke="#456e69" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="100" x2="67.01" y1="405.939" y2="405.939"></line> <path d=" M143,393.939v24c0,6.62-5.37,12-12,12h-19c-6.63,0-12-5.38-12-12v-24c0-6.63,5.37-12,12-12h19 C137.63,381.939,143,387.309,143,393.939z" fill="none" stroke="#456e69" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> </g> </g></g>
    EOD;
    }

    public static function getBookIcon(): string
    {
        return <<<'EOD'
        <g transform="scale(0.15)">
        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
        <g id="SVGRepo_iconCarrier"> <g> <polyline fill="none" points=" 398,160 398,418 114,418 114,94 307.5,94 " stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polyline> <polygon fill="none" points=" 307.5,94 307.5,160 398,160 " stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polygon> <path d=" M335.5,371c0,6.627-5.373,12-12,12h-135c-6.627,0-12-5.373-12-12V215c0-6.627,5.373-12,12-12h135c6.627,0,12,5.373,12,12V371z" fill="none" stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> <line fill="none" stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="206.875" x2="306.708" y1="247.334" y2="247.334"></line> <line fill="none" stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="206.875" x2="306.708" y1="280.667" y2="280.667"></line> <line fill="none" stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="206.875" x2="306.708" y1="314.001" y2="314.001"></line> <line fill="none" stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="205.292" x2="305.125" y1="347.334" y2="347.334"></line> <line fill="none" stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="145.667" x2="145.667" y1="94" y2="131.333"></line> <line fill="none" stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="180" x2="180" y1="94" y2="131.333"></line> <line fill="none" stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="214.333" x2="214.333" y1="94" y2="131.333"></line> <polyline fill="#D9DCE1" points=" 398,160 398,418 114,418 114,94 307.5,94 " stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polyline> <polygon fill="#af66d6" points=" 307.5,94 307.5,160 398,160 " stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polygon> <path d=" M335.5,371c0,6.627-5.373,12-12,12h-135c-6.627,0-12-5.373-12-12V215c0-6.627,5.373-12,12-12h135c6.627,0,12,5.373,12,12V371z" fill="#af66d6" stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> <line fill="none" stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="206.875" x2="306.708" y1="247.334" y2="247.334"></line> <line fill="none" stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="206.875" x2="306.708" y1="280.667" y2="280.667"></line> <line fill="none" stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="206.875" x2="306.708" y1="314.001" y2="314.001"></line> <line fill="none" stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="205.292" x2="305.125" y1="347.334" y2="347.334"></line> <line fill="none" stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="145.667" x2="145.667" y1="94" y2="131.333"></line> <line fill="none" stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="180" x2="180" y1="94" y2="131.333"></line> <line fill="none" stroke="#5f4d75" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="214.333" x2="214.333" y1="94" y2="131.333"></line> </g> </g></g>
    EOD;
    }

    public static function getMusicIcon(): string
    {
        return <<<'EOD'
        <g transform="scale(0.15)">
        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
        <g id="SVGRepo_iconCarrier"> <g> <circle cx="159.466" cy="353.741" fill="none" r="35.509" stroke="#73512b" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></circle> <polyline fill="none" points=" 194.975,353.741 194.975,184.25 388.043,122.75 388.043,292.241 " stroke="#73512b" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polyline> <circle cx="352.534" cy="292.241" fill="none" r="35.509" stroke="#73512b" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></circle> <line fill="none" stroke="#73512b" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="194.975" x2="388.043" y1="221.917" y2="157.251"></line> <circle cx="159.466" cy="353.741" fill="#e77d04" r="35.509" stroke="#73512b" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></circle> <polyline fill="none" points=" 194.975,353.741 194.975,184.25 388.043,122.75 388.043,292.241 " stroke="#73512b" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polyline> <circle cx="352.534" cy="292.241" fill="#e77d04" r="35.509" stroke="#73512b" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></circle> <line fill="none" stroke="#73512b" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="194.975" x2="388.043" y1="221.917" y2="157.251"></line> </g> </g></g>
    EOD;
    }

    public static function getGameIcon(): string
    {
        return <<<'EOD'
        <g transform="scale(0.15)">
        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
        <g id="SVGRepo_iconCarrier"> <g> <path d=" M410.495,293.495c0,33.32-27.011,60.34-60.33,60.34c-25.15,0-46.71-15.39-55.771-37.26c-2.939-7.11-4.569-14.91-4.569-23.08 c0-33.32,27.02-60.33,60.34-60.33c2.63,0,5.22,0.17,7.76,0.49C387.585,237.465,410.495,262.805,410.495,293.495z" fill="#619424" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> <path d=" M222.175,293.495c0,33.32-27.011,60.34-60.33,60.34c-25.15,0-46.71-15.39-55.771-37.26c-2.939-7.11-4.569-14.91-4.569-23.08 c0-33.32,27.02-60.33,60.34-60.33c2.63,0,5.22,0.17,7.76,0.49C199.265,237.465,222.175,262.805,222.175,293.495z" fill="#619424" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> <polyline fill="none" points=" 163.255,316.575 163.255,293.495 160.425,293.495 " stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polyline> <path d=" M160.425,233.235c0.94-0.051,1.88-0.07,2.83-0.07c33.32,0,60.34,27.01,60.34,60.33c0,8.17-1.62,15.97-4.57,23.08" fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> <line fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="197.255" x2="163.255" y1="293.495" y2="293.495"></line> <line fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="160.425" x2="129.255" y1="293.495" y2="293.495"></line> <line fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="163.255" x2="163.255" y1="327.495" y2="316.575"></line> <line fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="163.255" x2="163.255" y1="293.495" y2="259.495"></line> <line fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="349.996" x2="350.33" y1="264.665" y2="264.665"></line> <line fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="350.163" x2="350.497" y1="317.665" y2="317.665"></line> <line fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="318.091" x2="318.425" y1="293.499" y2="293.499"></line> <line fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="380.591" x2="380.925" y1="293.499" y2="293.499"></line> <path d=" M357.925,233.146v0.51c-2.54-0.32-5.13-0.49-7.76-0.49c-33.32,0-60.34,27.01-60.34,60.33c0,8.17,1.63,15.97,4.569,23.08h-75.37 c2.95-7.11,4.57-14.91,4.57-23.08c0-33.32-27.02-60.33-60.34-60.33c-0.95,0-1.89,0.02-2.83,0.07v-0.09H357.925z" fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> <polyline fill="none" points=" 160.425,293.495 163.255,293.495 163.255,316.575 " stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polyline> <polyline fill="none" points=" 163.255,316.575 163.255,293.495 160.425,293.495 " stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polyline> <path d=" M160.425,233.235c0.94-0.051,1.88-0.07,2.83-0.07c33.32,0,60.34,27.01,60.34,60.33c0,8.17-1.62,15.97-4.57,23.08" fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> <path d=" M410.495,293.495c0,33.32-27.011,60.34-60.33,60.34c-25.15,0-46.71-15.39-55.771-37.26c-2.939-7.11-4.569-14.91-4.569-23.08 c0-33.32,27.02-60.33,60.34-60.33c2.63,0,5.22,0.17,7.76,0.49C387.585,237.465,410.495,262.805,410.495,293.495z" fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> <path d=" M259.175,233.235c0-41.494,33.576-75.07,75.07-75.07" fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> <polyline fill="none" points=" 163.255,316.575 163.255,293.495 160.425,293.495 " stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polyline> <path d=" M160.425,233.235c0.94-0.051,1.88-0.07,2.83-0.07c33.32,0,60.34,27.01,60.34,60.33c0,8.17-1.62,15.97-4.57,23.08" fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> <line fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="197.255" x2="163.255" y1="293.495" y2="293.495"></line> <line fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="160.425" x2="129.255" y1="293.495" y2="293.495"></line> <line fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="163.255" x2="163.255" y1="327.495" y2="316.575"></line> <line fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="163.255" x2="163.255" y1="293.495" y2="259.495"></line> <line fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="349.996" x2="350.33" y1="264.665" y2="264.665"></line> <line fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="350.163" x2="350.497" y1="317.665" y2="317.665"></line> <line fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="318.091" x2="318.425" y1="293.499" y2="293.499"></line> <line fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="380.591" x2="380.925" y1="293.499" y2="293.499"></line> <path d=" M357.925,233.146v0.51c-2.54-0.32-5.13-0.49-7.76-0.49c-33.32,0-60.34,27.01-60.34,60.33c0,8.17,1.63,15.97,4.569,23.08h-75.37 c2.95-7.11,4.57-14.91,4.57-23.08c0-33.32-27.02-60.33-60.34-60.33c-0.95,0-1.89,0.02-2.83,0.07v-0.09H357.925z" fill="#c6f3b9" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> <polyline fill="none" points=" 160.425,293.495 163.255,293.495 163.255,316.575 " stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polyline> <polyline fill="none" points=" 163.255,316.575 163.255,293.495 160.425,293.495 " stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polyline> <path d=" M222.175,293.495c0,33.32-27.011,60.34-60.33,60.34c-25.15,0-46.71-15.39-55.771-37.26c-2.939-7.11-4.569-14.91-4.569-23.08 c0-33.32,27.02-60.33,60.34-60.33c2.63,0,5.22,0.17,7.76,0.49C199.265,237.465,222.175,262.805,222.175,293.495z" fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> <path d=" M259.175,233.235c0-41.494,33.576-75.07,75.07-75.07" fill="none" stroke="#3b5026" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> </g> </g></g>
    EOD;
    }

    public static function getApplicationIcon(): string
    {
        return <<<'EOD'
        <g transform="scale(0.15)">
        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
        <g id="SVGRepo_iconCarrier"> <g> <rect fill="#e4c4c4" height="239.988" stroke="#603e3e" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" width="301.484" x="105.258" y="115.574"></rect> <polygon fill="none" points=" 231.016,192.077 306.344,235.567 231.016,279.06 " stroke="#603e3e" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polygon> <rect fill="none" height="239.988" stroke="#603e3e" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" width="301.484" x="105.258" y="115.574"></rect> <rect fill="none" height="44.998" stroke="#603e3e" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" width="301.484" x="105.258" y="310.564"></rect> <rect fill="none" height="40.863" stroke="#603e3e" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" width="65.332" x="223.334" y="355.563"></rect> <line fill="none" stroke="#603e3e" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="196.014" x2="315.348" y1="396.426" y2="396.426"></line> <polygon fill="#d33636" points=" 231.016,192.077 306.344,235.567 231.016,279.06 " stroke="#603e3e" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></polygon> <rect fill="#d33636" height="44.998" stroke="#603e3e" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" width="301.484" x="105.258" y="310.564"></rect> <rect fill="none" height="40.863" stroke="#603e3e" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" width="65.332" x="223.334" y="355.563"></rect> <line fill="none" stroke="#603e3e" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="196.014" x2="315.348" y1="396.426" y2="396.426"></line> </g> </g>
        </g>
    EOD;
    }

    public static function getDefaultIcon(): string
    {
        return <<<'EOD'
        <g transform="scale(0.15)">
        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
        <g id="SVGRepo_iconCarrier"> <g> <path d=" M466.398,364.662v23.744c0,8.42-6.408,15.264-14.318,15.264H59.92c-7.911,0-14.318-6.844-14.318-15.264v-23.744 c0-8.434,6.408-15.264,14.318-15.264h138.722v4.936c0,7.109,5.417,12.871,12.087,12.871h97.103c6.669,0,12.087-5.762,12.087-12.871 v-4.936h132.16C459.99,349.398,466.398,356.229,466.398,364.662z" fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> <path d=" M424.838,108.33v233.434H319.92v6.289c0,9.063-5.417,16.406-12.087,16.406H210.73c-6.67,0-12.087-7.344-12.087-16.406v-6.289 H87.161V108.33H424.838z" fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="127.833" x2="181.167" y1="162.996" y2="162.996"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="127.833" x2="181.167" y1="196.162" y2="196.162"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="127.833" x2="181.167" y1="229.328" y2="229.328"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="127.833" x2="181.167" y1="262.494" y2="262.494"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="127.833" x2="181.167" y1="295.662" y2="295.662"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="223.833" x2="277.167" y1="229.328" y2="229.328"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="223.833" x2="277.167" y1="262.494" y2="262.494"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="223.833" x2="277.167" y1="295.662" y2="295.662"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="315.167" x2="368.5" y1="262.494" y2="262.494"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="315.167" x2="368.5" y1="295.662" y2="295.662"></line> <path d=" M466.398,364.662v23.744c0,8.42-6.408,15.264-14.318,15.264H59.92c-7.911,0-14.318-6.844-14.318-15.264v-23.744 c0-8.434,6.408-15.264,14.318-15.264h138.722v4.936c0,7.109,5.417,12.871,12.087,12.871h97.103c6.669,0,12.087-5.762,12.087-12.871 v-4.936h132.16C459.99,349.398,466.398,356.229,466.398,364.662z" fill="#75808a" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> <path d=" M424.838,108.33v233.434H319.92v6.289c0,9.063-5.417,16.406-12.087,16.406H210.73c-6.67,0-12.087-7.344-12.087-16.406v-6.289 H87.161V108.33H424.838z" fill="#D9DCE1" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20"></path> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="127.833" x2="181.167" y1="162.996" y2="162.996"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="127.833" x2="181.167" y1="196.162" y2="196.162"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="127.833" x2="181.167" y1="229.328" y2="229.328"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="127.833" x2="181.167" y1="262.494" y2="262.494"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="127.833" x2="181.167" y1="295.662" y2="295.662"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="223.833" x2="277.167" y1="229.328" y2="229.328"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="223.833" x2="277.167" y1="262.494" y2="262.494"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="223.833" x2="277.167" y1="295.662" y2="295.662"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="315.167" x2="368.5" y1="262.494" y2="262.494"></line> <line fill="none" stroke="#474747" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="20" x1="315.167" x2="368.5" y1="295.662" y2="295.662"></line> </g> </g></g>
    EOD;
    }
}
