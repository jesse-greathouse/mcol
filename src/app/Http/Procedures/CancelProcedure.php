<?php

declare(strict_types=1);

namespace App\Http\Procedures;

use App\Exceptions\UnknownBotException;
use App\Jobs\CancelRequest;
use App\Models\Bot;
use Illuminate\Http\Request;
use Sajya\Server\Procedure;

/**
 * Class CancelProcedure
 *
 * Handles the cancellation of a bot request.
 */
class CancelProcedure extends Procedure
{
    /**
     * @var string The name of the procedure.
     */
    public static string $name = 'cancel';

    /**
     * Execute the procedure to cancel the bot request.
     *
     * @param  Request  $request  The incoming request.
     * @return array|string|int Response data with bot information or an error.
     *
     * @throws UnknownBotException If the bot with the provided ID cannot be found.
     */
    public function request(Request $request): array|string|int
    {
        $id = $request->input('bot');

        // Fetch bot by ID efficiently and handle not found case
        $bot = Bot::find($id);

        if ($bot === null) {
            throw new UnknownBotException("Bot: {$id} was not found.");
        }

        // Dispatch the cancel request job for the found bot
        CancelRequest::dispatch($bot);

        // Return bot data in array format for view
        return [
            'bot' => $bot->toArray(),
        ];
    }
}
