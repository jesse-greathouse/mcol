<?php

declare(strict_types=1);

namespace App\Http\Procedures;

use Illuminate\Http\Request;
use Sajya\Server\Procedure;

use App\Exceptions\UnknownBotException,
    App\Jobs\CancelRequest,
    App\Models\Bot;

class CancelProcedure extends Procedure
{
    /**
     * @var string
     */
    public static string $name = 'cancel';

    /**
     * Execute the procedure.
     *
     * @param Request $request
     *
     * @return array|string|integer
     */
    public function request(Request $request)
    {
        $id = $request->input('bot');
        $bot = Bot::where('id', $id)->first();
        if (null === $bot) {
            throw new UnknownBotException("Bot: {$id} was not found.");
        }

        CancelRequest::dispatch($bot);

        return [
            'bot' => $bot->toArray(),
        ];
    }
}
