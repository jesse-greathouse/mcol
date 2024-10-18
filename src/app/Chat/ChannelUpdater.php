<?php

namespace App\Chat;

use App\Models\Channel;

use Jerodev\PhpIrcClient\IrcChannel;

class ChannelUpdater
{

    /**
     * Synchronizes the channel record with live data.
     *
     * @param IrcChannel $ircChannel
     * @return void
     */
    public function update(IrcChannel $ircChannel): void
    {
        $users = $ircChannel->getUsers();
        $userCount = count($users);
        $topic = $ircChannel->getTopic();

        if (null !== $topic && (0 < $userCount)) {
            Channel::updateOrCreate(
                ['name' => $ircChannel->getName()],
                ['topic' => $topic, 'users' => $userCount]
            );
        }
    }
}
